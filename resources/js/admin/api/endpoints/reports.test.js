import {
    resolveReportCardDetailsEndpoint,
    resolveReportsComparativesEndpoint,
    resolveReportsEndpoint,
    resolveReportsExportEndpoint,
    resolveReportsInsightsEndpoint,
    resolveReportsInsightsTeaserEndpoint,
    resolveReportsSummaryEndpoint,
} from './reports.js';

describe('admin/api/endpoints/reports', () => {
    it('resolve os endpoints v1 de relatorios e card details', () => {
        expect(resolveReportsEndpoint()).toBe('api/v1/reports');
        expect(resolveReportsSummaryEndpoint()).toBe('api/v1/reports/summary');
        expect(resolveReportsInsightsEndpoint()).toBe('api/v1/reports/insights');
        expect(resolveReportsInsightsTeaserEndpoint()).toBe('api/v1/reports/insights-teaser');
        expect(resolveReportsComparativesEndpoint()).toBe('api/v1/reports/comparatives');
        expect(resolveReportCardDetailsEndpoint(33)).toBe('api/v1/reports/card-details/33');
        expect(resolveReportsExportEndpoint()).toBe('api/v1/reports/export');
    });
});