function encodeEndpointSegment(value) {
    return encodeURIComponent(String(value));
}

export function resolveAiSuggestCategoryEndpoint() {
    return 'api/v1/ai/suggest-category';
}

export function resolveAiQuotaEndpoint() {
    return 'api/v1/ai/quota';
}

export function resolveAiConversationsEndpoint() {
    return 'api/v1/ai/conversations';
}

export function resolveAiConversationEndpoint(conversationId) {
    return `${resolveAiConversationsEndpoint()}/${encodeEndpointSegment(conversationId)}`;
}

export function resolveAiConversationMessagesEndpoint(conversationId) {
    return `${resolveAiConversationEndpoint(conversationId)}/messages`;
}

export function resolveAiActionConfirmEndpoint(actionId) {
    return `api/v1/ai/actions/${encodeEndpointSegment(actionId)}/confirm`;
}

export function resolveAiActionRejectEndpoint(actionId) {
    return `api/v1/ai/actions/${encodeEndpointSegment(actionId)}/reject`;
}