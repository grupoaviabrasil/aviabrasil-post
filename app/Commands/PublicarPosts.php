<?php

namespace App\Commands;

use App\Models\FilaPublicacaoModel;
use App\Services\PublicacaoService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Motor de publicação: para cada franquiado com automacao_ativa = true,
 * verifica se a HORA atual bate com algum horário da regra de agendamento
 * dele, monta o registro em fila_publicacoes com a próxima mídia central
 * (conforme o ponteiro dele), tenta publicar no Instagram e grava o log.
 *
 * Roda via cron a cada 15 minutos: a comparação é por HORA CHEIA e cada
 * franqueado ocupa um slot único (usuario_id + hora) na fila, então só a
 * primeira execução da hora publica — as seguintes só pegam quem ainda
 * não foi processado (ex.: se a anterior foi interrompida no meio).
 *
 * Uma trava de arquivo impede duas execuções simultâneas: se a anterior
 * ainda estiver rodando, a nova sai sem fazer nada.
 *
 * Opção --agora "Y-m-d H:i" (com espaço, não "=") permite simular um horário específico para
 * testes, sem precisar esperar o relógio real bater na hora agendada.
 */
class PublicarPosts extends BaseCommand
{
    protected $group       = 'posts';
    protected $name        = 'posts:publicar';
    protected $description = 'Verifica agendamentos vencidos e publica no Instagram dos franqueados ativos.';
    protected $usage       = 'posts:publicar [--agora "Y-m-d H:i"]';
    protected $options     = [
        '--agora' => 'Simula a data/hora atual (formato "Y-m-d H:i"), útil para testes.',
    ];

    private const DIAS_SEMANA_POR_ISO = [
        1 => 'seg', 2 => 'ter', 3 => 'qua', 4 => 'qui', 5 => 'sex', 6 => 'sab', 7 => 'dom',
    ];

    /** @var resource|null Mantido aberto até o fim do processo para segurar a trava. */
    private $trava;

    public function run(array $params)
    {
        if (! $this->obterTrava()) {
            CLI::write('Outra execução de posts:publicar ainda está rodando — saindo.', 'yellow');

            return;
        }

        $agoraTexto = CLI::getOption('agora');
        $agora      = $agoraTexto ? new \DateTime($agoraTexto) : new \DateTime();

        $diaAtual     = self::DIAS_SEMANA_POR_ISO[(int) $agora->format('N')];
        $horarioAtual = $agora->format('H:00'); // hora cheia — granularidade do agendamento
        $horaReferencia = $agora->format('Y-m-d H:00:00'); // chave de deduplicação por hora

        CLI::write("Verificando agendamentos para {$diaAtual} {$horarioAtual}...", 'yellow');

        $db = Database::connect();
        $agendamentosAtivos = $db->table('agendamentos')
            ->select('agendamentos.*, usuarios.nome as usuario_nome')
            ->join('usuarios', 'usuarios.id = agendamentos.usuario_id')
            ->where('usuarios.automacao_ativa', 1)
            ->get()
            ->getResultArray();

        $filaModel = new FilaPublicacaoModel();
        $service   = new PublicacaoService();

        $travados = $service->encerrarPendentesTravados();
        if ($travados > 0) {
            CLI::write("{$travados} publicação(ões) interrompida(s) anteriormente marcada(s) como erro.", 'red');
        }

        $processados = 0;

        foreach ($agendamentosAtivos as $agendamento) {
            $dias     = explode(',', $agendamento['dias_semana']);
            $horarios = explode(',', $agendamento['horarios']);

            if (! in_array($diaAtual, $dias, true) || ! in_array($horarioAtual, $horarios, true)) {
                continue;
            }

            // Atalho barato: franqueado já processado nesta hora. A garantia
            // real contra duplicidade é a chave única, dentro do publicar().
            $jaExiste = $filaModel
                ->where('usuario_id', $agendamento['usuario_id'])
                ->where('slot_agendado', $horaReferencia)
                ->first();

            if ($jaExiste) {
                continue;
            }

            $midia = $service->selecionarProximaMidia((int) $agendamento['ultimo_indice_midia']);

            if (! $midia) {
                CLI::write("  [{$agendamento['usuario_nome']}] sem mídia ativa na biblioteca central — nada a publicar.", 'red');
                continue;
            }

            // Uma falha de rede/API para um franquiado não pode derrubar o
            // processamento dos demais franquiados no mesmo lote (o service
            // já isola exceções por dentro de publicar()).
            $resultado = $service->publicar($agendamento['usuario_id'], $midia, $agendamento, $horaReferencia);

            if ($resultado === null) {
                continue; // outra execução reservou este horário primeiro
            }

            [$sucesso, $mensagemErro, $nomeArquivo] = $resultado;

            $status = $sucesso ? 'green' : 'red';
            CLI::write("  [{$agendamento['usuario_nome']}] {$nomeArquivo} → " . ($sucesso ? 'publicado' : "erro: {$mensagemErro}"), $status);
            $processados++;
        }

        CLI::write($processados > 0 ? "Concluído: {$processados} agendamento(s) processado(s)." : 'Nenhum agendamento vencido nesta hora.', 'green');
    }

    /**
     * Trava exclusiva via flock: o sistema operacional libera sozinho se o
     * processo morrer, então não existe trava "esquecida" para limpar.
     */
    private function obterTrava(): bool
    {
        $this->trava = fopen(WRITEPATH . 'posts_publicar.lock', 'c');

        return $this->trava !== false && flock($this->trava, LOCK_EX | LOCK_NB);
    }
}
