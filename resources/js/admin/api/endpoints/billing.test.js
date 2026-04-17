import {
    resolveCouponValidateEndpoint,
    resolvePlanCanCreateEndpoint,
    resolvePlanFeaturesEndpoint,
    resolvePlanHistoryRestrictionEndpoint,
    resolvePlanLimitsEndpoint,
    resolvePremiumCancelEndpoint,
    resolvePremiumCancelPendingEndpoint,
    resolvePremiumCheckPaymentEndpoint,
    resolvePremiumCheckoutEndpoint,
    resolvePremiumPendingPaymentEndpoint,
    resolvePremiumPendingPixEndpoint,
} from './billing.js';

describe('admin/api/endpoints/billing', () => {
    it('resolve os endpoints v1 de plano e validacao de cupom', () => {
        expect(resolvePlanLimitsEndpoint()).toBe('api/v1/plan/limits');
        expect(resolvePlanFeaturesEndpoint()).toBe('api/v1/plan/features');
        expect(resolvePlanCanCreateEndpoint('metas')).toBe('api/v1/plan/can-create/metas');
        expect(resolvePlanHistoryRestrictionEndpoint()).toBe('api/v1/plan/history-restriction');
        expect(resolvePremiumCheckoutEndpoint()).toBe('api/v1/premium/checkout');
        expect(resolvePremiumCancelEndpoint()).toBe('api/v1/premium/cancel');
        expect(resolvePremiumCheckPaymentEndpoint(42)).toBe('api/v1/premium/check-payment/42');
        expect(resolvePremiumPendingPaymentEndpoint()).toBe('api/v1/premium/pending-payment');
        expect(resolvePremiumPendingPixEndpoint()).toBe('api/v1/premium/pending-pix');
        expect(resolvePremiumCancelPendingEndpoint()).toBe('api/v1/premium/cancel-pending');
        expect(resolveCouponValidateEndpoint()).toBe('api/v1/cupons/validar');
    });
});