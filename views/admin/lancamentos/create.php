<?php
$backUrl = (string) ($backUrl ?? (BASE_URL . 'lancamentos'));
$backLabel = (string) ($backLabel ?? 'Voltar para transações');
$wizardSource = (string) ($wizardSource ?? 'global');
$wizardPresetAccountId = $wizardPresetAccountId ?? null;
$wizardTipo = (string) ($wizardTipo ?? '');

$lancamentoWizardMode = 'page';
$lancamentoWizardTitle = 'Nova Transação';
$lancamentoWizardReturnUrl = $backUrl;
$lancamentoWizardReturnLabel = $backLabel;
$lancamentoWizardSource = $wizardSource;
$lancamentoWizardPresetAccountId = $wizardPresetAccountId;
$lancamentoWizardTipo = $wizardTipo;
$wizardInitialStep = $wizardTipo !== '' ? '2' : '1';
?>

<section class="lancamento-create-page"
    data-wizard-step="<?= htmlspecialchars($wizardInitialStep, ENT_QUOTES, 'UTF-8') ?>"
    <?php if ($wizardTipo !== ''): ?>
    data-wizard-tipo="<?= htmlspecialchars($wizardTipo, ENT_QUOTES, 'UTF-8') ?>"
    data-wizard-booting="true"
    <?php endif; ?>>
    <div class="lancamento-create-page__toolbar">
        <a class="lancamento-create-page__back" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>"
            data-no-transition="true">
            <i data-lucide="arrow-left"></i>
            <span><?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    </div>

    <div class="lancamento-create-page__heading">
        <span class="lancamento-create-page__eyebrow">Novo lançamento</span>
        <div class="lancamento-create-page__heading-main">
            <div class="lancamento-create-page__heading-copy">
                <h2 class="lancamento-create-page__title">Nova transação</h2>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/modals/modal-lancamento-global.php'; ?>
</section>