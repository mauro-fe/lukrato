<!-- ==================== CARD DE EXPORTAÇÃO ==================== -->
<div class="export-card" data-aos="fade-up">
    <label class="export-label" for="exportType">
        <i class="fas fa-file-export"></i>
        Exportar Relatórios
    </label>

    <div class="export-controls">
        <div class="export-controls-select">
            <div class="select-wrapper">
                <select id="exportType" class="lk-select" aria-label="Tipo de relatório para exportação">
                    <option value="despesas_por_categoria">Despesas por categoria</option>
                    <option value="receitas_por_categoria">Receitas por categoria</option>
                    <option value="saldo_mensal">Saldo diário</option>
                    <option value="receitas_despesas_diario">Receitas x Despesas diário</option>
                    <option value="evolucao_12m">Evolução 12 meses</option>
                    <option value="receitas_despesas_por_conta">Receitas x Despesas por conta</option>
                    <option value="resumo_anual">Resumo anual</option>
                    <option value="despesas_anuais_por_categoria">Despesas anuais por categoria</option>
                    <option value="receitas_anuais_por_categoria">Receitas anuais por categoria</option>
                </select>
            </div>

            <div class="select-wrapper">
                <select id="exportFormat" class="lk-select" aria-label="Formato de exportação">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel (.xlsx)</option>
                </select>
            </div>
        </div>
        <button id="exportBtn" class="btn btn-primary" aria-label="Exportar relatório">
            <i class="fas fa-download"></i>
            <span>Exportar</span>
        </button>
    </div>
</div>

<!-- ==================== HEADER COM NAVEGAÇÃO DE MÊS ==================== -->
<?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

<!-- ==================== CONTROLES ==================== -->
<section class="controls-section" data-aos="fade-up">
    <!-- Tabs de visualização -->
    <div class="tabs" role="tablist" aria-label="Tipos de relatório">
        <button class="tab-btn active" data-view="category" role="tab" aria-selected="true" aria-controls="reportArea"
            title="Visualizar relatório por categoria">
            <i class="fas fa-chart-pie"></i>
            <span>Por Categoria</span>
        </button>

        <button class="tab-btn" data-view="balance" role="tab" aria-selected="false" aria-controls="reportArea"
            title="Visualizar saldo diário">
            <i class="fas fa-chart-line"></i>
            <span>Saldo Diário</span>
        </button>

        <button class="tab-btn" data-view="comparison" role="tab" aria-selected="false" aria-controls="reportArea"
            title="Comparar receitas e despesas">
            <i class="fas fa-chart-column"></i>
            <span>Receitas x Despesas</span>
        </button>

        <button class="tab-btn" data-view="accounts" role="tab" aria-selected="false" aria-controls="reportArea"
            title="Visualizar por conta">
            <i class="fas fa-wallet"></i>
            <span>Por Conta</span>
        </button>

        <button class="tab-btn" data-view="evolution" role="tab" aria-selected="false" aria-controls="reportArea"
            title="Evolução dos últimos 12 meses">
            <i class="fas fa-timeline"></i>
            <span>Evolução 12m</span>
        </button>

        <button class="tab-btn" data-view="annual_summary" role="tab" aria-selected="false" aria-controls="reportArea"
            title="Resumo anual">
            <i class="fas fa-calendar-alt"></i>
            <span>Resumo Anual</span>
        </button>

        <button class="tab-btn" data-view="annual_category" role="tab" aria-selected="false" aria-controls="reportArea"
            title="Categorias anuais">
            <i class="fas fa-chart-pie"></i>
            <span>Categoria Anual</span>
        </button>
    </div>

    <!-- Select de tipo de relatório (visível apenas para views específicas) -->
    <div class="select-wrapper" id="typeSelectWrapper">
        <select id="reportType" class="lk-select" aria-label="Selecionar tipo de relatório">
            <option value="despesas_por_categoria">Despesas por categoria</option>
            <option value="receitas_por_categoria">Receitas por categoria</option>
        </select>
    </div>

    <!-- Filtro de conta -->
    <div class="select-wrapper hidden" id="accountSelectWrapper">
        <select id="accountFilter" class="lk-select" aria-label="Filtrar por conta">
            <option value="">Todas as contas</option>
        </select>
    </div>
</section>

<!-- ==================== ÁREA DE RELATÓRIO ==================== -->
<div data-aos="zoom-in">
    <section class="report-area fade-in" id="reportArea" role="region" aria-label="Área de visualização do relatório"
        aria-live="polite" aria-busy="false">
        <div class="loading">
            <div class="spinner" aria-label="Carregando"></div>
            <p>Carregando relatório...</p>
        </div>
    </section>
</div>

<!-- ==================== SCRIPTS ==================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>