<?php

use Application\Lib\Auth;

$topNavUser = $currentUser ?? Auth::user();
$topNavName = $topNavUser->nome ?? ($topNavUser->name ?? '');
$topNavFirstName = '';
if ($topNavName) {
    $topNavFirstName = trim($topNavName);
    $parts = preg_split('/\s+/', $topNavFirstName);
    $topNavFirstName = $parts[0] ?? $topNavFirstName;
}

$isPro = $topNavUser && method_exists($topNavUser, 'isPro') && $topNavUser->isPro();
$planLabel = $isPro ? 'PRO' : 'FREE';
?>

<div class="top-navbar">
    <div class="top-navbar-container">
        <!-- Page Title / Breadcrumb -->
        <div class="top-navbar-title">
            <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
            <?php if (!empty($subTitle)): ?>
                <span class="subtitle"><?= $subTitle ?></span>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="top-navbar-actions">
            <!-- User Info -->
            <div class="user-info">
                <span class="greeting">Olá, <strong><?= $topNavFirstName ?: 'usuário' ?></strong></span>
                <span class="plan-badge <?= $isPro ? 'pro' : 'free' ?>">
                    <i class="fa-solid <?= $isPro ? 'fa-crown' : 'fa-leaf' ?>"></i>
                    <?= $planLabel ?>
                </span>
            </div>

            <!-- Theme Toggle -->
            <button id="topNavThemeToggle" type="button" class="top-nav-btn theme-toggle"
                aria-label="Alternar tema" title="Modo claro/escuro">
                <i class="fa-solid fa-sun"></i>
                <i class="fa-solid fa-moon"></i>
            </button>

            <!-- Notifications -->
            <div class="top-nav-bell-wrapper">
                <?php include __DIR__ . '/notificacoes/bell.php'; ?>
            </div>

            <!-- Upgrade Button (if not pro) -->
            <?php if (!$isPro): ?>
                <a href="<?= BASE_URL ?>billing" class="top-nav-btn upgrade-btn"
                    title="Fazer upgrade para Pro">
                    <i class="fa-solid fa-crown"></i>
                    <span class="btn-text">Upgrade</span>
                </a>
            <?php endif; ?>

            <!-- Logout Button -->
            <a href="<?= BASE_URL ?>logout" id="topNavLogout" class="top-nav-btn logout-btn"
                title="Sair do sistema">
                <i class="fas fa-sign-out-alt"></i>
                <span class="btn-text">Sair</span>
            </a>
        </div>
    </div>
</div>

<script>
    (() => {
        'use strict';

        const root = document.documentElement;
        const themeBtn = document.getElementById('topNavThemeToggle');
        const STORAGE_KEY = 'lukrato-theme';
        const THEME_EVENT = 'lukrato:theme-changed';

        function getTheme() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'light' || saved === 'dark') return saved;
            const attr = root.getAttribute('data-theme');
            if (attr === 'light' || attr === 'dark') return attr;
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        function updateThemeIcon(theme) {
            if (!themeBtn) return;
            themeBtn.classList.toggle('dark', theme === 'dark');
        }

        async function saveThemeToDatabase(theme) {
            // Temporariamente desabilitado - rota não implementada
            return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                if (!csrfToken) {
                    return;
                }

                const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
                const url = baseUrl + 'api/perfil/tema';

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        theme: theme,
                        csrf_token: csrfToken
                    }),
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json();
                }
            } catch (error) {
                // Silenciosamente falha - não é crítico
            }
        }

        function applyTheme(theme, saveToDb = true) {
            root.setAttribute('data-theme', theme);
            localStorage.setItem(STORAGE_KEY, theme);
            updateThemeIcon(theme);
            document.dispatchEvent(new CustomEvent(THEME_EVENT, {
                detail: {
                    theme
                }
            }));

            // Salvar no banco de dados apenas se solicitado
            if (saveToDb) {
                saveThemeToDatabase(theme);
            }
        }

        function toggleTheme() {
            const current = getTheme();
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        }

        // Initialize - sincronizar com tema do servidor
        const initialTheme = getTheme();
        const htmlTheme = root.getAttribute('data-theme');

        // Se o HTML tem um tema definido (vindo do banco), usar esse
        if (htmlTheme && (htmlTheme === 'light' || htmlTheme === 'dark')) {
            localStorage.setItem(STORAGE_KEY, htmlTheme);
            updateThemeIcon(htmlTheme);
        } else {
            updateThemeIcon(initialTheme);
        }

        if (themeBtn) themeBtn.addEventListener('click', toggleTheme);

        // Listen for theme changes
        document.addEventListener(THEME_EVENT, (e) => {
            updateThemeIcon(e.detail.theme);
        });
    })();
</script>