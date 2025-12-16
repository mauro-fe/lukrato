document.addEventListener('DOMContentLoaded', () => {
    const PAYWALL_MESSAGE = 'Agendamentos sao exclusivos do plano Pro.';

    const base = (typeof LK !== 'undefined' && typeof LK.getBase === 'function')
        ? LK.getBase()
        : (document.querySelector('meta[name="base-url"]')?.content || '/');
    const tokenId = document.querySelector('meta[name="csrf-token-id"]')?.content || 'default';
    let csrfToken = '';

    const tableElement = document.getElementById('agendamentosTable');
    const tableContainer = document.getElementById('agList');
    const cardsContainer = document.getElementById('agCards');
    const pager = document.getElementById('agCardsPager');
    const pagerInfo = document.getElementById('agPagerInfo');
    const pagerFirst = document.getElementById('agPagerFirst');
    const pagerPrev = document.getElementById('agPagerPrev');
    const pagerNext = document.getElementById('agPagerNext');
    const pagerLast = document.getElementById('agPagerLast');
    const paywallBox = document.getElementById('agPaywall');
    const paywallMessageEl = document.getElementById('agPaywallMessage');
    const paywallCta = document.getElementById('agPaywallCta');

    let accessRestricted = false;

    const goToBilling = () => {
        if (typeof openBillingModal === 'function') {
            openBillingModal();
        } else {
            location.href = `${base}billing`;
        }
    };

    const showPaywall = (message = PAYWALL_MESSAGE) => {
        if (paywallMessageEl) {
            paywallMessageEl.textContent = message;
        }
        if (paywallBox) {
            paywallBox.classList.remove('d-none');
            paywallBox.removeAttribute('hidden');
        }
        if (tableContainer) {
            tableContainer.classList.add('d-none');
        }
    };

    const hidePaywall = () => {
        if (paywallBox) {
            paywallBox.classList.add('d-none');
            paywallBox.setAttribute('hidden', 'hidden');
        }
        if (tableContainer) {
            tableContainer.classList.remove('d-none');
        }
        accessRestricted = false;
    };

    paywallCta?.addEventListener('click', goToBilling);

    const promptUpgrade = async (message) => {
        const text = message || PAYWALL_MESSAGE;
        if (typeof Swal !== 'undefined' && Swal.fire) {
            const ret = await Swal.fire({
                title: 'Acesso restrito',
                text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Assinar plano Pro',
                cancelButtonText: 'Agora nao',
                reverseButtons: true,
                focusConfirm: true
            });
            if (ret.isConfirmed) goToBilling();
        } else if (confirm(`${text}\n\nIr para a pagina de assinatura agora?`)) {
            goToBilling();
        }
    };

    const handleFetch403 = async (response) => {
        if (!response) return false;

        if (response.status === 401) {
            const here = encodeURIComponent(location.pathname + location.search);
            location.href = `${base}login?return=${here}`;
            return true;
        }

        if (response.status === 403) {
            let msg = PAYWALL_MESSAGE;
            try {
                const data = await response.clone().json();
                msg = data?.message || msg;
            } catch { }

            showPaywall(msg);
            if (!accessRestricted) {
                accessRestricted = true;
                await promptUpgrade(msg);
            }

            return true;
        }

        hidePaywall();
        return false;
    };

    const form = document.getElementById('formAgendamento');
    const agIdInput = document.getElementById('agId');
    const categoriaSelect = document.getElementById('agCategoria');
    const contaSelect = document.getElementById('agConta');
    const tipoSelect = document.getElementById('agTipo');
    const valorInput = document.getElementById('agValor');
    const dataHoraInput = document.getElementById('agDataHora');
    const agModal = document.getElementById('modalAgendamento');
    const modalTitle = document.getElementById('modalAgendamentoTitle');
    const modalSubmitBtn = document.querySelector('#modalAgendamento [type=\"submit\"]');
    const recurrenceButton = document.getElementById('agRecorrenteToggle');
    const recurrenceInput = document.getElementById('agRecorrente');
    const selectCache = {
        contas: null,
        categorias: new Map()
    };
    const cache = new Map();

    let tableInstance = null;

    const hideFormError = () => {
        const alertBox = document.getElementById('agAlert');
        if (!alertBox) return;
        alertBox.textContent = '';
        alertBox.classList.add('d-none');
    };

    const showFormError = (message) => {
        const alertBox = document.getElementById('agAlert');
        if (!alertBox) return;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    };

    const fetchJSON = async (url, options = {}) => {
        const res = await fetch(url, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                ...(options.headers || {})
            },
            ...options
        });

        if (await handleFetch403(res)) {
            return null;
        }

        let data = null;
        try {
            data = await res.json();
        } catch {
            // resposta vazia ou texto puro
        }

        if (!res.ok) {
            const message = data?.message || 'Nao foi possivel carregar os dados.';
            throw new Error(message);
        }

        return data;
    };

    const listFromPayload = (payload) => {
        if (!payload) return [];
        if (Array.isArray(payload)) return payload;
        if (Array.isArray(payload.data)) return payload.data;
        if (Array.isArray(payload.items)) return payload.items;
        if (Array.isArray(payload.itens)) return payload.itens;
        return [];
    };

    const fillSelect = (selectEl, items, {
        placeholder = null,
        getValue = (item) => item?.id ?? '',
        getLabel = (item) => item?.nome ?? ''
    } = {}) => {
        if (!selectEl) return;
        const previous = selectEl.value;
        selectEl.innerHTML = '';

        if (placeholder !== null) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholder;
            selectEl.appendChild(opt);
        }

        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(getValue(item) ?? '');
            option.textContent = getLabel(item) ?? '';
            selectEl.appendChild(option);
        });

        if (previous && selectEl.querySelector(`option[value="${previous}"]`)) {
            selectEl.value = previous;
        }
    };

    const loadContasSelect = async (force = false) => {
        if (!contaSelect) return;
        if (selectCache.contas && !force) {
            fillSelect(contaSelect, selectCache.contas, {
                placeholder: 'Todas as contas (opcional)',
                getLabel: (item) => {
                    const instituicao = item?.instituicao ? ` � ${item.instituicao}` : '';
                    return `${item?.nome ?? ''}${instituicao}`;
                }
            });
            return;
        }

        const data = await fetchJSON(`${base}api/accounts?only_active=1&with_balances=0`);
        if (!data) return;
        const items = listFromPayload(data);
        selectCache.contas = items;
        fillSelect(contaSelect, items, {
            placeholder: 'Todas as contas (opcional)',
            getLabel: (item) => {
                const instituicao = item?.instituicao ? ` � ${item.instituicao}` : '';
                return `${item?.nome ?? ''}${instituicao}`;
            }
        });
    };

    const loadCategoriasSelect = async (tipo = 'despesa', force = false) => {
        if (!categoriaSelect) return;
        const key = tipo || 'todos';
        if (selectCache.categorias.has(key) && !force) {
            fillSelect(categoriaSelect, selectCache.categorias.get(key), {
                placeholder: 'Selecione uma categoria'
            });
            return;
        }

        const qs = tipo ? `?tipo=${encodeURIComponent(tipo)}` : '';
        const data = await fetchJSON(`${base}api/categorias${qs}`);
        if (!data) return;
        const items = listFromPayload(data);
        selectCache.categorias.set(key, items);
        fillSelect(categoriaSelect, items, {
            placeholder: 'Selecione uma categoria'
        });
    };

    const moneyMask = (() => {
        const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

        const format = (num) => {
            const n = Number(num);
            return Number.isFinite(n) ? formatter.format(n) : '';
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

        return { format, bind };
    })();

    const parseMoneyToCents = (value) => {
        if (value === null || value === undefined) return 0;
        const normalized = String(value)
            .replace(/[^\d,.-]/g, '')
            .replace(/\./g, '')
            .replace(',', '.');
        const number = Number(normalized);
        if (Number.isFinite(number)) {
            return Math.round(number * 100);
        }
        return 0;
    };

    const getLocalDateTimeInputValue = () => {
        const now = new Date();
        const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 16);
    };

    // Máscara e valores padrão
    moneyMask.bind(valorInput);
    if (valorInput && !valorInput.value) {
        valorInput.value = moneyMask.format(0);
    }
    if (dataHoraInput && !dataHoraInput.value) {
        dataHoraInput.value = getLocalDateTimeInputValue();
    }

    const getCsrf = () => {
        if (typeof LK !== 'undefined' && typeof LK.getCSRF === 'function') {
            return LK.getCSRF();
        }
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    };
    const applyCsrfToken = (token) => {
        if (!token) return;
        csrfToken = token;
        document.querySelectorAll(`[data-csrf-id="${tokenId}"]`).forEach((el) => {
            if (el.tagName === 'META') {
                el.setAttribute('content', token);
            } else if ('value' in el) {
                el.value = token;
            }
        });
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        if (window.LK) {
            window.LK.csrfToken = token;
        }
    };
    const refreshCsrf = async () => {
        const res = await fetch(`${base}api/csrf/refresh`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ token_id: tokenId })
        });
        const data = await res.json().catch(() => null);
        if (data?.token) {
            applyCsrfToken(data.token);
            return data.token;
        }
        throw new Error('Falha ao renovar CSRF');
    };
    applyCsrfToken(getCsrf());

    const fetchWithCsrf = async (url, options = {}, retry = true) => {
        const res = await fetch(url, {
            credentials: options.credentials || 'include',
            ...options,
            headers: {
                'Accept': 'application/json',
                ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
                'X-CSRF-TOKEN': csrfToken || getCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {})
            }
        });
        const resClone = res.clone();
        let json = null;
        try { json = await res.json(); } catch (_) { }

        const isCsrfError = res.status === 403 && (
            (json?.errors && json.errors.csrf_token) ||
            String(json?.message || '').toLowerCase().includes('csrf')
        );

        if (isCsrfError && retry) {
            try {
                await refreshCsrf();
                return fetchWithCsrf(url, options, false);
            } catch (_) {
                // segue para o fluxo normal de erro
            }
        }

        if (res.status === 403 && typeof handleFetch403 === 'function') {
            await handleFetch403(resClone);
        }

        if (!res.ok || (json && json.status === 'error')) {
            if (res.status === 422 && json?.errors) {
                const detalhes = Object.values(json.errors).flat().join('\n');
                throw new Error(detalhes || json?.message || 'Erros de validacao.');
            }
            const msg = json?.message || `HTTP ${res.status}`;
            throw new Error(msg);
        }

        if (json?.token) {
            applyCsrfToken(json.token);
        }

        return json;
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (match) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[match] || match));

    const formatCurrency = (value) => {
        const number = Number(value ?? 0) / 100;
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2
        }).format(number || 0);
    };

    const formatDateTime = (value) => {
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
    };

    const toDateTimeLocalValue = (value) => {
        if (!value) return '';
        try {
            const dt = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(dt.getTime())) return '';
            const local = new Date(dt.getTime() - dt.getTimezoneOffset() * 60000);
            return local.toISOString().slice(0, 16);
        } catch {
            return '';
        }
    };

    const resetFormMode = () => {
        if (agIdInput) agIdInput.value = '';
        if (modalTitle) modalTitle.textContent = 'Agendar pagamento';
        if (modalSubmitBtn) modalSubmitBtn.textContent = 'Salvar Agendamento';
    };

    const applyRecurrenceVisual = (isActive) => {
        if (!recurrenceButton) return;
        recurrenceButton.dataset.recorrente = isActive ? '1' : '0';
        recurrenceButton.classList.toggle('btn-primary', isActive);
        recurrenceButton.classList.toggle('btn-outline-secondary', !isActive);
        recurrenceButton.textContent = isActive ? 'Sim, recorrente' : 'Nao, agendamento unico';
    };

    const statusBadge = (status) => {
        const map = {
            pendente: 'warning',
            enviado: 'info',
            concluido: 'success',
            cancelado: 'danger'
        };
        const color = map[String(status).toLowerCase()] || 'secondary';
        return `<span class="badge bg-${color} text-uppercase">${escapeHtml(status || '-')}</span>`;
    };

    const getTipoClass = (tipo) => {
        const value = String(tipo || '').toLowerCase();
        if (value === 'receita') return 'tipo-receita';
        if (value === 'despesa') return 'tipo-despesa';
        return '';
    };

    const mobileCards = {
        data: [],
        pageSize: 6,
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
            if (field === 'valor_centavos') return Number(value) || 0;
            if (field === 'data_pagamento') {
                const date = value ? new Date(String(value).replace(' ', 'T')) : null;
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
            if (!cardsContainer) return;

            const { list, total, page, totalPages } = this.getPagedData();
            const parts = [];

            parts.push(`
               <div class="cards-header">
                 <button type="button" class="cards-header-btn" data-sort="data_pagamento">
                     <span>Data</span>
                      <span class="sort-indicator" data-field="data_pagamento"></span>
                    </button>
                    
                    <button type="button" class="ag-cards-header-btn cards-header-btn" data-sort="tipo">
                        <span>Tipo</span>
                        <span class="ag-sort-indicator sort-indicator" data-field="tipo"></span>
                    </button>
                    <button type="button" class="ag-cards-header-btn cards-header-btn" data-sort="valor_centavos">
                        <span>Valor</span>
                        <span class="ag-sort-indicator sort-indicator" data-field="valor_centavos"></span>
                    </button>
                    <span class="ag-cards-header-btn cards-header-btn-actions">Ações</span>
                </div>
            `);

            if (!total) {
                parts.push(`
                    <div class="ag-card card-item card-empty">
                        <div class="card-empty-text">
                            Nenhum agendamento encontrado.
                        </div>
                    </div>
                `);
                cardsContainer.innerHTML = parts.join('');
                this.updatePager(0, 1, 1);
                this.updateSortIndicators();
                return;
            }

            const isXs = window.matchMedia('(max-width: 768px)').matches;

            list.forEach((item) => {
                const id = item?.id ?? '';
                const tipo = String(item?.tipo || '').toLowerCase();
                const tipoLabel = tipo ? (tipo.charAt(0).toUpperCase() + tipo.slice(1)) : '-';
                const tipoClass = getTipoClass(tipo);
                const valor = formatCurrency(item?.valor_centavos || item?.valor);
                const data = formatDateTime(item?.data_pagamento || item?.created_at);
                const titulo = item?.titulo || '-';
                const categoria = item?.categoria?.nome || item?.categoria_nome || '-';
                const conta = item?.conta?.nome || item?.conta_nome || '-';
                const recorrente = item?.recorrente === 1 || item?.recorrente === '1';
                const descricao = item?.descricao || '--';
                const status = String(item?.status || '').toLowerCase();

                const actions = [];
                if (status === 'pendente') {
                    actions.push(`
                        <button class="lk-btn ghost ag-card-btn" data-ag-action="pagar" data-id="${id}" title="Confirmar pagamento">
                            <i class="fas fa-check"></i>
                        </button>
                    `);
                    actions.push(`
                        <button class="lk-btn ghost ag-card-btn" data-ag-action="editar" data-id="${id}" title="Editar agendamento">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    `);
                    actions.push(`
                        <button class="lk-btn danger ag-card-btn" data-ag-action="cancelar" data-id="${id}" title="Cancelar agendamento">
                            <i class="fas fa-times"></i>
                        </button>
                    `);
                } else if (status === 'cancelado') {
                    actions.push(`
                        <button class="lk-btn ghost ag-card-btn" data-ag-action="reativar" data-id="${id}" title="Reativar agendamento">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                    `);
                }
                const actionsHtml = actions.join('');

                parts.push(`
                    <article class="ag-card card-item" data-id="${id}" aria-expanded="false">
                        <div class="ag-card-main card-main">
                            <span class="ag-card-date card-date">${escapeHtml(data)}</span>
                            <span class="ag-card-type card-type">
                                <span class="badge-tipo ${tipoClass}">${escapeHtml(tipoLabel)}</span>
                            </span>
                            <span class="ag-card-value card-value ${tipoClass}">${escapeHtml(valor)}</span>
                            <span class="ag-card-actions card-actions" data-slot="main">${actionsHtml}</span>
                        </div>


                        <button class="ag-card-toggle card-toggle" type="button" data-toggle="details" aria-label="Ver detalhes do agendamento">
                            <span class="ag-card-toggle-icon card-toggle-icon"><i class="fas fa-chevron-right"></i></span>
                            <span> Ver detalhes</span>
                        </button>

                        <div class="ag-card-details card-details">
                        
                            <div class="ag-card-detail-row card-detail-row">
                                <span class="ag-card-detail-label card-detail-label">Título</span>
                                <span class="ag-card-detail-value card-detail-value">${escapeHtml(titulo)}</span>
                            </div>
                            <div class="ag-card-detail-row card-detail-row">
                                <span class="ag-card-detail-label card-detail-label">Status</span>
                                <span class="ag-card-status card-status" aria-label="Status">${statusBadge(status)}</span>
                            </div>
                            <div class="ag-card-detail-row card-detail-row">
                                <span class="ag-card-detail-label card-detail-label">Categoria</span>
                                <span class="ag-card-detail-value card-detail-value">${escapeHtml(categoria)}</span>
                            </div>
                            <div class="ag-card-detail-row card-detail-row">
                                <span class="ag-card-detail-label card-detail-label">Conta</span>
                                <span class="ag-card-detail-value card-detail-value">${escapeHtml(conta)}</span>
                            </div>
                            <div class="ag-card-detail-row card-detail-row">
                                <span class="ag-card-detail-label card-detail-label">Recorrente</span>
                                <span class="ag-card-detail-value card-detail-value">${recorrente ? 'Sim' : 'nao'}</span>
                            </div>
                            <div class="ag-card-detail-row card-detail-row">
                                <span class="ag-card-detail-label card-detail-label">Descrição</span>
                                <span class="ag-card-detail-value card-detail-value">${escapeHtml(descricao)}</span>
                            </div>
                            ${isXs ? `<div class="ag-card-detail-row card-detail-row actions-row">
                                <span class="ag-card-detail-label card-detail-label">Ações</span>
                                <span class="ag-card-detail-value card-detail-value actions-slot">${actionsHtml}</span>
                            </div>` : ''}
                        </div>
                    </article>
                `);
            });

            cardsContainer.innerHTML = parts.join('');
            this.updatePager(total, page, totalPages);
            this.updateSortIndicators();
        },

        updatePager(total, page, totalPages) {
            if (!pager || !pagerInfo) return;

            if (!total) {
                pagerInfo.textContent = 'Nenhum agendamento';
                [pagerFirst, pagerPrev, pagerNext, pagerLast].forEach((btn) => {
                    if (btn) btn.disabled = true;
                });
                return;
            }

            pagerInfo.textContent = `pagina ${page} de ${totalPages}`;

            if (pagerFirst) pagerFirst.disabled = page <= 1;
            if (pagerPrev) pagerPrev.disabled = page <= 1;
            if (pagerNext) pagerNext.disabled = page >= totalPages;
            if (pagerLast) pagerLast.disabled = page >= totalPages;
        },

        updateSortIndicators() {
            const indicators = cardsContainer?.querySelectorAll('.ag-sort-indicator sort-indicator');
            if (!indicators) return;
            indicators.forEach((el) => {
                const field = el?.dataset?.field;
                if (field === this.sortField) {
                    el.textContent = this.sortDir === 'asc' ? '?' : '?';
                } else {
                    el.textContent = '';
                }
            });
        }
    };

    if (pagerFirst) {
        pagerFirst.addEventListener('click', () => {
            mobileCards.currentPage = 1;
            mobileCards.render();
        });
    }
    if (pagerPrev) {
        pagerPrev.addEventListener('click', () => {
            mobileCards.currentPage = Math.max(1, mobileCards.currentPage - 1);
            mobileCards.render();
        });
    }
    if (pagerNext) {
        pagerNext.addEventListener('click', () => {
            mobileCards.currentPage += 1;
            mobileCards.render();
        });
    }
    if (pagerLast) {
        pagerLast.addEventListener('click', () => {
            const { totalPages } = mobileCards.getPagedData();
            mobileCards.currentPage = totalPages;
            mobileCards.render();
        });
    }

    cardsContainer?.addEventListener('click', (event) => {
        const sortBtn = event.target.closest('[data-sort]');
        if (sortBtn?.dataset?.sort) {
            mobileCards.setSort(sortBtn.dataset.sort);
            return;
        }

        const toggleBtn = event.target.closest('[data-toggle="details"]');
        if (toggleBtn) {
            const article = toggleBtn.closest('.ag-card');
            if (article) {
                const expanded = article.getAttribute('aria-expanded') === 'true';
                article.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                article.classList.toggle('open', !expanded);
            }
        }

        const actionBtn = event.target.closest('[data-ag-action]');
        if (actionBtn) {
            const action = actionBtn.dataset.agAction;
            const id = actionBtn.dataset.id;
            if (action && id) {
                document.dispatchEvent(new CustomEvent('lukrato:agendamento-action', {
                    detail: { action, id: Number(id) }
                }));
            }
        }
    });

    const ensureTable = () => {
        if (tableInstance || !tableElement || typeof Tabulator === 'undefined') {
            return tableInstance;
        }

        tableInstance = new Tabulator(tableElement, {
            layout: 'fitColumns',
            reactiveData: false,
            placeholder: 'Nenhum agendamento encontrado.',
            index: 'id',
            height: tableElement.dataset.height || '',
            pagination: false,
            columnDefaults: {
                tooltip: true,
                headerFilter: true,
                headerFilterPlaceholder: 'filtrar...'
            },
            columns: [
                {
                    title: 'Titulo',
                    field: 'titulo',
                    minWidth: 100,
                    formatter: (cell) => escapeHtml(cell.getValue() || '-')
                },
                {
                    title: 'Tipo',
                    field: 'tipo',
                    minWidth: 130,
                    headerFilter: 'select',
                    headerFilterParams: {
                        values: {
                            '': 'Todos',
                            despesa: 'Despesa',
                            receita: 'Receita'
                        }
                    },
                    formatter: (cell) => {
                        const value = String(cell.getValue() || '').toLowerCase();
                        if (!value) return '-';
                        return value.charAt(0).toUpperCase() + value.slice(1);
                    }
                },
                {
                    title: 'Categoria',
                    field: 'categoria.nome',
                    minWidth: 100,
                    formatter: (cell) => escapeHtml(cell.getValue() || '-'),
                    headerFilter: 'input',
                    headerFilterPlaceholder: 'Categoria'
                },
                {
                    title: 'Conta',
                    field: 'conta.nome',
                    minWidth: 160,
                    formatter: (cell) => escapeHtml(cell.getValue() || '-'),
                    headerFilter: 'input',
                    headerFilterPlaceholder: 'Conta'
                },
                {
                    title: 'Valor',
                    field: 'valor_centavos',
                    hozAlign: 'right',
                    width: 140,
                    formatter: (cell) => formatCurrency(cell.getValue())
                },
                {
                    title: 'Data',
                    field: 'data_pagamento',
                    width: 160,
                    formatter: (cell) => escapeHtml(formatDateTime(cell.getValue()))
                },
                {
                    title: 'Status',
                    field: 'status',
                    width: 140,
                    hozAlign: 'center',
                    headerFilter: 'select',
                    headerFilterParams: {
                        values: {
                            '': 'Todos',
                            pendente: 'Pendente',
                            enviado: 'Enviado',
                            concluido: 'Concluido',
                            cancelado: 'Cancelado'
                        }
                    },
                    formatter: (cell) => statusBadge(cell.getValue())
                },
                {
                    title: 'Acoes',
                    field: 'acoes',
                    headerSort: false,
                    hozAlign: 'center',
                    width: 190,
                    formatter: (cell) => {
                        const data = cell.getRow()?.getData?.() || {};
                        const status = String(data?.status || '').toLowerCase();
                        const buttons = [];

                        if (status === 'pendente') {
                            buttons.push('<button type="button" class="lk-btn ghost btn-pay" data-action="pagar" title="Confirmar pagamento"><i class="fas fa-check"></i></button>');
                            buttons.push('<button type="button" class="lk-btn ghost btn-edit" data-action="editar" title="Editar agendamento"><i class="fas fa-pencil-alt"></i></button>');
                            buttons.push('<button type="button" class="lk-btn ghost btn-cancel" data-action="cancelar" title="Cancelar agendamento"><i class="fas fa-times"></i></button>');
                        } else if (status === 'cancelado') {
                            buttons.push('<button type="button" class="lk-btn ghost btn-restore" data-action="reativar" title="Reativar agendamento"><i class="fas fa-undo-alt"></i></button>');
                        }

                        return `<div class="d-flex justify-content-center gap-2">${buttons.join('')}</div>`;
                    },
                    cellClick: (e, cell) => {
                        const button = e.target.closest('[data-action]');
                        if (!button) return;
                        const action = button.getAttribute('data-action');
                        const row = cell.getRow();
                        const data = row?.getData();
                        if (!data || !action) return;

                        document.dispatchEvent(new CustomEvent('lukrato:agendamento-action', {
                            detail: {
                                action,
                                id: data.id ?? null,
                                record: data
                            }
                        }));
                    }
                }
            ]
        });

        return tableInstance;
    };

    async function loadAgendamentos(preserveFilters = true) {
        const table = ensureTable();
        if (!table) return;

        const filters = preserveFilters ? [...table.getHeaderFilters()] : [];

        try {
            const res = await fetch(`${base}api/agendamentos`, { credentials: 'include' });
            if (await handleFetch403(res)) {
                table.clearData(); // limpa tabela
                mobileCards.setData([]);
                return;
            }
            const json = await res.json();
            if (json?.status !== 'success') throw new Error(json?.message || 'Erro ao carregar agendamentos.');

            const itens = Array.isArray(json?.data?.itens) ? json.data.itens : [];
            cache.clear();
            itens.forEach((item) => {
                if (item?.id !== undefined && item?.id !== null) {
                    cache.set(String(item.id), item);
                }
            });

            await table.replaceData(itens);
            mobileCards.setData(itens);

            if (filters.length) {
                filters.forEach((filter) => {
                    if (filter?.field) {
                        table.setHeaderFilterValue(filter.field, filter.value ?? '');
                    }
                });
            }
        } catch (error) {
            table.clearData();
            mobileCards.setData([]);
            console.error(error);
            if (typeof Swal !== 'undefined' && Swal?.fire) {
                Swal.fire('Erro', 'Nao foi possivel carregar os agendamentos.', 'error');
            }
        }
    }

    const getAgendamentoFromCache = (id) => {
        const key = id ? String(id) : '';
        return key && cache.has(key) ? cache.get(key) : null;
    };

    const openAgendamentoModal = () => {
        if (!agModal) return null;
        if (window.bootstrap) {
            const modal = bootstrap.Modal.getOrCreateInstance(agModal);
            modal.show();
            return modal;
        }
        agModal.classList.add('show');
        agModal.style.display = 'block';
        return agModal;
    };

    const closeAgendamentoModal = () => {
        if (!agModal) return;
        if (window.bootstrap) {
            const modal = bootstrap.Modal.getInstance(agModal) || bootstrap.Modal.getOrCreateInstance(agModal);
            modal.hide();
            return;
        }
        document.querySelector('#modalAgendamento .btn-close')?.click();
    };

    const fillAgendamentoForm = async (record) => {
        if (!record || !form) return;

        const tipo = String(record.tipo || 'despesa').toLowerCase();
        await loadContasSelect();
        await loadCategoriasSelect(tipo);

        if (agIdInput) agIdInput.value = record.id ?? '';
        if (modalTitle) modalTitle.textContent = 'Editar agendamento';
        if (modalSubmitBtn) modalSubmitBtn.textContent = 'Salvar alterações';

        const tituloInput = document.getElementById('agTitulo');
        const lembrarInput = document.getElementById('agLembrar');
        const categoriaInput = document.getElementById('agCategoria');
        const contaInput = document.getElementById('agConta');
        const valorInputLocal = document.getElementById('agValor');
        const descricaoInput = document.getElementById('agDescricao');
        const canalInappInput = document.getElementById('agCanalInapp');
        const canalEmailInput = document.getElementById('agCanalEmail');

        if (tituloInput) tituloInput.value = record.titulo || '';
        if (dataHoraInput) {
            const dtValue = toDateTimeLocalValue(record.data_pagamento || record.created_at);
            dataHoraInput.value = dtValue || getLocalDateTimeInputValue();
        }
        if (lembrarInput) lembrarInput.value = String(record.lembrar_antes_segundos ?? '0');
        if (tipoSelect) tipoSelect.value = tipo;

        const categoriaId = record.categoria_id ?? record.categoria?.id ?? '';
        const contaId = record.conta_id ?? record.conta?.id ?? '';
        if (categoriaInput) categoriaInput.value = categoriaId ? String(categoriaId) : '';
        if (contaInput) contaInput.value = contaId ? String(contaId) : '';

        const valorCentavos = Number(record.valor_centavos ?? record.valor ?? 0);
        if (valorInputLocal) valorInputLocal.value = moneyMask.format(valorCentavos / 100);
        if (descricaoInput) descricaoInput.value = record.descricao || '';

        const recorrenteValor = record.recorrente === 1 || record.recorrente === '1';
        if (recurrenceInput) {
            recurrenceInput.value = recorrenteValor ? '1' : '0';
            applyRecurrenceVisual(recorrenteValor);
        }

        if (canalInappInput) {
            const canalInappValor = record.canal_inapp;
            const canalInapp = canalInappValor === null || canalInappValor === undefined
                ? true
                : (canalInappValor === 1 || canalInappValor === '1' || canalInappValor === true);
            canalInappInput.checked = canalInapp;
        }
        if (canalEmailInput) {
            const canalEmailValor = record.canal_email;
            const canalEmail = canalEmailValor === null || canalEmailValor === undefined
                ? true
                : (canalEmailValor === 1 || canalEmailValor === '1' || canalEmailValor === true);
            canalEmailInput.checked = canalEmail;
        }

        hideFormError();
    };

    const startEditAgendamento = async (record) => {
        if (!record) return;
        await fillAgendamentoForm(record);
        openAgendamentoModal();
    };

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form) return;
        hideFormError();

        const agendamentoId = (agIdInput?.value || '').trim();
        const isEditMode = !!agendamentoId;

        const tituloInput = document.getElementById('agTitulo');
        const dataHoraInput = document.getElementById('agDataHora');
        const lembrarInput = document.getElementById('agLembrar');
        const tipoInput = document.getElementById('agTipo');
        const categoriaInput = document.getElementById('agCategoria');
        const contaInput = document.getElementById('agConta');
        const valorInput = document.getElementById('agValor');
        const descricaoInput = document.getElementById('agDescricao');
        const recorrenteInput = recurrenceInput || document.getElementById('agRecorrente');
        const canalInappInput = document.getElementById('agCanalInapp');
        const canalEmailInput = document.getElementById('agCanalEmail');

        const titulo = (tituloInput?.value || '').trim();
        const dataPagamento = (dataHoraInput?.value || '').trim();
        const lembrarAntes = lembrarInput?.value || '0';
        const tipo = tipoInput?.value || 'despesa';
        const categoriaId = (categoriaInput?.value || '').trim();
        const contaId = (contaInput?.value || '').trim();
        const valorBruto = valorInput?.value || '';
        const descricao = (descricaoInput?.value || '').trim();
        const recorrente = recorrenteInput?.value === '1';
        const canalInapp = !!(canalInappInput?.checked);
        const canalEmail = !!(canalEmailInput?.checked);

        const erros = [];
        if (!titulo) erros.push('Informe o titulo.');
        if (!dataPagamento) erros.push('Informe a data e hora do pagamento.');
        if (!categoriaId) erros.push('Selecione a categoria.');

        const valorCentavos = parseMoneyToCents(valorBruto);
        if (valorCentavos < 0) {
            erros.push('Informe um valor valido.');
        }

        if (erros.length) {
            showFormError(erros.join('\n'));
            return;
        }

        const payload = new FormData();
        const token = getCsrf();
        if (token) {
            payload.append('_token', token);
            payload.append('csrf_token', token);
        }

        payload.append('titulo', titulo);
        payload.append('data_pagamento', dataPagamento);
        payload.append('lembrar_antes_segundos', lembrarAntes || '0');
        payload.append('tipo', tipo);
        payload.append('categoria_id', categoriaId);
        if (contaId) payload.append('conta_id', contaId);
        payload.append('valor', valorBruto);
        payload.append('valor_centavos', String(valorCentavos));
        if (descricao) payload.append('descricao', descricao);
        payload.append('recorrente', recorrente ? '1' : '0');
        payload.append('canal_inapp', canalInapp ? '1' : '0');
        payload.append('canal_email', canalEmail ? '1' : '0');

        Swal.fire({
            title: isEditMode ? 'Salvando alterações...' : 'Salvando...',
            text: 'Aguarde enquanto o agendamento e salvo.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const endpoint = isEditMode ? `${base}api/agendamentos/${agendamentoId}` : `${base}api/agendamentos`;
            const json = await fetchWithCsrf(endpoint, {
                method: 'POST',
                body: payload
            });

            if (json?.errors) {
                const detalhes = Object.values(json.errors).flat().join('\n');
                showFormError(detalhes || (json?.message || 'Erros de validacao.'));
                throw new Error('Erros de validacao.');
            }

            Swal.fire('Sucesso', isEditMode ? 'Agendamento atualizado com sucesso!' : 'Agendamento salvo com sucesso!', 'success');
            form.reset();
            hideFormError();
            resetFormMode();
            if (recorrenteInput) recorrenteInput.value = '0';
            applyRecurrenceVisual(false);
            if (dataHoraInput) dataHoraInput.value = getLocalDateTimeInputValue();
            if (valorInput) valorInput.value = moneyMask.format(0);
            closeAgendamentoModal();
            await loadAgendamentos(true);
        } catch (error) {
            console.error(error);
            Swal.close();
            if (error.message && error.message !== 'Erros de validacao.') {
                Swal.fire('Erro', error.message, 'error');
            }
        }
    });

    tipoSelect?.addEventListener('change', () => {
        loadCategoriasSelect(tipoSelect.value).catch((error) => {
            console.error(error);
            showFormError(error?.message || 'Nao foi possivel carregar as categorias.');
        });
    });

    agModal?.addEventListener('shown.bs.modal', async () => {
        try {
            await Promise.all([
                loadContasSelect(),
                loadCategoriasSelect(tipoSelect?.value || 'despesa')
            ]);
            if (dataHoraInput && !dataHoraInput.value) {
                dataHoraInput.value = getLocalDateTimeInputValue();
            }
            if (valorInput && !valorInput.value) {
                valorInput.value = moneyMask.format(0);
            }
            hideFormError();
        } catch (error) {
            console.error(error);
            showFormError(error?.message || 'Nao foi possivel carregar os dados do formulario.');
        }
    });

    agModal?.addEventListener('hidden.bs.modal', () => {
        resetFormMode();
        hideFormError();
        if (agIdInput) agIdInput.value = '';
        if (recurrenceInput) recurrenceInput.value = '0';
        applyRecurrenceVisual(false);
    });

    document.addEventListener('lukrato:agendamento-action', async (event) => {
        const detail = event?.detail || {};
        const id = detail.id ? Number(detail.id) : null;
        const action = detail.action || '';
        if (!id || !action) return;

        const record = detail.record || getAgendamentoFromCache(id);

        if (action === 'editar') {
            if (!record) {
                Swal.fire('Erro', 'Agendamento não encontrado para edição.', 'error');
                return;
            }
            await startEditAgendamento(record);
            return;
        }

        if (action === 'pagar') {
            const confirm = await Swal.fire({
                title: 'Confirmar pagamento?',
                text: 'Isso vai gerar um lançamento e remover o agendamento desta lista.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Nao'
            });
            if (!confirm.isConfirmed) return;

            const fd = new FormData();
            const token = getCsrf();
            if (token) {
            fd.append('_token', token);
            fd.append('csrf_token', token);
        }
        fd.append('status', 'concluido');

        try {
            const json = await fetchWithCsrf(`${base}api/agendamentos/${id}/status`, {
                method: 'POST',
                body: fd
            });
            if (!json || json?.status !== 'success') {
                throw new Error(json?.message || 'Falha ao concluir o agendamento.');
            }
            Swal.fire('Sucesso', 'Agendamento concluido!', 'success');
            await loadAgendamentos(true);
            document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                detail: { resource: 'transactions', action: 'create' }
            }));
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao concluir agendamento.', 'error');
            }
        } else if (action === 'cancelar') {
            const confirm = await Swal.fire({
                title: 'Cancelar agendamento?',
                text: 'Ele vai para Cancelados e pode ser reativado.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Nao'
            });
            if (!confirm.isConfirmed) return;

            const fd = new FormData();
            const token = getCsrf();
            if (token) {
                fd.append('_token', token);
                fd.append('csrf_token', token);
            }

            try {
                const json = await fetchWithCsrf(`${base}api/agendamentos/${id}/cancelar`, {
                    method: 'POST',
                    body: fd
                });
                if (!json || json?.status !== 'success') {
                    throw new Error(json?.message || 'Falha ao cancelar o agendamento.');
                }
                Swal.fire('Sucesso', 'Agendamento cancelado.', 'success');
                await loadAgendamentos(true);
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao cancelar agendamento.', 'error');
            }
        } else if (action === 'reativar') {
            const confirm = await Swal.fire({
                title: 'Reativar agendamento?',
                text: 'Volta para Pendentes.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Nao'
            });
            if (!confirm.isConfirmed) return;

            const fd = new FormData();
            const token = getCsrf();
            if (token) {
                fd.append('_token', token);
                fd.append('csrf_token', token);
            }

            try {
                const json = await fetchWithCsrf(`${base}api/agendamentos/${id}/reativar`, {
                    method: 'POST',
                    body: fd
                });
                if (!json || json?.status !== 'success') {
                    throw new Error(json?.message || 'Falha ao reativar o agendamento.');
                }
                Swal.fire('Sucesso', 'Agendamento reativado.', 'success');
                await loadAgendamentos(true);
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao reativar agendamento.', 'error');
            }
        }
    });

    if (recurrenceButton && recurrenceInput) {
        applyRecurrenceVisual(recurrenceInput.value === '1');
        recurrenceButton.addEventListener('click', () => {
            const next = recurrenceInput.value === '1' ? '0' : '1';
            recurrenceInput.value = next;
            applyRecurrenceVisual(next === '1');
        });
    }

    mobileCards.render();
    ensureTable();
    loadContasSelect().catch(console.error);
    loadCategoriasSelect(tipoSelect?.value || 'despesa').catch(console.error);
    loadAgendamentos();
});
