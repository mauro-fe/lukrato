<!-- ============================================================
         LISTA DE FATURAS — Seção principal
         ============================================================ -->
<section class="fat-list-section surface-card">
    <div class="fat-list-header">
        <div class="fat-list-heading">
            <span class="fat-section__eyebrow">Faturas</span>
            <h2 class="fat-section__title">Painel de acompanhamento</h2>
            <p class="fat-section__desc">
                A fatura do mes ganha destaque primeiro, enquanto as referencias passadas ficam no fim da leitura.
            </p>
            <div class="fat-list-summary" aria-live="polite">
                <span class="fat-summary-pill accent" id="faturasResultsSummary">Carregando faturas...</span>
                <span class="fat-summary-pill subtle" id="faturasContextSummary">Visão completa</span>
            </div>
        </div>

        <div class="fat-list-controls">
            <span class="fat-list-controls__label">Modo de visualização</span>
            <div class="view-toggle" id="faturasViewToggle" role="group" <?= !$showFaturasViewToggle ? ' style="display:none;"' : '' ?>
                aria-label="Escolha a visualização das faturas">
                <button class="view-btn active" data-view="grid" type="button" aria-pressed="true"
                    title="Visualização em cards">
                    <i data-lucide="layout-grid"></i>
                    <span>Cards</span>
                </button>
                <button class="view-btn" data-view="list" type="button" aria-pressed="false"
                    title="Visualização em lista">
                    <i data-lucide="list"></i>
                    <span>Lista</span>
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
        <span>Status e ações</span>
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

    <div class="fat-list-footer">
        <div class="fat-list-footer-copy">
            <span class="fat-list-footer-eyebrow">Leitura da tela</span>
            <p class="fat-list-footer-text">Personalize os blocos deste painel sem sair do acompanhamento das faturas.
            </p>
        </div>
        <button class="fat-customize-open" id="btnCustomizeFaturas" type="button">
            <i data-lucide="sliders-horizontal"></i>
            <span><?= escape($faturasTriggerLabel) ?></span>
        </button>
    </div>
</section>