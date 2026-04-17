// @vitest-environment jsdom

import { buildCompletedFrontendPilotState } from './state.js';
import { createFrontendPilotRenderer } from './render.js';

function buildRoot() {
    document.body.innerHTML = `
        <section data-frontend-pilot-root>
            <section data-slot="flash"></section>
            <span data-slot="session-tone"></span>
            <span data-slot="session-label"></span>
            <span data-slot="session-detail"></span>
            <span data-slot="profile-name"></span>
            <span data-slot="profile-meta"></span>
            <span data-slot="theme"></span>
            <span data-slot="help-auto-offer"></span>
            <span data-slot="help-meta"></span>
            <span data-slot="navigation-label"></span>
            <span data-slot="navigation-meta"></span>
            <span data-slot="dashboard-count"></span>
            <span data-slot="dashboard-meta"></span>
            <span data-slot="unread-count"></span>
            <span data-slot="unread-meta"></span>
            <span data-slot="shell-current-menu"></span>
            <span data-slot="shell-current-view"></span>
            <nav data-slot="shell-breadcrumbs"></nav>
            <div data-slot="shell-sidebar"></div>
            <div data-slot="shell-footer"></div>
            <span data-slot="mode-label"></span>
            <span data-slot="context-path"></span>
            <span data-slot="last-updated"></span>
            <button data-action="reload"></button>
            <button data-action="renew"></button>
            <button data-action="set-theme" data-theme-value="light"></button>
            <button data-action="set-theme" data-theme-value="dark"></button>
            <button data-action="set-theme" data-theme-value="system"></button>
            <form data-role="display-name-form">
                <input data-role="display-name-input">
                <button data-action="save-display-name"></button>
            </form>
            <form data-role="help-form">
                <input type="checkbox" data-help-toggle="auto_offer">
                <button data-action="save-help-preferences"></button>
            </form>
            <form data-role="dashboard-form">
                <input type="checkbox" data-dashboard-toggle="toggleGrafico">
                <input type="checkbox" data-dashboard-toggle="toggleMetas">
                <button data-action="save-dashboard"></button>
            </form>
            <pre data-payload="bootstrap"></pre>
            <pre data-payload="session"></pre>
            <pre data-payload="dashboard"></pre>
            <pre data-payload="unread"></pre>
            <pre data-payload="renew"></pre>
            <pre data-payload="theme-write"></pre>
            <pre data-payload="display-name-write"></pre>
            <pre data-payload="help-preferences-write"></pre>
            <pre data-payload="dashboard-write"></pre>
        </section>
    `;

    return document.querySelector('[data-frontend-pilot-root]');
}

describe('frontend-pilot/render', () => {
    it('renderiza a shell de navegação a partir do bootstrap autenticado', () => {
        const root = buildRoot();
        const renderer = createFrontendPilotRenderer(root);
        const state = {
            ...buildCompletedFrontendPilotState(
                {
                    success: true,
                    data: {
                        authenticated: true,
                        expired: false,
                        canRenew: false,
                        remainingTime: 1800,
                    },
                },
                {
                    bootstrap: {
                        success: true,
                        data: {
                            username: 'Bootstrap User',
                            userTheme: 'dark',
                            currentMenu: 'perfil',
                            currentViewPath: 'admin/frontend-pilot/index',
                            pageContext: {
                                breadcrumbs: [{ label: 'Perfil', icon: 'user' }],
                                sidebar: {
                                    Principal: [
                                        { key: 'dashboard', label: 'Dashboard', route: 'dashboard', menu: 'dashboard', icon: 'home' },
                                        { key: 'lancamentos', label: 'Lançamentos', route: 'lancamentos', menu: 'lancamentos', icon: 'wallet' },
                                    ],
                                },
                                footerModules: [
                                    { key: 'configuracoes', label: 'Configurações', route: 'configuracoes', menu: 'configuracoes', icon: 'settings' },
                                    { key: 'perfil', label: 'Perfil', route: 'perfil', menu: 'perfil', icon: 'user' },
                                ],
                            },
                        },
                    },
                    dashboard: { data: { preferences: { toggleGrafico: true, toggleMetas: false } } },
                    unread: { data: { unread: 3 } },
                }
            ),
            lastUpdatedAt: '2026-04-11T12:00:00.000Z',
        };

        renderer.render(state);

        expect(root.querySelector('[data-slot="shell-current-menu"]')?.textContent).toBe('perfil');
        expect(root.querySelector('[data-slot="shell-current-view"]')?.textContent).toBe('admin/frontend-pilot/index');
        expect(root.querySelector('[data-slot="shell-breadcrumbs"]')?.textContent).toContain('Perfil');
        expect(root.querySelector('[data-slot="shell-sidebar"]')?.textContent).toContain('Dashboard');

        const activeFooterLink = root.querySelector('[data-slot="shell-footer"] [data-active="true"]');
        expect(activeFooterLink?.textContent).toContain('Perfil');
    });
});