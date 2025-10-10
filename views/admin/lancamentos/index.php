<section class="lan-page">
    <div class="lan-header">
        <h3 class="lan-title">Lancamentos</h3>
        <div class="lan-controls">
            <header class="dash-lk-header">
                <div class="header-left">
                    <div class="month-selector">
                        <div class="lk-period">
                            <button class="month-nav-btn" id="prevMonth" type="button" aria-label="Mes anterior">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="month-dropdown-btn" id="monthDropdownBtn" type="button"
                                data-bs-toggle="modal" data-bs-target="#monthModal" aria-haspopup="true"
                                aria-expanded="false">
                                <span id="currentMonthText">Carregando...</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="month-display">
                                <div class="month-dropdown" id="monthDropdown" role="menu"></div>
                            </div>
                            <button class="month-nav-btn" id="nextMonth" type="button" aria-label="Proximo mes">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            <div class="lan-filter">
                <div class="type-filter" role="group" aria-label="Filtros">
                    <label for="filtroTipo" class="sr-only">Tipo</label>
                    <select id="filtroTipo" class="lk-select btn btn-primary">
                        <option value="">Todos</option>
                        <option value="receita">Receitas</option>
                        <option value="despesa">Despesas</option>
                    </select>
                    <label for="filtroCategoria" class="sr-only">Categoria</label>
                    <select id="filtroCategoria" class="lk-select btn btn-primary">
                        <option value="">Todas as categorias</option>
                        <option value="none">Sem categoria</option>
                    </select>
                    <label for="filtroConta" class="sr-only">Conta</label>
                    <select id="filtroConta" class="lk-select btn btn-primary">
                        <option value="">Todas as contas</option>
                    </select>
                    <button id="btnFiltrar" type="button" class="lk-btn ghost btn">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <button id="btnExcluirSel" type="button" class="lk-btn danger btn" disabled>
                        <i class="fas fa-trash"></i> Excluir selecionados
                    </button>
                    <small id="selInfo" class="text-muted d-none">
                        <span id="selCount">0</span> selecionado(s)
                    </small>
                </div>
            </div>
        </div>
    </div>

    <section class="table-container">
        <table class="lukrato-table" id="tabelaLancamentos">
            <thead>
                <tr>
                    <th class="text-center" style="width:36px">
                        <input type="checkbox" id="chkAll" aria-label="Selecionar todos">
                    </th>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Categoria</th>
                    <th>Conta</th>
                    <th>Descricao</th>
                    <th class="text-right">Valor</th>
                    <th style="width:82px">Acoes</th>
                </tr>
            </thead>
            <tbody id="tbodyLancamentos">
                <tr>
                    <td colspan="8" class="text-center">Carregando...</td>
                </tr>
            </tbody>
        </table>
    </section>
</section>

<div class="modal fade" id="monthModal" tabindex="-1" aria-labelledby="monthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="monthModalLabel">Selecionar mês</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="btn-group" role="group" aria-label="Navegar entre anos">
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpPrevYear" title="Ano anterior">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="px-3 fw-semibold" id="mpYearLabel">2024</span>
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpNextYear" title="Próximo ano">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpTodayBtn">Hoje</button>
                        <input type="month" class="form-control form-control-sm bg-dark text-light border-secondary"
                            id="mpInputMonth" style="width:165px">
                    </div>
                </div>
                <div id="mpGrid" class="row g-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
</script>
<script>
(() => {
    'use strict';
    if (window.__LK_LANCAMENTOS_LOADER__) return;
    window.__LK_LANCAMENTOS_LOADER__ = true;

    const rawBase = (window.BASE_URL || (location.pathname.includes('/public/') ?
        location.pathname.split('/public/')[0] + '/public/' :
        '/')).replace(/\/?$/, '/');
    const ENDPOINT = `${rawBase}api/lancamentos`;

    const $ = (s) => document.querySelector(s);
    const tbody = $('#tbodyLancamentos');
    const selectTipo = $('#filtroTipo');
    const selectCategoria = $('#filtroCategoria');
    const selectConta = $('#filtroConta');
    const btnFiltrar = $('#btnFiltrar');
    const btnExcluirSel = $('#btnExcluirSel');
    const chkAll = $('#chkAll');
    const selInfo = $('#selInfo');
    const selCountSpan = $('#selCount');
    const monthEl = document.getElementById('currentMonthText');
    const setMonthLabel = (ym) => {
        if (!monthEl || !/^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) return;
        const [y, m] = ym.split('-').map(Number);
        monthEl.textContent = new Date(y, m - 1, 1)
            .toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
        monthEl.setAttribute('data-month', ym);
    };


    const fmtMoney = (n) => new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(Number(n || 0));
    const fmtDate = (iso) => {
        if (!iso) return '-';
        const d = new Date(iso);
        return isNaN(d) ? '-' : d.toLocaleDateString('pt-BR');
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[m] || m));

    const normalizeDataList = (payload) => {
        if (!payload) return [];
        if (Array.isArray(payload)) return payload;
        if (payload && Array.isArray(payload.data)) return payload.data;
        return [];
    };

    const fetchJsonList = async (url) => {
        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!res.ok) return [];
            const body = await res.json().catch(() => null);
            return normalizeDataList(body);
        } catch {
            return [];
        }
    };

    async function loadFilterOptions() {
        const categoriaPromise = selectCategoria ? fetchJsonList(`${rawBase}api/categorias`) : Promise.resolve([]);
        const contaPromise = selectConta ? fetchJsonList(`${rawBase}api/accounts?only_active=1`) : Promise.resolve([]);

        if (selectCategoria) {
            selectCategoria.innerHTML = '<option value="">Todas as categorias</option><option value="none">Sem categoria</option>';
        }
        if (selectConta) {
            selectConta.innerHTML = '<option value="">Todas as contas</option>';
        }

        const [categorias, contas] = await Promise.all([categoriaPromise, contaPromise]);

        if (selectCategoria && categorias.length) {
            const items = categorias
                .map((cat) => ({
                    id: Number(cat?.id ?? 0),
                    nome: String(cat?.nome ?? '').trim()
                }))
                .filter((cat) => Number.isFinite(cat.id) && cat.id > 0 && cat.nome);

            items.sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', { sensitivity: 'base' }));

            const options = items
                .map((cat) => `<option value="${cat.id}">${escapeHtml(cat.nome)}</option>`)
                .join('');
            selectCategoria.insertAdjacentHTML('beforeend', options);
        }

        if (selectConta && contas.length) {
            const items = contas
                .map((acc) => {
                    const id = Number(acc?.id ?? 0);
                    const nome = String(acc?.nome ?? '').trim();
                    const instituicao = String(acc?.instituicao ?? '').trim();
                    const label = nome || instituicao;
                    return { id, label };
                })
                .filter((acc) => Number.isFinite(acc.id) && acc.id > 0 && acc.label);

            items.sort((a, b) => a.label.localeCompare(b.label, 'pt-BR', { sensitivity: 'base' }));

            const options = items
                .map((acc) => `<option value="${acc.id}">${escapeHtml(acc.label)}</option>`)
                .join('');
            selectConta.insertAdjacentHTML('beforeend', options);
        }
    }

    const hasSwal = !!window.Swal;
    const ask = async (title, text = '') => {
        if (hasSwal) {
            const r = await Swal.fire({
                title,
                text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            });
            return r.isConfirmed;
        }
        return confirm(title || 'Confirmar exclusão?');
    };
    const toast = (msg, icon = 'success') => {
        if (hasSwal) Swal.fire({
            toast: true,
            position: 'top-end',
            timer: 1800,
            showConfirmButton: false,
            icon,
            title: msg
        });
    };

    function renderEmpty() {
        tbody.innerHTML =
            `<tr><td colspan="8" class="text-center text-muted">Sem lançamentos para o período</td></tr>`;
        if (btnExcluirSel) btnExcluirSel.disabled = true;
        if (selInfo) selInfo.classList.add('d-none');
        if (chkAll) chkAll.checked = false;
    }

    function updateSelectionInfo() {
        const checks = tbody.querySelectorAll('.row-check:checked');
        const count = checks.length;
        if (selCountSpan) selCountSpan.textContent = String(count);
        if (btnExcluirSel) btnExcluirSel.disabled = count === 0;
        if (selInfo) selInfo.classList.toggle('d-none', count === 0);
        if (chkAll) chkAll.checked = count > 0 && count === tbody.querySelectorAll('.row-check').length;
    }

    function renderRows(items) {
        if (!Array.isArray(items) || items.length === 0) return renderEmpty();
        const rows = items.map(it => `
      <tr data-id="${it.id ?? ''}">
        <td class="text-center" style="width:36px"><input type="checkbox" class="row-check" aria-label="Selecionar"></td>
        <td>${fmtDate(it.data || it.created_at)}</td>
        <td>${(it.tipo||'-').toString().replace(/^./, m=>m.toUpperCase())}</td>
        <td>${it.categoria_nome || it.categoria || '-'}</td>
        <td>${it.conta_nome || it.conta || '-'}</td>
        <td>${it.descricao || '-'}</td>
        <td class="text-end">${fmtMoney(it.valor)}</td>
        <td class="text-nowrap" style="width:120px">
            <button class="lk-btn danger btn-del" title="Excluir" data-action="delete">
                    <i class="fas fa-trash"></i>
                    </button>
        </td>
      </tr>
    `).join('');
        tbody.innerHTML = rows;
        wireRowEvents();
        updateSelectionInfo();
    }

    function wireRowEvents() {
        tbody.addEventListener('click', async (ev) => {
            const btn = ev.target.closest('button[data-action="delete"]');
            if (!btn) return;
            const tr = btn.closest('tr');
            const id = tr?.getAttribute('data-id');
            if (!id) return;

            const ok = await ask('Excluir lançamento?', 'Essa ação não pode ser desfeita.');
            if (!ok) return;

            btn.disabled = true;
            const okDel = await apiDeleteOne(id);
            btn.disabled = false;

            if (okDel) {
                tr.remove();
                toast('Excluído.');
                if (!tbody.querySelector('tr')) renderEmpty();
                updateSelectionInfo();
            } else {
                toast('Falha ao excluir.', 'error');
            }
        });

        tbody.querySelectorAll('.row-check').forEach(cb => cb.addEventListener('change', updateSelectionInfo));
    }

    chkAll && chkAll.addEventListener('change', () => {
        tbody.querySelectorAll('.row-check').forEach(cb => cb.checked = chkAll.checked);
        updateSelectionInfo();
    });

    async function fetchLancamentos({
        month,
        tipo = '',
        categoria = '',
        conta = '',
        limit = 500
    }) {
        const qs = new URLSearchParams();
        if (month) qs.set('month', month);
        if (tipo) qs.set('tipo', tipo);
        if (categoria !== undefined && categoria !== null && categoria !== '') {
            qs.set('categoria_id', categoria);
        }
        if (conta !== undefined && conta !== null && conta !== '') {
            qs.set('account_id', conta);
        }
        qs.set('limit', String(limit));
        try {
            const res = await fetch(`${ENDPOINT}?${qs.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (res.status === 204 || res.status === 404 || !res.ok) return [];
            const data = await res.json().catch(() => null);
            if (Array.isArray(data)) return data;
            if (data && Array.isArray(data.data)) return data.data;
            return [];
        } catch {
            return [];
        }
    }

    async function apiDeleteOne(id) {
        try {
            const token = (window.LK && typeof LK.getCSRF === 'function') ? LK.getCSRF() : '';
            const res = await fetch(`${ENDPOINT}/${encodeURIComponent(id)}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            return res.ok;
        } catch {
            return false;
        }
    }

    async function apiBulkDelete(ids) {
        try {
            const token = (window.LK && typeof LK.getCSRF === 'function') ? LK.getCSRF() : '';
            const payload = {
                ids,
                _token: token,
                csrf_token: token
            };
            const res = await fetch(`${ENDPOINT}delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            });
            if (res.ok) return true;
        } catch {}
        const results = await Promise.all(ids.map(apiDeleteOne));
        return results.every(Boolean);
    }

    btnExcluirSel && btnExcluirSel.addEventListener('click', async () => {
        const checks = [...tbody.querySelectorAll('.row-check:checked')];
        if (checks.length === 0) return;

        const ids = checks.map(cb => cb.closest('tr')?.getAttribute('data-id')).filter(Boolean);
        const ok = await ask(`Excluir ${ids.length} lançamento(s)?`,
            'Essa ação não pode ser desfeita.');
        if (!ok) return;

        btnExcluirSel.disabled = true;
        const done = await apiBulkDelete(ids);
        btnExcluirSel.disabled = false;

        if (done) {
            ids.forEach(id => tbody.querySelector(`tr[data-id="${CSS.escape(id)}"]`)?.remove());
            toast('Itens excluídos.');
            if (!tbody.querySelector('tr')) renderEmpty();
            updateSelectionInfo();
        } else {
            toast('Alguns itens não foram excluídos.', 'error');
        }
    });

    let timer = null;
    async function load() {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const month = (window.LukratoHeader?.getMonth?.()) || (new Date()).toISOString().slice(
                0, 7);
            const tipo = selectTipo ? selectTipo.value : '';
            const categoria = selectCategoria ? selectCategoria.value : '';
            const conta = selectConta ? selectConta.value : '';

            setMonthLabel(month);

            tbody.innerHTML = `<tr><td colspan="8" class="text-center">Carregando…</td></tr>`;
            const items = await fetchLancamentos({
                month,
                tipo,
                categoria,
                conta,
                limit: 500
            });
            renderRows(items);
        }, 10);
    }
    window.refreshLancamentos = load;
    document.addEventListener('lukrato:data-changed', (e) => {
        const res = e.detail?.resource;
        if (!res || res === 'transactions') load();
    });


    btnFiltrar && btnFiltrar.addEventListener('click', load);

    loadFilterOptions();
    setMonthLabel(window.LukratoHeader?.getMonth?.() || (new Date()).toISOString().slice(0, 7));
    load();

})();
</script>
<script>
(() => {
    'use strict';
    if (window.__LK_MONTH_PICKER__) return;
    window.__LK_MONTH_PICKER__ = true;

    const elText = document.getElementById('currentMonthText');
    const btnPrev = document.getElementById('prevMonth');
    const btnNext = document.getElementById('nextMonth');
    const modalEl = document.getElementById('monthModal');

    const mpYearLabel = document.getElementById('mpYearLabel');
    const mpPrevYear = document.getElementById('mpPrevYear');
    const mpNextYear = document.getElementById('mpNextYear');
    const mpGrid = document.getElementById('mpGrid');
    const mpTodayBtn = document.getElementById('mpTodayBtn');
    const mpInput = document.getElementById('mpInputMonth');

    const STORAGE_KEY = 'lukrato.month.dashboard';
    const SHORT = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    const toYM = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
    const monthLabel = ym => {
        const [y, m] = ym.split('-').map(Number);
        return new Date(y, m - 1, 1).toLocaleDateString('pt-BR', {
            month: 'long',
            year: 'numeric'
        });
    };

    let state = sessionStorage.getItem(STORAGE_KEY) || toYM(new Date());
    let modalYear = Number(state.split('-')[0]) || (new Date()).getFullYear();

    const setState = (ym, {
        silent = false
    } = {}) => {
        if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) return;
        state = ym;
        sessionStorage.setItem(STORAGE_KEY, state);
        if (elText) {
            elText.textContent = monthLabel(state);
            elText.setAttribute('data-month', state);
        }
        if (!silent) {
            document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
                detail: {
                    month: state
                }
            }));
        }
    };

    const shiftMonth = (delta) => {
        const [y, m] = state.split('-').map(Number);
        const d = new Date(y, (m - 1) + delta, 1);
        setState(toYM(d));
    };

    const buildGrid = () => {
        if (!mpYearLabel || !mpGrid) return;
        mpYearLabel.textContent = modalYear;
        let html = '';
        for (let i = 0; i < 12; i++) {
            const ym = `${modalYear}-${String(i+1).padStart(2,'0')}`;
            const active = ym === state ? 'btn-warning text-dark fw-bold' : 'btn-outline-light';
            html += `<div class="col-4">
        <button type="button" class="mp-month btn w-100 py-3 ${active}" data-val="${ym}">${SHORT[i]}</button>
      </div>`;
        }
        mpGrid.innerHTML = html;
        mpGrid.querySelectorAll('.mp-month').forEach(btn => {
            btn.addEventListener('click', () => {
                setState(btn.getAttribute('data-val'));
                bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
            });
        });
    };

    btnPrev?.addEventListener('click', e => {
        e.preventDefault();
        shiftMonth(-1);
    });
    btnNext?.addEventListener('click', e => {
        e.preventDefault();
        shiftMonth(+1);
    });

    modalEl?.addEventListener('show.bs.modal', () => {
        modalYear = Number(state.split('-')[0]) || (new Date()).getFullYear();
        if (mpInput) mpInput.value = state;
        buildGrid();
    });
    mpPrevYear?.addEventListener('click', () => {
        modalYear--;
        buildGrid();
    });
    mpNextYear?.addEventListener('click', () => {
        modalYear++;
        buildGrid();
    });
    mpTodayBtn?.addEventListener('click', () => {
        const nowYM = toYM(new Date());
        setState(nowYM);
        bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
    });
    mpInput?.addEventListener('change', e => {
        const ym = e.target.value;
        if (/^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) {
            setState(ym);
            bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
        }
    });

    window.LukratoHeader = {
        getMonth: () => state,
        setMonth: (ym) => setState(ym)
    };
    setState(state);
})();
</script>
