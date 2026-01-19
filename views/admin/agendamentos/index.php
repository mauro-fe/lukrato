<!-- CSS Agendamentos -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-agendamentos-index.css">


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
                        <label for="filtroCategoria" class="filter-label">
                            <i class="fas fa-folder"></i>
                            <span>Categoria</span>
                        </label>
                        <select id="filtroCategoria" class="modern-select" aria-label="Filtrar por categoria">
                            <option value="">Todas as Categorias</option>
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

                    <div class="filter-group">
                        <label for="filtroStatus" class="filter-label">
                            <i class="fas fa-info-circle"></i>
                            <span>Status</span>
                        </label>
                        <select id="filtroStatus" class="modern-select" aria-label="Filtrar por status">
                            <option value="">Todos</option>
                            <option value="pendente">⏱️ Pendente</option>
                            <option value="concluido">✅ Concluído</option>
                            <option value="cancelado">❌ Cancelado</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="button" id="btnLimparFiltros" class="modern-btn primary" aria-label="Limpar filtros">
                        <i class="fas fa-eraser"></i>
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
                <i class="fas fa-calendar-day"></i>
                <span>Hoje</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="semana">
                <i class="fas fa-calendar-week"></i>
                <span>Esta Semana</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="vencidos">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Vencidos</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="receitas">
                <i class="fas fa-arrow-down"></i>
                <span>Receitas</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="despesas">
                <i class="fas fa-arrow-up"></i>
                <span>Despesas</span>
            </button>
            <button type="button" class="quick-filter-btn" data-filter="recorrentes">
                <i class="fas fa-sync-alt"></i>
                <span>Recorrentes</span>
            </button>
        </div>

        <!-- Header com título e botão de ação -->
        <div class="modern-table-wrapper" style="margin-bottom: var(--spacing-4);">
            <div class="table-header-info">
                <div class="info-group">
                    <i class="fas fa-clock"></i>
                    <span>Seus Agendamentos</span>
                </div>
                <div class="table-actions">
                    <button type="button" id="btnAddAgendamento" class="modern-btn primary"
                        aria-label="Novo agendamento">
                        <i class="fas fa-plus"></i>
                        <span>Novo Agendamento</span>
                    </button>
                </div>
            </div>
        </div>

        <section class="table-container ag-table-desktop tab-desktop">
            <div class="table-wrapper">
                <table class="ag-table" id="agendamentosTable">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Conta</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="agendamentosTableBody">
                        <tr>
                            <td colspan="8" class="text-center">Carregando agendamentos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="ag-cards-wrapper cards-wrapper">
            <div class="ag-cards-container cards-container" id="agCards"></div>

            <nav class="ag-cards-pager cards-pager" id="agCardsPager">
                <button type="button" id="agPagerFirst" class="ag-pager-btn pager-btn" disabled>
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button type="button" id="agPagerPrev" class="ag-pager-btn pager-btn" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span id="agPagerInfo" class="ag-pager-info pager-info">Nenhum agendamento</span>
                <button type="button" id="agPagerNext" class="ag-pager-btn pager-btn" disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button type="button" id="agPagerLast" class="ag-pager-btn pager-btn" disabled>
                    <i class="fas fa-angle-double-right"></i>
                </button>
            </nav>
        </section>

        <template id="agCardTemplate">
            <article class="ag-card card-item" aria-expanded="false">
                <div class="ag-card-header">
                    <div class="ag-card-title-group">
                        <h3 class="ag-card-title"></h3>
                        <p class="ag-card-subtitle">
                            <i class="fas fa-calendar-alt"></i>
                            <span data-field="data"></span>
                        </p>
                        <p class="ag-card-value"></p>
                        <button type="button" class="card-toggle" data-toggle="details">
                            <span class="card-toggle-text">Ver detalhes</span>
                            <i class="fas fa-chevron-down"></i>
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

    <div id="agPaywall" class="paywall-message d-none" role="alert">
        <div class="paywall-content">
            <i class="fas fa-crown"></i>
            <h3>Recurso Premium</h3>
            <p id="agPaywallMessage">Agendamentos são exclusivos do plano Pro.</p>
            <button type="button" class="btn-upgrade" id="agPaywallCta">
                <i class="fas fa-crown"></i> Fazer Upgrade para PRO
            </button>
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

<!-- ==================== MODAL VISUALIZAÇÃO ==================== -->
<div class="modal fade" id="modalVisualizacao" tabindex="-1" aria-labelledby="modalVisualizacaoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-view-content">
            <div class="modal-header modal-view-header">
                <div class="view-header-content">
                    <i class="fas fa-eye view-icon"></i>
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
                            <i class="fas fa-info-circle"></i> Informações Principais
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
                            <i class="fas fa-tag"></i> Classificação
                        </h6>
                        <div class="view-item">
                            <span class="view-label">Categoria:</span>
                            <span class="view-value" id="viewCategoria">-</span>
                        </div>
                        <div class="view-item">
                            <span class="view-label">Conta:</span>
                            <span class="view-value" id="viewConta">-</span>
                        </div>
                    </div>

                    <!-- Datas e Prazos -->
                    <div class="view-section">
                        <h6 class="view-section-title">
                            <i class="fas fa-calendar-alt"></i> Datas e Prazos
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
                            <i class="fas fa-sync-alt"></i> Recorrência
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
                            <i class="fas fa-bell"></i> Notificações
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
                            <i class="fas fa-align-left"></i> Descrição
                        </h6>
                        <div class="view-description" id="viewDescricao">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fechar
                </button>
                <button type="button" class="btn btn-primary" id="btnEditarFromView">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL AGENDAMENTO ==================== -->
<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-labelledby="modalAgendamentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgendamentoLabel">Novo Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="formAgendamento" novalidate>
                    <input type="hidden" id="agId" name="id">

                    <!-- Tipo -->
                    <div class="mb-3">
                        <label for="agTipo" class="form-label">
                            <i class="fas fa-tag"></i> Tipo *
                        </label>
                        <select id="agTipo" name="tipo" class="form-select" required>
                            <option value="despesa">Despesa</option>
                            <option value="receita">Receita</option>
                        </select>
                    </div>

                    <!-- Título -->
                    <div class="mb-3">
                        <label for="agTitulo" class="form-label">
                            <i class="fas fa-heading"></i> Título *
                        </label>
                        <input type="text" id="agTitulo" name="titulo" class="form-control"
                            placeholder="Ex: Aluguel, Salário..." required maxlength="100">
                    </div>

                    <!-- Categoria -->
                    <div class="mb-3">
                        <label for="agCategoria" class="form-label">
                            <i class="fas fa-folder"></i> Categoria *
                        </label>
                        <select id="agCategoria" name="categoria_id" class="form-select" required>
                            <option value="">Selecione...</option>
                        </select>
                    </div>

                    <!-- Conta -->
                    <div class="mb-3">
                        <label for="agConta" class="form-label">
                            <i class="fas fa-wallet"></i> Conta *
                        </label>
                        <select id="agConta" name="conta_id" class="form-select" required>
                            <option value="">Selecione...</option>
                        </select>
                    </div>

                    <!-- Valor -->
                    <div class="mb-3">
                        <label for="agValor" class="form-label">
                            <i class="fas fa-dollar-sign"></i> Valor *
                        </label>
                        <input type="text" id="agValor" name="valor" class="form-control" placeholder="R$ 0,00"
                            required>
                    </div>

                    <!-- Data de Pagamento -->
                    <div class="mb-3">
                        <label for="agDataPagamento" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Data de Execução *
                        </label>
                        <input type="datetime-local" id="agDataPagamento" name="data_pagamento" class="form-control"
                            required>
                    </div>

                    <!-- Recorrente -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-sync-alt"></i> Recorrência
                        </label>
                        <input type="checkbox" id="agRecorrente" name="recorrente" value="1" hidden>
                        <button type="button" class="toggle-btn" id="btnToggleRecorrente" data-target="agRecorrente">
                            <i class="fas fa-calendar-check"></i>
                            <span class="toggle-text">Não, agendamento único</span>
                        </button>
                    </div>

                    <!-- Lembrar -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-bell"></i> Canais de notificação
                        </label>
                        <input type="checkbox" id="agLembrar" name="lembrar" value="1" hidden>
                        <div class="notification-toggles">
                            <button type="button" class="toggle-btn notification-toggle active" id="btnToggleSistema"
                                data-notification="sistema">
                                <i class="fas fa-desktop"></i>
                                <span class="toggle-text">Aviso no sistema</span>
                            </button>
                            <button type="button" class="toggle-btn notification-toggle active" id="btnToggleEmail"
                                data-notification="email">
                                <i class="fas fa-envelope"></i>
                                <span class="toggle-text">E-mail</span>
                            </button>
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div class="mb-3">
                        <label for="agDescricao" class="form-label">
                            <i class="fas fa-align-left"></i> Descrição
                        </label>
                        <textarea id="agDescricao" name="descricao" class="form-control" rows="3"
                            placeholder="Informações adicionais..." maxlength="500"></textarea>
                        <small class="text-muted">Opcional</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" form="formAgendamento" class="btn btn-primary" id="btnSubmitAgendamento">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/admin-agendamentos-index.js"></script>