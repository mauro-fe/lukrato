<?php
$lancamentoWizardMode = ($lancamentoWizardMode ?? 'modal') === 'page' ? 'page' : 'modal';
$lancamentoWizardReturnUrl = trim((string) ($lancamentoWizardReturnUrl ?? ''));
$lancamentoWizardReturnLabel = trim((string) ($lancamentoWizardReturnLabel ?? ''));
$lancamentoWizardSource = trim((string) ($lancamentoWizardSource ?? 'global'));
$lancamentoWizardPresetAccountId = $lancamentoWizardPresetAccountId ?? null;
$lancamentoWizardTipo = trim((string) ($lancamentoWizardTipo ?? ''));

$wizardOverlayClasses = 'lk-modal-overlay lk-modal-overlay-lancamento';
if ($lancamentoWizardMode === 'page') {
    $wizardOverlayClasses .= ' lk-modal-overlay--page';
}

$wizardDialogClasses = 'lk-modal-modern lk-modal-lancamento';
if ($lancamentoWizardMode === 'page') {
    $wizardDialogClasses .= ' surface-card surface-card--clip';
}
?>

<!-- Modal Global de Lançamento -->
<div
    class="<?= htmlspecialchars($wizardOverlayClasses, ENT_QUOTES, 'UTF-8') ?>"
    id="modalLancamentoGlobalOverlay"
    data-mode="<?= htmlspecialchars($lancamentoWizardMode, ENT_QUOTES, 'UTF-8') ?>"
    data-return-url="<?= htmlspecialchars($lancamentoWizardReturnUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-return-label="<?= htmlspecialchars($lancamentoWizardReturnLabel, ENT_QUOTES, 'UTF-8') ?>"
    data-source="<?= htmlspecialchars($lancamentoWizardSource, ENT_QUOTES, 'UTF-8') ?>"
    data-preset-account-id="<?= htmlspecialchars((string) ($lancamentoWizardPresetAccountId ?? ''), ENT_QUOTES, 'UTF-8') ?>"
    data-tipo="<?= htmlspecialchars($lancamentoWizardTipo, ENT_QUOTES, 'UTF-8') ?>">
    <div
        class="<?= htmlspecialchars($wizardDialogClasses, ENT_QUOTES, 'UTF-8') ?>"
        onclick="event.stopPropagation()"
        role="dialog"
        aria-labelledby="modalLancamentoGlobalTitulo">
        <?php include __DIR__ . '/modal-lancamento-global/sections/header.php'; ?>

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
