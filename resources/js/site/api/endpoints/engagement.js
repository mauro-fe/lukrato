export function resolveContactSendEndpoint() {
    return 'api/v1/contato/enviar';
}

function normalizeBaseUrl(value) {
    return String(value || '').replace(/\/+$/, '');
}

function extractPathname(baseUrl) {
    if (!baseUrl) {
        return '';
    }

    try {
        return new URL(baseUrl, globalThis.location?.origin || 'http://localhost').pathname;
    } catch {
        return String(baseUrl || '');
    }
}

export function getSiteBaseUrl(baseUrl = (globalThis.window?.APP_BASE_URL || '')) {
    const metaBase = globalThis.document?.querySelector?.('meta[name="base-url"]')?.getAttribute?.('content');
    const normalizedBase = normalizeBaseUrl(metaBase || baseUrl || '');

    return normalizedBase;
}

export function getSiteApiBaseUrl(baseUrl = (globalThis.window?.API_BASE_URL || '')) {
    const metaApiBase = globalThis.document?.querySelector?.('meta[name="api-base-url"]')?.getAttribute?.('content');
    const normalizedBase = normalizeBaseUrl(metaApiBase || baseUrl || '');

    if (normalizedBase) {
        return normalizedBase;
    }

    return getSiteBaseUrl();
}

export function getSiteBasePath(baseUrl = getSiteBaseUrl(), pathname = (globalThis.location?.pathname || '/')) {
    const normalizedPath = String(pathname || '/');
    const normalizedBase = normalizeBaseUrl(baseUrl || '');
    const explicitPath = normalizedBase !== ''
        ? extractPathname(normalizedBase).replace(/\/?$/, '/')
        : '';

    if (explicitPath && explicitPath !== './') {
        return explicitPath;
    }

    const publicIndex = normalizedPath.indexOf('/public/');

    if (publicIndex !== -1) {
        return normalizedPath.substring(0, publicIndex + 8);
    }

    return '/';
}

function buildAbsoluteUrl(path, baseUrl) {
    const normalizedEndpoint = String(path || '').replace(/^\/+/, '');
    const normalizedBase = normalizeBaseUrl(baseUrl || '');

    if (!normalizedBase) {
        return `/${normalizedEndpoint}`;
    }

    return `${normalizedBase}/${normalizedEndpoint}`;
}

export function buildSiteAppUrl(path, baseUrl = getSiteBaseUrl()) {
    return buildAbsoluteUrl(path, baseUrl);
}

export function buildSiteUrl(endpoint, baseUrl = getSiteApiBaseUrl()) {
    return buildAbsoluteUrl(endpoint, baseUrl);
}