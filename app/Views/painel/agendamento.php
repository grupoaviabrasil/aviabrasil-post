<?= $this->extend('layout/base') ?>
<?= $this->section('conteudo') ?>

<p class="rodape" style="margin-top:0;"><a href="<?= site_url('painel') ?>">← Voltar ao passo a passo</a></p>
<span class="kicker">Etapa 3</span>
<h1>Escolha dias e <em>horários</em></h1>

<?php
$diasSalvos = $agendamento ? explode(',', $agendamento['dias_semana']) : [];
$horariosSalvos = $agendamento ? $agendamento['horarios'] : '';
$formatoSalvo = $agendamento['formato_publicacao'] ?? 'feed';
$diasLabel = ['seg' => 'Segunda', 'ter' => 'Terça', 'qua' => 'Quarta', 'qui' => 'Quinta', 'sex' => 'Sexta', 'sab' => 'Sábado', 'dom' => 'Domingo'];
?>

<?php if (session()->getFlashdata('sucesso')): ?>
    <div class="alerta alerta-sucesso"><?= esc(session()->getFlashdata('sucesso')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('erros')): ?>
    <ul class="erros">
        <?php foreach (session()->getFlashdata('erros') as $erro): ?>
            <li><?= esc($erro) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?= form_open('agendamento') ?>
    <label>Dias da semana</label>
    <div class="dias">
        <?php foreach ($diasLabel as $valor => $rotulo): ?>
            <label class="dia-item">
                <input type="checkbox" name="dias[]" value="<?= $valor ?>" <?= in_array($valor, old('dias', $diasSalvos), true) ? 'checked' : '' ?>>
                <?= $rotulo ?>
            </label>
        <?php endforeach; ?>
    </div>

    <label for="hora-selecao">Horários (horas cheias)</label>
    <div class="linha-adicionar">
        <select id="hora-selecao">
            <?php for ($h = 0; $h < 24; $h++): ?>
                <?php $valorHora = sprintf('%02d:00', $h); ?>
                <option value="<?= $valorHora ?>"><?= $valorHora ?></option>
            <?php endfor; ?>
        </select>
        <button type="button" id="btn-adicionar-hora" class="btn-icone" data-dica="Adicionar este horário">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Adicionar
        </button>
    </div>
    <small style="display:block; margin-top:.5rem; color:var(--cor-texto-suave); font-size:.78rem;">O sistema roda 1x por hora, então só é possível escolher horas cheias — não há precisão de minutos.</small>

    <div id="lista-horarios" class="chips"></div>
    <input type="hidden" name="horarios" id="horarios-valor" value="<?= esc(old('horarios', $horariosSalvos)) ?>">

    <label>Onde publicar</label>
    <div class="dias">
        <?php foreach (\App\Models\AgendamentoModel::FORMATOS as $valor => $rotulo): ?>
            <label class="dia-item">
                <input type="radio" name="formato_publicacao" value="<?= $valor ?>" <?= old('formato_publicacao', $formatoSalvo) === $valor ? 'checked' : '' ?>>
                <?= $rotulo ?>
            </label>
        <?php endforeach; ?>
    </div>
    <small style="display:block; margin-top:.5rem; color:var(--cor-texto-suave); font-size:.78rem;">O Story some depois de 24 horas e não mostra a legenda — só a imagem.</small>

    <button type="submit">Salvar agendamento</button>
<?= form_close() ?>

<script>
(function () {
    var campoValor = document.getElementById('horarios-valor');
    var lista = document.getElementById('lista-horarios');
    var seletor = document.getElementById('hora-selecao');
    var botaoAdicionar = document.getElementById('btn-adicionar-hora');

    function horariosAtuais() {
        return campoValor.value.split(',').map(function (h) { return h.trim(); }).filter(Boolean);
    }

    function atualizarOpcoesDisponiveis(horarios) {
        Array.prototype.forEach.call(seletor.options, function (opcao) {
            opcao.disabled = horarios.indexOf(opcao.value) !== -1;
        });
    }

    function renderizar() {
        var horarios = horariosAtuais().sort();
        campoValor.value = horarios.join(',');
        lista.innerHTML = '';

        if (horarios.length === 0) {
            lista.innerHTML = '<span class="chips-vazio">Nenhum horário adicionado ainda.</span>';
        }

        horarios.forEach(function (hora) {
            var chip = document.createElement('span');
            chip.className = 'chip';
            chip.textContent = hora;

            var botaoRemover = document.createElement('button');
            botaoRemover.type = 'button';
            botaoRemover.setAttribute('aria-label', 'Remover horário ' + hora);
            botaoRemover.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>';
            botaoRemover.addEventListener('click', function () {
                var restantes = horariosAtuais().filter(function (h) { return h !== hora; });
                campoValor.value = restantes.join(',');
                renderizar();
            });

            chip.appendChild(botaoRemover);
            lista.appendChild(chip);
        });

        atualizarOpcoesDisponiveis(horarios);
    }

    botaoAdicionar.addEventListener('click', function () {
        var horarios = horariosAtuais();
        if (horarios.indexOf(seletor.value) === -1) {
            horarios.push(seletor.value);
            campoValor.value = horarios.join(',');
            renderizar();
        }
    });

    renderizar();
})();
</script>

<?= $this->endSection() ?>
