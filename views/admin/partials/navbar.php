<?php

use Application\Lib\Auth;

$navbarUser = $currentUser ?? Auth::user();
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
        max-width: 1200px;
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
        align-items: center;
        gap: var(--spacing-6);
    }

    .lk-navbar-left h1 {
        font-size: 1.6rem;
        font-weight: 600;
        color: var(--color-primary);
        margin: 0;
        border-bottom: 3px solid var(--color-primary);
        letter-spacing: 1px;
        line-height: 1.5;
    }

    .lk-navbar-left h1 span {
        color: var(--color-text);
        font-size: 1.4rem;

    }

    @media (max-width: 576px) {
        .lk-navbar {
            padding: 18px 10px;
            height: auto;
        }

        .lk-navbar-inner {
            gap: var(--spacing-2) !important;
        }

        .lk-navbar-left h1 {
            font-size: 1.25rem;
            line-height: 1.3;
        }

        .lk-navbar-left h1 span {
            font-size: 1.1rem;
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


    /* =========================================================
 * HEADER CONTROLS (menu lateral & tema)
 * =======================================================*/

    .theme-toggle {
        border: 1px solid var(--glass-border);
        backdrop-filter: var(--glass-backdrop);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: background var(--transition-fast);
        color: var(--color-text) !important;
    }

    .lk-header .header-menu-btn {
        z-index: 1200;
    }


    .theme-toggle:hover {
        background-color: var(--color-primary) !important;
    }

    .theme-toggle i {
        position: absolute;
        font-size: 18px;
        transition: opacity var(--transition-fast), transform var(--transition-fast);
    }

    .theme-toggle i.fa-sun {
        opacity: 1;
        transform: rotate(0deg);

    }

    .theme-toggle i.fa-moon {
        opacity: 0;
        transform: rotate(-90deg);
    }

    .theme-toggle.dark i.fa-sun {
        opacity: 0;
        transform: rotate(90deg);

    }

    .theme-toggle.dark i.fa-moon {
        opacity: 1;
        transform: rotate(0deg);

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
    }

    @media (max-width: 300px) {
        .lk-navbar-left h1 {
            font-size: 0.9rem;
        }

        .lk-navbar-left h1 span {
            font-size: 0.75rem;
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
</style>

<nav class="lk-navbar" data-aos="fade-up">
    <div class="lk-navbar-inner">
        <div class="lk-navbar-left">
            <h1><?= $pageTitle ?? 'Painel' ?> <span> - <?= $subTitle ?></span></h1>
        </div>

        <div class="lk-navbar-right">
            <button id="toggleTheme" type="button" class="theme-toggle" aria-label="Alternar tema"
                title="Modo claro/escuro">
                <i id="themeIcon" class="fa-solid fa-sun"></i>
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

        // Detecta se é FA 5 ou FA 6 (pra usar o prefixo correto)
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

        function notifyThemeChange(theme) {
            document.dispatchEvent(new CustomEvent(THEME_EVENT, {
                detail: {
                    theme
                }
            }));
        }

        function applyTheme(theme) {
            root.setAttribute('data-theme', theme);
            localStorage.setItem(STORAGE_KEY, theme);
            updateIcon(theme);
            notifyThemeChange(theme);
        }

        function updateIcon(theme) {
            if (!icon) return;
            icon.className = ''; // limpa as classes antigas
            icon.classList.add(FA_PREFIX, theme === 'dark' ? 'fa-moon' : 'fa-sun');
            btn?.classList.toggle('dark', theme === 'dark');
        }

        function toggleTheme() {
            const next = getTheme() === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            // applyTheme already dispara o evento
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