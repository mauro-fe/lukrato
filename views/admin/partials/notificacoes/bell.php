<style>
    .hidden {
        display: none !important;
    }


    /* ===============================
 * NOTIFICACOES
 * =============================== */
    .lk-navbar-notifications {
        position: relative;
    }

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
    }

    #lk-bell:hover {
        background-color: var(--color-bg);
        color: #fff;
        border-color: var(--color-primary);
    }

    #lk-bell:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }

    /* Popover container */
    .lk-popover {
        position: absolute;
        top: calc(100% + 10px);
        /* distancia abaixo do sino */
        right: 0;
        width: 300px;
        z-index: 2000;
        background: var(--color-surface);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        color: var(--color-text);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
    }

    /* Quando visível (controlado pelo JS) */
    .lk-popover:not(.hidden) {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    /* Card interno */
    .lk-popover-card {
        display: flex;
        flex-direction: column;
        max-height: 360px;
        overflow: hidden;
    }

    /* Cabeçalho */
    .lk-popover-h {
        font-weight: 600;
        color: var(--color-primary);
        padding: 10px 16px;
        border-bottom: 1px solid var(--glass-border);
        font-size: 0.95rem;
    }

    /* Corpo (lista de notificações) */
    .lk-popover-b {
        max-height: 260px;
        overflow-y: auto;
        background: var(--color-surface-muted);
        padding: 0;
    }

    /* Item de notificação */
    .lk-popover-item {
        padding: 10px 14px;
        border-bottom: 1px solid var(--glass-border);
        transition: background 0.2s ease;
    }

    .lk-popover-item:hover {
        background: rgba(255, 255, 255, 0.04);
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
        padding: 6px 12px;
        font-size: 0.9rem;
        cursor: pointer;
        transition: background var(--transition-fast);
    }

    .lk-popover-f .lk-btn:hover {
        background: color-mix(in srgb, var(--color-primary) 90%, black);
    }

    /* Scroll bonito */
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
<button id="lk-bell" class="btn btn-ghost relative" aria-label="Notificacoes">
    <i class="fas fa-bell"></i>
    <span id="lk-bell-badge" class="absolute -top-1 -right-1 rounded-full text-xs px-1.5 py-0.5"
        style="background: var(--color-danger); color: #fff; display: <?= $badgeStyle ?>;">
        <?= $initialUnread ?>
    </span>
</button>

<div id="lk-bell-menu" class="lk-popover hidden" data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="lk-popover-card">
        <div class="lk-popover-h">Avisos</div>
        <div id="lk-bell-list" class="lk-popover-b"></div>
        <div class="lk-popover-f">
            <button id="lk-mark-read" class="lk-btn">Marcar como lidas</button>
        </div>
    </div>
</div>

<script>
    (() => {
        /* ============ helpers base/csrf/403 ============ */
        const base = (window.LK?.getBase?.() ??
            document.querySelector('meta[name="base-url"]')?.content ??
            '/');

        const getCSRF = () =>
            (window.LK?.getCSRF?.() ??
                document.querySelector('meta[name="csrf"]')?.content ??
                document.querySelector('meta[name="csrf-token"]')?.content ??
                document.getElementById('lk-bell-menu')?.dataset?.csrf ??
                '');

        async function handleFetch403(res) {
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

        /* ============ elementos ============ */
        const bellBtn = document.getElementById('lk-bell');
        const menu = document.getElementById('lk-bell-menu');
        const listEl = document.getElementById('lk-bell-list');
        const badge = document.getElementById('lk-bell-badge');
        const markRead = document.getElementById('lk-mark-read');

        if (!bellBtn || !menu || !listEl) return;

        /* ============ estado ============ */
        let loadedOnce = false;

        /* ============ UI ============ */
        function openMenu() {
            menu.classList.remove('hidden');
            bellBtn.setAttribute('aria-expanded', 'true');
            document.addEventListener('click', onOutside, {
                once: true
            });
            document.addEventListener('keydown', onEsc, {
                once: true
            });
        }

        function closeMenu() {
            menu.classList.add('hidden');
            bellBtn.setAttribute('aria-expanded', 'false');
        }

        function onOutside(e) {
            if (!menu.contains(e.target) && e.target !== bellBtn) closeMenu();
        }

        function onEsc(e) {
            if (e.key === 'Escape') closeMenu();
        }
        bellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (menu.classList.contains('hidden')) {
                openMenu();
                if (!loadedOnce) loadList();
            } else closeMenu();
        });

        function setBadge(n) {
            if (!badge) return;
            const v = Number(n || 0);
            badge.style.display = v > 0 ? 'inline-block' : 'none';
            badge.textContent = String(v);
        }

        function renderList(items) {
            if (!Array.isArray(items) || !items.length) {
                listEl.innerHTML = `
        <div class="text-center py-3" style="opacity:.75">
          Nenhum aviso.
        </div>`;
                setBadge(0);
                return;
            }

            // tenta detectar campo de leitura: lida|read|is_read
            const unread = items.filter(it => {
                const r = (it.lida ?? it.read ?? it.is_read ?? false);
                return r === false || r === 0 || r === '0';
            }).length;
            setBadge(unread);

            listEl.innerHTML = items.map((it) => {
                const title = it.titulo || it.title || 'Aviso';
                const body = it.mensagem || it.body || it.descricao || '';
                const time = it.data || it.created_at || it.timestamp || '';
                const isRead = (it.lida ?? it.read ?? it.is_read ?? false) ? true : false;
                return `
        <div class="lk-popover-item" style="padding:.5rem .75rem; border-bottom:1px solid var(--glass-border)">
          <div style="display:flex; align-items:center; gap:.5rem">
            <span class="badge" style="background:${isRead?'#64748b':'var(--color-primary)'};color:#fff; font-size:.7rem">${isRead?'Lida':'Novo'}</span>
            <strong>${escapeHtml(title)}</strong>
          </div>
          <div style="font-size:.9rem; opacity:.9; margin-top:.2rem">${escapeHtml(body)}</div>
          ${time ? `<div style="font-size:.75rem; opacity:.6; margin-top:.2rem">${escapeHtml(time)}</div>` : ''}
        </div>`;
            }).join('');
        }

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [m]));
        }

        /* ============ API ============ */

        // Tolerante a /api/avisos e /api/notifications
        async function fetchList() {
            const url = `${base}api/notificacoes`;
            const r = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (await handleFetch403(r)) return [];

            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const j = await r.json();

            // formato do seu Response::success
            // { status: 'success', data: { itens: [...], unread: N } }
            const items = j?.data?.itens ?? j?.itens ?? [];
            const unread = j?.data?.unread ?? j?.unread ?? 0;
            setBadge(unread);
            return Array.isArray(items) ? items : [];
        }


        async function markAllRead() {
            const token = getCSRF();
            const body = new FormData();
            if (token) {
                body.append('_token', token);
                body.append('csrf_token', token);
            }

            const r = await fetch(`${base}api/notificacoes/marcar-todas`, {
                method: 'POST',
                body,
                credentials: 'include'
            });
            if (await handleFetch403(r)) return false;
            return r.ok;
        }

        async function refreshUnread() {
            try {
                const r = await fetch(`${base}api/notificacoes/unread`, {
                    credentials: 'include'
                });
                if (await handleFetch403(r)) return;
                const j = await r.json();
                setBadge(j?.data?.unread ?? j?.unread ?? 0);
            } catch {}
        }
        // Ex.: atualiza a cada 60s
        setInterval(refreshUnread, 60000);



        async function loadList() {
            listEl.innerHTML = '<div class="py-3 text-center">Carregando…</div>';
            try {
                const items = await fetchList();
                renderList(items);
                loadedOnce = true;
            } catch (e) {
                console.error(e);
                listEl.innerHTML = '<div class="py-3 text-center">Falha ao carregar avisos.</div>';
            }
        }

        markRead?.addEventListener('click', async () => {
            markRead.disabled = true;
            try {
                const ok = await markAllRead();
                if (ok) {
                    setBadge(0);
                    await loadList();
                    window.Swal?.fire?.('Pronto', 'Avisos marcados como lidos.', 'success');
                } else {
                    throw new Error('Falha ao marcar como lidas.');
                }
            } catch (e) {
                console.error(e);
                window.Swal?.fire?.('Erro', e.message || 'Falha ao marcar como lidas.', 'error');
            } finally {
                markRead.disabled = false;
            }
        });

        // opcional: recarregar quando o popover abrir de novo
        menu.addEventListener('transitionend', () => {
            if (!menu.classList.contains('hidden')) loadList();
        });

        // também expõe helpers globais (se quiser recarregar de outro lugar)
        window.lkNotify = {
            refresh: loadList,
            setUnread: setBadge
        };
    })();
</script>