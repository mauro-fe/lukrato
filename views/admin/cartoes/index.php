<div class="cartoes-page">
    <!-- ==================== HEADER COM ESTATISTICAS ==================== -->
    <div class="cartoes-header">
        <div class="header-top">
            <div class="page-copy">
                <h1 class="page-title">
                    <i data-lucide="credit-card"></i>
                    Seus cartoes em um so lugar
                </h1>
                <p class="page-subtitle">
                    Acompanhe limite, faturas pendentes e os cartoes que merecem atencao primeiro.
                </p>
            </div>

            <div class="header-actions">
                <button class="btn btn-primary" id="btnNovoCartao">
                    <i data-lucide="plus"></i>
                    Adicionar cartao
                </button>

                <a href="<?= BASE_URL ?>cartoes/arquivadas" class="btn btn-secondary">
                    <i data-lucide="archive"></i>
                    Arquivados
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card" data-stat="total">
                <div class="stat-icon">
                    <i data-lucide="credit-card" style="color: white"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total de cartoes</div>
                    <div class="stat-value" id="totalCartoes">0</div>
                </div>
            </div>

            <div class="stat-card" data-stat="limite">
                <div class="stat-icon">
                    <i data-lucide="hand-coins" style="color: white"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Limite total</div>
                    <div class="stat-value" id="statLimiteTotal">R$ 0,00</div>
                </div>
            </div>

            <div class="stat-card" data-stat="disponivel">
                <div class="stat-icon success">
                    <i data-lucide="circle-check" style="color: white"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Limite disponivel</div>
                    <div class="stat-value success" id="limiteDisponivel">R$ 0,00</div>
                </div>
            </div>

            <div class="stat-card" data-stat="utilizado">
                <div class="stat-icon warning">
                    <i data-lucide="trending-up" style="color: white"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Limite utilizado</div>
                    <div class="stat-value warning" id="limiteUtilizado">R$ 0,00</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== FILTROS E ACOES ==================== -->
    <div class="cartoes-toolbar" aria-label="Filtros e acoes da pagina de cartoes">
        <div class="toolbar-left">
            <div class="search-box">
                <i data-lucide="search"></i>
                <input type="text" id="searchCartoes" placeholder="Buscar por nome ou final..." autocomplete="off">
            </div>

            <div class="filter-group">
                <button class="filter-btn active" data-filter="all">
                    <i data-lucide="grid-3x3"></i>
                    Todos
                </button>
                <button class="filter-btn" data-filter="visa">
                    <img src="<?= BASE_URL ?>assets/img/bandeiras/visa.png" alt="Visa" class="brand-logo-filter">
                    Visa
                </button>
                <button class="filter-btn" data-filter="mastercard">
                    <img src="<?= BASE_URL ?>assets/img/bandeiras/mastercard.png" alt="Mastercard"
                        class="brand-logo-filter">
                    Master
                </button>
                <button class="filter-btn" data-filter="elo">
                    <img src="<?= BASE_URL ?>assets/img/bandeiras/elo.png" alt="Elo" class="brand-logo-filter">
                    Elo
                </button>
                <button class="filter-btn btn-clear-filters" id="btnLimparFiltrosCartoes" title="Limpar busca e filtros"
                    style="display:none;">
                    <i data-lucide="eraser"></i>
                    Limpar
                </button>
            </div>
        </div>

        <div class="toolbar-right">
            <button class="btn btn-ghost" id="btnExportar" title="Exportar relatorio">
                <i data-lucide="download"></i>
            </button>
            <button class="btn btn-ghost" id="btnReload" title="Atualizar cartoes">
                <i data-lucide="refresh-cw"></i>
            </button>
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid" title="Visualizacao em grade">
                    <i data-lucide="layout-grid"></i>
                </button>
                <button class="view-btn" data-view="list" title="Visualizacao em lista">
                    <i data-lucide="list"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="cartoes-filter-summary" id="cartoesFilterSummary" aria-live="polite"></div>

    <section class="cartoes-alertas" id="alertasContainer" style="display: none;" aria-live="polite"></section>

    <!-- ==================== GRID DE CARTOES ==================== -->
    <div class="cartoes-container" id="cartoesContainer">
        <div class="cartoes-grid" id="cartoesGrid" aria-live="polite" aria-busy="true">
            <div class="lk-skeleton lk-skeleton--card"></div>
            <div class="lk-skeleton lk-skeleton--card"></div>
            <div class="lk-skeleton lk-skeleton--card"></div>
        </div>

        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">
                <i data-lucide="credit-card" style="color: white"></i>
            </div>
            <h3>Nenhum cartao cadastrado</h3>
            <p>Adicione seu primeiro cartao para acompanhar limite, vencimentos e faturas em tempo real.</p>
            <div class="empty-state-actions">
                <button class="btn btn-primary" id="btnNovoCartaoEmpty">
                    <i data-lucide="plus"></i>
                    Adicionar primeiro cartao
                </button>
                <button class="btn btn-secondary" id="btnLimparFiltrosEmpty" style="display: none;">
                    <i data-lucide="eraser"></i>
                    Limpar filtros
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAIS ==================== -->
<?php include __DIR__ . '/../partials/modals/modal-cartoes.php'; ?>
<?php include __DIR__ . '/../partials/modals/card-detail-modal.php'; ?>


<!-- ==================== SCRIPTS E ESTILOS ==================== -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/cartoes-modern.css.php?v=<?= time() ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/relatorios/_modal-cartao.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/relatorios/_modal-responsive.css?v=<?= time() ?>">
<?= vite_scripts('admin/card-modals/index.js') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->