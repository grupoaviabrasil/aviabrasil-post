<?php

namespace App\Services;

use App\Models\AgendamentoModel;
use App\Models\ContaInstagramModel;
use App\Models\FilaPublicacaoModel;
use App\Models\LogPublicacaoModel;
use App\Models\MidiaModel;

/**
 * Lógica de publicação compartilhada entre o motor de cron
 * (app/Commands/PublicarPosts.php) e o botão "Postar Agora" do painel
 * (App\Controllers\PainelFranquiado::postarAgora) — ambos escolhem a
 * próxima mídia pelo ponteiro do franqueado, publicam no Instagram e
 * registram fila/log/ponteiro da mesma forma; só muda quem decide
 * QUANDO chamar.
 */
class PublicacaoService
{
    /**
     * Pega a próxima mídia ativa da biblioteca central a partir do
     * ponteiro do franqueado, recomeçando do início se ele chegou ao fim.
     */
    public function selecionarProximaMidia(int $ultimoIndiceMidia): ?array
    {
        $midiaModel = new MidiaModel();

        $midia = $midiaModel
            ->where('status', 'ativo')
            ->where('ordem >', $ultimoIndiceMidia)
            ->orderBy('ordem', 'ASC')
            ->first();

        return $midia ?? $midiaModel->where('status', 'ativo')->orderBy('ordem', 'ASC')->first();
    }

    /**
     * Registra a fila, publica no Instagram, grava o log e avança o
     * ponteiro do franqueado (se a publicação deu certo).
     *
     * @param string|null $dataHoraProgramada Hora cheia ("Y-m-d H:00:00") ao
     *   ser chamado pelo cron, para dedup; null (agora mesmo) quando é um
     *   disparo manual do botão "Postar Agora".
     *
     * @return array{0: bool, 1: ?string, 2: string} [sucesso, mensagem de erro, nome do arquivo publicado]
     */
    public function publicar(int $usuarioId, array $midia, ?array $agendamento, ?string $dataHoraProgramada = null): array
    {
        $filaModel = new FilaPublicacaoModel();
        $filaId    = $filaModel->insert([
            'usuario_id'           => $usuarioId,
            'midia_id'             => $midia['id'],
            'data_hora_programada' => $dataHoraProgramada ?? date('Y-m-d H:i:s'),
            'status'               => 'pendente',
        ], true);

        try {
            [$sucesso, $mensagemErro] = $this->publicarNoInstagram($usuarioId, $midia);
        } catch (\Throwable $e) {
            $sucesso      = false;
            $mensagemErro = 'Erro inesperado ao publicar: ' . $e->getMessage();
            log_message('error', 'PublicacaoService::publicar falhou para usuario_id {0}: {1}', [$usuarioId, $e]);
        }

        $filaModel->update($filaId, ['status' => $sucesso ? 'publicado' : 'erro']);

        (new LogPublicacaoModel())->insert([
            'fila_publicacao_id' => $filaId,
            'sucesso'            => $sucesso ? 1 : 0,
            'mensagem_erro'      => $mensagemErro,
        ]);

        if ($sucesso && $agendamento) {
            (new AgendamentoModel())->update($agendamento['id'], ['ultimo_indice_midia' => $midia['ordem']]);
        }

        return [$sucesso, $mensagemErro, $midia['nome_arquivo']];
    }

    /**
     * Publica de fato via Instagram Graph API (graph.instagram.com), método
     * "Instagram API with Instagram Login" — ver docs/GUIA_CONEXAO_INSTAGRAM.md.
     *
     * @return array{0: bool, 1: ?string} [sucesso, mensagem de erro]
     */
    private function publicarNoInstagram(int $usuarioId, array $midia): array
    {
        $contaModel = new ContaInstagramModel();
        $conta      = $contaModel->where('usuario_id', $usuarioId)->first();

        if (! $conta || $conta['status_conexao'] !== 'conectado') {
            return [false, 'Franquiado não tem uma conta do Instagram conectada.'];
        }

        $accessToken = service('encrypter')->decrypt(base64_decode($conta['access_token']));
        $accessToken = $this->renovarTokenSeNecessario($conta, $accessToken, $contaModel);

        $cliente  = service('curlrequest', ['http_errors' => false]);
        $imageUrl = base_url('midia_central/' . $midia['nome_arquivo']);

        $respostaContainer = $cliente->post("https://graph.instagram.com/{$conta['instagram_user_id']}/media", [
            'form_params' => [
                'image_url'    => $imageUrl,
                'caption'      => $midia['legenda'],
                'access_token' => $accessToken,
            ],
        ]);

        $dadosContainer = json_decode((string) $respostaContainer->getBody(), true);

        if (empty($dadosContainer['id'])) {
            return [false, 'Falha ao criar container: ' . ($dadosContainer['error']['message'] ?? (string) $respostaContainer->getBody())];
        }

        $respostaPublicar = $cliente->post("https://graph.instagram.com/{$conta['instagram_user_id']}/media_publish", [
            'form_params' => [
                'creation_id'  => $dadosContainer['id'],
                'access_token' => $accessToken,
            ],
        ]);

        $dadosPublicar = json_decode((string) $respostaPublicar->getBody(), true);

        if (empty($dadosPublicar['id'])) {
            return [false, 'Falha ao publicar: ' . ($dadosPublicar['error']['message'] ?? (string) $respostaPublicar->getBody())];
        }

        return [true, null];
    }

    /**
     * Renova o token de longa duração quando ele estiver perto de expirar
     * (a Meta exige que o token tenha pelo menos 24h para poder ser
     * renovado — se ainda for muito novo, a renovação falha silenciosamente
     * e seguimos usando o token atual, que ainda é válido).
     */
    private function renovarTokenSeNecessario(array $conta, string $accessToken, ContaInstagramModel $contaModel): string
    {
        $diasRestantes = (strtotime($conta['token_expira_em']) - time()) / 86400;

        if ($diasRestantes > 10) {
            return $accessToken;
        }

        $cliente  = service('curlrequest', ['http_errors' => false]);
        $resposta = $cliente->get('https://graph.instagram.com/refresh_access_token', [
            'query' => [
                'grant_type'   => 'ig_refresh_token',
                'access_token' => $accessToken,
            ],
        ]);

        $dados = json_decode((string) $resposta->getBody(), true);

        if (empty($dados['access_token'])) {
            return $accessToken;
        }

        $expiraEmSegundos = (int) ($dados['expires_in'] ?? 60 * 24 * 60 * 60);

        $contaModel->update($conta['id'], [
            'access_token'    => base64_encode(service('encrypter')->encrypt($dados['access_token'])),
            'token_expira_em' => date('Y-m-d H:i:s', time() + $expiraEmSegundos),
        ]);

        return $dados['access_token'];
    }
}
