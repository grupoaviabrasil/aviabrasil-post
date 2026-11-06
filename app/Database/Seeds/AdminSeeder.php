<?php

namespace App\Database\Seeds;

use App\Models\UsuarioModel;
use CodeIgniter\Database\Seeder;

/**
 * Cria (ou promove) o administrador inicial a partir de ADMIN_EMAIL/ADMIN_SENHA
 * no .env. Rodar uma única vez por ambiente: php spark db:seed AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run()
    {
        $email = env('ADMIN_EMAIL');
        $senha = env('ADMIN_SENHA');

        if (! $email || ! $senha) {
            echo "Defina ADMIN_EMAIL e ADMIN_SENHA no .env antes de rodar este seeder.\n";
            return;
        }

        $usuarioModel = new UsuarioModel();
        $existente    = $usuarioModel->where('email', $email)->first();

        if ($existente) {
            $usuarioModel->update($existente['id'], ['admin' => 1]);
            echo "Usuário existente '{$email}' promovido a administrador.\n";
            return;
        }

        $usuarioModel->insert([
            'nome'            => 'Administrador',
            'email'           => $email,
            'senha_hash'      => password_hash($senha, PASSWORD_DEFAULT),
            'automacao_ativa' => 0,
            'admin'           => 1,
        ]);

        echo "Administrador '{$email}' criado com sucesso.\n";
    }
}
