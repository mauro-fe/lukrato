<!-- CSS Lancamentos — carregado via Vite (import no JS entry) -->

<?php $isPro = $isPro ?? false; ?>

<section class="lan-page">
<?php include __DIR__ . '/sections/hero.php'; ?>
<?php include __DIR__ . '/sections/summary-strip.php'; ?>
<?php include __DIR__ . '/sections/export-card.php'; ?>
<?php include __DIR__ . '/sections/filters.php'; ?>
<?php include __DIR__ . '/sections/table.php'; ?>
<?php include __DIR__ . '/sections/customize-modal.php'; ?>
</section>

<?php include __DIR__ . '/../partials/modals/editar-lancamentos.php'; ?>
<?php include __DIR__ . '/../partials/modals/visualizar-lancamento.php'; ?>
<?php include __DIR__ . '/../partials/modals/editar-transferencia.php'; ?>
<?php include __DIR__ . '/../partials/modals/excluir-lancamento-escopo.php'; ?>
