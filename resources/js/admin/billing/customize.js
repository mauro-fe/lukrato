/**
 * Billing customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

let billingCustomizer = null;
let billingCustomizerInitialized = false;
let billingCustomizerInitPromise = null;

async function loadBillingPrefs() {
    return fetchUiPagePreferences('billing');
}

async function saveBillingPrefs(prefs) {
    await persistUiPagePreferences('billing', prefs);
}

function resolveBillingCustomizerConfig() {
    const runtimeConfig = getRuntimeConfig();
    const billingCustomizerCapabilities = runtimeConfig?.pageCapabilities?.pageKey === 'billing'
        && runtimeConfig?.pageCapabilities?.customizer
        && typeof runtimeConfig.pageCapabilities.customizer === 'object'
        ? runtimeConfig.pageCapabilities.customizer
        : null;

    const billingCustomizerDescriptor = billingCustomizerCapabilities?.descriptor
        && typeof billingCustomizerCapabilities.descriptor === 'object'
        ? billingCustomizerCapabilities.descriptor
        : null;

    const sectionMap = billingCustomizerDescriptor?.sectionMap
        && typeof billingCustomizerDescriptor.sectionMap === 'object'
        ? billingCustomizerDescriptor.sectionMap
        : {};

    const completeDefaults = billingCustomizerCapabilities?.completePreferences
        && typeof billingCustomizerCapabilities.completePreferences === 'object'
        ? billingCustomizerCapabilities.completePreferences
        : {};

    const essentialDefaults = billingCustomizerCapabilities?.essentialPreferences
        && typeof billingCustomizerCapabilities.essentialPreferences === 'object'
        ? billingCustomizerCapabilities.essentialPreferences
        : {};

    const modalConfig = billingCustomizerDescriptor?.ids
        && typeof billingCustomizerDescriptor.ids === 'object'
        ? {
            overlayId: billingCustomizerDescriptor.ids.overlay,
            openButtonId: billingCustomizerDescriptor.trigger?.id || 'btnCustomizeBilling',
            closeButtonId: billingCustomizerDescriptor.ids.close,
            saveButtonId: billingCustomizerDescriptor.ids.save,
            presetEssentialButtonId: billingCustomizerDescriptor.ids.presetEssential,
            presetCompleteButtonId: billingCustomizerDescriptor.ids.presetComplete,
        }
        : undefined;

    return {
        capabilities: billingCustomizerCapabilities,
        sectionMap,
        completeDefaults,
        essentialDefaults,
        modalConfig,
    };
}

function getOrCreateBillingCustomizer() {
    const resolved = resolveBillingCustomizerConfig();

    if (billingCustomizer || Object.keys(resolved.sectionMap).length === 0) {
        return {
            customizer: billingCustomizer,
            resolved,
        };
    }

    billingCustomizer = createPageCustomizer({
        storageKey: 'lk_billing_prefs',
        sectionMap: resolved.sectionMap,
        completeDefaults: resolved.completeDefaults,
        essentialDefaults: resolved.essentialDefaults,
        capabilities: resolved.capabilities,
        loadPreferences: loadBillingPrefs,
        savePreferences: saveBillingPrefs,
        modal: resolved.modalConfig,
    });

    return {
        customizer: billingCustomizer,
        resolved,
    };
}

export function initCustomize() {
    const initialize = () => {
        const { customizer } = getOrCreateBillingCustomizer();

        if (!customizer) {
            return false;
        }

        if (!billingCustomizerInitialized) {
            customizer.init();
            billingCustomizerInitialized = true;
        }

        return true;
    };

    if (initialize()) {
        return;
    }

    if (!billingCustomizerInitPromise) {
        billingCustomizerInitPromise = ensureRuntimeConfig({}, { silent: true }).finally(() => {
            billingCustomizerInitPromise = null;
            initialize();
        });
    }
}

