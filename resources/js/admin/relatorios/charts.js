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
            return Modules.UI.showEmptyState();
        }

        // Preparar entradas com cores
        let entries = labels
            .map((label, idx) => ({
                label,
                value: Number(values[idx]) || 0,
                color: CONFIG.CHART_COLORS[idx % CONFIG.CHART_COLORS.length]
            }))
            .filter(e => e.value > 0)
            .sort((a, b) => b.value - a.value);

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
            ${isMobile ? '<div id="categoryListMobile" class="category-list-mobile"></div>' : ''}
        `;

        Modules.UI.setContent(html);
        ChartManager.destroy();

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

        STATE.chart = chunks.map((chunk, idx) => {
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
     */
    renderMobileCategoryList(entries) {
        const container = document.getElementById('categoryListMobile');
        if (!container) return;

        const total = entries.reduce((sum, item) => sum + item.value, 0);

        // Todas as categorias dentro do card expansível
        const allCategoriesHTML = entries.map(entry => {
            const percentage = ((entry.value / total) * 100).toFixed(1);
            return `
                <div class="category-item">
                    <div class="category-indicator" style="background-color: ${entry.color}"></div>
                    <div class="category-info">
                        <span class="category-name">${entry.label}</span>
                        <span class="category-value">${formatCurrency(entry.value)}</span>
                    </div>
                    <span class="category-percentage">${percentage}%</span>
                </div>
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
            <p class="category-info-text">
                <i data-lucide="info"></i>
                Para visualizar todas as categorias detalhadamente, exporte este relatório em PDF.
            </p>
        `;
        if (window.lucide) lucide.createIcons();

        // Adicionar listener ao botão de expansão
        ChartManager.setupExpandToggle();
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
