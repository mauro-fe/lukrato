/**
 * ============================================================================
 * LUKRATO — LK Namespace Setup + Error Suppression + Page Transitions
 * ============================================================================
 * Extraído de views/admin/partials/header.php (inline <script> block)
 *
 * Responsabilidades:
 * - Suprimir erros do Bootstrap Modal (backdrop issues)
 * - Configurar window.LK namespace com helpers globais
 * - Sistema de transições entre páginas
 * - Inicialização DOM (header, bell, modals, transitions)
 * ============================================================================
 */

import { getRuntimeConfig } from './runtime-config.js';

// ── Error Suppression (Bootstrap Modal backdrop only) ────────────────────────
// Narrowly suppresses only the known Bootstrap 5 backdrop disposal error.
// Previous version also suppressed ALL "Cannot read properties of undefined"
// which masked real bugs. Now only suppresses messages matching Bootstrap's
// specific backdrop-related error patterns.
(function () {
    if (window.__LK_PRODUCTION_CONSOLE_GUARD__) {
        return;
    }

    window.__LK_PRODUCTION_CONSOLE_GUARD__ = true;

    const isLocalDebugEnvironment = () => {
        const host = String(window.location.hostname || '').toLowerCase();
        return host === 'localhost'
            || host === '127.0.0.1'
            || host === '::1'
            || host.endsWith('.local')
            || host.endsWith('.test');
    };

    const sanitizeConsoleArgs = (args, fallbackLabel) => {
        const strings = args
            .filter((arg) => typeof arg === 'string' && arg.trim() !== '')
            .map((arg) => arg.trim());

        if (strings.length > 0) {
            return strings.join(' | ');
        }

        const status = args.find((arg) => typeof arg?.status === 'number')?.status;
        return status ? `${fallbackLabel} (status=${status})` : fallbackLabel;
    };

    const isBootstrapBackdropError = (msg) =>
        typeof msg === 'string' &&
        msg.includes('backdrop') &&
        (msg.includes('Modal') || msg.includes('modal') || msg.includes('dispose') || msg.includes('hide'));

    const originalError = console.error.bind(console);
    const originalWarn = console.warn.bind(console);
    console.error = function (...args) {
        const message = args.join(' ');
        if (isBootstrapBackdropError(message)) return;
        if (!isLocalDebugEnvironment()) {
            originalError(sanitizeConsoleArgs(args, 'Erro inesperado'));
            return;
        }
        originalError(...args);
    };

    console.warn = function (...args) {
        if (!isLocalDebugEnvironment()) {
            originalWarn(sanitizeConsoleArgs(args, 'Aviso inesperado'));
            return;
        }
        originalWarn(...args);
    };

    window.addEventListener('error', function (event) {
        if (event.message && isBootstrapBackdropError(event.message)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return true;
        }
    }, true);

    window.onerror = function (message) {
        if (isBootstrapBackdropError(message)) return true;
        return false;
    };
})();

// ── LK Namespace ────────────────────────────────────────────────────────────
window.LK = window.LK || {};

// Lê csrfTtl do bridge PHP→JS
window.LK.csrfTtl = getRuntimeConfig().csrfTtl ?? 3600;

function getLancamentoCreateFallbackUrl(options = {}) {
    const runtimeBaseUrl = String(getRuntimeConfig().baseUrl || '/');
    const currentUrl = new URL(window.location.href);
    const normalizedBaseUrl = runtimeBaseUrl.replace(/\/?$/, '/');
    const target = new URL(`${normalizedBaseUrl}lancamentos/novo`, window.location.origin);

    const explicitReturnPath = typeof options?.returnPath === 'string'
        ? options.returnPath.trim().replace(/^\/+/, '')
        : '';

    if (explicitReturnPath !== '') {
        target.searchParams.set('return', explicitReturnPath);
    }

    try {
        const base = new URL(runtimeBaseUrl, window.location.origin);
        if (explicitReturnPath === '' && currentUrl.origin === base.origin && currentUrl.pathname.startsWith(base.pathname)) {
            const relativePath = currentUrl.pathname.slice(base.pathname.length).replace(/^\/+/, '');
            const returnPath = `${relativePath}${currentUrl.search}`.replace(/^\/+/, '');
            if (returnPath) {
                target.searchParams.set('return', returnPath);
            }
        }
    } catch {
        // ignore and keep bare route
    }

    if (options?.source === 'contas') {
        target.searchParams.set('origem', 'contas');
    }

    if (options?.presetAccountId) {
        target.searchParams.set('conta', String(options.presetAccountId));
    }

    if (options?.tipo) {
        target.searchParams.set('tipo', String(options.tipo));
    }

    return target.toString();
}

function flushPendingToast() {
    let raw = '';

    try {
        raw = sessionStorage.getItem('lk:pending-toast') || '';
        if (raw !== '') {
            sessionStorage.removeItem('lk:pending-toast');
        }
    } catch {
        raw = '';
    }

    if (!raw) {
        return;
    }

    try {
        const payload = JSON.parse(raw);
        const message = String(payload?.message || '').trim();
        const type = String(payload?.type || 'success').trim() || 'success';

        if (message && typeof window.showToast === 'function') {
            window.showToast(message, type);
        }
    } catch {
        // ignore malformed payload
    }
}

// ── Page Transitions ────────────────────────────────────────────────────────
LK.initPageTransitions = () => {
    const overlay = document.getElementById('lkPageTransitionOverlay');
    if (!overlay) return;

    let isTransitioning = false;

    const cleanup = () => {
        isTransitioning = false;
        overlay.classList.remove('active');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('page-transitioning');
    };

    const startTransition = (target) => {
        if (isTransitioning) return;
        isTransitioning = true;
        document.body.classList.add('page-transitioning');
        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden', 'false');

        setTimeout(() => {
            window.location.href = target;
        }, 220);
    };

    const isSamePageAnchor = (href) => href?.startsWith('#');

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a[href]');
        if (!link) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;
        if (link.dataset.noTransition === 'true') return;

        const href = link.getAttribute('href');
        if (!href || isSamePageAnchor(href)) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (url.pathname === window.location.pathname && url.search === window.location.search) return;

        event.preventDefault();
        startTransition(url.href);
    });

    window.addEventListener('pageshow', cleanup);
    cleanup();
};

// ── Global FAB Menu Helper ──────────────────────────────────────────────────
window.openLancamentoModalGlobal = function (options = {}) {
    if (window.LK?.modals?.openLancamentoModal) {
        window.LK.modals.openLancamentoModal(options);
        return;
    }

    if (typeof lancamentoGlobalManager !== 'undefined') {
        lancamentoGlobalManager.openModal(options);
        return;
    }

    window.location.href = getLancamentoCreateFallbackUrl(options);
};

// ── DOM Initialization ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (window.LK?.initHeader) {
        window.LK.initHeader();
    }

    if (window.initNotificationsBell) {
        window.initNotificationsBell();
    }

    if (window.LK?.initModals) {
        window.LK.initModals();
    }

    if (window.LK?.initPageTransitions) {
        window.LK.initPageTransitions();
    }

    flushPendingToast();
});
