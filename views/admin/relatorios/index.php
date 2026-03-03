<!-- Relatórios View -->
<?php $isPro = $isPro ?? false; ?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/admin-relatorios-relatorios.css.php?v=<?= time() ?>">

<div class="rel-page">
    <!-- ==================== NAVEGAÇÃO DE MÊS ==================== -->
    <?php include BASE_PATH . '/views/admin/partials/header-mes.php'; ?>

    <!-- ==================== CARDS DE RESUMO RÁPIDO ==================== -->
    <div class="quick-stats-grid">
        <div class="stat-card stat-receitas" title="Total de entradas financeiras registradas neste mês" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="trending-up"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Receitas do Mês</span>
                <span class="stat-value" id="totalReceitas">R$ 0,00</span>
                <span class="stat-hint">Total de entradas no período</span>
            </div>
        </div>

        <div class="stat-card stat-despesas" title="Total de saídas e gastos registrados neste mês" tabindex="0">
            <div class="stat-icon">
                <i data-lucide="trending-down"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Despesas do Mês</span>
                <span class="stat-value" id="totalDespesas">R$ 0,00</span>
                <span class="stat-hint">Total de saídas no período</span>
            </div>
        </div>

        <div class="stat-card stat-saldo" title="Diferença entre receitas e despesas (receitas - despesas)"
            tabindex="0">
            <div class="stat-icon">
                <i data-lucide="wallet" style="color: white"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Saldo do Mês</span>
                <span class="stat-value" id="saldoMes">R$ 0,00</span>
                <span class="stat-hint">Receitas menos despesas</span>
            </div>
        </div>

        <div class="stat-card stat-cartoes" title="Soma de todas as faturas de cartões de crédito neste mês"
            tabindex="0">
            <div class="stat-icon">
                <i data-lucide="credit-card" style="color: white"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Faturas Cartões</span>
                <span class="stat-value" id="totalCartoes">R$ 0,00</span>
                <span class="stat-hint">Gastos em cartões de crédito</span>
            </div>
        </div>
    </div>

    <!-- ==================== TABS DE SEÇÃO (estilo Perfil) ==================== -->
    <nav class="rel-section-tabs" role="tablist" aria-label="Seções de relatórios">
        <button type="button" class="rel-section-tab active" data-section="relatorios" role="tab" aria-selected="true"
            aria-controls="section-relatorios">
            <span class="tab-icon"><i data-lucide="bar-chart-3" style="color:#e67e22"></i></span>
            <span class="tab-label">Relatórios</span>
        </button>
        <button type="button" class="rel-section-tab<?= !$isPro ? ' pro-tab-locked' : '' ?>" data-section="insights"
            role="tab" aria-selected="false" aria-controls="section-insights">
            <span class="tab-icon"><i data-lucide="lightbulb" style="color:#facc15"></i></span>
            <span class="tab-label">Insights Inteligentes</span>
            <?php if (!$isPro): ?><span class="tab-pro-badge"><i data-lucide="crown"></i> PRO</span><?php endif; ?>
        </button>
        <button type="button" class="rel-section-tab<?= !$isPro ? ' pro-tab-locked' : '' ?>" data-section="comparativos"
            role="tab" aria-selected="false" aria-controls="section-comparativos">
            <span class="tab-icon"><i data-lucide="git-compare" style="color:#3b82f6"></i></span>
            <span class="tab-label">Comparativos</span>
            <?php if (!$isPro): ?><span class="tab-pro-badge"><i data-lucide="crown"></i> PRO</span><?php endif; ?>
        </button>
    </nav>

    <!-- ==================== SEÇÃO: RELATÓRIOS (padrão) ==================== -->
    <div class="rel-section-panel active" id="section-relatorios" role="tabpanel">

        <!-- Card de Exportação -->
        <div class="modern-card export-card <?= !$isPro ? 'pro-locked' : '' ?>">
            <div class="card-header">
                <div class="header-left">
                    <i data-lucide="file-output"></i>
                    <div class="header-text">
                        <h3>Exportar Relatório</h3>
                        <p>Baixe seus dados em PDF ou Excel</p>
                    </div>
                </div>
                <?php if (!$isPro): ?>
                    <span class="pro-badge">
                        <i data-lucide="crown"></i> PRO
                    </span>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <?php if (!$isPro): ?>
                    <div class="pro-overlay">
                        <div class="pro-message">
                            <i data-lucide="crown"></i>
                            <h4>Recurso Premium</h4>
                            <p style="font-size:0.9rem;margin:0 0 var(--spacing-4);line-height:1.5;">
                                Exportação de relatórios é exclusiva do <a href="<?= BASE_URL ?>billing">
                                    plano Pro.
                                </a></p>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="export-controls <?= !$isPro ? 'disabled-blur' : '' ?>">
                    <div class="form-group">
                        <label for="exportType">
                            <i data-lucide="bar-chart-3" style="color: var(--color-primary)"></i>
                            Tipo de Relatório
                        </label>
                        <select id="exportType" class="form-select" <?= !$isPro ? 'disabled' : '' ?>>
                            <option value="despesas_por_categoria">Despesas por Categoria</option>
                            <option value="receitas_por_categoria">Receitas por Categoria</option>
                            <option value="saldo_mensal">Saldo Diário</option>
                            <option value="receitas_despesas_diario">Receitas x Despesas Diário</option>
                            <option value="evolucao_12m">Evolução 12 Meses</option>
                            <option value="receitas_despesas_por_conta">Receitas x Despesas por Conta</option>
                            <option value="cartoes_credito">Relatório de Cartões</option>
                            <option value="resumo_anual">Resumo Anual</option>
                            <option value="despesas_anuais_por_categoria">Despesas Anuais por Categoria</option>
                            <option value="receitas_anuais_por_categoria">Receitas Anuais por Categoria</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="exportFormat">
                            <i data-lucide="file" style="color: var(--color-primary)"></i>
                            Formato
                        </label>
                        <select id="exportFormat" class="form-select" <?= !$isPro ? 'disabled' : '' ?>>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel (.xlsx)</option>
                        </select>
                    </div>

                    <button id="exportBtn" class="btn btn-primary btn-export" <?= !$isPro ? 'disabled' : '' ?>>
                        <i data-lucide="download"></i>
                        <span>Exportar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs de Visualização de Gráficos -->
        <div class="modern-card tabs-card">
            <div class="tabs-container" role="tablist">
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

        <!-- Controles Adicionais -->
        <div class="controls-row">
            <div class="control-group hidden" id="typeSelectWrapper">
                <label for="reportType">
                    <i data-lucide="filter" style="color: var(--color-primary)"></i>
                    Filtrar por
                </label>
                <select id="reportType" class="form-select">
                    <option value="despesas_por_categoria">Despesas por Categoria</option>
                    <option value="receitas_por_categoria">Receitas por Categoria</option>
                </select>
            </div>

            <div class="control-group hidden" id="accountSelectWrapper">
                <label for="accountFilter">
                    <i data-lucide="landmark" style="color: var(--color-primary)"></i>
                    Conta
                </label>
                <select id="accountFilter" class="form-select">
                    <option value="">Todas as Contas</option>
                </select>
            </div>

            <div class="control-group" id="clearFiltersWrapper" style="display:none; align-items: flex-end;">
                <button type="button" id="btnLimparFiltrosRel" class="btn btn-secondary"
                    title="Resetar filtros para padrão" style="white-space: nowrap;">
                    <i data-lucide="eraser"></i>
                    Limpar Filtros
                </button>
            </div>
        </div>

        <!-- Área de Relatório / Gráfico -->
        <div class="modern-card report-card">
            <div class="report-area" id="reportArea">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Carregando relatório...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== SEÇÃO: INSIGHTS INTELIGENTES ==================== -->
    <div class="rel-section-panel" id="section-insights" role="tabpanel">
        <div class="modern-card insights-card <?= !$isPro ? 'pro-locked' : '' ?>">
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
                <?php if (!$isPro): ?>
                    <div class="pro-overlay">
                        <div class="pro-message">
                            <i data-lucide="crown"></i>
                            <h4>Recurso Premium</h4>
                            <p style="font-size:0.9rem;margin:0 0 var(--spacing-4);line-height:1.5;">
                                Insights Inteligentes é exclusivo do <a href="<?= BASE_URL ?>billing"
                                    style="color:#60a5fa;text-decoration:underline">plano Pro</a>.
                            </p>
                            <a href="<?= BASE_URL ?>billing" class="btn-upgrade-cta">
                                <i data-lucide="crown"></i> Fazer Upgrade
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div id="insightsContainer" class="insights-grid">
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <p>Analisando seus dados...</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ==================== SEÇÃO: COMPARATIVOS ==================== -->
    <div class="rel-section-panel" id="section-comparativos" role="tabpanel">
        <div class="modern-card comparatives-card <?= !$isPro ? 'pro-locked' : '' ?>">
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
                            <a href="<?= BASE_URL ?>billing" class="btn-upgrade-cta">
                                <i data-lucide="crown"></i> Fazer Upgrade
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div id="comparativesContainer" class="comparatives-container">
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <p>Carregando comparativos...</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ==================== LOADING ==================== -->
    <div id="loadingParcelamentos" class="loading-container" style="display: none;">
        <div class="loading-spinner">
            <i data-lucide="loader-2" class="icon-spin"></i>
            <p>Carregando faturas...</p>
        </div>
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