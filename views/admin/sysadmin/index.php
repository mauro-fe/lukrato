<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/sysadmin-modern.css.php?v=<?= time() ?>">

<div class="sysadmin-container">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <!-- Total Users Card -->
        <div class="stat-card" data-aos="fade-up" data-aos-delay="0">
            <div class="stat-icon users">
                <i data-lucide="users"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="total-users"><?= number_format($metrics['totalUsers'] ?? 0, 0, ',', '.') ?>
                </h3>
                <p class="stat-label">Usuarios Totais</p>
                <span class="stat-badge positive">
                    <i data-lucide="arrow-up"></i>
                    +<?= number_format($metrics['newToday'] ?? 0, 0, ',', '.') ?> hoje
                </span>
            </div>
        </div>

        <!-- Admins Card -->
        <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-icon admins">
                <i data-lucide="shield-check"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?= number_format($metrics['totalAdmins'] ?? 0, 0, ',', '.') ?></h3>
                <p class="stat-label">Admins Ativos</p>
                <span class="stat-badge success">
                    <i data-lucide="circle-check"></i>
                    Com permissoes
                </span>
            </div>
        </div>

        <!-- Error Logs Card -->
        <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
            <div class="stat-icon errors">
                <i data-lucide="triangle-alert"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="stat-error-total">–</h3>
                <p class="stat-label">Logs de Erro</p>
                <span class="stat-badge warning" id="stat-error-badge">
                    <i data-lucide="clock"></i>
                    <span id="stat-error-unresolved">Carregando...</span>
                </span>
            </div>
            <a href="#errorLogsSection" class="stat-link" data-action="scrollTo" data-target="errorLogsSection">Ver Logs <i data-lucide="arrow-right"></i></a>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="analytics-section" data-aos="fade-up" data-aos-delay="250">
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
                    <canvas id="usersByDayChart"></canvas>
                </div>
            </div>

            <!-- Pie Chart - User Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i data-lucide="pie-chart"></i> Distribuição de Usuários</h3>
                </div>
                <div class="chart-body">
                    <canvas id="userDistributionChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart - Subscriptions by Gateway -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i data-lucide="credit-card"></i> Assinaturas por Gateway</h3>
                </div>
                <div class="chart-body">
                    <canvas id="subscriptionsByGatewayChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <div class="control-section" data-aos="fade-up" data-aos-delay="300">
        <h2 class="section-title">
            <i data-lucide="sliders-horizontal"></i>
            Controle Mestre
        </h2>

        <div class="control-grid">
            <!-- Maintenance Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="wrench"></i>
                    <div>
                        <h3>Manutencao e Limpeza</h3>
                        <p>Ferramentas para saude do servidor</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control primary" data-action="limparCache">
                        <i data-lucide="paintbrush"></i>
                        Limpar Cache do Sistema
                    </button>
                    <button class="btn-control danger" id="btnMaintenance" data-action="toggleMaintenance">
                        <i data-lucide="wrench" id="btnMaintenanceIcon"></i>
                        <span id="btnMaintenanceText">Verificando...</span>
                    </button>
                </div>
            </div>

            <!-- User Search Card -->


            <!-- Cupons de Desconto Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="ticket"></i>
                    <div>
                        <h3>Cupons de Desconto</h3>
                        <p>Gerenciar cupons promocionais</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control primary" data-action="navigateTo" data-href="<?= BASE_URL ?>sysadmin/cupons">
                        <i data-lucide="ticket"></i>
                        Gerenciar Cupons
                    </button>
                </div>
            </div>

            <!-- Comunicações Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="megaphone" style="color: #f59e0b;"></i>
                    <div>
                        <h3>Comunicações</h3>
                        <p>Envie mensagens e campanhas</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control primary btn-campanhas" data-action="navigateTo" data-href="<?= BASE_URL ?>sysadmin/comunicacoes">
                        <i data-lucide="send"></i>
                        Gerenciar Campanhas
                    </button>
                </div>
            </div>

            <!-- Grant Access Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="gift"></i>
                    <div>
                        <h3>Liberar Acesso PRO</h3>
                        <p>Conceda acesso premium temporário</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control success" data-action="openGrantAccessModal">
                        <i data-lucide="crown"></i>
                        Liberar Acesso
                    </button>
                </div>
            </div>

            <!-- Revoke Access Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="ban"></i>
                    <div>
                        <h3>Remover Acesso PRO</h3>
                        <p>Revogue o acesso premium de um usuário</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control danger" data-action="openRevokeAccessModal">
                        <i data-lucide="user-x"></i>
                        Remover Acesso
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================================== -->
    <!-- ERROR LOGS SECTION - Real-time Monitoring      -->
    <!-- =============================================== -->
    <div class="error-logs-section" id="errorLogsSection" data-aos="fade-up" data-aos-delay="320">
        <h2 class="section-title">
            <i data-lucide="shield-alert"></i>
            Logs de Erro em Tempo Real
            <div class="error-logs-title-actions">
                <label class="auto-refresh-toggle" title="Atualização automática">
                    <input type="checkbox" id="errorLogsAutoRefresh" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">Auto</span>
                </label>
                <button class="btn-refresh-stats" data-action="loadErrorLogs" title="Atualizar logs">
                    <i data-lucide="refresh-cw" id="errorLogsRefreshIcon"></i>
                </button>
            </div>
        </h2>

        <!-- Error Level Summary Cards -->
        <div class="error-level-cards" id="errorLevelCards">
            <div class="error-level-card level-critical">
                <div class="error-level-icon"><i data-lucide="zap"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelCritical">0</span>
                    <span class="error-level-label">Critical</span>
                </div>
            </div>
            <div class="error-level-card level-error">
                <div class="error-level-icon"><i data-lucide="x-circle"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelError">0</span>
                    <span class="error-level-label">Error</span>
                </div>
            </div>
            <div class="error-level-card level-warning">
                <div class="error-level-icon"><i data-lucide="alert-triangle"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelWarning">0</span>
                    <span class="error-level-label">Warning</span>
                </div>
            </div>
            <div class="error-level-card level-info">
                <div class="error-level-icon"><i data-lucide="info"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelInfo">0</span>
                    <span class="error-level-label">Info</span>
                </div>
            </div>
            <div class="error-level-card level-unresolved">
                <div class="error-level-icon"><i data-lucide="clock"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelUnresolved">0</span>
                    <span class="error-level-label">Não Resolvidos</span>
                </div>
            </div>
        </div>

        <!-- Error Logs Filters -->
        <div class="error-logs-filters">
            <form id="errorLogsFilters" class="error-logs-filters-form">
                <input type="text" name="search" class="filter-input" placeholder="Buscar mensagem, classe, arquivo..." id="errorLogSearch">
                <select name="level" class="filter-select" id="errorLogLevel">
                    <option value="">Todos os Níveis</option>
                </select>
                <select name="category" class="filter-select" id="errorLogCategory">
                    <option value="">Todas as Categorias</option>
                </select>
                <select name="resolved" class="filter-select" id="errorLogResolved">
                    <option value="">Todos</option>
                    <option value="0" selected>Não Resolvidos</option>
                    <option value="1">Resolvidos</option>
                </select>
                <select name="per_page" class="filter-select" id="errorLogPerPage">
                    <option value="15">15 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                </select>
                <button type="submit" class="btn-control primary"><i data-lucide="filter"></i> Filtrar</button>
                <button type="button" class="btn-control danger" data-action="confirmCleanupLogs" title="Limpar logs antigos resolvidos">
                    <i data-lucide="trash-2"></i> Limpar
                </button>
            </form>
        </div>

        <!-- Error Logs Table -->
        <div class="error-logs-table-wrapper" id="errorLogsTableWrapper">
            <div class="error-logs-loading">
                <i data-lucide="loader-2" class="icon-spin"></i>
                <span>Carregando logs...</span>
            </div>
        </div>
    </div>

    <!-- Filtros de Usuários -->
    <div class="user-filters-card" data-aos="fade-up" data-aos-delay="350">
        <form id="userFilters" class="user-filters-form">
            <input type="text" name="query" class="filter-input" placeholder="Buscar por nome, email ou ID..." />
            <select name="status" class="filter-select">
                <option value="">Todos</option>
                <option value="admin">Admin</option>
                <option value="user">Usuário</option>
            </select>
            <select name="plan" class="filter-select">
                <option value="">Todos os Planos</option>
                <option value="pro">⭐ Pro</option>
                <option value="free">Free</option>
            </select>
            <select name="perPage" class="filter-select">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
            </select>
            <button type="submit" class="btn-control primary"><i data-lucide="filter"></i> Filtrar</button>
        </form>
    </div>

    <!-- Tabela dinâmica de usuários -->
    <div class="table-section" id="userTableSection" data-aos="fade-up" data-aos-delay="400">
        <!-- Conteúdo da tabela será renderizado via JS -->
    </div>
</div>