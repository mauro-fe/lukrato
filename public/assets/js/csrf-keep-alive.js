(function (win, doc) {
    'use strict';

    if (!win || !doc) {
        return;
    }

    const SAFETY = 0.75; // renova antes de expirar
    const MIN_INTERVAL = 60 * 1000; // 1 minuto
    const lkTtl = win && win.LK && win.LK.csrfTtl ? Number(win.LK.csrfTtl) : 0;
    const globalTtl = win && win.__CSRF_TTL ? Number(win.__CSRF_TTL) : 0;
    const baseTtlSeconds = Math.max(lkTtl || globalTtl || 1200, 60);
    const DEFAULT_INTERVAL = baseTtlSeconds * 1000 * SAFETY;
    const state = new Map(); // tokenId => { timer, refreshing, interval }
    let cachedBase = null;

    const cssEscape = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function')
        ? CSS.escape
        : function (value) {
            return String(value).replace(/[^a-zA-Z0-9_\-]/g, '\\$&');
        };

    function resolveBase() {
        if (cachedBase) {
            return cachedBase;
        }
        const meta = doc.querySelector('meta[name="base-url"]');
        let base = meta && meta.content ? meta.content : '';

        if (!base && typeof win.BASE_URL === 'string') {
            base = win.BASE_URL;
        }
        if (!base && win.LK && typeof win.LK.getBase === 'function') {
            base = win.LK.getBase();
        }

        if (!base) {
            base = '/';
        }

        cachedBase = base.endsWith('/') ? base : base + '/';
        return cachedBase;
    }

    function ensureTokenId(tokenId) {
        if (!tokenId || state.has(tokenId)) {
            return;
        }

        state.set(tokenId, {
            timer: null,
            refreshing: false,
            interval: DEFAULT_INTERVAL,
        });

        schedule(tokenId);
    }

    function schedule(tokenId) {
        const ctx = state.get(tokenId);
        if (!ctx) {
            return;
        }

        if (ctx.timer) {
            clearTimeout(ctx.timer);
        }

        const delay = Math.max(ctx.interval || DEFAULT_INTERVAL, MIN_INTERVAL);
        ctx.timer = setTimeout(() => refresh(tokenId), delay);
    }

    async function refresh(tokenId) {
        const ctx = state.get(tokenId);
        if (!ctx || ctx.refreshing) {
            return;
        }

        ctx.refreshing = true;

        try {
            const res = await fetch(resolveBase() + 'api/csrf/refresh', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token_id: tokenId }),
            });

            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            const data = await res.json();
            if (data && data.token) {
                const newTokenId = data.token_id || tokenId;
                applyToken(newTokenId, data.token);

                const ttlSeconds = Number(data.ttl || 0);
                if (Number.isFinite(ttlSeconds) && ttlSeconds > 0) {
                    ctx.interval = Math.max(ttlSeconds * 1000 * SAFETY, MIN_INTERVAL);
                }
            }
        } catch (err) {
            console.warn('[Lukrato] Falha ao renovar token CSRF:', err);
        } finally {
            ctx.refreshing = false;
            schedule(tokenId);
        }
    }

    function applyToken(tokenId, token) {
        if (!tokenId || !token) {
            return;
        }

        const selector = '[data-csrf-id="' + cssEscape(tokenId) + '"]';
        doc.querySelectorAll(selector).forEach((node) => {
            if (node.tagName === 'META') {
                node.setAttribute('content', token);
            } else if ('value' in node) {
                node.value = token;
            }
        });

        if (!win.LK) {
            win.LK = {};
        }
        win.LK.csrfToken = token;
    }

    function bootstrap() {
        const nodes = doc.querySelectorAll('[data-csrf-id]');
        nodes.forEach((node) => {
            const tokenId = node.getAttribute('data-csrf-id');
            if (tokenId) {
                ensureTokenId(tokenId);
            }
        });
    }

    function refreshVisibleTokens() {
        state.forEach((_ctx, tokenId) => {
            refresh(tokenId);
        });
    }

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', bootstrap, { once: true });
    } else {
        bootstrap();
    }

    doc.addEventListener('visibilitychange', () => {
        if (doc.visibilityState === 'visible') {
            refreshVisibleTokens();
        }
    });

    win.addEventListener('focus', refreshVisibleTokens);
})(window, document);
