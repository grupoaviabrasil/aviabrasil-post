<?php

namespace App\Controllers;

use App\Models\AgendamentoModel;
use App\Models\ContaInstagramModel;
use App\Models\UsuarioModel;
use App\Services\PublicacaoService;

class PainelFranquiado extends BaseController
{
    /**
     * Painel principal do franquiado: status da automação, conexão com o
     * Instagram e resumo do agendamento.
     */
    public function index(): string
    {
        $usuarioSessao = $this->usuarioLogado();

        $usuario = (new UsuarioModel())->find($usuarioSessao['id']);
        unset($usuario['senha_hash']);
        $conta   = (new ContaInstagramModel())->where('usuario_id', $usuarioSessao['id'])->first();
        $agendamento = (new AgendamentoModel())->where('usuario_id', $usuarioSessao['id'])->first();

        return view('painel/index', [
            'usuario'     => array_merge($usuarioSessao, $usuario),
            'conta'       => $conta,
            'agendamento' => $agendamento,
            'classeCaixa' => 'caixa-larga',
        ]);
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

        return redirect()->to('/painel')->with('sucesso', "Publicado agora: {$midia['nome_arquivo']}.");
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
