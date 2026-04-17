function encodeEndpointSegment(value) {
    return encodeURIComponent(String(value));
}

export function resolvePlanLimitsEndpoint() {
    return 'api/v1/plan/limits';
}

export function resolvePlanFeaturesEndpoint() {
    return 'api/v1/plan/features';
}

export function resolvePlanCanCreateEndpoint(resource) {
    return `api/v1/plan/can-create/${encodeEndpointSegment(resource)}`;
}

export function resolvePlanHistoryRestrictionEndpoint() {
    return 'api/v1/plan/history-restriction';
}

export function resolvePremiumCheckoutEndpoint() {
    return 'api/v1/premium/checkout';
}

export function resolvePremiumCancelEndpoint() {
    return 'api/v1/premium/cancel';
}

export function resolvePremiumCheckPaymentEndpoint(paymentId) {
    return `api/v1/premium/check-payment/${encodeEndpointSegment(paymentId)}`;
}

export function resolvePremiumPendingPaymentEndpoint() {
    return 'api/v1/premium/pending-payment';
}

export function resolvePremiumPendingPixEndpoint() {
    return 'api/v1/premium/pending-pix';
}

export function resolvePremiumCancelPendingEndpoint() {
    return 'api/v1/premium/cancel-pending';
}

export function resolveCouponValidateEndpoint() {
    return 'api/v1/cupons/validar';
}