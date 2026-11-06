<?php

namespace App\Libraries;

use App\Models\MidiaModel;
use Config\PastaMidiaCentral;

/**
 * Varre a pasta central de mídia e sincroniza a tabela midias com o que
 * existe fisicamente em disco (imagem + legenda .txt homônima). Usado tanto
 * pelo comando de cron (php spark midia:varrer) quanto pelo botão "Varrer
 * agora" do painel administrativo.
 */
class VarredorMidiaCentral
{
    private PastaMidiaCentral $config;
    private MidiaModel $midiaModel;

    public function __construct()
    {
        $this->config     = config(PastaMidiaCentral::class);
        $this->midiaModel = new MidiaModel();
    }

    /**
     * @return array{caminhoInvalido: bool, novos: int, atualizados: int, removidos: int, semLegenda: list<string>}
     */
    public function executar(): array
    {
        $caminho = $this->config->caminhoAbsoluto;

        if (! is_dir($caminho)) {
            return [
                'caminhoInvalido' => true,
                'novos'           => 0,
                'atualizados'     => 0,
                'removidos'       => 0,
                'semLegenda'      => [],
            ];
        }

        $arquivos = scandir($caminho);
        sort($arquivos, SORT_NATURAL | SORT_FLAG_CASE);

        $encontrados = [];
        $semLegenda  = [];
        $novos       = 0;
        $atualizados = 0;

        foreach ($arquivos as $arquivo) {
            $caminhoCompleto = $caminho . $arquivo;

            if (! is_file($caminhoCompleto)) {
                continue;
            }

            $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));

            if (! in_array($extensao, $this->config->extensoesImagemAceitas, true)) {
                continue;
            }

            $nomeBase       = pathinfo($arquivo, PATHINFO_FILENAME);
            $arquivoLegenda = $caminho . $nomeBase . '.' . $this->config->extensaoLegenda;

            if (! is_file($arquivoLegenda)) {
                $semLegenda[] = $arquivo;
                continue;
            }

            $legenda = trim(file_get_contents($arquivoLegenda));

            if ($legenda === '') {
                $semLegenda[] = $arquivo;
                continue;
            }

            $encontrados[] = $arquivo;
            $existente     = $this->midiaModel->where('nome_arquivo', $arquivo)->first();

            if ($existente) {
                $dados = [];

                if ($existente['legenda'] !== $legenda) {
                    $dados['legenda'] = $legenda;
                }

                if ($existente['status'] === 'removido') {
                    $dados['status'] = 'ativo';
                }

                if (! empty($dados)) {
                    $this->midiaModel->update($existente['id'], $dados);
                    $atualizados++;
                }

                continue;
            }

            $proximaOrdem = (int) ($this->midiaModel->selectMax('ordem')->first()['ordem'] ?? 0) + 1;

            $this->midiaModel->insert([
                'nome_arquivo' => $arquivo,
                'legenda'      => $legenda,
                'ordem'        => $proximaOrdem,
                'status'       => 'ativo',
            ]);
            $novos++;
        }

        // Marca como "removido" (soft delete) os itens que sumiram da pasta.
        // Nunca apaga de verdade: fila_publicacoes/logs podem referenciar a mídia.
        $registrosSumidos = $this->midiaModel
            ->whereNotIn('nome_arquivo', $encontrados ?: [''])
            ->where('status !=', 'removido')
            ->findAll();

        if (! empty($registrosSumidos)) {
            $this->midiaModel
                ->whereIn('id', array_column($registrosSumidos, 'id'))
                ->set(['status' => 'removido'])
                ->update();
        }

        return [
            'caminhoInvalido' => false,
            'novos'           => $novos,
            'atualizados'     => $atualizados,
            'removidos'       => count($registrosSumidos),
            'semLegenda'      => $semLegenda,
        ];
    }
}
