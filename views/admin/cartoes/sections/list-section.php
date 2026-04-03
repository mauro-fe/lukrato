<!-- ============================================================
         LISTA DE CARTÕES — Seção principal
         ============================================================ -->
<section class="cart-list-section surface-card">
    <div class="cart-list-header">
        <div class="cart-list-heading">
            <span class="cart-section__eyebrow">Cartões</span>
            <h2 class="cart-section__title">Seus cartões ativos</h2>
            <p class="cart-section__desc">
                Cartões com fatura pendente ou uso elevado aparecem em destaque.
            </p>
        </div>

        <div class="cart-list-controls">
            <div class="cart-list-actions">
                <button class="btn btn-primary" id="btnNovoCartao" aria-label="Adicionar cartão">
                    <i data-lucide="plus"></i> Novo cartão
                </button>
                <button class="btn btn-ghost" id="btnExportar" title="Exportar relatório"
                    aria-label="Exportar relatório">
                    <i data-lucide="download"></i>
                </button>
                <button class="btn btn-ghost" id="btnReload" title="Atualizar cartões" aria-label="Recarregar cartões">
                    <i data-lucide="refresh-cw"></i>
                </button>
            </div>

            <div class="cart-list-right">
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid" title="Visualização em grade">
                        <i data-lucide="layout-grid"></i>
                    </button>
                    <button class="view-btn" data-view="list" title="Visualização em lista">
                        <i data-lucide="list"></i>
                    </button>
                </div>
                <a class="btn btn-ghost" href="<?= BASE_URL ?>cartoes/arquivadas" aria-label="Ver cartões arquivados">
                    <i data-lucide="archive"></i> Arquivados
                </a>
            </div>
        </div>
    </div>

    <!-- Toolbar — Busca + Filtro por bandeira -->
    <div class="cartoes-toolbar" id="cartoesToolbar" aria-label="Filtros e ações da página de cartões">
        <div class="cart-search-wrapper">
            <i data-lucide="search" class="cart-search-icon"></i>
            <input type="text" id="searchCartoes" class="cart-search-input" placeholder="Buscar por nome ou final..."
                autocomplete="off" />
        </div>

        <div class="filter-group">
            <button class="filter-btn surface-filter surface-filter--pill active" data-filter="all">
                <i data-lucide="grid-3x3"></i>
                Todos
            </button>
            <button class="filter-btn surface-filter surface-filter--pill" data-filter="visa">
                <img src="<?= BASE_URL ?>assets/img/bandeiras/visa.png" alt="Visa" class="brand-logo-filter">
                Visa
            </button>
            <button class="filter-btn surface-filter surface-filter--pill" data-filter="mastercard">
                <img src="<?= BASE_URL ?>assets/img/bandeiras/mastercard.png" alt="Mastercard"
                    class="brand-logo-filter">
                Master
            </button>
            <button class="filter-btn surface-filter surface-filter--pill" data-filter="elo">
                <img src="<?= BASE_URL ?>assets/img/bandeiras/elo.png" alt="Elo" class="brand-logo-filter">
                Elo
            </button>
            <button class="filter-btn surface-filter surface-filter--pill surface-filter--warning btn-clear-filters"
                id="btnLimparFiltrosCartoes" title="Limpar busca e filtros" style="display:none;">
                <i data-lucide="eraser"></i>
                Limpar
            </button>
        </div>
    </div>

    <!-- Resumo dinâmico dos filtros -->
    <div class="cartoes-filter-summary" id="cartoesFilterSummary" aria-live="polite"></div>

    <!-- Grid de cartões (JS-rendered) -->
    <div class="cartoes-container" id="cartoesContainer">
        <div class="cartoes-grid" id="cartoesGrid" aria-live="polite" aria-busy="true">
            <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
            <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
            <div class="lk-skeleton lk-skeleton--card" aria-hidden="true"></div>
        </div>

        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">
                <i data-lucide="credit-card"></i>
            </div>
            <h3>Nenhum cartão cadastrado</h3>
            <p>Adicione seu primeiro cartão para acompanhar limite, vencimentos e faturas em tempo real.</p>
            <div class="empty-state-actions">
                <button class="btn btn-primary" id="btnNovoCartaoEmpty">
                    <i data-lucide="plus"></i>
                    Adicionar primeiro cartão
                </button>
                <button class="btn btn-ghost" id="btnLimparFiltrosEmpty" style="display: none;">
                    <i data-lucide="eraser"></i>
                    Limpar filtros
                </button>
            </div>
        </div>
    </div>

    <noscript>
        <div class="empty-state" style="text-align:center;padding:3rem 1rem;">
            <div class="empty-icon" style="font-size:3rem;margin-bottom:1rem;">
                <i data-lucide="credit-card" style="color:var(--color-primary);"></i>
            </div>
            <h3 style="color:var(--color-text);margin-bottom:0.5rem;">JavaScript necessário</h3>
            <p style="color:var(--color-text-muted);">Ative o JavaScript no navegador para visualizar seus cartões.</p>
        </div>
    </noscript>
</section>