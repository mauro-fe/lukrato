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
    toggleContasKpis: 'contasKpis'
};

const COMPLETE_DEFAULTS = {
    toggleContasHero: true,
    toggleContasKpis: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleContasKpis: false
};

async function loadContasPrefs() {
    return fetchUiPagePreferences('contas');
}

async function saveContasPrefs(prefs) {
    await persistUiPagePreferences('contas', prefs);
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

function syncContasLayout() {
    const overviewStage = document.querySelector('.cont-stage--overview');
    const overviewTop = document.querySelector('.cont-overview-top');
    const topCount = syncVisibleChildren(overviewTop);

    if (overviewStage) {
        const visibleBlocks = topCount > 0 ? 1 : 0;
        overviewStage.dataset.visibleCount = String(visibleBlocks);
        overviewStage.style.display = visibleBlocks > 0 ? '' : 'none';
    }
}

const contasCustomizer = createPageCustomizer({
    storageKey: 'lk_contas_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadContasPrefs,
    savePreferences: saveContasPrefs,
    onApply: syncContasLayout,
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
