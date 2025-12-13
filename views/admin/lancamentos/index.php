<!-- Tabulator -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">

<!-- CSS REFATORADO -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-tables-shared.css">

<section class="lan-page">
    <!-- ==================== HEADER ==================== -->
    <div class="lan-header">
        <div class="lan-controls">
            <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

            <!-- Exportação -->
            <div class="export-range" data-aos="fade-left" aria-describedby="exportHint">
                <label class="export-label" for="exportType">
                    <i class="fas fa-file-export"></i> Exportar Lançamentos
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
                        <select id="exportFormat" class="lk-select btn btn-primary" aria-label="Formato">
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
            <div class="lan-card ft-card mt-4" data-aos="fade-up">
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
    <div class="container-table" data-aos="fade-up">
        <div class="lan-table-container">
            <!-- DESKTOP: Tabela Tabulator -->
            <section class="table-container tab-desktop">
                <div id="lancamentosTable"></div>
            </section>

            <!-- MOBILE: Cards + pager -->
            <section class="lan-cards-wrapper cards-wrapper">
                <!-- Cards -->
                <section class="lan-cards-container cards-container" id="lanCards"></section>

                <!-- Pager -->
                <nav class="lan-cards-pager cards-pager" id="lanCardsPager" aria-label="Paginação de lançamentos">
                    <button type="button" id="lanPagerFirst" class="lan-pager-btn pager-btn" disabled
                        aria-label="Primeira página">
                        <i class="fas fa-angle-double-left"></i>
                    </button>

                    <button type="button" id="lanPagerPrev" class="lan-pager-btn pager-btn" disabled
                        aria-label="Página anterior">
                        <i class="fas fa-chevron-left"></i>
                    </button>

                    <span id="lanPagerInfo" class="lan-pager-info pager-info">Nenhum lançamento</span>

                    <button type="button" id="lanPagerNext" class="lan-pager-btn pager-btn" disabled
                        aria-label="Próxima página">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <button type="button" id="lanPagerLast" class="lan-pager-btn pager-btn" disabled
                        aria-label="Última página">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </nav>
            </section>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../partials/modals/editar-lancamentos.php'; ?>



<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>