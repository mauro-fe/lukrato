import {
    formatPayload,
    resolveDisplayNameProbeValue,
    resolveDashboardProbePreferences,
    resolveHelpAutoOfferValue,
    resolveSelectedTheme,
} from './state.js';
import { createBootstrapShellRenderer, resolveNavigationShell } from '../shared/bootstrap-shell.js';

function setText(node, value) {
    if (node) {
        node.textContent = value;
    }
}

function setPayload(node, payload) {
    if (node) {
        node.textContent = formatPayload(payload);
    }
}

function formatLastUpdated(value) {
    if (!value) {
        return 'ainda não carregado';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(value));
}

export function createFrontendPilotRenderer(root) {
    const refs = {
        flash: root.querySelector('[data-slot="flash"]'),
        sessionTone: root.querySelector('[data-slot="session-tone"]'),
        sessionLabel: root.querySelector('[data-slot="session-label"]'),
        sessionDetail: root.querySelector('[data-slot="session-detail"]'),
        profileName: root.querySelector('[data-slot="profile-name"]'),
        profileMeta: root.querySelector('[data-slot="profile-meta"]'),
        theme: root.querySelector('[data-slot="theme"]'),
        helpAutoOffer: root.querySelector('[data-slot="help-auto-offer"]'),
        helpMeta: root.querySelector('[data-slot="help-meta"]'),
        navigationLabel: root.querySelector('[data-slot="navigation-label"]'),
        navigationMeta: root.querySelector('[data-slot="navigation-meta"]'),
        dashboardCount: root.querySelector('[data-slot="dashboard-count"]'),
        dashboardMeta: root.querySelector('[data-slot="dashboard-meta"]'),
        unreadCount: root.querySelector('[data-slot="unread-count"]'),
        unreadMeta: root.querySelector('[data-slot="unread-meta"]'),
        shellCurrentMenu: root.querySelector('[data-slot="shell-current-menu"]'),
        shellCurrentView: root.querySelector('[data-slot="shell-current-view"]'),
        shellBreadcrumbs: root.querySelector('[data-slot="shell-breadcrumbs"]'),
        shellSidebar: root.querySelector('[data-slot="shell-sidebar"]'),
        shellFooter: root.querySelector('[data-slot="shell-footer"]'),
        modeLabel: root.querySelector('[data-slot="mode-label"]'),
        contextPath: root.querySelector('[data-slot="context-path"]'),
        lastUpdated: root.querySelector('[data-slot="last-updated"]'),
        reloadButton: root.querySelector('[data-action="reload"]'),
        renewButton: root.querySelector('[data-action="renew"]'),
        themeButtons: Array.from(root.querySelectorAll('[data-action="set-theme"]')),
        displayNameForm: root.querySelector('[data-role="display-name-form"]'),
        displayNameInput: root.querySelector('[data-role="display-name-input"]'),
        displayNameSaveButton: root.querySelector('[data-action="save-display-name"]'),
        helpForm: root.querySelector('[data-role="help-form"]'),
        helpToggle: root.querySelector('[data-help-toggle="auto_offer"]'),
        helpSaveButton: root.querySelector('[data-action="save-help-preferences"]'),
        dashboardForm: root.querySelector('[data-role="dashboard-form"]'),
        dashboardSaveButton: root.querySelector('[data-action="save-dashboard"]'),
        dashboardToggles: Array.from(root.querySelectorAll('[data-dashboard-toggle]')),
        payloads: {
            bootstrap: root.querySelector('[data-payload="bootstrap"]'),
            session: root.querySelector('[data-payload="session"]'),
            dashboard: root.querySelector('[data-payload="dashboard"]'),
            unread: root.querySelector('[data-payload="unread"]'),
            renew: root.querySelector('[data-payload="renew"]'),
            themeWrite: root.querySelector('[data-payload="theme-write"]'),
            displayNameWrite: root.querySelector('[data-payload="display-name-write"]'),
            helpPreferencesWrite: root.querySelector('[data-payload="help-preferences-write"]'),
            dashboardWrite: root.querySelector('[data-payload="dashboard-write"]'),
        },
    };
    const shellRenderer = createBootstrapShellRenderer({
        currentMenuNode: refs.shellCurrentMenu,
        currentViewNode: refs.shellCurrentView,
        breadcrumbsNode: refs.shellBreadcrumbs,
        sidebarNode: refs.shellSidebar,
        footerNode: refs.shellFooter,
        classPrefix: 'frontend-pilot-shell',
    });

    return {
        bind(handlers) {
            refs.reloadButton?.addEventListener('click', handlers.onReload);
            refs.renewButton?.addEventListener('click', handlers.onRenew);
            refs.themeButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    handlers.onThemeChange(button.dataset.themeValue || 'system');
                });
            });
            refs.displayNameInput?.addEventListener('input', (event) => {
                handlers.onDisplayNameInput(event.target.value);
            });
            refs.displayNameForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                handlers.onDisplayNameSave(refs.displayNameInput?.value || '');
            });
            refs.helpToggle?.addEventListener('input', (event) => {
                handlers.onHelpPreferencesInput(Boolean(event.target.checked));
            });
            refs.helpForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                handlers.onHelpPreferencesSave(Boolean(refs.helpToggle?.checked));
            });
            refs.dashboardForm?.addEventListener('submit', (event) => {
                event.preventDefault();

                const payload = refs.dashboardToggles.reduce((accumulator, input) => {
                    accumulator[input.dataset.dashboardToggle || input.name] = input.checked;
                    return accumulator;
                }, {});

                handlers.onDashboardSave(payload);
            });
        },

        render(state) {
            const snapshot = state.snapshot;
            const navigation = state.navigation || resolveNavigationShell(state.payloads.bootstrap);
            const selectedTheme = resolveSelectedTheme(state.payloads.bootstrap);
            const resolvedDisplayName = state.displayNameDraft || resolveDisplayNameProbeValue(
                state.payloads.bootstrap,
                state.payloads.displayNameWrite
            );
            const resolvedHelpAutoOffer = resolveHelpAutoOfferValue(
                state.payloads.bootstrap,
                state.payloads.helpPreferencesWrite
            );
            const helpAutoOfferValue = typeof state.helpAutoOfferDraft === 'boolean'
                ? state.helpAutoOfferDraft
                : resolvedHelpAutoOffer;
            const dashboardProbePreferences = resolveDashboardProbePreferences(state.payloads.dashboard);
            const isBusy = state.loading
                || state.renewing
                || state.writingTheme
                || state.writingDisplayName
                || state.writingHelpPreferences
                || state.writingDashboard;

            if (refs.flash) {
                const flashMessage = state.error !== ''
                    ? state.error
                    : state.loading
                        ? 'Carregando bootstrap autenticado do piloto...'
                        : state.writingTheme
                            ? 'Persistindo nova preferência de tema...'
                            : state.writingDisplayName
                                ? 'Persistindo nome de exibição...'
                                : state.writingHelpPreferences
                                    ? 'Persistindo preferências de ajuda...'
                                    : state.writingDashboard
                                        ? 'Persistindo preferências do dashboard...'
                                        : state.notice !== ''
                                            ? state.notice
                                            : state.canRenew
                                                ? 'A sessão expirou, mas a rota de renew ainda pode recuperar o contexto.'
                                                : 'Tela consumindo apenas contratos v1 já congelados.';

                refs.flash.hidden = flashMessage === '';
                refs.flash.textContent = flashMessage;
                refs.flash.dataset.tone = state.error !== ''
                    ? 'danger'
                    : state.notice !== ''
                        ? state.noticeTone
                        : (state.canRenew ? 'warning' : 'neutral');
            }

            if (refs.sessionTone) {
                refs.sessionTone.textContent = snapshot.sessionTone;
                refs.sessionTone.dataset.tone = snapshot.sessionTone;
            }

            setText(refs.sessionLabel, snapshot.sessionLabel);
            setText(refs.sessionDetail, snapshot.sessionDetail);
            setText(refs.profileName, snapshot.profileName);
            setText(refs.profileMeta, snapshot.profileDetail);
            setText(refs.theme, snapshot.theme);
            setText(refs.helpAutoOffer, snapshot.helpAutoOffer);
            setText(refs.helpMeta, snapshot.helpDetail);
            setText(refs.navigationLabel, snapshot.navigationLabel);
            setText(refs.navigationMeta, snapshot.navigationDetail);
            setText(refs.dashboardCount, String(snapshot.dashboardPreferenceCount));
            setText(refs.dashboardMeta, snapshot.dashboardDetail);
            setText(refs.unreadCount, String(snapshot.unread));
            setText(refs.unreadMeta, snapshot.unreadDetail);
            setText(refs.modeLabel, snapshot.modeLabel);
            setText(refs.contextPath, snapshot.contextPath);
            setText(refs.lastUpdated, formatLastUpdated(state.lastUpdatedAt));

            shellRenderer.render(navigation);

            if (refs.reloadButton) {
                refs.reloadButton.disabled = isBusy;
            }

            if (refs.renewButton) {
                refs.renewButton.hidden = !state.canRenew;
                refs.renewButton.disabled = isBusy;
            }

            refs.themeButtons.forEach((button) => {
                const isActive = button.dataset.themeValue === selectedTheme;
                button.disabled = isBusy;
                button.dataset.active = isActive ? 'true' : 'false';
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            if (refs.displayNameInput) {
                if (refs.displayNameInput.value !== resolvedDisplayName) {
                    refs.displayNameInput.value = resolvedDisplayName;
                }

                refs.displayNameInput.disabled = isBusy;
            }

            if (refs.displayNameSaveButton) {
                refs.displayNameSaveButton.disabled = isBusy;
            }

            if (refs.helpToggle) {
                refs.helpToggle.checked = helpAutoOfferValue;
                refs.helpToggle.disabled = isBusy;
            }

            if (refs.helpSaveButton) {
                refs.helpSaveButton.disabled = isBusy;
            }

            refs.dashboardToggles.forEach((input) => {
                const key = input.dataset.dashboardToggle || input.name;
                input.checked = Boolean(dashboardProbePreferences[key]);
                input.disabled = isBusy;
            });

            if (refs.dashboardSaveButton) {
                refs.dashboardSaveButton.disabled = isBusy;
            }

            setPayload(refs.payloads.bootstrap, state.payloads.bootstrap);
            setPayload(refs.payloads.session, state.payloads.session);
            setPayload(refs.payloads.dashboard, state.payloads.dashboard);
            setPayload(refs.payloads.unread, state.payloads.unread);
            setPayload(refs.payloads.renew, state.payloads.renew);
            setPayload(refs.payloads.themeWrite, state.payloads.themeWrite);
            setPayload(refs.payloads.displayNameWrite, state.payloads.displayNameWrite);
            setPayload(refs.payloads.helpPreferencesWrite, state.payloads.helpPreferencesWrite);
            setPayload(refs.payloads.dashboardWrite, state.payloads.dashboardWrite);
        },
    };
}