<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$financasPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'financas'
    ? $layoutPageCapabilities
    : [];

$financasCustomizerCapabilities = is_array($financasPageCapabilities['customizer'] ?? null)
    ? $financasPageCapabilities['customizer']
    : [];

$financasForcedPreferences = is_array($financasCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $financasCustomizerCapabilities['forcedPreferences']
    : [];

$showFinSummary = (bool) ($financasForcedPreferences['toggleFinSummary'] ?? true);
$showFinOrcActions = (bool) ($financasForcedPreferences['toggleFinOrcActions'] ?? true);
$showFinMetasActions = (bool) ($financasForcedPreferences['toggleFinMetasActions'] ?? true);
$showFinInsights = (bool) ($financasForcedPreferences['toggleFinInsights'] ?? true);
?>

<section class="fin-page">

    <?php include __DIR__ . '/sections/summary.php'; ?>
    <?php include __DIR__ . '/sections/tabs.php'; ?>
    <?php include __DIR__ . '/sections/tab-orcamentos.php'; ?>
    <?php include __DIR__ . '/sections/tab-metas.php'; ?>
    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
</section>

<?php include __DIR__ . '/sections/modal-orcamento.php'; ?>
<?php include __DIR__ . '/sections/modal-sugestoes.php'; ?>
<?php include __DIR__ . '/sections/modal-meta.php'; ?>
<?php include __DIR__ . '/sections/modal-templates.php'; ?>
<?php include __DIR__ . '/sections/modal-aporte.php'; ?>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->