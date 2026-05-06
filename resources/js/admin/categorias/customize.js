/**
 * Categorias customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';
import { Modules } from './state.js';

let categoriasCustomizer = null;
let categoriasCustomizerInitialized = false;
let categoriasCustomizerInitPromise = null;

async function loadCategoriasPrefs() {
    return fetchUiPagePreferences('categorias');
}

async function saveCategoriasPrefs(prefs) {
    await persistUiPagePreferences('categorias', prefs);
}

function resolveCategoriasCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const categoriasCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'categorias'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const categoriasCustomizerDescriptor = categoriasCustomizerCapabilities?.descriptor
        && typeof categoriasCustomizerCapabilities.descriptor === 'object'
        ? categoriasCustomizerCapabilities.descriptor
        : null;

    const sectionMap = categoriasCustomizerDescriptor?.sectionMap
        && typeof categoriasCustomizerDescriptor.sectionMap === 'object'
        ? categoriasCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = categoriasCustomizerCapabilities?.completePreferences
        && typeof categoriasCustomizerCapabilities.completePreferences === 'object'
        ? categoriasCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = categoriasCustomizerCapabilities?.essentialPreferences
        && typeof categoriasCustomizerCapabilities.essentialPreferences === 'object'
        ? categoriasCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = categoriasCustomizerDescriptor?.ids
        && typeof categoriasCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: categoriasCustomizerDescriptor.ids.overlay,
            openButtonId: categoriasCustomizerDescriptor.trigger?.id || 'btnCustomizeCategorias',
            closeButtonId: categoriasCustomizerDescriptor.ids.close,
            saveButtonId: categoriasCustomizerDescriptor.ids.save,
            presetEssentialButtonId: categoriasCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: categoriasCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: categoriasCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateCategoriasCustomizer() {
    const resolved = resolveCategoriasCustomizerConfig();

    if (categoriasCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: categoriasCustomizer,
            resolved,
        };
    }

    categoriasCustomizer = createPageCustomizer({
        storageKey: 'lk_categorias_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadCategoriasPrefs,
        savePreferences: saveCategoriasPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: categoriasCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreateCategoriasCustomizer();

        if (!customizer) {
            return false;
        }

        if (!categoriasCustomizerInitialized) {
            customizer.init();
            categoriasCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!categoriasCustomizerInitPromise) {
        categoriasCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            categoriasCustomizerInitPromise = null;
            initialize();
        });
    }
}

Modules.Customize = {
    init: initCustomize,
    open: () => {
        const { customizer } = getOrCreateCategoriasCustomizer();
        if (customizer?.open) {
            customizer.open();
            return;
        }

        void ensureRuntimeConfig({}, { silent: true }).finally(() => {
            const { customizer: nextCustomizer } = getOrCreateCategoriasCustomizer();
            nextCustomizer?.open?.();
        });
    },
    close: () => {
        const { customizer } = getOrCreateCategoriasCustomizer();
        customizer?.close?.();
    }
};

