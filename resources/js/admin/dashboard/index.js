/**
 * ============================================================================
 * LUKRATO — Dashboard / Entry Point
 * ============================================================================
 * Bootstraps the dashboard: imports all modules, wires up DOMContentLoaded,
 * and exposes globals that PHP views expect (window.refreshDashboard, etc.).
 * ============================================================================
 */

import './greeting.js';
import './health-score.js';
import './health-score-insights.js';
import './ai-tip.js';
import './finance-overview.js';
import './progressive-disclosure.js';
import './celebration.js';
import './sprint2-loader.js';
import { Modules } from './state.js';
import { DashboardManager, EventListeners } from './app.js';
import { initOnboardingChecklist } from './onboarding.js';
import { initCustomize } from './customize.js';

// ─── Guard against double-loading ────────────────────────────────────────────
if (!window.__LK_DASHBOARD_LOADER__) {
    window.__LK_DASHBOARD_LOADER__ = true;

    // ─── Expose globals for PHP views ────────────────────────────────────────
    window.refreshDashboard = DashboardManager.refresh;
    window.LK = window.LK || {};
    window.LK.refreshDashboard = DashboardManager.refresh;

    // ─── Bootstrap ───────────────────────────────────────────────────────────
    const init = () => {
        const bootstrap = () => {
            EventListeners.init();
            DashboardManager.init();
            initCustomize();
        };

        initOnboardingChecklist();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootstrap);
        } else {
            bootstrap();
        }
    };

    init();
}

// ─── Named exports for other ES modules that may need them ───────────────────
export { Modules, DashboardManager };
