export const OFFER_SESSION_PREFIX = `lk_help_offer_${window.__LK_CONFIG?.userId ?? 'anon'}_`;

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
        return `${OFFER_SESSION_PREFIX}${currentPage}_${defaultVersion}`;
    }

    return `${OFFER_SESSION_PREFIX}${target.key}_${target.version}`;
}

export function wasOfferShownThisSession(target, defaults = {}) {
    try {
        return sessionStorage.getItem(getOfferSessionKey(target, defaults)) === '1';
    } catch (_error) {
        return false;
    }
}

export function markOfferShownThisSession(target, defaults = {}) {
    try {
        sessionStorage.setItem(getOfferSessionKey(target, defaults), '1');
    } catch (_error) {
        // ignore sessionStorage failures
    }
}

export function clearOfferSessionCache() {
    try {
        for (let i = sessionStorage.length - 1; i >= 0; i -= 1) {
            const key = sessionStorage.key(i);
            if (key?.startsWith(OFFER_SESSION_PREFIX)) {
                sessionStorage.removeItem(key);
            }
        }
    } catch (_error) {
        // ignore sessionStorage failures
    }
}

export async function persistHelpPreference(action, extra = {}, options = {}) {
    if (!window.LK?.api?.post) {
        return { ok: false, preferences: null };
    }

    const { silent = false } = options;

    const response = await window.LK.api.post('api/user/help-preferences', {
        action,
        ...extra,
    });

    if (!response?.ok) {
        if (!silent) {
            window.LK?.toast?.error(response?.message || 'NÃ£o foi possivel salvar sua preferencia de ajuda.');
        }

        return { ok: false, preferences: null };
    }

    return {
        ok: true,
        preferences: response.data?.preferences ? normalizeHelpPreferences(response.data.preferences) : null,
    };
}
