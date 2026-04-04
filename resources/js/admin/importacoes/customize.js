/**
 * Importacoes customization entry.
 * Keeps the first access focused on the main upload and preview workflow.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

const SECTION_MAP = {
    toggleImpHero: 'impHeroSection',
    toggleImpSidebar: 'impIndexSideSection'
};

const COMPLETE_DEFAULTS = {
    toggleImpHero: true,
    toggleImpSidebar: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleImpHero: false,
    toggleImpSidebar: false
};

async function loadImportacoesPrefs() {
    return fetchUiPagePreferences('importacoes');
}

async function saveImportacoesPrefs(prefs) {
    await persistUiPagePreferences('importacoes', prefs);
}

function syncImportacoesLayout(prefs = COMPLETE_DEFAULTS) {
    const layout = document.querySelector('.imp-index-layout');
    if (!layout) return;

    const sidebarVisible = !!prefs.toggleImpSidebar;
    layout.classList.toggle('imp-index-layout--single-column', !sidebarVisible);
}

const importacoesCustomizer = createPageCustomizer({
    storageKey: 'lk_importacoes_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadImportacoesPrefs,
    savePreferences: saveImportacoesPrefs,
    onApply: syncImportacoesLayout,
    modal: {
        overlayId: 'importacoesCustomizeModalOverlay',
        openButtonId: 'btnCustomizeImportacoes',
        closeButtonId: 'btnCloseCustomizeImportacoes',
        saveButtonId: 'btnSaveCustomizeImportacoes',
        presetEssentialButtonId: 'btnPresetEssencialImportacoes',
        presetCompleteButtonId: 'btnPresetCompletoImportacoes'
    }
});

export function initCustomize() {
    importacoesCustomizer.init();
}