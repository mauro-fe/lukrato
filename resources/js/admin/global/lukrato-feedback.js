/**
 * ============================================
 * LUKRATO FEEDBACK - Sistema Unificado
 * ============================================
 * Funções wrapper para SweetAlert2 garantindo
 * consistência visual e acessibilidade.
 */

(function() {
    'use strict';

    // ============================================
    // CONFIGURAÇÃO
    // ============================================

    // Cores fallback alinhadas com variables.css
    const FALLBACK_COLORS = {
        success: '#2ecc71',
        error: '#e74c3c',
        warning: '#f39c12',
        info: '#3498db',
        primary: '#e67e22',
    };

    /**
     * Lê cores dos CSS custom properties (design tokens) com fallback.
     * Garante sincronização automática com dark/light theme.
     */
    function getThemeColors() {
        const root = document.documentElement;
        const style = getComputedStyle(root);
        return {
            success: style.getPropertyValue('--color-success').trim() || FALLBACK_COLORS.success,
            error:   style.getPropertyValue('--color-danger').trim()  || FALLBACK_COLORS.error,
            warning: style.getPropertyValue('--color-warning').trim() || FALLBACK_COLORS.warning,
            info:    style.getPropertyValue('--color-secondary').trim() || FALLBACK_COLORS.info,
            primary: style.getPropertyValue('--color-primary').trim() || FALLBACK_COLORS.primary,
        };
    }

    const CONFIG = {
        // Duração padrão em ms
        durations: {
            toast: 3000,
            alert: null, // Sem auto-close
            loading: null,
        },
        // Posição do toast
        toastPosition: 'top-end',

        // Getter dinâmico para cores — sempre lê dos CSS tokens
        get colors() {
            return getThemeColors();
        },
    };

    // ============================================
    // HELPERS
    // ============================================

    function getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }

    function getSwalTheme() {
        const isDark = getTheme() === 'dark';
        return {
            background: isDark ? '#1e293b' : '#ffffff',
            color: isDark ? '#f1f5f9' : '#1e293b',
            confirmButtonColor: CONFIG.colors.primary,
        };
    }

    function resolveUpgradeUrl(upgradeUrl) {
        const raw = typeof upgradeUrl === 'string' && upgradeUrl.trim()
            ? upgradeUrl.trim()
            : 'billing';

        if (/^https?:\/\//i.test(raw)) {
            return raw;
        }

        const base = (typeof window.getBaseUrl === 'function' ? window.getBaseUrl() : (window.BASE_URL || '/'))
            .replace(/\/?$/, '/');
        const normalizedPath = raw.replace(/^\/+/, '');
        return `${base}${normalizedPath}`;
    }

    // ============================================
    // FEEDBACK FUNCTIONS
    // ============================================

    /**
     * Mostra alerta de sucesso
     */
    function showSuccess(message, options = {}) {
        const {
            title = 'Sucesso!',
            duration = CONFIG.durations.toast,
            showConfirmButton = false,
            toast = true,
        } = options;

        return Swal.fire({
            icon: 'success',
            title: toast ? message : title,
            text: toast ? undefined : message,
            toast: toast,
            position: toast ? CONFIG.toastPosition : 'center',
            timer: duration,
            timerProgressBar: !!duration,
            showConfirmButton: showConfirmButton,
            ...getSwalTheme(),
            customClass: {
                popup: 'lk-swal-popup',
                title: 'lk-swal-title',
            },
            didOpen: (popup) => {
                popup.setAttribute('role', 'alert');
                popup.setAttribute('aria-live', 'polite');
            },
        });
    }

    /**
     * Mostra alerta de erro
     */
    function showError(message, options = {}) {
        const {
            title = 'Erro',
            duration = options.toast ? 5000 : null,
            timer = duration,
            showConfirmButton = !options.toast,
            confirmButtonText = 'Entendi',
            toast = false,
        } = options;

        return Swal.fire({
            icon: 'error',
            title: toast ? message : title,
            text: toast ? undefined : message,
            toast: toast,
            position: toast ? CONFIG.toastPosition : 'center',
            timer: timer,
            timerProgressBar: !!timer,
            showConfirmButton: showConfirmButton,
            confirmButtonText: confirmButtonText,
            ...getSwalTheme(),
            customClass: {
                popup: 'lk-swal-popup lk-swal-error',
            },
            didOpen: (popup) => {
                popup.setAttribute('role', 'alert');
                popup.setAttribute('aria-live', 'assertive');
            },
        });
    }

    /**
     * Mostra alerta de aviso
     */
    function showWarning(message, options = {}) {
        const {
            title = 'Atenção',
            duration = options.toast ? 4000 : 5000,
            timer = duration,
            showConfirmButton = !options.toast,
            confirmButtonText = 'OK',
            toast = false,
        } = options;

        return Swal.fire({
            icon: 'warning',
            title: toast ? message : title,
            text: toast ? undefined : message,
            toast: toast,
            position: toast ? CONFIG.toastPosition : 'center',
            timer: timer,
            timerProgressBar: !!timer,
            showConfirmButton: showConfirmButton,
            confirmButtonText: confirmButtonText,
            ...getSwalTheme(),
            customClass: {
                popup: 'lk-swal-popup lk-swal-warning',
            },
            didOpen: (popup) => {
                popup.setAttribute('role', 'alert');
                popup.setAttribute('aria-live', 'polite');
            },
        });
    }

    /**
     * Mostra alerta informativo
     */
    function showInfo(message, options = {}) {
        const {
            title = 'Informação',
            duration = 4000,
            showConfirmButton = false,
            toast = true,
        } = options;

        return Swal.fire({
            icon: 'info',
            title: toast ? message : title,
            text: toast ? undefined : message,
            toast: toast,
            position: toast ? CONFIG.toastPosition : 'center',
            timer: duration,
            timerProgressBar: !!duration,
            showConfirmButton: showConfirmButton,
            ...getSwalTheme(),
            customClass: {
                popup: 'lk-swal-popup lk-swal-info',
            },
            didOpen: (popup) => {
                popup.setAttribute('role', 'status');
                popup.setAttribute('aria-live', 'polite');
            },
        });
    }

    /**
     * Mostra diálogo de confirmação
     */
    function showConfirm(message, options = {}) {
        const {
            title = 'Confirmar',
            confirmButtonText = 'Confirmar',
            cancelButtonText = 'Cancelar',
            icon = 'question',
            isDanger = false,
        } = options;

        return Swal.fire({
            icon: icon,
            title: title,
            text: message,
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
            confirmButtonColor: isDanger ? CONFIG.colors.error : CONFIG.colors.primary,
            reverseButtons: true,
            focusCancel: isDanger,
            ...getSwalTheme(),
            customClass: {
                popup: 'lk-swal-popup lk-swal-confirm',
                confirmButton: isDanger ? 'lk-swal-btn-danger' : '',
            },
        });
    }

    /**
     * Mostra loading
     */
    function showLoading(message = 'Carregando...', options = {}) {
        const { allowOutsideClick = false } = options;

        return Swal.fire({
            title: message,
            allowOutsideClick: allowOutsideClick,
            allowEscapeKey: false,
            showConfirmButton: false,
            ...getSwalTheme(),
            customClass: {
                popup: 'lk-swal-popup lk-swal-loading',
            },
            didOpen: () => {
                Swal.showLoading();
            },
        });
    }

    /**
     * Fecha loading/alert atual
     */
    function hideLoading() {
        Swal.close();
    }

    /**
     * Mostra modal de upgrade para Pro
     */
    function showUpgradePrompt(options = {}) {
        const {
            title = '🚀 Desbloqueie com o Pro',
            message = 'Este recurso está disponível no plano Pro.',
            features = [
                'Lançamentos ilimitados',
                'Relatórios avançados',
                'Exportação PDF/Excel',
                'Suporte prioritário',
            ],
            context = 'default',
            upgradeUrl = null,
        } = options;

        // Mensagens contextuais
        const contextMessages = {
            relatorios: '📊 Análises completas e exportação com o Pro',
            cartoes: '💳 Gerencie todos os seus cartões de crédito',
            contas: '🏦 Organize todas as suas contas bancárias',
            agendamentos: '⏰ Lembretes automáticos por email',
            metas: '🎯 Crie metas ilimitadas',
            categorias: '🏷️ Personalize sem limites',
            lancamentos: '💰 Registre sem preocupações',
            dashboard: '📈 Dashboard avançado com insights',
            faturas: '📄 Visualize todo o histórico de faturas',
            financas: '📊 Metas e orçamento sem limites para planejar melhor',
            orcamento: '📈 Orçamentos inteligentes e ilimitados',
            perfil: '👤 Recursos avançados para personalizar sua conta',
            gamification: '🏆 Acelere seu progresso e desbloqueie vantagens exclusivas',
            default: '🚀 Desbloqueie todo o potencial',
        };

        const contextMsg = contextMessages[context] || contextMessages.default;
        const targetUpgradeUrl = resolveUpgradeUrl(upgradeUrl);

        return Swal.fire({
            title: title,
            html: `
                <div class="upgrade-prompt-content">
                    <p class="upgrade-context-msg">${contextMsg}</p>
                    <p>${message}</p>
                    <ul class="upgrade-features-list">
                        ${features.map(f => `<li><i data-lucide="check"></i> ${f}</li>`).join('')}
                    </ul>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i data-lucide="crown"></i> Quero ser Pro!',
            cancelButtonText: 'Agora não',
            confirmButtonColor: CONFIG.colors.warning,
            ...getSwalTheme(),
            customClass: {
                popup: 'lk-swal-popup lk-swal-upgrade',
                htmlContainer: 'lk-swal-html',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = targetUpgradeUrl;
            }
            return result;
        });
    }

    /**
     * Mostra alerta de limite atingido
     */
    function showLimitReached(options = {}) {
        const {
            resource = 'recurso',
            used = 0,
            limit = 0,
            context = 'default',
        } = options;

        const percentage = limit > 0 ? Math.round((used / limit) * 100) : 100;

        return Swal.fire({
            title: '🚫 Limite Atingido',
            html: `
                <div class="limit-reached-content">
                    <div class="limit-progress-bar">
                        <div class="limit-progress-fill" style="width: 100%"></div>
                    </div>
                    <p class="limit-stats">${used} de ${limit} ${resource} usados (${percentage}%)</p>
                    <p>Faça upgrade para o Pro e tenha acesso ilimitado!</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i data-lucide="rocket"></i> Fazer Upgrade',
            cancelButtonText: 'Depois',
            confirmButtonColor: CONFIG.colors.primary,
            ...getSwalTheme(),
            customClass: {
                popup: 'lk-swal-popup lk-swal-limit',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = (window.BASE_URL || '/') + 'billing';
            }
            return result;
        });
    }

    // ============================================
    // API PÚBLICA
    // ============================================

    window.LKFeedback = {
        success: showSuccess,
        error: showError,
        warning: showWarning,
        info: showInfo,
        confirm: showConfirm,
        loading: showLoading,
        hideLoading: hideLoading,
        upgradePrompt: showUpgradePrompt,
        limitReached: showLimitReached,
        
        // Alias para compatibilidade
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showConfirm,
        showLoading,
        hideLoading,
    };

    // Alias global para acesso rápido
    window.showNotification = function(message, type = 'success') {
        switch (type) {
            case 'error':
            case 'danger':
                return showError(message, { toast: true, duration: 4000, showConfirmButton: false });
            case 'warning':
                return showWarning(message, { toast: true, duration: 4000 });
            case 'info':
                return showInfo(message);
            default:
                return showSuccess(message);
        }
    };

})();
