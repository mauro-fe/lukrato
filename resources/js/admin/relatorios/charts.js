/**
 * ============================================================================
 * LUKRATO — Relatórios / Charts
 * ============================================================================
 * Chart.js plugin, chart rendering (pie/doughnut, line, bar).
 * Registers ChartManager on Modules for cross-module access.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';

// Local aliases (keep method bodies identical to original)
const formatCurrency = (v) => Utils.formatCurrency(v);
const hexToRgba = (h, a) => Utils.hexToRgba(h, a);
const escapeHtml = (v) => String(v ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m] || m));

// ─── Doughnut Labels Plugin ──────────────────────────────────────────────────

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

        Chart.defaults.color = textColor;
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
    },

    renderPie(data) {
        const { labels = [], values = [], details = null, cat_ids = null } = data;

        if (!labels.length || !values.some(v => v > 0)) {
            return Modules.UI.showEmptyState();
        }

        // Preparar entradas com cores
        // Use cat_ids from API (same query as labels) for reliable mapping;
        // fall back to details label matching if cat_ids unavailable
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
        // Aplicar agrupamento "Top 5 + Outros" APENAS no mobile
        // Isso mantém o gráfico limpo e profissional em telas pequenas
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
                    color: '#95a5a6', // Cor neutra para "Outros"
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
                            <canvas id="chart${idx}"></canvas>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div id="subcategoryDrilldown" class="drilldown-panel" aria-hidden="true"></div>
            ${isMobile ? '<div id="categoryListMobile" class="category-list-mobile"></div>' : ''}
        `;

        Modules.UI.setContent(html);
        ChartManager.destroy();

        // Store details AFTER destroy() — destroy() clears STATE.reportDetails
        STATE.reportDetails = details;
        STATE.activeDrilldown = null;

        // Armazenar as entradas processadas para renderizar a lista mobile
        ChartManager._currentEntries = processedEntries;

        const type = Utils.getActiveCategoryType();
        const titleMap = {
            'receitas_por_categoria': 'Receitas por Categoria',
            'despesas_por_categoria': 'Despesas por Categoria',
            'receitas_anuais_por_categoria': 'Receitas anuais por Categoria',
            'despesas_anuais_por_categoria': 'Despesas anuais por Categoria'
        };
        const title = titleMap[type] || 'Distribuição por Categoria';

        // Track cumulative offset for multi-chunk indexing
        let chunkOffset = 0;

        STATE.chart = chunks.map((chunk, idx) => {
            const canvas = document.getElementById(`chart${idx}`);
            const isLightTheme = (document.documentElement.getAttribute('data-theme') || '').toLowerCase() === 'light'
                || Utils.isLightTheme();
            const labelColor = isLightTheme ? '#2c3e50' : '#ffffff';
            const chunkTotal = chunk.reduce((sum, item) => sum + item.value, 0);
            const currentChunkOffset = chunkOffset;
            chunkOffset += chunk.length;

            const chart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: chunk.map(e => e.label),
                    datasets: [{
                        data: chunk.map(e => e.value),
                        backgroundColor: chunk.map(e => e.color),
                        borderWidth: 2,
                        borderColor: getComputedStyle(document.documentElement)
                            .getPropertyValue('--color-surface').trim(),
                        offset: chunk.map(() => 0),
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    // ===================================================================
                    // CLICK HANDLER: Drill-down into subcategories (PRO)
                    // ===================================================================
                    onClick: (event, elements) => {
                        if (!elements.length) return;
                        const el = elements[0];
                        const globalIdx = currentChunkOffset + el.index;
                        const entry = processedEntries[globalIdx];
                        if (!entry || entry.isOthers) return;
                        ChartManager.handlePieClick(entry, globalIdx, el, idx);
                    },
                    // Cursor pointer on hoverable segments (visual cue for drill-down)
                    onHover: (event, elements) => {
                        const target = event.native?.target;
                        if (target) {
                            target.style.cursor = elements.length ? 'pointer' : 'default';
                        }
                    },
                    plugins: {
                        // ===================================================================
                        // MOBILE: Esconder legendas para visual limpo
                        // Desktop: Mostrar legendas na parte inferior
                        // ===================================================================
                        legend: {
                            display: !isMobile,
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: chunks.length > 1 ? `${title} - Parte ${idx + 1}` : title
                        },
                        // ===================================================================
                        // TOOLTIP PROFISSIONAL
                        // Exibe: Nome da categoria + Valor em R$ + Percentual
                        // ===================================================================
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: (context) => {
                                    const label = context.label || '';
                                    const value = formatCurrency(context.parsed);
                                    const percentage = ((context.parsed / chunkTotal) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        },
                        // ===================================================================
                        // PERCENTUAIS NO GRÁFICO
                        // Desativados em mobile e desktop para visual limpo
                        // ===================================================================
                        lkDoughnutLabels: false
                    }
                }
            });

            return chart;
        });

        // ===================================================================
        // RENDERIZAR LISTA DE CATEGORIAS (MOBILE ONLY)
        // Lista vertical profissional abaixo do gráfico
        // ===================================================================
        if (isMobile) {
            ChartManager.renderMobileCategoryList(processedEntries);
        }
    },

    /**
     * Renderiza a lista de categorias para mobile com expansão
     * UX: Visual clean - apenas botão + lista expansível
     * Inclui drill-down de subcategorias (PRO)
     */
    renderMobileCategoryList(entries) {
        const container = document.getElementById('categoryListMobile');
        if (!container) return;

        const total = entries.reduce((sum, item) => sum + item.value, 0);
        const hasDetails = !!STATE.reportDetails && window.IS_PRO;

        // Todas as categorias dentro do card expansível
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

        // HTML final: apenas botão + card expansível + texto informativo
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

        // Adicionar listener ao botão de expansão
        ChartManager.setupExpandToggle();

        // Setup mobile subcategory accordion toggles
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
                // Recolher
                card.style.maxHeight = '0px';
                card.setAttribute('aria-hidden', 'true');
                btn.setAttribute('aria-expanded', 'false');
                btn.querySelector('span').textContent = 'Ver todas as categorias';
                btn.querySelector('i').style.transform = 'rotate(0deg)';
            } else {
                // Expandir
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
    handlePieClick(entry, globalIdx, element, chartIdx) {
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

        // If ALL subcategories are just "Outros" (no real subcategoria assigned), show info toast
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

        // Highlight clicked segment (offset it)
        ChartManager._resetAllSegmentOffsets();
        if (Array.isArray(STATE.chart) && STATE.chart[chartIdx]) {
            const ds = STATE.chart[chartIdx].data.datasets[0];
            if (ds.offset) {
                ds.offset[element.index] = 14;
                STATE.chart[chartIdx].update('none');
            }
        }

        ChartManager.renderSubcategoryDrilldown(detail, entry.color);
    },

    /**
     * Reset offset on all chart segments
     */
    _resetAllSegmentOffsets() {
        if (!Array.isArray(STATE.chart)) return;
        STATE.chart.forEach(c => {
            if (!c) return;
            const ds = c.data.datasets[0];
            if (ds.offset) {
                ds.offset = ds.offset.map(() => 0);
                c.update('none');
            }
        });
    },

    /**
     * Close the drill-down panel
     */
    closeDrilldown() {
        STATE.activeDrilldown = null;
        ChartManager._resetAllSegmentOffsets();

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
                <canvas id="drilldownMiniChart"></canvas>
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
        const canvas = document.getElementById('drilldownMiniChart');
        if (!canvas) return;

        // Destroy any existing mini chart
        if (ChartManager._drilldownChart) {
            ChartManager._drilldownChart.destroy();
            ChartManager._drilldownChart = null;
        }

        ChartManager._drilldownChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: subcategories.map(s => s.label),
                datasets: [{
                    data: subcategories.map(s => s.total),
                    backgroundColor: shades,
                    borderWidth: 2,
                    borderColor: getComputedStyle(document.documentElement)
                        .getPropertyValue('--color-surface').trim()
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '55%',
                plugins: {
                    legend: { display: false },
                    title: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => {
                                const total = subcategories.reduce((s, sc) => s + sc.total, 0);
                                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : '0';
                                return `${ctx.label}: ${formatCurrency(ctx.parsed)} (${pct}%)`;
                            }
                        }
                    },
                    lkDoughnutLabels: false
                }
            }
        });
    },

    _drilldownChart: null,

    renderLine(data) {
        const { labels = [], values = [] } = data;

        if (!labels.length) return Modules.UI.showEmptyState();

        Modules.UI.setContent(`
            <div class="chart-container chart-container-line">
                <div class="chart-wrapper chart-wrapper-line">
                    <canvas id="chart0"></canvas>
                </div>
            </div>
        `);

        ChartManager.destroy();

        const color = getComputedStyle(document.documentElement)
            .getPropertyValue('--color-primary').trim();
        const isLightTheme = (document.documentElement.getAttribute('data-theme') || '').toLowerCase() === 'light'
            || Utils.isLightTheme();
        const yTickColor = isLightTheme ? '#000' : '#fff';

        STATE.chart = new Chart(document.getElementById('chart0'), {
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

        if (!labels.length) return Modules.UI.showEmptyState();

        Modules.UI.setContent(`
            <div class="chart-container chart-container-bar">
                <div class="chart-wrapper chart-wrapper-bar">
                    <canvas id="chart0"></canvas>
                </div>
            </div>
        `);

        ChartManager.destroy();

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

        STATE.chart = new Chart(document.getElementById('chart0'), {
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
                        text: STATE.currentView === CONFIG.VIEWS.ACCOUNTS
                            ? 'Receitas x Despesas por Conta'
                            : STATE.currentView === CONFIG.VIEWS.ANNUAL_SUMMARY
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

Modules.ChartManager = ChartManager;
