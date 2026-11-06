<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdicionarAdminEmUsuariosMigration extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usuarios', [
            'admin' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'automacao_ativa',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('usuarios', 'admin');
    }
}
