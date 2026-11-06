<?php

namespace App\Models;

use CodeIgniter\Model;

class FilaPublicacaoModel extends Model
{
    protected $table            = 'fila_publicacoes';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'usuario_id',
        'midia_id',
        'data_hora_programada',
        'slot_agendado',
        'status',
    ];
}
