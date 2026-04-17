import { resolveHelpPreferencesEndpoint } from '../../api/endpoints/preferences.js';
import { apiGet, apiPost, getApiPayload, getErrorMessage } from '../../shared/api.js';
import { getRuntimeConfig } from '../runtime-config.js';

export function getOfferSessionPrefix() {
    return `lk_help_offer_${getRuntimeConfig().userId ?? 'anon'}_`;
}

export function normalizeHelpPreferences(value) {
    const fallback = {
        settings: {
            auto_offer: true,
        },
        tour_completed: {},
        offer_dismissed: {},
        tips_seen: {},
    };

    if (!value || typeof value !== 'object') {
        return fallback;
    }

    return {
        settings: {
            auto_offer: value?.settings?.auto_offer !== false,
        },
        tour_completed: typeof value.tour_completed === 'object' && value.tour_completed ? value.tour_completed : {},
        offer_dismissed: typeof value.offer_dismissed === 'object' && value.offer_dismissed ? value.offer_dismissed : {},
        tips_seen: typeof value.tips_seen === 'object' && value.tips_seen ? value.tips_seen : {},
    };
}

export function getOfferSessionKey(target, defaults = {}) {
    const { currentPage = 'dashboard', defaultVersion = 'v2' } = defaults;
    if (!target) {
        return `${getOfferSessionPrefix()}${currentPage}_${defaultVersion}`;
    }

    return `${getOfferSessionPrefix()}${target.key}_${target.version}`;
}

export function wasOfferShownThisSession(target, defaults = {}) {
    try {
        return sessionStorage.getItem(getOfferSessionKey(target, defaults)) === '1';
    } catch {
        return false;
    }
}

export function markOfferShownThisSession(target, defaults = {}) {
    try {
        sessionStorage.setItem(getOfferSessionKey(target, defaults), '1');
    } catch {
        // ignore sessionStorage failures
    }
}

export function clearOfferSessionCache() {
    try {
        const prefix = getOfferSessionPrefix();
        for (let i = sessionStorage.length - 1; i >= 0; i -= 1) {
            const key = sessionStorage.key(i);
            if (key?.startsWith(prefix)) {
                sessionStorage.removeItem(key);
            }
        }
    } catch {
        // ignore sessionStorage failures
    }
}

export async function persistHelpPreference(action, extra = {}, options = {}) {
    const { silent = false } = options;

    try {
        const response = await apiPost(resolveHelpPreferencesEndpoint(), {
            action,
            ...extra,
        });

        if (response?.success === false) {
            throw new Error(getErrorMessage({ data: response }, 'Não foi possível salvar sua preferência de ajuda.'));
        }

        const payload = getApiPayload(response, null);

        return {
            ok: true,
            preferences: payload?.preferences ? normalizeHelpPreferences(payload.preferences) : null,
        };
    } catch (error) {
        if (!silent) {
            window.LK?.toast?.error(getErrorMessage(error, 'Não foi possível salvar sua preferência de ajuda.'));
        }

        return { ok: false, preferences: null };
    }
}

export async function fetchHelpPreferences(options = {}) {
    const { silent = true } = options;

    try {
        const response = await apiGet(resolveHelpPreferencesEndpoint());
        if (response?.success === false) {
            throw new Error(getErrorMessage({ data: response }, 'Não foi possível carregar suas preferências de ajuda.'));
        }

        const payload = getApiPayload(response, null);

        return {
            ok: true,
            preferences: payload?.preferences ? normalizeHelpPreferences(payload.preferences) : null,
        };
    } catch (error) {
        if (!silent) {
            window.LK?.toast?.error(getErrorMessage(error, 'Não foi possível carregar suas preferências de ajuda.'));
        }

        return { ok: false, preferences: null };
    }
}
