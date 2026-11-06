<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<h1>Histórico de Publicações</h1>
<p>Últimas tentativas de publicação no Instagram de todos os franqueados, mais recentes primeiro.</p>

<hr class="divisor">

<div class="linha-config">
    <div>
        <h2><?= $apenasErros ? 'Mostrando apenas erros' : 'Mostrando todas as tentativas' ?></h2>
        <p>Limitado às 200 mais recentes.</p>
    </div>
    <div class="grupo-acoes">
        <?php if ($apenasErros): ?>
            <a href="<?= site_url('admin/publicacoes') ?>" class="botao-link">Ver todas</a>
        <?php else: ?>
            <a href="<?= site_url('admin/publicacoes?apenas_erros=1') ?>" class="botao-link">Ver somente erros</a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($logs)): ?>
    <p class="vazio-midias">
        <?= $apenasErros ? 'Nenhum erro registrado — tudo publicando normalmente.' : 'Nenhuma publicação registrada ainda.' ?>
    </p>
<?php else: ?>
    <table class="tabela">
        <thead>
            <tr>
                <th>Quando</th>
                <th>Franqueado</th>
                <th>Mídia</th>
                <th>Status</th>
                <th>Detalhe do erro</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= esc(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
                    <td><?= esc($log['nome_franquia'] ?: $log['usuario_nome']) ?></td>
                    <td><?= esc($log['nome_arquivo']) ?></td>
                    <td>
                        <?php if ($log['sucesso']): ?>
                            <span class="selo selo-admin">Publicado</span>
                        <?php else: ?>
                            <span class="selo selo-erro">Erro</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $log['mensagem_erro'] ? esc($log['mensagem_erro']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?= $this->endSection() ?>
