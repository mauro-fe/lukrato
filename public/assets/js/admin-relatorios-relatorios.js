/**
 * ============================================================================
 * SISTEMA DE RELATÃ“RIOS - JAVASCRIPT
 * ============================================================================
 * Gerencia visualizaÃ§Ã£o de relatÃ³rios financeiros com grÃ¡ficos interativos
 * ============================================================================
 */

(() => {
    'use strict';

    const PAYWALL_MESSAGE = 'Relatórios são exclusivos do plano Pro.';

    // Previne inicializaÃ§Ã£o dupla
    if (window.__LK_REPORTS_LOADED__) return;
    window.__LK_REPORTS_LOADED__ = true;

    // ============================================================================
    // CONFIGURAÃ‡ÃƒO
    // ============================================================================

    const CONFIG = {
        // Detecta BASE_URL do DOM ou usa padrÃ£o
        BASE_URL: (() => {
            const meta = document.querySelector('meta[name="base-url"]');
            if (meta) return meta.content.replace(/\/?$/, '/');

            const base = document.querySelector('base[href]');
            if (base) return base.href.replace(/\/?$/, '/');

            return window.BASE_URL ? String(window.BASE_URL).replace(/\/?$/, '/') : '/';
        })(),

        CHART_COLORS: [
            '#E67E22', '#2C3E50', '#2ECC71', '#F39C12',
            '#9B59B6', '#1ABC9C', '#E74C3C', '#3498DB'
        ],

        VIEWS: {
            CATEGORY: 'category',
            BALANCE: 'balance',
            COMPARISON: 'comparison',
            ACCOUNTS: 'accounts',
            EVOLUTION: 'evolution',
            ANNUAL_SUMMARY: 'annual_summary',
            ANNUAL_CATEGORY: 'annual_category'
        }
    };

    const YEARLY_VIEWS = new Set([
        CONFIG.VIEWS.ANNUAL_SUMMARY,
        CONFIG.VIEWS.ANNUAL_CATEGORY
    ]);

    const TYPE_OPTIONS = {
        [CONFIG.VIEWS.CATEGORY]: [
            { value: 'despesas_por_categoria', label: 'Despesas por categoria' },
            { value: 'receitas_por_categoria', label: 'Receitas por categoria' }
        ],
        [CONFIG.VIEWS.ANNUAL_CATEGORY]: [
            { value: 'despesas_anuais_por_categoria', label: 'Despesas anuais por categoria' },
            { value: 'receitas_anuais_por_categoria', label: 'Receitas anuais por categoria' }
        ]
    };

    function computeInitialMonth() {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    }

    // ============================================================================
    // UTILITÃRIOS
    // ============================================================================

    const Utils = {
        getCurrentMonth: computeInitialMonth,

        formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(Number(value) || 0);
        },

        formatMonthLabel(yearMonth) {
            const [year, month] = yearMonth.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
        },

        addMonths(yearMonth, delta) {
            const [year, month] = yearMonth.split('-').map(Number);
            const date = new Date(year, month - 1 + delta);
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        },

        hexToRgba(hex, alpha = 0.25) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },

        isYearlyView(view) {
            return YEARLY_VIEWS.has(view);
        },

        extractFilename(disposition) {
            if (!disposition) return null;

            const utf8Match = /filename\*=UTF-8''([^;]+)/i.exec(disposition);
            if (utf8Match) {
                try {
                    return decodeURIComponent(utf8Match[1]);
                } catch (e) {
                    return utf8Match[1];
                }
            }

            const simpleMatch = /filename="?([^";]+)"?/i.exec(disposition);
            return simpleMatch ? simpleMatch[1] : null;
        }
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>\"']/g, function (match) {
        const replacements = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };

        return replacements[match] ?? match;
    });

    // ============================================================================
    // ESTADO GLOBAL
    // ============================================================================

    const state = {
        currentView: CONFIG.VIEWS.CATEGORY,
        categoryType: 'despesas_por_categoria',
        annualCategoryType: 'despesas_anuais_por_categoria',
        currentMonth: computeInitialMonth(),
        currentAccount: null,
        chart: null,
        accounts: [],
        accessRestricted: false
    };

    // Aliases para compatibilidade
    function getCurrentMonth() { return Utils.getCurrentMonth(); }
    function formatCurrency(v) { return Utils.formatCurrency(v); }
    function formatMonthLabel(m) { return Utils.formatMonthLabel(m); }
    function hexToRgba(h, a) { return Utils.hexToRgba(h, a); }
    function isYearlyView(v = state.currentView) { return Utils.isYearlyView(v); }

    // ============================================================================
    // API
    // ============================================================================

    const API = {
        async fetchReportData() {
            const params = new URLSearchParams({
                type: this.getReportType(),
                year: state.currentMonth.split('-')[0],
                month: state.currentMonth.split('-')[1]
            });

            if (state.currentAccount) {
                params.set('account_id', state.currentAccount);
            }

            try {
                const response = await fetch(`${CONFIG.BASE_URL}api/reports?${params}`, {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' }
                });

                if (await handleRestrictedAccess(response)) {
                    return null;
                }

                if (!response.ok) throw new Error('API request failed');

                state.accessRestricted = false;

                const json = await response.json();
                return json.data || json;
            } catch (error) {
                console.error('Error fetching report data:', error);
                return { labels: [], values: [] };
            }
        },

        async fetchAccounts() {
            try {
                const response = await fetch(`${CONFIG.BASE_URL}api/accounts`, {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' }
                });

                if (await handleRestrictedAccess(response)) {
                    return [];
                }

                if (!response.ok) throw new Error('Failed to fetch accounts');

                state.accessRestricted = false;

                const json = await response.json();
                return (json.items || json || []).map(acc => ({
                    id: Number(acc.id),
                    name: acc.nome || acc.apelido || acc.instituicao || `Conta #${acc.id}`
                }));
            } catch (error) {
                console.error('Error fetching accounts:', error);
                return [];
            }
        },

        getReportType() {
            const typeMap = {
                [CONFIG.VIEWS.CATEGORY]: state.categoryType,
                [CONFIG.VIEWS.ANNUAL_CATEGORY]: state.annualCategoryType,
                [CONFIG.VIEWS.BALANCE]: 'saldo_mensal',
                [CONFIG.VIEWS.COMPARISON]: 'receitas_despesas_diario',
                [CONFIG.VIEWS.ACCOUNTS]: 'receitas_despesas_por_conta',
                [CONFIG.VIEWS.EVOLUTION]: 'evolucao_12m',
                [CONFIG.VIEWS.ANNUAL_SUMMARY]: 'resumo_anual'
            };
            return typeMap[state.currentView] ?? state.categoryType;
        },

        getActiveCategoryType() {
            return state.currentView === CONFIG.VIEWS.ANNUAL_CATEGORY
                ? state.annualCategoryType
                : state.categoryType;
        }
    };

    // Aliases
    const fetchReportData = () => API.fetchReportData();
    const fetchAccounts = () => API.fetchAccounts();
    const getReportType = () => API.getReportType();
    const getActiveCategoryType = () => API.getActiveCategoryType();

    // ============================================================================
    // GERENCIAMENTO DE GRÃFICOS
    // ============================================================================

    const ChartManager = {
        destroy() {
            if (state.chart) {
                if (Array.isArray(state.chart)) {
                    state.chart.forEach(c => c?.destroy());
                } else {
                    state.chart.destroy();
                }
                state.chart = null;
            }
        },

        setupDefaults() {
            const textColor = getComputedStyle(document.documentElement)
                .getPropertyValue('--color-text').trim();

            Chart.defaults.color = textColor;
            Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
        },

        renderPie(data) {
            const { labels = [], values = [] } = data;

            if (!labels.length || !values.some(v => v > 0)) {
                return UI.showEmptyState();
            }

            const entries = labels
                .map((label, idx) => ({
                    label,
                    value: Number(values[idx]) || 0,
                    color: CONFIG.CHART_COLORS[idx % CONFIG.CHART_COLORS.length]
                }))
                .filter(e => e.value > 0)
                .sort((a, b) => b.value - a.value);

            const shouldSplit = entries.length > 2;
            const chunkSize = shouldSplit ? Math.ceil(entries.length / 2) : entries.length;
            const chunks = shouldSplit
                ? [entries.slice(0, chunkSize), entries.slice(chunkSize)].filter(chunk => chunk.length)
                : [entries];

            const html = `
                <div class="chart-dual">
                    ${chunks.map((_, idx) => `
                        <div class="chart-wrapper">
                            <canvas id="chart${idx}"></canvas>
                        </div>
                    `).join('')}
                </div>
            `;

            UI.setContent(html);
            this.destroy();

            const type = getActiveCategoryType();
            const titleMap = {
                'receitas_por_categoria': 'Receitas por Categoria',
                'despesas_por_categoria': 'Despesas por Categoria',
                'receitas_anuais_por_categoria': 'Receitas anuais por Categoria',
                'despesas_anuais_por_categoria': 'Despesas anuais por Categoria'
            };
            const title = titleMap[type] || 'DistribuiÃ§Ã£o por Categoria';

            state.chart = chunks.map((chunk, idx) => {
                const canvas = document.getElementById(`chart${idx}`);
                return new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: chunk.map(e => e.label),
                        datasets: [{
                            data: chunk.map(e => e.value),
                            backgroundColor: chunk.map(e => e.color),
                            borderWidth: 2,
                            borderColor: getComputedStyle(document.documentElement)
                                .getPropertyValue('--color-surface').trim()
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: { position: 'bottom' },
                            title: {
                                display: true,
                                text: chunks.length > 1 ? `${title} - Parte ${idx + 1}` : title
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const label = context.label || '';
                                        const value = formatCurrency(context.parsed);
                                        return `${label}: ${value}`;
                                    }
                                }
                            }
                        }
                    }
                });
            });
        },

        renderLine(data) {
            const { labels = [], values = [] } = data;

            if (!labels.length) return UI.showEmptyState();

            UI.setContent(`
                <div class="chart-container">
                    <canvas id="chart0"></canvas>
                </div>
            `);

            this.destroy();

            const color = getComputedStyle(document.documentElement)
                .getPropertyValue('--color-primary').trim();

            state.chart = new Chart(document.getElementById('chart0'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Saldo DiÃ¡rio',
                        data: values.map(Number),
                        borderColor: color,
                        backgroundColor: hexToRgba(color, 0.2),
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: { display: true, text: 'EvoluÃ§Ã£o do Saldo Mensal' },
                        tooltip: {
                            callbacks: {
                                label: (context) => formatCurrency(context.parsed.y)
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: (value) => formatCurrency(value)
                            }
                        }
                    }
                }
            });
        },

        renderBar(data) {
            const { labels = [], receitas = [], despesas = [] } = data;

            if (!labels.length) return UI.showEmptyState();

            UI.setContent(`
                <div class="chart-container">
                    <canvas id="chart0"></canvas>
                </div>
            `);

            this.destroy();

            const colorSuccess = getComputedStyle(document.documentElement)
                .getPropertyValue('--color-success').trim();
            const colorDanger = getComputedStyle(document.documentElement)
                .getPropertyValue('--color-danger').trim();

            state.chart = new Chart(document.getElementById('chart0'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Receitas',
                            data: receitas.map(Number),
                            backgroundColor: hexToRgba(colorSuccess, 0.6),
                            borderColor: colorSuccess,
                            borderWidth: 2
                        },
                        {
                            label: 'Despesas',
                            data: despesas.map(Number),
                            backgroundColor: hexToRgba(colorDanger, 0.6),
                            borderColor: colorDanger,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: {
                            display: true,
                            text: state.currentView === CONFIG.VIEWS.ACCOUNTS
                                ? 'Receitas x Despesas por Conta'
                                : state.currentView === CONFIG.VIEWS.ANNUAL_SUMMARY
                                    ? 'Resumo Anual por MÃªs'
                                    : 'Receitas x Despesas'
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const label = context.dataset.label || '';
                                    const value = formatCurrency(context.parsed.y);
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: (value) => formatCurrency(value)
                            }
                        }
                    }
                }
            });
        }
    };

    // Aliases
    const destroyChart = () => ChartManager.destroy();
    const setupChartDefaults = () => ChartManager.setupDefaults();
    const renderPieChart = (d) => ChartManager.renderPie(d);
    const renderLineChart = (d) => ChartManager.renderLine(d);
    const renderBarChart = (d) => ChartManager.renderBar(d);

    // ============================================================================
    // INTERFACE DO USUÃRIO
    // ============================================================================

    const UI = {
        setContent(html) {
            const area = document.getElementById('reportArea');
            if (area) {
                area.innerHTML = html;
                area.setAttribute('aria-busy', 'false');
            }
        },

        showLoading() {
            const area = document.getElementById('reportArea');
            if (area) {
                area.setAttribute('aria-busy', 'true');
                area.innerHTML = `
                    <div class="loading">
                        <div class="spinner" aria-label="Carregando"></div>
                        <p>Carregando relatÃ³rio...</p>
                    </div>
                `;
            }
        },

        showEmptyState() {
            this.setContent(`
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h3>Nenhum dado encontrado</h3>
                    <p>NÃ£o hÃ¡ informaÃ§Ãµes disponÃ­veis para o perÃ­odo selecionado.</p>
                </div>
            `);
        },
        showPaywall(message = PAYWALL_MESSAGE) {
            const area = document.getElementById('reportArea');
            if (!area) return;

            const safeMessage = escapeHtml(message || PAYWALL_MESSAGE);
            area.setAttribute('aria-busy', 'false');
            area.innerHTML = `
                <div class="empty-state paywall-state">
                    <i class="fas fa-lock"></i>
                    <h3>Recurso exclusivo do plano Pro</h3>
                    <p>${safeMessage}</p>
                    <button type="button" class="lk-btn btn btn-primary" data-action="go-pro">
                        <i class="fas fa-crown"></i>
                        Assinar plano Pro
                    </button>
                </div>
            `;

            const cta = area.querySelector('[data-action="go-pro"]');
            if (cta) {
                cta.addEventListener('click', goToBilling);
            }
        },

        updateMonthLabel() {
            const labelEl = document.getElementById('monthLabel');
            if (labelEl) {
                labelEl.textContent = isYearlyView()
                    ? state.currentMonth.split('-')[0]
                    : formatMonthLabel(state.currentMonth);
            }
        },

        updateControls() {
            const typeWrapper = document.getElementById('typeSelectWrapper');
            const showTypeSelect = [CONFIG.VIEWS.CATEGORY, CONFIG.VIEWS.ANNUAL_CATEGORY]
                .includes(state.currentView);

            if (typeWrapper) {
                typeWrapper.classList.toggle('hidden', !showTypeSelect);
                if (showTypeSelect) {
                    this.syncTypeSelect();
                }
            }

            const accountWrapper = document.getElementById('accountSelectWrapper');
            if (accountWrapper) {
                accountWrapper.classList.remove('hidden');
            }

            const exportType = document.getElementById('exportType');
            if (exportType) {
                exportType.value = getReportType();
            }
        },

        syncTypeSelect() {
            const select = document.getElementById('reportType');
            if (!select) return;

            const options = TYPE_OPTIONS[state.currentView];
            if (!options) return;

            const needsUpdate = select.options.length !== options.length ||
                options.some((opt, idx) => select.options[idx]?.value !== opt.value);

            if (needsUpdate) {
                select.innerHTML = options
                    .map(option => `<option value="${option.value}">${option.label}</option>`)
                    .join('');
            }

            select.value = getActiveCategoryType();
        },

        setActiveTab(view) {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                const isActive = btn.dataset.view === view;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-selected', isActive);
            });
        }
    };

    // Aliases
    const setReportContent = (h) => UI.setContent(h);
    const showLoading = () => UI.showLoading();
    const showEmptyState = () => UI.showEmptyState();
    const updateMonthLabel = () => UI.updateMonthLabel();
    const updateControls = () => UI.updateControls();
    const syncTypeSelect = () => UI.syncTypeSelect();
    const setActiveTab = (v) => UI.setActiveTab(v);
    const showPaywall = (message) => UI.showPaywall(message);

    function goToBilling() {
        if (typeof openBillingModal === 'function') {
            openBillingModal();
        } else {
            location.href = `${CONFIG.BASE_URL}billing`;
        }
    }

    async function showRestrictionAlert(message) {
        const text = message || PAYWALL_MESSAGE;
        if (window.Swal?.fire) {
            const result = await Swal.fire({
                title: 'Recurso exclusivo',
                html: escapeHtml(text),
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Assinar plano Pro',
                cancelButtonText: 'Agora não',
                reverseButtons: true,
                focusConfirm: true
            });
            if (result.isConfirmed) {
                goToBilling();
            }
        } else if (confirm(`${text}\n\nDeseja ir para a página de planos agora?`)) {
            goToBilling();
        }
    }

    async function handleRestrictedAccess(response) {
        if (!response) return false;

        if (response.status === 401) {
            const current = encodeURIComponent(location.pathname + location.search);
            location.href = `${CONFIG.BASE_URL}login?return=${current}`;
            return true;
        }

        if (response.status === 403) {
            let message = PAYWALL_MESSAGE;
            try {
                const payload = await response.clone().json();
                if (payload?.message) {
                    message = payload.message;
                }
            } catch {
                // ignora problemas ao converter JSON
            }

            if (!state.accessRestricted) {
                state.accessRestricted = true;
                await showRestrictionAlert(message);
            }

            showPaywall(message);
            return true;
        }

        return false;
    }

    // ============================================================================
    // RENDERIZAÃ‡ÃƒO
    // ============================================================================

    async function renderReport() {
        showLoading();

        const data = await fetchReportData();

        if (state.accessRestricted) {
            return;
        }

        if (!data || !data.labels || data.labels.length === 0) {
            return showEmptyState();
        }

        switch (state.currentView) {
            case CONFIG.VIEWS.CATEGORY:
            case CONFIG.VIEWS.ANNUAL_CATEGORY:
                renderPieChart(data);
                break;
            case CONFIG.VIEWS.BALANCE:
            case CONFIG.VIEWS.EVOLUTION:
                renderLineChart(data);
                break;
            case CONFIG.VIEWS.COMPARISON:
            case CONFIG.VIEWS.ACCOUNTS:
            case CONFIG.VIEWS.ANNUAL_SUMMARY:
                renderBarChart(data);
                break;
            default:
                showEmptyState();
        }
    }

    // ============================================================================
    // EXPORTAÃ‡ÃƒO
    // ============================================================================

    async function handleExport() {
        const exportBtn = document.getElementById('exportBtn');
        if (!exportBtn) return;

        const originalHTML = exportBtn.innerHTML;

        exportBtn.disabled = true;
        exportBtn.innerHTML = `
            <div class="spinner" style="width: 1rem; height: 1rem; border-width: 2px;"></div>
            <span>Exportando...</span>
        `;

        try {
            const type = document.getElementById('exportType')?.value || 'despesas_por_categoria';
            const format = document.getElementById('exportFormat')?.value || 'pdf';

            const params = new URLSearchParams({
                type,
                format,
                year: state.currentMonth.split('-')[0],
                month: state.currentMonth.split('-')[1]
            });

            if (state.currentAccount) {
                params.set('account_id', state.currentAccount);
            }

            const response = await fetch(`${CONFIG.BASE_URL}api/reports/export?${params}`, {
                credentials: 'include'
            });

            if (await handleRestrictedAccess(response)) {
                return;
            }

            if (!response.ok) throw new Error('Export failed');

            const blob = await response.blob();
            const disposition = response.headers.get('Content-Disposition');
            const filename = Utils.extractFilename(disposition) ||
                (format === 'excel' ? 'relatorio.xlsx' : 'relatorio.pdf');

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Export error:', error);
            alert('Erro ao exportar relatÃ³rio. Tente novamente.');
        } finally {
            exportBtn.disabled = false;
            exportBtn.innerHTML = originalHTML;
        }
    }

    // ============================================================================
    // EVENT HANDLERS
    // ============================================================================

    function syncPickerMode() {
        const showYear = isYearlyView();
        window.LukratoHeader?.setPickerMode?.(showYear ? 'year' : 'month');
        if (showYear) {
            const headerYear = window.LukratoHeader?.getYear?.();
            if (headerYear) {
                const [, monthPart = '01'] = state.currentMonth.split('-');
                const normalizedMonth = String(monthPart).padStart(2, '0');
                state.currentMonth = `${headerYear}-${normalizedMonth}`;
            }
        }
    }

    function handleTabChange(view) {
        state.currentView = view;
        setActiveTab(view);
        updateControls();
        syncPickerMode();
        renderReport();
    }

    function handleTypeChange(type) {
        if (state.currentView === CONFIG.VIEWS.ANNUAL_CATEGORY) {
            state.annualCategoryType = type;
        } else {
            state.categoryType = type;
        }
        renderReport();
    }

    function handleAccountChange(accountId) {
        state.currentAccount = accountId || null;
        renderReport();
    }

    function onExternalMonthChange(event) {
        if (!event?.detail?.month || isYearlyView()) return;
        if (state.currentMonth === event.detail.month) return;
        state.currentMonth = event.detail.month;
        updateMonthLabel();
        renderReport();
    }

    function onExternalYearChange(event) {
        if (!isYearlyView() || !event?.detail?.year) return;
        const [, monthPart = '01'] = state.currentMonth.split('-');
        const normalizedMonth = String(monthPart).padStart(2, '0');
        const newValue = `${event.detail.year}-${normalizedMonth}`;
        if (state.currentMonth === newValue) return;
        state.currentMonth = newValue;
        renderReport();
    }

    // ============================================================================
    // INICIALIZAÃ‡ÃƒO
    // ============================================================================

    async function initialize() {
        console.log('ðŸš€ Inicializando Sistema de RelatÃ³rios...');
        console.log('Base URL:', CONFIG.BASE_URL);

        setupChartDefaults();

        // Carregar contas
        state.accounts = await fetchAccounts();
        const accountSelect = document.getElementById('accountFilter');
        if (accountSelect) {
            state.accounts.forEach(acc => {
                const option = document.createElement('option');
                option.value = acc.id;
                option.textContent = acc.name;
                accountSelect.appendChild(option);
            });
        }

        // Event listeners
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => handleTabChange(btn.dataset.view));
        });

        const reportType = document.getElementById('reportType');
        if (reportType) {
            reportType.addEventListener('change', (e) => handleTypeChange(e.target.value));
        }

        if (accountSelect) {
            accountSelect.addEventListener('change', (e) => handleAccountChange(e.target.value));
        }

        const headerMonth = window.LukratoHeader?.getMonth?.();
        if (headerMonth) {
            state.currentMonth = headerMonth;
        }

        document.addEventListener('lukrato:month-changed', onExternalMonthChange);
        document.addEventListener('lukrato:year-changed', onExternalYearChange);

        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', handleExport);
        }

        // RenderizaÃ§Ã£o inicial
        syncPickerMode();
        updateMonthLabel();
        updateControls();
        renderReport();

        console.log('âœ… Sistema de RelatÃ³rios carregado com sucesso!');
    }

    // Iniciar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

    // API Global
    window.ReportsAPI = {
        setMonth: (yearMonth) => {
            if (!/^\d{4}-\d{2}$/.test(yearMonth)) return;
            state.currentMonth = yearMonth;
            updateMonthLabel();
            renderReport();
        },
        setView: (view) => {
            if (Object.values(CONFIG.VIEWS).includes(view)) {
                handleTabChange(view);
            }
        },
        refresh: () => renderReport(),
        getState: () => ({ ...state })
    };
})();

