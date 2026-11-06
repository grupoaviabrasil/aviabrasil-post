<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CriarTabelaLogsPublicacaoMigration extends Migration
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
            'fila_publicacao_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'sucesso' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'mensagem_erro' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('fila_publicacao_id');
        $this->forge->addForeignKey('fila_publicacao_id', 'fila_publicacoes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('logs_publicacao');
    }

    public function down()
    {
        $this->forge->dropTable('logs_publicacao');
    }
}
