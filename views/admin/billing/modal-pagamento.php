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

    // CEP e Endereço – endereço principal ou primeiro endereço disponível
    $endereco = $user->enderecoPrincipal ?? null;

    // Se não tem endereço principal, tentar buscar qualquer endereço
    if (!$endereco || empty($endereco->cep)) {
        $endereco = DB::table('enderecos')
            ->where('user_id', $user->id)
            ->whereNotNull('cep')
            ->where('cep', '!=', '')
            ->first();
    }

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

    /* Estado desabilitado/travado das abas */
    .payment-method-btn.is-locked {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    .payment-method-btn.is-locked:hover {
        background: transparent;
        color: var(--color-text-muted);
    }

    .payment-method-btn.is-locked:hover i {
        transform: none;
    }

    .payment-method-selector.is-locked {
        position: relative;
    }

    .payment-method-selector.is-locked::after {
        content: 'Aguardando pagamento';
        position: absolute;
        bottom: -20px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.75rem;
        color: var(--color-warning);
        white-space: nowrap;
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

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
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

    /* ========================================================================== 
     SEÇÃO DE CUPOM DE DESCONTO
     ========================================================================== */
    .coupon-section {
        margin-top: var(--spacing-4);
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
        padding: 0 var(--spacing-2);
    }

    .coupon-section__input-group {
        display: flex;
        gap: var(--spacing-2);
        align-items: flex-end;
    }

    .coupon-section__field {
        flex: 1;
    }

    .coupon-section__label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--color-text);
        margin-bottom: 6px;
    }

    .coupon-section__label i {
        color: var(--color-primary);
    }

    .coupon-section__input {
        width: 100%;
        padding: 12px 14px;
        border: 2px solid var(--glass-border);
        border-radius: var(--radius-md);
        background: var(--color-surface-muted);
        color: var(--color-text);
        font-size: 1rem;
        text-transform: uppercase;
        outline: none;
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .coupon-section__input:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent);
        background: var(--color-surface);
    }

    .coupon-section__apply-btn {
        padding: 12px 24px;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: white;
        border: none;
        border-radius: var(--radius-md);
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9375rem;
        transition: all 0.3s ease;
        white-space: nowrap;
        box-shadow: 0 4px 12px color-mix(in srgb, var(--color-primary) 30%, transparent);
    }

    .coupon-section__apply-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px color-mix(in srgb, var(--color-primary) 40%, transparent);
    }

    .coupon-section__apply-btn:active {
        transform: translateY(0);
    }

    .coupon-section__feedback {
        margin-top: 8px;
        font-size: 0.875rem;
        display: none;
        padding: 8px 12px;
        border-radius: var(--radius-md);
        font-weight: 500;
    }

    .coupon-section__discount-display {
        margin-top: 12px;
        padding: 14px 16px;
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05));
        border: 2px solid rgba(34, 197, 94, 0.3);
        border-radius: var(--radius-lg);
        color: #16a34a;
        font-weight: 600;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: var(--spacing-3);
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .coupon-section__discount-display.show {
        display: flex;
    }

    .coupon-section__discount-text {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
    }

    .coupon-section__discount-text i {
        font-size: 1.1rem;
    }

    .coupon-section__remove-btn {
        background: rgba(220, 38, 38, 0.1);
        border: 1px solid rgba(220, 38, 38, 0.3);
        color: #dc2626;
        padding: 6px 12px;
        border-radius: var(--radius-md);
        cursor: pointer;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .coupon-section__remove-btn:hover {
        background: rgba(220, 38, 38, 0.15);
        border-color: rgba(220, 38, 38, 0.5);
        transform: translateY(-1px);
    }

    .coupon-section__remove-btn:active {
        transform: translateY(0);
    }

    /* Responsivo Mobile */
    @media (max-width: 640px) {
        .coupon-section {
            max-width: 100%;
            padding: 0 var(--spacing-3);
        }

        .coupon-section__input-group {
            flex-direction: column;
            align-items: stretch;
            gap: var(--spacing-3);
        }

        .coupon-section__apply-btn {
            width: 100%;
            padding: 14px;
        }

        .coupon-section__discount-display {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--spacing-2);
            padding: 12px;
        }

        .coupon-section__remove-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* ========================================================================== 
     SEÇÃO DE PAGAMENTO PENDENTE (BLOQUEIO)
     ========================================================================== */
    .pending-payment-section {
        display: none;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: var(--spacing-6);
        gap: var(--spacing-4);
    }

    .pending-payment-section.is-visible {
        display: flex;
    }

    .pending-payment-section__icon {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--color-warning) 0%, color-mix(in srgb, var(--color-warning) 80%, var(--color-primary)) 100%);
        border-radius: 50%;
        font-size: 2rem;
        color: #fff;
        box-shadow: 0 8px 25px color-mix(in srgb, var(--color-warning) 40%, transparent);
    }

    .pending-payment-section__title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--color-text);
        margin: 0;
    }

    .pending-payment-section__description {
        font-size: 0.95rem;
        color: var(--color-text-muted);
        margin: 0;
        max-width: 400px;
        line-height: 1.6;
    }

    .pending-payment-section__info {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-2);
        padding: var(--spacing-4);
        background: var(--color-surface-muted);
        border-radius: var(--radius-md);
        width: 100%;
        max-width: 350px;
    }

    .pending-payment-section__info-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
    }

    .pending-payment-section__info-label {
        color: var(--color-text-muted);
    }

    .pending-payment-section__info-value {
        color: var(--color-text);
        font-weight: 600;
    }

    .pending-payment-section__info-value.pending-type-pix {
        color: #32bcad;
    }

    .pending-payment-section__info-value.pending-type-boleto {
        color: #f59e0b;
    }

    .pending-payment-section__qrcode {
        max-width: 200px;
        border-radius: var(--radius-md);
        border: 2px solid var(--glass-border);
    }

    .pending-payment-section__boleto-code {
        font-family: monospace;
        font-size: 0.75rem;
        word-break: break-all;
        padding: var(--spacing-3);
        background: var(--color-surface-muted);
        border-radius: var(--radius-md);
        color: var(--color-text);
        max-width: 100%;
    }

    .pending-payment-section__actions {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-3);
        width: 100%;
        max-width: 350px;
        margin-top: var(--spacing-2);
    }

    .pending-payment-section__actions .btn-primary {
        width: 100%;
        justify-content: center;
    }

    .pending-payment-section__cancel-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-2);
        padding: var(--spacing-3) var(--spacing-4);
        background: transparent;
        border: 1px solid var(--color-danger);
        color: var(--color-danger);
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pending-payment-section__cancel-btn:hover {
        background: var(--color-danger);
        color: #fff;
    }

    .pending-payment-section__cancel-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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

            <!-- ========== SEÇÃO DE CUPOM DE DESCONTO ========== -->
            <div class="coupon-section">
                <div class="coupon-section__input-group">
                    <div class="coupon-section__field">
                        <label for="coupon-input" class="coupon-section__label">
                            <i class="fas fa-ticket-alt"></i> Cupom de Desconto
                        </label>
                        <input
                            type="text"
                            id="coupon-input"
                            class="coupon-section__input"
                            placeholder="Digite seu cupom"
                            maxlength="20">
                    </div>
                    <button
                        type="button"
                        id="apply-coupon-btn"
                        class="coupon-section__apply-btn">
                        <i class="fas fa-check"></i> Aplicar
                    </button>
                </div>
                <div id="coupon-feedback" class="coupon-section__feedback"></div>
                <div id="coupon-discount-display" class="coupon-section__discount-display">
                    <span id="coupon-discount-text" class="coupon-section__discount-text">
                        <i class="fas fa-check-circle"></i>
                        <span></span>
                    </span>
                    <button type="button" id="remove-coupon-btn" class="coupon-section__remove-btn">
                        <i class="fas fa-times"></i> Remover
                    </button>
                </div>
            </div>
        </div>

        <!-- ========== SEÇÃO DE PAGAMENTO PENDENTE (BLOQUEIO) ========== -->
        <div id="pending-payment-section" class="pending-payment-section">
            <div class="pending-payment-section__icon">
                <i class="fa-solid fa-clock"></i>
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
                        <i class="fa-solid fa-copy"></i>
                        <span>Copiar</span>
                    </button>
                </div>
            </div>

            <!-- Linha digitável do boleto (aparece apenas se for BOLETO) -->
            <div id="pending-boleto-code" class="pending-payment-section__boleto-code" style="display:none"></div>

            <div class="pending-payment-section__actions">
                <a id="pending-boleto-download" href="#" target="_blank" class="btn-primary" style="display:none">
                    <i class="fa-solid fa-download"></i>
                    <span>Baixar Boleto</span>
                </a>
                <button type="button" id="pending-copy-btn" class="btn-primary" style="display:none">
                    <i class="fa-solid fa-copy"></i>
                    <span>Copiar código do boleto</span>
                </button>
                <button type="button" id="cancel-pending-btn" class="pending-payment-section__cancel-btn">
                    <i class="fa-solid fa-times-circle"></i>
                    <span>Cancelar e escolher outro método</span>
                </button>
            </div>
        </div>

        <div class="payment-modal__body">
            <!-- Seletor de Método de Pagamento -->
            <div class="payment-method-selector" role="tablist" aria-label="Método de pagamento">
                <button type="button" class="payment-method-btn is-active" data-method="CREDIT_CARD" role="tab"
                    aria-selected="true">
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
                            <input type="text" id="card_number" name="card_number" inputmode="numeric" maxlength="19"
                                class="payment-form__input" placeholder="0000 0000 0000 0000">
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
                            <input type="text" id="card_expiry" name="card_expiry" inputmode="numeric" maxlength="5"
                                class="payment-form__input" placeholder="07/29">
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
                                        placeholder="00000-000"
                                        value="<?= htmlspecialchars($cepValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="payment-form__field">
                                    <label for="boleto_endereco" class="payment-form__label">Endereço</label>
                                    <input type="text" id="boleto_endereco" name="boleto_endereco"
                                        class="payment-form__input" placeholder="Rua, número"
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
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

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

        // Pending payment elements
        const pendingPaymentSection = document.getElementById('pending-payment-section');
        const pendingBillingType = document.getElementById('pending-billing-type');
        const pendingCreatedAt = document.getElementById('pending-created-at');
        const pendingPixQrcode = document.getElementById('pending-pix-qrcode');
        const pendingPixCopyArea = document.getElementById('pending-pix-copy-area');
        const pendingPixCode = document.getElementById('pending-pix-code');
        const pendingPixCopyBtn = document.getElementById('pending-pix-copy-btn');
        const pendingBoletoCode = document.getElementById('pending-boleto-code');
        const pendingBoletoDownload = document.getElementById('pending-boleto-download');
        const pendingCopyBtn = document.getElementById('pending-copy-btn');
        const cancelPendingBtn = document.getElementById('cancel-pending-btn');
        const modalBody = document.querySelector('.payment-modal__body');

        const planButtons = document.querySelectorAll('[data-plan-button]');

        const currencyFormatter = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        let currentPlanConfig = null;
        let currentBillingType = 'CREDIT_CARD';
        let paymentPollingInterval = null;
        let hasPendingPayment = false;
        let pendingPaymentData = null;
        let paymentMethodsLocked = false;

        // ===============================
        // GERENCIAMENTO DE CUPOM DE DESCONTO
        // ===============================
        let appliedCoupon = null;
        const couponInput = document.getElementById('coupon-input');
        const applyCouponBtn = document.getElementById('apply-coupon-btn');
        const removeCouponBtn = document.getElementById('remove-coupon-btn');
        const couponFeedback = document.getElementById('coupon-feedback');
        const couponDiscountDisplay = document.getElementById('coupon-discount-display');
        const couponDiscountText = document.getElementById('coupon-discount-text');

        async function applyCoupon() {
            const codigo = couponInput.value.trim().toUpperCase();

            if (!codigo) {
                showCouponFeedback('Digite um código de cupom', 'error');
                return;
            }

            applyCouponBtn.disabled = true;
            applyCouponBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validando...';

            try {
                const response = await fetch(`${BASE_URL}api/cupons/validar?codigo=${encodeURIComponent(codigo)}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.status === 'success') {
                    appliedCoupon = data.data.cupom;
                    showCouponSuccess();
                    updatePriceWithDiscount();
                    couponInput.value = '';
                    couponInput.disabled = true;
                    applyCouponBtn.style.display = 'none';
                } else {
                    showCouponFeedback(data.message || 'Cupom inválido', 'error');
                }
            } catch (error) {
                console.error('Erro ao validar cupom:', error);
                showCouponFeedback('Erro ao validar cupom', 'error');
            } finally {
                applyCouponBtn.disabled = false;
                applyCouponBtn.innerHTML = '<i class="fas fa-check"></i> Aplicar';
            }
        }

        function removeCoupon() {
            appliedCoupon = null;
            couponInput.value = '';
            couponInput.disabled = false;
            applyCouponBtn.style.display = 'flex';
            couponDiscountDisplay.classList.remove('show');
            couponFeedback.style.display = 'none';
            updatePriceWithDiscount();
        }

        function showCouponFeedback(message, type) {
            couponFeedback.textContent = message;
            couponFeedback.style.display = 'block';
            couponFeedback.style.color = type === 'error' ? '#ef4444' : '#22c55e';
            couponFeedback.style.background = type === 'error' ?
                'rgba(239, 68, 68, 0.1)' :
                'rgba(34, 197, 94, 0.1)';
            couponFeedback.style.border = type === 'error' ?
                '1px solid rgba(239, 68, 68, 0.3)' :
                '1px solid rgba(34, 197, 94, 0.3)';
            setTimeout(() => {
                couponFeedback.style.display = 'none';
            }, 3000);
        }

        function showCouponSuccess() {
            const textSpan = couponDiscountText.querySelector('span');
            textSpan.textContent = `Cupom "${appliedCoupon.codigo}" aplicado! Desconto: ${appliedCoupon.desconto_formatado}`;
            couponDiscountDisplay.classList.add('show');
        }

        function calculateFinalPrice(basePrice) {
            if (!appliedCoupon) return basePrice;

            if (appliedCoupon.tipo_desconto === 'percentual') {
                return basePrice * (1 - appliedCoupon.valor_desconto / 100);
            } else {
                return Math.max(0, basePrice - appliedCoupon.valor_desconto);
            }
        }

        function updatePriceWithDiscount() {
            if (!currentPlanConfig || !modalPrice) return;

            const baseTotal = calcTotal(currentPlanConfig.monthlyBase, currentPlanConfig.months, currentPlanConfig.discount);
            const finalTotal = calculateFinalPrice(baseTotal);

            if (appliedCoupon) {
                const discount = baseTotal - finalTotal;
                modalPrice.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                        <span style="text-decoration: line-through; opacity: 0.6; font-size: 0.9rem;">${currencyFormatter.format(baseTotal)}</span>
                        <span>${currencyFormatter.format(finalTotal)}/${cycleLabel(currentPlanConfig.months)}</span>
                        <small style="font-size: 0.75rem; opacity: 0.9;">Economia: ${currencyFormatter.format(discount)}</small>
                    </div>
                `;
            } else {
                modalPrice.textContent = `${currentPlanConfig.planName} - ${currencyFormatter.format(baseTotal)}/${cycleLabel(currentPlanConfig.months)}`;
            }
        }

        applyCouponBtn?.addEventListener('click', applyCoupon);
        removeCouponBtn?.addEventListener('click', removeCoupon);
        couponInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyCoupon();
            }
        });

        // ===============================
        // TRAVAR/DESTRAVAR TABS DE PAGAMENTO
        // ===============================
        const paymentMethodSelector = document.querySelector('.payment-method-selector');

        function lockPaymentMethods() {
            paymentMethodsLocked = true;
            paymentMethodSelector?.classList.add('is-locked');
            paymentMethodBtns.forEach(btn => {
                btn.classList.add('is-locked');
                btn.setAttribute('aria-disabled', 'true');
            });
        }

        function unlockPaymentMethods() {
            paymentMethodsLocked = false;
            paymentMethodSelector?.classList.remove('is-locked');
            paymentMethodBtns.forEach(btn => {
                btn.classList.remove('is-locked');
                btn.removeAttribute('aria-disabled');
            });
        }

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
            if (!active) return {
                cycle: 'monthly',
                months: 1,
                discount: 0
            };
            return {
                cycle: active.dataset.cycle || 'monthly',
                months: Number(active.dataset.months || '1'),
                discount: Number(active.dataset.discount || '0')
            };
        }

        function syncPlanHiddenFields() {
            if (!currentPlanConfig) return;

            const total = calcTotal(currentPlanConfig.monthlyBase, currentPlanConfig.months, currentPlanConfig
                .discount);

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
                modalPrice.textContent =
                    `${currentPlanConfig.planName} - ${currencyFormatter.format(total)}/${cycleLabel(currentPlanConfig.months)}`;
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
            pixPendingStatus?.classList.remove('is-visible');
            boletoPendingStatus?.classList.remove('is-visible');

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

            // Se selecionou PIX, verificar se tem PIX pendente
            if (method === 'PIX') {
                checkPendingPix();
            }
        }

        // ===============================
        // VERIFICAR PIX PENDENTE
        // ===============================
        async function checkPendingPix() {
            try {
                const resp = await fetch(`${BASE_URL}premium/pending-pix`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const json = await resp.json();

                if (json.status === 'success' && json.data?.hasPending && json.data?.pix) {
                    // Tem PIX pendente! Mostrar seção de pagamento pendente
                    const pix = json.data.pix;

                    showPendingPaymentSection({
                        billingType: 'PIX',
                        createdAt: json.data.createdAt || new Date().toLocaleString('pt-BR'),
                        paymentId: json.data.paymentId,
                        pix: {
                            qrCodeImage: pix.qrCodeImage,
                            payload: pix.payload
                        }
                    });
                } else if (json.data?.paid) {
                    // Já foi pago! Recarregar página
                    window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado.', 'success')
                        .then(() => window.location.reload());
                }
                // Se não tem pendente, não faz nada - usuário precisa clicar em Gerar PIX
            } catch (err) {
                // Silencioso - se falhar, usuário pode gerar novo PIX
            }
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
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const json = await resp.json();

                    if (json.status === 'success' && json.data?.paid) {
                        clearInterval(paymentPollingInterval);
                        window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado com sucesso.',
                                'success')
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
        // VERIFICAR PAGAMENTO PENDENTE (QUALQUER TIPO)
        // ===============================
        async function checkPendingPayment() {
            try {
                const resp = await fetch(`${BASE_URL}premium/pending-payment`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const json = await resp.json();

                if (json.status === 'success' && json.data?.hasPending) {
                    hasPendingPayment = true;
                    pendingPaymentData = json.data;
                    showPendingPaymentSection(json.data);
                    return true;
                } else if (json.data?.paid) {
                    // Já foi pago! Recarregar página
                    window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado.', 'success')
                        .then(() => window.location.reload());
                    return true;
                }

                hasPendingPayment = false;
                pendingPaymentData = null;
                hidePendingPaymentSection();
                return false;
            } catch (err) {
                console.error('[checkPendingPayment] Erro:', err);
                hasPendingPayment = false;
                hidePendingPaymentSection();
                return false;
            }
        }

        function showPendingPaymentSection(data) {
            // Esconder o body normal do modal (métodos de pagamento)
            if (modalBody) modalBody.style.display = 'none';

            // Mostrar seção de pagamento pendente
            pendingPaymentSection?.classList.add('is-visible');

            // Preencher informações
            const billingTypeLabel = data.billingType === 'PIX' ? 'PIX' : 'Boleto';
            const typeClass = data.billingType === 'PIX' ? 'pending-type-pix' : 'pending-type-boleto';

            if (pendingBillingType) {
                pendingBillingType.textContent = billingTypeLabel;
                pendingBillingType.className = 'pending-payment-section__info-value ' + typeClass;
            }
            if (pendingCreatedAt) pendingCreatedAt.textContent = data.createdAt || '-';

            // Esconder todos os elementos específicos primeiro
            if (pendingPixQrcode) pendingPixQrcode.style.display = 'none';
            if (pendingPixCopyArea) pendingPixCopyArea.style.display = 'none';
            if (pendingBoletoCode) pendingBoletoCode.style.display = 'none';
            if (pendingBoletoDownload) pendingBoletoDownload.style.display = 'none';
            if (pendingCopyBtn) pendingCopyBtn.style.display = 'none';

            if (data.billingType === 'PIX' && data.pix) {
                // Mostrar QR Code PIX
                if (pendingPixQrcode && data.pix.qrCodeImage) {
                    pendingPixQrcode.src = data.pix.qrCodeImage;
                    pendingPixQrcode.style.display = 'block';
                }
                if (pendingPixCode && data.pix.payload) {
                    pendingPixCode.value = data.pix.payload;
                    if (pendingPixCopyArea) pendingPixCopyArea.style.display = 'block';
                }

                // Iniciar polling para PIX
                if (data.paymentId) {
                    startPaymentPolling(data.paymentId);
                }
            } else if (data.billingType === 'BOLETO' && data.boleto) {
                // Mostrar linha digitável do boleto
                if (pendingBoletoCode && data.boleto.identificationField) {
                    pendingBoletoCode.textContent = data.boleto.identificationField;
                    pendingBoletoCode.style.display = 'block';
                    if (pendingCopyBtn) pendingCopyBtn.style.display = 'flex';
                }
                if (pendingBoletoDownload && data.boleto.bankSlipUrl) {
                    pendingBoletoDownload.href = data.boleto.bankSlipUrl;
                    pendingBoletoDownload.style.display = 'flex';
                }

                // Iniciar polling para Boleto também
                if (data.paymentId) {
                    startPaymentPolling(data.paymentId);
                }
            }
        }

        function hidePendingPaymentSection() {
            pendingPaymentSection?.classList.remove('is-visible');
            if (modalBody) modalBody.style.display = '';
        }

        // Cancelar pagamento pendente
        async function cancelPendingPayment() {
            const result = await window.Swal?.fire({
                title: 'Cancelar pagamento?',
                text: 'Você poderá escolher outro método de pagamento após cancelar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sim, cancelar',
                cancelButtonText: 'Não, manter'
            });

            if (!result?.isConfirmed) return;

            try {
                cancelPendingBtn.disabled = true;
                cancelPendingBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> <span>Cancelando...</span>';

                const resp = await fetch(`${BASE_URL}premium/cancel-pending`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    }
                });
                const json = await resp.json();

                if (json.status === 'success') {
                    hasPendingPayment = false;
                    pendingPaymentData = null;
                    stopPaymentPolling();
                    hidePendingPaymentSection();

                    window.Swal?.fire({
                        icon: 'success',
                        title: 'Pagamento cancelado!',
                        text: 'Agora você pode escolher outro método.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(json.message || 'Erro ao cancelar pagamento');
                }
            } catch (err) {
                console.error('[cancelPendingPayment] Erro:', err);
                window.Swal?.fire('Erro', err.message || 'Não foi possível cancelar o pagamento.', 'error');
            } finally {
                cancelPendingBtn.disabled = false;
                cancelPendingBtn.innerHTML =
                    '<i class="fa-solid fa-times-circle"></i> <span>Cancelar e escolher outro método</span>';
            }
        }

        // Event listeners para seção de pagamento pendente
        cancelPendingBtn?.addEventListener('click', cancelPendingPayment);

        pendingPixCopyBtn?.addEventListener('click', () => {
            const code = pendingPixCode?.value;
            if (code) copyToClipboard(code, pendingPixCopyBtn);
        });

        pendingCopyBtn?.addEventListener('click', () => {
            const code = pendingBoletoCode?.textContent;
            if (code) copyToClipboard(code, pendingCopyBtn);
        });

        // ===============================
        // MODAL OPEN/CLOSE
        // ===============================
        async function openBillingModal(planConfig) {
            currentPlanConfig = planConfig;
            currentBillingType = 'CREDIT_CARD';

            if (modalTitle) modalTitle.textContent = 'Pagamento Seguro';

            // Primeiro, abrir o modal
            modal.classList.add('payment-modal--open');
            document.body.style.overflow = 'hidden';

            // Verificar se tem pagamento pendente
            const hasPending = await checkPendingPayment();

            if (!hasPending) {
                // Se não tem pendente, mostrar métodos normalmente
                hidePendingPaymentSection();
                switchPaymentMethod('CREDIT_CARD');
                syncPlanHiddenFields();
            }
        }

        function closeBillingModal() {
            modal.classList.remove('payment-modal--open');
            document.body.style.overflow = '';
            currentPlanConfig = null;
            stopPaymentPolling();

            // Reset estado de pagamento pendente
            hasPendingPayment = false;
            pendingPaymentData = null;
            hidePendingPaymentSection();

            // Destravar abas de pagamento
            unlockPaymentMethods();

            // Reset containers
            pixQrCodeContainer?.classList.remove('is-visible');
            boletoContainer?.classList.remove('is-visible');
            pixPendingStatus?.classList.remove('is-visible');
            boletoPendingStatus?.classList.remove('is-visible');

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
                // Não permitir trocar método se estiver travado
                if (paymentMethodsLocked) return;

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
                    window.Swal?.fire('Plano inválido',
                        'Não foi possível identificar o plano. Recarregue a página.', 'warning');
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

                        const priceValueElement = proPriceElement.querySelector(
                            '.plan-card__price-value');
                        const pricePeriodElement = proPriceElement.querySelector(
                            '.plan-card__price-period');

                        if (priceValueElement) {
                            priceValueElement.style.animation = 'none';
                            void priceValueElement.offsetWidth;
                            priceValueElement.textContent =
                                `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
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

                    if (modal?.classList.contains('payment-modal--open'))
                        syncPlanHiddenFields();
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
                amount_base_monthly: Number(inputBaseMonthly.value || currentPlanConfig.monthlyBase ||
                    0),
                amount: Number(inputPlanAmount.value || 0),
                billingType: currentBillingType
            };

            // Adicionar cupom ao payload se aplicado
            if (appliedCoupon) {
                payload.couponCode = appliedCoupon.codigo;
            }

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
                    window.Swal?.fire('Campos obrigatórios', 'Preencha todos os dados do cartão.',
                        'warning');
                    return;
                }

                const [month, year] = cardExpiry.split('/').map(v => v.trim());
                if (!month || !year) {
                    window.Swal?.fire('Validade inválida', 'Informe a validade no formato MM/AA.',
                        'warning');
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
                const cpf = userDataComplete.pix ? userDataComplete.cpf : onlyDigits(fd.get('pix_cpf')
                    ?.toString() || '');
                const phone = userDataComplete.pix ? userDataComplete.phone : onlyDigits(fd.get('pix_phone')
                    ?.toString() || '');

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
                const cpf = userDataComplete.boleto ? userDataComplete.cpf : onlyDigits(fd.get('boleto_cpf')
                    ?.toString() || '');
                const phone = userDataComplete.boleto ? userDataComplete.phone : onlyDigits(fd.get(
                    'boleto_phone')?.toString() || '');
                const cep = userDataComplete.boleto ? userDataComplete.cep : onlyDigits(fd.get('boleto_cep')
                    ?.toString() || '');
                const endereco = userDataComplete.boleto ? userDataComplete.endereco : (fd.get(
                    'boleto_endereco')?.toString().trim() || '');

                if (!cpf || cpf.length !== 11) {
                    window.Swal?.fire('CPF inválido', 'Informe um CPF válido para gerar o boleto.',
                        'warning');
                    return;
                }

                if (!cep || cep.length !== 8) {
                    window.Swal?.fire('CEP inválido', 'Informe um CEP válido para gerar o boleto.',
                        'warning');
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
                        'X-CSRF-Token': CSRF_TOKEN
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
                    window.Swal?.fire('Sucesso! 🎉', json?.message || 'Pagamento realizado com sucesso.',
                            'success')
                        .then(() => window.location.reload());
                    closeBillingModal();

                } else if (currentBillingType === 'PIX') {
                    // Exibir seção de pagamento pendente com QR Code
                    const pix = json.data?.pix;
                    if (pix) {
                        // Mostrar seção de pagamento pendente
                        showPendingPaymentSection({
                            billingType: 'PIX',
                            createdAt: new Date().toLocaleString('pt-BR'),
                            paymentId: json.data?.paymentId,
                            pix: {
                                qrCodeImage: pix.qrCodeImage,
                                payload: pix.payload
                            }
                        });

                        window.Swal?.fire({
                            icon: 'success',
                            title: 'PIX gerado!',
                            text: 'Escaneie o QR Code ou copie o código para pagar.',
                            confirmButtonText: 'Entendi'
                        });
                    } else {
                        throw new Error('PIX gerado mas dados não recebidos. Tente novamente.');
                    }

                } else if (currentBillingType === 'BOLETO') {
                    // Exibir seção de pagamento pendente com boleto
                    if (json.data?.boleto) {
                        const boleto = json.data.boleto;

                        // Mostrar seção de pagamento pendente
                        showPendingPaymentSection({
                            billingType: 'BOLETO',
                            createdAt: new Date().toLocaleString('pt-BR'),
                            paymentId: json.data.paymentId,
                            boleto: {
                                identificationField: boleto.identificationField,
                                bankSlipUrl: boleto.bankSlipUrl
                            }
                        });

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
                window.Swal?.fire('Erro', error.message || 'Erro ao processar. Tente novamente.',
                    'error');
            } finally {
                if (currentBillingType === 'CREDIT_CARD') {
                    submitBtn.disabled = false;
                    updateSubmitButton();
                }
            }
        });
    })();
</script>