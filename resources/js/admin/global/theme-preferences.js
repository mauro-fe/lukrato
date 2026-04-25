import { apiGet } from '../shared/api.js';
import { resolveThemePreferenceEndpoint } from '../api/endpoints/preferences.js';

export const STORAGE_KEY = 'lukrato-theme';
export const THEME_EVENT = 'lukrato:theme-changed';

const ALLOWED_THEME_PREFERENCES = ['light', 'dark', 'system'];
const APPLIED_THEME_VALUES = ['light', 'dark'];

export function normalizeThemePreference(value, fallback = null) {
    const normalizedValue = typeof value === 'string'
        ? value.trim().toLowerCase()
        : '';

    return ALLOWED_THEME_PREFERENCES.includes(normalizedValue)
        ? normalizedValue
        : fallback;
}

export function readStoredThemePreference(storage = globalThis.localStorage) {
    try {
        return normalizeThemePreference(storage?.getItem?.(STORAGE_KEY), null);
    } catch {
        return null;
    }
}

export function storeThemePreference(themePreference, storage = globalThis.localStorage) {
    const normalizedPreference = normalizeThemePreference(themePreference, null);
    if (!normalizedPreference) {
        return;
    }

    try {
        storage?.setItem?.(STORAGE_KEY, normalizedPreference);
    } catch {
        // ignore storage failures during bootstrap
    }
}

export function storeAppliedTheme(theme, storage = globalThis.localStorage) {
    storeThemePreference(theme, storage);
}

export function readHtmlTheme(root = globalThis.document?.documentElement ?? null) {
    return normalizeThemePreference(root?.getAttribute?.('data-theme'), null);
}

export function resolveSystemTheme(options = {}) {
    const {
        matchMedia = globalThis.window?.matchMedia?.bind(globalThis.window),
        fallbackTheme = 'dark',
    } = options;

    const normalizedFallback = APPLIED_THEME_VALUES.includes(fallbackTheme)
        ? fallbackTheme
        : 'dark';

    if (typeof matchMedia !== 'function') {
        return normalizedFallback;
    }

    try {
        return matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    } catch {
        return normalizedFallback;
    }
}

export function resolveAppliedTheme(themePreference, options = {}) {
    const normalizedPreference = normalizeThemePreference(themePreference, null);

    if (normalizedPreference === 'light' || normalizedPreference === 'dark') {
        return normalizedPreference;
    }

    return resolveSystemTheme(options);
}

export function getInitialAppliedTheme(options = {}) {
    const { storage, root, matchMedia } = options;
    const rootTheme = readHtmlTheme(root);
    const storedPreference = readStoredThemePreference(storage);

    if (storedPreference) {
        return resolveAppliedTheme(storedPreference, {
            matchMedia,
            fallbackTheme: rootTheme === 'light' || rootTheme === 'dark' ? rootTheme : 'dark',
        });
    }

    if (rootTheme === 'light' || rootTheme === 'dark') {
        return rootTheme;
    }

    return resolveSystemTheme({ matchMedia, fallbackTheme: 'dark' });
}

export function createSystemThemeMediaQuery(matchMedia = globalThis.window?.matchMedia?.bind(globalThis.window)) {
    if (typeof matchMedia !== 'function') {
        return null;
    }

    try {
        return matchMedia('(prefers-color-scheme: dark)');
    } catch {
        return null;
    }
}

export function extractThemePreference(response) {
    return normalizeThemePreference(
        response?.data?.theme
        ?? response?.raw?.data?.theme
        ?? response?.theme
        ?? response?.data?.data?.theme
        ?? null,
        null,
    );
}

export async function fetchThemePreference() {
    try {
        const response = await apiGet(resolveThemePreferenceEndpoint());
        if (response?.success === false) {
            return { ok: false, theme: null };
        }

        const theme = extractThemePreference(response);
        if (!theme) {
            return { ok: false, theme: null };
        }

        return { ok: true, theme };
    } catch {
        return { ok: false, theme: null };
    }
}
