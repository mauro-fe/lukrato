import {
    resolveMarkAllNotificationsEndpoint,
    resolveNotificationsListEndpoint,
    resolveReferralRewardsEndpoint,
    resolveReferralRewardsSeenEndpoint,
    resolveUnreadNotificationsEndpoint,
} from './notifications.js';

describe('admin/api/endpoints/notifications', () => {
    it('resolve os endpoints v1 de notificacoes e recompensas de indicacao', () => {
        expect(resolveNotificationsListEndpoint()).toBe('api/v1/notificacoes');
        expect(resolveUnreadNotificationsEndpoint()).toBe('api/v1/notificacoes/unread');
        expect(resolveMarkAllNotificationsEndpoint()).toBe('api/v1/notificacoes/marcar-todas');
        expect(resolveReferralRewardsEndpoint()).toBe('api/v1/notificacoes/referral-rewards');
        expect(resolveReferralRewardsSeenEndpoint()).toBe('api/v1/notificacoes/referral-rewards/seen');
    });
});