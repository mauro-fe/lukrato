/**
 * Categorias customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { Modules } from './state.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleCategoriasKpis: 'categoriasKpis',
    toggleCategoriasCreateCard: 'categoriasCreateCard'
};

const COMPLETE_DEFAULTS = {
    toggleCategoriasKpis: true,
    toggleCategoriasCreateCard: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleCategoriasKpis: false
};

async function loadCategoriasPrefs() {
    return fetchUiPagePreferences('categorias');
}

async function saveCategoriasPrefs(prefs) {
    await persistUiPagePreferences('categorias', prefs);
}

const categoriasCustomizer = createPageCustomizer({
    storageKey: 'lk_categorias_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadCategoriasPrefs,
    savePreferences: saveCategoriasPrefs,
    modal: {
        overlayId: 'categoriasCustomizeModalOverlay',
        openButtonId: 'btnCustomizeCategorias',
        closeButtonId: 'btnCloseCustomizeCategorias',
        saveButtonId: 'btnSaveCustomizeCategorias',
        presetEssentialButtonId: 'btnPresetEssencialCategorias',
        presetCompleteButtonId: 'btnPresetCompletoCategorias'
    }
});

export function initCustomize() {
    categoriasCustomizer.init();
}

Modules.Customize = {
    init: initCustomize,
    open: categoriasCustomizer.open,
    close: categoriasCustomizer.close
};

