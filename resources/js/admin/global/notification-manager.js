/**
 * ============================================================================
 * LUKRATO — Notification Manager
 * ============================================================================
 * Gerencia o sino de notificações, popover e polling de contagem.
 * Extraído de: views/admin/partials/notificacoes/bell.php
 * ============================================================================
 */

(() => {
    // Evita múltiplas instâncias
    if (window.__lkNotificationManagerInitialized) {
        return;
    }
    window.__lkNotificationManagerInitialized = true;

    /* ============ Utility/Helper Functions ============ */

    /**
     * Safely gets base URL from global config or meta tags.
     * @returns {string} The base URL.
     */
    const getBaseUrl = () =>
        (window.LK?.getBase?.() ??
            document.querySelector('meta[name="base-url"]')?.content ??
            '/').replace(/\/$/, '') + '/';

    /**
     * Safely gets CSRF token.
     * @returns {string} The CSRF token.
     */
    const getCSRF = () =>
    (window.LK?.getCSRF?.() ??
        document.querySelector('meta[name="csrf"]')?.content ??
        document.querySelector('meta[name="csrf-token"]')?.content ??
        document.getElementById('lk-bell-menu')?.dataset?.csrf ??
        '');

    /**
     * Handles common fetch errors (401, 403).
     * @param {Response} res The fetch response.
     * @returns {Promise<boolean>} True if an error was handled (redirection/alert).
     */
    async function handleFetch403(res) {
        const base = getBaseUrl();
        if (res.status === 401) {
            const here = encodeURIComponent(location.pathname + location.search);
            location.href = `${base}login?return=${here}`;
            return true;
        }
        if (res.status === 403) {
            let msg = 'Acesso não permitido.';
            try {
                const j = await res.clone().json();
                msg = j?.message || msg;
            } catch { }
            window.Swal?.fire ? Swal.fire('Acesso restrito', msg, 'warning') : alert(msg);
            return true;
        }
        return false;
    }

    /**
     * Escapes HTML special characters.
     * @param {string} s The string to escape.
     * @returns {string} The escaped string.
     */
    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m]));
    }

    /**
     * Formats notification date/time to a friendly string.
     * Falls back to raw value if parsing fails.
     * @param {string|number|Date} value
     * @returns {string}
     */
    function formatNotificationTime(value) {
        if (!value) return '';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return String(value);
        return d.toLocaleString('pt-BR', {
            dateStyle: 'short',
            timeStyle: 'short'
        });
    }

    /* ============ API Service Module ============ */

    const NotificationApi = {
        /**
         * Fetches the list of notifications.
         * @returns {Promise<Array<object>>} The list of notification items.
         */
        async fetchList() {
            const url = `${getBaseUrl()}api/notificacoes`;
            const r = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (await handleFetch403(r)) return [];

            if (!r.ok) throw new Error(`Falha ao buscar avisos: HTTP ${r.status}`);
            const j = await r.json();

            const items = j?.data?.itens ?? j?.itens ?? [];
            return Array.isArray(items) ? items : [];
        },

        /**
         * Fetches the current unread count.
         * @returns {Promise<number>} The unread count.
         */
        async fetchUnreadCount() {
            const url = `${getBaseUrl()}api/notificacoes/unread`;
            const r = await fetch(url, {
                credentials: 'include'
            });
            if (await handleFetch403(r)) return 0;

            if (!r.ok) throw new Error(`Falha ao buscar contagem: HTTP ${r.status}`);
            const j = await r.json();
            return Number(j?.data?.unread ?? j?.unread ?? 0);
        },

        /**
         * Marks all notifications as read.
         * @returns {Promise<boolean>} True if successful.
         */
        async markAllRead() {
            const token = getCSRF();
            const body = new FormData();
            if (token) {
                body.append('_token', token);
                body.append('csrf_token', token);
            }

            const r = await fetch(`${getBaseUrl()}api/notificacoes/marcar-todas`, {
                method: 'POST',
                body,
                credentials: 'include'
            });
            if (await handleFetch403(r)) return false;

            // Se retornar 422, pode ser porque só há alertas dinâmicos (não salvos no banco)
            if (r.status === 422) {
                return true;
            }

            if (!r.ok) throw new Error(`Falha ao marcar como lidas: HTTP ${r.status}`);
            return r.ok;
        }
    };

    /* ============ Notification Manager Class ============ */

    class NotificationManager {
        constructor() {
            this.bellBtn = document.getElementById('lk-bell');
            this.menu = document.getElementById('lk-bell-menu');
            this.listEl = document.getElementById('lk-bell-list');
            this.badge = document.getElementById('lk-bell-badge');
            this.markReadBtn = document.getElementById('lk-mark-read');

            this.isMenuOpen = false;
            this.loadedOnce = false;
            this.isRefreshing = false;
            this.refreshTimeout = null;

            if (!this.bellBtn || !this.menu || !this.listEl) return;

            const initialText = (this.badge?.textContent ?? '').trim();
            this.initialUnread = Number.parseInt(initialText, 10) || 0;
            this.unreadCount = this.initialUnread;

            this.refreshUnread = this.refreshUnread.bind(this);
            this.loadList = this.loadList.bind(this);

            this.initEvents();
            this.startPolling();
            // Primeira atualização após 1 segundo
            setTimeout(() => this.refreshUnread(), 1000);
        }

        initEvents() {
            this.bellBtn.addEventListener('click', this.toggleMenu.bind(this));
            this.markReadBtn?.addEventListener('click', this.handleMarkAllRead.bind(this));
            document.addEventListener('click', this.handleOutsideClick.bind(this));
            document.addEventListener('keydown', this.handleEsc.bind(this));
            window.addEventListener('focus', this.refreshUnread);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.refreshUnread();
                }
            });
            document.addEventListener('lukrato:notifications-changed', () => {
                this.refreshUnread();
            });

            // Recarrega o popover *apenas* quando for visível
            this.menu.addEventListener('transitionend', (event) => {
                if (event.target !== this.menu) return;
                if (event.propertyName !== 'opacity') return;
                if (this.menu.classList.contains('visible')) {
                    this.loadList();
                }
            });
        }

        /* ============ UI Control ============ */

        toggleMenu(e) {
            e?.stopPropagation();
            this.isMenuOpen ? this.closeMenu() : this.openMenu();
        }

        openMenu() {
            this.isMenuOpen = true;
            this.menu.classList.add('visible');
            this.menu.classList.remove('hidden');
            this.bellBtn.setAttribute('aria-expanded', 'true');

            if (!this.loadedOnce) this.loadList();
        }

        closeMenu() {
            this.isMenuOpen = false;
            this.menu.classList.remove('visible');
            this.menu.classList.add('hidden');
            this.bellBtn.setAttribute('aria-expanded', 'false');
        }

        handleOutsideClick(e) {
            if (this.isMenuOpen && !this.menu.contains(e.target) && e.target !== this.bellBtn) {
                this.closeMenu();
            }
        }

        handleEsc(e) {
            if (e.key === 'Escape' && this.isMenuOpen) {
                this.closeMenu();
            }
        }

        setBadge(n) {
            if (!this.badge) return;
            const v = Number(n || 0);
            this.unreadCount = v;
            const label = v > 99 ? '99+' : String(v);

            if (v > 0) {
                this.badge.classList.remove('hidden');
                this.badge.textContent = label;
            } else {
                this.badge.classList.add('hidden');
            }

            if (this.bellBtn) {
                this.bellBtn.classList.toggle('lk-bell-alert', v > 0);
            }
            if (this.markReadBtn) {
                this.markReadBtn.disabled = v === 0;
            }
        }

        renderList(items) {
            if (!Array.isArray(items) || !items.length) {
                this.listEl.innerHTML = `
                    <div class="py-3 text-center" style="opacity:.75">
                        Nenhum aviso.
                    </div>`;
                this.setBadge(0);
                return;
            }

            const unread = items.filter(it => {
                const r = (it.lida ?? it.read ?? it.is_read ?? false);
                return r === false || r === 0 || r === '0';
            }).length;

            this.setBadge(unread);

            this.listEl.innerHTML = items.map((it) => {
                const title = it.titulo || it.title || 'Aviso';
                const body = it.mensagem || it.body || it.descricao || '';
                const timeRaw = it.data || it.created_at || it.timestamp || '';
                const time = formatNotificationTime(timeRaw);
                const isRead = (it.lida ?? it.read ?? it.is_read ?? false) ? true : false;
                const readClass = isRead ? 'is-read' : '';
                const tagClass = isRead ? 'lk-item-tag-read' : 'lk-item-tag-new';
                const tagText = isRead ? 'Lida' : 'Novo';

                return `
                    <div class="lk-popover-item ${readClass}" data-id="${it.id || ''}">
                        <div class="lk-popover-item-content">
                            <div class="lk-item-header">
                                <strong class="lk-item-title">${escapeHtml(title)}</strong>
                                <span class="lk-item-tag ${tagClass}">${tagText}</span>
                            </div>
                            <div class="lk-item-body">${escapeHtml(body)}</div>
                            ${time ? `<div class="lk-item-time">${escapeHtml(time)}</div>` : ''}
                        </div>
                    </div>`;
            }).join('');
        }

        /* ============ Data Management ============ */

        async loadList() {
            this.listEl.innerHTML = '<div class="py-3 text-center">Carregando...</div>';
            this.markReadBtn.disabled = true;

            try {
                const items = await NotificationApi.fetchList();
                this.renderList(items);
                this.loadedOnce = true;
            } catch (e) {
                console.error('Erro ao carregar lista de avisos:', e);
                this.listEl.innerHTML =
                    '<div class="py-3 text-center text-danger">Falha ao carregar avisos.</div>';
                this.setBadge(this.unreadCount);
            }
        }

        async refreshUnread() {
            if (this.isRefreshing) return;

            if (this.refreshTimeout) {
                clearTimeout(this.refreshTimeout);
            }

            this.refreshTimeout = setTimeout(async () => {
                this.isRefreshing = true;
                const previous = Number(this.unreadCount || 0);

                try {
                    const count = await NotificationApi.fetchUnreadCount();
                    this.setBadge(count);
                    if (this.isMenuOpen && count > previous) {
                        this.loadList();
                    }
                } catch (e) {
                    if (e instanceof TypeError && e.message.includes('NetworkError')) {
                        // Silencia erros de rede transitórios
                    } else if (e.message && !e.message.includes('500')) {
                        console.warn('Falha ao atualizar contagem:', e.message);
                    }
                } finally {
                    this.isRefreshing = false;
                }
            }, 200);
        }

        startPolling() {
            setInterval(() => this.refreshUnread(), 30000);
        }

        /* ============ Actions ============ */

        async handleMarkAllRead() {
            this.markReadBtn.disabled = true;
            this.markReadBtn.textContent = 'Aguarde...';

            try {
                const ok = await NotificationApi.markAllRead();
                if (ok) {
                    this.setBadge(0);
                    await this.loadList();
                    window.Swal?.fire?.('Pronto', 'Avisos marcados como lidos.', 'success');
                } else {
                    throw new Error('Falha desconhecida ao marcar como lidas.');
                }
            } catch (e) {
                console.error('Erro ao marcar como lidas:', e);
                window.Swal?.fire?.('Erro', e.message || 'Falha ao marcar como lidas.', 'error');
            } finally {
                this.markReadBtn.textContent = 'Marcar como lidas';
                this.markReadBtn.disabled = this.unreadCount === 0;
            }
        }
    }

    // Inicializa o gerenciador
    const manager = new NotificationManager();

    // Expõe helpers globais para interação externa
    window.lkNotify = {
        refresh: manager.loadList.bind(manager),
        setUnread: manager.setBadge.bind(manager),
        toggleMenu: manager.toggleMenu.bind(manager)
    };
})();
