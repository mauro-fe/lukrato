import {
    resolveCampaignBirthdaysEndpoint,
    resolveCampaignBirthdaysSendEndpoint,
    resolveCampaignCancelScheduledEndpoint,
    resolveCampaignEndpoint,
    resolveCampaignOptionsEndpoint,
    resolveCampaignPreviewEndpoint,
    resolveCampaignProcessDueEndpoint,
    resolveCampaignsEndpoint,
    resolveCampaignStatsEndpoint,
} from './campaigns.js';

describe('admin/api/endpoints/campaigns', () => {
    it('resolve os endpoints v1 de campanhas', () => {
        expect(resolveCampaignsEndpoint()).toBe('api/v1/campaigns');
        expect(resolveCampaignPreviewEndpoint()).toBe('api/v1/campaigns/preview');
        expect(resolveCampaignStatsEndpoint()).toBe('api/v1/campaigns/stats');
        expect(resolveCampaignOptionsEndpoint()).toBe('api/v1/campaigns/options');
        expect(resolveCampaignBirthdaysEndpoint()).toBe('api/v1/campaigns/birthdays');
        expect(resolveCampaignBirthdaysSendEndpoint()).toBe('api/v1/campaigns/birthdays/send');
        expect(resolveCampaignProcessDueEndpoint()).toBe('api/v1/campaigns/process-due');
        expect(resolveCampaignEndpoint(19)).toBe('api/v1/campaigns/19');
        expect(resolveCampaignCancelScheduledEndpoint(19)).toBe('api/v1/campaigns/19/cancel');
    });
});