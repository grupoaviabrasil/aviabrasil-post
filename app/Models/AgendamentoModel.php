<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendamentoModel extends Model
{
    /** Onde as publicações saem no Instagram (valor salvo => rótulo na tela). */
    public const FORMATOS = [
        'feed'       => 'Feed',
        'story'      => 'Story',
        'feed_story' => 'Feed + Story',
    ];

    /**
     * Regra de dias/horários por franquiado, incluindo o ponteiro
     * (ultimo_indice_midia) de onde ele está na biblioteca central.
     */
    protected $table            = 'agendamentos';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'usuario_id',
        'dias_semana',
        'horarios',
        'formato_publicacao',
        'ultimo_indice_midia',
    ];
}
