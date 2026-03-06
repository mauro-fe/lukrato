<div class="cartoes-page">
    <!-- ==================== HEADER COM ESTATÍSTICAS ==================== -->
    <div class="cartoes-header">
        <div class="header-top">

            <button class="btn btn-primary" id="btnNovoCartao">
                <i data-lucide="plus"></i>
                Adicionar Cartão
            </button>

            <a href="<?= BASE_URL ?>cartoes/arquivadas" class="btn btn-secondary">
                <i data-lucide="archive"></i>
                Cartões Arquivados
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card" data-stat="total">
                <div class="stat-icon">
                    <i data-lucide="credit-card" style="color: white"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total de Cartões</div>
                    <div class="stat-value" id="totalCartoes">0</div>
                </div>
            </div>

            <div class="stat-card" data-stat="limite">
                <div class="stat-icon">
                    <i data-lucide="hand-coins" style="color: white"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Limite Total</div>
                    <div class="stat-value" id="statLimiteTotal">R$ 0,00</div>
                </div>
            </div>

            <div class="stat-card" data-stat="disponivel">
                <div class="stat-icon success">
                    <i data-lucide="circle-check" style="color: white"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Limite Disponível</div>
                    <div class="stat-value success" id="limiteDisponivel">R$ 0,00</div>
                </div>
            </div>

            <div class="stat-card" data-stat="utilizado">
                <div class="stat-icon warning">
                    <i data-lucide="trending-up" style="color: white"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Limite Utilizado</div>
                    <div class="stat-value warning" id="limiteUtilizado">R$ 0,00</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== FILTROS E AÇÕES ==================== -->
    <div class="cartoes-toolbar">
        <div class="toolbar-left">
            <div class="search-box">
                <i data-lucide="search"></i>
                <input type="text" id="searchCartoes" placeholder="Buscar cartões..." autocomplete="off">
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
            <button class="btn btn-ghost" id="btnExportar" title="Exportar relatório">
                <i data-lucide="download"></i>
            </button>
            <button class="btn btn-ghost" id="btnReload" title="Atualizar">
                <i data-lucide="refresh-cw"></i>
            </button>
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid" title="Visualização em grade">
                    <i data-lucide="layout-grid"></i>
                </button>
                <button class="view-btn" data-view="list" title="Visualização em lista">
                    <i data-lucide="list"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== GRID DE CARTÕES ==================== -->
    <div class="cartoes-container" id="cartoesContainer">
        <div class="cartoes-grid" id="cartoesGrid">
            <!-- Skeleton Loading -->
            <div class="lk-skeleton lk-skeleton--card"></div>
            <div class="lk-skeleton lk-skeleton--card"></div>
            <div class="lk-skeleton lk-skeleton--card"></div>
        </div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">
                <i data-lucide="credit-card" style="color: white"></i>
            </div>
            <h3>Nenhum cartão cadastrado</h3>
            <p>Adicione seu primeiro cartão para começar a controlar seus gastos</p>
            <button class="btn btn-primary" id="btnNovoCartaoEmpty">
                <i data-lucide="plus"></i>
                Adicionar Primeiro Cartão
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAIS ==================== -->
<?php include __DIR__ . '/../partials/modals/modal-cartoes.php'; ?>


<!-- ==================== SCRIPTS E ESTILOS ==================== -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/cartoes-modern.css.php?v=<?= time() ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->