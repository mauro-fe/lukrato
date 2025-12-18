<style>
/* Vari├íveis de cor e tamanho (assumindo que j├í est├úo definidas globalmente) */
/* Ex: --color-primary, --color-text, --color-surface, --radius-md, etc. */

.hidden {
    display: none !important;
}

/* ===============================
 * NOTIFICACOES
 * =============================== */
.lk-navbar-notifications {
    position: relative;
    /* Container que envolve o bot├úo e o popover */
}

/* Botão do Sino */
#lk-bell {
    background-color: transparent;
    color: #fff !important;
    border-radius: var(--radius-md);
    padding: 8px 14px;
    font-size: 0.9rem;
    transition: all var(--transition-fast);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
    cursor: pointer;
    height: 40px;
}

#lk-bell:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

#lk-bell i,
#lk-bell .fas,
#lk-bell .fa-bell {
    color: #fff !important;
}

/* Sobrescrever btn-ghost para o sino */
#lk-bell.btn-ghost,
#lk-bell.btn-ghost:not(:hover),
#lk-bell.btn {
    color: #fff !important;
}

/* Vibracao do sino quando ha nao lidas */
.lk-bell-alert i {
    animation: lk-bell-shake 1s ease-in-out infinite;
    transform-origin: top center;
}

@keyframes lk-bell-shake {
    0% {
        transform: rotate(0deg);
    }

    15% {
        transform: rotate(-10deg);
    }

    30% {
        transform: rotate(8deg);
    }

    45% {
        transform: rotate(-6deg);
    }

    60% {
        transform: rotate(4deg);
    }

    75% {
        transform: rotate(-2deg);
    }

    100% {
        transform: rotate(0deg);
    }
}


#lk-bell:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

/* Badge de Contagem */
#lk-bell-badge {
    background: var(--color-danger);
    color: #fff;
    font-size: 0.75rem;
    /* Tamanho levemente maior */
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    line-height: 1;
    border-radius: 9999px;
    /* Círculo perfeito */
    text-align: center;
    display: none;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

/* Popover Container */
.lk-popover {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 320px;
    /* Levemente mais largo para melhor leitura */
    max-width: 90vw;
    z-index: 2000;
    background: var(--color-surface);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    color: var(--color-text);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: opacity 0.2s ease, transform 0.2s ease;
    /* Remove visibility da transi├º├úo para evitar flash */
}

/* Quando vis├¡vel (controlado pelo JS) */
.lk-popover.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Card interno */
.lk-popover-card {
    display: flex;
    flex-direction: column;
    max-height: 400px;
    /* Aumentado um pouco para mostrar mais itens */
    overflow: hidden;
}

/* Cabe├ºalho */
.lk-popover-h {
    font-weight: 600;
    color: var(--color-primary);
    padding: 12px 16px;
    /* Padding ajustado */
    border-bottom: 1px solid var(--glass-border);
    font-size: 1rem;
}

/* Corpo (lista de notifica├º├Áes) */
.lk-popover-b {
    max-height: 300px;
    /* Altura ajustada */
    overflow-y: auto;
    background: var(--color-surface-muted, #f7f7f7);
    padding: 0;
    flex-grow: 1;
    /* Permite que o corpo ocupe o espa├ºo restante */
}

/* Item de notifica├º├úo */
.lk-popover-item {
    padding: 10px 14px;
    border-bottom: 1px solid var(--glass-border);
    transition: background 0.2s ease;
    line-height: 1.4;
    cursor: pointer;
    /* Indica que ├® clic├ível */
}

.lk-popover-item:last-child {
    border-bottom: none;
}

.lk-popover-item:hover {
    background: var(--color-hover-subtle, rgba(255, 255, 255, 0.08));
}

/* Estilo para notifica├º├úo n├úo lida (melhora o destaque) */
.lk-popover-item:not(.is-read) {
    background: var(--color-unread-bg, rgba(var(--color-primary-rgb), 0.05));
}

.lk-popover-item:not(.is-read):hover {
    background: var(--color-hover-unread, rgba(var(--color-primary-rgb), 0.1));
}

.lk-popover-item-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.lk-item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.9rem;
}

.lk-item-title {
    font-weight: 600;
    color: var(--color-primary);
    flex-grow: 1;
}

.lk-item-tag {
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-weight: 500;
    margin-left: 10px;
    white-space: nowrap;
}

.lk-item-tag-new {
    background: var(--color-primary);
    color: #fff;
}

.lk-item-tag-read {
    background: var(--color-secondary, #64748b);
    color: #fff;
}

.lk-item-body {
    font-size: 0.85rem;
    opacity: 0.9;
}

.lk-item-time {
    font-size: 0.75rem;
    opacity: 0.6;
    text-align: right;
}

/* Scrollbar */
.lk-popover-b::-webkit-scrollbar {
    width: 6px;
}

.lk-popover-b::-webkit-scrollbar-thumb {
    background: var(--glass-border);
    border-radius: 6px;
}

.lk-popover-b::-webkit-scrollbar-thumb:hover {
    background: var(--color-primary);
}

@media (max-width: 600px) {
    #lk-bell {
        padding: 6px 10px;
        font-size: 0.65rem;
        width: 32px;
        height: 32px;
    }

    #lk-bell i {
        font-size: 0.85rem !important;
    }

    #lk-bell-badge {
        font-size: 0.6rem !important;
        min-width: 16px;
        padding: 2px 4px;
        top: -2px;
        right: -2px;
    }


}


@media (max-width: 300px) {
    #lk-bell {
        padding: 6px 10px;
        font-size: 0.65rem;
        height: 32px;
    }

    #lk-bell i {
        font-size: 0.85rem !important;
    }

    #lk-bell-badge {
        font-size: 0.6rem !important;
        min-width: 16px;
        padding: 2px 4px;
        top: -2px;
        right: -2px;
    }
}
</style>
<?php

/** @var int $initialUnread */
/** @var string $csrf */
$initialUnread = (int)($initialUnread ?? 0);
$badgeStyle = $initialUnread > 0 ? 'inline-flex' : 'none';
$initialBadgeLabel = $initialUnread > 99 ? '99+' : $initialUnread;
?>
<button id="lk-bell" class="btn btn-ghost relative" aria-label="Notificacoes" aria-expanded="false">
    <i class="fas fa-bell" style="color: #fff !important;"></i>
    <span id="lk-bell-badge" class="absolute -top-1 -right-1" style="display: <?= $badgeStyle ?>;">
        <?= $initialBadgeLabel ?>
    </span>
</button>

<div id="lk-bell-menu" class="lk-popover hidden" data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="lk-popover-card">
        <div class="lk-popover-h">Avisos</div>
        <div id="lk-bell-list" class="lk-popover-b">
            <div class="py-3 text-center" style="opacity:.75">Clique no sino para carregar os avisos.</div>
        </div>
        <div class="lk-popover-f">
            <button id="lk-mark-read" class="lk-btn" disabled>Marcar como lidas</button>
        </div>
    </div>
</div>

<script>
(() => {
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
            let msg = 'Acesso n├úo permitido.';
            try {
                const j = await res.clone().json();
                msg = j?.message || msg;
            } catch {}
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
        } [m]));
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
            // Unread count is handled by the main manager
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

            if (!this.bellBtn || !this.menu || !this.listEl) return;

            const initialText = (this.badge?.textContent ?? '').trim();
            this.initialUnread = Number.parseInt(initialText, 10) || 0;
            this.unreadCount = this.initialUnread;

            this.refreshUnread = this.refreshUnread.bind(this);
            this.loadList = this.loadList.bind(this);

            this.initEvents();
            this.startPolling();
            this.refreshUnread();
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

            // Recarrega o popover *apenas* quando for visível, evitando chamada desnecessária
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

            // Garante que a lista seja carregada na primeira abertura
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
            this.badge.style.display = v > 0 ? 'inline-flex' : 'none';
            this.badge.textContent = label;
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

            // Recalcula o total de n├úo lidas com base nos dados mais recentes
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
                this.setBadge(this.unreadCount); // mant├®m a contagem atual se falhar
            }
        }

        async refreshUnread() {
            const previous = Number(this.unreadCount || 0);
            try {
                const count = await NotificationApi.fetchUnreadCount();
                this.setBadge(count);
                if (this.isMenuOpen && count > previous) {
                    this.loadList();
                }
            } catch (e) {
                // Silently fail on network/API errors for polling
                console.warn('Falha ao atualizar contagem de nao lidos.', e.message);
            }
        }

        startPolling() {
            // Atualiza a contagem com intervalo mais curto para parecer instantaneo
            setInterval(this.refreshUnread.bind(this), 15000);
        }

        /* ============ Actions ============ */

        async handleMarkAllRead() {
            this.markReadBtn.disabled = true;
            this.markReadBtn.textContent = 'Aguarde...';

            try {
                const ok = await NotificationApi.markAllRead();
                if (ok) {
                    this.setBadge(0);
                    // Recarrega a lista para refletir o estado de 'lida'
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

    // Exp├Áe helpers globais para intera├º├úo externa
    window.lkNotify = {
        refresh: manager.loadList.bind(manager),
        setUnread: manager.setBadge.bind(manager),
        toggleMenu: manager.toggleMenu.bind(manager)
    };
})();
</script>