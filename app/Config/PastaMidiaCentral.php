<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuração da pasta central de mídia mantida pela matriz Avia Brasil.
 * Fica dentro de /public pois a API do Instagram exige URL pública para
 * buscar a imagem antes de publicar.
 */
class PastaMidiaCentral extends BaseConfig
{
    /**
     * Caminho absoluto no servidor até a pasta central de mídia.
     */
    public string $caminhoAbsoluto = FCPATH . 'midia_central/';

    /**
     * Extensões de imagem aceitas na varredura. A Instagram Graph API só
     * aceita JPEG na publicação — por isso PNG não entra aqui (evita
     * cadastrar mídia que vai falhar na hora de publicar de verdade).
     */
    public array $extensoesImagemAceitas = ['jpg', 'jpeg'];

    /**
     * Extensão do arquivo de legenda associado a cada imagem (mesmo nome).
     */
    public string $extensaoLegenda = 'txt';
}
