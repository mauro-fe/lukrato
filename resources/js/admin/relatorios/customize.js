/**
 * Relatorios customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleRelQuickStats: 'relQuickStats',
    toggleRelOverviewCharts: 'relOverviewChartsRow',
    toggleRelControls: 'relControlsRow'
};

const COMPLETE_DEFAULTS = {
    toggleRelQuickStats: true,
    toggleRelOverviewCharts: true,
    toggleRelControls: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleRelQuickStats: false,
    toggleRelControls: false
};

async function loadRelatoriosPrefs() {
    return fetchUiPagePreferences('relatorios');
}

async function saveRelatoriosPrefs(prefs) {
    await persistUiPagePreferences('relatorios', prefs);
}

const relatoriosCustomizer = createPageCustomizer({
    storageKey: 'lk_relatorios_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadRelatoriosPrefs,
    savePreferences: saveRelatoriosPrefs,
    modal: {
        overlayId: 'relatoriosCustomizeModalOverlay',
        openButtonId: 'btnCustomizeRelatorios',
        closeButtonId: 'btnCloseCustomizeRelatorios',
        saveButtonId: 'btnSaveCustomizeRelatorios',
        presetEssentialButtonId: 'btnPresetEssencialRelatorios',
        presetCompleteButtonId: 'btnPresetCompletoRelatorios'
    }
});

export function initCustomize() {
    relatoriosCustomizer.init();
}

