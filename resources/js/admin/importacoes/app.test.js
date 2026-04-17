import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';

const apiFetchMock = vi.fn();

vi.mock('../shared/api.js', () => ({
    apiFetch: apiFetchMock,
    getCSRFToken: vi.fn(() => 'csrf-token'),
    getErrorMessage: vi.fn((error, fallback) => error?.data?.message || error?.message || fallback),
}));

describe('admin/importacoes/app', () => {
    beforeEach(() => {
        vi.resetModules();
        apiFetchMock.mockReset();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.resetModules();
    });

    it('delega a leitura JSON para shared/api e retorna o payload de sucesso', async () => {
        apiFetchMock.mockResolvedValue({ success: true, data: { ok: true } });

        const { fetchApiJson } = await import('./app.js');
        const payload = await fetchApiJson('api/v1/importacoes/preview');

        expect(payload).toEqual({ success: true, data: { ok: true } });
        expect(apiFetchMock).toHaveBeenCalledWith(
            'api/v1/importacoes/preview',
            expect.objectContaining({
                headers: expect.objectContaining({
                    Accept: 'application/json',
                }),
            })
        );
    });

    it('preserva payload e messages quando a api retorna success false', async () => {
        apiFetchMock.mockResolvedValue({
            success: false,
            message: 'Arquivo invalido.',
            errors: {
                file: ['Extensao nao suportada.'],
            },
        });

        const { fetchApiJson } = await import('./app.js');

        await expect(fetchApiJson('api/v1/importacoes/preview')).rejects.toMatchObject({
            message: 'Arquivo invalido.',
            payload: {
                success: false,
                message: 'Arquivo invalido.',
            },
            messages: ['Extensao nao suportada.', 'Arquivo invalido.'],
        });
    });
});