<?php

namespace App\Models;

use CodeIgniter\Model;

class ContaInstagramModel extends Model
{
    protected $table            = 'contas_instagram';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'usuario_id',
        'instagram_user_id',
        'instagram_username',
        'access_token',
        'token_expira_em',
        'status_conexao',
    ];

    /**
     * Consulta o @ da conta na Graph API com o token salvo e grava no
     * banco. Retorna null se a API falhar (o painel cai para o ID).
     */
    public function atualizarUsername(array $conta): ?string
    {
        try {
            $accessToken = service('encrypter')->decrypt(base64_decode($conta['access_token']));
            $resposta    = service('curlrequest', ['http_errors' => false])->get('https://graph.instagram.com/me', [
                'query' => [
                    'fields'       => 'username',
                    'access_token' => $accessToken,
                ],
            ]);
            $dados = json_decode((string) $resposta->getBody(), true);
        } catch (\Throwable $e) {
            log_message('error', 'Erro ao buscar username do Instagram: {0}', [$e]);

            return null;
        }

        if (empty($dados['username'])) {
            log_message('error', 'Falha ao buscar username do Instagram: {0}', [(string) $resposta->getBody()]);

            return null;
        }

        $this->update($conta['id'], ['instagram_username' => $dados['username']]);

        return $dados['username'];
    }
}
