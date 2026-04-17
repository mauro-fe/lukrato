import {
    resolveSysadminClearCacheEndpoint,
    resolveSysadminCleanupErrorLogsEndpoint,
    resolveSysadminErrorLogsEndpoint,
    resolveSysadminErrorLogsSummaryEndpoint,
    resolveSysadminFeedbackEndpoint,
    resolveSysadminFeedbackExportEndpoint,
    resolveSysadminFeedbackStatsEndpoint,
    resolveSysadminGrantAccessEndpoint,
    resolveSysadminMaintenanceEndpoint,
    resolveSysadminResolveErrorLogEndpoint,
    resolveSysadminRevokeAccessEndpoint,
    resolveSysadminStatsEndpoint,
    resolveSysadminUserEndpoint,
    resolveSysadminUsersEndpoint,
} from './sysadmin.js';

describe('admin/api/endpoints/sysadmin', () => {
    it('resolve os endpoints v1 do painel sysadmin', () => {
        expect(resolveSysadminUsersEndpoint()).toBe('api/v1/sysadmin/users');
        expect(resolveSysadminUserEndpoint(42)).toBe('api/v1/sysadmin/users/42');
        expect(resolveSysadminGrantAccessEndpoint()).toBe('api/v1/sysadmin/grant-access');
        expect(resolveSysadminRevokeAccessEndpoint()).toBe('api/v1/sysadmin/revoke-access');
        expect(resolveSysadminStatsEndpoint()).toBe('api/v1/sysadmin/stats');
        expect(resolveSysadminMaintenanceEndpoint()).toBe('api/v1/sysadmin/maintenance');
        expect(resolveSysadminClearCacheEndpoint()).toBe('api/v1/sysadmin/clear-cache');
        expect(resolveSysadminErrorLogsEndpoint()).toBe('api/v1/sysadmin/error-logs');
        expect(resolveSysadminErrorLogsSummaryEndpoint()).toBe('api/v1/sysadmin/error-logs/summary');
        expect(resolveSysadminResolveErrorLogEndpoint(7)).toBe('api/v1/sysadmin/error-logs/7/resolve');
        expect(resolveSysadminCleanupErrorLogsEndpoint()).toBe('api/v1/sysadmin/error-logs/cleanup');
        expect(resolveSysadminFeedbackEndpoint()).toBe('api/v1/sysadmin/feedback');
        expect(resolveSysadminFeedbackStatsEndpoint()).toBe('api/v1/sysadmin/feedback/stats');
        expect(resolveSysadminFeedbackExportEndpoint()).toBe('api/v1/sysadmin/feedback/export');
    });
});