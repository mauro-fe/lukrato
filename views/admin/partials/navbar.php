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

$isPro = !($showNavbarUpgradeCTA ?? true);
$planLabel = $isPro ? 'PRO' : 'FREE';
$planTip   = $isPro ? 'Plano Pro ativo' : 'Você está no Free. Faça upgrade para liberar recursos.';
?>

<style>
.tw-navbar {
    width: 100%;
    background: rgba(255, 255, 255, 0.35);
    border: 1px solid rgba(30, 41, 59, 0.1);
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
}

[data-theme="dark"] .tw-navbar {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.12);
}

.tw-navbar-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tw-navbar-welcome {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
}

.tw-navbar-welcome span {
    color: var(--color-text-muted);
}

.tw-navbar-welcome strong {
    color: var(--color-text);
}

.tw-navbar-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.tw-navbar-badge.pro {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(249, 115, 22, 0.2));
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

[data-theme="dark"] .tw-navbar-badge.pro {
    color: #fbbf24;
}

.tw-navbar-badge.free {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(16, 185, 129, 0.2));
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

[data-theme="dark"] .tw-navbar-badge.free {
    color: #4ade80;
}

.tw-navbar-title {
    text-align: center;
    font-size: 20px;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
}

.tw-navbar-subtitle {
    font-size: 16px;
    font-weight: 400;
    color: var(--color-text-muted);
}

.tw-navbar-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.tw-theme-toggle {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(230, 126, 34, 0.1), rgba(230, 126, 34, 0.05));
    border: 1px solid rgba(230, 126, 34, 0.2);
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tw-theme-toggle:hover {
    background: linear-gradient(135deg, rgba(230, 126, 34, 0.15), rgba(230, 126, 34, 0.08));
    border-color: rgba(230, 126, 34, 0.3);
    transform: scale(1.05);
}

.tw-theme-toggle i {
    position: absolute;
    transition: all 0.3s ease;
    font-size: 18px;
}

.tw-theme-toggle .fa-sun {
    opacity: 1;
    transform: rotate(0deg) scale(1);
    color: #f59e0b;
}

.tw-theme-toggle .fa-moon {
    opacity: 0;
    transform: rotate(-90deg) scale(0);
    color: #60a5fa;
}

.tw-theme-toggle.dark .fa-sun {
    opacity: 0;
    transform: rotate(90deg) scale(0);
}

.tw-theme-toggle.dark .fa-moon {
    opacity: 1;
    transform: rotate(0deg) scale(1);
}

.tw-upgrade-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    background: linear-gradient(135deg, #f59e0b, #ea580c);
    color: white !important;
    border: 1px solid rgba(249, 115, 22, 0.5);
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.tw-upgrade-btn:hover {
    background: linear-gradient(135deg, #d97706, #c2410c);
    box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
    transform: translateY(-2px);
}

.tw-upgrade-btn:active {
    transform: scale(0.95);
}

@media (min-width: 640px) {
    .tw-navbar {
        padding: 20px;
    }
    
    .tw-navbar-title {
        font-size: 24px;
    }
}

@media (min-width: 1280px) {
    .tw-navbar-content {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
    
    .tw-navbar-left {
        display: flex;
        flex-direction: column;
        gap: 4px;
        align-items: flex-start;
    }
    
    .tw-navbar-welcome {
        justify-content: flex-start;
    }
    
    .tw-navbar-title {
        text-align: left;
    }
    
    .tw-navbar-actions {
        justify-content: flex-end;
    }
}
</style>

<nav class="tw-navbar" data-aos="fade-up">
    <div class="tw-navbar-content">
        <div class="tw-navbar-left">
            <div class="tw-navbar-welcome">
                <span>Bem-vindo</span>
                <strong><?= $navbarFirstName ?: 'usuário' ?></strong>
                
                <span class="tw-navbar-badge <?= $isPro ? 'pro' : 'free' ?>"
                      tabindex="0" role="status"
                      aria-label="<?= htmlspecialchars($planTip, ENT_QUOTES, 'UTF-8') ?>"
                      title="<?= htmlspecialchars($planTip, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid <?= $isPro ? 'fa-crown' : 'fa-leaf' ?>"></i>
                    <?= $planLabel ?>
                </span>
            </div>

            <h1 class="tw-navbar-title">
                <?= $pageTitle ?? 'Painel' ?>
                <?php if ($subTitle): ?>
                    <span class="tw-navbar-subtitle">- <?= $subTitle ?></span>
                <?php endif; ?>
            </h1>
        </div>

        <div class="tw-navbar-actions">
            <button id="toggleTheme" type="button" class="tw-theme-toggle" 
                    aria-label="Alternar tema" title="Modo claro/escuro">
                <i class="fa-solid fa-sun"></i>
                <i class="fa-solid fa-moon"></i>
            </button>

            <?php include __DIR__ . '/notificacoes/bell.php'; ?>

            <?php if ($showNavbarUpgradeCTA): ?>
                <a href="<?= BASE_URL ?>billing" class="tw-upgrade-btn"
                   aria-label="Fazer upgrade para Pro">
                    <i class="fa-solid fa-crown"></i>
                    <span class="d-none d-sm-inline">PRO</span>
                    <span class="d-sm-none">PRO</span>
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
        document.dispatchEvent(new CustomEvent(THEME_EVENT, { detail: { theme } }));
    }

    function applyTheme(theme, options = { updateUi: true }) {
        root.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        if (options.updateUi) updateIconAndLabel(theme);
        notifyThemeChange(theme);
    }

    function toggleTheme() {
        const current = getTheme();
        const next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next);
    }

    // Initialize
    const initialTheme = getTheme();
    updateIconAndLabel(initialTheme);
    applyTheme(initialTheme, { updateUi: false });

    // Event listeners
    if (btn) btn.addEventListener('click', toggleTheme);

    // Listen for theme changes from other sources
    document.addEventListener(THEME_EVENT, (e) => {
        updateIconAndLabel(e.detail.theme);
    });
})();
</script>
