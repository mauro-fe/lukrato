<section class="cartoes-page">
<?php include __DIR__ . '/sections/hero.php'; ?>
<?php include __DIR__ . '/sections/kpis.php'; ?>
<?php include __DIR__ . '/sections/alerts.php'; ?>
<?php include __DIR__ . '/sections/list-section.php'; ?>
<?php include __DIR__ . '/sections/customize-modal.php'; ?>
</section>

<!-- ==================== MODAIS ==================== -->
<?php include __DIR__ . '/../partials/modals/modal-cartoes.php'; ?>
<?php include __DIR__ . '/../partials/modals/card-detail-modal.php'; ?>

<!-- ==================== ESTILOS ==================== -->
<?= vite_scripts('admin/card-modals/index.js') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
