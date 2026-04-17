function encodeEndpointSegment(value) {
    return encodeURIComponent(String(value));
}

export function resolveReportsEndpoint() {
    return 'api/v1/reports';
}

export function resolveReportsSummaryEndpoint() {
    return `${resolveReportsEndpoint()}/summary`;
}

export function resolveReportsInsightsEndpoint() {
    return `${resolveReportsEndpoint()}/insights`;
}

export function resolveReportsInsightsTeaserEndpoint() {
    return `${resolveReportsEndpoint()}/insights-teaser`;
}

export function resolveReportsComparativesEndpoint() {
    return `${resolveReportsEndpoint()}/comparatives`;
}

export function resolveReportCardDetailsEndpoint(cardId) {
    return `${resolveReportsEndpoint()}/card-details/${encodeEndpointSegment(cardId)}`;
}

export function resolveReportsExportEndpoint() {
    return `${resolveReportsEndpoint()}/export`;
}