import { beforeEach, describe, expect, it, vi } from 'vitest';

describe('admin/global/theme-preferences', () => {
    let apiGetMock;

    beforeEach(() => {
        vi.resetModules();
        apiGetMock = vi.fn();
        vi.doMock('../shared/api.js', () => ({
            apiGet: apiGetMock,
        }));
        global.window = {};
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.resetModules();
        delete global.window;
    });

    it('resolve system para o tema do sistema com fallback seguro', async () => {
        const { resolveAppliedTheme } = await import('./theme-preferences.js');

        expect(resolveAppliedTheme('system', {
            fallbackTheme: 'dark',
            matchMedia: vi.fn().mockReturnValue({ matches: false }),
        })).toBe('light');

        expect(resolveAppliedTheme('system', {
            fallbackTheme: 'dark',
            matchMedia: undefined,
        })).toBe('dark');
    });

    it('prioriza o tema salvo localmente para reduzir flicker antes da hidratacao', async () => {
        const { getInitialAppliedTheme, STORAGE_KEY } = await import('./theme-preferences.js');
        const storage = {
            getItem: vi.fn((key) => (key === STORAGE_KEY ? 'light' : null)),
        };
        const root = {
            getAttribute: vi.fn(() => 'dark'),
        };

        expect(getInitialAppliedTheme({ storage, root })).toBe('light');
    });

    it('preserva a preferencia system no armazenamento local', async () => {
        const { STORAGE_KEY, storeThemePreference } = await import('./theme-preferences.js');
        const storage = {
            setItem: vi.fn(),
        };

        storeThemePreference('system', storage);

        expect(storage.setItem).toHaveBeenCalledWith(STORAGE_KEY, 'system');
    });

    it('carrega a preferencia de tema pelo endpoint v1', async () => {
        apiGetMock.mockResolvedValue({
            success: true,
            data: {
                theme: 'system',
            },
        });

        const { fetchThemePreference } = await import('./theme-preferences.js');

        await expect(fetchThemePreference()).resolves.toEqual({
            ok: true,
            theme: 'system',
        });
        expect(apiGetMock).toHaveBeenCalledWith('api/v1/user/theme');
    });

    it('falha de forma segura quando a chamada da api falha', async () => {
        apiGetMock.mockRejectedValue(new Error('falha ao carregar tema'));

        const { fetchThemePreference } = await import('./theme-preferences.js');

        await expect(fetchThemePreference()).resolves.toEqual({
            ok: false,
            theme: null,
        });
    });
});
