/**
 * Lancamentos customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { Modules } from './state.js';
import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleLanSummary: 'lanSummaryStrip',
    toggleLanExport: 'exportCard',
    toggleLanFilters: 'lanFiltersSection'
};

const COMPLETE_DEFAULTS = {
    toggleLanSummary: true,
    toggleLanExport: true,
    toggleLanFilters: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleLanSummary: false,
    toggleLanExport: false
};

async function loadLancamentosPrefs() {
    return fetchUiPagePreferences('lancamentos');
}

async function saveLancamentosPrefs(prefs) {
    await persistUiPagePreferences('lancamentos', prefs);
}

const lancamentosCustomizer = createPageCustomizer({
    storageKey: 'lk_lancamentos_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadLancamentosPrefs,
    savePreferences: saveLancamentosPrefs,
    modal: {
        overlayId: 'lanCustomizeModalOverlay',
        openButtonId: 'btnCustomizeLancamentos',
        closeButtonId: 'btnCloseCustomizeLancamentos',
        saveButtonId: 'btnSaveCustomizeLancamentos',
        presetEssentialButtonId: 'btnPresetEssencialLancamentos',
        presetCompleteButtonId: 'btnPresetCompletoLancamentos'
    }
});

export function initCustomize() {
    lancamentosCustomizer.init();
}

Modules.Customize = {
    init: initCustomize,
    open: lancamentosCustomizer.open,
    close: lancamentosCustomizer.close
};
