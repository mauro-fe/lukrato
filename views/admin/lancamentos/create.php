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
?>

<section class="lancamento-create-page" data-wizard-step="1">
    <div class="lancamento-create-page__toolbar">
        <a class="lancamento-create-page__back" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>"
            data-no-transition="true">
            <i data-lucide="arrow-left"></i>
            <span><?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </a>

        <span class="lancamento-create-page__pill">
            <i data-lucide="plus-circle"></i>
            <span>Nova transação</span>
        </span>
    </div>

    <div class="lancamento-create-page__heading">
        <h2 class="lancamento-create-page__title">Registre uma nova transação</h2>
        <p class="lancamento-create-page__subtitle">
            Escolha a conta, veja o contexto e siga o fluxo completo.
        </p>
    </div>

    <?php include __DIR__ . '/../partials/modals/modal-lancamento-global.php'; ?>
</section>
