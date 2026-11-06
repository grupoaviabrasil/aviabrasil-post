<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<?php
$diasLabel   = ['seg' => 'Seg', 'ter' => 'Ter', 'qua' => 'Qua', 'qui' => 'Qui', 'sex' => 'Sex', 'sab' => 'Sáb', 'dom' => 'Dom'];
$diasExtenso = [1 => 'segunda', 2 => 'terça', 3 => 'quarta', 4 => 'quinta', 5 => 'sexta', 6 => 'sábado', 7 => 'domingo'];

$automacaoAtiva = (bool) $usuario['automacao_ativa'];

// Cada etapa é marcada a partir do estado real no banco — nada é marcado à mão.
$feitas = [
    'conta'       => true,
    'instagram'   => (bool) $conta,
    'agendamento' => (bool) $agendamento,
    'automacao'   => $automacaoAtiva,
    'publicacao'  => (bool) $primeiraPublicacao,
];
$totalFeitas = count(array_filter($feitas));
$tudoPronto  = $totalFeitas === count($feitas);
$etapaAtual  = array_search(false, $feitas, true); // primeira etapa pendente (ou false)

$classeEtapa = static function (string $chave) use ($feitas, $etapaAtual): string {
    if ($feitas[$chave]) {
        return 'feita';
    }
    return $chave === $etapaAtual ? 'atual' : 'pendente';
};

$seloEtapa = static function (string $chave) use ($classeEtapa): string {
    return match ($classeEtapa($chave)) {
        'feita'  => '<span class="selo selo-admin">✓ Concluída</span>',
        'atual'  => '<span class="selo selo-atual">Próxima etapa</span>',
        default  => '<span class="selo selo-franquiado">Pendente</span>',
    };
};

$formatarDataHora = static function (?string $valor): string {
    return $valor ? date('d/m/Y \à\s H:i', strtotime($valor)) : '';
};
?>

<div class="painel-topo">
    <div>
        <span class="kicker">Passo a passo</span>
        <?php if ($tudoPronto): ?>
            <h1>Tudo pronto. <em>Seu Instagram está no automático.</em></h1>
            <p class="painel-sub">Todas as etapas foram concluídas. Acompanhe abaixo as publicações.</p>
        <?php else: ?>
            <h1>Olá, <?= esc(strtok($usuario['nome'], ' ')) ?>! <em>Vamos colocar seu Instagram no automático.</em></h1>
            <p class="painel-sub">Conclua as etapas abaixo. Cada uma é marcada sozinha assim que você termina.</p>
        <?php endif; ?>
    </div>
    <div class="progresso" aria-label="<?= $totalFeitas ?> de <?= count($feitas) ?> etapas concluídas">
        <div><b><?= $totalFeitas ?></b><span>de <?= count($feitas) ?> etapas</span></div>
        <div class="progresso-trilha">
            <?php foreach ($feitas as $feita): ?>
                <i class="<?= $feita ? 'feito' : '' ?>"></i>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('sucesso')): ?>
    <div class="alerta alerta-sucesso"><?= esc(session()->getFlashdata('sucesso')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('erro')): ?>
    <div class="alerta alerta-erro"><?= esc(session()->getFlashdata('erro')) ?></div>
<?php endif; ?>

<?php if ($proximaPostagem): ?>
    <div class="status-vivo">
        <div class="status-vivo-texto">
            <span class="ponto-vivo" aria-hidden="true"></span>
            <div>
                <small>Próxima postagem</small>
                <b><?= esc(ucfirst($diasExtenso[(int) $proximaPostagem->format('N')])) ?>, <?= $proximaPostagem->format('d/m') ?> por volta das <?= $proximaPostagem->format('H:00') ?></b>
            </div>
        </div>
        <div class="etapa-acoes">
            <?= form_open('painel/postar-agora') ?>
                <button type="submit" class="botao botao-claro">Postar agora <span aria-hidden="true">↑</span></button>
            <?= form_close() ?>
            <?= form_open('painel/pausar') ?>
                <button type="submit" class="botao contorno claro">Pausar</button>
            <?= form_close() ?>
        </div>
    </div>
<?php endif; ?>

<ol class="linha-tempo">

    <li class="etapa <?= $classeEtapa('conta') ?>">
        <span class="etapa-marcador">✓</span>
        <div class="etapa-corpo">
            <div class="etapa-cabeca"><h2>Criar sua conta</h2><?= $seloEtapa('conta') ?></div>
            <p>
                Cadastro feito<?= ! empty($usuario['created_at']) ? ' em ' . date('d/m/Y', strtotime($usuario['created_at'])) : '' ?>
                com o e-mail <strong><?= esc($usuario['email']) ?></strong>.
            </p>
        </div>
    </li>

    <li class="etapa <?= $classeEtapa('instagram') ?>">
        <span class="etapa-marcador"><?= $feitas['instagram'] ? '✓' : '2' ?></span>
        <div class="etapa-corpo">
            <div class="etapa-cabeca"><h2>Conectar o Instagram</h2><?= $seloEtapa('instagram') ?></div>
            <?php if ($conta): ?>
                <?php if (! empty($conta['instagram_username'])): ?>
                    <p>Conta profissional conectada: <strong>@<?= esc($conta['instagram_username']) ?></strong></p>
                <?php else: ?>
                    <p>Conta profissional conectada (ID <?= esc($conta['instagram_user_id']) ?>).</p>
                <?php endif ?>
                <div class="etapa-acoes">
                    <?= form_open('instagram/desconectar') ?>
                        <button type="submit" class="botao-link perigo-link" onclick="return confirm('Desconectar o Instagram? As postagens automáticas param até você conectar de novo.');">Desconectar</button>
                    <?= form_close() ?>
                </div>
            <?php else: ?>
                <p>Autorize o sistema a publicar no Instagram da sua franquia. Leva menos de 1 minuto.</p>
                <details class="requisitos">
                    <summary>O que preciso ter antes de conectar?</summary>
                    <div class="conteudo">
                        <ol>
                            <li>Uma conta <strong>profissional</strong> do Instagram (Empresa ou Criador de conteúdo). No app do Instagram: <em>Configurações → Conta → Mudar para conta profissional</em>.</li>
                            <li>O login dessa conta. Você vai usá-lo na tela do Instagram que abre ao clicar no botão.</li>
                        </ol>
                        <p class="observacao">Não precisa de Página do Facebook.</p>
                    </div>
                </details>
                <div class="etapa-acoes">
                    <a href="<?= site_url('instagram/conectar') ?>" class="botao">Conectar Instagram <span aria-hidden="true">→</span></a>
                </div>
            <?php endif; ?>
        </div>
    </li>

    <li class="etapa <?= $classeEtapa('agendamento') ?>">
        <span class="etapa-marcador"><?= $feitas['agendamento'] ? '✓' : '3' ?></span>
        <div class="etapa-corpo">
            <div class="etapa-cabeca"><h2>Escolher dias e horários</h2><?= $seloEtapa('agendamento') ?></div>
            <?php if ($agendamento): ?>
                <p>O sistema publica nestes dias e horários:</p>
                <div class="resumo-agenda">
                    <?php foreach (explode(',', $agendamento['dias_semana']) as $dia): ?>
                        <span class="pilula"><?= esc($diasLabel[$dia] ?? $dia) ?></span>
                    <?php endforeach; ?>
                    <span class="resumo-sep">·</span>
                    <?php foreach (explode(',', $agendamento['horarios']) as $hora): ?>
                        <span class="chip"><?= esc($hora) ?></span>
                    <?php endforeach; ?>
                    <span class="resumo-sep">·</span>
                    <span class="pilula"><?= esc(\App\Models\AgendamentoModel::FORMATOS[$agendamento['formato_publicacao'] ?? 'feed'] ?? 'Feed') ?></span>
                </div>
                <div class="etapa-acoes">
                    <a href="<?= site_url('agendamento') ?>" class="botao contorno">Alterar</a>
                </div>
            <?php else: ?>
                <p>Escolha em quais dias da semana e em quais horários as postagens devem sair.</p>
                <div class="etapa-acoes">
                    <a href="<?= site_url('agendamento') ?>" class="botao">Escolher dias e horários <span aria-hidden="true">→</span></a>
                </div>
            <?php endif; ?>
        </div>
    </li>

    <li class="etapa <?= $classeEtapa('automacao') ?>">
        <span class="etapa-marcador"><?= $feitas['automacao'] ? '✓' : '4' ?></span>
        <div class="etapa-corpo">
            <div class="etapa-cabeca"><h2>Ligar a automação</h2><?= $seloEtapa('automacao') ?></div>
            <?php if ($automacaoAtiva): ?>
                <?php if ($conta && $agendamento): ?>
                    <p>Ligada. O sistema publica sozinho nos horários escolhidos.</p>
                <?php else: ?>
                    <p>Ligada. As postagens começam assim que as etapas acima estiverem concluídas.</p>
                <?php endif; ?>
                <div class="etapa-acoes">
                    <?= form_open('painel/pausar') ?>
                        <button type="submit" class="botao contorno">Pausar automação</button>
                    <?= form_close() ?>
                </div>
            <?php else: ?>
                <p>Com a automação ligada, o sistema publica sozinho. Você pode pausar quando quiser.</p>
                <div class="etapa-acoes">
                    <?= form_open('painel/retomar') ?>
                        <button type="submit" class="botao">Ligar automação <span aria-hidden="true">→</span></button>
                    <?= form_close() ?>
                </div>
            <?php endif; ?>
        </div>
    </li>

    <li class="etapa <?= $classeEtapa('publicacao') ?>">
        <span class="etapa-marcador"><?= $feitas['publicacao'] ? '✓' : '5' ?></span>
        <div class="etapa-corpo">
            <div class="etapa-cabeca"><h2>Primeira publicação</h2><?= $seloEtapa('publicacao') ?></div>
            <?php if ($primeiraPublicacao): ?>
                <p>Primeira postagem feita em <?= esc($formatarDataHora($primeiraPublicacao['created_at'])) ?>.</p>
            <?php elseif ($conta): ?>
                <p>Faça um teste agora ou aguarde o próximo horário agendado.</p>
                <div class="etapa-acoes">
                    <?= form_open('painel/postar-agora') ?>
                        <button type="submit" class="botao">Postar agora <span aria-hidden="true">↑</span></button>
                    <?= form_close() ?>
                </div>
            <?php else: ?>
                <p>Disponível depois de conectar o Instagram.</p>
            <?php endif; ?>
        </div>
    </li>

</ol>

<?php if ($ultimasPublicacoes): ?>
    <hr class="divisor">
    <h2>Últimas publicações</h2>
    <table class="tabela">
        <thead>
            <tr><th>Quando</th><th>Imagem</th><th>Situação</th></tr>
        </thead>
        <tbody>
            <?php foreach ($ultimasPublicacoes as $item): ?>
                <tr>
                    <td><?= esc($formatarDataHora($item['created_at'])) ?></td>
                    <td><?= esc($item['nome_arquivo'] ?? '—') ?></td>
                    <td>
                        <?php if ($item['status'] === 'publicado'): ?>
                            <span class="selo selo-admin">Publicado</span>
                        <?php elseif ($item['status'] === 'erro'): ?>
                            <span class="selo selo-erro">Erro</span>
                            <?php if (! empty($item['mensagem_erro'])): ?>
                                <small class="erro-detalhe"><?= esc($item['mensagem_erro']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="selo selo-franquiado"><?= esc(ucfirst($item['status'])) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<hr class="divisor">

<p class="rodape"><a href="<?= site_url('painel/excluir-conta') ?>" class="perigo-link">Excluir minha conta</a></p>

<?= $this->endSection() ?>
