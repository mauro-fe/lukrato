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

    <div class="lancamento-create-page__stepper" aria-label="Etapas do lançamento">
        <div class="lancamento-create-page__step" data-step="1">
            <span class="lancamento-create-page__step-index">1</span>
            <span class="lancamento-create-page__step-copy">
                <strong>Escolha</strong>
                <small>Receita, despesa ou transferência</small>
            </span>
        </div>
        <div class="lancamento-create-page__step" data-step="2">
            <span class="lancamento-create-page__step-index">2</span>
            <span class="lancamento-create-page__step-copy">
                <strong>Informações</strong>
                <small>Valor, data, categoria e forma</small>
            </span>
        </div>
        <div class="lancamento-create-page__step" data-step="3">
            <span class="lancamento-create-page__step-index">3</span>
            <span class="lancamento-create-page__step-copy">
                <strong>Opcional</strong>
                <small>Meta, lembrete e ajustes finais</small>
            </span>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/modals/modal-lancamento-global.php'; ?>
</section>