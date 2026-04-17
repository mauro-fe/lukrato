import {
    resolveGoogleCancelEndpoint,
    resolveGoogleConfirmEndpoint,
    resolveGooglePendingEndpoint,
} from './auth.js';

describe('site/api/endpoints/auth', () => {
    it('resolve os endpoints v1 do fluxo Google publico', () => {
        expect(resolveGooglePendingEndpoint()).toBe('api/v1/auth/google/pending');
        expect(resolveGoogleConfirmEndpoint()).toBe('api/v1/auth/google/confirm');
        expect(resolveGoogleCancelEndpoint()).toBe('api/v1/auth/google/cancel');
    });
});