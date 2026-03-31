/**
 * Sysadmin customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleSysStats: 'sysStatsGrid',
    toggleSysTabs: 'sysTabsNav',
    toggleSysDashboard: 'panel-dashboard',
    toggleSysFeedback: 'panel-feedback'
};

const COMPLETE_DEFAULTS = {
    toggleSysStats: true,
    toggleSysTabs: true,
    toggleSysDashboard: true,
    toggleSysFeedback: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleSysStats: false,
    toggleSysFeedback: false
};

async function loadSysadminPrefs() {
    return fetchUiPagePreferences('sysadmin');
}

async function saveSysadminPrefs(prefs) {
    await persistUiPagePreferences('sysadmin', prefs);
}

const sysadminCustomizer = createPageCustomizer({
    storageKey: 'lk_sysadmin_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadSysadminPrefs,
    savePreferences: saveSysadminPrefs,
    modal: {
        overlayId: 'sysadminCustomizeModalOverlay',
        openButtonId: 'btnCustomizeSysadmin',
        closeButtonId: 'btnCloseCustomizeSysadmin',
        saveButtonId: 'btnSaveCustomizeSysadmin',
        presetEssentialButtonId: 'btnPresetEssencialSysadmin',
        presetCompleteButtonId: 'btnPresetCompletoSysadmin'
    }
});

export function initCustomize() {
    sysadminCustomizer.init();
}
