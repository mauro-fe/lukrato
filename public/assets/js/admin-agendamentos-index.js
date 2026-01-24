/**
 * Sistema de Agendamentos - Refatorado
 * Gerencia agendamentos de receitas e despesas
 */

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================================
    // CONSTANTES E CONFIGURAÇÃO
    // ============================================================================

    const CONFIG = {
        PAYWALL_MESSAGE: 'Agendamentos são exclusivos do plano Pro.',
        BASE_URL: (typeof LK !== 'undefined' && typeof LK.getBase === 'function')
            ? LK.getBase()
            : (document.querySelector('meta[name="base-url"]')?.content || window.BASE_URL || '/lukrato/public/').replace(/\/?$/, '/'),
        TOKEN_ID: document.querySelector('meta[name="csrf-token-id"]')?.content || 'default',
        CARDS_PAGE_SIZE: 6
    };


    // ============================================================================
    // ESTADO GLOBAL
    // ============================================================================

    const STATE = {
        csrfToken: '',
        accessRestricted: false,
        cache: new Map(),
        selectCache: {
            contas: null,
            categorias: new Map()
        },
        ignoreFilterChange: false,
        activeQuickFilter: null,
        applyingQuickFilter: false
    };


    // ============================================================================
    // MÓDULO TOGGLE RECORRÊNCIA - PROFISSIONAL
    // ============================================================================

    const RecurrenceToggle = {
        // Configuração de estilos
        styles: {
            active: {
                backgroundColor: '#e67e22',
                backgroundImage: 'linear-gradient(135deg, #e67e22, #d35400)',
                borderColor: '#e67e22',
                color: '#ffffff',
                boxShadow: '0 4px 12px rgba(230, 126, 34, 0.4)'
            },
            inactive: {
                backgroundColor: '',
                backgroundImage: '',
                borderColor: '',
                color: '',
                boxShadow: ''
            }
        },

        // Textos
        text: {
            active: 'Sim, agendamento recorrente',
            inactive: 'Não, agendamento único'
        },

        // Referências DOM (lazy)
        _elements: null,

        // Getter para elementos DOM
        get elements() {
            if (!this._elements) {
                this._elements = {
                    button: document.getElementById('btnToggleRecorrente'),
                    checkbox: document.getElementById('agRecorrente'),
                    textSpan: document.getElementById('recorrenteText')
                };
            }
            return this._elements;
        },

        // Resetar cache de elementos
        clearElementsCache() {
            this._elements = null;
        },

        // Verificar se elementos existem
        isAvailable() {
            const { button, checkbox, textSpan } = this.elements;
            return !!(button && checkbox && textSpan);
        },

        // Obter estado atual
        isActive() {
            return this.elements.checkbox?.checked || false;
        },

        // Aplicar estilos inline
        applyStyles(button, styles) {
            const props = ['backgroundColor', 'backgroundImage', 'borderColor', 'color', 'boxShadow'];

            props.forEach(prop => {
                const cssProperty = prop.replace(/([A-Z])/g, '-$1').toLowerCase();
                const value = styles[prop];

                if (value) {
                    button.style.setProperty(cssProperty, value, 'important');
                } else {
                    button.style.removeProperty(cssProperty);
                }
            });
        },

        // Ativar (laranja)
        activate() {
            const { button, checkbox, textSpan } = this.elements;
            if (!button || !checkbox || !textSpan) return;

            checkbox.checked = true;
            button.classList.add('active');
            button.setAttribute('data-active', 'true');
            this.applyStyles(button, this.styles.active);
            textSpan.textContent = this.text.active;
            textSpan.style.setProperty('color', '#ffffff', 'important');

            // Mostrar campo de frequência
            const frequenciaGroup = document.getElementById('frequenciaGroup');
            if (frequenciaGroup) frequenciaGroup.style.display = 'block';

        },

        // Desativar (cinza)
        deactivate() {
            const { button, checkbox, textSpan } = this.elements;
            if (!button || !checkbox || !textSpan) return;

            checkbox.checked = false;
            button.classList.remove('active');
            button.setAttribute('data-active', 'false');
            this.applyStyles(button, this.styles.inactive);
            textSpan.textContent = this.text.inactive;
            textSpan.style.removeProperty('color');

            // Ocultar campo de frequência
            const frequenciaGroup = document.getElementById('frequenciaGroup');
            if (frequenciaGroup) frequenciaGroup.style.display = 'none';

        },

        // Toggle (inverter estado)
        toggle() {
            if (this.isActive()) {
                this.deactivate();
            } else {
                this.activate();
            }
        },

        // Definir estado específico
        setState(active) {
            if (active) {
                this.activate();
            } else {
                this.deactivate();
            }
        },

        // Reset para estado inicial
        reset() {
            this.clearElementsCache();
            this.deactivate();
        },

        // Inicializar event listeners
        init() {
            this.clearElementsCache();
            const { button } = this.elements;

            if (!button) {
                // Botão não existe mais - o modal agora usa select dropdown para recorrência
                return false;
            }

            // Remover listeners antigos clonando o elemento
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            // Atualizar cache com novo botão E novo textSpan (que está dentro do botão clonado)
            this._elements.button = newButton;
            this._elements.textSpan = newButton.querySelector('#recorrenteText');

            // Adicionar listener
            newButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            });

            return true;
        },

        // Debug: mostrar estado atual
        debug() {
            const { button } = this.elements;
            if (!button) return null;

            const computed = window.getComputedStyle(button);
            return {
                isActive: this.isActive(),
                dataActive: button.getAttribute('data-active'),
                hasActiveClass: button.classList.contains('active'),
                computedBg: computed.backgroundColor,
                computedBgImage: computed.backgroundImage
            };
        }
    };


    // ============================================================================
    // ELEMENTOS DO DOM
    // ============================================================================

    const DOM = {
        // Containers
        cardsContainer: document.getElementById('agCards'),
        tableBody: document.getElementById('agendamentosTableBody'),
        agList: document.getElementById('agList'),

        // Paginação
        pager: document.getElementById('agCardsPager'),
        pagerInfo: document.getElementById('agPagerInfo'),
        pagerFirst: document.getElementById('agPagerFirst'),
        pagerPrev: document.getElementById('agPagerPrev'),
        pagerNext: document.getElementById('agPagerNext'),
        pagerLast: document.getElementById('agPagerLast'),

        // Paywall
        paywallBox: document.getElementById('agPaywall'),
        paywallMessage: document.getElementById('agPaywallMessage'),
        paywallCta: document.getElementById('agPaywallCta'),

        // Modal e Formulário
        modal: document.getElementById('modalAgendamento'),
        form: document.getElementById('formAgendamento'),
        modalTitle: document.getElementById('modalAgendamentoLabel'),
        // querySelector é mais sensível; garanta que o modal tenha este ID exato
        modalSubmitBtn: document.querySelector('#modalAgendamento [type="submit"]'),

        // Inputs do Formulário
        agId: document.getElementById('agId'),
        agTitulo: document.getElementById('agTitulo'),
        agTipo: document.getElementById('agTipo'),
        agCategoria: document.getElementById('agCategoria'),
        agConta: document.getElementById('agConta'),
        agValor: document.getElementById('agValor'),
        agDataPagamento: document.getElementById('agDataPagamento'),
        agFrequencia: document.getElementById('agFrequencia'),
        agRepeticoes: document.getElementById('agRepeticoes'),
        agLembrar: document.getElementById('agLembrar'),
        agDescricao: document.getElementById('agDescricao'),

        // Filtros
        filtroTipo: document.getElementById('filtroTipo'),
        filtroCategoria: document.getElementById('filtroCategoria'),
        filtroConta: document.getElementById('filtroConta'),
        filtroStatus: document.getElementById('filtroStatus'),
        btnLimparFiltros: document.getElementById('btnLimparFiltros'),

        // Botões
        btnAddAgendamento: document.getElementById('btnAddAgendamento'),

        // Modal de Visualização
        modalVisualizacao: document.getElementById('modalVisualizacao'),
        btnEditarFromView: document.getElementById('btnEditarFromView'),

        // Template
        cardTemplate: document.getElementById('agCardTemplate')
    };


    // ============================================================================
    // UTILITÁRIOS - CSRF
    // ============================================================================

    const CSRF = {
        get() {
            if (typeof LK !== 'undefined' && typeof LK.getCSRF === 'function') {
                return LK.getCSRF();
            }
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        apply(token) {
            if (!token) return;

            STATE.csrfToken = token;

            document.querySelectorAll(`[data-csrf-id="${CONFIG.TOKEN_ID}"]`).forEach(el => {
                if (el.tagName === 'META') {
                    el.setAttribute('content', token);
                } else if ('value' in el) {
                    el.value = token;
                }
            });

            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) meta.setAttribute('content', token);

            if (window.LK) window.LK.csrfToken = token;
        },

        async refresh() {
            const res = await fetch(`${CONFIG.BASE_URL}api/csrf/refresh`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ token_id: CONFIG.TOKEN_ID })
            });

            const data = await res.json().catch(() => null);

            if (data?.token) {
                this.apply(data.token);
                return data.token;
            }

            throw new Error('Falha ao renovar CSRF');
        }
    };


    // ============================================================================
    // UTILITÁRIOS - HTTP
    // ============================================================================

    const HTTP = {
        async fetchJSON(url, options = {}) {
            const res = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    ...(options.headers || {})
                },
                ...options
            });

            if (await Paywall.handleResponse(res)) {
                return null;
            }

            let data = null;
            try {
                data = await res.json();
            } catch {
                // Resposta vazia ou texto puro
            }

            if (!res.ok) {
                const message = data?.message || 'Não foi possível carregar os dados.';
                throw new Error(message);
            }

            return data;
        },

        async fetchWithCSRF(url, options = {}, retry = true) {
            const res = await fetch(url, {
                credentials: options.credentials || 'include',
                ...options,
                headers: {
                    'Accept': 'application/json',
                    ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
                    'X-CSRF-TOKEN': STATE.csrfToken || CSRF.get(),
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {})
                }
            });

            const resClone = res.clone();
            let json = null;

            try {
                json = await res.json();
            } catch (_) {
                // Resposta vazia
            }

            const isCsrfError = (res.status === 403 || res.status === 419 || res.status === 422) && (
                (json?.errors && json.errors.csrf_token) ||
                String(json?.message || '').toLowerCase().includes('csrf')
            );

            if (isCsrfError && retry) {
                try {
                    await CSRF.refresh();
                    return this.fetchWithCSRF(url, options, false);
                } catch (_) {
                    // Continua para o fluxo normal de erro
                }
            }

            if (res.status === 403) {
                await Paywall.handleResponse(resClone);
            }

            if (!res.ok || (json && json.status === 'error')) {
                if (res.status === 422 && json?.errors && !isCsrfError) {
                    const detalhes = Object.values(json.errors).flat().join('\n');
                    throw new Error(detalhes || json?.message || 'Erros de validação.');
                }
                const msg = json?.message || `HTTP ${res.status}`;
                throw new Error(msg);
            }

            if (json?.token) {
                CSRF.apply(json.token);
            }

            return json;
        }
    };


    // ============================================================================
    // UTILITÁRIOS - FORMATAÇÃO
    // ============================================================================

    const Format = {
        escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, match => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match] || match));
        },

        currency(value) {
            const number = Number(value ?? 0) / 100;
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL',
                minimumFractionDigits: 2
            }).format(number || 0);
        },

        dateTime(value) {
            if (!value) return '-';

            try {
                const dt = new Date(value.replace(' ', 'T'));
                return new Intl.DateTimeFormat('pt-BR', {
                    dateStyle: 'short',
                    timeStyle: 'short'
                }).format(dt);
            } catch {
                return value;
            }
        },

        toDateTimeLocalValue(value) {
            if (!value) return '';

            try {
                const dt = new Date(String(value).replace(' ', 'T'));
                if (Number.isNaN(dt.getTime())) return '';

                const local = new Date(dt.getTime() - dt.getTimezoneOffset() * 60000);
                return local.toISOString().slice(0, 16);
            } catch {
                return '';
            }
        },

        getLocalDateTimeInputValue() {
            const now = new Date();
            const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
            return local.toISOString().slice(0, 16);
        },

        statusBadge(status) {
            if (!status) return '<span class="badge bg-secondary text-uppercase">-</span>';

            const statusLower = String(status).toLowerCase();
            const colorMap = {
                pendente: 'warning',
                enviado: 'info',
                concluido: 'success',
                cancelado: 'danger'
            };

            const color = colorMap[statusLower] || 'secondary';
            const label = statusLower.charAt(0).toUpperCase() + statusLower.slice(1);

            return `<span class="badge bg-${color} text-uppercase">${this.escapeHtml(label)}</span>`;
        },

        getTipoClass(tipo) {
            const value = String(tipo || '').toLowerCase();
            if (value === 'receita') return 'tipo-receita';
            if (value === 'despesa') return 'tipo-despesa';
            return '';
        },

        /**
         * Calcula status dinâmico baseado na data
         * @param {string} dataPagamento - Data do agendamento
         * @param {boolean} recorrente - Se é recorrente
         * @param {string} statusBanco - Status do banco (cancelado, concluido)
         * @returns {string} 'hoje', 'agendado', 'vencido', 'executado', 'cancelado'
         */
        calcularStatusDinamico(dataPagamento, recorrente, statusBanco) {
            // Se foi cancelado no banco
            if (statusBanco === 'cancelado') {
                return 'cancelado';
            }

            // Se foi executado E não é recorrente
            if (statusBanco === 'concluido' && !recorrente) {
                return 'executado';
            }

            if (!dataPagamento) {
                return 'agendado';
            }

            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);

            const dataAgendamento = new Date(dataPagamento);
            dataAgendamento.setHours(0, 0, 0, 0);

            if (dataAgendamento.getTime() === hoje.getTime()) {
                return 'hoje';
            } else if (dataAgendamento < hoje) {
                return 'vencido';
            } else {
                return 'agendado';
            }
        },

        /**
         * Retorna badge HTML para status dinâmico
         */
        statusDinamicoBadge(status) {
            const badges = {
                'hoje': '<span class="badge-status badge-hoje" title="Agendado para hoje">📅 Hoje</span>',
                'agendado': '<span class="badge-status badge-agendado" title="Agendado para o futuro">⏰ Agendado</span>',
                'vencido': '<span class="badge-status badge-vencido" title="Data passou">⚠️ Vencido</span>',
                'executado': '<span class="badge-status badge-executado" title="Já foi executado">✅ Executado</span>',
                'cancelado': '<span class="badge-status badge-cancelado" title="Cancelado">❌ Cancelado</span>',
            };

            return badges[status] || '<span class="badge-status badge-agendado">⏰ Agendado</span>';
        },

        /**
         * Retorna ícone de recorrência
         */
        recorrenteIcon(isRecorrente) {
            return isRecorrente
                ? '<span class="recorrente-icon" title="Agendamento recorrente">🔁</span>'
                : '';
        }
    };


    // ============================================================================
    // UTILITÁRIOS - MÁSCARA DE DINHEIRO
    // ============================================================================

    const MoneyMask = (() => {
        const formatter = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        return {
            format(num) {
                const n = Number(num);
                return Number.isFinite(n) ? formatter.format(n) : '';
            },

            bind(input) {
                if (!input) return;

                const onInput = (e) => {
                    const digits = String(e.target.value || '').replace(/[^\d]/g, '');
                    const num = Number(digits || '0') / 100;
                    e.target.value = this.format(num);
                };

                input.addEventListener('input', onInput, { passive: true });
                input.addEventListener('focus', () => {
                    if (!input.value) input.value = this.format(0);
                });
            },

            parseToCents(value) {
                if (value === null || value === undefined) return 0;

                const normalized = String(value)
                    .replace(/[^\d,.-]/g, '')
                    .replace(/\./g, '')
                    .replace(',', '.');

                const number = Number(normalized);
                return Number.isFinite(number) ? Math.round(number * 100) : 0;
            }
        };
    })();


    // ============================================================================
    // UTILITÁRIOS - HELPERS
    // ============================================================================

    const Helpers = {
        listFromPayload(payload) {
            if (!payload) return [];
            if (Array.isArray(payload)) return payload;
            if (Array.isArray(payload.data)) return payload.data;
            if (Array.isArray(payload.items)) return payload.items;
            if (Array.isArray(payload.itens)) return payload.itens;
            return [];
        },

        isDesktopView() {
            const desktopTable = document.querySelector('.ag-table-desktop');
            if (desktopTable) {
                const displayStyle = getComputedStyle(desktopTable).display;
                return displayStyle !== 'none';
            }
            return false;
        }
    };


    // ============================================================================
    // PAYWALL
    // ============================================================================

    const Paywall = {
        show(message = CONFIG.PAYWALL_MESSAGE) {
            if (DOM.paywallMessage) {
                DOM.paywallMessage.textContent = message;
            }

            if (DOM.paywallBox) {
                DOM.paywallBox.classList.remove('d-none');
                DOM.paywallBox.removeAttribute('hidden');
            }

            if (DOM.agList) {
                DOM.agList.classList.add('d-none');
            }
        },

        hide() {
            if (DOM.paywallBox) {
                DOM.paywallBox.classList.add('d-none');
                DOM.paywallBox.setAttribute('hidden', 'hidden');
            }

            if (DOM.agList) {
                DOM.agList.classList.remove('d-none');
            }

            STATE.accessRestricted = false;
        },

        goToBilling() {
            if (typeof openBillingModal === 'function') {
                openBillingModal();
            } else {
                location.href = `${CONFIG.BASE_URL}billing`;
            }
        },

        async prompt(message) {
            const text = message || CONFIG.PAYWALL_MESSAGE;

            if (typeof Swal !== 'undefined' && Swal.fire) {
                const result = await Swal.fire({
                    title: 'Acesso restrito',
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Assinar plano Pro',
                    cancelButtonText: 'Agora não',
                    reverseButtons: true,
                    focusConfirm: true
                });

                if (result.isConfirmed) this.goToBilling();
            } else if (confirm(`${text}\n\nIr para a página de assinatura agora?`)) {
                this.goToBilling();
            }
        },

        async handleResponse(response) {
            if (!response) return false;

            if (response.status === 401) {
                const here = encodeURIComponent(location.pathname + location.search);
                location.href = `${CONFIG.BASE_URL}login?return=${here}`;
                return true;
            }

            if (response.status === 403) {
                let msg = CONFIG.PAYWALL_MESSAGE;

                try {
                    const data = await response.clone().json();
                    msg = data?.message || msg;
                } catch {
                    // Ignora erro de parsing
                }

                this.show(msg);

                if (!STATE.accessRestricted) {
                    STATE.accessRestricted = true;
                    await this.prompt(msg);
                }

                return true;
            }

            this.hide();
            return false;
        }
    };


    // ============================================================================
    // SELECTS - CATEGORIAS E CONTAS
    // ============================================================================

    const Selects = {
        fill(selectEl, items, options = {}) {
            const {
                placeholder = null,
                getValue = (item) => item?.id ?? '',
                getLabel = (item) => item?.nome ?? ''
            } = options;

            if (!selectEl) return;

            const previous = selectEl.value;
            selectEl.innerHTML = '';

            if (placeholder !== null) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = placeholder;
                selectEl.appendChild(opt);
            }

            items.forEach(item => {
                const option = document.createElement('option');
                option.value = String(getValue(item) ?? '');
                option.textContent = getLabel(item) ?? '';
                selectEl.appendChild(option);
            });

            if (previous && selectEl.querySelector(`option[value="${previous}"]`)) {
                selectEl.value = previous;
            }
        },

        async loadContas(force = false) {
            if (!DOM.agConta) return;

            if (STATE.selectCache.contas && !force) {
                this.fill(DOM.agConta, STATE.selectCache.contas, {
                    placeholder: 'Todas as contas (opcional)',
                    getLabel: (item) => {
                        const instituicao = item?.instituicao ? ` · ${item.instituicao}` : '';
                        return `${item?.nome ?? ''}${instituicao}`;
                    }
                });
                return;
            }

            const data = await HTTP.fetchJSON(`${CONFIG.BASE_URL}api/contas?only_active=1&with_balances=0`);
            if (!data) return;

            const items = Helpers.listFromPayload(data);
            STATE.selectCache.contas = items;

            this.fill(DOM.agConta, items, {
                placeholder: 'Todas as contas (opcional)',
                getLabel: (item) => {
                    const instituicao = item?.instituicao ? ` · ${item.instituicao}` : '';
                    return `${item?.nome ?? ''}${instituicao}`;
                }
            });
        },

        async loadCategorias(tipo = 'despesa', force = false) {
            if (!DOM.agCategoria) return;

            const key = tipo || 'todos';

            if (STATE.selectCache.categorias.has(key) && !force) {
                this.fill(DOM.agCategoria, STATE.selectCache.categorias.get(key), {
                    placeholder: 'Selecione uma categoria'
                });
                return;
            }

            const qs = tipo ? `?tipo=${encodeURIComponent(tipo)}` : '';
            const data = await HTTP.fetchJSON(`${CONFIG.BASE_URL}api/categorias${qs}`);
            if (!data) return;

            const items = Helpers.listFromPayload(data);
            STATE.selectCache.categorias.set(key, items);

            this.fill(DOM.agCategoria, items, {
                placeholder: 'Selecione uma categoria'
            });
        }
    };


    // ============================================================================
    // CARDS MOBILE - GERENCIAMENTO
    // ============================================================================

    const MobileCards = {
        data: [],
        pageSize: CONFIG.CARDS_PAGE_SIZE,
        currentPage: 1,
        sortField: 'data_pagamento',
        sortDir: 'desc',

        setData(list) {

            this.data = Array.isArray(list) ? [...list] : [];
            this.currentPage = 1;
            this.render();
        },

        setSort(field) {
            if (!field) return;

            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir = field === 'titulo' ? 'asc' : 'desc';
            }

            this.render();
        },

        getSortValue(item, field) {
            const value = item?.[field];

            if (field === 'valor_centavos') {
                return Number(value) || 0;
            }

            // Suportar tanto data_pagamento quanto data_agendada para ordenação
            if (field === 'data_pagamento' || field === 'data_agendada') {
                const dataStr = item?.data_pagamento || item?.data_agendada;
                const date = dataStr ? new Date(String(dataStr).replace(' ', 'T')) : null;
                return date ? date.getTime() : 0;
            }

            return String(value || '').toLowerCase();
        },

        getPagedData() {
            const sorted = [...this.data].sort((a, b) => {
                const av = this.getSortValue(a, this.sortField);
                const bv = this.getSortValue(b, this.sortField);

                if (av === bv) return 0;

                const dir = this.sortDir === 'asc' ? 1 : -1;
                return av > bv ? dir : -dir;
            });

            const total = sorted.length;
            const totalPages = Math.max(1, Math.ceil(total / this.pageSize));
            const page = Math.min(this.currentPage, totalPages);

            this.currentPage = page;

            const start = (page - 1) * this.pageSize;

            return {
                list: sorted.slice(start, start + this.pageSize),
                total,
                page,
                totalPages
            };
        },

        render() {
            if (!DOM.cardsContainer) return;

            const { list, total, page, totalPages } = this.getPagedData();

            DOM.cardsContainer.innerHTML = '';

            this.renderHeader();

            if (!total) {
                this.renderEmpty();
                this.updatePager(0, 1, 1);
                this.updateSortIndicators();
                return;
            }

            if (!DOM.cardTemplate?.content) {
                console.error('[Agendamentos] Template não encontrado.');
                return;
            }

            list.forEach(item => this.renderCard(item));

            this.updatePager(total, page, totalPages);
            this.updateSortIndicators();
        },

        renderHeader() {
            const header = document.createElement('div');
            header.className = 'ag-card-header';
            header.innerHTML = `
                <button type="button" class="cards-header-btn" data-sort="data_pagamento">
                    <span>Data</span>
                    <span class="sort-indicator" data-field="data_pagamento"></span>
                </button>
                <button type="button" class="cards-header-btn" data-sort="tipo">
                    <span>Tipo</span>
                    <span class="sort-indicator" data-field="tipo"></span>
                </button>
                <button type="button" class="cards-header-btn" data-sort="valor_centavos">
                    <span>Valor</span>
                    <span class="sort-indicator" data-field="valor_centavos"></span>
                </button>
                <span class="cards-header-btn cards-header-btn-actions">Ações</span>
            `;

            DOM.cardsContainer.appendChild(header);
        },

        renderEmpty() {
            const empty = document.createElement('div');
            empty.className = 'ag-card card-item card-empty';
            empty.innerHTML = '<div class="card-empty-text">Nenhum agendamento encontrado.</div>';

            DOM.cardsContainer.appendChild(empty);
        },

        renderCard(item) {
            const clone = DOM.cardTemplate.content.cloneNode(true);
            const card = clone.querySelector('.ag-card');

            const tipo = String(item.tipo || '').toLowerCase();
            const tipoClass = Format.getTipoClass(tipo);
            const status = String(item.status || '').toLowerCase();

            card.dataset.id = item.id;

            const isRecorrente = item.recorrente === 1 || item.recorrente === true;
            const statusDinamico = item.status_dinamico ||
                Format.calcularStatusDinamico(item.data_pagamento || item.data_agendada, isRecorrente, status);

            // Adicionar classe de destaque ao card
            if (statusDinamico === 'hoje') {
                card.classList.add('card-hoje');
            } else if (statusDinamico === 'vencido') {
                card.classList.add('card-vencido');
            }

            // Título com ícone de recorrência
            const tituloEl = clone.querySelector('.ag-card-title');
            tituloEl.innerHTML = `${Format.escapeHtml(item.titulo || '-')} ${Format.recorrenteIcon(isRecorrente)}`;

            // Data - usar data_pagamento com fallback
            clone.querySelector('[data-field="data"]').textContent =
                Format.dateTime(item.data_pagamento || item.data_agendada || item.created_at);

            // Valor
            const valorEl = clone.querySelector('.ag-card-value');
            valorEl.textContent = Format.currency(item.valor_centavos || item.valor);
            valorEl.classList.add(tipoClass);

            // Badge de tipo
            const badge = clone.querySelector('.ag-tipo-badge');
            badge.classList.add(tipoClass);
            badge.querySelector('[data-field="tipo"]').textContent =
                tipo.charAt(0).toUpperCase() + tipo.slice(1);
            badge.querySelector('i').className =
                `fas ${tipo === 'receita' ? 'fa-arrow-up' : 'fa-arrow-down'}`;

            // Categoria e Conta
            clone.querySelector('[data-field="categoria"]').textContent =
                item.categoria?.nome || item.categoria_nome || '-';
            clone.querySelector('[data-field="conta"]').textContent =
                item.conta?.nome || item.conta_nome || '-';

            // Recorrente
            clone.querySelector('[data-field="recorrente"]').textContent =
                isRecorrente ? '✅ Sim (automático)' : '❌ Não (único)';

            // Descrição (opcional)
            if (item.descricao) {
                clone.querySelector('[data-field="descricao"]').textContent = item.descricao;
            } else {
                clone.querySelector('[data-section="descricao"]')?.remove();
            }

            // Status dinâmico
            clone.querySelector('[data-field="status"]').innerHTML =
                Format.statusDinamicoBadge(statusDinamico);

            // Ações
            this.renderCardActions(clone, status, item.id, statusDinamico);

            DOM.cardsContainer.appendChild(clone);
        },

        renderCardActions(clone, status, itemId, statusDinamico) {
            const actionsContainer = clone.querySelector('.ag-card-actions');

            const viewBtn = `
                <button class="lk-btn primary ag-card-btn" data-ag-action="visualizar" data-id="${itemId}" title="Visualizar">
                    <i class="fas fa-eye"></i>
                </button>
            `;

            // Verificar cancelado PRIMEIRO
            if (status === 'cancelado') {
                actionsContainer.innerHTML = `
                    ${viewBtn}
                    <button class="lk-btn ghost ag-card-btn" data-ag-action="reativar" data-id="${itemId}" title="Reativar">
                        <i class="fas fa-undo-alt"></i>
                    </button>
                `;
            } else if (status === 'pendente' || statusDinamico !== 'executado') {
                actionsContainer.innerHTML = `
                    ${viewBtn}
                    <button class="lk-btn ghost-pagar ag-card-btn" data-ag-action="pagar" data-id="${itemId}" title="Executar">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="lk-btn ghost ag-card-btn" data-ag-action="editar" data-id="${itemId}" title="Editar">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button class="lk-btn danger ag-card-btn" data-ag-action="cancelar" data-id="${itemId}" title="Cancelar">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else {
                actionsContainer.innerHTML = viewBtn;
            }
        },

        updatePager(total, page, totalPages) {
            if (!DOM.pager || !DOM.pagerInfo) return;

            if (!total) {
                DOM.pagerInfo.textContent = 'Nenhum agendamento';

                [DOM.pagerFirst, DOM.pagerPrev, DOM.pagerNext, DOM.pagerLast].forEach(btn => {
                    if (btn) btn.disabled = true;
                });

                return;
            }

            DOM.pagerInfo.textContent = `página ${page} de ${totalPages}`;

            if (DOM.pagerFirst) DOM.pagerFirst.disabled = page <= 1;
            if (DOM.pagerPrev) DOM.pagerPrev.disabled = page <= 1;
            if (DOM.pagerNext) DOM.pagerNext.disabled = page >= totalPages;
            if (DOM.pagerLast) DOM.pagerLast.disabled = page >= totalPages;
        },

        updateSortIndicators() {
            const indicators = DOM.cardsContainer?.querySelectorAll('.sort-indicator');
            if (!indicators?.length) return;

            indicators.forEach(el => {
                const field = el?.dataset?.field;

                if (field === this.sortField) {
                    el.textContent = this.sortDir === 'asc' ? ' ↑' : '↓';
                } else {
                    el.textContent = '';
                }
            });
        }
    };


    // ============================================================================
    // TABELA DESKTOP - RENDERIZAÇÃO
    // ============================================================================

    const DesktopTable = {
        render(data) {

            if (!DOM.tableBody) return;

            if (!data || data.length === 0) {
                DOM.tableBody.innerHTML =
                    '<tr><td colspan="8" class="text-center">Nenhum agendamento encontrado.</td></tr>';
                return;
            }

            DOM.tableBody.innerHTML = data.map(item => this.renderRow(item)).join('');

            // Adicionar event listeners
            DOM.tableBody.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.getAttribute('data-action');
                    const id = btn.getAttribute('data-id');

                    if (action && id) {
                        Events.dispatchAgendamentoAction(action, Number(id));
                    }
                });
            });
        },

        renderRow(item) {
            const status = String(item.status || '').toLowerCase();
            const tipo = String(item.tipo || '').toLowerCase();
            const isRecorrente = item.recorrente === 1 || item.recorrente === true;

            // Calcular status dinâmico
            const statusDinamico = item.status_dinamico ||
                Format.calcularStatusDinamico(item.data_pagamento || item.data_agendada, isRecorrente, status);

            // Classe CSS para linha baseada no status
            const rowClass = statusDinamico === 'vencido' ? 'row-vencido' :
                statusDinamico === 'hoje' ? 'row-hoje' : '';

            // Usar data_pagamento (ou data_agendada como fallback)
            const dataExibicao = item.data_pagamento || item.data_agendada;

            return `
                <tr data-id="${item.id}" class="${rowClass}">
                    <td>
                        ${Format.escapeHtml(item.titulo || '-')}
                        ${Format.recorrenteIcon(isRecorrente)}
                    </td>
                    <td>
                        <span class="ag-tipo-badge ${tipo}">
                            ${tipo === 'receita' ? 'Receita' : 'Despesa'}
                        </span>
                    </td>
                    <td>${Format.escapeHtml(item.categoria?.nome || item.categoria_nome || '-')}</td>
                    <td>${Format.escapeHtml(item.conta?.nome || item.conta_nome || '-')}</td>
                    <td>${Format.currency(item.valor_centavos)}</td>
                    <td>${dataExibicao ? Format.escapeHtml(Format.dateTime(dataExibicao)) : '-'}</td>
                    <td>${Format.statusDinamicoBadge(statusDinamico)}</td>
                    <td>${this.renderActions(status, item.id, statusDinamico)}</td>
                </tr>
            `;
        },

        renderActions(status, itemId, statusDinamico) {
            const viewBtn = `
                <button type="button" class="btn-action btn-view" data-action="visualizar" data-id="${itemId}" 
                    title="👁️ Visualizar detalhes completos">
                    <i class="fas fa-eye"></i>
                </button>
            `;

            // Verificar cancelado PRIMEIRO
            if (status === 'cancelado') {
                return `
                    ${viewBtn}
                    <button type="button" class="btn-action btn-restore" data-action="reativar" data-id="${itemId}" 
                        title="🔄 Reativar agendamento">
                        <i class="fas fa-undo-alt"></i>
                    </button>
                `;
            }

            if (status === 'pendente' || statusDinamico !== 'executado') {
                return `
                    ${viewBtn}
                    <button type="button" class="btn-action btn-pay" data-action="pagar" data-id="${itemId}" 
                        title="✅ Executar agendamento - Cria lançamento">
                        <i class="fas fa-check"></i>
                    </button>
                    <button type="button" class="btn-action btn-edit" data-action="editar" data-id="${itemId}" 
                        title="✏️ Editar agendamento">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button type="button" class="btn-action btn-cancel" data-action="cancelar" data-id="${itemId}" 
                        title="❌ Cancelar agendamento">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            }

            return viewBtn;
        }
    };


    // ============================================================================
    // AGENDAMENTOS - CARREGAMENTO E CACHE
    // ============================================================================

    const Agendamentos = {
        async load() {
            const stack = new Error().stack;

            // Se há filtro rápido ativo OU está sendo aplicado, não recarregar
            if (STATE.activeQuickFilter || STATE.applyingQuickFilter) {
                return;
            }

            try {
                const res = await fetch(`${CONFIG.BASE_URL}api/agendamentos`, {
                    credentials: 'include'
                });

                if (await Paywall.handleResponse(res)) {
                    DesktopTable.render([]);
                    MobileCards.setData([]);
                    return;
                }

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: Erro ao carregar agendamentos.`);
                }

                const json = await res.json();

                if (json?.status !== 'success') {
                    throw new Error(json?.message || 'Erro ao carregar agendamentos.');
                }

                const itens = Array.isArray(json?.data?.itens) ? json.data.itens : [];

                // Atualizar cache
                STATE.cache.clear();
                itens.forEach(item => {
                    if (item?.id !== undefined && item?.id !== null) {
                        STATE.cache.set(String(item.id), item);
                    }
                });

                // Renderizar ambas as visualizações (CSS controla qual é exibida)
                DesktopTable.render(itens);
                MobileCards.setData(itens);

            } catch (error) {
                DesktopTable.render([]);
                MobileCards.setData([]);

                console.error('Erro ao carregar agendamentos:', error);

                if (typeof Swal !== 'undefined' && Swal?.fire) {
                    Swal.fire('Erro', error.message || 'Não foi possível carregar os agendamentos.', 'error');
                }
            }
        },

        getFromCache(id) {
            const key = id ? String(id) : '';
            return key && STATE.cache.has(key) ? STATE.cache.get(key) : null;
        }
    };


    // ============================================================================
    // MODAL - GERENCIAMENTO
    // ============================================================================

    const Modal = {
        open() {
            if (!DOM.modal) return null;

            if (window.bootstrap) {
                const modal = bootstrap.Modal.getOrCreateInstance(DOM.modal);
                modal.show();
                return modal;
            }

            DOM.modal.classList.add('show');
            DOM.modal.style.display = 'block';
            return DOM.modal;
        },

        close() {
            if (!DOM.modal) return;

            if (window.bootstrap) {
                const modal = bootstrap.Modal.getInstance(DOM.modal) ||
                    bootstrap.Modal.getOrCreateInstance(DOM.modal);
                modal.hide();
                return;
            }

            DOM.modal.querySelector('.btn-close')?.click();
        },

        resetMode() {
            if (DOM.agId) DOM.agId.value = '';
            if (DOM.modalTitle) DOM.modalTitle.textContent = 'Novo Agendamento';
            if (DOM.modalSubmitBtn) DOM.modalSubmitBtn.textContent = 'Salvar';
        },

        showError(message) {
            const alertBox = document.getElementById('agAlert');
            if (!alertBox) return;

            alertBox.textContent = message;
            alertBox.classList.remove('d-none');
        },

        hideError() {
            const alertBox = document.getElementById('agAlert');
            if (!alertBox) return;

            alertBox.textContent = '';
            alertBox.classList.add('d-none');
        }
    };


    // ============================================================================
    // VISUALIZAÇÃO - MODAL DE DETALHES
    // ============================================================================

    const Visualizacao = {
        currentId: null,

        open(agendamento) {
            if (!agendamento || !DOM.modalVisualizacao) return;

            this.currentId = agendamento.id;
            this.preencherDados(agendamento);

            if (window.bootstrap) {
                const modal = bootstrap.Modal.getOrCreateInstance(DOM.modalVisualizacao);
                modal.show();
            } else {
                DOM.modalVisualizacao.classList.add('show');
                DOM.modalVisualizacao.style.display = 'block';
            }
        },

        close() {
            if (!DOM.modalVisualizacao) return;

            if (window.bootstrap) {
                const modal = bootstrap.Modal.getInstance(DOM.modalVisualizacao);
                modal?.hide();
            } else {
                DOM.modalVisualizacao.classList.remove('show');
                DOM.modalVisualizacao.style.display = 'none';
            }

            this.currentId = null;
        },

        preencherDados(agendamento) {
            const isRecorrente = agendamento.recorrente === 1 || agendamento.recorrente === true;
            const statusDinamico = agendamento.status_dinamico ||
                Format.calcularStatusDinamico(agendamento.data_pagamento, isRecorrente, agendamento.status);

            // Título e subtítulo do modal
            const titulo = agendamento.titulo || 'Sem título';
            const subtitulo = `ID: #${agendamento.id}`;
            document.getElementById('modalVisualizacaoLabel').textContent = titulo;
            document.getElementById('viewSubtitle').textContent = subtitulo;

            // Informações Principais
            this.setElementText('viewTitulo', titulo);

            const tipoEl = document.getElementById('viewTipo');
            const tipo = agendamento.tipo === 'receita' ? 'Receita' : 'Despesa';
            tipoEl.innerHTML = `<span class="ag-tipo-badge ${agendamento.tipo}">${tipo}</span>`;

            this.setElementText('viewValor', Format.currency(agendamento.valor_centavos || agendamento.valor));

            const statusEl = document.getElementById('viewStatus');
            statusEl.innerHTML = Format.statusDinamicoBadge(statusDinamico);

            // Classificação
            this.setElementText('viewCategoria', agendamento.categoria?.nome || agendamento.categoria_nome || 'Não informada');
            this.setElementText('viewConta', agendamento.conta?.nome || agendamento.conta_nome || 'Não informada');

            // Datas
            this.setElementText('viewDataAgendada', Format.dateTime(agendamento.data_pagamento));
            this.setElementText('viewCriadoEm', Format.dateTime(agendamento.created_at));

            // Próxima execução
            if (agendamento.proxima_execucao) {
                this.showElement('viewProximaExecucaoItem');
                this.setElementText('viewProximaExecucao', Format.dateTime(agendamento.proxima_execucao));
            } else {
                this.hideElement('viewProximaExecucaoItem');
            }

            // Última execução
            if (agendamento.concluido_em) {
                this.showElement('viewConcluidoEmItem');
                this.setElementText('viewConcluidoEm', Format.dateTime(agendamento.concluido_em));
            } else {
                this.hideElement('viewConcluidoEmItem');
            }

            // Recorrência
            this.setElementText('viewRecorrente', isRecorrente ? '✅ Sim (automático)' : '❌ Não (único)');

            if (isRecorrente && agendamento.recorrencia_freq) {
                this.showElement('viewRecorrenciaFreqItem');
                const freqTexto = this.getFrequenciaTexto(agendamento.recorrencia_freq);
                this.setElementText('viewRecorrenciaFreq', freqTexto);

                if (agendamento.recorrencia_intervalo && agendamento.recorrencia_intervalo > 1) {
                    this.showElement('viewRecorrenciaIntervaloItem');
                    this.setElementText('viewRecorrenciaIntervalo', `A cada ${agendamento.recorrencia_intervalo} ${freqTexto.toLowerCase()}`);
                } else {
                    this.hideElement('viewRecorrenciaIntervaloItem');
                }
            } else {
                this.hideElement('viewRecorrenciaFreqItem');
                this.hideElement('viewRecorrenciaIntervaloItem');
            }

            // Notificações
            this.setElementText('viewCanalEmail', agendamento.canal_email ? '✅ Ativo' : '❌ Inativo');
            this.setElementText('viewCanalInapp', agendamento.canal_inapp ? '✅ Ativo' : '❌ Inativo');

            if (agendamento.notificado_em) {
                this.showElement('viewNotificadoEmItem');
                this.setElementText('viewNotificadoEm', Format.dateTime(agendamento.notificado_em));
            } else {
                this.hideElement('viewNotificadoEmItem');
            }

            // Descrição
            if (agendamento.descricao && agendamento.descricao.trim()) {
                this.showElement('viewDescricaoSection');
                this.setElementText('viewDescricao', agendamento.descricao);
            } else {
                this.hideElement('viewDescricaoSection');
            }
        },

        getFrequenciaTexto(freq) {
            const textos = {
                'diario': 'Diário',
                'semanal': 'Semanal',
                'mensal': 'Mensal',
                'anual': 'Anual',
                // Fallback para valores em inglês (caso existam dados antigos)
                'daily': 'Diário',
                'weekly': 'Semanal',
                'monthly': 'Mensal',
                'yearly': 'Anual'
            };
            return textos[freq?.toLowerCase()] || freq || 'Não especificado';
        },

        setElementText(id, text) {
            const el = document.getElementById(id);
            if (el) el.textContent = text || '-';
        },

        showElement(id) {
            const el = document.getElementById(id);
            if (el) el.style.display = '';
        },

        hideElement(id) {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        },

        editarAtual() {
            if (!this.currentId) return;

            const agendamento = Agendamentos.getFromCache(this.currentId);
            if (!agendamento) {
                Swal.fire('Erro', 'Agendamento não encontrado.', 'error');
                return;
            }

            this.close();
            Actions.edit(agendamento);
        }
    };


    // ============================================================================
    // FORMULÁRIO - PREENCHIMENTO E VALIDAÇÃO
    // ============================================================================

    const FormManager = {
        async fill(record) {
            if (!record || !DOM.form) return;

            const tipo = String(record.tipo || 'despesa').toLowerCase();

            await Selects.loadContas();
            await Selects.loadCategorias(tipo);

            if (DOM.agId) DOM.agId.value = record.id ?? '';
            if (DOM.modalTitle) DOM.modalTitle.textContent = 'Editar agendamento';
            if (DOM.modalSubmitBtn) DOM.modalSubmitBtn.textContent = 'Salvar alterações';

            if (DOM.agTitulo) DOM.agTitulo.value = record.titulo || '';
            if (DOM.agTipo) DOM.agTipo.value = tipo;

            if (DOM.agDataPagamento) {
                const dtValue = Format.toDateTimeLocalValue(record.data_pagamento || record.created_at);
                DOM.agDataPagamento.value = dtValue || Format.getLocalDateTimeInputValue();
            }

            const categoriaId = record.categoria_id ?? record.categoria?.id ?? '';
            const contaId = record.conta_id ?? record.conta?.id ?? '';

            if (DOM.agCategoria) DOM.agCategoria.value = categoriaId ? String(categoriaId) : '';
            if (DOM.agConta) DOM.agConta.value = contaId ? String(contaId) : '';

            const valorCentavos = Number(record.valor_centavos ?? record.valor ?? 0);
            if (DOM.agValor) DOM.agValor.value = MoneyMask.format(valorCentavos / 100);

            if (DOM.agDescricao) DOM.agDescricao.value = record.descricao || '';

            // Preencher frequência se recorrente (campo: recorrencia_freq)
            // O campo agFrequencia define automaticamente se é recorrente ou não
            const recorrenteValor = record.recorrente === 1 || record.recorrente === '1';
            const agFrequencia = document.getElementById('agFrequencia');

            if (recorrenteValor && record.recorrencia_freq && agFrequencia) {
                agFrequencia.value = record.recorrencia_freq;
                // Mostrar campo de repetições se tiver frequência
                const repeticoesGroup = document.getElementById('repeticoesGroup');
                if (repeticoesGroup) repeticoesGroup.style.display = 'block';
            } else if (agFrequencia) {
                agFrequencia.value = '';
            }

            // Preencher repetições se existir
            if (record.recorrencia_repeticoes) {
                const agRepeticoes = document.getElementById('agRepeticoes');
                if (agRepeticoes) agRepeticoes.value = record.recorrencia_repeticoes;
            }

            // Atualizar checkboxes de notificação com valores do registro
            const checkboxSistema = document.getElementById('agCanalInapp');
            const checkboxEmail = document.getElementById('agCanalEmail');

            // Usar valores específicos do registro, ou fallback para lembrar
            const canalInappValor = record.canal_inapp === 1 || record.canal_inapp === '1' || record.canal_inapp === true;
            const canalEmailValor = record.canal_email === 1 || record.canal_email === '1' || record.canal_email === true;

            if (checkboxSistema) checkboxSistema.checked = canalInappValor;
            if (checkboxEmail) checkboxEmail.checked = canalEmailValor;

            Modal.hideError();
        },

        validate() {
            const erros = [];

            const titulo = (DOM.agTitulo?.value || '').trim();
            const dataPagamento = (DOM.agDataPagamento?.value || '').trim();
            const categoriaId = (DOM.agCategoria?.value || '').trim();
            const valorBruto = DOM.agValor?.value || '';

            if (!titulo) erros.push('Informe o título.');
            if (!dataPagamento) erros.push('Informe a data e hora do pagamento.');
            if (!categoriaId) erros.push('Selecione a categoria.');

            const valorCentavos = MoneyMask.parseToCents(valorBruto);
            if (valorCentavos < 0) {
                erros.push('Informe um valor válido.');
            }

            return { valid: erros.length === 0, erros, valorCentavos };
        },

        getData(valorCentavos) {
            const payload = new FormData();
            const token = CSRF.get();

            if (token) {
                payload.append('_token', token);
                payload.append('csrf_token', token);
            }

            payload.append('titulo', (DOM.agTitulo?.value || '').trim());
            payload.append('data_pagamento', (DOM.agDataPagamento?.value || '').trim());
            payload.append('tipo', DOM.agTipo?.value || 'despesa');
            payload.append('categoria_id', (DOM.agCategoria?.value || '').trim());

            const contaId = (DOM.agConta?.value || '').trim();
            if (contaId) payload.append('conta_id', contaId);

            payload.append('valor', DOM.agValor?.value || '');
            payload.append('valor_centavos', String(valorCentavos));

            const descricao = (DOM.agDescricao?.value || '').trim();
            if (descricao) payload.append('descricao', descricao);

            // Verificar se há recorrência selecionada
            const frequencia = (DOM.agFrequencia?.value || '').trim();
            const recorrente = frequencia !== '' ? '1' : '0';
            payload.append('recorrente', recorrente);

            // Enviar frequência se houver recorrência
            if (recorrente === '1' && frequencia) {
                payload.append('recorrencia_freq', frequencia);

                // Enviar número de repetições se informado
                const repeticoes = (DOM.agRepeticoes?.value || '').trim();
                if (repeticoes) {
                    payload.append('recorrencia_repeticoes', repeticoes);
                }
            }

            // Campos de notificação
            const canalInapp = document.getElementById('agCanalInapp');
            const canalEmail = document.getElementById('agCanalEmail');
            payload.append('canal_inapp', canalInapp?.checked ? '1' : '0');
            payload.append('canal_email', canalEmail?.checked ? '1' : '0');

            // Lembrar (compatível com campo antigo)
            payload.append('lembrar', (canalInapp?.checked || canalEmail?.checked) ? '1' : '0');

            return payload;
        },

        reset() {
            DOM.form?.reset();
            Modal.resetMode();
            Modal.hideError();

            if (DOM.agDataPagamento) {
                DOM.agDataPagamento.value = Format.getLocalDateTimeInputValue();
            }

            if (DOM.agValor) {
                DOM.agValor.value = MoneyMask.format(0);
            }

            // Resetar select de frequência e campo de repetições
            if (DOM.agFrequencia) {
                DOM.agFrequencia.value = '';
            }
            if (DOM.agRepeticoes) {
                DOM.agRepeticoes.value = '';
            }
            const repeticoesGroup = document.getElementById('repeticoesGroup');
            if (repeticoesGroup) {
                repeticoesGroup.style.display = 'none';
            }

            // Reset da recorrência agora é feito via select dropdown (já tratado acima)

            // Ativar todos os checkboxes de notificação por padrão
            const checkboxSistema = document.getElementById('agCanalInapp');
            const checkboxEmail = document.getElementById('agCanalEmail');
            if (checkboxSistema) checkboxSistema.checked = true;
            if (checkboxEmail) checkboxEmail.checked = true;
        }
    };


    // ============================================================================
    // AÇÕES - GERENCIAMENTO DE AGENDAMENTOS
    // ============================================================================

    const Actions = {
        async edit(record) {
            if (!record) return;

            await FormManager.fill(record);
            Modal.open();
        },

        async save() {
            Modal.hideError();

            const agendamentoId = (DOM.agId?.value || '').trim();
            const isEditMode = !!agendamentoId;

            const { valid, erros, valorCentavos } = FormManager.validate();

            if (!valid) {
                Modal.showError(erros.join('\n'));
                return;
            }

            Swal.fire({
                title: isEditMode ? 'Salvando alterações...' : 'Salvando...',
                text: 'Aguarde enquanto o agendamento é salvo.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                // Tentar renovar token CSRF ANTES de criar o payload
                try {
                    await CSRF.refresh();
                } catch (csrfError) {
                    console.warn('[Actions.save] Não foi possível renovar CSRF, usando token atual:', csrfError);
                }

                // Criar payload DEPOIS do refresh para garantir token atualizado
                const payload = FormManager.getData(valorCentavos);

                const endpoint = isEditMode
                    ? `${CONFIG.BASE_URL}api/agendamentos/${agendamentoId}`
                    : `${CONFIG.BASE_URL}api/agendamentos`;

                const json = await HTTP.fetchWithCSRF(endpoint, {
                    method: 'POST',
                    body: payload
                });

                if (json?.errors) {
                    const detalhes = Object.values(json.errors).flat().join('\n');
                    Modal.showError(detalhes || (json?.message || 'Erros de validação.'));
                    throw new Error('Erros de validação.');
                }

                Swal.fire(
                    'Sucesso',
                    isEditMode ? 'Agendamento atualizado com sucesso!' : 'Agendamento salvo com sucesso!',
                    'success'
                );

                FormManager.reset();
                Modal.close();
                await Agendamentos.load();

            } catch (error) {
                console.error(error);
                Swal.close();

                if (error.message && error.message !== 'Erros de validação.') {
                    Swal.fire('Erro', error.message, 'error');
                }
            }
        },

        async pagar(id) {
            // Buscar agendamento do cache para verificar se é recorrente
            const agendamento = Agendamentos.getFromCache(id);
            const isRecorrente = agendamento?.recorrente === 1 || agendamento?.recorrente === true;

            const confirm = await Swal.fire({
                title: 'Executar agendamento?',
                html: isRecorrente
                    ? '<p>✅ Um lançamento será criado.</p><p>🔁 Este agendamento é <strong>recorrente</strong> e continuará ativo na próxima data.</p>'
                    : '<p>✅ Um lançamento será criado.</p><p>❌ Este agendamento será <strong>finalizado</strong> e não aparecerá mais.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '✔️ Sim, executar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981',
            });

            if (!confirm.isConfirmed) return;

            Swal.fire({
                title: 'Executando...',
                text: 'Criando lançamento e atualizando agendamento.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const json = await HTTP.fetchWithCSRF(`${CONFIG.BASE_URL}api/agendamentos/${id}/executar`, {
                    method: 'POST',
                });

                if (!json || json?.status !== 'success') {
                    throw new Error(json?.message || 'Falha ao executar o agendamento.');
                }

                const mensagemSucesso = json?.data?.message || 'Agendamento executado com sucesso!';

                Swal.fire({
                    icon: 'success',
                    title: 'Executado!',
                    html: `<p>${mensagemSucesso}</p>`,
                    confirmButtonColor: '#10b981',
                });

                await Agendamentos.load();
                Events.dispatchDataChanged('transactions', 'create');

            } catch (err) {
                console.error(err);
                Swal.close();
                Swal.fire('Erro', err.message || 'Falha ao executar agendamento.', 'error');
            }
        },

        async cancelar(id) {
            const confirm = await Swal.fire({
                title: '❌ Cancelar agendamento?',
                html: '<p>O agendamento será marcado como <strong>cancelado</strong>.</p><p>✅ Você poderá reativá-lo depois se quiser.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, cancelar',
                cancelButtonText: 'Não',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
            });

            if (!confirm.isConfirmed) return;

            Swal.fire({
                title: 'Cancelando...',
                text: 'Aguarde um momento.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const json = await HTTP.fetchWithCSRF(`${CONFIG.BASE_URL}api/agendamentos/${id}/cancelar`, {
                    method: 'POST',
                });

                if (!json || json?.status !== 'success') {
                    throw new Error(json?.message || 'Falha ao cancelar o agendamento.');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Cancelado!',
                    text: 'Agendamento cancelado com sucesso.',
                    confirmButtonColor: '#10b981',
                });

                await Agendamentos.load();

            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao cancelar agendamento.', 'error');
            }
        },

        async reativar(id) {
            const confirm = await Swal.fire({
                title: '🔄 Reativar agendamento?',
                html: '<p>O agendamento voltará para o status <strong>pendente</strong>.</p><p>✅ Ele aparecerá novamente na lista de agendamentos ativos.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, reativar',
                cancelButtonText: 'Não',
                confirmButtonColor: '#10b981',
            });

            if (!confirm.isConfirmed) return;

            Swal.fire({
                title: 'Reativando...',
                text: 'Aguarde um momento.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const json = await HTTP.fetchWithCSRF(`${CONFIG.BASE_URL}api/agendamentos/${id}/reativar`, {
                    method: 'POST',
                });

                if (!json || json?.status !== 'success') {
                    throw new Error(json?.message || 'Falha ao reativar o agendamento.');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Reativado!',
                    text: 'Agendamento reativado com sucesso.',
                    confirmButtonColor: '#10b981',
                });

                await Agendamentos.load();

            } catch (err) {
                console.error(err);
                Swal.close();
                Swal.fire('Erro', err.message || 'Falha ao reativar agendamento.', 'error');
            }
        }
    };


    // ============================================================================
    // FILTROS
    // ============================================================================

    const Filters = {
        async loadCategorias() {
            try {
                const data = await HTTP.fetchJSON(`${CONFIG.BASE_URL}api/categorias`);
                if (!data) return;

                const cats = Helpers.listFromPayload(data);

                if (DOM.filtroCategoria) {
                    DOM.filtroCategoria.innerHTML = '<option value="">Todas as Categorias</option>';

                    cats.forEach(cat => {
                        const opt = document.createElement('option');
                        opt.value = cat.id;
                        opt.textContent = cat.nome;
                        DOM.filtroCategoria.appendChild(opt);
                    });
                }
            } catch (err) {
                console.error('Erro ao carregar categorias para filtro:', err);
            }
        },

        async loadContas() {
            try {
                const data = await HTTP.fetchJSON(`${CONFIG.BASE_URL}api/contas?only_active=1&with_balances=0`);
                if (!data) return;

                const contas = Helpers.listFromPayload(data);

                if (DOM.filtroConta) {
                    DOM.filtroConta.innerHTML = '<option value="">Todas as Contas</option>';

                    contas.forEach(conta => {
                        const opt = document.createElement('option');
                        opt.value = conta.id;
                        const instituicao = conta.instituicao ? ` · ${conta.instituicao}` : '';
                        opt.textContent = `${conta.nome}${instituicao}`;
                        DOM.filtroConta.appendChild(opt);
                    });
                }
            } catch (err) {
                console.error('Erro ao carregar contas para filtro:', err);
            }
        },

        apply() {

            // Se há filtro rápido ativo, não aplicar filtros normais
            if (STATE.activeQuickFilter) {
                return;
            }

            const isDesktop = Helpers.isDesktopView();
            const allData = Array.from(STATE.cache.values());

            let filtered = allData;

            if (DOM.filtroTipo?.value) {
                filtered = filtered.filter(item => item.tipo === DOM.filtroTipo.value);
            }

            if (DOM.filtroCategoria?.value) {
                const catId = Number(DOM.filtroCategoria.value);
                filtered = filtered.filter(item => item.categoria_id === catId);
            }

            if (DOM.filtroConta?.value) {
                const contaId = Number(DOM.filtroConta.value);
                filtered = filtered.filter(item => item.conta_id === contaId);
            }

            if (DOM.filtroStatus?.value) {
                filtered = filtered.filter(item => item.status === DOM.filtroStatus.value);
            }

            if (isDesktop) {
                DesktopTable.render(filtered);
            }

            MobileCards.setData(filtered);
        },

        clear() {
            if (DOM.filtroTipo) DOM.filtroTipo.value = '';
            if (DOM.filtroCategoria) DOM.filtroCategoria.value = '';
            if (DOM.filtroConta) DOM.filtroConta.value = '';
            if (DOM.filtroStatus) DOM.filtroStatus.value = '';

            // Limpar filtro rápido ativo
            STATE.activeQuickFilter = null;

            // Limpar filtros rápidos
            document.querySelectorAll('.quick-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            Agendamentos.load();
        },

        /**
         * Aplicar filtro rápido
         */
        applyQuickFilter(filterType) {

            // Marcar que estamos aplicando um filtro rápido
            STATE.applyingQuickFilter = true;
            STATE.activeQuickFilter = filterType;

            // Atualizar visualmente os botões IMEDIATAMENTE
            document.querySelectorAll('.quick-filter-btn').forEach(btn => {
                if (btn.dataset.filter === filterType) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            const allData = Array.from(STATE.cache.values());
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);

            let filtered = allData;

            switch (filterType) {
                case 'hoje':
                    filtered = allData.filter(item => {
                        // Usar data_pagamento (campo da API) com fallback para data_agendada
                        const dataStr = item.data_pagamento || item.data_agendada;
                        if (!dataStr) return false;
                        const dataItem = new Date(dataStr);
                        dataItem.setHours(0, 0, 0, 0);
                        return dataItem.getTime() === hoje.getTime();
                    });
                    break;

                case 'semana':
                    const fimSemana = new Date(hoje);
                    fimSemana.setDate(hoje.getDate() + 7);
                    filtered = allData.filter(item => {
                        // Usar data_pagamento (campo da API) com fallback para data_agendada
                        const dataStr = item.data_pagamento || item.data_agendada;
                        if (!dataStr) return false;
                        const dataItem = new Date(dataStr);
                        dataItem.setHours(0, 0, 0, 0);
                        return dataItem >= hoje && dataItem <= fimSemana;
                    });
                    break;

                case 'vencidos':
                    filtered = allData.filter(item => {
                        // Usar data_pagamento (campo da API) com fallback para data_agendada
                        const dataStr = item.data_pagamento || item.data_agendada;
                        if (!dataStr) return false;
                        // Apenas itens pendentes podem estar vencidos
                        if (item.status !== 'pendente') return false;
                        const dataItem = new Date(dataStr);
                        dataItem.setHours(0, 0, 0, 0);
                        const isRecorrente = item.recorrente === 1 || item.recorrente === true;
                        const statusDinamico = Format.calcularStatusDinamico(dataStr, isRecorrente, item.status);
                        return statusDinamico === 'vencido';
                    });
                    break;

                case 'receitas':
                    filtered = allData.filter(item => item.tipo === 'receita');
                    break;

                case 'despesas':
                    filtered = allData.filter(item => item.tipo === 'despesa');
                    break;

                case 'recorrentes':
                    filtered = allData.filter(item => item.recorrente === 1 || item.recorrente === true);
                    break;

                default:
                    filtered = allData;
            }


            // Renderizar resultados
            DesktopTable.render(filtered);
            MobileCards.setData(filtered);

            // Liberar flag e confirmar estado
            STATE.applyingQuickFilter = false;
            STATE.activeQuickFilter = filterType;

        }
    };


    // ============================================================================
    // EVENTOS
    // ============================================================================

    const Events = {
        dispatchAgendamentoAction(action, id) {
            document.dispatchEvent(new CustomEvent('lukrato:agendamento-action', {
                detail: { action, id }
            }));
        },

        dispatchDataChanged(resource, action) {
            document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                detail: { resource, action }
            }));
        },

        setupCardInteractions() {
            DOM.cardsContainer?.addEventListener('click', (event) => {
                const target = event.target;

                // Ordenação
                const sortBtn = target.closest('[data-sort]');
                if (sortBtn?.dataset?.sort) {
                    MobileCards.setSort(sortBtn.dataset.sort);
                    return;
                }

                // Toggle de detalhes
                const toggleBtn = target.closest('[data-toggle="details"]');
                if (toggleBtn) {
                    this.handleCardToggle(event, toggleBtn);
                    return;
                }

                // Ações
                const actionBtn = target.closest('[data-ag-action]');
                if (actionBtn) {
                    const action = actionBtn.dataset.agAction;
                    const id = actionBtn.dataset.id;

                    if (action && id) {
                        this.dispatchAgendamentoAction(action, Number(id));
                    }
                }
            });
        },

        handleCardToggle(event, toggleBtn) {
            event.preventDefault();
            event.stopPropagation();

            // Debounce
            if (toggleBtn.dataset.toggling === 'true') return;

            toggleBtn.dataset.toggling = 'true';
            setTimeout(() => { toggleBtn.dataset.toggling = 'false'; }, 300);

            const card = toggleBtn.closest('.ag-card, .card-item');
            if (!card) return;

            const details = card.querySelector('.ag-card-details');
            const isCurrentlyOpen = details?.classList.contains('show');

            // Fechar outros cards (accordion)
            if (!isCurrentlyOpen && DOM.cardsContainer) {
                const allCards = DOM.cardsContainer.querySelectorAll('.ag-card[aria-expanded="true"], .card-item[aria-expanded="true"]');

                allCards.forEach(otherCard => {
                    if (otherCard !== card) {
                        this.closeCard(otherCard);
                    }
                });
            }

            // Toggle do card atual
            if (isCurrentlyOpen) {
                this.closeCard(card);
            } else {
                this.openCard(card);
            }
        },

        openCard(card) {
            const details = card.querySelector('.ag-card-details');
            const toggleBtn = card.querySelector('[data-toggle="details"]');

            card.setAttribute('aria-expanded', 'true');

            if (details) {
                details.classList.add('show');
                details.style.setProperty('max-height', '800px', 'important');
                details.style.setProperty('opacity', '1', 'important');
                details.style.setProperty('padding', '1rem', 'important');
                details.style.setProperty('overflow', 'visible', 'important');
                details.style.setProperty('display', 'block', 'important');
                details.style.setProperty('visibility', 'visible', 'important');
                details.style.setProperty('height', 'auto', 'important');
            }

            if (toggleBtn) {
                const textSpan = toggleBtn.querySelector('.card-toggle-text');

                if (textSpan) textSpan.textContent = 'Fechar detalhes';
            }

            // Scroll suave (mobile)
            if (window.innerWidth <= 768) {
                setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 300);
            }
        },

        closeCard(card) {
            const details = card.querySelector('.ag-card-details');
            const toggleBtn = card.querySelector('[data-toggle="details"]');

            card.setAttribute('aria-expanded', 'false');

            if (details) {
                details.classList.remove('show');
                details.style.setProperty('max-height', '0', 'important');
                details.style.setProperty('opacity', '0', 'important');
                details.style.setProperty('padding', '0 1rem', 'important');
                details.style.setProperty('overflow', 'hidden', 'important');
            }

            if (toggleBtn) {
                const textSpan = toggleBtn.querySelector('.card-toggle-text');

                if (textSpan) textSpan.textContent = 'Ver detalhes';
            }
        },

        setupPagination() {
            DOM.pagerFirst?.addEventListener('click', () => {
                MobileCards.currentPage = 1;
                MobileCards.render();
            });

            DOM.pagerPrev?.addEventListener('click', () => {
                MobileCards.currentPage = Math.max(1, MobileCards.currentPage - 1);
                MobileCards.render();
            });

            DOM.pagerNext?.addEventListener('click', () => {
                MobileCards.currentPage += 1;
                MobileCards.render();
            });

            DOM.pagerLast?.addEventListener('click', () => {
                const { totalPages } = MobileCards.getPagedData();
                MobileCards.currentPage = totalPages;
                MobileCards.render();
            });
        },

        setupFormSubmit() {
            DOM.form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                await Actions.save();
            });
        },

        setupTipoChange() {
            DOM.agTipo?.addEventListener('change', async () => {
                try {
                    await Selects.loadCategorias(DOM.agTipo.value);
                } catch (error) {
                    console.error(error);
                    Modal.showError(error?.message || 'Não foi possível carregar as categorias.');
                }
            });
        },

        setupModalEvents() {

            DOM.modal?.addEventListener('shown.bs.modal', async () => {
                try {
                    // Carregar dados necessários para o formulário
                    await Promise.all([
                        Selects.loadContas(),
                        Selects.loadCategorias(DOM.agTipo?.value || 'despesa')
                    ]);

                    // Setar data atual se o campo estiver vazio
                    if (DOM.agDataPagamento && !DOM.agDataPagamento.value) {
                        DOM.agDataPagamento.value = Format.getLocalDateTimeInputValue();
                    }

                    // Setar valor zero formatado se estiver vazio
                    if (DOM.agValor && !DOM.agValor.value) {
                        DOM.agValor.value = MoneyMask.format(0);
                    }

                    // Configurar botões de toggle (precisa ser feito toda vez que o modal abre)
                    Events.setupToggleButtonsInModal();

                    // Configurar evento de recorrência para mostrar/ocultar campo de repetições
                    Events.setupRecurrenceToggle();

                    Modal.hideError();
                } catch (error) {
                    Modal.showError(error?.message || 'Não foi possível carregar os dados do formulário.');
                }
            });

            DOM.modal?.addEventListener('hidden.bs.modal', () => {
                FormManager.reset();
            });
        },

        setupToggleButtons() {
            // Esta função é chamada na inicialização, mas os botões só existem no modal
            // Por isso, criamos setupToggleButtonsInModal() que é chamado quando o modal abre
        },

        setupToggleButtonsInModal() {
            // O modal agora usa select dropdown (agFrequencia) em vez de toggle button
            // Mantido para compatibilidade, mas o RecurrenceToggle não é mais utilizado
            // RecurrenceToggle.init() - comentado pois não há mais botão toggle
        },

        setupRecurrenceToggle() {
            const selectFrequencia = document.getElementById('agFrequencia');
            const repeticoesGroup = document.getElementById('repeticoesGroup');
            const inputRepeticoes = document.getElementById('agRepeticoes');

            if (!selectFrequencia || !repeticoesGroup) return;

            // Mostrar/ocultar campo de repetições baseado no select de frequência
            selectFrequencia.addEventListener('change', function () {
                if (this.value && this.value !== '') {
                    repeticoesGroup.style.display = 'block';
                } else {
                    repeticoesGroup.style.display = 'none';
                    if (inputRepeticoes) inputRepeticoes.value = '';
                }
            });

            // Inicializar estado correto ao abrir modal
            if (selectFrequencia.value && selectFrequencia.value !== '') {
                repeticoesGroup.style.display = 'block';
            } else {
                repeticoesGroup.style.display = 'none';
            }
        },

        setupActionHandler() {
            document.addEventListener('lukrato:agendamento-action', async (event) => {
                const { action, id } = event?.detail || {};
                if (!id || !action) return;

                const record = Agendamentos.getFromCache(id);

                switch (action) {
                    case 'visualizar':
                        if (!record) {
                            Swal.fire('Erro', 'Agendamento não encontrado.', 'error');
                            return;
                        }
                        Visualizacao.open(record);
                        break;

                    case 'editar':
                        if (!record) {
                            Swal.fire('Erro', 'Agendamento não encontrado para edição.', 'error');
                            return;
                        }
                        await Actions.edit(record);
                        break;

                    case 'pagar':
                        await Actions.pagar(id);
                        break;

                    case 'cancelar':
                        await Actions.cancelar(id);
                        break;

                    case 'reativar':
                        await Actions.reativar(id);
                        break;
                }
            });
        },

        setupFilters() {
            DOM.filtroTipo?.addEventListener('change', () => {
                STATE.activeQuickFilter = null; // Desativa filtro r\u00e1pido
                document.querySelectorAll('.quick-filter-btn').forEach(btn => btn.classList.remove('active'));
                Filters.apply();
            });
            DOM.filtroCategoria?.addEventListener('change', () => {
                STATE.activeQuickFilter = null; // Desativa filtro r\u00e1pido
                document.querySelectorAll('.quick-filter-btn').forEach(btn => btn.classList.remove('active'));
                Filters.apply();
            });
            DOM.filtroConta?.addEventListener('change', () => {
                STATE.activeQuickFilter = null; // Desativa filtro r\u00e1pido
                document.querySelectorAll('.quick-filter-btn').forEach(btn => btn.classList.remove('active'));
                Filters.apply();
            });
            DOM.filtroStatus?.addEventListener('change', () => {
                STATE.activeQuickFilter = null; // Desativa filtro r\u00e1pido
                document.querySelectorAll('.quick-filter-btn').forEach(btn => btn.classList.remove('active'));
                Filters.apply();
            });

            DOM.btnLimparFiltros?.addEventListener('click', () => Filters.clear());

            // Filtros rápidos
            document.querySelectorAll('.quick-filter-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const filterType = this.dataset.filter;
                    const isCurrentlyActive = this.classList.contains('active');
                    const isSameFilter = STATE.activeQuickFilter === filterType;


                    // Se clicar no mesmo filtro que já está ativo, desativa
                    if (isSameFilter && isCurrentlyActive) {
                        this.classList.remove('active');
                        STATE.activeQuickFilter = null;
                        Agendamentos.load();
                    } else {
                        // Remover active de todos os botões
                        document.querySelectorAll('.quick-filter-btn').forEach(b => {
                            b.classList.remove('active');
                        });

                        // Adicionar active ao botão clicado
                        this.classList.add('active');

                        // Aplicar o filtro
                        Filters.applyQuickFilter(filterType);
                    }
                });
            });
        },

        setupAddButton() {
            DOM.btnAddAgendamento?.addEventListener('click', async () => {
                if (STATE.accessRestricted) {
                    await Paywall.prompt();
                    return;
                }

                FormManager.reset();

                await Selects.loadContas();
                await Selects.loadCategorias(DOM.agTipo?.value || 'despesa');

                Modal.open();

                // Configurar botões de toggle após abrir o modal
                setTimeout(() => {
                    Events.setupToggleButtonsInModal();
                }, 300);
            });
        }
    };


    // ============================================================================
    // INICIALIZAÇÃO
    // ============================================================================

    const init = async () => {
        // Aplicar CSRF token
        CSRF.apply(CSRF.get());

        // Limpar estado inicial dos filtros rápidos
        STATE.activeQuickFilter = null;
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Configurar máscara de dinheiro
        if (DOM.agValor) {
            MoneyMask.bind(DOM.agValor);
            if (!DOM.agValor.value) {
                DOM.agValor.value = MoneyMask.format(0);
            }
        }

        // Configurar data padrão se estiver vazio
        if (DOM.agDataPagamento && !DOM.agDataPagamento.value) {
            DOM.agDataPagamento.value = Format.getLocalDateTimeInputValue();
        }

        // Configurar event listeners do Paywall
        DOM.paywallCta?.addEventListener('click', () => Paywall.goToBilling());

        // Botão editar do modal de visualização
        DOM.btnEditarFromView?.addEventListener('click', () => Visualizacao.editarAtual());

        // Configurar todos os event listeners
        Events.setupCardInteractions();
        Events.setupPagination();
        Events.setupFormSubmit();
        Events.setupTipoChange();
        Events.setupModalEvents();
        Events.setupToggleButtons();
        Events.setupActionHandler();
        Events.setupFilters();
        Events.setupAddButton();

        // Renderizar cards iniciais (Mobile)
        MobileCards.render();

        // Carregar dados iniciais de selects e filtros
        try {
            await Promise.all([
                Selects.loadContas(),
                Selects.loadCategorias(DOM.agTipo?.value || 'despesa'),
                Filters.loadCategorias(),
                Filters.loadContas()
            ]);
        } catch (error) {
            console.error('Erro ao carregar dados iniciais:', error);
        }

        // Carregar listagem de agendamentos
        await Agendamentos.load();
    };

    // Executar inicialização
    init();
});
