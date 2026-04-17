import {
    buildCompletedFrontendPilotState,
    buildFrontendPilotSnapshot,
    formatPayload,
    resolveDisplayNameProbeValue,
    resolveHelpAutoOfferValue,
    resolveDashboardProbePreferences,
    resolveSelectedTheme,
    shouldLoadProtectedResources,
    shouldOfferRenew,
} from './state.js';
import { resolveNavigationShell } from '../shared/bootstrap-shell.js';

describe('frontend-pilot/state', () => {
    it('carrega recursos protegidos apenas quando a sessao esta autenticada', () => {
        expect(shouldLoadProtectedResources({
            success: true,
            data: { authenticated: true, expired: false },
        })).toBe(true);

        expect(shouldLoadProtectedResources({
            success: true,
            data: { authenticated: false, expired: true },
        })).toBe(false);
    });

    it('oferece renovacao quando a sessao expirou mas ainda pode ser renovada', () => {
        expect(shouldOfferRenew({
            success: true,
            data: { authenticated: false, expired: true, canRenew: true },
        })).toBe(true);

        expect(shouldOfferRenew({
            success: false,
            errors: { authenticated: false, expired: true, canRenew: false },
        })).toBe(false);
    });

    it('combina os payloads em um snapshot legivel para a tela', () => {
        const snapshot = buildFrontendPilotSnapshot(
            {
                success: true,
                data: {
                    authenticated: true,
                    expired: false,
                    canRenew: true,
                    showWarning: false,
                    remainingTime: 1200,
                    userName: 'Session User',
                },
            },
            {
                bootstrap: {
                    success: true,
                    data: {
                        username: 'Bootstrap User',
                        userEmail: 'perfil@example.com',
                        userTheme: 'dark',
                        currentMenu: 'perfil',
                        currentViewPath: 'admin/frontend-pilot/index',
                        planLabel: 'FREE',
                        helpCenter: {
                            settings: { auto_offer: false },
                        },
                        pageContext: {
                            sidebar: {
                                Principal: [
                                    { key: 'dashboard', label: 'Dashboard', route: 'dashboard', menu: 'dashboard', icon: 'home' },
                                    { key: 'lancamentos', label: 'Lançamentos', route: 'lancamentos', menu: 'lancamentos', icon: 'wallet' },
                                ],
                                Planejamento: [
                                    { key: 'orcamento', label: 'Orçamento', route: 'orcamento', menu: 'orcamento', icon: 'target' },
                                ],
                            },
                            breadcrumbs: [{ label: 'Perfil', icon: 'user' }],
                            footerModules: [
                                { key: 'configuracoes', label: 'Configurações', route: 'configuracoes', menu: 'configuracoes', icon: 'settings' },
                                { key: 'perfil', label: 'Perfil', route: 'perfil', menu: 'perfil', icon: 'user' },
                            ],
                        },
                    },
                },
                dashboard: { data: { preferences: { toggleGrafico: true, toggleMetas: true } } },
                unread: { data: { unread: 7 } },
            }
        );

        expect(snapshot.sessionLabel).toBe('Autenticado');
        expect(snapshot.profileName).toBe('Bootstrap User');
        expect(snapshot.theme).toBe('dark');
        expect(snapshot.helpAutoOffer).toBe('Pausado');
        expect(snapshot.navigationLabel).toBe('2 grupos / 3 módulos');
        expect(snapshot.navigationDetail).toBe('1 breadcrumb(s) e 2 atalho(s) no rodapé da shell.');
        expect(snapshot.unread).toBe(7);
        expect(snapshot.dashboardPreferenceCount).toBe(2);
        expect(snapshot.contextPath).toBe('admin/frontend-pilot/index');
    });

    it('gera um estado completo com payloads crus e flag de renovacao', () => {
        const state = buildCompletedFrontendPilotState(
            {
                success: true,
                data: {
                    authenticated: false,
                    expired: true,
                    canRenew: true,
                    remainingTime: 0,
                },
            },
            {
                bootstrap: {
                    success: true,
                    data: {
                        username: 'Bootstrap User',
                        currentViewPath: 'admin/frontend-pilot/index',
                    },
                },
            },
            { success: true, data: { newToken: 'abc' } }
        );

        expect(state.canRenew).toBe(true);
        expect(state.payloads.renew).toEqual({ success: true, data: { newToken: 'abc' } });
        expect(state.payloads.bootstrap).toEqual({
            success: true,
            data: {
                username: 'Bootstrap User',
                currentViewPath: 'admin/frontend-pilot/index',
            },
        });
        expect(state.snapshot.sessionLabel).toBe('Expirada, com renovação');
    });

    it('resolve o tema selecionado a partir do bootstrap carregado', () => {
        expect(resolveSelectedTheme({ data: { userTheme: 'dark' } })).toBe('dark');
        expect(resolveSelectedTheme(null)).toBe('system');
    });

    it('prioriza o ultimo display name salvo ao hidratar a sonda', () => {
        expect(resolveDisplayNameProbeValue(
            { data: { username: 'Perfil User' } },
            { data: { display_name: 'Maria Silva' } }
        )).toBe('Maria Silva');
    });

    it('usa o nome exposto pelo bootstrap quando ainda nao existe write de display name', () => {
        expect(resolveDisplayNameProbeValue({
            data: {
                username: 'Perfil User',
            },
        })).toBe('Perfil User');
    });

    it('prioriza o ultimo valor salvo de auto_offer ao hidratar help preferences', () => {
        expect(resolveHelpAutoOfferValue(
            { data: { helpCenter: { settings: { auto_offer: false } } } },
            { data: { preferences: { settings: { auto_offer: true } } } }
        )).toBe(true);
    });

    it('usa o helpCenter do bootstrap quando ainda nao existe write', () => {
        expect(resolveHelpAutoOfferValue({
            data: {
                helpCenter: {
                    settings: { auto_offer: false },
                },
            },
        })).toBe(false);
    });

    it('extrai apenas os toggles usados pela sonda de dashboard', () => {
        expect(resolveDashboardProbePreferences({
            data: {
                preferences: {
                    toggleGrafico: true,
                    toggleMetas: false,
                    toggleCartoes: true,
                },
            },
        })).toEqual({
            toggleGrafico: true,
            toggleMetas: false,
        });
    });

    it('normaliza a shell de navegação a partir do pageContext do bootstrap', () => {
        const navigation = resolveNavigationShell({
            data: {
                currentMenu: 'perfil',
                currentViewId: 'admin-frontend-pilot-index',
                currentViewPath: 'admin/frontend-pilot/index',
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
            },
        });

        expect(navigation.currentMenu).toBe('perfil');
        expect(navigation.breadcrumbs[0]).toMatchObject({ label: 'Perfil', icon: 'user' });
        expect(navigation.sidebarGroups).toHaveLength(1);
        expect(navigation.sidebarGroups[0].modules[0]).toMatchObject({ label: 'Dashboard', isActive: false });
        expect(navigation.footerModules[0]).toMatchObject({ label: 'Perfil', isActive: true });
    });

    it('usa placeholder consistente para payload ausente', () => {
        expect(formatPayload(null)).toBe('Aguardando...');
    });
});