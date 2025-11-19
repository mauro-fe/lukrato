<!-- ============================================================================
     BILLING PAGE - LUKRATO
     ============================================================================
     Descrição: Página de planos e assinaturas
     Versão: 2.0 (Refatorado)
     ============================================================================ -->

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">

<style>
/* ============================================================================
   1. CONTAINER PRINCIPAL
   ============================================================================ */
.billing-container {
    max-width: 1000px;
    margin: clamp(40px, 8vw, 80px) auto;
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl);
    padding: clamp(32px, 6vw, 60px) clamp(24px, 5vw, 60px);
    color: var(--color-text);
    text-align: center;
    position: relative;
    overflow: hidden;
    border: 1px solid var(--glass-border);
}

/* 1.1 Efeitos Decorativos de Fundo */
.billing-container::before,
.billing-container::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    opacity: 0.08;
}

.billing-container::before {
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, var(--color-primary) 0%, transparent 70%);
    animation: float 8s ease-in-out infinite;
}

.billing-container::after {
    bottom: -30%;
    left: -15%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, var(--color-secondary) 0%, transparent 70%);
    animation: float 10s ease-in-out infinite reverse;
}

@keyframes float {

    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }

    50% {
        transform: translate(20px, -20px) scale(1.05);
    }
}

/* 1.2 Header do Container */
.billing-container h1 {
    font-size: clamp(2rem, 5vw, 2.5rem);
    font-weight: 800;
    margin-bottom: var(--spacing-3);
    color: var(--color-primary);
    letter-spacing: -0.02em;
    position: relative;
    z-index: 1;
    line-height: 1.2;
}

.billing-container>p {
    font-size: var(--font-size-base);
    color: var(--color-text-muted);
    margin-bottom: clamp(24px, 4vw, 40px);
    position: relative;
    z-index: 1;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* ============================================================================
   2. GRID DE PLANOS
   ============================================================================ */
.billing-plan {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
    gap: clamp(24px, 4vw, 40px);
    margin-top: clamp(32px, 5vw, 48px);
    position: relative;
    z-index: 1;
    align-items: stretch;
}

/* ============================================================================
   3. CARDS DOS PLANOS
   ============================================================================ */

/* 3.1 Estrutura Base */
.plan-card {
    background: var(--color-surface);
    backdrop-filter: var(--glass-backdrop);
    border-radius: var(--radius-lg);
    padding: clamp(24px, 4vw, 36px) clamp(20px, 3.5vw, 32px);
    border: 2px solid var(--glass-border);
    box-shadow: var(--shadow-md);
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

/* 3.2 Barra Superior Decorativa */
.plan-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
    opacity: 0;
    transition: opacity var(--transition-fast);
}

/* 3.3 Estados Hover e Focus */
.plan-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-xl);
    border-color: var(--color-primary);
}

.plan-card:hover::before {
    opacity: 1;
}

.plan-card:focus-within {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

/* 3.4 Título do Plano */
.plan-card h2 {
    font-size: clamp(1.5rem, 3vw, 1.75rem);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    line-height: 1.2;
}

.plan-card h2 i {
    font-size: 1.5rem;
    color: var(--color-primary);
    flex-shrink: 0;
}

/* 3.5 Descrição do Plano */
.plan-card>p {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
    margin: 0;
    line-height: 1.5;
    min-height: 42px;
    text-align: left;
}

/* 3.6 Preço */
.plan-price {
    font-size: clamp(2rem, 4vw, 2.5rem);
    font-weight: 800;
    color: var(--color-primary);
    letter-spacing: -0.03em;
    margin: var(--spacing-2) 0;
    line-height: 1;
}

.plan-price small {
    font-size: var(--font-size-base);
    font-weight: 500;
    color: var(--color-text-muted);
    margin-left: var(--spacing-1);
}

/* ============================================================================
   4. FEATURES / RECURSOS
   ============================================================================ */
.plan-features {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    gap: var(--spacing-3);
}

.plan-features li {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-3);
    font-size: var(--font-size-sm);
    line-height: 1.5;
    transition: transform var(--transition-fast);
    padding: var(--spacing-2) 0;
}

.plan-features li:hover {
    transform: translateX(4px);
}

/* 4.1 Ícones de Feature */
.plan-features li i {
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

.plan-features li i.fa-check {
    background: var(--color-success);
    color: white;
}

.plan-features li i.fa-times {
    background: var(--color-surface-muted);
    color: var(--color-text-muted);
    opacity: 0.5;
}

/* ============================================================================
   5. BOTÃO DE ASSINATURA
   ============================================================================ */
.plan-subscribe-btn {
    margin-top: auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-3);
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    color: white;
    font-weight: 700;
    font-size: var(--font-size-base);
    font-family: var(--font-primary);
    border: none;
    border-radius: var(--radius-lg);
    padding: clamp(14px, 2.5vw, 18px) clamp(24px, 4vw, 36px);
    cursor: pointer;
    transition: all var(--transition-normal);
    box-shadow: 0 8px 20px color-mix(in srgb, var(--color-primary) 35%, transparent);
    width: 100%;
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    min-height: 56px;
}

/* 5.1 Efeito de Brilho Animado */
.plan-subscribe-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg,
            transparent 30%,
            rgba(255, 255, 255, 0.25) 50%,
            transparent 70%);
    animation: shine 3s ease-in-out infinite;
}

@keyframes shine {

    0%,
    100% {
        transform: translateX(-100%) translateY(-100%);
    }

    50% {
        transform: translateX(100%) translateY(100%);
    }
}

/* 5.2 Ícone Sparkle */
.plan-subscribe-btn::after {
    content: '✨';
    position: absolute;
    right: clamp(16px, 3vw, 24px);
    font-size: 1.2rem;
    animation: sparkle 2s ease-in-out infinite;
}

@keyframes sparkle {

    0%,
    100% {
        opacity: 0.5;
        transform: scale(1);
    }

    50% {
        opacity: 1;
        transform: scale(1.3);
    }
}

/* 5.3 Estados do Botão */
.plan-subscribe-btn:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 30px color-mix(in srgb, var(--color-primary) 45%, transparent);
}

.plan-subscribe-btn:active {
    transform: translateY(-2px) scale(0.98);
}

.plan-subscribe-btn:focus-visible {
    outline: 2px solid white;
    outline-offset: 2px;
}

.plan-subscribe-btn span,
.plan-subscribe-btn i {
    position: relative;
    z-index: 1;
}

.plan-subscribe-btn i {
    font-size: 1.2rem;
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-3px);
    }
}

/* 5.4 Loading State */
.plan-subscribe-btn.loading {
    pointer-events: none;
    background: linear-gradient(135deg,
            var(--color-primary),
            color-mix(in srgb, var(--color-primary) 70%, black));
}

.plan-subscribe-btn.loading::before,
.plan-subscribe-btn.loading::after {
    display: none;
}

.plan-subscribe-btn.loading i {
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

/* 5.5 Disabled State */
.plan-subscribe-btn:disabled:not(.loading) {
    background: var(--color-surface-muted);
    color: var(--color-text-muted);
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
    opacity: 0.7;
}

.plan-subscribe-btn:disabled::before,
.plan-subscribe-btn:disabled::after {
    display: none;
}

.plan-subscribe-btn:disabled:hover {
    transform: none;
    box-shadow: none;
}

/* ============================================================================
   6. PLANO ATIVO
   ============================================================================ */
.plan-active {
    border: 2px solid var(--color-success);
    background: linear-gradient(135deg,
            color-mix(in srgb, var(--color-success) 10%, var(--color-surface)),
            color-mix(in srgb, var(--color-success) 5%, var(--color-surface)));
    box-shadow:
        0 0 0 4px color-mix(in srgb, var(--color-success) 15%, transparent),
        var(--shadow-lg);
}

.plan-active::before {
    background: linear-gradient(90deg, var(--color-success), var(--color-success));
    opacity: 1;
    height: 5px;
}

.plan-active h2 {
    color: var(--color-success);
}

.plan-active h2::after {
    content: '✓ Ativo';
    display: inline-flex;
    align-items: center;
    margin-left: auto;
    font-size: var(--font-size-xs);
    font-weight: 600;
    background: var(--color-success);
    color: white;
    padding: var(--spacing-1) var(--spacing-3);
    border-radius: var(--radius-sm);
    animation: pulseGlow 2s ease-in-out infinite;
}

@keyframes pulseGlow {

    0%,
    100% {
        opacity: 1;
        box-shadow: 0 0 0 0 color-mix(in srgb, var(--color-success) 70%, transparent);
    }

    50% {
        opacity: 0.85;
        box-shadow: 0 0 0 8px transparent;
    }
}

.plan-active .plan-subscribe-btn {
    background: var(--color-success);
    box-shadow: 0 8px 20px color-mix(in srgb, var(--color-success) 35%, transparent);
}

.plan-active .plan-subscribe-btn:hover {
    background: color-mix(in srgb, var(--color-success) 90%, black);
    transform: translateY(-2px);
}

/* ============================================================================
   7. BADGE RECOMENDADO
   ============================================================================ */
.plan-recommended {
    position: relative;
}

.plan-recommended::after {
    content: '⭐ Recomendado';
    position: absolute;
    right: clamp(16px, 3vw, 28px);
    background: linear-gradient(135deg, var(--color-warning), #f59e0b);
    color: white;
    font-size: var(--font-size-xs);
    font-weight: 700;
    padding: var(--spacing-2) var(--spacing-4);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    z-index: 2;
    animation: floatBadge 3s ease-in-out infinite;
}

@keyframes floatBadge {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-4px);
    }
}

/* ============================================================================
   8. MENSAGEM DE FEEDBACK
   ============================================================================ */
#msg {
    margin-top: var(--spacing-4);
    min-height: 1.5rem;
    font-size: var(--font-size-sm);
    font-weight: 500;
    padding: var(--spacing-2) var(--spacing-3);
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
    text-align: center;
}

#msg:not(:empty) {
    animation: slideInMessage 0.3s ease;
}

@keyframes slideInMessage {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#msg.success {
    background: color-mix(in srgb, var(--color-success) 15%, transparent);
    color: var(--color-success);
    border: 1px solid color-mix(in srgb, var(--color-success) 30%, transparent);
}

#msg.error {
    background: color-mix(in srgb, var(--color-danger) 15%, transparent);
    color: var(--color-danger);
    border: 1px solid color-mix(in srgb, var(--color-danger) 30%, transparent);
}

/* ============================================================================
   9. AJUSTES POR TEMA
   ============================================================================ */

/* Tema Escuro */
:root[data-theme="dark"] .billing-container {
    box-shadow:
        var(--shadow-xl),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

/* Tema Claro */
:root[data-theme="light"] .billing-container {
    background: rgba(255, 255, 255, 0.85);
}

:root[data-theme="light"] .plan-card {
    background: rgba(255, 255, 255, 0.7);
}

/* ============================================================================
   10. RESPONSIVIDADE
   ============================================================================ */

/* Tablets */
@media (max-width: 992px) {
    .billing-plan {
        grid-template-columns: 1fr;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
}

/* Mobile */
@media (max-width: 768px) {
    .billing-container {
        margin: 32px 16px;
        padding: 32px 20px;
    }

    .billing-plan {
        gap: 24px;
    }

    .plan-card {
        padding: 24px 20px;
    }

    .plan-recommended::after {
        top: -12px;
        right: 16px;
        font-size: 0.65rem;
        padding: 4px 12px;
    }
}

/* Small Mobile */
@media (max-width: 480px) {
    .billing-container {
        padding: 24px 16px;
        margin: 24px 12px;
    }

    .plan-card {
        padding: 20px 16px;
        gap: var(--spacing-3);
    }

    .plan-features {
        gap: var(--spacing-2);
    }

    .plan-features li {
        font-size: var(--font-size-xs);
        gap: var(--spacing-2);
    }

    .plan-subscribe-btn {
        font-size: var(--font-size-sm);
        padding: 12px 20px;
        min-height: 48px;
        letter-spacing: 0.02em;
    }

    .plan-subscribe-btn::after {
        right: 12px;
        font-size: 1rem;
    }
}

/* ============================================================================
   11. ANIMAÇÕES DE ENTRADA
   ============================================================================ */
.billing-container[data-aos] {
    opacity: 0;
    animation: fadeInContainer 0.6s ease forwards;
}

@keyframes fadeInContainer {
    to {
        opacity: 1;
    }
}

.billing-container .plan-card {
    opacity: 0;
    transform: translateY(30px);
    animation: slideUpPlan 0.6s ease forwards;
}

.billing-container .plan-card:nth-child(1) {
    animation-delay: 0.1s;
}

.billing-container .plan-card:nth-child(2) {
    animation-delay: 0.2s;
}

@keyframes slideUpPlan {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ============================================================================
   12. ACESSIBILIDADE
   ============================================================================ */
@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Foco visível para navegação por teclado */
.plan-card:focus-within .plan-subscribe-btn {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

/* ============================================================================
   FIM DOS ESTILOS
   ============================================================================ */
</style>

<!-- ============================================================================
     MARKUP HTML
     ============================================================================ -->
<div class="billing-container" data-aos="fade-up">
    <!-- Header -->
    <header>
        <h1>🚀 Escolha seu plano</h1>
        <p>Escolha o plano ideal para suas necessidades financeiras e tenha controle total sobre seu dinheiro</p>
    </header>

    <!-- Grid de Planos -->
    <div class="billing-plan">

        <!-- ===== PLANO GRATUITO ===== -->
        <article class="plan-card <?= $user->isGratuito() ? 'plan-active' : '' ?>" aria-label="Plano Gratuito">
            <h2>
                <i class="fas fa-gift" aria-hidden="true"></i>
                Gratuito
            </h2>
            <p>Perfeito para começar a organizar suas finanças pessoais</p>

            <div class="plan-price" aria-label="Preço: Gratuito">
                R$ 0<small>/mês</small>
            </div>

            <ul class="plan-features" role="list">
                <li>
                    <i class="fas fa-check" aria-label="Incluído"></i>
                    Controle básico de transações
                </li>
                <li>
                    <i class="fas fa-check" aria-label="Incluído"></i>
                    Categorização de gastos
                </li>
                <li>
                    <i class="fas fa-times" aria-label="Não incluído"></i>
                    Relatórios avançados
                </li>
                <li>
                    <i class="fas fa-times" aria-label="Não incluído"></i>
                    Agendamentos automáticos
                </li>
                <li>
                    <i class="fas fa-times" aria-label="Não incluído"></i>
                    Exportação de dados
                </li>
            </ul>

            <?php if ($user->isGratuito()): ?>
            <button class="plan-subscribe-btn" disabled aria-label="Plano atual">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Plano Atual</span>
            </button>
            <?php else: ?>
            <button class="plan-subscribe-btn" disabled aria-label="Plano básico">
                <span>Plano Básico</span>
            </button>
            <?php endif; ?>
        </article>

        <!-- ===== PLANO PRO ===== -->
        <article class="plan-card plan-recommended <?= $user->isPro() ? 'plan-active' : '' ?>"
            aria-label="Plano Pro - Recomendado">
            <h2>
                <i class="fa-solid fa-crown" aria-hidden="true"></i>
                Pro
            </h2>
            <p>Controle total e insights poderosos para suas finanças</p>

            <div class="plan-price" aria-label="Preço: 12 reais por mês">
                R$ 12<small>/mês</small>
            </div>

            <ul class="plan-features" role="list">
                <li>
                    <i class="fa-solid fa-check" aria-label="Incluído"></i>
                    Tudo do plano Gratuito
                </li>
                <li>
                    <i class="fa-solid fa-check" aria-label="Incluído"></i>
                    Relatórios completos e detalhados
                </li>
                <li>
                    <i class="fa-solid fa-check" aria-label="Incluído"></i>
                    Agendamentos automáticos
                </li>
                <li>
                    <i class="fa-solid fa-check" aria-label="Incluído"></i>
                    Exportação ilimitada (CSV, Excel)
                </li>
                <li>
                    <i class="fa-solid fa-check" aria-label="Incluído"></i>
                    Suporte prioritário
                </li>
            </ul>

            <?php if ($user->isPro()): ?>
            <button class="plan-subscribe-btn" disabled
                aria-label="Plano ativo até <?= htmlspecialchars(($user->plano_renova_em ?? $user->plan_renews_at) ?: 'data não disponível') ?>">
                <i class="fa-solid fa-check-circle" aria-hidden="true"></i>
                <span>Ativo até <?= htmlspecialchars(($user->plano_renova_em ?? $user->plan_renews_at) ?: '—') ?></span>
            </button>
            <?php else: ?>
            <button id="btnAssinar" class="plan-subscribe-btn" aria-label="Assinar plano Pro agora">
                <i class="fa-solid fa-rocket" aria-hidden="true"></i>
                <span>Assinar Pro Agora</span>
            </button>
            <div id="msg" aria-live="polite" aria-atomic="true"></div>
            <?php endif; ?>
        </article>

    </div>
</div>

<!-- Modal de Pagamento -->
<?php include __DIR__ . '/modal-pagamento.php'; ?>

<!-- ============================================================================
     SCRIPT DE FEEDBACK
     ============================================================================ -->
<?php if (isset($_GET['status'])): ?>
<script>
(function() {
    'use strict';

    const status = '<?= htmlspecialchars($_GET['status'], ENT_QUOTES, 'UTF-8') ?>';

    if (typeof Swal === 'undefined') {
        console.warn('[Billing] SweetAlert2 não está disponível');
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

    // Limpa URL após mostrar mensagem
    if (window.history && window.history.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
})();
</script>
<?php endif; ?>

<!-- ============================================================================
     FIM DO ARQUIVO
     ============================================================================ -->