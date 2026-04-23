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
$hideLaunchFab  = $hideLaunchFab  ?? false;
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

    <style>
        html.lk-preboot,
        html.lk-preboot body {
            overflow: hidden;
            background: #0b1220;
        }

        .lk-preboot-overlay {
            position: fixed;
            inset: 0;
            z-index: 2147483000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background:
                radial-gradient(1200px 420px at 50% 0%, rgba(249, 115, 22, 0.16), transparent 60%),
                linear-gradient(180deg, rgba(11, 18, 32, 0.98), rgba(15, 23, 42, 0.98));
            opacity: 1;
            visibility: visible;
            transition: opacity 0.22s ease, visibility 0.22s ease;
        }

        html:not(.lk-preboot) .lk-preboot-overlay,
        .lk-preboot-overlay.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .lk-preboot-card {
            position: relative;
            overflow: hidden;
            width: min(360px, calc(100vw - 2rem));
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.85rem;
            padding: 1.55rem 1.6rem 1.45rem;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(18, 32, 48, 0.94), rgba(9, 23, 37, 0.96));
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 26px 60px rgba(0, 0, 0, 0.42), inset 0 1px 0 rgba(255, 255, 255, 0.06);
            text-align: center;
        }

        .lk-preboot-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 3px;
            background: linear-gradient(90deg, #f97316, rgba(249, 115, 22, 0.18), transparent);
        }

        .lk-preboot-spinner {
            position: relative;
            width: 78px;
            height: 78px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(249, 115, 22, 0.08);
            box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.14), 0 14px 34px rgba(249, 115, 22, 0.12);
        }

        .lk-preboot-spinner::before {
            content: "";
            position: absolute;
            inset: 10px;
            border-radius: inherit;
            border: 1px solid rgba(249, 115, 22, 0.2);
        }

        .lk-preboot-ring {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            border: 2px solid rgba(249, 115, 22, 0.16);
            border-top-color: #f97316;
            border-right-color: rgba(249, 115, 22, 0.48);
        }

        .lk-preboot-orbit {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            transform-origin: 50% 50%;
            animation: lk-preboot-spin 0.95s linear infinite;
            will-change: transform;
        }

        .lk-preboot-orbit-dot {
            content: "";
            position: absolute;
            top: -4px;
            left: 50%;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #f97316;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.16), 0 0 18px rgba(249, 115, 22, 0.55);
            transform: translateX(-50%);
        }

        .lk-preboot-logo {
            position: relative;
            z-index: 1;
            width: 40px;
            height: 40px;
            object-fit: contain;
            filter: drop-shadow(0 6px 14px rgba(249, 115, 22, 0.24));
        }

        .lk-preboot-title {
            margin: 0;
            color: #f8fafc;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0;
        }

        .lk-preboot-subtitle {
            margin: 0;
            color: rgba(226, 232, 240, 0.78);
            font-size: 0.86rem;
            line-height: 1.45;
        }

        @keyframes lk-preboot-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .lk-preboot-overlay {
                transition: none;
            }
        }
    </style>

    <script>
        (function() {
            const root = document.documentElement;
            root.classList.add('lk-preboot');

            let released = false;

            window.__LK_RELEASE_PREBOOT__ = function() {
                if (released) {
                    return;
                }

                released = true;
                root.classList.remove('lk-preboot');

                const overlay = document.getElementById('lkPrebootOverlay');
                if (!overlay) {
                    return;
                }

                overlay.classList.add('is-hidden');
                window.setTimeout(function() {
                    overlay.remove();
                }, 260);
            };

            window.addEventListener('load', function() {
                window.setTimeout(function() {
                    if (typeof window.__LK_RELEASE_PREBOOT__ === 'function') {
                        window.__LK_RELEASE_PREBOOT__();
                    }
                }, 180);
            }, {
                once: true
            });
        })();
    </script>

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
         CONTEXTO DE PAGINA
         ============================================================================ -->
</head>

<body data-lk-menu="<?= htmlspecialchars((string) $menu, ENT_QUOTES, 'UTF-8') ?>"
    data-lk-view-id="<?= htmlspecialchars((string) $currentViewId, ENT_QUOTES, 'UTF-8') ?>"
    data-lk-view-path="<?= htmlspecialchars((string) $currentViewPath, ENT_QUOTES, 'UTF-8') ?>"
    <?php if (!empty($showMonthSelector)) echo ' class="has-month-bar"'; ?>>
    <div class="lk-preboot-overlay" id="lkPrebootOverlay" aria-hidden="true">
        <div class="lk-preboot-card" role="status" aria-live="polite">
            <div class="lk-preboot-spinner" aria-hidden="true">
                <span class="lk-preboot-ring"></span>
                <span class="lk-preboot-orbit">
                    <span class="lk-preboot-orbit-dot"></span>
                </span>
                <img src="<?= BASE_URL ?>assets/img/icone.png" alt="" class="lk-preboot-logo">
            </div>
            <p class="lk-preboot-title">Carregando...</p>
            <p class="lk-preboot-subtitle">Preparando conteúdo</p>
        </div>
    </div>
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
    <aside class="sidebar surface-card" id="sidebar-main">
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

    <?php if (!$hideLaunchFab): ?>
        <?php include __DIR__ . '/botao-lancamento.php'; ?>
    <?php endif; ?>
    <?php include __DIR__ . '/botao-suporte.php'; ?>

    <div id="lk-app-modal-root" class="lk-modal-root lk-modal-root--app" aria-hidden="true"></div>

    <!-- ==================== MODAIS ==================== -->
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
                <div id="lk-page-modal-root" class="lk-modal-root lk-modal-root--page" aria-hidden="true"></div>
                <div id="lk-page-loader" class="lk-page-loader" hidden aria-hidden="true" aria-live="polite">
                    <div class="lk-page-loader__card">
                        <div class="lk-page-loader__logo-wrap" aria-hidden="true">
                            <span class="lk-page-loader__ring"></span>
                            <span class="lk-page-loader__orbit">
                                <span class="lk-page-loader__orbit-dot"></span>
                            </span>
                            <img src="<?= BASE_URL ?>assets/img/icone.png" alt="" class="lk-page-loader__logo">
                        </div>
                        <p id="lk-page-loader-title" class="lk-page-loader__title">Carregando...</p>
                        <p id="lk-page-loader-subtitle" class="lk-page-loader__subtitle">Preparando seus dados</p>
                    </div>
                </div>
                <div id="lk-page-content" class="lk-page-content">
