<style>
    .hidden {
        display: none !important;
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
            const tryUrls = [`${base}api/avisos`, `${base}api/notifications`];
            let lastErr = null;
            for (const url of tryUrls) {
                try {
                    const r = await fetch(url, {
                        credentials: 'include',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (await handleFetch403(r)) return [];
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    const j = await r.json();
                    // aceita {status,data:{items:[]}} | {items:[]} | []
                    if (Array.isArray(j)) return j;
                    if (j?.data?.items) return j.data.items;
                    if (j?.items) return j.items;
                    if (j?.data) return j.data;
                    return [];
                } catch (e) {
                    lastErr = e;
                }
            }
            console.warn('Falha ao carregar avisos:', lastErr);
            return [];
        }

        async function markAllRead() {
            const token = getCSRF();
            const body = new FormData();
            if (token) {
                body.append('_token', token);
                body.append('csrf_token', token);
            }

            const tryUrls = [
                `${base}api/avisos/mark-read`, // POST
                `${base}api/avisos/marcar-lidas`,
                `${base}api/notifications/mark-read`
            ];
            for (const url of tryUrls) {
                try {
                    const r = await fetch(url, {
                        method: 'POST',
                        body,
                        credentials: 'include'
                    });
                    if (await handleFetch403(r)) return false;
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    return true;
                } catch (_) {}
            }
            return false;
        }

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