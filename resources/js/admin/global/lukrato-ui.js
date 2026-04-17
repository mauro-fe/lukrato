/**
 * Lukrato UI — Facade Unificada
 * 
 * Provê uma API única e consistente para:
 *   LK.toast.success(msg)  / .error(msg) / .warning(msg) / .info(msg)
 *   LK.api.get(url)        / .post(url, data) / .put(url, data) / .delete(url)
 *   LK.confirm(opts)       — prompt de confirmação padronizado
 *   LK.loading(msg)        — action loading (overlay/modal)
 *   LK.hideLoading()
 *   LK.pageLoading(msg)    — page loading (area de conteudo)
 *   LK.sectionLoading(el)  — section loading (bloco/cartao)
 *
 * Delega para LKFeedback (toasts) e shared/api (HTTP + CSRF)
 * com fallback direto para SweetAlert2 quando necessario.
 *
 * @version 1.0.0
 */
import { apiFetch as sharedApiFetch, getApiPayload, getErrorMessage } from '../shared/api.js';

(function () {
    'use strict';

    // ── TOAST ────────────────────────────────────────────────────
    // Wrappers finos sobre LKFeedback (já carregado globalmente).
    // Se LKFeedback não existir, faz Swal.fire direto.

    const toast = {
        success(msg, opts) {
            if (window.LKFeedback) return window.LKFeedback.success(msg, { toast: true, ...opts });
            return Swal.fire({ icon: 'success', title: msg, toast: true, position: 'top-end', timer: 3000, timerProgressBar: true, showConfirmButton: false });
        },
        error(msg, opts) {
            if (window.LKFeedback) return window.LKFeedback.error(msg, { toast: true, duration: 5000, showConfirmButton: false, ...opts });
            return Swal.fire({ icon: 'error', title: msg, toast: true, position: 'top-end', timer: 5000, timerProgressBar: true, showConfirmButton: false });
        },
        warning(msg, opts) {
            if (window.LKFeedback) return window.LKFeedback.warning(msg, { toast: true, showConfirmButton: false, ...opts });
            return Swal.fire({ icon: 'warning', title: msg, toast: true, position: 'top-end', timer: 4000, timerProgressBar: true, showConfirmButton: false });
        },
        info(msg, opts) {
            if (window.LKFeedback) return window.LKFeedback.info(msg, { toast: true, ...opts });
            return Swal.fire({ icon: 'info', title: msg, toast: true, position: 'top-end', timer: 3000, timerProgressBar: true, showConfirmButton: false });
        },
    };

    // ── CONFIRM ──────────────────────────────────────────────────
    // Retorna uma Promise<boolean> (true = confirmado, false = cancelado)

    /**
     * @param {Object} opts
     * @param {string} opts.title
     * @param {string} [opts.text]
     * @param {string} [opts.html]
     * @param {string} [opts.icon='warning']
     * @param {string} [opts.confirmText='Confirmar']
     * @param {string} [opts.cancelText='Cancelar']
     * @param {string} [opts.confirmColor]
     * @param {boolean} [opts.danger=false]  — atalho: botão vermelho
     * @returns {Promise<boolean>}
     */
    async function confirm(opts = {}) {
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

        if (window.LKFeedback?.confirm) {
            const result = await window.LKFeedback.confirm(title, text || html || '', {
                confirmText,
                cancelText,
                icon,
            });
            return !!result?.isConfirmed;
        }

        const result = await Swal.fire({
            title,
            text,
            html,
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            confirmButtonColor: confirmColor || (danger
                ? (getComputedStyle(document.documentElement).getPropertyValue('--color-danger').trim() || '#e74c3c')
                : (getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#e67e22')),
            cancelButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--color-neutral').trim() || '#95a5a6',
            focusCancel: danger,
        });
        return !!result.isConfirmed;
    }

    // ── LOADING ──────────────────────────────────────────────────

    function loading(msg) {
        if (window.LKFeedback?.loading) return window.LKFeedback.loading(msg);
        return Swal.fire({
            title: msg || 'Carregando...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading(),
        });
    }

    function hideLoading() {
        if (window.LKFeedback?.hideLoading) return window.LKFeedback.hideLoading();
        Swal.close();
    }

    function getPageLoadingApi() {
        return window.LKPageLoading || null;
    }

    function pageLoading(message, options = {}) {
        const api = getPageLoadingApi();
        if (!api?.show) {
            return () => { };
        }

        return api.show(message || 'Carregando...', options);
    }

    function hidePageLoading() {
        const api = getPageLoadingApi();
        api?.hide?.();
    }

    async function withPageLoading(task, options = {}) {
        const api = getPageLoadingApi();
        if (!api?.withLoading) {
            if (typeof task === 'function') {
                return task();
            }
            return task;
        }
        return api.withLoading(task, options);
    }

    function sectionLoading(target, isLoading = true) {
        const api = getPageLoadingApi();
        if (api?.setSectionLoading) {
            api.setSectionLoading(target, isLoading);
            return;
        }

        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (!element) {
            return;
        }
        element.classList.toggle('lk-section-loading', !!isLoading);
        element.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    // ── API (HTTP) ───────────────────────────────────────────────
    // Adapter fino sobre a camada compartilhada.
    // Mantem o contrato legado esperado por LK.api.*

    function normalizeApiRequestOptions(opts = {}) {
        const request = opts && typeof opts === 'object' ? { ...opts } : {};
        const extra = {};

        if (Object.prototype.hasOwnProperty.call(request, 'timeout')) {
            extra.timeout = request.timeout;
            delete request.timeout;
        }

        if (request.suppressErrorLogging === true) {
            extra.suppressErrorLogging = true;
        }

        delete request.suppressErrorLogging;
        delete request.showLoading;
        delete request.loadingTarget;

        return { request, extra };
    }

    function buildLegacyApiSuccess(raw) {
        return {
            ok: true,
            data: getApiPayload(raw, null),
            raw,
            message: typeof raw?.message === 'string' ? raw.message : null,
            errors: raw?.errors ?? null,
            status: Number(raw?.status || 200),
        };
    }

    function buildLegacyApiFailure(error) {
        const status = Number(error?.status || error?.data?.status || 0) || null;

        return {
            ok: false,
            data: null,
            raw: error?.data ?? null,
            message: getErrorMessage(error, 'Erro na requisição'),
            errors: error?.data?.errors ?? null,
            status,
        };
    }

    async function apiRequest(url, method, body, opts = {}) {
        const { request, extra } = normalizeApiRequestOptions(opts);
        const options = {
            ...request,
            method,
        };

        if (body !== undefined && body !== null && options.body === undefined) {
            options.body = body;
        }

        try {
            const raw = await sharedApiFetch(url, options, extra);
            return buildLegacyApiSuccess(raw);
        } catch (error) {
            return buildLegacyApiFailure(error);
        }
    }

    const api = {
        get: (url, opts) => apiRequest(url, 'GET', null, opts),
        post: (url, data, opts) => apiRequest(url, 'POST', data, opts),
        put: (url, data, opts) => apiRequest(url, 'PUT', data, opts),
        patch: (url, data, opts) => apiRequest(url, 'PATCH', data, opts),
        delete: (url, opts) => apiRequest(url, 'DELETE', null, opts),
    };

    // ── EXPOSE GLOBAL ────────────────────────────────────────────
    // Extend existing window.LK if it exists, don't overwrite

    const LK = window.LK || {};
    LK.toast = toast;
    LK.api = api;
    LK.confirm = confirm;
    LK.loading = loading;
    LK.hideLoading = hideLoading;
    LK.pageLoading = pageLoading;
    LK.hidePageLoading = hidePageLoading;
    LK.withPageLoading = withPageLoading;
    LK.sectionLoading = sectionLoading;
    window.LK = LK;

})();
