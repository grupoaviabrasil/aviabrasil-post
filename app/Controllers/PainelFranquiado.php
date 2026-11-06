<?php

namespace App\Controllers;

use App\Models\AgendamentoModel;
use App\Models\ContaInstagramModel;
use App\Models\FilaPublicacaoModel;
use App\Models\UsuarioModel;
use App\Services\PublicacaoService;

class PainelFranquiado extends BaseController
{
    private const DIAS_SEMANA_POR_ISO = [
        1 => 'seg', 2 => 'ter', 3 => 'qua', 4 => 'qui', 5 => 'sex', 6 => 'sab', 7 => 'dom',
    ];

    /**
     * Painel principal do franquiado, em forma de linha do tempo: cada
     * etapa (conta, Instagram, agendamento, automação, 1ª publicação) é
     * marcada como concluída a partir do estado real no banco.
     */
    public function index(): string
    {
        $usuarioSessao = $this->usuarioLogado();

        $usuario = (new UsuarioModel())->find($usuarioSessao['id']);
        unset($usuario['senha_hash']);
        $conta   = (new ContaInstagramModel())->where('usuario_id', $usuarioSessao['id'])->first();
        $agendamento = (new AgendamentoModel())->where('usuario_id', $usuarioSessao['id'])->first();

        $ultimasPublicacoes = (new FilaPublicacaoModel())
            ->select('fila_publicacoes.*, midias.nome_arquivo, logs_publicacao.mensagem_erro')
            ->join('midias', 'midias.id = fila_publicacoes.midia_id', 'left')
            ->join('logs_publicacao', 'logs_publicacao.fila_publicacao_id = fila_publicacoes.id', 'left')
            ->where('fila_publicacoes.usuario_id', $usuarioSessao['id'])
            ->orderBy('fila_publicacoes.id', 'DESC')
            ->limit(5)
            ->find();

        $primeiraPublicacao = (new FilaPublicacaoModel())
            ->where('usuario_id', $usuarioSessao['id'])
            ->where('status', 'publicado')
            ->orderBy('id', 'ASC')
            ->first();

        $contaConectada = $conta && $conta['status_conexao'] === 'conectado';

        // Contas conectadas antes de guardarmos o @ são completadas aqui.
        if ($contaConectada && empty($conta['instagram_username'])) {
            $conta['instagram_username'] = (new ContaInstagramModel())->atualizarUsername($conta);
        }
        $proximaPostagem = ($contaConectada && $usuario['automacao_ativa'] && $agendamento)
            ? $this->calcularProximaPostagem($agendamento)
            : null;

        return view('painel/index', [
            'usuario'            => array_merge($usuarioSessao, $usuario),
            'conta'              => $contaConectada ? $conta : null,
            'agendamento'        => $agendamento,
            'ultimasPublicacoes' => $ultimasPublicacoes,
            'primeiraPublicacao' => $primeiraPublicacao,
            'proximaPostagem'    => $proximaPostagem,
            'classeCaixa'        => 'caixa-larga',
        ]);
    }

    /**
     * Próxima hora cheia que bate com os dias/horários do agendamento.
     * O cron roda por volta do minuto 10 de cada hora (ver
     * docs/GUIA_DEPLOY_CPANEL.md), então até esse minuto a hora atual
     * ainda conta como "próxima".
     */
    private function calcularProximaPostagem(array $agendamento): ?\DateTime
    {
        $dias     = explode(',', $agendamento['dias_semana']);
        $horarios = explode(',', $agendamento['horarios']);

        $candidato = new \DateTime(date('Y-m-d H:00:00'));
        if ((int) date('i') >= 10) {
            $candidato->modify('+1 hour');
        }

        for ($i = 0; $i < 24 * 8; $i++) {
            $dia = self::DIAS_SEMANA_POR_ISO[(int) $candidato->format('N')];
            if (in_array($dia, $dias, true) && in_array($candidato->format('H:00'), $horarios, true)) {
                return $candidato;
            }
            $candidato->modify('+1 hour');
        }

        return null;
    }

    /**
     * Pausa a automação do franquiado logado (automacao_ativa = false).
     */
    public function pausar()
    {
        $usuario = $this->usuarioLogado();
        (new UsuarioModel())->update($usuario['id'], ['automacao_ativa' => 0]);

        return redirect()->to('/painel')->with('sucesso', 'Automação pausada.');
    }

    /**
     * Retoma a automação do franquiado logado (automacao_ativa = true).
     */
    public function retomar()
    {
        $usuario = $this->usuarioLogado();
        (new UsuarioModel())->update($usuario['id'], ['automacao_ativa' => 1]);

        return redirect()->to('/painel')->with('sucesso', 'Automação retomada.');
    }

    /**
     * Publica imediatamente a próxima mídia da rotação do franqueado
     * logado, sem esperar o cron horário — usa o mesmo ponteiro e a mesma
     * lógica de publicação do motor de agendamento (PublicacaoService),
     * só que disparada na hora em vez de aguardar a hora cheia agendada.
     */
    public function postarAgora()
    {
        $usuario = $this->usuarioLogado();

        $conta = (new ContaInstagramModel())->where('usuario_id', $usuario['id'])->first();

        if (! $conta || $conta['status_conexao'] !== 'conectado') {
            return redirect()->to('/painel')->with('erro', 'Conecte sua conta do Instagram antes de postar.');
        }

        $agendamento = (new AgendamentoModel())->where('usuario_id', $usuario['id'])->first();
        $service     = new PublicacaoService();

        $midia = $service->selecionarProximaMidia((int) ($agendamento['ultimo_indice_midia'] ?? 0));

        if (! $midia) {
            return redirect()->to('/painel')->with('erro', 'Nenhuma mídia ativa disponível na biblioteca central.');
        }

        [$sucesso, $mensagemErro] = $service->publicar($usuario['id'], $midia, $agendamento);

        if (! $sucesso) {
            return redirect()->to('/painel')->with('erro', 'Falha ao postar agora: ' . $mensagemErro);
        }

        $formato = AgendamentoModel::FORMATOS[$agendamento['formato_publicacao'] ?? 'feed'] ?? 'Feed';

        return redirect()->to('/painel')->with('sucesso', "Publicado agora ({$formato}): {$midia['nome_arquivo']}.");
    }

    /**
     * Tela de confirmação para excluir a própria conta.
     */
    public function confirmarExclusao(): string
    {
        return view('painel/excluir_conta', [
            'usuario'     => $this->usuarioLogado(),
            'classeCaixa' => 'caixa-larga',
        ]);
    }

    /**
     * Exclui permanentemente a conta do franquiado logado, mediante
     * confirmação de senha. A exclusão em cascata (conta do Instagram,
     * agendamento e fila de publicações) é feita pelas chaves
     * estrangeiras ON DELETE CASCADE no banco.
     */
    public function excluirConta()
    {
        $usuario = $this->usuarioLogado();

        if (! $this->validate(['senha' => 'required'])) {
            return redirect()->back()->with('erro', 'Informe sua senha para confirmar a exclusão.');
        }

        $usuarioModel = new UsuarioModel();
        $registro = $usuarioModel->find($usuario['id']);

        if (! $registro || ! password_verify($this->request->getPost('senha'), $registro['senha_hash'])) {
            return redirect()->back()->with('erro', 'Senha incorreta.');
        }

        $usuarioModel->delete($usuario['id']);
        $this->session->destroy();

        return redirect()->to('/login')->with('sucesso', 'Sua conta foi excluída permanentemente.');
    }
}
