/**
 * Faturas customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';
import { Modules } from './state.js';

let faturasCustomizer = null;
let faturasCustomizerInitialized = false;
let faturasCustomizerInitPromise = null;

async function loadFaturasPrefs() {
    return fetchUiPagePreferences('faturas');
}

async function saveFaturasPrefs(prefs) {
    await persistUiPagePreferences('faturas', prefs);
}

function resolveFaturasCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const faturasCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'faturas'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const faturasCustomizerDescriptor = faturasCustomizerCapabilities?.descriptor
        && typeof faturasCustomizerCapabilities.descriptor === 'object'
        ? faturasCustomizerCapabilities.descriptor
        : null;

    const sectionMap = faturasCustomizerDescriptor?.sectionMap
        && typeof faturasCustomizerDescriptor.sectionMap === 'object'
        ? faturasCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = faturasCustomizerCapabilities?.completePreferences
        && typeof faturasCustomizerCapabilities.completePreferences === 'object'
        ? faturasCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = faturasCustomizerCapabilities?.essentialPreferences
        && typeof faturasCustomizerCapabilities.essentialPreferences === 'object'
        ? faturasCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = faturasCustomizerDescriptor?.ids
        && typeof faturasCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: faturasCustomizerDescriptor.ids.overlay,
            openButtonId: faturasCustomizerDescriptor.trigger?.id || 'btnCustomizeFaturas',
            closeButtonId: faturasCustomizerDescriptor.ids.close,
            saveButtonId: faturasCustomizerDescriptor.ids.save,
            presetEssentialButtonId: faturasCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: faturasCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: faturasCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateFaturasCustomizer() {
    const resolved = resolveFaturasCustomizerConfig();

    if (faturasCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: faturasCustomizer,
            resolved,
        };
    }

    faturasCustomizer = createPageCustomizer({
        storageKey: 'lk_faturas_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadFaturasPrefs,
        savePreferences: saveFaturasPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: faturasCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreateFaturasCustomizer();

        if (!customizer) {
            return false;
        }

        if (!faturasCustomizerInitialized) {
            customizer.init();
            faturasCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!faturasCustomizerInitPromise) {
        faturasCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            faturasCustomizerInitPromise = null;
            initialize();
        });
    }
}

Modules.Customize = {
    init: initCustomize,
    open: () => {
        const { customizer } = getOrCreateFaturasCustomizer();
        if (customizer?.open) {
            customizer.open();
            return;
        }

        void ensureRuntimeConfig({}, { silent: true }).finally(() => {
            const { customizer: nextCustomizer } = getOrCreateFaturasCustomizer();
            nextCustomizer?.open?.();
        });
    },
    close: () => {
        const { customizer } = getOrCreateFaturasCustomizer();
        customizer?.close?.();
    }
};

