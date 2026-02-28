/**
 * ============================================================================
 * LUKRATO — Shared UI Utilities
 * ============================================================================
 * Toast, confirm dialogs, loading overlays.
 * Delegates to LKFeedback / SweetAlert2 when available.
 *
 * import { showToast, showConfirm, showLoading, hideLoading } from '../shared/ui';
 * ============================================================================
 */

// ─── Toast Notifications ────────────────────────────────────────────────────

/**
 * Mostrar toast de sucesso
 * @param {string} message
 * @param {Object} [opts]
 */
export function toastSuccess(message, opts = {}) {
    if (window.LKFeedback?.success) return window.LKFeedback.success(message, { toast: true, ...opts });
    return _swalToast('success', message, opts);
}

/**
 * Mostrar toast de erro
 * @param {string} message
 * @param {Object} [opts]
 */
export function toastError(message, opts = {}) {
    if (window.LKFeedback?.error) return window.LKFeedback.error(message, { toast: true, duration: 4000, ...opts });
    return _swalToast('error', message, { timer: 4000, ...opts });
}

/**
 * Mostrar toast de aviso
 * @param {string} message
 * @param {Object} [opts]
 */
export function toastWarning(message, opts = {}) {
    if (window.LKFeedback?.warning) return window.LKFeedback.warning(message, { toast: true, ...opts });
    return _swalToast('warning', message, { timer: 4000, ...opts });
}

/**
 * Mostrar toast informativo
 * @param {string} message
 * @param {Object} [opts]
 */
export function toastInfo(message, opts = {}) {
    if (window.LKFeedback?.info) return window.LKFeedback.info(message, { toast: true, ...opts });
    return _swalToast('info', message, opts);
}

/**
 * showToast genérico (compatível com assinatura antiga)
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 */
export function showToast(message, type = 'success') {
    const map = { success: toastSuccess, error: toastError, warning: toastWarning, info: toastInfo };
    return (map[type] || toastInfo)(message);
}

// ─── Confirm Dialog ─────────────────────────────────────────────────────────

/**
 * Diálogo de confirmação (Promise<boolean>)
 * @param {Object} opts
 * @param {string} [opts.title='Tem certeza?']
 * @param {string} [opts.text]
 * @param {string} [opts.html]
 * @param {string} [opts.icon='warning']
 * @param {string} [opts.confirmText='Confirmar']
 * @param {string} [opts.cancelText='Cancelar']
 * @param {boolean} [opts.danger=false] — botão vermelho
 * @returns {Promise<boolean>}
 */
export async function showConfirm(opts = {}) {
    const {
        title = 'Tem certeza?',
        text,
        html,
        icon = 'warning',
        confirmText = 'Confirmar',
        cancelText = 'Cancelar',
        confirmColor,
        danger = false,
    } = opts;

    // Delegate to LKFeedback
    if (window.LKFeedback?.confirm) {
        const result = await window.LKFeedback.confirm(text || html || '', {
            title,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            icon,
            isDanger: danger,
        });
        return !!result?.isConfirmed;
    }

    // Fallback to Swal directly
    if (window.Swal) {
        const result = await Swal.fire({
            title,
            text,
            html,
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            confirmButtonColor: confirmColor || (danger ? '#e74c3c' : '#e67e22'),
            cancelButtonColor: '#95a5a6',
            reverseButtons: true,
            focusCancel: danger,
        });
        return !!result.isConfirmed;
    }

    // Last resort: native confirm
    return confirm(`${title}\n\n${text || ''}`);
}

/**
 * Atalho para confirmação de exclusão
 * @param {string} [itemName='este item']
 * @returns {Promise<boolean>}
 */
export function confirmDelete(itemName = 'este item') {
    return showConfirm({
        title: 'Excluir?',
        text: `Tem certeza que deseja excluir ${itemName}? Esta ação não pode ser desfeita.`,
        icon: 'warning',
        confirmText: 'Sim, excluir',
        cancelText: 'Cancelar',
        danger: true,
    });
}

// ─── Loading ────────────────────────────────────────────────────────────────

/**
 * Exibir overlay de carregamento
 * @param {string} [message='Carregando...']
 */
export function showLoading(message = 'Carregando...') {
    if (window.LKFeedback?.loading) return window.LKFeedback.loading(message);
    if (window.Swal) {
        return Swal.fire({
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });
    }
}

/**
 * Esconder overlay de carregamento
 */
export function hideLoading() {
    if (window.LKFeedback?.hideLoading) return window.LKFeedback.hideLoading();
    if (window.Swal) Swal.close();
}

// ─── DOM Helpers ────────────────────────────────────────────────────────────

/**
 * Selecionar elemento (atalho)
 * @param {string} selector
 * @param {Element} [parent=document]
 * @returns {Element|null}
 */
export const $ = (selector, parent = document) => parent.querySelector(selector);

/**
 * Selecionar múltiplos elementos
 * @param {string} selector
 * @param {Element} [parent=document]
 * @returns {Element[]}
 */
export const $$ = (selector, parent = document) => [...parent.querySelectorAll(selector)];

/**
 * Inicializar ícones Lucide (se disponível)
 */
export function refreshIcons() {
    if (window.lucide?.createIcons) {
        window.lucide.createIcons();
    }
}

/**
 * Aplicar máscara de loading em um botão
 * @param {HTMLElement} btn
 * @param {boolean} loading
 * @param {string} [originalText]
 */
export function setBtnLoading(btn, loading, originalText) {
    if (!btn) return;
    if (loading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Salvando...';
        refreshIcons();
    } else {
        btn.disabled = false;
        btn.innerHTML = originalText || btn.dataset.originalText || 'Salvar';
        refreshIcons();
    }
}

// ─── Private Helpers ────────────────────────────────────────────────────────

function _swalToast(icon, message, opts = {}) {
    if (window.Swal) {
        return Swal.fire({
            icon,
            title: message,
            toast: true,
            position: 'top-end',
            timer: opts.timer || 3000,
            timerProgressBar: true,
            showConfirmButton: false,
        });
    }
    // Fallback: DOM toast
    _domToast(message, icon);
}

function _domToast(message, type = 'info') {
    let container = document.getElementById('lk-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'lk-toast-container';
        container.style.cssText = 'position:fixed;top:80px;right:20px;z-index:10000;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `lk-toast lk-toast-${type}`;
    toast.innerHTML = `<span>${message}</span>`;
    toast.style.cssText = 'padding:14px 20px;border-radius:10px;background:var(--color-surface,#fff);border-left:4px solid var(--color-primary,#e67e22);box-shadow:0 4px 12px rgba(0,0,0,.15);color:var(--color-text,#333);opacity:0;transition:opacity .3s;';
    container.appendChild(toast);
    requestAnimationFrame(() => (toast.style.opacity = '1'));
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
