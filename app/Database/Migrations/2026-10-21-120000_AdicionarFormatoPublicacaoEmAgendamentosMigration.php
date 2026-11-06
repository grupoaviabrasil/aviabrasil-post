<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cada franqueado escolhe onde as publicações saem: no feed, como Story
 * ou nos dois. O padrão "feed" mantém o comportamento de quem já tinha
 * agendamento antes desta opção existir.
 */
class AdicionarFormatoPublicacaoEmAgendamentosMigration extends Migration
{
    public function up()
    {
        $this->forge->addColumn('agendamentos', [
            'formato_publicacao' => [
                'type'       => 'ENUM',
                'constraint' => ['feed', 'story', 'feed_story'],
                'default'    => 'feed',
                'null'       => false,
                'after'      => 'horarios',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('agendamentos', 'formato_publicacao');
    }
}
