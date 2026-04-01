/**
 * ============================================================================
 * LUKRATO - Faturas / UI
 * ============================================================================
 * All rendering, DOM manipulation, modals and user interaction for Faturas.
 * ============================================================================
 */
import { DOM, Modules } from './state.js';
import { CardListMethods } from './ui-card-list.js';
import { DetailsMethods } from './ui-details.js';
import { ActionMethods } from './ui-actions.js';

// ─── FaturasUI ──────────────────────────────────────────────────────────────

export const FaturasUI = {
    showLoading() {
        DOM.loadingEl.style.display = 'flex';
        DOM.containerEl.style.display = 'none';
        DOM.emptyStateEl.style.display = 'none';
    },

    hideLoading() {
        DOM.loadingEl.style.display = 'none';
    },

    showEmpty() {
        DOM.containerEl.style.display = 'none';
        DOM.emptyStateEl.style.display = 'block';
    },

    ...CardListMethods,
    ...DetailsMethods,
    ...ActionMethods,
};

// ─── Register in Modules ───────────────────────────────────────────────────

Modules.UI = FaturasUI;