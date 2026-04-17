import { beforeEach, describe, expect, it, vi } from 'vitest';

const apiGetMock = vi.fn();
const apiPostMock = vi.fn();

vi.mock('./api.js', () => ({
    apiGet: apiGetMock,
    apiPost: apiPostMock,
}));

describe('shared/ui-preferences', () => {
    beforeEach(() => {
        apiGetMock.mockReset();
        apiPostMock.mockReset();
    });

    it('carrega preferencias por pagina via endpoint v1', async () => {
        apiGetMock.mockResolvedValue({
            data: {
                preferences: {
                    toggleGrafico: true,
                },
            },
        });

        const { fetchUiPagePreferences } = await import('./ui-preferences.js');

        await expect(fetchUiPagePreferences('dashboard')).resolves.toEqual({
            toggleGrafico: true,
        });
        expect(apiGetMock).toHaveBeenCalledWith('api/v1/user/ui-preferences/dashboard');
    });

    it('persiste preferencias por pagina via endpoint v1', async () => {
        apiPostMock.mockResolvedValue({
            data: {
                preferences: {
                    togglePerfilTabs: false,
                },
            },
        });

        const { persistUiPagePreferences } = await import('./ui-preferences.js');

        await expect(persistUiPagePreferences('perfil', { togglePerfilTabs: false })).resolves.toEqual({
            togglePerfilTabs: false,
        });
        expect(apiPostMock).toHaveBeenCalledWith('api/v1/user/ui-preferences/perfil', {
            preferences: {
                togglePerfilTabs: false,
            },
        });
    });

    it('rejeita page keys invalidas antes de disparar requisicao', async () => {
        const { fetchUiPagePreferences } = await import('./ui-preferences.js');

        await expect(fetchUiPagePreferences('..invalid')).rejects.toThrow('Invalid UI page key.');
        expect(apiGetMock).not.toHaveBeenCalled();
    });
});