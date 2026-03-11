/**
 * ============================================================================
 * LUKRATO — Relatórios / App
 * ============================================================================
 * Main application logic: API layer, UI helpers, rendering functions,
 * data export, and event handlers.
 * ============================================================================
 */

import {
    CONFIG, STATE, Utils, Modules,
    PAYWALL_MESSAGE, TYPE_OPTIONS, YEARLY_VIEWS,
    escapeHtml, computeInitialMonth, safeColor, getChartColor
} from './state.js';
import { ChartManager } from './charts.js';

// ─── Local Aliases (keep method bodies identical to original) ────────────────

const formatCurrency = (v) => Utils.formatCurrency(v);
const formatMonthLabel = (m) => Utils.formatMonthLabel(m);
const hexToRgba = (h, a) => Utils.hexToRgba(h, a);
const isYearlyView = (v) => Utils.isYearlyView(v);
const getReportType = () => Utils.getReportType();
const getActiveCategoryType = () => Utils.getActiveCategoryType();

// ─── Billing / Paywall Helpers ───────────────────────────────────────────────

function goToBilling() {
    if (typeof window.openBillingModal === 'function') {
        window.openBillingModal();
    } else {
        location.href = `${CONFIG.BASE_URL}billing`;
    }
}

async function showRestrictionAlert(message) {
    const text = message || PAYWALL_MESSAGE;
    if (window.Swal?.fire) {
        const result = await Swal.fire({
            title: 'Recurso exclusivo',
            text: text,
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

        if (!STATE.accessRestricted) {
            STATE.accessRestricted = true;
            await showRestrictionAlert(message);
        }

        UI.showPaywall(message);
        return true;
    }

    return false;
}

function showErrorToast(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: message,
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
    }
}

// ─── API ─────────────────────────────────────────────────────────────────────

export const API = {
    async fetchReportData() {
        const params = new URLSearchParams({
            type: Utils.getReportType(),
            year: STATE.currentMonth.split('-')[0],
            month: STATE.currentMonth.split('-')[1]
        });

        if (STATE.currentAccount) {
            params.set('account_id', STATE.currentAccount);
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.FETCH_TIMEOUT);

        try {
            const response = await fetch(`${CONFIG.BASE_URL}api/reports?${params}`, {
                credentials: 'include',
                headers: { 'Accept': 'application/json' },
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (await handleRestrictedAccess(response)) {
                return null;
            }

            if (!response.ok) throw new Error('API request failed');

            STATE.accessRestricted = false;

            const json = await response.json();
            return json.data || json;
        } catch (error) {
            clearTimeout(timeoutId);
            console.error('Error fetching report data:', error);
            showErrorToast(error.name === 'AbortError'
                ? 'A requisição demorou demais. Tente novamente.'
                : 'Erro ao carregar relatório. Verifique sua conexão.');
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

            STATE.accessRestricted = false;

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
        const [year, month] = STATE.currentMonth.split('-');
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.FETCH_TIMEOUT);
        try {
            const response = await fetch(
                `${CONFIG.BASE_URL}api/reports/summary?year=${year}&month=${month}`,
                {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal
                }
            );

            clearTimeout(timeoutId);

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
            clearTimeout(timeoutId);
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
        const [year, month] = STATE.currentMonth.split('-');
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.FETCH_TIMEOUT);
        try {
            const response = await fetch(
                `${CONFIG.BASE_URL}api/reports/insights?year=${year}&month=${month}`,
                {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal
                }
            );

            clearTimeout(timeoutId);
            if (await handleRestrictedAccess(response)) return { insights: [] };
            if (!response.ok) throw new Error('Failed to fetch insights');

            const json = await response.json();
            return json.data || json;
        } catch (error) {
            clearTimeout(timeoutId);
            console.error('Error fetching insights:', error);
            return { insights: [] };
        }
    },

    async fetchComparatives() {
        const [year, month] = STATE.currentMonth.split('-');
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.FETCH_TIMEOUT);
        try {
            const response = await fetch(
                `${CONFIG.BASE_URL}api/reports/comparatives?year=${year}&month=${month}`,
                {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal
                }
            );

            clearTimeout(timeoutId);
            if (await handleRestrictedAccess(response)) return null;
            if (!response.ok) throw new Error('Failed to fetch comparatives');

            const json = await response.json();
            return json.data || json;
        } catch (error) {
            clearTimeout(timeoutId);
            console.error('Error fetching comparatives:', error);
            return null;
        }
    }
};

Modules.API = API;

// ─── UI ──────────────────────────────────────────────────────────────────────

export const UI = {
    setContent(html) {
        const area = document.getElementById('reportArea');
        if (area) {
            area.innerHTML = html;
            area.setAttribute('aria-busy', 'false');
            if (window.lucide) lucide.createIcons();
        }
    },

    showLoading() {
        const area = document.getElementById('reportArea');
        if (area) {
            area.setAttribute('aria-busy', 'true');
            area.innerHTML = `
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando relatório...</p>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
        }
    },

    showEmptyState() {
        UI.setContent(`
            <div class="empty-state">
                <i data-lucide="pie-chart"></i>
                <h3>Nenhum dado encontrado</h3>
                <p>Não há lançamentos registrados para o período selecionado. Adicione receitas ou despesas para visualizar seus relatórios.</p>
                <a href="${CONFIG.BASE_URL}lancamentos" class="empty-cta">
                    <i data-lucide="plus"></i>
                    <span>Adicionar lançamento</span>
                </a>
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
                <i data-lucide="crown" aria-hidden="true"></i>
                <h3>Recurso Premium</h3>
                <p>${safeMessage}</p>
                <button type="button" class="btn-upgrade" data-action="go-pro">
                    Fazer Upgrade para PRO
                </button>
            </div>
        `;
        if (window.lucide) lucide.createIcons();

        const cta = area.querySelector('[data-action="go-pro"]');
        if (cta) {
            cta.addEventListener('click', goToBilling);
        }
    },

    updateMonthLabel() {
        const labelEl = document.getElementById('monthLabel');
        if (labelEl) {
            labelEl.textContent = isYearlyView()
                ? STATE.currentMonth.split('-')[0]
                : formatMonthLabel(STATE.currentMonth);
        }
    },

    updateControls() {
        const typeWrapper = document.getElementById('typeSelectWrapper');
        const showTypeSelect = [CONFIG.VIEWS.CATEGORY, CONFIG.VIEWS.ANNUAL_CATEGORY]
            .includes(STATE.currentView);

        if (typeWrapper) {
            typeWrapper.classList.toggle('hidden', !showTypeSelect);
            if (showTypeSelect) {
                UI.syncTypeSelect();
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

        const options = TYPE_OPTIONS[STATE.currentView];
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

Modules.UI = UI;

// ─── Rendering Aliases ───────────────────────────────────────────────────────

const fetchReportData = () => API.fetchReportData();
const showLoading = () => UI.showLoading();
const showEmptyState = () => UI.showEmptyState();
const showPaywall = (m) => UI.showPaywall(m);
const updateMonthLabel = () => UI.updateMonthLabel();
const updateControls = () => UI.updateControls();
const setActiveTab = (v) => UI.setActiveTab(v);
const renderPieChart = (d) => ChartManager.renderPie(d);
const renderLineChart = (d) => ChartManager.renderLine(d);
const renderBarChart = (d) => ChartManager.renderBar(d);

// ─── Rendering ───────────────────────────────────────────────────────────────

export async function renderReport() {
    showLoading();

    // Atualizar cards de resumo
    updateSummaryCards();

    const data = await fetchReportData();

    if (STATE.accessRestricted) {
        return;
    }

    // Validação específica para cada tipo de relatório
    if (STATE.currentView === CONFIG.VIEWS.CARDS) {
        renderCardsReport(data);
        return;
    }

    if (!data || !data.labels || data.labels.length === 0) {
        return showEmptyState();
    }

    switch (STATE.currentView) {
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

    // Atualizar insights e comparativos apenas se a seção estiver visível
    const insightsPanel = document.getElementById('section-insights');
    if (insightsPanel && insightsPanel.classList.contains('active')) {
        await updateInsightsSection();
    }

    const comparativosPanel = document.getElementById('section-comparativos');
    if (comparativosPanel && comparativosPanel.classList.contains('active')) {
        await updateComparativesSection();
    }
}

async function updateInsightsSection() {
    const insightsContainer = document.getElementById('insightsContainer');
    if (!insightsContainer) return;

    const data = await API.fetchInsights();
    if (!data || !data.insights || data.insights.length === 0) {
        insightsContainer.innerHTML = '<p class="empty-message">Nenhum insight disponível no momento</p>';
        return;
    }

    const faToLucide = {
        'arrow-trend-up': 'trending-up', 'arrow-trend-down': 'trending-down',
        'arrow-up': 'arrow-up', 'arrow-down': 'arrow-down',
        'chart-line': 'line-chart', 'chart-pie': 'pie-chart',
        'exclamation-triangle': 'triangle-alert', 'exclamation-circle': 'circle-alert',
        'check-circle': 'circle-check', 'info-circle': 'info',
        'lightbulb': 'lightbulb', 'star': 'star', 'bolt': 'zap',
        'wallet': 'wallet', 'credit-card': 'credit-card',
        'calendar-check': 'calendar-check', 'calendar': 'calendar',
        'crown': 'crown', 'trophy': 'trophy', 'leaf': 'leaf',
        'shield-alt': 'shield', 'money-bill-wave': 'banknote',
        // Novos ícones (já Lucide nativos)
        'trending-up': 'trending-up', 'trending-down': 'trending-down',
        'shield-alert': 'shield-alert', 'gauge': 'gauge',
        'target': 'target', 'clock': 'clock',
        'receipt': 'receipt', 'calculator': 'calculator',
        'layers': 'layers', 'calendar-clock': 'calendar-clock',
        'pie-chart': 'pie-chart', 'calendar-range': 'calendar-range',
        'list-plus': 'list-plus', 'list-minus': 'list-minus',
        'file-text': 'file-text', 'piggy-bank': 'piggy-bank',
        'banknote': 'banknote'
    };
    const insightsHTML = data.insights.map(insight => {
        const lucideIcon = faToLucide[insight.icon] || insight.icon;
        return `
        <div class="insight-card insight-${insight.type}">
            <div class="insight-icon">
                <i data-lucide="${lucideIcon}"></i>
            </div>
            <div class="insight-content">
                <h4>${escapeHtml(insight.title)}</h4>
                <p>${escapeHtml(insight.message)}</p>
            </div>
        </div>
    `;
    }).join('');

    insightsContainer.innerHTML = insightsHTML;
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

    const monthlyHTML = renderComparative('Comparativo Mensal', data.monthly, 'mês anterior');
    const yearlyHTML = renderComparative('Comparativo Anual', data.yearly, 'ano anterior');
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

    // Mini chart for evolução
    renderEvolucaoChart(data.evolucao || []);
}

// ── Comparativo de categorias ───────────────────────────
function renderCategoryComparison(categories) {
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
        <div class="comparative-card comp-full-width">
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
function renderEvolucao(evolucao) {
    if (!evolucao || evolucao.length === 0) return '';

    return `
        <div class="comparative-card comp-full-width">
            <div class="comparative-header">
                <h3><i data-lucide="line-chart"></i> Evolução dos Últimos 6 Meses</h3>
                <span class="comp-subtitle">Receitas, despesas e saldo ao longo do tempo</span>
            </div>
            <div class="evolucao-chart-wrapper">
                <canvas id="evolucaoMiniChart" height="220"></canvas>
            </div>
        </div>
    `;
}

function renderEvolucaoChart(evolucao) {
    if (!evolucao || evolucao.length === 0) return;
    const canvas = document.getElementById('evolucaoMiniChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const labels = evolucao.map(e => e.label);
    const style = getComputedStyle(document.documentElement);
    const textColor = style.getPropertyValue('--color-text-muted').trim() || '#999';

    if (canvas._chartInstance) canvas._chartInstance.destroy();

    canvas._chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Receitas',
                    data: evolucao.map(e => e.receitas),
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderRadius: 6,
                    barPercentage: 0.6,
                },
                {
                    label: 'Despesas',
                    data: evolucao.map(e => e.despesas),
                    backgroundColor: 'rgba(231, 76, 60, 0.7)',
                    borderRadius: 6,
                    barPercentage: 0.6,
                },
                {
                    label: 'Saldo',
                    type: 'line',
                    data: evolucao.map(e => e.saldo),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#3498db',
                    fill: true,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, padding: 16, usePointStyle: true, pointStyle: 'circle' }
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${formatCurrency(ctx.parsed.y)}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor }
                },
                y: {
                    grid: { color: 'rgba(128,128,128,0.1)' },
                    ticks: {
                        color: textColor,
                        callback: (v) => formatCurrency(v)
                    }
                }
            }
        }
    });
}

// ── Média diária ────────────────────────────────────────
function renderMediaDiaria(data) {
    if (!data) return '';
    const varClass = data.variacao > 0 ? 'trend-negative' : data.variacao < 0 ? 'trend-positive' : 'trend-neutral';
    const varIcon = data.variacao > 0 ? 'arrow-up' : data.variacao < 0 ? 'arrow-down' : 'equal';

    return `
        <div class="comparative-card comp-mini-card">
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
function renderTaxaEconomia(data) {
    if (!data) return '';
    const isPositive = data.atual >= 0;
    const diffClass = data.diferenca > 0 ? 'trend-positive' : data.diferenca < 0 ? 'trend-negative' : 'trend-neutral';
    const diffIcon = data.diferenca > 0 ? 'arrow-up' : data.diferenca < 0 ? 'arrow-down' : 'equal';
    const gradientColor = isPositive ? '#2ecc71, #27ae60' : '#e74c3c, #c0392b';

    return `
        <div class="comparative-card comp-mini-card">
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

// ── Formas de pagamento ─────────────────────────────────
function renderFormasPagamento(formas) {
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
        <div class="comparative-card comp-full-width">
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

function renderComparative(title, data, period) {
    const getTrendIcon = (value, isDespesa = false) => {
        // Para despesas, aumento é ruim (vermelho), redução é bom (verde)
        if (isDespesa) {
            if (value > 0) return '<i data-lucide="arrow-up"></i>';
            if (value < 0) return '<i data-lucide="arrow-down"></i>';
        } else {
            if (value > 0) return '<i data-lucide="arrow-up"></i>';
            if (value < 0) return '<i data-lucide="arrow-down"></i>';
        }
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
        <div class="comparative-card">
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

function renderCardsReport(data) {
    const reportArea = document.getElementById('reportArea');
    if (!reportArea) return;

    // Renderizar resumo consolidado apenas se houver cartões
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
                    <div class="card-item ${card.status_saude.status}" 
                         style="--card-color: ${cardColor}; cursor: pointer;"
                         data-card-id="${card.id || ''}"
                         data-card-nome="${escapeHtml(card.nome)}"
                         data-card-cor="${cardColor}"
                         data-card-month="${STATE.currentMonth}"
                         data-action="open-card-detail"
                         role="button"
                         tabindex="0">
                        <!-- Header -->
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
                                        <i data-lucide="${alert.type === 'danger' ? 'triangle-alert' : alert.type === 'warning' ? 'circle-alert' : 'info'}"></i>
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
                `;}).join('') : `
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

// ─── Export ──────────────────────────────────────────────────────────────────

export async function handleExport() {
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
            year: STATE.currentMonth.split('-')[0],
            month: STATE.currentMonth.split('-')[1]
        });

        if (STATE.currentAccount) {
            params.set('account_id', STATE.currentAccount);
        }

        const response = await fetch(`${CONFIG.BASE_URL}api/reports/export?${params}`, {
            credentials: 'include'
        });

        if (await handleRestrictedAccess(response)) {
            return;
        }

        if (!response.ok) {
            let errorMsg = 'Erro ao exportar relatório.';
            try {
                const errorData = await response.json();
                if (errorData?.message) errorMsg = errorData.message;
                else if (errorData?.errors) errorMsg = Object.values(errorData.errors).flat().join(', ');
            } catch (_) { /* response body not JSON */ }
            throw new Error(errorMsg);
        }

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

        // Toast de sucesso
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Relatório exportado!',
                text: filename,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    } catch (error) {
        console.error('Export error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Erro ao exportar',
                text: error.message || 'Tente novamente.',
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            alert(error.message || 'Erro ao exportar relatório. Tente novamente.');
        }
    } finally {
        exportBtn.disabled = false;
        exportBtn.innerHTML = originalHTML;
    }
}

/**
 * Refresh PRO sections (insights/comparativos) when their tab becomes active.
 */
export async function refreshActiveSection(section) {
    if (section === 'insights') {
        await updateInsightsSection();
    } else if (section === 'comparativos') {
        await updateComparativesSection();
    }
}

// ─── Event Handlers ──────────────────────────────────────────────────────────

export function syncPickerMode() {
    const showYear = isYearlyView();
    window.LukratoHeader?.setPickerMode?.(showYear ? 'year' : 'month');
    if (showYear) {
        const headerYear = window.LukratoHeader?.getYear?.();
        if (headerYear) {
            const [, monthPart = '01'] = STATE.currentMonth.split('-');
            const normalizedMonth = String(monthPart).padStart(2, '0');
            STATE.currentMonth = `${headerYear}-${normalizedMonth}`;
        }
    }
}

export function handleTabChange(view) {
    STATE.currentView = view;
    setActiveTab(view);
    updateControls();
    syncPickerMode();
    renderReport();
}

export function handleTypeChange(type) {
    if (STATE.currentView === CONFIG.VIEWS.ANNUAL_CATEGORY) {
        STATE.annualCategoryType = type;
    } else {
        STATE.categoryType = type;
    }
    renderReport();
}

export function handleAccountChange(accountId) {
    STATE.currentAccount = accountId || null;
    renderReport();
}

export function onExternalMonthChange(event) {
    if (!event?.detail?.month || isYearlyView()) return;
    if (STATE.currentMonth === event.detail.month) return;
    STATE.currentMonth = event.detail.month;
    updateMonthLabel();
    renderReport();
}

export function onExternalYearChange(event) {
    if (!isYearlyView() || !event?.detail?.year) return;
    const [, monthPart = '01'] = STATE.currentMonth.split('-');
    const normalizedMonth = String(monthPart).padStart(2, '0');
    const newValue = `${event.detail.year}-${normalizedMonth}`;
    if (STATE.currentMonth === newValue) return;
    STATE.currentMonth = newValue;
    renderReport();
}
