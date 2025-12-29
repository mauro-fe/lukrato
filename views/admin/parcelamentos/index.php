<!-- CSS MODERNIZADO -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-tables-shared.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/parcelamentos-modern.css">

<section class="parc-page">

    <!-- ==================== HEADER COM SELETOR DE MÊS ==================== -->
    <div class="parc-header-modern">
        <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>
    </div>

    <!-- ==================== FILTROS ==================== -->
    <div class="modern-card filter-card" data-aos="fade-up" data-aos-delay="100">
        <div class="card-header-icon">
            <div class="icon-wrapper filter">
                <i class="fas fa-filter"></i>
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
                        <i class="fas fa-toggle-on"></i>
                        <span>Status</span>
                    </label>
                    <select id="filtroStatus" class="modern-select">
                        <option value="">Todos</option>
                        <option value="ativo" selected>✅ Ativos</option>
                        <option value="concluido">✔️ Concluídos</option>
                        <option value="cancelado">❌ Cancelados</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filtroTipo" class="filter-label">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Tipo</span>
                    </label>
                    <select id="filtroTipo" class="modern-select">
                        <option value="">Todos</option>
                        <option value="saida">💸 Despesas</option>
                        <option value="entrada">💰 Receitas</option>
                    </select>
                </div>

                <button type="button" id="btnFiltrar" class="modern-btn secondary">
                    <i class="fas fa-search"></i>
                    <span>Filtrar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== LOADING ==================== -->
    <div id="loadingParcelamentos" class="loading-container" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-circle-notch fa-spin"></i>
            <p>Carregando parcelamentos...</p>
        </div>
    </div>

    <!-- ==================== LISTA DE PARCELAMENTOS ==================== -->
    <div id="parcelamentosContainer" class="parcelamentos-grid" data-aos="fade-up" data-aos-delay="200">
        <!-- Cards serão inseridos aqui via JS -->
    </div>

    <!-- ==================== EMPTY STATE ==================== -->
    <div id="emptyState" class="empty-state" style="display: none;">
        <div class="empty-icon">
            <i class="fas fa-credit-card"></i>
        </div>
        <h3>Nenhum parcelamento encontrado</h3>
        <p>Seus parcelamentos aparecerão aqui quando você criar lançamentos parcelados</p>
    </div>
</section>

<!-- ==================== MODAL: DETALHES DO PARCELAMENTO ==================== -->
<div class="modal fade" id="modalDetalhesParcelamento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list"></i>
                    <span>Detalhes do Parcelamento</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesParcelamentoContent">
                <!-- Conteúdo carregado dinamicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-check"></i> Salvar
                </button>
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
<script src="<?= BASE_URL ?>assets/js/admin-parcelamentos.js"></script>