<!-- Relatórios View -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/relatorios-modern.css">

<div class="rel-page">
    <!-- ==================== HEADER COM TÍTULO ==================== -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-chart-line"></i>
            <span>Relatórios</span>
        </h1>
        <p class="page-subtitle">Análise detalhada das suas finanças</p>
    </div>

    <!-- ==================== CARD DE EXPORTAÇÃO ==================== -->
    <div class="modern-card export-card">
        <div class="card-header">
            <div class="header-left">
                <i class="fas fa-file-export"></i>
                <div class="header-text">
                    <h3>Exportar Relatório</h3>
                    <p>Baixe seus dados em PDF ou Excel</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="export-controls">
                <div class="form-group">
                    <label for="exportType">
                        <i class="fas fa-chart-bar"></i>
                        Tipo de Relatório
                    </label>
                    <select id="exportType" class="form-select">
                        <option value="despesas_por_categoria">Despesas por Categoria</option>
                        <option value="receitas_por_categoria">Receitas por Categoria</option>
                        <option value="saldo_mensal">Saldo Diário</option>
                        <option value="receitas_despesas_diario">Receitas x Despesas Diário</option>
                        <option value="evolucao_12m">Evolução 12 Meses</option>
                        <option value="receitas_despesas_por_conta">Receitas x Despesas por Conta</option>
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
                    <select id="exportFormat" class="form-select">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel (.xlsx)</option>
                    </select>
                </div>

                <button id="exportBtn" class="btn btn-primary btn-export">
                    <i class="fas fa-download"></i>
                    <span>Exportar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== NAVEGAÇÃO DE MÊS ==================== -->
    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

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

<!-- ==================== SCRIPTS ==================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/admin-relatorios-relatorios.js"></script>
