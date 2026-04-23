/**
 * ============================================================================
 * LUKRATO — Shared API Layer
 * ============================================================================
 * CSRF management, fetch wrapper, error handling.
 * import { apiGet, apiPost, apiPut, apiDelete, getCSRFToken } from '../shared/api';
 * ============================================================================
 */

import { resolveAuthLogoutEndpoint } from '../api/endpoints/auth.js';
import { resolveCsrfRefreshEndpoint } from '../api/endpoints/security.js';
import { applyRuntimeCsrfToken, readRuntimeApiBaseUrl, readRuntimeBaseUrl, readRuntimeCsrfToken } from './runtime.js';

// ─── Base URL ───────────────────────────────────────────────────────────────

/**
 * Resolve a base URL da aplicação
 * @returns {string} URL com trailing slash
 */
export function getBaseUrl() {
    return readRuntimeBaseUrl();
}

export function getApiBaseUrl() {
    return readRuntimeApiBaseUrl();
}

function buildAbsoluteUrl(baseUrl, endpoint, params = {}) {
    const normalizedEndpoint = String(endpoint || '');
    const url = normalizedEndpoint.startsWith('http')
        ? normalizedEndpoint
        : String(baseUrl || '/') + normalizedEndpoint.replace(/^\//, '');
    const filtered = Object.entries(params)
        .filter(([_, value]) => value !== null && value !== undefined && value !== '')
        .map(([key, value]) => `${key}=${encodeURIComponent(value)}`);

    return filtered.length > 0 ? `${url}?${filtered.join('&')}` : url;
}

export function buildAppUrl(endpoint, params = {}) {
    return buildAbsoluteUrl(getBaseUrl(), endpoint, params);
}

export function buildAssetUrl(assetPath) {
    const normalizedAssetPath = String(assetPath || '')
        .replace(/^\/+/, '')
        .replace(/^assets\/+/, '');

    return buildAppUrl(`assets/${normalizedAssetPath}`);
}

// ─── CSRF Token ─────────────────────────────────────────────────────────────

/**
 * Obtém o CSRF token (primeiro tenta meta tag, depois fetch fresco)
 * @returns {string}
 */
export function getCSRFToken() {
    return readRuntimeCsrfToken();
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

const NETWORK_WARNING_IDS = {
    offline: 'lk-offline-notification',
    slow: 'lk-slow-connection',
};

const NETWORK_SLOW_THRESHOLD = 5000;

let activeNetworkRequests = 0;
let slowWarningTimerId = null;

function getDocumentRef() {
    if (typeof document !== 'undefined') {
        return document;
    }

    return null;
}

function getWindowRef() {
    if (typeof window !== 'undefined') {
        return window;
    }

    return null;
}

function showOfflineNotification() {
    const doc = getDocumentRef();
    if (!doc?.body || typeof doc.getElementById !== 'function') {
        return;
    }

    if (doc.getElementById(NETWORK_WARNING_IDS.offline)) {
        return;
    }

    const notification = doc.createElement('div');
    notification.id = NETWORK_WARNING_IDS.offline;
    notification.className = 'lk-offline-notification';
    notification.innerHTML = `
        <i data-lucide="wifi" style="opacity: 0.5;"></i>
        <span>Sem conexão com a internet</span>
        <button type="button" class="btn-retry-connection">Tentar novamente</button>
    `;

    const retryButton = notification.querySelector('button');
    retryButton?.addEventListener('click', () => {
        getWindowRef()?.location?.reload?.();
    });

    doc.body.appendChild(notification);
}

function hideOfflineNotification() {
    const doc = getDocumentRef();
    const notification = doc?.getElementById?.(NETWORK_WARNING_IDS.offline);
    notification?.remove();
}

function showSlowConnectionWarning() {
    const doc = getDocumentRef();
    if (!doc?.body || typeof doc.getElementById !== 'function') {
        return;
    }

    let warning = doc.getElementById(NETWORK_WARNING_IDS.slow);
    if (!warning) {
        warning = doc.createElement('div');
        warning.id = NETWORK_WARNING_IDS.slow;
        warning.className = 'lk-slow-connection';
        warning.innerHTML = `
            <i data-lucide="hourglass"></i>
            <span>Conexão lenta detectada. Aguarde...</span>
        `;
        doc.body.appendChild(warning);
    }

    warning.classList.add('visible');
}

function hideSlowConnectionWarning() {
    if (slowWarningTimerId) {
        clearTimeout(slowWarningTimerId);
        slowWarningTimerId = null;
    }

    const doc = getDocumentRef();
    const warning = doc?.getElementById?.(NETWORK_WARNING_IDS.slow);
    warning?.classList.remove('visible');
}

function ensureNetworkUiBindings() {
    const win = getWindowRef();

    if (!win || win.__LK_SHARED_API_NETWORK_UI__ === true) {
        return;
    }

    win.__LK_SHARED_API_NETWORK_UI__ = true;

    if (typeof win.addEventListener === 'function') {
        win.addEventListener('online', hideOfflineNotification);
        win.addEventListener('offline', showOfflineNotification);
    }

    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        showOfflineNotification();
    }
}

function startNetworkActivityWatch({ showSlowWarning = true } = {}) {
    ensureNetworkUiBindings();
    activeNetworkRequests += 1;

    if (showSlowWarning && activeNetworkRequests === 1 && !slowWarningTimerId) {
        slowWarningTimerId = setTimeout(() => {
            if (activeNetworkRequests > 0) {
                showSlowConnectionWarning();
            }
        }, NETWORK_SLOW_THRESHOLD);
    }

    return () => {
        activeNetworkRequests = Math.max(0, activeNetworkRequests - 1);

        if (activeNetworkRequests === 0) {
            hideSlowConnectionWarning();
        }
    };
}

function isPlanLimitReachedPayload(data) {
    return Boolean(data && typeof data === 'object' && (data.limit_reached === true || data?.errors?.limit_reached === true));
}

function notifyPlanLimitReached(data) {
    if (!isPlanLimitReachedPayload(data)) {
        return;
    }

    const handler = getWindowRef()?.PlanLimits?.handleApiLimitReached;
    if (typeof handler !== 'function') {
        return;
    }

    try {
        const result = handler(data);
        if (result && typeof result.catch === 'function') {
            result.catch(() => { /* ignore */ });
        }
    } catch {
        // ignore prompt failures and preserve original request error flow
    }
}

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
export async function refreshCSRFSession(tokenId = 'default') {
    const finishNetworkActivity = startNetworkActivityWatch({ showSlowWarning: false });

    try {
        if (typeof navigator !== 'undefined' && navigator.onLine === false) {
            showOfflineNotification();
            return { token: '', ttl: null };
        }

        const response = await fetch(buildUrl(resolveCsrfRefreshEndpoint()), {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ token_id: tokenId })
        });

        if (response.ok) {
            const data = await response.json();
            const token = data?.data?.token || data?.token || '';
            const ttl = typeof data?.data?.ttl === 'number'
                ? data.data.ttl
                : (typeof data?.ttl === 'number' ? data.ttl : null);

            if (token) {
                return {
                    token: applyRuntimeCsrfToken(token, { tokenId, ttl }),
                    ttl,
                };
            }
        }
    } catch (error) {
        logClientWarning('Erro ao renovar CSRF pela rota oficial', error, 'Falha ao renovar token CSRF');
    } finally {
        finishNetworkActivity();
    }

    return { token: '', ttl: null };
}

export async function refreshCSRFToken(tokenId = 'default') {
    const { token } = await refreshCSRFSession(tokenId);
    return token;
}

// ─── Fetch Wrapper ──────────────────────────────────────────────────────────

/**
 * Constrói URL com query params
 * @param {string} endpoint - Caminho relativo ou absoluto
 * @param {Object} params - Query params
 * @returns {string}
 */
export function buildUrl(endpoint, params = {}) {
    return buildAbsoluteUrl(getApiBaseUrl(), endpoint, params);
}

/**
 * Fetch wrapper com CSRF automático e error handling
 * @param {string} url - Relativo ao base URL (ex: "api/lancamentos")
 * @param {RequestInit} options
 * @param {Object} [extra] - Opções extras: { timeout: ms, responseType: 'response' }
 * @returns {Promise<any>} JSON parsed response or raw Response when responseType='response'
 */
export async function apiFetch(url, options = {}, extra = {}) {
    const base = getApiBaseUrl();
    const fullUrl = url.startsWith('http') ? url : base + url.replace(/^\//, '');
    const method = (options.method || 'GET').toUpperCase();
    const responseType = extra.responseType === 'response' ? 'response' : 'json';
    const releaseBootRequest = window.LKPageLoading?.bootRequestStart?.() || null;
    const suppressErrorLogging = extra.suppressErrorLogging === true;
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
    const finishNetworkActivity = startNetworkActivityWatch();

    try {
        if (typeof navigator !== 'undefined' && navigator.onLine === false) {
            showOfflineNotification();
            throw new Error('Você está offline. Verifique sua conexão.');
        }

        const response = await fetch(fullUrl, {
            ...options,
            body: requestBody,
            signal: requestSignal,
            credentials: options.credentials ?? 'include',
            headers: requestHeaders,
        });

        if (responseType === 'response' && response.ok) {
            return response;
        }

        const responseClone = typeof response.clone === 'function' ? response.clone() : response;
        const data = await parseResponsePayload(response);

        if (shouldRetryWithFreshCsrf(response, data)) {
            const newToken = await refreshCSRFToken();
            if (newToken) {
                const retryResponse = await fetch(fullUrl, {
                    ...options,
                    body: requestBody,
                    signal: requestSignal,
                    credentials: options.credentials ?? 'include',
                    headers: normalizeRequestHeaders({
                        ...requestHeaders,
                        'X-CSRF-Token': newToken,
                    }),
                });

                if (responseType === 'response' && retryResponse.ok) {
                    return retryResponse;
                }

                const retryResponseClone = typeof retryResponse.clone === 'function' ? retryResponse.clone() : retryResponse;
                const retryData = await parseResponsePayload(retryResponse);
                if (!retryResponse.ok) {
                    notifyPlanLimitReached(retryData);
                    throw buildApiError(retryResponseClone, retryData);
                }
                return retryData;
            }
        }
        if (!response.ok) {
            notifyPlanLimitReached(data);
            throw buildApiError(responseClone, data);
        }
        return data;
    } catch (error) {
        if (error.name === 'AbortError') {
            throw new Error('A requisição demorou demais. Verifique sua conexão e tente novamente.');
        }
        if (!suppressErrorLogging) {
            logClientError('Erro na requisição', error);
        }
        throw error;
    } finally {
        finishNetworkActivity();
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

    return message.includes('csrf') || message.includes('token inválido') || message.includes('token inválido');
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

    if (validationMessage && (!apiMessage || status === 422 || isGenericValidationMessage(apiMessage))) {
        return validationMessage;
    }

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

export async function apiLogout(options = {}) {
    const redirectTo = Object.prototype.hasOwnProperty.call(options, 'redirectTo')
        ? options.redirectTo
        : buildAppUrl('login');

    try {
        await apiPost(resolveAuthLogoutEndpoint(), {}, {
            timeout: options.timeout ?? 10000,
            suppressErrorLogging: true,
        });
    } catch {
        // Redireciona para login mesmo quando a sessão já expirou no backend.
    }

    if (redirectTo) {
        window.location.href = redirectTo;
    }
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
    error.response = response;
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

function isGenericValidationMessage(message) {
    return String(message || '').trim().toLowerCase() === 'validation failed';
}
