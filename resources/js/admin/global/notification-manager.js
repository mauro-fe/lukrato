import { apiGet, apiPost, getBaseUrl, getErrorMessage } from '../shared/api.js';
import { toastError, toastSuccess } from '../shared/ui.js';
import { escapeHtml } from '../shared/utils.js';

(() => {
    if (window.__lkNotificationManagerInitialized) {
        return;
    }
    window.__lkNotificationManagerInitialized = true;

    function formatNotificationTime(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);

        return date.toLocaleString('pt-BR', {
            dateStyle: 'short',
            timeStyle: 'short'
        });
    }

    async function handleApiError(error, fallbackTitle = 'Erro') {
        if (error?.status === 401) {
            const here = encodeURIComponent(location.pathname + location.search);
            location.href = `${getBaseUrl()}login?return=${here}`;
            return true;
        }

        if (error?.status === 403) {
            toastError(getErrorMessage(error, 'Acesso nao permitido.'));
            return true;
        }

        if (fallbackTitle) {
            toastError(getErrorMessage(error, fallbackTitle));
        }

        return false;
    }

    const NotificationApi = {
        async fetchList() {
            try {
                const response = await apiGet('api/notificacoes');
                const items = response?.data?.itens ?? response?.itens ?? [];
                return Array.isArray(items) ? items : [];
            } catch (error) {
                if (await handleApiError(error, null)) {
                    return [];
                }
                throw error;
            }
        },

        async fetchUnreadCount() {
            try {
                const response = await apiGet('api/notificacoes/unread');
                return Number(response?.data?.unread ?? response?.unread ?? 0);
            } catch (error) {
                if (await handleApiError(error, null)) {
                    return 0;
                }
                throw error;
            }
        },

        async markAllRead() {
            try {
                await apiPost('api/notificacoes/marcar-todas', {});
                return true;
            } catch (error) {
                if (error?.status === 422) {
                    return true;
                }

                if (await handleApiError(error, null)) {
                    return false;
                }

                throw error;
            }
        }
    };

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

            if (!this.bellBtn || !this.menu || !this.listEl) {
                return;
            }

            const initialText = (this.badge?.textContent ?? '').trim();
            this.initialUnread = Number.parseInt(initialText, 10) || 0;
            this.unreadCount = this.initialUnread;

            this.refreshUnread = this.refreshUnread.bind(this);
            this.loadList = this.loadList.bind(this);

            this.initEvents();
            this.startPolling();
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
            this.menu.addEventListener('transitionend', (event) => {
                if (event.target !== this.menu || event.propertyName !== 'opacity') return;
                if (this.menu.classList.contains('visible')) {
                    this.loadList();
                }
            });
        }

        toggleMenu(event) {
            event?.stopPropagation();
            this.isMenuOpen ? this.closeMenu() : this.openMenu();
        }

        openMenu() {
            this.isMenuOpen = true;
            this.menu.classList.add('visible');
            this.menu.classList.remove('hidden');
            this.bellBtn.setAttribute('aria-expanded', 'true');

            if (!this.loadedOnce) {
                this.loadList();
            }
        }

        closeMenu() {
            this.isMenuOpen = false;
            this.menu.classList.remove('visible');
            this.menu.classList.add('hidden');
            this.bellBtn.setAttribute('aria-expanded', 'false');
        }

        handleOutsideClick(event) {
            if (this.isMenuOpen && !this.menu.contains(event.target) && event.target !== this.bellBtn) {
                this.closeMenu();
            }
        }

        handleEsc(event) {
            if (event.key === 'Escape' && this.isMenuOpen) {
                this.closeMenu();
            }
        }

        setBadge(value) {
            if (!this.badge) return;

            const unread = Number(value || 0);
            this.unreadCount = unread;
            const label = unread > 99 ? '99+' : String(unread);

            if (unread > 0) {
                this.badge.classList.remove('hidden');
                this.badge.textContent = label;
            } else {
                this.badge.classList.add('hidden');
            }

            if (this.bellBtn) {
                this.bellBtn.classList.toggle('lk-bell-alert', unread > 0);
            }

            if (this.markReadBtn) {
                this.markReadBtn.disabled = unread === 0;
            }
        }

        renderList(items) {
            if (!Array.isArray(items) || items.length === 0) {
                this.listEl.innerHTML = '<div class="py-3 text-center" style="opacity:.75">Nenhum aviso.</div>';
                this.setBadge(0);
                return;
            }

            const unread = items.filter((item) => {
                const read = item.lida ?? item.read ?? item.is_read ?? false;
                return read === false || read === 0 || read === '0';
            }).length;

            this.setBadge(unread);

            this.listEl.innerHTML = items.map((item) => {
                const title = item.titulo || item.title || 'Aviso';
                const body = item.mensagem || item.body || item.descricao || '';
                const timeRaw = item.data || item.created_at || item.timestamp || '';
                const time = formatNotificationTime(timeRaw);
                const isRead = Boolean(item.lida ?? item.read ?? item.is_read ?? false);
                const readClass = isRead ? 'is-read' : '';
                const tagClass = isRead ? 'lk-item-tag-read' : 'lk-item-tag-new';
                const tagText = isRead ? 'Lida' : 'Novo';

                return `
                    <div class="lk-popover-item ${readClass}" data-id="${item.id || ''}">
                        <div class="lk-popover-item-content">
                            <div class="lk-item-header">
                                <strong class="lk-item-title">${escapeHtml(title)}</strong>
                                <span class="lk-item-tag ${tagClass}">${tagText}</span>
                            </div>
                            <div class="lk-item-body">${escapeHtml(body)}</div>
                            ${time ? `<div class="lk-item-time">${escapeHtml(time)}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        async loadList() {
            this.listEl.innerHTML = '<div class="py-3 text-center">Carregando...</div>';
            if (this.markReadBtn) {
                this.markReadBtn.disabled = true;
            }

            try {
                const items = await NotificationApi.fetchList();
                this.renderList(items);
                this.loadedOnce = true;
            } catch (error) {
                console.error('Erro ao carregar lista de avisos:', error);
                this.listEl.innerHTML = '<div class="py-3 text-center text-danger">Falha ao carregar avisos.</div>';
                this.setBadge(this.unreadCount);
            }
        }

        async refreshUnread() {
            if (this.isRefreshing) {
                return;
            }

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
                } catch (error) {
                    if (!(error instanceof TypeError && error.message.includes('NetworkError'))) {
                        console.warn('Falha ao atualizar contagem:', error?.message || error);
                    }
                } finally {
                    this.isRefreshing = false;
                }
            }, 200);
        }

        startPolling() {
            setInterval(() => this.refreshUnread(), 30000);
        }

        async handleMarkAllRead() {
            if (!this.markReadBtn) return;

            this.markReadBtn.disabled = true;
            this.markReadBtn.textContent = 'Aguarde...';

            try {
                const ok = await NotificationApi.markAllRead();
                if (!ok) {
                    throw new Error('Falha ao marcar como lidas.');
                }

                this.setBadge(0);
                await this.loadList();
                toastSuccess('Avisos marcados como lidos.');
            } catch (error) {
                console.error('Erro ao marcar como lidas:', error);
                toastError(error?.message || 'Falha ao marcar como lidas.');
            } finally {
                this.markReadBtn.textContent = 'Marcar como lidas';
                this.markReadBtn.disabled = this.unreadCount === 0;
            }
        }
    }

    const manager = new NotificationManager();

    window.lkNotify = {
        refresh: manager.loadList.bind(manager),
        setUnread: manager.setBadge.bind(manager),
        toggleMenu: manager.toggleMenu.bind(manager)
    };
})();
