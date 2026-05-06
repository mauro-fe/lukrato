<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$faturasPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'faturas'
    ? $layoutPageCapabilities
    : [];

$faturasCustomizerCapabilities = is_array($faturasPageCapabilities['customizer'] ?? null)
    ? $faturasPageCapabilities['customizer']
    : [];

$faturasForcedPreferences = is_array($faturasCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $faturasCustomizerCapabilities['forcedPreferences']
    : [];

$showFaturasHero = (bool) ($faturasForcedPreferences['toggleFaturasHero'] ?? true);
$showFaturasFiltros = (bool) ($faturasForcedPreferences['toggleFaturasFiltros'] ?? true);
$showFaturasViewToggle = (bool) ($faturasForcedPreferences['toggleFaturasViewToggle'] ?? true);
$faturasTrigger = is_array($faturasCustomizerCapabilities['trigger'] ?? null)
    ? $faturasCustomizerCapabilities['trigger']
    : [];
$faturasTriggerLabel = (string) ($faturasTrigger['label'] ?? 'Personalizar faturas');
?>

<section class="parc-page">

    <?php include __DIR__ . '/sections/hero.php'; ?>
    <?php include __DIR__ . '/sections/filters.php'; ?>
    <?php include __DIR__ . '/sections/list-section.php'; ?>
    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
</section>