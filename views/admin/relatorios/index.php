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

<!-- Template do Modal de Detalhes do Cartão -->
<?php include BASE_PATH . '/views/admin/partials/modals/card-detail-modal.php'; ?>

<!-- ==================== SCRIPTS ==================== -->
<script>
    window.IS_PRO = <?= json_encode($isPro) ?>;
</script>
<?= vite_scripts('admin/card-modals/index.js') ?>
<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->
