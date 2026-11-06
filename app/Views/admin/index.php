<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<h1>Painel Administrativo</h1>
<p>Visão geral dos franqueados (a implementar).</p>

<hr class="divisor">

<div class="grupo-acoes">
    <a href="<?= site_url('admin/usuarios') ?>" class="btn-icone" data-dica="Gerenciar usuários">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </a>
    <a href="<?= site_url('admin/midias') ?>" class="btn-icone" data-dica="Biblioteca de mídia">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
    </a>
    <a href="<?= site_url('admin/publicacoes') ?>" class="btn-icone" data-dica="Histórico de publicações">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
    </a>
</div>

<?= $this->endSection() ?>
