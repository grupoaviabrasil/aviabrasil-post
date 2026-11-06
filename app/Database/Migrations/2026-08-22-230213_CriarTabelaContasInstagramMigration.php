<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CriarTabelaContasInstagramMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'usuario_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'instagram_user_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'access_token' => [
                'type' => 'TEXT',
            ],
            'token_expira_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status_conexao' => [
                'type'       => 'ENUM',
                'constraint' => ['conectado', 'desconectado', 'erro'],
                'default'    => 'conectado',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('usuario_id');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('contas_instagram');
    }

    public function down()
    {
        $this->forge->dropTable('contas_instagram');
    }
}
