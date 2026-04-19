<?php
$selectedPlanData = is_array($checkoutPlan ?? null) ? $checkoutPlan : [];
$billingPaymentMode = 'page';

$planName = (string) ($selectedPlanData['planName'] ?? 'Lukrato PRO');
$planCode = (string) ($selectedPlanData['planCode'] ?? 'pro');
$cycleLabel = (string) ($selectedPlanData['label'] ?? 'Mensal');
$period = (string) ($selectedPlanData['period'] ?? 'mes');
$monthlyBase = (float) ($selectedPlanData['monthlyBase'] ?? 0);
$total = (float) ($selectedPlanData['total'] ?? 0);
$discount = (int) ($selectedPlanData['discount'] ?? 0);
$months = (int) ($selectedPlanData['months'] ?? 1);
?>

<div class="billing-checkout">
    <header class="billing-checkout__hero">
        <a class="billing-checkout__back" href="<?= BASE_URL ?>billing">
            <i data-lucide="arrow-left" aria-hidden="true"></i>
            <span>Voltar aos planos</span>
        </a>

        <div class="billing-checkout__headline">
            <span class="billing-checkout__eyebrow">Pagamento seguro</span>
            <h1>Finalize sua assinatura <?= htmlspecialchars($planName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Confira o periodo escolhido e complete os dados de pagamento.</p>
        </div>
    </header>

    <section class="billing-checkout__summary" aria-label="Resumo do plano">
        <div class="billing-checkout__summary-item">
            <span>Plano</span>
            <strong><?= htmlspecialchars(strtoupper($planCode), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="billing-checkout__summary-item">
            <span>Periodo</span>
            <strong><?= htmlspecialchars($cycleLabel, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="billing-checkout__summary-item">
            <span>Valor mensal</span>
            <strong>R$ <?= number_format($monthlyBase, 2, ',', '.') ?></strong>
        </div>
        <div class="billing-checkout__summary-item billing-checkout__summary-item--total">
            <span>Total<?= $months > 1 ? ' com desconto' : '' ?></span>
            <strong>R$ <?= number_format($total, 2, ',', '.') ?>/<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <?php if ($discount > 0): ?>
            <div class="billing-checkout__summary-badge">
                <i data-lucide="badge-percent" aria-hidden="true"></i>
                <span><?= $discount ?>% aplicado automaticamente</span>
            </div>
        <?php endif; ?>
    </section>

    <?php include __DIR__ . '/../partials/modals/modal-pagamento.php'; ?>
</div>

<!-- JS carregado via Vite (loadPageJs) -->
