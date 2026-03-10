(function () {
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
                    analyze_spending: 'Análise',
                    categorize: 'Categorização',
                    analyze: 'Análise (novo)',
                    quick_query: 'Consulta Rápida',
                    extract_transaction: 'Extração'
                }[log.type] || log.type;
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

    window._aiLogsNav = function (page) {
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
