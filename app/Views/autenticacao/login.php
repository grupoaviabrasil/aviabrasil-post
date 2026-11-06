<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<h1>Entrar — Avia Brasil</h1>

<?php if (session()->getFlashdata('sucesso')): ?>
    <div class="alerta alerta-sucesso"><?= esc(session()->getFlashdata('sucesso')) ?></div>
<?php endif; ?>

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

<?= form_open('login') ?>
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" required>

    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required>

    <button type="submit">Entrar</button>
<?= form_close() ?>

<p class="rodape">Ainda não tem cadastro? <a href="<?= site_url('cadastro') ?>">Cadastre-se</a></p>

<?= $this->endSection() ?>
