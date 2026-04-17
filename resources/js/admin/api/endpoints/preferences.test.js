import {
    resolveBirthdayCheckEndpoint,
    resolveDisplayNameEndpoint,
    resolveHelpPreferencesEndpoint,
    resolveThemePreferenceEndpoint,
    resolveUserBootstrapEndpoint,
} from './preferences.js';

describe('admin/api/endpoints/preferences', () => {
    it('resolve os endpoints v1 de preferencias globais do usuario', () => {
        expect(resolveThemePreferenceEndpoint()).toBe('api/v1/user/theme');
        expect(resolveUserBootstrapEndpoint()).toBe('api/v1/user/bootstrap');
        expect(resolveHelpPreferencesEndpoint()).toBe('api/v1/user/help-preferences');
        expect(resolveDisplayNameEndpoint()).toBe('api/v1/user/display-name');
        expect(resolveBirthdayCheckEndpoint()).toBe('api/v1/user/birthday-check');
    });
});