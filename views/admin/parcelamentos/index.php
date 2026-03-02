<!-- CSS MODERNIZADO -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/faturas-modern.css.php?v=<?= time() ?>">

<section class="parc-page">

    <!-- ==================== HEADER COM SELETOR DE MÊS ==================== -->
    <div class="parc-header-modern">
        <?php include BASE_PATH . '/views/admin/partials/header-mes.php'; ?>
    </div>

    <!-- ==================== FILTROS COLAPSÁVEIS ==================== -->
    <div class="filters-modern" data-aos="fade-up" data-aos-delay="100">
        <div class="filters-header">
            <div class="filters-title">
                <div class="filters-icon">
                    <i data-lucide="sliders-horizontal"></i>
                </div>
                <div class="filters-text">
                    <h3>Filtros</h3>
                    <span class="filters-subtitle">Refine sua busca</span>
                </div>
            </div>
            <button type="button" class="filters-toggle" id="toggleFilters" aria-label="Expandir filtros">
                <i data-lucide="chevron-down"></i>
            </button>
        </div>

        <div class="filters-body" id="filtersBody">
            <div class="filters-grid">
                <div class="filter-item">
                    <label class="filter-label-modern" for="filtroStatus">
                        <i data-lucide="toggle-right" style="color: var(--color-primary)"></i>
                        Status
                    </label>
                    <div class="select-wrapper">
                        <select id="filtroStatus" class="filter-select">
                            <option value="">Todos</option>
                            <option value="pendente">⏳ Pendentes</option>
                            <option value="parcial">🔵 Parcialmente Pago</option>
                            <option value="paga">✅ Pagas</option>
                        </select>
                        <i data-lucide="chevron-down" class="select-arrow"></i>
                    </div>
                </div>

                <div class="filter-item">
                    <label class="filter-label-modern" for="filtroCartao">
                        <i data-lucide="credit-card" style="color: var(--color-primary)"></i>
                        Cartão
                    </label>
                    <div class="select-wrapper">
                        <select id="filtroCartao" class="filter-select">
                            <option value="">Todos os cartões</option>
                        </select>
                        <i data-lucide="chevron-down" class="select-arrow"></i>
                    </div>
                </div>

                <div class="filter-item">
                    <label class="filter-label-modern" for="filtroAno">
                        <i data-lucide="calendar" style="color: var(--color-primary)"></i>
                        Ano
                    </label>
                    <div class="select-wrapper">
                        <select id="filtroAno" class="filter-select">
                            <option value="">Todos os anos</option>
                        </select>
                        <i data-lucide="chevron-down" class="select-arrow"></i>
                    </div>
                </div>
            </div>

            <div class="filters-actions">
                <button type="button" id="btnLimparFiltros" class="btn-filter-clear">
                    <i data-lucide="x"></i>
                    <span>Limpar</span>
                </button>
                <button type="button" id="btnFiltrar" class="btn-filter-apply">
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
<div class="modal fade" id="modalDetalhesParcelamento" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
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

<!-- JavaScript (CDN scripts já carregados no header) -->
<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->