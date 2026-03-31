/**
 * Orcamento customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleOrcSummary: 'summaryOrcamentos',
    toggleOrcFocus: 'orcFocusPanel',
    toggleOrcToolbar: 'orcToolbarSection'
};

const COMPLETE_DEFAULTS = {
    toggleOrcSummary: true,
    toggleOrcFocus: true,
    toggleOrcToolbar: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleOrcSummary: false,
    toggleOrcFocus: false
};

async function loadOrcamentoPrefs() {
    return fetchUiPagePreferences('orcamento');
}

async function saveOrcamentoPrefs(prefs) {
    await persistUiPagePreferences('orcamento', prefs);
}

const orcamentoCustomizer = createPageCustomizer({
    storageKey: 'lk_orcamento_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadOrcamentoPrefs,
    savePreferences: saveOrcamentoPrefs,
    modal: {
        overlayId: 'orcamentoCustomizeModalOverlay',
        openButtonId: 'btnCustomizeOrcamento',
        closeButtonId: 'btnCloseCustomizeOrcamento',
        saveButtonId: 'btnSaveCustomizeOrcamento',
        presetEssentialButtonId: 'btnPresetEssencialOrcamento',
        presetCompleteButtonId: 'btnPresetCompletoOrcamento'
    }
});

export function initCustomize() {
    orcamentoCustomizer.init();
}

