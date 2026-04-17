// @vitest-environment jsdom

import { createBootstrapShellRenderer, resolveNavigationShell } from './bootstrap-shell.js';

describe('admin/shared/bootstrap-shell', () => {
    it('normaliza pageContext em uma estrutura navegável', () => {
        const navigation = resolveNavigationShell({
            currentMenu: 'perfil',
            currentViewId: 'admin-perfil-index',
            currentViewPath: 'admin/perfil/index',
            pageContext: {
                breadcrumbs: [{ label: 'Perfil', icon: 'user' }],
                sidebar: {
                    Principal: [
                        { key: 'dashboard', label: 'Dashboard', route: 'dashboard', menu: 'dashboard', icon: 'home' },
                    ],
                },
                footerModules: [
                    { key: 'perfil', label: 'Perfil', route: 'perfil', menu: 'perfil', icon: 'user' },
                ],
            },
        });

        expect(navigation.currentMenu).toBe('perfil');
        expect(navigation.breadcrumbs[0]).toMatchObject({ label: 'Perfil', icon: 'user' });
        expect(navigation.sidebarGroups[0].modules[0]).toMatchObject({ label: 'Dashboard', isActive: false });
        expect(navigation.footerModules[0]).toMatchObject({ label: 'Perfil', isActive: true });
    });

    it('renderiza breadcrumbs, sidebar e rodape com prefixo de classe customizado', () => {
        document.body.innerHTML = `
            <div>
                <strong data-slot="menu"></strong>
                <strong data-slot="view"></strong>
                <nav data-slot="breadcrumbs"></nav>
                <aside data-slot="sidebar"></aside>
                <div data-slot="footer"></div>
            </div>
        `;

        const renderer = createBootstrapShellRenderer({
            currentMenuNode: document.querySelector('[data-slot="menu"]'),
            currentViewNode: document.querySelector('[data-slot="view"]'),
            breadcrumbsNode: document.querySelector('[data-slot="breadcrumbs"]'),
            sidebarNode: document.querySelector('[data-slot="sidebar"]'),
            footerNode: document.querySelector('[data-slot="footer"]'),
            classPrefix: 'test-shell',
        });

        renderer.render(resolveNavigationShell({
            currentMenu: 'perfil',
            currentViewPath: 'admin/perfil/index',
            pageContext: {
                breadcrumbs: [{ label: 'Perfil', icon: 'user' }],
                sidebar: {
                    Principal: [
                        { key: 'dashboard', label: 'Dashboard', route: 'dashboard', menu: 'dashboard', icon: 'home' },
                    ],
                },
                footerModules: [
                    { key: 'perfil', label: 'Perfil', route: 'perfil', menu: 'perfil', icon: 'user' },
                ],
            },
        }));

        expect(document.querySelector('[data-slot="menu"]')?.textContent).toBe('perfil');
        expect(document.querySelector('[data-slot="view"]')?.textContent).toBe('admin/perfil/index');
        expect(document.querySelector('[data-slot="breadcrumbs"]')?.textContent).toContain('Perfil');
        expect(document.querySelector('[data-slot="sidebar"] .test-shell-link')?.textContent).toContain('Dashboard');
        expect(document.querySelector('[data-slot="footer"] .test-shell-chip[data-active="true"]')?.textContent).toContain('Perfil');
    });
});