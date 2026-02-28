<!-- CSS MODERNIZADO -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/lancamentos-modern.css.php?v=<?= time() ?>">

<?php $isPro = $isPro ?? false; ?>

<section class="lan-page">
    <!-- ==================== HEADER MODERNIZADO ==================== -->
    <div class="lan-header-modern">
        <?php include BASE_PATH . '/views/admin/partials/header-mes.php'; ?>

        <!-- CARD DE EXPORTAÇÃO -->
        <div class="modern-card export-card <?= !$isPro ? 'pro-locked' : '' ?>" data-aos="fade-up" data-aos-delay="100">
            <div class="card-header-icon">
                <div class="icon-wrapper export">
                    <i data-lucide="file-output" style="color: var(--color-primary)"></i>
                </div>
                <div class="card-title-group">
                    <h3 class="card-title">Exportar Lançamentos</h3>
                    <p class="card-subtitle">Exporte seus dados em PDF ou Excel</p>
                </div>
                <?php if (!$isPro): ?>
                    <span class="pro-badge">
                        <i data-lucide="crown"></i> PRO
                    </span>
                <?php endif; ?>
            </div>

            <div class="export-card-body">
                <?php if (!$isPro): ?>
                    <div class="pro-overlay">
                        <div class="pro-message">
                            <i data-lucide="crown"
                                style="font-size:2.5rem;color:var(--color-warning);margin-bottom:var(--spacing-4);"></i>
                            <h4 style="color:#fff;font-size:1.25rem;font-weight:700;margin:0 0 var(--spacing-2);">Recurso
                                Premium</h4>
                            <p
                                style="color:rgba(255,255,255,0.8);font-size:0.9rem;margin:0 0 var(--spacing-4);line-height:1.5;">
                                Exportação de lançamentos é exclusiva do <a href="<?= BASE_URL ?>billing">
                                    plano Pro.
                                </a></p>

                        </div>
                    </div>
                <?php endif; ?>
                <div class="export-controls <?= !$isPro ? 'disabled-blur' : '' ?>">
                    <div class="date-range-group">
                        <div class="input-group">
                            <label for="exportStart" class="input-label">
                                <i data-lucide="calendar-days" style="color: var(--color-primary)"></i>
                                <span>Data Inicial</span>
                            </label>
                            <input type="date" id="exportStart" class="modern-input" data-default-today="1"
                                aria-label="Data inicial" <?= !$isPro ? 'disabled' : '' ?>>
                        </div>

                        <div class="input-group">
                            <label for="exportEnd" class="input-label">
                                <i data-lucide="calendar-days" style="color: var(--color-primary)"></i>
                                <span>Data Final</span>
                            </label>
                            <input type="date" id="exportEnd" class="modern-input" data-default-today="1"
                                aria-label="Data final" <?= !$isPro ? 'disabled' : '' ?>>
                        </div>
                    </div>

                    <div class="export-filters-row">
                        <div class="export-filter-item">
                            <label for="exportConta" class="export-filter-label">Conta</label>
                            <select id="exportConta" class="modern-select export-select"
                                <?= !$isPro ? 'disabled' : '' ?>>
                                <option value="">Todas</option>
                            </select>
                        </div>
                        <div class="export-filter-item">
                            <label for="exportCategoria" class="export-filter-label">Categoria</label>
                            <select id="exportCategoria" class="modern-select export-select"
                                <?= !$isPro ? 'disabled' : '' ?>>
                                <option value="">Todas</option>
                            </select>
                        </div>
                        <div class="export-filter-item">
                            <label for="exportTipo" class="export-filter-label">Tipo</label>
                            <select id="exportTipo" class="modern-select export-select"
                                <?= !$isPro ? 'disabled' : '' ?>>
                                <option value="">Todos</option>
                                <option value="receita">Receitas</option>
                                <option value="despesa">Despesas</option>
                            </select>
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
                            <i data-lucide="download"></i>
                            <span>Exportar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- BARRA DE FILTROS INLINE -->
        <div class="lk-filters-bar" data-aos="fade-up" data-aos-delay="200">
            <div class="lk-filters-row">
                <div class="lk-filter-search">
                    <i data-lucide="search" class="lk-filter-search-icon"></i>
                    <input type="text" id="filtroTexto" class="lk-filter-search-input"
                        placeholder="Buscar por descrição..." aria-label="Buscar lançamentos">
                </div>

                <div class="lk-filter-group">
                    <div class="lk-filter-chip-select">
                        <i data-lucide="tag"></i>
                        <select id="filtroTipo" class="lk-filter-native" aria-label="Filtrar por tipo">
                            <option value="">Tipo</option>
                            <option value="receita">Receitas</option>
                            <option value="despesa">Despesas</option>
                        </select>
                    </div>

                    <div class="lk-filter-chip-select">
                        <i data-lucide="folder"></i>
                        <select id="filtroCategoria" class="lk-filter-native" aria-label="Filtrar por categoria">
                            <option value="">Categoria</option>
                            <option value="none">Sem Categoria</option>
                        </select>
                    </div>

                    <div class="lk-filter-chip-select">
                        <i data-lucide="wallet"></i>
                        <select id="filtroConta" class="lk-filter-native" aria-label="Filtrar por conta">
                            <option value="">Conta</option>
                        </select>
                    </div>

                    <div class="lk-filter-chip-select">
                        <i data-lucide="circle-check"></i>
                        <select id="filtroStatus" class="lk-filter-native" aria-label="Filtrar por status">
                            <option value="">Status</option>
                            <option value="pago">Pagos</option>
                            <option value="pendente">Pendentes</option>
                        </select>
                    </div>
                </div>

                <button id="btnLimparFiltros" type="button" class="lk-filter-clear-btn" aria-label="Limpar filtros"
                    title="Limpar filtros">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <!-- Active filter badges -->
            <div class="lk-active-filters" id="activeFilterBadges" style="display: none;">
            </div>
        </div>
    </div>

    <!-- ==================== TABELA MODERNIZADA ==================== -->
    <div class="modern-table-wrapper" data-aos="fade-up" data-aos-delay="300">
        <div class="table-header-info">
            <div class="info-group">
                <i data-lucide="list"></i>
                <span>Seus Lançamentos</span>
            </div>
            <div class="table-actions">
                <button type="button" class="modern-btn" onclick="lancamentoGlobalManager.openModal()"
                    style="background: var(--color-primary); color: white;" aria-label="Novo lançamento">
                    <i data-lucide="plus"></i>
                    <span>Novo Lançamento</span>
                </button>

                <button id="btnExcluirSel" type="button" class="modern-btn delete" disabled
                    aria-label="Excluir registros selecionados">
                    <i data-lucide="trash-2"></i>
                    <span>Excluir (<span id="selCount">0</span>)</span>
                </button>

                <button type="button" class="icon-btn" title="Atualizar" onclick="location.reload()">
                    <i data-lucide="refresh-cw"></i>
                </button>
            </div>
        </div>

        <div class="lan-table-container">
            <!-- DESKTOP: Tabela HTML Pura -->
            <section class="table-container tab-desktop">
                <div class="lk-table-wrapper">
                    <table class="lk-table" id="lancamentosTable">
                        <thead>
                            <tr>
                                <th class="th-checkbox">
                                    <input type="checkbox" id="selectAllLancamentos" class="lk-checkbox"
                                        title="Selecionar todos">
                                </th>
                                <th class="th-expand"></th>
                                <th class="th-data sortable" data-sort="data">
                                    <span>Data</span>
                                    <i data-lucide="arrow-up-down" class="sort-icon"></i>
                                </th>
                                <th class="th-tipo sortable" data-sort="tipo">
                                    <span>Tipo</span>
                                    <i data-lucide="arrow-up-down" class="sort-icon"></i>
                                </th>
                                <th class="th-descricao">Descrição</th>
                                <th class="th-categoria">Categoria</th>
                                <th class="th-valor sortable" data-sort="valor">
                                    <span>Valor</span>
                                    <i data-lucide="arrow-up-down" class="sort-icon"></i>
                                </th>
                                <th class="th-tags">Info</th>
                                <th class="th-pago-em">Pago Em</th>
                                <th class="th-acoes">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lancamentosTableBody">
                            <!-- Loading state inicial -->
                            <tr class="lk-loading-row">
                                <td colspan="10" style="text-align:center; padding:3rem 1rem;">
                                    <div class="lk-loading-state">
                                        <div class="spinner-border" role="status"
                                            style="width:2rem;height:2rem;color:var(--color-primary);">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        <p style="margin:1rem 0 0;color:var(--color-text-muted);font-size:0.9rem;">
                                            Carregando lançamentos...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação Desktop -->
                <div class="lk-pagination" id="desktopPagination">
                    <div class="pagination-info">
                        <span id="paginationInfo">0 lançamentos</span>
                    </div>
                    <div class="pagination-controls">
                        <select id="pageSize" class="page-size-select">
                            <option value="10" selected>10 por página</option>
                            <option value="25">25 por página</option>
                            <option value="50">50 por página</option>
                            <option value="100">100 por página</option>
                        </select>
                        <button type="button" id="prevPage" class="pagination-btn" disabled>
                            <i data-lucide="chevron-left"></i>
                        </button>
                        <span id="pageNumbers" class="page-numbers"></span>
                        <button type="button" id="nextPage" class="pagination-btn" disabled>
                            <i data-lucide="chevron-right"></i>
                        </button>
                    </div>
                </div>
            </section>

            <!-- MOBILE: Cards + pager -->
            <section class="lan-cards-wrapper cards-wrapper">
                <!-- Cards -->
                <section class="lan-cards-container cards-container" id="lanCards">
                    <!-- Loading state mobile -->
                    <div class="lk-loading-state" id="lanCardsLoading"
                        style="text-align:center;padding:2rem 1rem;grid-column:1/-1;">
                        <div class="spinner-border" role="status"
                            style="width:2rem;height:2rem;color:var(--color-primary);">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p style="margin:1rem 0 0;color:var(--color-text-muted);font-size:0.9rem;">Carregando
                            lançamentos...</p>
                    </div>
                </section>

                <!-- Pager -->
                <nav class="lan-cards-pager cards-pager" id="lanCardsPager" aria-label="Paginação de lançamentos">
                    <button type="button" id="lanPagerFirst" class="lan-pager-btn pager-btn" disabled
                        aria-label="Primeira página">
                        <i data-lucide="chevrons-left"></i>
                    </button>

                    <button type="button" id="lanPagerPrev" class="lan-pager-btn pager-btn" disabled
                        aria-label="Página anterior">
                        <i data-lucide="chevron-left"></i>
                    </button>

                    <span id="lanPagerInfo" class="lan-pager-info pager-info">Nenhum lançamento</span>

                    <button type="button" id="lanPagerNext" class="lan-pager-btn pager-btn" disabled
                        aria-label="Próxima página">
                        <i data-lucide="chevron-right"></i>
                    </button>

                    <button type="button" id="lanPagerLast" class="lan-pager-btn pager-btn" disabled
                        aria-label="Última página">
                        <i data-lucide="chevrons-right"></i>
                    </button>
                </nav>
            </section>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../partials/modals/editar-lancamentos.php'; ?>
<?php include __DIR__ . '/../partials/modals/visualizar-lancamento.php'; ?>
<?php include __DIR__ . '/../partials/modals/editar-transferencia.php'; ?>