/**
 * Lancamentos customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { Modules } from './state.js';
import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let lancamentosCustomizer = null;
let lancamentosCustomizerInitialized = false;
let lancamentosCustomizerInitPromise = null;
let lancamentosCompleteDefaults = {};

async function loadLancamentosPrefs() {
    return fetchUiPagePreferences('lancamentos');
}

async function saveLancamentosPrefs(prefs) {
    await persistUiPagePreferences('lancamentos', prefs);
}

function isVisible(element) {
    return !!element && getComputedStyle(element).display !== 'none';
}

function syncVisibleChildren(container) {
    if (!container) return 0;

    const visibleCount = Array.from(container.children).filter(isVisible).length;
    container.dataset.visibleCount = String(visibleCount);
    container.style.display = visibleCount > 0 ? '' : 'none';
    return visibleCount;
}

function resolveLancamentosCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const lancamentosCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'lancamentos'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const lancamentosCustomizerDescriptor = lancamentosCustomizerCapabilities?.descriptor
        && typeof lancamentosCustomizerCapabilities.descriptor === 'object'
        ? lancamentosCustomizerCapabilities.descriptor
        : null;

    const sectionMap = lancamentosCustomizerDescriptor?.sectionMap
        && typeof lancamentosCustomizerDescriptor.sectionMap === 'object'
        ? lancamentosCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = lancamentosCustomizerCapabilities?.completePreferences
        && typeof lancamentosCustomizerCapabilities.completePreferences === 'object'
        ? lancamentosCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = lancamentosCustomizerCapabilities?.essentialPreferences
        && typeof lancamentosCustomizerCapabilities.essentialPreferences === 'object'
        ? lancamentosCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = lancamentosCustomizerDescriptor?.ids
        && typeof lancamentosCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: lancamentosCustomizerDescriptor.ids.overlay,
            openButtonId: lancamentosCustomizerDescriptor.trigger?.id || 'btnCustomizeLancamentos',
            closeButtonId: lancamentosCustomizerDescriptor.ids.close,
            saveButtonId: lancamentosCustomizerDescriptor.ids.save,
            presetEssentialButtonId: lancamentosCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: lancamentosCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    lancamentosCompleteDefaults = completeDefaults;

    return {
        capabilities: lancamentosCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function syncLancamentosLayout(prefs = lancamentosCompleteDefaults) {
    const overviewStage = document.querySelector('.lan-stage--overview');
    const overviewBottom = document.querySelector('.lan-overview-bottom');
    const bottomCount = syncVisibleChildren(overviewBottom);

    if (overviewStage) {
        const visibleBlocks = bottomCount > 0 ? 1 : 0;
        overviewStage.dataset.visibleCount = String(visibleBlocks);
        overviewStage.style.display = visibleBlocks > 0 ? '' : 'none';
    }
}

function getOrCreateLancamentosCustomizer() {
    const resolved = resolveLancamentosCustomizerConfig();

    if (lancamentosCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: lancamentosCustomizer,
            resolved,
        };
    }

    lancamentosCustomizer = createPageCustomizer({
        storageKey: 'lk_lancamentos_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadLancamentosPrefs,
        savePreferences: saveLancamentosPrefs,
        onApply: syncLancamentosLayout,
        modal: resolved.modalConfig,
    });

    return {
        customizer: lancamentosCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer, resolved } = getOrCreateLancamentosCustomizer();

        if (!customizer) {
            syncLancamentosLayout(resolved.essentialDefaults);
            return false;
        }

        if (!lancamentosCustomizerInitialized) {
            customizer.init();
            lancamentosCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!lancamentosCustomizerInitPromise) {
        lancamentosCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            lancamentosCustomizerInitPromise = null;
            initialize();
        });
    }
}

Modules.Customize = {
    init: initCustomize,
    open: () => {
        const { customizer } = getOrCreateLancamentosCustomizer();
        if (customizer?.open) {
            customizer.open();
            return;
        }

        void ensureRuntimeConfig({}, { silent: true }).finally(() => {
            const { customizer: nextCustomizer } = getOrCreateLancamentosCustomizer();
            nextCustomizer?.open?.();
        });
    },
    close: () => {
        const { customizer } = getOrCreateLancamentosCustomizer();
        customizer?.close?.();
    }
};
