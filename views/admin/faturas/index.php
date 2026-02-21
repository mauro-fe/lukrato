<!-- CSS MODERNIZADO -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-tables-shared.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/faturas-modern.css">

<section class="parc-page">

    <!-- ==================== FILTROS MODERNOS ==================== -->
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
                <!-- Status -->
                <div class="filter-item">
                    <label class="filter-label-modern" for="filtroStatus">
                        <i data-lucide="circle-check"></i>
                        Status
                    </label>
                    <div class="select-wrapper">
                        <select id="filtroStatus" class="filter-select">
                            <option value="">Todos os status</option>
                            <option value="pendente">&#x25F7; Pendentes</option>
                            <option value="parcial">&#x21BB; Parcialmente Pagas</option>
                            <option value="paga">&#x2714; Pagas</option>
                            <option value="cancelado">&#x2718; Canceladas</option>
                        </select>
                        <i data-lucide="chevron-down" class="select-arrow"></i>
                    </div>
                </div>

                <!-- Cartão -->
                <div class="filter-item">
                    <label class="filter-label-modern" for="filtroCartao">
                        <i data-lucide="credit-card"></i>
                        Cartão
                    </label>
                    <div class="select-wrapper">
                        <select id="filtroCartao" class="filter-select">
                            <option value="">Todos os cartões</option>
                        </select>
                        <i data-lucide="chevron-down" class="select-arrow"></i>
                    </div>
                </div>

                <!-- Ano -->
                <div class="filter-item">
                    <label class="filter-label-modern" for="filtroAno">
                        <i data-lucide="calendar"></i>
                        Ano
                    </label>
                    <div class="select-wrapper">
                        <select id="filtroAno" class="filter-select">
                            <option value="">Todos os anos</option>
                        </select>
                        <i data-lucide="chevron-down" class="select-arrow"></i>
                    </div>
                </div>

                <!-- Mês -->
                <div class="filter-item">
                    <label class="filter-label-modern" for="filtroMes">
                        <i data-lucide="calendar"></i>
                        Mês
                    </label>
                    <div class="select-wrapper">
                        <select id="filtroMes" class="filter-select">
                            <option value="">Todos os meses</option>
                            <option value="1">Janeiro</option>
                            <option value="2">Fevereiro</option>
                            <option value="3">Março</option>
                            <option value="4">Abril</option>
                            <option value="5">Maio</option>
                            <option value="6">Junho</option>
                            <option value="7">Julho</option>
                            <option value="8">Agosto</option>
                            <option value="9">Setembro</option>
                            <option value="10">Outubro</option>
                            <option value="11">Novembro</option>
                            <option value="12">Dezembro</option>
                        </select>
                        <i data-lucide="chevron-down" class="select-arrow"></i>
                    </div>
                </div>
            </div>

            <!-- Botões de ação -->
            <div class="filters-actions">
                <button type="button" id="btnLimparFiltros" class="btn-filter-clear">
                    <i data-lucide="x"></i>
                    <span>Limpar</span>
                </button>
                <button type="button" id="btnFiltrar" class="btn-filter-apply">
                    <i data-lucide="search"></i>
                    <span>Aplicar Filtros</span>
                </button>
            </div>
        </div>

        <!-- Filtros ativos (badges) -->
        <div class="active-filters" id="activeFilters" style="display: none;">
            <!-- Badges de filtros ativos serão inseridos via JS -->
        </div>
    </div>

    <!-- ==================== HEADER COM TOGGLE ==================== -->
    <div class="faturas-header" data-aos="fade-up" data-aos-delay="150">
        <div class="faturas-title">
            <i data-lucide="file-text"></i>
            <span>Suas Faturas</span>
        </div>
        <div class="view-toggle">
            <button class="view-btn active" data-view="grid" title="Visualização em Cards">
                <i data-lucide="grip-horizontal"></i>
            </button>
            <button class="view-btn" data-view="list" title="Visualização em Lista">
                <i data-lucide="list"></i>
            </button>
        </div>
    </div>

    <!-- ==================== LOADING ==================== -->
    <div id="loadingParcelamentos" class="loading-container" style="display: none;">
        <div class="loading-spinner">
            <i data-lucide="loader-2" class="icon-spin"></i>
            <p>Carregando faturas...</p>
        </div>
    </div>

    <!-- ==================== LISTA DE FATURAS ==================== -->
    <!-- Headers da lista (visível apenas em modo lista) -->
    <div id="faturasListHeader" class="faturas-list-header">
        <span></span>
        <span>Cartão</span>
        <span>Valor</span>
        <span>Progresso</span>
        <span>Status</span>
        <span>Ações</span>
    </div>
    <div id="parcelamentosContainer" class="parcelamentos-grid" data-aos="fade-up" data-aos-delay="200">
        <!-- Cards serão inseridos aqui via JS -->
    </div>

    <!-- ==================== EMPTY STATE ==================== -->
    <div id="emptyState" class="empty-state" style="display: none;">
        <div class="empty-icon">
            <i data-lucide="credit-card"></i>
        </div>
        <h3>Nenhuma fatura encontrada</h3>
        <p>Suas faturas de cartão aparecerão aqui automaticamente quando você cadastrar compras parceladas</p>
        <a href="<?= BASE_URL ?>lancamentos" class="btn-cta">
            <i data-lucide="plus"></i>
            Criar Lançamento Parcelado
        </a>
    </div>
</section>
<?php include __DIR__ . '/../partials/modals/modal-detalhes-faturas.php'; ?>


<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>assets/js/admin-faturas-index.js"></script>