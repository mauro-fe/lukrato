/**
 * ============================================================================
 * SISTEMA DE GERENCIAMENTO DE LANÃ‡AMENTOS
 * ============================================================================
 * Gerencia listagem, filtros, ediÃ§Ã£o e exportação de lançamentos financeiros
 * Utiliza tabela HTML pura para renderização da tabela
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
        tableBody: document.getElementById('lancamentosTableBody'),
        selectAllCheckbox: document.getElementById('selectAllLancamentos'),
        paginationInfo: document.getElementById('paginationInfo'),
        pageSize: document.getElementById('pageSize'),
        prevPage: document.getElementById('prevPage'),
        nextPage: document.getElementById('nextPage'),
        pageNumbers: document.getElementById('pageNumbers'),
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
        loadTimer: null,
        lancamentos: [], // Armazena dados originais para agrupamento
        // HTML Table state
        allData: [],
        filteredData: [],
        currentPage: 1,
        pageSize: 25,
        sortField: 'data',
        sortDirection: 'desc',
        selectedIds: new Set()
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
                DOM.selectConta ? API.fetchJsonList(`${CONFIG.BASE_URL}api/contas?only_active=1`) : Promise.resolve([])
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
    // GERENCIAMENTO DE TABELA (HTML PURO) - DESKTOP
    // ============================================================================

    const TableManager = {
        /**
         * Initialize table event listeners for sorting, pagination, and selection
         */
        init() {
            // Sortable headers
            const sortableHeaders = document.querySelectorAll('.sortable[data-sort]');
            sortableHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const field = header.dataset.sort;
                    if (!field) return;
                    
                    // Toggle direction if same field, else default to desc
                    if (STATE.sortField === field) {
                        STATE.sortDirection = STATE.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        STATE.sortField = field;
                        STATE.sortDirection = 'desc';
                    }
                    STATE.currentPage = 1;
                    this.sortData();
                    this.render();
                    this.updateSortIndicators();
                });
            });

            // Select all checkbox
            if (DOM.selectAllCheckbox) {
                DOM.selectAllCheckbox.addEventListener('change', (e) => {
                    const checked = e.target.checked;
                    const checkboxes = DOM.tableBody?.querySelectorAll('.row-checkbox') || [];
                    checkboxes.forEach(cb => {
                        const row = cb.closest('tr');
                        const id = row?.dataset.id;
                        if (!id) return;
                        
                        // Find item to check if selectable
                        const item = STATE.filteredData.find(i => String(i.id) === String(id));
                        if (item && !Utils.isSaldoInicial(item) && !item._isParcelamentoGroup) {
                            cb.checked = checked;
                            if (checked) {
                                STATE.selectedIds.add(id);
                            } else {
                                STATE.selectedIds.delete(id);
                            }
                        }
                    });
                    this.updateSelectionInfo();
                });
            }

            // Page size selector
            if (DOM.pageSize) {
                DOM.pageSize.addEventListener('change', (e) => {
                    STATE.pageSize = parseInt(e.target.value) || 25;
                    STATE.currentPage = 1;
                    this.render();
                });
            }

            // Prev/Next buttons
            if (DOM.prevPage) {
                DOM.prevPage.addEventListener('click', () => {
                    if (STATE.currentPage > 1) {
                        this.goToPage(STATE.currentPage - 1);
                    }
                });
            }
            if (DOM.nextPage) {
                DOM.nextPage.addEventListener('click', () => {
                    const totalPages = Math.ceil(STATE.filteredData.length / STATE.pageSize);
                    if (STATE.currentPage < totalPages) {
                        this.goToPage(STATE.currentPage + 1);
                    }
                });
            }

            // Delegated event handler for table clicks
            if (DOM.tableBody) {
                DOM.tableBody.addEventListener('click', (e) => this.handleTableClick(e));
                DOM.tableBody.addEventListener('change', (e) => this.handleCheckboxChange(e));
            }
        },

        /**
         * Update sort indicators in table headers
         */
        updateSortIndicators() {
            const sortableHeaders = document.querySelectorAll('.sortable[data-sort]');
            sortableHeaders.forEach(header => {
                const field = header.dataset.sort;
                const icon = header.querySelector('.sort-icon');
                if (!icon) return;
                
                if (field === STATE.sortField) {
                    icon.className = STATE.sortDirection === 'asc' 
                        ? 'fas fa-sort-up sort-icon active' 
                        : 'fas fa-sort-down sort-icon active';
                } else {
                    icon.className = 'fas fa-sort sort-icon';
                }
            });
        },

        /**
         * Store and prepare data for rendering
         */
        setData(items) {
            STATE.allData = Array.isArray(items) ? items : [];
            
            // Process for parcelamento groups
            STATE.filteredData = ParcelamentoGrouper.processForTable(STATE.allData);
            
            // Sort data
            this.sortData();
            
            STATE.currentPage = 1;
            STATE.selectedIds.clear();
            
            if (DOM.selectAllCheckbox) {
                DOM.selectAllCheckbox.checked = false;
            }
            
            // Update sort indicators
            this.updateSortIndicators();
        },

        /**
         * Sort the filtered data based on current sort field and direction
         */
        sortData() {
            const field = STATE.sortField;
            const dir = STATE.sortDirection;
            
            STATE.filteredData.sort((a, b) => {
                let valA, valB;
                
                if (field === 'data') {
                    const dateA = a.data || a.created_at || '';
                    const dateB = b.data || b.created_at || '';
                    valA = new Date(dateA).getTime() || 0;
                    valB = new Date(dateB).getTime() || 0;
                } else if (field === 'valor') {
                    valA = parseFloat(a.valor) || 0;
                    valB = parseFloat(b.valor) || 0;
                    // For groups, calculate total
                    if (a._isParcelamentoGroup && a._parcelas) {
                        valA = a._parcelas.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
                    }
                    if (b._isParcelamentoGroup && b._parcelas) {
                        valB = b._parcelas.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
                    }
                } else if (field === 'tipo') {
                    valA = String(a.tipo || '').toLowerCase();
                    valB = String(b.tipo || '').toLowerCase();
                } else {
                    valA = String(a[field] || '');
                    valB = String(b[field] || '');
                }
                
                if (typeof valA === 'string' && typeof valB === 'string') {
                    return dir === 'asc' 
                        ? valA.localeCompare(valB, 'pt-BR') 
                        : valB.localeCompare(valA, 'pt-BR');
                }
                
                return dir === 'asc' ? (valA - valB) : (valB - valA);
            });
        },

        /**
         * Render the current page of data
         */
        render() {
            if (!DOM.tableBody) return;
            
            const total = STATE.filteredData.length;
            const totalPages = Math.max(1, Math.ceil(total / STATE.pageSize));
            STATE.currentPage = Math.min(STATE.currentPage, totalPages);
            
            const start = (STATE.currentPage - 1) * STATE.pageSize;
            const end = Math.min(start + STATE.pageSize, total);
            const pageData = STATE.filteredData.slice(start, end);
            
            // Render empty state if no data
            if (total === 0) {
                DOM.tableBody.innerHTML = `
                    <tr>
                        <td colspan="10" class="empty-state-cell">
                            <div class="empty-state" style="text-align:center; padding: 3rem 1rem;">
                                <div class="empty-icon" style="width:120px;height:120px;margin:0 auto 1.5rem;background:var(--color-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-exchange-alt" style="font-size:3rem;color:white;"></i>
                                </div>
                                <h3 style="color:var(--color-text);margin-bottom:0.75rem;font-size:1.5rem;font-weight:600;">Nenhum lançamento encontrado</h3>
                                <p style="color:var(--color-text-muted);margin-bottom:1.5rem;font-size:1rem;">Comece criando seu primeiro lançamento para gerenciar suas finanças</p>
                                <div style="display:flex;justify-content:center;">
                                    <button type="button" class="btn btn-primary btn-lg" onclick="lancamentoGlobalManager.openModal()" style="background:var(--color-primary);border:none;padding:0.75rem 1.5rem;font-size:1rem;border-radius:var(--radius-md);color:white;font-weight:500;">
                                        <i class="fas fa-plus"></i> Criar primeiro lançamento
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
                this.updatePagination();
                this.updateSelectionInfo();
                return;
            }
            
            // Render rows
            const rows = pageData.map(item => this.renderRow(item)).join('');
            DOM.tableBody.innerHTML = rows;
            
            this.updatePagination();
            this.updateSelectionInfo();
            this.updateSortIndicators();
        },

        /**
         * Create HTML for a single row
         */
        renderRow(item) {
            const id = item.id;
            const isGroup = item._isParcelamentoGroup;
            const isSaldoInicial = Utils.isSaldoInicial(item);
            const isSelectable = !isSaldoInicial && !isGroup;
            const isSelected = STATE.selectedIds.has(String(id));
            
            // Data
            const dataValue = item.data || item.created_at || '';
            const dataFormatted = Utils.fmtDate(dataValue);
            
            // Tipo
            const tipoRaw = String(item.tipo || '').toLowerCase();
            const tipoClass = Utils.getTipoClass(tipoRaw);
            const tipoLabel = tipoRaw ? tipoRaw.charAt(0).toUpperCase() + tipoRaw.slice(1) : '-';
            
            // Categoria
            let categoria = item.categoria_nome ?? 
                (typeof item.categoria === 'object' ? item.categoria?.nome : item.categoria) ?? '-';
            if (categoria && typeof categoria === 'object') {
                categoria = categoria.nome ?? categoria.label ?? '-';
            }
            categoria = categoria || '-';
            
            // Conta
            let conta = item.conta_nome ?? 
                (typeof item.conta === 'object' ? item.conta?.nome : item.conta) ?? '-';
            if (conta && typeof conta === 'object') {
                conta = conta.nome ?? conta.label ?? '-';
            }
            conta = conta || '-';
            
            // Descrição
            let descricao = item.descricao ?? item.descricao_titulo ?? '';
            if (descricao && typeof descricao === 'object') {
                descricao = descricao.texto ?? descricao.value ?? '';
            }
            descricao = String(descricao || '-').trim();
            
            // Valor
            let valor = parseFloat(item.valor) || 0;
            if (isGroup && item._parcelas) {
                valor = item._parcelas.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
            }
            
            // Row classes
            const rowClasses = ['lk-table-row'];
            if (isSaldoInicial) rowClasses.push('lk-row-inicial');
            if (isGroup) rowClasses.push('parcelamento-grupo');
            if (isSelected) rowClasses.push('selected');
            
            // Checkbox cell
            const checkboxCell = isSelectable
                ? `<td class="td-checkbox">
                       <input type="checkbox" class="lk-checkbox row-checkbox" ${isSelected ? 'checked' : ''}>
                   </td>`
                : `<td class="td-checkbox lk-cell-select-disabled"></td>`;
            
            // Descrição cell (special for groups)
            let descricaoCell;
            if (isGroup) {
                const totalParcelas = item._parcelas.length;
                const parcelasPagas = item._parcelas.filter(p => p.pago).length;
                const valorParcela = valor / totalParcelas;
                const percentual = totalParcelas > 0 ? (parcelasPagas / totalParcelas) * 100 : 0;
                
                descricaoCell = `
                    <td class="td-descricao">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-link p-0 text-decoration-none toggle-parcelas" 
                                    data-parcelamento-id="${String(id).replace('grupo_', '')}"
                                    title="Ver parcelas">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <div>
                                <div class="fw-bold">📦 ${Utils.escapeHtml(descricao)}</div>
                                <small class="text-muted">
                                    ${totalParcelas}x de R$ ${valorParcela.toFixed(2)} 
                                    · ${parcelasPagas}/${totalParcelas} pagas (${Math.round(percentual)}%)
                                </small>
                            </div>
                        </div>
                    </td>
                `;
            } else {
                descricaoCell = `<td class="td-descricao">${Utils.escapeHtml(descricao)}</td>`;
            }
            
            // Cartão de crédito
            const cartaoNome = item.cartao_nome || '';
            const cartaoBandeira = item.cartao_bandeira || '';
            const cartaoDisplay = cartaoNome ? `${cartaoNome}${cartaoBandeira ? ` (${cartaoBandeira})` : ''}` : '-';
            const cartaoCell = `<td class="td-cartao">${Utils.escapeHtml(cartaoDisplay)}</td>`;
            
            // Status (Pago/Pendente)
            const isPago = Boolean(item.pago);
            const statusClass = isPago ? 'status-pago' : 'status-pendente';
            const statusLabel = isPago ? 'Pago' : 'Pendente';
            const statusIcon = isPago ? 'fa-check-circle' : 'fa-clock';
            const statusCell = `<td class="td-status"><span class="badge-status ${statusClass}"><i class="fas ${statusIcon}"></i> ${statusLabel}</span></td>`;
            
            // Valor cell (special for groups)
            let valorCell;
            if (isGroup) {
                const totalParcelas = item._parcelas.length;
                const parcelasPagas = item._parcelas.filter(p => p.pago).length;
                const percentual = totalParcelas > 0 ? (parcelasPagas / totalParcelas) * 100 : 0;
                
                valorCell = `
                    <td class="td-valor">
                        <div>
                            <div class="fw-bold valor-cell ${tipoClass}">${Utils.fmtMoney(valor)}</div>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-${tipoRaw === 'receita' ? 'success' : 'danger'}" 
                                     style="width: ${percentual}%"></div>
                            </div>
                        </div>
                    </td>
                `;
            } else {
                valorCell = `<td class="td-valor"><span class="valor-cell ${tipoClass}">${Utils.fmtMoney(valor)}</span></td>`;
            }
            
            // Actions cell
            let actionsCell;
            if (isSaldoInicial) {
                actionsCell = '<td class="td-acoes"></td>';
            } else if (isGroup) {
                const parcelamentoId = String(id).replace('grupo_', '');
                actionsCell = `
                    <td class="td-acoes">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item toggle-parcelas-menu" href="#" data-parcelamento-id="${parcelamentoId}">
                                        <i class="fas fa-list"></i> Ver Parcelas
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger delete-parcelamento" href="#" data-parcelamento-id="${parcelamentoId}">
                                        <i class="fas fa-trash"></i> Cancelar Parcelamento
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                `;
            } else {
                const buttons = [];
                if (Utils.canEditLancamento(item)) {
                    buttons.push(`<button class="lk-btn ghost" data-action="edit" data-id="${id}" title="Editar"><i class="fas fa-pen"></i></button>`);
                }
                buttons.push(`<button class="lk-btn delete" data-action="delete" data-id="${id}" title="Excluir"><i class="fas fa-trash"></i></button>`);
                actionsCell = `<td class="td-acoes"><div class="lk-actions">${buttons.join('')}</div></td>`;
            }
            
            return `
                <tr class="${rowClasses.join(' ')}" data-id="${id}">
                    ${checkboxCell}
                    <td class="td-data">${Utils.escapeHtml(dataFormatted)}</td>
                    <td class="td-tipo"><span class="badge-tipo ${tipoClass}">${Utils.escapeHtml(tipoLabel)}</span></td>
                    <td class="td-categoria">${Utils.escapeHtml(categoria)}</td>
                    <td class="td-conta">${Utils.escapeHtml(conta)}</td>
                    ${cartaoCell}
                    ${descricaoCell}
                    ${statusCell}
                    ${valorCell}
                    ${actionsCell}
                </tr>
            `;
        },

        /**
         * Navigate to a specific page
         */
        goToPage(page) {
            const totalPages = Math.max(1, Math.ceil(STATE.filteredData.length / STATE.pageSize));
            const safePage = Math.min(Math.max(1, page), totalPages);
            
            if (safePage !== STATE.currentPage) {
                STATE.currentPage = safePage;
                this.render();
            }
        },

        /**
         * Update pagination controls
         */
        updatePagination() {
            const total = STATE.filteredData.length;
            const totalPages = Math.max(1, Math.ceil(total / STATE.pageSize));
            const start = total > 0 ? (STATE.currentPage - 1) * STATE.pageSize + 1 : 0;
            const end = Math.min(STATE.currentPage * STATE.pageSize, total);
            
            // Update info text
            if (DOM.paginationInfo) {
                if (total === 0) {
                    DOM.paginationInfo.textContent = '0 lançamentos';
                } else {
                    DOM.paginationInfo.textContent = `${start}-${end} de ${total} lançamentos`;
                }
            }
            
            // Update buttons
            if (DOM.prevPage) {
                DOM.prevPage.disabled = STATE.currentPage <= 1;
            }
            if (DOM.nextPage) {
                DOM.nextPage.disabled = STATE.currentPage >= totalPages;
            }
            
            // Update page numbers
            if (DOM.pageNumbers) {
                const pages = [];
                const maxVisible = 5;
                let startPage = Math.max(1, STATE.currentPage - Math.floor(maxVisible / 2));
                let endPage = Math.min(totalPages, startPage + maxVisible - 1);
                
                if (endPage - startPage + 1 < maxVisible) {
                    startPage = Math.max(1, endPage - maxVisible + 1);
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    const isActive = i === STATE.currentPage;
                    pages.push(`
                        <button type="button" class="page-number-btn ${isActive ? 'active' : ''}" 
                                data-page="${i}" ${isActive ? 'disabled' : ''}>
                            ${i}
                        </button>
                    `);
                }
                
                DOM.pageNumbers.innerHTML = pages.join('');
                
                // Add click handlers for page numbers
                DOM.pageNumbers.querySelectorAll('.page-number-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const page = parseInt(btn.dataset.page);
                        if (page) this.goToPage(page);
                    });
                });
            }
        },

        /**
         * Update selection info (count and button state)
         */
        updateSelectionInfo() {
            const count = STATE.selectedIds.size;
            
            if (DOM.selCountSpan) {
                DOM.selCountSpan.textContent = String(count);
            }
            
            if (DOM.btnExcluirSel) {
                DOM.btnExcluirSel.toggleAttribute('disabled', count === 0);
            }
            
            // Update select all checkbox state
            if (DOM.selectAllCheckbox && DOM.tableBody) {
                const checkboxes = DOM.tableBody.querySelectorAll('.row-checkbox');
                const checkedCount = DOM.tableBody.querySelectorAll('.row-checkbox:checked').length;
                const totalSelectable = checkboxes.length;
                
                if (totalSelectable === 0) {
                    DOM.selectAllCheckbox.checked = false;
                    DOM.selectAllCheckbox.indeterminate = false;
                } else if (checkedCount === 0) {
                    DOM.selectAllCheckbox.checked = false;
                    DOM.selectAllCheckbox.indeterminate = false;
                } else if (checkedCount === totalSelectable) {
                    DOM.selectAllCheckbox.checked = true;
                    DOM.selectAllCheckbox.indeterminate = false;
                } else {
                    DOM.selectAllCheckbox.checked = false;
                    DOM.selectAllCheckbox.indeterminate = true;
                }
            }
        },

        /**
         * Handle clicks on table (edit/delete actions)
         */
        handleTableClick(e) {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            
            const action = btn.dataset.action;
            const id = btn.dataset.id;
            if (!id) return;
            
            const item = STATE.filteredData.find(i => String(i.id) === String(id));
            if (!item) return;
            
            if (action === 'edit') {
                if (!Utils.canEditLancamento(item)) return;
                ModalManager.openEditLancamento(item);
            }
            
            if (action === 'delete') {
                if (Utils.isSaldoInicial(item)) return;
                
                (async () => {
                    const ok = await Notifications.ask(
                        'Excluir lançamento?',
                        'Esta ação não pode ser desfeita.'
                    );
                    if (!ok) return;
                    
                    btn.disabled = true;
                    const okDel = await API.deleteOne(id);
                    btn.disabled = false;
                    
                    if (okDel) {
                        // Remove from data and re-render
                        STATE.selectedIds.delete(String(id));
                        Notifications.toast('Lançamento excluído com sucesso!');
                        await DataManager.load();
                    } else {
                        Notifications.toast('Falha ao excluir lançamento.', 'error');
                    }
                })();
            }
        },

        /**
         * Handle checkbox changes for row selection
         */
        handleCheckboxChange(e) {
            const checkbox = e.target.closest('.row-checkbox');
            if (!checkbox) return;
            
            const row = checkbox.closest('tr');
            const id = row?.dataset.id;
            if (!id) return;
            
            if (checkbox.checked) {
                STATE.selectedIds.add(id);
                row.classList.add('selected');
            } else {
                STATE.selectedIds.delete(id);
                row.classList.remove('selected');
            }
            
            this.updateSelectionInfo();
        },

        /**
         * Compatibility method: render rows from items array
         */
        renderRows(items) {
            this.setData(items);
            this.render();
        },

        /**
         * Get selected row IDs (for bulk operations)
         */
        getSelectedIds() {
            return Array.from(STATE.selectedIds);
        },

        /**
         * Clear all selections
         */
        clearSelection() {
            STATE.selectedIds.clear();
            if (DOM.selectAllCheckbox) {
                DOM.selectAllCheckbox.checked = false;
                DOM.selectAllCheckbox.indeterminate = false;
            }
            const checkboxes = DOM.tableBody?.querySelectorAll('.row-checkbox') || [];
            checkboxes.forEach(cb => cb.checked = false);
            const rows = DOM.tableBody?.querySelectorAll('.selected') || [];
            rows.forEach(row => row.classList.remove('selected'));
            this.updateSelectionInfo();
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
                    <div class="empty-state" style="grid-column:1/-1;padding:2rem 1rem;text-align:center;">
                        <div class="empty-icon" style="width:100px;height:100px;margin:0 auto 1rem;background:var(--color-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-exchange-alt" style="font-size:2.5rem;color:white;"></i>
                        </div>
                        <h3 style="color:var(--color-text);margin-bottom:0.5rem;font-size:1.25rem;font-weight:600;">Nenhum lançamento encontrado</h3>
                        <p style="color:var(--color-text-muted);margin-bottom:1.25rem;font-size:0.9rem;">Comece criando seu primeiro lançamento para gerenciar suas finanças</p>
                        <button type="button" class="btn btn-primary btn-lg" onclick="lancamentoGlobalManager.openModal()" style="background:var(--color-primary);border:none;padding:0.65rem 1.25rem;font-size:0.95rem;border-radius:var(--radius-md);color:white;font-weight:500;">
                            <i class="fas fa-plus"></i> Criar primeiro lançamento
                        </button>
                    </div>
                </div>
            `;
                this.updatePager(0, 1, 1);
                this.updateSortIndicators();
                return;
            }

            const parts = [];
            const isXs = window.matchMedia('(max-width: 414px)').matches;

            // Debug log
            if (isXs) {
            }

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

                // Botões de ação para desktop/tablet
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
            `.trim();

                // Para mobile pequeno, sempre gera os botões (com ou sem permissões para garantir que apareçam)
                let mobileActionsHtml = '';
                if (isXs) {
                    const canEdit = Utils.canEditLancamento(item);
                    const canDelete = !Utils.isSaldoInicial(item);

                    const buttonStyle = 'display: flex !important; visibility: visible !important; opacity: 1 !important; width: 30px !important; height: 36px !important; min-width: 30px !important; min-height: 36px !important; border-radius: 10px !important; padding: 0 !important; margin: 0 2px !important; align-items: center !important; justify-content: center !important; flex: 0 0 auto !important; position: relative !important; z-index: 999 !important;';

                    // Se tiver ao menos uma permissão, mostra os botões permitidos
                    if (canEdit || canDelete) {
                        mobileActionsHtml = `
                        ${canEdit ? `<button class="lk-btn ghost lan-card-btn" data-action="edit" data-id="${id}" title="Editar lançamento" style="${buttonStyle} background: rgba(230, 126, 34, 0.3) !important; color: #e67e22 !important; border: 1px solid #e67e22 !important;">
                               <i class="fas fa-pen" style="font-size: 0.75rem; color: #e67e22;"></i>
                           </button>` : ''}
                        ${canDelete ? `<button class="lk-btn danger lan-card-btn" data-action="delete" data-id="${id}" title="Excluir lançamento" style="${buttonStyle} background: rgba(231, 76, 60, 0.3) !important; color: #e74c3c !important; border: 1px solid #e74c3c !important;">
                               <i class="fas fa-trash" style="font-size: 0.75rem; color: #e74c3c;"></i>
                           </button>` : ''}`.trim();
                    } else {
                        // Fallback: sempre mostra os botões em telas pequenas
                        mobileActionsHtml = `<button class="lk-btn ghost lan-card-btn" data-action="edit" data-id="${id}" title="Editar lançamento" style="${buttonStyle} background: rgba(230, 126, 34, 0.3) !important; color: #e67e22 !important; border: 1px solid #e67e22 !important;">
                               <i class="fas fa-pen" style="font-size: 0.75rem; color: #e67e22;"></i>
                           </button>
                           <button class="lk-btn danger lan-card-btn" data-action="delete" data-id="${id}" title="Excluir lançamento" style="${buttonStyle} background: rgba(231, 76, 60, 0.3) !important; color: #e74c3c !important; border: 1px solid #e74c3c !important;">
                               <i class="fas fa-trash" style="font-size: 0.75rem; color: #e74c3c;"></i>
                           </button>`.trim();
                    }
                }

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

                    <button class="lan-card-toggle card-toggle" type="button" data-toggle="details" aria-label="Ver detalhes do lançamento">
                        <span class="lan-card-toggle-icon card-toggle-icon"><i class="fas fa-chevron-right"></i></span>
                        <span class="detalhes"> Ver detalhes</span>
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
                        <div class="lan-card-detail-row card-detail-row actions-row" style="display: flex !important;">
                            <span class="lan-card-detail-label card-detail-label">AÇÕES</span>
                            <span class="lan-card-detail-value card-detail-value actions-slot" style="display: flex !important; gap: 8px;">
                                ${actionsHtml || '<span style="color: var(--text-secondary); font-size: 0.75rem;">Nenhuma ação disponível</span>'}
                            </span>
                        </div>
                    </div>
                </article>
            `);
            }

            DOM.lanCards.innerHTML = parts.join('');

            // Debug: verificar se os botões foram inseridos no DOM
            if (isXs) {
                const actionsRows = document.querySelectorAll('.actions-row');
                actionsRows.forEach((row, i) => {
                    const buttons = row.querySelectorAll('.lk-btn');
                });
            }
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

            // Detectar se é um parcelamento
            const ehParcelado = payload.eh_parcelado == 1 || payload.eh_parcelado === '1';
            const numeroParcelas = parseInt(payload.numero_parcelas) || 0;

            // Se for parcelamento com múltiplas parcelas E não for edição (sem id), redirecionar para API de parcelamentos
            if (!STATE.editingLancamentoId && ehParcelado && numeroParcelas > 1) {
                try {
                    const parcelamentoData = {
                        descricao: payload.descricao,
                        valor: parseFloat(payload.valor) || 0,
                        numero_parcelas: numeroParcelas,
                        categoria_id: payload.categoria_id,
                        conta_id: payload.conta_id,
                        tipo: payload.tipo,
                        data: payload.data
                    };

                    const response = await fetch('/api/parcelamentos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify(parcelamentoData)
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Erro ao criar parcelamento');
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: result.message || `Parcelamento criado! ${numeroParcelas} parcelas foram geradas.`,
                        timer: 3000
                    });

                    bootstrap.Modal.getInstance(DOM.modalEdit).hide();
                    await LancamentoManager.load();
                    return;
                } catch (error) {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: error.message || 'Erro ao criar parcelamento'
                    });
                    return;
                }
            }

            // Continuar com lógica normal de lançamento simples...
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

                // Clear table while loading
                TableManager.renderRows([]);
                
                // Limpa cards enquanto carrega
                MobileCards.setItems([]);

                const items = await API.fetchLancamentos({
                    month,
                    tipo,
                    categoria,
                    conta,
                    limit: CONFIG.DATA_LIMIT
                });

                // Armazenar no STATE para uso do ParcelamentoGrouper
                STATE.lancamentos = items;

                TableManager.renderRows(items);
                MobileCards.setItems(items);

            }, CONFIG.DEBOUNCE_DELAY);
        },

        bulkDelete: async () => {
            const ids = TableManager.getSelectedIds();

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
                TableManager.clearSelection();
                Notifications.toast('Lançamentos excluídos com sucesso!');
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

            // Botão de filtrar
            DOM.btnFiltrar?.addEventListener('click', DataManager.load);

            // Filtros automáticos ao mudar select
            DOM.selectTipo?.addEventListener('change', DataManager.load);
            DOM.selectCategoria?.addEventListener('change', DataManager.load);
            DOM.selectConta?.addEventListener('change', DataManager.load);

            // Botão de exportar
            DOM.btnExportar?.addEventListener('click', () => ExportManager.export());

            // BotÃ£o de excluir selecionados
            DOM.btnExcluirSel?.addEventListener('click', DataManager.bulkDelete);

            // Eventos globais do sistema
            document.addEventListener('lukrato:month-changed', () => DataManager.load());
            document.addEventListener('lukrato:export-click', () => ExportManager.export());

            document.addEventListener('lukrato:data-changed', (e) => {
                const res = e.detail?.resource;
                if (!res || res === 'transactions') DataManager.load();
                if (res === 'categorias' || res === 'contas') OptionsManager.loadFilterOptions();
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
    // AGRUPAMENTO DE PARCELAMENTOS
    // ============================================================================

    const ParcelamentoGrouper = {
        /**
         * Processa itens para a tabela (agrupa parcelamentos)
         */
        processForTable(items) {
            const { agrupados, simples } = this.agrupar(items);

            // Retornar simples + grupos marcados
            return [
                ...simples,
                ...agrupados.map(g => ({
                    ...g,
                    _isParcelamentoGroup: true,
                    _parcelas: g.parcelas,
                    // Para compatibilidade com Tabulator
                    id: `grupo_${g.id}`,
                    data: g.parcelas[0].data,
                    pago: false
                }))
            ];
        },

        /**
         * Interceptar renderização para agrupar parcelas
         */
        installInterceptor() {
            // Não precisamos mais interceptar, processamos direto no renderRows
        },

        /**
         * Agrupa itens por parcelamento_id
         */
        agrupar(items) {
            const grupos = {};
            const simples = [];

            items.forEach(item => {
                if (item.parcelamento_id) {
                    if (!grupos[item.parcelamento_id]) {
                        grupos[item.parcelamento_id] = {
                            id: item.parcelamento_id,
                            descricao: item.descricao.replace(/ \(\d+\/\d+\)$/, ''),
                            tipo: item.tipo,
                            categoria: item.categoria,
                            conta: item.conta,
                            cartao_credito: item.cartao_credito,
                            parcelas: []
                        };
                    }
                    grupos[item.parcelamento_id].parcelas.push(item);
                } else {
                    simples.push(item);
                }
            });

            return {
                agrupados: Object.values(grupos),
                simples
            };
        },

        /**
         * Cria row visual de parcelamento
         */
        createRow(grupo) {
            const totalParcelas = grupo.parcelas.length;
            const parcelasPagas = grupo.parcelas.filter(p => p.pago).length;
            const valorTotal = grupo.parcelas.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
            const percentual = totalParcelas > 0 ? (parcelasPagas / totalParcelas) * 100 : 0;

            const primeira = grupo.parcelas[0];
            const tipoClass = primeira.tipo === 'receita' ? 'success' : 'danger';
            const tipoIcon = primeira.tipo === 'receita' ? '↑' : '↓';

            const tr = document.createElement('tr');
            tr.className = 'parcelamento-grupo bg-light';
            tr.dataset.parcelamentoId = grupo.id;

            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-link p-0 text-decoration-none toggle-parcelas" 
                                data-parcelamento-id="${grupo.id}"
                                title="Ver parcelas">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div>
                            <div class="fw-bold">
                                📦 ${grupo.descricao}
                            </div>
                            <small class="text-muted">
                                ${totalParcelas}x de R$ ${(valorTotal / totalParcelas).toFixed(2)}
                            </small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${tipoClass}">
                        ${tipoIcon} ${primeira.tipo}
                    </span>
                </td>
                <td>
                    <span class="badge bg-secondary">${primeira.categoria || '-'}</span>
                </td>
                <td>
                    ${primeira.conta || primeira.cartao_credito || '-'}
                </td>
                <td class="text-end">
                    <div class="fw-bold">R$ ${valorTotal.toFixed(2)}</div>
                    <div class="progress mt-1" style="height: 6px;">
                        <div class="progress-bar bg-${tipoClass}" 
                             role="progressbar"
                             style="width: ${percentual}%"></div>
                    </div>
                    <small class="text-muted">
                        ${parcelasPagas}/${totalParcelas} pagas (${Math.round(percentual)}%)
                    </small>
                </td>
                <td class="text-center">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item toggle-parcelas-menu" 
                                   href="#" 
                                   data-parcelamento-id="${grupo.id}">
                                    <i class="fas fa-list"></i> Ver Parcelas
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger delete-parcelamento" 
                                   href="#" 
                                   data-parcelamento-id="${grupo.id}">
                                    <i class="fas fa-trash"></i> Cancelar Parcelamento
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            `;

            return tr;
        },

        /**
         * Toggle parcelas (expandir/colapsar)
         */
        toggle(parcelamentoId) {
            const grupoRow = document.querySelector(`tr[data-parcelamento-id="${parcelamentoId}"]`);
            if (!grupoRow) return;

            const icon = grupoRow.querySelector('.toggle-parcelas i');
            const existingDetails = grupoRow.nextElementSibling;

            // Se já está expandido, colapsar
            if (existingDetails?.classList.contains('parcelas-detalhes')) {
                existingDetails.remove();
                icon.className = 'fas fa-chevron-right';
                return;
            }

            // Expandir - buscar parcelas do STATE
            const data = STATE.lancamentos || [];
            const parcelas = data.filter(item => item.parcelamento_id == parcelamentoId)
                .sort((a, b) => new Date(a.data) - new Date(b.data));

            if (parcelas.length === 0) return;

            // Criar row com detalhes
            const detailsRow = document.createElement('tr');
            detailsRow.className = 'parcelas-detalhes';
            detailsRow.innerHTML = `
                <td colspan="6" class="p-0 border-0">
                    <div class="bg-white p-3 border-start border-end border-bottom">
                        <h6 class="mb-3">📋 Parcelas:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Parcela</th>
                                        <th>Data</th>
                                        <th class="text-end">Valor</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${parcelas.map((parcela, idx) => `
                                        <tr>
                                            <td>${parcela.numero_parcela || (idx + 1)}/${parcelas.length}</td>
                                            <td>${Utils.fmtDate(parcela.data)}</td>
                                            <td class="text-end fw-bold">${Utils.fmtMoney(parcela.valor)}</td>
                                            <td class="text-center">
                                                ${parcela.pago
                    ? '<span class="badge bg-success">✓ Pago</span>'
                    : '<span class="badge bg-warning">⏳ Pendente</span>'}
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm ${parcela.pago ? 'btn-warning' : 'btn-success'} toggle-pago-parcela"
                                                        data-lancamento-id="${parcela.id}"
                                                        data-pago="${!parcela.pago}"
                                                        title="${parcela.pago ? 'Marcar como não pago' : 'Marcar como pago'}">
                                                    <i class="fas ${parcela.pago ? 'fa-times' : 'fa-check'}"></i>
                                                </button>
                                                <button class="btn btn-sm btn-primary edit-lancamento"
                                                        data-lancamento-id="${parcela.id}"
                                                        title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            `;

            grupoRow.after(detailsRow);
            icon.className = 'fas fa-chevron-down';
        },

        /**
         * Marca/desmarca parcela como paga
         */
        async togglePago(lancamentoId, pago) {
            try {
                const response = await fetch(`${CONFIG.ENDPOINT}/${lancamentoId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ pago: pago })
                });

                const data = await response.json();

                if (response.ok) {
                    await DataManager.load();
                } else {
                    throw new Error(data.message || 'Erro ao atualizar status');
                }
            } catch (error) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message
                });
            }
        },

        /**
         * Deleta parcelamento inteiro (CASCADE)
         */
        async deletar(parcelamentoId) {
            const result = await Swal.fire({
                title: 'Cancelar Parcelamento?',
                html: '<p>Isso irá <strong>deletar todas as parcelas</strong> deste parcelamento!</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, cancelar!',
                cancelButtonText: 'Não'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`${CONFIG.BASE_URL}api/parcelamentos/${parcelamentoId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Cancelado!',
                            text: data.message || 'Parcelamento cancelado com sucesso',
                            timer: 2000
                        });
                        await DataManager.load();
                    } else {
                        throw new Error(data.message || 'Erro ao cancelar parcelamento');
                    }
                } catch (error) {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: error.message
                    });
                }
            }
        },

        /**
         * Instala event listeners
         */
        installListeners() {
            document.addEventListener('click', (e) => {
                // Toggle parcelas
                if (e.target.closest('.toggle-parcelas') || e.target.closest('.toggle-parcelas-menu')) {
                    e.preventDefault();
                    const btn = e.target.closest('.toggle-parcelas') || e.target.closest('.toggle-parcelas-menu');
                    const parcelamentoId = btn.dataset.parcelamentoId;
                    ParcelamentoGrouper.toggle(parcelamentoId);
                }

                // Toggle pago/não pago de parcela
                if (e.target.closest('.toggle-pago-parcela')) {
                    e.preventDefault();
                    const btn = e.target.closest('.toggle-pago-parcela');
                    const lancamentoId = btn.dataset.lancamentoId;
                    const pago = btn.dataset.pago === 'true';
                    ParcelamentoGrouper.togglePago(lancamentoId, pago);
                }

                // Editar parcela
                if (e.target.closest('.edit-lancamento')) {
                    e.preventDefault();
                    const btn = e.target.closest('.edit-lancamento');
                    const lancamentoId = btn.dataset.lancamentoId;
                    // Buscar item completo
                    const item = STATE.lancamentos.find(l => l.id == lancamentoId);
                    if (item) {
                        ModalManager.openEditLancamento(item);
                    }
                }

                // Deletar parcelamento
                if (e.target.closest('.delete-parcelamento')) {
                    e.preventDefault();
                    const btn = e.target.closest('.delete-parcelamento');
                    const parcelamentoId = btn.dataset.parcelamentoId;
                    ParcelamentoGrouper.deletar(parcelamentoId);
                }
            });
        }
    };

    // ============================================================================
    // INICIALIZAÇÃO
    // ============================================================================

    const init = async () => {

        // Inicializar tabela HTML
        TableManager.init();

        // Instalar sistema de agrupamento de parcelamentos
        ParcelamentoGrouper.installInterceptor();
        ParcelamentoGrouper.installListeners();

        // Inicializar componentes
        ExportManager.initDefaults();
        EventListeners.init();

        // Carregar dados iniciais
        await OptionsManager.loadFilterOptions();
        await DataManager.load();
    };

    // Expor funÃ§Ãµes globais necessÃ¡rias
    window.refreshLancamentos = DataManager.load;

    // Iniciar aplicação
    init();
})();
