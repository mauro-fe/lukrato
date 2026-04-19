<?php
$billingPaymentMode = $billingPaymentMode ?? 'modal';
$isBillingCheckoutPage = $billingPaymentMode === 'page';
$selectedPlanData = is_array($selectedPlanData ?? null) ? $selectedPlanData : [];
$paymentShellId = $isBillingCheckoutPage ? 'billing-checkout-page' : 'billing-modal';
$paymentShellClass = $isBillingCheckoutPage ? 'payment-modal payment-modal--page payment-modal--open' : 'payment-modal';
$paymentShellRole = $isBillingCheckoutPage ? 'region' : 'dialog';
$paymentShellAriaModal = $isBillingCheckoutPage ? '' : ' aria-modal="true"';
?>

<div id="<?= $paymentShellId ?>" class="<?= $paymentShellClass ?>" role="<?= $paymentShellRole ?>"
    aria-labelledby="billing-modal-title"<?= $paymentShellAriaModal ?>
    data-mode="<?= $isBillingCheckoutPage ? 'page' : 'modal' ?>"
    data-return-url="<?= htmlspecialchars(BASE_URL . 'billing', ENT_QUOTES, 'UTF-8') ?>"
    data-pix-complete="<?= $pixDataComplete ? '1' : '0' ?>"
    data-boleto-complete="<?= $boletoDataComplete ? '1' : '0' ?>"
    data-cpf="<?= htmlspecialchars($cpfDigits, ENT_QUOTES, 'UTF-8') ?>"
    data-phone="<?= htmlspecialchars($phoneDigits, ENT_QUOTES, 'UTF-8') ?>"
    data-cep="<?= htmlspecialchars($cepDigits, ENT_QUOTES, 'UTF-8') ?>"
    data-endereco="<?= htmlspecialchars($enderecoValue, ENT_QUOTES, 'UTF-8') ?>"
    data-email="<?= htmlspecialchars($user->email ?? '', ENT_QUOTES, 'UTF-8') ?>"
    data-plan-id="<?= htmlspecialchars((string) ($selectedPlanData['planId'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
    data-plan-code="<?= htmlspecialchars((string) ($selectedPlanData['planCode'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
    data-plan-name="<?= htmlspecialchars((string) ($selectedPlanData['planName'] ?? 'Lukrato PRO'), ENT_QUOTES, 'UTF-8') ?>"
    data-plan-monthly="<?= htmlspecialchars((string) ($selectedPlanData['monthlyBase'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
    data-plan-cycle="<?= htmlspecialchars((string) ($selectedPlanData['cycle'] ?? 'monthly'), ENT_QUOTES, 'UTF-8') ?>"
    data-plan-months="<?= htmlspecialchars((string) ($selectedPlanData['months'] ?? 1), ENT_QUOTES, 'UTF-8') ?>"
    data-plan-discount="<?= htmlspecialchars((string) ($selectedPlanData['discount'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
    <div class="payment-modal__content">
        <div class="payment-modal__header">
            <?php if ($isBillingCheckoutPage): ?>
                <a class="payment-modal__close" aria-label="Voltar para planos" href="<?= BASE_URL ?>billing">
                    <i data-lucide="x" aria-hidden="true"></i>
                </a>
            <?php else: ?>
                <button class="payment-modal__close" aria-label="Fechar modal" type="button"
                    onclick="window.closeBillingModal?.()">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            <?php endif; ?>

            <h2 id="billing-modal-title" class="payment-modal__title">
                Pagamento Seguro
            </h2>

            <p id="billing-modal-text" class="payment-modal__subtitle">
                Escolha a forma de pagamento para ativar o Lukrato PRO
            </p>

            <div id="billing-modal-price" class="payment-modal__price" role="status" aria-live="polite">
                <?= $isBillingCheckoutPage && isset($selectedPlanData['total'], $selectedPlanData['period'])
                    ? htmlspecialchars((string) $selectedPlanData['planName'], ENT_QUOTES, 'UTF-8') . ' - R$ ' . number_format((float) $selectedPlanData['total'], 2, ',', '.') . '/' . htmlspecialchars((string) $selectedPlanData['period'], ENT_QUOTES, 'UTF-8')
                    : 'Selecione um plano para continuar' ?>
            </div>
        </div>

        <!-- ========== SEÇÃO DE PAGAMENTO PENDENTE (BLOQUEIO) ========== -->
        <div id="pending-payment-section" class="pending-payment-section">
            <div class="pending-payment-section__icon">
                <i data-lucide="clock"></i>
            </div>
            <h3 class="pending-payment-section__title">
                Você tem um pagamento pendente
            </h3>
            <p class="pending-payment-section__description">
                Você já gerou um pagamento que ainda está aguardando confirmação.
                Para escolher outro método, cancele o atual primeiro.
            </p>

            <div class="pending-payment-section__info">
                <div class="pending-payment-section__info-row">
                    <span class="pending-payment-section__info-label">Método:</span>
                    <span id="pending-billing-type" class="pending-payment-section__info-value">-</span>
                </div>
                <div class="pending-payment-section__info-row">
                    <span class="pending-payment-section__info-label">Criado em:</span>
                    <span id="pending-created-at" class="pending-payment-section__info-value">-</span>
                </div>
            </div>

            <!-- QR Code PIX (aparece apenas se for PIX) -->
            <img id="pending-pix-qrcode" class="pending-payment-section__qrcode" src="" alt="QR Code PIX"
                style="display:none">

            <!-- Código PIX para copiar -->
            <div id="pending-pix-copy-area" style="display:none; width: 100%; max-width: 350px;">
                <div class="pix-copy-paste__wrapper" style="margin-top: var(--spacing-2);">
                    <input type="text" id="pending-pix-code" class="pix-copy-paste__input" readonly>
                    <button type="button" id="pending-pix-copy-btn" class="pix-copy-paste__btn">
                        <i data-lucide="copy"></i>
                        <span>Copiar</span>
                    </button>
                </div>
            </div>

            <!-- Linha digitável do boleto (aparece apenas se for BOLETO) -->
            <div id="pending-boleto-code" class="pending-payment-section__boleto-code" style="display:none"></div>

            <div class="pending-payment-section__actions">
                <a id="pending-boleto-download" href="#" target="_blank" class="btn-primary" style="display:none">
                    <i data-lucide="download"></i>
                    <span>Baixar Boleto</span>
                </a>
                <button type="button" id="pending-copy-btn" class="btn-primary" style="display:none">
                    <i data-lucide="copy"></i>
                    <span>Copiar código do boleto</span>
                </button>
                <button type="button" id="cancel-pending-btn" class="pending-payment-section__cancel-btn">
                    <i data-lucide="x-circle"></i>
                    <span>Cancelar e escolher outro método</span>
                </button>
            </div>
        </div>

        <div class="payment-modal__body">
            <!-- Seletor de Método de Pagamento -->
            <div class="payment-method-selector" role="tablist" aria-label="Método de pagamento">
                <button type="button" class="payment-method-btn is-active" data-method="CREDIT_CARD" role="tab"
                    aria-selected="true">
                    <i data-lucide="credit-card" aria-hidden="true"></i>
                    <span>Cartão</span>
                </button>
                <button type="button" class="payment-method-btn" data-method="PIX" role="tab" aria-selected="false">
                    <i class="fa-brands fa-pix" aria-hidden="true"></i>
                    <span>PIX</span>
                </button>
                <button type="button" class="payment-method-btn" data-method="BOLETO" role="tab" aria-selected="false">
                    <i data-lucide="scan-line" aria-hidden="true"></i>
                    <span>Boleto</span>
                </button>
            </div>

            <form id="asaasPaymentForm" class="payment-form" autocomplete="off">
