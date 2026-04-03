<!-- ============================================================
         LISTA DE FATURAS — Seção principal
         ============================================================ -->
<section class="fat-list-section surface-card">
    <div class="fat-list-header">
        <div class="fat-list-heading">
            <span class="fat-section__eyebrow">Faturas</span>
            <h2 class="fat-section__title">Suas Faturas</h2>
            <p class="fat-section__desc">
                Faturas pendentes e com valor elevado aparecem em destaque.
            </p>
        </div>

        <div class="fat-list-controls">
            <div class="view-toggle" id="faturasViewToggle">
                <button class="view-btn active" data-view="grid" title="Visualização em Cards">
                    <i data-lucide="layout-grid"></i>
                </button>
                <button class="view-btn" data-view="list" title="Visualização em Lista">
                    <i data-lucide="list"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="loadingParcelamentos" class="lk-loading-state" style="display: none;">
        <i data-lucide="loader-2"></i>
        <p>Carregando faturas...</p>
    </div>

    <!-- Headers da lista (visível apenas em modo lista) -->
    <div id="faturasListHeader" class="faturas-list-header">
        <span></span>
        <span>Cartão</span>
        <span>Valor</span>
        <span>Progresso</span>
        <span>Status</span>
        <span>Ações</span>
    </div>

    <!-- Grid de faturas (JS-rendered) -->
    <div id="parcelamentosContainer" class="parcelamentos-grid"></div>

    <!-- Empty state -->
    <div id="emptyState" class="empty-state" style="display: none;">
        <div class="empty-icon">
            <i data-lucide="credit-card"></i>
        </div>
        <h3>Nenhuma fatura encontrada</h3>
        <p>Suas faturas de cartão aparecerão aqui automaticamente quando você cadastrar compras parceladas.</p>
        <a href="<?= BASE_URL ?>lancamentos" class="btn-cta">
            <i data-lucide="plus"></i>
            Criar Lançamento Parcelado
        </a>
    </div>
</section>