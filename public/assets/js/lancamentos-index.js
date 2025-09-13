/* Página: Lançamentos (mês auto via header, botão filtra tipo no cliente) */
(() => {
    const rawBase = (window.BASE_URL || '/').replace(/\/?$/, '/');
    const BASES = [`${rawBase}api/`, `${rawBase}index.php/api/`];

    const $ = (s) => document.querySelector(s);
    const tbody = $('#tbodyLancamentos');
    const selectTipo = $('#filtroTipo');
    const btnFiltrar = $('#btnFiltrar');

    const monthCache = new Map();
    let isLoading = false;

    const fmt = {
        money: (n) =>
            new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
                .format(Number(n || 0)),
        date: (iso) => {
            if (!iso) return '—';
            const m = String(iso).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? `${m[3]}/${m[2]}/${m[1]}` : '—';
        },
    };

    const getMonth = () => {
        if (window.LukratoHeader?.getMonth) {
            const m = window.LukratoHeader.getMonth();
            if (m) return m;
        }
        const el = document.getElementById('currentMonthText');
        const dm = el?.getAttribute?.('data-month');
        return dm || new Date().toISOString().slice(0, 7);
    };

    const setEmpty = (msg) => {
        if (!tbody) return;
        tbody.innerHTML = `<tr><td colspan="7" class="text-center">${msg}</td></tr>`; // 7 colunas (inclui Ações)
    };

    const normalizeList = (p) => {
        if (Array.isArray(p)) return p;
        if (p?.items) return p.items;
        if (p?.data) return p.data;
        if (p?.lancamentos) return p.lancamentos;
        return [];
    };

    const normalizeTipo = (v) => {
        if (!v) return '';
        const s = String(v).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        if (/(receita|entrada|credit)/.test(s)) return 'receita';
        if (/(despesa|saida|debit)/.test(s)) return 'despesa';
        return '';
    };

    function applyTipoFilter(list, tipoSelecionado) {
        if (!tipoSelecionado) return list;
        const alvo = normalizeTipo(tipoSelecionado);
        if (!alvo) return list;

        return list.filter((t) => {
            const candidatos = [t.tipo, t.tipo_transacao, t.kind, t.category_type, t.categoria?.tipo, t.categoria_tipo];
            for (const c of candidatos) {
                const n = normalizeTipo(c);
                if (n) return n === alvo;
            }
            const v = Number(t.valor);
            if (!Number.isNaN(v)) {
                return (alvo === 'receita' && v >= 0) || (alvo === 'despesa' && v < 0);
            }
            return true;
        });
    }

    async function tryFetch(pathWithQuery) {
        for (const base of BASES) {
            try {
                const r = await fetch(base + pathWithQuery, { credentials: 'include' });
                if (r.ok) return await r.json();
                if (r.status === 404) continue;
            } catch (_) { }
        }
        return null;
    }

    async function fetchListByMonth(month) {
        if (monthCache.has(month)) return monthCache.get(month);
        const q = `month=${encodeURIComponent(month)}&limit=500`;
        const candidates = [
            `lancamentos?${q}`,            // usa endpoint que já traz "conta" (instituição/nome)
            `dashboard/transactions?${q}`, // fallback
            `transactions?${q}`,           // fallback
        ];
        for (const c of candidates) {
            const json = await tryFetch(c);
            const list = normalizeList(json);
            if (list.length) {
                monthCache.set(month, list);
                return list;
            }
        }
        monthCache.set(month, []);
        return [];
    }

    // Monta rótulo da conta priorizando INSTITUIÇÃO
    function getContaLabel(t) {
        if (typeof t.conta === 'string' && t.conta.trim()) return t.conta.trim(); // /api/lancamentos
        if (t.conta && typeof t.conta === 'object') {
            if (t.conta.instituicao) return t.conta.instituicao;
            if (t.conta.nome) return t.conta.nome;
        }
        const origem =
            t.conta_origem_instituicao || t.origem_instituicao ||
            (t.conta && t.conta.instituicao) ||
            (t.conta_origem && t.conta_origem.instituicao) ||
            t.conta_origem_nome || (t.conta && t.conta.nome) ||
            (t.conta_origem && t.conta_origem.nome) || null;

        const destino =
            t.conta_destino_instituicao || t.destino_instituicao ||
            (t.conta_destino && t.conta_destino.instituicao) ||
            t.conta_destino_nome || (t.conta_destino && t.conta_destino.nome) || null;

        if (t.eh_transferencia && (origem || destino)) {
            return `${origem || '—'} → ${destino || '—'}`;
        }
        return '—';
    }

    function setLoading(on) {
        isLoading = on;
        if (btnFiltrar) {
            btnFiltrar.disabled = on;
            btnFiltrar.classList.toggle('is-loading', on);
            btnFiltrar.innerHTML = on
                ? `<i class="fas fa-circle-notch fa-spin"></i> Filtrando…`
                : `<i class="fas fa-filter"></i> Filtrar`;
        }
    }

    async function getDataForRender() {
        const month = getMonth();
        const lista = await fetchListByMonth(month);
        return { month, lista };
    }

    // --- SweetAlert2 ---
    async function ensureSwal() {
        if (window.Swal) return;
        await new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            s.onload = resolve; s.onerror = reject;
            document.head.appendChild(s);
        });
    }
    function toast(icon, title) {
        window.Swal.fire({
            toast: true, position: 'top-end', timer: 1700, showConfirmButton: false,
            icon, title
        });
    }

    // --- Exclusão: tenta DELETE e fallbacks POST ---
    async function apiDeleteLancamento(id) {
        const raw = (window.BASE_URL || '/').replace(/\/?$/, '/');
        const tries = [
            { url: `${raw}api/lancamentos/${id}`, opt: { method: 'DELETE' } },
            { url: `${raw}index.php/api/lancamentos/${id}`, opt: { method: 'DELETE' } },
            { url: `${raw}api/lancamentos/${id}/delete`, opt: { method: 'POST' } },
            { url: `${raw}index.php/api/lancamentos/${id}/delete`, opt: { method: 'POST' } },
            { url: `${raw}api/lancamentos/delete`, opt: { method: 'POST', body: JSON.stringify({ id }) } },
            { url: `${raw}index.php/api/lancamentos/delete`, opt: { method: 'POST', body: JSON.stringify({ id }) } },
        ];
        for (const t of tries) {
            try {
                const r = await fetch(t.url, {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    ...t.opt
                });
                if (r.ok) return await r.json();
                if (r.status !== 404) {
                    const j = await r.json().catch(() => ({}));
                    throw new Error(j?.message || `HTTP ${r.status}`);
                }
            } catch (_) { /* tenta próxima */ }
        }
        throw new Error('Endpoint de exclusão não encontrado (404). Verifique as rotas do back-end.');
    }

    // --- Linha da tabela (inclui botão Excluir) ---
    function renderRow(t) {
        const tipo = normalizeTipo(t.tipo) || t.tipo || '—';
        const cat = t.categoria_nome || t.categoria?.nome || t.categoria || '—';
        const acc = getContaLabel(t);
        return `
      <tr data-id="${t.id}">
        <td>${fmt.date(t.data)}</td>
        <td>${tipo}</td>
        <td>${cat}</td>
        <td>${acc}</td>
        <td>${t.descricao || t.observacao || '—'}</td>
        <td>${fmt.money(t.valor)}</td>
        <td class="text-right">
          <button class="lk-btn danger btn-del" title="Excluir" aria-label="Excluir">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>`;
    }

    // --- RENDER principal (faltava) ---
    async function render({ byButton = false } = {}) {
        if (!tbody || isLoading) return;
        setEmpty('Carregando…');
        setLoading(true);

        try {
            const { month, lista } = await getDataForRender();
            const tipoSelecionado = byButton ? (selectTipo?.value || '') : '';
            const final = applyTipoFilter(lista, tipoSelecionado);

            if (!final.length) {
                setEmpty('Sem lançamentos para o período');
            } else {
                tbody.innerHTML = final.map(renderRow).join('');
            }

            document.dispatchEvent(new CustomEvent('lukrato:lancamentos-rendered', {
                detail: { month, total: final.length }
            }));
        } catch (e) {
            console.error(e);
            setEmpty('Erro ao carregar lançamentos');
        } finally {
            setLoading(false);
        }
    }

    // --- Exportar CSV (estava referenciado) ---
    async function onExport(monthOverride) {
        const month = monthOverride || getMonth();
        const lista = await fetchListByMonth(month);
        const tipoSelecionado = selectTipo?.value || '';
        const final = applyTipoFilter(lista, tipoSelecionado);

        const rows = final.map(t => [
            fmt.date(t.data),
            (normalizeTipo(t.tipo) || t.tipo || ''),
            (t.categoria_nome || t.categoria?.nome || t.categoria || ''),
            (getContaLabel(t) || ''),
            String(t.descricao || t.observacao || '').replace(/[\r\n;]+/g, ' '),
            (Number(t.valor) || 0).toFixed(2).replace('.', ',')
        ].join(';'));

        const csv = ['Data;Tipo;Categoria;Conta/Cartão;Descrição;Valor', ...rows].join('\r\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = Object.assign(document.createElement('a'), { href: url, download: `lukrato-${month}.csv` });
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // --- Clique no botão Excluir ---
    tbody?.addEventListener('click', async (e) => {
        const btn = (e.target.closest && e.target.closest('.btn-del')) || null;
        if (!btn) return;

        const tr = e.target.closest('tr');
        const id = tr?.getAttribute('data-id');
        if (!id) return;

        try {
            await ensureSwal();

            const confirm = await Swal.fire({
                title: 'Excluir lançamento?',
                text: 'Essa ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true
            });
            if (!confirm.isConfirmed) return;

            Swal.fire({ title: 'Excluindo...', didOpen: () => Swal.showLoading(), allowOutsideClick: false, allowEscapeKey: false });

            await apiDeleteLancamento(Number(id));

            Swal.close();
            toast('success', 'Lançamento excluído');

            // invalida cache + re-render
            const m = getMonth();
            monthCache.delete(m);
            document.dispatchEvent(new CustomEvent('lukrato:data-changed'));
            render();

        } catch (err) {
            console.error(err);
            await ensureSwal();
            Swal.fire({ icon: 'error', title: 'Erro', text: (err && err.message) || 'Falha ao excluir' });
        }
    });

    // --- Boot ---
    document.addEventListener('DOMContentLoaded', () => {
        render();

        btnFiltrar?.addEventListener('click', () => render({ byButton: true }));

        document.addEventListener('lukrato:month-changed', (e) => {
            const m = e?.detail?.month;
            const el = document.getElementById('currentMonthText');
            if (m && el) el.setAttribute('data-month', m);
            render();
        });

        document.addEventListener('lukrato:export-click', (e) => onExport(e.detail?.month));

        document.addEventListener('lukrato:data-changed', () => {
            const m = getMonth();
            monthCache.delete(m);
            render();
        });
        // ====== Transferência via botão do header ======
        const btnTransferHeader = $('#btnTransferHeader');

        const modalTr = $('#modalTransfer');
        const trClose = $('#trClose');
        const trCancel = $('#trCancel');
        const formTr = $('#formTransfer');

        const grpOrigemReadOnly = $('#grpOrigemReadOnly');
        const grpOrigemSelect = $('#grpOrigemSelect');

        const trOrigemId = $('#trOrigemId');     // hidden (modo card)
        const trOrigemNome = $('#trOrigemNome');   // readonly (modo card)
        const trOrigemIdSel = $('#trOrigemIdSel');  // select (modo header)

        const trDestinoId = $('#trDestinoId');
        const trData = $('#trData');
        const trValor = $('#trValor');
        const trDesc = $('#trDesc');

        const apiTransfer = (payload) => fetchJSON(BASE + 'api/transfers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        function closeFab() {
            const fab = $('#fabButton');
            const menu = $('#fabMenu');
            menu?.classList.remove('active');
            fab?.classList.remove('active');
            fab?.setAttribute('aria-expanded', 'false');
        }

        function openTransferModal(fromAccountId = null) {
            // data de hoje
            if (trData && 'valueAsDate' in trData) trData.valueAsDate = new Date();
            else if (trData) trData.value = new Date().toISOString().slice(0, 10);

            trValor && (trValor.value = '');
            trDesc && (trDesc.value = '');

            // Modo header (sem origem fixa) → mostra select de origem
            if (!fromAccountId) {
                grpOrigemReadOnly.style.display = 'none';
                grpOrigemSelect.style.display = '';
                trOrigemId.value = '';
                populateOrigemSelect().then(() => populateDestinoSelect());
            } else {
                // (Se você chamar com uma conta fixa no futuro)
                grpOrigemReadOnly.style.display = '';
                grpOrigemSelect.style.display = 'none';
                trOrigemId.value = String(fromAccountId);
                const c = (_lastRows || []).find(r => r.id === Number(fromAccountId));
                trOrigemNome.value = c ? `${(c.instituicao || '').trim()}${c.instituicao ? ' — ' : ''}${(c.nome || '').trim()}` : '';
                populateDestinoSelect(fromAccountId);
            }

            modalTr.classList.add('open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => trValor?.focus(), 40);
        }

        function closeTransferModal() {
            modalTr.classList.remove('open');
            document.body.style.overflow = '';
        }

        trClose?.addEventListener('click', closeTransferModal);
        trCancel?.addEventListener('click', closeTransferModal);
        modalTr?.addEventListener('click', (e) => { if (e.target === modalTr) closeTransferModal(); });

        trValor?.addEventListener('blur', () => {
            const v = parseMoney(trValor.value);
            trValor.value = v ? brl(v) : '';
        });

        // Popula selects
        async function populateOrigemSelect(selectedId = '') {
            const opts = await ensureOptions();
            const contas = Array.isArray(opts?.contas) ? opts.contas : [];
            trOrigemIdSel.innerHTML = `<option value="">Selecione a conta de origem</option>`;
            contas.forEach(c => {
                const op = document.createElement('option');
                op.value = c.id;
                const inst = (c.instituicao || '').trim(), nome = (c.nome || '').trim();
                op.textContent = inst && inst !== nome ? `${inst} (${nome})` : (inst || nome || '—');
                if (String(c.id) === String(selectedId)) op.selected = true;
                trOrigemIdSel.appendChild(op);
            });
        }

        async function populateDestinoSelect(originId = '') {
            const opts = await ensureOptions();
            const contas = Array.isArray(opts?.contas) ? opts.contas : [];
            trDestinoId.innerHTML = `<option value="">Selecione a conta de destino</option>`;
            contas.forEach(c => {
                if (originId && Number(c.id) === Number(originId)) return; // não permitir mesma conta
                const op = document.createElement('option');
                op.value = c.id;
                const inst = (c.instituicao || '').trim(), nome = (c.nome || '').trim();
                op.textContent = inst && inst !== nome ? `${inst} (${nome})` : (inst || nome || '—');
                trDestinoId.appendChild(op);
            });
        }

        // Quando trocar a origem (modo header), refaz o destino
        trOrigemIdSel?.addEventListener('change', () => {
            const oid = Number(trOrigemIdSel.value || 0);
            populateDestinoSelect(oid);
        });

        // Clique no botão do header
        btnTransferHeader?.addEventListener('click', (e) => {
            e.preventDefault();
            closeFab();
            openTransferModal(null);            // origem será escolhida no select
        });

        // Submit
        formTr?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const origem = trOrigemId.value ? Number(trOrigemId.value) : Number(trOrigemIdSel.value);
                const destino = Number(trDestinoId.value);
                const valor = parseMoney(trValor.value);

                if (!origem || !destino || origem === destino)
                    return Swal.fire('Atenção', 'Selecione contas de origem e destino diferentes.', 'warning');
                if (!trData.value || !valor || valor <= 0)
                    return Swal.fire('Atenção', 'Preencha data e valor válidos.', 'warning');

                await apiTransfer({
                    data: trData.value,
                    valor,
                    conta_id: origem,
                    conta_id_destino: destino,
                    descricao: trDesc.value || null,
                    observacao: null
                });

                Swal.fire({ icon: 'success', title: 'Transferência registrada!', timer: 1300, showConfirmButton: false });
                closeTransferModal();
                // atualiza UI
                window.refreshDashboard && window.refreshDashboard();
                window.refreshReports && window.refreshReports();
                window.fetchLancamentos && window.fetchLancamentos();
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao salvar transferência.', 'error');
            }
        });

    });
})();
