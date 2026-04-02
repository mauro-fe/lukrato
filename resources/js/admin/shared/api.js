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
    // Delega para LK.getBase() (fonte canônica) com fallbacks
    if (window.LK && typeof window.LK.getBase === 'function') {
        return window.LK.getBase();
    }
    const meta = document.querySelector('meta[name="base-url"]');
    if (meta?.content) return meta.content.replace(/\/?$/, '/');
    if (window.BASE_URL) return window.BASE_URL.replace(/\/?$/, '/');
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

function isLocalDebugEnvironment() {
    const host = String(window.location.hostname || '').toLowerCase();

    return host === 'localhost'
        || host === '127.0.0.1'
        || host === '::1'
        || host.endsWith('.local')
        || host.endsWith('.test');
}

function installProductionConsoleGuard() {
    if (window.__LK_PRODUCTION_CONSOLE_GUARD__) {
        return;
    }

    window.__LK_PRODUCTION_CONSOLE_GUARD__ = true;

    if (isLocalDebugEnvironment()) {
        return;
    }

    const originalError = console.error.bind(console);
    const originalWarn = console.warn.bind(console);
    const summarizeArgs = (args, fallback) => {
        const parts = args
            .filter((arg) => typeof arg === 'string' && arg.trim() !== '')
            .map((arg) => arg.trim());

        if (parts.length > 0) {
            return parts.join(' | ');
        }

        const status = args.find((arg) => typeof arg?.status === 'number')?.status;
        return status ? `${fallback} (status=${status})` : fallback;
    };

    console.error = (...args) => originalError(summarizeArgs(args, 'Erro inesperado'));
    console.warn = (...args) => originalWarn(summarizeArgs(args, 'Aviso inesperado'));
}

installProductionConsoleGuard();

function summarizeClientError(error, fallback = 'Erro inesperado') {
    if (!error) {
        return fallback;
    }

    const status = Number(error?.status || error?.data?.status || 0);
    const code = typeof error?.data?.code === 'string' ? error.data.code.trim() : '';
    const message = getErrorMessage(error, fallback);
    const details = [];

    if (status > 0) {
        details.push(`status=${status}`);
    }

    if (code) {
        details.push(`code=${code}`);
    }

    return details.length > 0
        ? `${message} (${details.join(', ')})`
        : message;
}

export function logClientError(context, error, fallback = 'Erro inesperado') {
    if (isLocalDebugEnvironment()) {
        console.error(context, error);
        return;
    }

    console.error(`${context}: ${summarizeClientError(error, fallback)}`);
}

export function logClientWarning(context, error, fallback = 'Aviso inesperado') {
    if (isLocalDebugEnvironment()) {
        console.warn(context, error);
        return;
    }

    console.warn(`${context}: ${summarizeClientError(error, fallback)}`);
}

/**
 * Obtém CSRF token fresco do servidor (async)
 * @returns {Promise<string>}
 */
export async function refreshCSRFToken() {
    const base = getBaseUrl();

    try {
        const response = await fetch(`${base}api/csrf/refresh`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ token_id: 'default' })
        });

        if (response.ok) {
            const data = await response.json();
            const token = data?.data?.token || data?.token || '';
            if (token) {
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) metaTag.setAttribute('content', token);
                return token;
            }
        }
    } catch (error) {
        logClientWarning('Erro ao renovar CSRF pela rota oficial, tentando fallback legado', error, 'Falha ao renovar token CSRF');
    }

    try {
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
        logClientWarning('Erro ao buscar CSRF token fresco', error, 'Falha ao buscar token CSRF');
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
 * @param {Object} [extra] - Opções extras: { timeout: ms }
 * @returns {Promise<any>} JSON parsed response
 */
export async function apiFetch(url, options = {}, extra = {}) {
    const base = getBaseUrl();
    const fullUrl = url.startsWith('http') ? url : base + url.replace(/^\//, '');
    const method = (options.method || 'GET').toUpperCase();
    const releaseBootRequest = window.LKPageLoading?.bootRequestStart?.() || null;
    const requestHeaders = normalizeRequestHeaders({
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    });
    let requestBody = options.body;

    if (requestBody instanceof FormData) {
        requestHeaders['X-CSRF-Token'] = requestHeaders['X-CSRF-Token'] || getCSRFToken();
        delete requestHeaders['Content-Type'];
    } else if (method !== 'GET' && method !== 'HEAD') {
        requestHeaders['X-CSRF-Token'] = requestHeaders['X-CSRF-Token'] || getCSRFToken();

        if (requestBody && typeof requestBody === 'object' && !(requestBody instanceof Blob) && !(requestBody instanceof URLSearchParams)) {
            requestBody = JSON.stringify(requestBody);
        }

        if (!requestHeaders['Content-Type']) {
            requestHeaders['Content-Type'] = 'application/json';
        }
    }

    const timeout = extra.timeout ?? 20000;
    const controller = new AbortController();
    let timeoutId;
    if (timeout > 0) {
        timeoutId = setTimeout(() => controller.abort(), timeout);
    }

    const requestSignal = options.signal ?? controller.signal;

    try {
        const response = await fetch(fullUrl, {
            ...options,
            body: requestBody,
            signal: requestSignal,
            credentials: options.credentials ?? 'same-origin',
            headers: requestHeaders,
        });

        const data = await parseResponsePayload(response);

        if (shouldRetryWithFreshCsrf(response, data)) {
            const newToken = await refreshCSRFToken();
            if (newToken) {
                const retryResponse = await fetch(fullUrl, {
                    ...options,
                    body: requestBody,
                    signal: requestSignal,
                    credentials: options.credentials ?? 'same-origin',
                    headers: normalizeRequestHeaders({
                        ...requestHeaders,
                        'X-CSRF-Token': newToken,
                    }),
                });
                const retryData = await parseResponsePayload(retryResponse);
                if (!retryResponse.ok) {
                    throw buildApiError(retryResponse, retryData);
                }
                return retryData;
            }
        }
        if (!response.ok) {
            throw buildApiError(response, data);
        }
        return data;
    } catch (error) {
        if (error.name === 'AbortError') {
            throw new Error('A requisição demorou demais. Verifique sua conexão e tente novamente.');
        }
        logClientError('Erro na requisição', error);
        throw error;
    } finally {
        if (timeoutId) clearTimeout(timeoutId);
        if (typeof releaseBootRequest === 'function') {
            releaseBootRequest();
        }
    }
}

function shouldRetryWithFreshCsrf(response, data) {
    if (!response || (response.status !== 419 && response.status !== 403)) {
        return false;
    }

    if (response.status === 419) {
        return true;
    }

    if (!data || typeof data !== 'object') {
        return false;
    }

    if (data.csrf_expired === true) {
        return true;
    }

    if (data.errors && typeof data.errors === 'object') {
        if (data.errors.csrf_token || data.errors._token) {
            return true;
        }
    }

    const message = typeof data.message === 'string' ? data.message.toLowerCase() : '';

    return message.includes('csrf') || message.includes('token inválido') || message.includes('token invalido');
}

function normalizeRequestHeaders(headers) {
    const normalized = {};

    const assignHeader = (key, value) => {
        if (value === undefined || value === null) {
            return;
        }

        const originalKey = String(key);
        const lowerKey = originalKey.toLowerCase();

        if (lowerKey === 'x-csrf-token') {
            normalized['X-CSRF-Token'] = value;
            return;
        }

        if (lowerKey === 'content-type') {
            normalized['Content-Type'] = value;
            return;
        }

        if (lowerKey === 'accept') {
            normalized['Accept'] = value;
            return;
        }

        if (lowerKey === 'x-requested-with') {
            normalized['X-Requested-With'] = value;
            return;
        }

        normalized[originalKey] = value;
    };

    if (headers instanceof Headers) {
        headers.forEach((value, key) => assignHeader(key, value));
        return normalized;
    }

    Object.entries(headers || {}).forEach(([key, value]) => assignHeader(key, value));
    return normalized;
}

export function getErrorMessage(error, fallback = 'Ocorreu um erro. Tente novamente.') {
    if (!error) {
        return fallback;
    }

    const status = Number(error.status || error?.data?.status || 0);
    const apiMessage = typeof error?.data?.message === 'string' ? error.data.message.trim() : '';
    const validationMessage = extractValidationMessage(error?.data?.errors);
    const directMessage = typeof error?.message === 'string' ? error.message.trim() : '';

    if (apiMessage) {
        return apiMessage;
    }

    if (validationMessage) {
        return validationMessage;
    }

    if (status >= 500 && status < 600) {
        return fallback;
    }

    if (directMessage) {
        return directMessage;
    }

    return fallback;
}

/**
 * Extrai o payload real de respostas da API.
 * Mantém compatibilidade com endpoints que retornam o objeto diretamente
 * e com endpoints que usam envelope { success, data, message }.
 *
 * @param {any} response
 * @param {any} fallback
 * @returns {any}
 */
export function getApiPayload(response, fallback = null) {
    if (response === null || response === undefined) {
        return fallback;
    }

    if (typeof response === 'object' && !Array.isArray(response) && Object.prototype.hasOwnProperty.call(response, 'data')) {
        return response.data ?? fallback;
    }

    return response;
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
 * @param {Object} extra
 * @returns {Promise<any>}
 */
export function apiPost(url, data = {}, extra = {}) {
    return apiFetch(url, {
        method: 'POST',
        body: data,
    }, extra);
}

/**
 * PUT request
 * @param {string} url
 * @param {Object} data
 * @param {Object} extra
 * @returns {Promise<any>}
 */
export function apiPut(url, data = {}, extra = {}) {
    return apiFetch(url, {
        method: 'PUT',
        body: data,
    }, extra);
}

/**
 * DELETE request
 * @param {string} url
 * @param {Object|null} data
 * @param {Object} extra
 * @returns {Promise<any>}
 */
export function apiDelete(url, data = null, extra = {}) {
    return apiFetch(url, {
        method: 'DELETE',
        body: data ?? undefined,
    }, extra);
}

async function parseResponsePayload(response) {
    if (response.status === 204) {
        return null;
    }

    const contentType = response.headers.get('Content-Type') || '';
    if (contentType.includes('application/json') || contentType.includes('+json')) {
        return response.json().catch(() => null);
    }

    const text = await response.text();
    return text === '' ? null : text;
}

function buildApiError(response, data) {
    const message = typeof data === 'object' && data !== null && 'message' in data
        ? data.message
        : `Erro ${response.status}: ${response.statusText}`;
    const error = new Error(message);

    error.status = response.status;
    error.data = data;
    error.code = typeof data === 'object' && data !== null && typeof data.code === 'string'
        ? data.code
        : null;

    return error;
}

function extractValidationMessage(errors) {
    if (!errors) {
        return '';
    }

    if (typeof errors === 'string') {
        return errors.trim();
    }

    if (Array.isArray(errors)) {
        return errors
            .map((value) => String(value || '').trim())
            .filter(Boolean)
            .join(', ');
    }

    if (typeof errors === 'object') {
        const messages = [];

        Object.values(errors).forEach((value) => {
            if (Array.isArray(value)) {
                messages.push(...value.map((item) => String(item || '').trim()).filter(Boolean));
                return;
            }

            const message = String(value || '').trim();
            if (message) {
                messages.push(message);
            }
        });

        return messages.join(', ');
    }

    return '';
}
