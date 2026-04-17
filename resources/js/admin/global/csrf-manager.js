/**
 * ============================================================================
 * GERENCIADOR GLOBAL DE CSRF
 * ============================================================================
 * Gerencia tokens CSRF com renovação automática e retry em caso de expiração
 * ============================================================================
 */

import { apiFetch, getCSRFToken, refreshCSRFSession } from '../shared/api.js';
import { applyRuntimeCsrfToken } from '../shared/runtime.js';

(function initGlobalCsrfManager() {
    'use strict';

    // ============================================================================
    // CONFIGURAÇÃO
    // ============================================================================

    const CONFIG = {
        TOKEN_ID: document.querySelector('meta[name="csrf-token-id"]')?.content || 'default',
        PROACTIVE_REFRESH_THRESHOLD: 300, // Renova quando restar menos de 5 minutos
        CHECK_INTERVAL: 60000, // Verifica TTL a cada 1 minuto
    };

    // ============================================================================
    // ESTADO
    // ============================================================================

    let csrfToken = '';
    let csrfTtl = 1200; // 20 minutos padrão
    let lastCheck = Date.now();
    let refreshInProgress = false;

    // ============================================================================
    // UTILITÁRIOS
    // ============================================================================

    /**
     * Obtém o token CSRF atual.
     */
    function getToken() {
        if (csrfToken) return csrfToken;

        const resolvedToken = getCSRFToken();
        if (resolvedToken) {
            csrfToken = resolvedToken;
            return csrfToken;
        }

        return '';
    }

    /**
     * Aplica um novo token em todos os lugares.
     */
    function applyToken(token, ttl = null) {
        if (!token) return;

        csrfToken = token;
        if (ttl !== null && typeof ttl === 'number') {
            csrfTtl = ttl;
        }
        lastCheck = Date.now();

        applyRuntimeCsrfToken(token, {
            tokenId: CONFIG.TOKEN_ID,
            ttl: csrfTtl,
        });
    }

    /**
     * Renova o token CSRF.
     */
    async function refreshToken() {
        if (refreshInProgress) {
            return csrfToken;
        }

        refreshInProgress = true;

        try {
            const { token, ttl } = await refreshCSRFSession(CONFIG.TOKEN_ID);

            if (token) {
                applyToken(token, ttl);
                return token;
            }

            throw new Error('Resposta sem token');
        } catch (err) {
            console.error('[CSRF] Falha ao renovar token:', err);
            throw err;
        } finally {
            refreshInProgress = false;
        }
    }

    /**
     * Verifica se o token precisa ser renovado proativamente.
     */
    function checkAndRefreshIfNeeded() {
        const elapsed = (Date.now() - lastCheck) / 1000;
        const estimatedRemaining = Math.max(0, csrfTtl - elapsed);

        if (estimatedRemaining < CONFIG.PROACTIVE_REFRESH_THRESHOLD) {
            refreshToken().catch(() => {
                console.warn('[CSRF] Falha na renovação proativa');
            });
        }
    }

    /**
     * Atualiza o TTL restante com base no header da resposta.
     */
    function updateTtlFromResponse(response) {
        const ttlHeader = response.headers.get('X-CSRF-TTL');
        if (ttlHeader) {
            const ttl = parseInt(ttlHeader, 10);
            if (!isNaN(ttl) && ttl > 0) {
                csrfTtl = ttl;
                lastCheck = Date.now();
            }
        }
    }

    /**
     * Verifica se um erro é relacionado a CSRF.
     */
    function isCsrfError(response, data) {
        if (response.status === 419) return true; // Session Expired
        if (response.status === 403) {
            if (data?.errors?.csrf_token) return true;
            if (data?.csrf_expired === true) return true;
            const msg = String(data?.message || '').toLowerCase();
            if (msg.includes('csrf') || msg.includes('token')) return true;
        }
        return false;
    }

    // ============================================================================
    // API PÚBLICA
    // ============================================================================

    /**
     * Fetch com suporte automático a retry de CSRF.
     * 
     * @param {string} url - URL da requisição
     * @param {RequestInit} options - Opções do fetch
     * @param {boolean} retry - Se deve fazer retry em caso de erro CSRF
     * @returns {Promise<Response>}
     */
    async function fetchWithCsrf(url, options = {}, retry = true) {
        try {
            const response = await apiFetch(url, {
                credentials: 'include',
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
                    'X-CSRF-TOKEN': getToken(),
                    ...(options.headers || {})
                }
            }, { responseType: 'response' });

            updateTtlFromResponse(response);
            return response;
        } catch (error) {
            const response = error?.response ?? null;
            const data = error?.data ?? null;

            if (response) {
                updateTtlFromResponse(response);
            }

            if (isCsrfError(response || { status: error?.status ?? 0 }, data) && retry) {
                console.warn('[CSRF] Token expirado detectado, tentando renovar...');

                try {
                    await refreshToken();
                    return fetchWithCsrf(url, options, false);
                } catch (refreshErr) {
                    // Retorna a resposta original do erro
                }
            }

            if (response) {
                return response;
            }

            throw error;
        }
    }

    /**
     * Fetch com retry e parsing automático de JSON.
     */
    async function fetchJson(url, options = {}, retry = true) {
        const response = await fetchWithCsrf(url, options, retry);

        if (!response.ok) {
            const contentType = response.headers.get('content-type') || '';
            let errorMsg = `HTTP ${response.status}`;

            if (contentType.includes('application/json')) {
                try {
                    const errorData = await response.json();
                    errorMsg = errorData?.message || errorMsg;
                } catch (_) {
                    // Ignora erro de parse
                }
            }

            throw new Error(errorMsg);
        }

        return response.json();
    }

    // ============================================================================
    // INICIALIZAÇÃO
    // ============================================================================

    // Inicializa com token existente
    applyToken(getToken());

    // Inicia verificação periódica
    setInterval(checkAndRefreshIfNeeded, CONFIG.CHECK_INTERVAL);

    // Renova ao voltar para a aba (usuário retornou após ausência)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            setTimeout(checkAndRefreshIfNeeded, 1000);
        }
    });

    // Expõe API global
    window.CsrfManager = {
        getToken,
        refreshToken,
        fetchWithCsrf,
        fetchJson,
        applyToken
    };

})();
