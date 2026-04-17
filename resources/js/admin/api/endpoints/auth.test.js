import {
    resolveAuthForgotPasswordEndpoint,
    resolveAuthLoginEndpoint,
    resolveAuthLogoutEndpoint,
    resolveAuthRegisterEndpoint,
    resolveAuthResendVerificationEndpoint,
    resolveAuthResetPasswordEndpoint,
} from './auth.js';

describe('admin/api/endpoints/auth', () => {
    it('resolve os endpoints v1 de autenticacao', () => {
        expect(resolveAuthLoginEndpoint()).toBe('api/v1/auth/login');
        expect(resolveAuthLogoutEndpoint()).toBe('api/v1/auth/logout');
        expect(resolveAuthRegisterEndpoint()).toBe('api/v1/auth/register');
        expect(resolveAuthForgotPasswordEndpoint()).toBe('api/v1/auth/password/forgot');
        expect(resolveAuthResetPasswordEndpoint()).toBe('api/v1/auth/password/reset');
        expect(resolveAuthResendVerificationEndpoint()).toBe('api/v1/auth/email/resend');
    });
});