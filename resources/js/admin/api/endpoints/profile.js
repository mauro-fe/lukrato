import { resolveDisplayNameEndpoint } from './preferences.js';

export function resolveProfileEndpoint() {
    return 'api/v1/perfil';
}

export function resolveProfileAvatarEndpoint() {
    return 'api/v1/perfil/avatar';
}

export function resolveDashboardPreferencesEndpoint() {
    return 'api/v1/perfil/dashboard-preferences';
}

export function resolveProfilePasswordEndpoint() {
    return 'api/v1/perfil/senha';
}

export function resolveDeleteAccountEndpoint() {
    return 'api/v1/perfil/delete';
}

export function createPerfilEndpoints() {
    return {
        profile: resolveProfileEndpoint(),
        avatar: resolveProfileAvatarEndpoint(),
        displayName: resolveDisplayNameEndpoint(),
        dashboardPreferences: resolveDashboardPreferencesEndpoint(),
        password: resolveProfilePasswordEndpoint(),
        deleteAccount: resolveDeleteAccountEndpoint(),
    };
}