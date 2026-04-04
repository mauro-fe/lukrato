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
$isPro          = $isPro          ?? (!$showUpgradeCTA);
$userTheme      = $userTheme      ?? 'dark';
$currentViewPath = trim((string) ($currentViewPath ?? ''), '/');
$currentViewId = trim((string) ($currentViewId ?? ''), '-');
if ($currentViewId === '' && $currentViewPath !== '') {
    $currentViewId = trim(str_replace('/', '-', $currentViewPath), '-');
}
$currentPageJsViewId = trim((string) ($currentPageJsViewId ?? ''));
if ($currentPageJsViewId === '' && $currentViewId !== '') {
    $currentPageJsViewId = \Application\Support\Admin\AdminModuleRegistry::resolvePageJsViewId(
        $currentViewId,
        [
            'menu' => is_string($menu ?? null) ? $menu : null,
        ]
    ) ?? $currentViewId;
}
$resolvedBundle = [
    'pageJsViewId' => $currentPageJsViewId,
    'viteEntry' => $currentPageJsViewId !== ''
        ? \Application\Support\Admin\AdminModuleRegistry::resolveViteEntryByViewId($currentPageJsViewId)
        : null,
    'cssEntry' => $currentViewId !== ''
        ? \Application\Support\Admin\AdminModuleRegistry::resolveCssEntryByViewId($currentViewId)
        : null,
];
$bundle = is_array($bundle ?? null) ? array_replace($resolvedBundle, $bundle) : $resolvedBundle;
$dashboardPreferences = is_array($currentUser?->dashboard_preferences ?? null)
    ? $currentUser->dashboard_preferences
    : [];
$rawHelpCenterPreferences = is_array($dashboardPreferences['help_center'] ?? null)
    ? $dashboardPreferences['help_center']
    : [];
$rawHelpSettings = is_array($rawHelpCenterPreferences['settings'] ?? null)
    ? $rawHelpCenterPreferences['settings']
    : [];
$helpCenterPreferences = [
    'settings' => [
        'auto_offer' => array_key_exists('auto_offer', $rawHelpSettings)
            ? (bool) $rawHelpSettings['auto_offer']
            : true,
    ],
    'tour_completed' => is_array($rawHelpCenterPreferences['tour_completed'] ?? null)
        ? $rawHelpCenterPreferences['tour_completed']
        : [],
    'offer_dismissed' => is_array($rawHelpCenterPreferences['offer_dismissed'] ?? null)
        ? $rawHelpCenterPreferences['offer_dismissed']
        : [],
    'tips_seen' => is_array($rawHelpCenterPreferences['tips_seen'] ?? null)
        ? $rawHelpCenterPreferences['tips_seen']
        : [],
];

// Helpers para menu ativo
$active = fn(string $key): string => (!empty($menu) && $menu === $key) ? 'active' : '';
$aria   = fn(string $key): string => (!empty($menu) && $menu === $key) ? ' aria-current="page"' : '';
$routeUrl = static fn(string $route): string => rtrim(BASE_URL, '/') . '/' . ltrim($route, '/');
$sidebarGroups = is_array($sidebarModules ?? null)
    ? $sidebarModules
    : \Application\Support\Admin\AdminModuleRegistry::groupedSidebarModules($isSysAdmin, $isPro);
$footerModules = is_array($footerModules ?? null)
    ? $footerModules
    : \Application\Support\Admin\AdminModuleRegistry::footerModules($isSysAdmin, $isPro);

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
    <script src="<?= BASE_URL ?>assets/js/lucide.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- ============================================================================
         STYLES INTERNOS
         ============================================================================ -->
    <?= function_exists('vite_css') ? vite_css('admin-base') : '' ?>



    <!-- Page-specific CSS (auto-detected) -->
    <?php loadPageCss($currentViewId); ?>

    <!-- Proteção contra internet lenta (timeout, retry, indicadores) -->

    <!-- Enhancements por último para sobrescrever tudo -->

    <!-- ============================================================================
         SIDEBAR PRE-RENDER (inline, antes de qualquer script externo)
         Aplica sidebar-collapsed + desabilita transition no primeiro paint
         ============================================================================ -->
    <script>
        (function() {
            try {
                var h = document.documentElement;
                // 1) Bloquear transitions no first paint
                h.classList.add('sidebar-no-transition');
                // 2) Guardar estado para aplicar no body logo que existir
                window.__lkSidebarCollapsed = (localStorage.getItem('lk.sidebar') === '1' && window.matchMedia(
                    '(min-width:993px)').matches);
                // 3) Liberar transitions após o primeiro frame
                window.addEventListener('load', function() {
                    requestAnimationFrame(function() {
                        requestAnimationFrame(function() {
                            h.classList.remove('sidebar-no-transition');
                        });
                    });
                });
            } catch (e) {}
        })();
    </script>

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
            isPro: <?= json_encode($isPro) ?>,
            isSysAdmin: <?= json_encode($isSysAdmin) ?>,
            userId: <?= json_encode($currentUser?->id ?? null) ?>,
            username: <?= json_encode($username) ?>,
            userEmail: <?= json_encode($currentUser?->email ?? '') ?>,
            currentMenu: <?= json_encode($menu ?: 'dashboard') ?>,
            currentViewId: <?= json_encode($currentViewId) ?>,
            currentViewPath: <?= json_encode($currentViewPath, JSON_UNESCAPED_SLASHES) ?>,
            bundle: <?= json_encode($bundle, JSON_UNESCAPED_SLASHES) ?>,
            needsDisplayNamePrompt: <?= json_encode(trim((string) ($currentUser?->nome ?? '')) === '') ?>,
            tourCompleted: <?= json_encode(!empty($currentUser?->tour_completed_at)) ?>,
            helpCenter: <?= json_encode($helpCenterPreferences, JSON_UNESCAPED_SLASHES) ?>,
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
         CONTEXTO DE PAGINA
         ============================================================================ -->
</head>

<body<?php if (!empty($showMonthSelector)) echo ' class="has-month-bar"'; ?>>
    <script>
        if (window.__lkSidebarCollapsed) document.body.classList.add('sidebar-collapsed');
    </script>
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
            <?php foreach ($sidebarGroups as $groupLabel => $modules): ?>
                <div class="sidebar-nav-group">
                    <span
                        class="sidebar-nav-label"><?= htmlspecialchars((string) $groupLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php foreach ($modules as $module): ?>
                        <?php
                        $moduleMenu = (string) ($module['menu'] ?? '');
                        $moduleLabel = (string) ($module['label'] ?? '');
                        $moduleTitle = (string) ($module['title'] ?? $moduleLabel);
                        $moduleRoute = (string) ($module['route'] ?? '');
                        $moduleIcon = (string) ($module['icon'] ?? 'circle');
                        ?>
                        <a href="<?= htmlspecialchars($routeUrl($moduleRoute), ENT_QUOTES, 'UTF-8') ?>"
                            class="nav-item <?= $moduleMenu !== '' ? $active($moduleMenu) : '' ?>"
                            <?= $moduleMenu !== '' ? $aria($moduleMenu) : '' ?>
                            title="<?= htmlspecialchars($moduleTitle, ENT_QUOTES, 'UTF-8') ?>">
                            <i data-lucide="<?= htmlspecialchars($moduleIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                            <span class="nav-item-content">
                                <span class="nav-item-title"><?= htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <!-- Rodapé da Sidebar -->
        <div class="sidebar-footer">
            <?php foreach ($footerModules as $module): ?>
                <?php
                $moduleKey = (string) ($module['key'] ?? '');
                $moduleMenu = (string) ($module['menu'] ?? '');
                $moduleLabel = (string) ($module['label'] ?? '');
                $moduleTitle = (string) ($module['title'] ?? $moduleLabel);
                $moduleRoute = (string) ($module['route'] ?? '');
                $moduleIcon = (string) ($module['icon'] ?? 'circle');
                ?>
                <a href="<?= htmlspecialchars($routeUrl($moduleRoute), ENT_QUOTES, 'UTF-8') ?>"
                    class="nav-item <?= $moduleMenu !== '' ? $active($moduleMenu) : '' ?>"
                    <?= $moduleMenu !== '' ? $aria($moduleMenu) : '' ?>
                    title="<?= htmlspecialchars($moduleTitle, ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($moduleKey === 'perfil'): ?>
                        <div class="sidebar-avatar" id="sidebarAvatar">
                            <span class="avatar-initials-xs"><?= mb_substr($topNavFirstName ?? $username ?? 'U', 0, 1) ?></span>
                        </div>
                    <?php else: ?>
                        <i data-lucide="<?= htmlspecialchars($moduleIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                    <?php endif; ?>
                    <span class="nav-item-content">
                        <span class="nav-item-title"><?= htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
            <a href="#" class="nav-item" id="sidebarSuggestionBtn" title="Enviar sugestão">
                <i data-lucide="message-circle"></i>
                <span class="nav-item-content">
                    <span class="nav-item-title">Sugestão</span>
                    <small class="nav-item-subtitle">Envie seu feedback</small>
                </span>
            </a>
            <a id="btn-logout" class="nav-item" href="<?= BASE_URL ?>logout" title="Sair">
                <i data-lucide="log-out"></i>
                <span class="nav-item-content">
                    <span class="nav-item-title">Sair</span>
                    <small class="nav-item-subtitle">Encerrar sessão</small>
                </span>
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
        <div id="lk-demo-banner-root" hidden></div>
        <div id="lk-usage-banner-root"></div>
        <main class="lk-main">
            <div id="lk-page-shell" class="lk-page-shell" data-page-loading-state="idle" aria-busy="false">
                <div id="lk-page-loader" class="lk-page-loader" hidden aria-hidden="true" aria-live="polite">
                    <div class="lk-page-loader__card">
                        <div class="lk-page-loader__logo-wrap" aria-hidden="true">
                            <span class="lk-page-loader__ring"></span>
                            <img src="<?= BASE_URL ?>assets/img/icone.png" alt="" class="lk-page-loader__logo">
                        </div>
                        <p id="lk-page-loader-title" class="lk-page-loader__title">Carregando...</p>
                        <p id="lk-page-loader-subtitle" class="lk-page-loader__subtitle">Preparando seus dados</p>
                    </div>
                </div>
                <div id="lk-page-content" class="lk-page-content">
