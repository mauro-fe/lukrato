/**
 * Metas customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleMetasSummary: 'summaryMetas',
    toggleMetasFocus: 'metFocusPanel',
    toggleMetasToolbar: 'metToolbarSection'
};

const COMPLETE_DEFAULTS = {
    toggleMetasSummary: true,
    toggleMetasFocus: true,
    toggleMetasToolbar: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleMetasSummary: false,
    toggleMetasFocus: false
};

async function loadMetasPrefs() {
    return fetchUiPagePreferences('metas');
}

async function saveMetasPrefs(prefs) {
    await persistUiPagePreferences('metas', prefs);
}

const metasCustomizer = createPageCustomizer({
    storageKey: 'lk_metas_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadMetasPrefs,
    savePreferences: saveMetasPrefs,
    modal: {
        overlayId: 'metasCustomizeModalOverlay',
        openButtonId: 'btnCustomizeMetas',
        closeButtonId: 'btnCloseCustomizeMetas',
        saveButtonId: 'btnSaveCustomizeMetas',
        presetEssentialButtonId: 'btnPresetEssencialMetas',
        presetCompleteButtonId: 'btnPresetCompletoMetas'
    }
});

export function initCustomize() {
    metasCustomizer.init();
}

