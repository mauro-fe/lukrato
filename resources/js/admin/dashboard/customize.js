/**
 * Dashboard customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { Modules } from './state.js';
import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> dashboard section ID */
const SECTION_MAP = {
    // Main
    toggleHealthScore: 'sectionHealthScore',
    toggleAiTip: 'sectionAiTip',
    toggleEvolucao: 'sectionEvolucao',
    toggleAlertas: 'sectionAlertas',
    toggleGrafico: 'chart-section',
    togglePrevisao: 'sectionPrevisao',
    // Extra grid
    toggleMetas: 'sectionMetas',
    toggleCartoes: 'sectionCartoes',
    toggleContas: 'sectionContas',
    toggleOrcamentos: 'sectionOrcamentos',
    toggleFaturas: 'sectionFaturas',
    // Standalone
    toggleGamificacao: 'sectionGamificacao'
};

const COMPLETE_DEFAULTS = {
    toggleHealthScore: true,
    toggleAiTip: true,
    toggleEvolucao: true,
    toggleAlertas: true,
    toggleGrafico: true,
    togglePrevisao: true,
    toggleMetas: false,
    toggleCartoes: false,
    toggleContas: false,
    toggleOrcamentos: false,
    toggleFaturas: false,
    toggleGamificacao: false
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleHealthScore: false,
    toggleAiTip: false,
    toggleEvolucao: false,
    togglePrevisao: false
};

async function loadDashboardPrefs() {
    return fetchUiPagePreferences('dashboard');
}

async function saveDashboardPrefs(prefs) {
    await persistUiPagePreferences('dashboard', prefs);
}

function isVisible(element) {
    return !!element && getComputedStyle(element).display !== 'none';
}

function syncVisibleChildren(container, { hideWhenEmpty = true } = {}) {
    if (!container) return 0;

    const visibleCount = Array.from(container.children).filter(isVisible).length;
    container.dataset.visibleCount = String(visibleCount);

    if (hideWhenEmpty) {
        container.style.display = visibleCount > 0 ? '' : 'none';
    }

    return visibleCount;
}

function syncStage(stage, visibleChildCount) {
    if (!stage) return;

    stage.dataset.visibleCount = String(visibleChildCount);
    stage.style.display = visibleChildCount > 0 ? '' : 'none';
}

function syncDashboardLayout(prefs = COMPLETE_DEFAULTS) {
    const overviewStage = document.querySelector('.dashboard-stage--overview');
    const overviewTop = document.querySelector('.dashboard-overview-top');
    const overviewBottom = document.querySelector('.dashboard-overview-bottom');
    const alertsSection = document.getElementById('sectionAlertas');
    const healthAiRow = document.getElementById('rowHealthAi');
    const healthInsights = document.getElementById('healthScoreInsights');

    const decisionStage = document.querySelector('.dashboard-stage--decision');
    const decisionRow = document.querySelector('.dash-duo-row--decision');
    const insightsRow = document.querySelector('.dash-duo-row--insights');

    const historyStage = document.querySelector('.dashboard-stage--history');
    const historySection = document.getElementById('sectionEvolucao');

    const secondaryStage = document.querySelector('.dashboard-stage--secondary');
    const optionalGrid = document.getElementById('optionalGrid');

    const overviewTopCount = syncVisibleChildren(overviewTop, { hideWhenEmpty: false });

    const healthAiCount = syncVisibleChildren(healthAiRow);
    if (healthInsights) {
        healthInsights.style.display = prefs.toggleHealthScore ? '' : 'none';
    }

    const overviewBottomCount = [alertsSection, healthAiRow, healthInsights].filter(isVisible).length;
    if (overviewBottom) {
        overviewBottom.dataset.visibleCount = String(overviewBottomCount);
        overviewBottom.style.display = overviewBottomCount > 0 ? '' : 'none';
    }

    const decisionRowCount = syncVisibleChildren(decisionRow);
    const insightsRowCount = syncVisibleChildren(insightsRow, { hideWhenEmpty: false });
    const optionalGridCount = syncVisibleChildren(optionalGrid);

    if (optionalGrid) {
        optionalGrid.dataset.layout = optionalGridCount > 0 && optionalGridCount < 5 ? 'fluid' : 'default';
    }

    syncStage(overviewStage, (overviewTopCount > 0 ? 1 : 0) + (overviewBottomCount > 0 ? 1 : 0));
    syncStage(decisionStage, (decisionRowCount > 0 ? 1 : 0) + (insightsRowCount > 0 ? 1 : 0));
    syncStage(historyStage, isVisible(historySection) ? 1 : 0);
    syncStage(secondaryStage, optionalGridCount > 0 ? 1 : 0);
}

const dashboardCustomizer = createPageCustomizer({
    storageKey: 'lk_dashboard_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    gridContainerId: 'optionalGrid',
    gridToggleKeys: ['toggleMetas', 'toggleCartoes', 'toggleContas', 'toggleOrcamentos', 'toggleFaturas'],
    loadPreferences: loadDashboardPrefs,
    savePreferences: saveDashboardPrefs,
    onApply: syncDashboardLayout
});

export function initCustomize() {
    dashboardCustomizer.init();
}

Modules.Customize = {
    init: initCustomize,
    open: dashboardCustomizer.open,
    close: dashboardCustomizer.close
};

