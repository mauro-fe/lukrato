/**
 * Lancamentos customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { Modules } from './state.js';
import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleLanExport: 'exportCard',
    toggleLanFilters: 'lanFiltersSection'
};

const COMPLETE_DEFAULTS = {
    toggleLanExport: true,
    toggleLanFilters: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleLanExport: false
};

async function loadLancamentosPrefs() {
    return fetchUiPagePreferences('lancamentos');
}

async function saveLancamentosPrefs(prefs) {
    await persistUiPagePreferences('lancamentos', prefs);
}

function isVisible(element) {
    return !!element && getComputedStyle(element).display !== 'none';
}

function syncVisibleChildren(container) {
    if (!container) return 0;

    const visibleCount = Array.from(container.children).filter(isVisible).length;
    container.dataset.visibleCount = String(visibleCount);
    container.style.display = visibleCount > 0 ? '' : 'none';
    return visibleCount;
}

function syncLancamentosLayout() {
    const overviewStage = document.querySelector('.lan-stage--overview');
    const overviewBottom = document.querySelector('.lan-overview-bottom');
    const bottomCount = syncVisibleChildren(overviewBottom);

    if (overviewStage) {
        const visibleBlocks = bottomCount > 0 ? 1 : 0;
        overviewStage.dataset.visibleCount = String(visibleBlocks);
        overviewStage.style.display = visibleBlocks > 0 ? '' : 'none';
    }
}

const lancamentosCustomizer = createPageCustomizer({
    storageKey: 'lk_lancamentos_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadLancamentosPrefs,
    savePreferences: saveLancamentosPrefs,
    onApply: syncLancamentosLayout,
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
