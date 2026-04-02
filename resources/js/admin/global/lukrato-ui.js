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
 * Delega para LKFeedback (toasts), lkFetch (HTTP) e CsrfManager (CSRF)
 * quando disponíveis, com fallback direto para SweetAlert2 / fetch nativo.
 *
 * @version 1.0.0
 */
(function () {
    'use strict';

    // ── helpers ──────────────────────────────────────────────────
    function getBase() {
        if (window.LK?.getBase) return window.LK.getBase();
        const meta = document.querySelector('meta[name="base-url"]');
        if (meta?.content) return meta.content.replace(/\/?$/, '/');
        return '/';
    }

    function getCsrf() {
        if (window.CsrfManager?.getToken) return window.CsrfManager.getToken();
        if (window.LK?.getCSRF) return window.LK.getCSRF();
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta?.content) return meta.content;
        const input = document.querySelector('input[name="csrf_token"]');
        return input?.value || '';
    }

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
            return () => {};
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
    // Usa lkFetch quando disponível. Senão, fetch nativo com CSRF.
    // Todas retornam Promise<{ok, data, message?, errors?}>

    async function apiFetch(url, method, body, opts = {}) {
        // Resolve URL relativa baseada no BASE_URL
        const fullUrl = url.startsWith('http') ? url : getBase() + url.replace(/^\//, '');

        // Tentar via lkFetch (tem retry, timeout, loading bar)
        if (window.lkFetch && !opts._skipLkFetch) {
            try {
                const headers = { 'Accept': 'application/json' };
                if (method !== 'GET') {
                    headers['X-CSRF-Token'] = getCsrf();
                }

                const fetchOpts = { method, headers, credentials: 'same-origin' };

                if (body !== undefined && body !== null) {
                    if (body instanceof FormData) {
                        // FormData: don't set Content-Type (browser sets boundary)
                        fetchOpts.body = body;
                        delete headers['Content-Type'];
                    } else {
                        headers['Content-Type'] = 'application/json';
                        fetchOpts.body = JSON.stringify(body);
                    }
                }

                const result = await window.lkFetch.request(fullUrl, fetchOpts, {
                    showLoading: opts.showLoading ?? false,
                    loadingTarget: opts.loadingTarget || null,
                });

                return { ok: true, data: result.data?.data ?? result.data, raw: result.data };
            } catch (err) {
                return { ok: false, message: err.message || 'Erro na requisição', data: null };
            }
        }

        // Fallback: fetch nativo
        try {
            const headers = { 'Accept': 'application/json' };
            if (method !== 'GET') {
                headers['X-CSRF-Token'] = getCsrf();
            }
            const fetchOpts = { method, headers, credentials: 'same-origin' };

            if (body !== undefined && body !== null) {
                if (body instanceof FormData) {
                    fetchOpts.body = body;
                } else {
                    headers['Content-Type'] = 'application/json';
                    fetchOpts.body = JSON.stringify(body);
                }
            }

            const res = await fetch(fullUrl, fetchOpts);
            const json = await res.json().catch(() => null);

            if (!res.ok) {
                const msg = json?.message || json?.errors?.[0] || `Erro ${res.status}`;
                return { ok: false, message: msg, errors: json?.errors, data: null, status: res.status };
            }

            return { ok: true, data: json?.data ?? json, raw: json };
        } catch (err) {
            return { ok: false, message: err.message || 'Erro de conexão', data: null };
        }
    }

    const api = {
        get:    (url, opts) => apiFetch(url, 'GET', null, opts),
        post:   (url, data, opts) => apiFetch(url, 'POST', data, opts),
        put:    (url, data, opts) => apiFetch(url, 'PUT', data, opts),
        patch:  (url, data, opts) => apiFetch(url, 'PATCH', data, opts),
        delete: (url, opts) => apiFetch(url, 'DELETE', null, opts),
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
    LK.getBase = LK.getBase || getBase;
    LK.getCSRF = LK.getCSRF || getCsrf;
    window.LK = LK;

})();
