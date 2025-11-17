<style>
    /* Variáveis de cor e tamanho (assumindo que já estão definidas globalmente) */
    /* Ex: --color-primary, --color-text, --color-surface, --radius-md, etc. */

    .hidden {
        display: none !important;
    }

    /* ===============================
 * NOTIFICACOES
 * =============================== */
    .lk-navbar-notifications {
        position: relative;
        /* Container que envolve o botão e o popover */
    }

    /* Botão do Sino */
    #lk-bell {
        background-color: var(--color-primary);
        color: var(--color-text);
        border-radius: var(--radius-md);
        padding: 8px 12px;
        font-size: 1rem;
        transition: all var(--transition-fast);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
    }

    #lk-bell:hover {
        background-color: var(--color-bg);
        color: #fff;
        /* Ajuste de cor de hover para um tema escuro/claro */
        /* Considerar usar uma cor de destaque no hover se for tema claro */
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
        line-height: 1;
        padding: 2px 6px;
        border-radius: 9999px;
        /* Círculo perfeito */
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 20px;
        /* Garante que o 1 ou 99 caiba bem */
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
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
        /* Remove visibility da transição para evitar flash */
    }

    /* Quando visível (controlado pelo JS) */
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

    /* Cabeçalho */
    .lk-popover-h {
        font-weight: 600;
        color: var(--color-primary);
        padding: 12px 16px;
        /* Padding ajustado */
        border-bottom: 1px solid var(--glass-border);
        font-size: 1rem;
    }

    /* Corpo (lista de notificações) */
    .lk-popover-b {
        max-height: 300px;
        /* Altura ajustada */
        overflow-y: auto;
        background: var(--color-surface-muted, #f7f7f7);
        padding: 0;
        flex-grow: 1;
        /* Permite que o corpo ocupe o espaço restante */
    }

    /* Item de notificação */
    .lk-popover-item {
        padding: 10px 14px;
        border-bottom: 1px solid var(--glass-border);
        transition: background 0.2s ease;
        line-height: 1.4;
        cursor: pointer;
        /* Indica que é clicável */
    }

    .lk-popover-item:last-child {
        border-bottom: none;
    }

    .lk-popover-item:hover {
        background: var(--color-hover-subtle, rgba(255, 255, 255, 0.08));
    }

    /* Estilo para notificação não lida (melhora o destaque) */
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

    /* Rodapé */
    .lk-popover-f {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
        border-top: 1px solid var(--glass-border);
        background: var(--color-surface);
    }

    /* Botão "Marcar como lidas" */
    .lk-popover-f .lk-btn {
        background: var(--color-primary);
        color: #fff;
        font-weight: 500;
        border: none;
        border-radius: var(--radius-sm);
        padding: 8px 16px;
        /* Padding ajustado para melhor toque/clique */
        font-size: 0.9rem;
        cursor: pointer;
        transition: background var(--transition-fast);
    }

    .lk-popover-f .lk-btn:hover:not(:disabled) {
        background: color-mix(in srgb, var(--color-primary) 90%, black);
    }

    .lk-popover-f .lk-btn:disabled {
        background: var(--color-secondary, #94a3b8);
        cursor: not-allowed;
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
</style>
<?php

/** @var int $initialUnread */
/** @var string $csrf */
$initialUnread = (int)($initialUnread ?? 0);
$badgeStyle = $initialUnread > 0 ? 'inline-block' : 'none';
?>
<button id="lk-bell" class="btn btn-ghost relative" aria-label="Notificacoes" aria-expanded="false">
    <i class="fas fa-bell"></i>
    <span id="lk-bell-badge" class="absolute -top-1 -right-1"
        style="background: var(--color-danger); color: #fff; display: <?= $badgeStyle ?>;">
        <?= $initialUnread ?>
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
                let msg = 'Acesso não permitido.';
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

                this.initialUnread = Number(this.badge?.textContent ?? 0);
                this.unreadCount = this.initialUnread;

                this.initEvents();
                this.startPolling();
            }

            initEvents() {
                this.bellBtn.addEventListener('click', this.toggleMenu.bind(this));
                this.markReadBtn?.addEventListener('click', this.handleMarkAllRead.bind(this));
                document.addEventListener('click', this.handleOutsideClick.bind(this));
                document.addEventListener('keydown', this.handleEsc.bind(this));

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
                this.badge.style.display = v > 0 ? 'flex' : 'none';
                this.badge.textContent = String(v);
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

                // Recalcula o total de não lidas com base nos dados mais recentes
                const unread = items.filter(it => {
                    const r = (it.lida ?? it.read ?? it.is_read ?? false);
                    return r === false || r === 0 || r === '0';
                }).length;

                this.setBadge(unread);

                this.listEl.innerHTML = items.map((it) => {
                    const title = it.titulo || it.title || 'Aviso';
                    const body = it.mensagem || it.body || it.descricao || '';
                    const time = it.data || it.created_at || it.timestamp || '';
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
                this.listEl.innerHTML = '<div class="py-3 text-center">Carregando…</div>';
                this.markReadBtn.disabled = true;

                try {
                    const items = await NotificationApi.fetchList();
                    this.renderList(items);
                    this.loadedOnce = true;
                } catch (e) {
                    console.error('Erro ao carregar lista de avisos:', e);
                    this.listEl.innerHTML =
                        '<div class="py-3 text-center text-danger">Falha ao carregar avisos.</div>';
                    this.setBadge(this.unreadCount); // mantém a contagem atual se falhar
                }
            }

            async refreshUnread() {
                try {
                    const count = await NotificationApi.fetchUnreadCount();
                    this.setBadge(count);
                } catch (e) {
                    // Silently fail on network/API errors for polling
                    console.warn('Falha ao atualizar contagem de não lidos.', e.message);
                }
            }

            startPolling() {
                // Atualiza a contagem a cada 60s
                setInterval(this.refreshUnread.bind(this), 60000);
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

        // Expõe helpers globais para interação externa
        window.lkNotify = {
            refresh: manager.loadList.bind(manager),
            setUnread: manager.setBadge.bind(manager),
            toggleMenu: manager.toggleMenu.bind(manager)
        };
    })();
</script>
