<?php

$username  = $username  ?? 'usuario';
$menu      = $menu      ?? '';
$allowedMenus = ['dashboard', 'contas', 'lancamentos', 'relatorios', 'categorias', 'perfil'];
$u    = 'admin';
$base = BASE_URL;

?>
<?php

use Application\Middlewares\CsrfMiddleware;

$csrfToken = CsrfMiddleware::generateToken('default'); // MESMO ID do handle()
?>
<meta name="csrf" content="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
<script>
    window.CSRF = document.querySelector('meta[name="csrf"]')?.content || '';
</script>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark" class="sidebar-collapsed">

<head>
    <?= csrf_meta('default') ?>
    <script>
        // helpers globais
        window.LK = window.LK || {};
        LK.getBase = () => (document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');
        LK.getCSRF = () =>
            document.querySelector('meta[name="csrf-token"]')?.content || // do csrf_meta
            document.querySelector('input[name="_token"]')?.value || '';
        LK.apiBase = LK.getBase() + 'api/';
    </script>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">
    <?php $favicon = rtrim(BASE_URL, '/') . '/assets/img/logo.png?v=1'; ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?= $favicon ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">

    <?php loadPageCss(); ?>
    <?php loadPageCss('admin-partials-header'); ?>

    <?php loadPageJs('admin-home-header'); ?>
    <?php loadPageJs(); ?>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>

<body class="g-sidenav-show bg-gray-100">
    <?php
    $active = function (string $key) use ($menu) {
        return (!empty($menu) && $menu === $key) ? 'active' : '';
    };
    $aria   = function (string $key) use ($menu) {
        return (!empty($menu) && $menu === $key) ? ' aria-current="page"' : '';
    };

    ?>
    <aside class="sidebar no-glass collapsed" id="sidebar-main">
        <div class="sidebar-header">
            <a class="logo" href="<?= BASE_URL ?>/dashboard" aria-label="Ir para o Dashboard">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
            </a>
        </div>
        <nav class="sidebar-nav">
            <button id="edgeMenuBtn" class="edge-menu-btn" aria-label="Abrir/fechar menu" aria-expanded="false"
                title="Fechar/Abrir menu">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
            <a href="<?= BASE_URL ?>dashboard" class="nav-item <?= $active('dashboard')   ?>"
                <?= $aria('dashboard')   ?> title="Dashboard"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="<?= BASE_URL ?>contas" class="nav-item <?= $active('contas')      ?>" <?= $aria('contas')      ?>
                title="Contas"><i class="fa fa-university" aria-hidden="true"></i><span>Contas</span></a>
            <a href="<?= BASE_URL ?>lancamentos" class="nav-item <?= $active('lancamentos') ?>"
                <?= $aria('lancamentos') ?> title="Lançamentos"><i
                    class="fas fa-exchange-alt"></i><span>Lançamentos</span></a>
            <a href="<?= BASE_URL ?>categorias" class="nav-item <?= $active('categorias')  ?>"
                <?= $aria('categorias')  ?> title="Categorias"><i class="fas fa-tags"></i><span>Categorias</span></a>
            <a href="<?= BASE_URL ?>relatorios" class="nav-item <?= $active('relatorios')  ?>"
                <?= $aria('relatorios')  ?> title="Relatórios"><i
                    class="fas fa-chart-bar"></i><span>Relatórios</span></a>
            <a href="<?= BASE_URL ?>agendamentos" class="nav-item <?= $active('agendamentos')  ?>"
                <?= $aria('agendamentos')  ?> title="Agendamentos"><i
                    class="fas fa-clock"></i><span>Agendamentos</span></a>
            <a href="<?= BASE_URL ?>perfil" class="nav-item <?= $active('perfil')      ?>" <?= $aria('perfil') ?>
                title="Perfil"><i class="fas fa-user-circle"></i><span>Perfil</span></a>

            <a id="btn-logout" class="nav-item" href="<?= BASE_URL ?>logout" title="Sair"><i
                    class="fas fa-sign-out-alt"></i>
                <span>Sair</span></a>
        </nav>

        <!-- FAB -->
        <div class="fab-container">
            <button class="fab" id="fabButton" aria-label="Adicionar transação" aria-haspopup="true"
                aria-expanded="false">
                <i class="fas fa-plus"></i>
            </button>
            <div class="fab-menu" id="fabMenu" role="menu">
                <button class="fab-menu-item" data-open-modal="receita" role="menuitem"><i
                        class="fas fa-arrow-up"></i><span>Receita</span></button>
                <button class="fab-menu-item" data-open-modal="despesa" role="menuitem"><i
                        class="fas fa-arrow-down"></i><span>Despesa</span></button>
                <button class="fab-menu-item" data-open-modal="agendamento" role="menuitem">
                    <i class="fas fa-calendar-plus"></i><span>Agendar</span>
                </button>

            </div>
        </div>
    </aside>
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>
    <main class="container">
        <?php include __DIR__ . '/navbar.php'; ?>
        <div class="lk-page">