<?php
$backUrl = (string) ($backUrl ?? (BASE_URL . 'metas'));
$backLabel = (string) ($backLabel ?? 'Voltar para metas');
?>

<section
    class="finance-flow-page finance-flow-page--metas"
    id="metTemplatesPage"
    data-return-url="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">
    <header class="finance-flow-header">
        <a class="finance-flow-back" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" data-no-transition="true">
            <i data-lucide="arrow-left" aria-hidden="true"></i>
            <span><?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </a>

        <div class="finance-flow-header__actions">
            <button type="button" class="met-action-btn" id="btnRecarregarTemplates">
                <i data-lucide="refresh-cw" aria-hidden="true"></i>
                Recarregar
            </button>
            <button type="button" class="met-action-btn met-action-btn--primary" id="btnNovaMetaHeader">
                <i data-lucide="plus" aria-hidden="true"></i>
                Criar em branco
            </button>
        </div>
    </header>

    <div class="finance-flow-shell surface-card">
        <div class="finance-flow-shell__head">
            <div class="finance-flow-shell__title">
                <span class="finance-flow-kicker">
                    <i data-lucide="wand-sparkles" aria-hidden="true"></i>
                    Templates de Metas
                </span>
                <h2>Escolha um ponto de partida</h2>
                <p>
                    Selecione um modelo para preencher a meta automaticamente.
                    Voc&ecirc; ainda pode ajustar t&iacute;tulo, valor, prioridade, prazo e cor antes de salvar.
                </p>
            </div>
        </div>

        <div class="templates-grid finance-flow-list" id="templatesGrid">
            <div class="lk-loading-state">
                <i data-lucide="loader-2" aria-hidden="true"></i>
                <p>Carregando templates...</p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/sections/modal-meta.php'; ?>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->
