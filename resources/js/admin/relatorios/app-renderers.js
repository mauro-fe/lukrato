/**
 * ============================================================================
 * LUKRATO - Relatorios / Renderers
 * ============================================================================
 * Shared rendering helpers extracted from app.js.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, escapeHtml, safeColor } from './state.js';

const formatCurrency = (v) => Utils.formatCurrency(v);

export function updateTrendBadge(elementId, current, previous, invertColors = false) {
    const el = document.getElementById(elementId);
    if (!el) return;

    if (!previous || previous === 0) {
        el.innerHTML = '';
        el.className = 'stat-trend';
        return;
    }

    const pctChange = ((current - previous) / Math.abs(previous)) * 100;
    const absChange = Math.abs(pctChange).toFixed(1);

    if (Math.abs(pctChange) < 0.5) {
        el.className = 'stat-trend trend-neutral';
        el.textContent = '— Sem alteração';
    } else {
        const isUp = pctChange > 0;
        const isPositive = invertColors ? !isUp : isUp;
        el.className = `stat-trend ${isPositive ? 'trend-positive' : 'trend-negative'}`;
        const arrow = isUp ? '↑' : '↓';
        el.textContent = `${arrow} ${absChange}% vs mês anterior`;
    }
}

// ─── Chart Insight Annotation ────────────────────────────────────────────────

export function renderChartInsight(data) {
    const existing = document.querySelector('.chart-insight-line');
    if (existing) existing.remove();

    if (!data) return;

    let insightText = '';
    const view = STATE.currentView;

    switch (view) {
        case CONFIG.VIEWS.CATEGORY:
        case CONFIG.VIEWS.ANNUAL_CATEGORY: {
            if (!data.labels || !data.values || data.values.length === 0) break;
            const total = data.values.reduce((s, v) => s + Number(v), 0);
            if (total > 0) {
                const maxIdx = data.values.reduce((mi, v, i, a) => Number(v) > Number(a[mi]) ? i : mi, 0);
                const pct = ((Number(data.values[maxIdx]) / total) * 100).toFixed(0);
                insightText = `${data.labels[maxIdx]} lidera com ${pct}% dos gastos (${formatCurrency(data.values[maxIdx])})`;
            }
            break;
        }
        case CONFIG.VIEWS.BALANCE: {
            if (!data.labels || !data.values || data.values.length === 0) break;
            const vals = data.values.map(Number);
            const minVal = Math.min(...vals);
            const minIdx = vals.indexOf(minVal);
            insightText = `Menor saldo: ${formatCurrency(minVal)} em ${data.labels[minIdx]}`;
            break;
        }
        case CONFIG.VIEWS.COMPARISON: {
            if (!data.receitas || !data.despesas) break;
            const rec = data.receitas.map(Number);
            const desp = data.despesas.map(Number);
            const goodDays = rec.filter((r, i) => r > (desp[i] || 0)).length;
            insightText = `Em ${goodDays} de ${rec.length} dias, receitas superaram despesas`;
            break;
        }
        case CONFIG.VIEWS.ACCOUNTS: {
            if (!data.labels || !data.despesas || data.despesas.length === 0) break;
            const desp = data.despesas.map(Number);
            const maxIdx = desp.reduce((mi, v, i, a) => v > a[mi] ? i : mi, 0);
            insightText = `Maior gasto: ${data.labels[maxIdx]} com ${formatCurrency(desp[maxIdx])} em despesas`;
            break;
        }
        case CONFIG.VIEWS.EVOLUTION: {
            if (!data.values || data.values.length < 2) break;
            const vals = data.values.map(Number);
            const first = vals[0];
            const last = vals[vals.length - 1];
            const direction = last > first ? 'tendência de alta' : last < first ? 'tendência de queda' : 'estável';
            insightText = `Evolução nos últimos 12 meses: ${direction}`;
            break;
        }
        case CONFIG.VIEWS.ANNUAL_SUMMARY: {
            if (!data.labels || !data.receitas || data.receitas.length === 0) break;
            const rec = data.receitas.map(Number);
            const desp = data.despesas.map(Number);
            const saldos = rec.map((r, i) => r - (desp[i] || 0));
            const bestIdx = saldos.reduce((mi, v, i, a) => v > a[mi] ? i : mi, 0);
            const worstIdx = saldos.reduce((mi, v, i, a) => v < a[mi] ? i : mi, 0);
            insightText = `Melhor mês: ${data.labels[bestIdx]}. Pior mês: ${data.labels[worstIdx]}`;
            break;
        }
    }

    if (!insightText) return;

    const reportArea = document.getElementById('reportArea');
    if (!reportArea) return;

    const div = document.createElement('div');
    div.className = 'chart-insight-line';
    div.innerHTML = `<i data-lucide="sparkles"></i> <span>${escapeHtml(insightText)}</span>`;
    reportArea.appendChild(div);

    if (window.lucide) lucide.createIcons();
}

export function renderCategoryComparison(categories) {
    if (!categories || categories.length === 0) return '';

    const rows = categories.map((cat, i) => {
        const varClass = cat.variacao > 0 ? 'trend-negative' : cat.variacao < 0 ? 'trend-positive' : 'trend-neutral';
        const varIcon = cat.variacao > 0 ? 'arrow-up' : cat.variacao < 0 ? 'arrow-down' : 'equal';
        const varText = Math.abs(cat.variacao) < 0.1 ? 'Sem alteração' : `${cat.variacao > 0 ? '+' : ''}${cat.variacao.toFixed(1)}%`;
        const total = categories.reduce((s, c) => s + c.atual, 0);
        const pct = total > 0 ? (cat.atual / total * 100).toFixed(0) : 0;

        // Subcategory pills (PRO)
        let subcatPills = '';
        if (cat.subcategorias && cat.subcategorias.length > 0) {
            const pills = cat.subcategorias.map(sub => {
                const subVarClass = sub.variacao > 0 ? 'trend-negative' : sub.variacao < 0 ? 'trend-positive' : '';
                const subVarText = Math.abs(sub.variacao) < 0.1
                    ? ''
                    : `<span class="subcat-trend ${subVarClass}">${sub.variacao > 0 ? '↑' : '↓'}${Math.abs(sub.variacao).toFixed(0)}%</span>`;
                return `
                    <span class="cat-comp-subcat-pill">
                        ${escapeHtml(sub.nome)}
                        <span class="subcat-value">${formatCurrency(sub.atual)}</span>
                        ${subVarText}
                    </span>
                `;
            }).join('');
            subcatPills = `<div class="cat-comp-subcats">${pills}</div>`;
        }

        return `
            <div class="cat-comp-row" style="animation-delay: ${i * 0.06}s">
                <div class="cat-comp-rank">${i + 1}</div>
                <div class="cat-comp-info">
                    <span class="cat-comp-name">${escapeHtml(cat.nome)}</span>
                    <div class="cat-comp-bar-bg">
                        <div class="cat-comp-bar" style="width: ${pct}%"></div>
                    </div>
                    ${subcatPills}
                </div>
                <div class="cat-comp-values">
                    <span class="cat-comp-current">${formatCurrency(cat.atual)}</span>
                    <span class="cat-comp-prev">${formatCurrency(cat.anterior)}</span>
                </div>
                <div class="cat-comp-trend ${varClass}">
                    <i data-lucide="${varIcon}"></i>
                    <span>${varText}</span>
                </div>
            </div>
        `;
    }).join('');

    return `
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="bar-chart-3"></i> Top Categorias de Despesa</h3>
                <span class="comp-subtitle">Mês atual vs anterior</span>
            </div>
            <div class="cat-comp-list">
                <div class="cat-comp-header-row">
                    <span></span><span></span>
                    <span class="cat-comp-col-label">Atual / Anterior</span>
                    <span class="cat-comp-col-label">Variação</span>
                </div>
                ${rows}
            </div>
        </div>
    `;
}

// ── Evolução últimos 6 meses ────────────────────────────
export function renderEvolucao(evolucao) {
    if (!evolucao || evolucao.length === 0) return '';

    return `
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="line-chart"></i> Evolução dos Últimos 6 Meses</h3>
                <span class="comp-subtitle">Receitas, despesas e saldo ao longo do tempo</span>
            </div>
            <div class="evolucao-chart-wrapper">
                <div id="evolucaoMiniChart" style="min-height:220px;"></div>
            </div>
        </div>
    `;
}

let _evolucaoChartInstance = null;

export function renderEvolucaoChart(evolucao) {
    if (!evolucao || evolucao.length === 0) return;
    const el = document.getElementById('evolucaoMiniChart');
    if (!el) return;

    const labels = evolucao.map(e => e.label);
    const style = getComputedStyle(document.documentElement);
    const textColor = style.getPropertyValue('--color-text-muted').trim() || '#999';
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const themeMode = isDark ? 'dark' : 'light';

    if (_evolucaoChartInstance) { _evolucaoChartInstance.destroy(); _evolucaoChartInstance = null; }

    _evolucaoChartInstance = new ApexCharts(el, {
        chart: {
            type: 'line',
            height: 260,
            stacked: false,
            toolbar: { show: false },
            background: 'transparent',
            fontFamily: 'Inter, Arial, sans-serif',
        },
        series: [
            { name: 'Receitas', type: 'column', data: evolucao.map(e => e.receitas) },
            { name: 'Despesas', type: 'column', data: evolucao.map(e => e.despesas) },
            { name: 'Saldo', type: 'area', data: evolucao.map(e => e.saldo) },
        ],
        xaxis: {
            categories: labels,
            labels: { style: { colors: textColor } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                style: { colors: textColor },
                formatter: (v) => formatCurrency(v),
            },
        },
        colors: ['rgba(46, 204, 113, 0.85)', 'rgba(231, 76, 60, 0.85)', '#3498db'],
        stroke: { width: [0, 0, 2.5], curve: 'smooth' },
        fill: { opacity: [0.85, 0.85, 0.1] },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        grid: { borderColor: 'rgba(128,128,128,0.1)', strokeDashArray: 4, xaxis: { lines: { show: false } } },
        tooltip: {
            theme: themeMode,
            shared: true,
            intersect: false,
            y: { formatter: (v) => formatCurrency(v) },
        },
        legend: { position: 'bottom', labels: { colors: textColor }, markers: { shape: 'circle' } },
        dataLabels: { enabled: false },
        theme: { mode: themeMode },
    });
    _evolucaoChartInstance.render();
}

// ── Média diária ────────────────────────────────────────
export function renderMediaDiaria(data) {
    if (!data) return '';
    const varClass = data.variacao > 0 ? 'trend-negative' : data.variacao < 0 ? 'trend-positive' : 'trend-neutral';
    const varIcon = data.variacao > 0 ? 'arrow-up' : data.variacao < 0 ? 'arrow-down' : 'equal';

    return `
        <div class="comparative-card comp-mini-card surface-card surface-card--interactive">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i data-lucide="calendar-clock"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Média Diária de Gastos</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${formatCurrency(data.atual)}/dia</span>
                    <span class="comp-mini-prev">anterior: ${formatCurrency(data.anterior)}/dia</span>
                </div>
                <div class="comp-mini-trend ${varClass}">
                    <i data-lucide="${varIcon}"></i>
                    <span>${Math.abs(data.variacao).toFixed(1)}%</span>
                </div>
            </div>
        </div>
    `;
}

// ── Taxa de economia ────────────────────────────────────
export function renderTaxaEconomia(data) {
    if (!data) return '';
    const isPositive = data.atual >= 0;
    const diffClass = data.diferenca > 0 ? 'trend-positive' : data.diferenca < 0 ? 'trend-negative' : 'trend-neutral';
    const diffIcon = data.diferenca > 0 ? 'arrow-up' : data.diferenca < 0 ? 'arrow-down' : 'equal';
    const gradientColor = isPositive ? '#2ecc71, #27ae60' : '#e74c3c, #c0392b';

    return `
        <div class="comparative-card comp-mini-card surface-card surface-card--interactive">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, ${gradientColor});">
                <i data-lucide="piggy-bank" style= "color: white"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Taxa de Economia</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${data.atual.toFixed(1)}%</span>
                    <span class="comp-mini-prev">anterior: ${data.anterior.toFixed(1)}%</span>
                </div>
                <div class="comp-mini-trend ${diffClass}">
                    <i data-lucide="${diffIcon}"></i>
                    <span>${data.diferenca > 0 ? '+' : ''}${data.diferenca.toFixed(1)}pp</span>
                </div>
            </div>
        </div>
    `;
}

// ── Formas de pagamento ──────────────────────────────────
export function renderFormasPagamento(formas) {
    if (!formas || formas.length === 0) return '';

    const iconMap = {
        'Pix': 'zap',
        'Cartão de Crédito': 'credit-card',
        'Cartão de Débito': 'credit-card',
        'Dinheiro': 'banknote',
        'Boleto': 'file-text',
        'Depósito': 'landmark',
        'Transferência': 'arrow-right-left',
        'Estorno': 'undo-2',
    };

    const totalAtual = formas.reduce((s, f) => s + f.atual, 0);

    const rows = formas.map((f, i) => {
        const pct = totalAtual > 0 ? (f.atual / totalAtual * 100).toFixed(0) : 0;
        const icon = iconMap[f.nome] || 'wallet';

        return `
            <div class="forma-comp-row" style="animation-delay: ${i * 0.06}s">
                <div class="forma-comp-icon"><i data-lucide="${icon}"></i></div>
                <div class="forma-comp-info">
                    <span class="forma-comp-name">${escapeHtml(f.nome)}</span>
                    <div class="forma-comp-bar-bg">
                        <div class="forma-comp-bar" style="width: ${pct}%"></div>
                    </div>
                </div>
                <div class="forma-comp-values">
                    <span class="forma-comp-current">${formatCurrency(f.atual)} <small>(${f.atual_qtd}x)</small></span>
                    <span class="forma-comp-prev">${formatCurrency(f.anterior)} <small>(${f.anterior_qtd}x)</small></span>
                </div>
            </div>
        `;
    }).join('');

    return `
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="wallet"></i> Formas de Pagamento</h3>
                <span class="comp-subtitle">Distribuição mês atual vs anterior</span>
            </div>
            <div class="forma-comp-list">
                ${rows}
            </div>
        </div>
    `;
}

export function renderComparative(title, data, period) {
    const getTrendIcon = (value, isDespesa = false) => {
        if (value > 0) return '<i data-lucide="arrow-up"></i>';
        if (value < 0) return '<i data-lucide="arrow-down"></i>';
        return '<i data-lucide="equal"></i>';
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

        if (value > 0) return `Aumentou ${Math.abs(value).toFixed(1)}%`;
        if (value < 0) return `Reduziu ${Math.abs(value).toFixed(1)}%`;

        return 'Sem alteração';
    };

    const getCurrentPeriod = () => {
        if (period.includes('mês')) {
            const [year, month] = STATE.currentMonth.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
        } else {
            return STATE.currentMonth.split('-')[0];
        }
    };

    const getPreviousPeriod = () => {
        if (period.includes('mês')) {
            const [year, month] = STATE.currentMonth.split('-');
            const date = new Date(year, month - 2);
            return date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
        } else {
            return (parseInt(STATE.currentMonth.split('-')[0]) - 1).toString();
        }
    };

    return `
        <div class="comparative-card surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3>${escapeHtml(title)}</h3>
                <div class="period-labels">
                    <span class="period-current"><i data-lucide="calendar" style="color: white;"></i> ${getCurrentPeriod()}</span>
                    <span class="period-separator">vs</span>
                    <span class="period-previous">${getPreviousPeriod()}</span>
                </div>
            </div>
            
            <div class="comparative-grid-new">
                <div class="comparative-item-new">
                    <div class="item-header">
                        <i data-lucide="trending-up" class="item-icon revenue"></i>
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
                        <i data-lucide="trending-down" class="item-icon expense"></i>
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
                        <i data-lucide="wallet" class="item-icon balance"></i>
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

export function renderCardsReport(data) {
    const reportArea = document.getElementById('reportArea');
    if (!reportArea) return;

    const resumoHTML = (data.resumo_consolidado && data.cards && data.cards.length > 0) ? `
        <div class="consolidated-summary">
            <div class="summary-header">
                <div class="summary-icon">
                    <i data-lucide="credit-card" style="color: white"></i>
                </div>
                <div class="summary-title">
                    <h3>Visão Geral dos Cartões</h3>
                    <p>Resumo consolidado de todos os seus cartões de crédito</p>
                </div>
            </div>
            
            <div class="summary-grid">
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i data-lucide="file-text" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total em Faturas</span>
                        <span class="stat-value">${formatCurrency(data.resumo_consolidado.total_faturas)}</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i data-lucide="wallet" style="color: white"></i>
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
                        <i data-lucide="pie-chart" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Utilização Geral</span>
                        <span class="stat-value">${data.resumo_consolidado.utilizacao_geral.toFixed(1)}%</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                        <i data-lucide="banknote" style="color: white"></i>
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
                            <i data-lucide="star"></i>
                            <span><strong>Melhor cartão:</strong> ${escapeHtml(data.resumo_consolidado.melhor_cartao.nome)} (${data.resumo_consolidado.melhor_cartao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    ` : ''}
                    ${data.resumo_consolidado.requer_atencao ? `
                        <div class="insight-item warning">
                            <i data-lucide="triangle-alert"></i>
                            <span><strong>Requer atenção:</strong> ${escapeHtml(data.resumo_consolidado.requer_atencao.nome)} (${data.resumo_consolidado.requer_atencao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    ` : ''}
                    ${data.resumo_consolidado.total_parcelamentos > 0 ? `
                        <div class="insight-item info">
                            <i data-lucide="calendar-check"></i>
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
                ${data.cards && data.cards.length > 0 ? data.cards.map(card => {
        const cardColor = safeColor(card.cor, '#E67E22');
        return `
                    <div class="card-item surface-card surface-card--interactive surface-card--clip ${card.status_saude.status}"
                         style="--card-color: ${cardColor}; cursor: pointer;"
                         data-card-id="${card.id || ''}"
                         data-card-nome="${escapeHtml(card.nome)}"
                         data-card-cor="${cardColor}"
                         data-card-month="${STATE.currentMonth}"
                         data-action="open-card-detail"
                         role="button"
                         tabindex="0">
                        <div class="card-header-gradient">
                            <div class="card-brand">
                                <div class="card-icon-wrapper" style="background: linear-gradient(135deg, ${cardColor}, ${cardColor}99);">
                                    <i data-lucide="credit-card" style="color: white"></i>
                                </div>
                                <div class="card-info">
                                    <h3 class="card-name">${escapeHtml(card.nome)}</h3>
                                    <div class="card-meta">
                                        ${card.conta ? `<span class="card-account"><i data-lucide="landmark"></i> ${escapeHtml(card.conta)}</span>` : ''}
                                        ${card.dia_vencimento ? `<span class="card-due"><i data-lucide="calendar"></i> Vence dia ${card.dia_vencimento}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                            ${card.status_saude && (card.status_saude.status === 'critico' || card.status_saude.status === 'alto_uso') ? `
                                <div class="health-indicator ${card.status_saude.status}">
                                    <i data-lucide="triangle-alert"></i>
                                </div>
                            ` : ''}
                        </div>

                        ${card.historico_6_meses && card.historico_6_meses.length > 0 ? `
                            <div class="card-trend-compact">
                                <span class="trend-label">ÚLTIMOS 6 MESES</span>
                                <span class="trend-indicator ${card.tendencia}">
                                    ${card.tendencia === 'subindo' ? '↗' : card.tendencia === 'caindo' ? '↘' : '→'} ${card.tendencia === 'subindo' ? 'Em alta' : card.tendencia === 'caindo' ? 'Em queda' : 'Estável'}
                                </span>
                            </div>
                        ` : ''}

                        ${card.alertas && card.alertas.length > 0 ? `
                            <div class="card-alerts">
                                ${card.alertas.map(alert => `
                                    <span class="alert-badge alert-${alert.type}">
                                        <i data-lucide="${alert.type === 'danger' ? 'triangle-alert' : alert.type === 'warning' ? 'circle-alert' : 'info'}"></i>
                                        ${escapeHtml(alert.message)}
                                    </span>
                                `).join('')}
                            </div>
                        ` : ''}


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

                        ${card.parcelamentos && card.parcelamentos.ativos > 0 || (card.proximos_meses && card.proximos_meses.length > 0 && card.proximos_meses.some(m => m.valor > 0)) ? `
                            <div class="card-quick-info">
                                ${card.parcelamentos && card.parcelamentos.ativos > 0 ? `
                                    <div class="quick-info-item">
                                        <i data-lucide="calendar-check"></i>
                                        <span>${card.parcelamentos.ativos} parcelamento${card.parcelamentos.ativos > 1 ? 's' : ''}</span>
                                    </div>
                                ` : ''}
                                ${card.proximos_meses && card.proximos_meses.length > 0 && card.proximos_meses.some(m => m.valor > 0) ? `
                                    <div class="quick-info-item">
                                        <i data-lucide="line-chart"></i>
                                        <span>Próximo: ${formatCurrency(card.proximos_meses.find(m => m.valor > 0)?.valor || 0)}</span>
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                        
                        <div class="card-footer">
                            <button class="card-action-btn primary full-width" data-action="open-card-detail" data-card-id="${card.id || ''}" data-card-nome="${escapeHtml(card.nome)}" data-card-cor="${cardColor}" data-card-month="${STATE.currentMonth}" title="Ver relatório detalhado">
                                <i data-lucide="eye"></i>
                                <span>Ver Detalhes</span>
                            </button>
                        </div>
                    </div>
                `;
    }).join('') : `
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i data-lucide="credit-card"></i>
                        </div>
                        <h3>Nenhum cartão de crédito cadastrado</h3>
                        <p>Cadastre seus cartões de crédito para visualizar relatórios detalhados de gastos e parcelamentos.</p>
                    </div>
                `}
            </div>
        </div>
    `;
    if (window.lucide) lucide.createIcons();
}
