<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CriarTabelaAgendamentosMigration extends Migration
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
            'dias_semana' => [
                'type'    => 'VARCHAR',
                // lista separada por vírgula, ex: "seg,qua,sex"
                'constraint' => 100,
            ],
            'horarios' => [
                'type' => 'TEXT',
                // lista separada por vírgula, ex: "09:00,18:00"
            ],
            'ordem_publicacao' => [
                'type'       => 'ENUM',
                'constraint' => ['sequencial', 'aleatoria'],
                'default'    => 'sequencial',
            ],
            'ultimo_indice_midia' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
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
        $this->forge->addUniqueKey('usuario_id');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('agendamentos');
    }

    public function down()
    {
        $this->forge->dropTable('agendamentos');
    }
}
