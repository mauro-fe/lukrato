import {
    resolveSessionHeartbeatEndpoint,
    resolveSessionRenewEndpoint,
    resolveSessionStatusEndpoint,
} from './session.js';

describe('admin/api/endpoints/session', () => {
    it('resolve os endpoints v1 de sessao', () => {
        expect(resolveSessionStatusEndpoint()).toBe('api/v1/session/status');
        expect(resolveSessionRenewEndpoint()).toBe('api/v1/session/renew');
        expect(resolveSessionHeartbeatEndpoint()).toBe('api/v1/session/heartbeat');
    });
});