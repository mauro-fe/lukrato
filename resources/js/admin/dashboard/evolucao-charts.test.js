import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';

const apiGetMock = vi.fn();

vi.mock('../shared/api.js', () => ({
    apiGet: apiGetMock,
}));

describe('admin/dashboard/evolucao-charts', () => {
    beforeEach(() => {
        vi.resetModules();
        apiGetMock.mockReset();
        global.window = {};
        global.document = {
            getElementById: vi.fn(() => ({ innerHTML: '' })),
        };
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.resetModules();
        delete global.document;
        delete global.window;
    });

    it('carrega os dados usando o mes atual via apiGet', async () => {
        apiGetMock.mockResolvedValue({
            data: {
                mensal: [],
                anual: [],
            },
        });

        await import('./evolucao-charts.js');
        const EvolucaoCharts = global.window.EvolucaoCharts;
        const widget = new EvolucaoCharts();

        widget._currentMonth = '2026-04';
        widget._drawMensal = vi.fn();
        widget._drawAnual = vi.fn();
        widget._updateStats = vi.fn();

        await widget._loadAndDraw();

        expect(apiGetMock).toHaveBeenCalledWith('api/v1/dashboard/evolucao', { month: '2026-04' });
        expect(widget._drawMensal).toHaveBeenCalledWith([]);
        expect(widget._drawAnual).toHaveBeenCalledWith([]);
        expect(widget._updateStats).toHaveBeenCalledWith({ mensal: [], anual: [] });
    });
});