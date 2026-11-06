<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CriarTabelaMidiasMigration extends Migration
{
    /**
     * Biblioteca CENTRAL de mídia — compartilhada entre todos os
     * franqueados, alimentada pela varredura de public/midia_central/.
     */
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nome_arquivo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'legenda' => [
                'type' => 'TEXT',
            ],
            'ordem' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pendente', 'ativo', 'removido'],
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
        $this->forge->addUniqueKey('nome_arquivo');
        $this->forge->addKey('ordem');
        $this->forge->createTable('midias');
    }

    public function down()
    {
        $this->forge->dropTable('midias');
    }
}
