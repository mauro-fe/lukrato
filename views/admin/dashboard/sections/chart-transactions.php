<!-- ============================================================
         GRID — Gráfico + Transações lado a lado
         ============================================================ -->
<div class="dash-duo-row dash-duo-row--insights">
    <section class="dash-chart-section surface-card surface-card--interactive" id="chart-section">
        <div class="dash-section-header">
            <h2 class="dash-section-title">Saídas por categoria</h2>
            <div class="dash-chart-toggle" id="chartToggle">
                <button class="dash-chart-toggle__btn is-active" data-mode="donut" type="button">
                    <i data-lucide="pie-chart"></i>
                </button>
                <button class="dash-chart-toggle__btn" data-mode="compare" type="button">
                    <i data-lucide="bar-chart-3"></i>
                </button>
            </div>
        </div>
        <div class="dash-chart-wrap">
            <div class="chart-loading" id="chartLoading"><i data-lucide="hourglass" aria-hidden="true"></i></div>
            <div id="categoryChart" role="img" aria-label="Gráfico de despesas por categoria"></div>
        </div>
    </section>

    <section class="dash-transactions-section surface-card surface-card--interactive" id="table-section">
        <div class="dash-section-header">
            <h2 class="dash-section-title">Transações recentes</h2>
            <a href="<?= BASE_URL ?>lancamentos" class="dash-section-link">
                Ver todas <i data-lucide="arrow-right"></i>
            </a>
        </div>

        <!-- Estado vazio -->
        <div class="dash-empty" id="emptyState" style="display:none;">
            <i data-lucide="receipt"></i>
            <p>Seu histórico começa na primeira transação.</p>
            <span class="dash-empty__subtext">Adicione um lançamento para ver saldo, gráfico e categorias ganhando
                contexto real.</span>
            <button class="dash-btn dash-btn--primary dash-btn--lg" id="dashboardEmptyStateCta" type="button">
                <i data-lucide="plus"></i> Adicionar agora
            </button>
        </div>

        <!-- Lista renderizada pelo JS -->
        <div class="dash-transactions-list" id="transactionsList"></div>
    </section>
</div>