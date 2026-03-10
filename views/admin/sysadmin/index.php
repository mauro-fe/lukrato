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
                <p class="stat-label">Usuários Totais</p>
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
                    Com permissões
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
            <a href="#" class="stat-link" data-action="switchTab" data-tab="logs">Ver Logs <i data-lucide="arrow-right"></i></a>
        </div>
    </div>

    <!-- Tab Navigation -->
    <nav class="sysadmin-tabs" role="tablist" aria-label="Seções do painel">
        <button type="button" class="sysadmin-tab active" data-tab="dashboard" role="tab" aria-selected="true" aria-controls="panel-dashboard">
            <span class="tab-icon"><i data-lucide="line-chart" style="color:#3b82f6"></i></span>
            <span class="tab-label">Visão Geral</span>
        </button>
        <button type="button" class="sysadmin-tab" data-tab="controle" role="tab" aria-selected="false" aria-controls="panel-controle">
            <span class="tab-icon"><i data-lucide="sliders-horizontal" style="color:#8b5cf6"></i></span>
            <span class="tab-label">Controle</span>
        </button>
        <button type="button" class="sysadmin-tab" data-tab="usuarios" role="tab" aria-selected="false" aria-controls="panel-usuarios">
            <span class="tab-icon"><i data-lucide="users" style="color:#10b981"></i></span>
            <span class="tab-label">Usuários</span>
        </button>
        <button type="button" class="sysadmin-tab" data-tab="ia" role="tab" aria-selected="false" aria-controls="panel-ia">
            <span class="tab-icon"><i data-lucide="bot" style="color:#8b5cf6"></i></span>
            <span class="tab-label">IA</span>
        </button>
        <button type="button" class="sysadmin-tab tab-danger" data-tab="logs" role="tab" aria-selected="false" aria-controls="panel-logs">
            <span class="tab-icon"><i data-lucide="shield-alert" style="color:#e74c3c"></i></span>
            <span class="tab-label">Logs</span>
        </button>
    </nav>

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
    </div>

    <!-- Tab Panel: Control -->
    <div class="sysadmin-tab-panel" id="panel-controle" role="tabpanel" aria-labelledby="tab-controle">
        <!-- Control Panel -->
        <div class="control-section">
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
                            <h3>Manutenção e Limpeza</h3>
                            <p>Ferramentas para saúde do servidor</p>
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

                <!-- Blog / Aprenda Card -->
                <div class="control-card">
                    <div class="control-header">
                        <i data-lucide="book-open" style="color: #f97316;"></i>
                        <div>
                            <h3>Blog / Aprenda</h3>
                            <p>Gerencie artigos educacionais</p>
                        </div>
                    </div>
                    <div class="control-actions">
                        <button class="btn-control primary" data-action="navigateTo" data-href="<?= BASE_URL ?>sysadmin/blog">
                            <i data-lucide="pen-line"></i>
                            Gerenciar Blog
                        </button>
                    </div>
                </div>

                <!-- Grant Access Card -->
                <div class="control-card">
                    <div class="control-header">
                        <i data-lucide="gift"></i>
                        <div>
                            <h3>Liberar Acesso Premium</h3>
                            <p>Conceda acesso Pro ou Ultra temporário</p>
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
                            <h3>Remover Acesso Premium</h3>
                            <p>Revogue o acesso Pro ou Ultra de um usuário</p>
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
    </div>

    <!-- Tab Panel: Logs -->
    <div class="sysadmin-tab-panel" id="panel-logs" role="tabpanel" aria-labelledby="tab-logs">
        <!-- =============================================== -->
        <!-- ERROR LOGS SECTION - Real-time Monitoring      -->
        <!-- =============================================== -->
        <div class="error-logs-section" id="errorLogsSection">
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
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando logs...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Panel: Users -->
    <div class="sysadmin-tab-panel" id="panel-usuarios" role="tabpanel" aria-labelledby="tab-usuarios">
        <!-- Filtros de Usuários -->
        <div class="user-filters-card">
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
        <div class="table-section" id="userTableSection">
            <!-- Conteúdo da tabela será renderizado via JS -->
        </div>
    </div>

    <!-- Tab Panel: IA -->
    <div class="sysadmin-tab-panel" id="panel-ia" role="tabpanel" aria-labelledby="tab-ia">
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.25rem;padding:3rem 1rem;text-align:center;">
            <div style="width:64px;height:64px;background:#ede9fe;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i data-lucide="bot" style="width:32px;height:32px;color:#7c3aed;"></i>
            </div>
            <div>
                <h2 style="font-size:1.3rem;font-weight:700;margin:0 0 .5rem;">Assistente de Inteligência Artificial</h2>
                <p style="color:#6b7280;margin:0;max-width:440px;">Chat interativo com IA, sugestão automática de categorias e análise de padrões financeiros dos seus usuários.</p>
            </div>
            <a href="<?= BASE_URL ?>sysadmin/ai" style="display:inline-flex;align-items:center;gap:.5rem;padding:.65rem 1.5rem;background:#7c3aed;color:#fff;border-radius:.625rem;text-decoration:none;font-weight:600;font-size:.9rem;transition:background .15s;"
                onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">
                <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                Abrir Assistente IA
            </a>
            <a href="<?= BASE_URL ?>sysadmin/ai/logs" style="display:inline-flex;align-items:center;gap:.5rem;padding:.65rem 1.5rem;background:transparent;color:#7c3aed;border:1px solid #7c3aed;border-radius:.625rem;text-decoration:none;font-weight:600;font-size:.9rem;transition:background .15s,color .15s;"
                onmouseover="this.style.background='#7c3aed';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='#7c3aed'">
                <i data-lucide="file-text" style="width:16px;height:16px;"></i>
                Logs da IA
            </a>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;justify-content:center;margin-top:.5rem;">
                <div style="text-align:center;">
                    <div style="font-size:1.4rem;font-weight:700;color:#7c3aed;">3</div>
                    <div style="font-size:.78rem;color:#9ca3af;">Endpoints de IA</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:1.4rem;font-weight:700;color:#7c3aed;">gpt-4o-mini</div>
                    <div style="font-size:.78rem;color:#9ca3af;">Modelo padrão</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:1.4rem;font-weight:700;color:#7c3aed;">800ms</div>
                    <div style="font-size:.78rem;color:#9ca3af;">Debounce sugestão</div>
                </div>
            </div>
        </div>
    </div>
</div>