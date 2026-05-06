/**
 * Contas customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';
import { Modules } from './state.js';

let contasCustomizer = null;
let contasCustomizerInitialized = false;
let contasCustomizerInitPromise = null;
let contasCompleteDefaults = {};

async function loadContasPrefs() {
    return fetchUiPagePreferences('contas');
}

async function saveContasPrefs(prefs) {
    await persistUiPagePreferences('contas', prefs);
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

function resolveContasCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const contasCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'contas'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const contasCustomizerDescriptor = contasCustomizerCapabilities?.descriptor
        && typeof contasCustomizerCapabilities.descriptor === 'object'
        ? contasCustomizerCapabilities.descriptor
        : null;

    const sectionMap = contasCustomizerDescriptor?.sectionMap
        && typeof contasCustomizerDescriptor.sectionMap === 'object'
        ? contasCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = contasCustomizerCapabilities?.completePreferences
        && typeof contasCustomizerCapabilities.completePreferences === 'object'
        ? contasCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = contasCustomizerCapabilities?.essentialPreferences
        && typeof contasCustomizerCapabilities.essentialPreferences === 'object'
        ? contasCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = contasCustomizerDescriptor?.ids
        && typeof contasCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: contasCustomizerDescriptor.ids.overlay,
            openButtonId: contasCustomizerDescriptor.trigger?.id || 'btnCustomizeContas',
            closeButtonId: contasCustomizerDescriptor.ids.close,
            saveButtonId: contasCustomizerDescriptor.ids.save,
            presetEssentialButtonId: contasCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: contasCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    contasCompleteDefaults = completeDefaults;

    return {
        capabilities: contasCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function syncContasLayout(prefs = contasCompleteDefaults) {
    const overviewStage = document.querySelector('.cont-stage--overview');
    const overviewTop = document.querySelector('.cont-overview-top');
    const topCount = syncVisibleChildren(overviewTop);

    if (overviewStage) {
        const visibleBlocks = topCount > 0 ? 1 : 0;
        overviewStage.dataset.visibleCount = String(visibleBlocks);
        overviewStage.style.display = visibleBlocks > 0 ? '' : 'none';
    }
}

function getOrCreateContasCustomizer() {
    const resolved = resolveContasCustomizerConfig();

    if (contasCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: contasCustomizer,
            resolved,
        };
    }

    contasCustomizer = createPageCustomizer({
        storageKey: 'lk_contas_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadContasPrefs,
        savePreferences: saveContasPrefs,
        onApply: syncContasLayout,
        modal: resolved.modalConfig,
    });

    return {
        customizer: contasCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer, resolved } = getOrCreateContasCustomizer();

        if (!customizer) {
            syncContasLayout(resolved.essentialDefaults);
            return false;
        }

        if (!contasCustomizerInitialized) {
            customizer.init();
            contasCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!contasCustomizerInitPromise) {
        contasCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            contasCustomizerInitPromise = null;
            initialize();
        });
    }
}

Modules.Customize = {
    init: initCustomize,
    open: () => {
        const { customizer } = getOrCreateContasCustomizer();
        if (customizer?.open) {
            customizer.open();
            return;
        }

        void ensureRuntimeConfig({}, { silent: true }).finally(() => {
            const { customizer: nextCustomizer } = getOrCreateContasCustomizer();
            nextCustomizer?.open?.();
        });
    },
    close: () => {
        const { customizer } = getOrCreateContasCustomizer();
        customizer?.close?.();
    }
};
