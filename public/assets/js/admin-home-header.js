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
        // força exibição como "block" para não herdar displays externos
        modal.style.display = open ? 'block' : '';
        document.body.style.overflow = open ? 'hidden' : '';

        if (open) {
            // limpa campos básicos
            const today = new Date().toISOString().slice(0, 10);
            $('#lanData').value = today;
            $('#lanValor').value = '';
            $('#lanDescricao').value = '';
            $('#lanObservacao') && ($('#lanObservacao').value = '');
            $('#lanPago') && ($('#lanPago').checked = false);
            // inicializa opções para esta abertura (sem repopular contas se já tiverem sido preenchidas)
            initModalFieldsOnce();
            // foca primeiro campo
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
            // fecha menu do FAB
            menu.classList.remove('active'); fab.classList.remove('active'); fab.setAttribute('aria-expanded', 'false');
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
        const contaSel = $('#lanConta');

        const opts = await ensureOptions();

        // CONTAS: só popula se ainda não tiver sido preenchido (<= 1 = só o placeholder)
        if ((contaSel?.options?.length || 0) <= 1) {
            fillSelect(contaSel, opts?.contas || [], { first: 'Selecione uma conta' });
        }

        function refreshCategorias() {
            const tipo = (tipoSel.value || 'despesa').toLowerCase();
            const list = (tipo === 'receita') ? (opts?.categorias?.receitas || []) : (opts?.categorias?.despesas || []);
            fillSelect(catSel, list, { first: 'Selecione uma categoria' });
            const lbl = $('#lanPagoLabel'); if (lbl) lbl.textContent = (tipo === 'receita') ? 'Foi recebido?' : 'Foi pago?';
        }
        refreshCategorias();

        if (!tipoSel.dataset.bound) {
            tipoSel.addEventListener('change', refreshCategorias);
            tipoSel.dataset.bound = '1';
        }
    }

    // ---------- submit ----------
    const form = $('#formLancamento');
    if (form && !form.dataset.bound) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const tipo = $('#lanTipo').value;           // 'receita' | 'despesa'
                const data = $('#lanData').value;
                const valor = parseMoney($('#lanValor').value);
                const catId = $('#lanCategoria').value || null;
                const conta = $('#lanConta')?.value || null;
                const desc = $('#lanDescricao').value || '';
                const obs = $('#lanObservacao') ? $('#lanObservacao').value : '';

                if (!conta) { Swal.fire('Atenção', 'Selecione uma conta.', 'warning'); return; }
                if (!data || !valor || valor <= 0) { Swal.fire('Atenção', 'Preencha data e valor válidos.', 'warning'); return; }

                await apiCreate({
                    tipo, data, valor,
                    categoria_id: catId ? Number(catId) : null,
                    conta_id: conta ? Number(conta) : null,
                    descricao: desc || null,
                    observacao: obs || null
                });

                Swal.fire({ icon: 'success', title: 'Salvo!', timer: 1300, showConfirmButton: false });
                toggleModal(false);

                // atualiza visões se existirem
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

})();
