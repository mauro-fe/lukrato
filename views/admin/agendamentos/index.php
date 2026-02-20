<!-- CSS Agendamentos -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-agendamentos-index.css?v=<?= md5(uniqid(rand(), true)) ?>">


<section class="ag-page">
    <!-- ==================== HEADER MODERNIZADO ==================== -->
    <div class="lan-header-modern">
        <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

        <!-- CARD DE FILTROS -->
        <div class="modern-card filter-card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-header-icon">
                <div class="icon-wrapper filter">
                    <i data-lucide="filter"></i>
                </div>
                <div class="card-title-group">
                    <h3 class="card-title">Filtros Avançados</h3>
                    <p class="card-subtitle">Refine sua busca por tipo, categoria e conta</p>
                </div>
            </div>

            <div class="filter-controls">
                <div class="filter-row">


                    <div class="filter-group">
                        <label for="filtroCategoria" class="filter-label">
                            <i data-lucide="folder"></i>
                            <span>Categoria</span>
                        </label>
                        <select id="filtroCategoria" class="modern-select" aria-label="Filtrar por categoria">
                            <option value="">Todas as categorias</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filtroConta" class="filter-label">
                            <i data-lucide="wallet"></i>
                            <span>Conta</span>
                        </label>
                        <select id="filtroConta" class="modern-select" aria-label="Filtrar por conta">
                            <option value="">Todas as contas</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filtroStatus" class="filter-label">
                            <i data-lucide="info"></i>
                            <span>Status</span>
                        </label>
                        <select id="filtroStatus" class="modern-select" aria-label="Filtrar por status">
                            <option value="">Todos</option>
                            <option value="hoje">📅 Hoje</option>
                            <option value="agendado">⏰ Agendado</option>
                            <option value="vencido">⚠️ Vencido</option>
                            <option value="cancelado">❌ Cancelado</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="button" id="btnLimparFiltros" class="modern-btn primary" aria-label="Limpar filtros">
                        <i data-lucide="eraser"></i>
                        <span>Limpar Filtros</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TABELA/CARDS ==================== -->
    <div class="ag-table-container" id="agList">

        <!-- Filtros Rápidos -->
        <div class="quick-filters" id="quickFilters" data-aos="fade-up">
            <button type="button" class="quick-filter-btn" data-filter="hoje">
                <i data-lucide="calendar"></i>
                <span>Hoje</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="semana">
                <i data-lucide="calendar-range"></i>
                <span>Esta Semana</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="vencidos">
                <i data-lucide="triangle-alert"></i>
                <span>Vencidos</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="receitas">
                <i data-lucide="arrow-up" style="color: var(--color-success)"></i>
                <span>Receitas</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="despesas">
                <i data-lucide="arrow-down" style="color: var(--color-danger)"></i>
                <span>Despesas</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="recorrentes">
                <i data-lucide="refresh-cw"></i>
                <span>Recorrentes</span>
            </button>
        </div>

        <!-- Header com título e botão de ação -->
        <div class="modern-table-wrapper" style="margin-bottom: var(--spacing-4);">
            <div class="table-header-info">
                <div class="info-group">
                    <i data-lucide="clock"></i>
                    <span>Seus Agendamentos</span>
                </div>
                <div class="table-actions">
                    <button type="button" id="btnAddAgendamento" class="modern-btn primary"
                        aria-label="Novo agendamento">
                        <i data-lucide="plus"></i>
                        <span>Novo Agendamento</span>
                    </button>
                </div>
            </div>

            <section class="table-container ag-table-desktop tab-desktop">
                <div class="table-wrapper">
                    <table class="ag-table" id="agendamentosTable">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th class="sortable" data-sort="tipo">
                                    <span>Tipo</span>
                                    <i data-lucide="arrow-up-down" class="sort-icon"></i>
                                </th>
                                <th>Categoria</th>
                                <th>Conta</th>
                                <th class="sortable" data-sort="valor_centavos">
                                    <span>Valor</span>
                                    <i data-lucide="arrow-up-down" class="sort-icon"></i>
                                </th>
                                <th class="sortable" data-sort="data_pagamento">
                                    <span>Data</span>
                                    <i data-lucide="arrow-up-down" class="sort-icon"></i>
                                </th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="agendamentosTableBody">
                            <tr class="lk-loading-row">
                                <td colspan="8" style="text-align:center;padding:2rem 1rem;">
                                    <div class="lk-loading-state">
                                        <div class="spinner-border" role="status" style="width:2rem;height:2rem;color:var(--color-primary);">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        <p style="margin:0.75rem 0 0;color:var(--color-text-muted);font-size:0.85rem;">Carregando agendamentos...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação Desktop -->
                <div class="lk-pagination" id="agDesktopPagination">
                    <div class="pagination-info">
                        <span id="agPaginationInfo">0 agendamentos</span>
                    </div>
                    <div class="pagination-controls">
                        <select id="agPageSize" class="page-size-select">
                            <option value="10" selected>10 por página</option>
                            <option value="25">25 por página</option>
                            <option value="50">50 por página</option>
                            <option value="100">100 por página</option>
                        </select>
                        <button type="button" id="agPrevPage" class="pagination-btn" disabled>
                            <i data-lucide="chevron-left"></i>
                        </button>
                        <span id="agPageNumbers" class="page-numbers"></span>
                        <button type="button" id="agNextPage" class="pagination-btn" disabled>
                            <i data-lucide="chevron-right"></i>
                        </button>
                    </div>
                </div>
            </section>

            <section class="ag-cards-wrapper cards-wrapper">
                <div class="ag-cards-container cards-container" id="agCards"></div>

                <nav class="ag-cards-pager cards-pager" id="agCardsPager">
                    <button type="button" id="agPagerFirst" class="ag-pager-btn pager-btn" disabled>
                        <i data-lucide="chevrons-left"></i>
                    </button>
                    <button type="button" id="agPagerPrev" class="ag-pager-btn pager-btn" disabled>
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <span id="agPagerInfo" class="ag-pager-info pager-info">Nenhum agendamento</span>
                    <button type="button" id="agPagerNext" class="ag-pager-btn pager-btn" disabled>
                        <i data-lucide="chevron-right"></i>
                    </button>
                    <button type="button" id="agPagerLast" class="ag-pager-btn pager-btn" disabled>
                        <i data-lucide="chevrons-right"></i>
                    </button>
                </nav>
            </section>

            <template id="agCardTemplate">
                <article class="ag-card card-item" aria-expanded="false">
                    <div class="ag-card-header">
                        <div class="ag-card-title-group">
                            <h3 class="ag-card-title"></h3>
                            <p class="ag-card-subtitle">
                                <i data-lucide="calendar-days"></i>
                                <span data-field="data"></span>
                            </p>
                            <p class="ag-card-value"></p>
                            <button type="button" class="card-toggle" data-toggle="details">
                                <span class="card-toggle-text">Ver detalhes</span>
                                <i data-lucide="chevron-down"></i>
                            </button>
                        </div>

                        <span class="ag-tipo-badge">
                            <i class="fas"></i>
                            <span data-field="tipo"></span>
                        </span>



                    </div>

                    <div class="ag-card-details">
                        <div class="ag-card-details-row">
                            <div class="ag-card-details-content">
                                <p class="ag-card-details-label">Categoria</p>
                                <p class="ag-card-details-value" data-field="categoria"></p>
                            </div>
                        </div>
                        <div class="ag-card-details-row">
                            <div class="ag-card-details-content">
                                <p class="ag-card-details-label">Conta</p>
                                <p class="ag-card-details-value" data-field="conta"></p>
                            </div>
                        </div>
                        <div class="ag-card-details-row">
                            <div class="ag-card-details-content">
                                <p class="ag-card-details-label">Recorrente</p>
                                <p class="ag-card-details-value" data-field="recorrente"></p>
                            </div>
                        </div>
                        <div class="ag-card-details-row" data-section="descricao">
                            <div class="ag-card-details-content">
                                <p class="ag-card-details-label">Descrição</p>
                                <p class="ag-card-details-value" data-field="descricao"></p>
                            </div>
                        </div>
                        <div class="ag-card-details-row">
                            <div class="ag-card-details-content">
                                <p class="ag-card-details-label">Status</p>
                                <p class="ag-card-details-value" data-field="status"></p>
                            </div>
                        </div>
                        <div class="ag-card-details-row ag-card-btn" data-section="acoes">
                            <div class="ag-card-details-content">
                                <p class="ag-card-details-label">Ações</p>
                                <div class="ag-card-actions"></div>
                            </div>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </div>

    <div id="agPaywall" class="paywall-message d-none" role="alert">
        <div class="paywall-content">
            <i data-lucide="crown"></i>
            <h3>Recurso Premium</h3>
            <p id="agPaywallMessage">Agendamentos são exclusivos do plano Pro.</p>
            <button type="button" class="btn-upgrade" id="agPaywallCta">
                Fazer Upgrade para PRO
            </button>
        </div>
    </div>




</section>

<!-- ==================== MODAL VISUALIZAÇÃO ==================== -->
<div class="modal fade" id="modalVisualizacao" tabindex="-1" aria-labelledby="modalVisualizacaoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-view-content">
            <div class="modal-header modal-view-header">
                <div class="view-header-content">
                    <i data-lucide="eye" class="view-icon"></i>
                    <div>
                        <h5 class="modal-title" id="modalVisualizacaoLabel">Detalhes do Agendamento</h5>
                        <p class="modal-subtitle" id="viewSubtitle">Visualização completa</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body modal-view-body">
                <div class="view-grid">
                    <!-- Informações Principais -->
                    <div class="view-section">
                        <h6 class="view-section-title">
                            <i data-lucide="info"></i> Informações Principais
                        </h6>
                        <div class="view-item">
                            <span class="view-label">Título:</span>
                            <span class="view-value" id="viewTitulo">-</span>
                        </div>
                        <div class="view-item">
                            <span class="view-label">Tipo:</span>
                            <span class="view-value" id="viewTipo">-</span>
                        </div>
                        <div class="view-item">
                            <span class="view-label">Valor:</span>
                            <span class="view-value view-value-destaque" id="viewValor">-</span>
                        </div>
                        <div class="view-item">
                            <span class="view-label">Status:</span>
                            <span class="view-value" id="viewStatus">-</span>
                        </div>
                    </div>

                    <!-- Classificação -->
                    <div class="view-section">
                        <h6 class="view-section-title">
                            <i data-lucide="tag"></i> Classificação
                        </h6>
                        <div class="view-item">
                            <span class="view-label">Categoria:</span>
                            <span class="view-value" id="viewCategoria">-</span>
                        </div>
                        <div class="view-item">
                            <span class="view-label">Conta:</span>
                            <span class="view-value" id="viewConta">-</span>
                        </div>
                        <div class="view-item" id="viewFormaPagamentoItem" style="display: none;">
                            <span class="view-label">Forma de Pagamento:</span>
                            <span class="view-value" id="viewFormaPagamento">-</span>
                        </div>
                    </div>

                    <!-- Datas e Prazos -->
                    <div class="view-section">
                        <h6 class="view-section-title">
                            <i data-lucide="calendar-days"></i> Datas e Prazos
                        </h6>
                        <div class="view-item">
                            <span class="view-label">Data Agendada:</span>
                            <span class="view-value" id="viewDataAgendada">-</span>
                        </div>
                        <div class="view-item" id="viewProximaExecucaoItem" style="display: none;">
                            <span class="view-label">Próxima Execução:</span>
                            <span class="view-value" id="viewProximaExecucao">-</span>
                        </div>
                        <div class="view-item" id="viewConcluidoEmItem" style="display: none;">
                            <span class="view-label">Última Execução:</span>
                            <span class="view-value" id="viewConcluidoEm">-</span>
                        </div>
                        <div class="view-item">
                            <span class="view-label">Criado em:</span>
                            <span class="view-value" id="viewCriadoEm">-</span>
                        </div>
                    </div>

                    <!-- Recorrência -->
                    <div class="view-section">
                        <h6 class="view-section-title">
                            <i data-lucide="refresh-cw"></i> Recorrência
                        </h6>
                        <div class="view-item">
                            <span class="view-label">É Recorrente:</span>
                            <span class="view-value" id="viewRecorrente">-</span>
                        </div>
                        <div class="view-item" id="viewRecorrenciaFreqItem" style="display: none;">
                            <span class="view-label">Frequência:</span>
                            <span class="view-value" id="viewRecorrenciaFreq">-</span>
                        </div>
                        <div class="view-item" id="viewRecorrenciaIntervaloItem" style="display: none;">
                            <span class="view-label">Intervalo:</span>
                            <span class="view-value" id="viewRecorrenciaIntervalo">-</span>
                        </div>
                    </div>

                    <!-- Notificações -->
                    <div class="view-section">
                        <h6 class="view-section-title">
                            <i data-lucide="bell"></i> Notificações
                        </h6>
                        <div class="view-item">
                            <span class="view-label">Canal E-mail:</span>
                            <span class="view-value" id="viewCanalEmail">-</span>
                        </div>
                        <div class="view-item">
                            <span class="view-label">Canal Sistema:</span>
                            <span class="view-value" id="viewCanalInapp">-</span>
                        </div>
                        <div class="view-item" id="viewNotificadoEmItem" style="display: none;">
                            <span class="view-label">Notificado em:</span>
                            <span class="view-value" id="viewNotificadoEm">-</span>
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div class="view-section view-section-full" id="viewDescricaoSection" style="display: none;">
                        <h6 class="view-section-title">
                            <i data-lucide="align-left"></i> Descrição
                        </h6>
                        <div class="view-description" id="viewDescricao">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-lucide="x"></i> Fechar
                </button>
                <button type="button" class="btn btn-primary" id="btnEditarFromView">
                    <i data-lucide="pencil"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Agendamento -->
<?php include BASE_PATH . '/views/admin/partials/modals/modal_agendamento.php'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/admin-agendamentos-index.js?v=<?= md5(uniqid(rand(), true)) ?>"></script>