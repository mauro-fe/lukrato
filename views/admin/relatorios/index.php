<!-- Relatórios View -->
<?php $isPro = $isPro ?? false; ?>

<div class="rel-page">

    <?php include __DIR__ . '/sections/quick-stats.php'; ?>
    <?php include __DIR__ . '/sections/section-tabs.php'; ?>
    <?php include __DIR__ . '/sections/panel-overview.php'; ?>
    <?php include __DIR__ . '/sections/panel-relatorios.php'; ?>
    <?php include __DIR__ . '/sections/panel-insights.php'; ?>
    <?php include __DIR__ . '/sections/panel-comparativos.php'; ?>
    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
    <?php include __DIR__ . '/sections/loading-state.php'; ?>
</div>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->
