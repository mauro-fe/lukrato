import '../../../css/admin/sysadmin/ai-logs.css';
import { apiDelete, apiGet, getBaseUrl, getErrorMessage } from '../shared/api.js';

(function () {
    'use strict';

    const BASE = getBaseUrl();
    const FILTERS_STORAGE_KEY = 'sysadmin-ai-logs-filters-v2';
    const PERIOD_STORAGE_KEY = 'sysadmin-ai-logs-summary-period-v1';

    let currentPage = 1;
    let summaryHours = loadStoredPeriod();

    function getEl(id) {
        return document.getElementById(id);
    }

    function esc(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function fmtDate(iso) {
        if (!iso) return '-';
        const date = new Date(iso);
        return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }

    function fmtNumber(value) {
        return Number(value || 0).toLocaleString('pt-BR');
    }

    function fmtPercent(value) {
        return `${Number(value || 0).toFixed(1)}%`;
    }

    function renderTokenBreakdown(log) {
        const prompt = Number(log.tokens_prompt ?? 0);
        const completion = Number(log.tokens_completion ?? 0);
        const total = Number(log.tokens_total ?? 0);

        if (prompt > 0 || completion > 0) {
            const breakdown = `${fmtNumber(prompt)} / ${fmtNumber(completion)}`;
            return total > 0 ? `${breakdown} | Total: ${fmtNumber(total)}` : breakdown;
        }

        if (total > 0) {
            return `-- / -- | Total: ${fmtNumber(total)}`;
        }

        return '-- / --';
    }

    function periodLabel(hours) {
        if (hours >= 720) return 'ultimos 30 dias';
        if (hours >= 168) return 'ultimos 7 dias';
        return 'ultimas 24h';
    }

    function typeLabel(type) {
        return {
            chat: 'Chat',
            suggest_category: 'Sugestao',
            analyze_spending: 'Analise',
            categorize: 'Categorizacao',
            analyze: 'Analise (novo)',
            quick_query: 'Consulta rapida',
            extract_transaction: 'Extracao',
            create_entity: 'Criacao',
            confirm_action: 'Confirmacao',
            image_analysis: 'Analise de imagem',
            audio_transcription: 'Transcricao',
            pay_fatura: 'Pagamento de fatura'
        }[type] || type || '-';
    }

    function sourceLabel(source) {
        return {
            llm: 'LLM',
            rule: 'Regra',
            cache: 'Cache',
            computed: 'Computado',
            trivial: 'Trivial',
            api: 'API'
        }[source] || (source || '-').replace(/_/g, ' ');
    }

    function channelInfo(channel) {
        return {
            web: { label: 'Web', icon: 'WEB' },
            telegram: { label: 'Telegram', icon: 'TG' },
            whatsapp: { label: 'WhatsApp', icon: 'WA' },
            api: { label: 'API', icon: 'API' },
            admin: { label: 'Admin', icon: 'ADM' }
        }[channel] || { label: channel || 'Web', icon: 'WEB' };
    }

    function barColor(remainingPct) {
        if (remainingPct > 50) return 'var(--color-success)';
        if (remainingPct > 20) return 'var(--color-warning)';
        return 'var(--color-danger)';
    }

    function loadStoredPeriod() {
        const raw = localStorage.getItem(PERIOD_STORAGE_KEY);
        const parsed = Number(raw || 24);
        return [24, 168, 720].includes(parsed) ? parsed : 24;
    }

    function saveStoredPeriod(hours) {
        localStorage.setItem(PERIOD_STORAGE_KEY, String(hours));
    }

    function loadStoredFilters() {
        try {
            const parsed = JSON.parse(localStorage.getItem(FILTERS_STORAGE_KEY) || '{}');
            return {
                type: parsed.type || '',
                channel: parsed.channel || '',
                success: parsed.success ?? '',
                dateFrom: parsed.dateFrom || '',
                dateTo: parsed.dateTo || '',
                search: parsed.search || ''
            };
        } catch {
            return {
                type: '',
                channel: '',
                success: '',
                dateFrom: '',
                dateTo: '',
                search: ''
            };
        }
    }

    function saveFilters(filters) {
        localStorage.setItem(FILTERS_STORAGE_KEY, JSON.stringify(filters));
    }

    function applyStoredState() {
        const filters = loadStoredFilters();

        getEl('filterType').value = filters.type;
        getEl('filterChannel').value = filters.channel;
        getEl('filterSuccess').value = filters.success;
        getEl('filterDateFrom').value = filters.dateFrom;
        getEl('filterDateTo').value = filters.dateTo;
        getEl('filterSearch').value = filters.search;
        getEl('summaryPeriod').value = String(summaryHours);

        updateFilterSummary(filters);
        updateRangeChipState(filters);
    }

    function readFilters() {
        return {
            type: getEl('filterType').value,
            channel: getEl('filterChannel').value,
            success: getEl('filterSuccess').value,
            dateFrom: getEl('filterDateFrom').value,
            dateTo: getEl('filterDateTo').value,
            search: getEl('filterSearch').value.trim()
        };
    }

    function validateFilters(filters) {
        if (filters.dateFrom && filters.dateTo && filters.dateFrom > filters.dateTo) {
            return 'A data inicial nao pode ser maior que a data final.';
        }

        return null;
    }

    function updateFilterSummary(filters) {
        const summary = [];

        if (filters.type) summary.push(typeLabel(filters.type));
        if (filters.channel) summary.push(channelInfo(filters.channel).label);
        if (filters.success !== '') summary.push(filters.success === '1' ? 'Somente sucesso' : 'Somente erro');
        if (filters.dateFrom || filters.dateTo) {
            summary.push(`Periodo: ${filters.dateFrom || '...'} ate ${filters.dateTo || '...'}`);
        }
        if (filters.search) summary.push(`Busca: "${filters.search}"`);

        getEl('filterSummary').textContent = summary.length
            ? summary.join(' | ')
            : 'Sem filtros ativos';
    }

    function setDateRange(range) {
        const today = new Date();
        const dateTo = formatInputDate(today);
        let dateFrom = dateTo;

        if (range === '7d') {
            const start = new Date(today);
            start.setDate(start.getDate() - 6);
            dateFrom = formatInputDate(start);
        }

        if (range === '30d') {
            const start = new Date(today);
            start.setDate(start.getDate() - 29);
            dateFrom = formatInputDate(start);
        }

        getEl('filterDateFrom').value = dateFrom;
        getEl('filterDateTo').value = dateTo;
    }

    function clearFilters() {
        getEl('filterType').value = '';
        getEl('filterChannel').value = '';
        getEl('filterSuccess').value = '';
        getEl('filterDateFrom').value = '';
        getEl('filterDateTo').value = '';
        getEl('filterSearch').value = '';
        updateFilterSummary(readFilters());
        updateRangeChipState(readFilters());
        saveFilters(readFilters());
    }

    function formatInputDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function updateRangeChipState(filters) {
        const chips = document.querySelectorAll('[data-range]');
        const today = formatInputDate(new Date());

        let activeRange = '';
        if (filters.dateFrom && filters.dateTo === today) {
            if (filters.dateFrom === today) {
                activeRange = 'today';
            } else {
                const diffDays = Math.round((new Date(filters.dateTo) - new Date(filters.dateFrom)) / 86400000) + 1;
                if (diffDays === 7) activeRange = '7d';
                if (diffDays === 30) activeRange = '30d';
            }
        }

        chips.forEach((chip) => {
            chip.classList.toggle('active', chip.dataset.range === activeRange);
        });
    }

    function setLoadingRow(message) {
        getEl('logsBody').innerHTML = `<tr><td colspan="7"><div class="logs-empty">${esc(message)}</div></td></tr>`;
    }

    function renderKeyValueList(targetId, data, formatter, emptyText, valueClass, labelFormatter) {
        const el = getEl(targetId);

        if (!data || !Object.keys(data).length) {
            el.innerHTML = `<span class="panel-loading">${esc(emptyText || 'Sem dados')}</span>`;
            return;
        }

        el.innerHTML = Object.entries(data)
            .sort((a, b) => Number(b[1]) - Number(a[1]))
            .map(([key, value]) => {
                const renderedValue = formatter ? formatter(key, value) : esc(String(value));
                const strongClass = valueClass ? ` class="${valueClass}"` : '';
                const renderedLabel = labelFormatter ? labelFormatter(key) : typeLabel(key);
                return `<div class="quality-panel-row"><span>${esc(renderedLabel)}</span><strong${strongClass}>${renderedValue}</strong></div>`;
            })
            .join('');
    }

    async function loadQuota() {
        const badge = getEl('quotaStatus');

        try {
            const json = await apiGet(`${BASE}api/sysadmin/ai/quota`);
            const data = json.data;

            if (!data) {
                badge.className = 'quota-status-badge error';
                badge.textContent = json.message || 'Erro';
                return;
            }

            const statusLabels = {
                active: 'Ativo',
                quota_exceeded: 'Quota excedida',
                invalid_key: 'Chave invalida',
                error: 'Erro'
            };

            badge.className = `quota-status-badge ${data.status}`;
            badge.textContent = statusLabels[data.status] || data.status;

            const reqUsed = Math.max((data.requests_limit || 0) - (data.requests_remaining || 0), 0);
            const tokUsed = Math.max((data.tokens_limit || 0) - (data.tokens_remaining || 0), 0);
            const reqRemainingPct = data.requests_limit > 0 ? (data.requests_remaining / data.requests_limit) * 100 : 0;
            const tokRemainingPct = data.tokens_limit > 0 ? (data.tokens_remaining / data.tokens_limit) * 100 : 0;
            const reqUsedPct = data.requests_limit > 0 ? (reqUsed / data.requests_limit) * 100 : 0;
            const tokUsedPct = data.tokens_limit > 0 ? (tokUsed / data.tokens_limit) * 100 : 0;

            getEl('quotaReqBar').style.width = `${reqRemainingPct}%`;
            getEl('quotaReqBar').style.background = barColor(reqRemainingPct);
            getEl('quotaReqRemaining').textContent = fmtNumber(data.requests_remaining || 0);
            getEl('quotaReqLimit').textContent = fmtNumber(data.requests_limit || 0);
            getEl('quotaReqHint').textContent = `Usado: ${fmtNumber(reqUsed)} (${fmtPercent(reqUsedPct)})`;

            getEl('quotaTokBar').style.width = `${tokRemainingPct}%`;
            getEl('quotaTokBar').style.background = barColor(tokRemainingPct);
            getEl('quotaTokRemaining').textContent = fmtNumber(data.tokens_remaining || 0);
            getEl('quotaTokLimit').textContent = fmtNumber(data.tokens_limit || 0);
            getEl('quotaTokHint').textContent = `Usado: ${fmtNumber(tokUsed)} (${fmtPercent(tokUsedPct)})`;

            const resetParts = [];
            if (data.reset_requests) resetParts.push(`Requisicoes resetam em ${data.reset_requests}`);
            if (data.reset_tokens) resetParts.push(`Tokens resetam em ${data.reset_tokens}`);
            getEl('quotaReset').textContent = resetParts.join(' | ');
            getEl('quotaReset').style.display = resetParts.length ? '' : 'none';

            if (data.message && data.status !== 'active') {
                getEl('quotaMsg').textContent = data.message;
                getEl('quotaMsg').style.display = '';
            } else {
                getEl('quotaMsg').style.display = 'none';
            }
        } catch {
            badge.className = 'quota-status-badge error';
            badge.textContent = 'Erro de conexao';
        }
    }

    async function loadSummary() {
        try {
            const json = await apiGet(`${BASE}api/sysadmin/ai/logs/summary`, { hours: summaryHours });
            if (!json.success) return;

            const data = json.data;
            getEl('metricTotal').textContent = fmtNumber(data.total);
            getEl('metricSuccess').textContent = fmtPercent(data.success_rate);
            getEl('metricTokens').textContent = fmtNumber(data.tokens_total);
            getEl('metricCost').textContent = '$' + Number(data.estimated_cost || 0).toFixed(4);
            getEl('metricAvgTime').textContent = `${fmtNumber(data.avg_time_ms)}ms`;
            getEl('metricErrors').textContent = fmtNumber(data.error_count);

            const labels = document.querySelectorAll('#metricsGrid .metric-card .metric-label');
            if (labels.length >= 6) {
                labels[0].textContent = `Total (${periodLabel(summaryHours)})`;
                labels[5].textContent = `Erros (${periodLabel(summaryHours)})`;
            }
        } catch {
            // silent
        }
    }

    async function loadQualityMetrics() {
        try {
            const json = await apiGet(`${BASE}api/sysadmin/ai/logs/quality`, { hours: summaryHours });
            if (!json.success) return;

            const data = json.data;
            getEl('metricLowConf').textContent = fmtPercent(data.low_confidence_rate);
            getEl('metricFallback').textContent = fmtPercent(data.fallback_to_chat_rate);

            renderKeyValueList('intentDistribution', data.intent_distribution, (key, value) => fmtNumber(value), 'Sem dados');
            renderKeyValueList('errorsByHandler', data.error_by_handler, (key, value) => fmtNumber(value), 'Nenhum erro', 'error-highlight');
            renderKeyValueList('sourceDistribution', data.source_distribution, (key, value) => fmtNumber(value), 'Sem dados', '', sourceLabel);
            renderKeyValueList('avgTimeByType', data.avg_response_time_by_type, (key, value) => `${fmtNumber(value)}ms`, 'Sem dados');
        } catch (error) {
            console.warn('Quality metrics load failed:', error);
        }
    }

    async function loadLogs(page) {
        const filters = readFilters();
        const validationError = validateFilters(filters);
        if (validationError) {
            alert(validationError);
            return;
        }

        currentPage = page || 1;
        updateFilterSummary(filters);
        updateRangeChipState(filters);
        saveFilters(filters);

        const params = new URLSearchParams({
            page: String(currentPage),
            per_page: '20'
        });

        if (filters.type) params.set('type', filters.type);
        if (filters.channel) params.set('channel', filters.channel);
        if (filters.success !== '') params.set('success', filters.success);
        if (filters.dateFrom) params.set('date_from', filters.dateFrom);
        if (filters.dateTo) params.set('date_to', filters.dateTo);
        if (filters.search) params.set('search', filters.search);

        setLoadingRow('Carregando logs...');

        try {
            const json = await apiGet(`${BASE}api/sysadmin/ai/logs`, Object.fromEntries(params.entries()));

            if (!json.success) {
                setLoadingRow('Erro ao carregar logs');
                return;
            }

            const { data, total, page: current, per_page: perPage } = json.data;
            const body = getEl('logsBody');

            if (!data.length) {
                body.innerHTML = '<tr><td colspan="7"><div class="logs-empty">Nenhum log encontrado para os filtros atuais.</div></td></tr>';
                getEl('pagination').innerHTML = '';
                return;
            }

            body.innerHTML = data.map((log, index) => {
                const rowId = `expand-${current}-${index}`;
                const channel = channelInfo(log.channel);
                const confidence = Number(log.confidence ?? 0);
                const metaItems = [
                    `<span><strong>Canal:</strong> ${esc(log.channel || 'web')}</span>`,
                    `<span><strong>Provider:</strong> ${esc(log.provider || '-')}</span>`,
                    `<span><strong>Model:</strong> ${esc(log.model || '-')}</span>`,
                    `<span><strong>Tokens:</strong> ${renderTokenBreakdown(log)}</span>`,
                    `<span><strong>Tempo:</strong> ${log.response_time_ms ? `${fmtNumber(log.response_time_ms)}ms` : '--'}</span>`
                ];

                if (log.source) {
                    metaItems.push(`<span><strong>Origem:</strong> ${esc(log.source)}</span>`);
                }

                if (confidence > 0) {
                    metaItems.push(`<span><strong>Confianca:</strong> ${confidence.toFixed(2)}</span>`);
                }

                if (log.prompt_version) {
                    metaItems.push(`<span><strong>Prompt:</strong> ${esc(log.prompt_version)}</span>`);
                }

                if (log.user_id) {
                    metaItems.push(`<span><strong>User ID:</strong> ${log.user_id}</span>`);
                }

                return `
                    <tr class="log-row" data-expand="${rowId}" aria-expanded="false">
                        <td style="white-space:nowrap;">${fmtDate(log.created_at)}</td>
                        <td><span class="badge-channel ${esc(log.channel || 'web')}">${esc(channel.icon)} ${esc(channel.label)}</span></td>
                        <td><span class="badge-type ${esc(log.type)}">${esc(typeLabel(log.type))}</span></td>
                        <td><div class="prompt-preview" title="Clique para expandir">${esc((log.prompt || '').substring(0, 100))}</div></td>
                        <td class="col-tokens">${log.tokens_total ? fmtNumber(log.tokens_total) : '-'}</td>
                        <td class="col-time">${log.response_time_ms ? `${fmtNumber(log.response_time_ms)}ms` : '-'}</td>
                        <td><span class="badge-status ${log.success ? 'ok' : 'fail'}">${log.success ? 'OK' : 'Erro'}</span></td>
                    </tr>
                    <tr class="expand-row" id="${rowId}">
                        <td colspan="7">
                            <div class="expand-content">
                                <div>
                                    <div class="label">Prompt</div>
                                    <pre>${esc(log.prompt || '-')}</pre>
                                </div>
                                <div>
                                    <div class="label">${log.success ? 'Resposta' : 'Erro'}</div>
                                    <pre>${esc(log.success ? (log.response || '-') : (log.error_message || '-'))}</pre>
                                </div>
                            </div>
                            <div style="margin-top:.75rem;font-size:.75rem;color:var(--color-text-muted);display:flex;gap:1rem;flex-wrap:wrap;">
                                ${metaItems.join('')}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            const totalPages = Math.max(1, Math.ceil(total / perPage));
            getEl('pagination').innerHTML = `
                <button ${current <= 1 ? 'disabled' : ''} onclick="window._aiLogsNav(${current - 1})">Anterior</button>
                <span class="page-info">Pagina ${current} de ${totalPages} (${fmtNumber(total)} registros)</span>
                <button ${current >= totalPages ? 'disabled' : ''} onclick="window._aiLogsNav(${current + 1})">Proxima</button>
            `;

            body.querySelectorAll('.log-row').forEach((row) => {
                row.addEventListener('click', () => {
                    const target = getEl(row.dataset.expand);
                    if (!target) return;

                    const expanded = target.classList.toggle('active');
                    row.setAttribute('aria-expanded', String(expanded));
                });
            });
        } catch {
            setLoadingRow('Erro de conexao');
        }
    }

    async function refreshDashboard() {
        await Promise.allSettled([
            loadSummary(),
            loadQualityMetrics(),
            loadQuota()
        ]);
        await loadLogs(currentPage || 1);
    }

    window._aiLogsNav = function (page) {
        loadLogs(page);
    };

    getEl('btnFilter').addEventListener('click', () => loadLogs(1));
    getEl('btnRefreshPage').addEventListener('click', () => refreshDashboard());
    getEl('summaryPeriod').addEventListener('change', (event) => {
        summaryHours = Number(event.target.value || 24);
        saveStoredPeriod(summaryHours);
        Promise.allSettled([loadSummary(), loadQualityMetrics()]);
    });

    getEl('filterSearch').addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            loadLogs(1);
        }
    });

    getEl('btnClearFilters').addEventListener('click', () => {
        clearFilters();
        loadLogs(1);
    });

    document.querySelectorAll('[data-range]').forEach((button) => {
        button.addEventListener('click', () => {
            setDateRange(button.dataset.range);
            updateRangeChipState(readFilters());
            loadLogs(1);
        });
    });

    getEl('btnCleanup').addEventListener('click', async () => {
        if (!confirm('Remover todos os logs com mais de 90 dias?')) return;

        try {
            const json = await apiDelete(`${BASE}api/sysadmin/ai/logs/cleanup`, { days: 90 });
            alert(json.data?.message || json.message || 'Concluido');
            await refreshDashboard();
        } catch (error) {
            alert(getErrorMessage(error, 'Erro ao limpar logs'));
        }
    });

    applyStoredState();
    refreshDashboard();
    setInterval(loadQuota, 60000);
})();
