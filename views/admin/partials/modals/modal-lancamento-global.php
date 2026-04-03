<!-- Modal Global de Lançamento -->
<div class="lk-modal-overlay lk-modal-overlay-lancamento" id="modalLancamentoGlobalOverlay">
    <div class="lk-modal-modern lk-modal-lancamento" onclick="event.stopPropagation()" role="dialog"
        aria-labelledby="modalLancamentoGlobalTitulo">
<?php include __DIR__ . '/modal-lancamento-global/sections/header.php'; ?>

        <!-- Body do Modal -->
        <div class="lk-modal-body-modern">
<?php include __DIR__ . '/modal-lancamento-global/sections/account-context.php'; ?>
<?php include __DIR__ . '/modal-lancamento-global/sections/step-1.php'; ?>
<?php include __DIR__ . '/modal-lancamento-global/sections/form-open.php'; ?>
<?php include __DIR__ . '/modal-lancamento-global/sections/step-2.php'; ?>
<?php include __DIR__ . '/modal-lancamento-global/sections/step-3.php'; ?>
<?php include __DIR__ . '/modal-lancamento-global/sections/step-4.php'; ?>
<?php include __DIR__ . '/modal-lancamento-global/sections/step-5.php'; ?>
<?php include __DIR__ . '/modal-lancamento-global/sections/form-close.php'; ?>
        </div>
    </div>
</div>
<!-- Estilos movidos para: resources/css/admin/modal-lancamento/index.css -->
