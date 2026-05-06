/**
 * Cartoes customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';
import { Modules } from './state.js';

let cartoesCustomizer = null;
let cartoesCustomizerInitialized = false;
let cartoesCustomizerInitPromise = null;

async function loadCartoesPrefs() {
    return fetchUiPagePreferences('cartoes');
}

async function saveCartoesPrefs(prefs) {
    await persistUiPagePreferences('cartoes', prefs);
}

function resolveCartoesCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const cartoesCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'cartoes'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const cartoesCustomizerDescriptor = cartoesCustomizerCapabilities?.descriptor
        && typeof cartoesCustomizerCapabilities.descriptor === 'object'
        ? cartoesCustomizerCapabilities.descriptor
        : null;

    const sectionMap = cartoesCustomizerDescriptor?.sectionMap
        && typeof cartoesCustomizerDescriptor.sectionMap === 'object'
        ? cartoesCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = cartoesCustomizerCapabilities?.completePreferences
        && typeof cartoesCustomizerCapabilities.completePreferences === 'object'
        ? cartoesCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = cartoesCustomizerCapabilities?.essentialPreferences
        && typeof cartoesCustomizerCapabilities.essentialPreferences === 'object'
        ? cartoesCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = cartoesCustomizerDescriptor?.ids
        && typeof cartoesCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: cartoesCustomizerDescriptor.ids.overlay,
            openButtonId: cartoesCustomizerDescriptor.trigger?.id || 'btnCustomizeCartoes',
            closeButtonId: cartoesCustomizerDescriptor.ids.close,
            saveButtonId: cartoesCustomizerDescriptor.ids.save,
            presetEssentialButtonId: cartoesCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: cartoesCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: cartoesCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateCartoesCustomizer() {
    const resolved = resolveCartoesCustomizerConfig();

    if (cartoesCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: cartoesCustomizer,
            resolved,
        };
    }

    cartoesCustomizer = createPageCustomizer({
        storageKey: 'lk_cartoes_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadCartoesPrefs,
        savePreferences: saveCartoesPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: cartoesCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreateCartoesCustomizer();

        if (!customizer) {
            return false;
        }

        if (!cartoesCustomizerInitialized) {
            customizer.init();
            cartoesCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!cartoesCustomizerInitPromise) {
        cartoesCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            cartoesCustomizerInitPromise = null;
            initialize();
        });
    }
}

Modules.Customize = {
    init: initCustomize,
    open: () => {
        const { customizer } = getOrCreateCartoesCustomizer();
        if (customizer?.open) {
            customizer.open();
            return;
        }

        void ensureRuntimeConfig({}, { silent: true }).finally(() => {
            const { customizer: nextCustomizer } = getOrCreateCartoesCustomizer();
            nextCustomizer?.open?.();
        });
    },
    close: () => {
        const { customizer } = getOrCreateCartoesCustomizer();
        customizer?.close?.();
    }
};

