describe('admin/global/help-center/persistence', () => {
    let apiGetMock;
    let apiPostMock;

    beforeEach(() => {
        vi.resetModules();
        apiGetMock = vi.fn();
        apiPostMock = vi.fn();
        vi.doMock('../../shared/api.js', () => ({
            apiGet: apiGetMock,
            apiPost: apiPostMock,
            getApiPayload: (response, fallback = null) => {
                if (response === null || response === undefined) {
                    return fallback;
                }

                if (typeof response === 'object' && !Array.isArray(response) && Object.prototype.hasOwnProperty.call(response, 'data')) {
                    return response.data ?? fallback;
                }

                return response;
            },
            getErrorMessage: (error, fallback = 'Ocorreu um erro.') => error?.data?.message || error?.message || fallback,
        }));
        global.window = {
            __LK_CONFIG: { userId: 42 },
            LK: {
                toast: {
                    error: vi.fn(),
                },
            },
        };
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.resetModules();
        delete global.window;
    });

    it('hidrata as preferencias de ajuda com o payload lido da api v1', async () => {
        apiGetMock.mockResolvedValue({
            success: true,
            data: {
                preferences: {
                    settings: {
                        auto_offer: false,
                    },
                    tour_completed: {
                        dashboard: 'v2',
                    },
                    offer_dismissed: null,
                    tips_seen: {
                        perfil: 'v1',
                    },
                },
            },
        });

        const { fetchHelpPreferences } = await import('./persistence.js');

        await expect(fetchHelpPreferences()).resolves.toEqual({
            ok: true,
            preferences: {
                settings: {
                    auto_offer: false,
                },
                tour_completed: {
                    dashboard: 'v2',
                },
                offer_dismissed: {},
                tips_seen: {
                    perfil: 'v1',
                },
            },
        });
    });

    it('falha de forma segura quando a leitura das preferencias falha', async () => {
        apiGetMock.mockRejectedValue(new Error('falha ao carregar'));

        const { fetchHelpPreferences } = await import('./persistence.js');

        await expect(fetchHelpPreferences()).resolves.toEqual({
            ok: false,
            preferences: null,
        });
    });

    it('gera a chave de sessao da offer usando o user id mais recente do runtime config', async () => {
        const { getOfferSessionKey } = await import('./persistence.js');

        expect(getOfferSessionKey(null, { currentPage: 'dashboard', defaultVersion: 'v2' }))
            .toBe('lk_help_offer_42_dashboard_v2');

        global.window.__LK_CONFIG.userId = 84;

        expect(getOfferSessionKey(null, { currentPage: 'dashboard', defaultVersion: 'v2' }))
            .toBe('lk_help_offer_84_dashboard_v2');
    });
});