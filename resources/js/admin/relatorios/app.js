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
    STORAGE_KEYS, SECTION_META, VIEW_META,
    escapeHtml, computeInitialMonth, safeColor, getChartColor
} from './state.js';
import { ChartManager } from './charts.js';
import { apiGet, getErrorMessage } from '../shared/api.js';

// ─── Local Aliases (keep method bodies identical to original) ────────────────

const formatCurrency = (v) => Utils.formatCurrency(v);
const formatMonthLabel = (m) => Utils.formatMonthLabel(m);
const isYearlyView = (v) => Utils.isYearlyView(v);
const getReportType = () => Utils.getReportType();
const getActiveCategoryType = () => Utils.getActiveCategoryType();

function getSelectedAccountName(accountId = STATE.currentAccount) {
    if (!accountId) return null;
    return STATE.accounts.find(acc => String(acc.id) === String(accountId))?.name || `Conta #${accountId}`;
}

function getCurrentPeriodLabel() {
    if (isYearlyView()) {
        return `Ano ${STATE.currentMonth.split('-')[0]}`;
    }
    return formatMonthLabel(STATE.currentMonth);
}

function getCurrentTypeLabel() {
    const currentType = getActiveCategoryType();
    const options = TYPE_OPTIONS[STATE.currentView] || [];
    return options.find(option => option.value === currentType)?.label || null;
}

function isScopedAnalysisSection(section = STATE.activeSection) {
    return section === 'relatorios' || section === 'comparativos';
}

function getSectionMeta(section = STATE.activeSection) {
    return SECTION_META[section] || SECTION_META.overview;
}

function getViewMeta(view = STATE.currentView) {
    return VIEW_META[view] || VIEW_META[CONFIG.VIEWS.CATEGORY];
}

function persistReportPreferences() {
    try {
        localStorage.setItem(STORAGE_KEYS.ACTIVE_VIEW, STATE.currentView);
        localStorage.setItem(STORAGE_KEYS.CATEGORY_TYPE, STATE.categoryType);
        localStorage.setItem(STORAGE_KEYS.ANNUAL_CATEGORY_TYPE, STATE.annualCategoryType);
    } catch {
        // storage can be unavailable in private mode or restricted browsers
    }
}

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

    const status = Number(response.status || response?.data?.status || 0);

    if (status === 401) {
        const current = encodeURIComponent(location.pathname + location.search);
        location.href = `${CONFIG.BASE_URL}login?return=${current}`;
        return true;
    }

    if (status === 403) {
        let message = PAYWALL_MESSAGE;
        if (response?.data?.message) {
            message = response.data.message;
        } else if (typeof response?.clone === 'function') {
            try {
                const payload = await response.clone().json();
                if (payload?.message) {
                    message = payload.message;
                }
            } catch {
                // ignora problemas ao converter JSON
            }
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
        STATE.lastReportError = null;

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.FETCH_TIMEOUT);

        try {
            const json = await apiGet(`${CONFIG.BASE_URL}api/reports`, {
                type: Utils.getReportType(),
                year: STATE.currentMonth.split('-')[0],
                month: STATE.currentMonth.split('-')[1],
                account_id: STATE.currentAccount || undefined
            });
            clearTimeout(timeoutId);

            STATE.accessRestricted = false;
            STATE.lastReportError = null;
            return json.data || json;
        } catch (error) {
            clearTimeout(timeoutId);
            if (await handleRestrictedAccess(error)) {
                return null;
            }
            STATE.lastReportError = error.name === 'AbortError'
                ? 'A requisição demorou demais. Tente novamente em instantes.'
                : 'Não foi possível carregar o relatório agora. Verifique a conexão e tente novamente.';
            console.error('Error fetching report data:', error);
            showErrorToast(getErrorMessage(error, 'Erro ao carregar relatório. Verifique sua conexão.'));
            return null;
        }
    },

    async fetchReportDataForType(type, options = {}) {
        const params = new URLSearchParams({
            type,
            year: STATE.currentMonth.split('-')[0],
            month: STATE.currentMonth.split('-')[1]
        });

        const accountId = Object.prototype.hasOwnProperty.call(options, 'accountId')
            ? options.accountId
            : STATE.currentAccount;

        if (accountId) {
            params.set('account_id', accountId);
        }

        try {
            const json = await apiGet(`${CONFIG.BASE_URL}api/reports`, Object.fromEntries(params.entries()));
            return json.data || json;
        } catch {
            return null;
        }
    },

    async fetchAccounts() {
        try {
            const json = await apiGet(`${CONFIG.BASE_URL}api/contas`);
            STATE.accessRestricted = false;
            const items = json.data || json.items || json || [];
            return (Array.isArray(items) ? items : []).map(acc => ({
                id: Number(acc.id),
                name: acc.nome || acc.apelido || acc.instituicao || `Conta #${acc.id}`
            }));
        } catch (error) {
            if (await handleRestrictedAccess(error)) {
                return [];
            }
            console.error('Error fetching accounts:', error);
            return [];
        }
    },

    async fetchSummaryStats() {
        const [year, month] = STATE.currentMonth.split('-');
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.FETCH_TIMEOUT);
        try {
            const json = await apiGet(`${CONFIG.BASE_URL}api/reports/summary`, { year, month });
            clearTimeout(timeoutId);
            return json.data || json;
        } catch (error) {
            clearTimeout(timeoutId);
            if (await handleRestrictedAccess(error)) {
                return {
                    totalReceitas: 0,
                    totalDespesas: 0,
                    saldo: 0,
                    totalCartoes: 0
                };
            }
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
            const json = await apiGet(`${CONFIG.BASE_URL}api/reports/insights`, { year, month });
            clearTimeout(timeoutId);
            return json.data || json;
        } catch (error) {
            clearTimeout(timeoutId);
            if (await handleRestrictedAccess(error)) return { insights: [] };
            console.error('Error fetching insights:', error);
            return { insights: [] };
        }
    },

    async fetchInsightsTeaser() {
        const [year, month] = STATE.currentMonth.split('-');
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.FETCH_TIMEOUT);
        try {
            const json = await apiGet(`${CONFIG.BASE_URL}api/reports/insights-teaser`, { year, month });
            clearTimeout(timeoutId);
            return json.data || json;
        } catch (error) {
            clearTimeout(timeoutId);
            console.error('Error fetching insights teaser:', error);
            return { insights: [], totalCount: 0, isTeaser: true };
        }
    },

    async fetchComparatives() {
        const [year, month] = STATE.currentMonth.split('-');
        const params = new URLSearchParams({ year, month });
        if (STATE.currentAccount) {
            params.set('account_id', STATE.currentAccount);
        }
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.FETCH_TIMEOUT);
        try {
            const json = await apiGet(`${CONFIG.BASE_URL}api/reports/comparatives`, Object.fromEntries(params.entries()));
            clearTimeout(timeoutId);
            return json.data || json;
        } catch (error) {
            clearTimeout(timeoutId);
            if (await handleRestrictedAccess(error)) return null;
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
        const scopeName = getSelectedAccountName();
        const viewMeta = getViewMeta();
        const periodLabel = getCurrentPeriodLabel();
        const scopeHint = scopeName
            ? `Nenhum dado foi encontrado para ${scopeName} em ${periodLabel}.`
            : `Não há lançamentos suficientes para montar este recorte em ${periodLabel}.`;

        UI.setContent(`
            <div class="empty-state report-empty-state">
                <i data-lucide="pie-chart"></i>
                <h3>${escapeHtml(viewMeta.title)}</h3>
                <p>${escapeHtml(scopeHint)}</p>
                <div class="report-state-actions">
                    <a href="${CONFIG.BASE_URL}lancamentos" class="empty-cta">
                        <i data-lucide="plus"></i>
                        <span>Adicionar lançamento</span>
                    </a>
                    ${scopeName ? `
                        <button type="button" class="btn btn-secondary" data-action="clear-report-account">
                            <i data-lucide="layers"></i>
                            <span>Mostrar todas as contas</span>
                        </button>
                    ` : ''}
                </div>
            </div>
        `);
    },

    showErrorState(message) {
        const safeMessage = escapeHtml(message || 'Não foi possível carregar este relatório.');
        UI.setContent(`
            <div class="error-state report-error-state">
                <i data-lucide="triangle-alert"></i>
                <p class="error-message">${safeMessage}</p>
                <div class="report-state-actions">
                    <button type="button" class="btn btn-primary btn-retry" data-action="retry-report">
                        <i data-lucide="refresh-cw"></i>
                        <span>Tentar novamente</span>
                    </button>
                    ${STATE.currentAccount ? `
                        <button type="button" class="btn btn-secondary" data-action="clear-report-account">
                            <i data-lucide="layers"></i>
                            <span>Voltar para todas as contas</span>
                        </button>
                    ` : ''}
                </div>
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
                <button type="button" class="btn-upgrade surface-button surface-button--upgrade surface-button--lg" data-action="go-pro">
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

    updatePageContext() {
        const kickerEl = document.getElementById('reportsContextKicker');
        const titleEl = document.getElementById('reportsContextTitle');
        const descriptionEl = document.getElementById('reportsContextDescription');
        const chipsEl = document.getElementById('reportsContextChips');
        const actionsEl = document.getElementById('reportsContextActions');

        if (!kickerEl || !titleEl || !descriptionEl || !chipsEl || !actionsEl) {
            return;
        }

        const sectionMeta = getSectionMeta();
        const viewMeta = getViewMeta();
        const periodLabel = getCurrentPeriodLabel();
        const accountName = getSelectedAccountName();
        const scopedSection = isScopedAnalysisSection();
        const currentTypeLabel = getCurrentTypeLabel();
        const isPreview = !window.IS_PRO && STATE.activeSection === 'insights';

        kickerEl.textContent = sectionMeta.kicker;
        titleEl.textContent = STATE.activeSection === 'relatorios' ? viewMeta.title : sectionMeta.title;
        descriptionEl.textContent = STATE.activeSection === 'relatorios' ? viewMeta.description : sectionMeta.description;

        const chips = [
            `<span class="context-chip surface-chip"><i data-lucide="calendar-range"></i><span>${escapeHtml(periodLabel)}</span></span>`
        ];

        if (STATE.activeSection === 'relatorios' && currentTypeLabel) {
            chips.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="filter"></i><span>${escapeHtml(currentTypeLabel)}</span></span>`);
        }

        if (accountName && scopedSection) {
            chips.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="landmark"></i><span>${escapeHtml(accountName)}</span></span>`);
        } else if (accountName && !scopedSection) {
            chips.push(`<span class="context-chip surface-chip"><i data-lucide="bookmark"></i><span>Filtro salvo: ${escapeHtml(accountName)}</span></span>`);
        } else {
            chips.push(`<span class="context-chip surface-chip"><i data-lucide="layers"></i><span>Consolidado</span></span>`);
        }

        if (isPreview) {
            chips.push(`<span class="context-chip surface-chip surface-chip--pro context-chip-pro"><i data-lucide="crown"></i><span>Preview PRO</span></span>`);
        }

        chipsEl.innerHTML = chips.join('');
        actionsEl.innerHTML = accountName ? `
            <button type="button" class="context-action-btn surface-button surface-button--subtle" data-action="clear-report-account">
                <i data-lucide="eraser"></i>
                <span>Limpar filtro de conta</span>
            </button>
        ` : '';

        if (window.lucide) lucide.createIcons();
    },

    updateReportFilterSummary() {
        const summaryEl = document.getElementById('reportFilterSummary');
        const noteEl = document.getElementById('reportScopeNote');
        if (!summaryEl || !noteEl) return;

        const chips = [
            `<span class="report-filter-chip surface-chip"><i data-lucide="calendar-range"></i><span>${escapeHtml(getCurrentPeriodLabel())}</span></span>`,
            `<span class="report-filter-chip surface-chip"><i data-lucide="bar-chart-3"></i><span>${escapeHtml(getViewMeta().title)}</span></span>`
        ];

        const typeLabel = getCurrentTypeLabel();
        if (typeLabel) {
            chips.push(`<span class="report-filter-chip surface-chip"><i data-lucide="filter"></i><span>${escapeHtml(typeLabel)}</span></span>`);
        }

        if (STATE.currentAccount) {
            chips.push(`<span class="report-filter-chip surface-chip surface-chip--highlight report-filter-chip-highlight"><i data-lucide="landmark"></i><span>${escapeHtml(getSelectedAccountName())}</span></span>`);
        } else {
            chips.push(`<span class="report-filter-chip surface-chip"><i data-lucide="layers"></i><span>Todas as contas</span></span>`);
        }

        summaryEl.innerHTML = chips.join('');
        noteEl.classList.remove('hidden');
        noteEl.innerHTML = STATE.currentAccount
            ? '<i data-lucide="info"></i><span>O resumo do topo continua consolidado. O filtro por conta afeta este gráfico e a aba Comparativos.</span>'
            : '<i data-lucide="info"></i><span>Use o filtro de conta para analisar um recorte específico sem perder o consolidado do topo.</span>';

        if (window.lucide) lucide.createIcons();
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
const showErrorState = (message) => UI.showErrorState(message);
const showPaywall = (m) => UI.showPaywall(m);
const updateMonthLabel = () => UI.updateMonthLabel();
const updatePageContext = () => UI.updatePageContext();
const updateReportFilterSummary = () => UI.updateReportFilterSummary();
const updateControls = () => UI.updateControls();
const setActiveTab = (v) => UI.setActiveTab(v);
const renderPieChart = (d) => ChartManager.renderPie(d);
const renderLineChart = (d) => ChartManager.renderLine(d);
const renderBarChart = (d) => ChartManager.renderBar(d);

// ─── Rendering ───────────────────────────────────────────────────────────────

export async function renderReport() {
    updatePageContext();
    updateReportFilterSummary();
    showLoading();

    // Atualizar cards de resumo
    updateSummaryCards();

    const data = await fetchReportData();

    if (STATE.accessRestricted) {
        return;
    }

    if (STATE.lastReportError) {
        return showErrorState(STATE.lastReportError);
    }

    // Validação específica para cada tipo de relatório
    if (STATE.currentView === CONFIG.VIEWS.CARDS) {
        if (!data || !Array.isArray(data.cards)) {
            return showEmptyState();
        }
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

    renderChartInsight(data);
}

// ─── Trend Badge Helper ──────────────────────────────────────────────────────

function updateTrendBadge(elementId, current, previous, invertColors = false) {
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

function renderChartInsight(data) {
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

    // Trend badges (receitas/saldo: up=good, despesas/cartoes: up=bad)
    updateTrendBadge('trendReceitas', stats.totalReceitas, stats.prevReceitas, false);
    updateTrendBadge('trendDespesas', stats.totalDespesas, stats.prevDespesas, true);
    updateTrendBadge('trendSaldo', stats.saldo, stats.prevSaldo, false);
    updateTrendBadge('trendCartoes', stats.totalCartoes, stats.prevCartoes, true);

    // Atualizar seções visíveis
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

async function updateInsightsSection() {
    const insightsContainer = document.getElementById('insightsContainer');
    if (!insightsContainer) return;

    // PRO: full insights; Free: teaser (max 3)
    let data;
    if (window.IS_PRO) {
        data = await API.fetchInsights();
    } else {
        data = await API.fetchInsightsTeaser();
    }

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
        <div class="insight-card insight-${insight.type} surface-card surface-card--interactive">
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

    // Free users: append teaser overlay with upgrade CTA
    if (!window.IS_PRO && data.isTeaser) {
        const remaining = Math.max(0, (data.totalCount || 0) - data.insights.length);
        const remainingLabel = remaining > 0
            ? `Desbloqueie mais ${remaining} insights com PRO`
            : 'Desbloqueie todos os insights com PRO';

        insightsContainer.insertAdjacentHTML('beforeend', `
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
        `);
    }

    if (window.lucide) lucide.createIcons();
}

// ─── Overview Section ────────────────────────────────────────────────────────

let overviewCharts = [];

async function updateOverviewSection() {
    const pulseEl = document.getElementById('overviewPulse');
    const insightsEl = document.getElementById('overviewInsights');
    const catChartEl = document.getElementById('overviewCategoryChart');
    const compChartEl = document.getElementById('overviewComparisonChart');

    // Destroy previous mini charts
    overviewCharts.forEach(c => { try { c.destroy(); } catch { } });
    overviewCharts = [];

    // Fetch data in parallel
    const [stats, insightsData, categoryData, comparisonData] = await Promise.all([
        API.fetchSummaryStats(),
        API.fetchInsightsTeaser(),
        API.fetchReportDataForType('despesas_por_categoria', { accountId: null }),
        API.fetchReportDataForType('receitas_despesas_diario', { accountId: null }),
    ]);

    // 1. Monthly Pulse
    if (pulseEl) {
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

    // 2. Top Insights
    if (insightsEl) {
        if (insightsData?.insights?.length > 0) {
            const faToLucide = {
                'arrow-trend-up': 'trending-up', 'arrow-trend-down': 'trending-down',
                'exclamation-triangle': 'triangle-alert', 'check-circle': 'circle-check',
                'shield-alert': 'shield-alert', 'gauge': 'gauge', 'target': 'target',
                'receipt': 'receipt', 'calculator': 'calculator', 'layers': 'layers',
                'piggy-bank': 'piggy-bank', 'pie-chart': 'pie-chart',
                'calendar-range': 'calendar-range', 'calendar-clock': 'calendar-clock',
                'credit-card': 'credit-card', 'trending-up': 'trending-up',
                'trending-down': 'trending-down', 'list-plus': 'list-plus',
                'list-minus': 'list-minus', 'banknote': 'banknote', 'clock': 'clock'
            };
            insightsEl.innerHTML = insightsData.insights.map(insight => {
                const icon = faToLucide[insight.icon] || insight.icon;
                return `
                <div class="insight-card insight-${insight.type} surface-card surface-card--interactive">
                    <div class="insight-icon"><i data-lucide="${icon}"></i></div>
                    <div class="insight-content">
                        <h4>${escapeHtml(insight.title)}</h4>
                        <p>${escapeHtml(insight.message)}</p>
                    </div>
                </div>`;
            }).join('');
        } else {
            insightsEl.innerHTML = '<p class="empty-message">Nenhum insight disponível no momento</p>';
        }
    }

    // 3. Mini Category Donut
    if (catChartEl && categoryData?.labels?.length > 0) {
        catChartEl.innerHTML = '';
        const topN = 5;
        let labels = categoryData.labels.slice(0, topN);
        let values = categoryData.values.slice(0, topN).map(Number);
        if (categoryData.labels.length > topN) {
            const otherSum = categoryData.values.slice(topN).reduce((s, v) => s + Number(v), 0);
            labels.push('Outros');
            values.push(otherSum);
        }

        const miniDonut = new ApexCharts(catChartEl, {
            chart: { type: 'donut', height: 220, background: 'transparent' },
            series: values,
            labels: labels,
            colors: ['#E67E22', '#2C3E50', '#2ECC71', '#F39C12', '#9B59B6', '#1ABC9C'],
            legend: { position: 'bottom', fontSize: '11px', labels: { colors: 'var(--color-text-muted)' } },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '60%' } } },
            stroke: { show: false },
            tooltip: {
                y: { formatter: (v) => formatCurrency(v) }
            }
        });
        miniDonut.render();
        overviewCharts.push(miniDonut);
    } else if (catChartEl) {
        catChartEl.innerHTML = '<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de categorias</p>';
    }

    // 4. Mini Comparison Bar
    if (compChartEl && comparisonData?.labels?.length > 0) {
        compChartEl.innerHTML = '';
        const rec = (comparisonData.receitas || []).map(Number);
        const desp = (comparisonData.despesas || []).map(Number);
        // Aggregate into weeks for a cleaner mini chart
        const weekLabels = [];
        const weekRec = [];
        const weekDesp = [];
        const chunkSize = 7;
        for (let i = 0; i < comparisonData.labels.length; i += chunkSize) {
            const weekNum = Math.floor(i / chunkSize) + 1;
            weekLabels.push(`Sem ${weekNum}`);
            weekRec.push(rec.slice(i, i + chunkSize).reduce((s, v) => s + v, 0));
            weekDesp.push(desp.slice(i, i + chunkSize).reduce((s, v) => s + v, 0));
        }

        const miniBar = new ApexCharts(compChartEl, {
            chart: { type: 'bar', height: 220, background: 'transparent', toolbar: { show: false } },
            series: [
                { name: 'Receitas', data: weekRec },
                { name: 'Despesas', data: weekDesp }
            ],
            colors: ['#2ECC71', '#E74C3C'],
            xaxis: {
                categories: weekLabels,
                labels: { style: { colors: 'var(--color-text-muted)', fontSize: '11px' } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { fontSize: '10px' },
                    formatter: (v) => formatCurrency(v)
                }
            },
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 4 } },
            dataLabels: { enabled: false },
            legend: { position: 'bottom', fontSize: '11px', labels: { colors: 'var(--color-text-muted)' } },
            grid: { borderColor: 'rgba(255,255,255,0.05)' },
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: (v) => formatCurrency(v) }
            }
        });
        miniBar.render();
        overviewCharts.push(miniBar);
    } else if (compChartEl) {
        compChartEl.innerHTML = '<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de movimentação</p>';
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
function renderEvolucao(evolucao) {
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

function renderEvolucaoChart(evolucao) {
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
function renderMediaDiaria(data) {
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
function renderTaxaEconomia(data) {
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

function renderComparative(title, data, period) {
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

function renderCardsReport(data) {
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

// ─── Export ──────────────────────────────────────────────────────────────────

export async function handleExport() {
    if (!window.IS_PRO) {
        return showRestrictionAlert('Exportação de relatórios é exclusiva do plano PRO.');
    }

    const currentType = getReportType() || 'despesas_por_categoria';

    const { value: formValues } = await Swal.fire({
        title: 'Exportar Relatório',
        html: `
            <div style="text-align:left;display:flex;flex-direction:column;gap:12px;padding-top:8px;">
                <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Tipo de Relatório</label>
                <select id="swalExportType" class="swal2-select" style="width:100%;font-size:0.9rem;">
                    <option value="despesas_por_categoria" ${currentType === 'despesas_por_categoria' ? 'selected' : ''}>Despesas por Categoria</option>
                    <option value="receitas_por_categoria" ${currentType === 'receitas_por_categoria' ? 'selected' : ''}>Receitas por Categoria</option>
                    <option value="saldo_mensal" ${currentType === 'saldo_mensal' ? 'selected' : ''}>Saldo Diário</option>
                    <option value="receitas_despesas_diario" ${currentType === 'receitas_despesas_diario' ? 'selected' : ''}>Receitas x Despesas Diário</option>
                    <option value="evolucao_12m" ${currentType === 'evolucao_12m' ? 'selected' : ''}>Evolução 12 Meses</option>
                    <option value="receitas_despesas_por_conta" ${currentType === 'receitas_despesas_por_conta' ? 'selected' : ''}>Receitas x Despesas por Conta</option>
                    <option value="cartoes_credito" ${currentType === 'cartoes_credito' ? 'selected' : ''}>Relatório de Cartões</option>
                    <option value="resumo_anual" ${currentType === 'resumo_anual' ? 'selected' : ''}>Resumo Anual</option>
                    <option value="despesas_anuais_por_categoria" ${currentType === 'despesas_anuais_por_categoria' ? 'selected' : ''}>Despesas Anuais por Categoria</option>
                    <option value="receitas_anuais_por_categoria" ${currentType === 'receitas_anuais_por_categoria' ? 'selected' : ''}>Receitas Anuais por Categoria</option>
                </select>
                <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Formato</label>
                <select id="swalExportFormat" class="swal2-select" style="width:100%;font-size:0.9rem;">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel (.xlsx)</option>
                </select>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Exportar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e67e22',
        preConfirm: () => ({
            type: document.getElementById('swalExportType').value,
            format: document.getElementById('swalExportFormat').value
        })
    });

    if (!formValues) return;

    const exportBtn = document.getElementById('exportBtn');
    const originalHTML = exportBtn ? exportBtn.innerHTML : '';
    if (exportBtn) {
        exportBtn.disabled = true;
        exportBtn.innerHTML = `
            <div class="spinner" style="width: 1rem; height: 1rem; border-width: 2px;"></div>
            <span>Exportando...</span>
        `;
    }

    try {
        const type = formValues.type;
        const format = formValues.format;

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
            } catch (_) { }
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
        const message = getErrorMessage(error, 'Erro ao exportar relatório. Tente novamente.');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Erro ao exportar',
                text: message,
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            alert(message);
        }
    } finally {
        if (exportBtn) {
            exportBtn.disabled = false;
            exportBtn.innerHTML = originalHTML;
        }
    }
}

/**
 * Refresh sections when their tab becomes active.
 */
export async function refreshActiveSection(section) {
    if (section === 'overview') {
        await updateOverviewSection();
    } else if (section === 'relatorios') {
        await renderReport();
    } else if (section === 'insights') {
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
    updatePageContext();
    updateReportFilterSummary();
    syncPickerMode();
    persistReportPreferences();
    renderReport();
}

export function handleTypeChange(type) {
    if (STATE.currentView === CONFIG.VIEWS.ANNUAL_CATEGORY) {
        STATE.annualCategoryType = type;
    } else {
        STATE.categoryType = type;
    }
    updatePageContext();
    updateReportFilterSummary();
    persistReportPreferences();
    renderReport();
}

export function handleAccountChange(accountId) {
    STATE.currentAccount = accountId || null;
    updatePageContext();
    updateReportFilterSummary();
    renderReport();
}

export function onExternalMonthChange(event) {
    if (!event?.detail?.month || isYearlyView()) return;
    if (STATE.currentMonth === event.detail.month) return;
    STATE.currentMonth = event.detail.month;
    updateMonthLabel();
    updatePageContext();
    updateReportFilterSummary();
    renderReport();
}

export function onExternalYearChange(event) {
    if (!isYearlyView() || !event?.detail?.year) return;
    const [, monthPart = '01'] = STATE.currentMonth.split('-');
    const normalizedMonth = String(monthPart).padStart(2, '0');
    const newValue = `${event.detail.year}-${normalizedMonth}`;
    if (STATE.currentMonth === newValue) return;
    STATE.currentMonth = newValue;
    updatePageContext();
    updateReportFilterSummary();
    renderReport();
}
