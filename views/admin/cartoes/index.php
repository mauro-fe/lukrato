<div class="cartoes-page">
    <!-- ==================== HEADER COM ESTATÍSTICAS ==================== -->
    <div class="cartoes-header">
        <div class="header-top">

            <button class="btn btn-primary" id="btnNovoCartao">
                <i class="fas fa-plus"></i>
                Adicionar Cartão
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card" data-stat="total">
                <div class="stat-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total de Cartões</div>
                    <div class="stat-value" id="totalCartoes">0</div>
                </div>
            </div>

            <div class="stat-card" data-stat="limite">
                <div class="stat-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Limite Total</div>
                    <div class="stat-value" id="statLimiteTotal">R$ 0,00</div>
                </div>
            </div>

            <div class="stat-card" data-stat="disponivel">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Limite Disponível</div>
                    <div class="stat-value success" id="limiteDisponivel">R$ 0,00</div>
                </div>
            </div>

            <div class="stat-card" data-stat="utilizado">
                <div class="stat-icon warning">
                    <i class="fas fa-chart-line"></i>
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
                <i class="fas fa-search"></i>
                <input type="text" id="searchCartoes" placeholder="Buscar cartões..." autocomplete="off">
            </div>

            <div class="filter-group">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-th"></i>
                    Todos
                </button>
                <button class="filter-btn" data-filter="visa">
                    <i class="fab fa-cc-visa"></i>
                    Visa
                </button>
                <button class="filter-btn" data-filter="mastercard">
                    <i class="fab fa-cc-mastercard"></i>
                    Master
                </button>
                <button class="filter-btn" data-filter="elo">
                    <i class="fas fa-credit-card"></i>
                    Elo
                </button>
            </div>
        </div>

        <div class="toolbar-right">
            <button class="btn btn-ghost" id="btnExportar" title="Exportar relatório">
                <i class="fas fa-download"></i>
            </button>
            <button class="btn btn-ghost" id="btnReload" title="Atualizar">
                <i class="fas fa-sync-alt"></i>
            </button>
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid" title="Visualização em grade">
                    <i class="fas fa-th-large"></i>
                </button>
                <button class="view-btn" data-view="list" title="Visualização em lista">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== GRID DE CARTÕES ==================== -->
    <div class="cartoes-container" id="cartoesContainer">
        <div class="cartoes-grid" id="cartoesGrid">
            <!-- Skeleton Loading -->
            <div class="card-skeleton"></div>
            <div class="card-skeleton"></div>
            <div class="card-skeleton"></div>
        </div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <h3>Nenhum cartão cadastrado</h3>
            <p>Adicione seu primeiro cartão para começar a controlar seus gastos</p>
            <button class="btn btn-primary" id="btnNovoCartaoEmpty">
                <i class="fas fa-plus"></i>
                Adicionar Primeiro Cartão
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAIS ==================== -->
<?php include __DIR__ . '/../partials/modals/modal_cartoes.php'; ?>


<!-- ==================== SCRIPTS E ESTILOS ==================== -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/cartoes-modern.css?v=<?= time() ?>">
<script src="<?= BASE_URL ?>assets/js/cartoes-manager.js?v=<?= time() ?>"></script>
<script>
    window.BASE_URL = '<?= BASE_URL ?>';

    // Inicializar manager ao carregar a página
    document.addEventListener('DOMContentLoaded', () => {
        window.cartoesManager = new CartoesManager();
    });
</script>