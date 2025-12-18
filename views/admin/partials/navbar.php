<?php

use Application\Lib\Auth;

$navbarUser = $currentUser ?? Auth::user();
$subTitle = $subTitle ?? '';
$navbarName = $navbarUser->nome ?? ($navbarUser->name ?? '');
$navbarFirstName = '';
if ($navbarName) {
    $navbarFirstName = trim($navbarName);
    $parts = preg_split('/\s+/', $navbarFirstName);
    $navbarFirstName = $parts[0] ?? $navbarFirstName;
}
$showNavbarUpgradeCTA = isset($showUpgradeCTA) ? $showUpgradeCTA : !($navbarUser && method_exists($navbarUser, 'isPro') && $navbarUser->isPro());

?>
<style>
    /* =========================================================
 * NAVBAR
 * =======================================================*/

    .lk-navbar {
        color: var(--color-text);
        padding: 20px 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border-bottom: 1px solid var(--glass-border);
        position: sticky;
        top: 0;
        z-index: 1000;
        margin: 0 auto var(--spacing-5);
        border-radius: var(--radius-lg);
        background: var(--glass-bg);
        backdrop-filter: var(--glass-backdrop);
        height: 90px;
        transition: all 0.3s ease;
    }

    .lk-navbar::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, 
            color-mix(in srgb, var(--color-primary) 3%, transparent), 
            color-mix(in srgb, var(--color-secondary) 3%, transparent));
        border-radius: var(--radius-lg);
        opacity: 0.5;
        pointer-events: none;
    }

    .lk-navbar-inner {
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--spacing-5);
        height: 100%;
        padding: 0 20px;
    }

    /* ===============================
 * LADO ESQUERDO (logo / titulo)
 * =============================== */
    .lk-navbar-left {
        display: flex;
        align-items: flex-start;
        gap: var(--spacing-3);
        flex-direction: column;
    }

    .lk-welcome {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--color-text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
        letter-spacing: 0.01em;
    }

    .lk-welcome strong {
        color: var(--color-text);
        font-weight: 700;
    }

    .lk-navbar-left h1 {
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--color-primary);
        margin: 0;
        letter-spacing: 0.5px;
        line-height: 1.4;
    }

    .lk-navbar-left h1 span {
        color: var(--color-text);
        font-size: 1.2rem;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .lk-navbar {
            padding: 14px 8px;
            height: auto;
            min-height: 70px;
            margin: 0;
        }

        .lk-navbar-inner {
            gap: var(--spacing-2) !important;
            flex-wrap: wrap;
            padding: 0 12px;
        }

        .lk-navbar-left {
            gap: var(--spacing-2);
            flex: 1;
            min-width: 180px;
        }

        .lk-navbar-left h1 {
            font-size: 1.35rem;
        }

        .lk-navbar-right {
            gap: 10px;
        }

        .lk-navbar-right .lk-upgrade-btn {
            padding: 9px 14px;
            font-size: 0.8125rem;
        }
    }

    @media (max-width: 576px) {
        .lk-navbar {
            padding: 12px 8px;
            height: auto;
            margin: 0;
            margin-bottom: var(--spacing-5);
            border-radius: var(--radius-md);
        }

        .lk-navbar-inner {
            gap: var(--spacing-3) !important;
            flex-direction: column;
            align-items: stretch;
            padding: 0;
        }

        .lk-navbar-left {
            gap: var(--spacing-1);
            width: 100%;
            align-items: flex-start;
        }

        /* Linha 1: Bem-vindo + Badge */
        .lk-welcome {
            font-size: 0.8125rem;
            justify-content: center;
            width: 100%;
        }

        /* Linha 2: Título da página */
        .lk-navbar-left h1 {
            font-size: 1.25rem;
            line-height: 1.3;
            text-align: center;
            margin-top: 0;
            width: 100%;
        }

        .lk-navbar-left h1 span {
            font-size: 1rem;
        }

        /* Linha 3: Botões */
        .lk-navbar-right {
            justify-content: center;
            gap: 10px;
            width: 100%;
            flex-shrink: 0;
        }

        .lk-navbar-right .lk-upgrade-btn {
            padding: 10px 16px;
            font-size: 0.8125rem;
        }

        .lk-navbar-right .lk-upgrade-btn i {
            font-size: 1rem;
        }

        .lk-plan-badge {
            height: 24px;
            padding: 0 10px;
            font-size: 0.65rem;
        }

        /* Toggle theme menos arredondado no mobile */
        .theme-toggle {
            border-radius: var(--radius-md);
        }

        .theme-toggle::before,
        .theme-toggle::after {
            border-radius: var(--radius-md);
        }
    }

    @media (max-width: 430px) {
        .lk-navbar {
            padding: 10px 6px;
        }

        .lk-navbar-left h1 {
            font-size: 1.125rem;
        }

        .lk-navbar-left h1 span {
            display: none;
        }

        .lk-welcome {
            font-size: 0.75rem;
        }

        .lk-navbar-right {
            gap: 8px;
        }

        .lk-navbar-right .lk-upgrade-btn {
            padding: 9px 14px;
            font-size: 0.75rem;
        }

        .lk-navbar-right .lk-upgrade-btn i {
            font-size: 0.9rem;
        }

        .lk-plan-badge {
            font-size: 0.6rem;
            padding: 0 8px;
            height: 22px;
        }
    }

    /* ===============================
 * LADO DIREITO (acoes / botoes)
 * =============================== */
    .lk-navbar-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .lk-navbar-right button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-5);
        background-color: transparent;
        color: #fff !important;
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        padding: 8px 14px;
        cursor: pointer;
        transition: all var(--transition-fast);
        height: 40px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .lk-navbar-right button:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .lk-navbar-right .lk-upgrade-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        padding: 12px 24px;
        border-radius: var(--radius-full, 999px);
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: #fff !important;
        font-weight: 700;
        font-size: 0.9375rem;
        text-decoration: none;
        box-shadow: 0 4px 16px color-mix(in srgb, var(--color-primary) 40%, transparent);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        overflow: hidden;
        border: 2px solid transparent;
    }

    .lk-navbar-right .lk-upgrade-btn::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, 
            color-mix(in srgb, var(--color-primary) 120%, white), 
            color-mix(in srgb, var(--color-secondary) 120%, white));
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .lk-navbar-right .lk-upgrade-btn::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .lk-navbar-right .lk-upgrade-btn:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 8px 28px color-mix(in srgb, var(--color-primary) 60%, transparent);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .lk-navbar-right .lk-upgrade-btn:hover::before {
        opacity: 1;
    }

    .lk-navbar-right .lk-upgrade-btn:hover::after {
        width: 300px;
        height: 300px;
    }

    .lk-navbar-right .lk-upgrade-btn:active {
        transform: translateY(-1px) scale(1.02);
    }

    .lk-navbar-right .lk-upgrade-btn i {
        font-size: 1.1rem;
        position: relative;
        z-index: 1;
        animation: starPulse 2s ease-in-out infinite;
        color: #fff !important;
    }

    .lk-navbar-right .lk-upgrade-btn span {
        position: relative;
        z-index: 1;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        color: #fff !important;
    }

    @keyframes starPulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.2);
        }
    }

    .lk-navbar-right button:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }

    .lk-navbar-right button i {
        font-size: 1rem;
        color: #fff !important;
    }

    /* SOBRESCREVER .btn-ghost NO NAVBAR - FORÇA BRANCO */
    .lk-navbar-right .btn,
    .lk-navbar-right .btn-ghost,
    .lk-navbar-right button,
    #lk-bell.btn,
    #lk-bell.btn-ghost {
        color: #fff !important;
    }

    .lk-navbar-right .btn i,
    .lk-navbar-right .btn-ghost i,
    .lk-navbar-right button i,
    .lk-navbar-right .fas,
    .lk-navbar-right .fa-solid,
    .lk-navbar-right .fa-regular,
    .lk-navbar-right .fa-bell,
    #lk-bell i,
    #lk-bell .fas,
    #lk-bell .fa-bell {
        color: #fff !important;
    }

    .theme-toggle {
        position: relative;
        width: 68px;
        height: 34px;
        border-radius: 999px;
        border: none;
        background: #e2e8f0;
        padding: 0;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .theme-toggle.dark {
        background: #1e293b;
        box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.4);
    }

    .theme-toggle:hover {
        box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.15),
                    0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
    }

    .theme-toggle.dark:hover {
        box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.4),
                    0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
    }

    .theme-toggle:active {
        transform: scale(0.96);
    }

    /* Container dos ícones - atrás da bolinha */
    .theme-toggle__icon-wrapper {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 9px;
        z-index: 1;
    }

    .theme-toggle__icon {
        font-size: 0.875rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
    }

    /* Ícone do sol (esquerda) - FORÇAR BRANCO */
    .theme-toggle__icon--sun {
        color: #ffffff !important;
        opacity: 0;
    }

    .theme-toggle.dark .theme-toggle__icon--sun {
        opacity: 1;
        color: #ffffff !important;
    }

    /* Ícone da lua (direita) - FORÇAR BRANCO */
    .theme-toggle__icon--moon {
        color: #ffffff !important;
        opacity: 1;
    }

    .theme-toggle.dark .theme-toggle__icon--moon {
        opacity: 0;
        color: #ffffff !important;
    }

    /* Slider (bolinha que desliza) - por cima dos ícones */
    .theme-toggle::before {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #ffffff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2),
                    0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        z-index: 3;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .theme-toggle.dark::before {
        left: calc(100% - 31px);
        background: #f8fafc;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.4),
                    0 1px 4px rgba(0, 0, 0, 0.2);
    }

    .theme-toggle:hover::before {
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25),
                    0 2px 4px rgba(0, 0, 0, 0.15);
    }

    /* Ícone dentro da bolinha */
    .theme-toggle::after {
        content: '☀️';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        z-index: 4;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        pointer-events: none;
    }

    .theme-toggle.dark::after {
        content: '🌙';
        left: calc(100% - 31px);
    }

    @media (prefers-reduced-motion: reduce) {

        .theme-toggle,
        .theme-toggle .theme-toggle__icon {
            transition: none !important;
        }
    }


    .lk-navbar-notifications .lk-popover {
        position: absolute;
        top: calc(100% + var(--spacing-2));
        right: -30;
        z-index: 1100;
        width: clamp(250px, 40vw, 200px);
    }

    .lk-popover-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md, 0 12px 32px rgba(0, 0, 0, 0.35));
        overflow: hidden;
    }

    .lk-popover-h {
        padding: 12px 16px;
        font-weight: 600;
        border-bottom: 1px solid var(--color-border);
        background: var(--color-surface-muted);
    }

    .lk-popover-b {
        max-height: 320px;
        overflow-y: auto;
        padding: 12px 16px;
        background: var(--glass-bg);
    }

    .lk-popover-f {
        padding: 12px 16px;
        border-top: 1px solid var(--color-border);
        text-align: right;
        background: var(--color-surface-muted);
    }


    @media (max-width: 600px) {
        .lk-navbar-right .theme-toggle {
            width: 58px;
            height: 30px;
        }

        .lk-navbar-right .theme-toggle::before,
        .lk-navbar-right .theme-toggle::after {
            width: 24px;
            height: 24px;
            top: 3px;
            left: 3px;
        }

        .lk-navbar-right .theme-toggle.dark::before,
        .lk-navbar-right .theme-toggle.dark::after {
            left: calc(100% - 27px);
        }

        .lk-navbar-right .theme-toggle::after {
            font-size: 0.75rem;
        }

        .lk-navbar-right .theme-toggle__icon {
            font-size: 0.75rem;
            width: 14px;
            height: 14px;
        }

        .lk-navbar-right .theme-toggle__icon-wrapper {
            padding: 0 8px;
        }

        .lk-navbar-right button {
            font-size: 0.65rem !important;
            height: 32px;
            width: 32px;
        }

        .lk-navbar-right i {
            font-size: 0.85rem !important;
        }

        .lk-upgrade-btn span {
            font-size: 0.7rem !important;
        }
    }

    @media (max-width: 500px) {
        .lk-navbar-left span {
            display: none !important;
        }

        .lk-welcome span {
            display: block !important;
        }
    }

    @media (max-width: 300px) {
        .lk-navbar-left h1 {
            font-size: 0.95rem;
        }

        .lk-navbar-left h1 span {
            font-size: 0.8rem;
        }

        .lk-navbar-right .theme-toggle,
        .lk-navbar-right button {
            font-size: 0.65rem !important;
            height: 32px;
            width: 32px;
        }

        .lk-navbar-right i {
            font-size: 0.85rem !important;
        }

        .lk-upgrade-btn span {
            font-size: 0.7rem !important;
        }
    }

    .lk-welcome {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--color-text-muted);
        font-size: 13px;
    }

    .lk-welcome strong {
        color: var(--color-text);
        font-weight: 700;
    }

    .lk-plan-badge {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 26px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .8px;
        text-transform: uppercase;
        border: 2px solid var(--glass-border, rgba(255, 255, 255, .12));
        background: color-mix(in srgb, var(--color-surface) 70%, transparent);
        backdrop-filter: blur(8px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        user-select: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: default;
    }

    .lk-plan-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .lk-plan-badge i {
        font-size: 12px;
        opacity: .95;
    }

    .lk-plan-badge--pro {
        color: var(--color-warning);
        background: linear-gradient(135deg, 
            color-mix(in srgb, var(--color-warning) 15%, transparent),
            color-mix(in srgb, var(--color-primary) 12%, transparent));
        border-color: color-mix(in srgb, var(--color-warning) 30%, transparent);
        box-shadow: 0 2px 12px color-mix(in srgb, var(--color-warning) 20%, transparent);
    }

    .lk-plan-badge--pro i {
        animation: crownShine 3s ease-in-out infinite;
    }

    @keyframes crownShine {
        0%, 100% {
            opacity: 1;
            filter: drop-shadow(0 0 2px currentColor);
        }
        50% {
            opacity: 0.8;
            filter: drop-shadow(0 0 4px currentColor);
        }
    }

    .lk-plan-badge--free {
        color: var(--color-neutral);
        background: color-mix(in srgb, var(--color-neutral) 12%, transparent);
    }

    .lk-plan-badge:focus {
        outline: 2px solid color-mix(in srgb, var(--color-primary) 55%, transparent);
        outline-offset: 2px;
    }

    /* Tooltip */
    .lk-plan-badge[data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        left: 50%;
        top: -10px;
        transform: translate(-50%, -100%);
        white-space: nowrap;
        padding: 8px 10px;
        border-radius: 10px;
        background: var(--color-surface);
        color: var(--color-text);
        border: 1px solid var(--glass-border, rgba(255, 255, 255, .12));
        box-shadow: var(--shadow-md);
        opacity: 0;
        pointer-events: none;
        transition: var(--transition);
        font-size: 12px;
        z-index: 30;
    }

    .lk-plan-badge:hover::after,
    .lk-plan-badge:focus::after {
        opacity: 1;
    }

    /* AnimaÃ§Ã£o de entrada do badge */
    @keyframes lkBadgeIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(-2px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    /* Aplica a animação apenas no carregamento */
    .lk-plan-badge--pro {
        animation: lkBadgeIn 420ms cubic-bezier(.16, 1, .3, 1);
    }

    /* ===============================
   Acessibilidade â€“ Redução de movimento
   =============================== */
    @media (prefers-reduced-motion: reduce) {

        .lk-plan-badge,
        .lk-plan-badge--pro {
            animation: none !important;
            transition: none !important;
        }
    }
</style>

<nav class="lk-navbar" data-aos="fade-up">
    <div class="lk-navbar-inner">
        <div class="lk-navbar-left">
            <div class="lk-welcome">
                <span>Bem-vindo</span>
                <strong><?= $navbarFirstName ?: 'usuário' ?></strong>

                <?php
                // Ajuste aqui conforme o seu sistema:
                // Ex.: $isPro = ($usuario->plano === 'pro');
                $isPro = !($showNavbarUpgradeCTA ?? true); // se você mostra CTA de upgrade, normalmente o user Ã© Free
                $planLabel = $isPro ? 'PRO' : 'FREE';
                $planClass = $isPro ? 'lk-plan-badge--pro' : 'lk-plan-badge--free';
                $planTip   = $isPro ? 'Plano Pro ativo' : 'Você está no Free. Faça upgrade para liberar recursos.';
                ?>

                <span class="lk-plan-badge <?= $planClass ?>" tabindex="0" role="status"
                    aria-label="<?= htmlspecialchars($planTip, ENT_QUOTES, 'UTF-8') ?>"
                    data-tooltip="<?= htmlspecialchars($planTip, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid <?= $isPro ? 'fa-crown' : 'fa-leaf' ?>"></i>
                    <?= $planLabel ?>
                </span>
            </div>

            <h1><?= $pageTitle ?? 'Painel' ?> <span><?= $subTitle ? "- {$subTitle}" : '' ?></span></h1>
        </div>

        <div class="lk-navbar-right">
            <button id="toggleTheme" type="button" class="theme-toggle" aria-label="Alternar tema"
                title="Modo claro/escuro">
                <div class="theme-toggle__icon-wrapper">
                    <i class="theme-toggle__icon theme-toggle__icon--sun fa-solid fa-sun" style="color: #fff !important;"></i>
                    <i class="theme-toggle__icon theme-toggle__icon--moon fa-solid fa-moon" style="color: #fff !important;"></i>
                </div>
            </button>

            <?php include __DIR__ . '/notificacoes/bell.php'; ?>

            <?php if ($showNavbarUpgradeCTA): ?>
                <a href="<?= BASE_URL ?>billing" class="lk-upgrade-btn">
                    <i class="fa-solid fa-star" style="color: #fff !important;"></i>
                    <span>Pro</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    (() => {
        const root = document.documentElement;
        const btn = document.getElementById('toggleTheme');
        const STORAGE_KEY = 'lukrato-theme';
        const THEME_EVENT = 'lukrato:theme-changed';
        const THEME_ANIMATION_MS = 420;
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let isAnimating = false;

        function getTheme() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'light' || saved === 'dark') return saved;
            const attr = root.getAttribute('data-theme');
            if (attr === 'light' || attr === 'dark') return attr;
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        function updateIconAndLabel(theme) {
            if (!btn) return;
            const label = theme === 'dark' ? 'Alternar para modo claro' : 'Alternar para modo escuro';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
            btn.classList.toggle('dark', theme === 'dark');
        }

        function notifyThemeChange(theme) {
            document.dispatchEvent(new CustomEvent(THEME_EVENT, {
                detail: {
                    theme
                }
            }));
        }

        function applyTheme(theme, options = {
            updateUi: true
        }) {
            root.setAttribute('data-theme', theme);
            localStorage.setItem(STORAGE_KEY, theme);
            if (options.updateUi) updateIconAndLabel(theme);
            notifyThemeChange(theme);
        }

        function animateAndApplyTheme(nextTheme) {
            if (!btn || prefersReducedMotion.matches) {
                applyTheme(nextTheme);
                return;
            }

            if (isAnimating) return;
            isAnimating = true;
            btn.classList.add('is-animating');

            // Aplica o tema imediatamente
            updateIconAndLabel(nextTheme);
            applyTheme(nextTheme, {
                updateUi: false
            });

            setTimeout(() => {
                btn.classList.remove('is-animating');
                isAnimating = false;
            }, THEME_ANIMATION_MS + 20);
        }

        function toggleTheme() {
            const next = getTheme() === 'dark' ? 'light' : 'dark';
            animateAndApplyTheme(next);
        }

        btn?.addEventListener('click', toggleTheme);

        // aplica o tema inicial
        applyTheme(getTheme());

        // sincroniza se o sistema mudar
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem(STORAGE_KEY)) applyTheme(e.matches ? 'dark' : 'light');
        });

        // sincroniza entre abas
        window.addEventListener('storage', (e) => {
            if (e.key !== STORAGE_KEY) return;
            const newTheme = (e.newValue === 'light' || e.newValue === 'dark') ? e.newValue : getTheme();
            applyTheme(newTheme);
        });
    })();

    // FORÇA ÍCONES BRANCOS NO NAVBAR
    (() => {
        function forceWhiteIcons() {
            const selectors = [
                '.lk-navbar-right i',
                '.lk-navbar-right .fa-solid',
                '.lk-navbar-right .fa-regular',
                '.lk-navbar-right .fas',
                '.theme-toggle__icon',
                '.theme-toggle__icon--sun',
                '.theme-toggle__icon--moon',
                '#lk-bell i',
                '.lk-upgrade-btn i'
            ];
            
            selectors.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.style.setProperty('color', '#ffffff', 'important');
                });
            });
        }

        // Aplica imediatamente
        forceWhiteIcons();
        
        // Aplica após o DOM estar totalmente carregado
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', forceWhiteIcons);
        }
        
        // Aplica após 100ms (para garantir que sobrescreva qualquer JS)
        setTimeout(forceWhiteIcons, 100);
        
        // Observa mudanças no DOM e reaplica
        const observer = new MutationObserver(forceWhiteIcons);
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    })();
</script>