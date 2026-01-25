<?php

use Application\Lib\Auth;

// ============================================================================
// VARIÁVEIS E CONFIGURAÇÕES
// ============================================================================

$username       = $username     ?? 'usuario';
$menu           = $menu         ?? '';
$base           = BASE_URL;
$favicon        = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1';
$pageTitle      = $pageTitle    ?? 'Lukrato';
$currentUser    = $currentUser  ?? Auth::user();
$isSysAdmin     = ($currentUser?->is_admin ?? 0) == 1;
$showUpgradeCTA = !($currentUser && method_exists($currentUser, 'isPro') && $currentUser->isPro());

// Helpers para menu ativo
$active = fn(string $key): string => (!empty($menu) && $menu === $key) ? 'active' : '';
$aria   = fn(string $key): string => (!empty($menu) && $menu === $key) ? ' aria-current="page"' : '';

// Obter tema do usuário do banco de dados
$userTheme = 'dark'; // valor padrão
if ($currentUser && isset($currentUser->theme_preference)) {
    $userTheme = in_array($currentUser->theme_preference, ['light', 'dark'])
        ? $currentUser->theme_preference
        : 'dark';
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- ============================================================================
         STYLES INTERNOS
         ============================================================================ -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/components.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-partials-header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/top-navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/gamification.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/gamification-page.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modal-contas-modern.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modal-lancamento.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/onboarding.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/plan-limits.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/tooltips.css?v=<?= time() ?>">

    <?php loadPageCss(); ?>

    <!-- Enhancements por último para sobrescrever tudo -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/enhancements.css?v=<?= time() ?>">

    <!-- ============================================================================
         SCRIPTS EXTERNOS
         ============================================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/csrf-manager.js"></script>
    <script src="<?= BASE_URL ?>assets/js/csrf-keep-alive.js" defer></script>
    <script src="<?= BASE_URL ?>assets/js/enhancements.js" defer></script>

    <!-- Sistema de Gerenciamento de Sessão -->
    <script src="<?= BASE_URL ?>assets/js/session-manager.js?v=<?= time() ?>" defer></script>

    <!-- Sistema de Gamificação Global (conquistas e level up) -->
    <script src="<?= BASE_URL ?>assets/js/gamification-global.js?v=<?= time() ?>"></script>

    <!-- Sistema de Limites do Plano (avisos de upgrade) -->
    <script src="<?= BASE_URL ?>assets/js/plan-limits.js?v=<?= time() ?>" defer></script>

    <!-- Sistema de Onboarding (carrega em todas as páginas) -->
    <script src="<?= BASE_URL ?>assets/js/onboarding.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/onboarding.js') ?>"
        defer></script>

    <!-- ============================================================================
         CONFIGURAÇÃO GLOBAL (Lukrato Namespace)
         ============================================================================ -->
    <script>
        // Suprimir erros do Bootstrap Modal GLOBALMENTE
        (function() {
            // Interceptar console.error para suprimir erros do Bootstrap
            const originalError = console.error;
            console.error = function(...args) {
                const message = args.join(' ');
                if (message.includes('backdrop') || message.includes('Cannot read properties of undefined')) {
                    return; // Silenciosamente ignorar
                }
                originalError.apply(console, args);
            };

            // Interceptar erros do JavaScript
            window.addEventListener('error', function(event) {
                if (event.message && (event.message.includes('backdrop') || event.message.includes(
                        'Cannot read properties of undefined'))) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return true;
                }
            }, true);

            // Interceptar erros não tratados
            window.onerror = function(message, source, lineno, colno, error) {
                if (message && (message.includes('backdrop') || message.includes(
                        'Cannot read properties of undefined'))) {
                    return true; // Suprimir erro
                }
                return false;
            };
        })();

        // Namespace global do Lukrato
        window.LK = window.LK || {};
        window.LK.csrfTtl = <?= (int) \Application\Middlewares\CsrfMiddleware::TOKEN_TTL ?>;

        // ========================================================================
        // HELPERS GLOBAIS
        // ========================================================================
        LK.getBase = () => {
            const meta = document.querySelector('meta[name="base-url"]');
            return (meta?.content || '/').replace(/\/?$/, '/');
        };

        // Definir window.BASE_URL para compatibilidade com scripts antigos
        window.BASE_URL = LK.getBase();

        LK.getCSRF = () => {
            return document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="_token"]')?.value || '';
        };

        LK.apiBase = () => LK.getBase() + 'api/';

        LK.initPageTransitions = () => {
            const overlay = document.getElementById('lkPageTransitionOverlay');
            if (!overlay) return;

            let isTransitioning = false;

            const cleanup = () => {
                isTransitioning = false;
                overlay.classList.remove('active');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('page-transitioning');
            };

            const startTransition = (target) => {
                if (isTransitioning) return;
                isTransitioning = true;
                document.body.classList.add('page-transitioning');
                overlay.classList.add('active');
                overlay.setAttribute('aria-hidden', 'false');

                setTimeout(() => {
                    window.location.href = target;
                }, 220);
            };

            const isSamePageAnchor = (href) => href?.startsWith('#');

            document.addEventListener('click', (event) => {
                if (event.defaultPrevented || event.button !== 0) return;
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

                const link = event.target.closest('a[href]');
                if (!link) return;
                if (link.target && link.target !== '_self') return;
                if (link.hasAttribute('download')) return;
                if (link.dataset.noTransition === 'true') return;

                const href = link.getAttribute('href');
                if (!href || isSamePageAnchor(href)) return;

                const url = new URL(link.href, window.location.href);
                if (url.origin !== window.location.origin) return;
                if (url.pathname === window.location.pathname && url.search === window.location.search) return;

                event.preventDefault();
                startTransition(url.href);
            });

            window.addEventListener('pageshow', cleanup);
            cleanup();
        };

        // ========================================================================
        // INICIALIZAÇÃO DOM
        // ========================================================================
        document.addEventListener('DOMContentLoaded', () => {
            // Header (sidebar active, logout confirm, seletor de contas)
            if (window.LK?.initHeader) {
                window.LK.initHeader();
            }

            // Sininho de notificações
            if (window.initNotificationsBell) {
                window.initNotificationsBell();
            }

            // Modais (abre/fecha via data-open-modal / data-close-modal)
            if (window.LK?.initModals) {
                window.LK.initModals();
            }

            if (window.LK?.initPageTransitions) {
                window.LK.initPageTransitions();
            }
        });
    </script>

    <!-- ============================================================================
         SCRIPTS DE PÁGINA
         ============================================================================ -->
    <?php loadPageJs('admin-home-header'); ?>
    <?php loadPageJs(); ?>
</head>

<body>
    <div id="lkPageTransitionOverlay" aria-hidden="true"></div>

    <!-- ============================================================================
         TOP NAVBAR
         ============================================================================ -->
    <?php include __DIR__ . '/top-navbar.php'; ?>

    <!-- ============================================================================
         SIDEBAR COLLAPSE STATE (Pre-render)
         ============================================================================ -->
    <script>
        (function() {
            try {
                const STORAGE_KEY = 'lk.sidebar';
                const prefersCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
                const isDesktop = window.matchMedia('(min-width: 993px)').matches;

                if (prefersCollapsed && isDesktop) {
                    document.body.classList.add('sidebar-collapsed');
                }
            } catch (err) {
                console.error('Erro ao restaurar estado da sidebar:', err);
            }
        })();
    </script>

    <!-- ============================================================================
         SIDEBAR NAVIGATION
         ============================================================================ -->
    <aside class="sidebar no-glass" id="sidebar-main">
        <!-- Logo -->
        <div class="sidebar-header">
            <a class="logo" href="<?= BASE_URL ?>dashboard" aria-label="Ir para o Dashboard">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
            </a>
            <button class="sidebar-close-btn" aria-label="Fechar menu" title="Fechar menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Menu de Navegação -->
        <nav class="sidebar-nav">
            <!-- Dashboard -->
            <a href="<?= BASE_URL ?>dashboard" class="nav-item <?= $active('dashboard') ?>" <?= $aria('dashboard') ?>
                title="Dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>

            <!-- Contas -->
            <a href="<?= BASE_URL ?>contas" class="nav-item <?= $active('contas') ?>" <?= $aria('contas') ?>
                title="Contas">
                <i class="fa fa-university" aria-hidden="true"></i>
                <span>Contas</span>
            </a>

            <!-- Cartões -->
            <a href="<?= BASE_URL ?>cartoes" class="nav-item <?= $active('cartoes') ?>" <?= $aria('cartoes') ?>
                title="Cartões de Crédito">
                <i class="fa-regular fa-credit-card"></i>
                <span>Cartões</span>
            </a>

            <!-- Faturas de Cartão -->
            <a href="<?= BASE_URL ?>faturas" class="nav-item <?= $active('faturas') ?>" <?= $aria('faturas') ?>
                title="Faturas de Cartão">
                <i class="fa-solid fa-file-invoice"></i>
                <span>Faturas</span>
            </a>

            <!-- Categorias -->
            <a href="<?= BASE_URL ?>categorias" class="nav-item <?= $active('categorias') ?>" <?= $aria('categorias') ?>
                title="Categorias">
                <i class="fas fa-tags"></i>
                <span>Categorias</span>
            </a>

            <!-- Lançamentos -->
            <a href="<?= BASE_URL ?>lancamentos" class="nav-item <?= $active('lancamentos') ?>"
                <?= $aria('lancamentos') ?> title="lançamentos">
                <i class="fa-solid fa-layer-group"></i>
                <span>lançamentos</span>
            </a>


            <!-- Relatórios -->
            <a href="<?= BASE_URL ?>relatorios" class="nav-item <?= $active('relatorios') ?>" <?= $aria('relatorios') ?>
                title="Relatórios">
                <i class="fa fa-pie-chart"></i>
                <span>Relatórios</span>
            </a>

            <!-- Agendamentos -->
            <a href="<?= BASE_URL ?>agendamentos" class="nav-item <?= $active('agendamentos') ?>"
                <?= $aria('agendamentos') ?> title="Agendamentos">
                <i class="fas fa-clock"></i>
                <span>Agendados</span>
            </a>

            <!-- Gamificação -->
            <a href="<?= BASE_URL ?>gamification" class="nav-item <?= $active('gamification') ?>"
                <?= $aria('gamification') ?> title="Gamificação">
                <i class="fas fa-trophy"></i>
                <span>Conquistas</span>
            </a>

            <!-- Investimentos -->
            <!-- <a href="<?= BASE_URL ?>investimentos" class="nav-item <?= $active('investimentos') ?>"
                <?= $aria('investimentos') ?> title="Investimentos">
                <i class="fa fa-line-chart" aria-hidden="true"></i>
                <span>Investimentos</span>
            </a> -->

            <!-- Perfil -->
            <a href="<?= BASE_URL ?>perfil" class="nav-item <?= $active('perfil') ?>" <?= $aria('perfil') ?>
                title="Perfil">
                <i class="fas fa-user-circle"></i>
                <span>Perfil</span>
            </a>
            <?php if ($isSysAdmin): ?>
                <a href="<?= BASE_URL ?>super_admin" class="nav-item <?= $active('super_admin') ?>"
                    <?= $aria('super_admin') ?> title="SysAdmin">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>SysAdmin</span>
                </a>
            <?php endif; ?>

            <!-- Sair -->
            <a id="btn-logout" class="nav-item" href="<?= BASE_URL ?>logout" title="Sair">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>

            <!-- CTA Upgrade Pro -->
            <?php if ($showUpgradeCTA): ?>
                <div class="sidebar-pro-cta">
                    <a href="<?= BASE_URL ?>billing" class="sidebar-pro-btn">
                        <i class="fa-solid fa-star"></i>
                        <span>Pro</span>
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- ================ BOTÃO TOGGLE SIDEBAR ======================== -->
    <button id="edgeMenuBtn" class="edge-menu-btn" aria-label="Expandir/Colapsar Menu" title="Expandir/Colapsar Menu">
        <i class="fas fa-angle-left"></i>
    </button>

    <!-- ================ BOTÕES ======================== -->

    <?php include __DIR__ . '/botao-lancamento_header.php'; ?>
    <?php include __DIR__ . '/botao_suporte.php'; ?>

    <!-- ==================== MODAIS ==================== -->
    <?php include __DIR__ . '/modals/modal_lancamento_global.php'; ?>
    <?php include __DIR__ . '/modals/modal_meses.php'; ?>
    <?php include __DIR__ . '/modals/aviso-lancamentos.php'; ?>

    <script>
        // Função global para abrir FAB menu
        function openLancamentoModalGlobal() {
            if (typeof lancamentoGlobalManager !== 'undefined') {
                lancamentoGlobalManager.openModal();
            }
        }
    </script>



    <!-- ============================================================================
         CONTENT WRAPPER
         ============================================================================ -->
    <div class="content-wrapper">
        <div id="lk-usage-banner-root"></div>
        <main class="lk-main">