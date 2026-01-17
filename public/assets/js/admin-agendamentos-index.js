document.addEventListener('DOMContentLoaded', () => {
    const PAYWALL_MESSAGE = 'Agendamentos sao exclusivos do plano Pro.';

    const base = (typeof LK !== 'undefined' && typeof LK.getBase === 'function')
        ? LK.getBase()
        : (document.querySelector('meta[name="base-url"]')?.content || window.BASE_URL || '/lukrato/public/').replace(/\/?$/, '/');
    const tokenId = document.querySelector('meta[name="csrf-token-id"]')?.content || 'default';
    let csrfToken = '';

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
    let tableInstance = null;

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
        const tableContainer = document.getElementById('agList');
        if (tableContainer) {
            tableContainer.classList.add('d-none');
        }
    };

    const hidePaywall = () => {
        if (paywallBox) {
            paywallBox.classList.add('d-none');
            paywallBox.setAttribute('hidden', 'hidden');
        }
        const tableContainer = document.getElementById('agList');
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

        const data = await fetchJSON(`${base}api/contas?only_active=1&with_balances=0`);
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
        if (!status) return '<span class="badge bg-secondary text-uppercase">-</span>';
        const statusLower = String(status).toLowerCase();
        const map = {
            pendente: 'warning',
            enviado: 'info',
            concluido: 'success',
            cancelado: 'danger'
        };
        const color = map[statusLower] || 'secondary';
        const label = statusLower.charAt(0).toUpperCase() + statusLower.slice(1);
        return `<span class="badge bg-${color} text-uppercase">${escapeHtml(label)}</span>`;
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
                    <button type="button" class="cards-header-btn" data-sort="tipo">
                        <span>Tipo</span>
                        <span class="sort-indicator" data-field="tipo"></span>
                    </button>
                    <button type="button" class="cards-header-btn" data-sort="valor_centavos">
                        <span>Valor</span>
                        <span class="sort-indicator" data-field="valor_centavos"></span>
                    </button>
                    <span class="cards-header-btn cards-header-btn-actions">Ações</span>
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
                    actions.push(
                        `<button class="lk-btn ghost ag-card-btn" data-ag-action="pagar" data-id="${id}" title="Confirmar pagamento"><i class="fas fa-check"></i></button>`,
                        `<button class="lk-btn ghost ag-card-btn" data-ag-action="editar" data-id="${id}" title="Editar agendamento"><i class="fas fa-pencil-alt"></i></button>`,
                        `<button class="lk-btn danger ag-card-btn" data-ag-action="cancelar" data-id="${id}" title="Cancelar agendamento"><i class="fas fa-times"></i></button>`
                    );
                } else if (status === 'cancelado') {
                    actions.push(
                        `<button class="lk-btn ghost ag-card-btn" data-ag-action="reativar" data-id="${id}" title="Reativar agendamento"><i class="fas fa-undo-alt"></i></button>`
                    );
                }
                const actionsHtml = actions.join('');

                parts.push(`
                    <article class="ag-card card-item" data-id="${id}" aria-expanded="false">
                        <div class="ag-card-header">
                            <div class="ag-card-title-group">
                                <h3 class="ag-card-title">${escapeHtml(titulo)}</h3>
                                <p class="ag-card-subtitle">
                                    <i class="fas fa-calendar-alt"></i>
                                    ${escapeHtml(data)}
                                </p>
                                <p class="ag-card-value ${tipoClass}">
                                    ${escapeHtml(valor)}
                                </p>
                            </div>
                            <span class="ag-tipo-badge ${tipoClass}">
                                <i class="fas ${tipo === 'receita' ? 'fa-arrow-up' : 'fa-arrow-down'}"></i>
                                ${escapeHtml(tipoLabel)}
                            </span>
                        </div>

                        <button type="button" class="card-toggle" data-toggle="details">
                            <span class="card-toggle-text">Ver detalhes</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>

                        <div class="ag-card-details" style="max-height: 0; overflow: hidden; opacity: 0; transition: all 0.3s ease; padding: 0 1rem; background: rgba(241, 245, 249, 0.5);">
                            <div class="ag-card-details-row" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                <span class="ag-card-details-icon" style="width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; background: #e67e22; color: white; border-radius: 8px; font-size: 0.875rem;"><i class="fas fa-folder"></i></span>
                                <div class="ag-card-details-content" style="flex: 1;">
                                    <p class="ag-card-details-label" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 0.25rem 0;">Categoria</p>
                                    <p class="ag-card-details-value" style="font-size: 0.875rem; font-weight: 500; color: #f8f9fa; margin: 0;">${escapeHtml(categoria)}</p>
                                </div>
                            </div>
                            <div class="ag-card-details-row" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid rgba(0,0,0,0.1);">`);
                
                parts.push(`
                                <span class="ag-card-details-icon" style="width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; background: #e67e22; color: white; border-radius: 8px; font-size: 0.875rem;"><i class="fas fa-wallet"></i></span>
                                <div class="ag-card-details-content" style="flex: 1;">
                                    <p class="ag-card-details-label" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 0.25rem 0;">Conta</p>
                                    <p class="ag-card-details-value" style="font-size: 0.875rem; font-weight: 500; color: #f8f9fa; margin: 0;">${escapeHtml(conta)}</p>
                                </div>
                            </div>
                            <div class="ag-card-details-row" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                <span class="ag-card-details-icon" style="width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; background: #e67e22; color: white; border-radius: 8px; font-size: 0.875rem;"><i class="fas fa-sync-alt"></i></span>
                                <div class="ag-card-details-content" style="flex: 1;">
                                    <p class="ag-card-details-label" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 0.25rem 0;">Recorrente</p>
                                    <p class="ag-card-details-value" style="font-size: 0.875rem; font-weight: 500; color: #f8f9fa; margin: 0;">${recorrente ? 'Sim' : 'Não'}</p>
                                </div>
                            </div>
                            ${descricao && descricao !== '--' ? `
                            <div class="ag-card-details-row" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                <span class="ag-card-details-icon" style="width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; background: #e67e22; color: white; border-radius: 8px; font-size: 0.875rem;"><i class="fas fa-align-left"></i></span>
                                <div class="ag-card-details-content" style="flex: 1;">
                                    <p class="ag-card-details-label" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 0.25rem 0;">Descrição</p>
                                    <p class="ag-card-details-value" style="font-size: 0.875rem; font-weight: 500; color: #f8f9fa; margin: 0;">${escapeHtml(descricao)}</p>
                                </div>
                            </div>
                            ` : ''}
                            <div class="ag-card-details-row" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                <span class="ag-card-details-icon" style="width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; background: #e67e22; color: white; border-radius: 8px; font-size: 0.875rem;"><i class="fas fa-info-circle"></i></span>
                                <div class="ag-card-details-content" style="flex: 1;">
                                    <p class="ag-card-details-label" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 0.25rem 0;">Status</p>
                                    <p class="ag-card-details-value" style="font-size: 0.875rem; font-weight: 500; color: #f8f9fa; margin: 0;">${statusBadge(status)}</p>
                                </div>
                            </div>
                            ${actionsHtml ? `
                            <div class="ag-card-details-row" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0;">
                                <span class="ag-card-details-icon" style="width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; background: #e67e22; color: white; border-radius: 8px; font-size: 0.875rem;"><i class="fas fa-cog"></i></span>
                                <div class="ag-card-details-content" style="flex: 1;">
                                    <p class="ag-card-details-label" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 0.25rem 0;">Ações</p>
                                    <div class="ag-card-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                        ${actionsHtml}
                                    </div>
                                </div>
                            </div>
                            ` : ''}
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
            const indicators = cardsContainer?.querySelectorAll('.sort-indicator');
            if (!indicators || !indicators.length) return;
            indicators.forEach((el) => {
                const field = el?.dataset?.field;
                if (field === this.sortField) {
                    el.textContent = this.sortDir === 'asc' ? '↑' : '↓';
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

    // Delegação de eventos para cards
    cardsContainer?.addEventListener('click', (event) => {
        const target = event.target;
        
        const sortBtn = target.closest('[data-sort]');
        if (sortBtn?.dataset?.sort) {
            mobileCards.setSort(sortBtn.dataset.sort);
            return;
        }

        const toggleBtn = target.closest('[data-toggle="details"]');
        
        if (toggleBtn) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            
            // Debounce para evitar duplo-clique
            if (toggleBtn.dataset.toggling === 'true') return;
            toggleBtn.dataset.toggling = 'true';
            setTimeout(() => { toggleBtn.dataset.toggling = 'false'; }, 300);
            
            const card = toggleBtn.closest('.ag-card, .card-item');
            if (!card) return;

            const details = card.querySelector('.ag-card-details');
            const isCurrentlyOpen = details && details.classList.contains('show');
            
            // Accordion behavior: fechar todos os outros cards se vamos abrir este
            if (!isCurrentlyOpen && cardsContainer) {
                const allCards = cardsContainer.querySelectorAll('.ag-card[aria-expanded="true"], .card-item[aria-expanded="true"]');
                allCards.forEach(otherCard => {
                    if (otherCard !== card) {
                        otherCard.setAttribute('aria-expanded', 'false');
                        const otherDetails = otherCard.querySelector('.ag-card-details');
                        if (otherDetails) otherDetails.classList.remove('show');
                        
                        const otherToggle = otherCard.querySelector('[data-toggle="details"]');
                        if (otherToggle) {
                            const otherText = otherToggle.querySelector('.card-toggle-text');
                            const otherIcon = otherToggle.querySelector('i');
                            if (otherText) otherText.textContent = 'Ver detalhes';
                            if (otherIcon) otherIcon.className = 'fas fa-chevron-down';
                        }
                        // Fechar os detalhes visualmente via style
                        if (otherDetails) {
                            otherDetails.style.maxHeight = '0';
                            otherDetails.style.opacity = '0';
                            otherDetails.style.padding = '0 1rem';
                        }
                    }
                });
            }
            
            // Toggle do card atual
            card.setAttribute('aria-expanded', isCurrentlyOpen ? 'false' : 'true');
            
            if (details) {
                details.classList.toggle('show', !isCurrentlyOpen);
                // Aplicar estilos usando setProperty com !important
                if (!isCurrentlyOpen) {
                    // Abrindo
                    details.style.setProperty('max-height', '800px', 'important');
                    details.style.setProperty('opacity', '1', 'important');
                    details.style.setProperty('padding', '1rem', 'important');
                    details.style.setProperty('overflow', 'visible', 'important');
                    details.style.setProperty('display', 'block', 'important');
                    details.style.setProperty('visibility', 'visible', 'important');
                    details.style.setProperty('height', 'auto', 'important');
                } else {
                    // Fechando
                    details.style.setProperty('max-height', '0', 'important');
                    details.style.setProperty('opacity', '0', 'important');
                    details.style.setProperty('padding', '0 1rem', 'important');
                    details.style.setProperty('overflow', 'hidden', 'important');
                }
            }
            
            // Atualizar texto do botão
            const textSpan = toggleBtn.querySelector('.card-toggle-text');
            const iconSpan = toggleBtn.querySelector('i');
            
            if (textSpan) {
                textSpan.textContent = isCurrentlyOpen ? 'Ver detalhes' : 'Fechar detalhes';
            }
            if (iconSpan) {
                iconSpan.className = isCurrentlyOpen ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
            }
            
            // Scroll suave para o card expandido (só no mobile)
            if (!isCurrentlyOpen && window.innerWidth <= 768) {
                setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 300);
            }
            
            return;
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

    const renderTableRows = (data) => {
        const tbody = document.getElementById('agendamentosTableBody');
        if (!tbody) return;

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">Nenhum agendamento encontrado.</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(item => {
            const status = String(item.status || '').toLowerCase();
            const tipo = String(item.tipo || '').toLowerCase();
            const recorrente = item.recorrente === 1 || item.recorrente === '1';
            
            let actions = '';
            if (status === 'pendente') {
                actions = `
                    <button type="button" class="btn-action btn-pay" data-action="pagar" data-id="${item.id}" title="Confirmar pagamento">
                        <i class="fas fa-check"></i>
                    </button>
                    <button type="button" class="btn-action btn-edit" data-action="editar" data-id="${item.id}" title="Editar">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button type="button" class="btn-action btn-cancel" data-action="cancelar" data-id="${item.id}" title="Cancelar">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else if (status === 'cancelado') {
                actions = `
                    <button type="button" class="btn-action btn-restore" data-action="reativar" data-id="${item.id}" title="Reativar">
                        <i class="fas fa-undo-alt"></i>
                    </button>
                `;
            }

            return `
                <tr data-id="${item.id}">
                    <td>${escapeHtml(item.titulo || '-')}</td>
                    <td><span class="ag-tipo-badge ${tipo}">${tipo === 'receita' ? 'Receita' : 'Despesa'}</span></td>
                    <td>${escapeHtml(item.categoria?.nome || item.categoria_nome || '-')}</td>
                    <td>${escapeHtml(item.conta?.nome || item.conta_nome || '-')}</td>
                    <td>${formatCurrency(item.valor_centavos)}</td>
                    <td>${escapeHtml(formatDateTime(item.data_pagamento))}</td>
                    <td>${statusBadge(status)}</td>
                    <td>${actions}</td>
                </tr>
            `;
        }).join('');

        // Adicionar event listeners para os botões de ação
        tbody.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');
                if (action && id) {
                    document.dispatchEvent(new CustomEvent('lukrato:agendamento-action', {
                        detail: { action, id: Number(id) }
                    }));
                }
            });
        });
    };

    const ensureTable = () => {
        // Apenas retorna true se está no modo desktop
        const desktopTable = document.querySelector('.ag-table-desktop');
        if (desktopTable) {
            const displayStyle = getComputedStyle(desktopTable).display;
            return displayStyle !== 'none';
        }
        return false;
    };
    async function loadAgendamentos(preserveFilters = true) {
        const isDesktop = ensureTable();

        try {
            const res = await fetch(`${base}api/agendamentos`, { credentials: 'include' });
            if (await handleFetch403(res)) {
                if (isDesktop) renderTableRows([]);
                mobileCards.setData([]);
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
            cache.clear();
            itens.forEach((item) => {
                if (item?.id !== undefined && item?.id !== null) {
                    cache.set(String(item.id), item);
                }
            });

            // Atualizar tabela desktop e cards mobile
            if (isDesktop) {
                renderTableRows(itens);
            }
            mobileCards.setData(itens);

        } catch (error) {
            if (isDesktop) renderTableRows([]);
            mobileCards.setData([]);
            console.error('Erro ao carregar agendamentos:', error);
            if (typeof Swal !== 'undefined' && Swal?.fire) {
                Swal.fire('Erro', error.message || 'Não foi possível carregar os agendamentos.', 'error');
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
        const dataHoraInput = document.getElementById('agDataPagamento') || document.getElementById('agDataHora');
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
    
    // ==================== FILTROS ====================
    const filtroTipo = document.getElementById('filtroTipo');
    const filtroCategoria = document.getElementById('filtroCategoria');
    const filtroConta = document.getElementById('filtroConta');
    const filtroStatus = document.getElementById('filtroStatus');
    const btnLimparFiltros = document.getElementById('btnLimparFiltros');

    // Carregar opções dos filtros
    const carregarFiltrosCategorias = async () => {
        try {
            const res = await fetch(`${base}api/categorias`, { credentials: 'include' });
            if (!res.ok) return;
            const json = await res.json();
            if (json?.status === 'success' && Array.isArray(json?.data)) {
                const cats = json.data;
                if (filtroCategoria) {
                    filtroCategoria.innerHTML = '<option value="">Todas as Categorias</option>';
                    cats.forEach(cat => {
                        const opt = document.createElement('option');
                        opt.value = cat.id;
                        opt.textContent = cat.nome;
                        filtroCategoria.appendChild(opt);
                    });
                }
            }
        } catch (err) {
            console.error('Erro ao carregar categorias para filtro:', err);
        }
    };

    const carregarFiltrosContas = async () => {
        try {
            const res = await fetch(`${base}api/contas`, { credentials: 'include' });
            if (!res.ok) return;
            const json = await res.json();
            if (json?.status === 'success' && Array.isArray(json?.data)) {
                const contas = json.data;
                if (filtroConta) {
                    filtroConta.innerHTML = '<option value="">Todas as Contas</option>';
                    contas.forEach(conta => {
                        const opt = document.createElement('option');
                        opt.value = conta.id;
                        opt.textContent = conta.nome;
                        filtroConta.appendChild(opt);
                    });
                }
            }
        } catch (err) {
            console.error('Erro ao carregar contas para filtro:', err);
        }
    };

    // Aplicar filtros
    const aplicarFiltros = () => {
        const isDesktop = ensureTable();
        
        // Obter todos os agendamentos do cache
        const allData = Array.from(cache.values());
        
        // Aplicar filtros
        let filtered = allData;
        
        if (filtroTipo?.value) {
            filtered = filtered.filter(item => item.tipo === filtroTipo.value);
        }
        if (filtroCategoria?.value) {
            const catId = Number(filtroCategoria.value);
            filtered = filtered.filter(item => item.categoria_id === catId);
        }
        if (filtroConta?.value) {
            const contaId = Number(filtroConta.value);
            filtered = filtered.filter(item => item.conta_id === contaId);
        }
        if (filtroStatus?.value) {
            filtered = filtered.filter(item => item.status === filtroStatus.value);
        }
        
        // Atualizar visualizações
        if (isDesktop) {
            renderTableRows(filtered);
        }
        mobileCards.setData(filtered);
    };

    // Event listeners dos filtros
    filtroTipo?.addEventListener('change', aplicarFiltros);
    filtroCategoria?.addEventListener('change', aplicarFiltros);
    filtroConta?.addEventListener('change', aplicarFiltros);
    filtroStatus?.addEventListener('change', aplicarFiltros);

    btnLimparFiltros?.addEventListener('click', () => {
        if (filtroTipo) filtroTipo.value = '';
        if (filtroCategoria) filtroCategoria.value = '';
        if (filtroConta) filtroConta.value = '';
        if (filtroStatus) filtroStatus.value = '';
        
        loadAgendamentos(false);
    });

    // ==================== MODAL AGENDAMENTO ====================
    const btnAddAgendamento = document.getElementById('btnAddAgendamento');

    // Abrir modal para novo agendamento
    btnAddAgendamento?.addEventListener('click', async () => {
        if (accessRestricted) {
            await promptUpgrade();
            return;
        }

        // Limpar formulário
        form?.reset();
        if (agIdInput) agIdInput.value = '';
        if (modalTitle) modalTitle.textContent = 'Novo Agendamento';
        if (modalSubmitBtn) modalSubmitBtn.textContent = 'Salvar';

        // Carregar dados
        await loadContasSelect();
        await loadCategoriasSelect(document.getElementById('agTipo')?.value || 'despesa');

        // Abrir modal
        openAgendamentoModal();
    });

    // Atualizar categorias quando mudar o tipo
    const agTipoSelect = document.getElementById('agTipo');
    agTipoSelect?.addEventListener('change', async () => {
        const tipo = agTipoSelect.value;
        await loadCategoriasSelect(tipo);
    });
    
    // Inicialização
    mobileCards.render();
    loadContasSelect().catch(console.error);
    loadCategoriasSelect(tipoSelect?.value || 'despesa').catch(console.error);
    carregarFiltrosCategorias().catch(console.error);
    carregarFiltrosContas().catch(console.error);
    loadAgendamentos();
});
