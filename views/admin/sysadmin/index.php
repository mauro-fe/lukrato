<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$sysadminPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'sysadmin'
    ? $layoutPageCapabilities
    : [];

$sysadminCustomizerCapabilities = is_array($sysadminPageCapabilities['customizer'] ?? null)
    ? $sysadminPageCapabilities['customizer']
    : [];

$sysadminTrigger = is_array($sysadminCustomizerCapabilities['trigger'] ?? null)
    ? $sysadminCustomizerCapabilities['trigger']
    : [];
$sysadminTriggerLabel = (string) ($sysadminTrigger['label'] ?? 'Personalizar sysadmin');
?>

<div class="sysadmin-container">
    <?php include __DIR__ . '/sections/customize-trigger.php'; ?>
    <?php include __DIR__ . '/sections/stats-grid.php'; ?>
    <?php include __DIR__ . '/sections/tabs-nav.php'; ?>
    <?php include __DIR__ . '/sections/panel-dashboard.php'; ?>
    <?php include __DIR__ . '/sections/panel-controle.php'; ?>
    <?php include __DIR__ . '/sections/panel-logs.php'; ?>
    <?php include __DIR__ . '/sections/panel-usuarios.php'; ?>
    <?php include __DIR__ . '/sections/panel-ia.php'; ?>
    <?php include __DIR__ . '/sections/panel-feedback.php'; ?>
    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
</div>

<?php include __DIR__ . '/sections/cleanup-logs-modal.php'; ?>