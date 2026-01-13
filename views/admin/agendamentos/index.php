<!-- Tabulator -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">

<!-- CSS MODERNIZADO -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-tables-shared.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/lancamentos-modern.css">

<section class="lan-page">
    <!-- ==================== HEADER MODERNIZADO ==================== -->
    <div class="lan-header-modern">
        <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

        <!-- CARD DE FILTROS -->
        <div class="modern-card filter-card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-header-icon">
                <div class="icon-wrapper filter">
                    <i class="fas fa-filter"></i>
                </div>
                <div class="card-title-group">
                    <h3 class="card-title">Filtros Avançados</h3>
                    <p class="card-subtitle">Refine sua busca por tipo, categoria e conta</p>
                </div>
            </div>

            <div class="filter-controls">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filtroTipo" class="filter-label">
                            <i class="fas fa-tag"></i>
                            <span>Tipo</span>
                        </label>
                        <select id="filtroTipo" class="modern-select" aria-label="Filtrar por tipo">
                            <option value="">Todos os Tipos</option>
                            <option value="receita">💰 Receitas</option>
                            <option value="despesa">💸 Despesas</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filtroCategoria" class="filter-label">
                            <i class="fas fa-folder"></i>
                            <span>Categoria</span>
                        </label>
                        <select id="filtroCategoria" class="modern-select" aria-label="Filtrar por categoria">
                            <option value="">Todas as Categorias</option>
                            <option value="none">Sem Categoria</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filtroConta" class="filter-label">
                            <i class="fas fa-wallet"></i>
                            <span>Conta</span>
                        </label>
                        <select id="filtroConta" class="modern-select" aria-label="Filtrar por conta">
                            <option value="">Todas as Contas</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button id="btnFiltrar" type="button" class="modern-btn primary" aria-label="Aplicar filtros">
                        <i class="fas fa-search"></i>
                        <span>Aplicar Filtros</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TABELA MODERNIZADA ==================== -->
    <div class="modern-table-wrapper" data-aos="fade-up" data-aos-delay="200">
        <div class="table-header-info">
            <div class="info-group">
                <i class="fas fa-calendar-check"></i>
                <span>Seus Agendamentos</span>
            </div>
            <div class="table-actions">
                <button type="button" class="modern-btn primary" data-bs-toggle="modal"
                    data-bs-target="#modalAgendamento">
                    <i class="fas fa-plus"></i>
                    <span>Novo Agendamento</span>
                </button>

                <button type="button" class="icon-btn" title="Atualizar" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <div class="lan-table-container">
            <!-- DESKTOP: Tabela Tabulator -->
            <section class="table-container tab-desktop">
                <div id="agendamentosTable"></div>
            </section>

            <!-- MOBILE: Cards + pager -->
            <section class="lan-cards-wrapper cards-wrapper">
                <!-- Cards -->
                <section class="lan-cards-container cards-container" id="agCards"></section>

                <!-- Pager -->
                <nav class="lan-cards-pager cards-pager" id="agCardsPager" aria-label="Paginação de agendamentos">
                    <button type="button" id="agPagerFirst" class="lan-pager-btn pager-btn" disabled
                        aria-label="Primeira página">
                        <i class="fas fa-angle-double-left"></i>
                    </button>

                    <button type="button" id="agPagerPrev" class="lan-pager-btn pager-btn" disabled
                        aria-label="Página anterior">
                        <i class="fas fa-chevron-left"></i>
                    </button>

                    <span id="agPagerInfo" class="lan-pager-info pager-info">Nenhum agendamento</span>

                    <button type="button" id="agPagerNext" class="lan-pager-btn pager-btn" disabled
                        aria-label="Próxima página">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <button type="button" id="agPagerLast" class="lan-pager-btn pager-btn" disabled
                        aria-label="Última página">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </nav>
            </section>
        </div>
    </div>

    <!-- ==================== PAYWALL ==================== -->
    <div id="agPaywall" class="paywall-message d-none" role="alert" hidden>
        <i class="fas fa-crown"></i>
        <h3>Recurso Premium</h3>
        <p id="agPaywallMessage">Agendamentos são exclusivos do plano Pro.</p>
        <button type="button" class="btn-upgrade" id="agPaywallCta">
            <i class="fas fa-crown"></i>
            Fazer Upgrade para PRO
        </button>
    </div>
</section>

<!-- ==================== SCRIPTS ==================== -->
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/admin-agendamentos-index.js"></script>