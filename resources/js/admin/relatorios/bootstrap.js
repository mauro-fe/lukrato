import { CONFIG, STATE, STORAGE_KEYS, TYPE_OPTIONS } from './state.js';
import { ChartManager } from './charts.js';
import { initCustomize } from './customize.js';
import {
    ensureRuntimeConfig,
    getRuntimeConfig,
    onRuntimeConfigUpdate,
} from '../global/runtime-config.js';
import {
    API,
    UI,
    handleAccountChange,
    handleExport,
    handleTabChange,
    handleTypeChange,
    onExternalMonthChange,
    onExternalYearChange,
    refreshActiveSection,
    renderReport,
    syncPickerMode,
} from './app.js';

function syncReportsProFlag() {
    window.IS_PRO = getRuntimeConfig().isPro === true;
    return window.IS_PRO;
}

onRuntimeConfigUpdate(() => {
    syncReportsProFlag();
});

function restoreSavedPreferences() {
    try {
        const savedView = localStorage.getItem(STORAGE_KEYS.ACTIVE_VIEW);
        if (savedView && Object.values(CONFIG.VIEWS).includes(savedView)) {
            STATE.currentView = savedView;
        }

        const savedCategoryType = localStorage.getItem(STORAGE_KEYS.CATEGORY_TYPE);
        if (savedCategoryType && TYPE_OPTIONS[CONFIG.VIEWS.CATEGORY]?.some((option) => option.value === savedCategoryType)) {
            STATE.categoryType = savedCategoryType;
        }

        const savedAnnualType = localStorage.getItem(STORAGE_KEYS.ANNUAL_CATEGORY_TYPE);
        if (savedAnnualType && TYPE_OPTIONS[CONFIG.VIEWS.ANNUAL_CATEGORY]?.some((option) => option.value === savedAnnualType)) {
            STATE.annualCategoryType = savedAnnualType;
        }
    } catch {
        // keep defaults
    }
}

function setupSectionTabs() {
    const switchSection = (section) => {
        STATE.activeSection = section;

        document.querySelectorAll('.rel-section-tab').forEach((tab) => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });

        document.querySelectorAll('.rel-section-panel').forEach((panel) => {
            panel.classList.remove('active');
        });

        const activeTab = document.querySelector(`.rel-section-tab[data-section="${section}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
            activeTab.setAttribute('aria-selected', 'true');
        }

        const panel = document.getElementById(`section-${section}`);
        if (panel) {
            panel.classList.add('active');
        }

        localStorage.setItem(STORAGE_KEYS.ACTIVE_SECTION, section);
        UI.updatePageContext();
        refreshActiveSection(section);
        window.lucide?.createIcons?.();
    };

    const proLockedSections = ['comparativos'];

    document.querySelectorAll('.rel-section-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const section = tab.dataset.section;

            if (!window.IS_PRO && proLockedSections.includes(section)) {
                if (window.PlanLimits?.promptUpgrade) {
                    window.PlanLimits.promptUpgrade({
                        context: 'relatorios',
                        message: 'Esta funcionalidade e exclusiva do plano Pro.',
                    }).catch(() => { });
                } else if (window.LKFeedback?.upgradePrompt) {
                    window.LKFeedback.upgradePrompt({
                        context: 'relatorios',
                        message: 'Esta funcionalidade e exclusiva do plano Pro.',
                    }).catch(() => { });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Recurso Premium',
                        html: 'Esta funcionalidade e exclusiva do <b>plano Pro</b>.<br>Faca upgrade para desbloquear!',
                        confirmButtonText: '<i class="lucide-crown" style="margin-right:6px"></i> Fazer Upgrade',
                        showCancelButton: true,
                        cancelButtonText: 'Agora nao',
                        confirmButtonColor: '#f59e0b',
                        cancelButtonColor: '#64748b',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `${CONFIG.BASE_URL}billing`;
                        }
                    });
                }
                return;
            }

            switchSection(section);
        });
    });

    const savedSection = localStorage.getItem(STORAGE_KEYS.ACTIVE_SECTION);
    if (savedSection && document.getElementById(`section-${savedSection}`)) {
        if (!window.IS_PRO && proLockedSections.includes(savedSection)) {
            switchSection('overview');
        } else {
            switchSection(savedSection);
        }
        return;
    }

    switchSection('overview');
}

function setupFilters(reportType, accountSelect) {
    const clearFiltersWrapper = document.getElementById('clearFiltersWrapper');
    const clearBtn = document.getElementById('btnLimparFiltrosRel');

    const showClearBtn = () => {
        if (!clearFiltersWrapper) {
            return;
        }

        const hasTypeFilter = reportType && reportType.selectedIndex > 0;
        const hasAccountFilter = accountSelect && accountSelect.value !== '';
        clearFiltersWrapper.style.display = (hasTypeFilter || hasAccountFilter) ? 'flex' : 'none';
        UI.syncControlsLayoutState();
    };

    reportType?.addEventListener('change', showClearBtn);
    accountSelect?.addEventListener('change', showClearBtn);

    clearBtn?.addEventListener('click', () => {
        if (reportType) {
            reportType.selectedIndex = 0;
            handleTypeChange(reportType.value);
        }

        if (accountSelect) {
            accountSelect.value = '';
            handleAccountChange('');
        }

        showClearBtn();
    });

    showClearBtn();
    return showClearBtn;
}

function setupDetailModalDelegation(accountSelect, showClearFiltersButton) {
    document.addEventListener('click', (event) => {
        const retryTrigger = event.target.closest('[data-action="retry-report"]');
        if (retryTrigger) {
            event.preventDefault();
            renderReport();
            return;
        }

        const clearAccountTrigger = event.target.closest('[data-action="clear-report-account"]');
        if (clearAccountTrigger) {
            event.preventDefault();
            if (accountSelect) {
                accountSelect.value = '';
            }
            handleAccountChange('');
            if (typeof showClearFiltersButton === 'function') {
                showClearFiltersButton();
            }
            return;
        }

        const trigger = event.target.closest('[data-action="open-card-detail"]');
        if (!trigger) {
            return;
        }

        event.stopPropagation();

        const cardId = Number.parseInt(String(trigger.dataset.cardId || ''), 10);
        const cardMonth = trigger.dataset.cardMonth || STATE.currentMonth;
        const monthParam = /^\d{4}-\d{2}$/.test(cardMonth) ? cardMonth : STATE.currentMonth;

        if (!Number.isInteger(cardId) || cardId <= 0) {
            return;
        }

        const params = new URLSearchParams({
            mes: monthParam,
            origem: 'relatorios',
        });
        window.location.href = `${CONFIG.BASE_URL}cartoes/${cardId}?${params.toString()}`;
    });
}

function setupGlobalApi() {
    window.ReportsAPI = {
        setMonth: (yearMonth) => {
            if (!/^\d{4}-\d{2}$/.test(yearMonth)) {
                return;
            }
            STATE.currentMonth = yearMonth;
            UI.updateMonthLabel();
            renderReport();
        },
        setView: (view) => {
            if (Object.values(CONFIG.VIEWS).includes(view)) {
                handleTabChange(view);
            }
        },
        refresh: () => renderReport(),
        getState: () => ({ ...STATE }),
    };
}

async function initialize() {
    syncReportsProFlag();
    initCustomize();
    ChartManager.setupDefaults();

    STATE.accounts = await API.fetchAccounts();
    const accountSelect = document.getElementById('accountFilter');
    if (accountSelect) {
        STATE.accounts.forEach((account) => {
            const option = document.createElement('option');
            option.value = account.id;
            option.textContent = account.name;
            accountSelect.appendChild(option);
        });
    }

    restoreSavedPreferences();

    document.querySelectorAll('.tab-btn').forEach((button) => {
        button.addEventListener('click', () => handleTabChange(button.dataset.view));
    });

    UI.setActiveTab(STATE.currentView);
    UI.updateControls();
    UI.updatePageContext();

    setupSectionTabs();

    const reportType = document.getElementById('reportType');
    reportType?.addEventListener('change', (event) => handleTypeChange(event.target.value));
    accountSelect?.addEventListener('change', (event) => handleAccountChange(event.target.value));

    const showClearFiltersButton = setupFilters(reportType, accountSelect);

    document.addEventListener('lukrato:theme-changed', () => {
        ChartManager.setupDefaults();
        renderReport();
    });

    const headerMonth = window.LukratoHeader?.getMonth?.();
    if (headerMonth) {
        STATE.currentMonth = headerMonth;
    }

    document.addEventListener('lukrato:month-changed', onExternalMonthChange);
    document.addEventListener('lukrato:year-changed', onExternalYearChange);

    document.getElementById('exportBtn')?.addEventListener('click', handleExport);

    setupDetailModalDelegation(accountSelect, showClearFiltersButton);

    syncPickerMode();
    UI.updateMonthLabel();
    UI.updateControls();
    await renderReport();
}

export function bootRelatoriosPage() {
    if (window.__LK_REPORTS_LOADED__) {
        return;
    }

    window.__LK_REPORTS_LOADED__ = true;

    const start = async () => {
        await ensureRuntimeConfig({}, { silent: true });
        syncReportsProFlag();
        await initialize();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            void start();
        });
    } else {
        void start();
    }

    setupGlobalApi();
}
