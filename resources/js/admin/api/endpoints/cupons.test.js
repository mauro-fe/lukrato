import {
    resolveCuponsEndpoint,
    resolveCuponsStatisticsEndpoint,
} from './cupons.js';

describe('admin/api/endpoints/cupons', () => {
    it('resolve os endpoints v1 de cupons administrativos', () => {
        expect(resolveCuponsEndpoint()).toBe('api/v1/cupons');
        expect(resolveCuponsStatisticsEndpoint()).toBe('api/v1/cupons/estatisticas');
    });
});