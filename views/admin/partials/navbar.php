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
        padding: 30px 10px;
        box-shadow: var(--shadow-sm);
        border-bottom: 1px solid var(--color-border);
        position: sticky;
        top: 0;
        z-index: 1000;
        margin: 0 auto;
        border-radius: var(--radius-md);
        background-color: var(--glass-bg);
        height: 100px;
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

    @media (max-width: 576px) {
        .lk-navbar {
            padding: 18px 10px;
            height: auto;
        }

        .lk-navbar-inner {
            gap: var(--spacing-2) !important;
            align-items: stretch;
        }

        .lk-navbar-left {
            gap: var(--spacing-1);
            width: 100%;
        }

        .lk-welcome {
            font-size: 0.85rem;
        }

        .lk-navbar-left h1 {
            font-size: 1.2rem;
            line-height: 1.25;
        }

        .lk-navbar-left h1 span {
            font-size: 1rem;
        }

        .lk-navbar-right {
            justify-content: flex-end;
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
        background-color: var(--color-primary);
        color: var(--color-text);
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        padding: 8px 14px;
        cursor: pointer;
        transition: all var(--transition-fast);
        height: 40px;
    }

    .lk-navbar-right button:hover {
        background-color: var(--color-bg);
        color: #fff;
        border-color: var(--color-primary);
        transform: translateY(-2px);
    }

    .lk-navbar-right .lk-upgrade-btn {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        padding: 10px 18px;
        border-radius: var(--radius-full, 999px);
        background: linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 60%, var(--color-secondary) 40%));
        color: #fff;
        font-weight: 600;
        text-decoration: none;
        box-shadow: var(--shadow-md);
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        white-space: nowrap;
    }

    .lk-navbar-right .lk-upgrade-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        color: #fff;
    }

    .lk-navbar-right .lk-upgrade-btn i {
        font-size: 1rem;

    }

    .lk-navbar-right button:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }

    .lk-navbar-right button i {
        font-size: 1rem;
    }

    .theme-toggle {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-full, 999px);
        border: 1px solid var(--color-border-strong, rgba(255, 255, 255, 0.14));
        background: linear-gradient(135deg, color-mix(in srgb, var(--color-surface) 70%, transparent), color-mix(in srgb, var(--color-primary) 6%, transparent));
        box-shadow: var(--shadow-sm);
        color: var(--color-text);
        padding: 0;
        transition: background-color var(--transition-fast), box-shadow var(--transition-fast), border-color var(--transition-fast), transform var(--transition-fast);
    }

    .theme-toggle:hover {
        background: color-mix(in srgb, var(--color-surface) 78%, var(--color-primary) 8%);
        border-color: color-mix(in srgb, var(--color-primary) 35%, var(--color-border));
        box-shadow: var(--shadow-md);
    }

    .theme-toggle:active {
        transform: translateY(1px) scale(0.98);
    }

    .theme-toggle .theme-toggle__icon {
        font-size: 1.1rem;
        transition: transform 420ms cubic-bezier(.4, 0, .2, 1), opacity 420ms cubic-bezier(.4, 0, .2, 1), filter 420ms cubic-bezier(.4, 0, .2, 1);
        display: inline-flex;
    }

    .theme-toggle.is-animating .theme-toggle__icon {
        transform: rotate(180deg) scale(0.9);
        opacity: 0;
        filter: blur(0.3px);
    }

    .theme-toggle.dark {
        background: linear-gradient(135deg, color-mix(in srgb, var(--color-surface) 55%, transparent), color-mix(in srgb, var(--color-primary) 14%, transparent));
        border-color: color-mix(in srgb, var(--color-primary) 25%, var(--color-border));
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

        .lk-navbar-right .theme-toggle,
        .lk-navbar-right button {
            font-size: 0.65rem !important;
            height: 32px;
            width: 32px;
            border-radius: 50% !important;
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
        height: 22px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .6px;
        text-transform: uppercase;
        border: 1px solid var(--glass-border, rgba(255, 255, 255, .12));
        background: color-mix(in srgb, var(--color-surface) 70%, transparent);
        box-shadow: var(--shadow-sm);
        user-select: none;
        transition: var(--transition);
    }

    .lk-plan-badge i {
        font-size: 12px;
        opacity: .95;
    }

    .lk-plan-badge--pro {
        color: var(--color-warning);
        background: color-mix(in srgb, var(--color-warning) 12%, transparent);
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
                <i id="themeIcon" class="theme-toggle__icon fa-solid fa-sun"></i>
            </button>

            <?php include __DIR__ . '/notificacoes/bell.php'; ?>

            <?php if ($showNavbarUpgradeCTA): ?>
                <a href="<?= BASE_URL ?>billing" class="lk-upgrade-btn">
                    <i class="fa-solid fa-star"></i>
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
        const icon = document.getElementById('themeIcon');
        const STORAGE_KEY = 'lukrato-theme';
        const THEME_EVENT = 'lukrato:theme-changed';
        const THEME_ANIMATION_MS = 420;
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let isAnimating = false;

        // Detecta se eh FA 5 ou FA 6 (pra usar o prefixo correto)
        const FA_PREFIX = (() => {
            const link = [...document.styleSheets].find(s => (s.href || '').includes('font-awesome/5'));
            return link ? 'fas' : 'fa-solid';
        })();

        function getTheme() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'light' || saved === 'dark') return saved;
            const attr = root.getAttribute('data-theme');
            if (attr === 'light' || attr === 'dark') return attr;
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        function updateIconAndLabel(theme) {
            if (!icon || !btn) return;
            icon.className = '';
            icon.classList.add(FA_PREFIX, 'theme-toggle__icon', theme === 'dark' ? 'fa-moon' : 'fa-sun');
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
            if (!btn || !icon || prefersReducedMotion.matches) {
                applyTheme(nextTheme);
                return;
            }

            if (isAnimating) return;
            isAnimating = true;
            btn.classList.add('is-animating');

            const halfway = THEME_ANIMATION_MS / 2;

            // Troca o tema e o icone no meio da animacao para evitar flicker
            setTimeout(() => {
                updateIconAndLabel(nextTheme);
                applyTheme(nextTheme, {
                    updateUi: false
                });
            }, halfway);

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
</script>