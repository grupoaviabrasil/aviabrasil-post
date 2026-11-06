<?php

namespace App\Commands;

use App\Libraries\VarredorMidiaCentral;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\PastaMidiaCentral;

/**
 * Varre a pasta pública midia_central/, detecta imagens novas (com legenda
 * .txt homônima) e registra na tabela midias. Deve rodar via cron a cada
 * poucos minutos: php spark midia:varrer
 */
class VarrerPastaCentral extends BaseCommand
{
    protected $group       = 'midia';
    protected $name        = 'midia:varrer';
    protected $description = 'Varre a pasta central de mídia e registra conteúdo novo.';

    public function run(array $params)
    {
        $resultado = (new VarredorMidiaCentral())->executar();

        if ($resultado['caminhoInvalido']) {
            $caminho = config(PastaMidiaCentral::class)->caminhoAbsoluto;
            CLI::error("Pasta não encontrada: {$caminho}");
            return;
        }

        CLI::write('Varredura concluída.', 'green');
        CLI::write(sprintf(
            'Novos: %d | Atualizados: %d | Marcados como removidos: %d',
            $resultado['novos'],
            $resultado['atualizados'],
            $resultado['removidos']
        ));

        if (! empty($resultado['semLegenda'])) {
            CLI::write('Imagens ignoradas por falta de legenda (.txt homônima) ou legenda vazia:', 'yellow');
            foreach ($resultado['semLegenda'] as $arquivo) {
                CLI::write(" - {$arquivo}", 'yellow');
            }
        }
    }
}
