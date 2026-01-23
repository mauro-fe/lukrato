<!-- Tabulator -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">

<!-- CSS MODERNIZADO -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-tables-shared.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/lancamentos-modern.css">

<?php $isPro = $isPro ?? false; ?>

<section class="lan-page">
    <!-- ==================== HEADER MODERNIZADO ==================== -->
    <div class="lan-header-modern">
        <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

        <!-- CARD DE EXPORTAÇÃO -->
        <div class="modern-card export-card <?= !$isPro ? 'pro-locked' : '' ?>" data-aos="fade-up" data-aos-delay="100">
            <div class="card-header-icon">
                <div class="icon-wrapper export">
                    <i class="fas fa-file-export"></i>
                </div>
                <div class="card-title-group">
                    <h3 class="card-title">Exportar Lançamentos</h3>
                    <p class="card-subtitle">Exporte seus dados em PDF ou Excel</p>
                </div>
                <?php if (!$isPro): ?>
                    <span class="pro-badge">
                        <i class="fas fa-crown"></i> PRO
                    </span>
                <?php endif; ?>
            </div>

            <div class="export-card-body">
                <?php if (!$isPro): ?>
                    <div class="pro-overlay">
                        <div class="pro-message">
                            <i class="fas fa-crown"
                                style="font-size:2.5rem;color:var(--color-warning);margin-bottom:var(--spacing-4);"></i>
                            <h4 style="color:#fff;font-size:1.25rem;font-weight:700;margin:0 0 var(--spacing-2);">Recurso
                                Premium</h4>
                            <p
                                style="color:rgba(255,255,255,0.8);font-size:0.9rem;margin:0 0 var(--spacing-4);line-height:1.5;">
                                Exportação de lançamentos é exclusiva do <a href="<?= BASE_URL ?>billing">
                                    <i class="fas fa-crown"></i> plano Pro.
                                </a></p>

                        </div>
                    </div>
                <?php endif; ?>
                <div class="export-controls <?= !$isPro ? 'disabled-blur' : '' ?>">
                    <div class="date-range-group">
                        <div class="input-group">
                            <label for="exportStart" class="input-label">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Data Inicial</span>
                            </label>
                            <input type="date" id="exportStart" class="modern-input" data-default-today="1"
                                aria-label="Data inicial" <?= !$isPro ? 'disabled' : '' ?>>
                        </div>

                        <div class="input-group">
                            <label for="exportEnd" class="input-label">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Data Final</span>
                            </label>
                            <input type="date" id="exportEnd" class="modern-input" data-default-today="1"
                                aria-label="Data final" <?= !$isPro ? 'disabled' : '' ?>>
                        </div>
                    </div>

                    <div class="export-actions-group">
                        <select id="exportFormat" class="modern-select" aria-label="Formato de exportação"
                            <?= !$isPro ? 'disabled' : '' ?>>
                            <option value="pdf">📄 PDF</option>
                            <option value="excel">📊 Excel (.xlsx)</option>
                        </select>

                        <button id="btnExportar" type="button" class="modern-btn primary"
                            aria-label="Exportar lançamentos" <?= !$isPro ? 'disabled' : '' ?>>
                            <i class="fas fa-download"></i>
                            <span>Exportar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD DE FILTROS -->
        <div class="modern-card filter-card" data-aos="fade-up" data-aos-delay="200">
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
    <div class="modern-table-wrapper" data-aos="fade-up" data-aos-delay="300">
        <div class="table-header-info">
            <div class="info-group">
                <i class="fas fa-list-ul"></i>
                <span>Seus Lançamentos</span>
            </div>
            <div class="table-actions">
                <button type="button" class="modern-btn" onclick="lancamentoGlobalManager.openModal()"
                    style="background: var(--color-primary); color: white;" aria-label="Novo lançamento">
                    <i class="fas fa-plus"></i>
                    <span>Novo Lançamento</span>
                </button>

                <button id="btnExcluirSel" type="button" class="modern-btn delete" disabled
                    aria-label="Excluir registros selecionados">
                    <i class="fas fa-trash-alt"></i>
                    <span>Excluir (<span id="selCount">0</span>)</span>
                </button>

                <button type="button" class="icon-btn" title="Atualizar" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

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