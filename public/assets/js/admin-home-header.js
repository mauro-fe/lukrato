// /assets/js/header.js
// Comportos do HEADER: seletor de conta, logout confirm, highlight de sidebar

(function (global) {
    function initHeader(userOpts = {}) {
        const opts = {
            selectors: {
                accountSelect: '#headerConta',
                logoutButton: '#btn-logout',
                sidebarLinks: '.sidebar .nav-item[href]',
            },
            api: {
                accounts: 'api/accounts?only_active=1',
            },
            storageKey: 'lukrato.account_id',
            baseUrl: getBaseUrl(),
            getCsrf: () => (window.CSRF || ''),
            confirmLogout: defaultConfirmLogout,
            onAccountChange: (accountId) => {
                document.dispatchEvent(new CustomEvent('lukrato:account-changed', {
                    detail: { account_id: accountId }
                }));
            },
            ...userOpts
        };

        const selAccount = qs(opts.selectors.accountSelect);
        const logoutBtn = qs(opts.selectors.logoutButton);

        // 1) Popular seletor de contas do header (se existir)
        if (selAccount) {
            bindHeaderAccountPicker(selAccount, opts).catch(err => console.error('[header] contas:', err));
        }

        // 2) Logout com confirmação
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const href = logoutBtn.getAttribute('href');
                if (!href) return;
                opts.confirmLogout().then(ok => { if (ok) window.location.href = href; });
            });
        }

        // 3) Highlight da sidebar
        initSidebarActive(opts.selectors.sidebarLinks);
        initSidebarToggle();

        return {
            refreshAccounts: () => selAccount ? bindHeaderAccountPicker(selAccount, opts, { force: true }) : Promise.resolve(),
        };
    }

    /* ---------------- helpers ---------------- */
    function qs(s, sc = document) { return sc.querySelector(s); }

    function getBaseUrl() {
        const m = document.querySelector('meta[name="base-url"]');
        if (m?.content) return m.content.replace(/\/?$/, '/');
        if (window.BASE_URL) return String(window.BASE_URL).replace(/\/?$/, '/');
        return (location.origin + '/').replace(/\/?$/, '/');
    }

    async function fetchJSON(baseUrl, path, init = {}) {
        const url = baseUrl.replace(/\/?$/, '/') + path.replace(/^\/?/, '');
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

    async function bindHeaderAccountPicker(selectEl, opts, { force = false } = {}) {
        if (selectEl.dataset.bound === '1' && !force) return;

        const keep = selectEl.value;
        const list = await fetchJSON(opts.baseUrl, opts.api.accounts);

        selectEl.innerHTML = '<option value="">Todas as contas (opcional)</option>';
        (Array.isArray(list) ? list : []).forEach(c => {
            const op = document.createElement('option');
            op.value = c.id;
            op.textContent = c.instituicao ? `${c.nome} - ${c.instituicao}` : c.nome;
            selectEl.appendChild(op);
        });

        const saved = sessionStorage.getItem(opts.storageKey) || keep || '';
        if (saved) selectEl.value = saved;

        if (selectEl.dataset.bound !== '1') {
            selectEl.addEventListener('change', () => {
                const v = selectEl.value;
                if (v) sessionStorage.setItem(opts.storageKey, v);
                else sessionStorage.removeItem(opts.storageKey);
                opts.onAccountChange(v ? Number(v) : null);
            });
            selectEl.dataset.bound = '1';
        }
    }

    function initSidebarActive(selector) {
        const links = Array.from(document.querySelectorAll(selector));
        const normalize = (p) => (p || '').replace(/\/+$/, '');
        const here = normalize(location.pathname);

        document.querySelectorAll('.sidebar .nav-item.active,[aria-current="page"]').forEach(link => {
            if (link && !link.hasAttribute('data-no-active') && link.id !== 'btn-logout') {
                link.classList.remove('active');
                link.removeAttribute('aria-current');
            }
        });

        let hasActive = false;
        links.forEach(a => {
            if (a.id === 'btn-logout' || a.hasAttribute('data-no-active')) return;
            try {
                const path = normalize(new URL(a.getAttribute('href'), location.origin).pathname);
                if (path && (path === here || (path !== '/' && here.startsWith(path + '/')))) {
                    a.classList.add('active');
                    a.setAttribute('aria-current', 'page');
                    hasActive = true;
                }
            } catch { }
        });

        links.forEach(a => {
            a.addEventListener('click', () => {
                if (a.id === 'btn-logout' || a.hasAttribute('data-no-active')) return;
                links.forEach(x => { x.classList.remove('active'); x.removeAttribute('aria-current'); });
                a.classList.add('active');
                a.setAttribute('aria-current', 'page');
            });
        });
    }

    function defaultConfirmLogout() {
        if (window.Swal?.fire) {
            return window.Swal.fire({
                title: 'Deseja realmente sair?',
                text: 'Sua sessão será encerrada.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, sair',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c'
            }).then(r => Boolean(r.isConfirmed));
        }
        return Promise.resolve(window.confirm('Deseja realmente sair?'));
    }
    function initSidebarToggle() {
        const body = document.body;
        const aside = document.getElementById('sidebar-main');
        const btn = document.getElementById('edgeMenuBtn') || document.getElementById('btn-toggle-sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!aside || !btn || !body) return;

        const KEY = 'lk.sidebar';
        const media = window.matchMedia('(max-width: 992px)');

        const getSavedState = () => {
            const saved = localStorage.getItem(KEY);
            return saved === null ? true : saved === '1';
        };

        const setDesktopState = (collapsed) => {
            body.classList.toggle('sidebar-collapsed', collapsed);
            btn.setAttribute('aria-expanded', String(!collapsed));
            localStorage.setItem(KEY, collapsed ? '1' : '0');
        };

        const openMobile = () => {
            body.classList.add('sidebar-open-mobile');
            btn.setAttribute('aria-expanded', 'true');
        };

        const closeMobile = () => {
            body.classList.remove('sidebar-open-mobile');
            btn.setAttribute('aria-expanded', 'false');
        };

        const handleToggle = () => {
            if (media.matches) {
                if (body.classList.contains('sidebar-open-mobile')) {
                    closeMobile();
                } else {
                    openMobile();
                }
            } else {
                const next = !body.classList.contains('sidebar-collapsed');
                setDesktopState(next);
            }
        };

        btn.addEventListener('click', handleToggle);
        backdrop?.addEventListener('click', closeMobile);
        document.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape' && body.classList.contains('sidebar-open-mobile')) {
                closeMobile();
            }
        });

        const syncState = () => {
            if (media.matches) {
                closeMobile();
            } else {
                setDesktopState(getSavedState());
            }
        };

        syncState();
        const listener = () => syncState();
        if (typeof media.addEventListener === 'function') {
            media.addEventListener('change', listener);
        } else if (typeof media.addListener === 'function') {
            media.addListener(listener);
        }
    }

    // expõe global
    global.LK = global.LK || {};
    global.LK.initHeader = initHeader;
})(window);

// /assets/js/modals.js
(function (global) {
    function initModals() {
        const $ = (s, c = document) => c.querySelector(s);
        const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
        const open = id => { const m = $('#' + id); if (!m) return; m.classList.add('active'); document.body.classList.add('lk-modal-open'); };
        const close = id => { const m = $('#' + id); if (!m) return; m.classList.remove('active'); if (!$('.lk-modal.active')) document.body.classList.remove('lk-modal-open'); };

        $$('[data-open-modal]').forEach(btn => {
            btn.addEventListener('click', () => open(btn.getAttribute('data-open-modal')));
        });
        $$('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => close(btn.getAttribute('data-close-modal')));
        });
        $$('.lk-modal').forEach(m => {
            m.addEventListener('click', e => { if (e.target === m) close(m.id); });
            m.querySelectorAll('[data-action="close"]').forEach(x => x.addEventListener('click', () => close(m.id)));
        });
    }

    global.LK = global.LK || {};
    global.LK.initModals = initModals;
})(window);

(() => {
    const container = document.querySelector('.fab-container');
    const fabBtn = document.getElementById('fabButton');   // o seu <button id="fabButton">
    const menu = document.getElementById('fabMenu');
    if (!container || !fabBtn || !menu) return;

    const bs = window.bootstrap; // precisa do bootstrap.bundle.js p/ Modal

    function openMenu() {
        container.classList.add('open');
        fabBtn.classList.add('active');
        fabBtn.setAttribute('aria-expanded', 'true');
        document.addEventListener('click', handleOutside, { once: true });
        document.addEventListener('keydown', handleEsc, { once: true });
    }

    function closeMenu() {
        container.classList.remove('open');
        fabBtn.classList.remove('active');
        fabBtn.setAttribute('aria-expanded', 'false');
    }

    function toggleMenu(e) {
        e.stopPropagation();
        container.classList.contains('open') ? closeMenu() : openMenu();
    }

    function handleOutside(e) {
        if (!container.contains(e.target)) closeMenu();
    }
    function handleEsc(e) {
        if (e.key === 'Escape') closeMenu();
    }

    fabBtn.addEventListener('click', toggleMenu);

    // abrir modais conforme data-open-modal
    container.querySelectorAll('[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const kind = btn.getAttribute('data-open-modal');
            if (kind === 'receita' || kind === 'despesa') {
                const modalEl = document.querySelector('#modalLancamento');
                const tipoSel = document.querySelector('#lanTipo');
                const title = document.querySelector('#modalLancamentoTitle');
                if (tipoSel) tipoSel.value = kind;
                if (title) title.textContent = `Novo ${kind}`;
                bs?.Modal?.getOrCreateInstance(modalEl)?.show();
            } else if (kind === 'agendamento') {
                const modalEl = document.querySelector('#modalAgendamento');
                bs?.Modal?.getOrCreateInstance(modalEl)?.show();
            }
            closeMenu();
        });
    });

    // garantir que clique não está sendo bloqueado
    fabBtn.style.pointerEvents = 'auto';
    menu.style.pointerEvents = 'auto';

    // se ainda não clicar, force um z-index maior:
    container.style.zIndex = container.style.zIndex || '9999';
})();
