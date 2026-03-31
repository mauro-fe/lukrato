<!-- Relatórios View -->
<?php $isPro = $isPro ?? false; ?>

<div class="rel-page">
    <!-- ==================== CARDS DE RESUMO RÁPIDO ==================== -->
    <div class="quick-stats-grid" id="relQuickStats">
        <div class="stat-card stat-receitas surface-card surface-card--interactive surface-card--clip"
            title="Total de entradas financeiras registradas neste mês" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="trending-up"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Receitas do Mês</span>
                <span class="stat-value" id="totalReceitas">R$ 0,00</span>
                <span class="stat-hint">Total de entradas no período</span>
                <span class="stat-trend" id="trendReceitas"></span>
            </div>
        </div>

        <div class="stat-card stat-despesas surface-card surface-card--interactive surface-card--clip"
            title="Total de saídas e gastos registrados neste mês" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="trending-down"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Despesas do Mês</span>
                <span class="stat-value" id="totalDespesas">R$ 0,00</span>
                <span class="stat-hint">Total de saídas no período</span>
                <span class="stat-trend" id="trendDespesas"></span>
            </div>
        </div>

        <div class="stat-card stat-saldo surface-card surface-card--interactive surface-card--clip"
            title="Diferença entre receitas e despesas (receitas - despesas)" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="wallet" style="color: white"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Saldo do Mês</span>
                <span class="stat-value" id="saldoMes">R$ 0,00</span>
                <span class="stat-hint">Receitas menos despesas</span>
                <span class="stat-trend" id="trendSaldo"></span>
            </div>
        </div>

        <div class="stat-card stat-cartoes surface-card surface-card--interactive surface-card--clip"
            title="Soma de todas as faturas de cartões de crédito neste mês" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="credit-card" style="color: white"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Faturas Cartões</span>
                <span class="stat-value" id="totalCartoes">R$ 0,00</span>
                <span class="stat-hint">Gastos em cartões de crédito</span>
                <span class="stat-trend" id="trendCartoes"></span>
            </div>
        </div>
    </div>

    <!-- ==================== TABS DE SEÇÃO (estilo Perfil) ==================== -->


    <nav class="rel-section-tabs" role="tablist" aria-label="Seções de relatórios">
        <button type="button" class="rel-section-tab active" data-section="overview" role="tab" aria-selected="true"
            aria-controls="section-overview">
            <span class="tab-icon"><i data-lucide="layout-dashboard" style="color:#3b82f6"></i></span>
            <span class="tab-label">Visão Geral</span>
        </button>
        <button type="button" class="rel-section-tab" data-section="relatorios" role="tab" aria-selected="false"
            aria-controls="section-relatorios">
            <span class="tab-icon"><i data-lucide="bar-chart-3" style="color:#e67e22"></i></span>
            <span class="tab-label">Relatórios</span>
        </button>
        <button type="button" class="rel-section-tab" data-section="insights" role="tab" aria-selected="false"
            aria-controls="section-insights">
            <span class="tab-icon"><i data-lucide="lightbulb" style="color:#facc15"></i></span>
            <span class="tab-label">Insights Inteligentes</span>
            <?php if (!$isPro): ?><span class="tab-pro-badge surface-chip surface-chip--pro surface-chip--xs"><i
                        data-lucide="crown"></i> PRO</span><?php endif; ?>
        </button>
        <button type="button" class="rel-section-tab<?= !$isPro ? ' pro-tab-locked' : '' ?>" data-section="comparativos"
            role="tab" aria-selected="false" aria-controls="section-comparativos">
            <span class="tab-icon"><i data-lucide="git-compare" style="color:#3b82f6"></i></span>
            <span class="tab-label">Comparativos</span>
            <?php if (!$isPro): ?><span class="tab-pro-badge surface-chip surface-chip--pro surface-chip--xs"><i
                        data-lucide="crown"></i> PRO</span><?php endif; ?>
        </button>
    </nav>

    <!-- ==================== SEÇÃO: VISÃO GERAL (padrão) ==================== -->
    <div class="rel-section-panel active" id="section-overview" role="tabpanel">
        <div class="overview-grid">
            <!-- Pulso Mensal -->
            <div class="modern-card overview-pulse-card surface-card surface-card--interactive surface-card--clip">
                <div class="card-header">
                    <div class="header-left">
                        <i data-lucide="activity"></i>
                        <div class="header-text">
                            <h3>Pulso Mensal</h3>
                            <p>Resumo do seu mês financeiro</p>
                        </div>
                    </div>
                </div>
                <div class="card-body" id="overviewPulse">
                    <div class="lk-loading-state">
                        <i data-lucide="loader-2"></i>
                        <p>Analisando...</p>
                    </div>
                </div>
            </div>

            <!-- Top Insights -->
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

            <!-- Mini Charts Row -->
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

            <!-- PRO CTA para usuários free -->
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

    <!-- ==================== SEÇÃO: RELATÓRIOS ==================== -->
    <div class="rel-section-panel" id="section-relatorios" role="tabpanel">

        <!-- Tabs de Visualização de Gráficos -->
        <div class="modern-card tabs-card surface-card surface-card--interactive surface-card--clip">
            <div class="tabs-container" role="tablist">
                <div class="tab-group" data-group-label="Mensal">
                    <button class="tab-btn active" data-view="category" role="tab">
                        <i data-lucide="pie-chart"></i>
                        <span>Por Categoria</span>
                    </button>

                    <button class="tab-btn" data-view="balance" role="tab">
                        <i data-lucide="line-chart"></i>
                        <span>Saldo Diário</span>
                    </button>

                    <button class="tab-btn" data-view="comparison" role="tab">
                        <i data-lucide="bar-chart-2"></i>
                        <span>Receitas x Despesas</span>
                    </button>

                    <button class="tab-btn" data-view="accounts" role="tab">
                        <i data-lucide="wallet"></i>
                        <span>Por Conta</span>
                    </button>

                    <button class="tab-btn" data-view="cards" role="tab">
                        <i data-lucide="credit-card"></i>
                        <span>Cartões de Crédito</span>
                    </button>

                    <button class="tab-btn" data-view="evolution" role="tab">
                        <i data-lucide="git-branch"></i>
                        <span>Evolução 12m</span>
                    </button>
                </div>

                <div class="tab-separator"></div>

                <div class="tab-group" data-group-label="Anual">
                    <button class="tab-btn" data-view="annual_summary" role="tab">
                        <i data-lucide="calendar-days"></i>
                        <span>Resumo Anual</span>
                    </button>

                    <button class="tab-btn" data-view="annual_category" role="tab">
                        <i data-lucide="pie-chart"></i>
                        <span>Categoria Anual</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Controles Adicionais -->
        <div class="controls-row" id="relControlsRow">
            <div class="control-group surface-control-box surface-control-box--interactive hidden"
                id="typeSelectWrapper">
                <label for="reportType">
                    <i data-lucide="filter" style="color: var(--color-primary)"></i>
                    Filtrar por
                </label>
                <select id="reportType" class="form-select">
                    <option value="despesas_por_categoria">Despesas por Categoria</option>
                    <option value="receitas_por_categoria">Receitas por Categoria</option>
                </select>
            </div>

            <div class="control-group surface-control-box surface-control-box--interactive hidden"
                id="accountSelectWrapper">
                <label for="accountFilter">
                    <i data-lucide="landmark" style="color: var(--color-primary)"></i>
                    Conta
                </label>
                <select id="accountFilter" class="form-select">
                    <option value="">Todas as Contas</option>
                </select>
            </div>

            <div class="control-group surface-control-box surface-control-box--interactive" id="clearFiltersWrapper"
                style="display:none; align-items: flex-end;">
                <button type="button" id="btnLimparFiltrosRel" class="btn btn-secondary"
                    title="Resetar filtros para padrão" style="white-space: nowrap;">
                    <i data-lucide="eraser"></i>
                    Limpar Filtros
                </button>
            </div>

            <div class="control-group surface-control-box surface-control-box--interactive" id="exportControl"
                style="margin-left: auto; align-items: flex-end;">
                <button type="button" id="exportBtn" class="btn btn-secondary btn-compact-export"
                    <?= !$isPro ? 'disabled title="Recurso PRO"' : '' ?>>
                    <i data-lucide="download"></i>
                    <span>Exportar</span>
                    <?php if (!$isPro): ?><span class="tab-pro-badge surface-chip surface-chip--pro surface-chip--xs"
                            style="margin-left:4px"><i data-lucide="crown"></i> PRO</span><?php endif; ?>
                </button>
            </div>
        </div>

        <!-- Área de Relatório / Gráfico -->
        <div class="report-filter-summary" id="reportFilterSummary" aria-live="polite"></div>
        <div class="report-scope-note hidden" id="reportScopeNote" aria-live="polite"></div>

        <div class="modern-card report-card surface-card surface-card--interactive surface-card--clip">
            <div class="report-area" id="reportArea">
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando relatório...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== SEÇÃO: INSIGHTS INTELIGENTES ==================== -->
    <div class="rel-section-panel" id="section-insights" role="tabpanel">
        <div class="modern-card insights-card surface-card surface-card--interactive surface-card--clip">
            <div class="card-header">
                <div class="header-left">
                    <i data-lucide="lightbulb"></i>
                    <div class="header-text">
                        <h3>Insights Inteligentes</h3>
                        <p>Análise automática dos seus dados financeiros</p>
                    </div>
                </div>
                <?php if (!$isPro): ?>
                    <span class="pro-badge"><i data-lucide="crown"></i> PRO</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div id="insightsContainer" class="insights-grid">
                    <div class="lk-loading-state">
                        <i data-lucide="loader-2"></i>
                        <p>Analisando seus dados...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== SEÇÃO: COMPARATIVOS ==================== -->
    <div class="rel-section-panel" id="section-comparativos" role="tabpanel">
        <div
            class="modern-card comparatives-card surface-card surface-card--interactive surface-card--clip <?= !$isPro ? 'pro-locked' : '' ?>">
            <div class="card-header">
                <div class="header-left">
                    <i data-lucide="line-chart"></i>
                    <div class="header-text">
                        <h3>Comparativos</h3>
                        <p>Análise de evolução temporal</p>
                    </div>
                </div>
                <?php if (!$isPro): ?>
                    <span class="pro-badge"><i data-lucide="crown"></i> PRO</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$isPro): ?>
                    <div class="pro-overlay">
                        <div class="pro-message">
                            <i data-lucide="crown"></i>
                            <h4>Recurso Premium</h4>
                            <p style="font-size:0.9rem;margin:0 0 var(--spacing-4);line-height:1.5;">
                                Comparativos é exclusivo do <a href="<?= BASE_URL ?>billing"
                                    style="color:#60a5fa;text-decoration:underline">plano Pro</a>.
                            </p>
                            <a href="<?= BASE_URL ?>billing" class="btn-upgrade-cta surface-button surface-button--upgrade">
                                <i data-lucide="crown"></i> Fazer Upgrade
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div id="comparativesContainer" class="comparatives-container">
                        <div class="lk-loading-state">
                            <i data-lucide="loader-2"></i>
                            <p>Carregando comparativos...</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="rel-customize-trigger">
        <button class="rel-customize-open" id="btnCustomizeRelatorios" type="button">
            <i data-lucide="sliders-horizontal"></i>
            <span>Personalizar tela</span>
        </button>
    </div>

    <div class="rel-customize-overlay" id="relatoriosCustomizeModalOverlay" style="display:none;">
        <div class="rel-customize-modal surface-card" role="dialog" aria-modal="true"
            aria-labelledby="relatoriosCustomizeModalTitle">
            <div class="rel-customize-header">
                <h3 class="rel-customize-title" id="relatoriosCustomizeModalTitle">Personalizar relatorios</h3>
                <button class="rel-customize-close" id="btnCloseCustomizeRelatorios" type="button"
                    aria-label="Fechar personalizacao">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="rel-customize-body">
                <p class="rel-customize-desc">Comece no modo essencial e habilite os blocos quando quiser.</p>

                <div class="rel-customize-presets" role="group" aria-label="Preset de visualizacao">
                    <button class="rel-customize-preset" id="btnPresetEssencialRelatorios" type="button">Modo
                        essencial</button>
                    <button class="rel-customize-preset" id="btnPresetCompletoRelatorios" type="button">Modo
                        completo</button>
                </div>

                <div class="rel-customize-group">
                    <p class="rel-customize-group-title">Blocos da tela</p>
                    <label class="rel-customize-toggle">
                        <span>Cards de resumo rápido</span>
                        <input type="checkbox" id="toggleRelQuickStats" checked>
                    </label>
                    <label class="rel-customize-toggle">
                        <span>Mini graficos da visão geral</span>
                        <input type="checkbox" id="toggleRelOverviewCharts" checked>
                    </label>
                    <label class="rel-customize-toggle">
                        <span>Barra de controles</span>
                        <input type="checkbox" id="toggleRelControls" checked>
                    </label>
                </div>
            </div>

            <div class="rel-customize-footer">
                <button class="rel-customize-save" id="btnSaveCustomizeRelatorios" type="button">Salvar</button>
            </div>
        </div>
    </div>

    <!-- ==================== LOADING ==================== -->
    <div id="loadingParcelamentos" class="lk-loading-state" style="display: none;">
        <i data-lucide="loader-2"></i>
        <p>Carregando faturas...</p>
    </div>
</div>

<!-- Template do Modal de Detalhes do Cartão -->
<?php include BASE_PATH . '/views/admin/partials/modals/card-detail-modal.php'; ?>

<!-- ==================== SCRIPTS ==================== -->
<script>
    window.IS_PRO = <?= json_encode($isPro) ?>;
</script>
<?= vite_scripts('admin/card-modals/index.js') ?>
<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->