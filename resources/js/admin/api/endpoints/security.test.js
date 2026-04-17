import { resolveCsrfRefreshEndpoint } from './security.js';

describe('admin/api/endpoints/security', () => {
    it('resolve o endpoint v1 de refresh do csrf', () => {
        expect(resolveCsrfRefreshEndpoint()).toBe('api/v1/csrf/refresh');
    });
});