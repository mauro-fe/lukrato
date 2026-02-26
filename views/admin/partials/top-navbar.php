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

// Definir breadcrumbs baseado no menu atual
$breadcrumbsMap = [
    'dashboard'    => [],
    'contas'       => [['label' => 'Finanças', 'icon' => 'wallet']],
    'cartoes'      => [['label' => 'Finanças', 'icon' => 'wallet']],
    'faturas'      => [['label' => 'Finanças', 'icon' => 'wallet'], ['label' => 'Cartões', 'url' => 'cartoes', 'icon' => 'credit-card']],
    'categorias'   => [['label' => 'Organização', 'icon' => 'folder']],
    'lancamentos'  => [['label' => 'Finanças', 'icon' => 'wallet']],
    'relatorios'   => [['label' => 'Análises', 'icon' => 'bar-chart-3']],
    'agendamentos' => [['label' => 'Automações', 'icon' => 'clock']],
    'gamification' => [['label' => 'Perfil', 'icon' => 'user']],
    'perfil'       => [],
    'billing'      => [['label' => 'Perfil', 'icon' => 'user']],
];
$currentBreadcrumbs = $breadcrumbsMap[$menu ?? ''] ?? [];
?>

<div class="top-navbar">
    <div class="top-navbar-container">
        <!-- Menu Button (Mobile Only) -->
        <button id="mobileMenuBtn" class="top-navbar-menu-btn" aria-label="Abrir/fechar menu" aria-expanded="false"
            title="Menu">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

        <!-- Page Title / Breadcrumb -->
        <div class="top-navbar-title">
            <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
            <?php if (!empty($currentBreadcrumbs) || ($menu ?? '') !== 'dashboard'): ?>
                <nav class="lk-breadcrumbs-wrapper" aria-label="Navegação">
                    <ol class="lk-breadcrumbs">
                        <li class="lk-breadcrumb-item">
                            <a href="<?= BASE_URL ?>dashboard" title="Início">
                                <i data-lucide="home" class="lk-breadcrumb-home" style="color: var(--color-primary)"></i>
                            </a>
                        </li>
                        <?php foreach ($currentBreadcrumbs as $crumb): ?>
                            <li class="lk-breadcrumb-separator"><i data-lucide="chevron-right" class="icon-xs"></i></li>
                            <li class="lk-breadcrumb-item">
                                <?php if (!empty($crumb['url'])): ?>
                                    <a href="<?= BASE_URL . $crumb['url'] ?>">
                                        <?php if (!empty($crumb['icon'])): ?><i data-lucide="<?= $crumb['icon'] ?>"
                                                style="color: var(--color-primary)"></i><?php endif; ?>
                                        <?= htmlspecialchars($crumb['label']) ?>
                                    </a>
                                <?php else: ?>
                                    <span>
                                        <?php if (!empty($crumb['icon'])): ?><i data-lucide="<?= $crumb['icon'] ?>"
                                                style="color: var(--color-primary)"></i><?php endif; ?>
                                        <?= htmlspecialchars($crumb['label']) ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <li class="lk-breadcrumb-separator"><i data-lucide="chevron-right" class="icon-xs"></i></li>
                        <li class="lk-breadcrumb-item current"><?= $pageTitle ?? 'Dashboard' ?></li>
                    </ol>
                </nav>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="top-navbar-actions">
            <!-- User Info -->
            <div class="user-info">
                <span class="greeting">Olá, <a href="<?= BASE_URL ?>perfil" class="greeting-name"
                        title="Ir para o perfil"><strong><?= $topNavFirstName ?: 'usuário' ?></strong></a></span>
                <a href="<?= BASE_URL ?>billing" class="plan-badge <?= $isPro ? 'pro' : 'free' ?>"
                    title="Gerenciar assinatura">
                    <i data-lucide="<?= $isPro ? 'crown' : 'leaf' ?>"></i>
                    <?= $planLabel ?>
                </a>
            </div>

            <!-- Upgrade Button (if not pro) -->
            <?php if (!$isPro): ?>
                <a href="<?= BASE_URL ?>billing" class="top-nav-btn upgrade-btn" title="Fazer upgrade para Pro">
                    <i data-lucide="crown"></i>
                    <span class="btn-text">Upgrade</span>
                </a>
            <?php endif; ?>

            <!-- Theme Toggle -->
            <button id="topNavThemeToggle" type="button" class="top-nav-btn theme-toggle" aria-label="Alternar tema"
                title="Modo claro/escuro">
                <i data-lucide="sun"></i>
                <i data-lucide="moon"></i>
            </button>

            <!-- Notifications -->
            <div class="top-nav-bell-wrapper">
                <?php include __DIR__ . '/notificacoes/bell.php'; ?>
            </div>

            <!-- Logout Button (Desktop Only) -->
            <a href="<?= BASE_URL ?>logout" class="top-nav-btn logout-btn desktop-only" title="Sair">
                <i data-lucide="log-out"></i>
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
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                if (!csrfToken) {
                    console.warn('[Theme] CSRF token não encontrado');
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
                } else {
                    console.warn('[Theme] Falha ao salvar tema:', response.status);
                }
            } catch (error) {
                console.warn('[Theme] Erro ao salvar tema:', error);
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