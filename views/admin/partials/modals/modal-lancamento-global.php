<?php
$lancamentoWizardMode = 'page';
$lancamentoWizardReturnUrl = trim((string) ($lancamentoWizardReturnUrl ?? ''));
$lancamentoWizardReturnLabel = trim((string) ($lancamentoWizardReturnLabel ?? ''));
$lancamentoWizardSource = trim((string) ($lancamentoWizardSource ?? 'global'));
$lancamentoWizardPresetAccountId = $lancamentoWizardPresetAccountId ?? null;
$lancamentoWizardTipo = trim((string) ($lancamentoWizardTipo ?? ''));
$lancamentoWizardInitialStep = $lancamentoWizardTipo !== '' ? '2' : '1';
$lancamentoWizardIsBooting = $lancamentoWizardTipo !== '';

$wizardOverlayClasses = 'lk-modal-overlay lk-modal-overlay-lancamento lk-modal-overlay--page';
$wizardDialogClasses = 'lk-modal-modern lk-modal-lancamento surface-card surface-card--clip';
?>

<!-- Fluxo de lançamento em página -->
<div
    class="<?= htmlspecialchars($wizardOverlayClasses, ENT_QUOTES, 'UTF-8') ?>"
    id="modalLancamentoGlobalOverlay"
    data-mode="<?= htmlspecialchars($lancamentoWizardMode, ENT_QUOTES, 'UTF-8') ?>"
    data-return-url="<?= htmlspecialchars($lancamentoWizardReturnUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-return-label="<?= htmlspecialchars($lancamentoWizardReturnLabel, ENT_QUOTES, 'UTF-8') ?>"
    data-source="<?= htmlspecialchars($lancamentoWizardSource, ENT_QUOTES, 'UTF-8') ?>"
    data-preset-account-id="<?= htmlspecialchars((string) ($lancamentoWizardPresetAccountId ?? ''), ENT_QUOTES, 'UTF-8') ?>"
    data-tipo="<?= htmlspecialchars($lancamentoWizardTipo, ENT_QUOTES, 'UTF-8') ?>"
    data-wizard-step="<?= htmlspecialchars($lancamentoWizardInitialStep, ENT_QUOTES, 'UTF-8') ?>"
    <?php if ($lancamentoWizardIsBooting): ?>
        data-wizard-booting="true"
    <?php endif; ?>>
    <div
        class="<?= htmlspecialchars($wizardDialogClasses, ENT_QUOTES, 'UTF-8') ?>"
        onclick="event.stopPropagation()"
        role="region"
        aria-label="Nova transação">
        <div class="lk-modal-body-modern">
            <?php include __DIR__ . '/modal-lancamento-global/sections/step-1.php'; ?>
            <?php include __DIR__ . '/modal-lancamento-global/sections/form-open.php'; ?>
            <?php include __DIR__ . '/modal-lancamento-global/sections/step-page-quick.php'; ?>
            <?php include __DIR__ . '/modal-lancamento-global/sections/form-close.php'; ?>
        </div>
    </div>
</div>
