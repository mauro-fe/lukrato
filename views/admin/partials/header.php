<?php

use Application\Lib\Auth;

// ============================================================================
// VARIÁVEIS E CONFIGURAÇÕES
// ============================================================================

$username       = $username     ?? 'usuario';
$menu           = $menu         ?? '';
$base           = BASE_URL;
$favicon        = rtrim(BASE_URL, '/') . '/assets/img/logo.png?v=1';
$pageTitle      = $pageTitle    ?? 'Lukrato';
$currentUser    = $currentUser  ?? Auth::user();
$isSysAdmin     = ($currentUser?->is_admin ?? 0) == 1;
$showUpgradeCTA = !($currentUser && method_exists($currentUser, 'isPro') && $currentUser->isPro());

// Helpers para menu ativo
$active = fn(string $key): string => (!empty($menu) && $menu === $key) ? 'active' : '';
$aria   = fn(string $key): string => (!empty($menu) && $menu === $key) ? ' aria-current="page"' : '';

?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">

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
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main-styles.css">

    <?php loadPageCss(); ?>
    <?php loadPageCss('admin-partials-header'); ?>

    <!-- ============================================================================
         SCRIPTS EXTERNOS
         ============================================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/csrf-keep-alive.js" defer></script>

    <!-- ============================================================================
         CONFIGURAÇÃO GLOBAL (Lukrato Namespace)
         ============================================================================ -->
    <script>
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
    <?php loadPageJs('admin-agendamentos-index'); ?>
    <?php loadPageJs(); ?>
</head>

<body>
    <div id="lkPageTransitionOverlay" aria-hidden="true"></div>
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
         BOTÃO TOGGLE SIDEBAR (Edge Button)
         ============================================================================ -->
    <button id="edgeMenuBtn" class="edge-menu-btn" aria-label="Abrir/fechar menu" aria-expanded="false"
        title="Fechar/Abrir menu">
        <i class="fa fa-angle-left" aria-hidden="true"></i>
    </button>

    <!-- ============================================================================
         SIDEBAR NAVIGATION
         ============================================================================ -->
    <aside class="sidebar no-glass" id="sidebar-main">
        <!-- Logo -->
        <div class="sidebar-header">
            <a class="logo" href="<?= BASE_URL ?>/dashboard" aria-label="Ir para o Dashboard">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
            </a>
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

            <!-- Lançamentos -->
            <a href="<?= BASE_URL ?>lancamentos" class="nav-item <?= $active('lancamentos') ?>"
                <?= $aria('lancamentos') ?> title="Lançamentos">
                <i class="fas fa-exchange-alt"></i>
                <span>Lançamentos</span>
            </a>

            <!-- Categorias -->
            <a href="<?= BASE_URL ?>categorias" class="nav-item <?= $active('categorias') ?>" <?= $aria('categorias') ?>
                title="Categorias">
                <i class="fas fa-tags"></i>
                <span>Categorias</span>
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

    <!-- ============================================================================
         MODAIS
         ============================================================================ -->
    <?php include __DIR__ . '/modals/modal_lancamento.php'; ?>
    <?php include __DIR__ . '/modals/modal_agendamento.php'; ?>
    <?php include __DIR__ . '/modals/modal_meses.php'; ?>
    <?php include __DIR__ . '/modals/aviso-lancamentos.php'; ?>
    <?php include __DIR__ . '/botao-lancamento_header.php'; ?>
    <?php include __DIR__ . '/botao_suporte.php'; ?>



    <!-- ============================================================================
         CONTENT WRAPPER
         ============================================================================ -->
    <div class="content-wrapper">
        <main class="lk-main">
            <?php include __DIR__ . '/navbar.php'; ?>