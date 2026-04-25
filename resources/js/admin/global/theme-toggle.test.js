// @vitest-environment jsdom

import { beforeEach, describe, expect, it, vi } from 'vitest';

function buildThemeControls() {
    document.body.innerHTML = `
        <section>
            <button type="button" data-theme-choice="system" aria-pressed="false">Sistema</button>
            <button type="button" data-theme-choice="light" aria-pressed="false">Claro</button>
            <button type="button" data-theme-choice="dark" aria-pressed="false">Escuro</button>
        </section>
    `;
}

async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
}

describe('admin/global/theme-toggle', () => {
    let apiGetMock;
    let apiPostMock;
    let mediaQueryMock;

    beforeEach(() => {
        vi.resetModules();
        apiGetMock = vi.fn();
        apiPostMock = vi.fn().mockResolvedValue({ success: true });
        mediaQueryMock = {
            matches: false,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        };

        vi.doMock('../shared/api.js', () => ({
            apiGet: apiGetMock,
            apiPost: apiPostMock,
        }));

        document.body.innerHTML = '';
        document.documentElement.removeAttribute('data-theme');
        window.localStorage.clear();
        window.matchMedia = vi.fn().mockReturnValue(mediaQueryMock);
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.resetModules();
        document.body.innerHTML = '';
        document.documentElement.removeAttribute('data-theme');
        window.localStorage.clear();
    });

    it('hidrata os botoes de configuracoes com a preferencia system', async () => {
        buildThemeControls();
        apiGetMock.mockResolvedValue({
            success: true,
            data: { theme: 'system' },
        });

        await import('./theme-toggle.js');
        await flushPromises();

        const systemButton = document.querySelector('[data-theme-choice="system"]');
        const lightButton = document.querySelector('[data-theme-choice="light"]');
        const darkButton = document.querySelector('[data-theme-choice="dark"]');

        expect(document.documentElement.getAttribute('data-theme')).toBe('light');
        expect(systemButton?.classList.contains('is-active')).toBe(true);
        expect(systemButton?.getAttribute('aria-pressed')).toBe('true');
        expect(lightButton?.getAttribute('aria-pressed')).toBe('false');
        expect(darkButton?.getAttribute('aria-pressed')).toBe('false');
        expect(window.localStorage.getItem('lukrato-theme')).toBe('system');
    });

    it('salva o tema escolhido pelos botoes de configuracoes', async () => {
        buildThemeControls();
        apiGetMock.mockResolvedValue({ success: false });

        await import('./theme-toggle.js');
        await flushPromises();

        const darkButton = document.querySelector('[data-theme-choice="dark"]');
        darkButton?.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await flushPromises();

        expect(apiPostMock).toHaveBeenCalledWith('api/v1/user/theme', { theme: 'dark' });
        expect(document.documentElement.getAttribute('data-theme')).toBe('dark');
        expect(darkButton?.classList.contains('is-active')).toBe(true);
        expect(darkButton?.getAttribute('aria-pressed')).toBe('true');
        expect(window.localStorage.getItem('lukrato-theme')).toBe('dark');
    });
});
