/**
 * Cartoes customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { Modules } from './state.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleCartoesHero: 'cartoesHero',
    toggleCartoesKpis: 'cartoesKpis',
    toggleCartoesToolbar: 'cartoesToolbar'
};

const COMPLETE_DEFAULTS = {
    toggleCartoesHero: true,
    toggleCartoesKpis: true,
    toggleCartoesToolbar: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleCartoesKpis: false,
    toggleCartoesToolbar: false
};

async function loadCartoesPrefs() {
    return fetchUiPagePreferences('cartoes');
}

async function saveCartoesPrefs(prefs) {
    await persistUiPagePreferences('cartoes', prefs);
}

const cartoesCustomizer = createPageCustomizer({
    storageKey: 'lk_cartoes_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadCartoesPrefs,
    savePreferences: saveCartoesPrefs,
    modal: {
        overlayId: 'cartoesCustomizeModalOverlay',
        openButtonId: 'btnCustomizeCartoes',
        closeButtonId: 'btnCloseCustomizeCartoes',
        saveButtonId: 'btnSaveCustomizeCartoes',
        presetEssentialButtonId: 'btnPresetEssencialCartoes',
        presetCompleteButtonId: 'btnPresetCompletoCartoes'
    }
});

export function initCustomize() {
    cartoesCustomizer.init();
}

Modules.Customize = {
    init: initCustomize,
    open: cartoesCustomizer.open,
    close: cartoesCustomizer.close
};

