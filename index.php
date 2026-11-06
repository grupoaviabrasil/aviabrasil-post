<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<h1>Painel do Franquiado</h1>

<?php if (session()->getFlashdata('sucesso')): ?>
    <div class="alerta alerta-sucesso"><?= esc(session()->getFlashdata('sucesso')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('erro')): ?>
    <div class="alerta alerta-erro"><?= esc(session()->getFlashdata('erro')) ?></div>
<?php endif; ?>

<p>Bem-vindo(a), <strong><?= esc($usuario['nome']) ?></strong> (<?= esc($usuario['email']) ?>)</p>

<hr class="divisor">

<div class="linha-config">
    <div>
        <h2>Conta do Instagram</h2>
        <?php if ($conta): ?>
            <p><span class="selo selo-admin">Conectado</span> — ID Instagram: <?= esc($conta['instagram_user_id']) ?></p>
        <?php else: ?>
            <p><span class="selo selo-franquiado">Não conectado</span></p>
            <details class="requisitos">
                <summary>O que preciso preparar antes de conectar?</summary>
                <div class="conteudo">
                    <ol>
                        <li>
                            Seu Instagram precisa ser uma conta <strong>Business</strong> ou
                            <strong>Creator</strong> — não pode ser uma conta pessoal comum.
                            No app do Instagram: <em>Configurações → Conta → Mudar para
                            conta profissional</em>.
                        </li>
                        <li>
                            Tenha em mãos o login dessa conta do Instagram — você vai
                            usá-lo na tela de autorização que vai aparecer ao clicar em
                            "Conectar Instagram".
                        </li>
                    </ol>
                    <p class="observacao">Não precisa de Página do Facebook nem de conta pessoal vinculada — só a conta profissional do Instagram. Com isso pronto, é só clicar no botão de conectar ao lado.</p>
                </div>
            </details>
        <?php endif; ?>
    </div>
    <div class="grupo-acoes">
        <?php if ($conta): ?>
            <?= form_open('instagram/desconectar') ?>
                <button type="submit" class="btn-icone destrutivo" data-dica="Desconectar Instagram">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M14.5 9.5l-5 5M9.5 9.5l5 5"/></svg>
                </button>
            <?= form_close() ?>
        <?php else: ?>
            <a href="<?= site_url('instagram/conectar') ?>" class="btn-icone" data-dica="Conectar Instagram">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.07 0l1.71-1.71a5 5 0 0 0-7.07-7.07L10 5.94"/><path d="M14 11a5 5 0 0 0-7.07 0L5.22 12.7a5 5 0 0 0 7.07 7.07L14 18.06"/></svg>
            </a>
        <?php endif; ?>
    </div>
</div>

<hr class="divisor">

<div class="linha-config">
    <div>
        <h2>Automação de postagem</h2>
        <?php if ($usuario['automacao_ativa']): ?>
            <p><span class="selo selo-admin">Ativa</span></p>
        <?php else: ?>
            <p><span class="selo selo-franquiado">Pausada</span></p>
        <?php endif; ?>
    </div>
    <div class="grupo-acoes">
        <?php if ($usuario['automacao_ativa']): ?>
            <?= form_open('painel/pausar') ?>
                <button type="submit" class="btn-icone" data-dica="Pausar automação">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
                </button>
            <?= form_close() ?>
        <?php else: ?>
            <?= form_open('painel/retomar') ?>
                <button type="submit" class="btn-icone positivo" data-dica="Retomar automação">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                </button>
            <?= form_close() ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($conta): ?>
<hr class="divisor">

<div class="linha-config">
    <div>
        <h2>Postar agora</h2>
        <p>Publica imediatamente a próxima mídia da rotação, sem esperar o horário agendado.</p>
    </div>
    <div class="grupo-acoes">
        <?= form_open('painel/postar-agora') ?>
            <button type="submit" class="btn-icone com-texto positivo" data-dica="Postar agora">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                Postar Agora
            </button>
        <?= form_close() ?>
    </div>
</div>
<?php endif; ?>

<hr class="divisor">

<div class="linha-config">
    <div>
        <h2>Agendamento</h2>
        <?php if ($agendamento): ?>
            <p>Dias: <?= esc($agendamento['dias_semana']) ?> — Horários: <?= esc($agendamento['horarios']) ?></p>
        <?php else: ?>
            <p>Nenhum agendamento configurado ainda.</p>
        <?php endif; ?>
    </div>
    <div class="grupo-acoes">
        <a href="<?= site_url('agendamento') ?>" class="btn-icone" data-dica="Configurar agendamento">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
        </a>
    </div>
</div>

<hr class="divisor">

<p class="rodape"><a href="<?= site_url('painel/excluir-conta') ?>" style="color: #B42318;">Excluir minha conta</a></p>

<?= $this->endSection() ?>
