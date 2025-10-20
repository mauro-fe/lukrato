// /assets/js/header.js
// Comportos do HEADER: seletor de conta, logout confirm, highlight de sidebar

export function initHeader(userOpts = {}) {
    const opts = {
        selectors: {
            accountSelect: '#headerConta',              // <select> no topo
            logoutButton: '#btn-logout',               // <a> sair
            sidebarLinks: '.sidebar .nav-item[href]',  // links da sidebar
        },
        api: {
            accounts: 'api/accounts?only_active=1',     // endpoint para listar contas ativas
        },
        storageKey: 'lukrato.account_id',
        baseUrl: getBaseUrl(),
        getCsrf: () => (window.CSRF || ''),
        confirmLogout: defaultConfirmLogout,          // usa SweetAlert2 se disponível
        onAccountChange: (accountId) => {
            // dispara evento global para outros módulos reagirem (dashboard, charts etc.)
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
        bindHeaderAccountPicker(selAccount, opts)
            .catch(err => console.error('[header] contas:', err));
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

    // 3) Highlight da sidebar (ativa visualmente o link atual)
    initSidebarActive(opts.selectors.sidebarLinks);

    // API pública
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
    // evita re-binds desnecessários
    if (selectEl.dataset.bound === '1' && !force) return;

    const keep = selectEl.value; // tenta preservar seleção já renderizada
    const list = await fetchJSON(opts.baseUrl, opts.api.accounts);

    selectEl.innerHTML = '<option value="">Todas as contas (opcional)</option>';
    (Array.isArray(list) ? list : []).forEach(c => {
        const op = document.createElement('option');
        op.value = c.id;
        op.textContent = c.instituicao ? `${c.nome} - ${c.instituicao}` : c.nome;
        selectEl.appendChild(op);
    });

    // restaura seleção (prioridade: sessionStorage > keep do HTML > vazio)
    const saved = sessionStorage.getItem(opts.storageKey) || keep || '';
    if (saved) selectEl.value = saved;

    if (selectEl.dataset.bound !== '1') {
        selectEl.addEventListener('change', () => {
            const v = selectEl.value;
            if (v) sessionStorage.setItem(opts.storageKey, v);
            else sessionStorage.removeItem(opts.storageKey);

            const idNum = v ? Number(v) : null;
            opts.onAccountChange(idNum);
        });
        selectEl.dataset.bound = '1';
    }
}

function initSidebarActive(selector) {
    const links = Array.from(document.querySelectorAll(selector));
    const normalize = (p) => (p || '').replace(/\/+$/, '');
    const here = normalize(location.pathname);

    // limpa ativos prévios
    document.querySelectorAll('.sidebar .nav-item.active,[aria-current="page"]').forEach(link => {
        if (link && !link.hasAttribute('data-no-active') && link.id !== 'btn-logout') {
            link.classList.remove('active');
            link.removeAttribute('aria-current');
        }
    });

    // seta ativo pelo path atual
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

    // mantém ativo no clique (experiência mais “SPA”)
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
    // usa SweetAlert2 se disponível; fallback = confirm()
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

