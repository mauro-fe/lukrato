<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>


<section class="lan-page">
    <!-- ==================== HEADER ==================== -->
    <div class="lan-header">
        <div class="lan-controls">
            <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

            <!-- Exportação -->
            <div class="export-range" data-aos="fade-left" aria-describedby="exportHint">
                <label class="export-label" for="exportType">
                    <i class="fas fa-file-export"></i>
                    Exportar Lançamentos
                </label>

                <div class="export-content">
                    <div class="mes">
                        <div class="date-group">

                            <label class="sr-only" for="exportStart">Data Inicial</label>
                            <span>De</span>
                            <input type="date" id="exportStart" class="lk-input lk-btn date-range" placeholder="Início"
                                data-default-today="1" aria-label="Data inicial">
                        </div>

                        <div class="date-group">

                            <label class="sr-only" for="exportEnd">Data Final</label>
                            <span>Até</span>
                            <input type="date" id="exportEnd" class="lk-input lk-btn date-range" placeholder="Fim"
                                data-default-today="1" aria-label="Data final">
                        </div>
                    </div>

                    <div class="export-actions">
                        <label class="sr-only" for="exportFormat">Formato</label>
                        <select id="exportFormat" class="lk-select lk-btn btn btn-secondary" aria-label="Formato">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel (.xlsx)</option>
                        </select>

                        <button id="btnExportar" type="button" class="lk-btn btn btn-primary" data-aos="fade-left"
                            data-aos-delay="150" aria-label="Exportar">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>



            <!-- Filtros -->
            <div class="lan-card mt-4" data-aos="fade-up">
                <div class="lan-filter">

                    <div class="type-filter" role="group" aria-label="Filtros de lançamentos">
                        <div class="mobile-filter-heading d-md-none" aria-hidden="true">
                            <i class="fas fa-filter"></i>
                            <span>Filtrar Lançamentos</span>
                        </div>

                        <!-- Tipo -->
                        <label for="filtroTipo" class="sr-only">Filtrar por Tipo</label>
                        <select id="filtroTipo" class="lk-select btn btn-primary" data-aos="fade-right"
                            data-aos-delay="250" aria-label="Filtrar por tipo de lançamento">
                            <option value="">Todos os Tipos</option>
                            <option value="receita">Receitas</option>
                            <option value="despesa">Despesas</option>
                        </select>

                        <!-- Categoria -->
                        <label for="filtroCategoria" class="sr-only">Filtrar por Categoria</label>
                        <select id="filtroCategoria" class="lk-select btn btn-primary" data-aos="fade-right"
                            aria-label="Filtrar por categoria">
                            <option value="">Todas as Categorias</option>
                            <option value="none">Sem Categoria</option>
                        </select>

                        <!-- Conta -->
                        <label for="filtroConta" class="sr-only">Filtrar por Conta</label>
                        <select id="filtroConta" class="lk-select btn btn-primary" data-aos="fade-up"
                            aria-label="Filtrar por conta">
                            <option value="">Todas as Contas</option>
                        </select>

                        <!-- Ações -->
                        <button id="btnFiltrar" type="button" class="lk-btn btn btn-primary" data-aos="fade-left"
                            aria-label="Aplicar filtros">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>

                        <button id="btnExcluirSel" type="button" class="lk-btn danger btn" data-aos="fade-left"
                            data-aos-delay="250" disabled aria-label="Excluir lançamentos selecionados">
                            <i class="fas fa-trash"></i> Excluir Selecionados
                        </button>

                        <!-- Info de seleção -->
                        <small id="selInfo" aria-live="polite">
                            <i class="fas fa-check-square"></i>
                            <span id="selCount">0</span> selecionado(s)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TABELA ==================== -->
    <div class="container-table" data-aos="fade-up" data-aos-delay="250">
        <section class="table-container">
            <div id="tabLancamentos"></div>
        </section>
        <div>
            <!-- DESKTOP: Tabela Tabulator -->
            <section class="table-container">
                <div id="tabLancamentos"></div>
            </section>

            <!-- MOBILE: Cards de lançamentos -->
            <section class="lan-cards-container" id="lanCards"></section>
        </div>

    </div>
</section>

<!-- ==================== MODAL DE EDIÇÃO ==================== -->
<div class="modal fade" id="modalEditarLancamento" tabindex="-1" aria-labelledby="modalEditarLancamentoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarLancamentoLabel">Editar Lançamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar modal">
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body pt-0">
                <div id="editLancAlert" class="alert alert-danger d-none" role="alert">
                </div>

                <form id="formLancamento" novalidate>
                    <!-- Data -->
                    <div class="mb-3">
                        <label for="editLancData" class="form-label">📅 Data</label>
                        <input type="date" class="form-control form-control-sm" id="editLancData" required
                            aria-required="true">
                    </div>

                    <div class="row g-3">
                        <!-- Tipo -->
                        <div class="col-md-6">
                            <label for="editLancTipo" class="form-label">🏷️ Tipo</label>
                            <select class="form-select form-select-sm" id="editLancTipo" required aria-required="true">
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>

                        <!-- Conta -->
                        <div class="col-md-6">
                            <label for="editLancConta" class="form-label">🏦 Conta</label>
                            <select class="form-select form-select-sm" id="editLancConta" required aria-required="true">
                            </select>
                        </div>
                    </div>

                    <!-- Categoria -->
                    <div class="mb-3 mt-3">
                        <label for="editLancCategoria" class="form-label">📂 Categoria</label>
                        <select class="form-select form-select-sm" id="editLancCategoria">
                        </select>
                    </div>

                    <div class="row g-3">
                        <!-- Valor -->
                        <div class="col-md-3">
                            <label for="editLancValor" class="form-label">💰 Valor</label>
                            <input type="number" class="form-control form-control-sm" id="editLancValor" step="0.01"
                                min="0" required aria-required="true">
                        </div>

                        <!-- Descrição -->
                        <div class="col-md-9">
                            <label for="editLancDescricao" class="form-label">📝 Descrição</label>
                            <input type="text" class="form-control form-control-sm" id="editLancDescricao"
                                maxlength="190">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary btn-sm" form="formLancamento">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>