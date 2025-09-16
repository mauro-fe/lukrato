
// ====== BASE_URL a partir do <meta name="base-url"> ======
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
        const r = await fetch(url, { credentials: 'include', ...opts });
        let payload = null; try { payload = await r.json(); } catch { }
        if (!r.ok || (payload && (payload.error || payload.status === 'error'))) {
            throw new Error(payload?.message || payload?.error || 'Erro na requisição');
        }
        return payload;
    }
    const apiOptions = () => fetchJSON(BASE + 'api/options');
    const apiCreate = (payload) => fetchJSON(BASE + 'api/transactions', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    });

    // --- API: contas ativas ---
    const apiAccounts = () => fetchJSON(BASE + 'api/accounts?only_active=1');

    // --- Preencher o seletor de conta do header/modal (opcional) ---
    async function initHeaderAccountPicker() {
        const sel = document.getElementById('headerConta');
        if (!sel) return;

        try {
            const contas = await apiAccounts(); // [{id, nome, instituicao, ...}]
            const keep = sel.value; // tenta preservar seleção atual

            sel.innerHTML = '<option value="">Todas as contas (opcional)</option>';
            contas.forEach(c => {
                const op = document.createElement('option');
                op.value = c.id;
                op.textContent = c.instituicao ? `${c.nome} — ${c.instituicao}` : c.nome;
                sel.appendChild(op);
            });

            // restaura: prioridade = sessionStorage > seleção anterior > vazio
            const saved = sessionStorage.getItem('lukrato.account_id') || keep || '';
            if (saved) sel.value = saved;

            sel.onchange = () => {
                const v = sel.value;
                if (v) sessionStorage.setItem('lukrato.account_id', v);
                else sessionStorage.removeItem('lukrato.account_id');

                // avisa quem quiser reagir (dashboard/relatórios/etc.)
                document.dispatchEvent(new CustomEvent('lukrato:account-changed', {
                    detail: { account_id: v ? Number(v) : null }
                }));
            };

            // expõe helper (se quiser usar em outros scripts)
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


    // ---------- cache de opções ----------
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

    // ---------- Modal open/close ----------
    const modal = $('#modalLancamento');
    const modalBackdropClickOrClose = (e) =>
        e.target.closest('.lkh-modal-close,[data-dismiss="modal"]') ||
        e.target.classList.contains('lkh-modal-backdrop');

    function toggleModal(open) {
        if (!modal) return;
        modal.classList.toggle('active', !!open);
        modal.setAttribute('aria-hidden', String(!open));
        modal.style.display = open ? 'block' : '';
        document.body.style.overflow = open ? 'hidden' : '';

        if (open) {
            initHeaderAccountPicker(); // <-- garante que o <select> de conta seja preenchido/atualizado
            const today = new Date().toISOString().slice(0, 10);
            $('#lanData').value = today;
            $('#lanValor').value = '';
            $('#lanDescricao').value = '';
            $('#lanObservacao') && ($('#lanObservacao').value = '');
            $('#lanPago') && ($('#lanPago').checked = false);
            initModalFieldsOnce();
            (modal.querySelector('input,select,textarea') || {}).focus?.();
        }
    }


    // abre pelo menu do FAB
    $$('.fab-menu-item[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.getAttribute('data-open-modal');
            if (type === 'receita' || type === 'despesa') $('#lanTipo').value = type;
            else $('#lanTipo').value = 'despesa';
            toggleModal(true);
            initHeaderAccountPicker();
            menu.classList.remove('active');
            fab.classList.remove('active');
            fab.setAttribute('aria-expanded', 'false');
        });
    });

    // fechar modal (botão X, backdrop)
    document.addEventListener('click', (e) => {
        if (modalBackdropClickOrClose(e)) {
            e.preventDefault();
            toggleModal(false);
        }
    });
    // ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal?.classList.contains('active')) toggleModal(false);
    });

    // ---------- máscara leve de dinheiro ----------
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
        if (!modal) return;
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
    const form = $('#formLancamento');
    if (form && !form.dataset.bound) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const tipo = $('#lanTipo').value;          // 'receita' | 'despesa'
                const data = $('#lanData').value;
                const valor = parseMoney($('#lanValor').value);
                const catId = $('#lanCategoria').value || null;
                const desc = $('#lanDescricao').value || '';
                const obs = $('#lanObservacao') ? $('#lanObservacao').value : '';

                if (!data || !valor || valor <= 0) {
                    Swal.fire('Atenção', 'Preencha data e valor válidos.', 'warning');
                    return;
                }

                // conta (opcional): usa o seletor do header/modal
                const contaId = document.getElementById('headerConta')?.value || '';

                const payload = {
                    tipo, data, valor,
                    categoria_id: catId ? Number(catId) : null,
                    descricao: desc || null,
                    observacao: obs || null,
                    ...(contaId ? { conta_id: Number(contaId) } : {}) // só envia se tiver
                };

                await apiCreate(payload);

                Swal.fire({ icon: 'success', title: 'Salvo!', timer: 1300, showConfirmButton: false });
                toggleModal(false);

                // reativos
                window.refreshDashboard && window.refreshDashboard();
                window.refreshReports && window.refreshReports();
                window.fetchLancamentos && window.fetchLancamentos();
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao salvar', 'error');
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
            title: 'Deseja realmente sair?', text: 'Sua sessão será encerrada.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, sair',
            cancelButtonText: 'Cancelar', confirmButtonColor: '#e74c3c'
        }).then(r => { if (r.isConfirmed) window.location.href = url; });
    });

    // ---------- Sidebar ativo (apenas texto + ícone em laranja) ----------
    (function initSidebarActive() {
        const links = $$('.sidebar .nav-item[href]');
        const here = location.pathname.replace(/\/+$/, '');
        // Se o PHP já marcou algum ativo, respeita
        let hasActive = !!document.querySelector('.sidebar .nav-item.active,[aria-current="page"]');

        // Marca pelo URL atual se nada veio do PHP
        if (!hasActive) {
            links.forEach(a => {
                if (a.id === 'btn-logout' || a.hasAttribute('data-no-active')) return;
                try {
                    const path = new URL(a.getAttribute('href'), location.origin).pathname.replace(/\/+$/, '');
                    if (path && (path === here || (path !== '/' && here.startsWith(path + '/')))) {
                        a.classList.add('active');
                        a.setAttribute('aria-current', 'page');
                        hasActive = true;
                    }
                } catch { }
            });
        }

        // Mantém visual ativo ao clicar (SPA / antes do reload)
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

