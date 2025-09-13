<?php
$pageTitle = $pageTitle ?? 'Painel Administrativo';
$username  = $username  ?? 'usuário';
$menu      = $menu      ?? '';

// Fix
$u    = 'admin';
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="pt-BR" lang="pt-BR" data-theme="dark">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>

    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">

    <?php loadPageCss(); ?>
    <?php loadPageCss('admin-home-header'); ?>

    <style>
        /* =========================================================
 * Modal (LK)
 * =======================================================*/
        .lkh-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-normal);
        }

        .lkh-modal.active {
            display: flex;
            opacity: 1;
            visibility: visible;
        }

        .lkh-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .lkh-modal-content {
            position: relative;
            left: 30%;
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.95);
            transition: var(--transition-normal);
            z-index: 1;
            color: var(--color-text);
        }

        .lkh-modal.active .lkh-modal-content {
            transform: scale(1);
        }
    </style>
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

    <aside class="sidebar no-glass" id="sidebar-main">
        <div class="sidebar-header">
            <a class="logo" href="<?= BASE_URL ?>/dashboard" aria-label="Ir para o Dashboard">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
            </a>
        </div>

        <button id="sidebarToggle" class="sidebar-toggle" aria-label="Abrir/Fechar menu">
            <i class="fas fa-bars"></i>
        </button>
        <button id="toggleTheme" class="theme-toggle" aria-label="Alternar tema">
            <i class="fas fa-sun"></i>
            <i class="fas fa-moon"></i>
        </button>
        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>dashboard" class="nav-item <?= $active('dashboard')   ?>"
                <?= $aria('dashboard')   ?>><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="<?= BASE_URL ?>contas" class="nav-item <?= $active('contas')      ?>"
                <?= $aria('contas')      ?>><i class="fa fa-university" aria-hidden="true"></i><span>Contas</span></a>
            <a href="<?= BASE_URL ?>lancamentos" class="nav-item <?= $active('lancamentos') ?>"
                <?= $aria('lancamentos') ?>><i class="fas fa-exchange-alt"></i><span>Lançamentos</span></a>
            <a href="<?= BASE_URL ?>relatorios" class="nav-item <?= $active('relatorios')  ?>"
                <?= $aria('relatorios')  ?>><i class="fas fa-chart-bar"></i><span>Relatórios</span></a>
            <a href="<?= BASE_URL ?>perfil" class="nav-item <?= $active('perfil')      ?>"
                <?= $aria('perfil')      ?>><i class="fas fa-user-circle"></i><span>Perfil</span></a>

            <a id="btn-logout" class="nav-item" href="<?= BASE_URL ?>logout"><i class="fas fa-sign-out-alt"></i>
                Sair</a>
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
            </div>
        </div>
    </aside>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg pt-0">
        <div class="container-fluid lk-page">
            <!-- Modal Único -->
            <div class="lkh-modal" id="modalLancamento" role="dialog" aria-labelledby="modalLancamentoTitle"
                aria-hidden="true">
                <div class="lkh-modal-backdrop"></div>
                <div class="lkh-modal-content">
                    <div class="lkh-modal-header">
                        <h2 id="modalLancamentoTitle">Novo Lançamento</h2>
                        <button class="lkh-modal-close" aria-label="Fechar modal"><i class="fas fa-times"></i></button>
                    </div>

                    <form class="lkh-modal-body" id="formLancamento" novalidate>
                        <div class="form-group">
                            <label for="lanTipo">Tipo</label>
                            <select id="lanTipo" class="form-select" required>
                                <option value="despesa">Despesa</option>
                                <option value="receita">Receita</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="lanData">Data</label>
                            <input type="date" id="lanData" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="lanCategoria">Categoria</label>
                            <select id="lanCategoria" class="form-select" required>
                                <option value="">Selecione uma categoria</option>
                            </select>
                        </div>


                        <div class="form-group" id="grpParcelas" style="display:none;">
                            <label for="lanParcelas">Parcelas</label>
                            <select id="lanParcelas" class="form-select">
                                <option value="1">1x (à vista)</option>
                                <option value="2">2x sem juros</option>
                                <option value="3">3x sem juros</option>
                                <option value="4">4x sem juros</option>
                                <option value="5">5x sem juros</option>
                                <option value="6">6x sem juros</option>
                                <option value="7">7x sem juros</option>
                                <option value="8">8x sem juros</option>
                                <option value="9">9x sem juros</option>
                                <option value="10">10x sem juros</option>
                                <option value="11">11x sem juros</option>
                                <option value="12">12x sem juros</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="lanDescricao">Descrição</label>
                            <input type="text" id="lanDescricao" class="form-input"
                                placeholder="Descrição do lançamento" />
                        </div>

                        <div class="form-group" style="display:none;">
                            <label for="lanObservacao">Observação (opcional)</label>
                            <input type="text" id="lanObservacao" class="form-input"
                                placeholder="Detalhe, nota interna..." />
                        </div>

                        <div class="form-group">
                            <label for="lanValor">Valor</label>
                            <input type="text" id="lanValor" class="form-input money-mask" placeholder="R$ 0,00"
                                required />
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="lanPago"><span class="checkbox-custom"></span>
                                <span id="lanPagoLabel">Foi pago?</span>
                            </label>
                        </div>
                    </form>

                    <div class="lkh-modal-footer">
                        <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
                        <button type="submit" form="formLancamento" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            </div>

            <?php loadPageJs('admin-home-header'); ?>
            <?php loadPageJs(); ?>
        </div>

        <script>
            (function() {
                const root = document.documentElement;
                const btn = document.getElementById("toggleTheme");

                // aplica o tema salvo no localStorage (padrão = light)
                const saved = localStorage.getItem("theme") || "dark";
                root.setAttribute("data-theme", saved);

                // atualiza ícone
                updateIcon(saved);

                // alternar ao clicar
                btn.addEventListener("click", () => {
                    const current = root.getAttribute("data-theme");
                    const next = current === "dark" ? "light" : "dark";
                    root.setAttribute("data-theme", next);
                    localStorage.setItem("theme", next);
                    updateIcon(next);
                });

                function updateIcon(theme) {
                    if (theme === "dark") {
                        btn.classList.add("dark");
                    } else {
                        btn.classList.remove("dark");
                    }
                }
            })();
        </script>