/**
 * ============================================================================
 * LUKRATO - SESSION MANAGER
 * ============================================================================
 * Sistema moderno de gerenciamento de sessão com:
 * - Monitoramento em tempo real do tempo restante
 * - Modal de aviso antes da expiração
 * - Renovação automática ou manual
 * - Notificação de logout
 * 
 * @version 2.0.0
 * @author Lukrato Team
 */

(function () {
    'use strict';

    // ========================================================================
    // CONFIGURAÇÕES
    // ========================================================================
    const CONFIG = {
        // Tempo de verificação da sessão (em ms)
        checkInterval: 30000, // 30 segundos

        // Tempo antes da expiração para mostrar aviso (em segundos)
        warningThreshold: 300, // 5 minutos

        // Tempo de sessão total (em segundos) - fallback (1 hora como no Auth)
        sessionLifetime: 3600, // 1 hora

        // Endpoints da API
        endpoints: {
            status: 'api/session/status',
            renew: 'api/session/renew'
        }
    };

    // ========================================================================
    // ESTADO
    // ========================================================================
    let state = {
        remainingTime: CONFIG.sessionLifetime,
        isWarningShown: false,
        isLoggedOutShown: false,
        checkIntervalId: null,
        countdownIntervalId: null,
        lastCheck: Date.now()
    };

    // ========================================================================
    // UTILITÁRIOS
    // ========================================================================
    const utils = {
        getBaseUrl() {
            const meta = document.querySelector('meta[name="base-url"]');
            return (meta?.content || '/').replace(/\/?$/, '/');
        },

        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="_token"]')?.value || '';
        },

        formatTime(seconds) {
            if (seconds <= 0) return '0:00';

            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;

            if (mins >= 60) {
                const hours = Math.floor(mins / 60);
                const remainingMins = mins % 60;
                return `${hours}h ${remainingMins.toString().padStart(2, '0')}min`;
            }

            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },

        formatTimeVerbose(seconds) {
            if (seconds <= 0) return 'expirada';

            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;

            if (mins >= 60) {
                const hours = Math.floor(mins / 60);
                const remainingMins = mins % 60;
                if (remainingMins === 0) {
                    return `${hours} hora${hours > 1 ? 's' : ''}`;
                }
                return `${hours}h e ${remainingMins} minuto${remainingMins > 1 ? 's' : ''}`;
            }

            if (mins > 0) {
                if (secs === 0) {
                    return `${mins} minuto${mins > 1 ? 's' : ''}`;
                }
                return `${mins}min e ${secs}s`;
            }

            return `${secs} segundo${secs > 1 ? 's' : ''}`;
        },

        async apiRequest(endpoint, method = 'GET', body = null) {
            const url = this.getBaseUrl() + endpoint;
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                credentials: 'same-origin'
            };

            if (body && method !== 'GET') {
                options.body = JSON.stringify(body);
            }

            try {
                const response = await fetch(url, options);
                const data = await response.json();
                return { ok: response.ok, status: response.status, data };
            } catch (error) {
                console.error('[SessionManager] API Error:', error);
                return { ok: false, status: 0, data: null, error };
            }
        }
    };

    // ========================================================================
    // COMPONENTES UI
    // ========================================================================
    const UI = {
        // Cria ou obtém o container dos modais de sessão
        getContainer() {
            let container = document.getElementById('lk-session-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'lk-session-container';
                document.body.appendChild(container);
            }
            return container;
        },

        // Cria o modal de aviso de expiração
        createWarningModal() {
            const modal = document.createElement('div');
            modal.id = 'lk-session-warning-modal';
            modal.className = 'lk-session-modal';
            modal.innerHTML = `
                <div class="lk-session-backdrop"></div>
                <div class="lk-session-dialog" role="dialog" aria-modal="true" aria-labelledby="session-warning-title">
                    <div class="lk-session-content">
                        <div class="lk-session-icon warning">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                        </div>
                        
                        <h2 id="session-warning-title" class="lk-session-title">
                            Sua sessão está expirando
                        </h2>
                        
                        <p class="lk-session-message">
                            Sua sessão expira em <strong id="lk-session-countdown">5:00</strong>
                        </p>
                        
                        <div class="lk-session-progress-container">
                            <div class="lk-session-progress-bar" id="lk-session-progress"></div>
                        </div>
                        
                        <p class="lk-session-submessage">
                            Deseja continuar conectado?
                        </p>
                        
                        <div class="lk-session-actions">
                            <button type="button" class="lk-session-btn lk-session-btn-secondary" id="lk-session-logout-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16,17 21,12 16,7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                Sair
                            </button>
                            <button type="button" class="lk-session-btn lk-session-btn-primary" id="lk-session-renew-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                    <path d="M3 3v5h5"/>
                                    <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                                    <path d="M16 21h5v-5"/>
                                </svg>
                                Continuar Conectado
                            </button>
                        </div>
                    </div>
                </div>
            `;

            return modal;
        },

        // Cria o modal de sessão expirada MAS que ainda pode renovar
        createExpiredWarningModal() {
            const modal = document.createElement('div');
            modal.id = 'lk-session-expired-warning-modal';
            modal.className = 'lk-session-modal';
            modal.innerHTML = `
                <div class="lk-session-backdrop"></div>
                <div class="lk-session-dialog" role="dialog" aria-modal="true" aria-labelledby="session-expired-warning-title">
                    <div class="lk-session-content">
                        <div class="lk-session-icon warning">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 8v4"/>
                                <path d="M12 16h.01"/>
                            </svg>
                        </div>
                        
                        <h2 id="session-expired-warning-title" class="lk-session-title">
                            Sua sessão expirou
                        </h2>
                        
                        <p class="lk-session-message">
                            Você ficou inativo por muito tempo.
                        </p>
                        
                        <p class="lk-session-submessage">
                            Deseja continuar sua sessão ou prefere sair?
                        </p>
                        
                        <div class="lk-session-actions">
                            <button type="button" class="lk-session-btn lk-session-btn-secondary" id="lk-session-expired-logout-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16,17 21,12 16,7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                Sair
                            </button>
                            <button type="button" class="lk-session-btn lk-session-btn-primary" id="lk-session-expired-renew-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                    <path d="M3 3v5h5"/>
                                    <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                                    <path d="M16 21h5v-5"/>
                                </svg>
                                Continuar Sessão
                            </button>
                        </div>
                    </div>
                </div>
            `;

            return modal;
        },

        // Cria o modal de sessão expirada
        createLoggedOutModal() {
            const modal = document.createElement('div');
            modal.id = 'lk-session-loggedout-modal';
            modal.className = 'lk-session-modal';
            modal.innerHTML = `
                <div class="lk-session-backdrop"></div>
                <div class="lk-session-dialog" role="dialog" aria-modal="true" aria-labelledby="session-expired-title">
                    <div class="lk-session-content">
                        <div class="lk-session-icon expired">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </div>
                        
                        <h2 id="session-expired-title" class="lk-session-title">
                            Você foi desconectado
                        </h2>
                        
                        <p class="lk-session-message">
                            Sua sessão expirou por inatividade.
                        </p>
                        
                        <p class="lk-session-submessage">
                            Por motivos de segurança, você foi automaticamente desconectado.
                            Faça login novamente para continuar.
                        </p>
                        
                        <div class="lk-session-actions single">
                            <button type="button" class="lk-session-btn lk-session-btn-primary" id="lk-session-login-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                    <polyline points="10,17 15,12 10,7"/>
                                    <line x1="15" y1="12" x2="3" y2="12"/>
                                </svg>
                                Fazer Login
                            </button>
                        </div>
                    </div>
                </div>
            `;

            return modal;
        },

        // Mostra o modal de aviso
        showWarningModal() {
            if (state.isWarningShown) return;

            const container = this.getContainer();
            let modal = document.getElementById('lk-session-warning-modal');

            if (!modal) {
                modal = this.createWarningModal();
                container.appendChild(modal);

                // Event listeners
                document.getElementById('lk-session-renew-btn')?.addEventListener('click', () => {
                    SessionManager.renewSession();
                });

                document.getElementById('lk-session-logout-btn')?.addEventListener('click', () => {
                    SessionManager.logout();
                });
            }

            // Reset progress bar
            const progressBar = document.getElementById('lk-session-progress');
            if (progressBar) {
                progressBar.style.width = '100%';
            }

            // Mostra modal com animação
            requestAnimationFrame(() => {
                modal.classList.add('active');
                state.isWarningShown = true;

                // Inicia countdown visual
                this.startCountdown();
            });

            // Foca no botão de renovar para acessibilidade
            setTimeout(() => {
                document.getElementById('lk-session-renew-btn')?.focus();
            }, CONFIG.animationDuration);
        },

        // Esconde o modal de aviso
        hideWarningModal() {
            const modal = document.getElementById('lk-session-warning-modal');
            if (modal) {
                modal.classList.remove('active');
                state.isWarningShown = false;
                this.stopCountdown();
            }
            // Também esconde o modal de expirado se estiver aberto
            const expiredModal = document.getElementById('lk-session-expired-warning-modal');
            if (expiredModal) {
                expiredModal.classList.remove('active');
            }
        },

        // Mostra o modal de sessão expirada mas que ainda pode renovar
        showExpiredWarningModal() {
            if (state.isWarningShown) return;

            // Esconde outros modais
            this.hideWarningModal();

            const container = this.getContainer();
            let modal = document.getElementById('lk-session-expired-warning-modal');

            if (!modal) {
                modal = this.createExpiredWarningModal();
                container.appendChild(modal);

                // Event listeners
                document.getElementById('lk-session-expired-renew-btn')?.addEventListener('click', () => {
                    SessionManager.renewSession();
                });

                document.getElementById('lk-session-expired-logout-btn')?.addEventListener('click', () => {
                    SessionManager.logout();
                });
            }

            // Mostra modal com animação
            requestAnimationFrame(() => {
                modal.classList.add('active');
                state.isWarningShown = true;
            });

            // Foca no botão de renovar para acessibilidade
            setTimeout(() => {
                document.getElementById('lk-session-expired-renew-btn')?.focus();
            }, CONFIG.animationDuration);
        },

        // Mostra o modal de desconectado
        showLoggedOutModal() {
            if (state.isLoggedOutShown) return;

            // Esconde o modal de aviso se estiver aberto
            this.hideWarningModal();

            const container = this.getContainer();
            let modal = document.getElementById('lk-session-loggedout-modal');

            if (!modal) {
                modal = this.createLoggedOutModal();
                container.appendChild(modal);

                // Event listener
                document.getElementById('lk-session-login-btn')?.addEventListener('click', () => {
                    SessionManager.redirectToLogin();
                });
            }

            // Mostra modal com animação
            requestAnimationFrame(() => {
                modal.classList.add('active');
                state.isLoggedOutShown = true;
            });

            // Foca no botão de login
            setTimeout(() => {
                document.getElementById('lk-session-login-btn')?.focus();
            }, CONFIG.animationDuration);
        },

        // Inicia o countdown visual
        startCountdown() {
            this.stopCountdown();

            const updateCountdown = () => {
                const countdownEl = document.getElementById('lk-session-countdown');
                const progressBar = document.getElementById('lk-session-progress');

                if (countdownEl) {
                    countdownEl.textContent = utils.formatTime(state.remainingTime);
                }

                if (progressBar) {
                    const percentage = (state.remainingTime / CONFIG.warningThreshold) * 100;
                    progressBar.style.width = `${Math.max(0, Math.min(100, percentage))}%`;

                    // Muda cor conforme o tempo diminui
                    if (percentage <= 20) {
                        progressBar.classList.add('critical');
                    } else if (percentage <= 50) {
                        progressBar.classList.add('warning');
                        progressBar.classList.remove('critical');
                    } else {
                        progressBar.classList.remove('warning', 'critical');
                    }
                }

                // Decrementa tempo
                state.remainingTime = Math.max(0, state.remainingTime - 1);

                // Verifica se expirou
                if (state.remainingTime <= 0) {
                    this.stopCountdown();
                    this.showLoggedOutModal();
                }
            };

            // Atualiza imediatamente
            updateCountdown();

            // Atualiza a cada segundo
            state.countdownIntervalId = setInterval(updateCountdown, 1000);
        },

        // Para o countdown
        stopCountdown() {
            if (state.countdownIntervalId) {
                clearInterval(state.countdownIntervalId);
                state.countdownIntervalId = null;
            }
        },

        // Mostra toast de sucesso
        showSuccessToast(message) {
            // Usa SweetAlert2 se disponível
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Sessão Renovada!',
                    text: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'lk-session-toast'
                    }
                });
            } else {
                // Fallback para toast simples
                this.showSimpleToast(message, 'success');
            }
        },

        // Toast simples como fallback
        showSimpleToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `lk-simple-toast lk-simple-toast-${type}`;
            toast.innerHTML = `
                <div class="lk-simple-toast-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22,4 12,14.01 9,11.01"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.add('show');
            });

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };

    // ========================================================================
    // SESSION MANAGER
    // ========================================================================
    const SessionManager = {
        // Inicializa o gerenciador de sessão
        init() {
            // Verifica se está em uma página que requer autenticação
            if (!this.isAuthenticatedPage()) {
                return;
            }

            // Injeta CSS
            this.injectStyles();

            // Verifica status inicial
            this.checkSession();

            // Inicia verificação periódica
            this.startPeriodicCheck();

            // Escuta eventos de visibilidade da página
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.checkSession();
                }
            });

            // Escuta atividade do usuário para resetar timer de inatividade
            this.setupActivityListeners();

        },

        // Verifica se está em página autenticada
        isAuthenticatedPage() {
            // Verifica se há indicadores de página autenticada
            const hasCSRFMeta = !!document.querySelector('meta[name="csrf-token"]');
            const isLoginPage = window.location.pathname.includes('/login');
            const isPublicPage = window.location.pathname.includes('/site/') ||
                window.location.pathname === '/';

            return hasCSRFMeta && !isLoginPage && !isPublicPage;
        },

        // Injeta os estilos CSS
        injectStyles() {
            if (document.getElementById('lk-session-styles')) return;

            const link = document.createElement('link');
            link.id = 'lk-session-styles';
            link.rel = 'stylesheet';
            link.href = utils.getBaseUrl() + 'assets/css/session-manager.css';
            document.head.appendChild(link);
        },

        // Configura listeners de atividade
        setupActivityListeners() {
            const events = ['mousedown', 'keydown', 'touchstart', 'scroll'];
            let lastActivityUpdate = 0;

            const handleActivity = () => {
                const now = Date.now();
                // Atualiza no máximo a cada 60 segundos
                if (now - lastActivityUpdate > 60000) {
                    lastActivityUpdate = now;
                    // Não faz heartbeat aqui para não sobrecarregar
                    // O check periódico já cuida disso
                }
            };

            events.forEach(event => {
                document.addEventListener(event, handleActivity, { passive: true });
            });
        },

        // Inicia verificação periódica
        startPeriodicCheck() {
            this.stopPeriodicCheck();
            state.checkIntervalId = setInterval(() => {
                this.checkSession();
            }, CONFIG.checkInterval);
        },

        // Para verificação periódica
        stopPeriodicCheck() {
            if (state.checkIntervalId) {
                clearInterval(state.checkIntervalId);
                state.checkIntervalId = null;
            }
        },

        // Verifica status da sessão
        async checkSession() {
            const result = await utils.apiRequest(CONFIG.endpoints.status);

            // Se não conseguiu conectar, não faz nada (pode ser problema de rede)
            if (!result) {
                return;
            }

            const data = result.data || {};
            state.remainingTime = data.remainingTime || 0;
            state.lastCheck = Date.now();

            // Atualiza configurações do servidor
            if (data.warningThreshold) {
                CONFIG.warningThreshold = data.warningThreshold;
            }
            if (data.sessionLifetime) {
                CONFIG.sessionLifetime = data.sessionLifetime;
            }

            // Verifica o status da sessão
            // Caso 1: Usuário não tem sessão alguma (nunca logou ou sessão completamente inválida)
            if (result.status === 401 && !data.canRenew) {
                UI.showLoggedOutModal();
                this.stopPeriodicCheck();
                return;
            }

            // Caso 2: Sessão expirada mas ainda pode renovar (grace period)
            if (data.expired && data.canRenew) {
                // Mostra o modal de aviso para dar chance de renovar
                if (!state.isWarningShown && !state.isLoggedOutShown) {
                    state.remainingTime = 0; // Já expirou
                    UI.showExpiredWarningModal();
                }
                return;
            }

            // Caso 3: Sessão ativa, verifica se deve mostrar aviso
            if (data.showWarning && !state.isWarningShown && !state.isLoggedOutShown) {
                UI.showWarningModal();
            } else if (!data.showWarning && state.isWarningShown) {
                // Sessão foi renovada por outra aba/ação
                UI.hideWarningModal();
            }

            // Caso 4: Sessão completamente expirada e sem possibilidade de renovar
            if (data.expired && !data.canRenew) {
                UI.showLoggedOutModal();
                this.stopPeriodicCheck();
            }
        },

        // Renova a sessão
        async renewSession() {
            // Obtém os botões de renovação (pode ser do modal de aviso OU do modal de expirado)
            const renewBtn = document.getElementById('lk-session-renew-btn');
            const expiredRenewBtn = document.getElementById('lk-session-expired-renew-btn');

            const setButtonLoading = (btn) => {
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `
                        <svg class="lk-session-spinner" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="60 30"/>
                        </svg>
                        Renovando...
                    `;
                }
            };

            const restoreButton = (btn) => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                            <path d="M16 21h5v-5"/>
                        </svg>
                        Continuar Conectado
                    `;
                }
            };

            setButtonLoading(renewBtn);
            setButtonLoading(expiredRenewBtn);

            const result = await utils.apiRequest(CONFIG.endpoints.renew, 'POST', {
                _token: utils.getCsrfToken()
            });

            if (result && result.ok && result.data?.success) {
                // Atualiza token CSRF se fornecido
                if (result.data.newToken) {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfMeta.content = result.data.newToken;
                    }
                }

                // Atualiza estado
                state.remainingTime = result.data.remainingTime || CONFIG.sessionLifetime;

                // Esconde modal
                UI.hideWarningModal();

                // Mostra sucesso
                UI.showSuccessToast('Sua sessão foi renovada com sucesso!');

                // Reinicia verificação
                this.startPeriodicCheck();
            } else {
                // Falha na renovação
                UI.showLoggedOutModal();
                this.stopPeriodicCheck();
            }

            // Restaura botões
            restoreButton(renewBtn);
            restoreButton(expiredRenewBtn);
        },

        // Faz logout
        logout() {
            window.location.href = utils.getBaseUrl() + 'logout';
        },

        // Redireciona para login
        redirectToLogin() {
            const currentPath = window.location.pathname + window.location.search;
            const loginUrl = utils.getBaseUrl() + 'login';

            // Salva a página atual para redirecionar após login
            try {
                sessionStorage.setItem('lk_redirect_after_login', currentPath);
            } catch (e) {
                // Ignora erro de sessionStorage
            }

            window.location.href = loginUrl;
        }
    };

    // ========================================================================
    // INICIALIZAÇÃO
    // ========================================================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => SessionManager.init());
    } else {
        SessionManager.init();
    }

    // Expõe para uso externo se necessário
    window.LK = window.LK || {};
    window.LK.SessionManager = SessionManager;

})();
