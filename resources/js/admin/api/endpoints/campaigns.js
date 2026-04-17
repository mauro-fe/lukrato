function encodeEndpointSegment(value) {
    return encodeURIComponent(String(value));
}

export function resolveCampaignsEndpoint() {
    return 'api/v1/campaigns';
}

export function resolveCampaignPreviewEndpoint() {
    return `${resolveCampaignsEndpoint()}/preview`;
}

export function resolveCampaignStatsEndpoint() {
    return `${resolveCampaignsEndpoint()}/stats`;
}

export function resolveCampaignOptionsEndpoint() {
    return `${resolveCampaignsEndpoint()}/options`;
}

export function resolveCampaignBirthdaysEndpoint() {
    return `${resolveCampaignsEndpoint()}/birthdays`;
}

export function resolveCampaignBirthdaysSendEndpoint() {
    return `${resolveCampaignBirthdaysEndpoint()}/send`;
}

export function resolveCampaignProcessDueEndpoint() {
    return `${resolveCampaignsEndpoint()}/process-due`;
}

export function resolveCampaignEndpoint(campaignId) {
    return `${resolveCampaignsEndpoint()}/${encodeEndpointSegment(campaignId)}`;
}

export function resolveCampaignCancelScheduledEndpoint(campaignId) {
    return `${resolveCampaignEndpoint(campaignId)}/cancel`;
}