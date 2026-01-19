<!-- ============================================================================
     BILLING PAGE - LUKRATO PRO
     Página de planos e assinaturas (Refatorado)
     ============================================================================ -->

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">

<style>
    /* ==========================================================================
       ROOT & VARIÁVEIS LOCAIS
       ========================================================================== */
    .billing-page {
        --billing-max-width: 1200px;
        --billing-spacing: clamp(40px, 8vw, 80px);
        --card-padding: clamp(28px, 4vw, 40px);
        --card-gap: clamp(24px, 4vw, 32px);

        /* Cores contextuais usando variáveis do sistema */
        --plan-border: var(--glass-border);
        --plan-hover-border: var(--color-primary);
        --badge-bg: linear-gradient(135deg, var(--color-warning), var(--color-primary));
        --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        --card-shadow-hover: 0 20px 50px rgba(0, 0, 0, 0.15);
    }

    /* ==========================================================================
       CONTAINER PRINCIPAL
       ========================================================================== */
    .billing-page {
        max-width: var(--billing-max-width);
        margin: var(--spacing-6) auto;
        padding: var(--spacing-5);
        background: var(--glass-bg);
        backdrop-filter: var(--glass-backdrop);
        border-radius: var(--radius-xl);
        border: 1px solid var(--plan-border);
        box-shadow: var(--shadow-xl);
        position: relative;
        overflow: hidden;
    }

    /* Gradientes decorativos de fundo */
    .billing-page::before,
    .billing-page::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
        opacity: 0.08;
        filter: blur(80px);
    }

    .billing-page::before {
        top: -15%;
        right: -10%;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, var(--color-primary), transparent 70%);
        animation: float 15s ease-in-out infinite;
    }

    .billing-page::after {
        bottom: -20%;
        left: -10%;
        width: 550px;
        height: 550px;
        background: radial-gradient(circle, var(--color-secondary), transparent 70%);
        animation: float 18s ease-in-out infinite reverse;
    }

    @keyframes float {

        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }

        33% {
            transform: translate(40px, -40px) scale(1.05);
        }

        66% {
            transform: translate(-30px, 30px) scale(0.95);
        }
    }

    /* ==========================================================================
       CABEÇALHO
       ========================================================================== */
    .billing-header {
        position: relative;
        z-index: 1;
        text-align: center;
        margin-bottom: var(--spacing-6);
    }

    .billing-header__title {
        font-size: clamp(2.25rem, 5vw, 3.5rem);
        font-weight: 900;
        margin: 0 0 var(--spacing-4);
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 50%, var(--color-primary) 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.03em;
        line-height: 1.1;
        animation: gradientShift 8s ease infinite;
        position: relative;
        display: inline-block;
    }

    @keyframes gradientShift {

        0%,
        100% {
            background-position: 0% center;
        }

        50% {
            background-position: 100% center;
        }
    }

    .billing-header__title::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--color-primary), transparent);
        border-radius: 2px;
    }

    .billing-header__subtitle {
        font-size: clamp(1rem, 2vw, 1.25rem);
        color: var(--color-text-muted);
        margin: var(--spacing-4) 0 0;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.7;
        font-weight: 500;
    }

    /* ==========================================================================
       GRID DE PLANOS
       ========================================================================== */
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
        gap: var(--card-gap);
        position: relative;
        z-index: 1;
    }

    /* Animação de entrada dos cards */
    .plan-card {
        animation: cardSlideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1) backwards;
    }

    .plan-card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .plan-card:nth-child(2) {
        animation-delay: 0.2s;
    }

    .plan-card:nth-child(3) {
        animation-delay: 0.3s;
    }

    @keyframes cardSlideIn {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* ==========================================================================
       CARD DO PLANO
       ========================================================================== */
    .plan-card {
        background: var(--color-surface);
        border: 2px solid var(--plan-border);
        border-radius: var(--radius-xl);
        padding: var(--spacing-6);
        display: flex;
        flex-direction: column;
        gap: var(--spacing-5);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }

    /* Barra superior colorida */
    .plan-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
        opacity: 0;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .plan-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--card-shadow-hover);
        border-color: color-mix(in srgb, var(--color-primary) 30%, var(--glass-border));
    }

    .plan-card:hover::before {
        opacity: 1;
    }

    /* Badge "Recomendado" */
    .plan-card--recommended::after {
        content: '⭐ Recomendado';
        position: absolute;
        top: 20px;
        right: 20px;
        background: var(--badge-bg);
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 8px 16px;
        border-radius: var(--radius-lg);
        box-shadow: 0 4px 16px color-mix(in srgb, var(--color-primary) 40%, transparent);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        z-index: 3;
        pointer-events: none;
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1) translateY(0);
            box-shadow: 0 4px 16px color-mix(in srgb, var(--color-primary) 40%, transparent);
        }

        50% {
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 8px 24px color-mix(in srgb, var(--color-primary) 50%, transparent);
        }
    }

    /* Plano ativo */
    .plan-card--active {
        border-color: var(--color-success);
        border-width: 3px;
        background: linear-gradient(to bottom,
                color-mix(in srgb, var(--color-success) 8%, var(--color-surface)),
                var(--color-surface));
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-success) 20%, transparent),
            0 8px 32px color-mix(in srgb, var(--color-success) 25%, transparent);
        position: relative;
    }

    .plan-card--active::before {
        background: var(--color-success);
        opacity: 1;
        height: 5px;
    }

    /* Efeito de pulso no plano ativo */
    @keyframes activePulse {

        0%,
        100% {
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-success) 20%, transparent),
                0 8px 32px color-mix(in srgb, var(--color-success) 25%, transparent);
        }

        50% {
            box-shadow: 0 0 0 6px color-mix(in srgb, var(--color-success) 15%, transparent),
                0 12px 40px color-mix(in srgb, var(--color-success) 30%, transparent);
        }
    }

    .plan-card--active {
        animation: activePulse 3s ease-in-out infinite;
    }

    /* ==========================================================================
       CABEÇALHO DO CARD
       ========================================================================== */
    .plan-card__header {
        display: flex;
        align-items: flex-start;
        gap: var(--spacing-3);
    }

    .plan-card__icon {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: white;
        border-radius: var(--radius-lg);
        font-size: 1.75rem;
        flex-shrink: 0;
        box-shadow: 0 8px 20px color-mix(in srgb, var(--color-primary) 30%, transparent);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .plan-card:hover .plan-card__icon {
        transform: scale(1.08);
        box-shadow: 0 10px 24px color-mix(in srgb, var(--color-primary) 35%, transparent);
    }

    .plan-card__title-wrapper {
        flex: 1;
    }

    .plan-card__title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 var(--spacing-2);
        color: var(--color-text);
        line-height: 1.3;
    }

    .plan-card--active .plan-card__title {
        color: var(--color-success);
    }

    .plan-card__badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--color-success);
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: var(--radius-sm);
        animation: glow 2s ease-in-out infinite;
    }

    @keyframes glow {

        0%,
        100% {
            box-shadow: 0 0 0 0 color-mix(in srgb, var(--color-success) 50%, transparent);
        }

        50% {
            box-shadow: 0 0 0 8px transparent;
        }
    }

    .plan-card__description {
        font-size: 0.9375rem;
        color: var(--color-text-muted);
        line-height: 1.6;
        margin: 0;
    }

    /* ==========================================================================
       PREÇO
       ========================================================================== */
    .plan-card__price {
        font-size: 3rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.03em;
        line-height: 1.1;
        margin: var(--spacing-3) 0;
        display: flex;
        align-items: baseline;
        gap: var(--spacing-2);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .plan-card__price-value {
        display: inline-block;
        animation: priceAppear 0.5s ease;
    }

    @keyframes priceAppear {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .plan-card__price-period {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--color-text-muted);
        opacity: 0.8;
    }

    .plan-card:hover .plan-card__price {
        transform: scale(1.02);
    }

    /* ==========================================================================
       FEATURES
       ========================================================================== */
    .plan-card__features {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: var(--spacing-3);
    }

    .plan-card__feature {
        display: flex;
        align-items: flex-start;
        gap: var(--spacing-3);
        font-size: 0.9375rem;
        line-height: 1.6;
        padding: var(--spacing-2);
        border-radius: var(--radius-sm);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: var(--color-text);
    }

    .plan-card__feature:hover {
        transform: translateX(4px);
    }

    .plan-card__feature-icon {
        width: 26px;
        height: 26px;
        min-width: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 0.8rem;
        margin-top: 2px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .plan-card__feature-icon--check {
        background: linear-gradient(135deg, var(--color-success), color-mix(in srgb, var(--color-success) 80%, black));
        color: white;
    }

    .plan-card__feature:hover .plan-card__feature-icon--check {
        transform: scale(1.1);
        box-shadow: 0 3px 10px color-mix(in srgb, var(--color-success) 30%, transparent);
    }

    .plan-card__feature-icon--times {
        background: var(--color-surface-muted);
        color: var(--color-text-muted);
        opacity: 0.5;
    }

    /* ==========================================================================
       BOTÃO DE AÇÃO
       ========================================================================== */
    .plan-card__button {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-3);
        padding: 18px 32px;
        font-size: 1rem;
        font-weight: 700;
        font-family: var(--font-primary);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border: none;
        border-radius: var(--radius-lg);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    /* Botão primário (ativo) */
    .plan-card__button--primary {
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: white;
        box-shadow: 0 8px 24px color-mix(in srgb, var(--color-primary) 40%, transparent);
        position: relative;
        overflow: hidden;
    }

    .plan-card__button--primary::before {
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

    .plan-card__button--primary:hover::before {
        width: 300px;
        height: 300px;
    }

    .plan-card__button--primary:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 14px 40px color-mix(in srgb, var(--color-primary) 60%, transparent);
    }

    .plan-card__button--primary:active {
        transform: translateY(-2px) scale(1);
    }

    /* Botão do plano ativo */
    .plan-card__button--active {
        background: var(--color-success);
        color: white;
        box-shadow: 0 8px 24px color-mix(in srgb, var(--color-success) 40%, transparent);
    }

    /* Botão do plano cancelado (warning) */
    .plan-card__button--warning {
        background: linear-gradient(135deg, #ff9800, #ff6b00);
        color: white;
        box-shadow: 0 8px 24px rgba(255, 152, 0, 0.4);
        animation: pulse-warning 2s ease-in-out infinite;
    }

    @keyframes pulse-warning {

        0%,
        100% {
            box-shadow: 0 8px 24px rgba(255, 152, 0, 0.4);
        }

        50% {
            box-shadow: 0 8px 32px rgba(255, 152, 0, 0.6);
        }
    }

    .plan-card__button--active:hover {
        background: color-mix(in srgb, var(--color-success) 90%, black);
        transform: translateY(-2px);
    }

    /* Botão desabilitado */
    .plan-card__button:disabled {
        background: var(--color-surface-muted);
        color: var(--color-text-muted);
        cursor: not-allowed;
        box-shadow: none;
        opacity: 0.7;
    }

    .plan-card__button:disabled:hover {
        transform: none;
    }

    /* Botão de Cancelar Assinatura */
    .plan-card__cancel-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-2);
        width: 100%;
        padding: var(--spacing-3) var(--spacing-4);
        margin-top: var(--spacing-3);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--color-danger);
        background: transparent;
        border: 1px solid color-mix(in srgb, var(--color-danger) 30%, transparent);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .plan-card__cancel-btn:hover {
        background: color-mix(in srgb, var(--color-danger) 10%, transparent);
        border-color: var(--color-danger);
    }

    .plan-card__cancel-btn:active {
        transform: scale(0.98);
    }

    .plan-card__cancel-btn i {
        font-size: 1rem;
    }

    /* Loading state */
    .plan-card__button--loading {
        pointer-events: none;
        opacity: 0.8;
    }

    .plan-card__button--loading .plan-card__button-icon {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .plan-card__button-icon {
        font-size: 1.25rem;
    }

    /* ==========================================================================
       FEEDBACK MESSAGE
       ========================================================================== */
    .billing-message {
        margin-top: var(--spacing-4);
        padding: var(--spacing-3) var(--spacing-4);
        border-radius: var(--radius-md);
        font-size: 0.9375rem;
        font-weight: 500;
        text-align: center;
        border: 1px solid;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .billing-message--success {
        background: color-mix(in srgb, var(--color-success) 12%, transparent);
        color: var(--color-success);
        border-color: color-mix(in srgb, var(--color-success) 30%, transparent);
    }

    .billing-message--error {
        background: color-mix(in srgb, var(--color-danger) 12%, transparent);
        color: var(--color-danger);
        border-color: color-mix(in srgb, var(--color-danger) 30%, transparent);
    }

    /* ==========================================================================
       EMPTY STATE
       ========================================================================== */
    .plans-grid--empty {
        text-align: center;
        padding: clamp(40px, 8vw, 80px) var(--spacing-4);
    }

    .empty-state__icon {
        font-size: 4rem;
        color: var(--color-text-muted);
        margin-bottom: var(--spacing-4);
        opacity: 0.5;
    }

    .empty-state__title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--color-text);
        margin: 0 0 var(--spacing-3);
    }

    .empty-state__description {
        color: var(--color-text-muted);
        margin: 0;
    }

    /* ==========================================================================
       RESPONSIVIDADE
       ========================================================================== */
    @media (max-width: 992px) {
        .plans-grid {
            grid-template-columns: 1fr;
            max-width: 600px;
            margin: 0 auto;
        }
    }

    @media (max-width: 768px) {
        .billing-page {
            margin: 16px 8px;
            padding: 24px 16px;
        }

        .billing-header {
            margin-bottom: var(--spacing-4);
        }

        .plan-card--recommended::after {
            top: 12px;
            right: 12px;
            font-size: 0.65rem;
            padding: 4px 8px;
        }
    }

    @media (max-width: 480px) {
        .billing-page {
            margin: 8px 4px;
            padding: 20px 12px;
            border-radius: var(--radius-lg);
        }

        .billing-header__title {
            font-size: clamp(1.75rem, 5vw, 2.5rem);
        }

        .billing-header__subtitle {
            font-size: 0.9375rem;
        }

        .plan-card {
            padding: 20px 16px;
        }

        .plan-card__icon {
            width: 40px;
            height: 40px;
            font-size: 1.25rem;
        }

        .plan-card__title {
            font-size: 1.25rem;
        }

        .plan-card__price {
            font-size: 2.25rem;
        }

        .plan-card__button {
            padding: 12px 20px;
            font-size: 0.875rem;
        }

        .plan-billing-toggle {
            flex-direction: column;
            gap: var(--spacing-2);
            padding: var(--spacing-2);
        }

        .plan-billing-toggle__btn {
            width: 100%;
            padding: 12px 16px;
            font-size: .875rem;
            justify-content: center;
        }

        .plan-billing-toggle__off {
            font-size: .75rem;
            padding: 2px 6px;
        }
    }

    @media (max-width: 390px) {
        .billing-page {
            margin: 4px 2px;
            padding: 16px 8px;
        }

        .billing-header__title {
            font-size: 1.5rem;
        }

        .billing-header__subtitle {
            font-size: 0.875rem;
        }

        .plan-card {
            padding: 16px 12px;
        }

        .plan-card__price {
            font-size: 2rem;
        }

        .plan-card__feature {
            font-size: 0.875rem;
        }
    }

    /* ==========================================================================
       ACESSIBILIDADE
       ========================================================================== */
    @media (prefers-reduced-motion: reduce) {

        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }

    .plan-card:focus-within {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }

    .plan-billing-toggle {
        display: flex;
        gap: var(--spacing-2);
        flex-wrap: nowrap;
        margin-top: var(--spacing-3);
        padding: var(--spacing-2);
        background: var(--color-surface-muted);
        border-radius: var(--radius-lg);
        border: 1px solid var(--glass-border);
    }

    .plan-billing-toggle__btn {
        flex: 1;
        min-width: 0;
        border: 2px solid transparent;
        background: transparent;
        color: var(--color-text);
        border-radius: var(--radius-md);
        padding: 10px 8px;
        font-weight: 700;
        font-size: .8125rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        white-space: nowrap;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .plan-billing-toggle__btn::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: -1;
    }

    .plan-billing-toggle__btn:hover {
        transform: translateY(-2px);
        border-color: var(--color-primary);
        box-shadow: 0 4px 12px color-mix(in srgb, var(--color-primary) 20%, transparent);
    }

    .plan-billing-toggle__btn.is-active {
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: #fff;
        border-color: transparent;
        box-shadow: 0 6px 20px color-mix(in srgb, var(--color-primary) 40%, transparent);
        transform: translateY(-2px);
    }

    .plan-billing-toggle__btn.is-active::before {
        opacity: 1;
    }

    .plan-billing-toggle__off {
        font-size: .65rem;
        font-weight: 800;
        opacity: 1;
        background: rgba(255, 255, 255, 0.2);
        padding: 2px 5px;
        border-radius: 3px;
        display: inline-block;
    }

    .plan-billing-toggle__btn:not(.is-active) .plan-billing-toggle__off {
        background: var(--color-success);
        color: white;
    }
</style>

<?php
// =============================================================================
// PROCESSAMENTO DOS PLANOS
// =============================================================================
$planItems = [];
if (isset($plans) && is_iterable($plans)) {
    foreach ($plans as $plan) {
        $serialized = is_array($plan)
            ? $plan
            : (method_exists($plan, 'toArray') ? $plan->toArray() : (array) $plan);

        if (empty($serialized['code'])) continue;

        $metadata = $serialized['metadados'] ?? [];
        if (!is_array($metadata) && $metadata !== null) {
            $metadata = json_decode((string) $metadata, true) ?: [];
        }

        $planItems[] = [
            'id'          => $serialized['id'] ?? null,
            'code'        => (string) $serialized['code'],
            'name'        => $serialized['nome'] ?? ($serialized['name'] ?? (string) $serialized['code']),
            'price_cents' => (int) ($serialized['preco_centavos'] ?? 0),
            'interval'    => $serialized['intervalo'] ?? 'month',
            'active'      => (bool) ($serialized['ativo'] ?? true),
            'metadata'    => is_array($metadata) ? $metadata : [],
        ];
    }
}

$currentPlanCode = $currentPlanCode ?? ($user?->planoAtual()?->code ?? null);

// Função helper para formatar o intervalo
function formatInterval(string $interval): string
{
    return match (strtolower($interval)) {
        'year', 'ano', 'anual', 'annual' => 'ano',
        'week', 'semanal' => 'semana',
        'day', 'dia', 'daily' => 'dia',
        default => 'mês',
    };
}
?>

<!-- ============================================================================
     MARKUP HTML
     ============================================================================ -->
<div class="billing-page">
    <!-- Cabeçalho -->
    <header class="billing-header">
        <h1 class="billing-header__title">🚀 Escolha seu plano</h1>
        <p class="billing-header__subtitle">
            Escolha o plano ideal para suas necessidades financeiras e tenha controle total sobre seu dinheiro
        </p>
    </header>

    <!-- Grid de Planos -->
    <?php if (empty($planItems)): ?>
        <div class="plans-grid plans-grid--empty">
            <div class="empty-state">
                <div class="empty-state__icon">
                    <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                </div>
                <h2 class="empty-state__title">Nenhum plano cadastrado</h2>
                <p class="empty-state__description">
                    Cadastre planos ativos no banco de dados para exibi-los aqui.
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="plans-grid">
            <?php foreach ($planItems as $plan): ?>
                <?php
                $meta = $plan['metadata'];
                $icon = trim($meta['icone'] ?? $meta['icon'] ?? 'fa-layer-group');
                $description = trim($meta['descricao'] ?? $meta['description'] ?? 'Plano completo para organizar suas finanças.');
                $features = array_values(array_filter(array_map('trim', $meta['features'] ?? [])));
                $missingFeatures = array_values(array_filter(array_map('trim', $meta['missing_features'] ?? [])));

                $priceCents = max(0, $plan['price_cents']);
                $priceValue = $priceCents / 100;
                $intervalLabel = formatInterval($plan['interval']);

                $isCurrentPlan = $currentPlanCode && strcasecmp($plan['code'], $currentPlanCode) === 0;
                $isFreePlan = $priceCents === 0;
                $isRecommended = !empty($meta['destaque']) || !empty($meta['highlight']) ||
                    strcasecmp($plan['code'], 'pro') === 0;

                $ctaLabel = trim($meta['cta_label'] ?? ($isFreePlan ? 'Plano gratuito' : 'Assinar agora'));

                $cardClasses = ['plan-card'];
                if ($isRecommended) $cardClasses[] = 'plan-card--recommended';
                if ($isCurrentPlan) $cardClasses[] = 'plan-card--active';

                $buttonId = strcasecmp($plan['code'], 'pro') === 0 ? 'btnAssinar' : null;
                $renewDate = $user->plano_renova_em ?? $user->plan_renews_at ?? null;
                ?>

                <article class="<?= implode(' ', $cardClasses) ?>" aria-label="Plano <?= htmlspecialchars($plan['name']) ?>">

                    <!-- Cabeçalho do Card -->
                    <div class="plan-card__header">
                        <div class="plan-card__icon" aria-hidden="true">
                            <i class="fa-solid fa-<?= htmlspecialchars($icon) ?>"></i>
                        </div>
                        <div class="plan-card__title-wrapper">
                            <h2 class="plan-card__title">
                                <?= htmlspecialchars($plan['name']) ?>
                                <?php if ($isCurrentPlan): ?>
                                    <span class="plan-card__badge">
                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                        Ativo
                                    </span>
                                <?php endif; ?>
                            </h2>
                        </div>
                    </div>

                    <!-- Descrição -->
                    <p class="plan-card__description">
                        <?= htmlspecialchars($description) ?>
                    </p>

                    <!-- Preço -->
                    <div class="plan-card__price"
                        <?= strcasecmp($plan['code'], 'pro') === 0 ? 'id="planProPrice"' : '' ?>
                        data-base-price="<?= number_format($priceValue, 2, '.', '') ?>"
                        aria-label="<?= $priceCents > 0 ? 'Preço: ' . number_format($priceValue, 2, ',', '.') . ' por ' . $intervalLabel : 'Plano gratuito' ?>">
                        <?php if ($priceCents > 0): ?>
                            <span class="plan-card__price-value">R$ <?= number_format($priceValue, 2, ',', '.') ?></span>
                            <span class="plan-card__price-period">/<?= $intervalLabel ?></span>
                        <?php else: ?>
                            Gratuito
                            <span class="plan-card__price-period">/<?= $intervalLabel ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Features -->
                    <ul class="plan-card__features" role="list">
                        <?php foreach ($features as $feature): ?>
                            <li class="plan-card__feature">
                                <span class="plan-card__feature-icon plan-card__feature-icon--check" aria-label="Incluído">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                                <?= htmlspecialchars($feature) ?>
                            </li>
                        <?php endforeach; ?>

                        <?php foreach ($missingFeatures as $feature): ?>
                            <li class="plan-card__feature">
                                <span class="plan-card__feature-icon plan-card__feature-icon--times" aria-label="Não incluído">
                                    <i class="fa-solid fa-times"></i>
                                </span>
                                <?= htmlspecialchars($feature) ?>
                            </li>
                        <?php endforeach; ?>

                        <?php if (empty($features) && empty($missingFeatures)): ?>
                            <li class="plan-card__feature">
                                <span class="plan-card__feature-icon plan-card__feature-icon--times">
                                    <i class="fa-solid fa-circle-info"></i>
                                </span>
                                Recursos serão exibidos em breve
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Botão de Ação -->
                    <?php if ($isCurrentPlan): ?>
                        <?php if ($isCanceled ?? false): ?>
                            <!-- Status: Cancelado mas ainda tem acesso -->
                            <button class="plan-card__button plan-card__button--warning" disabled
                                aria-label="Plano cancelado - acesso até <?= $accessUntil ?? '' ?>">
                                <i class="plan-card__button-icon fa-solid fa-exclamation-triangle" aria-hidden="true"></i>
                                <span>
                                    Cancelado - Acesso até <?= htmlspecialchars($accessUntil ?? $renewDate ?? '') ?>
                                </span>
                            </button>
                        <?php else: ?>
                            <!-- Status: Ativo normalmente -->
                            <button class="plan-card__button plan-card__button--active" disabled
                                aria-label="<?= $renewDate ? 'Plano ativo até ' . $renewDate : 'Plano atual' ?>">
                                <i class="plan-card__button-icon fa-solid fa-check-circle" aria-hidden="true"></i>
                                <span>
                                    <?php if ($renewDate && !$isFreePlan): ?>
                                        Ativo até <?= htmlspecialchars($renewDate) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($meta['current_label'] ?? 'Plano atual') ?>
                                    <?php endif; ?>
                                </span>
                            </button>

                            <?php if (!$isFreePlan): ?>
                                <!-- Botão de Cancelar Plano PRO -->
                                <button type="button" class="plan-card__cancel-btn" id="btn-cancel-subscription"
                                    aria-label="Cancelar assinatura do plano Pro">
                                    <i class="fa-solid fa-times-circle"></i>
                                    <span>Cancelar assinatura</span>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php elseif ($isFreePlan): ?>
                        <button class="plan-card__button" disabled aria-label="Plano gratuito">
                            <span><?= htmlspecialchars($ctaLabel) ?></span>
                        </button>
                    <?php else: ?>
                        <?php if (strcasecmp($plan['code'], 'pro') === 0): ?>
                            <div class="plan-billing-toggle" role="group" aria-label="Período de cobrança do Pro">
                                <button type="button" class="plan-billing-toggle__btn is-active" data-cycle="monthly" data-months="1"
                                    data-discount="0">
                                    <span>Mensal</span>
                                </button>

                                <button type="button" class="plan-billing-toggle__btn" data-cycle="semiannual" data-months="6"
                                    data-discount="10">
                                    <span>Semestral</span> <span class="plan-billing-toggle__off">💰 -10%</span>
                                </button>

                                <button type="button" class="plan-billing-toggle__btn" data-cycle="annual" data-months="12"
                                    data-discount="15">
                                    <span>Anual</span> <span class="plan-billing-toggle__off">🎉 -15%</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <button <?= $buttonId ? 'id="' . $buttonId . '"' : '' ?>
                            class="plan-card__button plan-card__button--primary" data-plan-button="1"
                            data-plan-id="<?= htmlspecialchars((string) $plan['id']) ?>"
                            data-plan-code="<?= htmlspecialchars($plan['code']) ?>"
                            data-plan-name="<?= htmlspecialchars($plan['name']) ?>"
                            data-plan-amount="<?= number_format($priceValue, 2, '.', '') ?>"
                            data-plan-monthly="<?= number_format($priceValue, 2, '.', '') ?>"
                            data-plan-interval="<?= htmlspecialchars($intervalLabel) ?>">
                            <i class="plan-card__button-icon fa-solid fa-rocket" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($ctaLabel) ?></span>
                        </button>


                        <?php if ($buttonId): ?>
                            <div id="msg" role="status" aria-live="polite" aria-atomic="true"></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Pagamento -->
<?php include __DIR__ . '/modal-pagamento.php'; ?>

<!-- ============================================================================
     FEEDBACK DE STATUS
     ============================================================================ -->
<?php if (isset($_GET['status'])): ?>
    <script>
        (function() {
            'use strict';

            const status = '<?= htmlspecialchars($_GET['status'], ENT_QUOTES, 'UTF-8') ?>';

            if (typeof Swal === 'undefined') {
                console.warn('[Billing] SweetAlert2 não disponível');
                return;
            }

            const messages = {
                success: {
                    title: 'Tudo certo! 🎉',
                    text: 'Pagamento aprovado com sucesso. Bem-vindo ao Pro!',
                    icon: 'success'
                },
                pending: {
                    title: 'Pagamento pendente ⏳',
                    text: 'Aguardando confirmação do pagamento...',
                    icon: 'info'
                },
                error: {
                    title: 'Ops! Algo deu errado 😕',
                    text: 'Pagamento não aprovado. Tente novamente.',
                    icon: 'error'
                },
                cancelled: {
                    title: 'Assinatura cancelada',
                    text: 'Sua assinatura Pro foi cancelada com sucesso.',
                    icon: 'success'
                }
            };

            const config = messages[status] || messages.error;

            Swal.fire({
                title: config.title,
                text: config.text,
                icon: config.icon,
                confirmButtonText: 'Entendi',
                confirmButtonColor: getComputedStyle(document.documentElement)
                    .getPropertyValue('--color-primary').trim() || '#e67e22'
            });

            // Limpa URL
            if (window.history?.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        })();
    </script>
<?php endif; ?>

<!-- ============================================================================
     SCRIPT DE CANCELAMENTO DE ASSINATURA
     ============================================================================ -->
<script>
    (function() {
        'use strict';

        const cancelBtn = document.getElementById('btn-cancel-subscription');
        if (!cancelBtn) return;

        cancelBtn.addEventListener('click', async () => {
            if (typeof Swal === 'undefined') {
                alert('Erro: SweetAlert não carregado');
                return;
            }

            // Primeira confirmação
            const result = await Swal.fire({
                title: '⚠️ Cancelar assinatura Pro?',
                html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 1rem;">Ao cancelar sua assinatura:</p>
                    <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-text-muted);">
                        <li style="margin-bottom: 0.5rem;">Você perderá acesso aos recursos Pro</li>
                        <li style="margin-bottom: 0.5rem;">Agendamentos serão desativados</li>
                        <li style="margin-bottom: 0.5rem;">Relatórios avançados serão bloqueados</li>
                        <li>Seus dados serão mantidos</li>
                    </ul>
                </div>
            `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Sim, cancelar assinatura',
                cancelButtonText: 'Manter plano Pro',
                focusCancel: true
            });

            if (!result.isConfirmed) return;

            // Segunda confirmação
            const finalConfirm = await Swal.fire({
                title: 'Última confirmação',
                text: 'Digite "CANCELAR" para confirmar o cancelamento',
                input: 'text',
                inputPlaceholder: 'Digite: CANCELAR',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Confirmar cancelamento',
                cancelButtonText: 'Voltar',
                inputValidator: (value) => {
                    if (value !== 'CANCELAR') {
                        return 'Você precisa digitar "CANCELAR" para confirmar';
                    }
                }
            });

            if (!finalConfirm.isConfirmed) return;

            // Mostrar loading
            Swal.fire({
                title: 'Cancelando assinatura...',
                text: 'Por favor aguarde',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '/';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const response = await fetch(`${baseUrl}premium/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (!response.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Erro ao cancelar assinatura');
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Assinatura cancelada!',
                    html: `
                    <p>Sua assinatura Pro foi cancelada com sucesso.</p>
                    <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-top: 0.5rem;">
                        Você ainda terá acesso aos recursos Pro até o fim do período pago.
                    </p>
                `,
                    confirmButtonText: 'Entendi',
                    confirmButtonColor: '#e67e22'
                });

                // Recarregar a página
                window.location.reload();

            } catch (err) {
                console.error('Erro ao cancelar assinatura:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: err.message || 'Não foi possível cancelar a assinatura. Tente novamente.'
                });
            }
        });
    })();
</script>