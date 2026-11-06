<?php

namespace App\Models;

use CodeIgniter\Model;

class MidiaModel extends Model
{
    /**
     * Biblioteca CENTRAL de mídia — compartilhada entre todos os franqueados,
     * alimentada pela varredura da pasta pública midia_central/.
     */
    protected $table            = 'midias';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'nome_arquivo',
        'legenda',
        'ordem',
        'status',
    ];
}
