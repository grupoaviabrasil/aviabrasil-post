<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($titulo ?? 'Avia Brasil — Postagem Automática') ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --cor-azul: #00548C;
            --cor-azul-escuro: #003B63;
            --cor-azul-suave: #EAF2F9;
            --cor-verde: #006E44;
            --cor-verde-escuro: #004E31;
            --cor-verde-suave: #E4F3EC;
            --cor-fundo: #F6F7FA;
            --cor-cartao: #FFFFFF;
            --cor-texto: #101828;
            --cor-texto-suave: #667085;
            --cor-borda: #E7EAEF;
            --sombra: 0 1px 2px rgba(16,24,40,.04), 0 8px 24px rgba(16,24,40,.06);
            --raio: 14px;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--cor-fundo);
            color: var(--cor-texto);
            margin: 0;
        }
        a { color: inherit; }
        h1 { font-size: 1.5rem; font-weight: 800; margin: 0 0 .3rem; letter-spacing: -.01em; }
        h2 { font-size: 1rem; font-weight: 700; margin: 0 0 .6rem; color: var(--cor-texto); }
        p { line-height: 1.55; }

        label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            margin: 1.1rem 0 .4rem;
            color: var(--cor-texto-suave);
        }
        input[type=text], input[type=email], input[type=password], select {
            width: 100%;
            padding: .7rem .85rem;
            border: 1.5px solid var(--cor-borda);
            border-radius: 10px;
            font-size: .95rem;
            font-family: inherit;
            background: #FCFCFD;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--cor-azul);
            box-shadow: 0 0 0 4px var(--cor-azul-suave);
            background: #fff;
        }
        button, .botao {
            margin-top: 1.6rem;
            padding: .75rem 1.2rem;
            background: var(--cor-azul);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: .95rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background .15s, transform .1s, box-shadow .15s;
            box-shadow: 0 1px 2px rgba(16,24,40,.08);
        }
        button[type=submit] { width: 100%; }
        button[type=submit].btn-icone { width: 38px; }
        .grupo-acoes button[type=submit].btn-icone.com-texto { width: auto; height: 44px; padding: 0 1rem; gap: .5rem; font-size: .88rem; font-weight: 600; }
        button:hover, .botao:hover { background: var(--cor-azul-escuro); }
        button:active, .botao:active { transform: translateY(1px); }

        .rodape { margin-top: 1.4rem; font-size: .87rem; color: var(--cor-texto-suave); }
        .rodape a { color: var(--cor-azul); text-decoration: none; font-weight: 600; }
        .rodape a:hover { text-decoration: underline; }

        .alerta { padding: .75rem .95rem; border-radius: 10px; font-size: .87rem; margin-bottom: 1.1rem; font-weight: 500; }
        .alerta-erro { background: #FEF3F2; color: #B42318; }
        .alerta-sucesso { background: var(--cor-verde-suave); color: var(--cor-verde-escuro); }
        ul.erros { margin: 0 0 1.1rem; padding-left: 1.2rem; color: #B42318; font-size: .87rem; }
        ul.erros li { margin-bottom: .2rem; }

        .dias { display: flex; flex-wrap: wrap; gap: .6rem 1.1rem; margin-top: .5rem; }
        .dia-item { display: flex; align-items: center; gap: .45rem; font-weight: 400; font-size: .9rem; margin: 0; color: var(--cor-texto); }
        .dia-item input { width: auto; }

        table.tabela { width: 100%; border-collapse: collapse; margin-top: .9rem; font-size: .88rem; }
        table.tabela th, table.tabela td { text-align: left; padding: .7rem .65rem; border-bottom: 1px solid var(--cor-borda); }
        table.tabela th { color: var(--cor-texto-suave); font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; }
        table.tabela tr:last-child td { border-bottom: none; }

        .selo { display: inline-block; padding: .22rem .6rem; border-radius: 999px; font-size: .73rem; font-weight: 700; }
        .selo-admin { background: var(--cor-verde-suave); color: var(--cor-verde-escuro); }
        .selo-franquiado { background: #F2F4F7; color: var(--cor-texto-suave); }
        .selo-erro { background: #FEF3F2; color: #B42318; }

        .botao-link { background: none; border: none; padding: 0; margin-top: 0; color: var(--cor-azul); font-size: .85rem; font-weight: 600; cursor: pointer; font-family: inherit; box-shadow: none; }
        .botao-link:hover { background: none; text-decoration: underline; }

        hr.divisor { border: none; border-top: 1px solid var(--cor-borda); margin: 1.4rem 0; }

        /* ===== Botões de ícone com dica (hint) ===== */
        .grupo-acoes { display: flex; gap: .5rem; align-items: center; }
        .btn-icone {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            padding: 0;
            margin-top: 0;
            border-radius: 10px;
            border: 1.5px solid var(--cor-borda);
            background: #fff;
            color: var(--cor-texto-suave);
            cursor: pointer;
            font-family: inherit;
            box-shadow: none;
            transition: background .12s, color .12s, border-color .12s;
            text-decoration: none;
        }
        .btn-icone svg { width: 18px; height: 18px; flex-shrink: 0; }
        .btn-icone:hover { background: var(--cor-azul-suave); color: var(--cor-azul); border-color: var(--cor-azul); }
        .btn-icone.positivo:hover { background: var(--cor-verde-suave); color: var(--cor-verde); border-color: var(--cor-verde); }
        .btn-icone.destrutivo:hover { background: #FEF3F2; color: #B42318; border-color: #FDA29B; }

        [data-dica] { position: relative; }
        [data-dica]::after {
            content: attr(data-dica);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%) translateY(4px);
            background: var(--cor-texto);
            color: #fff;
            padding: .35rem .65rem;
            border-radius: 7px;
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity .12s, transform .12s;
            z-index: 30;
        }
        [data-dica]:hover::after, [data-dica]:focus-visible::after { opacity: 1; transform: translateX(-50%) translateY(0); }

        .linha-config { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .linha-config h2 { margin-bottom: .3rem; }
        .linha-config p { margin: 0; color: var(--cor-texto-suave); font-size: .88rem; }
        .linha-config { align-items: flex-start; }
        .linha-config > div:first-child { flex: 1; min-width: 0; }

        /* ===== Bloco de requisitos (expansível, sem JS) ===== */
        details.requisitos {
            width: 100%;
            margin: .7rem 0 0;
            border: 1px solid var(--cor-borda);
            border-radius: 10px;
            background: var(--cor-fundo);
            padding: 0 .9rem;
        }
        details.requisitos summary {
            cursor: pointer;
            padding: .7rem 0;
            font-weight: 600;
            font-size: .84rem;
            color: var(--cor-azul);
            list-style: none;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        details.requisitos summary::-webkit-details-marker { display: none; }
        details.requisitos summary::before { content: "▸"; font-size: .7rem; color: var(--cor-texto-suave); }
        details.requisitos[open] summary::before { content: "▾"; }
        details.requisitos .conteudo { padding: 0 0 1rem; }
        details.requisitos ol { margin: 0 0 .8rem; padding-left: 1.2rem; font-size: .85rem; color: var(--cor-texto); }
        details.requisitos ol li { margin-bottom: .55rem; line-height: 1.5; }
        details.requisitos ol li:last-child { margin-bottom: 0; }
        details.requisitos p.observacao { font-size: .82rem; color: var(--cor-texto-suave); margin: .8rem 0 0; }

        /* ===== Seletor de horários + chips removíveis ===== */
        .linha-adicionar { display: flex; gap: .6rem; align-items: center; }
        .linha-adicionar select { width: auto; flex: 1; height: 44px; }
        .linha-adicionar .btn-icone { width: auto; height: 44px; padding: 0 1rem; gap: .4rem; font-size: .88rem; font-weight: 600; }
        .chips { display: flex; flex-wrap: wrap; gap: .55rem; margin-top: .8rem; min-height: 2.2rem; }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: var(--cor-azul-suave);
            color: var(--cor-azul-escuro);
            padding: .35rem .4rem .35rem .75rem;
            border-radius: 999px;
            font-size: .84rem;
            font-weight: 700;
        }
        .chip button {
            all: unset;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--cor-azul);
            opacity: .65;
        }
        .chip button:hover { opacity: 1; background: rgba(0,84,140,.15); }
        .chip button svg { width: 13px; height: 13px; }
        .chips-vazio { color: var(--cor-texto-suave); font-size: .85rem; }

        .cartao {
            background: var(--cor-cartao);
            border: 1px solid var(--cor-borda);
            border-radius: var(--raio);
            box-shadow: var(--sombra);
            padding: 1.6rem 1.8rem;
        }

        /* ===== Biblioteca de mídia (grade de miniaturas) ===== */
        .grade-midias { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .midia-card { border: 1px solid var(--cor-borda); border-radius: 12px; overflow: hidden; background: #fff; display: flex; flex-direction: column; }
        .midia-thumb { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; background: var(--cor-fundo); display: block; }
        .midia-corpo { padding: .8rem .9rem 1rem; display: flex; flex-direction: column; gap: .5rem; flex: 1; }
        .midia-nome { font-size: .78rem; font-weight: 700; color: var(--cor-texto-suave); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .midia-legenda { font-size: .85rem; color: var(--cor-texto); line-height: 1.4; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .midia-rodape { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: auto; }
        .midia-card details.requisitos { margin-top: 0; }
        .midia-card details.requisitos summary { padding: .4rem 0; font-size: .78rem; }
        .midia-card details.requisitos textarea { width: 100%; min-height: 70px; padding: .6rem .7rem; border: 1.5px solid var(--cor-borda); border-radius: 8px; font-family: inherit; font-size: .85rem; resize: vertical; }
        .midia-card details.requisitos button { margin-top: .6rem; width: 100%; }
        .vazio-midias { color: var(--cor-texto-suave); font-size: .9rem; padding: 1.2rem 0; }
        input[type=file] { width: 100%; padding: .55rem 0; font-family: inherit; font-size: .9rem; }

        /* ===== Marca ===== */
        .marca { display: flex; align-items: center; gap: .65rem; text-decoration: none; }
        .marca-icone { width: 30px; height: auto; flex-shrink: 0; display: block; }
        .marca-texto { display: flex; flex-direction: column; line-height: 1; }
        .marca-nome { font-weight: 800; font-size: 1.15rem; letter-spacing: .01em; color: var(--cor-azul); }
        .marca-tagline { font-weight: 600; font-size: .56rem; letter-spacing: .07em; text-transform: uppercase; color: var(--cor-verde); margin-top: .18rem; }

        /* ===== Tela de autenticação (split-screen) ===== */
        .tela-auth { display: flex; min-height: 100vh; }
        .auth-marca {
            flex: 1 1 42%;
            background: linear-gradient(155deg, var(--cor-azul) 0%, #003457 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        .auth-marca::before {
            content: "";
            position: absolute;
            width: 620px; height: 620px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,110,68,.35) 0%, rgba(0,110,68,0) 70%);
            top: -220px; right: -220px;
        }
        .auth-marca-topo { display: flex; align-items: center; gap: .8rem; position: relative; z-index: 1; }
        .auth-marca-topo img { width: clamp(220px, 24vw, 320px); height: auto; }
        .auth-marca-mensagem { position: relative; z-index: 1; max-width: 380px; }
        .auth-marca-mensagem h2 { color: #fff; font-size: 1.7rem; font-weight: 800; line-height: 1.25; margin-bottom: .8rem; }
        .auth-marca-mensagem p { color: #CBD9E4; font-size: .95rem; }
        .auth-marca-rodape { position: relative; z-index: 1; color: #7FA3BE; font-size: .78rem; }

        .auth-formulario {
            flex: 1 1 58%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
        }
        .auth-formulario .cartao { width: 100%; max-width: 400px; background: none; border: none; box-shadow: none; padding: 0; }
        .auth-formulario .cartao.caixa-larga { max-width: 400px; }

        @media (max-width: 860px) {
            .tela-auth { flex-direction: column; }
            .auth-marca { flex: none; padding: 1.8rem; }
            .auth-marca::before { display: none; }
            .auth-marca-mensagem { display: none; }
            .auth-marca-rodape { display: none; }
        }

        /* ===== Casca do app (barra lateral) ===== */
        .app-shell { display: flex; min-height: 100vh; }
        .barra-lateral {
            width: 240px;
            flex-shrink: 0;
            background: var(--cor-cartao);
            border-right: 1px solid var(--cor-borda);
            display: flex;
            flex-direction: column;
            padding: 1.4rem 1.1rem;
        }
        .barra-marca { padding: .4rem .5rem 1.6rem; }
        .barra-nav { display: flex; flex-direction: column; gap: .2rem; flex: 1; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .6rem .7rem;
            border-radius: 9px;
            text-decoration: none;
            color: var(--cor-texto-suave);
            font-size: .89rem;
            font-weight: 600;
            transition: background .12s, color .12s;
        }
        .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
        .nav-item:hover { background: var(--cor-fundo); color: var(--cor-texto); }
        .nav-item.ativo { background: var(--cor-azul-suave); color: var(--cor-azul); }

        .barra-rodape { border-top: 1px solid var(--cor-borda); padding-top: .9rem; margin-top: .6rem; }
        .usuario-mini { padding: 0 .7rem; margin-bottom: .5rem; }
        .usuario-mini .nome { font-size: .85rem; font-weight: 700; color: var(--cor-texto); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .usuario-mini .email { font-size: .74rem; color: var(--cor-texto-suave); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .area-principal { flex: 1; min-width: 0; }
        .area-conteudo { max-width: 880px; margin: 0 auto; padding: 2.6rem 2rem; }

        @media (max-width: 780px) {
            .app-shell { flex-direction: column; }
            .barra-lateral {
                width: 100%;
                flex-direction: row;
                align-items: center;
                border-right: none;
                border-bottom: 1px solid var(--cor-borda);
                padding: .7rem 1rem;
            }
            .barra-marca { padding: 0; margin-right: .8rem; }
            .barra-marca .marca-texto { display: none; }
            .barra-nav { flex-direction: row; flex: 1; overflow-x: auto; }
            .nav-item span { display: none; }
            .barra-rodape { border-top: none; border-left: 1px solid var(--cor-borda); padding: 0 0 0 .8rem; margin: 0; display: flex; align-items: center; }
            .usuario-mini { display: none; }
            .area-conteudo { padding: 1.5rem 1.1rem; }
        }
    </style>
</head>
<body>
<?php $logado = (bool) session()->get('usuario_id'); ?>

<?php if (! $logado): ?>

    <div class="tela-auth">
        <div class="auth-marca">
            <div class="auth-marca-topo">
                <img src="<?= base_url('assets/img/avia-icone.png') ?>" alt="Avia">
            </div>
            <div class="auth-marca-mensagem">
                <h2>Postagem automática para toda a rede de franquias.</h2>
                <p>Conecte seu Instagram, defina os horários e deixe o sistema publicar por você — pause quando quiser, sem esforço.</p>
            </div>
            <div class="auth-marca-rodape">&copy; <?= date('Y') ?> Avia Brasil</div>
        </div>
        <div class="auth-formulario">
            <div class="cartao <?= esc($classeCaixa ?? '') ?>">
                <?= $this->renderSection('conteudo') ?>
            </div>
        </div>
    </div>

<?php else: ?>

    <div class="app-shell">
        <aside class="barra-lateral">
            <a href="<?= site_url('painel') ?>" class="marca barra-marca">
                <img src="<?= base_url('assets/img/favicon.png') ?>" alt="Avia" class="marca-icone">
                <span class="marca-texto">
                    <span class="marca-nome">AVIA</span>
                    <span class="marca-tagline">A mobilidade que transforma</span>
                </span>
            </a>

            <?php $rota = uri_string(); ?>
            <nav class="barra-nav">
                <a href="<?= site_url('painel') ?>" class="nav-item <?= $rota === 'painel' ? 'ativo' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>
                    <span>Painel</span>
                </a>
                <a href="<?= site_url('agendamento') ?>" class="nav-item <?= $rota === 'agendamento' ? 'ativo' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                    <span>Agendamento</span>
                </a>
                <?php if (session()->get('usuario_admin')): ?>
                    <a href="<?= site_url('admin') ?>" class="nav-item <?= str_starts_with($rota, 'admin') ? 'ativo' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3.5v5C20 17 16.5 20 12 21c-4.5-1-8-4-8-9.5v-5L12 3z"/></svg>
                        <span>Administração</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="barra-rodape">
                <div class="usuario-mini">
                    <span class="nome"><?= esc(session()->get('usuario_nome')) ?></span>
                    <span class="email"><?= esc(session()->get('usuario_email')) ?></span>
                </div>
                <a href="<?= site_url('sair') ?>" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <div class="area-principal">
            <main class="area-conteudo">
                <div class="cartao <?= esc($classeCaixa ?? '') ?>">
                    <?= $this->renderSection('conteudo') ?>
                </div>
            </main>
        </div>
    </div>

<?php endif; ?>
</body>
</html>
