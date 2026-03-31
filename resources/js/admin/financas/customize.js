/**
 * Financas customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleFinSummary: 'finSummarySection',
    toggleFinOrcActions: 'finOrcActionsSection',
    toggleFinMetasActions: 'finMetasActionsSection',
    toggleFinInsights: 'insightsSection'
};

const COMPLETE_DEFAULTS = {
    toggleFinSummary: true,
    toggleFinOrcActions: true,
    toggleFinMetasActions: true,
    toggleFinInsights: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleFinSummary: false,
    toggleFinInsights: false
};

async function loadFinancasPrefs() {
    return fetchUiPagePreferences('financas');
}

async function saveFinancasPrefs(prefs) {
    await persistUiPagePreferences('financas', prefs);
}

const financasCustomizer = createPageCustomizer({
    storageKey: 'lk_financas_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadFinancasPrefs,
    savePreferences: saveFinancasPrefs,
    modal: {
        overlayId: 'financasCustomizeModalOverlay',
        openButtonId: 'btnCustomizeFinancas',
        closeButtonId: 'btnCloseCustomizeFinancas',
        saveButtonId: 'btnSaveCustomizeFinancas',
        presetEssentialButtonId: 'btnPresetEssencialFinancas',
        presetCompleteButtonId: 'btnPresetCompletoFinancas'
    }
});

export function initCustomize() {
    financasCustomizer.init();
}

