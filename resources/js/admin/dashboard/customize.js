/**
 * Dashboard customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { Modules } from './state.js';
import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> dashboard section ID */
const SECTION_MAP = {
    // Main
    toggleHealthScore: 'sectionHealthScore',
    toggleAiTip: 'sectionAiTip',
    toggleEvolucao: 'sectionEvolucao',
    toggleAlertas: 'sectionAlertas',
    toggleGrafico: 'chart-section',
    togglePrevisao: 'sectionPrevisao',
    // Extra grid
    toggleMetas: 'sectionMetas',
    toggleCartoes: 'sectionCartoes',
    toggleContas: 'sectionContas',
    toggleOrcamentos: 'sectionOrcamentos',
    toggleFaturas: 'sectionFaturas',
    // Standalone
    toggleGamificacao: 'sectionGamificacao'
};

const COMPLETE_DEFAULTS = {
    toggleHealthScore: true,
    toggleAiTip: true,
    toggleEvolucao: true,
    toggleAlertas: true,
    toggleGrafico: true,
    togglePrevisao: true,
    toggleMetas: false,
    toggleCartoes: false,
    toggleContas: false,
    toggleOrcamentos: false,
    toggleFaturas: false,
    toggleGamificacao: false
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleHealthScore: false,
    toggleAiTip: false,
    toggleEvolucao: false,
    togglePrevisao: false
};

async function loadDashboardPrefs() {
    return fetchUiPagePreferences('dashboard');
}

async function saveDashboardPrefs(prefs) {
    await persistUiPagePreferences('dashboard', prefs);
}

const dashboardCustomizer = createPageCustomizer({
    storageKey: 'lk_dashboard_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    gridContainerId: 'optionalGrid',
    gridToggleKeys: ['toggleMetas', 'toggleCartoes', 'toggleContas', 'toggleOrcamentos', 'toggleFaturas'],
    loadPreferences: loadDashboardPrefs,
    savePreferences: saveDashboardPrefs
});

export function initCustomize() {
    dashboardCustomizer.init();
}

Modules.Customize = {
    init: initCustomize,
    open: dashboardCustomizer.open,
    close: dashboardCustomizer.close
};

