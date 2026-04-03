<!-- ==================== CONTEXTO + BUSCA UNIFICADOS ==================== -->
<section class="cat-context-card surface-card surface-card--interactive" id="catContextCard" aria-live="polite">
    <div class="cat-context-copy">
        <p class="cat-context-kicker" id="catContextKicker">Categorias e subcategorias</p>
        <h3 class="cat-context-title" id="catContextTitle">Organize sua estrutura financeira com clareza</h3>
        <p class="cat-context-description" id="catContextDescription">
            Os limites mensais exibidos abaixo seguem o mês selecionado no topo da página.
        </p>
    </div>

    <div class="cat-context-right">
        <div class="cat-search-wrapper">
            <i data-lucide="search" class="cat-search-icon"></i>
            <input type="text" id="catSearchInput" class="cat-search-input"
                placeholder="Buscar categoria ou subcategoria..." autocomplete="off" />
            <button type="button" id="catSearchClear" class="cat-search-clear d-none" title="Limpar busca">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="cat-context-actions">
            <button type="button" class="cat-context-btn" data-action="refresh-categorias" id="catRefreshButton">
                <i data-lucide="refresh-cw"></i>
                <span>Atualizar</span>
            </button>
            <button type="button" class="cat-context-btn ghost d-none" data-action="clear-categoria-search"
                id="catClearSearchButton">
                <i data-lucide="x"></i>
                <span>Limpar busca</span>
            </button>
        </div>
    </div>

    <div class="cat-context-chips" id="catContextChips"></div>
</section>

<div class="cat-filter-summary" id="catFilterSummary" aria-live="polite"></div>