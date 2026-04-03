<div class="modern-table-wrapper surface-card surface-card--clip" data-aos="fade-up" data-aos-delay="300">
    <div class="modern-table-inner">

        <div class="table-header-info">
            <div class="info-group">
                <i data-lucide="list"></i>
                <span>Suas transações</span>
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
                        <button type="button" id="nextPage" class="pagination-btn" disabled aria-label="Proxima pagina">
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