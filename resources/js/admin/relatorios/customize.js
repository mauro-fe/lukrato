/**
 * Relatorios customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

const TAB_SECTION_TOGGLE_MAP = {
    toggleRelSectionInsights: 'insights',
    toggleRelSectionRelatorios: 'relatorios',
    toggleRelSectionComparativos: 'comparativos'
};

let relatoriosCustomizer = null;
let relatoriosCustomizerInitialized = false;
let relatoriosCustomizerInitPromise = null;
let relatoriosCompleteDefaults = {};
const OVERVIEW_ESSENTIAL_COMPARATIVES_ID = 'overviewEssentialComparatives';
const RELATORIOS_STORAGE_KEY = 'lk_relatorios_prefs';

function normalizeRelatoriosPreferenceShape(rawPrefs, essentialDefaults = {}) {
    if (!rawPrefs || typeof rawPrefs !== 'object') {
        return rawPrefs;
    }

    const normalized = { ...rawPrefs };
    let hasChanges = false;

    Object.keys(TAB_SECTION_TOGGLE_MAP).forEach((toggleKey) => {
        if (Object.prototype.hasOwnProperty.call(normalized, toggleKey)) {
            return;
        }

        normalized[toggleKey] = essentialDefaults[toggleKey] ?? false;
        hasChanges = true;
    });

    return hasChanges ? normalized : rawPrefs;
}

function migrateStoredRelatoriosPrefs(essentialDefaults = {}) {
    try {
        const raw = localStorage.getItem(RELATORIOS_STORAGE_KEY);
        if (!raw) {
            return;
        }

        const parsed = JSON.parse(raw);
        const normalized = normalizeRelatoriosPreferenceShape(parsed, essentialDefaults);

        if (normalized !== parsed) {
            localStorage.setItem(RELATORIOS_STORAGE_KEY, JSON.stringify(normalized));
        }
    } catch {
        // Ignore storage parsing issues and keep runtime defaults.
    }
}

async function loadRelatoriosPrefs() {
    const prefs = await fetchUiPagePreferences('relatorios');

    return normalizeRelatoriosPreferenceShape(prefs, resolveRelatoriosCustomizerConfig().essentialDefaults);
}

async function saveRelatoriosPrefs(prefs) {
    await persistUiPagePreferences('relatorios', prefs);
}

function resolveRelatoriosCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const relatoriosCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'relatorios'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const relatoriosCustomizerDescriptor = relatoriosCustomizerCapabilities?.descriptor
        && typeof relatoriosCustomizerCapabilities.descriptor === 'object'
        ? relatoriosCustomizerCapabilities.descriptor
        : null;

    const sectionMap = relatoriosCustomizerDescriptor?.sectionMap
        && typeof relatoriosCustomizerDescriptor.sectionMap === 'object'
        ? relatoriosCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = relatoriosCustomizerCapabilities?.completePreferences
        && typeof relatoriosCustomizerCapabilities.completePreferences === 'object'
        ? relatoriosCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = relatoriosCustomizerCapabilities?.essentialPreferences
        && typeof relatoriosCustomizerCapabilities.essentialPreferences === 'object'
        ? relatoriosCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = relatoriosCustomizerDescriptor?.ids
        && typeof relatoriosCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: relatoriosCustomizerDescriptor.ids.overlay,
            openButtonId: relatoriosCustomizerDescriptor.trigger?.id || 'btnCustomizeRelatorios',
            closeButtonId: relatoriosCustomizerDescriptor.ids.close,
            saveButtonId: relatoriosCustomizerDescriptor.ids.save,
            presetEssentialButtonId: relatoriosCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: relatoriosCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    relatoriosCompleteDefaults = completeDefaults;
    migrateStoredRelatoriosPrefs(essentialDefaults);

    return {
        capabilities: relatoriosCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function isVisibleTab(tab) {
    return Boolean(tab) && !tab.hidden && tab.style.display !== 'none';
}

function syncSectionTab(section, visible) {
    const tab = document.querySelector(`.rel-section-tab[data-section="${section}"]`);
    if (!(tab instanceof HTMLElement)) {
        return;
    }

    tab.hidden = !visible;
    tab.style.display = visible ? '' : 'none';

    if (!visible) {
        tab.classList.remove('active');
        tab.setAttribute('aria-selected', 'false');
    }
}

function syncActiveSectionTab() {
    const activeTab = document.querySelector('.rel-section-tab.active');
    if (activeTab instanceof HTMLElement && isVisibleTab(activeTab)) {
        return;
    }

    const fallbackTab = Array.from(document.querySelectorAll('.rel-section-tab'))
        .find((tab) => tab instanceof HTMLElement && isVisibleTab(tab));

    if (fallbackTab instanceof HTMLButtonElement) {
        fallbackTab.click();
    }
}

function syncOverviewEssentialComparatives(prefs) {
    const container = document.getElementById(OVERVIEW_ESSENTIAL_COMPARATIVES_ID);
    if (!(container instanceof HTMLElement)) {
        return;
    }

    container.style.display = '';
}

function syncRelatoriosLayout(prefs = relatoriosCompleteDefaults) {
    Object.entries(TAB_SECTION_TOGGLE_MAP).forEach(([toggleId, section]) => {
        syncSectionTab(section, prefs[toggleId] !== false);
    });

    syncOverviewEssentialComparatives(prefs);
    syncActiveSectionTab();
}

function getOrCreateRelatoriosCustomizer() {
    const resolved = resolveRelatoriosCustomizerConfig();

    if (relatoriosCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: relatoriosCustomizer,
            resolved,
        };
    }

    relatoriosCustomizer = createPageCustomizer({
        storageKey: RELATORIOS_STORAGE_KEY,
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadRelatoriosPrefs,
        savePreferences: saveRelatoriosPrefs,
        onApply: syncRelatoriosLayout,
        modal: resolved.modalConfig,
    });

    return {
        customizer: relatoriosCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer, resolved } = getOrCreateRelatoriosCustomizer();

        if (!customizer) {
            syncRelatoriosLayout(resolved.essentialDefaults);
            return false;
        }

        if (!relatoriosCustomizerInitialized) {
            customizer.init();
            relatoriosCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!relatoriosCustomizerInitPromise) {
        relatoriosCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            relatoriosCustomizerInitPromise = null;
            initialize();
        });
    }
}
