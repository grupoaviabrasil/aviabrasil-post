<?php

namespace App\Controllers;

use App\Models\AgendamentoModel;

class Agendamento extends BaseController
{
    private const DIAS_VALIDOS = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];

    /**
     * Exibe a tela de configuração da regra de dias/horários do franquiado.
     */
    public function index(): string
    {
        $agendamentoModel = new AgendamentoModel();
        $usuario = $this->usuarioLogado();

        $agendamento = $agendamentoModel->where('usuario_id', $usuario['id'])->first();

        return view('painel/agendamento', [
            'agendamento' => $agendamento,
            'classeCaixa' => 'caixa-larga',
        ]);
    }

    /**
     * Salva/atualiza a regra de agendamento do franquiado logado.
     */
    public function salvar()
    {
        $usuario = $this->usuarioLogado();

        $regras = [
            'dias' => 'required',
            'horarios' => 'required',
            'formato_publicacao' => 'required|in_list[' . implode(',', array_keys(AgendamentoModel::FORMATOS)) . ']',
        ];

        if (! $this->validate($regras)) {
            return redirect()->to('/agendamento')->withInput()->with('erros', $this->validator->getErrors());
        }

        $diasEnviados = array_intersect((array) $this->request->getPost('dias'), self::DIAS_VALIDOS);

        if (empty($diasEnviados)) {
            return redirect()->to('/agendamento')->withInput()->with('erros', ['dias' => 'Selecione ao menos um dia da semana.']);
        }

        // O motor de publicação roda 1x por hora no servidor (cron), então só
        // faz sentido agendar em horas cheias — não há precisão de minutos.
        $horariosEnviados = array_filter(array_map('trim', explode(',', $this->request->getPost('horarios'))));
        $horariosInvalidos = array_filter($horariosEnviados, static fn ($h) => ! preg_match('/^([01]\d|2[0-3]):00$/', $h));

        if (empty($horariosEnviados) || ! empty($horariosInvalidos)) {
            return redirect()->to('/agendamento')->withInput()->with('erros', [
                'horarios' => 'Informe horas cheias separadas por vírgula, ex: 09:00, 18:00 (o sistema roda 1x por hora, não há precisão de minutos).',
            ]);
        }

        $agendamentoModel = new AgendamentoModel();
        $existente = $agendamentoModel->where('usuario_id', $usuario['id'])->first();

        $dados = [
            'usuario_id'         => $usuario['id'],
            'dias_semana'        => implode(',', $diasEnviados),
            'horarios'           => implode(',', $horariosEnviados),
            'formato_publicacao' => $this->request->getPost('formato_publicacao'),
        ];

        if ($existente) {
            $agendamentoModel->update($existente['id'], $dados);
        } else {
            $dados['ultimo_indice_midia'] = 0;
            $agendamentoModel->insert($dados);
        }

        // Volta para a linha do tempo do painel, onde a etapa aparece marcada.
        return redirect()->to('/painel')->with('sucesso', 'Agendamento salvo com sucesso.');
    }
}
