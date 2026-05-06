/**
 * Orcamento customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let orcamentoCustomizer = null;
let orcamentoCustomizerInitialized = false;
let orcamentoCustomizerInitPromise = null;

async function loadOrcamentoPrefs() {
    return fetchUiPagePreferences('orcamento');
}

async function saveOrcamentoPrefs(prefs) {
    await persistUiPagePreferences('orcamento', prefs);
}

function resolveOrcamentoCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const orcamentoCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'orcamento'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const orcamentoCustomizerDescriptor = orcamentoCustomizerCapabilities?.descriptor
        && typeof orcamentoCustomizerCapabilities.descriptor === 'object'
        ? orcamentoCustomizerCapabilities.descriptor
        : null;

    const sectionMap = orcamentoCustomizerDescriptor?.sectionMap
        && typeof orcamentoCustomizerDescriptor.sectionMap === 'object'
        ? orcamentoCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = orcamentoCustomizerCapabilities?.completePreferences
        && typeof orcamentoCustomizerCapabilities.completePreferences === 'object'
        ? orcamentoCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = orcamentoCustomizerCapabilities?.essentialPreferences
        && typeof orcamentoCustomizerCapabilities.essentialPreferences === 'object'
        ? orcamentoCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = orcamentoCustomizerDescriptor?.ids
        && typeof orcamentoCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: orcamentoCustomizerDescriptor.ids.overlay,
            openButtonId: orcamentoCustomizerDescriptor.trigger?.id || 'btnCustomizeOrcamento',
            closeButtonId: orcamentoCustomizerDescriptor.ids.close,
            saveButtonId: orcamentoCustomizerDescriptor.ids.save,
            presetEssentialButtonId: orcamentoCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: orcamentoCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: orcamentoCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateOrcamentoCustomizer() {
    const resolved = resolveOrcamentoCustomizerConfig();

    if (orcamentoCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: orcamentoCustomizer,
            resolved,
        };
    }

    orcamentoCustomizer = createPageCustomizer({
        storageKey: 'lk_orcamento_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadOrcamentoPrefs,
        savePreferences: saveOrcamentoPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: orcamentoCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreateOrcamentoCustomizer();

        if (!customizer) {
            return false;
        }

        if (!orcamentoCustomizerInitialized) {
            customizer.init();
            orcamentoCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!orcamentoCustomizerInitPromise) {
        orcamentoCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            orcamentoCustomizerInitPromise = null;
            initialize();
        });
    }
}

