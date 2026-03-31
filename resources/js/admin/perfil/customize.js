/**
 * Perfil customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    togglePerfilHeader: 'profileHeaderSection',
    togglePerfilTabs: 'profileTabsSection',
    togglePerfilConfigShortcut: 'profileConfigShortcutSection'
};

const COMPLETE_DEFAULTS = {
    togglePerfilHeader: true,
    togglePerfilTabs: true,
    togglePerfilConfigShortcut: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    togglePerfilConfigShortcut: false
};

async function loadPerfilPrefs() {
    return fetchUiPagePreferences('perfil');
}

async function savePerfilPrefs(prefs) {
    await persistUiPagePreferences('perfil', prefs);
}

const perfilCustomizer = createPageCustomizer({
    storageKey: 'lk_perfil_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadPerfilPrefs,
    savePreferences: savePerfilPrefs,
    modal: {
        overlayId: 'perfilCustomizeModalOverlay',
        openButtonId: 'btnCustomizePerfil',
        closeButtonId: 'btnCloseCustomizePerfil',
        saveButtonId: 'btnSaveCustomizePerfil',
        presetEssentialButtonId: 'btnPresetEssencialPerfil',
        presetCompleteButtonId: 'btnPresetCompletoPerfil'
    }
});

export function initCustomize() {
    perfilCustomizer.init();
}

