<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Cadastro extends BaseController
{
    /**
     * Exibe o formulário de cadastro do franquiado.
     */
    public function index()
    {
        if ($this->usuarioLogado()) {
            return redirect()->to('/painel');
        }

        return view('autenticacao/cadastro');
    }

    /**
     * Processa o envio do formulário de cadastro.
     */
    public function salvar()
    {
        $usuarioModel = new UsuarioModel();

        $regras = [
            'nome' => 'required|min_length[3]|max_length[150]',
            'email' => 'required|valid_email|max_length[150]|is_unique[usuarios.email]',
            'nome_franquia' => 'permit_empty|max_length[150]',
            'senha' => 'required|min_length[8]',
            'senha_confirmacao' => 'required|matches[senha]',
        ];

        $mensagens = [
            'email' => [
                'is_unique' => 'Este e-mail já está cadastrado.',
            ],
            'senha_confirmacao' => [
                'matches' => 'A confirmação de senha não é igual à senha informada.',
            ],
        ];

        if (! $this->validate($regras, $mensagens)) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $usuarioModel->insert([
            'nome'            => $this->request->getPost('nome'),
            'email'           => $this->request->getPost('email'),
            'senha_hash'      => password_hash($this->request->getPost('senha'), PASSWORD_DEFAULT),
            'nome_franquia'   => $this->request->getPost('nome_franquia'),
            'automacao_ativa' => 0,
        ]);

        return redirect()->to('/login')->with('sucesso', 'Cadastro realizado com sucesso! Faça login para continuar.');
    }
}
