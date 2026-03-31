/**
 * Gamification customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleGamHeader: 'gamHeaderSection',
    toggleGamProgress: 'gamProgressSection',
    toggleGamAchievements: 'gamAchievementsSection',
    toggleGamHistory: 'gamHistorySection',
    toggleGamLeaderboard: 'gamLeaderboardSection'
};

const COMPLETE_DEFAULTS = {
    toggleGamHeader: true,
    toggleGamProgress: true,
    toggleGamAchievements: true,
    toggleGamHistory: true,
    toggleGamLeaderboard: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS,
    toggleGamHistory: false,
    toggleGamLeaderboard: false
};

async function loadGamificationPrefs() {
    return fetchUiPagePreferences('gamification');
}

async function saveGamificationPrefs(prefs) {
    await persistUiPagePreferences('gamification', prefs);
}

const gamificationCustomizer = createPageCustomizer({
    storageKey: 'lk_gamification_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadGamificationPrefs,
    savePreferences: saveGamificationPrefs,
    modal: {
        overlayId: 'gamificationCustomizeModalOverlay',
        openButtonId: 'btnCustomizeGamification',
        closeButtonId: 'btnCloseCustomizeGamification',
        saveButtonId: 'btnSaveCustomizeGamification',
        presetEssentialButtonId: 'btnPresetEssencialGamification',
        presetCompleteButtonId: 'btnPresetCompletoGamification'
    }
});

export function initCustomize() {
    gamificationCustomizer.init();
}
