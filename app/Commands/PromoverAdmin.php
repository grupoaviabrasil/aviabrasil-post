<?php

namespace App\Commands;

use App\Models\UsuarioModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Promove um franquiado já cadastrado a administrador (acesso ao painel /admin).
 * Uso: php spark usuarios:promover-admin email@exemplo.com
 */
class PromoverAdmin extends BaseCommand
{
    protected $group       = 'usuarios';
    protected $name        = 'usuarios:promover-admin';
    protected $description = 'Promove um usuário já cadastrado a administrador.';
    protected $usage       = 'usuarios:promover-admin <email>';

    public function run(array $params)
    {
        $email = $params[0] ?? null;

        if (! $email) {
            CLI::error('Uso: php spark usuarios:promover-admin <email>');
            return;
        }

        $usuarioModel = new UsuarioModel();
        $usuario      = $usuarioModel->where('email', $email)->first();

        if (! $usuario) {
            CLI::error("Nenhum usuário encontrado com o e-mail: {$email}");
            return;
        }

        $usuarioModel->update($usuario['id'], ['admin' => 1]);

        CLI::write("'{$usuario['nome']}' ({$email}) agora é administrador.", 'green');
    }
}
