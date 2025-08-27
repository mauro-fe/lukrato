<?php
// =========================================================================
// As variáveis agora são recebidas do Controller e inicializadas com
// valores padrão para garantir que o template nunca quebre.
// =========================================================================
$pageTitle = $pageTitle ?? 'Painel Administrativo';
$username  = $username ?? 'usuário';
$menu      = $menu ?? 'dashboard'; // Variável que controla o item de menu ativo
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/soft-ui-dashboard.css?v=1.1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php loadPageCss(); ?>
    <?php loadPageCss('admin-home-header'); ?>
</head>

<body class="g-sidenav-show bg-gray-100">
    <?php
    // valores esperados em $menu: 'dashboard','lancamentos','contas','categorias','cartoes','relatorios','config'

    $active = function (string $key) use ($menu) {
        return (!empty($menu) && $menu === $key) ? 'active' : '';
    };
    $aria   = function (string $key) use ($menu) {
        return (!empty($menu) && $menu === $key) ? ' aria-current="page"' : '';
    };
    ?>

    <aside class="sidebar no-glass" id="sidebar-main">
        <div class="sidebar-header">
            <a class="logo" href="<?= BASE_URL ?>/dashboard" aria-label="Ir para o Dashboard">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
            </a>
        </div>
        <!-- Botão Toggle Sidebar -->
        <button id="sidebarToggle" class="sidebar-toggle" aria-label="Abrir/Fechar menu">
            <i class="fas fa-bars"></i>
        </button>

        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>/dashboard" class="nav-item <?= $active('dashboard') ?>"
                aria-label="Dashboard Principal" <?= $aria('dashboard') ?>>
                <i class="fas fa-home" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>lancamentos" class="nav-item <?= $active('lancamentos') ?>" aria-label="Lançamentos"
                <?= $aria('lancamentos') ?>>
                <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                <span>Lançamentos</span>
            </a>
            <a href="<?= BASE_URL ?>relatorios" class="nav-item <?= $active('relatorios') ?>" aria-label="Relatórios"
                <?= $aria('relatorios') ?>>
                <i class="fas fa-chart-bar"></i> <span>Relatórios</span>
            </a>
            <a href="<?= $base ?>admin/<?= $u ?>/config" class="nav-item <?= $active('config') ?>"
                aria-label="Configurações" <?= $aria('config') ?>>
                <i class="fas fa-cog" aria-hidden="true"></i>
                <span>Config</span>
            </a>
        </nav>
    </aside>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <div class="month-selector">
                <button class="month-nav-btn" id="prevMonth" aria-label="Mês anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="month-display">
                    <button class="month-dropdown-btn" id="monthDropdownBtn" aria-haspopup="true" aria-expanded="false">
                        <span id="currentMonthText"></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="month-dropdown" id="monthDropdown" role="menu"></div>
                </div>

                <button class="month-nav-btn" id="nextMonth" aria-label="Próximo mês">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <div class="header-right">
            <button class="btn btn-ghost" id="exportBtn" aria-label="Exportar dados">
                <i class="fas fa-download"></i> Exportar
            </button>
            <div class="user-avatar">
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face"
                    alt="Avatar do usuário">
            </div>
        </div>
    </header>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <div class="container-fluid py-4 lk-page">

            <script>
                function copiarTexto(event) {
                    event.preventDefault();
                    const url = event.currentTarget.getAttribute('data-url');

                    if (!url) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Link não disponível.',
                        });
                        return;
                    }

                    navigator.clipboard.writeText(url)
                        .then(() => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Link copiado!',
                                text: 'Você pode enviar este link ao cliente para preenchimento da ficha.',
                                timer: 3000,
                                showConfirmButton: false,
                                position: 'top'
                            });
                        })
                        .catch((err) => {
                            console.error("Erro ao copiar: ", err);
                            // Fallback para navegadores que não suportam clipboard API
                            const textArea = document.createElement("textarea");
                            textArea.value = url;
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();
                            try {
                                document.execCommand('copy');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Link copiado!',
                                    text: 'Você pode enviar este link ao cliente.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } catch (err) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: 'Não foi possível copiar o link automaticamente. Copie manualmente: ' +
                                        url,
                                });
                            }
                            document.body.removeChild(textArea);
                        });
                }

                document.addEventListener("DOMContentLoaded", function() {
                    const toggle = document.getElementById("sidebarToggle");
                    const sidebar = document.getElementById("sidebar-main");

                    toggle.addEventListener("click", () => {
                        sidebar.classList.toggle("open");
                    });
                });
            </script>

            <?php loadPageJs('admin-home-header'); ?>

            <?php loadPageJs(); ?>