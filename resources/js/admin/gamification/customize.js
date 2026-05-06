/**
 * Gamification customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let gamificationCustomizer = null;
let gamificationCustomizerInitialized = false;
let gamificationCustomizerInitPromise = null;

async function loadGamificationPrefs() {
    return fetchUiPagePreferences('gamification');
}

async function saveGamificationPrefs(prefs) {
    await persistUiPagePreferences('gamification', prefs);
}

function resolveGamificationCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const gamificationCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'gamification'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const gamificationCustomizerDescriptor = gamificationCustomizerCapabilities?.descriptor
        && typeof gamificationCustomizerCapabilities.descriptor === 'object'
        ? gamificationCustomizerCapabilities.descriptor
        : null;

    const sectionMap = gamificationCustomizerDescriptor?.sectionMap
        && typeof gamificationCustomizerDescriptor.sectionMap === 'object'
        ? gamificationCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = gamificationCustomizerCapabilities?.completePreferences
        && typeof gamificationCustomizerCapabilities.completePreferences === 'object'
        ? gamificationCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = gamificationCustomizerCapabilities?.essentialPreferences
        && typeof gamificationCustomizerCapabilities.essentialPreferences === 'object'
        ? gamificationCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = gamificationCustomizerDescriptor?.ids
        && typeof gamificationCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: gamificationCustomizerDescriptor.ids.overlay,
            openButtonId: gamificationCustomizerDescriptor.trigger?.id || 'btnCustomizeGamification',
            closeButtonId: gamificationCustomizerDescriptor.ids.close,
            saveButtonId: gamificationCustomizerDescriptor.ids.save,
            presetEssentialButtonId: gamificationCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: gamificationCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: gamificationCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateGamificationCustomizer() {
    const resolved = resolveGamificationCustomizerConfig();

    if (gamificationCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: gamificationCustomizer,
            resolved,
        };
    }

    gamificationCustomizer = createPageCustomizer({
        storageKey: 'lk_gamification_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadGamificationPrefs,
        savePreferences: saveGamificationPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: gamificationCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreateGamificationCustomizer();

        if (!customizer) {
            return false;
        }

        if (!gamificationCustomizerInitialized) {
            customizer.init();
            gamificationCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!gamificationCustomizerInitPromise) {
        gamificationCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            gamificationCustomizerInitPromise = null;
            initialize();
        });
    }
}
