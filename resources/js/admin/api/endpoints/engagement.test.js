import {
    resolveContactSendEndpoint,
    resolveFeedbackCanMicroEndpoint,
    resolveFeedbackCheckNpsEndpoint,
    resolveFeedbackEndpoint,
    resolveReferralCodeEndpoint,
    resolveReferralInfoEndpoint,
    resolveReferralRankingEndpoint,
    resolveReferralStatsEndpoint,
    resolveReferralValidateEndpoint,
    resolveSupportSendEndpoint,
} from './engagement.js';

describe('admin/api/endpoints/engagement', () => {
    it('resolve os endpoints v1 de contato, suporte, feedback e referral', () => {
        expect(resolveContactSendEndpoint()).toBe('api/v1/contato/enviar');
        expect(resolveSupportSendEndpoint()).toBe('api/v1/suporte/enviar');
        expect(resolveFeedbackEndpoint()).toBe('api/v1/feedback');
        expect(resolveFeedbackCheckNpsEndpoint()).toBe('api/v1/feedback/check-nps');
        expect(resolveFeedbackCanMicroEndpoint()).toBe('api/v1/feedback/can-micro');
        expect(resolveReferralInfoEndpoint()).toBe('api/v1/referral/info');
        expect(resolveReferralValidateEndpoint()).toBe('api/v1/referral/validate');
        expect(resolveReferralStatsEndpoint()).toBe('api/v1/referral/stats');
        expect(resolveReferralCodeEndpoint()).toBe('api/v1/referral/code');
        expect(resolveReferralRankingEndpoint()).toBe('api/v1/referral/ranking');
    });
});