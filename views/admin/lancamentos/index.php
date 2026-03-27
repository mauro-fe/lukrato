<!-- CSS Lancamentos — carregado via Vite (import no JS entry) -->

<?php $isPro = $isPro ?? false; ?>

<section class="lan-page">

    <!-- ─── HERO ──────────────────────────────────────────────────────── -->
    <section class="lan-hero" data-aos="fade-up" data-aos-delay="50" aria-label="Resumo da pagina de lancamentos">
        <div class="lan-hero-copy">
            <span class="lan-hero-eyebrow">Fluxo financeiro</span>
            <h1 class="lan-hero-title">Lancamentos</h1>
            <p class="lan-hero-subtitle" id="lanHeroSubtitle">Veja receitas, despesas e pendencias com leitura mais
                rapida e menos ruido visual.</p>
        </div>
        <div class="lan-hero-dynamic" id="lanHeroDynamic" style="display:none;">
            <span class="lan-hero-stat total">
                <span class="lan-hero-stat-dot"></span>
                <span id="lanHeroTotalCount">0 lancamentos</span>
            </span>
            <span class="lan-hero-stat receitas">
                <span class="lan-hero-stat-dot"></span>
                <span class="stat-value" id="lanHeroReceitas">R$ 0,00</span>
                <span>receitas</span>
            </span>
            <span class="lan-hero-stat despesas">
                <span class="lan-hero-stat-dot"></span>
                <span class="stat-value" id="lanHeroDespesas">R$ 0,00</span>
                <span>despesas</span>
            </span>
        </div>
    </section>

    <!-- ─── SUMMARY STRIP ─────────────────────────────────────────────── -->
    <section class="lan-summary-strip" id="lanSummaryStrip" data-aos="fade-up" data-aos-delay="100"
        aria-label="Resumo financeiro do periodo">
        <div class="lan-summary-card receitas">
            <div class="lan-summary-icon"><i data-lucide="trending-up"></i></div>
            <div class="lan-summary-info">
                <span class="lan-summary-label">Receitas</span>
                <span class="lan-summary-value" id="lanSummaryReceitas">R$ 0,00</span>
            </div>
        </div>
        <div class="lan-summary-card despesas">
            <div class="lan-summary-icon"><i data-lucide="trending-down"></i></div>
            <div class="lan-summary-info">
                <span class="lan-summary-label">Despesas</span>
                <span class="lan-summary-value" id="lanSummaryDespesas">R$ 0,00</span>
            </div>
        </div>
        <div class="lan-summary-card saldo">
            <div class="lan-summary-icon"><i data-lucide="wallet"></i></div>
            <div class="lan-summary-info">
                <span class="lan-summary-label">Saldo do periodo</span>
                <span class="lan-summary-value" id="lanSummarySaldo">R$ 0,00</span>
            </div>
        </div>
    </section>

    <!-- ─── EXPORT CARD ───────────────────────────────────────────────── -->
    <div class="modern-card export-card <?= !$isPro ? 'pro-locked' : '' ?>" data-aos="fade-up" data-aos-delay="100"
        id="exportCard">
        <div class="card-header-icon">
            <div class="icon-wrapper export">
                <i data-lucide="file-output"></i>
            </div>
            <div class="card-title-group">
                <h3 class="card-title">Exportar lancamentos</h3>
                <p class="card-subtitle">Exportacao rapida em PDF ou Excel.</p>
            </div>
            <?php if (!$isPro): ?>
            <span class="pro-badge"><i data-lucide="crown"></i> PRO</span>
            <?php endif; ?>
            <button type="button" class="card-collapse-btn" id="toggleExportCard" aria-expanded="false"
                aria-controls="exportCardBody" title="Expandir exportacao">
                <i data-lucide="chevron-down"></i>
            </button>
        </div>

        <div class="export-card-toolbar">
            <div class="export-toolbar-copy">
                <span class="export-toolbar-label">Exportacao</span>
                <p class="export-toolbar-text">Escolha o formato e exporte. Filtros avancados ficam recolhidos por
                    padrao.</p>
            </div>
            <div class="export-actions-group">
                <select id="exportFormat" class="modern-select" data-lk-custom-select="export"
                    aria-label="Formato de exportacao" <?= !$isPro ? 'disabled' : '' ?>>
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel (.xlsx)</option>
                </select>
                <button id="btnExportar" type="button" class="modern-btn primary" aria-label="Exportar lancamentos"
                    <?= !$isPro ? 'disabled' : '' ?>>
                    <i data-lucide="download"></i>
                    <span>Exportar</span>
                </button>
            </div>
        </div>

        <div class="export-card-body" id="exportCardBody" hidden>
            <?php if (!$isPro): ?>
            <div class="pro-overlay">
                <div class="pro-message">
                    <i data-lucide="crown"
                        style="font-size:2.5rem;color:var(--color-warning);margin-bottom:var(--spacing-4);"></i>
                    <h4>Recurso premium</h4>
                    <p>Exportacao de lancamentos e exclusiva do <a href="<?= BASE_URL ?>billing">plano Pro</a>.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="export-controls <?= !$isPro ? 'disabled-blur' : '' ?>">
                <div class="date-range-group">
                    <div class="input-group">
                        <label for="exportStart" class="input-label">
                            <i data-lucide="calendar-days"></i><span>Data inicial</span>
                        </label>
                        <input type="date" id="exportStart" class="modern-input" data-default-today="1"
                            aria-label="Data inicial" <?= !$isPro ? 'disabled' : '' ?>>
                    </div>
                    <div class="input-group">
                        <label for="exportEnd" class="input-label">
                            <i data-lucide="calendar-days"></i><span>Data final</span>
                        </label>
                        <input type="date" id="exportEnd" class="modern-input" data-default-today="1"
                            aria-label="Data final" <?= !$isPro ? 'disabled' : '' ?>>
                    </div>
                </div>
                <div class="export-filters-row">
                    <div class="export-filter-item">
                        <label for="exportConta" class="export-filter-label">Conta</label>
                        <select id="exportConta" class="modern-select export-select" data-lk-custom-select="export"
                            <?= !$isPro ? 'disabled' : '' ?>>
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="export-filter-item">
                        <label for="exportCategoria" class="export-filter-label">Categoria</label>
                        <select id="exportCategoria" class="modern-select export-select" data-lk-custom-select="export"
                            <?= !$isPro ? 'disabled' : '' ?>>
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="export-filter-item">
                        <label for="exportTipo" class="export-filter-label">Tipo</label>
                        <select id="exportTipo" class="modern-select export-select" data-lk-custom-select="export"
                            <?= !$isPro ? 'disabled' : '' ?>>
                            <option value="">Todos</option>
                            <option value="receita">Receitas</option>
                            <option value="despesa">Despesas</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── FILTERS ───────────────────────────────────────────────────── -->
    <section class="lk-filters-section collapsed" data-aos="fade-up" data-aos-delay="200"
        aria-label="Filtros de lancamentos">
        <div class="lk-filters-header">
            <div class="lk-filters-title-group">
                <div class="lk-filters-icon">
                    <i data-lucide="sliders-horizontal"></i>
                </div>
                <div class="lk-filters-text">
                    <h3 class="lk-filters-title">Filtrar lancamentos</h3>
                    <span class="lk-filters-subtitle">Busca unificada, chips compactos e periodo rapido.</span>
                </div>
            </div>
            <button type="button" class="lk-filters-toggle" id="btnToggleLanFilters" aria-label="Expandir filtros">
                <i data-lucide="chevron-down"></i>
            </button>
        </div>

        <div class="lk-filters-body" id="lanFiltersBody">
            <div class="lk-filter-search">
                <i data-lucide="search" class="lk-filter-search-icon"></i>
                <input type="text" id="filtroTexto" class="lk-filter-search-input"
                    placeholder="Buscar descricao, categoria ou conta..." aria-label="Buscar lancamentos">
            </div>

            <div class="lk-filters-grid">
                <div class="lk-filter-item">
                    <label class="lk-filter-label" for="filtroTipo">
                        <i data-lucide="tag"></i>
                        Tipo
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="filtroTipo" class="lk-filter-select" aria-label="Filtrar por tipo">
                            <option value="">Tipo</option>
                            <option value="receita">Receitas</option>
                            <option value="despesa">Despesas</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-arrow"></i>
                    </div>
                </div>
                <div class="lk-filter-item">
                    <label class="lk-filter-label" for="filtroCategoria">
                        <i data-lucide="folder"></i>
                        Categoria
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="filtroCategoria" class="lk-filter-select" aria-label="Filtrar por categoria">
                            <option value="">Categoria</option>
                            <option value="none">Sem categoria</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-arrow"></i>
                    </div>
                </div>
                <div class="lk-filter-item">
                    <label class="lk-filter-label" for="filtroConta">
                        <i data-lucide="wallet"></i>
                        Conta
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="filtroConta" class="lk-filter-select" aria-label="Filtrar por conta">
                            <option value="">Conta</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-arrow"></i>
                    </div>
                </div>
                <div class="lk-filter-item">
                    <label class="lk-filter-label" for="filtroStatus">
                        <i data-lucide="circle-check"></i>
                        Status
                    </label>
                    <div class="lk-select-wrapper">
                        <select id="filtroStatus" class="lk-filter-select" aria-label="Filtrar por status">
                            <option value="">Status</option>
                            <option value="pago">Pagos</option>
                            <option value="pendente">Pendentes</option>
                        </select>
                        <i data-lucide="chevron-down" class="lk-select-arrow"></i>
                    </div>
                </div>
            </div>

            <div class="lk-filters-period-row">
                <div class="lk-period-presets" aria-label="Atalhos de periodo">
                    <button type="button" class="lk-period-preset-btn" data-period-preset="today">Hoje</button>
                    <button type="button" class="lk-period-preset-btn" data-period-preset="7">7 dias</button>
                    <button type="button" class="lk-period-preset-btn" data-period-preset="30">30 dias</button>
                    <button type="button" class="lk-period-month-btn" id="btnUsarMesDoTopo">
                        <i data-lucide="calendar-sync"></i><span>Usar mes do topo</span>
                    </button>
                    <button type="button" class="lk-period-month-btn is-secondary" id="btnToggleAdvancedPeriod"
                        aria-expanded="false" aria-controls="advancedPeriodPanel">
                        <i data-lucide="sliders-horizontal"></i><span>Personalizar</span>
                    </button>
                </div>
            </div>

            <div class="lk-period-advanced" id="advancedPeriodPanel" hidden>
                <div class="lk-period-filter" aria-label="Periodo personalizado da listagem">
                    <div class="lk-period-filter-label">
                        <i data-lucide="calendar-range"></i><span>Periodo personalizado</span>
                    </div>
                    <div class="lk-period-inputs">
                        <div class="lk-period-field">
                            <label for="filtroDataInicio" class="lk-period-field-label">Data inicial</label>
                            <input type="date" id="filtroDataInicio" class="lk-filter-date-input"
                                aria-label="Data inicial da listagem">
                        </div>
                        <div class="lk-period-field">
                            <label for="filtroDataFim" class="lk-period-field-label">Data final</label>
                            <input type="date" id="filtroDataFim" class="lk-filter-date-input"
                                aria-label="Data final da listagem">
                        </div>
                    </div>
                </div>
            </div>

            <div class="lk-active-filters" id="activeFilterBadges" style="display:none;" aria-live="polite"></div>

            <div class="lk-filters-actions">
                <button id="btnLimparFiltros" type="button" class="lk-filter-clear-btn" aria-label="Limpar filtros"
                    title="Limpar filtros">
                    <i data-lucide="x"></i><span>Limpar</span>
                </button>
            </div>
        </div>
    </section>

    <!-- ─── TABLE SECTION ─────────────────────────────────────────────── -->
    <div class="modern-table-wrapper" data-aos="fade-up" data-aos-delay="300">
        <div class="modern-table-inner">

            <div class="table-header-info">
                <div class="info-group">
                    <i data-lucide="list"></i>
                    <span>Seus lancamentos</span>
                </div>
                <div class="table-actions">
                    <button type="button" class="modern-btn btn-novo-lancamento" id="btnNovoLancamento"
                        aria-label="Novo lancamento">
                        <i data-lucide="plus"></i><span>Novo lancamento</span>
                    </button>
                    <button type="button" class="icon-btn" title="Atualizar" id="btnRefreshPage">
                        <i data-lucide="refresh-cw"></i>
                    </button>
                </div>
            </div>

            <div class="lk-table-context" aria-live="polite">
                <div class="lk-table-context-main">
                    <span id="lancamentosContextText">Carregando lancamentos...</span>
                    <span id="lancamentosLimitNotice" class="lk-context-badge warning" style="display:none;"></span>
                </div>
                <span id="selectionScopeHint" class="lk-selection-hint">Selecao em massa vale apenas para a pagina
                    atual.</span>
            </div>

            <!-- Selection toolbar -->
            <div class="lk-selection-toolbar" id="selectionBulkBar" hidden>
                <div class="lk-selection-toolbar-copy">
                    <span class="lk-selection-toolbar-count" id="selectionBulkCount">0 selecionados</span>
                    <span class="lk-selection-toolbar-text" id="selectionBulkText">Acoes rapidas para os itens
                        selecionados nesta pagina.</span>
                </div>
                <div class="lk-selection-toolbar-actions">
                    <button id="btnEditarSel" type="button" class="modern-btn" disabled
                        aria-label="Editar lancamento selecionado">
                        <i data-lucide="pen"></i><span>Editar</span>
                    </button>
                    <button id="btnExcluirSel" type="button" class="modern-btn danger" disabled
                        aria-label="Excluir registros selecionados">
                        <i data-lucide="trash-2"></i><span>Excluir <span id="selCount">0</span></span>
                    </button>
                    <button id="btnLimparSelecao" type="button" class="lk-selection-clear-btn">
                        <i data-lucide="x"></i><span>Limpar selecao</span>
                    </button>
                </div>
            </div>

            <!-- Feed de lançamentos -->
            <div class="lan-table-container">
                <section class="table-container tab-desktop">

                    <!-- Sort & Select controls -->
                    <div class="lk-feed-toolbar">
                        <div class="lk-feed-sort-controls">
                            <button type="button" class="lk-feed-sort-btn active sortable" data-sort="data">
                                <i data-lucide="calendar"></i><span>Data</span><i data-lucide="arrow-up-down"
                                    class="sort-icon"></i>
                            </button>
                            <button type="button" class="lk-feed-sort-btn sortable" data-sort="valor">
                                <i data-lucide="dollar-sign"></i><span>Valor</span><i data-lucide="arrow-up-down"
                                    class="sort-icon"></i>
                            </button>
                            <button type="button" class="lk-feed-sort-btn sortable" data-sort="tipo">
                                <i data-lucide="tag"></i><span>Tipo</span><i data-lucide="arrow-up-down"
                                    class="sort-icon"></i>
                            </button>
                        </div>
                        <label class="lk-feed-select-all">
                            <input type="checkbox" id="selectAllLancamentos" class="lk-checkbox"
                                title="Selecionar itens da pagina atual" aria-label="Selecionar itens da pagina atual">
                            <span>Selecionar todos</span>
                        </label>
                    </div>

                    <!-- Transaction feed -->
                    <div class="lk-feed" id="lancamentosFeed" role="list">
                        <div class="lk-feed-loading">
                            <div class="spinner-border" role="status"
                                style="width:2rem;height:2rem;color:var(--color-primary);">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p>Carregando lancamentos...</p>
                        </div>
                    </div>

                    <div class="lk-pagination" id="desktopPagination">
                        <div class="pagination-info">
                            <span id="paginationInfo">0 lancamentos</span>
                        </div>
                        <div class="pagination-controls">
                            <select id="pageSize" class="page-size-select">
                                <option value="10" selected>10 por pagina</option>
                                <option value="25">25 por pagina</option>
                                <option value="50">50 por pagina</option>
                                <option value="100">100 por pagina</option>
                            </select>
                            <button type="button" id="prevPage" class="pagination-btn" disabled
                                aria-label="Pagina anterior">
                                <i data-lucide="chevron-left"></i>
                            </button>
                            <span id="pageNumbers" class="page-numbers"></span>
                            <button type="button" id="nextPage" class="pagination-btn" disabled
                                aria-label="Proxima pagina">
                                <i data-lucide="chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Mobile cards -->
                <section class="lan-cards-wrapper cards-wrapper">
                    <section class="lan-cards-container cards-container" id="lanCards">
                        <div class="lk-loading-state" id="lanCardsLoading"
                            style="text-align:center;padding:2rem 1rem;grid-column:1/-1;">
                            <div class="spinner-border" role="status"
                                style="width:2rem;height:2rem;color:var(--color-primary);">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p style="margin:1rem 0 0;color:var(--color-text-muted);font-size:0.9rem;">Carregando
                                lancamentos...</p>
                        </div>
                    </section>

                    <nav class="lan-cards-pager cards-pager" id="lanCardsPager" aria-label="Paginacao de lancamentos">
                        <button type="button" id="lanPagerFirst" class="lan-pager-btn pager-btn" disabled
                            aria-label="Primeira pagina">
                            <i data-lucide="chevrons-left"></i>
                        </button>
                        <button type="button" id="lanPagerPrev" class="lan-pager-btn pager-btn" disabled
                            aria-label="Pagina anterior">
                            <i data-lucide="chevron-left"></i>
                        </button>
                        <span id="lanPagerInfo" class="lan-pager-info pager-info">Nenhum lancamento</span>
                        <button type="button" id="lanPagerNext" class="lan-pager-btn pager-btn" disabled
                            aria-label="Proxima pagina">
                            <i data-lucide="chevron-right"></i>
                        </button>
                        <button type="button" id="lanPagerLast" class="lan-pager-btn pager-btn" disabled
                            aria-label="Ultima pagina">
                            <i data-lucide="chevrons-right"></i>
                        </button>
                    </nav>
                </section>
            </div>

        </div>
    </div>

</section>

<?php include __DIR__ . '/../partials/modals/editar-lancamentos.php'; ?>
<?php include __DIR__ . '/../partials/modals/visualizar-lancamento.php'; ?>
<?php include __DIR__ . '/../partials/modals/editar-transferencia.php'; ?>