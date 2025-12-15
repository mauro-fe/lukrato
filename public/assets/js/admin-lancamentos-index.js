/**
 * ============================================================================
 * SISTEMA DE GERENCIAMENTO DE LANÃ‡AMENTOS
 * ============================================================================
 * Gerencia listagem, filtros, ediÃ§Ã£o e exportação de lançamentos financeiros
 * Utiliza Tabulator.js para renderização da tabela
 * ============================================================================
 */

(() => {
    'use strict';

    // Previne inicialização dupla
    if (window.__LK_LANCAMENTOS_LOADER__) return;
    window.__LK_LANCAMENTOS_LOADER__ = true;

    // ============================================================================
    // CONFIGURAÃ‡ÃƒO
    // ============================================================================

    const CONFIG = {
        BASE_URL: (window.BASE_URL || (location.pathname.includes('/public/') ?
            location.pathname.split('/public/')[0] + '/public/' : '/')).replace(/\/?$/, '/'),
        TABLE_HEIGHT: '520px',
        PAGINATION_SIZE: 25,
        PAGINATION_OPTIONS: [10, 25, 50, 100],
        DATA_LIMIT: 500,
        DEBOUNCE_DELAY: 10
    };

    CONFIG.ENDPOINT = `${CONFIG.BASE_URL}api/lancamentos`;
    CONFIG.EXPORT_ENDPOINT = `${CONFIG.ENDPOINT}/export`;

    // ============================================================================
    // SELETORES DOM
    // ============================================================================

    const DOM = {

        // Tabela
        tabContainer: document.getElementById('lancamentosTable'),
        // Cards (mobile)
        lanCards: document.getElementById('lanCards'),

        // Pager (mobile)
        lanPager: document.getElementById('lanCardsPager'),
        lanPagerFirst: document.getElementById('lanPagerFirst'),
        lanPagerPrev: document.getElementById('lanPagerPrev'),
        lanPagerNext: document.getElementById('lanPagerNext'),
        lanPagerLast: document.getElementById('lanPagerLast'),
        lanPagerInfo: document.getElementById('lanPagerInfo'),
        // Filtros
        selectTipo: document.getElementById('filtroTipo'),
        selectCategoria: document.getElementById('filtroCategoria'),
        selectConta: document.getElementById('filtroConta'),
        btnFiltrar: document.getElementById('btnFiltrar'),

        // Exportação
        btnExportar: document.getElementById('btnExportar'),
        inputExportStart: document.getElementById('exportStart'),
        inputExportEnd: document.getElementById('exportEnd'),
        selectExportFormat: document.getElementById('exportFormat'),

        // SeleÃ§Ã£o e exclusÃ£o
        btnExcluirSel: document.getElementById('btnExcluirSel'),
        selCountSpan: document.getElementById('selCount'),

        // Modal de ediÃ§Ã£o
        modalEditLancEl: document.getElementById('modalEditarLancamento'),
        formLanc: document.getElementById('formLancamento'),
        editLancAlert: document.getElementById('editLancAlert'),
        inputLancData: document.getElementById('editLancData'),
        selectLancTipo: document.getElementById('editLancTipo'),
        selectLancConta: document.getElementById('editLancConta'),
        selectLancCategoria: document.getElementById('editLancCategoria'),
        inputLancValor: document.getElementById('editLancValor'),
        inputLancDescricao: document.getElementById('editLancDescricao')


    };

    // ============================================================================
    // ESTADO GLOBAL
    // ============================================================================

    const STATE = {
        table: null,
        modalEditLanc: null,
        editingLancamentoId: null,
        categoriaOptions: [],
        contaOptions: [],
        loadTimer: null
    };

    // ============================================================================
    // UTILITÃRIOS
    // ============================================================================

    const Utils = {
        // ---------- Formatação ----------
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

        escapeHtml: (value) => String(value ?? '')
            .replace(/[&<>"']/g, (m) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[m] || m)),

        normalizeText: (str) => String(str ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase(),

        // ---------- Classificação de tipos ----------
        getTipoClass: (tipo) => {
            const normalized = String(tipo || '').toLowerCase();
            if (normalized.includes('receita')) return 'receita';
            if (normalized.includes('despesa')) return 'despesa';
            if (normalized.includes('transfer')) return 'transferencia';
            return '';
        },

        isSaldoInicial: (data) => {
            if (!data) return false;
            const tipo = String(data?.tipo || '').toLowerCase();
            const descricao = String(data?.descricao || '').toLowerCase();
            return tipo === 'saldo_inicial' || tipo === 'saldo inicial' || descricao.includes('saldo inicial');
        },

        isTransferencia: (data) => Boolean(data?.eh_transferencia),

        canEditLancamento: (data) => !Utils.isSaldoInicial(data) && !Utils.isTransferencia(data),

        // ---------- Parsing de filtros ----------
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

            // Formato ISO: YYYY-MM-DD
            if (/^\d{4}-\d{1,2}-\d{1,2}$/.test(raw)) {
                const [year, month, day] = raw.split('-').map(Number);
                return Utils.normalizeFilterDate(day, month, year);
            }

            // Formato BR: DD/MM/YYYY
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

            return { day: safeDay, month: safeMonth, year: safeYear };
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

        // ---------- Helpers diversos ----------
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

        hasSwal: () => !!window.Swal,

        getCSRFToken: () => (window.LK && typeof LK.getCSRF === 'function') ? LK.getCSRF() : '',

        getCurrentMonth: () => (window.LukratoHeader?.getMonth?.()) || (new Date()).toISOString().slice(0, 7)
    };

    // ============================================================================
    // NOTIFICAÃ‡Ã•ES
    // ============================================================================

    // ============================================================================
    // MÁSCARA DE MOEDA
    // ============================================================================

    const MoneyMask = (() => {
        const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

        const format = (value) => {
            const num = Number(value);
            return Number.isFinite(num) ? formatter.format(num) : '';
        };

        const unformat = (value) => {
            const normalized = String(value || '')
                .replace(/\s|[R$]/g, '')
                .replace(/\./g, '')
                .replace(',', '.');
            const num = Number(normalized);
            return Number.isFinite(num) ? num : 0;
        };

        const bind = (input) => {
            if (!input) return;

            const onInput = (e) => {
                const digits = String(e.target.value || '').replace(/[^\d]/g, '');
                const num = Number(digits || '0') / 100;
                e.target.value = format(num);
            };

            input.addEventListener('input', onInput, { passive: true });
            input.addEventListener('focus', () => {
                if (!input.value) input.value = format(0);
            });
        };

        return { format, unformat, bind };
    })();

    // ============================================================================
    // NOTIFICACOES
    // ============================================================================

    const Notifications = {
        ask: async (title, text = '') => {
            if (Utils.hasSwal()) {
                const result = await Swal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, confirmar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: 'var(--color-primary)',
                    cancelButtonColor: 'var(--color-text-muted)'
                });
                return result.isConfirmed;
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

    // ============================================================================
    // API
    // ============================================================================

    const API = {
        fetchJsonList: async (url) => {
            try {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return [];
                const body = await res.json().catch(() => null);
                return Utils.normalizeDataList(body);
            } catch {
                return [];
            }
        },

        fetchLancamentos: async ({ month, tipo = '', categoria = '', conta = '', limit, startDate = '', endDate = '' }) => {
            const qs = API.buildQuery({ month, tipo, categoria, conta, limit, startDate, endDate });

            try {
                const res = await fetch(`${CONFIG.ENDPOINT}?${qs.toString()}`, {
                    headers: { 'Accept': 'application/json' }
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

        buildQuery: ({ month, tipo, categoria, conta, limit, startDate, endDate }) => {
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

            // Fallback: deletar individualmente
            const results = await Promise.all(ids.map(API.deleteOne));
            return results.every(Boolean);
        },

        updateLancamento: async (id, payload) => {
            const token = Utils.getCSRFToken();
            return fetch(`${CONFIG.ENDPOINT}/${encodeURIComponent(id)}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            });
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

    // ============================================================================
    // GERENCIAMENTO DE OPÃ‡Ã•ES (CATEGORIAS E CONTAS)
    // ============================================================================

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
                fallback.textContent = 'Categoria indisponÃ­vel';
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
                fallback.textContent = 'Conta indisponÃ­vel';
                fallback.selected = true;
                select.appendChild(fallback);
            }
        },

        loadFilterOptions: async () => {
            const [categorias, contas] = await Promise.all([
                DOM.selectCategoria ? API.fetchJsonList(`${CONFIG.BASE_URL}api/categorias`) : Promise.resolve([]),
                DOM.selectConta ? API.fetchJsonList(`${CONFIG.BASE_URL}api/accounts?only_active=1`) : Promise.resolve([])
            ]);

            // Inicializar seletores de filtro
            if (DOM.selectCategoria) {
                DOM.selectCategoria.innerHTML = '<option value="">Todas as categorias</option><option value="none">Sem categoria</option>';
            }
            if (DOM.selectConta) {
                DOM.selectConta.innerHTML = '<option value="">Todas as contas</option>';
            }

            // Processar categorias
            if (DOM.selectCategoria && categorias.length) {
                STATE.categoriaOptions = categorias
                    .map((cat) => ({
                        id: Number(cat?.id ?? 0),
                        nome: String(cat?.nome ?? '').trim(),
                        tipo: String(cat?.tipo ?? '').trim().toLowerCase()
                    }))
                    .filter((cat) => Number.isFinite(cat.id) && cat.id > 0 && cat.nome)
                    .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', { sensitivity: 'base' }));

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
                        const label = nome || instituicao || `Conta #${id}`;
                        return { id, label };
                    })
                    .filter((acc) => Number.isFinite(acc.id) && acc.id > 0 && acc.label)
                    .sort((a, b) => a.label.localeCompare(b.label, 'pt-BR', { sensitivity: 'base' }));

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

    // ============================================================================
    // GERENCIAMENTO DE TABELA (TABULATOR) - DESKTOP
    // ============================================================================

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

        buildColumns: () => [
            {
                formatter: 'rowSelection',
                titleFormatter: 'rowSelection',
                hozAlign: 'center',
                headerSort: false,
                width: 44,
                minWidth: 44,
                responsive: 5,
                cellClick: (e, cell) => {
                    const data = cell.getRow().getData();
                    if (Utils.isSaldoInicial(data)) {
                        e.preventDefault();
                        cell.getRow().deselect();
                    }
                }
            },
            {
                title: 'Data',
                field: 'data',
                sorter: 'date',
                hozAlign: 'left',
                width: 130,
                minWidth: 90,
                responsive: 0,
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
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filtrar data'
            },
            {
                title: 'Tipo',
                field: 'tipo',
                width: 150,
                hozAlign: 'center',
                minWidth: 90,
                responsive: 0,
                formatter: (cell) => {
                    const raw = String(cell.getValue() || '-');
                    const tipoClass = Utils.getTipoClass(raw);
                    const label = raw.charAt(0).toUpperCase() + raw.slice(1);
                    return `<span class="badge-tipo ${tipoClass}">${Utils.escapeHtml(label)}</span>`;
                },
                headerFilter: (cell, onRendered, success) => {
                    const select = document.createElement('select');
                    select.innerHTML = `
                        <option value="">Todos</option>
                        <option value="receita">Receitas</option>
                        <option value="despesa">Despesas</option>
                    `;
                    select.addEventListener('change', () => success(select.value));
                    onRendered(() => {
                        const current = typeof cell.getHeaderFilterValue === 'function' ?
                            (cell.getHeaderFilterValue() || '') : '';
                        select.value = current;
                    });
                    return select;
                }
            },
            {
                title: 'Categoria',
                field: 'categoria_nome',
                hozAlign: 'center',
                widthGrow: 1,
                minWidth: 160,
                responsive: 2,
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
                    return `<span>${Utils.escapeHtml(value)}</span>`;
                },
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filtrar categoria'
            },
            {
                title: 'Conta',
                field: 'conta_nome',
                hozAlign: 'center',
                width: 150,
                minWidth: 140,
                responsive: 2,

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
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filtrar conta'
            },
            {
                title: 'Descrição',
                field: 'descricao',
                hozAlign: 'center',
                widthGrow: 2,
                minWidth: 180,
                responsive: 3,
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
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filtrar Descrição'
            },
            {
                title: 'Valor',
                field: 'valor',
                hozAlign: 'center',
                width: 150,
                minWidth: 90,
                responsive: 0,
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
                headerFilter: 'input',
                headerFilterPlaceholder: 'Filtrar valor'
            },
            {
                title: 'Ações',
                field: 'actions',
                headerSort: false,
                hozAlign: 'center',
                width: 150,
                minWidth: 90,
                responsive: 0,
                formatter: (cell) => {
                    const data = cell.getRow().getData();
                    if (Utils.isSaldoInicial(data)) return '';

                    const buttons = [];
                    if (Utils.canEditLancamento(data)) {
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
                            Notifications.toast('lançamento excluÃ­do com sucesso!');
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
                layout: 'fitColumns',
                responsiveLayout: 'collapse',
                responsiveLayoutCollapseStartOpen: false,
                responsiveLayoutCollapseFormatter: (data) => {
                    const categoria = Utils.escapeHtml(
                        data?.categoria_nome ??
                        (typeof data?.categoria === 'object' ? data?.categoria?.nome : data?.categoria) ??
                        '-'
                    ) || '-';

                    const conta = Utils.escapeHtml(
                        data?.conta_nome ??
                        (typeof data?.conta === 'object' ? data?.conta?.nome : data?.conta) ??
                        '-'
                    ) || '-';

                    const descRaw = data?.descricao ??
                        data?.descricao_titulo ??
                        (typeof data?.descricao === 'object' ? data?.descricao?.texto : '') ??
                        '';
                    const descricao = Utils.escapeHtml(String(descRaw || '--'));

                    const container = document.createElement('div');
                    container.className = 'lk-collapse-details';

                    const makeRow = (label, value) => {
                        const row = document.createElement('div');
                        row.className = 'lk-collapse-row';

                        const labelEl = document.createElement('span');
                        labelEl.className = 'lk-label';
                        labelEl.textContent = label;

                        const valueEl = document.createElement('span');
                        valueEl.className = 'lk-value';
                        valueEl.innerHTML = value || '-';

                        row.appendChild(labelEl);
                        row.appendChild(valueEl);
                        return row;
                    };

                    container.appendChild(makeRow('Categoria', categoria));
                    container.appendChild(makeRow('Conta', conta));
                    container.appendChild(makeRow('Descrição', descricao || '--'));

                    return container;
                },
                placeholder: 'Nenhum lançamento encontrado para o perÃ­odo selecionado',
                selectable: true,
                index: 'id',
                pagination: 'local',
                paginationSize: CONFIG.PAGINATION_SIZE,
                paginationSizeSelector: CONFIG.PAGINATION_OPTIONS,
                rowFormatter: (row) => {
                    const data = row.getData();
                    row.getElement().setAttribute('data-id', data?.id ?? '');
                    if (Utils.isSaldoInicial(data)) {
                        row.getElement()?.classList.add('lk-row-inicial');
                        const firstCell = row.getCells()?.[0];
                        firstCell?.getElement()?.classList.add('lk-cell-select-disabled');
                    }
                },
                selectableCheck: (row) => !Utils.isSaldoInicial(row.getData()),
                columns: TableManager.buildColumns()
            });

            instance.on('rowSelectionChanged', (_data, rows) => {
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
                instance.on('tableBuilt', markReady);
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
                DOM.btnExcluirSel.toggleAttribute('disabled', count === 0);
            }
        }
    };

    // ============================================================================
    // MOBILE CARDS (substitui Tabulator no mobile)
    // ============================================================================

    const MobileCards = {
        cache: [],
        pageSize: 8,
        currentPage: 1,
        sortField: 'data',
        sortDir: 'desc',

        setItems(items) {
            this.cache = Array.isArray(items) ? items : [];
            this.currentPage = 1;
            this.renderPage();
        },

        getPagedData() {
            // Remove saldo inicial dos cards
            const base = this.cache.filter(item => !Utils.isSaldoInicial(item));

            // Ordenação
            let data = [...base];
            if (this.sortField === 'data') {
                data.sort((a, b) => {
                    const da = Utils.extractYMD(a.data || a.created_at) || {};
                    const db = Utils.extractYMD(b.data || b.created_at) || {};

                    const ka = (da.year || 0) * 10000 + (da.month || 0) * 100 + (da.day || 0);
                    const kb = (db.year || 0) * 10000 + (db.month || 0) * 100 + (db.day || 0);

                    return this.sortDir === 'asc' ? (ka - kb) : (kb - ka);
                });
            } else if (this.sortField === 'tipo') {
                data.sort((a, b) => {
                    const ta = (a.tipo || '').toString().toLowerCase();
                    const tb = (b.tipo || '').toString().toLowerCase();

                    if (this.sortDir === 'asc') {
                        return ta.localeCompare(tb);
                    } else {
                        return tb.localeCompare(ta);
                    }
                });
            } else if (this.sortField === 'valor') {
                data.sort((a, b) => {
                    const va = Number(a.valor || 0);
                    const vb = Number(b.valor || 0);
                    return this.sortDir === 'asc' ? (va - vb) : (vb - va);
                });
            }


            const total = data.length;
            const totalPages = Math.max(1, Math.ceil(total / this.pageSize));
            const page = Math.min(this.currentPage, totalPages);

            const start = (page - 1) * this.pageSize;
            const end = start + this.pageSize;

            return {
                list: data.slice(start, end),
                page,
                totalPages,
                total
            };
        },


        renderPage() {
            if (!DOM.lanCards) return;

            const { list, total, page, totalPages } = this.getPagedData();

            if (!total) {
                DOM.lanCards.innerHTML = `
                <div class="lan-cards-header cards-header">
                    <span>Data</span>
                    <span>Tipo</span>
                    <span>Valor</span>
                    <span>Ações</span>
                </div>
                <div class="lan-card card-item" style="border-radius:0 0 16px 16px;">
                    <div style="grid-column:1/-1;font-size:0.85rem;color:var(--color-text-muted);padding:0.5rem 0;">
                        Nenhum lançamento encontrado para o perÃ­odo selecionado.
                    </div>
                </div>
            `;
                this.updatePager(0, 1, 1);
                this.updateSortIndicators();
                return;
            }

            const parts = [];
            const isXs = window.matchMedia('(max-width: 768px)').matches;

            // CabeÃ§alho
            // CabeÃ§alho
            parts.push(`
         <div class="lan-cards-header cards-header">
                 <button type="button" class="lan-cards-header-btn cards-header-btn" data-sort="data">
                  <span>Data</span>
                  <span class="lan-sort-indicator sort-indicator" data-field="data"></span>
                 </button>
                <button type="button" class="lan-cards-header-btn cards-header-btn" data-sort="tipo">
                     <span>Tipo</span>
                    <span class="lan-sort-indicator sort-indicator" data-field="tipo"></span>
                </button>
                <button type="button" class="lan-cards-header-btn cards-header-btn" data-sort="valor">
                     <span>Valor</span>
                    <span class="lan-sort-indicator sort-indicator" data-field="valor"></span>
                </button>
                 <span>Ações</span>
             </div>
`);


            for (const item of list) {
                const id = item.id;
                const tipoRaw = String(item.tipo || '').toLowerCase();
                const tipoClass = Utils.getTipoClass(tipoRaw);
                const tipoLabel = tipoRaw
                    ? tipoRaw.charAt(0).toUpperCase() + tipoRaw.slice(1)
                    : '-';

                const valorFmt = Utils.fmtMoney(item.valor);
                const dataFmt = Utils.fmtDate(item.data || item.created_at);

                const categoria =
                    item.categoria_nome ??
                    (typeof item.categoria === 'object'
                        ? item.categoria?.nome
                        : item.categoria) ??
                    '-';

                const conta =
                    item.conta_nome ??
                    (typeof item.conta === 'object'
                        ? item.conta?.nome
                        : item.conta) ??
                    '-';

                const descRaw =
                    item.descricao ??
                    item.descricao_titulo ??
                    (typeof item.descricao === 'object'
                        ? item.descricao?.texto
                        : '') ??
                    '';
                const descricao = descRaw || '--';

                const actionsHtml = `
                ${Utils.canEditLancamento(item)
                        ? `<button class="lk-btn ghost lan-card-btn" data-action="edit" data-id="${id}" title="Editar lançamento">
                           <i class="fas fa-pen"></i>
                       </button>`
                        : ''
                    }
                ${!Utils.isSaldoInicial(item)
                        ? `<button class="lk-btn danger lan-card-btn" data-action="delete" data-id="${id}" title="Excluir lançamento">
                           <i class="fas fa-trash"></i>
                       </button>`
                        : ''
                    }
            `;

                parts.push(`
                <article class="lan-card card-item" data-id="${id}" aria-expanded="false">
                    <div class="lan-card-main card-main">
                        <span class="lan-card-date card-date">${Utils.escapeHtml(dataFmt)}</span>
                        <span class="lan-card-type card-type">
                            <span class="badge-tipo ${tipoClass}">
                                ${Utils.escapeHtml(tipoLabel)}
                            </span>
                        </span>
                        <span class="lan-card-value card-value ${tipoClass}">
                            ${Utils.escapeHtml(valorFmt)}
                        </span>
                    </div>

                    <div class="lan-card-actions card-actions" data-slot="main">
                        ${actionsHtml}
                    </div>

                    <button class="lan-card-toggle card-toggle" type="button" data-toggle="details" aria-label="Ver detalhes do lançamento">
                        <span class="lan-card-toggle-icon card-toggle-icon"><i class="fas fa-chevron-right"></i></span>
                        <span> Ver detalhes</span>
                    </button>

                    <div class="lan-card-details card-details">
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Categoria</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(categoria || '-')}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Conta</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(conta || '-')}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Descrição</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(descricao)}</span>
                        </div>
                        ${isXs ? `<div class="lan-card-detail-row card-detail-row actions-row">
                                    <span class="lan-card-detail-label card-detail-label">Ações</span>
                                    <span class="lan-card-detail-value card-detail-value actions-slot">
                                        ${actionsHtml}
                                    </span>
                                  </div>` : ``}
                    </div>
                </article>
            `);
            }

            DOM.lanCards.innerHTML = parts.join('');
            this.updatePager(total, page, totalPages);
            this.updateSortIndicators();
        },

        updatePager(total, page, totalPages) {
            if (!DOM.lanPager || !DOM.lanPagerInfo) return;

            // se não tiver dados
            if (!total) {
                DOM.lanPagerInfo.textContent = 'Nenhum lançamento';
                if (DOM.lanPagerFirst) DOM.lanPagerFirst.disabled = true;
                if (DOM.lanPagerPrev) DOM.lanPagerPrev.disabled = true;
                if (DOM.lanPagerNext) DOM.lanPagerNext.disabled = true;
                if (DOM.lanPagerLast) DOM.lanPagerLast.disabled = true;
                return;
            }

            DOM.lanPagerInfo.textContent = `Página ${page} de ${totalPages}`;

            if (DOM.lanPagerFirst) {
                DOM.lanPagerFirst.disabled = page <= 1;
            }
            if (DOM.lanPagerPrev) {
                DOM.lanPagerPrev.disabled = page <= 1;
            }
            if (DOM.lanPagerNext) {
                DOM.lanPagerNext.disabled = page >= totalPages;
            }
            if (DOM.lanPagerLast) {
                DOM.lanPagerLast.disabled = page >= totalPages;
            }
        },

        goToPage(page) {
            const data = this.cache.filter(item => !Utils.isSaldoInicial(item));
            const totalPages = Math.max(1, Math.ceil(data.length / this.pageSize));

            const safePage = Math.min(Math.max(1, page), totalPages);
            if (safePage === this.currentPage) return;

            this.currentPage = safePage;
            this.renderPage();
        },

        nextPage() {
            this.goToPage(this.currentPage + 1);
        },

        prevPage() {
            this.goToPage(this.currentPage - 1);

        },
        firstPage() {
            this.goToPage(1);
        },

        lastPage() {
            const data = this.cache.filter(item => !Utils.isSaldoInicial(item));
            const totalPages = Math.max(1, Math.ceil(data.length / this.pageSize));
            this.goToPage(totalPages);
        },
        setSort(field) {
            if (!field) return;

            if (this.sortField === field) {
                // SÃ³ alterna asc/desc
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                // PadrÃ£o: data e valor em desc
                this.sortDir = 'desc';
            }

            this.currentPage = 1;
            this.renderPage();
        },
        updateSortIndicators() {
            // Depois de renderizar o HTML, atualiza â–¼ â–² nos tÃ­tulos
            const indicators = DOM.lanCards?.querySelectorAll('.lan-sort-indicator sort-indicator') || [];
            indicators.forEach(el => {
                const field = el.dataset.field;
                if (!field || field !== this.sortField) {
                    el.textContent = '';
                    return;
                }
                el.textContent = this.sortDir === 'asc' ? '\u2191' : '\u2193';
            });
        },

        handleClick(ev) {
            const target = ev.target;

            // Clique nos tÃ­tulos de ordenação (Data / Valor)
            const sortBtn = target.closest('[data-sort]');
            if (sortBtn) {
                const field = sortBtn.dataset.sort;
                if (field) {
                    MobileCards.setSort(field);
                }
                return;
            }

            // Toggle de detalhes
            const toggleBtn = target.closest('[data-toggle="details"]');
            if (toggleBtn) {
                const card = toggleBtn.closest('.lan-card');
                if (card) {
                    const isExpanded = card.getAttribute('aria-expanded') === 'true';
                    card.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                }
                return;
            }

            // BotÃµes Editar / Excluir
            const actionBtn = target.closest('.lan-card-btn');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = Number(actionBtn.dataset.id);
            if (!id) return;

            const item = MobileCards.cache.find(l => Number(l.id) === id);
            if (!item) return;


            if (action === 'edit') {
                if (!Utils.canEditLancamento(item)) return;
                ModalManager.openEditLancamento(item);
                return;
            }

            if (action === 'delete') {
                if (Utils.isSaldoInicial(item)) return;
                (async () => {
                    const ok = await Notifications.ask(
                        'Excluir lançamento?',
                        'Esta ação não pode ser desfeita.'
                    );
                    if (!ok) return;

                    actionBtn.disabled = true;
                    const okDel = await API.deleteOne(id);
                    actionBtn.disabled = false;

                    if (okDel) {
                        Notifications.toast('lançamento excluÃ­do com sucesso!');
                        await DataManager.load();
                    } else {
                        Notifications.toast('Falha ao excluir lançamento.', 'error');
                    }
                })();
            }
        }
    };

    // ============================================================================
    // GERENCIAMENTO DE MODAL
    // ============================================================================

    const ModalManager = {
        ensureLancModal: () => {
            if (STATE.modalEditLanc) return STATE.modalEditLanc;
            if (!DOM.modalEditLancEl) return null;

            if (window.bootstrap?.Modal) {
                if (DOM.modalEditLancEl.parentElement && DOM.modalEditLancEl.parentElement !== document.body) {
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
                DOM.selectLancTipo.value = ['receita', 'despesa'].includes(tipo) ? tipo : 'despesa';
            }

            OptionsManager.populateContaSelect(DOM.selectLancConta, data?.conta_id ?? null);
            OptionsManager.populateCategoriaSelect(
                DOM.selectLancCategoria,
                DOM.selectLancTipo?.value || '',
                data?.categoria_id ?? null
            );

            if (DOM.inputLancValor) {
                const valor = Math.abs(Number(data?.valor ?? 0));
                DOM.inputLancValor.value = Number.isFinite(valor) ? MoneyMask.format(valor) : '';
            }

            if (DOM.inputLancDescricao) {
                DOM.inputLancDescricao.value = data?.descricao || '';
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
            const valorValue = DOM.inputLancValor?.value || '';
            const descricaoValue = (DOM.inputLancDescricao?.value || '').trim();

            if (!dataValue) return ModalManager.showLancAlert('Informe a data do lançamento.');
            if (!tipoValue) return ModalManager.showLancAlert('Selecione o tipo do lançamento.');
            if (!contaValue) return ModalManager.showLancAlert('Selecione a conta.');

            const valorFloat = Math.abs(Number(MoneyMask.unformat(valorValue)));
            if (!Number.isFinite(valorFloat)) {
                return ModalManager.showLancAlert('Informe um valor vÃ¡lido.');
            }

            const payload = {
                data: dataValue,
                tipo: tipoValue,
                valor: Number(valorFloat.toFixed(2)),
                descricao: descricaoValue,
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
                Notifications.toast('lançamento atualizado com sucesso!');
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

    // ============================================================================
    // GERENCIAMENTO DE EXPORTAÃ‡ÃƒO
    // ============================================================================

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

            // ValidaÃ§Ãµes
            if ((startDate && !endDate) || (!startDate && endDate)) {
                Notifications.toast('Informe tanto a data inicial quanto final para exportar.', 'error');
                return;
            }

            if (startDate && endDate && endDate < startDate) {
                Notifications.toast('A data final deve ser posterior ou igual Ã  inicial.', 'error');
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
                }, format);

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

                Notifications.toast('Exportação concluí­da com sucesso!');
            } catch (err) {
                console.error(err);
                Notifications.toast(err?.message || 'Falha ao exportar lançamentos.', 'error');
            } finally {
                ExportManager.setLoading(false);
            }
        }
    };

    // ============================================================================
    // GERENCIAMENTO DE DADOS
    // ============================================================================

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
                // Limpa cards enquanto carrega

                MobileCards.setItems([]);

                const items = await API.fetchLancamentos({
                    month,
                    tipo,
                    categoria,
                    conta,
                    limit: CONFIG.DATA_LIMIT
                });

                await TableManager.renderRows(items);
                MobileCards.setItems(items);
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
                Notifications.toast('lançamentos excluídos com sucesso!');
                TableManager.updateSelectionInfo();
                // Recarrega dados para manter cards em sincronia
                await DataManager.load();
            } else {
                Notifications.toast('Alguns itens não foram excluídos.', 'error');
            }
        }
    };

    // ============================================================================
    // EVENT LISTENERS
    // ============================================================================

    const EventListeners = {
        init: () => {
            MoneyMask.bind(DOM.inputLancValor);
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

            // Submit do formulÃ¡rio de ediÃ§Ã£o
            DOM.formLanc?.addEventListener('submit', ModalManager.submitEditForm);

            // BotÃ£o de filtrar
            DOM.btnFiltrar?.addEventListener('click', DataManager.load);

            // BotÃ£o de exportar
            DOM.btnExportar?.addEventListener('click', () => ExportManager.export());

            // BotÃ£o de excluir selecionados
            DOM.btnExcluirSel?.addEventListener('click', DataManager.bulkDelete);

            // Eventos globais do sistema
            document.addEventListener('lukrato:month-changed', () => DataManager.load());
            document.addEventListener('lukrato:export-click', () => ExportManager.export());

            document.addEventListener('lukrato:data-changed', (e) => {
                const res = e.detail?.resource;
                if (!res || res === 'transactions') DataManager.load();
                if (res === 'categorias' || res === 'accounts') OptionsManager.loadFilterOptions();
            });

            // Cliques nos cards (mobile)
            DOM.lanCards?.addEventListener('click', MobileCards.handleClick);
            // Paginação (mobile)
            DOM.lanPagerFirst?.addEventListener('click', () => MobileCards.firstPage());
            DOM.lanPagerPrev?.addEventListener('click', () => MobileCards.prevPage());
            DOM.lanPagerNext?.addEventListener('click', () => MobileCards.nextPage());
            DOM.lanPagerLast?.addEventListener('click', () => MobileCards.lastPage());
        }
    };

    // ============================================================================
    // INICIALIZAÃ‡ÃƒO
    // ============================================================================

    const init = async () => {
        console.log('ðŸš€ Inicializando Sistema de lançamentos...');

        // Inicializar componentes
        ExportManager.initDefaults();
        EventListeners.init();

        // Carregar dados iniciais
        await OptionsManager.loadFilterOptions();
        await DataManager.load();

        console.log('âœ… Sistema de lançamentos carregado com sucesso!');
    };

    // Expor funÃ§Ãµes globais necessÃ¡rias
    window.refreshLancamentos = DataManager.load;

    // Iniciar aplicação
    init();
})();
