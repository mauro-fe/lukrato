<?php
// CSS: public/assets/css/layout/top-navbar.css (carregado via header.php)
// JS:  resources/js/admin/global/theme-toggle.js (carregado via Vite bundle)
// Variáveis: $topNavFirstName, $isPro, $planLabel, $currentBreadcrumbs (via BaseController::renderAdmin)

$topNavFirstName   = $topNavFirstName   ?? '';
$isPro             = $isPro             ?? false;
$planLabel         = $planLabel         ?? 'FREE';
$currentBreadcrumbs = $currentBreadcrumbs ?? [];
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
                                <i data-lucide="home" class="lk-breadcrumb-home lk-breadcrumb-icon"></i>
                            </a>
                        </li>
                        <?php foreach ($currentBreadcrumbs as $crumb): ?>
                            <li class="lk-breadcrumb-separator"><i data-lucide="chevron-right" class="icon-xs"></i></li>
                            <li class="lk-breadcrumb-item">
                                <?php if (!empty($crumb['url'])): ?>
                                    <a href="<?= BASE_URL . $crumb['url'] ?>">
                                        <?php if (!empty($crumb['icon'])): ?><i data-lucide="<?= $crumb['icon'] ?>"
                                                class="lk-breadcrumb-icon"></i><?php endif; ?>
                                        <?= htmlspecialchars($crumb['label']) ?>
                                    </a>
                                <?php else: ?>
                                    <span>
                                        <?php if (!empty($crumb['icon'])): ?><i data-lucide="<?= $crumb['icon'] ?>"
                                                class="lk-breadcrumb-icon"></i><?php endif; ?>
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

        <!-- Month Selector (conditional) -->
        <?php if (!empty($showMonthSelector)): ?>
            <div class="top-navbar-month">
                <?php include __DIR__ . '/header-mes.php'; ?>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="top-navbar-actions">
            <!-- User Info -->
            <div class="user-info">
                <a href="<?= BASE_URL ?>perfil" class="top-nav-avatar" id="topNavAvatar" title="Ir para o perfil">
                    <span class="avatar-initials-sm"><?= mb_substr($topNavFirstName ?: 'U', 0, 1) ?></span>
                </a>
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