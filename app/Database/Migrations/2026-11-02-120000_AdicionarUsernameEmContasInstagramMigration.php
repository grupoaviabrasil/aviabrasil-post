<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Guarda o @ da conta conectada para o painel mostrar qual Instagram
 * está vinculado. Fica nulo para contas antigas até o painel buscá-lo.
 */
class AdicionarUsernameEmContasInstagramMigration extends Migration
{
    public function up()
    {
        $this->forge->addColumn('contas_instagram', [
            'instagram_username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'instagram_user_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('contas_instagram', 'instagram_username');
    }
}
