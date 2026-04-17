function encodeEndpointSegment(value) {
    return encodeURIComponent(String(value));
}

export function resolveSysadminUsersEndpoint() {
    return 'api/v1/sysadmin/users';
}

export function resolveSysadminUserEndpoint(userId) {
    return `${resolveSysadminUsersEndpoint()}/${encodeEndpointSegment(userId)}`;
}

export function resolveSysadminGrantAccessEndpoint() {
    return 'api/v1/sysadmin/grant-access';
}

export function resolveSysadminRevokeAccessEndpoint() {
    return 'api/v1/sysadmin/revoke-access';
}

export function resolveSysadminStatsEndpoint() {
    return 'api/v1/sysadmin/stats';
}

export function resolveSysadminMaintenanceEndpoint() {
    return 'api/v1/sysadmin/maintenance';
}

export function resolveSysadminClearCacheEndpoint() {
    return 'api/v1/sysadmin/clear-cache';
}

export function resolveSysadminErrorLogsEndpoint() {
    return 'api/v1/sysadmin/error-logs';
}

export function resolveSysadminErrorLogsSummaryEndpoint() {
    return `${resolveSysadminErrorLogsEndpoint()}/summary`;
}

export function resolveSysadminResolveErrorLogEndpoint(logId) {
    return `${resolveSysadminErrorLogsEndpoint()}/${encodeEndpointSegment(logId)}/resolve`;
}

export function resolveSysadminCleanupErrorLogsEndpoint() {
    return `${resolveSysadminErrorLogsEndpoint()}/cleanup`;
}

export function resolveSysadminFeedbackEndpoint() {
    return 'api/v1/sysadmin/feedback';
}

export function resolveSysadminFeedbackStatsEndpoint() {
    return `${resolveSysadminFeedbackEndpoint()}/stats`;
}

export function resolveSysadminFeedbackExportEndpoint() {
    return `${resolveSysadminFeedbackEndpoint()}/export`;
}