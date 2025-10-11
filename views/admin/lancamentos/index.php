<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">
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
        <div id="tabLancamentos"></div>
    </section>
</section>

<div class="modal fade" id="modalLancamento" tabindex="-1" aria-labelledby="modalLancamentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modalLancamentoLabel">Editar lancamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-0">
                <div id="editLancAlert" class="alert alert-danger d-none" role="alert"></div>
                <form id="formLancamento" novalidate>
                    <div class="mb-3">
                        <label for="editLancData" class="form-label text-light small mb-1">Data</label>
                        <input type="date" class="form-control form-control-sm bg-dark text-light border-secondary" id="editLancData" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editLancTipo" class="form-label text-light small mb-1">Tipo</label>
                            <select class="form-select form-select-sm bg-dark text-light border-secondary" id="editLancTipo" required>
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editLancConta" class="form-label text-light small mb-1">Conta</label>
                            <select class="form-select form-select-sm bg-dark text-light border-secondary" id="editLancConta" required></select>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label for="editLancCategoria" class="form-label text-light small mb-1">Categoria</label>
                        <select class="form-select form-select-sm bg-dark text-light border-secondary" id="editLancCategoria"></select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editLancValor" class="form-label text-light small mb-1">Valor</label>
                            <input type="number" class="form-control form-control-sm bg-dark text-light border-secondary" id="editLancValor" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editLancDescricao" class="form-label text-light small mb-1">Descricao</label>
                            <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary" id="editLancDescricao" maxlength="190">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label for="editLancObs" class="form-label text-light small mb-1">Observacao</label>
                        <textarea class="form-control form-control-sm bg-dark text-light border-secondary" id="editLancObs" rows="3" maxlength="500"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm" form="formLancamento">Salvar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="monthModal" tabindex="-1" aria-labelledby="monthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="monthModalLabel">Selecionar mes</h5>
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
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpNextYear" title="Proximo ano">
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
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
    const tabContainer = document.getElementById('tabLancamentos');
    const selectTipo = $('#filtroTipo');
    const selectCategoria = $('#filtroCategoria');
    const selectConta = $('#filtroConta');
    const btnFiltrar = $('#btnFiltrar');
    const btnExcluirSel = $('#btnExcluirSel');
    const selInfo = $('#selInfo');
    const selCountSpan = $('#selCount');
    const monthEl = document.getElementById('currentMonthText');
    const modalLancEl = document.getElementById('modalLancamento');
    let modalLanc = null;
    const formLanc = document.getElementById('formLancamento');
    const editLancAlert = document.getElementById('editLancAlert');
    const inputLancData = document.getElementById('editLancData');
    const selectLancTipo = document.getElementById('editLancTipo');
    const selectLancConta = document.getElementById('editLancConta');
    const selectLancCategoria = document.getElementById('editLancCategoria');
    const inputLancValor = document.getElementById('editLancValor');
    const inputLancDescricao = document.getElementById('editLancDescricao');
    const inputLancObs = document.getElementById('editLancObs');

    let editingLancamentoId = null;
    let categoriaOptions = [];
    let contaOptions = [];
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

    const ensureLancModal = () => {
        if (modalLanc) return modalLanc;
        if (!modalLancEl) return null;
        if (window.bootstrap?.Modal) {
            modalLanc = window.bootstrap.Modal.getOrCreateInstance(modalLancEl);
            return modalLanc;
        }
        return null;
    };

    const clearLancAlert = () => {
        if (!editLancAlert) return;
        editLancAlert.classList.add('d-none');
        editLancAlert.textContent = '';
    };

    const showLancAlert = (msg) => {
        if (!editLancAlert) return;
        editLancAlert.textContent = msg;
        editLancAlert.classList.remove('d-none');
    };

    function populateCategoriaSelect(select, tipo, selectedId) {
        if (!select) return;
        const normalized = (tipo || '').toLowerCase();
        const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';
        select.innerHTML = '<option value="">Sem categoria</option>';
        const items = categoriaOptions.filter((item) => {
            if (!normalized) return true;
            return item.tipo === normalized;
        });
        items.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = item.nome;
            opt.dataset.tipo = item.tipo || '';
            if (currentValue && String(item.id) === currentValue) opt.selected = true;
            select.appendChild(opt);
        });
        if (currentValue && select.value !== currentValue) {
            const fallback = document.createElement('option');
            fallback.value = currentValue;
            fallback.textContent = 'Categoria indisponivel';
            fallback.selected = true;
            select.appendChild(fallback);
        }
    }

    function populateContaSelect(select, selectedId) {
        if (!select) return;
        const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';
        select.innerHTML = '<option value="">Selecione</option>';
        contaOptions.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = item.label;
            if (currentValue && String(item.id) === currentValue) opt.selected = true;
            select.appendChild(opt);
        });
        if (currentValue && select.value !== currentValue) {
            const fallback = document.createElement('option');
            fallback.value = currentValue;
            fallback.textContent = 'Conta indisponivel';
            fallback.selected = true;
            select.appendChild(fallback);
        }
    }

    // Tabulator: instancia
    let table = null;
    function ensureTable() {
        if (table || !tabContainer) return table;
        table = new Tabulator(tabContainer, {
            height: "520px",
            layout: "fitColumns",
            placeholder: "Sem lancamentos para o periodo",
            selectable: true,
            index: "id",
            pagination: "local",
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100],
            rowFormatter: (row) => {
                const data = row.getData();
                row.getElement().setAttribute('data-id', data?.id ?? '');
                if (isSaldoInicial(data)) {
                    row.getElement()?.classList.add('lk-row-inicial');
                }
            },
            selectableCheck: (row) => !isSaldoInicial(row.getData()),
            columns: [
                {
                    formatter: "rowSelection",
                    titleFormatter: "rowSelection",
                    hozAlign: "center",
                    headerSort: false,
                    width: 44,
                    cellClick: (e, cell) => {
                        const data = cell.getRow().getData();
                        if (isSaldoInicial(data)) {
                            e.preventDefault();
                            cell.getRow().deselect();
                        }
                    },
                    cellRendered: (cell) => {
                        const data = cell.getRow().getData();
                        if (isSaldoInicial(data)) {
                            cell.getElement().classList.add('lk-cell-select-disabled');
                        }
                    }
                },
                {
                    title: "Data",
                    field: "data",
                    sorter: "date",
                    hozAlign: "left",
                    width: 130,
                    mutator: (value, data) => data.data || data.created_at,
                    formatter: (cell) => fmtDate(cell.getValue()),
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filtrar data"
                },
                {
                    title: "Tipo",
                    field: "tipo",
                    width: 120,
                    formatter: (cell) => {
                        const t = String(cell.getValue() || '-');
                        return t.charAt(0).toUpperCase() + t.slice(1);
                    },
                    headerFilter: "select",
                    headerFilterParams: {
                        values: {
                            "": "Todos",
                            receita: "Receitas",
                            despesa: "Despesas"
                        }
                    }
                },
                {
                    title: "Categoria",
                    field: "categoria_nome",
                    widthGrow: 1,
                    formatter: (cell) => cell.getValue() || cell.getRow().getData().categoria || '-',
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filtrar categoria"
                },
                {
                    title: "Conta",
                    field: "conta_nome",
                    widthGrow: 1,
                    formatter: (cell) => cell.getValue() || cell.getRow().getData().conta || '-',
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filtrar conta"
                },
                {
                    title: "Descricao",
                    field: "descricao",
                    widthGrow: 2,
                    formatter: (cell) => cell.getValue() || '-',
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filtrar descricao"
                },
                {
                    title: "Valor",
                    field: "valor",
                    hozAlign: "right",
                    width: 150,
                    formatter: (cell) => fmtMoney(cell.getValue()),
                    headerFilter: "input",
                    headerFilterPlaceholder: "Filtrar valor"
                },
                {
                    title: "Acoes",
                    field: "actions",
                    headerSort: false,
                    hozAlign: "center",
                    width: 120,
                    formatter: (cell) => {
                        const data = cell.getRow().getData();
                        if (isSaldoInicial(data)) return '';
                        const buttons = [];
                        if (canEditLancamento(data)) {
                            buttons.push('<button class="lk-btn ghost" data-action="edit" title="Editar"><i class="fas fa-pen"></i></button>');
                        }
                        buttons.push('<button class="lk-btn danger" data-action="delete" title="Excluir"><i class="fas fa-trash"></i></button>');
                        return `<div class="lk-actions">${buttons.join('')}</div>`;
                    },
                    cellClick: async (e, cell) => {
                        const row = cell.getRow();
                        const data = row.getData();
                        const btn = e.target.closest('button[data-action]');
                        if (!btn) return;
                        const action = btn.getAttribute('data-action');
                        if (action === 'edit') {
                            if (!canEditLancamento(data)) return;
                            openEditLancamento(data);
                            return;
                        }
                        if (action === 'delete') {
                            const id = data?.id;
                            if (!id || isSaldoInicial(data)) return;
                            const ok = await ask('Excluir lancamento?', 'Essa acao nao pode ser desfeita.');
                            if (!ok) return;
                            btn.disabled = true;
                            const okDel = await apiDeleteOne(id);
                            btn.disabled = false;
                            if (okDel) {
                                row.delete();
                                toast('Excluido.');
                                updateSelectionInfo();
                            } else {
                                toast('Falha ao excluir.', 'error');
                            }
                        }
                    }
                }
            ]
        });
        table.on("rowSelectionChanged", (_data, rows) => {
            if (Array.isArray(rows)) {
                rows.forEach((row) => {
                    if (isSaldoInicial(row.getData())) {
                        row.deselect();
                    }
                });
            }
            updateSelectionInfo();
        });
        return table;
    }



    function isSaldoInicial(data) {
        if (!data) return false;
        const tipo = String(data?.tipo || '').toLowerCase();
        const descricao = String(data?.descricao || '').toLowerCase();
        if (tipo === 'saldo_inicial' || tipo === 'saldo inicial') return true;
        return descricao.includes('saldo inicial');
    }
    function isTransferencia(data) {
        return Boolean(data?.eh_transferencia);
    }
    function canEditLancamento(data) {
        return !isSaldoInicial(data) && !isTransferencia(data);
    }
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
            categoriaOptions = categorias
                .map((cat) => ({
                    id: Number(cat?.id ?? 0),
                    nome: String(cat?.nome ?? '').trim(),
                    tipo: String(cat?.tipo ?? '').trim().toLowerCase()
                }))
                .filter((cat) => Number.isFinite(cat.id) && cat.id > 0 && cat.nome)
                .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', { sensitivity: 'base' }));

            const options = categoriaOptions
                .map((cat) => `<option value="${cat.id}">${escapeHtml(cat.nome)}</option>`)
                .join('');
            selectCategoria.insertAdjacentHTML('beforeend', options);
        }

        if (selectConta && contas.length) {
            contaOptions = contas
                .map((acc) => {
                    const id = Number(acc?.id ?? 0);
                    const nome = String(acc?.nome ?? '').trim();
                    const instituicao = String(acc?.instituicao ?? '').trim();
                    const label = nome || instituicao;
                    return { id, label: label || `Conta #${id}` };
                })
                .filter((acc) => Number.isFinite(acc.id) && acc.id > 0 && acc.label)
                .sort((a, b) => a.label.localeCompare(b.label, 'pt-BR', { sensitivity: 'base' }));

            const options = contaOptions
                .map((acc) => `<option value="${acc.id}">${escapeHtml(acc.label)}</option>`)
                .join('');
            selectConta.insertAdjacentHTML('beforeend', options);
        }

        if (selectLancConta) {
            populateContaSelect(selectLancConta, selectLancConta.value || null);
        }
        if (selectLancCategoria) {
            populateCategoriaSelect(selectLancCategoria, selectLancTipo?.value || '', selectLancCategoria.value || null);
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
        return confirm(title || 'Confirmar exclusao?');
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
    function updateSelectionInfo() {
        const t = ensureTable();
        const selected = t ? t.getSelectedRows().filter(row => !isSaldoInicial(row.getData())) : [];
        const count = selected.length;
        if (selCountSpan) selCountSpan.textContent = String(count);
        if (btnExcluirSel) {
            btnExcluirSel.toggleAttribute("disabled", count === 0);
        }
        if (selInfo) selInfo.classList.toggle("d-none", count === 0);
    }

    function openEditLancamento(data) {
        const modal = ensureLancModal();
        if (!modal || !canEditLancamento(data)) return;
        editingLancamentoId = data?.id ?? null;
        if (!editingLancamentoId) return;
        clearLancAlert();

        if (inputLancData) inputLancData.value = (data?.data || '').slice(0, 10);
        if (selectLancTipo) {
            const tipo = String(data?.tipo || '').toLowerCase();
            if (["receita", "despesa"].includes(tipo)) {
                selectLancTipo.value = tipo;
            } else {
                selectLancTipo.value = 'despesa';
            }
        }

        populateContaSelect(selectLancConta, data?.conta_id ?? null);
        populateCategoriaSelect(selectLancCategoria, selectLancTipo?.value || '', data?.categoria_id ?? null);

        if (inputLancValor) {
            const valor = Math.abs(Number(data?.valor ?? 0));
            inputLancValor.value = Number.isFinite(valor) ? valor.toFixed(2) : '';
        }
        if (inputLancDescricao) inputLancDescricao.value = data?.descricao || '';
        if (inputLancObs) inputLancObs.value = data?.observacao || '';

        modal.show();
    }


    function renderRows(items) {
        const grid = ensureTable();
        if (!grid) return;
        grid.setData(Array.isArray(items) ? items : []);
        updateSelectionInfo();
    }
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
    selectLancTipo?.addEventListener('change', () => {
        populateCategoriaSelect(selectLancCategoria, selectLancTipo.value, selectLancCategoria?.value || '');
    });

    modalLancEl?.addEventListener('hidden.bs.modal', () => {
        editingLancamentoId = null;
        formLanc?.reset?.();
        clearLancAlert();
    });

    formLanc?.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        if (!editingLancamentoId) return;
        clearLancAlert();

        const dataValue = inputLancData?.value || '';
        const tipoValue = selectLancTipo?.value || '';
        const contaValue = selectLancConta?.value || '';
        const categoriaValue = selectLancCategoria?.value || '';
        let valorValue = inputLancValor?.value || '';
        const descricaoValue = (inputLancDescricao?.value || '').trim();
        const obsValue = (inputLancObs?.value || '').trim();

        if (!dataValue) return showLancAlert('Informe a data do lancamento.');
        if (!tipoValue) return showLancAlert('Selecione o tipo do lancamento.');
        if (!contaValue) return showLancAlert('Selecione a conta.');

        valorValue = valorValue.replace(/\s+/g, '').replace(',', '.');
        const valorFloat = Math.abs(Number(valorValue));
        if (!Number.isFinite(valorFloat)) return showLancAlert('Informe um valor valido.');

        const payload = {
            data: dataValue,
            tipo: tipoValue,
            valor: Number(valorFloat.toFixed(2)),
            descricao: descricaoValue,
            observacao: obsValue,
            conta_id: Number(contaValue),
            categoria_id: categoriaValue ? Number(categoriaValue) : null
        };

        const submitBtn = formLanc.querySelector('button[type="submit"]');
        submitBtn?.setAttribute('disabled', 'disabled');

        const token = (window.LK && typeof LK.getCSRF === 'function') ? LK.getCSRF() : '';

        try {
            const res = await fetch(`${ENDPOINT}/${encodeURIComponent(editingLancamentoId)}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            });
            const json = await res.json().catch(() => null);
            if (!res.ok || (json && json.status !== 'success')) {
                const msg = json?.message || (json?.errors ? Object.values(json.errors).join('\n') : 'Falha ao atualizar lancamento.');
                throw new Error(msg);
            }
            ensureLancModal()?.hide();
            toast('success', 'Lancamento atualizado!');
            await load();
            document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                detail: {
                    resource: 'transactions',
                    action: 'update',
                    id: Number(editingLancamentoId)
                }
            }));
        } catch (err) {
            showLancAlert(err.message || 'Falha ao atualizar lancamento.');
        } finally {
            submitBtn?.removeAttribute('disabled');
        }
    });

    btnExcluirSel && btnExcluirSel.addEventListener('click', async () => {
        const t = ensureTable();
        const rows = t ? t.getSelectedRows() : [];
        const eligibleRows = rows.filter(r => !isSaldoInicial(r.getData()));
        const ids = eligibleRows.map(r => r.getData()?.id).filter(Boolean);
        if (!ids.length) return;
        const ok = await ask(`Excluir ${ids.length} lancamento(s)?`, 'Essa acao nao pode ser desfeita.');
        if (!ok) return;

        btnExcluirSel.disabled = true;
        const done = await apiBulkDelete(ids);
        btnExcluirSel.disabled = false;

        if (done) {
            eligibleRows.forEach(r => r.delete());
            toast('Itens excluidos.');
            updateSelectionInfo();
        } else {
            toast('Alguns itens nao foram excluidos.', 'error');
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

            const t2 = ensureTable();
            if (t2) {
                t2.replaceData([]);
                updateSelectionInfo();
            }
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
        if (res === 'categorias' || res === 'accounts') loadFilterOptions();
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

































