<?php

use Illuminate\Database\Capsule\Manager as DB;

/** @var \Application\Models\Usuario $user */

// Valores padrão
$cpfValue      = '';
$telefoneValue = '';
$cepValue      = '';
$enderecoValue = '';

if (isset($user) && $user) {
    // CPF – documentos.id_tipo = 1 (CPF)
    $cpfValue = DB::table('documentos')
        ->where('id_usuario', $user->id)
        ->where('id_tipo', 1) // 1 = CPF
        ->value('numero') ?? '';

    // Telefone – telefones + ddd
    $telRow = DB::table('telefones as t')
        ->leftJoin('ddd as d', 'd.id_ddd', '=', 't.id_ddd')
        ->where('t.id_usuario', $user->id)
        ->orderBy('t.id_telefone')
        ->first();

    if ($telRow) {
        $ddd = trim((string)($telRow->codigo ?? ''));
        $num = trim((string)($telRow->numero ?? ''));
        if ($ddd !== '' && $num !== '') {
            $telefoneValue = sprintf('(%s) %s', $ddd, $num);
        }
    }

    // CEP e Endereço – endereço principal
    $endereco = $user->enderecoPrincipal ?? null;
    if ($endereco && !empty($endereco->cep)) {
        $cepValue = $endereco->cep;
        $enderecoValue = ($endereco->logradouro ?? '') . ($endereco->numero ? ', ' . $endereco->numero : '');
    }
}

// Verificar se os dados estão completos para cada método
$cpfDigits = preg_replace('/\D/', '', $cpfValue);
$phoneDigits = preg_replace('/\D/', '', $telefoneValue);
$cepDigits = preg_replace('/\D/', '', $cepValue);

// PIX precisa apenas de CPF (11 dígitos)
$pixDataComplete = strlen($cpfDigits) === 11;

// Boleto precisa de CPF + CEP
$boletoDataComplete = strlen($cpfDigits) === 11 && strlen($cepDigits) === 8;
?>

<style>
    /* ========================================================================== 
     MODAL DE PAGAMENTO (MANTENDO SEU LAYOUT)
     ========================================================================== */
    .payment-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: var(--spacing-4);
        animation: fadeIn 0.3s ease;
    }

    .payment-modal--open {
        display: flex;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .payment-modal__content {
        background: var(--color-surface);
        border-radius: var(--radius-xl);
        border: 2px solid var(--glass-border);
        box-shadow: var(--shadow-xl), 0 0 0 1px color-mix(in srgb, var(--color-primary) 10%, transparent);
        max-width: 700px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .payment-modal__header {
        padding: var(--spacing-6);
        border-bottom: 1px solid var(--glass-border);
        text-align: center;
        position: relative;
    }

    .payment-modal__close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: var(--color-surface-muted);
        color: var(--color-text-muted);
        border-radius: 50%;
        cursor: pointer;
        transition: all .2s ease;
        font-size: 1.25rem;
    }

    .payment-modal__close:hover {
        background: var(--color-danger);
        color: #fff;
        transform: rotate(90deg);
    }

    .payment-modal__title {
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 700;
        margin: 0 0 var(--spacing-2);
        color: var(--color-text);
    }

    .payment-modal__subtitle {
        font-size: 1rem;
        color: var(--color-text-muted);
        margin: 0;
    }

    .payment-modal__price {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: #fff;
        font-size: 1.25rem;
        font-weight: 800;
        padding: var(--spacing-3) var(--spacing-6);
        border-radius: var(--radius-xl);
        margin-top: var(--spacing-4);
        box-shadow: 0 8px 24px color-mix(in srgb, var(--color-primary) 40%, transparent);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .payment-modal__price::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transform: translateX(-100%);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        to {
            transform: translateX(100%);
        }
    }

    .payment-modal__price:hover {
        transform: scale(1.05);
        box-shadow: 0 12px 32px color-mix(in srgb, var(--color-primary) 50%, transparent);
    }

    .payment-modal__body {
        padding: var(--spacing-6);

    }

    .payment-form {
        display: grid;
        gap: var(--spacing-4);
    }

    .payment-form__row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--spacing-3);
    }

    .payment-form__field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .payment-form__label {
        font-size: .875rem;
        font-weight: 600;
        color: var(--color-text);
    }

    .payment-form__input {
        border-radius: var(--radius-md);
        border: 2px solid var(--glass-border);
        background: var(--color-surface-muted);
        padding: 12px 14px;
        font-size: 1rem;
        color: var(--color-text);
        outline: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
    }

    .payment-form__input:hover {
        border-color: color-mix(in srgb, var(--color-primary) 50%, var(--glass-border));
    }

    .payment-form__input:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent),
            0 4px 12px color-mix(in srgb, var(--color-primary) 15%, transparent);
        background: var(--color-surface);
        transform: translateY(-1px);
    }

    .payment-form__actions {
        margin-top: var(--spacing-2);
        display: flex;
        justify-content: flex-end;
        gap: var(--spacing-3);
    }

    .btn-outline {
        border-radius: var(--radius-lg);
        padding: 10px 18px;
        border: 1px solid var(--color-surface-muted);
        background: transparent;
        color: var(--color-text-muted);
        cursor: pointer;
        font-size: .9375rem;
        transition: all .2s;
    }

    .btn-outline:hover {
        background: var(--color-surface-muted);
    }

    .btn-primary {
        border-radius: var(--radius-lg);
        padding: 14px 28px;
        border: none;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: #fff;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 8px 20px color-mix(in srgb, var(--color-primary) 40%, transparent);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .btn-primary::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-primary:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-primary:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 14px 32px color-mix(in srgb, var(--color-primary) 60%, transparent);
    }

    .btn-primary[disabled] {
        opacity: .7;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    .btn-primary[disabled]:hover::before {
        width: 0;
        height: 0;
    }

    @media (max-width:768px) {
        .payment-modal {
            padding: var(--spacing-2);
        }

        .payment-modal__content {
            max-height: 95vh;
        }

        .payment-modal__header {
            padding: 24px 20px 20px;
        }

        .payment-modal__body {
            padding: 20px;
        }

        .payment-form__row {
            grid-template-columns: 1fr;
        }
    }

    /* Força o SweetAlert2 a ficar SEMPRE acima de qualquer modal */
    .swal2-container {
        z-index: 20000 !important;
    }

    .swal2-popup {
        z-index: 20001 !important;
    }

    /* ==========================================================================
       SELETOR DE MÉTODO DE PAGAMENTO
       ========================================================================== */
    .payment-method-selector {
        display: flex;
        gap: var(--spacing-2);
        margin-bottom: var(--spacing-5);
        padding: var(--spacing-1);
        background: var(--color-surface-muted);
        border-radius: var(--radius-lg);
    }

    .payment-method-btn {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: var(--spacing-3) var(--spacing-2);
        border: 2px solid transparent;
        background: transparent;
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.3s ease;
        color: var(--color-text-muted);
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .payment-method-btn i {
        font-size: 1.25rem;
        transition: transform 0.3s ease;
    }

    .payment-method-btn:hover {
        background: var(--color-surface);
        color: var(--color-text);
    }

    .payment-method-btn:hover i {
        transform: scale(1.1);
    }

    .payment-method-btn.is-active {
        background: var(--color-surface);
        border-color: var(--color-primary);
        color: var(--color-primary);
        box-shadow: 0 4px 12px color-mix(in srgb, var(--color-primary) 25%, transparent);
    }

    .payment-method-btn.is-active i {
        transform: scale(1.15);
    }

    /* ==========================================================================
       SEÇÕES DE PAGAMENTO (CARTÃO vs PIX/BOLETO)
       ========================================================================== */
    .payment-section {
        display: none;
    }

    .payment-section.is-visible {
        display: block;
        animation: fadeSlideIn 0.3s ease;
    }

    @keyframes fadeSlideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ==========================================================================
       ÁREA DE PIX/BOLETO
       ========================================================================== */
    .pix-boleto-area {
        text-align: center;
        padding: var(--spacing-4);
    }

    .pix-boleto-area__icon {
        font-size: 3rem;
        margin-bottom: var(--spacing-3);
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .pix-boleto-area__title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--color-text);
        margin-bottom: var(--spacing-2);
    }

    .pix-boleto-area__description {
        color: var(--color-text-muted);
        font-size: 0.9375rem;
        margin-bottom: var(--spacing-4);
        line-height: 1.5;
    }

    .pix-boleto-area__description--auto {
        color: var(--color-success, #10b981);
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-2);
        background: color-mix(in srgb, var(--color-success, #10b981) 10%, transparent);
        padding: var(--spacing-3);
        border-radius: var(--radius-lg);
    }

    .pix-boleto-area__description--auto i {
        font-size: 1.125rem;
    }

    /* QR Code Container */
    .qr-code-container {
        display: none;
        flex-direction: column;
        align-items: center;
        gap: var(--spacing-4);
        padding: var(--spacing-4);
        background: var(--color-surface-muted);
        border-radius: var(--radius-lg);
        margin-bottom: var(--spacing-4);
    }

    .qr-code-container.is-visible {
        display: flex;
        animation: fadeSlideIn 0.4s ease;
    }

    .qr-code-container img {
        max-width: 200px;
        height: auto;
        border-radius: var(--radius-md);
        background: #fff;
        padding: var(--spacing-2);
    }

    .pix-copy-paste {
        width: 100%;
    }

    .pix-copy-paste__label {
        font-size: 0.8125rem;
        color: var(--color-text-muted);
        margin-bottom: var(--spacing-2);
        display: block;
    }

    .pix-copy-paste__wrapper {
        display: flex;
        gap: var(--spacing-2);
    }

    .pix-copy-paste__input {
        flex: 1;
        padding: 12px 14px;
        border: 2px solid var(--glass-border);
        background: var(--color-surface);
        border-radius: var(--radius-md);
        font-size: 0.8125rem;
        color: var(--color-text);
        font-family: monospace;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pix-copy-paste__btn {
        padding: 12px 16px;
        border: none;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: #fff;
        border-radius: var(--radius-md);
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .pix-copy-paste__btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px color-mix(in srgb, var(--color-primary) 40%, transparent);
    }

    .pix-copy-paste__btn.copied {
        background: var(--color-success);
    }

    /* Boleto */
    .boleto-container {
        display: none;
        flex-direction: column;
        align-items: center;
        gap: var(--spacing-4);
        padding: var(--spacing-4);
        background: var(--color-surface-muted);
        border-radius: var(--radius-lg);
        margin-bottom: var(--spacing-4);
    }

    .boleto-container.is-visible {
        display: flex;
        animation: fadeSlideIn 0.4s ease;
    }

    .boleto-linha-digitavel {
        width: 100%;
        padding: 14px;
        border: 2px dashed var(--glass-border);
        background: var(--color-surface);
        border-radius: var(--radius-md);
        font-family: monospace;
        font-size: 0.875rem;
        text-align: center;
        word-break: break-all;
        color: var(--color-text);
    }

    .boleto-actions {
        display: flex;
        gap: var(--spacing-3);
        flex-wrap: wrap;
        justify-content: center;
    }

    .boleto-actions .btn-primary {
        padding: 12px 20px;
    }

    /* Status de aguardando pagamento */
    .payment-pending-status {
        display: none;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-2);
        padding: var(--spacing-3);
        background: color-mix(in srgb, var(--color-warning) 15%, transparent);
        border: 1px solid var(--color-warning);
        border-radius: var(--radius-md);
        color: var(--color-warning);
        font-weight: 600;
        margin-top: var(--spacing-3);
    }

    .payment-pending-status.is-visible {
        display: flex;
    }

    .payment-pending-status i {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Campos obrigatórios para PIX/Boleto */
    .pix-boleto-fields {
        display: grid;
        gap: var(--spacing-4);
        margin-bottom: var(--spacing-4);
    }

    .pix-boleto-fields .payment-form__row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--spacing-3);
    }

    @media (max-width: 768px) {
        .payment-method-selector {
            flex-direction: column;
        }

        .pix-copy-paste__wrapper {
            flex-direction: column;
        }

        .boleto-actions {
            flex-direction: column;
            width: 100%;
        }

        .boleto-actions .btn-primary {
            width: 100%;
            justify-content: center;
        }

        .pix-boleto-fields .payment-form__row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div id="billing-modal" class="payment-modal" role="dialog" aria-labelledby="billing-modal-title" aria-modal="true">
    <div class="payment-modal__content">
        <div class="payment-modal__header">
            <button class="payment-modal__close" aria-label="Fechar modal" type="button"
                onclick="window.closeBillingModal?.()">
                <i class="fa-solid fa-times" aria-hidden="true"></i>
            </button>

            <h2 id="billing-modal-title" class="payment-modal__title">
                Pagamento Seguro
            </h2>

            <p id="billing-modal-text" class="payment-modal__subtitle">
                Escolha a forma de pagamento para ativar o Lukrato PRO
            </p>

            <div id="billing-modal-price" class="payment-modal__price" role="status" aria-live="polite">
                Selecione um plano para continuar
            </div>
        </div>

        <div class="payment-modal__body">
            <!-- Seletor de Método de Pagamento -->
            <div class="payment-method-selector" role="tablist" aria-label="Método de pagamento">
                <button type="button" class="payment-method-btn is-active" data-method="CREDIT_CARD" role="tab" aria-selected="true">
                    <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                    <span>Cartão</span>
                </button>
                <button type="button" class="payment-method-btn" data-method="PIX" role="tab" aria-selected="false">
                    <i class="fa-brands fa-pix" aria-hidden="true"></i>
                    <span>PIX</span>
                </button>
                <button type="button" class="payment-method-btn" data-method="BOLETO" role="tab" aria-selected="false">
                    <i class="fa-solid fa-barcode" aria-hidden="true"></i>
                    <span>Boleto</span>
                </button>
            </div>

            <form id="asaasPaymentForm" class="payment-form" autocomplete="off">

                <!-- Hidden fields sobre o plano selecionado -->
                <input type="hidden" name="plan_id" id="asaas_plan_id">
                <input type="hidden" name="plan_code" id="asaas_plan_code">
                <input type="hidden" name="plan_name" id="asaas_plan_name">
                <input type="hidden" name="amount" id="asaas_plan_amount">
                <input type="hidden" name="interval" id="asaas_plan_interval">
                <input type="hidden" name="cycle" id="asaas_plan_cycle" value="monthly">
                <input type="hidden" name="months" id="asaas_plan_months" value="1">
                <input type="hidden" name="discount" id="asaas_plan_discount" value="0">
                <input type="hidden" name="amount_base_monthly" id="asaas_plan_amount_base_monthly" value="">
                <input type="hidden" name="billing_type" id="asaas_billing_type" value="CREDIT_CARD">

                <!-- ========== SEÇÃO CARTÃO DE CRÉDITO ========== -->
                <div id="credit-card-section" class="payment-section is-visible">
                    <div class="payment-form__field">
                        <label for="card_holder" class="payment-form__label">Nome no cartão</label>
                        <input type="text" id="card_holder" name="card_holder" class="payment-form__input"
                            value="<?= htmlspecialchars($user->nome ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ex: João da Silva">
                    </div>

                    <div class="payment-form__row">
                        <div class="payment-form__field">
                            <label for="card_number" class="payment-form__label">Número do cartão</label>
                            <input type="text" id="card_number" name="card_number" inputmode="numeric"
                                maxlength="19" class="payment-form__input" placeholder="0000 0000 0000 0000">
                        </div>

                        <div class="payment-form__field">
                            <label for="card_cvv" class="payment-form__label">CVV</label>
                            <input type="text" id="card_cvv" name="card_cvv" inputmode="numeric" maxlength="4"
                                class="payment-form__input" placeholder="123">
                        </div>
                    </div>

                    <div class="payment-form__row">
                        <div class="payment-form__field">
                            <label for="card_expiry" class="payment-form__label">Validade (MM/AA)</label>
                            <input type="text" id="card_expiry" name="card_expiry" inputmode="numeric"
                                maxlength="5" class="payment-form__input" placeholder="07/29">
                        </div>

                        <div class="payment-form__field">
                            <label for="card_cpf" class="payment-form__label">CPF do titular</label>
                            <input type="text" id="card_cpf" name="card_cpf" class="payment-form__input"
                                placeholder="000.000.000-00"
                                value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="payment-form__row">
                        <div class="payment-form__field">
                            <label for="card_phone" class="payment-form__label">Telefone</label>
                            <input type="text" id="card_phone" name="card_phone" class="payment-form__input"
                                placeholder="(00) 00000-0000"
                                value="<?= htmlspecialchars($telefoneValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="payment-form__field">
                            <label for="card_cep" class="payment-form__label">CEP</label>
                            <input type="text" id="card_cep" name="card_cep" class="payment-form__input"
                                placeholder="00000-000" value="<?= htmlspecialchars($cepValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>

                <!-- ========== SEÇÃO PIX ========== -->
                <div id="pix-section" class="payment-section">
                    <div class="pix-boleto-area">
                        <div class="pix-boleto-area__icon">
                            <i class="fa-brands fa-pix"></i>
                        </div>
                        <h3 class="pix-boleto-area__title">Pagamento via PIX</h3>
                        <?php if ($pixDataComplete): ?>
                        <p class="pix-boleto-area__description pix-boleto-area__description--auto">
                            <i class="fa-solid fa-check-circle"></i>
                            Seus dados já estão cadastrados! Clique em "Gerar PIX" para continuar.
                        </p>
                        <?php else: ?>
                        <p class="pix-boleto-area__description">
                            Pague instantaneamente usando o QR Code ou copie o código PIX.<br>
                            O plano será ativado automaticamente após a confirmação do pagamento.
                        </p>
                        <?php endif; ?>

                        <!-- Campos obrigatórios para PIX (escondidos se dados completos) -->
                        <div class="pix-boleto-fields" <?= $pixDataComplete ? 'style="display:none"' : '' ?>>
                            <div class="payment-form__row">
                                <div class="payment-form__field">
                                    <label for="pix_cpf" class="payment-form__label">CPF</label>
                                    <input type="text" id="pix_cpf" name="pix_cpf" class="payment-form__input"
                                        placeholder="000.000.000-00"
                                        value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="payment-form__field">
                                    <label for="pix_phone" class="payment-form__label">Telefone</label>
                                    <input type="text" id="pix_phone" name="pix_phone" class="payment-form__input"
                                        placeholder="(00) 00000-0000"
                                        value="<?= htmlspecialchars($telefoneValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- QR Code (aparece após gerar) -->
                        <div id="pix-qrcode-container" class="qr-code-container">
                            <img id="pix-qrcode-img" src="" alt="QR Code PIX">
                            <div class="pix-copy-paste">
                                <span class="pix-copy-paste__label">Código PIX (copia e cola)</span>
                                <div class="pix-copy-paste__wrapper">
                                    <input type="text" id="pix-copy-paste-code" class="pix-copy-paste__input" readonly>
                                    <button type="button" id="pix-copy-btn" class="pix-copy-paste__btn">
                                        <i class="fa-solid fa-copy"></i>
                                        <span>Copiar</span>
                                    </button>
                                </div>
                            </div>
                            <div id="pix-pending-status" class="payment-pending-status">
                                <i class="fa-solid fa-clock"></i>
                                <span>Aguardando pagamento...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========== SEÇÃO BOLETO ========== -->
                <div id="boleto-section" class="payment-section">
                    <div class="pix-boleto-area">
                        <div class="pix-boleto-area__icon">
                            <i class="fa-solid fa-barcode"></i>
                        </div>
                        <h3 class="pix-boleto-area__title">Pagamento via Boleto</h3>
                        <?php if ($boletoDataComplete): ?>
                        <p class="pix-boleto-area__description pix-boleto-area__description--auto">
                            <i class="fa-solid fa-check-circle"></i>
                            Seus dados já estão cadastrados! Clique em "Gerar Boleto" para continuar.
                        </p>
                        <?php else: ?>
                        <p class="pix-boleto-area__description">
                            Gere o boleto bancário e pague em qualquer banco ou lotérica.<br>
                            O plano será ativado em até 3 dias úteis após a confirmação.
                        </p>
                        <?php endif; ?>

                        <!-- Campos obrigatórios para Boleto (escondidos se dados completos) -->
                        <div class="pix-boleto-fields" <?= $boletoDataComplete ? 'style="display:none"' : '' ?>>
                            <div class="payment-form__row">
                                <div class="payment-form__field">
                                    <label for="boleto_cpf" class="payment-form__label">CPF</label>
                                    <input type="text" id="boleto_cpf" name="boleto_cpf" class="payment-form__input"
                                        placeholder="000.000.000-00"
                                        value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="payment-form__field">
                                    <label for="boleto_phone" class="payment-form__label">Telefone</label>
                                    <input type="text" id="boleto_phone" name="boleto_phone" class="payment-form__input"
                                        placeholder="(00) 00000-0000"
                                        value="<?= htmlspecialchars($telefoneValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>

                            <div class="payment-form__row">
                                <div class="payment-form__field">
                                    <label for="boleto_cep" class="payment-form__label">CEP</label>
                                    <input type="text" id="boleto_cep" name="boleto_cep" class="payment-form__input"
                                        placeholder="00000-000" value="<?= htmlspecialchars($cepValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="payment-form__field">
                                    <label for="boleto_endereco" class="payment-form__label">Endereço</label>
                                    <input type="text" id="boleto_endereco" name="boleto_endereco" class="payment-form__input"
                                        placeholder="Rua, número"
                                        value="<?= htmlspecialchars($enderecoValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Boleto gerado (aparece após gerar) -->
                        <div id="boleto-container" class="boleto-container">
                            <div class="boleto-linha-digitavel" id="boleto-linha-digitavel"></div>
                            <div class="boleto-actions">
                                <button type="button" id="boleto-copy-btn" class="btn-primary">
                                    <i class="fa-solid fa-copy"></i>
                                    <span>Copiar código</span>
                                </button>
                                <a id="boleto-download-link" href="#" target="_blank" class="btn-primary">
                                    <i class="fa-solid fa-download"></i>
                                    <span>Baixar boleto</span>
                                </a>
                            </div>
                            <div id="boleto-pending-status" class="payment-pending-status">
                                <i class="fa-solid fa-clock"></i>
                                <span>Aguardando pagamento (até 3 dias úteis)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-form__actions">
                    <button type="button" class="btn-outline" onclick="window.closeBillingModal?.()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary" id="asaasSubmitBtn">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <span>Pagar com cartão</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        const BASE_URL = '<?= BASE_URL ?>';
        
        // Dados pré-preenchidos do banco
        const userDataComplete = {
            pix: <?= $pixDataComplete ? 'true' : 'false' ?>,
            boleto: <?= $boletoDataComplete ? 'true' : 'false' ?>,
            cpf: '<?= htmlspecialchars($cpfDigits, ENT_QUOTES, 'UTF-8') ?>',
            phone: '<?= htmlspecialchars($phoneDigits, ENT_QUOTES, 'UTF-8') ?>',
            cep: '<?= htmlspecialchars($cepDigits, ENT_QUOTES, 'UTF-8') ?>',
            endereco: '<?= htmlspecialchars($enderecoValue, ENT_QUOTES, 'UTF-8') ?>',
            email: <?= json_encode($user->email ?? '') ?>
        };

        // ===============================
        // ELEMENTOS DO DOM
        // ===============================
        const modal = document.getElementById('billing-modal');
        const modalTitle = document.getElementById('billing-modal-title');
        const modalText = document.getElementById('billing-modal-text');
        const modalPrice = document.getElementById('billing-modal-price');
        const form = document.getElementById('asaasPaymentForm');
        const submitBtn = document.getElementById('asaasSubmitBtn');
        
        // Inputs de cartão
        const cardNumberInput = document.getElementById('card_number');
        const cardExpiryInput = document.getElementById('card_expiry');
        const cardCpfInput = document.getElementById('card_cpf');
        const cardPhoneInput = document.getElementById('card_phone');
        
        // Inputs PIX
        const pixCpfInput = document.getElementById('pix_cpf');
        const pixPhoneInput = document.getElementById('pix_phone');
        
        // Inputs Boleto
        const boletoCpfInput = document.getElementById('boleto_cpf');
        const boletoPhoneInput = document.getElementById('boleto_phone');
        const boletoCepInput = document.getElementById('boleto_cep');

        // Hidden fields
        const inputPlanId = document.getElementById('asaas_plan_id');
        const inputPlanCode = document.getElementById('asaas_plan_code');
        const inputPlanName = document.getElementById('asaas_plan_name');
        const inputPlanAmount = document.getElementById('asaas_plan_amount');
        const inputPlanInterval = document.getElementById('asaas_plan_interval');
        const inputPlanCycle = document.getElementById('asaas_plan_cycle');
        const inputPlanMonths = document.getElementById('asaas_plan_months');
        const inputPlanDiscount = document.getElementById('asaas_plan_discount');
        const inputBaseMonthly = document.getElementById('asaas_plan_amount_base_monthly');
        const inputBillingType = document.getElementById('asaas_billing_type');

        // Seções de pagamento
        const creditCardSection = document.getElementById('credit-card-section');
        const pixSection = document.getElementById('pix-section');
        const boletoSection = document.getElementById('boleto-section');
        const paymentMethodBtns = document.querySelectorAll('.payment-method-btn');

        // PIX elements
        const pixQrCodeContainer = document.getElementById('pix-qrcode-container');
        const pixQrCodeImg = document.getElementById('pix-qrcode-img');
        const pixCopyPasteCode = document.getElementById('pix-copy-paste-code');
        const pixCopyBtn = document.getElementById('pix-copy-btn');
        const pixPendingStatus = document.getElementById('pix-pending-status');

        // Boleto elements
        const boletoContainer = document.getElementById('boleto-container');
        const boletoLinhaDigitavel = document.getElementById('boleto-linha-digitavel');
        const boletoCopyBtn = document.getElementById('boleto-copy-btn');
        const boletoDownloadLink = document.getElementById('boleto-download-link');
        const boletoPendingStatus = document.getElementById('boleto-pending-status');

        const planButtons = document.querySelectorAll('[data-plan-button]');

        const currencyFormatter = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        let currentPlanConfig = null;
        let currentBillingType = 'CREDIT_CARD';
        let paymentPollingInterval = null;

        // ===============================
        // FORMATADORES
        // ===============================
        const onlyDigits = (v = '') => v.replace(/\D+/g, '');

        function formatCardNumber(value) {
            return onlyDigits(value).slice(0, 16).replace(/(\d{4})(?=\d)/g, '$1 ').trim();
        }

        function formatExpiry(value) {
            const digits = onlyDigits(value).slice(0, 4);
            if (digits.length <= 2) return digits;
            return digits.replace(/(\d{2})(\d{0,2})/, (m, mm, yy) => `${mm}/${yy}`);
        }

        function formatCpf(value) {
            const digits = onlyDigits(value).slice(0, 11);
            return digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/(\d{3})\.(\d{3})\.(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        }

        function formatPhone(value) {
            const digits = onlyDigits(value).slice(0, 11);
            if (digits.length <= 2) return digits;
            if (digits.length <= 6) return digits.replace(/(\d{2})(\d+)/, '($1) $2');
            if (digits.length <= 10) return digits.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
            return digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }

        function formatCep(value) {
            const digits = onlyDigits(value).slice(0, 8);
            if (digits.length <= 5) return digits;
            return digits.replace(/(\d{5})(\d+)/, '$1-$2');
        }

        // Event listeners para formatação
        cardNumberInput?.addEventListener('input', (e) => e.target.value = formatCardNumber(e.target.value));
        cardExpiryInput?.addEventListener('input', (e) => e.target.value = formatExpiry(e.target.value));
        
        // Formatação de CPF para todos os campos
        [cardCpfInput, pixCpfInput, boletoCpfInput].forEach(input => {
            input?.addEventListener('input', (e) => e.target.value = formatCpf(e.target.value));
        });

        // Formatação de telefone
        [cardPhoneInput, pixPhoneInput, boletoPhoneInput].forEach(input => {
            input?.addEventListener('input', (e) => e.target.value = formatPhone(e.target.value));
        });

        // Formatação de CEP
        boletoCepInput?.addEventListener('input', (e) => e.target.value = formatCep(e.target.value));

        // ===============================
        // FUNÇÕES AUXILIARES
        // ===============================
        function calcTotal(baseMonthly, months, discountPct) {
            const base = Number(baseMonthly) || 0;
            const m = Number(months) || 1;
            const d = Number(discountPct) || 0;
            const total = base * m * (1 - (d / 100));
            return Math.round((total + Number.EPSILON) * 100) / 100;
        }

        function cycleLabel(months) {
            if (months === 12) return 'ano';
            if (months === 6) return 'semestre';
            return 'mês';
        }

        function getActiveCycleFromUI() {
            const active = document.querySelector('.plan-billing-toggle__btn.is-active');
            if (!active) return { cycle: 'monthly', months: 1, discount: 0 };
            return {
                cycle: active.dataset.cycle || 'monthly',
                months: Number(active.dataset.months || '1'),
                discount: Number(active.dataset.discount || '0')
            };
        }

        function syncPlanHiddenFields() {
            if (!currentPlanConfig) return;

            const total = calcTotal(currentPlanConfig.monthlyBase, currentPlanConfig.months, currentPlanConfig.discount);

            inputPlanId.value = currentPlanConfig.planId;
            inputPlanCode.value = currentPlanConfig.planCode;
            inputPlanName.value = currentPlanConfig.planName;
            inputPlanAmount.value = String(total);
            inputPlanInterval.value = cycleLabel(currentPlanConfig.months);
            inputPlanCycle.value = currentPlanConfig.cycle;
            inputPlanMonths.value = String(currentPlanConfig.months);
            inputPlanDiscount.value = String(currentPlanConfig.discount);
            inputBaseMonthly.value = String(currentPlanConfig.monthlyBase);
            inputBillingType.value = currentBillingType;

            if (modalPrice) {
                modalPrice.textContent = `${currentPlanConfig.planName} - ${currencyFormatter.format(total)}/${cycleLabel(currentPlanConfig.months)}`;
            }
        }

        function updateSubmitButton() {
            const btnSpan = submitBtn?.querySelector('span');
            const btnIcon = submitBtn?.querySelector('i');
            
            if (!btnSpan || !btnIcon) return;

            switch (currentBillingType) {
                case 'PIX':
                    btnSpan.textContent = 'Gerar PIX';
                    btnIcon.className = 'fa-brands fa-pix';
                    break;
                case 'BOLETO':
                    btnSpan.textContent = 'Gerar Boleto';
                    btnIcon.className = 'fa-solid fa-barcode';
                    break;
                default:
                    btnSpan.textContent = 'Pagar com cartão';
                    btnIcon.className = 'fa-solid fa-lock';
            }
        }

        function updateModalText() {
            if (!modalText) return;

            const planName = currentPlanConfig?.planName || 'Lukrato PRO';

            switch (currentBillingType) {
                case 'PIX':
                    modalText.textContent = `Ativando o plano ${planName}. Pague instantaneamente via PIX.`;
                    break;
                case 'BOLETO':
                    modalText.textContent = `Ativando o plano ${planName}. Gere o boleto para pagamento.`;
                    break;
                default:
                    modalText.textContent = `Ativando o plano ${planName}. Complete os dados do cartão.`;
            }
        }

        function switchPaymentMethod(method) {
            currentBillingType = method;

            // Atualizar botões
            paymentMethodBtns.forEach(btn => {
                const isActive = btn.dataset.method === method;
                btn.classList.toggle('is-active', isActive);
                btn.setAttribute('aria-selected', isActive);
            });

            // Atualizar seções visíveis
            creditCardSection?.classList.toggle('is-visible', method === 'CREDIT_CARD');
            pixSection?.classList.toggle('is-visible', method === 'PIX');
            boletoSection?.classList.toggle('is-visible', method === 'BOLETO');

            // Esconder containers de resultado se mudar de método
            pixQrCodeContainer?.classList.remove('is-visible');
            boletoContainer?.classList.remove('is-visible');

            // Esconder campos de formulário se dados já existem
            const pixFieldsContainer = pixSection?.querySelector('.pix-boleto-fields');
            const boletoFieldsContainer = boletoSection?.querySelector('.pix-boleto-fields');
            
            if (pixFieldsContainer) {
                pixFieldsContainer.style.display = userDataComplete.pix ? 'none' : 'block';
            }
            if (boletoFieldsContainer) {
                boletoFieldsContainer.style.display = userDataComplete.boleto ? 'none' : 'block';
            }

            // Atualizar botão e texto
            updateSubmitButton();
            updateModalText();
            syncPlanHiddenFields();

            // Habilitar submit
            if (submitBtn) submitBtn.disabled = false;
        }

        // ===============================
        // COPY TO CLIPBOARD
        // ===============================
        async function copyToClipboard(text, button) {
            try {
                await navigator.clipboard.writeText(text);
                const span = button.querySelector('span');
                const originalText = span?.textContent;
                
                button.classList.add('copied');
                if (span) span.textContent = 'Copiado!';
                
                setTimeout(() => {
                    button.classList.remove('copied');
                    if (span) span.textContent = originalText;
                }, 2000);
            } catch (err) {
                console.error('Erro ao copiar:', err);
                window.Swal?.fire('Erro', 'Não foi possível copiar. Selecione e copie manualmente.', 'warning');
            }
        }

        pixCopyBtn?.addEventListener('click', () => {
            const code = pixCopyPasteCode?.value;
            if (code) copyToClipboard(code, pixCopyBtn);
        });

        boletoCopyBtn?.addEventListener('click', () => {
            const code = boletoLinhaDigitavel?.textContent;
            if (code) copyToClipboard(code, boletoCopyBtn);
        });

        // ===============================
        // POLLING PARA VERIFICAR PAGAMENTO
        // ===============================
        function startPaymentPolling(paymentId) {
            if (paymentPollingInterval) clearInterval(paymentPollingInterval);

            paymentPollingInterval = setInterval(async () => {
                try {
                    const resp = await fetch(`${BASE_URL}premium/check-payment/${paymentId}`, {
                        credentials: 'include',
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await resp.json();

                    if (json.status === 'success' && json.data?.paid) {
                        clearInterval(paymentPollingInterval);
                        window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado com sucesso.', 'success')
                            .then(() => window.location.reload());
                    }
                } catch (err) {
                    console.error('[Polling] Erro:', err);
                }
            }, 5000); // Verificar a cada 5 segundos
        }

        function stopPaymentPolling() {
            if (paymentPollingInterval) {
                clearInterval(paymentPollingInterval);
                paymentPollingInterval = null;
            }
        }

        // ===============================
        // MODAL OPEN/CLOSE
        // ===============================
        function openBillingModal(planConfig) {
            currentPlanConfig = planConfig;
            currentBillingType = 'CREDIT_CARD';

            if (modalTitle) modalTitle.textContent = 'Pagamento Seguro';

            // Reset para estado inicial
            switchPaymentMethod('CREDIT_CARD');
            syncPlanHiddenFields();

            modal.classList.add('payment-modal--open');
            document.body.style.overflow = 'hidden';
        }

        function closeBillingModal() {
            modal.classList.remove('payment-modal--open');
            document.body.style.overflow = '';
            currentPlanConfig = null;
            stopPaymentPolling();
            
            // Reset containers
            pixQrCodeContainer?.classList.remove('is-visible');
            boletoContainer?.classList.remove('is-visible');
            
            if (form) form.reset();
        }

        window.closeBillingModal = closeBillingModal;
        window.openBillingModal = openBillingModal;

        // ===============================
        // EVENT LISTENERS
        // ===============================

        // Seletor de método de pagamento
        paymentMethodBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const method = btn.dataset.method;
                if (method) switchPaymentMethod(method);
            });
        });

        // Botões dos planos -> abre modal
        planButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const planId = btn.dataset.planId || null;
                const planCode = btn.dataset.planCode || null;
                const planName = btn.dataset.planName || 'Lukrato PRO';
                const monthlyBase = Number(btn.dataset.planMonthly ?? btn.dataset.planAmount ?? '0');

                if (!planId || !planCode || !monthlyBase || Number.isNaN(monthlyBase)) {
                    window.Swal?.fire('Plano inválido', 'Não foi possível identificar o plano. Recarregue a página.', 'warning');
                    return;
                }

                const picked = getActiveCycleFromUI();

                openBillingModal({
                    planId,
                    planCode,
                    planName,
                    monthlyBase,
                    cycle: picked.cycle,
                    months: picked.months,
                    discount: picked.discount
                });
            });
        });

        // Toggle Mensal/Semestral/Anual
        document.querySelectorAll('.plan-billing-toggle').forEach((group) => {
            const buttons = group.querySelectorAll('.plan-billing-toggle__btn');

            buttons.forEach((b) => {
                b.addEventListener('click', () => {
                    buttons.forEach(x => x.classList.remove('is-active'));
                    b.classList.add('is-active');

                    // Atualizar o preço exibido no card do plano Pro
                    const proPriceElement = document.getElementById('planProPrice');
                    if (proPriceElement) {
                        const basePrice = Number(proPriceElement.dataset.basePrice || 0);
                        const months = Number(b.dataset.months || 1);
                        const discount = Number(b.dataset.discount || 0);
                        const total = calcTotal(basePrice, months, discount);
                        const period = cycleLabel(months);

                        const priceValueElement = proPriceElement.querySelector('.plan-card__price-value');
                        const pricePeriodElement = proPriceElement.querySelector('.plan-card__price-period');

                        if (priceValueElement) {
                            priceValueElement.style.animation = 'none';
                            void priceValueElement.offsetWidth;
                            priceValueElement.textContent = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                            priceValueElement.style.animation = 'priceAppear 0.5s ease';
                        }
                        if (pricePeriodElement) {
                            pricePeriodElement.textContent = `/${period}`;
                        }
                    }

                    if (!currentPlanConfig) return;

                    currentPlanConfig.cycle = b.dataset.cycle || 'monthly';
                    currentPlanConfig.months = Number(b.dataset.months || '1');
                    currentPlanConfig.discount = Number(b.dataset.discount || '0');

                    if (modal?.classList.contains('payment-modal--open')) syncPlanHiddenFields();
                });
            });
        });

        // ===============================
        // SUBMIT DO FORMULÁRIO
        // ===============================
        form?.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!currentPlanConfig) {
                window.Swal?.fire('Plano inválido', 'Selecione um plano novamente.', 'warning');
                return;
            }

            syncPlanHiddenFields();

            const fd = new FormData(form);
            let payload = {
                plan_id: currentPlanConfig.planId,
                plan_code: currentPlanConfig.planCode,
                cycle: inputPlanCycle.value,
                months: Number(inputPlanMonths.value || 1),
                discount: Number(inputPlanDiscount.value || 0),
                amount_base_monthly: Number(inputBaseMonthly.value || currentPlanConfig.monthlyBase || 0),
                amount: Number(inputPlanAmount.value || 0),
                billingType: currentBillingType
            };

            // Validar e montar payload baseado no método
            if (currentBillingType === 'CREDIT_CARD') {
                const holderName = fd.get('card_holder')?.toString().trim() || '';
                const cardNumber = (fd.get('card_number')?.toString().replace(/\s+/g, '') || '');
                const cardCvv = fd.get('card_cvv')?.toString().trim() || '';
                const cardExpiry = fd.get('card_expiry')?.toString().trim() || '';
                const cpf = onlyDigits(fd.get('card_cpf')?.toString() || '');
                const phone = onlyDigits(fd.get('card_phone')?.toString() || '');
                const cep = onlyDigits(fd.get('card_cep')?.toString() || '');

                if (!holderName || !cardNumber || !cardCvv || !cardExpiry || !cpf || !phone || !cep) {
                    window.Swal?.fire('Campos obrigatórios', 'Preencha todos os dados do cartão.', 'warning');
                    return;
                }

                const [month, year] = cardExpiry.split('/').map(v => v.trim());
                if (!month || !year) {
                    window.Swal?.fire('Validade inválida', 'Informe a validade no formato MM/AA.', 'warning');
                    return;
                }

                payload.creditCard = {
                    holderName,
                    number: cardNumber,
                    expiryMonth: month,
                    expiryYear: (year.length === 2 ? '20' + year : year),
                    ccv: cardCvv
                };
                payload.creditCardHolderInfo = {
                    name: holderName,
                    email: <?= json_encode($user->email ?? '') ?>,
                    cpfCnpj: cpf,
                    mobilePhone: phone,
                    postalCode: cep
                };

            } else if (currentBillingType === 'PIX') {
                // Usar dados do banco se disponíveis, senão do formulário
                const cpf = userDataComplete.pix ? userDataComplete.cpf : onlyDigits(fd.get('pix_cpf')?.toString() || '');
                const phone = userDataComplete.pix ? userDataComplete.phone : onlyDigits(fd.get('pix_phone')?.toString() || '');

                if (!cpf || cpf.length !== 11) {
                    window.Swal?.fire('CPF inválido', 'Informe um CPF válido para gerar o PIX.', 'warning');
                    return;
                }

                payload.holderInfo = {
                    cpfCnpj: cpf,
                    mobilePhone: phone,
                    email: userDataComplete.email
                };

            } else if (currentBillingType === 'BOLETO') {
                // Usar dados do banco se disponíveis, senão do formulário
                const cpf = userDataComplete.boleto ? userDataComplete.cpf : onlyDigits(fd.get('boleto_cpf')?.toString() || '');
                const phone = userDataComplete.boleto ? userDataComplete.phone : onlyDigits(fd.get('boleto_phone')?.toString() || '');
                const cep = userDataComplete.boleto ? userDataComplete.cep : onlyDigits(fd.get('boleto_cep')?.toString() || '');
                const endereco = userDataComplete.boleto ? userDataComplete.endereco : (fd.get('boleto_endereco')?.toString().trim() || '');

                if (!cpf || cpf.length !== 11) {
                    window.Swal?.fire('CPF inválido', 'Informe um CPF válido para gerar o boleto.', 'warning');
                    return;
                }

                if (!cep || cep.length !== 8) {
                    window.Swal?.fire('CEP inválido', 'Informe um CEP válido para gerar o boleto.', 'warning');
                    return;
                }

                payload.holderInfo = {
                    cpfCnpj: cpf,
                    mobilePhone: phone,
                    postalCode: cep,
                    address: endereco,
                    email: userDataComplete.email
                };
            }

            // Enviar requisição
            try {
                submitBtn.disabled = true;
                const originalBtnText = submitBtn.querySelector('span').textContent;
                submitBtn.querySelector('span').textContent = 'Processando...';

                const loadingTitle = currentBillingType === 'PIX' ? 'Gerando PIX...' :
                                    currentBillingType === 'BOLETO' ? 'Gerando Boleto...' :
                                    'Processando pagamento...';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: loadingTitle,
                        text: 'Aguarde enquanto processamos sua solicitação.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                }

                const resp = await fetch(`${BASE_URL}premium/checkout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.CSRF || ''
                    },
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });

                const json = await resp.json().catch(() => null);

                if (!resp.ok || !json || json.status !== 'success') {
                    throw new Error(json?.message || 'Não foi possível processar a solicitação.');
                }

                Swal?.close();

                // Tratamento baseado no método de pagamento
                if (currentBillingType === 'CREDIT_CARD') {
                    window.Swal?.fire('Sucesso! 🎉', json?.message || 'Pagamento realizado com sucesso.', 'success')
                        .then(() => window.location.reload());
                    closeBillingModal();

                } else if (currentBillingType === 'PIX') {
                    // Exibir QR Code
                    if (json.data?.pix) {
                        const pix = json.data.pix;
                        
                        if (pix.qrCodeImage) {
                            pixQrCodeImg.src = pix.qrCodeImage;
                        }
                        if (pix.payload) {
                            pixCopyPasteCode.value = pix.payload;
                        }
                        
                        pixQrCodeContainer?.classList.add('is-visible');
                        pixPendingStatus?.classList.add('is-visible');
                        submitBtn.disabled = true;

                        // Iniciar polling
                        if (json.data.paymentId) {
                            startPaymentPolling(json.data.paymentId);
                        }

                        window.Swal?.fire({
                            icon: 'success',
                            title: 'PIX gerado!',
                            text: 'Escaneie o QR Code ou copie o código para pagar.',
                            confirmButtonText: 'Entendi'
                        });
                    }

                } else if (currentBillingType === 'BOLETO') {
                    // Exibir boleto
                    if (json.data?.boleto) {
                        const boleto = json.data.boleto;
                        
                        if (boleto.identificationField) {
                            boletoLinhaDigitavel.textContent = boleto.identificationField;
                        }
                        if (boleto.bankSlipUrl) {
                            boletoDownloadLink.href = boleto.bankSlipUrl;
                        }
                        
                        boletoContainer?.classList.add('is-visible');
                        boletoPendingStatus?.classList.add('is-visible');
                        submitBtn.disabled = true;

                        // Iniciar polling
                        if (json.data.paymentId) {
                            startPaymentPolling(json.data.paymentId);
                        }

                        window.Swal?.fire({
                            icon: 'success',
                            title: 'Boleto gerado!',
                            text: 'Copie o código ou baixe o PDF para pagar.',
                            confirmButtonText: 'Entendi'
                        });
                    }
                }

            } catch (error) {
                console.error('[Checkout] Erro:', error);
                Swal?.close();
                window.Swal?.fire('Erro', error.message || 'Erro ao processar. Tente novamente.', 'error');
            } finally {
                if (currentBillingType === 'CREDIT_CARD') {
                    submitBtn.disabled = false;
                    updateSubmitButton();
                }
            }
        });
    })();
</script>