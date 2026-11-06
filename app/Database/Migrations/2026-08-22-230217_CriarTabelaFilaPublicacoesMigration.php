<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CriarTabelaFilaPublicacoesMigration extends Migration
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
            'midia_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'data_hora_programada' => [
                'type' => 'DATETIME',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pendente', 'publicado', 'erro', 'cancelado'],
                'default'    => 'pendente',
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
        $this->forge->addKey(['usuario_id', 'status', 'data_hora_programada']);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('midia_id', 'midias', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('fila_publicacoes');
    }

    public function down()
    {
        $this->forge->dropTable('fila_publicacoes');
    }
}
