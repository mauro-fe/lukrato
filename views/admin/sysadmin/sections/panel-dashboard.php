<!-- Tab Panel: Dashboard / Analytics -->
<div class="sysadmin-tab-panel active" id="panel-dashboard" role="tabpanel" aria-labelledby="tab-dashboard">
    <!-- Analytics Section -->
    <div class="analytics-section">
        <h2 class="section-title">
            <i data-lucide="line-chart"></i>
            Estatísticas e Métricas
            <button class="btn-refresh-stats" data-action="loadStats" title="Atualizar estatísticas">
                <i data-lucide="refresh-cw"></i>
            </button>
        </h2>

        <!-- Stats Overview Cards -->
        <div class="stats-overview" id="statsOverview">
            <div class="overview-card">
                <div class="overview-icon pro">
                    <i data-lucide="crown"></i>
                </div>
                <div class="overview-content">
                    <span class="overview-value" id="statProUsers">-</span>
                    <span class="overview-label">Usuários PRO</span>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon free">
                    <i data-lucide="user"></i>
                </div>
                <div class="overview-content">
                    <span class="overview-value" id="statFreeUsers">-</span>
                    <span class="overview-label">Usuários Gratuitos</span>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon conversion">
                    <i data-lucide="percent"></i>
                </div>
                <div class="overview-content">
                    <span class="overview-value" id="statConversionRate">-</span>
                    <span class="overview-label">Taxa de Conversão</span>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon growth">
                    <i data-lucide="line-chart"></i>
                </div>
                <div class="overview-content">
                    <span class="overview-value" id="statGrowthRate">-</span>
                    <span class="overview-label">Crescimento Mensal</span>
                </div>
            </div>
        </div>

        <!-- New Users Summary -->
        <div class="new-users-summary">
            <div class="summary-item">
                <i data-lucide="calendar"></i>
                <span class="summary-value" id="statNewToday">-</span>
                <span class="summary-label">Novos Hoje</span>
            </div>
            <div class="summary-item">
                <i data-lucide="calendar-range"></i>
                <span class="summary-value" id="statNewWeek">-</span>
                <span class="summary-label">Esta Semana</span>
            </div>
            <div class="summary-item">
                <i data-lucide="calendar-days"></i>
                <span class="summary-value" id="statNewMonth">-</span>
                <span class="summary-label">Este Mês</span>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Line Chart - Users by Day -->
            <div class="chart-card large">
                <div class="chart-header">
                    <h3><i data-lucide="area-chart"></i> Novos Usuários (Últimos 30 dias)</h3>
                </div>
                <div class="chart-body">
                    <div id="usersByDayChart"></div>
                </div>
            </div>

            <!-- Pie Chart - User Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i data-lucide="pie-chart"></i> Distribuição de Usuários</h3>
                </div>
                <div class="chart-body">
                    <div id="userDistributionChart"></div>
                </div>
            </div>

            <!-- Doughnut Chart - Subscriptions by Gateway -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i data-lucide="credit-card"></i> Assinaturas por Gateway</h3>
                </div>
                <div class="chart-body">
                    <div id="subscriptionsByGatewayChart"></div>
                </div>
            </div>
        </div>
    </div>
</div>