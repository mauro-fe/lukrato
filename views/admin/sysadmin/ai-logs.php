<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/sysadmin-modern.css.php?v=<?= time() ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/pages/ai-logs.css?v=<?= time() ?>">

<div class="sysadmin-container logs-page">

    <!-- Header -->
    <div class="logs-header">
        <div class="header-content">
            <h1>
                <i data-lucide="file-text"></i>
                Logs da IA
            </h1>
            <p>Histórico de interações, métricas de uso e custos estimados</p>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;">
            <a href="<?= BASE_URL ?>sysadmin/ai" class="btn-back">
                <i data-lucide="bot" style="width:15px;height:15px;"></i>
                Assistente IA
            </a>
            <a href="<?= BASE_URL ?>sysadmin" class="btn-back">
                <i data-lucide="arrow-left"></i>
                Voltar ao Painel
            </a>
        </div>
    </div>

    <!-- Metrics -->
    <div class="metrics-grid" id="metricsGrid">
        <div class="metric-card">
            <div class="metric-value" id="metricTotal">—</div>
            <div class="metric-label">Total (24h)</div>
        </div>
        <div class="metric-card success">
            <div class="metric-value" id="metricSuccess">—</div>
            <div class="metric-label">Taxa de Sucesso</div>
        </div>
        <div class="metric-card">
            <div class="metric-value" id="metricTokens">—</div>
            <div class="metric-label">Tokens Usados</div>
        </div>
        <div class="metric-card warning">
            <div class="metric-value" id="metricCost">—</div>
            <div class="metric-label">Custo Estimado</div>
        </div>
        <div class="metric-card">
            <div class="metric-value" id="metricAvgTime">—</div>
            <div class="metric-label">Tempo Médio</div>
        </div>
        <div class="metric-card danger">
            <div class="metric-value" id="metricErrors">—</div>
            <div class="metric-label">Erros (24h)</div>
        </div>
    </div>

    <!-- Quota OpenAI -->
    <div class="quota-section" id="quotaSection">
        <h2>
            <i data-lucide="gauge" style="width:18px;height:18px;color:var(--blue-600);"></i>
            Quota OpenAI
            <span class="quota-status-badge loading" id="quotaStatus">Verificando...</span>
        </h2>
        <div class="quota-grid" id="quotaGrid">
            <div class="quota-item">
                <div class="quota-label">Requisições</div>
                <div class="quota-bar-wrap">
                    <div class="quota-bar" id="quotaReqBar" style="width:0%;background:var(--blue-600);"></div>
                </div>
                <div class="quota-values"><strong id="quotaReqRemaining">—</strong> / <span id="quotaReqLimit">—</span>
                </div>
            </div>
            <div class="quota-item">
                <div class="quota-label">Tokens</div>
                <div class="quota-bar-wrap">
                    <div class="quota-bar" id="quotaTokBar" style="width:0%;background:var(--color-success);"></div>
                </div>
                <div class="quota-values"><strong id="quotaTokRemaining">—</strong> / <span id="quotaTokLimit">—</span>
                </div>
            </div>
        </div>
        <div class="quota-reset" id="quotaReset" style="display:none;"></div>
        <div class="quota-msg" id="quotaMsg" style="display:none;"></div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <select id="filterType">
            <option value="">Todos os tipos</option>
            <option value="chat">Chat</option>
            <option value="suggest_category">Sugestão Categoria</option>
            <option value="analyze_spending">Análise Gastos</option>
        </select>
        <select id="filterSuccess">
            <option value="">Todos os status</option>
            <option value="1">Sucesso</option>
            <option value="0">Erro</option>
        </select>
        <input type="date" id="filterDateFrom" title="Data início">
        <input type="date" id="filterDateTo" title="Data fim">
        <input type="text" id="filterSearch" placeholder="Buscar no prompt/resposta..." style="min-width:180px;">
        <button class="btn-filter" id="btnFilter">
            <i data-lucide="search" style="width:14px;height:14px;vertical-align:middle;margin-right:.2rem;"></i>
            Filtrar
        </button>
        <button class="btn-cleanup" id="btnCleanup" title="Limpar logs antigos">
            <i data-lucide="trash-2" style="width:14px;height:14px;vertical-align:middle;margin-right:.2rem;"></i>
            Limpar +90 dias
        </button>
    </div>

    <!-- Table -->
    <div class="logs-table-wrap">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Prompt</th>
                    <th>Tokens</th>
                    <th>Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="logsBody">
                <tr>
                    <td colspan="6">
                        <div class="logs-empty"><i data-lucide="loader"></i> Carregando...</div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="pagination" id="pagination"></div>
    </div>

</div>

<!-- JS carregado via Vite (loadPageJs) -->