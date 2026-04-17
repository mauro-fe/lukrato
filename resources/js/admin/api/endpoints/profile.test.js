import {
    createPerfilEndpoints,
    resolveDashboardPreferencesEndpoint,
    resolveDeleteAccountEndpoint,
    resolveProfileAvatarEndpoint,
    resolveProfileEndpoint,
    resolveProfilePasswordEndpoint,
} from './profile.js';

describe('admin/api/endpoints/profile', () => {
    it('resolve os endpoints v1 usados pela pagina de perfil', () => {
        expect(resolveProfileEndpoint()).toBe('api/v1/perfil');
        expect(resolveProfileAvatarEndpoint()).toBe('api/v1/perfil/avatar');
        expect(resolveDashboardPreferencesEndpoint()).toBe('api/v1/perfil/dashboard-preferences');
        expect(resolveProfilePasswordEndpoint()).toBe('api/v1/perfil/senha');
        expect(resolveDeleteAccountEndpoint()).toBe('api/v1/perfil/delete');
        expect(createPerfilEndpoints()).toEqual({
            profile: 'api/v1/perfil',
            avatar: 'api/v1/perfil/avatar',
            displayName: 'api/v1/user/display-name',
            dashboardPreferences: 'api/v1/perfil/dashboard-preferences',
            password: 'api/v1/perfil/senha',
            deleteAccount: 'api/v1/perfil/delete',
        });
    });
});