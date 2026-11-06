<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<h1>Excluir minha conta</h1>

<?php if (session()->getFlashdata('erro')): ?>
    <div class="alerta alerta-erro"><?= esc(session()->getFlashdata('erro')) ?></div>
<?php endif; ?>

<p>
    Essa ação é <strong>permanente e irreversível</strong>. Ao confirmar, os
    seguintes dados serão apagados imediatamente:
</p>
<ul class="erros" style="color: var(--cor-texto-suave);">
    <li>Seu cadastro (nome, e-mail e senha)</li>
    <li>A conexão com sua conta do Instagram</li>
    <li>Seu agendamento de postagens</li>
    <li>A fila de publicações pendentes e o histórico de publicações</li>
</ul>

<?= form_open('painel/excluir-conta') ?>
    <label for="senha">Digite sua senha para confirmar</label>
    <input type="password" id="senha" name="senha" required autofocus>

    <button type="submit" class="botao perigo">
        Excluir minha conta permanentemente
    </button>
<?= form_close() ?>

<p class="rodape"><a href="<?= site_url('painel') ?>">Cancelar e voltar ao painel</a></p>

<?= $this->endSection() ?>
