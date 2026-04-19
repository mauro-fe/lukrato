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
    PAYWALL_MESSAGE, TYPE_OPTIONS,
    STORAGE_KEYS, SECTION_META, VIEW_META,
    escapeHtml
} from './state.js';
import { ChartManager } from './charts.js';
import { apiGet, getErrorMessage } from '../shared/api.js';
import { resolveAccountsEndpoint } from '../api/endpoints/finance.js';
import {
    resolveReportsComparativesEndpoint,
    resolveReportsEndpoint,
    resolveReportsInsightsEndpoint,
    resolveReportsInsightsTeaserEndpoint,
    resolveReportsSummaryEndpoint,
} from '../api/endpoints/reports.js';
import {
    renderChartInsight,
    renderCardsReport,
} from './app-renderers.js';
import { createSectionHandlers } from './app-sections.js';
import { createExportHandler } from './app-export.js';

// ─── Local Aliases (keep method bodies identical to original) ────────────────

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
    location.href = `${CONFIG.BASE_URL}billing`;
}

async function showRestrictionAlert(message) {
    const text = message || PAYWALL_MESSAGE;
    if (window.PlanLimits?.promptUpgrade) {
        await window.PlanLimits.promptUpgrade({
            context: 'relatorios',
            message: text,
        });
    } else if (window.LKFeedback?.upgradePrompt) {
        await window.LKFeedback.upgradePrompt({
            context: 'relatorios',
            message: text,
        });
    } else if (window.Swal?.fire) {
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
            const json = await apiGet(resolveReportsEndpoint(), {
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
            const json = await apiGet(resolveReportsEndpoint(), Object.fromEntries(params.entries()));
            return json.data || json;
        } catch {
            return null;
        }
    },

    async fetchAccounts() {
        try {
            const json = await apiGet(resolveAccountsEndpoint());
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
            const json = await apiGet(resolveReportsSummaryEndpoint(), { year, month });
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
            const json = await apiGet(resolveReportsInsightsEndpoint(), { year, month });
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
            const json = await apiGet(resolveReportsInsightsTeaserEndpoint(), { year, month });
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
            const json = await apiGet(resolveReportsComparativesEndpoint(), Object.fromEntries(params.entries()));
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

const {
    updateSummaryCards,
    updateInsightsSection,
    updateOverviewSection,
    updateComparativesSection,
} = createSectionHandlers({ API });

export const handleExport = createExportHandler({
    getReportType,
    showRestrictionAlert,
    handleRestrictedAccess,
});

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
