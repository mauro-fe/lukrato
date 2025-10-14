
// ====== BASE_URL - partir do <meta name="base-url"> ======
(function () {

    const m = document.querySelector('meta[name="base-url"]');
    let base = (m && m.content) ? m.content : (location.origin + '/');
    if (!/\/$/.test(base)) base += '/';
    window.BASE_URL = base;
})();

// ====== App (FAB, Modal, Options, Submit) ======
(function () {

    const BASE = window.BASE_URL || '/';
    const $ = (s, sc = document) => sc.querySelector(s);
    const $$ = (s, sc = document) => Array.from(sc.querySelectorAll(s));

    // ---------- helpers ----------
    const brl = (n) => Number(n || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    function parseMoney(input) {
        if (input == null) return 0;
        let s = String(input).trim().replace(/[R$\s]/g, '');
        if (s.includes(',')) s = s.replace(/\./g, '').replace(',', '.');
        const n = Number(s);
        return isNaN(n) ? 0 : Number(n.toFixed(2));
    }
    async function fetchJSON(url, opts = {}) {
        const method = (opts.method || 'GET').toUpperCase();
        const headers = Object.assign({}, opts.headers || {});
        const isJson = (headers['Content-Type'] || headers['content-type'] || '').includes('application/json');

        // injeta CSRF para POST/PUT/DELETE com JSON
        if (method !== 'GET' && isJson) {
            const token = LK.getCSRF();
            headers['X-CSRF-TOKEN'] = token;

            // mescla o payload com os campos de CSRF
            let payload = {};
            try { payload = opts.body ? JSON.parse(opts.body) : {}; } catch { payload = {}; }
            opts.body = JSON.stringify({ ...payload, _token: token, csrf_token: token });
        }

        const r = await fetch(url, { credentials: 'include', ...opts, headers });
        let payload = null; try { payload = await r.json(); } catch { }
        if (!r.ok) {
            if (r.status === 403) throw new Error('Sessao expirada ou CSRF invalido. Recarregue a pagina e tente novamente.');
            if (r.status === 422) throw new Error(payload?.message || 'Dados invalidos.');
            throw new Error(payload?.message || payload?.error || `Erro ${r.status}`);
        }
        if (payload && (payload.error || payload.status === 'error')) {
            throw new Error(payload?.message || payload?.error || 'Erro na requisicao');
        }
        return payload;
    }

    const apiOptions = () => fetchJSON(BASE + 'api/options');
    const apiCreate = (payload) => fetchJSON(BASE + 'api/transactions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    // --- API: contas ativas ---
    const apiAccounts = () => fetchJSON(BASE + 'api/accounts?only_active=1');

    // --- Preencher o seletor de conta do header/modal (opcional) ---
    async function initHeaderAccountPicker() {
        const sel = document.getElementById('headerConta');
        if (!sel) return;

        try {
            const contas = await apiAccounts(); // [{id, nome, instituicao, ...}]
            const keep = sel.value; // tenta preservar selecao atual

            sel.innerHTML = '<option value="">Todas as contas (opcional)</option>';
            contas.forEach(c => {
                const op = document.createElement('option');
                op.value = c.id;
                op.textContent = c.instituicao ? `${c.nome} - ${c.instituicao}` : c.nome;
                sel.appendChild(op);
            });

            // restaura: prioridade = sessionStorage > selecao anterior > vazio
            const saved = sessionStorage.getItem('lukrato.account_id') || keep || '';
            if (saved) sel.value = saved;

            sel.onchange = () => {
                const v = sel.value;
                if (v) sessionStorage.setItem('lukrato.account_id', v);
                else sessionStorage.removeItem('lukrato.account_id');

                // avisa quem quiser reagir (dashboard/relatArios/etc.)
                document.dispatchEvent(new CustomEvent('lukrato:account-changed', {
                    detail: { account_id: v ? Number(v) : null }
                }));
            };

            // expAe helper (se quiser usar em outros scripts)
            window.LukratoHeader = Object.assign({}, window.LukratoHeader, {
                getAccountId: () => {
                    const v = document.getElementById('headerConta')?.value || '';
                    return v ? Number(v) : null;
                }
            });
        } catch (e) {
            console.error('headerConta:', e);
        }
    }


    // ---------- cache de opcoes ----------
    let optionsCache = null;
    async function ensureOptions() {
        if (optionsCache) return optionsCache;
        optionsCache = await apiOptions();
        return optionsCache;
    }

    // ---------- FAB open/close ----------
    const fab = $('#fabButton');
    const menu = $('#fabMenu');
    fab?.addEventListener('click', () => {
        const open = !menu.classList.contains('active');
        fab.classList.toggle('active', open);
        fab.setAttribute('aria-expanded', String(open));
        menu.classList.toggle('active', open);
    });

    // ---------- Modal de novo lancamento ----------
    const modalLancamentoEl = document.getElementById('modalLancamento');
    let modalLancamentoInstance = null;

    const ensureLancamentoModal = () => {
        if (!modalLancamentoEl || !window.bootstrap?.Modal) return null;
        if (modalLancamentoEl.parentElement && modalLancamentoEl.parentElement !== document.body) {
            document.body.appendChild(modalLancamentoEl);
        }
        modalLancamentoInstance = window.bootstrap.Modal.getOrCreateInstance(modalLancamentoEl);
        return modalLancamentoInstance;
    };

    const resetLancamentoForm = () => {
        const today = new Date().toISOString().slice(0, 10);
        const dataInput = $('#lanData');
        if (dataInput) dataInput.value = today;
        const tipoSelect = $('#lanTipo');
        if (tipoSelect && !tipoSelect.value) tipoSelect.value = 'despesa';
        $('#lanValor') && ($('#lanValor').value = '');
        $('#lanDescricao') && ($('#lanDescricao').value = '');
        $('#lanObservacao') && ($('#lanObservacao').value = '');
        $('#lanPago') && ($('#lanPago').checked = false);
        const alertBox = document.getElementById('novoLancAlert');
        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }
    };

    modalLancamentoEl?.addEventListener('show.bs.modal', () => {
        initHeaderAccountPicker();
        initModalFieldsOnce();
        resetLancamentoForm();
    });

    modalLancamentoEl?.addEventListener('shown.bs.modal', () => {
        const firstField = modalLancamentoEl.querySelector('input, select, textarea');
        firstField?.focus?.();
    });

    modalLancamentoEl?.addEventListener('hidden.bs.modal', () => {
        const form = document.getElementById('formNovoLancamento');
        form?.reset?.();
        $('#lanValor') && ($('#lanValor').value = '');
        menu?.classList.remove('active');
        fab?.classList.remove('active');
        fab?.setAttribute('aria-expanded', 'false');
    });

    ensureLancamentoModal();

    // abre pelo menu do FAB
    $$('.fab-menu-item[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.getAttribute('data-open-modal');
            if (type === 'receita' || type === 'despesa') $('#lanTipo').value = type;
            else $('#lanTipo').value = 'despesa';
            ensureLancamentoModal()?.show();
            menu?.classList.remove('active');
            fab?.classList.remove('active');
            fab?.setAttribute('aria-expanded', 'false');
        });
    });
    // ---------- mascara leve de dinheiro ----------
    $('#lanValor')?.addEventListener('blur', () => {
        $('#lanValor').value = brl(parseMoney($('#lanValor').value));
    });
    $('#lanValor')?.addEventListener('focus', () => {
        const v = String(parseMoney($('#lanValor').value)).replace('.', ',');
        $('#lanValor').value = v === '0' ? '' : v;
    });

    // ---------- preencher selects ----------
    function fillSelect(sel, items, { value = 'id', label = 'nome', first = 'Selecione...' } = {}) {
        sel.innerHTML = '';
        if (first !== null) {
            const op = document.createElement('option');
            op.value = ''; op.textContent = first; sel.appendChild(op);
        }
        (items || []).forEach(it => {
            const op = document.createElement('option');
            op.value = it[value]; op.textContent = it[label];
            sel.appendChild(op);
        });
    }

    async function initModalFieldsOnce() {
        if (!modalLancamentoEl) return;
        const tipoSel = $('#lanTipo');
        const catSel = $('#lanCategoria');

        const opts = await ensureOptions();

        function refreshCategorias() {
            const tipo = (tipoSel.value || 'despesa').toLowerCase();
            const list = (tipo === 'receita') ? (opts?.categorias?.receitas || []) : (opts?.categorias?.despesas || []);
            fillSelect(catSel, list, { first: 'Selecione uma categoria' });
            const lbl = $('#lanPagoLabel');
            if (lbl) lbl.textContent = (tipo === 'receita') ? 'Foi recebido?' : 'Foi pago?';
        }
        refreshCategorias();

        if (!tipoSel.dataset.bound) {
            tipoSel.addEventListener('change', refreshCategorias);
            tipoSel.dataset.bound = '1';
        }
    }

    // ---------- submit ----------
    // ---------- submit ----------
    const form = document.getElementById('formNovoLancamento');
    if (form && !form.dataset.bound) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (form.dataset.submitting === '1') return;   // <-- guard
            form.dataset.submitting = '1';
            const btnSubmit = document.querySelector('button[form="formNovoLancamento"][type="submit"]');
            btnSubmit && (btnSubmit.disabled = true);

            try {
                const tipo = $('#lanTipo').value;
                const data = $('#lanData').value;
                const valor = parseMoney($('#lanValor').value);
                const catId = $('#lanCategoria').value || null;
                const desc = $('#lanDescricao').value || '';
                const obs = $('#lanObservacao') ? $('#lanObservacao').value : '';

                if (!data || !valor || valor <= 0) {
                    Swal.fire('Atencao', 'Preencha data e valor validos.', 'warning');
                    return;
                }

                const contaId = document.getElementById('headerConta')?.value || '';
                const payload = {
                    tipo, data, valor,
                    categoria_id: catId ? Number(catId) : null,
                    descricao: desc || null,
                    observacao: obs || null,
                    ...(contaId ? { conta_id: Number(contaId) } : {})
                };

                await apiCreate(payload);

                Swal.fire({ icon: 'success', title: 'Salvo!', timer: 1300, showConfirmButton: false });
                ensureLancamentoModal()?.hide();

                // avisa sA por evento (evita chamar 2x)
                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: { resource: 'transactions' }
                }));
            } catch (err) {
                console.error(err);
                const alertBox = document.getElementById('novoLancAlert');
                if (alertBox) {
                    alertBox.textContent = err.message || 'Falha ao salvar';
                    alertBox.classList.remove('d-none');
                } else {
                    Swal.fire('Erro', err.message || 'Falha ao salvar', 'error');
                }
            } finally {
                form.dataset.submitting = '0';
                btnSubmit && (btnSubmit.disabled = false);
            }
        });
        form.dataset.bound = '1';
    }



    // ---------- Logout confirm ----------
    document.addEventListener('click', (e) => {
        const a = e.target.closest('#btn-logout'); if (!a) return;
        e.preventDefault();
        const url = a.getAttribute('href'); if (!url) return;
        Swal.fire({
            title: 'Deseja realmente sair?', text: 'Sua sessao sera encerrada.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, sair',
            cancelButtonText: 'Cancelar', confirmButtonColor: '#e74c3c'
        }).then(r => { if (r.isConfirmed) window.location.href = url; });
    });

    // ---------- Sidebar ativo (apenas texto + icone em laranja) ----------
    (function initSidebarActive() {
        const links = $$('.sidebar .nav-item[href]');
        const normalize = (p) => (p || '').replace(/\/+$/, '');
        const here = normalize(location.pathname);

        let hasActive = false;
        const currentActives = Array.from(document.querySelectorAll('.sidebar .nav-item.active,[aria-current="page"]'));

        currentActives.forEach(link => {
            if (!link || link.id === 'btn-logout' || link.hasAttribute('data-no-active')) return;
            try {
                const path = normalize(new URL(link.getAttribute('href'), location.origin).pathname);
                if (path && (path === here || (path !== '/' && here.startsWith(path + '/')))) {
                    hasActive = true;
                    return;
                }
            } catch { }
            link.classList.remove('active');
            link.removeAttribute('aria-current');
        });

        if (!hasActive) {
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
        }

        // Mantem visual ativo ao clicar (SPA / antes do reload)
        links.forEach(a => {
            a.addEventListener('click', () => {
                if (a.id === 'btn-logout' || a.hasAttribute('data-no-active')) return;
                links.forEach(x => { x.classList.remove('active'); x.removeAttribute('aria-current'); });
                a.classList.add('active');
                a.setAttribute('aria-current', 'page');
            });
        });
    })();
    document.addEventListener('DOMContentLoaded', () => {
        initHeaderAccountPicker();
    });

})();

