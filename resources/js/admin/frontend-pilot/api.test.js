import { beforeEach, describe, expect, it, vi } from 'vitest';

const apiGetMock = vi.fn();
const apiPostMock = vi.fn();

vi.mock('../shared/api.js', () => ({
    apiGet: apiGetMock,
    apiPost: apiPostMock,
}));

describe('admin/frontend-pilot/api', () => {
    beforeEach(() => {
        apiGetMock.mockReset();
        apiPostMock.mockReset();
    });

    it('consulta o bootstrap autenticado com o contexto explicito da shell', async () => {
        apiGetMock.mockResolvedValue({
            success: true,
            data: {
                currentViewPath: 'admin/frontend-pilot/index',
            },
        });

        const { createFrontendPilotApi } = await import('./api.js');
        const api = createFrontendPilotApi({});
        const context = {
            menu: 'perfil',
            view_id: 'admin-frontend-pilot-index',
            view_path: 'admin/frontend-pilot/index',
        };

        await expect(api.getBootstrap(context)).resolves.toEqual({
            success: true,
            data: {
                currentViewPath: 'admin/frontend-pilot/index',
            },
        });

        expect(apiGetMock).toHaveBeenCalledWith('api/v1/user/bootstrap', context);
    });

    it('normaliza falhas do bootstrap em payload previsivel', async () => {
        apiGetMock.mockRejectedValue({
            status: 401,
            data: {
                message: 'Nao autenticado.',
            },
        });

        const { createFrontendPilotApi } = await import('./api.js');
        const api = createFrontendPilotApi({});

        await expect(api.getBootstrap()).resolves.toEqual({
            success: false,
            status: 401,
            message: 'Nao autenticado.',
            errors: null,
            data: null,
        });
    });
});