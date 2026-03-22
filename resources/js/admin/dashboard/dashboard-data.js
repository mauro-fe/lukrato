import { apiGetCached, invalidateApiCache } from '../shared/api-store.js';
import { CONFIG, Utils } from './state.js';

const OVERVIEW_TTL_MS = 30000;
const CHECKLIST_TTL_MS = 15000;
const USER_ID = window.__LK_CONFIG?.userId ?? 'anon';

function overviewCacheKey(month, limit) {
    return `dashboard:overview:${month}:${limit}`;
}

export function getDashboardOverview(month = Utils.getCurrentMonth(), { limit = CONFIG.TRANSACTIONS_LIMIT, force = false } = {}) {
    return apiGetCached(`${CONFIG.API_URL}dashboard/overview`, {
        month,
        limit,
    }, {
        cacheKey: overviewCacheKey(month, limit),
        ttlMs: OVERVIEW_TTL_MS,
        force,
    });
}

export function invalidateDashboardOverview(month = null) {
    const prefix = month
        ? `dashboard:overview:${month}:`
        : 'dashboard:overview:';

    invalidateApiCache(prefix);
}

export function getOnboardingChecklist({ force = false } = {}) {
    return apiGetCached(`${CONFIG.BASE_URL}api/onboarding/checklist`, {}, {
        cacheKey: `dashboard:onboarding:checklist:${USER_ID}`,
        ttlMs: CHECKLIST_TTL_MS,
        force,
    });
}

export function invalidateOnboardingChecklist() {
    invalidateApiCache(`dashboard:onboarding:checklist:${USER_ID}`);
}
