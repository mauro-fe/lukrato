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

    const CONFIG = {
        // Cores do tema
        colors: {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b', 
            info: '#3b82f6',
            primary: '#6366f1',
        },
        // Duração padrão em ms
        durations: {
            toast: 3000,
            alert: null, // Sem auto-close
            loading: null,
        },
        // Posição do toast
        toastPosition: 'top-end',
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
            duration = null,
            showConfirmButton = true,
            confirmButtonText = 'Entendi',
        } = options;

        return Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            timer: duration,
            timerProgressBar: !!duration,
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
            duration = 5000,
            showConfirmButton = true,
            confirmButtonText = 'OK',
            toast = false,
        } = options;

        return Swal.fire({
            icon: 'warning',
            title: toast ? message : title,
            text: toast ? undefined : message,
            toast: toast,
            position: toast ? CONFIG.toastPosition : 'center',
            timer: duration,
            timerProgressBar: !!duration,
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
            default: '🚀 Desbloqueie todo o potencial',
        };

        const contextMsg = contextMessages[context] || contextMessages.default;

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
            confirmButtonColor: '#f59e0b',
            ...getSwalTheme(),
            customClass: {
                popup: 'lk-swal-popup lk-swal-upgrade',
                htmlContainer: 'lk-swal-html',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = (window.BASE_URL || '/') + 'billing';
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
