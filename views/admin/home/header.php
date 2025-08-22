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


    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-icons.css" rel="stylesheet">
    <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-svg.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/soft-ui-dashboard.css?v=1.1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-dt/css/jquery.dataTables.min.css">
    <?php loadPageCss(); ?>
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
            <a class="logo" href="<?= BASE_URL ?>admin/dashboard" aria-label="Ir para o Dashboard">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
            </a>
        </div>

        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>admin/dashboard"
                class="nav-item <?= $active('dashboard') ?>" aria-label="Dashboard Principal" <?= $aria('dashboard') ?>>
                <i class="fas fa-home" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= $base ?>admin/<?= $u ?>/lancamentos"
                class="nav-item <?= $active('lancamentos') ?>" aria-label="Lançamentos" <?= $aria('lancamentos') ?>>
                <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                <span>Lançamentos</span>
            </a>
            <a href="<?= $base ?>admin/<?= $u ?>/contas"
                class="nav-item <?= $active('contas') ?>" aria-label="Contas" <?= $aria('contas') ?>>
                <i class="fas fa-wallet" aria-hidden="true"></i>
                <span>Contas</span>
            </a>
            <a href="<?= $base ?>admin/<?= $u ?>/categorias"
                class="nav-item <?= $active('categorias') ?>" aria-label="Categorias" <?= $aria('categorias') ?>>
                <i class="fas fa-tags" aria-hidden="true"></i>
                <span>Categorias</span>
            </a>
            <a href="<?= $base ?>admin/<?= $u ?>/cartoes"
                class="nav-item <?= $active('cartoes') ?>" aria-label="Cartões" <?= $aria('cartoes') ?>>
                <i class="fas fa-credit-card" aria-hidden="true"></i>
                <span>Cartões</span>
            </a>
            <a href="<?= $base ?>admin/<?= $u ?>/relatorios"
                class="nav-item <?= $active('relatorios') ?>" aria-label="Relatórios" <?= $aria('relatorios') ?>>
                <i class="fas fa-chart-bar" aria-hidden="true"></i>
                <span>Relatórios</span>
            </a>
            <a href="<?= $base ?>admin/<?= $u ?>/config"
                class="nav-item <?= $active('config') ?>" aria-label="Configurações" <?= $aria('config') ?>>
                <i class="fas fa-cog" aria-hidden="true"></i>
                <span>Config</span>
            </a>
        </nav>
    </aside>


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
                            text: 'Não foi possível copiar o link automaticamente. Copie manualmente: ' + url,
                        });
                    }
                    document.body.removeChild(textArea);
                });
        }
    </script>