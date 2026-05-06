/**
 * Financas customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let financasCustomizer = null;
let financasCustomizerInitialized = false;
let financasCustomizerInitPromise = null;

async function loadFinancasPrefs() {
    return fetchUiPagePreferences('financas');
}

async function saveFinancasPrefs(prefs) {
    await persistUiPagePreferences('financas', prefs);
}

function resolveFinancasCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const financasCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'financas'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const financasCustomizerDescriptor = financasCustomizerCapabilities?.descriptor
        && typeof financasCustomizerCapabilities.descriptor === 'object'
        ? financasCustomizerCapabilities.descriptor
        : null;

    const sectionMap = financasCustomizerDescriptor?.sectionMap
        && typeof financasCustomizerDescriptor.sectionMap === 'object'
        ? financasCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = financasCustomizerCapabilities?.completePreferences
        && typeof financasCustomizerCapabilities.completePreferences === 'object'
        ? financasCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = financasCustomizerCapabilities?.essentialPreferences
        && typeof financasCustomizerCapabilities.essentialPreferences === 'object'
        ? financasCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = financasCustomizerDescriptor?.ids
        && typeof financasCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: financasCustomizerDescriptor.ids.overlay,
            openButtonId: financasCustomizerDescriptor.trigger?.id || 'btnCustomizeFinancas',
            closeButtonId: financasCustomizerDescriptor.ids.close,
            saveButtonId: financasCustomizerDescriptor.ids.save,
            presetEssentialButtonId: financasCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: financasCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: financasCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateFinancasCustomizer() {
    const resolved = resolveFinancasCustomizerConfig();

    if (financasCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: financasCustomizer,
            resolved,
        };
    }

    financasCustomizer = createPageCustomizer({
        storageKey: 'lk_financas_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadFinancasPrefs,
        savePreferences: saveFinancasPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: financasCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreateFinancasCustomizer();

        if (!customizer) {
            return false;
        }

        if (!financasCustomizerInitialized) {
            customizer.init();
            financasCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!financasCustomizerInitPromise) {
        financasCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            financasCustomizerInitPromise = null;
            initialize();
        });
    }
}

