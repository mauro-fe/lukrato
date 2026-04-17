import { resolveBootstrapSource } from '../shared/bootstrap-context.js';
import { resolveNavigationShell } from '../shared/bootstrap-shell.js';

function countPreferences(preferences) {
    if (!preferences || typeof preferences !== 'object' || Array.isArray(preferences)) {
        return 0;
    }

    return Object.keys(preferences).length;
}

function resolveSessionSource(sessionPayload) {
    if (sessionPayload?.success) {
        return sessionPayload.data || {};
    }

    return sessionPayload?.errors || {};
}

function countSidebarModules(sidebarGroups) {
    if (!Array.isArray(sidebarGroups)) {
        return 0;
    }

    return sidebarGroups.reduce((total, group) => {
        if (!group || typeof group !== 'object' || !Array.isArray(group.modules)) {
            return total;
        }

        return total + group.modules.length;
    }, 0);
}

export function shouldLoadProtectedResources(sessionPayload) {
    return Boolean(
        sessionPayload?.success
        && sessionPayload?.data?.authenticated === true
        && sessionPayload?.data?.expired === false
    );
}

export function shouldOfferRenew(sessionPayload) {
    const source = resolveSessionSource(sessionPayload);

    return Boolean(source.canRenew) && Boolean(source.expired) && !Boolean(source.authenticated);
}

export function createInitialFrontendPilotState() {
    return {
        loading: false,
        renewing: false,
        writingTheme: false,
        writingDisplayName: false,
        writingHelpPreferences: false,
        writingDashboard: false,
        canRenew: false,
        resourcesLoaded: false,
        error: '',
        notice: '',
        noticeTone: 'neutral',
        lastUpdatedAt: '',
        displayNameDraft: '',
        helpAutoOfferDraft: null,
        navigation: {
            currentMenu: '',
            currentViewId: '',
            currentViewPath: '',
            breadcrumbs: [],
            sidebarGroups: [],
            footerModules: [],
        },
        snapshot: {
            sessionTone: 'neutral',
            sessionLabel: 'Aguardando bootstrap',
            sessionDetail: 'Preparando primeira leitura do contrato.',
            userName: '-',
            profileName: '-',
            theme: '-',
            helpAutoOffer: '-',
            navigationLabel: '-',
            unread: 0,
            dashboardPreferenceCount: 0,
            dashboardDetail: 'Preferências ainda não carregadas.',
            unreadDetail: 'Contagem ainda não carregada.',
            profileDetail: 'Sem bootstrap ainda.',
            helpDetail: 'Preferências ainda não carregadas.',
            navigationDetail: 'Navegação ainda não carregada.',
            contextPath: '-',
            modeLabel: 'api/v1',
        },
        payloads: {
            session: null,
            bootstrap: null,
            dashboard: null,
            unread: null,
            renew: null,
            themeWrite: null,
            displayNameWrite: null,
            helpPreferencesWrite: null,
            dashboardWrite: null,
        },
    };
}

export function resolveSelectedTheme(bootstrapPayload) {
    const bootstrap = resolveBootstrapSource(bootstrapPayload);

    return String(bootstrap.userTheme || 'system');
}

export function resolveDisplayNameProbeValue(bootstrapPayload, displayNameWritePayload = null) {
    const preferredDisplayName = displayNameWritePayload?.data?.display_name;
    if (typeof preferredDisplayName === 'string' && preferredDisplayName.trim() !== '') {
        return preferredDisplayName;
    }

    const bootstrap = resolveBootstrapSource(bootstrapPayload);
    const displayName = bootstrap.username;
    if (typeof displayName === 'string' && displayName.trim() !== '') {
        return displayName;
    }

    return '';
}

export function resolveHelpAutoOfferValue(bootstrapPayload, helpPreferencesWritePayload = null) {
    const writeValue = helpPreferencesWritePayload?.data?.preferences?.settings?.auto_offer;
    if (typeof writeValue === 'boolean') {
        return writeValue;
    }

    const bootstrap = resolveBootstrapSource(bootstrapPayload);
    const readValue = bootstrap.helpCenter?.settings?.auto_offer;
    if (typeof readValue === 'boolean') {
        return readValue;
    }

    return false;
}

export function resolveDashboardProbePreferences(dashboardPayload) {
    const preferences = dashboardPayload?.data?.preferences;
    if (!preferences || typeof preferences !== 'object' || Array.isArray(preferences)) {
        return {
            toggleGrafico: false,
            toggleMetas: false,
        };
    }

    return {
        toggleGrafico: Boolean(preferences.toggleGrafico),
        toggleMetas: Boolean(preferences.toggleMetas),
    };
}

export function buildFrontendPilotSnapshot(sessionPayload, resources = {}) {
    const session = resolveSessionSource(sessionPayload);
    const bootstrap = resolveBootstrapSource(resources.bootstrap);
    const navigation = resolveNavigationShell(resources.bootstrap);
    const dashboardPreferences = resources.dashboard?.data?.preferences || {};
    const unreadCount = Number(resources.unread?.data?.unread || 0);
    const dashboardCount = countPreferences(dashboardPreferences);
    const helpAutoOffer = bootstrap.helpCenter?.settings?.auto_offer;
    const navigationGroupCount = navigation.sidebarGroups.length;
    const navigationModuleCount = countSidebarModules(navigation.sidebarGroups);
    const currentMenu = navigation.currentMenu;
    const currentViewPath = navigation.currentViewPath;
    const resolvedContextPath = currentViewPath !== '' ? currentViewPath : (currentMenu !== '' ? currentMenu : '-');
    const planLabel = String(bootstrap.planLabel || '').trim();
    const dashboardError = resources.dashboard?.success === false
        ? String(resources.dashboard?.message || '').trim()
        : '';
    const unreadError = resources.unread?.success === false
        ? String(resources.unread?.message || '').trim()
        : '';

    let sessionTone = 'neutral';
    let sessionLabel = 'Não autenticado';

    if (session.authenticated === true) {
        sessionTone = session.showWarning ? 'warning' : 'success';
        sessionLabel = session.showWarning ? 'Autenticado com aviso' : 'Autenticado';
    } else if (session.expired === true && session.canRenew === true) {
        sessionTone = 'warning';
        sessionLabel = 'Expirada, com renovação';
    } else if (session.expired === true) {
        sessionTone = 'danger';
        sessionLabel = 'Expirada';
    }

    const remainingTime = Number(session.remainingTime || 0);

    return {
        sessionTone,
        sessionLabel,
        sessionDetail: remainingTime > 0
            ? `Tempo restante: ${remainingTime}s`
            : 'Sem tempo restante na sessão atual.',
        userName: String(session.userName || bootstrap.username || '-'),
        profileName: String(bootstrap.username || session.userName || '-'),
        profileDetail: bootstrap.userEmail
            ? `${bootstrap.userEmail}${planLabel !== '' ? ` • plano ${planLabel}` : ''}`
            : 'Bootstrap ainda não carregado.',
        theme: String(bootstrap.userTheme || '-'),
        helpAutoOffer: typeof helpAutoOffer === 'boolean'
            ? (helpAutoOffer ? 'Ativado' : 'Pausado')
            : '-',
        helpDetail: typeof helpAutoOffer === 'boolean'
            ? 'Preferência vinda de helpCenter no bootstrap autenticado.'
            : 'Preferências ainda não carregadas.',
        navigationLabel: navigationModuleCount > 0
            ? `${navigationGroupCount} grupos / ${navigationModuleCount} módulos`
            : '-',
        navigationDetail: navigationModuleCount > 0
            ? `${navigation.breadcrumbs.length} breadcrumb(s) e ${navigation.footerModules.length} atalho(s) no rodapé da shell.`
            : 'Navegação ainda não carregada.',
        unread: unreadCount,
        unreadDetail: unreadError !== ''
            ? unreadError
            : resources.unread?.data?.unread !== undefined
                ? 'Contagem vinda de /api/v1/notificacoes/unread.'
                : 'Contagem ainda não carregada.',
        dashboardPreferenceCount: dashboardCount,
        dashboardDetail: dashboardError !== ''
            ? dashboardError
            : dashboardCount > 0
                ? 'Preferências do dashboard carregadas com sucesso.'
                : 'Nenhuma preferência de dashboard disponível no payload.',
        contextPath: resolvedContextPath,
        modeLabel: 'api/v1',
    };
}

export function buildCompletedFrontendPilotState(sessionPayload, resources = {}, renewPayload = null) {
    return {
        loading: false,
        renewing: false,
        writingTheme: false,
        writingDisplayName: false,
        writingHelpPreferences: false,
        writingDashboard: false,
        canRenew: shouldOfferRenew(sessionPayload),
        resourcesLoaded: shouldLoadProtectedResources(sessionPayload),
        error: '',
        notice: '',
        noticeTone: 'neutral',
        displayNameDraft: resolveDisplayNameProbeValue(resources.bootstrap),
        helpAutoOfferDraft: resolveHelpAutoOfferValue(resources.bootstrap),
        navigation: resolveNavigationShell(resources.bootstrap),
        snapshot: buildFrontendPilotSnapshot(sessionPayload, resources),
        payloads: {
            session: sessionPayload,
            bootstrap: resources.bootstrap || null,
            dashboard: resources.dashboard || null,
            unread: resources.unread || null,
            renew: renewPayload,
            themeWrite: null,
            displayNameWrite: null,
            helpPreferencesWrite: null,
            dashboardWrite: null,
        },
    };
}

export function formatPayload(payload) {
    if (payload === null || payload === undefined) {
        return 'Aguardando...';
    }

    return JSON.stringify(payload, null, 2);
}