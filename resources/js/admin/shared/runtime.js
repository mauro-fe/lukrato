function normalizeBaseUrl(value, fallback = '') {
    const normalizedValue = typeof value === 'string'
        ? value.trim()
        : '';

    return normalizedValue !== ''
        ? normalizedValue.replace(/\/?$/, '/')
        : fallback;
}

function getRuntimeConfigObject() {
    if (typeof window !== 'undefined' && window.__LK_CONFIG && typeof window.__LK_CONFIG === 'object') {
        return window.__LK_CONFIG;
    }

    return {};
}

export function applyRuntimeCsrfToken(token, options = {}) {
    const normalizedToken = typeof token === 'string' ? token.trim() : '';
    if (normalizedToken === '') {
        return '';
    }

    const {
        tokenId = 'default',
        ttl = null,
    } = options;

    const runtimeConfig = getRuntimeConfigObject();
    runtimeConfig.csrfToken = normalizedToken;

    if (typeof ttl === 'number' && Number.isFinite(ttl) && ttl > 0) {
        runtimeConfig.csrfTtl = ttl;
    }

    if (typeof document !== 'undefined') {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', normalizedToken);
        }

        document.querySelectorAll(`[data-csrf-id="${tokenId}"]`).forEach((element) => {
            if (element.tagName === 'META') {
                element.setAttribute('content', normalizedToken);
            } else if ('value' in element) {
                element.value = normalizedToken;
            }
        });

        document.querySelectorAll('input[name="csrf_token"], input[name="_token"]').forEach((element) => {
            element.value = normalizedToken;
        });
    }

    if (typeof window !== 'undefined') {
        if (window.LK && typeof window.LK === 'object') {
            window.LK.csrfToken = normalizedToken;
        }

        window.CSRF = normalizedToken;
    }

    return normalizedToken;
}

export function readRuntimeBaseUrl() {
    const runtimeConfig = getRuntimeConfigObject();
    const configuredBaseUrl = normalizeBaseUrl(runtimeConfig.baseUrl, '');
    if (configuredBaseUrl) {
        return configuredBaseUrl;
    }

    const metaBase = typeof document !== 'undefined'
        ? document.querySelector('meta[name="base-url"]')?.getAttribute('content')
        : null;
    const normalizedMetaBase = normalizeBaseUrl(metaBase, '');
    if (normalizedMetaBase) {
        return normalizedMetaBase;
    }

    if (typeof window !== 'undefined' && typeof window.location?.pathname === 'string' && window.location.pathname.includes('/public/')) {
        return `${window.location.pathname.split('/public/')[0]}/public/`;
    }

    return '/';
}

export function readRuntimeApiBaseUrl() {
    const runtimeConfig = getRuntimeConfigObject();
    const configuredApiBaseUrl = normalizeBaseUrl(runtimeConfig.apiBaseUrl, '');
    if (configuredApiBaseUrl) {
        return configuredApiBaseUrl;
    }

    const metaApiBase = typeof document !== 'undefined'
        ? document.querySelector('meta[name="api-base-url"]')?.getAttribute('content')
        : null;
    const normalizedMetaApiBase = normalizeBaseUrl(metaApiBase, '');
    if (normalizedMetaApiBase) {
        return normalizedMetaApiBase;
    }

    return readRuntimeBaseUrl();
}

export function readRuntimeCsrfToken() {
    if (typeof document !== 'undefined') {
        const inputToken = document.querySelector('input[name="csrf_token"]')?.value
            || document.querySelector('input[name="_token"]')?.value
            || '';
        if (inputToken) {
            return inputToken;
        }

        const metaToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (metaToken) {
            return metaToken;
        }
    }

    const runtimeConfig = getRuntimeConfigObject();
    if (typeof runtimeConfig.csrfToken === 'string' && runtimeConfig.csrfToken) {
        return runtimeConfig.csrfToken;
    }

    if (typeof window !== 'undefined') {
        if (typeof window.LK?.csrfToken === 'string' && window.LK.csrfToken) {
            return window.LK.csrfToken;
        }

        if (typeof window.CSRF === 'string' && window.CSRF) {
            return window.CSRF;
        }
    }

    return '';
}

export { normalizeBaseUrl };