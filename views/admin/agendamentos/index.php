<!-- CSS Agendamentos -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/lancamentos-modern.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-agendamentos-index.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-tables-shared.css">

<!-- Estilos para toggle de detalhes nos cards mobile -->
<style>
    /* Estado fechado dos detalhes */
    #agCards .ag-card .ag-card-details,
    #agCards .card-item .ag-card-details {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        padding: 0 1rem;
        transition: all 0.3s ease;
    }

    /* Estado aberto dos detalhes */
    #agCards .ag-card .ag-card-details.show,
    #agCards .card-item .ag-card-details.show,
    #agCards .ag-card[aria-expanded="true"] .ag-card-details,
    #agCards .card-item[aria-expanded="true"] .ag-card-details {
        max-height: none !important;
        height: auto !important;
        opacity: 1 !important;
        padding: 1rem !important;
        overflow: visible !important;
        visibility: visible !important;
        background: var(--color-surface-muted, #1e3a5f);
        border-radius: 0 0 var(--radius-lg, 12px) var(--radius-lg, 12px);
    }

    /* Garantir que cards não escondem conteúdo */
    #agCards,
    #agCards .ag-card,
    #agCards .card-item,
    .ag-cards-wrapper,
    .ag-cards-container {
        overflow: visible !important;
    }
</style>

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
                    <h3 class="card-title">Filtros</h3>
                    <p class="card-subtitle">Refine sua busca</p>
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
                    <button type="button" id="btnLimparFiltros" class="modern-btn ghost" aria-label="Limpar filtros">
                        <i class="fas fa-eraser"></i>
                        <span>Limpar Filtros</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TABELA/CARDS ==================== -->
    <div class="ag-table-container" id="agList">
        <!-- TABELA DESKTOP -->
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

        <!-- CARDS MOBILE -->
        <section class="ag-cards-wrapper cards-wrapper">
            <section class="ag-cards-container cards-container" id="agCards"></section>

            <!-- PAGINAÇÃO MOBILE -->
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

    <!-- ==================== BOTÃO FLUTUANTE ==================== -->
    <button type="button" id="btnAddAgendamento" class="btn-float" title="Novo agendamento" aria-label="Adicionar agendamento">
        <i class="fas fa-plus"></i>
    </button>
</section>

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
                        <input type="text" id="agValor" name="valor" class="form-control"
                            placeholder="R$ 0,00" required>
                    </div>

                    <!-- Data de Pagamento -->
                    <div class="mb-3">
                        <label for="agDataPagamento" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Data de Execução *
                        </label>
                        <input type="datetime-local" id="agDataPagamento" name="data_pagamento"
                            class="form-control" required>
                    </div>

                    <!-- Recorrente -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="agRecorrente" name="recorrente" value="1">
                            <label class="form-check-label" for="agRecorrente">
                                <i class="fas fa-sync-alt"></i> Agendamento Recorrente
                            </label>
                        </div>
                        <small class="text-muted">Será executado automaticamente todo mês nesta data</small>
                    </div>

                    <!-- Lembrar -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="agLembrar" name="lembrar" value="1">
                            <label class="form-check-label" for="agLembrar">
                                <i class="fas fa-bell"></i> Enviar Notificação
                            </label>
                        </div>
                        <small class="text-muted">Receba um lembrete antes da execução</small>
                    </div>

                    <!-- Descrição -->
                    <div class="mb-3">
                        <label for="agDescricao" class="form-label">
                            <i class="fas fa-align-left"></i> Descrição
                        </label>
                        <textarea id="agDescricao" name="descricao" class="form-control"
                            rows="3" placeholder="Informações adicionais..." maxlength="500"></textarea>
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