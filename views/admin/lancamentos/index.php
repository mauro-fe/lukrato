<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">


<section class="lan-page">
    <div class="lan-header">
        <div class="lan-controls">
            <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

            <!-- Controles de Exportação -->
            <div class="export-range" data-aos="fade-left" aria-describedby="exportHint">
                <div class="mes">
                    <label class="sr-only" for="exportStart">Data Inicial</label>
                    de <input type="date" id="exportStart" class="lk-input date-range" placeholder="Início"
                        data-default-today="1">
                    à
                    <label class="sr-only" for="exportEnd">Data Final</label>
                    <input type="date" id="exportEnd" class="lk-input date-range" placeholder="Fim"
                        data-default-today="1">
                </div>

                <label class="sr-only" for="exportFormat">Formato de Exportação</label>
                <select id="exportFormat" class="lk-select btn btn-secondary" aria-label="Formato de exportação">
                    <option value="excel">Excel (.xlsx)</option>
                    <option value="pdf">PDF (.pdf)</option>
                </select>

                <button id="btnExportar" type="button" class="lk-btn primary btn" data-aos="fade-left"
                    data-aos-delay="150">
                    <i class="fas fa-file-export"></i> Exportar
                </button>

            </div>

            <!-- Filtros -->
            <div class="lan-card mt-4" data-aos="fade-up">
                <div class="lan-filter">
                    <div class="type-filter" role="group" aria-label="Filtros de lançamentos">
                        <label for="filtroTipo" class="sr-only">Filtrar por Tipo</label>
                        <select id="filtroTipo" class="lk-select btn btn-primary" data-aos="fade-right"
                            data-aos-delay="250">
                            <option value="">Todos os Tipos</option>
                            <option value="receita">Receitas</option>
                            <option value="despesa">Despesas</option>
                        </select>

                        <label for="filtroCategoria" class="sr-only">Filtrar por Categoria</label>
                        <select id="filtroCategoria" class="lk-select btn btn-primary" data-aos="fade-right">
                            <option value="">Todas as Categorias</option>
                            <option value="none">Sem Categoria</option>
                        </select>

                        <label for="filtroConta" class="sr-only">Filtrar por Conta</label>
                        <select id="filtroConta" class="lk-select btn btn-primary" data-aos="fade-up">
                            <option value="">Todas as Contas</option>
                        </select>

                        <button id="btnFiltrar" type="button" class="lk-btn ghost btn" data-aos="fade-left">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>

                        <button id="btnExcluirSel" type="button" class="lk-btn danger btn" data-aos="fade-left"
                            data-aos-delay="250" disabled>
                            <i class="fas fa-trash"></i> Excluir Selecionados
                        </button>

                        <small id="selInfo">
                            <i class="fas fa-check-square"></i>
                            <span id="selCount">0</span> selecionado(s)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Lançamentos -->
    <div class="container-table" data-aos="fade-up" data-aos-delay="250">
        <section class="table-container">
            <div id="tabLancamentos"></div>
        </section>
    </div>
</section>

<!-- Modal de Edição -->
<div class="modal fade" id="modalEditarLancamento" tabindex="-1" aria-labelledby="modalEditarLancamentoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarLancamentoLabel">Editar Lançamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body pt-0">
                <div id="editLancAlert" class="alert alert-danger d-none" role="alert"></div>

                <form id="formLancamento" novalidate>
                    <div class="mb-3">
                        <label for="editLancData" class="form-label">📅 Data</label>
                        <input type="date" class="form-control form-control-sm" id="editLancData" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editLancTipo" class="form-label">🏷️ Tipo</label>
                            <select class="form-select form-select-sm" id="editLancTipo" required>
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editLancConta" class="form-label">🏦 Conta</label>
                            <select class="form-select form-select-sm" id="editLancConta" required></select>
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label for="editLancCategoria" class="form-label">📂 Categoria</label>
                        <select class="form-select form-select-sm" id="editLancCategoria"></select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="editLancValor" class="form-label">💰 Valor</label>
                            <input type="number" class="form-control form-control-sm" id="editLancValor" step="0.01"
                                min="0" required>
                        </div>
                        <div class="col-md-9">
                            <label for="editLancDescricao" class="form-label">📝 Descrição</label>
                            <input type="text" class="form-control form-control-sm" id="editLancDescricao"
                                maxlength="190">
                        </div>
                    </div>
                </form>
            </div>

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