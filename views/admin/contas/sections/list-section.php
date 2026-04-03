<!-- ============================================================
         CONTAS — Lista principal
         ============================================================ -->
<section class="cont-list-section surface-card">
    <div class="cont-list-header">
        <div class="cont-list-heading">
            <span class="cont-section__eyebrow">Contas</span>
            <h2 class="cont-section__title" id="contasListTitle">Suas contas ativas</h2>
            <p class="cont-section__desc" id="contasListDescription">
                A conta com maior saldo aparece primeiro para facilitar sua leitura.
            </p>
        </div>

        <div class="cont-list-controls">
            <div class="cont-list-actions">
                <button class="btn btn-primary" id="btnNovaConta" aria-label="Criar nova conta">
                    <i data-lucide="plus"></i> Nova conta
                </button>
                <button class="btn btn-ghost" id="btnReload" aria-label="Recarregar contas" title="Atualizar lista">
                    <i data-lucide="refresh-cw"></i>
                </button>
            </div>

            <div class="cont-list-right">
                <div class="view-toggle" id="viewToggle">
                    <button class="view-btn active" data-view="grid" title="Visualização em cards">
                        <i data-lucide="layout-grid"></i>
                    </button>
                    <button class="view-btn" data-view="list" title="Visualização compacta">
                        <i data-lucide="list"></i>
                    </button>
                </div>
                <a class="btn btn-ghost" href="<?= BASE_URL ?>contas/arquivadas" aria-label="Ver contas arquivadas">
                    <i data-lucide="archive"></i> Arquivadas
                </a>
            </div>
        </div>
    </div>

    <!-- Toolbar — Busca + Filtro -->
    <div class="contas-toolbar">
        <div class="contas-search-wrapper">
            <i data-lucide="search" class="contas-search-icon"></i>
            <input type="text" id="contasSearchInput" class="contas-search-input"
                placeholder="Buscar conta, instituição ou tipo..." autocomplete="off" />
            <button type="button" id="contasSearchClear" class="contas-search-clear d-none" title="Limpar busca">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="contas-filter-wrapper">
            <label class="visually-hidden" for="contasTypeFilter">Filtrar por tipo</label>
            <select id="contasTypeFilter" class="contas-type-filter" aria-label="Filtrar contas por tipo">
                <option value="all">Todos os tipos</option>
                <option value="conta_corrente">Conta corrente</option>
                <option value="conta_poupanca">Poupança</option>
                <option value="conta_investimento">Reserva</option>
                <option value="carteira_digital">Carteira digital</option>
                <option value="dinheiro">Dinheiro</option>
            </select>
        </div>
    </div>

    <div class="contas-filter-summary" id="contasFilterSummary" aria-live="polite"></div>

    <!-- List-view header -->
    <div id="contasListHeader" class="contas-list-header">
        <span></span>
        <span>Conta</span>
        <span>% do total</span>
        <span>Valor</span>
        <span>Ações</span>
    </div>

    <!-- Grid de contas -->
    <div class="acc-grid" id="accountsGrid" aria-live="polite" aria-busy="true">
        <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
        <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
        <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
    </div>

    <noscript>
        <div class="empty-state" style="text-align:center;padding:3rem 1rem;">
            <div class="empty-icon" style="font-size:3rem;margin-bottom:1rem;">
                <i data-lucide="wallet" style="color:var(--color-primary);"></i>
            </div>
            <h3 style="color:var(--color-text);margin-bottom:0.5rem;">JavaScript necessário</h3>
            <p style="color:var(--color-text-muted);">Ative o JavaScript no navegador para visualizar suas contas.</p>
        </div>
    </noscript>
</section>