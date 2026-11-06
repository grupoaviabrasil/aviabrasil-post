<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<h1>Cadastro de Franquiado</h1>

<?php if (session()->getFlashdata('erro')): ?>
    <div class="alerta alerta-erro"><?= esc(session()->getFlashdata('erro')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('erros')): ?>
    <ul class="erros">
        <?php foreach (session()->getFlashdata('erros') as $erro): ?>
            <li><?= esc($erro) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?= form_open('cadastro') ?>
    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" value="<?= esc(old('nome')) ?>" required>

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" required>

    <label for="nome_franquia">Nome da franquia (opcional)</label>
    <input type="text" id="nome_franquia" name="nome_franquia" value="<?= esc(old('nome_franquia')) ?>">

    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" minlength="8" required>

    <label for="senha_confirmacao">Confirmar senha</label>
    <input type="password" id="senha_confirmacao" name="senha_confirmacao" minlength="8" required>

    <button type="submit">Cadastrar</button>
<?= form_close() ?>

<p class="rodape">Já tem cadastro? <a href="<?= site_url('login') ?>">Entrar</a></p>

<?= $this->endSection() ?>
