<!-- ============================================================================
     BILLING PAGE - LUKRATO PRO
     Página de planos e assinaturas (Refatorado)
     ============================================================================ -->

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/pages/billing.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/modal-pagamento.css">


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
?>

<!-- ============================================================================
     MARKUP HTML
     ============================================================================ -->
<div class="billing-page">
    <!-- Cabeçalho -->
    <header class="billing-header">
        <h1 class="billing-header__title"> Escolha seu plano</h1>
        <p class="billing-header__subtitle">
            Escolha o plano ideal para suas necessidades financeiras e tenha controle total sobre seu dinheiro
        </p>
    </header>

    <!-- Grid de Planos -->
    <?php if (empty($planItems)): ?>
        <div class="plans-grid plans-grid--empty">
            <div class="empty-state">
                <div class="empty-state__icon">
                    <i data-lucide="inbox" aria-hidden="true"></i>
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
                $icon = faToLucideIcon($meta['icone'] ?? $meta['icon'] ?? 'layer-group');
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
                            <i data-lucide="<?= htmlspecialchars($icon) ?>"></i>
                        </div>
                        <div class="plan-card__title-wrapper">
                            <h2 class="plan-card__title">
                                <?= htmlspecialchars($plan['name']) ?>
                                <?php if ($isCurrentPlan): ?>
                                    <span class="plan-card__badge">
                                        <i data-lucide="check" aria-hidden="true"></i>
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
                    <div class="plan-card__price" <?= strcasecmp($plan['code'], 'pro') === 0 ? 'id="planProPrice"' : '' ?>
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
                                    <i data-lucide="check"></i>
                                </span>
                                <?= htmlspecialchars($feature) ?>
                            </li>
                        <?php endforeach; ?>

                        <?php foreach ($missingFeatures as $feature): ?>
                            <li class="plan-card__feature">
                                <span class="plan-card__feature-icon plan-card__feature-icon--times" aria-label="Não incluído">
                                    <i data-lucide="x"></i>
                                </span>
                                <?= htmlspecialchars($feature) ?>
                            </li>
                        <?php endforeach; ?>

                        <?php if (empty($features) && empty($missingFeatures)): ?>
                            <li class="plan-card__feature">
                                <span class="plan-card__feature-icon plan-card__feature-icon--times">
                                    <i data-lucide="info"></i>
                                </span>
                                Recursos serão exibidos em breve
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Botão de Ação -->
                    <?php if ($isCurrentPlan): ?>
                        <?php if ($isInGrace ?? false): ?>
                            <!-- Status: Em período de carência - MOSTRAR RENOVAR -->
                            <div class="plan-card__grace-alert" role="alert">
                                <div class="plan-card__grace-alert-icon">
                                    <i data-lucide="clock"></i>
                                </div>
                                <div class="plan-card__grace-alert-content">
                                    <strong>⚠️ Plano vencido!</strong>
                                    <p>
                                        <?php if (($graceHoursRemaining ?? 0) <= 24): ?>
                                            Restam menos de 24 horas para renovar.
                                        <?php else: ?>
                                            Você tem <?= $graceDaysRemaining ?? 0 ?> dia(s) para renovar.
                                        <?php endif; ?>
                                        <br>
                                        <small>Bloqueio em: <?= htmlspecialchars($accessUntil ?? '') ?></small>
                                    </p>
                                </div>
                            </div>

                            <button type="button" class="plan-card__button plan-card__button--warning plan-card__button--renew"
                                id="btn-renew-subscription" data-plan-id="<?= htmlspecialchars((string) $plan['id']) ?>"
                                data-plan-code="<?= htmlspecialchars($plan['code']) ?>" aria-label="Renovar assinatura do plano Pro">
                                <i class="plan-card__button-icon" data-lucide="refresh-cw" aria-hidden="true"></i>
                                <span>Renovar agora</span>
                            </button>

                        <?php elseif ($isCanceled ?? false): ?>
                            <!-- Status: Cancelado mas ainda tem acesso -->
                            <button class="plan-card__button plan-card__button--warning" disabled
                                aria-label="Plano cancelado - acesso até <?= $accessUntil ?? '' ?>">
                                <i class="plan-card__button-icon" data-lucide="triangle-alert" aria-hidden="true"></i>
                                <span>
                                    Cancelado - Acesso até <?= htmlspecialchars($accessUntil ?? $renewDate ?? '') ?>
                                </span>
                            </button>

                            <!-- Botão para reativar -->
                            <button type="button" class="plan-card__button plan-card__button--primary" id="btn-reactivate-subscription"
                                data-plan-id="<?= htmlspecialchars((string) $plan['id']) ?>"
                                data-plan-code="<?= htmlspecialchars($plan['code']) ?>" aria-label="Reativar assinatura do plano Pro">
                                <i class="plan-card__button-icon" data-lucide="refresh-cw" aria-hidden="true"></i>
                                <span>Reativar assinatura</span>
                            </button>

                        <?php elseif ($isExpired ?? false): ?>
                            <!-- Status: Expirado/Bloqueado -->
                            <div class="plan-card__expired-alert" role="alert">
                                <i data-lucide="lock"></i>
                                <span>Acesso suspenso - Renove para continuar</span>
                            </div>

                            <button type="button" class="plan-card__button plan-card__button--primary" id="btn-renew-subscription"
                                data-plan-id="<?= htmlspecialchars((string) $plan['id']) ?>"
                                data-plan-code="<?= htmlspecialchars($plan['code']) ?>" aria-label="Renovar assinatura do plano Pro">
                                <i class="plan-card__button-icon" data-lucide="refresh-cw" aria-hidden="true"></i>
                                <span>Renovar assinatura</span>
                            </button>

                        <?php else: ?>
                            <!-- Status: Ativo normalmente -->
                            <button class="plan-card__button plan-card__button--active" disabled
                                aria-label="<?= $renewDate ? 'Plano ativo até ' . $renewDate : 'Plano atual' ?>">
                                <i class="plan-card__button-icon" data-lucide="circle-check" aria-hidden="true"></i>
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
                                    <i data-lucide="x-circle"></i>
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
                                    <span>Semestral</span> <span class="plan-billing-toggle__off"> -10%</span>
                                </button>

                                <button type="button" class="plan-billing-toggle__btn" data-cycle="annual" data-months="12"
                                    data-discount="15">
                                    <span>Anual</span> <span class="plan-billing-toggle__off"> -15%</span>
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
                            <i class="plan-card__button-icon" data-lucide="rocket" aria-hidden="true"></i>
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
<?php include __DIR__ . '/../partials/modals/modal-pagamento.php'; ?>

<!-- JS carregado via Vite (loadPageJs) -->