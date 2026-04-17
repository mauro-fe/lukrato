import { createFrontendPilotApi } from './api.js';
import { readFrontendPilotConfig } from './config.js';
import { applyRuntimeConfig } from '../global/runtime-config.js';
import { createFrontendPilotRenderer } from './render.js';
import {
    buildCompletedFrontendPilotState,
    createInitialFrontendPilotState,
    resolveDisplayNameProbeValue,
    resolveHelpAutoOfferValue,
    shouldLoadProtectedResources,
} from './state.js';

async function loadProtectedResources(api, sessionPayload, config) {
    if (!shouldLoadProtectedResources(sessionPayload)) {
        return {};
    }

    const bootstrap = await api.getBootstrap(config.context || {});
    if (!bootstrap?.success) {
        throw new Error(bootstrap?.message || 'Falha ao carregar o bootstrap autenticado.');
    }

    applyRuntimeConfig(bootstrap.data || {}, {
        source: 'frontend-pilot-bootstrap',
    });

    const [dashboard, unread] = await Promise.all([
        api.getDashboardPreferences(),
        api.getUnreadNotifications(),
    ]);

    return { bootstrap, dashboard, unread };
}

export function createFrontendPilotApp({ root, config, api, releasePreboot }) {
    const resolvedApi = api || createFrontendPilotApi(config.endpoints || {});
    const renderer = createFrontendPilotRenderer(root);
    let state = createInitialFrontendPilotState();

    const updateState = (nextState) => {
        state = {
            ...state,
            ...nextState,
        };
        renderer.render(state);
    };

    const safeReleasePreboot = () => {
        if (typeof releasePreboot === 'function') {
            releasePreboot();
        }
    };

    const load = async (options = {}) => {
        const notice = typeof options.notice === 'string' ? options.notice : '';
        const noticeTone = typeof options.noticeTone === 'string' ? options.noticeTone : 'neutral';

        updateState({
            loading: true,
            renewing: false,
            error: '',
            notice: '',
            noticeTone: 'neutral',
        });

        try {
            const sessionPayload = await resolvedApi.getSessionStatus();
            const resources = await loadProtectedResources(resolvedApi, sessionPayload, config);
            const completedState = buildCompletedFrontendPilotState(
                sessionPayload,
                resources,
                state.payloads.renew
            );
            const nextDisplayNameDraft = shouldLoadProtectedResources(sessionPayload)
                ? resolveDisplayNameProbeValue(resources.bootstrap, state.payloads.displayNameWrite)
                : state.displayNameDraft;
            const nextHelpAutoOfferDraft = shouldLoadProtectedResources(sessionPayload)
                ? resolveHelpAutoOfferValue(resources.bootstrap, state.payloads.helpPreferencesWrite)
                : state.helpAutoOfferDraft;

            updateState({
                ...completedState,
                notice,
                noticeTone,
                lastUpdatedAt: new Date().toISOString(),
                displayNameDraft: nextDisplayNameDraft,
                helpAutoOfferDraft: nextHelpAutoOfferDraft,
                payloads: {
                    ...completedState.payloads,
                    themeWrite: state.payloads.themeWrite,
                    displayNameWrite: state.payloads.displayNameWrite,
                    helpPreferencesWrite: state.payloads.helpPreferencesWrite,
                    dashboardWrite: state.payloads.dashboardWrite,
                },
            });
        } catch (error) {
            updateState({
                loading: false,
                renewing: false,
                error: error?.message || 'Falha ao carregar o bootstrap do piloto.',
            });
        } finally {
            safeReleasePreboot();
        }
    };

    const renew = async () => {
        updateState({ renewing: true, error: '', notice: '', noticeTone: 'neutral' });

        const renewPayload = await resolvedApi.renewSession();

        if (!renewPayload?.success) {
            updateState({
                renewing: false,
                error: renewPayload?.message || 'Não foi possível renovar a sessão.',
                payloads: {
                    ...state.payloads,
                    renew: renewPayload,
                },
            });
            safeReleasePreboot();
            return;
        }

        state = {
            ...state,
            payloads: {
                ...state.payloads,
                renew: renewPayload,
            },
        };

        await load({
            notice: renewPayload?.message || 'Sessão renovada com sucesso.',
            noticeTone: 'success',
        });
    };

    const updateTheme = async (theme) => {
        updateState({ writingTheme: true, error: '', notice: '', noticeTone: 'neutral' });

        const response = await resolvedApi.updateTheme(theme);

        if (!response?.success) {
            updateState({
                writingTheme: false,
                error: response?.message || 'Não foi possível atualizar o tema.',
                payloads: {
                    ...state.payloads,
                    themeWrite: response,
                },
            });
            safeReleasePreboot();
            return;
        }

        state = {
            ...state,
            payloads: {
                ...state.payloads,
                themeWrite: response,
            },
        };

        await load({
            notice: response?.data?.message || 'Tema atualizado com sucesso.',
            noticeTone: 'success',
        });
    };

    const updateDisplayName = async (displayName) => {
        updateState({ writingDisplayName: true, error: '', notice: '', noticeTone: 'neutral' });

        const response = await resolvedApi.updateDisplayName(displayName);

        if (!response?.success) {
            updateState({
                writingDisplayName: false,
                error: response?.message || 'Não foi possível atualizar o nome de exibição.',
                payloads: {
                    ...state.payloads,
                    displayNameWrite: response,
                },
                displayNameDraft: displayName,
            });
            safeReleasePreboot();
            return;
        }

        state = {
            ...state,
            payloads: {
                ...state.payloads,
                displayNameWrite: response,
            },
            displayNameDraft: displayName,
        };

        await load({
            notice: response?.data?.message || 'Nome de exibição atualizado com sucesso.',
            noticeTone: 'success',
        });
    };

    const updateDashboardPreferences = async (payload) => {
        updateState({ writingDashboard: true, error: '', notice: '', noticeTone: 'neutral' });

        const response = await resolvedApi.updateDashboardPreferences(payload);

        if (!response?.success) {
            updateState({
                writingDashboard: false,
                error: response?.message || 'Não foi possível salvar as preferências do dashboard.',
                payloads: {
                    ...state.payloads,
                    dashboardWrite: response,
                },
            });
            safeReleasePreboot();
            return;
        }

        state = {
            ...state,
            payloads: {
                ...state.payloads,
                dashboardWrite: response,
            },
        };

        await load({
            notice: response?.message || 'Preferências do dashboard atualizadas.',
            noticeTone: 'success',
        });
    };

    const updateHelpPreferences = async (autoOffer) => {
        updateState({ writingHelpPreferences: true, error: '', notice: '', noticeTone: 'neutral' });

        const response = await resolvedApi.updateHelpPreferences({
            action: 'set_auto_offer',
            value: autoOffer,
        });

        if (!response?.success) {
            updateState({
                writingHelpPreferences: false,
                error: response?.message || 'Não foi possível salvar as preferências de ajuda.',
                payloads: {
                    ...state.payloads,
                    helpPreferencesWrite: response,
                },
                helpAutoOfferDraft: autoOffer,
            });
            safeReleasePreboot();
            return;
        }

        state = {
            ...state,
            payloads: {
                ...state.payloads,
                helpPreferencesWrite: response,
            },
            helpAutoOfferDraft: autoOffer,
        };

        await load({
            notice: response?.message || 'Preferências de ajuda atualizadas.',
            noticeTone: 'success',
        });
    };

    renderer.bind({
        onReload: () => {
            void load();
        },
        onRenew: () => {
            void renew();
        },
        onThemeChange: (theme) => {
            void updateTheme(theme);
        },
        onDisplayNameInput: (displayName) => {
            updateState({ displayNameDraft: displayName });
        },
        onDisplayNameSave: (displayName) => {
            void updateDisplayName(displayName);
        },
        onHelpPreferencesInput: (autoOffer) => {
            updateState({ helpAutoOfferDraft: autoOffer });
        },
        onHelpPreferencesSave: (autoOffer) => {
            void updateHelpPreferences(autoOffer);
        },
        onDashboardSave: (payload) => {
            void updateDashboardPreferences(payload);
        },
    });

    renderer.render(state);

    return {
        start: load,
        reload: load,
        renew,
        updateTheme,
        updateDisplayName,
        updateHelpPreferences,
        updateDashboardPreferences,
        getState: () => state,
    };
}

export function bootFrontendPilotPage() {
    const root = document.querySelector('[data-frontend-pilot-root]');
    if (!root) {
        return null;
    }

    const app = createFrontendPilotApp({
        root,
        config: readFrontendPilotConfig(root),
        releasePreboot: window.__LK_RELEASE_PREBOOT__,
    });

    void app.start();

    return app;
}