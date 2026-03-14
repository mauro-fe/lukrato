/**
 * LukratoFetch - Utilitário de requisições HTTP robusto
 * 
 * Features:
 * - Timeout configurável
 * - Retry automático com backoff exponencial
 * - Detecção de conexão lenta
 * - Cache local (opcional)
 * - Indicadores de loading
 * - Tratamento de offline
 * 
 * @version 1.0.0
 */

class LukratoFetch {
    constructor(options = {}) {
        this.defaultTimeout = options.timeout || 15000; // 15 segundos
        this.maxRetries = options.maxRetries || 3;
        this.retryDelay = options.retryDelay || 1000; // 1 segundo inicial
        this.slowThreshold = options.slowThreshold || 3000; // 3 segundos = conexão lenta
        this.enableCache = options.enableCache || false;
        this.cachePrefix = 'lk_cache_';
        this.cacheDuration = options.cacheDuration || 5 * 60 * 1000; // 5 minutos

        // Estado
        this.pendingRequests = new Map();
        this.isSlowConnection = false;

        // Detectar tipo de conexão
        this.detectConnection();

        // Listener para mudanças de conexão
        if (navigator.connection) {
            navigator.connection.addEventListener('change', () => this.detectConnection());
        }

        // Listener para online/offline
        window.addEventListener('online', () => this.onConnectionChange(true));
        window.addEventListener('offline', () => this.onConnectionChange(false));
    }

    /**
     * Detecta tipo de conexão
     */
    detectConnection() {
        if (navigator.connection) {
            const conn = navigator.connection;
            // Conexões lentas: slow-2g, 2g, ou effectiveType lento
            this.isSlowConnection = ['slow-2g', '2g'].includes(conn.effectiveType) ||
                (conn.downlink && conn.downlink < 1);
        }
    }

    /**
     * Handler de mudança de conexão
     */
    onConnectionChange(isOnline) {
        if (!isOnline) {
            this.showOfflineNotification();
        } else {
            this.hideOfflineNotification();
        }
    }

    /**
     * Mostra notificação de offline
     */
    showOfflineNotification() {
        if (document.getElementById('lk-offline-notification')) return;

        const notification = document.createElement('div');
        notification.id = 'lk-offline-notification';
        notification.className = 'lk-offline-notification';
        notification.innerHTML = `
            <i data-lucide="wifi" style="opacity: 0.5;"></i>
            <span>Sem conexão com a internet</span>
            <button onclick="location.reload()" class="btn-retry-connection">
                <i data-lucide="refresh-cw"></i> Tentar novamente
            </button>
        `;
        document.body.appendChild(notification);
    }

    /**
     * Esconde notificação de offline
     */
    hideOfflineNotification() {
        const notification = document.getElementById('lk-offline-notification');
        if (notification) {
            notification.remove();
        }
    }

    /**
     * Requisição com timeout, retry e indicadores
     * 
     * @param {string} url - URL da requisição
     * @param {object} options - Opções do fetch
     * @param {object} config - Configurações extras (timeout, retries, etc)
     * @returns {Promise<Response>}
     */
    async request(url, options = {}, config = {}) {
        const timeout = config.timeout || this.defaultTimeout;
        const maxRetries = config.maxRetries ?? this.maxRetries;
        const showLoading = config.showLoading ?? true;
        const loadingTarget = config.loadingTarget || null;
        const cacheKey = config.cacheKey || null;

        // Verificar cache primeiro
        if (this.enableCache && cacheKey && options.method === 'GET') {
            const cached = this.getFromCache(cacheKey);
            if (cached) {
                return { ok: true, data: cached, fromCache: true };
            }
        }

        // Verificar se está offline
        if (!navigator.onLine) {
            this.showOfflineNotification();
            throw new Error('Você está offline. Verifique sua conexão.');
        }

        // Mostrar loading
        if (showLoading) {
            this.showLoadingIndicator(loadingTarget);
        }

        const startTime = Date.now();
        let lastError = null;
        let slowWarningShown = false;

        // Timer para mostrar aviso de conexão lenta
        const slowTimer = setTimeout(() => {
            if (!slowWarningShown) {
                slowWarningShown = true;
                this.showSlowConnectionWarning(loadingTarget);
            }
        }, this.slowThreshold);

        for (let attempt = 0; attempt <= maxRetries; attempt++) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeout);

            try {
                const fetchOptions = {
                    ...options,
                    signal: controller.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...options.headers
                    },
                    credentials: options.credentials || 'same-origin'
                };

                const response = await fetch(url, fetchOptions);
                clearTimeout(timeoutId);
                clearTimeout(slowTimer);

                const elapsed = Date.now() - startTime;

                // Log de performance
                if (elapsed > this.slowThreshold) {
                    console.warn(`[LukratoFetch] Requisição lenta: ${url} (${elapsed}ms)`);
                }

                // Esconder loading e aviso de lentidão
                if (showLoading) {
                    this.hideLoadingIndicator(loadingTarget);
                    this.hideSlowConnectionWarning();
                }

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Erro ${response.status}`);
                }

                const data = await response.json();

                // Salvar no cache
                if (this.enableCache && cacheKey && options.method === 'GET') {
                    this.saveToCache(cacheKey, data);
                }

                return { ok: true, data, fromCache: false, elapsed };

            } catch (error) {
                clearTimeout(timeoutId);
                lastError = error;

                // Se foi cancelado por timeout
                if (error.name === 'AbortError') {
                    console.warn(`[LukratoFetch] Timeout na tentativa ${attempt + 1}/${maxRetries + 1}: ${url}`);
                    lastError = new Error('A requisição demorou muito. Tente novamente.');
                } else if (attempt === 0) {
                    // Só loga no primeiro erro, retries são silenciosos
                    console.warn(`[LukratoFetch] Erro: ${url}`, error.message);
                }

                // Se ainda pode tentar
                if (attempt < maxRetries) {
                    // Backoff exponencial: 1s, 2s, 4s
                    const delay = this.retryDelay * Math.pow(2, attempt);

                    // Atualizar UI com status de retry
                    this.showRetryIndicator(loadingTarget, attempt + 1, maxRetries);

                    await this.sleep(delay);
                }
            }
        }

        // Esconder loading após falhas
        clearTimeout(slowTimer);
        if (showLoading) {
            this.hideLoadingIndicator(loadingTarget);
            this.hideSlowConnectionWarning();
        }

        throw lastError;
    }

    /**
     * GET request simplificado
     */
    async get(url, config = {}) {
        return this.request(url, { method: 'GET' }, config);
    }

    /**
     * POST request simplificado
     */
    async post(url, data = {}, config = {}) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        }, config);
    }

    /**
     * PUT request simplificado
     */
    async put(url, data = {}, config = {}) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        }, config);
    }

    /**
     * DELETE request simplificado
     */
    async delete(url, config = {}) {
        return this.request(url, { method: 'DELETE' }, config);
    }

    /**
     * Mostra indicador de loading
     */
    showLoadingIndicator(target) {
        // Loading global no topo da página
        let loadingBar = document.getElementById('lk-loading-bar');
        if (!loadingBar) {
            loadingBar = document.createElement('div');
            loadingBar.id = 'lk-loading-bar';
            loadingBar.className = 'lk-loading-bar';
            loadingBar.innerHTML = '<div class="lk-loading-progress"></div>';
            document.body.appendChild(loadingBar);
        }
        loadingBar.classList.add('active');

        // Loading específico do target
        if (target) {
            const element = typeof target === 'string' ? document.querySelector(target) : target;
            if (element) {
                element.classList.add('lk-loading');
            }
        }
    }

    /**
     * Esconde indicador de loading
     */
    hideLoadingIndicator(target) {
        const loadingBar = document.getElementById('lk-loading-bar');
        if (loadingBar) {
            loadingBar.classList.remove('active');
        }

        if (target) {
            const element = typeof target === 'string' ? document.querySelector(target) : target;
            if (element) {
                element.classList.remove('lk-loading');
            }
        }
    }

    /**
     * Mostra aviso de conexão lenta
     */
    showSlowConnectionWarning(target) {
        let warning = document.getElementById('lk-slow-connection');
        if (!warning) {
            warning = document.createElement('div');
            warning.id = 'lk-slow-connection';
            warning.className = 'lk-slow-connection';
            warning.innerHTML = `
                <i data-lucide="hourglass"></i>
                <span>Conexão lenta detectada. Aguarde...</span>
            `;
            document.body.appendChild(warning);
        }
        warning.classList.add('visible');
    }

    /**
     * Esconde aviso de conexão lenta
     */
    hideSlowConnectionWarning() {
        const warning = document.getElementById('lk-slow-connection');
        if (warning) {
            warning.classList.remove('visible');
        }
    }

    /**
     * Mostra indicador de retry
     */
    showRetryIndicator(target, attempt, maxAttempts) {
        let indicator = document.getElementById('lk-retry-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'lk-retry-indicator';
            indicator.className = 'lk-retry-indicator';
            document.body.appendChild(indicator);
        }
        indicator.innerHTML = `
            <i data-lucide="refresh-cw" class="icon-spin"></i>
            <span>Tentando novamente... (${attempt}/${maxAttempts})</span>
        `;
        indicator.classList.add('visible');
    }

    /**
     * Cache helpers
     */
    getFromCache(key) {
        try {
            const item = localStorage.getItem(this.cachePrefix + key);
            if (!item) return null;

            const { data, timestamp } = JSON.parse(item);
            if (Date.now() - timestamp > this.cacheDuration) {
                localStorage.removeItem(this.cachePrefix + key);
                return null;
            }
            return data;
        } catch {
            return null;
        }
    }

    saveToCache(key, data) {
        try {
            localStorage.setItem(this.cachePrefix + key, JSON.stringify({
                data,
                timestamp: Date.now()
            }));
        } catch (e) {
            console.warn('[LukratoFetch] Erro ao salvar no cache:', e);
        }
    }

    clearCache() {
        const keys = Object.keys(localStorage).filter(k => k.startsWith(this.cachePrefix));
        keys.forEach(k => localStorage.removeItem(k));
    }

    /**
     * Sleep helper
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Instância global
window.lkFetch = new LukratoFetch({
    timeout: 20000,      // 20 segundos de timeout
    maxRetries: 2,       // 2 retries
    slowThreshold: 4000, // 4 segundos para considerar lento
    enableCache: false   // Cache desabilitado por padrão
});

/**
 * Interceptor Global de Fetch
 * Mostra aviso de conexão lenta quando requisições demoram
 * NÃO mostra barra de loading (cada página tem seu próprio loader)
 */
(function () {
    const originalFetch = window.fetch;
    const SLOW_THRESHOLD = 5000; // 5 segundos para considerar lento
    let activeRequests = 0;
    let slowTimer = null;

    function showSlowWarning() {
        if (document.getElementById('lk-slow-connection')) return;

        const warning = document.createElement('div');
        warning.id = 'lk-slow-connection';
        warning.className = 'lk-slow-connection visible';
        warning.innerHTML = '<i data-lucide="hourglass"></i><span>Conexão lenta detectada. Aguarde...</span>';
        document.body.appendChild(warning);
    }

    function hideSlowWarning() {
        const warning = document.getElementById('lk-slow-connection');
        if (warning) {
            warning.classList.remove('visible');
            setTimeout(() => warning.remove(), 300);
        }

        if (slowTimer) {
            clearTimeout(slowTimer);
            slowTimer = null;
        }
    }

    window.fetch = function (input, init) {
        const url = typeof input === 'string' ? input : (input?.url || '');

        // Só intercepta requisições para API do Lukrato
        const isApiCall = url && (url.includes('/api/') || url.includes('api/'));

        if (!isApiCall) {
            return originalFetch.apply(this, arguments);
        }

        activeRequests++;

        // Timer para conexão lenta
        if (!slowTimer && activeRequests === 1) {
            slowTimer = setTimeout(() => {
                if (activeRequests > 0) {
                    showSlowWarning();
                }
            }, SLOW_THRESHOLD);
        }

        return originalFetch.apply(this, arguments)
            .finally(() => {
                activeRequests = Math.max(0, activeRequests - 1);
                if (activeRequests === 0) {
                    hideSlowWarning();
                }
            });
    };
})();


