<?php
$backUrl = (string) ($backUrl ?? (BASE_URL . 'orcamento'));
$backLabel = (string) ($backLabel ?? 'Voltar para orcamento');
?>

<section
    class="finance-flow-page finance-flow-page--orcamento"
    id="orcSugestoesPage"
    data-return-url="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">
    <header class="finance-flow-header">
        <a class="finance-flow-back" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" data-no-transition="true">
            <i data-lucide="arrow-left" aria-hidden="true"></i>
            <span><?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </a>

        <button type="button" class="orc-action-btn" id="btnRecarregarSugestoes">
            <i data-lucide="refresh-cw" aria-hidden="true"></i>
            Reanalisar
        </button>
    </header>

    <div class="finance-flow-shell surface-card">
        <div class="finance-flow-shell__head">
            <div class="finance-flow-shell__title">
                <span class="finance-flow-kicker">
                    <i data-lucide="wand-2" aria-hidden="true"></i>
                    Sugest&atilde;o Inteligente
                </span>
                <h2>Revise os limites antes de aplicar</h2>
                <p>
                    Analisamos seus gastos dos &uacute;ltimos 3 meses e sugerimos limites abaixo da sua m&eacute;dia.
                    Ajuste os valores, desmarque o que n&atilde;o quiser e aplique tudo de uma vez.
                </p>
            </div>

            <button type="button" class="orc-action-btn orc-action-btn--primary" id="btnAplicarSugestoes">
                <i data-lucide="check-check" aria-hidden="true"></i>
                Aplicar selecionados
            </button>
        </div>

        <div class="sugestoes-list finance-flow-list" id="sugestoesList">
            <div class="lk-loading-state">
                <i data-lucide="loader-2" aria-hidden="true"></i>
                <p>Analisando seu hist&oacute;rico...</p>
            </div>
        </div>
    </div>
</section>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->
