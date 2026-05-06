/**
 * Perfil customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let perfilCustomizer = null;
let perfilCustomizerInitialized = false;
let perfilCustomizerInitPromise = null;

async function loadPerfilPrefs() {
    return fetchUiPagePreferences('perfil');
}

async function savePerfilPrefs(prefs) {
    await persistUiPagePreferences('perfil', prefs);
}

function resolvePerfilCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const perfilCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'perfil'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const perfilCustomizerDescriptor = perfilCustomizerCapabilities?.descriptor
        && typeof perfilCustomizerCapabilities.descriptor === 'object'
        ? perfilCustomizerCapabilities.descriptor
        : null;

    const sectionMap = perfilCustomizerDescriptor?.sectionMap
        && typeof perfilCustomizerDescriptor.sectionMap === 'object'
        ? perfilCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = perfilCustomizerCapabilities?.completePreferences
        && typeof perfilCustomizerCapabilities.completePreferences === 'object'
        ? perfilCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = perfilCustomizerCapabilities?.essentialPreferences
        && typeof perfilCustomizerCapabilities.essentialPreferences === 'object'
        ? perfilCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = perfilCustomizerDescriptor?.ids
        && typeof perfilCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: perfilCustomizerDescriptor.ids.overlay,
            openButtonId: perfilCustomizerDescriptor.trigger?.id || 'btnCustomizePerfil',
            closeButtonId: perfilCustomizerDescriptor.ids.close,
            saveButtonId: perfilCustomizerDescriptor.ids.save,
            presetEssentialButtonId: perfilCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: perfilCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: perfilCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreatePerfilCustomizer() {
    const resolved = resolvePerfilCustomizerConfig();

    if (perfilCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: perfilCustomizer,
            resolved,
        };
    }

    perfilCustomizer = createPageCustomizer({
        storageKey: 'lk_perfil_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadPerfilPrefs,
        savePreferences: savePerfilPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: perfilCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreatePerfilCustomizer();

        if (!customizer) {
            return false;
        }

        if (!perfilCustomizerInitialized) {
            customizer.init();
            perfilCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!perfilCustomizerInitPromise) {
        perfilCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            perfilCustomizerInitPromise = null;
            initialize();
        });
    }
}
