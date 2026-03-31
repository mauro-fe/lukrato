/**
 * Faturas customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { Modules } from './state.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleFaturasHero: 'faturasHero',
    toggleFaturasFiltros: 'faturasFilters',
    toggleFaturasViewToggle: 'faturasViewToggle'
};

const COMPLETE_DEFAULTS = {
    toggleFaturasHero: true,
    toggleFaturasFiltros: true,
    toggleFaturasViewToggle: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleFaturasFiltros: false,
    toggleFaturasViewToggle: false
};

async function loadFaturasPrefs() {
    return fetchUiPagePreferences('faturas');
}

async function saveFaturasPrefs(prefs) {
    await persistUiPagePreferences('faturas', prefs);
}

const faturasCustomizer = createPageCustomizer({
    storageKey: 'lk_faturas_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadFaturasPrefs,
    savePreferences: saveFaturasPrefs,
    modal: {
        overlayId: 'faturasCustomizeModalOverlay',
        openButtonId: 'btnCustomizeFaturas',
        closeButtonId: 'btnCloseCustomizeFaturas',
        saveButtonId: 'btnSaveCustomizeFaturas',
        presetEssentialButtonId: 'btnPresetEssencialFaturas',
        presetCompleteButtonId: 'btnPresetCompletoFaturas'
    }
});

export function initCustomize() {
    faturasCustomizer.init();
}

Modules.Customize = {
    init: initCustomize,
    open: faturasCustomizer.open,
    close: faturasCustomizer.close
};

