<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Login extends BaseController
{
    /**
     * Exibe o formulário de login.
     */
    public function index()
    {
        if ($this->usuarioLogado()) {
            return redirect()->to('/painel');
        }

        return view('autenticacao/login');
    }

    /**
     * Processa a autenticação do franquiado.
     */
    public function autenticar()
    {
        $regras = [
            'email' => 'required|valid_email',
            'senha' => 'required',
        ];

        if (! $this->validate($regras)) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->where('email', $this->request->getPost('email'))->first();

        if (! $usuario || ! password_verify($this->request->getPost('senha'), $usuario['senha_hash'])) {
            return redirect()->back()->withInput()->with('erro', 'E-mail ou senha inválidos.');
        }

        $this->session->set([
            'usuario_id'    => $usuario['id'],
            'usuario_nome'  => $usuario['nome'],
            'usuario_email' => $usuario['email'],
            'usuario_admin' => (bool) $usuario['admin'],
        ]);
        $this->session->regenerate();

        return redirect()->to('/painel');
    }

    /**
     * Encerra a sessão do franquiado.
     */
    public function sair()
    {
        $this->session->destroy();

        return redirect()->to('/login')->with('sucesso', 'Sessão encerrada.');
    }
}
