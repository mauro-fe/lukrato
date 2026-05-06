/**
 * Metas customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let metasCustomizer = null;
let metasCustomizerInitialized = false;
let metasCustomizerInitPromise = null;

async function loadMetasPrefs() {
    return fetchUiPagePreferences('metas');
}

async function saveMetasPrefs(prefs) {
    await persistUiPagePreferences('metas', prefs);
}

function resolveMetasCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const metasCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'metas'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const metasCustomizerDescriptor = metasCustomizerCapabilities?.descriptor
        && typeof metasCustomizerCapabilities.descriptor === 'object'
        ? metasCustomizerCapabilities.descriptor
        : null;

    const sectionMap = metasCustomizerDescriptor?.sectionMap
        && typeof metasCustomizerDescriptor.sectionMap === 'object'
        ? metasCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = metasCustomizerCapabilities?.completePreferences
        && typeof metasCustomizerCapabilities.completePreferences === 'object'
        ? metasCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = metasCustomizerCapabilities?.essentialPreferences
        && typeof metasCustomizerCapabilities.essentialPreferences === 'object'
        ? metasCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = metasCustomizerDescriptor?.ids
        && typeof metasCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: metasCustomizerDescriptor.ids.overlay,
            openButtonId: metasCustomizerDescriptor.trigger?.id || 'btnCustomizeMetas',
            closeButtonId: metasCustomizerDescriptor.ids.close,
            saveButtonId: metasCustomizerDescriptor.ids.save,
            presetEssentialButtonId: metasCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: metasCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: metasCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateMetasCustomizer() {
    const resolved = resolveMetasCustomizerConfig();

    if (metasCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: metasCustomizer,
            resolved,
        };
    }

    metasCustomizer = createPageCustomizer({
        storageKey: 'lk_metas_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadMetasPrefs,
        savePreferences: saveMetasPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: metasCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreateMetasCustomizer();

        if (!customizer) {
            return false;
        }

        if (!metasCustomizerInitialized) {
            customizer.init();
            metasCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!metasCustomizerInitPromise) {
        metasCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            metasCustomizerInitPromise = null;
            initialize();
        });
    }
}

