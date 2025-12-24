/**
 * ============================================================================
 * GERENCIADOR GLOBAL DE CSRF
 * ============================================================================
 * Gerencia tokens CSRF com renovação automática e retry em caso de expiração
 * ============================================================================
 */

(function initGlobalCsrfManager() {
    'use strict';

    // ============================================================================
    // CONFIGURAÇÃO
    // ============================================================================

    const CONFIG = {
        TOKEN_ID: document.querySelector('meta[name="csrf-token-id"]')?.content || 'default',
        REFRESH_ENDPOINT: '/api/csrf/refresh',
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
        
        // Tenta obter da meta tag
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (metaToken) {
            csrfToken = metaToken;
            return csrfToken;
        }
        
        // Tenta obter do window.LK
        if (window.LK) {
            if (typeof window.LK.csrfToken === 'string' && window.LK.csrfToken) {
                csrfToken = window.LK.csrfToken;
                return csrfToken;
            }
            if (typeof window.LK.getCSRF === 'function') {
                const token = window.LK.getCSRF();
                if (token) {
                    csrfToken = token;
                    return csrfToken;
                }
            }
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
        
        // Atualiza meta tag
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        
        // Atualiza campos com data-csrf-id
        document.querySelectorAll(`[data-csrf-id="${CONFIG.TOKEN_ID}"]`).forEach((el) => {
            if (el.tagName === 'META') {
                el.setAttribute('content', token);
            } else if ('value' in el) {
                el.value = token;
            }
        });
        
        // Atualiza window.LK
        if (window.LK) {
            window.LK.csrfToken = token;
        }
        
        console.log('[CSRF] Token atualizado. TTL:', ttl || 'desconhecido');
    }

    /**
     * Renova o token CSRF.
     */
    async function refreshToken() {
        if (refreshInProgress) {
            console.log('[CSRF] Refresh já em andamento, aguardando...');
            return csrfToken;
        }
        
        refreshInProgress = true;
        
        try {
            const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '/';
            const url = `${baseUrl}${CONFIG.REFRESH_ENDPOINT}`.replace(/\/{2,}/g, '/').replace(':/', '://');
            
            const res = await fetch(url, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ token_id: CONFIG.TOKEN_ID })
            });
            
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            
            const data = await res.json();
            
            if (data?.token) {
                applyToken(data.token, data.ttl);
                console.log('[CSRF] Token renovado com sucesso');
                return data.token;
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
            console.log('[CSRF] TTL baixo detectado, renovando proativamente...');
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
        const token = getToken();
        
        const finalOptions = {
            credentials: 'include',
            ...options,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
                'X-CSRF-TOKEN': token,
                ...(options.headers || {})
            }
        };
        
        const response = await fetch(url, finalOptions);
        
        // Atualiza TTL com base no header
        updateTtlFromResponse(response);
        
        // Tenta parsear JSON
        let data = null;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            try {
                data = await response.clone().json();
            } catch (_) {
                // Ignora erros de parse
            }
        }
        
        // Verifica se é erro de CSRF
        if (isCsrfError(response, data) && retry) {
            console.warn('[CSRF] Token expirado detectado, tentando renovar...');
            
            try {
                await refreshToken();
                console.log('[CSRF] Tentando novamente a requisição...');
                return fetchWithCsrf(url, options, false);
            } catch (refreshErr) {
                console.error('[CSRF] Falha ao renovar token:', refreshErr);
                // Retorna a resposta original do erro
            }
        }
        
        return response;
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
    
    // Expõe API global
    window.CsrfManager = {
        getToken,
        refreshToken,
        fetchWithCsrf,
        fetchJson,
        applyToken
    };
    
    // Backward compatibility com window.LK
    if (!window.LK) window.LK = {};
    window.LK.getCSRF = getToken;
    window.LK.refreshCSRF = refreshToken;
    
    console.log('[CSRF] Gerenciador global inicializado');
})();
