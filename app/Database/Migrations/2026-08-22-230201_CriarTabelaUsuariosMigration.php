<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CriarTabelaUsuariosMigration extends Migration
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
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'senha_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'nome_franquia' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'automacao_ativa' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('usuarios');
    }

    public function down()
    {
        $this->forge->dropTable('usuarios');
    }
}
