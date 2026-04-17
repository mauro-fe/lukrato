import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';

describe('admin/shared/api', () => {
    beforeEach(() => {
        vi.resetModules();
        global.window = {
            location: {
                hostname: 'localhost',
                pathname: '/dashboard',
            },
            __LK_CONFIG: {
                baseUrl: 'https://lukrato.com.br/',
                apiBaseUrl: 'https://lukrato.com.br/',
            },
        };
        global.document = {
            querySelector: vi.fn(() => null),
        };
        global.location = global.window.location;
        global.fetch = vi.fn();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.resetModules();
        delete global.fetch;
        delete global.location;
        delete global.document;
        delete global.window;
    });

    it('resolve a api base URL separadamente da base de navegacao', async () => {
        const { getBaseUrl, getApiBaseUrl, getCSRFToken, buildAppUrl, buildAssetUrl, buildUrl } = await import('./api.js');

        expect(getBaseUrl()).toBe('https://lukrato.com.br/');
        expect(getApiBaseUrl()).toBe('https://lukrato.com.br/');
        expect(getCSRFToken()).toBe('');
        expect(buildAppUrl('billing')).toBe('https://lukrato.com.br/billing');
        expect(buildAppUrl('login', { return: 'dashboard' })).toBe('https://lukrato.com.br/login?return=dashboard');
        expect(buildAssetUrl('audio/success.mp3')).toBe('https://lukrato.com.br/assets/audio/success.mp3');
        expect(buildUrl('api/v1/user/bootstrap', { menu: 'perfil' })).toBe(
            'https://lukrato.com.br/api/v1/user/bootstrap?menu=perfil'
        );
    });

    it('prioriza input e meta ao resolver o CSRF token compartilhado', async () => {
        global.window.CSRF = 'legacy-token';
        global.document.querySelector = vi.fn((selector) => {
            if (selector === 'input[name="csrf_token"]') {
                return { value: 'input-token' };
            }

            if (selector === 'meta[name="csrf-token"]') {
                return { content: 'meta-token' };
            }

            return null;
        });

        const { getCSRFToken } = await import('./api.js');

        expect(getCSRFToken()).toBe('input-token');

        global.document.querySelector = vi.fn((selector) => {
            if (selector === 'input[name="csrf_token"]') {
                return { value: '' };
            }

            if (selector === 'meta[name="csrf-token"]') {
                return { content: 'meta-token' };
            }

            return null;
        });

        expect(getCSRFToken()).toBe('meta-token');

        global.document.querySelector = vi.fn(() => null);

        expect(getCSRFToken()).toBe('legacy-token');
    });

    it('usa credentials include nas requisicoes da camada compartilhada', async () => {
        global.fetch.mockResolvedValue({
            ok: true,
            status: 200,
            headers: {
                get: vi.fn(() => 'application/json'),
            },
            json: vi.fn(async () => ({ success: true, data: { ok: true } })),
        });

        const { apiGet } = await import('./api.js');

        await expect(apiGet('api/v1/user/bootstrap', { menu: 'perfil' })).resolves.toEqual({
            success: true,
            data: { ok: true },
        });

        expect(global.fetch).toHaveBeenCalledWith(
            'https://lukrato.com.br/api/v1/user/bootstrap?menu=perfil',
            expect.objectContaining({
                method: 'GET',
                credentials: 'include',
            })
        );
    });

    it('retorna a resposta bruta quando responseType=response', async () => {
        const rawResponse = {
            ok: true,
            status: 200,
            headers: {
                get: vi.fn(() => 'application/pdf'),
            },
            blob: vi.fn(async () => new Blob(['pdf'])),
        };

        global.fetch.mockResolvedValue(rawResponse);

        const { apiFetch } = await import('./api.js');
        const response = await apiFetch('api/v1/relatorios/export', { method: 'GET' }, { responseType: 'response' });

        expect(response).toBe(rawResponse);
        expect(global.fetch).toHaveBeenCalledWith(
            'https://lukrato.com.br/api/v1/relatorios/export',
            expect.objectContaining({
                method: 'GET',
                credentials: 'include',
            })
        );
    });

    it('falha cedo quando o navegador está offline', async () => {
        const originalNavigatorDescriptor = Object.getOwnPropertyDescriptor(globalThis, 'navigator');
        Object.defineProperty(globalThis, 'navigator', {
            configurable: true,
            value: {
                ...(originalNavigatorDescriptor?.value || global.navigator || {}),
                onLine: false,
            },
        });
        global.document = {
            ...global.document,
            body: {
                appendChild: vi.fn(),
            },
            getElementById: vi.fn(() => null),
            createElement: vi.fn(() => ({
                querySelector: vi.fn(() => ({
                    addEventListener: vi.fn(),
                })),
            })),
        };

        const { apiGet } = await import('./api.js');

        await expect(apiGet('api/v1/user/bootstrap')).rejects.toThrow('Você está offline. Verifique sua conexão.');
        expect(global.fetch).not.toHaveBeenCalled();

        if (originalNavigatorDescriptor) {
            Object.defineProperty(globalThis, 'navigator', originalNavigatorDescriptor);
        } else {
            delete globalThis.navigator;
        }
    });

    it('notifica plan limits quando a api responde 403 com limit_reached', async () => {
        const handleApiLimitReached = vi.fn();
        const payload = {
            message: 'Limite do plano atingido.',
            errors: {
                limit_reached: true,
                upgrade_url: 'billing',
            },
        };

        global.window.PlanLimits = {
            handleApiLimitReached,
        };

        global.fetch.mockResolvedValue({
            ok: false,
            status: 403,
            statusText: 'Forbidden',
            headers: {
                get: vi.fn(() => 'application/json'),
            },
            clone: vi.fn(function clone() {
                return this;
            }),
            json: vi.fn(async () => payload),
        });

        const { apiGet } = await import('./api.js');

        await expect(apiGet('api/v1/user/bootstrap')).rejects.toThrow('Limite do plano atingido.');
        expect(handleApiLimitReached).toHaveBeenCalledWith(payload);
    });
});