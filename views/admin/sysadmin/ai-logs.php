<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bundles/sysadmin-modern.css.php?v=<?= time() ?>">

<style>
    .logs-page {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-6);
    }

    .logs-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--spacing-4);
    }

    .logs-header .header-content h1 {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        font-size: var(--font-size-2xl);
        font-weight: 700;
        margin: 0;
    }

    .logs-header .header-content p {
        color: var(--color-text-muted);
        margin: .25rem 0 0;
        font-size: var(--font-size-sm);
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem 1rem;
        border-radius: var(--radius-sm);
        border: 1px solid var(--color-card-border);
        color: var(--color-text);
        font-size: var(--font-size-sm);
        text-decoration: none;
        transition: background var(--transition-fast);
    }

    .btn-back:hover {
        background: var(--color-surface-muted) !important;
    }

    /* Metrics */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
    }

    .metric-card {
        background: var(--glass-bg);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-lg);
        padding: 1.1rem 1.25rem;
        box-shadow: var(--shadow-sm);
        text-align: center;
    }

    .metric-card .metric-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--blue-600);
        margin: 0;
    }

    .metric-card .metric-label {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        margin-top: .2rem;
    }

    .metric-card.success .metric-value {
        color: var(--color-success);
    }

    .metric-card.danger .metric-value {
        color: var(--color-danger);
    }

    .metric-card.warning .metric-value {
        color: var(--color-warning);
    }

    /* Filters */
    .filters-bar {
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
        align-items: center;
        background: var(--glass-bg);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-lg);
        padding: .875rem 1.25rem;
        box-shadow: var(--shadow-sm);
    }

    .filters-bar select,
    .filters-bar input {
        padding: .45rem .75rem;
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-sm);
        font-size: var(--font-size-sm);
        background: var(--color-bg);
        color: var(--color-text);
        outline: none;
    }

    .filters-bar select:focus,
    .filters-bar input:focus {
        border-color: var(--blue-500);
        box-shadow: 0 0 0 3px rgba(95, 151, 238, .18);
    }

    .filters-bar .btn-filter {
        padding: .45rem 1rem;
        background: var(--blue-600);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: var(--font-size-sm);
        font-weight: 600;
        transition: background var(--transition-fast);
    }

    .filters-bar .btn-filter:hover {
        background: var(--blue-700);
    }

    .filters-bar .btn-cleanup {
        padding: .45rem 1rem;
        background: transparent;
        color: var(--color-danger);
        border: 1px solid var(--color-danger);
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: var(--font-size-sm);
        font-weight: 600;
        margin-left: auto;
        transition: background var(--transition-fast);
    }

    .filters-bar .btn-cleanup:hover {
        background: var(--color-danger);
        color: #fff;
    }

    /* Table */
    .logs-table-wrap {
        background: var(--glass-bg);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .logs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: var(--font-size-sm);
    }

    .logs-table th {
        background: var(--color-surface-muted);
        padding: .7rem 1rem;
        text-align: left;
        font-weight: 600;
        font-size: var(--font-size-xs);
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--color-text-muted);
        border-bottom: 1px solid var(--color-card-border);
    }

    .logs-table td {
        padding: .65rem 1rem;
        border-bottom: 1px solid var(--color-card-border);
        vertical-align: top;
    }

    .logs-table tr:last-child td {
        border-bottom: none;
    }

    .logs-table tr:hover {
        background: var(--color-surface-muted);
    }

    .badge-type {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .15rem .55rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 600;
    }

    .badge-type.chat {
        background: var(--blue-100);
        color: var(--blue-700);
    }

    .badge-type.suggest_category {
        background: rgba(124, 58, 237, .12);
        color: #7c3aed;
    }

    .badge-type.analyze_spending {
        background: rgba(16, 185, 129, .12);
        color: var(--color-success);
    }

    .badge-status {
        display: inline-block;
        padding: .15rem .5rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 600;
    }

    .badge-status.ok {
        background: rgba(16, 185, 129, .12);
        color: var(--color-success);
    }

    .badge-status.fail {
        background: rgba(239, 68, 68, .12);
        color: var(--color-danger);
    }

    .prompt-preview {
        max-width: 280px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: pointer;
    }

    .prompt-preview:hover {
        color: var(--blue-600);
    }

    /* Expand row */
    .expand-row {
        display: none;
    }

    .expand-row.active {
        display: table-row;
    }

    .expand-row td {
        background: var(--color-surface-muted);
        padding: 1rem 1.25rem;
    }

    .expand-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .expand-content pre {
        background: var(--color-bg);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-sm);
        padding: .75rem;
        font-size: .75rem;
        max-height: 200px;
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
        margin: 0;
    }

    .expand-content .label {
        font-weight: 600;
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        margin-bottom: .3rem;
    }

    /* Pagination */
    .pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: 1rem;
    }

    .pagination button {
        padding: .4rem .8rem;
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-sm);
        background: var(--color-bg);
        color: var(--color-text);
        font-size: var(--font-size-sm);
        cursor: pointer;
        transition: background var(--transition-fast);
    }

    .pagination button:hover:not(:disabled) {
        background: var(--color-surface-muted);
    }

    .pagination button:disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    .pagination .page-info {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
    }

    /* Empty */
    .logs-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem;
        color: var(--color-text-muted);
        font-size: var(--font-size-sm);
        gap: .5rem;
    }

    .logs-empty i {
        width: 40px;
        height: 40px;
    }

    /* ── Quota Card ── */
    .quota-section {
        background: var(--glass-bg);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        box-shadow: var(--shadow-sm);
    }

    .quota-section h2 {
        font-size: var(--font-size-base);
        font-weight: 600;
        margin: 0 0 1rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .quota-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .quota-item {
        text-align: center;
    }

    .quota-item .quota-label {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        margin-bottom: .35rem;
    }

    .quota-item .quota-bar-wrap {
        background: var(--color-surface-muted);
        border-radius: 999px;
        height: 8px;
        overflow: hidden;
        margin-bottom: .3rem;
    }

    .quota-item .quota-bar {
        height: 100%;
        border-radius: 999px;
        transition: width .4s ease;
    }

    .quota-item .quota-values {
        font-size: .75rem;
        color: var(--color-text-muted);
    }

    .quota-item .quota-values strong {
        color: var(--color-text);
    }

    .quota-status-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .6rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 600;
    }

    .quota-status-badge.active {
        background: rgba(16, 185, 129, .12);
        color: var(--color-success);
    }

    .quota-status-badge.quota_exceeded {
        background: rgba(239, 68, 68, .12);
        color: var(--color-danger);
    }

    .quota-status-badge.invalid_key {
        background: rgba(239, 68, 68, .12);
        color: var(--color-danger);
    }

    .quota-status-badge.error {
        background: rgba(245, 158, 11, .12);
        color: var(--color-warning);
    }

    .quota-status-badge.loading {
        background: var(--color-surface-muted);
        color: var(--color-text-muted);
    }

    .quota-msg {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        margin-top: .75rem;
        padding: .5rem .75rem;
        background: var(--color-surface-muted);
        border-radius: var(--radius-sm);
        word-break: break-word;
    }

    .quota-reset {
        font-size: .7rem;
        color: var(--color-text-muted);
        margin-top: .5rem;
        text-align: center;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .expand-content {
            grid-template-columns: 1fr;
        }

        .filters-bar {
            flex-direction: column;
        }

        .filters-bar .btn-cleanup {
            margin-left: 0;
        }

        .quota-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

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
                <div class="quota-values"><strong id="quotaReqRemaining">—</strong> / <span id="quotaReqLimit">—</span></div>
            </div>
            <div class="quota-item">
                <div class="quota-label">Tokens</div>
                <div class="quota-bar-wrap">
                    <div class="quota-bar" id="quotaTokBar" style="width:0%;background:var(--color-success);"></div>
                </div>
                <div class="quota-values"><strong id="quotaTokRemaining">—</strong> / <span id="quotaTokLimit">—</span></div>
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

<script>
    (function() {
        'use strict';

        const BASE = (window.BASE_URL || document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        let currentPage = 1;

        // ── Helpers ──
        function esc(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        function fmtDate(iso) {
            if (!iso) return '—';
            const d = new Date(iso);
            return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function fmtNumber(n) {
            return (n || 0).toLocaleString('pt-BR');
        }

        // ── Load Quota ──
        async function loadQuota() {
            const badge = document.getElementById('quotaStatus');
            try {
                const res = await fetch(`${BASE}api/sysadmin/ai/quota`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: AbortSignal.timeout(15000),
                });
                const json = await res.json();
                if (!json.success && !json.data) {
                    badge.className = 'quota-status-badge error';
                    badge.textContent = json.message || 'Erro';
                    return;
                }

                const d = json.data;
                const statusLabels = {
                    active: 'Ativo',
                    quota_exceeded: 'Quota Excedida',
                    invalid_key: 'Chave Inválida',
                    error: 'Erro',
                };

                badge.className = `quota-status-badge ${d.status}`;
                badge.textContent = statusLabels[d.status] || d.status;

                if (d.status === 'active') {
                    // Requests bar
                    const reqPct = d.requests_limit > 0 ? (d.requests_remaining / d.requests_limit * 100) : 0;
                    document.getElementById('quotaReqBar').style.width = reqPct + '%';
                    document.getElementById('quotaReqBar').style.background = barColor(reqPct);
                    document.getElementById('quotaReqRemaining').textContent = fmtNumber(d.requests_remaining);
                    document.getElementById('quotaReqLimit').textContent = fmtNumber(d.requests_limit);

                    // Tokens bar
                    const tokPct = d.tokens_limit > 0 ? (d.tokens_remaining / d.tokens_limit * 100) : 0;
                    document.getElementById('quotaTokBar').style.width = tokPct + '%';
                    document.getElementById('quotaTokBar').style.background = barColor(tokPct);
                    document.getElementById('quotaTokRemaining').textContent = fmtNumber(d.tokens_remaining);
                    document.getElementById('quotaTokLimit').textContent = fmtNumber(d.tokens_limit);

                    // Reset times
                    if (d.reset_requests || d.reset_tokens) {
                        const resetEl = document.getElementById('quotaReset');
                        let parts = [];
                        if (d.reset_requests) parts.push('Requisições resetam em: ' + d.reset_requests);
                        if (d.reset_tokens) parts.push('Tokens resetam em: ' + d.reset_tokens);
                        resetEl.textContent = parts.join(' · ');
                        resetEl.style.display = '';
                    }
                } else {
                    document.getElementById('quotaReqRemaining').textContent = '0';
                    document.getElementById('quotaReqLimit').textContent = '0';
                    document.getElementById('quotaTokRemaining').textContent = '0';
                    document.getElementById('quotaTokLimit').textContent = '0';
                }

                if (d.message && d.status !== 'active') {
                    const msgEl = document.getElementById('quotaMsg');
                    msgEl.textContent = d.message;
                    msgEl.style.display = '';
                }
            } catch {
                badge.className = 'quota-status-badge error';
                badge.textContent = 'Erro de conexão';
            }
        }

        function barColor(pct) {
            if (pct > 50) return 'var(--color-success)';
            if (pct > 20) return 'var(--color-warning)';
            return 'var(--color-danger)';
        }

        // ── Load Summary ──
        async function loadSummary() {
            try {
                const res = await fetch(`${BASE}api/sysadmin/ai/logs/summary?hours=24`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const json = await res.json();
                if (!json.success) return;
                const d = json.data;

                document.getElementById('metricTotal').textContent = fmtNumber(d.total);
                document.getElementById('metricSuccess').textContent = d.success_rate + '%';
                document.getElementById('metricTokens').textContent = fmtNumber(d.tokens_total);
                document.getElementById('metricCost').textContent = '$' + (d.estimated_cost || 0).toFixed(4);
                document.getElementById('metricAvgTime').textContent = fmtNumber(d.avg_time_ms) + 'ms';
                document.getElementById('metricErrors').textContent = fmtNumber(d.error_count);
            } catch {
                // silent
            }
        }

        // ── Load Logs ──
        async function loadLogs(page) {
            currentPage = page || 1;

            const params = new URLSearchParams({
                page: currentPage,
                per_page: 20
            });
            const type = document.getElementById('filterType').value;
            const success = document.getElementById('filterSuccess').value;
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;
            const search = document.getElementById('filterSearch').value.trim();

            if (type) params.set('type', type);
            if (success !== '') params.set('success', success);
            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo) params.set('date_to', dateTo);
            if (search) params.set('search', search);

            const body = document.getElementById('logsBody');

            try {
                const res = await fetch(`${BASE}api/sysadmin/ai/logs?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const json = await res.json();
                if (!json.success) {
                    body.innerHTML = `<tr><td colspan="6"><div class="logs-empty">Erro ao carregar logs</div></td></tr>`;
                    return;
                }

                const {
                    data,
                    total,
                    page: pg,
                    per_page
                } = json.data;

                if (!data.length) {
                    body.innerHTML = `<tr><td colspan="6"><div class="logs-empty"><i data-lucide="inbox"></i> Nenhum log encontrado</div></td></tr>`;
                    document.getElementById('pagination').innerHTML = '';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                    return;
                }

                let html = '';
                data.forEach((log, i) => {
                    const typeLabel = {
                        chat: 'Chat',
                        suggest_category: 'Sugestão',
                        analyze_spending: 'Análise'
                    } [log.type] || log.type;
                    const rowId = `expand-${pg}-${i}`;

                    html += `<tr class="log-row" data-expand="${rowId}">
                    <td style="white-space:nowrap;">${fmtDate(log.created_at)}</td>
                    <td><span class="badge-type ${esc(log.type)}">${esc(typeLabel)}</span></td>
                    <td><div class="prompt-preview" title="Clique para expandir">${esc((log.prompt || '').substring(0, 100))}</div></td>
                    <td>${log.tokens_total ? fmtNumber(log.tokens_total) : '—'}</td>
                    <td>${log.response_time_ms ? fmtNumber(log.response_time_ms) + 'ms' : '—'}</td>
                    <td><span class="badge-status ${log.success ? 'ok' : 'fail'}">${log.success ? 'OK' : 'Erro'}</span></td>
                </tr>
                <tr class="expand-row" id="${rowId}">
                    <td colspan="6">
                        <div class="expand-content">
                            <div>
                                <div class="label">Prompt</div>
                                <pre>${esc(log.prompt)}</pre>
                            </div>
                            <div>
                                <div class="label">${log.success ? 'Resposta' : 'Erro'}</div>
                                <pre>${esc(log.success ? log.response : log.error_message) || '—'}</pre>
                            </div>
                        </div>
                        <div style="margin-top:.75rem;font-size:.75rem;color:var(--color-text-muted);display:flex;gap:1.5rem;flex-wrap:wrap;">
                            <span><strong>Provider:</strong> ${esc(log.provider)}</span>
                            <span><strong>Model:</strong> ${esc(log.model)}</span>
                            <span><strong>Tokens (in/out):</strong> ${log.tokens_prompt ?? '—'} / ${log.tokens_completion ?? '—'}</span>
                            <span><strong>Tempo:</strong> ${log.response_time_ms ? log.response_time_ms + 'ms' : '—'}</span>
                            ${log.user_id ? `<span><strong>User ID:</strong> ${log.user_id}</span>` : ''}
                        </div>
                    </td>
                </tr>`;
                });

                body.innerHTML = html;

                // Pagination
                const totalPages = Math.ceil(total / per_page);
                const pagEl = document.getElementById('pagination');
                pagEl.innerHTML = `
                <button ${pg <= 1 ? 'disabled' : ''} onclick="window._aiLogsNav(${pg - 1})">← Anterior</button>
                <span class="page-info">Página ${pg} de ${totalPages} (${fmtNumber(total)} registros)</span>
                <button ${pg >= totalPages ? 'disabled' : ''} onclick="window._aiLogsNav(${pg + 1})">Próxima →</button>`;

                // Expand rows on click
                body.querySelectorAll('.log-row').forEach(row => {
                    row.addEventListener('click', () => {
                        const target = document.getElementById(row.dataset.expand);
                        if (target) target.classList.toggle('active');
                    });
                });

                if (typeof lucide !== 'undefined') lucide.createIcons();
            } catch {
                body.innerHTML = `<tr><td colspan="6"><div class="logs-empty">Erro de conexão</div></td></tr>`;
            }
        }

        window._aiLogsNav = function(page) {
            loadLogs(page);
        };

        // ── Filter ──
        document.getElementById('btnFilter').addEventListener('click', () => loadLogs(1));

        document.getElementById('filterSearch').addEventListener('keydown', e => {
            if (e.key === 'Enter') loadLogs(1);
        });

        // ── Cleanup ──
        document.getElementById('btnCleanup').addEventListener('click', async () => {
            if (!confirm('Remover todos os logs com mais de 90 dias?')) return;

            try {
                const res = await fetch(`${BASE}api/sysadmin/ai/logs/cleanup`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrf
                    },
                    body: JSON.stringify({
                        days: 90
                    }),
                });
                const json = await res.json();
                alert(json.data?.message || json.message || 'Concluído');
                loadSummary();
                loadLogs(1);
            } catch {
                alert('Erro ao limpar logs');
            }
        });

        // ── Init ──
        loadSummary();
        loadQuota();
        loadLogs(1);
    })();
</script>