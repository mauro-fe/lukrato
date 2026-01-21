/**
 * ============================================================================
 * SISTEMA DE RELATÓRIOS - JAVASCRIPT
 * ============================================================================
 * Gerencia visualização de relatórios financeiros com gráficos interativos
 * ============================================================================
 */

(() => {
    'use strict';

    const PAYWALL_MESSAGE = 'Relatórios são exclusivos do plano Pro.';

    // Previne inicialização dupla
    if (window.__LK_REPORTS_LOADED__) return;
    window.__LK_REPORTS_LOADED__ = true;

    // ============================================================================
    // CONFIGURAÇÃO
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
            CARDS: 'cards',
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
        },

        getCssVar(name, fallback = '') {
            try {
                const value = getComputedStyle(document.documentElement).getPropertyValue(name);
                return (value || '').trim() || fallback;
            } catch {
                return fallback;
            }
        },

        isLightTheme() {
            try {
                return (document.documentElement?.getAttribute('data-theme') || 'dark') === 'light';
            } catch {
                return false;
            }
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

    // Plugin para exibir porcentagens dentro dos gráficos de pizza/doughnut
    const DoughnutLabelsPlugin = {
        id: 'lkDoughnutLabels',
        afterDatasetDraw(chart, args, pluginOptions) {
            const meta = chart.getDatasetMeta(args.index);
            if (!meta || meta.type !== 'doughnut') return;

            const dataset = chart.data.datasets?.[args.index];
            const data = dataset?.data || [];
            const options = pluginOptions || {};
            const total = Number.isFinite(options.total)
                ? Number(options.total)
                : data.reduce((sum, value) => sum + (Number(value) || 0), 0);
            if (!total) return;

            const color = options.color || '#fff';
            const minPercentage = options.minPercentage ?? 0;
            const fontSize = options.font?.size || 12;
            const fontWeight = options.font?.weight || 'bold';
            const fontFamily = options.font?.family || 'Arial, sans-serif';

            const ctx = chart.ctx;
            ctx.save();
            ctx.fillStyle = color;
            ctx.font = `${fontWeight} ${fontSize}px ${fontFamily}`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            meta.data.forEach((element, index) => {
                const value = Number(data[index]) || 0;
                if (!value) return;

                const percentage = (value / total) * 100;
                if (percentage < minPercentage) return;

                const label = `${percentage.toFixed(percentage >= 10 ? 0 : 1)}%`;
                const { x, y } = element.tooltipPosition();
                ctx.fillText(label, x, y);
            });

            ctx.restore();
        }
    };

    Chart.register(DoughnutLabelsPlugin);

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
                const response = await fetch(`${CONFIG.BASE_URL}api/contas`, {
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

        async fetchSummaryStats() {
            const [year, month] = state.currentMonth.split('-');
            try {
                const response = await fetch(
                    `${CONFIG.BASE_URL}api/reports/summary?year=${year}&month=${month}`,
                    {
                        credentials: 'include',
                        headers: { 'Accept': 'application/json' }
                    }
                );

                if (await handleRestrictedAccess(response)) {
                    return {
                        totalReceitas: 0,
                        totalDespesas: 0,
                        saldo: 0,
                        totalCartoes: 0
                    };
                }

                if (!response.ok) throw new Error('Failed to fetch summary stats');

                const json = await response.json();
                return json.data || json;
            } catch (error) {
                console.error('Error fetching summary stats:', error);
                return {
                    totalReceitas: 0,
                    totalDespesas: 0,
                    saldo: 0,
                    totalCartoes: 0
                };
            }
        },

        async fetchInsights() {
            const [year, month] = state.currentMonth.split('-');
            try {
                const response = await fetch(
                    `${CONFIG.BASE_URL}api/reports/insights?year=${year}&month=${month}`,
                    {
                        credentials: 'include',
                        headers: { 'Accept': 'application/json' }
                    }
                );

                if (await handleRestrictedAccess(response)) return { insights: [] };
                if (!response.ok) throw new Error('Failed to fetch insights');

                const json = await response.json();
                return json.data || json;
            } catch (error) {
                console.error('Error fetching insights:', error);
                return { insights: [] };
            }
        },

        async fetchComparatives() {
            const [year, month] = state.currentMonth.split('-');
            try {
                const response = await fetch(
                    `${CONFIG.BASE_URL}api/reports/comparatives?year=${year}&month=${month}`,
                    {
                        credentials: 'include',
                        headers: { 'Accept': 'application/json' }
                    }
                );

                if (await handleRestrictedAccess(response)) return null;
                if (!response.ok) throw new Error('Failed to fetch comparatives');

                const json = await response.json();
                return json.data || json;
            } catch (error) {
                console.error('Error fetching comparatives:', error);
                return null;
            }
        },

        getReportType() {
            const typeMap = {
                [CONFIG.VIEWS.CATEGORY]: state.categoryType,
                [CONFIG.VIEWS.ANNUAL_CATEGORY]: state.annualCategoryType,
                [CONFIG.VIEWS.BALANCE]: 'saldo_mensal',
                [CONFIG.VIEWS.COMPARISON]: 'receitas_despesas_diario',
                [CONFIG.VIEWS.ACCOUNTS]: 'receitas_despesas_por_conta',
                [CONFIG.VIEWS.CARDS]: 'cartoes_credito',
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
    // GERENCIAMENTO DE GRÁFICOS
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

            const isMobile = window.innerWidth < 768;
            const shouldSplit = !isMobile && entries.length > 2;
            const chunkSize = shouldSplit ? Math.ceil(entries.length / 2) : entries.length;
            const chunks = shouldSplit
                ? [entries.slice(0, chunkSize), entries.slice(chunkSize)].filter(chunk => chunk.length)
                : [entries];

            const html = `
                <div class="chart-container chart-container-pie">
                    <div class="chart-dual">
                        ${chunks.map((_, idx) => `
                            <div class="chart-wrapper chart-wrapper-pie">
                                <canvas id="chart${idx}"></canvas>
                            </div>
                        `).join('')}
                    </div>
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
            const title = titleMap[type] || 'Distribuição por Categoria';

            state.chart = chunks.map((chunk, idx) => {
                const canvas = document.getElementById(`chart${idx}`);
                const isLightTheme = (document.documentElement.getAttribute('data-theme') || '').toLowerCase() === 'light'
                    || Utils.isLightTheme();
                const labelColor = isLightTheme ? '#2c3e50' : '#ffffff';
                const chunkTotal = chunk.reduce((sum, item) => sum + item.value, 0);

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
                            },
                            lkDoughnutLabels: {
                                color: labelColor,
                                font: { size: 12, weight: 'bold', family: 'Arial, sans-serif' },
                                minPercentage: 1,
                                total: chunkTotal
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
                <div class="chart-container chart-container-line">
                    <div class="chart-wrapper chart-wrapper-line">
                        <canvas id="chart0"></canvas>
                    </div>
                </div>
            `);

            this.destroy();

            const color = getComputedStyle(document.documentElement)
                .getPropertyValue('--color-primary').trim();
            const isLightTheme = (document.documentElement.getAttribute('data-theme') || '').toLowerCase() === 'light'
                || Utils.isLightTheme();
            const yTickColor = isLightTheme ? '#000' : '#fff';

            state.chart = new Chart(document.getElementById('chart0'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Saldo Diário',
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
                    aspectRatio: 1.8,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 12 }
                            }
                        },
                        title: { 
                            display: true, 
                            text: 'Evolução do Saldo Mensal',
                            font: { size: 16, weight: 'bold' },
                            padding: { top: 10, bottom: 20 }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => formatCurrency(context.parsed.y)
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: 20,
                            bottom: 20,
                            left: 10,
                            right: 10
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: yTickColor,
                                font: { size: 11 },
                                padding: 8,
                                callback: (value) => formatCurrency(value)
                            }
                        },
                        x: {
                            ticks: {
                                font: { size: 11 },
                                padding: 5
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
                <div class="chart-container chart-container-bar">
                    <div class="chart-wrapper chart-wrapper-bar">
                        <canvas id="chart0"></canvas>
                    </div>
                </div>
            `);

            this.destroy();

            const colorSuccess = Utils.getCssVar('--color-success', '#2ecc71');
            const colorDanger = Utils.getCssVar('--color-danger', '#e74c3c');
            const isLightTheme = (document.documentElement.getAttribute('data-theme') || '').toLowerCase() === 'light'
                || Utils.isLightTheme();
            const axisColor = isLightTheme
                ? Utils.getCssVar('--color-primary', '#e67e22')
                : 'rgba(255, 255, 255, 0.7)';
            const yTickColor = isLightTheme ? '#000' : '#fff';
            const gridColor = isLightTheme
                ? 'rgba(0, 0, 0, 0.08)'
                : 'rgba(255, 255, 255, 0.05)';
            const xTickColor = isLightTheme
                ? Utils.getCssVar('--color-text-muted', '#6c757d')
                : 'rgba(255, 255, 255, 0.7)';

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
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 12 }
                            }
                        },
                        title: {
                            display: true,
                            text: state.currentView === CONFIG.VIEWS.ACCOUNTS
                                ? 'Receitas x Despesas por Conta'
                                : state.currentView === CONFIG.VIEWS.ANNUAL_SUMMARY
                                    ? 'Resumo Anual por Mês'
                                    : 'Receitas x Despesas',
                            font: { size: 16, weight: 'bold' },
                            padding: { top: 10, bottom: 20 }
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
                    layout: {
                        padding: {
                            top: 20,
                            bottom: 20,
                            left: 10,
                            right: 10
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: yTickColor,
                                font: { size: 11 },
                                padding: 8,
                                callback: (value) => formatCurrency(value)
                            }
                        },
                        x: {
                            ticks: {
                                color: xTickColor,
                                font: { size: 11 },
                                padding: 5
                            },
                            grid: {
                                display: false
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
    // INTERFACE DO USUÁRIO
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
                    <div class="loading-state">
                        <div class="spinner" aria-label="Carregando"></div>
                        <p>Carregando relatório...</p>
                    </div>
                `;
            }
        },

        showEmptyState() {
            this.setContent(`
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h3>Nenhum registro encontrado</h3>
                    <p>Não há informações disponíveis para o perí­odo selecionado.</p>
                </div>
            `);
        },
        showPaywall(message = PAYWALL_MESSAGE) {
            const area = document.getElementById('reportArea');
            if (!area) return;

            const safeMessage = escapeHtml(message || PAYWALL_MESSAGE);
            area.setAttribute('aria-busy', 'false');
            area.innerHTML = `
                <div class="paywall-message" role="alert">
                    <i class="fas fa-crown" aria-hidden="true"></i>
                    <h3>Recurso Premium</h3>
                    <p>${safeMessage}</p>
                    <button type="button" class="btn-upgrade" data-action="go-pro">
                        <i class="fas fa-crown"></i>
                        Fazer Upgrade para PRO
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
    // RENDERIZAÇÃO
    // ============================================================================

    async function renderReport() {
        showLoading();

        // Atualizar cards de resumo
        updateSummaryCards();

        const data = await fetchReportData();

        if (state.accessRestricted) {
            return;
        }

        // Validação específica para cada tipo de relatório
        if (state.currentView === CONFIG.VIEWS.CARDS) {
            renderCardsReport(data);
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

    async function updateSummaryCards() {
        const stats = await API.fetchSummaryStats();

        const totalReceitasEl = document.getElementById('totalReceitas');
        const totalDespesasEl = document.getElementById('totalDespesas');
        const saldoMesEl = document.getElementById('saldoMes');
        const totalCartoesEl = document.getElementById('totalCartoes');

        if (totalReceitasEl) {
            totalReceitasEl.textContent = formatCurrency(stats.totalReceitas || 0);
        }
        if (totalDespesasEl) {
            totalDespesasEl.textContent = formatCurrency(stats.totalDespesas || 0);
        }
        if (saldoMesEl) {
            const saldo = stats.saldo || 0;
            saldoMesEl.textContent = formatCurrency(saldo);
            saldoMesEl.style.color = saldo >= 0 ? 'var(--color-success)' : 'var(--color-danger)';
        }
        if (totalCartoesEl) {
            totalCartoesEl.textContent = formatCurrency(stats.totalCartoes || 0);
        }

        // Atualizar insights se existir na página
        await updateInsightsSection();

        // Atualizar comparativos se existir na página
        await updateComparativesSection();
    }

    async function updateInsightsSection() {
        const insightsContainer = document.getElementById('insightsContainer');
        if (!insightsContainer) return;

        const data = await API.fetchInsights();
        if (!data || !data.insights || data.insights.length === 0) {
            insightsContainer.innerHTML = '<p class="empty-message">Nenhum insight disponível no momento</p>';
            return;
        }

        const insightsHTML = data.insights.map(insight => `
            <div class="insight-card insight-${insight.type}">
                <div class="insight-icon">
                    <i class="fas fa-${insight.icon}"></i>
                </div>
                <div class="insight-content">
                    <h4>${escapeHtml(insight.title)}</h4>
                    <p>${escapeHtml(insight.message)}</p>
                </div>
            </div>
        `).join('');

        insightsContainer.innerHTML = insightsHTML;
    }

    async function updateComparativesSection() {
        const comparativesContainer = document.getElementById('comparativesContainer');
        if (!comparativesContainer) return;

        const data = await API.fetchComparatives();
        if (!data) {
            comparativesContainer.innerHTML = '<p class="empty-message">Dados de comparação não disponíveis</p>';
            return;
        }

        const monthlyHTML = renderComparative('Comparativo Mensal', data.monthly, 'mês anterior');
        const yearlyHTML = renderComparative('Comparativo Anual', data.yearly, 'ano anterior');

        comparativesContainer.innerHTML = monthlyHTML + yearlyHTML;
    }

    function renderComparative(title, data, period) {
        const getTrendIcon = (value, isDespesa = false) => {
            // Para despesas, aumento é ruim (vermelho), redução é bom (verde)
            if (isDespesa) {
                if (value > 0) return '<i class="fas fa-arrow-up"></i>';
                if (value < 0) return '<i class="fas fa-arrow-down"></i>';
            } else {
                if (value > 0) return '<i class="fas fa-arrow-up"></i>';
                if (value < 0) return '<i class="fas fa-arrow-down"></i>';
            }
            return '<i class="fas fa-equals"></i>';
        };

        const getTrendClass = (value, isDespesa = false) => {
            if (isDespesa) {
                if (value > 0) return 'trend-negative';
                if (value < 0) return 'trend-positive';
            } else {
                if (value > 0) return 'trend-positive';
                if (value < 0) return 'trend-negative';
            }
            return 'trend-neutral';
        };

        const getTrendText = (value, isDespesa = false) => {
            if (Math.abs(value) < 0.1) return 'Sem alteração';

            if (isDespesa) {
                if (value > 0) return `Aumentou ${Math.abs(value).toFixed(1)}%`;
                if (value < 0) return `Reduziu ${Math.abs(value).toFixed(1)}%`;
            } else {
                if (value > 0) return `Aumentou ${Math.abs(value).toFixed(1)}%`;
                if (value < 0) return `Reduziu ${Math.abs(value).toFixed(1)}%`;
            }
            return 'Sem alteração';
        };

        // Formatar período atual e anterior
        const getCurrentPeriod = () => {
            if (period.includes('mês')) {
                const [year, month] = state.currentMonth.split('-');
                const date = new Date(year, month - 1);
                return date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
            } else {
                return state.currentMonth.split('-')[0];
            }
        };

        const getPreviousPeriod = () => {
            if (period.includes('mês')) {
                const [year, month] = state.currentMonth.split('-');
                const date = new Date(year, month - 2);
                return date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
            } else {
                return (parseInt(state.currentMonth.split('-')[0]) - 1).toString();
            }
        };

        return `
            <div class="comparative-card">
                <div class="comparative-header">
                    <h3>${escapeHtml(title)}</h3>
                    <div class="period-labels">
                        <span class="period-current"><i class="fas fa-calendar"></i> ${getCurrentPeriod()}</span>
                        <span class="period-separator">vs</span>
                        <span class="period-previous">${getPreviousPeriod()}</span>
                    </div>
                </div>
                
                <div class="comparative-grid-new">
                    <div class="comparative-item-new">
                        <div class="item-header">
                            <i class="fas fa-arrow-trend-up item-icon revenue"></i>
                            <span class="item-label">RECEITAS</span>
                        </div>
                        <div class="item-values">
                            <div class="value-current">
                                <span class="value-label">Atual</span>
                                <span class="value-amount">${formatCurrency(data.current.receitas)}</span>
                            </div>
                            <div class="value-previous">
                                <span class="value-label">Anterior</span>
                                <span class="value-amount">${formatCurrency(data.previous.receitas)}</span>
                            </div>
                        </div>
                        <div class="item-trend ${getTrendClass(data.variation.receitas, false)}">
                            ${getTrendIcon(data.variation.receitas, false)}
                            <span>${getTrendText(data.variation.receitas, false)}</span>
                        </div>
                    </div>
                    
                    <div class="comparative-item-new">
                        <div class="item-header">
                            <i class="fas fa-arrow-trend-down item-icon expense"></i>
                            <span class="item-label">DESPESAS</span>
                        </div>
                        <div class="item-values">
                            <div class="value-current">
                                <span class="value-label">Atual</span>
                                <span class="value-amount">${formatCurrency(data.current.despesas)}</span>
                            </div>
                            <div class="value-previous">
                                <span class="value-label">Anterior</span>
                                <span class="value-amount">${formatCurrency(data.previous.despesas)}</span>
                            </div>
                        </div>
                        <div class="item-trend ${getTrendClass(data.variation.despesas, true)}">
                            ${getTrendIcon(data.variation.despesas, true)}
                            <span>${getTrendText(data.variation.despesas, true)}</span>
                        </div>
                    </div>
                    
                    <div class="comparative-item-new">
                        <div class="item-header">
                            <i class="fas fa-wallet item-icon balance"></i>
                            <span class="item-label">SALDO</span>
                        </div>
                        <div class="item-values">
                            <div class="value-current">
                                <span class="value-label">Atual</span>
                                <span class="value-amount">${formatCurrency(data.current.saldo)}</span>
                            </div>
                            <div class="value-previous">
                                <span class="value-label">Anterior</span>
                                <span class="value-amount">${formatCurrency(data.previous.saldo)}</span>
                            </div>
                        </div>
                        <div class="item-trend ${getTrendClass(data.variation.saldo, false)}">
                            ${getTrendIcon(data.variation.saldo, false)}
                            <span>${getTrendText(data.variation.saldo, false)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderCardsReport(data) {
        const reportArea = document.getElementById('reportArea');
        if (!reportArea) return;

        // Renderizar resumo consolidado apenas se houver cartões
        const resumoHTML = (data.resumo_consolidado && data.cards && data.cards.length > 0) ? `
            <div class="consolidated-summary">
                <div class="summary-header">
                    <div class="summary-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="summary-title">
                        <h3>Visão Geral dos Cartões</h3>
                        <p>Resumo consolidado de todos os seus cartões de crédito</p>
                    </div>
                </div>
                
                <div class="summary-grid">
                    <div class="summary-stat">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total em Faturas</span>
                            <span class="stat-value">${formatCurrency(data.resumo_consolidado.total_faturas)}</span>
                        </div>
                    </div>
                    
                    <div class="summary-stat">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Limite Total</span>
                            <span class="stat-value">${formatCurrency(data.resumo_consolidado.total_limites)}</span>
                        </div>
                    </div>
                    
                    <div class="summary-stat">
                        <div class="stat-icon" style="background: linear-gradient(135deg, ${data.resumo_consolidado.utilizacao_geral > 70 ? '#e74c3c, #c0392b' :
                data.resumo_consolidado.utilizacao_geral > 50 ? '#f39c12, #e67e22' :
                    '#2ecc71, #27ae60'
            });">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Utilização Geral</span>
                            <span class="stat-value">${data.resumo_consolidado.utilizacao_geral.toFixed(1)}%</span>
                        </div>
                    </div>
                    
                    <div class="summary-stat">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Disponível</span>
                            <span class="stat-value">${formatCurrency(data.resumo_consolidado.total_disponivel)}</span>
                        </div>
                    </div>
                </div>
                
                ${data.resumo_consolidado.melhor_cartao || data.resumo_consolidado.requer_atencao ? `
                    <div class="summary-insights">
                        ${data.resumo_consolidado.melhor_cartao ? `
                            <div class="insight-item success">
                                <i class="fas fa-star"></i>
                                <span><strong>Melhor cartão:</strong> ${escapeHtml(data.resumo_consolidado.melhor_cartao.nome)} (${data.resumo_consolidado.melhor_cartao.percentual.toFixed(1)}% de uso)</span>
                            </div>
                        ` : ''}
                        ${data.resumo_consolidado.requer_atencao ? `
                            <div class="insight-item warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span><strong>Requer atenção:</strong> ${escapeHtml(data.resumo_consolidado.requer_atencao.nome)} (${data.resumo_consolidado.requer_atencao.percentual.toFixed(1)}% de uso)</span>
                            </div>
                        ` : ''}
                        ${data.resumo_consolidado.total_parcelamentos > 0 ? `
                            <div class="insight-item info">
                                <i class="fas fa-calendar-check"></i>
                                <span><strong>${data.resumo_consolidado.total_parcelamentos} parcelamento${data.resumo_consolidado.total_parcelamentos > 1 ? 's' : ''}</strong> comprometendo ${formatCurrency(data.resumo_consolidado.valor_parcelamentos)}</span>
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        ` : '';

        reportArea.innerHTML = `
            <div class="cards-report-container">
                ${resumoHTML}
                
                <div class="cards-grid">
                    ${data.cards && data.cards.length > 0 ? data.cards.map(card => `
                        <div class="card-item ${card.status_saude.status}" 
                             style="--card-color: ${card.cor || '#E67E22'}; cursor: pointer;"
                             data-card-id="${card.id || ''}"
                             data-card-nome="${escapeHtml(card.nome)}"
                             data-card-cor="${card.cor || '#E67E22'}"
                             onclick="if(window.LK_CardDetail?.open) window.LK_CardDetail.open(${card.id || 0}, '${escapeHtml(card.nome)}', '${card.cor || '#E67E22'}', '${state.currentMonth}')"
                             role="button"
                             tabindex="0">
                            <!-- Header -->
                            <div class="card-header-gradient">
                                <div class="card-brand">
                                    <div class="card-icon-wrapper" style="background: linear-gradient(135deg, ${card.cor || '#E67E22'}, ${card.cor || '#E67E22'}99);">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="card-info">
                                        <h3 class="card-name">${escapeHtml(card.nome)}</h3>
                                        <div class="card-meta">
                                            ${card.conta ? `<span class="card-account"><i class="fas fa-building-columns"></i> ${escapeHtml(card.conta)}</span>` : ''}
                                            ${card.dia_vencimento ? `<span class="card-due"><i class="fas fa-calendar"></i> Vence dia ${card.dia_vencimento}</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                                ${card.status_saude && (card.status_saude.status === 'critico' || card.status_saude.status === 'alto_uso') ? `
                                    <div class="health-indicator ${card.status_saude.status}">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                ` : ''}
                            </div>

                            <!-- Tendência compacta -->
                            ${card.historico_6_meses && card.historico_6_meses.length > 0 ? `
                                <div class="card-trend-compact">
                                    <span class="trend-label">ÚLTIMOS 6 MESES</span>
                                    <span class="trend-indicator ${card.tendencia}">
                                        ${card.tendencia === 'subindo' ? '↗' : card.tendencia === 'caindo' ? '↘' : '→'} ${card.tendencia === 'subindo' ? 'Em alta' : card.tendencia === 'caindo' ? 'Em queda' : 'Estável'}
                                    </span>
                                </div>
                            ` : ''}

                            <!-- Alertas -->
                            ${card.alertas && card.alertas.length > 0 ? `
                                <div class="card-alerts">
                                    ${card.alertas.map(alert => `
                                        <span class="alert-badge alert-${alert.type}">
                                            <i class="fas fa-${alert.type === 'danger' ? 'exclamation-triangle' : alert.type === 'warning' ? 'exclamation-circle' : 'info-circle'}"></i>
                                            ${escapeHtml(alert.message)}
                                        </span>
                                    `).join('')}
                                </div>
                            ` : ''}


                            <!-- Stats principais -->
                            <div class="card-balance">
                                <div class="balance-main">
                                    <span class="balance-label">FATURA DO MÊS</span>
                                    <span class="balance-value">${formatCurrency(card.fatura_atual || 0)}</span>
                                    ${card.media_historica > 0 && Math.abs(card.fatura_atual - card.media_historica) > 1 ? `
                                        <span class="balance-comparison">
                                            ${card.fatura_atual > card.media_historica ? '↑' : '↓'} ${((Math.abs(card.fatura_atual - card.media_historica) / card.media_historica) * 100).toFixed(0)}% vs média
                                        </span>
                                    ` : ''}
                                </div>
                                <div class="balance-grid">
                                    <div class="balance-item">
                                        <span class="balance-small-label">Limite</span>
                                        <span class="balance-small-value">${formatCurrency(card.limite || 0)}</span>
                                    </div>
                                    <div class="balance-item">
                                        <span class="balance-small-label">Disponível</span>
                                        <span class="balance-small-value">${formatCurrency(card.disponivel || 0)}</span>
                                    </div>
                                </div>
                            </div>


                            <!-- Barra de utilização -->
                            <div class="card-usage-new">
                                <div class="usage-header">
                                    <span class="usage-label">UTILIZAÇÃO DO LIMITE</span>
                                    <span class="usage-percentage">${(card.percentual || 0).toFixed(1)}%</span>
                                </div>
                                <div class="usage-bar-new">
                                    <div class="usage-fill-new" 
                                         style="width: ${Math.min(card.percentual || 0, 100)}%"></div>
                                </div>
                            </div>

                            <!-- Resumo rápido de informações adicionais -->
                            ${card.parcelamentos && card.parcelamentos.ativos > 0 || (card.proximos_meses && card.proximos_meses.length > 0 && card.proximos_meses.some(m => m.valor > 0)) ? `
                                <div class="card-quick-info">
                                    ${card.parcelamentos && card.parcelamentos.ativos > 0 ? `
                                        <div class="quick-info-item">
                                            <i class="fas fa-calendar-check"></i>
                                            <span>${card.parcelamentos.ativos} parcelamento${card.parcelamentos.ativos > 1 ? 's' : ''}</span>
                                        </div>
                                    ` : ''}
                                    ${card.proximos_meses && card.proximos_meses.length > 0 && card.proximos_meses.some(m => m.valor > 0) ? `
                                        <div class="quick-info-item">
                                            <i class="fas fa-chart-line"></i>
                                            <span>Próximo: ${formatCurrency(card.proximos_meses.find(m => m.valor > 0)?.valor || 0)}</span>
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}
                            
                            <div class="card-footer">
                                <button class="card-action-btn primary full-width" onclick="event.stopPropagation(); if(window.LK_CardDetail?.open) window.LK_CardDetail.open(${card.id || 0}, '${escapeHtml(card.nome)}', '${card.cor || '#E67E22'}', '${state.currentMonth}')" title="Ver relatório detalhado">
                                    <i class="fas fa-eye"></i>
                                    <span>Ver Detalhes</span>
                                </button>
                            </div>
                        </div>
                    `).join('') : `
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h3>Nenhum cartão de crédito cadastrado</h3>
                            <p>Cadastre seus cartões de crédito para visualizar relatórios detalhados de gastos e parcelamentos.</p>
                        </div>
                    `}
                </div>
            </div>
        `;
    }

    // ============================================================================
    // EXPORTAÇÃO
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
    // INICIALIZAÇÃO
    // ============================================================================

    async function initialize() {

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

        document.addEventListener('lukrato:theme-changed', () => {
            setupChartDefaults();
            renderReport();
        });

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

        // Renderização inicial
        syncPickerMode();
        updateMonthLabel();
        updateControls();
        renderReport();

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

