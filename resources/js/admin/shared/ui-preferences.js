import { apiGet, apiPost } from './api.js';

const BASE_ENDPOINT = 'api/user/ui-preferences';

function normalizePageKey(pageKey) {
    const normalized = String(pageKey || '').trim().toLowerCase();

    if (!/^[a-z0-9][a-z0-9_-]{1,39}$/.test(normalized)) {
        throw new Error('Invalid UI page key.');
    }

    return normalized;
}

export async function fetchUiPagePreferences(pageKey) {
    const page = normalizePageKey(pageKey);
    const response = await apiGet(`${BASE_ENDPOINT}/${encodeURIComponent(page)}`);
    const data = response?.data ?? response;

    return data?.preferences ?? {};
}

export async function persistUiPagePreferences(pageKey, preferences) {
    const page = normalizePageKey(pageKey);
    const response = await apiPost(`${BASE_ENDPOINT}/${encodeURIComponent(page)}`, {
        preferences
    });
    const data = response?.data ?? response;

    return data?.preferences ?? {};
}

