/**
 * ============================================================================
 * LUKRATO - Relatorios / Section Updaters
 * ============================================================================
 * Contains section-level orchestration extracted from app.js.
 * ============================================================================
 */

import { CONFIG, Utils, escapeHtml } from './state.js';
import {
    updateTrendBadge,
    renderCategoryComparison,
    renderEvolucao,
    renderEvolucaoChart,
    renderMediaDiaria,
    renderTaxaEconomia,
    renderFormasPagamento,
    renderComparative,
} from './app-renderers.js';

const formatCurrency = (v) => Utils.formatCurrency(v);

const INSIGHT_ICON_MAP = {
    'arrow-trend-up': 'trending-up',
    'arrow-trend-down': 'trending-down',
    'arrow-up': 'arrow-up',
    'arrow-down': 'arrow-down',
    'chart-line': 'line-chart',
    'chart-pie': 'pie-chart',
    'exclamation-triangle': 'triangle-alert',
    'exclamation-circle': 'circle-alert',
    'check-circle': 'circle-check',
    'info-circle': 'info',
    lightbulb: 'lightbulb',
    star: 'star',
    bolt: 'zap',
    wallet: 'wallet',
    'credit-card': 'credit-card',
    'calendar-check': 'calendar-check',
    calendar: 'calendar',
    crown: 'crown',
    trophy: 'trophy',
    leaf: 'leaf',
    'shield-alt': 'shield',
    'money-bill-wave': 'banknote',
    'trending-up': 'trending-up',
    'trending-down': 'trending-down',
    'shield-alert': 'shield-alert',
    gauge: 'gauge',
    target: 'target',
    clock: 'clock',
    receipt: 'receipt',
    calculator: 'calculator',
    layers: 'layers',
    'calendar-clock': 'calendar-clock',
    'pie-chart': 'pie-chart',
    'calendar-range': 'calendar-range',
    'list-plus': 'list-plus',
    'list-minus': 'list-minus',
    'file-text': 'file-text',
    'piggy-bank': 'piggy-bank',
    banknote: 'banknote',
};

let overviewCharts = [];

function destroyOverviewCharts() {
    overviewCharts.forEach((chart) => {
        try {
            chart.destroy();
        } catch {
            // ignore chart destroy errors
        }
    });
    overviewCharts = [];
}

function renderOverviewPulse(pulseEl, stats) {
    if (!pulseEl) return;

    const saldo = stats.saldo || 0;
    const saldoColor = saldo >= 0 ? 'var(--color-success)' : 'var(--color-danger)';
    const saldoStatus = saldo >= 0 ? 'positivo' : 'negativo';
    let pulseHTML = `
        <p class="pulse-text">
            Neste mês você recebeu <strong>${formatCurrency(stats.totalReceitas)}</strong>
            e gastou <strong>${formatCurrency(stats.totalDespesas)}</strong>.
            Seu saldo é <strong style="color:${saldoColor}">${saldoStatus} em ${formatCurrency(Math.abs(saldo))}</strong>.
    `;
    if (stats.totalCartoes > 0) {
        pulseHTML += ` Faturas de cartões somam <strong>${formatCurrency(stats.totalCartoes)}</strong>.`;
    }
    pulseHTML += '</p>';
    pulseEl.innerHTML = pulseHTML;
}

function renderOverviewInsights(insightsEl, insightsData) {
    if (!insightsEl) return;

    if (insightsData?.insights?.length > 0) {
        insightsEl.innerHTML = insightsData.insights
            .map((insight) => {
                const icon = INSIGHT_ICON_MAP[insight.icon] || insight.icon;
                return `
                <div class="insight-card insight-${insight.type} surface-card surface-card--interactive">
                    <div class="insight-icon"><i data-lucide="${icon}"></i></div>
                    <div class="insight-content">
                        <h4>${escapeHtml(insight.title)}</h4>
                        <p>${escapeHtml(insight.message)}</p>
                    </div>
                </div>`;
            })
            .join('');
        return;
    }

    insightsEl.innerHTML = '<p class="empty-message">Nenhum insight disponível no momento</p>';
}

function renderOverviewCategoryChart(catChartEl, categoryData) {
    if (!catChartEl) return;

    if (!categoryData?.labels?.length) {
        catChartEl.innerHTML = '<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de categorias</p>';
        return;
    }

    catChartEl.innerHTML = '';
    const topN = 5;
    const labels = categoryData.labels.slice(0, topN);
    const values = categoryData.values.slice(0, topN).map(Number);
    if (categoryData.labels.length > topN) {
        const otherSum = categoryData.values.slice(topN).reduce((sum, value) => sum + Number(value), 0);
        labels.push('Outros');
        values.push(otherSum);
    }

    const miniDonut = new ApexCharts(catChartEl, {
        chart: { type: 'donut', height: 220, background: 'transparent' },
        series: values,
        labels,
        colors: ['#E67E22', '#2C3E50', '#2ECC71', '#F39C12', '#9B59B6', '#1ABC9C'],
        legend: { position: 'bottom', fontSize: '11px', labels: { colors: 'var(--color-text-muted)' } },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '60%' } } },
        stroke: { show: false },
        tooltip: {
            y: { formatter: (v) => formatCurrency(v) },
        },
    });
    miniDonut.render();
    overviewCharts.push(miniDonut);
}

function renderOverviewComparisonChart(compChartEl, comparisonData) {
    if (!compChartEl) return;

    if (!comparisonData?.labels?.length) {
        compChartEl.innerHTML = '<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de movimentação</p>';
        return;
    }

    compChartEl.innerHTML = '';
    const rec = (comparisonData.receitas || []).map(Number);
    const desp = (comparisonData.despesas || []).map(Number);

    const weekLabels = [];
    const weekRec = [];
    const weekDesp = [];
    const chunkSize = 7;

    for (let i = 0; i < comparisonData.labels.length; i += chunkSize) {
        const weekNum = Math.floor(i / chunkSize) + 1;
        weekLabels.push(`Sem ${weekNum}`);
        weekRec.push(rec.slice(i, i + chunkSize).reduce((sum, value) => sum + value, 0));
        weekDesp.push(desp.slice(i, i + chunkSize).reduce((sum, value) => sum + value, 0));
    }

    const miniBar = new ApexCharts(compChartEl, {
        chart: { type: 'bar', height: 220, background: 'transparent', toolbar: { show: false } },
        series: [
            { name: 'Receitas', data: weekRec },
            { name: 'Despesas', data: weekDesp },
        ],
        colors: ['#2ECC71', '#E74C3C'],
        xaxis: {
            categories: weekLabels,
            labels: { style: { colors: 'var(--color-text-muted)', fontSize: '11px' } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                style: { fontSize: '10px' },
                formatter: (v) => formatCurrency(v),
            },
        },
        plotOptions: { bar: { columnWidth: '60%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '11px', labels: { colors: 'var(--color-text-muted)' } },
        grid: { borderColor: 'rgba(255,255,255,0.05)' },
        tooltip: {
            shared: true,
            intersect: false,
            y: { formatter: (v) => formatCurrency(v) },
        },
    });
    miniBar.render();
    overviewCharts.push(miniBar);
}

export function createSectionHandlers({ API }) {
    async function updateOverviewSection() {
        const pulseEl = document.getElementById('overviewPulse');
        const insightsEl = document.getElementById('overviewInsights');
        const catChartEl = document.getElementById('overviewCategoryChart');
        const compChartEl = document.getElementById('overviewComparisonChart');

        destroyOverviewCharts();

        const [stats, insightsData, categoryData, comparisonData] = await Promise.all([
            API.fetchSummaryStats(),
            API.fetchInsightsTeaser(),
            API.fetchReportDataForType('despesas_por_categoria', { accountId: null }),
            API.fetchReportDataForType('receitas_despesas_diario', { accountId: null }),
        ]);

        renderOverviewPulse(pulseEl, stats);
        renderOverviewInsights(insightsEl, insightsData);
        renderOverviewCategoryChart(catChartEl, categoryData);
        renderOverviewComparisonChart(compChartEl, comparisonData);

        if (window.lucide) lucide.createIcons();
    }

    async function updateInsightsSection() {
        const insightsContainer = document.getElementById('insightsContainer');
        if (!insightsContainer) return;

        const data = window.IS_PRO
            ? await API.fetchInsights()
            : await API.fetchInsightsTeaser();

        if (!data || !data.insights || data.insights.length === 0) {
            insightsContainer.innerHTML = '<p class="empty-message">Nenhum insight disponível no momento</p>';
            return;
        }

        const insightsHTML = data.insights
            .map((insight, index) => {
                const lucideIcon = INSIGHT_ICON_MAP[insight.icon] || insight.icon;
                return `
                <div class="insight-card insight-${insight.type} surface-card surface-card--interactive${index === 0 ? ' insight-card--featured' : ''}">
                    <div class="insight-icon">
                        <i data-lucide="${lucideIcon}"></i>
                    </div>
                    <div class="insight-content">
                        ${index === 0 ? '<span class="insight-priority-pill">Destaque do período</span>' : ''}
                        <h4>${escapeHtml(insight.title)}</h4>
                        <p>${escapeHtml(insight.message)}</p>
                    </div>
                </div>
            `;
            })
            .join('');

        insightsContainer.innerHTML = insightsHTML;

        if (!window.IS_PRO && data.isTeaser) {
            const remaining = Math.max(0, (data.totalCount || 0) - data.insights.length);
            const remainingLabel = remaining > 0
                ? `Desbloqueie mais ${remaining} insights com PRO`
                : 'Desbloqueie todos os insights com PRO';

            insightsContainer.insertAdjacentHTML(
                'beforeend',
                `
                <div class="insights-teaser-overlay">
                    <div class="teaser-blur-mask"></div>
                    <div class="teaser-cta">
                        <i data-lucide="crown"></i>
                        <h4>${remainingLabel}</h4>
                        <p>Tenha uma visão completa da sua saúde financeira com análises detalhadas.</p>
                        <a href="${CONFIG.BASE_URL}billing" class="btn-upgrade-cta surface-button surface-button--upgrade">
                            <i data-lucide="crown"></i> Fazer Upgrade
                        </a>
                    </div>
                </div>
            `,
            );
        }

        if (window.lucide) lucide.createIcons();
    }

    async function updateComparativesSection() {
        const comparativesContainer = document.getElementById('comparativesContainer');
        if (!comparativesContainer) return;

        const data = await API.fetchComparatives();
        if (!data) {
            comparativesContainer.innerHTML = '<p class="empty-message">Dados de comparação não disponíveis</p>';
            return;
        }

        const monthlyHTML = renderComparative('Comparativo Mensal', data.monthly, 'mês anterior', 'monthly');
        const yearlyHTML = renderComparative('Comparativo Anual', data.yearly, 'ano anterior', 'annual');
        const categoriesHTML = renderCategoryComparison(data.categories || []);
        const evolucaoHTML = renderEvolucao(data.evolucao || []);
        const mediaDiariaHTML = renderMediaDiaria(data.mediaDiaria);
        const taxaEconomiaHTML = renderTaxaEconomia(data.taxaEconomia);
        const formasHTML = renderFormasPagamento(data.formasPagamento || []);

        comparativesContainer.innerHTML =
            `<div class="comp-top-row">${monthlyHTML}${yearlyHTML}</div>` +
            `<div class="comp-duo-grid">${mediaDiariaHTML}${taxaEconomiaHTML}</div>` +
            categoriesHTML +
            evolucaoHTML +
            formasHTML;

        if (window.lucide) lucide.createIcons();
        renderEvolucaoChart(data.evolucao || []);
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

        updateTrendBadge('trendReceitas', stats.totalReceitas, stats.prevReceitas, false);
        updateTrendBadge('trendDespesas', stats.totalDespesas, stats.prevDespesas, true);
        updateTrendBadge('trendSaldo', stats.saldo, stats.prevSaldo, false);
        updateTrendBadge('trendCartoes', stats.totalCartoes, stats.prevCartoes, true);

        const overviewPanel = document.getElementById('section-overview');
        if (overviewPanel && overviewPanel.classList.contains('active')) {
            await updateOverviewSection();
        }

        const insightsPanel = document.getElementById('section-insights');
        if (insightsPanel && insightsPanel.classList.contains('active')) {
            await updateInsightsSection();
        }

        const comparativosPanel = document.getElementById('section-comparativos');
        if (comparativosPanel && comparativosPanel.classList.contains('active')) {
            await updateComparativesSection();
        }
    }

    return {
        updateSummaryCards,
        updateInsightsSection,
        updateOverviewSection,
        updateComparativesSection,
    };
}
