/**
 * Contas customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { Modules } from './state.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleContasHero: 'contasHero',
    toggleContasKpis: 'contasKpis',
    toggleContasDistribution: 'contasDistributionCard'
};

const COMPLETE_DEFAULTS = {
    toggleContasHero: true,
    toggleContasKpis: true,
    toggleContasDistribution: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleContasKpis: false,
    toggleContasDistribution: false
};

async function loadContasPrefs() {
    return fetchUiPagePreferences('contas');
}

async function saveContasPrefs(prefs) {
    await persistUiPagePreferences('contas', prefs);
}

const contasCustomizer = createPageCustomizer({
    storageKey: 'lk_contas_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadContasPrefs,
    savePreferences: saveContasPrefs,
    modal: {
        overlayId: 'contasCustomizeModalOverlay',
        openButtonId: 'btnCustomizeContas',
        closeButtonId: 'btnCloseCustomizeContas',
        saveButtonId: 'btnSaveCustomizeContas',
        presetEssentialButtonId: 'btnPresetEssencialContas',
        presetCompleteButtonId: 'btnPresetCompletoContas'
    }
});

export function initCustomize() {
    contasCustomizer.init();
}

Modules.Customize = {
    init: initCustomize,
    open: contasCustomizer.open,
    close: contasCustomizer.close
};
