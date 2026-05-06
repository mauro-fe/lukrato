<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$reportsPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'relatorios'
    ? $layoutPageCapabilities
    : [];

$reportsCustomizerCapabilities = is_array($reportsPageCapabilities['customizer'] ?? null)
    ? $reportsPageCapabilities['customizer']
    : [];

$reportsForcedPreferences = is_array($reportsCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $reportsCustomizerCapabilities['forcedPreferences']
    : [];

$reportsIsVisible = static function (string $toggleKey, bool $default = true) use ($reportsForcedPreferences): bool {
    if (array_key_exists($toggleKey, $reportsForcedPreferences)) {
        return (bool) $reportsForcedPreferences[$toggleKey];
    }

    return $default;
};

$showInsightsTab = $reportsIsVisible('toggleRelSectionInsights', true);
$showRelatoriosTab = $reportsIsVisible('toggleRelSectionRelatorios', true);
$showComparativosTab = $reportsIsVisible('toggleRelSectionComparativos', true);
$reportsTriggerLabel = (string) ($reportsCustomizerCapabilities['trigger']['label'] ?? 'Personalizar relatórios');
?>

<div class="rel-section-bar">
    <nav class="rel-section-tabs surface-card surface-card--clip" role="tablist" aria-label="Seções de relatórios">
        <button type="button" class="rel-section-tab surface-filter surface-filter--soft active" data-section="overview"
            role="tab" aria-selected="true" aria-controls="section-overview">
            <span class="tab-icon"><i data-lucide="layout-dashboard" style="color:#3b82f6"></i></span>
            <span class="tab-label">Visão Geral</span>
        </button>

        <?php if ($showRelatoriosTab): ?>
            <button type="button" class="rel-section-tab surface-filter surface-filter--soft" data-section="relatorios"
                role="tab" aria-selected="false" aria-controls="section-relatorios">
                <span class="tab-icon"><i data-lucide="bar-chart-3" style="color:#e67e22"></i></span>
                <span class="tab-label">Relatórios</span>
            </button>
        <?php endif; ?>

        <?php if ($showInsightsTab): ?>
            <button type="button" class="rel-section-tab surface-filter surface-filter--soft" data-section="insights"
                role="tab" aria-selected="false" aria-controls="section-insights">
                <span class="tab-icon"><i data-lucide="lightbulb" style="color:#facc15"></i></span>
                <span class="tab-label">Insights Inteligentes</span>
            </button>
        <?php endif; ?>

        <?php if ($showComparativosTab): ?>
            <button type="button" class="rel-section-tab surface-filter surface-filter--soft" data-section="comparativos"
                role="tab" aria-selected="false" aria-controls="section-comparativos">
                <span class="tab-icon"><i data-lucide="git-compare" style="color:#3b82f6"></i></span>
                <span class="tab-label">Comparativos</span>
            </button>
        <?php endif; ?>
    </nav>

    <button class="rel-customize-open surface-card" id="btnCustomizeRelatorios" type="button">
        <i data-lucide="sliders-horizontal"></i>
        <span><?= htmlspecialchars($reportsTriggerLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </button>
</div>