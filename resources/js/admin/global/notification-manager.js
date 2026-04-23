import { apiGet, apiPost, getBaseUrl, getErrorMessage } from '../shared/api.js';
import {
    resolveMarkAllNotificationsEndpoint,
    resolveNotificationsListEndpoint,
    resolveUnreadNotificationsEndpoint,
} from '../api/endpoints/notifications.js';
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

    const TYPE_META = {
        alerta: { icon: 'triangle-alert', tone: 'warning', label: 'Alerta' },
        alert: { icon: 'triangle-alert', tone: 'warning', label: 'Alerta' },
        reminder: { icon: 'alarm-clock', tone: 'info', label: 'Lembrete' },
        lembrete: { icon: 'alarm-clock', tone: 'info', label: 'Lembrete' },
        promo: { icon: 'tag', tone: 'promo', label: 'Promo' },
        update: { icon: 'rocket', tone: 'update', label: 'Atualizacao' },
        success: { icon: 'circle-check', tone: 'success', label: 'Sucesso' },
        info: { icon: 'info', tone: 'info', label: 'Aviso' },
        subscription_expired: { icon: 'crown', tone: 'warning', label: 'Plano' },
        subscription_blocked: { icon: 'shield-alert', tone: 'danger', label: 'Plano' },
        referral_referred: { icon: 'gift', tone: 'success', label: 'Indicacao' },
        referral_referrer: { icon: 'gift', tone: 'success', label: 'Indicacao' },
    };

    function normalizeCssColor(value) {
        const color = String(value || '').trim();
        return /^#[0-9a-f]{3}([0-9a-f]{3})?$/i.test(color) ? color : '';
    }

    function getNotificationMeta(item) {
        const rawType = String(item.tipo || item.type || '').trim().toLowerCase();
        const meta = TYPE_META[rawType] || TYPE_META.info;
        const accent = normalizeCssColor(item.cor || item.color);

        return {
            ...meta,
            accent,
            type: rawType || 'info',
        };
    }

    function isNotificationRead(item) {
        const read = item.lida ?? item.read ?? item.is_read ?? false;
        return read === true || read === 1 || read === '1';
    }

    const NotificationApi = {
        async fetchList() {
            try {
                const response = await apiGet(resolveNotificationsListEndpoint());
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
                const response = await apiGet(resolveUnreadNotificationsEndpoint());
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
                await apiPost(resolveMarkAllNotificationsEndpoint(), {});
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
            this.summary = document.getElementById('lk-bell-summary');
            this.markReadBtn = document.getElementById('lk-mark-read');
            this.isMenuOpen = false;
            this.loadedOnce = false;
            this.isRefreshing = false;
            this.isLoadingList = false;
            this.refreshTimeout = null;

            if (!this.bellBtn || !this.menu || !this.listEl) {
                return;
            }

            const initialText = (this.badge?.textContent ?? '').trim();
            this.initialUnread = Number.parseInt(initialText, 10) || 0;
            this.unreadCount = this.initialUnread;
            this.updateSummary();
            this.updateMarkReadButton();

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
        }

        toggleMenu(event) {
            event?.stopPropagation();
            this.isMenuOpen ? this.closeMenu() : this.openMenu();
        }

        openMenu() {
            this.isMenuOpen = true;
            this.menu.classList.remove('hidden');
            requestAnimationFrame(() => {
                this.menu.classList.add('visible');
            });
            this.bellBtn.setAttribute('aria-expanded', 'true');

            if (!this.loadedOnce) {
                this.loadList();
            } else {
                this.refreshUnread();
            }
        }

        closeMenu() {
            this.isMenuOpen = false;
            this.menu.classList.remove('visible');
            this.bellBtn.setAttribute('aria-expanded', 'false');
            window.setTimeout(() => {
                if (!this.isMenuOpen) this.menu.classList.add('hidden');
            }, 220);
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
                this.updateMarkReadButton();
            }

            this.updateSummary();
        }

        updateSummary() {
            if (!this.summary) return;

            const unread = Number(this.unreadCount || 0);
            if (unread > 0) {
                const label = unread > 99 ? '99+' : String(unread);
                this.summary.textContent = `${label} nao lido${unread === 1 ? '' : 's'}`;
                this.summary.classList.add('has-unread');
            } else {
                this.summary.textContent = 'Tudo lido';
                this.summary.classList.remove('has-unread');
            }
        }

        updateMarkReadButton(isLoading = false) {
            if (!this.markReadBtn) return;

            if (isLoading) {
                this.markReadBtn.disabled = true;
                this.markReadBtn.innerHTML = '<i data-lucide="loader-2" class="icon-spin"></i><span>Aguarde...</span>';
                this.refreshIcons();
                return;
            }

            const hasUnread = Number(this.unreadCount || 0) > 0;
            this.markReadBtn.disabled = !hasUnread;
            this.markReadBtn.innerHTML = hasUnread
                ? '<i data-lucide="check-check"></i><span>Marcar como lidas</span>'
                : '<i data-lucide="check-check"></i><span>Tudo lido</span>';
            this.refreshIcons();
        }

        refreshIcons() {
            if (window.lucide?.createIcons) {
                window.lucide.createIcons();
            }
        }

        renderListState(type, message) {
            const icon = type === 'loading' ? 'loader-2' : type === 'error' ? 'triangle-alert' : 'bell';
            const extraClass = type === 'loading' ? ' is-loading' : '';
            this.listEl.innerHTML = `
                <div class="lk-popover-state lk-popover-state--${type}${extraClass}">
                    <span class="lk-popover-state-icon" aria-hidden="true">
                        <i data-lucide="${icon}"${type === 'loading' ? ' class="icon-spin"' : ''}></i>
                    </span>
                    <span>${escapeHtml(message)}</span>
                </div>
            `;
            this.refreshIcons();
        }

        renderList(items) {
            if (!Array.isArray(items) || items.length === 0) {
                this.setBadge(0);
                this.renderListState('empty', 'Nenhum aviso por enquanto.');
                return;
            }

            const unread = items.filter((item) => !isNotificationRead(item)).length;

            this.setBadge(unread);

            this.listEl.innerHTML = items.map((item) => {
                const title = item.titulo || item.title || 'Aviso';
                const body = item.mensagem || item.body || item.descricao || '';
                const timeRaw = item.data || item.created_at || item.timestamp || '';
                const time = formatNotificationTime(timeRaw);
                const isRead = isNotificationRead(item);
                const readClass = isRead ? 'is-read' : '';
                const tagClass = isRead ? 'lk-item-tag-read' : 'lk-item-tag-new';
                const tagText = isRead ? 'Lida' : 'Novo';
                const meta = getNotificationMeta(item);
                const itemId = escapeHtml(item.id || '');
                const accentStyle = meta.accent ? ` style="--lk-item-accent: ${meta.accent}"` : '';

                return `
                    <article class="lk-popover-item lk-popover-item--${meta.tone} ${readClass}" data-id="${itemId}"${accentStyle}>
                        <span class="lk-popover-item-icon" aria-hidden="true">
                            <i data-lucide="${meta.icon}"></i>
                        </span>
                        <div class="lk-popover-item-content">
                            <div class="lk-item-header">
                                <strong class="lk-item-title">${escapeHtml(title)}</strong>
                                <span class="lk-item-tag ${tagClass}">${tagText}</span>
                            </div>
                            ${body ? `<p class="lk-item-body">${escapeHtml(body)}</p>` : ''}
                            <div class="lk-item-meta">
                                <span class="lk-item-type">${escapeHtml(meta.label)}</span>
                                ${time ? `<span class="lk-item-time"><i data-lucide="clock-3"></i>${escapeHtml(time)}</span>` : ''}
                            </div>
                        </div>
                    </article>
                `;
            }).join('');
            this.refreshIcons();
        }

        async loadList() {
            if (this.isLoadingList) return;
            this.isLoadingList = true;
            this.renderListState('loading', 'Carregando avisos...');
            this.updateMarkReadButton(true);

            try {
                const items = await NotificationApi.fetchList();
                this.renderList(items);
                this.loadedOnce = true;
            } catch (error) {
                console.error('Erro ao carregar lista de avisos:', error);
                this.setBadge(this.unreadCount);
                this.renderListState('error', 'Falha ao carregar avisos.');
            } finally {
                this.isLoadingList = false;
                this.updateMarkReadButton();
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

            this.updateMarkReadButton(true);

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
                this.updateMarkReadButton();
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
