/**
 * Billing customization entry.
 * Uses shared page-customizer engine for first-run + presets + persistence.
 */

import { createPageCustomizer } from '../shared/page-customizer.js';
import { fetchUiPagePreferences, persistUiPagePreferences } from '../shared/ui-preferences.js';

/** Map: checkbox ID -> section ID */
const SECTION_MAP = {
    toggleBillingHeader: 'billingHeaderSection',
    toggleBillingPlans: 'billingPlansSection'
};

const COMPLETE_DEFAULTS = {
    toggleBillingHeader: true,
    toggleBillingPlans: true
};

const ESSENTIAL_DEFAULTS = {
    ...COMPLETE_DEFAULTS
};

async function loadBillingPrefs() {
    return fetchUiPagePreferences('billing');
}

async function saveBillingPrefs(prefs) {
    await persistUiPagePreferences('billing', prefs);
}

const billingCustomizer = createPageCustomizer({
    storageKey: 'lk_billing_prefs',
    sectionMap: SECTION_MAP,
    completeDefaults: COMPLETE_DEFAULTS,
    essentialDefaults: ESSENTIAL_DEFAULTS,
    loadPreferences: loadBillingPrefs,
    savePreferences: saveBillingPrefs,
    modal: {
        overlayId: 'billingCustomizeModalOverlay',
        openButtonId: 'btnCustomizeBilling',
        closeButtonId: 'btnCloseCustomizeBilling',
        saveButtonId: 'btnSaveCustomizeBilling',
        presetEssentialButtonId: 'btnPresetEssencialBilling',
        presetCompleteButtonId: 'btnPresetCompletoBilling'
    }
});

export function initCustomize() {
    billingCustomizer.init();
}

