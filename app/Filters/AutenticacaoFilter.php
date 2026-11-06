<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bloqueia o acesso a rotas do franquiado logado para quem não fez login.
 */
class AutenticacaoFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');

        if (! $session->get('usuario_id')) {
            return redirect()->to('/login')->with('erro', 'Faça login para continuar.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nada a fazer depois da requisição.
    }
}
