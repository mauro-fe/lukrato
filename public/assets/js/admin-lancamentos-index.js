(() => {
    'use strict';

    // Previne inicialização dupla
    if (window.__LK_LANCAMENTOS_LOADER__) return;
    window.__LK_LANCAMENTOS_LOADER__ = true;

    // ==================== CONFIGURAÇÃO E CONSTANTES ====================
    const CONFIG = {
        BASE_URL: (window.BASE_URL || (location.pathname.includes('/public/') ?
            location.pathname.split('/public/')[0] + '/public/' : '/')).replace(/\/?$/, '/'),
        TABLE_HEIGHT: "520px",
        PAGINATION_SIZE: 25,
        PAGINATION_OPTIONS: [10, 25, 50, 100],
        DATA_LIMIT: 500,
        DEBOUNCE_DELAY: 10
    };

    CONFIG.ENDPOINT = `${CONFIG.BASE_URL}api/lancamentos`;
    CONFIG.EXPORT_ENDPOINT = `${CONFIG.ENDPOINT}/export`;

    // ==================== SELETORES DOM ====================
    const DOM = {
        // Elementos principais
        tabContainer: document.getElementById('tabLancamentos'),

        // Filtros
        selectTipo: document.getElementById('filtroTipo'),
        selectCategoria: document.getElementById('filtroCategoria'),
        selectConta: document.getElementById('filtroConta'),
        btnFiltrar: document.getElementById('btnFiltrar'),

        // Exportação
        btnExportar: document.getElementById('btnExportar'),
        exportHint: document.getElementById('exportHint'),
        inputExportStart: document.getElementById('exportStart'),
        inputExportEnd: document.getElementById('exportEnd'),
        selectExportFormat: document.getElementById('exportFormat'),

        // Seleção
        btnExcluirSel: document.getElementById('btnExcluirSel'),
        selInfo: document.getElementById('selInfo'),
        selCountSpan: document.getElementById('selCount'),

        // Modal
        modalEditLancEl: document.getElementById('modalEditarLancamento'),
        formLanc: document.getElementById('formLancamento'),
        editLancAlert: document.getElementById('editLancAlert'),
        inputLancData: document.getElementById('editLancData'),
        selectLancTipo: document.getElementById('editLancTipo'),
        selectLancConta: document.getElementById('editLancConta'),
        selectLancCategoria: document.getElementById('editLancCategoria'),
        inputLancValor: document.getElementById('editLancValor'),
        inputLancDescricao: document.getElementById('editLancDescricao'),
        inputLancObs: document.getElementById('editLancObs')
    };

    // ==================== ESTADO DA APLICAÇÃO ====================
    const STATE = {
        table: null,
        modalEditLanc: null,
        editingLancamentoId: null,
        categoriaOptions: [],
        contaOptions: [],
        loadTimer: null
    };

    // ==================== UTILITÁRIOS ====================
    const Utils = {
        // Formatação
        fmtMoney: (n) => new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(Number(n || 0)),

        fmtDate: (iso) => {
            if (!iso) return '-';
            if (typeof iso === 'string') {
                const normalized = iso.trim();
                const datePart = normalized.includes('T') ? normalized.split('T')[0] : normalized;
                if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
                    const [year, month, day] = datePart.split('-');
                    if (year && month && day) return `${day}/${month}/${year}`;
                }
            }
            const d = new Date(iso);
            return isNaN(d) ? '-' : d.toLocaleDateString('pt-BR');
        },

        getTipoClass: (tipo) => {
            const normalized = String(tipo || '').toLowerCase();
            if (normalized.includes('receita')) return 'receita';
            if (normalized.includes('despesa')) return 'despesa';
            if (normalized.includes('transfer')) return 'transferencia';
            return '';
        },

        normalizeText: (str) => String(str ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('pt-BR'),

        escapeHtml: (value) => String(value ?? '').replace(/[&<>"']/g, (m) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m] || m)),

        // Parsing
        parseFilterNumber: (input) => {
            if (input === undefined || input === null) return null;
            const raw = String(input).trim();
            if (!raw) return null;
            const normalized = raw.replace(/\./g, '').replace(',', '.');
            const num = Number(normalized);
            return Number.isFinite(num) ? num : null;
        },

        parseFilterDate: (input) => {
            if (!input) return null;
            const raw = String(input).trim();
            if (!raw) return null;

            if (/^\d{4}-\d{1,2}-\d{1,2}$/.test(raw)) {
                const [year, month, day] = raw.split('-').map(Number);
                return Utils.normalizeFilterDate(day, month, year);
            }

            const cleaned = raw.replace(/[-.]/g, '/');
            const match = cleaned.match(/^(\d{1,2})(?:\/(\d{1,2})(?:\/(\d{2,4}))?)?$/);
            if (!match) return null;

            const day = Number(match[1]);
            const month = match[2] !== undefined ? Number(match[2]) : null;
            const year = match[3] !== undefined ? Number(match[3]) : null;
            return Utils.normalizeFilterDate(day, month, year);
        },

        normalizeFilterDate: (day, month, year) => {
            const safeDay = Number.isFinite(day) ? day : null;
            const safeMonth = Number.isFinite(month) ? month : null;
            let safeYear = Number.isFinite(year) ? year : null;

            if (safeYear !== null && safeYear < 100) safeYear += 2000;
            if (safeDay !== null && (safeDay < 1 || safeDay > 31)) return null;
            if (safeMonth !== null && (safeMonth < 1 || safeMonth > 12)) return null;
            if (safeYear !== null && (safeYear < 1900 || safeYear > 2100)) return null;

            return {
                day: safeDay,
                month: safeMonth,
                year: safeYear
            };
        },

        extractYMD: (value) => {
            if (!value) return null;
            if (value instanceof Date && !isNaN(value)) {
                return {
                    year: value.getFullYear(),
                    month: value.getMonth() + 1,
                    day: value.getDate()
                };
            }
            if (typeof value === 'string') {
                const trimmed = value.trim();
                if (!trimmed) return null;
                if (/^\d{4}-\d{2}-\d{2}/.test(trimmed)) {
                    const [y, m, d] = trimmed.slice(0, 10).split('-').map(Number);
                    return Utils.normalizeFilterDate(d, m, y);
                }
                if (/^\d{1,2}\/\d{1,2}\/\d{2,4}$/.test(trimmed)) {
                    const [d, m, y] = trimmed.split('/').map(Number);
                    return Utils.normalizeFilterDate(d, m, y);
                }
            }
            const d = new Date(value);
            if (isNaN(d)) return null;
            return {
                year: d.getFullYear(),
                month: d.getMonth() + 1,
                day: d.getDate()
            };
        },

        // Data helpers
        normalizeDataList: (payload) => {
            if (!payload) return [];
            if (Array.isArray(payload)) return payload;
            if (payload && Array.isArray(payload.data)) return payload.data;
            return [];
        },

        getTrimmedDateValue: (input) => {
            if (!input) return '';
            const value = (input.value || '').trim();
            return value && /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/.test(value) ? value : '';
        },

        parseDownloadFilename: (disposition) => {
            if (!disposition) return null;
            const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
            if (utf8Match && utf8Match[1]) return decodeURIComponent(utf8Match[1]);
            const asciiMatch = disposition.match(/filename="?([^";]+)"?/i);
            if (asciiMatch && asciiMatch[1]) return asciiMatch[1];
            return null;
        },

        // Verificações
        isSaldoInicial: (data) => {
            if (!data) return false;
            const tipo = String(data?.tipo || '').toLowerCase();
            const descricao = String(data?.descricao || '').toLowerCase();
            if (tipo === 'saldo_inicial' || tipo === 'saldo inicial') return true;
            return descricao.includes('saldo inicial');
        },

        isTransferencia: (data) => Boolean(data?.eh_transferencia),

        canEditLancamento: (data) => !Utils.isSaldoInicial(data) && !Utils.isTransferencia(data),

        // UI
        hasSwal: () => !!window.Swal,

        getCSRFToken: () => (window.LK && typeof LK.getCSRF === 'function') ? LK.getCSRF() : '',

        getCurrentMonth: () => (window.LukratoHeader?.getMonth?.()) || (new Date()).toISOString().slice(0, 7)
    };

    // ==================== NOTIFICAÇÕES ====================
    const Notifications = {
        ask: async (title, text = '') => {
            if (Utils.hasSwal()) {
                const r = await Swal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, confirmar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: 'var(--color-primary)',
                    cancelButtonColor: 'var(--color-text-muted)'
                });
                return r.isConfirmed;
            }
            return confirm(title || 'Confirmar ação?');
        },

        toast: (msg, icon = 'success') => {
            if (Utils.hasSwal()) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    timer: 2500,
                    showConfirmButton: false,
                    icon,
                    title: msg,
                    timerProgressBar: true
                });
            } else {
                console.log(`[${icon.toUpperCase()}] ${msg}`);
            }
        }
    };

    // ==================== API ====================
    const API = {
        fetchJsonList: async (url) => {
            try {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) return [];
                const body = await res.json().catch(() => null);
                return Utils.normalizeDataList(body);
            } catch {
                return [];
            }
        },

        fetchLancamentos: async ({
            month,
            tipo = '',
            categoria = '',
            conta = '',
            limit,
            startDate = '',
            endDate = ''
        }) => {
            const qs = API.buildQuery({
                month,
                tipo,
                categoria,
                conta,
                limit,
                startDate,
                endDate
            });
            try {
                const res = await fetch(`${CONFIG.ENDPOINT}?${qs.toString()}`, {
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
        },

        buildQuery: ({
            month,
            tipo,
            categoria,
            conta,
            limit,
            startDate,
            endDate
        }) => {
            const qs = new URLSearchParams();
            if (month) qs.set('month', month);
            if (tipo) qs.set('tipo', tipo);
            if (categoria !== undefined && categoria !== null && categoria !== '') {
                qs.set('categoria_id', categoria);
            }
            if (conta !== undefined && conta !== null && conta !== '') {
                qs.set('account_id', conta);
            }
            if (limit !== undefined && limit !== null) {
                qs.set('limit', String(limit));
            }
            if (startDate) qs.set('start_date', startDate);
            if (endDate) qs.set('end_date', endDate);
            return qs;
        },

        deleteOne: async (id) => {
            try {
                const token = Utils.getCSRFToken();
                const res = await fetch(`${CONFIG.ENDPOINT}/${encodeURIComponent(id)}`, {
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
        },

        bulkDelete: async (ids) => {
            try {
                const token = Utils.getCSRFToken();
                const payload = {
                    ids,
                    _token: token,
                    csrf_token: token
                };
                const res = await fetch(`${CONFIG.ENDPOINT}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(payload)
                });
                if (res.ok) return true;
            } catch { }

            // Fallback: deletar um por um
            const results = await Promise.all(ids.map(API.deleteOne));
            return results.every(Boolean);
        },

        updateLancamento: async (id, payload) => {
            const token = Utils.getCSRFToken();
            const res = await fetch(`${CONFIG.ENDPOINT}/${encodeURIComponent(id)}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            });
            return res;
        },

        exportLancamentos: async (params, format) => {
            const qs = API.buildQuery(params);
            qs.set('format', format);

            const res = await fetch(`${CONFIG.EXPORT_ENDPOINT}?${qs.toString()}`, {
                credentials: 'include'
            });

            if (!res.ok) {
                let message = 'Falha ao exportar lançamentos.';
                const maybeJson = await res.json().catch(() => null);
                if (maybeJson?.message) message = maybeJson.message;
                throw new Error(message);
            }

            return res;
        }
    };

    // ==================== GERENCIAMENTO DE OPÇÕES ====================
    const OptionsManager = {
        populateCategoriaSelect: (select, tipo, selectedId) => {
            if (!select) return;
            const normalized = (tipo || '').toLowerCase();
            const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';

            select.innerHTML = '<option value="">Sem categoria</option>';

            const items = STATE.categoriaOptions.filter((item) => {
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
                fallback.textContent = 'Categoria indisponível';
                fallback.selected = true;
                select.appendChild(fallback);
            }
        },

        populateContaSelect: (select, selectedId) => {
            if (!select) return;
            const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';

            select.innerHTML = '<option value="">Selecione</option>';

            STATE.contaOptions.forEach((item) => {
                const opt = document.createElement('option');
                opt.value = String(item.id);
                opt.textContent = item.label;
                if (currentValue && String(item.id) === currentValue) opt.selected = true;
                select.appendChild(opt);
            });

            if (currentValue && select.value !== currentValue) {
                const fallback = document.createElement('option');
                fallback.value = currentValue;
                fallback.textContent = 'Conta indisponível';
                fallback.selected = true;
                select.appendChild(fallback);
            }
        },

        loadFilterOptions: async () => {
            const categoriaPromise = DOM.selectCategoria ?
                API.fetchJsonList(`${CONFIG.BASE_URL}api/categorias`) :
                Promise.resolve([]);

            const contaPromise = DOM.selectConta ?
                API.fetchJsonList(`${CONFIG.BASE_URL}api/accounts?only_active=1`) :
                Promise.resolve([]);

            // Preparar seletores com opções padrão
            if (DOM.selectCategoria) {
                DOM.selectCategoria.innerHTML =
                    '<option value="">Todas as categorias</option><option value="none">Sem categoria</option>';
            }
            if (DOM.selectConta) {
                DOM.selectConta.innerHTML = '<option value="">Todas as contas</option>';
            }

            const [categorias, contas] = await Promise.all([categoriaPromise, contaPromise]);

            // Processar categorias
            if (DOM.selectCategoria && categorias.length) {
                STATE.categoriaOptions = categorias
                    .map((cat) => ({
                        id: Number(cat?.id ?? 0),
                        nome: String(cat?.nome ?? '').trim(),
                        tipo: String(cat?.tipo ?? '').trim().toLowerCase()
                    }))
                    .filter((cat) => Number.isFinite(cat.id) && cat.id > 0 && cat.nome)
                    .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', {
                        sensitivity: 'base'
                    }));

                const options = STATE.categoriaOptions
                    .map((cat) => `<option value="${cat.id}">${Utils.escapeHtml(cat.nome)}</option>`)
                    .join('');
                DOM.selectCategoria.insertAdjacentHTML('beforeend', options);
            }

            // Processar contas
            if (DOM.selectConta && contas.length) {
                STATE.contaOptions = contas
                    .map((acc) => {
                        const id = Number(acc?.id ?? 0);
                        const nome = String(acc?.nome ?? '').trim();
                        const instituicao = String(acc?.instituicao ?? '').trim();
                        const label = nome || instituicao;
                        return {
                            id,
                            label: label || `Conta #${id}`
                        };
                    })
                    .filter((acc) => Number.isFinite(acc.id) && acc.id > 0 && acc.label)
                    .sort((a, b) => a.label.localeCompare(b.label, 'pt-BR', {
                        sensitivity: 'base'
                    }));

                const options = STATE.contaOptions
                    .map((acc) => `<option value="${acc.id}">${Utils.escapeHtml(acc.label)}</option>`)
                    .join('');
                DOM.selectConta.insertAdjacentHTML('beforeend', options);
            }

            // Atualizar selects do modal se existirem
            if (DOM.selectLancConta) {
                OptionsManager.populateContaSelect(DOM.selectLancConta, DOM.selectLancConta.value || null);
            }
            if (DOM.selectLancCategoria) {
                OptionsManager.populateCategoriaSelect(
                    DOM.selectLancCategoria,
                    DOM.selectLancTipo?.value || '',
                    DOM.selectLancCategoria.value || null
                );
            }
        }
    };

    // ==================== GERENCIAMENTO DE TABELA ====================
    const TableManager = {
        waitForTableReady: (instance) => {
            if (!instance) return Promise.resolve();
            if (instance.__lkReadyResolved || !instance.__lkReadyPromise) {
                return Promise.resolve();
            }
            return instance.__lkReadyPromise;
        },

        tableIsActive: (instance) => {
            if (!instance) return false;
            if (instance.__lkInitializing) return true;
            const rm = instance.rowManager;
            if (!rm || !rm.renderer) return false;
            if (instance.element && instance.element.isConnected === false) return false;
            return true;
        },

        buildColumns: () => [{
            formatter: "rowSelection",
            titleFormatter: "rowSelection",
            hozAlign: "center",
            headerSort: false,
            width: 44,
            cellClick: (e, cell) => {
                const data = cell.getRow().getData();
                if (Utils.isSaldoInicial(data)) {
                    e.preventDefault();
                    cell.getRow().deselect();
                }
            },
            cellRendered: (cell) => {
                const data = cell.getRow().getData();
                if (Utils.isSaldoInicial(data)) {
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
            formatter: (cell) => Utils.fmtDate(cell.getValue()),
            headerFilterFunc: (headerValue, rowValue) => {
                const filter = Utils.parseFilterDate(headerValue);
                if (!filter) return true;
                const value = Utils.extractYMD(rowValue);
                if (!value) return false;
                if (filter.year !== null && value.year !== filter.year) return false;
                if (filter.month !== null && value.month !== filter.month) return false;
                if (filter.day !== null && value.day !== filter.day) return false;
                return true;
            },
            headerFilter: "input",
            headerFilterPlaceholder: "Filtrar data"
        },
        {
              title: "Tipo",
              field: "tipo",
              width: 120,
              formatter: (cell) => {
                  const raw = String(cell.getValue() || '-');
                  const tipoClass = Utils.getTipoClass(raw);
                  const label = raw.charAt(0).toUpperCase() + raw.slice(1);
                  return `<span class="badge-tipo ${tipoClass}">${Utils.escapeHtml(label)}</span>`;
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
            mutator: (value, data) => {
                const candidate = value ??
                    data?.categoria ??
                    data?.categoria_nome ??
                    (typeof data?.categoria === 'object' ? data?.categoria?.nome : '') ?? '';
                if (candidate && typeof candidate === 'object') {
                    return String(candidate.nome ?? candidate.label ?? candidate.title ?? '');
                }
                return candidate ? String(candidate) : '';
              },
              formatter: (cell) => {
                  const value = cell.getValue() || '-';
                  if (value === '-') return value;
                  return `<span class="categoria-chip">${Utils.escapeHtml(value)}</span>`;
              },
            headerFilter: "input",
            headerFilterPlaceholder: "Filtrar categoria"
        },
        {
            title: "Conta",
            field: "conta_nome",
            widthGrow: 1,
            mutator: (value, data) => {
                const raw = value ??
                    data?.conta ??
                    data?.conta_nome ??
                    (typeof data?.conta === 'object' ? data?.conta?.nome : '') ?? '';
                if (raw && typeof raw === 'object') {
                    return String(raw.nome ?? raw.label ?? raw.title ?? '');
                }
                return raw ? String(raw) : '';
            },
            formatter: (cell) => cell.getValue() || '-',
            headerFilter: "input",
            headerFilterPlaceholder: "Filtrar conta"
        },
        {
            title: "Descrição",
            field: "descricao",
            widthGrow: 2,
            mutator: (value, data) => {
                let raw = value ??
                    data?.descricao ??
                    data?.descricao_titulo ??
                    (typeof data?.descricao === 'object' ? data?.descricao?.texto : '') ?? '';
                if (raw && typeof raw === 'object') {
                    raw = raw.texto ?? raw.value ?? raw.title ?? '';
                }
                return raw ? String(raw).trim() : '';
            },
            formatter: (cell) => cell.getValue() || '-',
            headerFilterFunc: (headerValue, rowValue) => {
                const needle = Utils.normalizeText(headerValue);
                if (!needle) return true;
                const hay = Utils.normalizeText(rowValue);
                return hay.includes(needle);
            },
            headerFilter: "input",
            headerFilterPlaceholder: "Filtrar descrição"
        },
        {
            title: "Valor",
            field: "valor",
            hozAlign: "right",
              width: 150,
              formatter: (cell) => {
                  const tipoClass = Utils.getTipoClass(cell.getRow()?.getData()?.tipo);
                  return `<span class="valor-cell ${tipoClass}">${Utils.fmtMoney(cell.getValue())}</span>`;
              },
            headerFilterFunc: (headerValue, rowValue) => {
                const needle = Utils.parseFilterNumber(headerValue);
                if (needle === null) return true;
                const value = Number(rowValue ?? 0);
                if (!Number.isFinite(value)) return false;
                return Math.abs(value - needle) < 0.005;
            },
            headerFilter: "input",
            headerFilterPlaceholder: "Filtrar valor"
        },
        {
            title: "Ações",
            field: "actions",
            headerSort: false,
            hozAlign: "center",
            width: 120,
            formatter: (cell) => {
                const data = cell.getRow().getData();
                if (Utils.isSaldoInicial(data)) return '';
                const buttons = [];
                if (Utils.canEditLancamento(data)) {
                    buttons.push(
                        '<button class="lk-btn ghost" data-action="edit" title="Editar"><i class="fas fa-pen"></i></button>'
                    );
                }
                buttons.push(
                    '<button class="lk-btn danger" data-action="delete" title="Excluir"><i class="fas fa-trash"></i></button>'
                );
                return `<div class="lk-actions">${buttons.join('')}</div>`;
            },
            cellClick: async (e, cell) => {
                const row = cell.getRow();
                const data = row.getData();
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;

                const action = btn.getAttribute('data-action');

                if (action === 'edit') {
                    if (!Utils.canEditLancamento(data)) return;
                    ModalManager.openEditLancamento(data);
                    return;
                }

                if (action === 'delete') {
                    const id = data?.id;
                    if (!id || Utils.isSaldoInicial(data)) return;

                    const ok = await Notifications.ask(
                        'Excluir lançamento?',
                        'Esta ação não pode ser desfeita.'
                    );
                    if (!ok) return;

                    btn.disabled = true;
                    const okDel = await API.deleteOne(id);
                    btn.disabled = false;

                    if (okDel) {
                        row.delete();
                        Notifications.toast('Lançamento excluído com sucesso!');
                        TableManager.updateSelectionInfo();
                    } else {
                        Notifications.toast('Falha ao excluir lançamento.', 'error');
                    }
                }
            }
        }
        ],

        buildTable: () => {
            if (!DOM.tabContainer) return null;

            const instance = new Tabulator(DOM.tabContainer, {
                height: CONFIG.TABLE_HEIGHT,
                layout: "fitColumns",
                placeholder: "Nenhum lançamento encontrado para o período selecionado",
                selectable: true,
                index: "id",
                pagination: "local",
                paginationSize: CONFIG.PAGINATION_SIZE,
                paginationSizeSelector: CONFIG.PAGINATION_OPTIONS,
                rowFormatter: (row) => {
                    const data = row.getData();
                    row.getElement().setAttribute('data-id', data?.id ?? '');
                    if (Utils.isSaldoInicial(data)) {
                        row.getElement()?.classList.add('lk-row-inicial');
                    }
                },
                selectableCheck: (row) => !Utils.isSaldoInicial(row.getData()),
                columns: TableManager.buildColumns()
            });

            instance.on("rowSelectionChanged", (_data, rows) => {
                if (Array.isArray(rows)) {
                    rows.forEach((row) => {
                        if (Utils.isSaldoInicial(row.getData())) row.deselect();
                    });
                }
                TableManager.updateSelectionInfo();
            });

            instance.__lkInitializing = true;
            instance.__lkReadyResolved = false;
            instance.__lkReadyPromise = new Promise((resolve) => {
                const markReady = () => {
                    if (instance.__lkReadyResolved) return;
                    instance.__lkInitializing = false;
                    instance.__lkReadyResolved = true;
                    resolve();
                };
                instance.on("tableBuilt", markReady);
                if (instance.rowManager?.renderer) {
                    markReady();
                }
            });

            return instance;
        },

        ensureTable: () => {
            if (!DOM.tabContainer) return null;
            if (!TableManager.tableIsActive(STATE.table)) {
                try {
                    if (STATE.table && typeof STATE.table.destroy === 'function') {
                        STATE.table.destroy();
                    }
                } catch (_) { }
                STATE.table = TableManager.buildTable();
            }
            return STATE.table;
        },

        renderRows: async (items) => {
            const grid = TableManager.ensureTable();
            if (!grid) return;
            await TableManager.waitForTableReady(grid);
            grid.setData(Array.isArray(items) ? items : []);
            TableManager.updateSelectionInfo();
        },

        updateSelectionInfo: () => {
            const t = TableManager.ensureTable();
            if (!t) {
                if (DOM.selCountSpan) DOM.selCountSpan.textContent = '0';
                DOM.btnExcluirSel?.setAttribute('disabled', 'disabled');
                return;
            }

            if (!t.__lkReadyResolved) {
                TableManager.waitForTableReady(t).then(() => TableManager.updateSelectionInfo());
                return;
            }

            const selected = t.getSelectedRows().filter(row => !Utils.isSaldoInicial(row.getData()));
            const count = selected.length;

            if (DOM.selCountSpan) DOM.selCountSpan.textContent = String(count);
            if (DOM.btnExcluirSel) {
                DOM.btnExcluirSel.toggleAttribute("disabled", count === 0);
            }
        }
    };

    // ==================== GERENCIAMENTO DE MODAL ====================
    const ModalManager = {
        ensureLancModal: () => {
            if (STATE.modalEditLanc) return STATE.modalEditLanc;
            if (!DOM.modalEditLancEl) return null;

            if (window.bootstrap?.Modal) {
                if (DOM.modalEditLancEl.parentElement && DOM.modalEditLancEl.parentElement !== document
                    .body) {
                    document.body.appendChild(DOM.modalEditLancEl);
                }
                STATE.modalEditLanc = window.bootstrap.Modal.getOrCreateInstance(DOM.modalEditLancEl);
                return STATE.modalEditLanc;
            }
            return null;
        },

        clearLancAlert: () => {
            if (!DOM.editLancAlert) return;
            DOM.editLancAlert.classList.add('d-none');
            DOM.editLancAlert.textContent = '';
        },

        showLancAlert: (msg) => {
            if (!DOM.editLancAlert) return;
            DOM.editLancAlert.textContent = msg;
            DOM.editLancAlert.classList.remove('d-none');
        },

        openEditLancamento: (data) => {
            const modal = ModalManager.ensureLancModal();
            if (!modal || !Utils.canEditLancamento(data)) return;

            STATE.editingLancamentoId = data?.id ?? null;
            if (!STATE.editingLancamentoId) return;

            ModalManager.clearLancAlert();

            // Preencher campos
            if (DOM.inputLancData) {
                DOM.inputLancData.value = (data?.data || '').slice(0, 10);
            }

            if (DOM.selectLancTipo) {
                const tipo = String(data?.tipo || '').toLowerCase();
                DOM.selectLancTipo.value = ["receita", "despesa"].includes(tipo) ? tipo : 'despesa';
            }

            OptionsManager.populateContaSelect(DOM.selectLancConta, data?.conta_id ?? null);
            OptionsManager.populateCategoriaSelect(
                DOM.selectLancCategoria,
                DOM.selectLancTipo?.value || '',
                data?.categoria_id ?? null
            );

            if (DOM.inputLancValor) {
                const valor = Math.abs(Number(data?.valor ?? 0));
                DOM.inputLancValor.value = Number.isFinite(valor) ? valor.toFixed(2) : '';
            }

            if (DOM.inputLancDescricao) {
                DOM.inputLancDescricao.value = data?.descricao || '';
            }

            if (DOM.inputLancObs) {
                DOM.inputLancObs.value = data?.observacao || '';
            }

            modal.show();
        },

        submitEditForm: async (ev) => {
            ev.preventDefault();
            if (!STATE.editingLancamentoId) return;

            ModalManager.clearLancAlert();

            // Validar e coletar dados
            const dataValue = DOM.inputLancData?.value || '';
            const tipoValue = DOM.selectLancTipo?.value || '';
            const contaValue = DOM.selectLancConta?.value || '';
            const categoriaValue = DOM.selectLancCategoria?.value || '';
            let valorValue = DOM.inputLancValor?.value || '';
            const descricaoValue = (DOM.inputLancDescricao?.value || '').trim();
            const obsValue = (DOM.inputLancObs?.value || '').trim();

            if (!dataValue) return ModalManager.showLancAlert('Informe a data do lançamento.');
            if (!tipoValue) return ModalManager.showLancAlert('Selecione o tipo do lançamento.');
            if (!contaValue) return ModalManager.showLancAlert('Selecione a conta.');

            valorValue = valorValue.replace(/\s+/g, '').replace(',', '.');
            const valorFloat = Math.abs(Number(valorValue));
            if (!Number.isFinite(valorFloat)) {
                return ModalManager.showLancAlert('Informe um valor válido.');
            }

            const payload = {
                data: dataValue,
                tipo: tipoValue,
                valor: Number(valorFloat.toFixed(2)),
                descricao: descricaoValue,
                observacao: obsValue,
                conta_id: Number(contaValue),
                categoria_id: categoriaValue ? Number(categoriaValue) : null
            };

            const submitBtn = DOM.formLanc.querySelector('button[type="submit"]');
            submitBtn?.setAttribute('disabled', 'disabled');

            try {
                const res = await API.updateLancamento(STATE.editingLancamentoId, payload);
                const json = await res.json().catch(() => null);

                if (!res.ok || (json && json.status !== 'success')) {
                    const msg = json?.message ||
                        (json?.errors ? Object.values(json.errors).join('\n') :
                            'Falha ao atualizar lançamento.');
                    throw new Error(msg);
                }

                ModalManager.ensureLancModal()?.hide();
                Notifications.toast('Lançamento atualizado com sucesso!');
                await DataManager.load();

                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'transactions',
                        action: 'update',
                        id: Number(STATE.editingLancamentoId)
                    }
                }));
            } catch (err) {
                ModalManager.showLancAlert(err.message || 'Falha ao atualizar lançamento.');
            } finally {
                submitBtn?.removeAttribute('disabled');
            }
        }
    };

    // ==================== GERENCIAMENTO DE EXPORTAÇÃO ====================
    const ExportManager = {
        initDefaults: () => {
            const inputs = [DOM.inputExportStart, DOM.inputExportEnd].filter(Boolean);
            if (!inputs.length) return;

            const now = new Date();
            const isoToday = now.toISOString().slice(0, 10);

            inputs.forEach((input) => {
                if (input.dataset.defaultToday === '1' && !input.value) {
                    input.value = isoToday;
                    input.dataset.autofilled = '1';
                }
            });

            if (DOM.exportHint) {
                const label = now.toLocaleDateString('pt-BR');
                DOM.exportHint.textContent = `Por padrão exportamos ${label}.`;
            }
        },

        setLoading: (isLoading) => {
            if (!DOM.btnExportar) return;
            DOM.btnExportar.disabled = isLoading;
            DOM.btnExportar.innerHTML = isLoading ?
                '<i class="fas fa-circle-notch fa-spin"></i> Exportando...' :
                '<i class="fas fa-file-export"></i> Exportar';
        },

        export: async (forcedFormat) => {
            const month = Utils.getCurrentMonth();
            const tipo = DOM.selectTipo ? DOM.selectTipo.value : '';
            const categoria = DOM.selectCategoria ? DOM.selectCategoria.value : '';
            const conta = DOM.selectConta ? DOM.selectConta.value : '';
            const startDate = Utils.getTrimmedDateValue(DOM.inputExportStart);
            const endDate = Utils.getTrimmedDateValue(DOM.inputExportEnd);

            // Validações
            if ((startDate && !endDate) || (!startDate && endDate)) {
                Notifications.toast('Informe tanto a data inicial quanto final para exportar.', 'error');
                return;
            }

            if (startDate && endDate && endDate < startDate) {
                Notifications.toast('A data final deve ser posterior ou igual à inicial.', 'error');
                return;
            }

            const format = forcedFormat ||
                (DOM.selectExportFormat ? (DOM.selectExportFormat.value || 'excel') : 'excel');

            ExportManager.setLoading(true);

            try {
                const res = await API.exportLancamentos({
                    month,
                    tipo,
                    categoria,
                    conta,
                    startDate,
                    endDate
                },
                    format
                );

                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const disposition = res.headers.get('Content-Disposition');
                const suffixDate = startDate && endDate ?
                    `${startDate}_a_${endDate}` :
                    (month || 'periodo');
                const fallback = `lancamentos-${suffixDate}.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
                const filename = Utils.parseDownloadFilename(disposition) || fallback;

                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                Notifications.toast('Exportação concluída com sucesso!');
            } catch (err) {
                console.error(err);
                Notifications.toast(err?.message || 'Falha ao exportar lançamentos.', 'error');
            } finally {
                ExportManager.setLoading(false);
            }
        }
    };

    // ==================== GERENCIAMENTO DE DADOS ====================
    const DataManager = {
        load: async () => {
            clearTimeout(STATE.loadTimer);
            STATE.loadTimer = setTimeout(async () => {
                const month = Utils.getCurrentMonth();
                const tipo = DOM.selectTipo ? DOM.selectTipo.value : '';
                const categoria = DOM.selectCategoria ? DOM.selectCategoria.value : '';
                const conta = DOM.selectConta ? DOM.selectConta.value : '';

                const t2 = TableManager.ensureTable();
                if (t2) {
                    await TableManager.waitForTableReady(t2);
                    t2.replaceData([]);
                    TableManager.updateSelectionInfo();
                }

                const items = await API.fetchLancamentos({
                    month,
                    tipo,
                    categoria,
                    conta,
                    limit: CONFIG.DATA_LIMIT
                });

                await TableManager.renderRows(items);
            }, CONFIG.DEBOUNCE_DELAY);
        },

        bulkDelete: async () => {
            const t = TableManager.ensureTable();
            const rows = t ? t.getSelectedRows() : [];
            const eligibleRows = rows.filter(r => !Utils.isSaldoInicial(r.getData()));
            const ids = eligibleRows.map(r => r.getData()?.id).filter(Boolean);

            if (!ids.length) return;

            const ok = await Notifications.ask(
                `Excluir ${ids.length} lançamento(s)?`,
                'Esta ação não pode ser desfeita.'
            );
            if (!ok) return;

            DOM.btnExcluirSel.disabled = true;
            const done = await API.bulkDelete(ids);
            DOM.btnExcluirSel.disabled = false;

            if (done) {
                eligibleRows.forEach(r => r.delete());
                Notifications.toast('Lançamentos excluídos com sucesso!');
                TableManager.updateSelectionInfo();
            } else {
                Notifications.toast('Alguns itens não foram excluídos.', 'error');
            }
        }
    };

    // ==================== EVENT LISTENERS ====================
    const EventListeners = {
        init: () => {
            // Tipo de lançamento mudou - atualizar categorias
            DOM.selectLancTipo?.addEventListener('change', () => {
                OptionsManager.populateCategoriaSelect(
                    DOM.selectLancCategoria,
                    DOM.selectLancTipo.value,
                    DOM.selectLancCategoria?.value || ''
                );
            });

            // Modal fechou - limpar dados
            DOM.modalEditLancEl?.addEventListener('hidden.bs.modal', () => {
                STATE.editingLancamentoId = null;
                DOM.formLanc?.reset?.();
                ModalManager.clearLancAlert();
            });

            // Submit do formulário de edição
            DOM.formLanc?.addEventListener('submit', ModalManager.submitEditForm);

            // Botão de filtrar
            DOM.btnFiltrar?.addEventListener('click', DataManager.load);

            // Botão de exportar
            DOM.btnExportar?.addEventListener('click', () => ExportManager.export());

            // Botão de excluir selecionados
            DOM.btnExcluirSel?.addEventListener('click', DataManager.bulkDelete);

            // Eventos globais do sistema
            document.addEventListener('lukrato:month-changed', () => DataManager.load());
            document.addEventListener('lukrato:export-click', () => ExportManager.export());

            document.addEventListener('lukrato:data-changed', (e) => {
                const res = e.detail?.resource;
                if (!res || res === 'transactions') DataManager.load();
                if (res === 'categorias' || res === 'accounts') OptionsManager.loadFilterOptions();
            });
        }
    };

    // ==================== INICIALIZAÇÃO ====================
    const init = async () => {
        console.log('🚀 Inicializando Sistema de Lançamentos...');

        // Inicializar componentes
        ExportManager.initDefaults();
        EventListeners.init();

        // Carregar dados iniciais
        await OptionsManager.loadFilterOptions();
        await DataManager.load();

        console.log('✅ Sistema de Lançamentos carregado com sucesso!');
    };

    // Expor funções globais necessárias
    window.refreshLancamentos = DataManager.load;

    // Iniciar aplicação
    init();
})();
