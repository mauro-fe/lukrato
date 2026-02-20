<!-- CSS MODERNIZADO -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-tables-shared.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/faturas-modern.css">

<section class="parc-page">

    <!-- ==================== HEADER COM SELETOR DE MÊS ==================== -->
    <div class="parc-header-modern">
        <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>
    </div>

    <!-- ==================== FILTROS ==================== -->
    <div class="modern-card filter-card" data-aos="fade-up" data-aos-delay="100">
        <div class="card-header-icon">
            <div class="icon-wrapper filter">
                <i data-lucide="filter"></i>
            </div>
            <div class="card-title-group">
                <h3 class="card-title">Filtros</h3>
                <p class="card-subtitle">Refine sua busca</p>
            </div>
        </div>

        <div class="filter-controls">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filtroStatus" class="filter-label">
                        <i data-lucide="toggle-right"></i>
                        <span>Status</span>
                    </label>
                    <select id="filtroStatus" class="modern-select">
                        <option value="">Todos</option>
                        <option value="pendente">⏳ Pendentes</option>
                        <option value="parcial">🔵 Parcialmente Pago</option>
                        <option value="paga">✅ Pagas</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filtroCartao" class="filter-label">
                        <i data-lucide="credit-card"></i>
                        <span>Cartão</span>
                    </label>
                    <select id="filtroCartao" class="modern-select">
                        <option value="">Todos os cartões</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filtroAno" class="filter-label">
                        <i data-lucide="calendar"></i>
                        <span>Ano</span>
                    </label>
                    <select id="filtroAno" class="modern-select">
                        <option value="">Todos os anos</option>
                    </select>
                </div>

                <button type="button" id="btnFiltrar" class="modern-btn secondary">
                    <i data-lucide="search"></i>
                    <span>Filtrar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== LOADING ==================== -->
    <div id="loadingParcelamentos" class="loading-container" style="display: none;">
        <div class="loading-spinner">
            <i class="icon-spin" data-lucide="loader-2"></i>
            <p>Carregando faturas...</p>
        </div>
    </div>

    <!-- ==================== LISTA DE FATURAS ==================== -->
    <div id="parcelamentosContainer" class="parcelamentos-grid" data-aos="fade-up" data-aos-delay="200">
        <!-- Cards serão inseridos aqui via JS -->
    </div>

    <!-- ==================== EMPTY STATE ==================== -->
    <div id="emptyState" class="empty-state" style="display: none;">
        <div class="empty-icon">
            <i data-lucide="credit-card"></i>
        </div>
        <h3>Nenhuma fatura encontrada</h3>
        <p>Suas faturas de cartão aparecerão aqui quando você fizer compras parceladas</p>
    </div>
</section>

<!-- ==================== MODAL: DETALHES DA FATURA ==================== -->
<div class="modal fade" id="modalDetalhesParcelamento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-lucide="list"></i>
                    <span>Detalhes da Fatura</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesParcelamentoContent">
                <!-- Conteúdo carregado dinamicamente -->
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>assets/js/admin-faturas.js"></script>