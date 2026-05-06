/**
 * Sysadmin customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let sysadminCustomizer = null;
let sysadminCustomizerInitialized = false;
let sysadminCustomizerInitPromise = null;

async function loadSysadminPrefs() {
    return fetchUiPagePreferences('sysadmin');
}

async function saveSysadminPrefs(prefs) {
    await persistUiPagePreferences('sysadmin', prefs);
}

function resolveSysadminCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const sysadminCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'sysadmin'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const sysadminCustomizerDescriptor = sysadminCustomizerCapabilities?.descriptor
        && typeof sysadminCustomizerCapabilities.descriptor === 'object'
        ? sysadminCustomizerCapabilities.descriptor
        : null;

    const sectionMap = sysadminCustomizerDescriptor?.sectionMap
        && typeof sysadminCustomizerDescriptor.sectionMap === 'object'
        ? sysadminCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = sysadminCustomizerCapabilities?.completePreferences
        && typeof sysadminCustomizerCapabilities.completePreferences === 'object'
        ? sysadminCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = sysadminCustomizerCapabilities?.essentialPreferences
        && typeof sysadminCustomizerCapabilities.essentialPreferences === 'object'
        ? sysadminCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = sysadminCustomizerDescriptor?.ids
        && typeof sysadminCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: sysadminCustomizerDescriptor.ids.overlay,
            openButtonId: sysadminCustomizerDescriptor.trigger?.id || 'btnCustomizeSysadmin',
            closeButtonId: sysadminCustomizerDescriptor.ids.close,
            saveButtonId: sysadminCustomizerDescriptor.ids.save,
            presetEssentialButtonId: sysadminCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: sysadminCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: sysadminCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateSysadminCustomizer() {
    const resolved = resolveSysadminCustomizerConfig();

    if (sysadminCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: sysadminCustomizer,
            resolved,
        };
    }

    sysadminCustomizer = createPageCustomizer({
        storageKey: 'lk_sysadmin_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadSysadminPrefs,
        savePreferences: saveSysadminPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: sysadminCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreateSysadminCustomizer();

        if (!customizer) {
            return false;
        }

        if (!sysadminCustomizerInitialized) {
            customizer.init();
            sysadminCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!sysadminCustomizerInitPromise) {
        sysadminCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            sysadminCustomizerInitPromise = null;
            initialize();
        });
    }
}
