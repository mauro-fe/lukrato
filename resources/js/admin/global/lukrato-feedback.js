/**
 * ============================================
 * LUKRATO FEEDBACK - Sistema Unificado
 * ============================================
 * Funções wrapper para SweetAlert2 garantindo
 * consistência visual e acessibilidade.
 */

import { buildAppUrl } from '../shared/api.js';

(function () {
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
            error: style.getPropertyValue('--color-danger').trim() || FALLBACK_COLORS.error,
            warning: style.getPropertyValue('--color-warning').trim() || FALLBACK_COLORS.warning,
            info: style.getPropertyValue('--color-secondary').trim() || FALLBACK_COLORS.info,
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

        return buildAppUrl(raw);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function joinClassNames(...parts) {
        return parts.filter(Boolean).join(' ');
    }

    function resolveConfirmIconName(icon, isDanger) {
        const normalizedIcon = typeof icon === 'string' ? icon.toLowerCase() : 'question';
        const iconMap = {
            error: 'octagon-alert',
            info: 'info',
            question: 'circle-help',
            success: 'badge-check',
            warning: 'triangle-alert',
        };

        if (isDanger && (normalizedIcon === 'warning' || normalizedIcon === 'question')) {
            return 'triangle-alert';
        }

        return iconMap[normalizedIcon] || 'circle-help';
    }

    function resolveConfirmEyebrow(eyebrow, icon, isDanger) {
        if (typeof eyebrow === 'string' && eyebrow.trim() !== '') {
            return eyebrow.trim();
        }

        const normalizedIcon = typeof icon === 'string' ? icon.toLowerCase() : 'question';

        if (isDanger || normalizedIcon === 'warning' || normalizedIcon === 'error') {
            return 'Atenção';
        }

        return 'Confirmação';
    }

    function buildConfirmHtml({ title, message, html, iconName, eyebrow, isDanger }) {
        return `
            <div class="lk-swal-card${isDanger ? ' lk-swal-card--danger' : ''}">
                <div class="lk-swal-card__hero">
                    <div class="lk-swal-card__icon" aria-hidden="true">
                        <i data-lucide="${escapeHtml(iconName)}"></i>
                    </div>
                    <div class="lk-swal-card__copy">
                        <span class="lk-swal-card__eyebrow">${escapeHtml(eyebrow)}</span>
                        <h3 class="lk-swal-card__title">${escapeHtml(title)}</h3>
                        ${message !== '' ? `<p class="lk-swal-card__text">${escapeHtml(message)}</p>` : ''}
                    </div>
                </div>
                ${html ? `<div class="lk-swal-card__body">${html}</div>` : ''}
            </div>
        `;
    }

    function decorateConfirmPopup(popup, accentColor, originalDidOpen) {
        const titleEl = popup.querySelector('.lk-swal-card__title');
        const textEl = popup.querySelector('.lk-swal-card__text');

        popup.setAttribute('role', 'alertdialog');

        if (titleEl) {
            titleEl.id = 'lkSwalConfirmTitle';
            popup.setAttribute('aria-labelledby', titleEl.id);
        }

        if (textEl) {
            textEl.id = 'lkSwalConfirmDescription';
            popup.setAttribute('aria-describedby', textEl.id);
        }

        if (accentColor) {
            popup.style.setProperty('--lk-swal-accent', accentColor);
        }

        window.lucide?.createIcons?.({ nodes: [popup] });

        if (typeof originalDidOpen === 'function') {
            originalDidOpen(popup);
        }
    }

    function buildConfirmDialogOptions(message, options = {}) {
        const {
            title = 'Confirmar',
            confirmButtonText = 'Confirmar',
            cancelButtonText = 'Cancelar',
            icon = 'question',
            iconName,
            eyebrow,
            description = '',
            html = '',
            isDanger = false,
            reverseButtons = true,
            focusCancel = isDanger,
            customClass = {},
            confirmButtonColor,
            didOpen,
            ...restOptions
        } = options;

        const confirmMessage = description || message || '';
        const bodyHtml = typeof html === 'string' ? html : '';
        const resolvedIconName = iconName || resolveConfirmIconName(icon, isDanger);
        const resolvedEyebrow = resolveConfirmEyebrow(eyebrow, icon, isDanger);
        const accentColor = confirmButtonColor || (isDanger ? CONFIG.colors.error : CONFIG.colors.primary);

        return {
            ...restOptions,
            title: '',
            html: buildConfirmHtml({
                title,
                message: confirmMessage,
                html: bodyHtml,
                iconName: resolvedIconName,
                eyebrow: resolvedEyebrow,
                isDanger,
            }),
            showCancelButton: true,
            confirmButtonText,
            cancelButtonText,
            confirmButtonColor: accentColor,
            reverseButtons,
            focusCancel,
            ...getSwalTheme(),
            customClass: {
                ...customClass,
                popup: joinClassNames(
                    'lk-swal-popup',
                    'lk-swal-confirm',
                    isDanger ? 'lk-swal-confirm--danger' : '',
                    customClass.popup
                ),
                confirmButton: joinClassNames(
                    isDanger ? 'lk-swal-btn-danger' : '',
                    customClass.confirmButton
                ),
            },
            didOpen: (popup) => {
                decorateConfirmPopup(popup, accentColor, didOpen);
            },
        };
    }

    function hasCustomPopupClass(customClass) {
        if (!customClass || typeof customClass !== 'object') {
            return false;
        }

        if (typeof customClass.popup === 'string') {
            return customClass.popup.trim() !== '';
        }

        return Array.isArray(customClass.popup) && customClass.popup.length > 0;
    }

    function isDangerConfirmColor(value) {
        if (typeof value !== 'string') {
            return false;
        }

        return /(danger|#e74c3c|#ef4444|#dc3545|#d33\b)/i.test(value.trim());
    }

    function canAutoShellRawConfirm(options) {
        if (!options || typeof options !== 'object' || Array.isArray(options)) {
            return false;
        }

        if (options.showCancelButton !== true || options.toast === true) {
            return false;
        }

        if (options.html != null && options.html !== '' && typeof options.html !== 'string') {
            return false;
        }

        if (hasCustomPopupClass(options.customClass)) {
            return false;
        }

        return typeof options.title === 'string'
            || typeof options.text === 'string'
            || typeof options.html === 'string'
            || typeof options.input === 'string';
    }

    function installRawSwalConfirmShell() {
        if (!window.Swal?.fire || window.Swal.__lkConfirmShellInstalled === true) {
            return;
        }

        const originalFire = window.Swal.fire.bind(window.Swal);

        window.Swal.fire = function wrappedSwalFire(...args) {
            if (args.length !== 1 || !canAutoShellRawConfirm(args[0])) {
                return originalFire(...args);
            }

            const options = args[0];
            const isDanger = options.icon === 'error'
                || isDangerConfirmColor(options.confirmButtonColor)
                || (options.focusCancel === true && options.icon === 'warning');

            return originalFire(buildConfirmDialogOptions(options.text || '', {
                ...options,
                title: options.title || 'Confirmar',
                isDanger,
            }));
        };

        window.Swal.__lkConfirmShellInstalled = true;
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
        return Swal.fire(buildConfirmDialogOptions(message, options));
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
                window.location.href = buildAppUrl('billing');
            }
            return result;
        });
    }

    installRawSwalConfirmShell();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', installRawSwalConfirmShell, { once: true });
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
    window.showNotification = function (message, type = 'success') {
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
