<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$orcamentoPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'orcamento'
    ? $layoutPageCapabilities
    : [];

$orcamentoCustomizerCapabilities = is_array($orcamentoPageCapabilities['customizer'] ?? null)
    ? $orcamentoPageCapabilities['customizer']
    : [];

$orcamentoForcedPreferences = is_array($orcamentoCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $orcamentoCustomizerCapabilities['forcedPreferences']
    : [];

$showOrcSummary = (bool) ($orcamentoForcedPreferences['toggleOrcSummary'] ?? true);
$showOrcFocus = (bool) ($orcamentoForcedPreferences['toggleOrcFocus'] ?? true);
$showOrcToolbar = (bool) ($orcamentoForcedPreferences['toggleOrcToolbar'] ?? true);
?>

<section class="orc-page">
    <section class="orc-overview-shell surface-card surface-card--interactive surface-card--clip">
        <div class="orc-overview-shell__top">
            <div class="orc-overview-shell__intro">
                <?php include __DIR__ . '/sections/header.php'; ?>
                <?php include __DIR__ . '/sections/actions-bar.php'; ?>
            </div>

            <?php include __DIR__ . '/sections/summary-cards.php'; ?>
        </div>

        <?php include __DIR__ . '/sections/toolbar.php'; ?>
    </section>

    <div class="orc-workspace-shell">
        <div class="orc-workspace-main">
            <section class="orc-listing-shell surface-card surface-card--interactive surface-card--clip">
                <header class="orc-listing-shell__head" data-aos="fade-up" data-aos-delay="140">
                    <div>
                        <p class="orc-section-eyebrow">Categorias do período</p>
                        <h2 class="orc-section-title">Mapa de orçamentos do mês</h2>
                        <p class="orc-section-copy">Pesquise, filtre e ajuste os limites conforme o uso real do ciclo.</p>
                    </div>
                    <div class="orc-listing-shell__meta">
                        <span class="orc-listing-shell__meta-label">Categorias visíveis</span>
                        <strong class="orc-listing-shell__meta-value" id="orcVisibleCount">--</strong>
                    </div>
                </header>

                <?php include __DIR__ . '/sections/grid.php'; ?>
                <?php include __DIR__ . '/sections/empty-state.php'; ?>
            </section>
        </div>

        <aside class="orc-workspace-side">
            <?php include __DIR__ . '/sections/focus-panel.php'; ?>
            <?php include __DIR__ . '/sections/insights.php'; ?>
            <?php include __DIR__ . '/sections/customize-modal.php'; ?>
        </aside>
    </div>
</section>

<?php include __DIR__ . '/sections/modal-orcamento.php'; ?>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->