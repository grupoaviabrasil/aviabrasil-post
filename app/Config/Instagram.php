<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Credenciais do App Instagram (método "Instagram API with Instagram Login",
 * sem Página do Facebook — ver docs/GUIA_CONEXAO_INSTAGRAM.md).
 * Lidas do .env: instagram.appId / instagram.appSecret / instagram.redirectUri
 */
class Instagram extends BaseConfig
{
    public string $appId;
    public string $appSecret;
    public string $redirectUri;

    /** Permissões solicitadas ao franquiado durante a autorização. */
    public string $scope = 'instagram_business_basic,instagram_business_content_publish';

    public function __construct()
    {
        parent::__construct();

        $this->appId       = (string) env('instagram.appId');
        $this->appSecret   = (string) env('instagram.appSecret');
        $this->redirectUri = (string) env('instagram.redirectUri');
    }
}
