/**
 * ============================================================================
 * LUKRATO — Relatórios / Charts
 * ============================================================================
 * ApexCharts chart rendering (donut, area/line, bar).
 * Registers ChartManager on Modules for cross-module access.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';

// Local aliases
const formatCurrency = (v) => Utils.formatCurrency(v);
const escapeHtml = (v) => String(v ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m] || m));

// ─── Theme helpers ──────────────────────────────────────────────────────────

function _getTheme() {
    const isLight = (document.documentElement.getAttribute('data-theme') || '').toLowerCase() === 'light'
        || Utils.isLightTheme?.();
    return {
        isLight,
        mode: isLight ? 'light' : 'dark',
        textColor: isLight ? '#2c3e50' : '#ffffff',
        textMuted: isLight ? '#6c757d' : 'rgba(255, 255, 255, 0.7)',
        gridColor: isLight ? 'rgba(0, 0, 0, 0.08)' : 'rgba(255, 255, 255, 0.05)',
        surfaceColor: getComputedStyle(document.documentElement).getPropertyValue('--color-surface').trim(),
    };
}

// ─── Chart Manager ───────────────────────────────────────────────────────────

export const ChartManager = {
    _currentEntries: null,

    destroy() {
        if (STATE.chart) {
            if (Array.isArray(STATE.chart)) {
                STATE.chart.forEach(c => c?.destroy());
            } else {
                STATE.chart.destroy();
            }
            STATE.chart = null;
        }
        // Clean up drilldown state
        if (ChartManager._drilldownChart) {
            ChartManager._drilldownChart.destroy();
            ChartManager._drilldownChart = null;
        }
        STATE.activeDrilldown = null;
        STATE.reportDetails = null;
    },

    setupDefaults() {
        const textColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--color-text').trim();

        window.Apex = window.Apex || {};
        window.Apex.chart = { foreColor: textColor, fontFamily: 'Inter, Arial, sans-serif' };
        window.Apex.grid = { borderColor: 'rgba(255, 255, 255, 0.1)' };
    },

    renderPie(data) {
        const { labels = [], values = [], details = null, cat_ids = null } = data;

        if (!labels.length || !values.some(v => v > 0)) {
            return Modules.UI.showEmptyState();
        }

        // Preparar entradas com cores
        let entries = labels
            .map((label, idx) => ({
                label,
                value: Number(values[idx]) || 0,
                color: CONFIG.CHART_COLORS[idx % CONFIG.CHART_COLORS.length],
                catId: cat_ids ? (cat_ids[idx] ?? null) : null
            }))
            .filter(e => e.value > 0)
            .sort((a, b) => b.value - a.value);

        // Re-map catId after sorting when we don't have cat_ids from API
        if (!cat_ids && details) {
            entries = entries.map(e => {
                const match = details.find(d => d.label === e.label);
                return { ...e, catId: match?.cat_id ?? null };
            });
        }

        const isMobile = window.innerWidth < 768;

        // ===================================================================
        // LÓGICA MOBILE: TOP 5 + OUTROS
        // ===================================================================
        let processedEntries = entries;
        if (isMobile && entries.length > 5) {
            const top5 = entries.slice(0, 5);
            const others = entries.slice(5);
            const othersTotal = others.reduce((sum, item) => sum + item.value, 0);

            processedEntries = [
                ...top5,
                {
                    label: 'Outros',
                    value: othersTotal,
                    color: '#95a5a6',
                    isOthers: true
                }
            ];
        }

        // Desktop: dividir em duas colunas se houver muitas categorias
        const shouldSplit = !isMobile && processedEntries.length > 2;
        const chunkSize = shouldSplit ? Math.ceil(processedEntries.length / 2) : processedEntries.length;
        const chunks = shouldSplit
            ? [processedEntries.slice(0, chunkSize), processedEntries.slice(chunkSize)].filter(chunk => chunk.length)
            : [processedEntries];

        const html = `
            <div class="chart-container chart-container-pie">
                <div class="chart-dual">
                    ${chunks.map((_, idx) => `
                        <div class="chart-wrapper chart-wrapper-pie">
                            <div id="chart${idx}"></div>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div id="subcategoryDrilldown" class="drilldown-panel" aria-hidden="true"></div>
            ${isMobile ? '<div id="categoryListMobile" class="category-list-mobile"></div>' : ''}
        `;

        Modules.UI.setContent(html);
        ChartManager.destroy();

        // Store details AFTER destroy()
        STATE.reportDetails = details;
        STATE.activeDrilldown = null;

        ChartManager._currentEntries = processedEntries;

        const type = Utils.getActiveCategoryType();
        const titleMap = {
            'receitas_por_categoria': 'Receitas por Categoria',
            'despesas_por_categoria': 'Despesas por Categoria',
            'receitas_anuais_por_categoria': 'Receitas anuais por Categoria',
            'despesas_anuais_por_categoria': 'Despesas anuais por Categoria'
        };
        const title = titleMap[type] || 'Distribuição por Categoria';

        const theme = _getTheme();

        // Track cumulative offset for multi-chunk indexing
        let chunkOffset = 0;

        STATE.chart = chunks.map((chunk, idx) => {
            const el = document.getElementById(`chart${idx}`);
            if (!el) return null;

            const chunkTotal = chunk.reduce((sum, item) => sum + item.value, 0);
            const currentChunkOffset = chunkOffset;
            chunkOffset += chunk.length;

            const chart = new ApexCharts(el, {
                chart: {
                    type: 'donut',
                    height: '100%',
                    background: 'transparent',
                    fontFamily: 'Inter, Arial, sans-serif',
                    events: {
                        dataPointSelection: (event, chartContext, config) => {
                            const globalIdx = currentChunkOffset + config.dataPointIndex;
                            const entry = processedEntries[globalIdx];
                            if (!entry || entry.isOthers) return;
                            ChartManager.handlePieClick(entry, globalIdx, config.dataPointIndex, idx);
                        },
                        dataPointMouseEnter: (event) => {
                            if (event.target) event.target.style.cursor = 'pointer';
                        },
                        dataPointMouseLeave: (event) => {
                            if (event.target) event.target.style.cursor = 'default';
                        },
                    },
                },
                series: chunk.map(e => e.value),
                labels: chunk.map(e => e.label),
                colors: chunk.map(e => e.color),
                stroke: { width: 2, colors: [theme.surfaceColor] },
                plotOptions: {
                    pie: {
                        donut: { size: '60%' },
                        expandOnClick: true,
                    },
                },
                legend: {
                    show: !isMobile,
                    position: 'bottom',
                    labels: { colors: theme.textColor },
                    markers: { shape: 'circle' },
                },
                title: {
                    text: chunks.length > 1 ? `${title} - Parte ${idx + 1}` : title,
                    align: 'center',
                    style: { fontSize: '14px', fontWeight: 'bold', color: theme.textColor },
                },
                tooltip: {
                    theme: theme.mode,
                    y: {
                        formatter: (val) => {
                            const pct = chunkTotal > 0 ? ((val / chunkTotal) * 100).toFixed(1) : '0';
                            return `${formatCurrency(val)} (${pct}%)`;
                        },
                    },
                },
                dataLabels: { enabled: false },
                theme: { mode: theme.mode },
            });
            chart.render();

            return chart;
        });

        // ===================================================================
        // RENDERIZAR LISTA DE CATEGORIAS (MOBILE ONLY)
        // ===================================================================
        if (isMobile) {
            ChartManager.renderMobileCategoryList(processedEntries);
        }
    },

    /**
     * Renderiza a lista de categorias para mobile com expansão
     */
    renderMobileCategoryList(entries) {
        const container = document.getElementById('categoryListMobile');
        if (!container) return;

        const total = entries.reduce((sum, item) => sum + item.value, 0);
        const hasDetails = !!STATE.reportDetails && window.IS_PRO;

        const allCategoriesHTML = entries.map((entry, idx) => {
            const percentage = ((entry.value / total) * 100).toFixed(1);
            const detail = hasDetails && entry.catId != null
                ? STATE.reportDetails.find(d => d.cat_id === entry.catId)
                : null;
            const hasSubcats = detail && detail.subcategories && detail.subcategories.filter(s => s.id !== 0).length > 0;
            const chevron = hasSubcats ? `<i data-lucide="chevron-down" class="category-chevron"></i>` : '';

            let subcatHTML = '';
            if (hasSubcats) {
                const shades = Utils.generateShades(entry.color, detail.subcategories.length);
                subcatHTML = `
                    <div class="category-subcats-panel" id="mobileSubcatPanel-${idx}" aria-hidden="true">
                        ${detail.subcategories.map((sub, si) => {
                    const subPct = detail.total > 0 ? ((sub.total / detail.total) * 100).toFixed(1) : '0.0';
                    return `
                                <div class="drilldown-item drilldown-item-mobile">
                                    <div class="drilldown-indicator" style="background-color: ${shades[si]}"></div>
                                    <div class="drilldown-info">
                                        <span class="drilldown-name">${escapeHtml(sub.label)}</span>
                                    </div>
                                    <div class="drilldown-values">
                                        <span class="drilldown-value">${formatCurrency(sub.total)}</span>
                                        <span class="drilldown-pct">${subPct}%</span>
                                    </div>
                                </div>
                            `;
                }).join('')}
                    </div>
                `;
            }

            return `
                <div class="category-item ${hasSubcats ? 'has-subcats' : ''}"
                     ${hasSubcats ? `data-subcat-toggle="${idx}"` : ''}>
                    <div class="category-indicator" style="background-color: ${entry.color}"></div>
                    <div class="category-info">
                        <span class="category-name">${escapeHtml(entry.label)}</span>
                        <span class="category-value">${formatCurrency(entry.value)}</span>
                    </div>
                    <span class="category-percentage">${percentage}%</span>
                    ${chevron}
                </div>
                ${subcatHTML}
            `;
        }).join('');

        container.innerHTML = `
            <button class="category-expand-btn" id="expandCategoriesBtn" aria-expanded="false">
                <span>Ver todas as categorias</span>
                <i data-lucide="chevron-down"></i>
            </button>
            <div class="category-expandable-card" id="expandableCard" aria-hidden="true">
                ${allCategoriesHTML}
            </div>
            ${hasDetails ? '' : `<p class="category-info-text">
                <i data-lucide="info"></i>
                Para visualizar todas as categorias detalhadamente, exporte este relatório em PDF.
            </p>`}
        `;
        if (window.lucide) lucide.createIcons();

        ChartManager.setupExpandToggle();

        if (hasDetails) {
            ChartManager.setupMobileSubcatToggles();
        }
    },

    /**
     * Setup accordion toggles for mobile subcategory panels
     */
    setupMobileSubcatToggles() {
        document.querySelectorAll('[data-subcat-toggle]').forEach(item => {
            item.addEventListener('click', function () {
                const idx = this.dataset.subcatToggle;
                const panel = document.getElementById(`mobileSubcatPanel-${idx}`);
                const chevron = this.querySelector('.category-chevron');
                if (!panel) return;

                const isOpen = panel.getAttribute('aria-hidden') === 'false';
                if (isOpen) {
                    panel.style.maxHeight = '0px';
                    panel.setAttribute('aria-hidden', 'true');
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                } else {
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                    panel.setAttribute('aria-hidden', 'false');
                    if (chevron) chevron.style.transform = 'rotate(180deg)';
                }
            });
        });
    },

    /**
     * Configura o comportamento de expandir/recolher lista de categorias
     */
    setupExpandToggle() {
        const btn = document.getElementById('expandCategoriesBtn');
        const card = document.getElementById('expandableCard');

        if (!btn || !card) return;

        btn.addEventListener('click', function () {
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                card.style.maxHeight = '0px';
                card.setAttribute('aria-hidden', 'true');
                btn.setAttribute('aria-expanded', 'false');
                btn.querySelector('span').textContent = 'Ver todas as categorias';
                btn.querySelector('i').style.transform = 'rotate(0deg)';
            } else {
                card.style.maxHeight = card.scrollHeight + 'px';
                card.setAttribute('aria-hidden', 'false');
                btn.setAttribute('aria-expanded', 'true');
                btn.querySelector('span').textContent = 'Ocultar categorias';
                btn.querySelector('i').style.transform = 'rotate(180deg)';
            }
        });
    },

    /**
     * Handle click on a doughnut segment: toggle subcategory drill-down
     */
    handlePieClick(entry, globalIdx, dataPointIndex, chartIdx) {
        // PRO check
        if (!window.IS_PRO) {
            if (window.Swal?.fire) {
                Swal.fire({
                    icon: 'info',
                    title: 'Recurso Premium',
                    html: 'O detalhamento por <b>subcategorias</b> é exclusivo do <b>plano Pro</b>.<br>Faça upgrade para desbloquear!',
                    confirmButtonText: 'Fazer Upgrade',
                    showCancelButton: true,
                    cancelButtonText: 'Agora não',
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#64748b'
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href = (CONFIG.BASE_URL || '/') + 'billing';
                    }
                });
            }
            return;
        }

        if (!STATE.reportDetails) return;

        const catId = entry.catId;
        const detail = STATE.reportDetails.find(d => d.cat_id === catId);
        if (!detail || !detail.subcategories || detail.subcategories.length === 0) return;

        const realSubcats = detail.subcategories.filter(s => s.id !== 0);
        if (realSubcats.length === 0) {
            if (window.Swal?.fire) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sem subcategorias',
                    text: 'Atribua subcategorias aos seus lançamentos para ver o detalhamento desta categoria.',
                    confirmButtonText: 'Entendi',
                    confirmButtonColor: '#f59e0b',
                    timer: 5000,
                    timerProgressBar: true
                });
            }
            return;
        }

        // Toggle: clicking same segment again closes drill-down
        if (STATE.activeDrilldown === catId) {
            ChartManager.closeDrilldown();
            return;
        }

        STATE.activeDrilldown = catId;

        // ApexCharts handles segment highlight via toggleDataPointSelection
        // The expandOnClick option already provides visual highlight

        ChartManager.renderSubcategoryDrilldown(detail, entry.color);
    },

    /**
     * Close the drill-down panel
     */
    closeDrilldown() {
        STATE.activeDrilldown = null;

        const panel = document.getElementById('subcategoryDrilldown');
        if (panel) {
            panel.style.maxHeight = '0px';
            panel.setAttribute('aria-hidden', 'true');
            setTimeout(() => { panel.innerHTML = ''; }, 400);
        }
    },

    /**
     * Render the subcategory drill-down panel below the doughnut chart
     */
    renderSubcategoryDrilldown(categoryDetail, parentColor) {
        const panel = document.getElementById('subcategoryDrilldown');
        if (!panel) return;

        const { label, total, subcategories } = categoryDetail;
        const shades = Utils.generateShades(parentColor, subcategories.length);

        const subcatItems = subcategories.map((sub, i) => {
            const pct = total > 0 ? ((sub.total / total) * 100).toFixed(1) : '0.0';
            const barWidth = total > 0 ? ((sub.total / total) * 100).toFixed(0) : '0';
            return `
                <div class="drilldown-item" style="animation-delay: ${i * 0.05}s">
                    <div class="drilldown-indicator" style="background-color: ${shades[i]}"></div>
                    <div class="drilldown-info">
                        <span class="drilldown-name">${escapeHtml(sub.label)}</span>
                        <div class="drilldown-bar-bg">
                            <div class="drilldown-bar" style="width: ${barWidth}%; background-color: ${shades[i]}"></div>
                        </div>
                    </div>
                    <div class="drilldown-values">
                        <span class="drilldown-value">${formatCurrency(sub.total)}</span>
                        <span class="drilldown-pct">${pct}%</span>
                    </div>
                </div>
            `;
        }).join('');

        // Mini doughnut only on desktop
        const isMobile = window.innerWidth < 768;
        const miniChartHTML = !isMobile ? `
            <div class="drilldown-mini-chart">
                <div id="drilldownMiniChart"></div>
            </div>
        ` : '';

        panel.innerHTML = `
            <div class="drilldown-header" style="border-left-color: ${parentColor}">
                <div class="drilldown-title">
                    <span class="drilldown-cat-indicator" style="background-color: ${parentColor}"></span>
                    <h4>${escapeHtml(label)}</h4>
                    <span class="drilldown-total">${formatCurrency(total)}</span>
                </div>
                <button class="drilldown-close" id="drilldownCloseBtn" aria-label="Fechar detalhamento">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="drilldown-body">
                ${miniChartHTML}
                <div class="drilldown-list">
                    ${subcatItems}
                </div>
            </div>
        `;

        // Animate open
        panel.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => {
            panel.style.maxHeight = panel.scrollHeight + 'px';
        });

        // Close button
        document.getElementById('drilldownCloseBtn')?.addEventListener('click', () => {
            ChartManager.closeDrilldown();
        });

        // Render mini doughnut chart
        if (!isMobile) {
            ChartManager._renderDrilldownMiniChart(subcategories, shades);
        }

        if (window.lucide) lucide.createIcons();
    },

    /**
     * Render mini doughnut inside the drill-down panel
     */
    _renderDrilldownMiniChart(subcategories, shades) {
        const el = document.getElementById('drilldownMiniChart');
        if (!el) return;

        if (ChartManager._drilldownChart) {
            ChartManager._drilldownChart.destroy();
            ChartManager._drilldownChart = null;
        }

        const theme = _getTheme();
        const totalValue = subcategories.reduce((s, sc) => s + sc.total, 0);

        ChartManager._drilldownChart = new ApexCharts(el, {
            chart: { type: 'donut', height: '100%', background: 'transparent', fontFamily: 'Inter, Arial, sans-serif' },
            series: subcategories.map(s => s.total),
            labels: subcategories.map(s => s.label),
            colors: shades,
            stroke: { width: 2, colors: [theme.surfaceColor] },
            plotOptions: { pie: { donut: { size: '55%' } } },
            legend: { show: false },
            tooltip: {
                theme: theme.mode,
                y: {
                    formatter: (val) => {
                        const pct = totalValue > 0 ? ((val / totalValue) * 100).toFixed(1) : '0';
                        return `${formatCurrency(val)} (${pct}%)`;
                    },
                },
            },
            dataLabels: { enabled: false },
            theme: { mode: theme.mode },
        });
        ChartManager._drilldownChart.render();
    },

    _drilldownChart: null,

    renderLine(data) {
        const { labels = [], values = [] } = data;

        if (!labels.length) return Modules.UI.showEmptyState();

        Modules.UI.setContent(`
            <div class="chart-container chart-container-line">
                <div class="chart-wrapper chart-wrapper-line">
                    <div id="chart0"></div>
                </div>
            </div>
        `);

        ChartManager.destroy();

        const color = getComputedStyle(document.documentElement)
            .getPropertyValue('--color-primary').trim();
        const theme = _getTheme();

        const chart = new ApexCharts(document.getElementById('chart0'), {
            chart: {
                type: 'area',
                height: 380,
                toolbar: { show: false },
                background: 'transparent',
                fontFamily: 'Inter, Arial, sans-serif',
            },
            series: [{ name: 'Saldo Diário', data: values.map(Number) }],
            xaxis: {
                categories: labels,
                labels: { style: { fontSize: '11px' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                min: 0,
                labels: {
                    style: { colors: theme.isLight ? '#000' : '#fff', fontSize: '11px' },
                    formatter: (value) => formatCurrency(value),
                },
            },
            colors: [color],
            stroke: { curve: 'smooth', width: 2.5 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] },
            },
            markers: { size: 4, hover: { size: 6 } },
            grid: { borderColor: theme.gridColor, strokeDashArray: 4 },
            tooltip: { theme: theme.mode, y: { formatter: (v) => formatCurrency(v) } },
            legend: { position: 'bottom', labels: { colors: theme.textColor } },
            title: {
                text: 'Evolução do Saldo Mensal',
                align: 'center',
                style: { fontSize: '16px', fontWeight: 'bold', color: theme.textColor },
            },
            dataLabels: { enabled: false },
            theme: { mode: theme.mode },
        });
        chart.render();
        STATE.chart = chart;
    },

    renderBar(data) {
        const { labels = [], receitas = [], despesas = [] } = data;

        if (!labels.length) return Modules.UI.showEmptyState();

        Modules.UI.setContent(`
            <div class="chart-container chart-container-bar">
                <div class="chart-wrapper chart-wrapper-bar">
                    <div id="chart0"></div>
                </div>
            </div>
        `);

        ChartManager.destroy();

        const colorSuccess = Utils.getCssVar('--color-success', '#2ecc71');
        const colorDanger = Utils.getCssVar('--color-danger', '#e74c3c');
        const theme = _getTheme();

        const chartTitle = STATE.currentView === CONFIG.VIEWS.ACCOUNTS
            ? 'Receitas x Despesas por Conta'
            : STATE.currentView === CONFIG.VIEWS.ANNUAL_SUMMARY
                ? 'Resumo Anual por Mês'
                : 'Receitas x Despesas';

        const chart = new ApexCharts(document.getElementById('chart0'), {
            chart: {
                type: 'bar',
                height: 380,
                toolbar: { show: false },
                background: 'transparent',
                fontFamily: 'Inter, Arial, sans-serif',
            },
            series: [
                { name: 'Receitas', data: receitas.map(Number) },
                { name: 'Despesas', data: despesas.map(Number) },
            ],
            xaxis: {
                categories: labels,
                labels: { style: { colors: theme.textMuted, fontSize: '11px' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                min: 0,
                labels: {
                    style: { colors: theme.isLight ? '#000' : '#fff', fontSize: '11px' },
                    formatter: (value) => formatCurrency(value),
                },
            },
            colors: [colorSuccess, colorDanger],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            grid: { borderColor: theme.gridColor, strokeDashArray: 4, xaxis: { lines: { show: false } } },
            tooltip: {
                theme: theme.mode,
                shared: true,
                intersect: false,
                y: { formatter: (v) => formatCurrency(v) },
            },
            legend: { position: 'bottom', labels: { colors: theme.textColor }, markers: { shape: 'circle' } },
            title: {
                text: chartTitle,
                align: 'center',
                style: { fontSize: '16px', fontWeight: 'bold', color: theme.textColor },
            },
            dataLabels: { enabled: false },
            theme: { mode: theme.mode },
        });
        chart.render();
        STATE.chart = chart;
    }
};

Modules.ChartManager = ChartManager;
