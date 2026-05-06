/**
 * Dashboard customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { Modules } from './state.js';
import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { buildAppUrl } from '../shared/api.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let dashboardCustomizer = null;
let dashboardCustomizerInitialized = false;
let dashboardCustomizerInitPromise = null;
let dashboardCompleteDefaults = {};
let dashboardEssentialDefaults = {};

function resolveDashboardCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const dashboardCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'dashboard'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const dashboardCustomizerDescriptor = dashboardCustomizerCapabilities?.descriptor
        && typeof dashboardCustomizerCapabilities.descriptor === 'object'
        ? dashboardCustomizerCapabilities.descriptor
        : null;

    const sectionMap = dashboardCustomizerDescriptor?.sectionMap
        && typeof dashboardCustomizerDescriptor.sectionMap === 'object'
        ? dashboardCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = dashboardCustomizerCapabilities?.completePreferences
        && typeof dashboardCustomizerCapabilities.completePreferences === 'object'
        ? dashboardCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = dashboardCustomizerCapabilities?.essentialPreferences
        && typeof dashboardCustomizerCapabilities.essentialPreferences === 'object'
        ? dashboardCustomizerCapabilities.essentialPreferences
        : {};

    const gridToggleKeys = Array.isArray(dashboardCustomizerDescriptor?.gridToggleKeys)
        ? dashboardCustomizerDescriptor.gridToggleKeys
        : [];

    const modalConfig = dashboardCustomizerDescriptor?.ids
        && typeof dashboardCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: dashboardCustomizerDescriptor.ids.overlay,
            openButtonId: dashboardCustomizerDescriptor.trigger?.id || 'btnCustomizeDashboard',
            closeButtonId: dashboardCustomizerDescriptor.ids.close,
            saveButtonId: dashboardCustomizerDescriptor.ids.save,
            presetEssentialButtonId: dashboardCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: dashboardCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    dashboardCompleteDefaults = completeDefaults;
    dashboardEssentialDefaults = essentialDefaults;

    return {
        capabilities: dashboardCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        gridToggleKeys,
        modalConfig,
    };
}

function getOrCreateDashboardCustomizer() {
    const resolved = resolveDashboardCustomizerConfig();

    if (dashboardCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: dashboardCustomizer,
            resolved,
        };
    }

    dashboardCustomizer = createPageCustomizer({
        storageKey: 'lk_dashboard_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        gridContainerId: 'optionalGrid',
        gridToggleKeys: resolved.gridToggleKeys,
        capabilities: resolved.capabilities,
        loadPreferences: loadDashboardPrefs,
        savePreferences: saveDashboardPrefs,
        onApply: syncDashboardLayout,
        onLockedOpen: goToDashboardUpgrade,
        modal: resolved.modalConfig,
    });

    return {
        customizer: dashboardCustomizer,
        resolved,
    };
}

async function loadDashboardPrefs() {
    return fetchUiPagePreferences('dashboard');
}

async function saveDashboardPrefs(prefs) {
    await persistUiPagePreferences('dashboard', prefs);
}

function goToDashboardUpgrade() {
    window.location.href = buildAppUrl('billing');
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

function syncDashboardLayout(prefs = dashboardCompleteDefaults) {
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

    syncVisibleChildren(healthAiRow);
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

export function initCustomize() {
    const initialize = () => {
        const { customizer, resolved } = getOrCreateDashboardCustomizer();

        if (!customizer) {
            syncDashboardLayout(resolved.essentialDefaults);
            return false;
        }

        if (!dashboardCustomizerInitialized) {
            customizer.init();
            dashboardCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!dashboardCustomizerInitPromise) {
        dashboardCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            dashboardCustomizerInitPromise = null;
            initialize();
        });
    }
}

Modules.Customize = {
    init: initCustomize,
    open: () => {
        const { customizer } = getOrCreateDashboardCustomizer();
        if (customizer?.open) {
            customizer.open();
            return;
        }

        void ensureRuntimeConfig({}, { silent: true }).finally(() => {
            const { customizer: nextCustomizer } = getOrCreateDashboardCustomizer();
            nextCustomizer?.open?.();
        });
    },
    close: () => {
        const { customizer } = getOrCreateDashboardCustomizer();
        customizer?.close?.();
    }
};

