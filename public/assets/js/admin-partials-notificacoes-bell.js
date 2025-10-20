// /assets/js/notifications.js
// Componente de notificações (sininho) — independente e reutilizável

export function initNotificationsBell(userOpts = {}) {
    // ---------- Config ----------
    const findMetaBase = () => {
        const m = document.querySelector('meta[name="base-url"]');
        if (m?.content) return m.content.replace(/\/?$/, '/');
        if (window.BASE_URL) return String(window.BASE_URL).replace(/\/?$/, '/');
        return (location.origin + '/').replace(/\/?$/, '/');
    };

    const defaults = {
        // Selectors (ids padrão do seu partial)
        selectors: {
            bell: '#lk-bell',
            badge: '#lk-bell-badge',
            menu: '#lk-bell-menu',
            list: '#lk-bell-list',
            markButton: '#lk-mark-read',
        },
        // Endpoints (padrão = como sugeri; ajuste se usa /unread-count)
        endpoints: {
            list: 'api/notificacoes',
            unread: 'api/notificacoes/unread',        // altere p/ 'api/notificacoes/unread-count' se for seu caso
            markRead: 'api/notificacoes/marcar-lida',
        },
        // Base e CSRF
        baseUrl: findMetaBase(),
        getCsrf: () => (window.CSRF || document.querySelector('#lk-bell-menu')?.dataset?.csrf || ''),
        // Comportamento
        pollMs: 30000,
        showError: (msg) => {
            // plug: integre com SweetAlert2 se quiser
            console.error('[notifications]', msg);
        },
    };

    const opts = deepMerge(defaults, userOpts);

    // ---------- DOM ----------
    const bellBtn = qs(opts.selectors.bell);
    const bellBadge = qs(opts.selectors.badge);
    const menu = qs(opts.selectors.menu);
    const listEl = qs(opts.selectors.list);
    const markBtn = qs(opts.selectors.markButton);

    // Early exit se não tiver o sino na página
    if (!bellBtn || !menu) return { destroy: () => { } };

    // ---------- State ----------
    const state = {
        items: [],
        unread: 0,
        loaded: false,
        loading: false,
        pollId: null,
    };

    // ---------- Helpers ----------
    function qs(sel, sc = document) { return sc.querySelector(sel); }

    function deepMerge(a, b) {
        const out = { ...a };
        for (const k in b) {
            if (b[k] && typeof b[k] === 'object' && !Array.isArray(b[k])) {
                out[k] = deepMerge(a[k] || {}, b[k]);
            } else {
                out[k] = b[k];
            }
        }
        return out;
    }

    const escapeHtml = (str) => {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    };

    const fmtDate = (value) => {
        if (!value) return '';
        try {
            const d = new Date(String(value).replace(' ', 'T'));
            if (isNaN(d)) return String(value);
            return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(d);
        } catch {
            return String(value);
        }
    };

    async function fetchJSON(path, init = {}) {
        const url = opts.baseUrl.replace(/\/?$/, '/') + path.replace(/^\/?/, '');
        const r = await fetch(url, { credentials: 'include', ...init });
        let json = null; try { json = await r.json(); } catch { }
        if (!r.ok) {
            const msg = json?.message || json?.error || `Erro ${r.status}`;
            throw new Error(msg);
        }
        if (json && (json.error || json.status === 'error')) {
            throw new Error(json?.message || json?.error || 'Erro na requisição');
        }
        return json;
    }

    const api = {
        unread: async () => {
            const j = await fetchJSON(opts.endpoints.unread);
            return j?.data?.unread ?? j?.unread ?? 0;
        },
        list: async () => {
            const j = await fetchJSON(opts.endpoints.list);
            return j?.data?.itens ?? j?.itens ?? [];
        },
        markRead: async (ids) => {
            const form = new FormData();
            const token = opts.getCsrf();
            ids.forEach(id => form.append('ids[]', String(id)));
            if (token) {
                form.append('_token', token);
                form.append('csrf_token', token);
            }
            const headers = token ? { 'X-CSRF-Token': token } : {};
            const j = await fetchJSON(opts.endpoints.markRead, {
                method: 'POST', body: form, headers
            });
            return j;
        }
    };

    // ---------- UI ----------
    const updateBadge = (n) => {
        if (!bellBadge) return;
        const v = Number(n) || 0;
        if (v > 0) {
            bellBadge.textContent = v > 99 ? '99+' : String(v);
            bellBadge.style.display = 'inline-flex';
        } else {
            bellBadge.textContent = '0';
            bellBadge.style.display = 'none';
        }
    };

    const setLoading = (v) => {
        state.loading = v;
        if (v && listEl) {
            listEl.innerHTML = `<div class="lk-notification-loading">Carregando...</div>`;
        }
    };

    const renderList = () => {
        if (!listEl) return;
        const items = state.items || [];
        if (!items.length) {
            listEl.innerHTML = `<div class="lk-notification-empty">Nenhum aviso por aqui.</div>`;
            markBtn && (markBtn.style.display = 'none', markBtn.disabled = true);
            return;
        }
        const html = items.map(n => {
            const unread = Number(n.lida) === 0;
            const titulo = escapeHtml(n.titulo || 'Aviso');
            const msg = escapeHtml(n.mensagem || '');
            const quando = escapeHtml(fmtDate(n.created_at || n.updated_at || n.data_pagamento));
            const link = n.link ? `<a class="lk-notification-link" href="${escapeHtml(n.link)}">Abrir</a>` : '';
            return `
      <article class="lk-notification-item${unread ? ' unread' : ''}"
               data-id="${n.id}" data-lida="${unread ? '0' : '1'}">
        <div class="lk-notification-title">${titulo}${unread ? ' <span class="badge">novo</span>' : ''}</div>
        ${msg ? `<p class="lk-notification-message">${msg}</p>` : ''}
        <div class="lk-notification-meta">
          <span class="lk-notification-date">${quando}</span>
          ${link}
        </div>
      </article>`;
        }).join('');
        listEl.innerHTML = `<div class="lk-notification-list">${html}</div>`;

        const hasUnread = items.some(i => Number(i.lida) === 0);
        if (markBtn) {
            markBtn.style.display = hasUnread ? '' : 'none';
            markBtn.disabled = !hasUnread;
        }
    };

    // ---------- Fluxo ----------
    async function loadUnread() {
        try {
            state.unread = Number(await api.unread());
            updateBadge(state.unread);
        } catch (e) {
            // silencia (ex.: sessão expirada); log se quiser
        }
    }

    async function loadList({ force = false } = {}) {
        if (state.loading) return;
        if (state.loaded && !force) return;
        setLoading(true);
        try {
            state.items = await api.list();
            state.loaded = true;
            renderList();
        } catch (e) {
            listEl && (listEl.innerHTML = `<div class="lk-notification-error">Erro ao carregar notificações.</div>`);
            opts.showError(e.message || 'Falha ao listar notificações');
        } finally {
            setLoading(false);
        }
    }

    async function openMenu() {
        menu.classList.remove('hidden');
        bellBtn.setAttribute('aria-expanded', 'true');
        await loadList({ force: true });
        await loadUnread();
    }

    function closeMenu() {
        menu.classList.add('hidden');
        bellBtn.setAttribute('aria-expanded', 'false');
    }

    // ---------- Eventos ----------
    const onBellClick = async () => {
        if (menu.classList.contains('hidden')) await openMenu(); else closeMenu();
    };

    const onClickOutside = (e) => {
        if (!menu.contains(e.target) && !bellBtn.contains(e.target)) closeMenu();
    };

    const onEsc = (e) => { if (e.key === 'Escape') closeMenu(); };

    const onMarkRead = async () => {
        const ids = (state.items || [])
            .filter(n => Number(n.lida) === 0)
            .map(n => Number(n.id))
            .filter(Boolean);
        if (!ids.length) return;

        markBtn.disabled = true;
        try {
            await api.markRead(ids);
            state.items = state.items.map(n => ids.includes(Number(n.id)) ? { ...n, lida: 1 } : n);
            state.unread = Math.max(0, state.unread - ids.length);
            renderList();
            updateBadge(state.unread);
        } catch (e) {
            opts.showError(e.message || 'Não foi possível marcar como lidas.');
        } finally {
            markBtn.disabled = false;
        }
    };

    // Eventos globais para “data-changed”
    const onDataChanged = (e) => {
        const res = String(e?.detail?.resource || '').toLowerCase();
        if (['notifications', 'notificacoes', 'agendamentos', 'transactions'].includes(res)) {
            state.loaded = false;
            loadList({ force: true });
            loadUnread();
        }
    };

    // ---------- Bootstrap ----------
    bellBtn.addEventListener('click', onBellClick);
    document.addEventListener('click', onClickOutside);
    document.addEventListener('keydown', onEsc);
    markBtn?.addEventListener('click', onMarkRead);
    document.addEventListener('lukrato:data-changed', onDataChanged);

    loadUnread();
    state.pollId = setInterval(loadUnread, opts.pollMs);

    // ---------- API pública ----------
    return {
        refresh: async () => { await loadList({ force: true }); await loadUnread(); },
        destroy: () => {
            clearInterval(state.pollId);
            bellBtn.removeEventListener('click', onBellClick);
            document.removeEventListener('click', onClickOutside);
            document.removeEventListener('keydown', onEsc);
            markBtn?.removeEventListener('click', onMarkRead);
            document.removeEventListener('lukrato:data-changed', onDataChanged);
        }
    };
}
