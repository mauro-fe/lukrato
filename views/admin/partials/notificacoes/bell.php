<style>
    .hidden {
        display: none !important;
    }

    /* ===============================
 * MODERN NOTIFICATIONS
 * =============================== */
    .lk-navbar-notifications {
        position: static;
    }

    /* Botão do Sino Modernizado */
    #lk-bell {
        position: relative;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(230, 126, 34, 0.1), rgba(230, 126, 34, 0.05));
        border: 1px solid rgba(230, 126, 34, 0.2);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    #lk-bell:hover {
        background: linear-gradient(135deg, rgba(230, 126, 34, 0.15), rgba(230, 126, 34, 0.08));
        border-color: rgba(230, 126, 34, 0.3);
        transform: scale(1.05);
    }

    #lk-bell:active {
        transform: scale(0.95);
    }

    #lk-bell i {
        font-size: 18px;
    }

    /* Badge de Contagem Modernizado */
    #lk-bell-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        font-size: 11px;
        font-weight: 700;
        border-radius: 10px;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(231, 76, 60, 0.4);
        border: 2px solid var(--color-surface);
    }

    /* Animação do sino quando há não lidas */
    .lk-bell-alert i {
        animation: lk-bell-shake 1s ease-in-out infinite;
        transform-origin: top center;
    }

    @keyframes lk-bell-shake {

        0%,
        100% {
            transform: rotate(0deg);
        }

        15% {
            transform: rotate(-15deg);
        }

        30% {
            transform: rotate(12deg);
        }

        45% {
            transform: rotate(-10deg);
        }

        60% {
            transform: rotate(8deg);
        }

        75% {
            transform: rotate(-4deg);
        }
    }

    /* Popover Modernizado */
    .lk-popover {
        position: fixed;
        top: 80px;
        right: 16px;
        width: 380px;
        max-width: 90vw;
        z-index: 99999;
        background: var(--color-surface);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .lk-popover.visible {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    /* Card interno */
    .lk-popover-card {
        display: flex;
        flex-direction: column;
        max-height: 480px;
        overflow: hidden;
    }

    /* Cabeçalho Modernizado */
    .lk-popover-h {
        font-weight: 700;
        font-size: 16px;
        color: var(--color-text);
        padding: 20px 20px 16px;
        border-bottom: 1px solid var(--glass-border);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .lk-popover-h::before {
        content: '';
        width: 4px;
        height: 20px;
        background: linear-gradient(180deg, var(--color-primary), var(--color-secondary));
        border-radius: 4px;
    }

    /* Corpo - lista de notificações */
    .lk-popover-b {
        max-height: 340px;
        overflow-y: auto;
        padding: 8px;
        flex-grow: 1;
    }

    /* Item de notificação Modernizado */
    .lk-popover-item {
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        background: var(--color-surface-muted);
        border: 1px solid transparent;
    }

    .lk-popover-item:last-child {
        margin-bottom: 0;
    }

    .lk-popover-item:hover {
        background: color-mix(in srgb, var(--color-primary) 8%, var(--color-surface-muted));
        border-color: color-mix(in srgb, var(--color-primary) 20%, transparent);
        transform: translateX(-2px);
    }

    /* Notificação não lida */
    .lk-popover-item:not(.is-read) {
        background: color-mix(in srgb, var(--color-primary) 12%, var(--color-surface));
        border-color: color-mix(in srgb, var(--color-primary) 25%, transparent);
    }

    .lk-popover-item:not(.is-read):hover {
        background: color-mix(in srgb, var(--color-primary) 18%, var(--color-surface));
    }

    .lk-popover-item-content {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .lk-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .lk-item-title {
        font-weight: 600;
        font-size: 14px;
        color: var(--color-text);
        flex-grow: 1;
    }

    .lk-item-tag {
        font-size: 10px;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .lk-item-tag-new {
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: white;
    }

    .lk-item-tag-read {
        background: var(--color-text-muted);
        color: white;
        opacity: 0.7;
    }

    .lk-item-body {
        font-size: 13px;
        color: var(--color-text-muted);
        line-height: 1.5;
    }

    .lk-item-time {
        font-size: 11px;
        color: var(--color-text-muted);
        opacity: 0.7;
        text-align: right;
    }

    /* Rodapé com botão */
    .lk-popover-f {
        padding: 12px;
        border-top: 1px solid var(--glass-border);
    }

    .lk-btn {
        padding: 12px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        color: white;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .lk-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.3);
    }

    .lk-btn:active:not(:disabled) {
        transform: scale(0.98);
    }

    .lk-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Scrollbar Modernizada */
    .lk-popover-b::-webkit-scrollbar {
        width: 6px;
    }

    .lk-popover-b::-webkit-scrollbar-track {
        background: transparent;
    }

    .lk-popover-b::-webkit-scrollbar-thumb {
        background: var(--glass-border);
        border-radius: 6px;
    }

    .lk-popover-b::-webkit-scrollbar-thumb:hover {
        background: var(--color-primary);
    }

    /* Mobile */
    @media (max-width: 600px) {
        .lk-popover {
            width: 340px;
        }

        #lk-bell {
            width: 40px;
            height: 40px;
        }

        #lk-bell i {
            font-size: 16px;
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
    <i class="fas fa-bell"></i>
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
        // Evita múltiplas instâncias
        if (window.__lkNotificationManagerInitialized) {
            console.log('NotificationManager já inicializado, ignorando duplicação');
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

                // Se retornar 422, pode ser porque só há alertas dinâmicos (não salvos no banco)
                // Nesses casos, consideramos sucesso pois não há nada para marcar
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
                // Evita chamadas simultâneas
                if (this.isRefreshing) return;

                // Debounce: cancela chamadas rápidas consecutivas
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
                        // Silently fail on network/API errors for polling
                        if (e.message && !e.message.includes('500')) {
                            console.warn('Falha ao atualizar contagem:', e.message);
                        }
                    } finally {
                        this.isRefreshing = false;
                    }
                }, 200);
            }

            startPolling() {
                // Atualiza a contagem a cada 30 segundos (reduzido para diminuir carga)
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