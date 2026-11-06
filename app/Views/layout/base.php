<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($titulo ?? 'Avia Brasil — Postagem Automática') ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Visual no padrão do site aviabrasil.com.br (tema avia-brasil: navy + verde-limão). */
        :root {
            --navy: #001729;
            --navy-2: #03324b;
            --ink: #071b2d;
            --lime: #72de23;
            --verde: #45973d;
            --verde-escuro: #34752d;
            --verde-suave: #e9f9e2;
            --paper: #f4f7f6;
            --muted: #617180;
            --line: #dfe7ea;

            /* Nomes antigos, ainda usados em estilos inline das telas. */
            --cor-azul: var(--verde);
            --cor-azul-escuro: var(--verde-escuro);
            --cor-azul-suave: var(--verde-suave);
            --cor-verde: var(--verde);
            --cor-verde-escuro: var(--verde-escuro);
            --cor-verde-suave: var(--verde-suave);
            --cor-fundo: var(--paper);
            --cor-cartao: #fff;
            --cor-texto: var(--ink);
            --cor-texto-suave: var(--muted);
            --cor-borda: var(--line);
            --sombra: 0 20px 50px rgba(0,30,48,.08);
            --raio: 18px;
        }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            font-family: Inter, Arial, Helvetica, sans-serif;
            background: var(--paper);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        h1 { margin: 0 0 .5rem; font-size: clamp(30px, 3.4vw, 44px); font-weight: 800; line-height: 1.05; letter-spacing: -.04em; }
        h2 { margin: 0 0 .5rem; font-size: 19px; font-weight: 800; line-height: 1.25; letter-spacing: -.01em; }
        h1 em, h2 em { color: var(--verde); font-style: normal; }
        p { line-height: 1.6; }
        strong { font-weight: 800; }

        .wrap { width: min(1180px, calc(100% - 48px)); margin: 0 auto; }
        .kicker { display: block; margin-bottom: 14px; color: var(--verde); font-size: 11px; font-weight: 900; letter-spacing: .16em; text-transform: uppercase; }
        .kicker.claro { color: var(--lime); }

        /* ===== Formulários ===== */
        label { display: block; margin: 1.3rem 0 .45rem; color: var(--ink); font-size: 13px; font-weight: 750; }
        input[type=text], input[type=email], input[type=password], select, textarea {
            width: 100%;
            min-height: 50px;
            padding: .75rem 1rem;
            border: 1px solid var(--line) !important;
            border-radius: 14px !important;
            background: #fff;
            color: var(--ink);
            font: inherit;
            font-size: 15px;
            transition: border-color .15s, box-shadow .15s;
        }
        textarea { min-height: 90px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--verde) !important; box-shadow: 0 0 0 4px rgba(114,222,35,.22); }
        input[type=checkbox], input[type=radio] { width: 18px; height: 18px; accent-color: var(--verde); }
        input[type=file] { width: 100%; padding: .55rem 0; font: inherit; font-size: 14px; }

        button, .botao {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-height: 50px;
            margin-top: 1.8rem;
            padding: 0 24px;
            border: 1px solid var(--lime);
            border-radius: 30px;
            background: var(--lime);
            color: var(--navy);
            font: inherit;
            font-size: 14px;
            font-weight: 850;
            letter-spacing: .02em;
            cursor: pointer;
            transition: transform .2s, filter .2s, background .15s;
        }
        button:hover, .botao:hover { transform: translateY(-2px); filter: brightness(1.04); }
        button:active, .botao:active { transform: translateY(0); }
        button[type=submit] { width: 100%; }
        .botao.perigo { border-color: #b42318; background: #b42318; color: #fff; }
        .botao.contorno { border-color: #94a5ad; background: #fff; color: var(--ink); }
        .botao.contorno:hover { border-color: var(--navy); }
        .botao.contorno.claro { border-color: rgba(255,255,255,.45); background: transparent; color: #fff; }
        .botao.contorno.claro:hover { border-color: #fff; }

        .rodape { margin-top: 1.5rem; color: var(--muted); font-size: 14px; }
        .rodape a { color: var(--verde); font-weight: 800; }
        .rodape a:hover { text-decoration: underline; text-underline-offset: 3px; }
        .perigo-link, .rodape a.perigo-link, .botao-link.perigo-link { color: #b42318; }

        .alerta { margin-bottom: 1.2rem; padding: 16px 18px; border-radius: 14px; font-size: 14px; font-weight: 700; line-height: 1.5; }
        .alerta-erro { border: 1px solid #fdd3cf; background: #fef3f2; color: #b42318; }
        .alerta-sucesso { border: 1px solid #cfe9c4; background: var(--verde-suave); color: #2c5f25; }
        ul.erros { margin: 0 0 1.2rem; padding-left: 1.2rem; color: #b42318; font-size: 14px; line-height: 1.6; }

        .dias { display: flex; flex-wrap: wrap; gap: 8px; margin-top: .5rem; }
        .dia-item {
            display: flex; align-items: center; gap: 9px;
            margin: 0; padding: 10px 16px;
            border: 1px solid var(--line); border-radius: 30px; background: #fff;
            font-size: 14px; font-weight: 650; cursor: pointer;
            transition: border-color .15s, background .15s;
        }
        .dia-item:hover { border-color: #94a5ad; }
        .dia-item:has(input:checked) { border-color: #9fd79d; background: var(--verde-suave); color: #2c5f25; }

        /* ===== Tabelas e selos ===== */
        table.tabela { width: 100%; margin-top: 1rem; border-collapse: collapse; font-size: 14px; }
        table.tabela th, table.tabela td { padding: 14px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: middle; }
        table.tabela th { color: var(--muted); font-size: 10px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
        table.tabela tr:last-child td { border-bottom: none; }
        table.tabela tbody tr:hover td { background: #fafcfb; }

        .selo { display: inline-block; padding: 6px 11px; border-radius: 30px; font-size: 10px; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
        .selo-admin { background: var(--verde-suave); color: var(--verde-escuro); }
        .selo-franquiado { background: #eef3f6; color: var(--muted); }
        .selo-erro { background: #fef3f2; color: #b42318; }
        .selo-atual { background: var(--navy); color: var(--lime); }

        .botao-link { width: auto; min-height: 0; margin-top: 0; padding: 0; border: 0; background: none; color: var(--verde); font-size: 13px; font-weight: 850; cursor: pointer; }
        .botao-link:hover { transform: none; text-decoration: underline; text-underline-offset: 3px; }

        hr.divisor { margin: 1.6rem 0; border: none; border-top: 1px solid var(--line); }

        /* ===== Botões de ícone com dica ===== */
        .grupo-acoes { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .btn-icone {
            display: inline-flex; align-items: center; justify-content: center;
            width: 46px; height: 46px; min-height: 0;
            margin-top: 0; padding: 0;
            border: 1px solid var(--line); border-radius: 50%;
            background: #fff; color: var(--ink);
            font: inherit; cursor: pointer;
            transition: background .15s, color .15s, border-color .15s, transform .2s;
        }
        .btn-icone svg { width: 19px; height: 19px; flex-shrink: 0; }
        .btn-icone:hover { border-color: var(--navy); background: var(--navy); color: var(--lime); filter: none; }
        .btn-icone.positivo:hover { border-color: var(--lime); background: var(--lime); color: var(--navy); }
        .btn-icone.destrutivo:hover { border-color: #b42318; background: #b42318; color: #fff; }
        button[type=submit].btn-icone { width: 46px; }
        .grupo-acoes .btn-icone.com-texto, button[type=submit].btn-icone.com-texto {
            width: auto; height: 50px; padding: 0 22px; gap: 10px;
            border-color: var(--lime); border-radius: 30px; background: var(--lime); color: var(--navy);
            font-size: 14px; font-weight: 850;
        }

        [data-dica] { position: relative; }
        [data-dica]::after {
            content: attr(data-dica);
            position: absolute; bottom: calc(100% + 10px); left: 50%; z-index: 40;
            padding: 7px 11px; border-radius: 8px;
            background: var(--navy); color: #fff;
            font-size: 12px; font-weight: 700; letter-spacing: 0; text-transform: none; white-space: nowrap;
            opacity: 0; pointer-events: none;
            transform: translateX(-50%) translateY(4px);
            transition: opacity .12s, transform .12s;
        }
        [data-dica]:hover::after, [data-dica]:focus-visible::after { opacity: 1; transform: translateX(-50%) translateY(0); }

        .linha-config { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.2rem; flex-wrap: wrap; }
        .linha-config > div:first-child { flex: 1; min-width: 0; }
        .linha-config h2 { margin-bottom: .35rem; }
        .linha-config p { margin: 0; color: var(--muted); font-size: 14px; }

        /* ===== Bloco expansível (sem JS), no estilo do FAQ do site ===== */
        details.requisitos { width: 100%; margin: .9rem 0 0; padding: 0 18px; border: 1px solid var(--line); border-radius: 14px; background: var(--paper); }
        details.requisitos summary {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            padding: 14px 0; list-style: none; cursor: pointer;
            color: var(--ink); font-size: 14px; font-weight: 750;
        }
        details.requisitos summary::-webkit-details-marker { display: none; }
        details.requisitos summary::after { content: "+"; color: var(--verde); font-size: 20px; line-height: 1; }
        details.requisitos[open] summary::after { content: "\2212"; }
        details.requisitos .conteudo { padding: 0 0 16px; }
        details.requisitos ol { margin: 0 0 .8rem; padding-left: 1.2rem; font-size: 14px; }
        details.requisitos ol li { margin-bottom: .6rem; line-height: 1.6; }
        details.requisitos p.observacao { margin: .8rem 0 0; color: var(--muted); font-size: 13px; }

        /* ===== Seletor de horários + chips ===== */
        .linha-adicionar { display: flex; gap: 10px; align-items: center; }
        .linha-adicionar select { flex: 1; width: auto; }
        .linha-adicionar .btn-icone { width: auto; height: 50px; padding: 0 20px; gap: 8px; border-radius: 30px; font-size: 14px; font-weight: 800; }
        .chips { display: flex; flex-wrap: wrap; gap: 8px; min-height: 2.4rem; margin-top: 1rem; }
        .chip {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 7px 8px 7px 14px; border-radius: 30px;
            background: var(--navy); color: var(--lime);
            font-size: 14px; font-weight: 800; letter-spacing: .02em;
        }
        .chip button {
            all: unset;
            display: flex; align-items: center; justify-content: center;
            width: 20px; height: 20px; border-radius: 50%;
            color: #fff; opacity: .6; cursor: pointer;
        }
        .chip button:hover { opacity: 1; background: rgba(255,255,255,.15); }
        .chip button svg { width: 12px; height: 12px; }
        .chips-vazio { color: var(--muted); font-size: 14px; }

        .cartao { padding: 40px; border: 1px solid var(--line); border-radius: var(--raio); background: #fff; box-shadow: var(--sombra); }

        /* ===== Biblioteca de mídia ===== */
        .grade-midias { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 1.2rem; }
        .midia-card { display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--line); border-radius: 18px; background: #fff; transition: box-shadow .25s, transform .25s; }
        .midia-card:hover { transform: translateY(-3px); box-shadow: 0 20px 50px rgba(0,30,48,.1); }
        .midia-thumb { display: block; width: 100%; aspect-ratio: 1 / 1; object-fit: cover; background: var(--paper); }
        .midia-corpo { flex: 1; display: flex; flex-direction: column; gap: 8px; padding: 14px 16px 16px; }
        .midia-nome { overflow: hidden; color: var(--muted); font-size: 11px; font-weight: 800; letter-spacing: .04em; text-overflow: ellipsis; white-space: nowrap; }
        .midia-legenda { display: -webkit-box; overflow: hidden; margin: 0; font-size: 14px; line-height: 1.45; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .midia-rodape { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: auto; }
        .midia-card details.requisitos { margin-top: 0; padding: 0 12px; }
        .midia-card details.requisitos summary { padding: 8px 0; font-size: 13px; }
        .midia-card details.requisitos button { width: 100%; margin-top: .6rem; }
        .vazio-midias { padding: 1.2rem 0; color: var(--muted); font-size: 14px; }

        /* ===== Painel: linha do tempo de etapas ===== */
        .painel-topo { display: grid; grid-template-columns: 1fr auto; gap: 28px; align-items: end; margin-bottom: 26px; }
        .painel-sub { margin: 10px 0 0; color: var(--muted); font-size: 15px; }
        .progresso { min-width: 210px; padding: 18px 20px; border-radius: 16px; background: var(--navy); color: #fff; }
        .progresso > div:first-child { display: flex; align-items: baseline; gap: 8px; }
        .progresso b { color: var(--lime); font-size: 38px; font-weight: 900; line-height: 1; letter-spacing: -.04em; }
        .progresso span { color: #b7c5cc; font-size: 13px; font-weight: 700; }
        .progresso-trilha { display: flex; gap: 5px; margin-top: 14px; }
        .progresso-trilha i { flex: 1; height: 7px; border-radius: 7px; background: rgba(255,255,255,.15); }
        .progresso-trilha i.feito { background: var(--lime); }

        .status-vivo { display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap; margin-bottom: 30px; padding: 20px 22px; border-radius: 16px; color: #fff; background: radial-gradient(circle at 90% 20%, rgba(114,222,35,.18), transparent 40%), linear-gradient(135deg, #001522, #03324b); }
        .status-vivo-texto { display: flex; align-items: center; gap: 16px; }
        .status-vivo small { display: block; color: var(--lime); font-size: 10px; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
        .status-vivo b { display: block; margin-top: 4px; font-size: 18px; }
        .status-vivo .etapa-acoes { margin-top: 0; }
        .ponto-vivo { position: relative; flex: none; width: 12px; height: 12px; border-radius: 50%; background: var(--lime); }
        .ponto-vivo::after { content: ""; position: absolute; inset: 0; border-radius: 50%; background: var(--lime); animation: pulsar 1.8s ease-out infinite; }
        @keyframes pulsar { from { transform: scale(1); opacity: .7; } to { transform: scale(3); opacity: 0; } }

        .linha-tempo { margin: 0; padding: 0; list-style: none; }
        .etapa { position: relative; display: grid; grid-template-columns: 48px 1fr; gap: 20px; padding-bottom: 22px; }
        .etapa::before { content: ""; position: absolute; left: 23px; top: 50px; bottom: 2px; width: 2px; border-radius: 2px; background: var(--line); }
        .etapa:last-child { padding-bottom: 0; }
        .etapa:last-child::before { display: none; }
        .etapa.feita::before { background: var(--lime); }
        .etapa-marcador { position: relative; z-index: 1; display: grid; place-items: center; width: 48px; height: 48px; border: 2px solid var(--line); border-radius: 50%; background: #fff; color: var(--muted); font-size: 15px; font-weight: 900; }
        .etapa.feita .etapa-marcador { border-color: var(--lime); background: var(--lime); color: var(--navy); }
        .etapa.atual .etapa-marcador { border-color: var(--navy); background: var(--navy); color: var(--lime); box-shadow: 0 0 0 6px rgba(114,222,35,.25); }
        .etapa-corpo { min-width: 0; padding: 18px 22px 20px; border: 1px solid var(--line); border-radius: 18px; background: #fff; }
        .etapa.atual .etapa-corpo { border-color: #9fd79d; background: linear-gradient(160deg, #f3fbef, #fff 55%); box-shadow: 0 20px 50px rgba(0,30,48,.08); }
        .etapa.pendente .etapa-corpo { background: var(--paper); }
        .etapa.pendente h2 { color: var(--muted); }
        .etapa-cabeca { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .etapa-cabeca h2 { margin: 0; font-size: 18px; }
        .etapa-corpo > p { margin: 6px 0 0; color: var(--muted); font-size: 14px; }
        .etapa-acoes { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 16px; }
        .etapa-acoes form { margin: 0; }
        .etapa-acoes .botao, .etapa-acoes button[type=submit] { width: auto; min-height: 44px; margin-top: 0; font-size: 13px; }
        .etapa-acoes .botao-link { min-height: 0; }
        .resumo-agenda { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 12px; }
        .resumo-agenda .chip { padding: 6px 12px; font-size: 13px; }
        .resumo-sep { margin: 0 4px; color: var(--muted); font-weight: 900; }
        .pilula { padding: 6px 12px; border: 1px solid #cfe9c4; border-radius: 30px; background: var(--verde-suave); color: #2c5f25; font-size: 13px; font-weight: 750; }
        .erro-detalhe { display: block; max-width: 420px; margin-top: 6px; color: #b42318; font-size: 12px; line-height: 1.45; }

        /* ===== Tela de autenticação ===== */
        .tela-auth { display: flex; min-height: 100vh; }
        .auth-marca {
            position: relative; overflow: hidden;
            flex: 1 1 48%;
            display: flex; flex-direction: column; justify-content: space-between; gap: 40px;
            padding: 44px 56px;
            color: #fff;
            background: radial-gradient(circle at 82% 42%, rgba(114,222,35,.16), transparent 30%), linear-gradient(135deg, #001522, #03324b);
        }
        .auth-marca::before {
            content: ""; position: absolute; right: -180px; bottom: -180px;
            width: 560px; height: 560px; border: 1px solid rgba(114,222,35,.18); border-radius: 50%;
            box-shadow: 0 0 0 90px rgba(255,255,255,.022), 0 0 0 180px rgba(255,255,255,.014);
        }
        .auth-marca > * { position: relative; z-index: 1; }
        .auth-marca-topo img { display: block; width: 150px; height: auto; }
        .auth-marca-mensagem { max-width: 560px; }
        .auth-marca-mensagem h2 { margin: 0; color: #fff; font-size: clamp(38px, 4.2vw, 60px); font-weight: 450; line-height: 1.02; letter-spacing: -.05em; }
        .auth-marca-mensagem h2 em { color: var(--lime); }
        .auth-marca-mensagem p { max-width: 480px; margin: 22px 0 0; color: #b7c5cc; font-size: 16px; }
        .auth-checks { margin: 26px 0 0; padding: 0; list-style: none; }
        .auth-checks li { position: relative; margin: 10px 0; padding-left: 28px; color: #e3ebef; font-size: 14px; }
        .auth-checks li::before { content: "\2713"; position: absolute; left: 0; color: var(--lime); font-weight: 900; }
        .auth-marca-rodape { color: #7d929e; font-size: 12px; }

        .auth-formulario { flex: 1 1 52%; display: flex; align-items: center; justify-content: center; padding: 48px 24px; background: var(--paper); }
        .auth-formulario .cartao { width: 100%; max-width: 440px; }
        .auth-formulario h1 { font-size: 32px; }

        /* ===== Casca do app logado ===== */
        .topo { position: sticky; top: 0; z-index: 50; border-bottom: 1px solid rgba(255,255,255,.1); background: var(--navy); color: #fff; }
        .topo-nav { position: relative; display: flex; align-items: center; gap: 30px; height: 76px; }
        .topo-marca { margin-right: auto; }
        .topo-marca img { display: block; width: 128px; height: auto; }
        .topo-links { display: flex; align-items: center; gap: 26px; }
        .topo-links a { position: relative; padding: 6px 0; color: rgba(255,255,255,.8); font-size: 11px; font-weight: 800; letter-spacing: .035em; text-transform: uppercase; transition: color .15s; }
        .topo-links a:hover, .topo-links a.ativo { color: var(--lime); }
        .topo-links a.ativo::after { content: ""; position: absolute; left: 0; right: 0; bottom: -2px; height: 2px; border-radius: 2px; background: var(--lime); }
        .topo-usuario { display: flex; align-items: center; gap: 14px; }
        .topo-usuario span { max-width: 180px; overflow: hidden; color: #9db0ba; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .topo-sair { display: flex; align-items: center; gap: 10px; min-height: 40px; padding: 0 18px; border: 1px solid rgba(114,222,35,.65); border-radius: 30px; color: #fff; font-size: 11px; font-weight: 800; text-transform: uppercase; transition: background .15s, color .15s; }
        .topo-sair:hover { background: var(--lime); color: var(--navy); }
        .topo-mobile { display: none; }
        .topo-mobile summary { font-size: 25px; list-style: none; cursor: pointer; }
        .topo-mobile summary::-webkit-details-marker { display: none; }
        .topo-mobile div { position: absolute; top: 68px; left: 0; right: 0; padding: 14px; border-radius: 14px; background: #011827; box-shadow: 0 20px 50px rgba(0,0,0,.35); }
        .topo-mobile a { display: block; padding: 13px 12px; border-bottom: 1px solid rgba(255,255,255,.1); font-size: 14px; font-weight: 700; }
        .topo-mobile a:last-child { border-bottom: 0; color: var(--lime); }
        .topo-mobile a.ativo { color: var(--lime); }

        .faixa-hero {
            padding: 46px 0 130px;
            color: #fff;
            background: radial-gradient(circle at 82% 30%, rgba(114,222,35,.16), transparent 30%), linear-gradient(135deg, #001522, #03324b);
        }
        .faixa-hero p { margin: 0; color: #b7c5cc; font-size: 16px; }

        .area-conteudo { width: min(980px, calc(100% - 48px)); margin: -96px auto 0; padding-bottom: 70px; }

        .rodape-app { padding: 26px 0; border-top: 1px solid var(--line); color: var(--muted); font-size: 12px; }
        .rodape-app .wrap { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }

        @media (max-width: 980px) {
            .topo-links, .topo-usuario { display: none; }
            .topo-mobile { display: block; }
            .auth-marca { padding: 40px; }
        }
        @media (max-width: 860px) {
            .tela-auth { flex-direction: column; }
            .auth-marca { flex: none; gap: 24px; padding: 28px 24px 34px; }
            .auth-marca::before, .auth-checks, .auth-marca-rodape { display: none; }
            .auth-marca-mensagem h2 { font-size: 34px; }
            .auth-marca-mensagem p { margin-top: 12px; font-size: 14px; }
            .auth-formulario { align-items: flex-start; padding: 24px 16px 48px; }
        }
        @media (max-width: 640px) {
            .wrap, .area-conteudo { width: calc(100% - 32px); }
            .topo-nav { height: 68px; }
            .topo-marca img { width: 112px; }
            .faixa-hero { padding: 30px 0 110px; }
            .cartao { padding: 26px 20px; }
            .linha-adicionar { flex-wrap: wrap; }
            .linha-adicionar select { flex: 1 1 100%; }
            table.tabela { display: block; overflow-x: auto; }
            .painel-topo { grid-template-columns: 1fr; gap: 18px; }
            .etapa { grid-template-columns: 36px 1fr; gap: 12px; }
            .etapa::before { left: 17px; top: 40px; }
            .etapa-marcador { width: 36px; height: 36px; font-size: 13px; }
            .etapa-corpo { padding: 16px; }
            .status-vivo b { font-size: 16px; }
        }
    </style>
</head>
<body>
<?php $logado = (bool) session()->get('usuario_id'); ?>

<?php if (! $logado): ?>

    <div class="tela-auth">
        <div class="auth-marca">
            <div class="auth-marca-topo">
                <img src="<?= base_url('assets/img/avia-logo-branco.webp') ?>" alt="Avia Brasil">
            </div>
            <div class="auth-marca-mensagem">
                <span class="kicker claro">Postagem automática</span>
                <h2>Seu Instagram no ar, <em>sem esforço.</em></h2>
                <p>Conecte seu Instagram, defina os horários e deixe o sistema publicar por você. Pause quando quiser.</p>
                <ul class="auth-checks">
                    <li>Conteúdo pronto da matriz Avia Brasil</li>
                    <li>Publicação nos dias e horários que você escolher</li>
                    <li>Pausa e retomada com um clique</li>
                </ul>
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

    <?php
    $rota  = uri_string();
    $admin = (bool) session()->get('usuario_admin');
    $links = [
        ['painel', 'Painel', $rota === 'painel' || str_starts_with($rota, 'painel/')],
        ['agendamento', 'Agendamento', $rota === 'agendamento'],
    ];
    if ($admin) {
        $links[] = ['admin', 'Administração', str_starts_with($rota, 'admin')];
    }
    $secao = str_starts_with($rota, 'admin') ? 'Administração' : ($rota === 'agendamento' ? 'Agendamento' : 'Área do franquiado');
    ?>

    <header class="topo">
        <div class="wrap topo-nav">
            <a href="<?= site_url('painel') ?>" class="topo-marca" aria-label="Avia Brasil — Painel">
                <img src="<?= base_url('assets/img/avia-logo-branco.webp') ?>" alt="Avia Brasil">
            </a>
            <nav class="topo-links">
                <?php foreach ($links as [$url, $rotulo, $ativo]): ?>
                    <a href="<?= site_url($url) ?>" class="<?= $ativo ? 'ativo' : '' ?>"><?= esc($rotulo) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="topo-usuario">
                <span title="<?= esc(session()->get('usuario_email')) ?>"><?= esc(session()->get('usuario_nome')) ?></span>
                <a href="<?= site_url('sair') ?>" class="topo-sair">Sair <span aria-hidden="true">↗</span></a>
            </div>
            <details class="topo-mobile">
                <summary aria-label="Abrir menu">☰</summary>
                <div>
                    <?php foreach ($links as [$url, $rotulo, $ativo]): ?>
                        <a href="<?= site_url($url) ?>" class="<?= $ativo ? 'ativo' : '' ?>"><?= esc($rotulo) ?></a>
                    <?php endforeach; ?>
                    <a href="<?= site_url('sair') ?>">Sair</a>
                </div>
            </details>
        </div>
    </header>

    <section class="faixa-hero">
        <div class="wrap">
            <span class="kicker claro">Postagem automática · <?= esc($secao) ?></span>
            <p>Seu Instagram publica sozinho nos dias e horários que você escolher.</p>
        </div>
    </section>

    <main class="area-conteudo">
        <div class="cartao <?= esc($classeCaixa ?? '') ?>">
            <?= $this->renderSection('conteudo') ?>
        </div>
    </main>

    <footer class="rodape-app">
        <div class="wrap">
            <span>&copy; <?= date('Y') ?> Avia Brasil</span>
            <span>A mobilidade que transforma</span>
        </div>
    </footer>

<?php endif; ?>
</body>
</html>
