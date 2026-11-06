<?php

namespace App\Models;

use CodeIgniter\Model;

class LogPublicacaoModel extends Model
{
    protected $table         = 'logs_publicacao';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'fila_publicacao_id',
        'sucesso',
        'mensagem_erro',
    ];
}
