import {
    resolveAiActionConfirmEndpoint,
    resolveAiActionRejectEndpoint,
    resolveAiConversationEndpoint,
    resolveAiConversationMessagesEndpoint,
    resolveAiConversationsEndpoint,
    resolveAiQuotaEndpoint,
    resolveAiSuggestCategoryEndpoint,
} from './ai.js';

describe('admin/api/endpoints/ai', () => {
    it('resolve os endpoints v1 da IA do usuario', () => {
        expect(resolveAiSuggestCategoryEndpoint()).toBe('api/v1/ai/suggest-category');
        expect(resolveAiQuotaEndpoint()).toBe('api/v1/ai/quota');
        expect(resolveAiConversationsEndpoint()).toBe('api/v1/ai/conversations');
        expect(resolveAiConversationEndpoint(15)).toBe('api/v1/ai/conversations/15');
        expect(resolveAiConversationMessagesEndpoint(15)).toBe('api/v1/ai/conversations/15/messages');
        expect(resolveAiActionConfirmEndpoint('abc')).toBe('api/v1/ai/actions/abc/confirm');
        expect(resolveAiActionRejectEndpoint('abc')).toBe('api/v1/ai/actions/abc/reject');
    });
});