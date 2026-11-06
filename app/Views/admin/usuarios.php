<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<h1>Gerenciar Usuários</h1>


<?php if (session()->getFlashdata('sucesso')): ?>
    <div class="alerta alerta-sucesso"><?= esc(session()->getFlashdata('sucesso')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('erro')): ?>
    <div class="alerta alerta-erro"><?= esc(session()->getFlashdata('erro')) ?></div>
<?php endif; ?>

<table class="tabela">
    <thead>
        <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Papel</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= esc($usuario['nome']) ?></td>
                <td><?= esc($usuario['email']) ?></td>
                <td>
                    <?php if ($usuario['admin']): ?>
                        <span class="selo selo-admin">Administrador</span>
                    <?php else: ?>
                        <span class="selo selo-franquiado">Franquiado</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= form_open('admin/usuarios/' . $usuario['id'] . '/alternar-admin') ?>
                        <?php if ($usuario['admin']): ?>
                            <button type="submit" class="btn-icone destrutivo" data-dica="Remover admin">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l4 4 4-4M12 8v8"/></svg>
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn-icone positivo" data-dica="Tornar admin">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 12l-4-4-4 4M12 16V8"/></svg>
                            </button>
                        <?php endif; ?>
                    <?= form_close() ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->endSection() ?>
