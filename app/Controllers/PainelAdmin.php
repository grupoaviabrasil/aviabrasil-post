<?php

namespace App\Controllers;

use App\Libraries\VarredorMidiaCentral;
use App\Models\FilaPublicacaoModel;
use App\Models\MidiaModel;
use App\Models\UsuarioModel;
use Config\Database;
use Config\PastaMidiaCentral;

class PainelAdmin extends BaseController
{
    /**
     * Visão geral da matriz: franquiados, status de automação, volume de posts.
     */
    public function index(): string
    {
        return view('admin/index', ['classeCaixa' => 'caixa-larga']);
    }

    /**
     * Histórico de tentativas de publicação (sucesso e erro) de todos os
     * franqueados, mais recentes primeiro — dá visibilidade quando a
     * automação falha (token expirado, erro da API do Instagram etc.),
     * já que isso hoje só existia na tabela logs_publicacao.
     */
    public function publicacoes(): string
    {
        $apenasErros = (bool) $this->request->getGet('apenas_erros');

        $consulta = Database::connect()->table('logs_publicacao')
            ->select('logs_publicacao.id, logs_publicacao.sucesso, logs_publicacao.mensagem_erro, logs_publicacao.created_at, fila_publicacoes.data_hora_programada, usuarios.nome as usuario_nome, usuarios.nome_franquia, midias.nome_arquivo')
            ->join('fila_publicacoes', 'fila_publicacoes.id = logs_publicacao.fila_publicacao_id')
            ->join('usuarios', 'usuarios.id = fila_publicacoes.usuario_id')
            ->join('midias', 'midias.id = fila_publicacoes.midia_id')
            ->orderBy('logs_publicacao.created_at', 'DESC')
            ->limit(200);

        if ($apenasErros) {
            $consulta->where('logs_publicacao.sucesso', 0);
        }

        return view('admin/publicacoes', [
            'logs'        => $consulta->get()->getResultArray(),
            'apenasErros' => $apenasErros,
            'classeCaixa' => 'caixa-larga',
        ]);
    }

    /**
     * Lista os itens detectados na pasta central de mídia e seu status.
     */
    public function midias(): string
    {
        $midiaModel = new MidiaModel();

        return view('admin/midias', [
            'midias'      => $midiaModel->orderBy('ordem', 'ASC')->findAll(),
            'classeCaixa' => 'caixa-larga',
        ]);
    }

    /**
     * Dispara manualmente a mesma varredura do cron (php spark midia:varrer)
     * — útil depois de subir arquivos novos por FTP, sem precisar de acesso
     * ao Terminal/SSH.
     */
    public function midiaVarrer()
    {
        $resultado = (new VarredorMidiaCentral())->executar();

        if ($resultado['caminhoInvalido']) {
            return redirect()->to('/admin/midias')->with('erro', 'Pasta midia_central/ não encontrada no servidor.');
        }

        $mensagem = sprintf(
            'Varredura concluída — %d novo(s), %d atualizado(s), %d marcado(s) como removido(s).',
            $resultado['novos'],
            $resultado['atualizados'],
            $resultado['removidos']
        );

        if (! empty($resultado['semLegenda'])) {
            $mensagem .= ' Ignorados por falta de legenda: ' . implode(', ', $resultado['semLegenda']) . '.';
        }

        return redirect()->to('/admin/midias')->with('sucesso', $mensagem);
    }

    /**
     * Recebe upload de uma nova mídia pelo painel: salva a imagem e a
     * legenda (.txt homônima) na pasta central e registra no banco — sem
     * precisar de FTP.
     */
    public function midiaEnviar()
    {
        $regras = [
            'imagem'  => 'uploaded[imagem]|ext_in[imagem,jpg,jpeg]|max_size[imagem,8192]',
            'legenda' => 'required',
        ];

        if (! $this->validate($regras)) {
            return redirect()->to('/admin/midias')->with('erro', implode(' ', $this->validator->getErrors()));
        }

        $config = config(PastaMidiaCentral::class);

        if (! is_dir($config->caminhoAbsoluto)) {
            return redirect()->to('/admin/midias')->with('erro', 'Pasta midia_central/ não encontrada no servidor.');
        }

        $arquivo  = $this->request->getFile('imagem');
        $extensao = strtolower(pathinfo($arquivo->getClientName(), PATHINFO_EXTENSION));
        $nomeBase = mb_strtolower(pathinfo($arquivo->getClientName(), PATHINFO_FILENAME));
        $nomeBase = preg_replace('/[^a-z0-9\-_]+/', '-', $nomeBase);
        $nomeBase = trim($nomeBase, '-') ?: 'midia';

        $nomeArquivo = $nomeBase . '.' . $extensao;
        $sufixo      = 1;

        while (is_file($config->caminhoAbsoluto . $nomeArquivo)) {
            $sufixo++;
            $nomeArquivo = $nomeBase . '-' . $sufixo . '.' . $extensao;
        }

        $arquivo->move($config->caminhoAbsoluto, $nomeArquivo);

        $nomeBaseFinal = pathinfo($nomeArquivo, PATHINFO_FILENAME);
        file_put_contents(
            $config->caminhoAbsoluto . $nomeBaseFinal . '.' . $config->extensaoLegenda,
            trim((string) $this->request->getPost('legenda'))
        );

        (new VarredorMidiaCentral())->executar();

        return redirect()->to('/admin/midias')->with('sucesso', "Mídia \"{$nomeArquivo}\" enviada com sucesso.");
    }

    /**
     * Ativa/remove uma mídia da rotação de publicação manualmente, sem
     * mexer no arquivo físico.
     */
    public function midiaAlternarStatus(int $id)
    {
        $midiaModel = new MidiaModel();
        $midia      = $midiaModel->find($id);

        if (! $midia) {
            return redirect()->to('/admin/midias')->with('erro', 'Mídia não encontrada.');
        }

        $novoStatus = $midia['status'] === 'ativo' ? 'removido' : 'ativo';
        $midiaModel->update($id, ['status' => $novoStatus]);

        $mensagem = $novoStatus === 'ativo' ? 'Mídia reativada.' : 'Mídia removida da rotação.';

        return redirect()->to('/admin/midias')->with('sucesso', $mensagem);
    }

    /**
     * Edita a legenda pelo painel — grava tanto no banco quanto no arquivo
     * .txt físico, para não ser sobrescrita na próxima varredura.
     */
    public function midiaEditarLegenda(int $id)
    {
        $midiaModel = new MidiaModel();
        $midia      = $midiaModel->find($id);

        if (! $midia) {
            return redirect()->to('/admin/midias')->with('erro', 'Mídia não encontrada.');
        }

        $legenda = trim((string) $this->request->getPost('legenda'));

        if ($legenda === '') {
            return redirect()->to('/admin/midias')->with('erro', 'A legenda não pode ficar vazia.');
        }

        $midiaModel->update($id, ['legenda' => $legenda]);

        $config   = config(PastaMidiaCentral::class);
        $nomeBase = pathinfo($midia['nome_arquivo'], PATHINFO_FILENAME);
        file_put_contents($config->caminhoAbsoluto . $nomeBase . '.' . $config->extensaoLegenda, $legenda);

        return redirect()->to('/admin/midias')->with('sucesso', 'Legenda atualizada.');
    }

    /**
     * Apaga os arquivos físicos (imagem + legenda) e o registro no banco —
     * exceto se a mídia já foi usada em alguma publicação, caso em que o
     * registro é mantido (marcado como removido) para preservar o
     * histórico em fila_publicacoes/logs_publicacao.
     */
    public function midiaExcluir(int $id)
    {
        $midiaModel = new MidiaModel();
        $midia      = $midiaModel->find($id);

        if (! $midia) {
            return redirect()->to('/admin/midias')->with('erro', 'Mídia não encontrada.');
        }

        $config     = config(PastaMidiaCentral::class);
        $nomeBase   = pathinfo($midia['nome_arquivo'], PATHINFO_FILENAME);
        $caminhoImg = $config->caminhoAbsoluto . $midia['nome_arquivo'];
        $caminhoTxt = $config->caminhoAbsoluto . $nomeBase . '.' . $config->extensaoLegenda;

        if (is_file($caminhoImg)) {
            unlink($caminhoImg);
        }

        if (is_file($caminhoTxt)) {
            unlink($caminhoTxt);
        }

        $emUso = (new FilaPublicacaoModel())->where('midia_id', $id)->countAllResults() > 0;

        if ($emUso) {
            $midiaModel->update($id, ['status' => 'removido']);

            return redirect()->to('/admin/midias')->with('sucesso', 'Arquivo apagado. O registro foi mantido (removido da rotação) porque já foi usado em publicações.');
        }

        $midiaModel->delete($id);

        return redirect()->to('/admin/midias')->with('sucesso', 'Mídia excluída permanentemente.');
    }

    /**
     * Lista todos os usuários, permitindo promover/rebaixar administradores
     * diretamente pela interface — sem precisar de acesso ao servidor.
     */
    public function usuarios(): string
    {
        $usuarioModel = new UsuarioModel();

        return view('admin/usuarios', [
            'usuarios'    => $usuarioModel->orderBy('nome')->findAll(),
            'classeCaixa' => 'caixa-larga',
        ]);
    }

    /**
     * Promove/rebaixa um usuário a administrador. Nunca deixa o sistema
     * sem nenhum administrador (evitaria precisar de CLI de novo).
     */
    public function alternarAdmin(int $id)
    {
        $usuarioModel = new UsuarioModel();
        $usuario      = $usuarioModel->find($id);

        if (! $usuario) {
            return redirect()->to('/admin/usuarios')->with('erro', 'Usuário não encontrado.');
        }

        $vaiRebaixar = (bool) $usuario['admin'];

        if ($vaiRebaixar) {
            $totalAdmins = $usuarioModel->where('admin', 1)->countAllResults();

            if ($totalAdmins <= 1) {
                return redirect()->to('/admin/usuarios')->with('erro', 'Não é possível remover o último administrador do sistema.');
            }
        }

        $usuarioModel->update($id, ['admin' => $vaiRebaixar ? 0 : 1]);

        $mensagem = $vaiRebaixar
            ? "'{$usuario['nome']}' não é mais administrador."
            : "'{$usuario['nome']}' agora é administrador.";

        return redirect()->to('/admin/usuarios')->with('sucesso', $mensagem);
    }
}
