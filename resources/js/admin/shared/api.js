/**
 * ============================================================================
 * LUKRATO — Shared API Layer
 * ============================================================================
 * CSRF management, fetch wrapper, error handling.
 * import { apiGet, apiPost, apiPut, apiDelete, getCSRFToken } from '../shared/api';
 * ============================================================================
 */

// ─── Base URL ───────────────────────────────────────────────────────────────

/**
 * Resolve a base URL da aplicação
 * @returns {string} URL com trailing slash
 */
export function getBaseUrl() {
    // 1) window.LK.getBase()
    if (window.LK && typeof window.LK.getBase === 'function') {
        return window.LK.getBase();
    }
    // 2) meta tag
    const meta = document.querySelector('meta[name="base-url"]');
    if (meta?.content) return meta.content.replace(/\/?$/, '/');
    // 3) window.BASE_URL
    if (window.BASE_URL) return window.BASE_URL.replace(/\/?$/, '/');
    // 4) fallback por pathname
    if (location.pathname.includes('/public/')) {
        return location.pathname.split('/public/')[0] + '/public/';
    }
    return '/';
}

// ─── CSRF Token ─────────────────────────────────────────────────────────────

/**
 * Obtém o CSRF token (primeiro tenta meta tag, depois fetch fresco)
 * @returns {string}
 */
export function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/**
 * Obtém CSRF token fresco do servidor (async)
 * @returns {Promise<string>}
 */
export async function refreshCSRFToken() {
    try {
        const base = getBaseUrl();
        const response = await fetch(`${base}api/csrf-token.php`);
        if (response.ok) {
            const data = await response.json();
            if (data.token) {
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) metaTag.setAttribute('content', data.token);
                return data.token;
            }
        }
    } catch (error) {
        console.warn('Erro ao buscar CSRF token fresco:', error);
    }
    return getCSRFToken();
}

// ─── Fetch Wrapper ──────────────────────────────────────────────────────────

/**
 * Constrói URL com query params
 * @param {string} endpoint - Caminho relativo ou absoluto
 * @param {Object} params - Query params
 * @returns {string}
 */
export function buildUrl(endpoint, params = {}) {
    const base = getBaseUrl();
    const url = endpoint.startsWith('http') ? endpoint : base + endpoint.replace(/^\//, '');
    const filtered = Object.entries(params)
        .filter(([_, v]) => v !== null && v !== undefined && v !== '')
        .map(([k, v]) => `${k}=${encodeURIComponent(v)}`);
    return filtered.length > 0 ? `${url}?${filtered.join('&')}` : url;
}

/**
 * Fetch wrapper com CSRF automático e error handling
 * @param {string} url - Relativo ao base URL (ex: "api/lancamentos")
 * @param {RequestInit} options
 * @returns {Promise<any>} JSON parsed response
 */
export async function apiFetch(url, options = {}) {
    const base = getBaseUrl();
    const fullUrl = url.startsWith('http') ? url : base + url.replace(/^\//, '');

    const defaultHeaders = {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCSRFToken(),
    };

    try {
        const response = await fetch(fullUrl, {
            ...options,
            headers: {
                ...defaultHeaders,
                ...options.headers,
            },
        });

        // Se CSRF expirou, renovar e tentar de novo
        if (response.status === 419 || response.status === 403) {
            const newToken = await refreshCSRFToken();
            if (newToken) {
                const retryResponse = await fetch(fullUrl, {
                    ...options,
                    headers: {
                        ...defaultHeaders,
                        'X-CSRF-Token': newToken,
                        ...options.headers,
                    },
                });
                const retryData = await retryResponse.json();
                if (!retryResponse.ok) {
                    throw new Error(retryData.message || `Erro ${retryResponse.status}`);
                }
                return retryData;
            }
        }

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `Erro ${response.status}: ${response.statusText}`);
        }
        return data;
    } catch (error) {
        console.error('Erro na requisição:', error);
        throw error;
    }
}

// ─── Convenience Methods ────────────────────────────────────────────────────

/**
 * GET request
 * @param {string} url
 * @param {Object} params - Query params opcionais
 * @returns {Promise<any>}
 */
export function apiGet(url, params = {}) {
    const fullUrl = buildUrl(url, params);
    return apiFetch(fullUrl, { method: 'GET' });
}

/**
 * POST request
 * @param {string} url
 * @param {Object} data
 * @returns {Promise<any>}
 */
export function apiPost(url, data = {}) {
    return apiFetch(url, {
        method: 'POST',
        body: JSON.stringify(data),
    });
}

/**
 * PUT request
 * @param {string} url
 * @param {Object} data
 * @returns {Promise<any>}
 */
export function apiPut(url, data = {}) {
    return apiFetch(url, {
        method: 'PUT',
        body: JSON.stringify(data),
    });
}

/**
 * DELETE request
 * @param {string} url
 * @returns {Promise<any>}
 */
export function apiDelete(url) {
    return apiFetch(url, { method: 'DELETE' });
}
