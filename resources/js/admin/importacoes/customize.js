/**
 * Importacoes customization entry.
 * Keeps the first access focused on the main upload and preview workflow.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let importacoesCustomizer = null;
let importacoesCustomizerInitialized = false;
let importacoesCustomizerInitPromise = null;
let importacoesCompleteDefaults = {};

async function loadImportacoesPrefs() {
    return fetchUiPagePreferences('importacoes');
}

async function saveImportacoesPrefs(prefs) {
    await persistUiPagePreferences('importacoes', prefs);
}

function syncImportacoesLayout(prefs = importacoesCompleteDefaults) {
    const layout = document.querySelector('.imp-index-layout');
    if (!layout) return;

    const sidebarVisible = !!prefs.toggleImpSidebar;
    layout.classList.toggle('imp-index-layout--single-column', !sidebarVisible);
}

function resolveImportacoesCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const importacoesCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'importacoes'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const importacoesCustomizerDescriptor = importacoesCustomizerCapabilities?.descriptor
        && typeof importacoesCustomizerCapabilities.descriptor === 'object'
        ? importacoesCustomizerCapabilities.descriptor
        : null;

    const sectionMap = importacoesCustomizerDescriptor?.sectionMap
        && typeof importacoesCustomizerDescriptor.sectionMap === 'object'
        ? importacoesCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = importacoesCustomizerCapabilities?.completePreferences
        && typeof importacoesCustomizerCapabilities.completePreferences === 'object'
        ? importacoesCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = importacoesCustomizerCapabilities?.essentialPreferences
        && typeof importacoesCustomizerCapabilities.essentialPreferences === 'object'
        ? importacoesCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = importacoesCustomizerDescriptor?.ids
        && typeof importacoesCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: importacoesCustomizerDescriptor.ids.overlay,
            openButtonId: importacoesCustomizerDescriptor.trigger?.id || 'btnCustomizeImportacoes',
            closeButtonId: importacoesCustomizerDescriptor.ids.close,
            saveButtonId: importacoesCustomizerDescriptor.ids.save,
            presetEssentialButtonId: importacoesCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: importacoesCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: importacoesCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateImportacoesCustomizer() {
    const resolved = resolveImportacoesCustomizerConfig();

    if (importacoesCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: importacoesCustomizer,
            resolved,
        };
    }

    importacoesCompleteDefaults = resolved.completeDefaults;

    importacoesCustomizer = createPageCustomizer({
        storageKey: 'lk_importacoes_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadImportacoesPrefs,
        savePreferences: saveImportacoesPrefs,
        onApply: syncImportacoesLayout,
        modal: resolved.modalConfig,
    });

    return {
        customizer: importacoesCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer, resolved } = getOrCreateImportacoesCustomizer();

        if (!customizer) {
            return false;
        }

        if (!importacoesCustomizerInitialized) {
            importacoesCompleteDefaults = resolved.completeDefaults;
            customizer.init();
            importacoesCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!importacoesCustomizerInitPromise) {
        importacoesCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            importacoesCustomizerInitPromise = null;
            initialize();
        });
    }
}