<!-- Relatórios View -->
<?php $isPro = $isPro ?? false; ?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-relatorios-relatorios.css?v=<?= time() ?>">

<div class="rel-page">
    <!-- ==================== NAVEGAÇÃO DE MÊS ==================== -->
    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

    <!-- ==================== CARDS DE RESUMO RÁPIDO ==================== -->
    <div class="quick-stats-grid">
        <div class="stat-card stat-receitas">
            <div class="stat-icon">
                <i class="fas fa-arrow-trend-up"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Receitas do Mês</span>
                <span class="stat-value" id="totalReceitas">R$ 0,00</span>
                <span class="stat-hint">Total de entradas no período</span>
            </div>
        </div>

        <div class="stat-card stat-despesas">
            <div class="stat-icon">
                <i class="fas fa-arrow-trend-down"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Despesas do Mês</span>
                <span class="stat-value" id="totalDespesas">R$ 0,00</span>
                <span class="stat-hint">Total de saídas no período</span>
            </div>
        </div>

        <div class="stat-card stat-saldo">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Saldo do Mês</span>
                <span class="stat-value" id="saldoMes">R$ 0,00</span>
                <span class="stat-hint">Receitas menos despesas</span>
            </div>
        </div>

        <div class="stat-card stat-cartoes">
            <div class="stat-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Faturas Cartões</span>
                <span class="stat-value" id="totalCartoes">R$ 0,00</span>
                <span class="stat-hint">Gastos em cartões de crédito</span>
            </div>
        </div>
    </div>

    <!-- ==================== INSIGHTS AUTOMÁTICOS ==================== -->
    <div class="modern-card insights-card">
        <div class="card-header">
            <div class="header-left">
                <i class="fas fa-lightbulb"></i>
                <div class="header-text">
                    <h3>Insights Inteligentes</h3>
                    <p>Análise automática dos seus dados financeiros</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="insightsContainer" class="insights-grid">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Analisando seus dados...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== COMPARATIVOS ==================== -->
    <div class="modern-card comparatives-card">
        <div class="card-header">
            <div class="header-left">
                <i class="fas fa-chart-line"></i>
                <div class="header-text">
                    <h3>Comparativos</h3>
                    <p>Análise de evolução temporal</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="comparativesContainer" class="comparatives-container">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Carregando comparativos...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== CARD DE EXPORTAÇÃO ==================== -->
    <div class="modern-card export-card <?= !$isPro ? 'pro-locked' : '' ?>">
        <div class="card-header">
            <div class="header-left">
                <i class="fas fa-file-export"></i>
                <div class="header-text">
                    <h3>Exportar Relatório</h3>
                    <p>Baixe seus dados em PDF ou Excel</p>
                </div>
            </div>
            <?php if (!$isPro): ?>
                <span class="pro-badge">
                    <i class="fas fa-crown"></i> PRO
                </span>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <?php if (!$isPro): ?>
                <div class="pro-overlay">
                    <div class="pro-message">
                        <i class="fas fa-crown"></i>
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
                        <i class="fas fa-chart-bar"></i>
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
                        <i class="fas fa-file"></i>
                        Formato
                    </label>
                    <select id="exportFormat" class="form-select" <?= !$isPro ? 'disabled' : '' ?>>
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel (.xlsx)</option>
                    </select>
                </div>

                <button id="exportBtn" class="btn btn-primary btn-export" <?= !$isPro ? 'disabled' : '' ?>>
                    <i class="fas fa-download"></i>
                    <span>Exportar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== TABS DE VISUALIZAÇÃO ==================== -->
    <div class="modern-card tabs-card">
        <div class="tabs-container" role="tablist">
            <button class="tab-btn active" data-view="category" role="tab">
                <i class="fas fa-chart-pie"></i>
                <span>Por Categoria</span>
            </button>

            <button class="tab-btn" data-view="balance" role="tab">
                <i class="fas fa-chart-line"></i>
                <span>Saldo Diário</span>
            </button>

            <button class="tab-btn" data-view="comparison" role="tab">
                <i class="fas fa-chart-column"></i>
                <span>Receitas x Despesas</span>
            </button>

            <button class="tab-btn" data-view="accounts" role="tab">
                <i class="fas fa-wallet"></i>
                <span>Por Conta</span>
            </button>

            <button class="tab-btn" data-view="cards" role="tab">
                <i class="fas fa-credit-card"></i>
                <span>Cartões de Crédito</span>
            </button>

            <button class="tab-btn" data-view="evolution" role="tab">
                <i class="fas fa-timeline"></i>
                <span>Evolução 12m</span>
            </button>

            <button class="tab-btn" data-view="annual_summary" role="tab">
                <i class="fas fa-calendar-alt"></i>
                <span>Resumo Anual</span>
            </button>

            <button class="tab-btn" data-view="annual_category" role="tab">
                <i class="fas fa-chart-pie"></i>
                <span>Categoria Anual</span>
            </button>
        </div>
    </div>

    <!-- ==================== CONTROLES ADICIONAIS ==================== -->
    <div class="controls-row">
        <!-- Select de tipo de relatório (visível para views específicas) -->
        <div class="control-group hidden" id="typeSelectWrapper">
            <label for="reportType">
                <i class="fas fa-filter"></i>
                Filtrar por
            </label>
            <select id="reportType" class="form-select">
                <option value="despesas_por_categoria">Despesas por Categoria</option>
                <option value="receitas_por_categoria">Receitas por Categoria</option>
            </select>
        </div>

        <!-- Filtro de conta -->
        <div class="control-group hidden" id="accountSelectWrapper">
            <label for="accountFilter">
                <i class="fas fa-building-columns"></i>
                Conta
            </label>
            <select id="accountFilter" class="form-select">
                <option value="">Todas as Contas</option>
            </select>
        </div>
    </div>

    <!-- ==================== ÁREA DE RELATÓRIO ==================== -->
    <div class="modern-card report-card">
        <div class="report-area" id="reportArea">
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Carregando relatório...</p>
            </div>
        </div>
    </div>
</div>

<!-- Template do Modal de Detalhes do Cartão -->
<?php include BASE_PATH . '/views/modals/card-detail-modal.php'; ?>

<!-- ==================== SCRIPTS ==================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/card-modal-renderers.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>assets/js/card-detail-modal-refactored.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>assets/js/admin-relatorios-relatorios.js"></script>