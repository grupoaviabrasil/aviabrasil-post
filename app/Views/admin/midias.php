<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<h1>Biblioteca de Mídia Central</h1>
<p>Imagens e legendas usadas na publicação automática de todos os franqueados.</p>

<?php if (session()->getFlashdata('sucesso')): ?>
    <div class="alerta alerta-sucesso"><?= esc(session()->getFlashdata('sucesso')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('erro')): ?>
    <div class="alerta alerta-erro"><?= esc(session()->getFlashdata('erro')) ?></div>
<?php endif; ?>

<hr class="divisor">

<div class="linha-config">
    <div>
        <h2>Enviar nova mídia</h2>
        <p>Aceita apenas JPEG (exigência da API do Instagram). A legenda é salva junto, como arquivo <code>.txt</code> homônimo.</p>
    </div>
</div>

<?= form_open_multipart('admin/midias/enviar') ?>
    <label for="imagem">Imagem (JPEG)</label>
    <input type="file" id="imagem" name="imagem" accept=".jpg,.jpeg" required>

    <label for="legenda">Legenda</label>
    <textarea id="legenda" name="legenda" rows="3" required style="width:100%; padding:.7rem .85rem; border:1.5px solid var(--cor-borda); border-radius:10px; font-family:inherit; font-size:.95rem; resize:vertical;"></textarea>

    <button type="submit">Enviar mídia</button>
<?= form_close() ?>

<hr class="divisor">

<div class="linha-config">
    <div>
        <h2>Mídias cadastradas</h2>
        <p>Ordem de publicação, da mais antiga para a mais nova.</p>
    </div>
    <div class="grupo-acoes">
        <?= form_open('admin/midias/varrer') ?>
            <button type="submit" class="btn-icone" data-dica="Varrer pasta agora (detecta arquivos enviados por FTP)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
            </button>
        <?= form_close() ?>
    </div>
</div>

<?php if (empty($midias)): ?>
    <p class="vazio-midias">Nenhuma mídia cadastrada ainda. Envie uma acima, ou coloque arquivos na pasta <code>midia_central/</code> e clique em "Varrer pasta agora".</p>
<?php else: ?>
    <div class="grade-midias">
        <?php foreach ($midias as $midia): ?>
            <div class="midia-card">
                <img class="midia-thumb" src="<?= base_url('midia_central/' . rawurlencode($midia['nome_arquivo'])) ?>" alt="<?= esc($midia['nome_arquivo']) ?>" loading="lazy">
                <div class="midia-corpo">
                    <div class="midia-rodape">
                        <?php if ($midia['status'] === 'ativo'): ?>
                            <span class="selo selo-admin">Ativa</span>
                        <?php elseif ($midia['status'] === 'removido'): ?>
                            <span class="selo selo-erro">Removida</span>
                        <?php else: ?>
                            <span class="selo selo-franquiado"><?= esc(ucfirst($midia['status'])) ?></span>
                        <?php endif; ?>
                        <span class="midia-nome" data-dica="<?= esc($midia['nome_arquivo']) ?>">#<?= esc($midia['ordem']) ?></span>
                    </div>

                    <p class="midia-legenda"><?= esc($midia['legenda']) ?></p>

                    <details class="requisitos">
                        <summary>Editar legenda</summary>
                        <div class="conteudo">
                            <?= form_open('admin/midias/' . $midia['id'] . '/legenda') ?>
                                <textarea name="legenda" required><?= esc($midia['legenda']) ?></textarea>
                                <button type="submit">Salvar legenda</button>
                            <?= form_close() ?>
                        </div>
                    </details>

                    <div class="grupo-acoes">
                        <?= form_open('admin/midias/' . $midia['id'] . '/alternar-status') ?>
                            <?php if ($midia['status'] === 'ativo'): ?>
                                <button type="submit" class="btn-icone" data-dica="Remover da rotação">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.8 21.8 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-icone positivo" data-dica="Reativar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            <?php endif; ?>
                        <?= form_close() ?>

                        <?= form_open('admin/midias/' . $midia['id'] . '/excluir') ?>
                            <button type="submit" class="btn-icone destrutivo" data-dica="Excluir">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                            </button>
                        <?= form_close() ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
