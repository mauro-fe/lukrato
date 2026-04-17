import { apiGet, apiPost } from '../shared/api.js';
import {
    resolveDisplayNameEndpoint,
    resolveHelpPreferencesEndpoint,
    resolveThemePreferenceEndpoint,
    resolveUserBootstrapEndpoint,
} from '../api/endpoints/preferences.js';
import { resolveUnreadNotificationsEndpoint } from '../api/endpoints/notifications.js';
import { resolveDashboardPreferencesEndpoint } from '../api/endpoints/profile.js';
import {
    resolveSessionRenewEndpoint,
    resolveSessionStatusEndpoint,
} from '../api/endpoints/session.js';

function normalizeApiFailure(error) {
    return {
        success: false,
        status: Number(error?.status || error?.data?.status || 0) || 500,
        message: error?.data?.message || error?.message || 'Erro inesperado.',
        errors: error?.data?.errors || null,
        data: error?.data?.data || null,
    };
}

async function settle(requestFactory) {
    try {
        return await requestFactory();
    } catch (error) {
        return normalizeApiFailure(error);
    }
}

export function createFrontendPilotApi(endpoints) {
    return {
        getSessionStatus() {
            return settle(() => apiGet(endpoints.sessionStatus || resolveSessionStatusEndpoint()));
        },

        getBootstrap(context = {}) {
            return settle(() => apiGet(
                endpoints.bootstrap || resolveUserBootstrapEndpoint(),
                context
            ));
        },

        renewSession() {
            return settle(() => apiPost(endpoints.sessionRenew || resolveSessionRenewEndpoint(), {}));
        },

        getDashboardPreferences() {
            return settle(() => apiGet(endpoints.dashboardPreferences || resolveDashboardPreferencesEndpoint()));
        },

        updateTheme(theme) {
            return settle(() => apiPost(
                endpoints.themeUpdate || endpoints.theme || resolveThemePreferenceEndpoint(),
                { theme }
            ));
        },

        updateDisplayName(displayName) {
            return settle(() => apiPost(
                endpoints.displayNameUpdate || resolveDisplayNameEndpoint(),
                { display_name: displayName }
            ));
        },

        updateHelpPreferences(payload) {
            return settle(() => apiPost(
                endpoints.helpPreferencesUpdate || endpoints.helpPreferences || resolveHelpPreferencesEndpoint(),
                payload
            ));
        },

        updateDashboardPreferences(payload) {
            return settle(() => apiPost(
                endpoints.dashboardPreferencesUpdate || endpoints.dashboardPreferences || resolveDashboardPreferencesEndpoint(),
                payload
            ));
        },

        getUnreadNotifications() {
            return settle(() => apiGet(endpoints.notificationsUnread || resolveUnreadNotificationsEndpoint()));
        },
    };
}