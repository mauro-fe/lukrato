export function resolveSysadminAiHealthProxyEndpoint() {
    return 'api/v1/sysadmin/ai/health-proxy';
}

export function resolveSysadminAiQuotaEndpoint() {
    return 'api/v1/sysadmin/ai/quota';
}

export function resolveSysadminAiChatEndpoint() {
    return 'api/v1/sysadmin/ai/chat';
}

export function resolveSysadminAiSuggestCategoryEndpoint() {
    return 'api/v1/sysadmin/ai/suggest-category';
}

export function resolveSysadminAiAnalyzeSpendingEndpoint() {
    return 'api/v1/sysadmin/ai/analyze-spending';
}

export function resolveSysadminAiLogsEndpoint() {
    return 'api/v1/sysadmin/ai/logs';
}

export function resolveSysadminAiLogsSummaryEndpoint() {
    return `${resolveSysadminAiLogsEndpoint()}/summary`;
}

export function resolveSysadminAiLogsQualityEndpoint() {
    return `${resolveSysadminAiLogsEndpoint()}/quality`;
}

export function resolveSysadminAiLogsCleanupEndpoint() {
    return `${resolveSysadminAiLogsEndpoint()}/cleanup`;
}