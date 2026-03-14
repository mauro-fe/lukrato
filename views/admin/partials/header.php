<?php

// ============================================================================
// VARIÁVEIS (preparadas pelo BaseController::renderAdmin)
// ============================================================================

$username       = $username       ?? 'usuario';
$menu           = $menu           ?? '';
$base           = BASE_URL;
$favicon        = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1';
$pageTitle      = $pageTitle      ?? 'Lukrato';
$currentUser    = $currentUser    ?? null;
$isSysAdmin     = $isSysAdmin     ?? false;
$showUpgradeCTA = $showUpgradeCTA ?? true;
$userTheme      = $userTheme      ?? 'dark';

// Helpers para menu ativo
$active = fn(string $key): string => (!empty($menu) && $menu === $key) ? 'active' : '';
$aria   = fn(string $key): string => (!empty($menu) && $menu === $key) ? ' aria-current="page"' : '';

?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= htmlspecialchars($userTheme, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">

    <?= csrf_meta('default') ?>

    <!-- ============================================================================
         FAVICONS
         ============================================================================ -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">

    <!-- ============================================================================
         STYLES EXTERNOS
         ============================================================================ -->
    <!-- Lucide Icons (substitui FA) + FA Brands (para ícones de marca) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/vendor/lucide-compat.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>assets/js/lucide.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- ============================================================================
         STYLES INTERNOS
         ============================================================================ -->
    <!-- Fonts (self-hosted) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/core/fonts.css">

    <!-- Core -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/core/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/core/animations.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/core/_loading-states.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/core/components.css">

    <!-- Layout -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout/view-toggle-shared.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout/admin-partials-header.css?v=<?= time() ?>">
    <?php loadPageCss('admin-partials-footer'); ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout/top-navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout/breadcrumbs.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout/notifications-bell.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout/header-month-picker.css?v=<?= time() ?>">

    <!-- Modules -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/gamification.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/gamification-page.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/modal-contas-modern.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/modal-lancamento.css.php?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/modal-lancamento-mobile.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/plan-limits.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/tooltips.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/first-visit-tooltips.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/lukrato-feedback.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/modal-meses.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/aviso-lancamentos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/support-button.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/feedback-collector.css?v=<?= time() ?>">

    <!-- Page-specific CSS (auto-detected) -->
    <?php loadPageCss(); ?>

    <!-- Proteção contra internet lenta (timeout, retry, indicadores) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modules/lukrato-fetch.css?v=<?= time() ?>">

    <!-- Enhancements por último para sobrescrever tudo -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/core/enhancements.css?v=<?= time() ?>">

    <!-- ============================================================================
         SCRIPTS EXTERNOS
         ============================================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- ============================================================================
         GLOBAL INFRASTRUCTURE BUNDLE (Vite)
         csrf-manager, lukrato-fetch, lukrato-feedback, lukrato-ui, session-manager,
         gamification-global, plan-limits, enhancements, lucide-init, accessibility,
         first-visit-tooltips, tooltips, admin-home-header, birthday-modal,
         soft-ui-dashboard
         ============================================================================ -->
    <?= vite_scripts('admin/global/index.js') ?>

    <!-- ============================================================================
         BRIDGE PHP → JS (configuração global centralizada)
         ============================================================================ -->
    <script>
        window.__LK_CONFIG = {
            baseUrl: <?= json_encode(rtrim(BASE_URL, '/') . '/', JSON_UNESCAPED_SLASHES) ?>,
            csrfTtl: <?= (int) \Application\Middlewares\CsrfMiddleware::TOKEN_TTL ?>,
            isPro: <?= json_encode(!$showUpgradeCTA) ?>,
            isSysAdmin: <?= json_encode($isSysAdmin) ?>,
            userId: <?= json_encode($currentUser?->id ?? null) ?>,
            username: <?= json_encode($username) ?>,
            userAvatar: <?= json_encode($currentUser?->avatar ? rtrim(BASE_URL, '/') . '/' . $currentUser->avatar : '') ?>,
            userAvatarSettings: <?= json_encode([
                'position_x' => max(0, min(100, (int) ($currentUser?->avatar_focus_x ?? 50))),
                'position_y' => max(0, min(100, (int) ($currentUser?->avatar_focus_y ?? 50))),
                'zoom' => max(1, min(2, round((float) ($currentUser?->avatar_zoom ?? 1), 2))),
            ]) ?>
        };
    </script>

    <!-- ============================================================================
         CONFIGURAÇÃO GLOBAL (Lukrato Namespace) — via Vite global bundle
         ============================================================================ -->

    <!-- ============================================================================
         SCRIPTS DE PÁGINA
         ============================================================================ -->
    <?php loadPageJs(); ?>
</head>

<body<?php if (!empty($showMonthSelector)) echo ' class="has-month-bar"'; ?>>
    <div id="lkPageTransitionOverlay" aria-hidden="true"></div>

    <!-- ============================================================================
         TOP NAVBAR
         ============================================================================ -->
    <?php include __DIR__ . '/top-navbar.php'; ?>

    <!-- ============================================================================
         SIDEBAR NAVIGATION
         ============================================================================ -->
    <aside class="sidebar no-glass" id="sidebar-main">
        <!-- Logo -->
        <div class="sidebar-header">
            <a class="logo" href="<?= BASE_URL ?>dashboard" aria-label="Ir para o Dashboard">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato" class="logo-full">
                <img src="<?= BASE_URL ?>assets/img/logo-top.png" alt="Lukrato" class="logo-icon">
            </a>
            <button class="sidebar-close-btn" aria-label="Fechar menu" title="Fechar menu">
                <i data-lucide="x"></i>
            </button>
        </div>

        <!-- Menu de Navegação -->
        <nav class="sidebar-nav">

            <!-- Grupo: Principal -->
            <div class="sidebar-nav-group">
                <span class="sidebar-nav-label">Principal</span>
                <a href="<?= BASE_URL ?>dashboard" class="nav-item <?= $active('dashboard') ?>" <?= $aria('dashboard') ?>
                    title="Dashboard">
                    <i data-lucide="home"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <!-- Grupo: Financeiro -->
            <div class="sidebar-nav-group">
                <span class="sidebar-nav-label">Financeiro</span>
                <a href="<?= BASE_URL ?>contas" class="nav-item <?= $active('contas') ?>" <?= $aria('contas') ?>
                    title="Contas">
                    <i data-lucide="landmark"></i>
                    <span>Contas</span>
                </a>
                <a href="<?= BASE_URL ?>cartoes" class="nav-item <?= $active('cartoes') ?>" <?= $aria('cartoes') ?>
                    title="Cartões de Crédito">
                    <i data-lucide="credit-card"></i>
                    <span>Cartões</span>
                </a>
                <a href="<?= BASE_URL ?>faturas" class="nav-item <?= $active('faturas') ?>" <?= $aria('faturas') ?>
                    title="Faturas de Cartão">
                    <i data-lucide="file-text"></i>
                    <span>Faturas</span>
                </a>
                <a href="<?= BASE_URL ?>lancamentos" class="nav-item <?= $active('lancamentos') ?>"
                    <?= $aria('lancamentos') ?> title="Lançamentos">
                    <i data-lucide="layers"></i>
                    <span>Lançamentos</span>
                </a>
            </div>

            <!-- Grupo: Gestão -->
            <div class="sidebar-nav-group">
                <span class="sidebar-nav-label">Gestão</span>
                <a href="<?= BASE_URL ?>categorias" class="nav-item <?= $active('categorias') ?>" <?= $aria('categorias') ?>
                    title="Categorias">
                    <i data-lucide="tags"></i>
                    <span>Categorias</span>
                </a>
                <a href="<?= BASE_URL ?>relatorios" class="nav-item <?= $active('relatorios') ?>" <?= $aria('relatorios') ?>
                    title="Relatórios">
                    <i data-lucide="pie-chart"></i>
                    <span>Relatórios</span>
                </a>
                <a href="<?= BASE_URL ?>financas" class="nav-item <?= $active('financas') ?>" <?= $aria('financas') ?>
                    title="Finanças">
                    <i data-lucide="wallet"></i>
                    <span>Finanças</span>
                </a>
            </div>

            <!-- Grupo: Extras -->
            <div class="sidebar-nav-group">
                <span class="sidebar-nav-label">Extras</span>
                <a href="<?= BASE_URL ?>gamification" class="nav-item <?= $active('gamification') ?>"
                    <?= $aria('gamification') ?> title="Gamificação">
                    <i data-lucide="trophy"></i>
                    <span>Conquistas</span>
                </a>
            </div>

        </nav>

        <!-- Rodapé da Sidebar -->
        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>perfil" class="nav-item <?= $active('perfil') ?>" <?= $aria('perfil') ?>
                title="Perfil">
                <div class="sidebar-avatar" id="sidebarAvatar">
                    <span class="avatar-initials-xs"><?= mb_substr($topNavFirstName ?? $username ?? 'U', 0, 1) ?></span>
                </div>
                <span>Perfil</span>
            </a>
            <?php if ($isSysAdmin): ?>
                <a href="<?= BASE_URL ?>super_admin" class="nav-item <?= $active('super_admin') ?>"
                    <?= $aria('super_admin') ?> title="SysAdmin">
                    <i data-lucide="shield-check"></i>
                    <span>SysAdmin</span>
                </a>
            <?php endif; ?>
            <a href="#" class="nav-item" id="sidebarSuggestionBtn" title="Enviar sugestão">
                <i data-lucide="message-circle"></i>
                <span>Sugestão</span>
            </a>
            <a id="btn-logout" class="nav-item" href="<?= BASE_URL ?>logout" title="Sair">
                <i data-lucide="log-out"></i>
                <span>Sair</span>
            </a>
            <?php if ($showUpgradeCTA): ?>
                <div class="sidebar-pro-cta">
                    <a href="<?= BASE_URL ?>billing" class="sidebar-pro-btn">
                        <i data-lucide="star"></i>
                        <span>Pro</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ================ BOTÃO TOGGLE SIDEBAR ======================== -->
    <button id="edgeMenuBtn" class="edge-menu-btn" aria-label="Expandir/Colapsar Menu" title="Expandir/Colapsar Menu">
        <i data-lucide="chevron-left"></i>
    </button>

    <!-- ================ BOTÕES ======================== -->

    <?php include __DIR__ . '/botao-lancamento.php'; ?>
    <?php include __DIR__ . '/botao-suporte.php'; ?>

    <!-- ==================== MODAIS ==================== -->
    <?php include __DIR__ . '/modals/modal-lancamento-global.php'; ?>
    <?php include __DIR__ . '/modals/modal-meses.php'; ?>
    <!-- aviso-lancamentos: funcionalidade migrada para JS (Vite bundle) -->

    <!-- ============================================================================
         CONTENT WRAPPER
         ============================================================================ -->
    <div class="content-wrapper">
        <div id="lk-usage-banner-root"></div>
        <main class="lk-main">
