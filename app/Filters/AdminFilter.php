<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bloqueia rotas administrativas para quem não tem o papel de administrador.
 * Pressupõe que o AutenticacaoFilter já garantiu que há um usuário logado.
 */
class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');

        if (! $session->get('usuario_admin')) {
            return redirect()->to('/painel')->with('erro', 'Acesso restrito a administradores.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nada a fazer depois da requisição.
    }
}
