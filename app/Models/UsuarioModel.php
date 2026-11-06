<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'nome',
        'email',
        'senha_hash',
        'nome_franquia',
        'automacao_ativa',
        'admin',
    ];
}
