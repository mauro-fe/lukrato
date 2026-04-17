import { resolveDashboardEvolutionEndpoint, resolveDashboardOverviewEndpoint } from './dashboard.js';

describe('admin/api/endpoints/dashboard', () => {
    it('resolve os endpoints v1 do dashboard', () => {
        expect(resolveDashboardOverviewEndpoint()).toBe('api/v1/dashboard/overview');
        expect(resolveDashboardEvolutionEndpoint()).toBe('api/v1/dashboard/evolucao');
    });
});