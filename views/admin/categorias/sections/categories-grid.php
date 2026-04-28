<!-- ==================== CATEGORIAS SEPARADAS POR TIPO ==================== -->
<section class="categories-section" aria-labelledby="categoriasSectionTitle">
    <div class="categories-section-header">
        <div class="categories-section-copy">
            <p class="categories-section-kicker">Organização por tipo</p>
            <h2 class="categories-section-title" id="categoriasSectionTitle">Receitas e despesas</h2>
            <p class="categories-section-description">Navegue pelos dois grupos lado a lado, com rolagem interna previsível.</p>
        </div>

        <div class="categories-section-tools">
            <div class="categories-section-search-row">
                <div class="cat-search-wrapper categories-section-search">
                    <i data-lucide="search" class="cat-search-icon"></i>
                    <input type="text" id="catSearchInput" class="cat-search-input"
                        placeholder="Buscar categoria ou subcategoria..." autocomplete="off" />
                    <button type="button" id="catSearchClear" class="cat-search-clear d-none" title="Limpar busca">
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <div class="cat-context-actions categories-section-actions">
                    <button type="button" class="cat-context-btn" data-action="refresh-categorias" id="catRefreshButton">
                        <i data-lucide="refresh-cw"></i>
                        <span>Atualizar</span>
                    </button>
                    <button type="button" class="cat-context-btn ghost d-none" data-action="clear-categoria-search"
                        id="catClearSearchButton">
                        <i data-lucide="x"></i>
                        <span>Limpar busca</span>
                    </button>

                    <button class="cat-customize-open surface-card" id="btnCustomizeCategorias" type="button">
                        <i data-lucide="sliders-horizontal"></i>
                        <span>Personalizar</span>
                    </button>
                </div>
            </div>

            <div class="cat-filter-summary d-none" id="catFilterSummary" aria-live="polite"></div>
        </div>
    </div>

    <div class="categories-grid">
        <!-- CATEGORIAS DE RECEITAS -->
        <div class="category-card receitas-card surface-card surface-card--interactive surface-card--clip">
            <div class="category-header receitas">
                <div class="header-content">
                    <div class="header-icon">
                        <i data-lucide="trending-up"></i>
                    </div>
                    <div class="header-text">
                        <h3 class="category-title">Receitas</h3>
                        <p class="category-count">
                            <span id="receitasCount">0</span>
                            <span class="category-count-divider">de</span>
                            <span id="receitasTotalCount">0</span> categorias
                        </p>
                    </div>
                </div>
            </div>

            <div class="category-list" id="receitasList">
                <div class="empty-state">
                    <i data-lucide="inbox"></i>
                    <p>Nenhuma categoria de receita cadastrada</p>
                </div>
            </div>
        </div>

        <!-- CATEGORIAS DE DESPESAS -->
        <div class="category-card despesas-card surface-card surface-card--interactive surface-card--clip">
            <div class="category-header despesas">
                <div class="header-content">
                    <div class="header-icon">
                        <i data-lucide="trending-down"></i>
                    </div>
                    <div class="header-text">
                        <h3 class="category-title">Despesas</h3>
                        <p class="category-count">
                            <span id="despesasCount">0</span>
                            <span class="category-count-divider">de</span>
                            <span id="despesasTotalCount">0</span> categorias
                        </p>
                    </div>
                </div>
            </div>

            <div class="category-list" id="despesasList">
                <div class="empty-state">
                    <i data-lucide="inbox"></i>
                    <p>Nenhuma categoria de despesa cadastrada</p>
                </div>
            </div>
        </div>
    </div>
</section>