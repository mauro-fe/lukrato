    <div class="rel-section-panel active" id="section-overview" role="tabpanel">
        <div class="overview-grid">
            <div class="modern-card overview-insights-card surface-card surface-card--interactive surface-card--clip">
                <div class="card-header">
                    <div class="header-left">
                        <i data-lucide="lightbulb"></i>
                        <div class="header-text">
                            <h3>Principais Insights</h3>
                            <p>Destaques do período</p>
                        </div>
                    </div>
                </div>
                <div class="card-body" id="overviewInsights">
                    <div class="lk-loading-state">
                        <i data-lucide="loader-2"></i>
                        <p>Carregando...</p>
                    </div>
                </div>
            </div>

            <div class="overview-charts-row" id="relOverviewChartsRow">
                <div class="modern-card overview-mini-chart surface-card surface-card--interactive surface-card--clip">
                    <h4><i data-lucide="pie-chart"></i> Despesas por Categoria</h4>
                    <div id="overviewCategoryChart" class="mini-chart-container">
                        <div class="lk-loading-state">
                            <i data-lucide="loader-2"></i>
                        </div>
                    </div>
                </div>
                <div class="modern-card overview-mini-chart surface-card surface-card--interactive surface-card--clip">
                    <h4><i data-lucide="bar-chart-2"></i> Receitas x Despesas</h4>
                    <div id="overviewComparisonChart" class="mini-chart-container">
                        <div class="lk-loading-state">
                            <i data-lucide="loader-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$isPro): ?>
                <div class="overview-pro-cta">
                    <i data-lucide="crown"></i>
                    <div>
                        <h4>Desbloqueie todo o potencial</h4>
                        <p>Com o plano PRO, acesse insights completos, comparativos, exportação e muito mais.</p>
                    </div>
                    <a href="<?= BASE_URL ?>billing" class="btn-upgrade-cta surface-button surface-button--upgrade">
                        <i data-lucide="crown"></i> Fazer Upgrade
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
