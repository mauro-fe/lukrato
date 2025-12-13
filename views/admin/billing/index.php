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

        /* Cores contextuais */
        --plan-border: var(--glass-border);
        --plan-hover-border: var(--color-primary);
        --badge-bg: linear-gradient(135deg, #f59e0b, #d97706);
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
        opacity: 0.06;
        filter: blur(60px);
    }

    .billing-page::before {
        top: -15%;
        right: -10%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, var(--color-primary), transparent 70%);
        animation: float 12s ease-in-out infinite;
    }

    .billing-page::after {
        bottom: -20%;
        left: -10%;
        width: 450px;
        height: 450px;
        background: radial-gradient(circle, var(--color-secondary), transparent 70%);
        animation: float 15s ease-in-out infinite reverse;
    }

    @keyframes float {

        0%,
        100% {
            transform: translate(0, 0);
        }

        50% {
            transform: translate(30px, -30px);
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
        font-size: clamp(2rem, 5vw, 3rem);
        font-weight: 800;
        margin: 0 0 var(--spacing-4);
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }

    .billing-header__subtitle {
        font-size: clamp(1rem, 2vw, 1.125rem);
        color: var(--color-text-muted);
        margin: 0;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    /* ==========================================================================
       GRID DE PLANOS
       ========================================================================== */
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr));
        gap: var(--card-gap);
        position: relative;
        z-index: 1;
    }

    /* ==========================================================================
       CARD DO PLANO
       ========================================================================== */
    .plan-card {
        background: var(--color-surface);
        border: 2px solid var(--plan-border);
        border-radius: var(--radius-lg);
        padding: var(--spacing-6);
        display: flex;
        flex-direction: column;
        gap: var(--spacing-5);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
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
        transition: opacity 0.3s ease;
    }

    .plan-card:hover {
        transform: translateY(-8px);
        border-color: var(--plan-hover-border);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .plan-card:hover::before {
        opacity: 1;
    }

    /* Badge "Recomendado" */
    .plan-card--recommended::after {
        content: '⭐ Recomendado';
        position: absolute;
        top: 24px;
        right: 24px;
        background: var(--badge-bg);
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: var(--radius-md);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        z-index: 2;
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    /* Plano ativo */
    .plan-card--active {
        border-color: var(--color-success);
        background: linear-gradient(to bottom,
                color-mix(in srgb, var(--color-success) 8%, var(--color-surface)),
                var(--color-surface));
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-success) 15%, transparent);
    }

    .plan-card--active::before {
        background: var(--color-success);
        opacity: 1;
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
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: white;
        border-radius: var(--radius-md);
        font-size: 1.5rem;
        flex-shrink: 0;
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
        color: var(--color-primary);
        letter-spacing: -0.03em;
        line-height: 1;
        margin: var(--spacing-2) 0;
    }

    .plan-card__price-period {
        font-size: 1rem;
        font-weight: 500;
        color: var(--color-text-muted);
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
        transition: transform 0.2s ease;
    }

    .plan-card__feature:hover {
        transform: translateX(4px);
    }

    .plan-card__feature-icon {
        width: 24px;
        height: 24px;
        min-width: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 0.75rem;
        margin-top: 2px;
    }

    .plan-card__feature-icon--check {
        background: var(--color-success);
        color: white;
    }

    .plan-card__feature-icon--times {
        background: var(--color-surface-muted);
        color: var(--color-text-muted);
        opacity: 0.6;
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
    }

    .plan-card__button--primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 32px color-mix(in srgb, var(--color-primary) 50%, transparent);
    }

    .plan-card__button--primary:active {
        transform: translateY(-1px);
    }

    /* Botão do plano ativo */
    .plan-card__button--active {
        background: var(--color-success);
        color: white;
        box-shadow: 0 8px 24px color-mix(in srgb, var(--color-success) 40%, transparent);
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
            margin: 32px 16px;
            padding: 32px 20px;
        }

        .plan-card--recommended::after {
            top: 16px;
            right: 16px;
            font-size: 0.65rem;
            padding: 4px 10px;
        }
    }

    @media (max-width: 480px) {
        .billing-page {
            padding: 24px 16px;
        }

        .plan-card {
            padding: 24px 20px;
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
            font-size: 2.5rem;
        }

        .plan-card__button {
            padding: 14px 24px;
            font-size: 0.9375rem;
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
        gap: 10px;
        flex-wrap: wrap;
        margin-top: var(--spacing-2);
    }

    .plan-billing-toggle__btn {
        border: 1px solid var(--glass-border);
        background: var(--color-surface-muted);
        color: var(--color-text);
        border-radius: 999px;
        padding: 10px 12px;
        font-weight: 800;
        font-size: .875rem;
        cursor: pointer;
        transition: .2s;
    }

    .plan-billing-toggle__btn:hover {
        transform: translateY(-1px);
        border-color: var(--color-primary);
    }

    .plan-billing-toggle__btn.is-active {
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: #fff;
        border-color: transparent;
    }

    .plan-billing-toggle__off {
        margin-left: 6px;
        font-size: .75rem;
        opacity: .95;
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
                        aria-label="<?= $priceCents > 0 ? 'Preço: ' . number_format($priceValue, 2, ',', '.') . ' por ' . $intervalLabel : 'Plano gratuito' ?>">
                        <?php if ($priceCents > 0): ?>
                            R$ <?= number_format($priceValue, 2, ',', '.') ?>
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
                    <?php elseif ($isFreePlan): ?>
                        <button class="plan-card__button" disabled aria-label="Plano gratuito">
                            <span><?= htmlspecialchars($ctaLabel) ?></span>
                        </button>
                    <?php else: ?>
                        <?php if (strcasecmp($plan['code'], 'pro') === 0): ?>
                            <div class="plan-billing-toggle" role="group" aria-label="Período de cobrança do Pro">
                                <button type="button" class="plan-billing-toggle__btn is-active" data-cycle="monthly" data-months="1"
                                    data-discount="0">
                                    Mensal
                                </button>

                                <button type="button" class="plan-billing-toggle__btn" data-cycle="semiannual" data-months="6"
                                    data-discount="10">
                                    Semestral <span class="plan-billing-toggle__off">-10%</span>
                                </button>

                                <button type="button" class="plan-billing-toggle__btn" data-cycle="annual" data-months="12"
                                    data-discount="15">
                                    Anual <span class="plan-billing-toggle__off">-15%</span>
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