<?php

namespace App\Services;

use App\Models\AgendamentoModel;
use App\Models\ContaInstagramModel;
use App\Models\FilaPublicacaoModel;
use App\Models\LogPublicacaoModel;
use App\Models\MidiaModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

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
    /** Tempo máximo (s) esperando o Instagram processar o container da mídia. */
    private const ESPERA_MAXIMA_SEGUNDOS = 60;

    /** Intervalo (s) entre consultas ao status do container. */
    private const INTERVALO_CONSULTA_SEGUNDOS = 3;

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
     * Reserva o horário na fila, publica no Instagram, grava o log e avança
     * o ponteiro do franqueado (se a publicação deu certo).
     *
     * @param string|null $slotAgendado Hora cheia ("Y-m-d H:00:00") ao ser
     *   chamado pelo cron; null (agora mesmo) quando é um disparo manual do
     *   botão "Postar Agora".
     *
     * @return array{0: bool, 1: ?string, 2: string}|null [sucesso, mensagem de erro, nome do arquivo publicado],
     *   ou null se o slot já foi reservado por outra execução (nada foi publicado).
     */
    public function publicar(int $usuarioId, array $midia, ?array $agendamento, ?string $slotAgendado = null): ?array
    {
        $filaModel = new FilaPublicacaoModel();
        $filaId    = $this->reservarSlot($filaModel, $usuarioId, $midia, $slotAgendado);

        if ($filaId === null) {
            return null;
        }

        $formato = $agendamento['formato_publicacao'] ?? 'feed';

        try {
            [$sucesso, $mensagemErro, $algumPublicado] = $this->publicarNoInstagram($usuarioId, $midia, $formato);
        } catch (\Throwable $e) {
            $sucesso        = false;
            $algumPublicado = false;
            $mensagemErro   = 'Erro inesperado ao publicar: ' . $e->getMessage();
            log_message('error', 'PublicacaoService::publicar falhou para usuario_id {0}: {1}', [$usuarioId, $e]);
        }

        // Em "Feed + Story", se só um dos dois saiu a mídia já apareceu no
        // perfil: conta como publicada (e avança o ponteiro, para não
        // repetir o post no próximo horário), mas o log registra a falha.
        $filaModel->update($filaId, ['status' => $algumPublicado ? 'publicado' : 'erro']);

        (new LogPublicacaoModel())->insert([
            'fila_publicacao_id' => $filaId,
            'sucesso'            => $sucesso ? 1 : 0,
            'mensagem_erro'      => $mensagemErro,
        ]);

        if ($algumPublicado && $agendamento) {
            (new AgendamentoModel())->update($agendamento['id'], ['ultimo_indice_midia' => $midia['ordem']]);
        }

        return [$sucesso, $mensagemErro, $midia['nome_arquivo']];
    }

    /**
     * Insere a linha da fila ANTES de publicar. A chave única
     * (usuario_id, slot_agendado) garante que, se duas execuções do cron
     * tentarem o mesmo horário ao mesmo tempo, só uma consegue — a outra
     * recebe null e não publica.
     */
    private function reservarSlot(FilaPublicacaoModel $filaModel, int $usuarioId, array $midia, ?string $slotAgendado): ?int
    {
        $erro = null;

        try {
            $filaId = $filaModel->insert([
                'usuario_id'           => $usuarioId,
                'midia_id'             => $midia['id'],
                'data_hora_programada' => $slotAgendado ?? date('Y-m-d H:i:s'),
                'slot_agendado'        => $slotAgendado,
                'status'               => 'pendente',
            ], true);
        } catch (DatabaseException $e) {
            $filaId = false;
            $erro   = $e;
        }

        if ($filaId) {
            return (int) $filaId;
        }

        $slotOcupado = $slotAgendado !== null && $filaModel
            ->where('usuario_id', $usuarioId)
            ->where('slot_agendado', $slotAgendado)
            ->countAllResults() > 0;

        if ($slotOcupado) {
            return null;
        }

        throw $erro ?? new \RuntimeException('Não foi possível registrar a publicação na fila.');
    }

    /**
     * Marca como erro as publicações que ficaram "pendente" porque o
     * processo morreu no meio (ex.: hospedagem derrubou o cron). Não
     * tentamos de novo: não dá para saber se o Instagram chegou a publicar,
     * e repetir poderia duplicar o post.
     *
     * @return int quantidade de itens marcados
     */
    public function encerrarPendentesTravados(int $minutos = 15): int
    {
        $filaModel = new FilaPublicacaoModel();
        $logModel  = new LogPublicacaoModel();

        $travados = $filaModel
            ->where('status', 'pendente')
            ->where('updated_at <', date('Y-m-d H:i:s', time() - $minutos * 60))
            ->findAll();

        foreach ($travados as $item) {
            $filaModel->update($item['id'], ['status' => 'erro']);
            $logModel->insert([
                'fila_publicacao_id' => $item['id'],
                'sucesso'            => 0,
                'mensagem_erro'      => 'O processo de publicação foi interrompido antes de terminar. Confira no Instagram se o post chegou a sair.',
            ]);
        }

        return count($travados);
    }

    /**
     * Publica de fato via Instagram Graph API (graph.instagram.com), método
     * "Instagram API with Instagram Login" — ver docs/GUIA_CONEXAO_INSTAGRAM.md —
     * no(s) formato(s) escolhido(s) pelo franqueado.
     *
     * @param string $formato 'feed', 'story' ou 'feed_story' (ver AgendamentoModel::FORMATOS)
     *
     * @return array{0: bool, 1: ?string, 2: bool} [tudo publicado, mensagem de erro, algum publicado]
     */
    private function publicarNoInstagram(int $usuarioId, array $midia, string $formato): array
    {
        // O processamento + espera podem passar do limite padrão do PHP no
        // botão "Postar Agora" (web); no cron (CLI) isso já é ilimitado.
        // 120 s por destino: "Feed + Story" faz o fluxo completo duas vezes.
        @set_time_limit($formato === 'feed_story' ? 240 : 120);

        $contaModel = new ContaInstagramModel();
        $conta      = $contaModel->where('usuario_id', $usuarioId)->first();

        if (! $conta || $conta['status_conexao'] !== 'conectado') {
            return [false, 'Franquiado não tem uma conta do Instagram conectada.', false];
        }

        $accessToken = service('encrypter')->decrypt(base64_decode($conta['access_token']));
        $accessToken = $this->renovarTokenSeNecessario($conta, $accessToken, $contaModel);

        $cliente = service('curlrequest', ['http_errors' => false, 'timeout' => 30]);

        // rawurlencode no nome do arquivo: espaços/acentos quebram a URL e o
        // Instagram não consegue baixar a imagem.
        $imageUrl = base_url('midia_central/' . rawurlencode($midia['nome_arquivo']));

        // Pré-checagem: o Instagram precisa baixar a imagem da internet, sem
        // login, e aceita apenas JPEG. Falhar aqui dá uma mensagem clara.
        $erroImagem = $this->validarImagemPublica($cliente, $imageUrl);
        if ($erroImagem !== null) {
            return [false, $erroImagem, false];
        }

        // A API não aceita legenda em Stories: lá vai só a imagem.
        $envios = [
            'feed'  => ['image_url' => $imageUrl, 'caption' => $midia['legenda'] ?? ''],
            'story' => ['image_url' => $imageUrl, 'media_type' => 'STORIES'],
        ];
        $destinos = $formato === 'feed_story' ? ['feed', 'story'] : [$formato === 'story' ? 'story' : 'feed'];

        $erros      = [];
        $publicados = [];

        foreach ($destinos as $destino) {
            $erro = $this->criarEPublicarContainer($cliente, $conta['instagram_user_id'], $accessToken, $envios[$destino]);

            if ($erro === null) {
                $publicados[] = $destino;
            } else {
                $erros[] = (count($destinos) > 1 ? ucfirst($destino) . ': ' : '') . $erro;
            }
        }

        if ($erros === []) {
            return [true, null, true];
        }

        $mensagem = implode(' | ', $erros);
        if ($publicados !== []) {
            $mensagem = ucfirst(implode(' e ', $publicados)) . ' publicado, mas houve falha — ' . $mensagem;
        }

        return [false, $mensagem, $publicados !== []];
    }

    /**
     * Fluxo correto da Meta para um container (feed ou Story): 1) cria o
     * container, 2) ESPERA o status FINISHED, 3) só então chama media_publish.
     *
     * @return string|null mensagem de erro, ou null se publicou
     */
    private function criarEPublicarContainer($cliente, string $instagramUserId, string $accessToken, array $parametros): ?string
    {
        // 1) Cria o container da mídia.
        $respostaContainer = $cliente->post("https://graph.instagram.com/{$instagramUserId}/media", [
            'form_params' => $parametros + ['access_token' => $accessToken],
        ]);

        $dadosContainer = json_decode((string) $respostaContainer->getBody(), true);

        if (empty($dadosContainer['id'])) {
            return 'Falha ao criar container: ' . $this->mensagemDeErro($dadosContainer, $respostaContainer);
        }

        $containerId = $dadosContainer['id'];

        // 2) Espera o Instagram terminar de processar a mídia.
        [$pronto, $erroEspera] = $this->aguardarContainerPronto($cliente, $containerId, $accessToken);
        if (! $pronto) {
            return $erroEspera;
        }

        // 3) Publica.
        $respostaPublicar = $cliente->post("https://graph.instagram.com/{$instagramUserId}/media_publish", [
            'form_params' => [
                'creation_id'  => $containerId,
                'access_token' => $accessToken,
            ],
        ]);

        $dadosPublicar = json_decode((string) $respostaPublicar->getBody(), true);

        // Rede de segurança: mesmo com FINISHED, a Meta às vezes responde
        // "Media ID is not available" (subcódigo 2207027) no primeiro
        // media_publish. Uma nova tentativa após alguns segundos resolve.
        for ($tentativa = 1; empty($dadosPublicar['id']) && $tentativa <= 3 && $this->erroMidiaNaoDisponivel($dadosPublicar); $tentativa++) {
            sleep(5);

            $respostaPublicar = $cliente->post("https://graph.instagram.com/{$instagramUserId}/media_publish", [
                'form_params' => [
                    'creation_id'  => $containerId,
                    'access_token' => $accessToken,
                ],
            ]);

            $dadosPublicar = json_decode((string) $respostaPublicar->getBody(), true);
        }

        if (empty($dadosPublicar['id'])) {
            return 'Falha ao publicar: ' . $this->mensagemDeErro($dadosPublicar, $respostaPublicar);
        }

        return null;
    }

    /**
     * Consulta o status do container até FINISHED (ou erro/tempo esgotado).
     *
     * @return array{0: bool, 1: ?string} [pronto, mensagem de erro]
     */
    private function aguardarContainerPronto($cliente, string $containerId, string $accessToken): array
    {
        $inicio = time();

        do {
            $resposta = $cliente->get("https://graph.instagram.com/{$containerId}", [
                'query' => [
                    'fields'       => 'status_code,status',
                    'access_token' => $accessToken,
                ],
            ]);

            $dados  = json_decode((string) $resposta->getBody(), true);
            $status = $dados['status_code'] ?? null;

            if ($status === 'FINISHED') {
                return [true, null];
            }

            if ($status === 'ERROR' || $status === 'EXPIRED') {
                $detalhe = $dados['status'] ?? $status;

                return [false, "O Instagram rejeitou a mídia (status {$status}): {$detalhe}"];
            }

            // IN_PROGRESS (ou resposta transitória sem status): espera e tenta de novo.
            sleep(self::INTERVALO_CONSULTA_SEGUNDOS);
        } while ((time() - $inicio) < self::ESPERA_MAXIMA_SEGUNDOS);

        return [false, 'Tempo esgotado esperando o Instagram processar a mídia (último status: ' . ($status ?? 'desconhecido') . ').'];
    }

    /**
     * Confere se a URL da imagem abre sem login e é JPEG (formato aceito
     * pelo Instagram para publicação de imagem).
     */
    private function validarImagemPublica($cliente, string $imageUrl): ?string
    {
        try {
            $resposta = $cliente->get($imageUrl, ['http_errors' => false, 'timeout' => 20]);
        } catch (\Throwable $e) {
            return 'Não foi possível acessar a imagem pela URL pública (' . $imageUrl . '): ' . $e->getMessage();
        }

        if ($resposta->getStatusCode() !== 200) {
            return 'A imagem não está acessível publicamente (HTTP ' . $resposta->getStatusCode() . '): ' . $imageUrl;
        }

        $tipo = strtolower($resposta->getHeaderLine('Content-Type'));

        if ($tipo !== '' && strpos($tipo, 'image/') === 0 && strpos($tipo, 'jpeg') === false) {
            return "O Instagram só aceita JPEG para publicar imagem, mas o arquivo é '{$tipo}': " . $imageUrl;
        }

        return null;
    }

    private function erroMidiaNaoDisponivel(?array $dados): bool
    {
        if (! $dados || empty($dados['error'])) {
            return false;
        }

        return (int) ($dados['error']['error_subcode'] ?? 0) === 2207027
            || (int) ($dados['error']['code'] ?? 0) === 9007
            || stripos($dados['error']['message'] ?? '', 'not available') !== false;
    }

    private function mensagemDeErro(?array $dados, $resposta): string
    {
        return $dados['error']['message'] ?? (string) $resposta->getBody();
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
