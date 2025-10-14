<?php
$pageTitle = $pageTitle ?? 'Painel Administrativo';
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
    <link rel="shortcut icon" href="<?= BASE_URL ?>assets/img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">

    <?php loadPageCss(); ?>
    <?php loadPageCss('admin-partials-header'); ?>
    <style>
        option {
            background-color: #1c2c3c;
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

    <button id="edgeMenuBtn" class="edge-menu-btn" aria-label="Abrir/fechar menu" aria-expanded="false"
        title="Fechar/Abrir menu">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>

    <aside class="sidebar no-glass collapsed" id="sidebar-main">
        <div class="sidebar-header">
            <a class="logo" href="<?= BASE_URL ?>/dashboard" aria-label="Ir para o Dashboard">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
            </a>
        </div>
        <nav class="sidebar-nav">
            <button id="toggleTheme" type="button" class="nav-item theme-toggle mb-3" aria-label="Alternar tema"
                title="Modo claro/escuro">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
            </button>
            <a href="<?= BASE_URL ?>dashboard" class="nav-item <?= $active('dashboard')   ?>"
                <?= $aria('dashboard')   ?> title="Dashboard"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="<?= BASE_URL ?>contas" class="nav-item <?= $active('contas')      ?>" <?= $aria('contas')      ?>
                title="Contas"><i class="fa fa-university" aria-hidden="true"></i><span>Contas</span></a>
            <a href="<?= BASE_URL ?>lancamentos" class="nav-item <?= $active('lancamentos') ?>"
                <?= $aria('lancamentos') ?> title="Lançamentos"><i
                    class="fas fa-exchange-alt"></i><span>Lançamentos</span></a>
            <a href="<?= BASE_URL ?>relatorios" class="nav-item <?= $active('relatorios')  ?>"
                <?= $aria('relatorios')  ?> title="Relatórios"><i
                    class="fas fa-chart-bar"></i><span>Relatórios</span></a>
            <a href="<?= BASE_URL ?>categorias" class="nav-item <?= $active('categorias')  ?>"
                <?= $aria('categorias')  ?> title="Categorias"><i class="fas fa-tags"></i><span>Categorias</span></a>
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

            </div>


        </div>
    </aside>
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg pt-0">
        <div class="container-fluid lk-page">
            <!-- Modal: Novo lancamento -->
            <div class="modal fade" id="modalLancamento" tabindex="-1" aria-labelledby="modalLancamentoTitle"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width:540px">
                    <div class="modal-content bg-dark text-light border-0 rounded-3">
                        <div class="modal-header border-0">
                            <h5 class="modal-title" id="modalLancamentoTitle">Novo lancamento</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Fechar"></button>
                        </div>

                        <div class="modal-body pt-0">
                            <div id="novoLancAlert" class="alert alert-danger d-none" role="alert"></div>
                            <form id="formNovoLancamento" novalidate>
                                <div class="row g-3">

                                    <div class="mb-3">
                                        <label for="lanData" class="form-label text-light small mb-1">Data</label>
                                        <input type="date" id="lanData"
                                            class="form-control form-control-sm bg-dark text-light border-secondary"
                                            required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="lanTipo" class="form-label text-light small mb-1">Tipo</label>
                                        <select id="lanTipo"
                                            class="form-select form-select-sm bg-dark text-light border-secondary"
                                            required>
                                            <option value="despesa">Despesa</option>
                                            <option value="receita">Receita</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="lanCategoria"
                                            class="form-label text-light small mb-1">Categoria</label>
                                        <select id="lanCategoria"
                                            class="form-select form-select-sm bg-dark text-light border-secondary"
                                            required>
                                            <option value="">Selecione uma categoria</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="headerConta" class="form-label text-light small mb-1">Conta</label>
                                        <select id="headerConta"
                                            class="form-select form-select-sm bg-dark text-light border-secondary"
                                            autocomplete="off">
                                            <option value="">Todas as contas (opcional)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="lanValor" class="form-label text-light small mb-1">Valor</label>
                                        <input type="text" id="lanValor"
                                            class="form-control form-control-sm bg-dark text-light border-secondary money-mask"
                                            placeholder="R$ 0,00" required>
                                    </div>

                                    <div class="col-md-9 mb-3">
                                        <label for="lanDescricao"
                                            class="form-label text-light small mb-1">Descricao</label>
                                        <input type="text" id="lanDescricao"
                                            class="form-control form-control-sm bg-dark text-light border-secondary"
                                            placeholder="Descricao do lancamento (opcional)">
                                    </div>

                                    <!-- 
                                    <div class="mb-3">
                                        <label for="lanObservacao"
                                            class="form-label text-light small mb-1">Observacao</label>
                                        <textarea id="lanObservacao"
                                            class="form-control form-control-sm bg-dark text-light border-secondary"
                                            rows="3" maxlength="500"
                                            placeholder="Detalhes adicionais (opcional)"></textarea>
                                    </div> -->

                                </div>
                            </form>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" form="formNovoLancamento"
                                class="btn btn-primary btn-sm">Salvar</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php loadPageJs('admin-home-header'); ?>
            <?php loadPageJs(); ?>
            <script>
                (function() {
                    const root = document.documentElement;
                    const btn = document.getElementById("toggleTheme");

                    const BASE_URL = LK.getBase();
                    const ENDPOINT = BASE_URL + 'api/user/theme';
                    const CSRF = LK.getCSRF();


                    // 1) aplica rápido o que estiver no localStorage para evitar "piscar"
                    const cached = localStorage.getItem("theme");
                    if (cached) applyTheme(cached, false);

                    // 2) busca do backend (fonte da verdade)
                    fetch(ENDPOINT, {
                            credentials: 'include'
                        })
                        .then(r => r.ok ? r.json() : Promise.reject(r))
                        .then(j => applyTheme(j?.theme || cached || 'light', false))
                        .catch(() => applyTheme(cached || 'light', false));

                    // 3) clique alterna e salva no backend
                    btn?.addEventListener("click", () => {
                        const current = root.getAttribute("data-theme") || 'light';
                        const next = current === 'dark' ? 'light' : 'dark';
                        applyTheme(next, true);
                    });

                    function applyTheme(theme, persist) {
                        root.setAttribute("data-theme", theme);
                        localStorage.setItem("theme", theme);
                        updateIcon(theme);

                        if (persist) {
                            const token = LK.getCSRF();
                            fetch(ENDPOINT, {
                                method: 'POST',
                                credentials: 'include',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': token
                                },
                                body: JSON.stringify({
                                    theme,
                                    _token: token,
                                    csrf_token: token
                                })
                            }).catch(err => console.error('Falha ao salvar tema:', err));
                        }

                    }

                    function updateIcon(theme) {
                        const btnEl = document.getElementById("toggleTheme");
                        if (!btnEl) return;
                        btnEl.classList.toggle('dark', theme === 'dark');
                    }
                })();
            </script>
            <script>
                (function() {
                    const rootEl = document.documentElement;
                    const sidebar = document.getElementById('sidebar-main');
                    const edgeBtn = document.getElementById('edgeMenuBtn');
                    const backdrop = document.getElementById('sidebarBackdrop');

                    if (!sidebar || !edgeBtn || !backdrop) return;

                    function applyCollapsedState(isCollapsed) {
                        sidebar.classList.toggle('collapsed', isCollapsed);
                        rootEl.classList.toggle('sidebar-collapsed', isCollapsed);
                        if (window.innerWidth > 992) {
                            edgeBtn.setAttribute('aria-expanded', String(!isCollapsed));
                        }
                    }

                    function closeMobile() {
                        sidebar.classList.remove('open');
                        rootEl.classList.remove('sidebar-open-mobile');
                        edgeBtn.setAttribute('aria-expanded', 'false');
                    }

                    function handleResponsive() {
                        const w = window.innerWidth;
                        if (w <= 992) {
                            // mobile: comeca fechada
                            rootEl.classList.remove('sidebar-collapsed');
                            sidebar.classList.remove('collapsed');
                            closeMobile();
                        } else {
                            // desktop/tablet: icones por padrao
                            closeMobile();
                            applyCollapsedState(true);
                        }
                    }

                    function onEdgeClick() {
                        if (window.innerWidth <= 992) {
                            const willOpen = !sidebar.classList.contains('open');
                            sidebar.classList.toggle('open', willOpen);
                            rootEl.classList.toggle('sidebar-open-mobile', willOpen);
                            edgeBtn.setAttribute('aria-expanded', String(willOpen));
                        } else {
                            const willCollapse = !sidebar.classList.contains('collapsed');
                            applyCollapsedState(willCollapse);
                        }
                    }

                    // Fechar tocando fora (mobile)
                    document.addEventListener('click', (e) => {
                        if (window.innerWidth > 992) return;
                        const hitSidebar = sidebar.contains(e.target);
                        const hitBtn = edgeBtn.contains(e.target);
                        const hitBackdrop = backdrop.contains(e.target);
                        if (!hitSidebar && !hitBtn) {
                            closeMobile();
                        }
                        if (hitBackdrop) {
                            closeMobile();
                        }
                    });

                    // Fechar com ESC no mobile
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && window.innerWidth <= 992) {
                            closeMobile();
                        }
                    });

                    edgeBtn.addEventListener('click', onEdgeClick);
                    window.addEventListener('resize', handleResponsive);
                    handleResponsive();
                })();
            </script>