import {
    resolveSysadminAiAnalyzeSpendingEndpoint,
    resolveSysadminAiChatEndpoint,
    resolveSysadminAiHealthProxyEndpoint,
    resolveSysadminAiLogsCleanupEndpoint,
    resolveSysadminAiLogsEndpoint,
    resolveSysadminAiLogsQualityEndpoint,
    resolveSysadminAiLogsSummaryEndpoint,
    resolveSysadminAiQuotaEndpoint,
    resolveSysadminAiSuggestCategoryEndpoint,
} from './sysadmin-ai.js';

describe('admin/api/endpoints/sysadmin-ai', () => {
    it('resolve os endpoints v1 de IA administrativa', () => {
        expect(resolveSysadminAiHealthProxyEndpoint()).toBe('api/v1/sysadmin/ai/health-proxy');
        expect(resolveSysadminAiQuotaEndpoint()).toBe('api/v1/sysadmin/ai/quota');
        expect(resolveSysadminAiChatEndpoint()).toBe('api/v1/sysadmin/ai/chat');
        expect(resolveSysadminAiSuggestCategoryEndpoint()).toBe('api/v1/sysadmin/ai/suggest-category');
        expect(resolveSysadminAiAnalyzeSpendingEndpoint()).toBe('api/v1/sysadmin/ai/analyze-spending');
        expect(resolveSysadminAiLogsEndpoint()).toBe('api/v1/sysadmin/ai/logs');
        expect(resolveSysadminAiLogsSummaryEndpoint()).toBe('api/v1/sysadmin/ai/logs/summary');
        expect(resolveSysadminAiLogsQualityEndpoint()).toBe('api/v1/sysadmin/ai/logs/quality');
        expect(resolveSysadminAiLogsCleanupEndpoint()).toBe('api/v1/sysadmin/ai/logs/cleanup');
    });
});