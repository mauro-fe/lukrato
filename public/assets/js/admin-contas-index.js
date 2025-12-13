/**
 * ============================================================================
 * SISTEMA DE GERENCIAMENTO DE CONTAS BANCÁRIAS
 * ============================================================================
 * Gerencia contas, lançamentos rápidos e transferências
 * ============================================================================
 */

(function initAccountsPage() {
    'use strict';

    // ============================================================================
    // CONFIGURAÇÃO
    // ============================================================================

    const CONFIG = {
        BASE: (document.querySelector('meta[name="base-url"]')?.content || location.origin + '/'),
        getCSRF: () => {
            const metaToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            if (metaToken) return metaToken;
            if (window.LK) {
                if (typeof window.LK.csrfToken === 'string' && window.LK.csrfToken) {
                    return window.LK.csrfToken;
                }
                if (typeof window.LK.getCSRF === 'function') {
                    return window.LK.getCSRF() || '';
                }
            }
            return '';
        }
    };

    // ============================================================================
    // UTILITÁRIOS DE API
    // ============================================================================

    const API = {
        getPrettyUrl: (path) =>
            `${CONFIG.BASE}api/${path}`.replace(/\/{2,}/g, '/').replace(':/', '://'),

        getIndexUrl: (path) =>
            `${CONFIG.BASE}index.php/api/${path}`.replace(/\/{2,}/g, '/').replace(':/', '://'),

        fetch: async (path, opts = {}) => {
            let res = await fetch(API.getPrettyUrl(path), opts);
            if (res.status === 404) {
                res = await fetch(API.getIndexUrl(path), opts);
            }
            return res;
        },

        getHeaders: (includeContentType = true) => {
            const headers = {};
            if (includeContentType) headers['Content-Type'] = 'application/json';
            const csrf = CONFIG.getCSRF();
            if (csrf) headers['X-CSRF-TOKEN'] = csrf;
            return headers;
        },

        processResponse: async (res) => {
            const ct = res.headers.get('content-type') || '';

            if (!res.ok) {
                let msg = `HTTP ${res.status}`;
                if (ct.includes('application/json')) {
                    const json = await res.json().catch(() => ({}));
                    msg = json?.message || msg;
                } else {
                    const text = await res.text();
                    msg = text.slice(0, 200);
                }
                throw new Error(msg);
            }

            if (!ct.includes('application/json')) {
                const text = await res.text();
                throw new Error('Resposta não é JSON. Prévia: ' + text.slice(0, 120));
            }

            return res.json();
        }
    };

    // ============================================================================
    // UTILITÁRIOS DOM
    // ============================================================================

    const DOM = {
        $: (selector, scope = document) => scope.querySelector(selector),
        $$: (selector, scope = document) => Array.from(scope.querySelectorAll(selector)),

        grid: null,
        btnReload: null,
        btnNovaConta: null,
        totalContas: null,
        saldoTotal: null,
        modal: null,
        modalTitle: null,
        form: null,
        modalLanc: null,
        modalLancTitle: null,
        formLanc: null,
        modalTr: null,
        modalTransferTitle: null,
        formTr: null,

        init() {
            this.grid = this.$('#accountsGrid');
            this.btnReload = this.$('#btnReload');
            this.btnNovaConta = this.$('#btnNovaConta');
            this.totalContas = this.$('#totalContas');
            this.saldoTotal = this.$('#saldoTotal');
            this.modal = this.$('#modalConta');
            this.modalTitle = this.$('#modalContaTitle');
            this.form = this.$('#formConta');
            this.modalLanc = this.$('#modalLancConta');
            this.modalLancTitle = this.$('#modalLancContaTitle');
            this.formLanc = this.$('#formLancConta');
            this.modalTr = this.$('#modalTransfer');
            this.modalTransferTitle = this.$('#modalTransferTitle');
            this.formTr = this.$('#formTransfer');
        }
    };

    // ============================================================================
    // UTILITÁRIOS GERAIS
    // ============================================================================

    const Utils = {
        parseMoneyBR(str) {
            if (!str) return 0;
            const cleaned = String(str)
                .replace(/\./g, '')
                .replace(',', '.')
                .replace(/[^\d.-]/g, '')
                .trim();
            const value = parseFloat(cleaned);
            return isFinite(value) ? Math.round(value * 100) / 100 : 0;
        },

        formatMoneyBR(value) {
            try {
                return Number(value).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } catch {
                return (Math.round((+value || 0) * 100) / 100).toFixed(2).replace('.', ',');
            }
        },

        escapeHTML(str = '') {
            return String(str).replace(/[&<>"']/g, m => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[m]));
        },

        getContaSaldo(conta) {
            if (!conta) return 0;
            const raw = (typeof conta.saldoAtual === 'number') ? conta.saldoAtual : conta.saldoInicial;
            const num = Number(raw);
            return Number.isFinite(num) ? num : 0;
        },

        sortContasBySaldo(rows = []) {
            return rows.slice().sort((a, b) => {
                const diff = this.getContaSaldo(b) - this.getContaSaldo(a);
                if (diff !== 0) return diff;
                return (a.nome || '').localeCompare(b.nome || '', 'pt-BR', {
                    sensitivity: 'base', ignorePunctuation: true
                });
            });
        },

        getTodayISO() {
            // Usa data local (sem UTC) para evitar adiantar 1 dia em fusos negativos
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    };

    // ============================================================================
    // NOTIFICAÇÕES
    // ============================================================================

    const Notify = {
        success(title, text = '', timer = 1300) {
            if (!window.Swal) {
                console.log(`✓ ${title}: ${text}`);
                return;
            }

            Swal.fire({
                icon: 'success',
                title,
                text,
                timer,
                showConfirmButton: false,
                timerProgressBar: true,
                draggable: true
            });
        },


        error(title, text = '') {
            if (!window.Swal) {
                console.error(`✗ ${title}: ${text}`);
                return;
            }
            Swal.fire('Erro', text || title, 'error');
        },

        warning(title, text = '') {
            if (!window.Swal) {
                console.warn(`⚠ ${title}: ${text}`);
                return;
            }
            Swal.fire('Atenção', text || title, 'warning');
        },

        processing(title = 'Processando...', text = '') {
            if (!window.Swal) {
                console.log(`${title} ${text}`);
                return () => { };
            }

            Swal.fire({
                title,
                text,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                backdrop: true,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            return () => {
                if (window.Swal) {
                    Swal.close();
                }
            };
        },

        async confirm(title, text = '', confirmText = 'Sim, confirmar') {
            if (!window.Swal) {
                return confirm(`${title}\n${text}`);
            }
            const result = await Swal.fire({
                title, text, icon: 'warning', showCancelButton: true,
                confirmButtonText: confirmText, cancelButtonText: 'Cancelar',
                reverseButtons: true
            });
            return result.isConfirmed;
        }
    };

    // ============================================================================
    // ESTADO GLOBAL
    // ============================================================================

    const STATE = {
        lastRows: [],
        optionsCache: null
    };

    // ============================================================================
    // ESTATÍSTICAS
    // ============================================================================

    const Stats = {
        update(rows) {
            const total = rows ? rows.length : 0;
            const saldo = rows ? rows.reduce((sum, a) => sum + Utils.getContaSaldo(a), 0) : 0;
            if (DOM.totalContas) DOM.totalContas.textContent = total;
            if (DOM.saldoTotal) DOM.saldoTotal.textContent = `R$ ${Utils.formatMoneyBR(saldo)}`;
        }
    };

    // ============================================================================
    // RENDERIZAÇÃO
    // ============================================================================

    const Renderer = {
        showLoading() {
            if (!DOM.grid) return;
            DOM.grid.setAttribute('aria-busy', 'true');
            DOM.grid.innerHTML = `
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
            `;
        },

        showEmpty(message = 'Nenhuma conta cadastrada ainda.') {
            if (!DOM.grid) return;
            DOM.grid.setAttribute('aria-busy', 'false');
            DOM.grid.innerHTML = `
                <div class="lk-empty">
                    <i class="fas fa-wallet" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                    <p>${Utils.escapeHTML(message)}</p>
                </div>
            `;
            Stats.update([]);
        },

        showError(message = 'Erro ao carregar contas.') {
            if (!DOM.grid) return;
            DOM.grid.setAttribute('aria-busy', 'false');
            DOM.grid.innerHTML = `
                <div class="lk-empty lk-error">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--color-danger); margin-bottom: 1rem;"></i>
                    <p>${Utils.escapeHTML(message)}</p>
                    <button class="btn btn-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Tentar novamente
                    </button>
                </div>
            `;
            Stats.update([]);
        },

        renderCards(rows) {
            if (!DOM.grid) return;
            DOM.grid.setAttribute('aria-busy', 'false');
            DOM.grid.innerHTML = '';

            if (!rows || rows.length === 0) {
                this.showEmpty();
                return;
            }

            Stats.update(rows);
            rows.forEach((conta, index) => {
                const card = this.createCard(conta, index);
                DOM.grid.appendChild(card);
            });
        },

        createCard(conta, index) {
            const isActive = !!conta.ativo;
            const saldo = Utils.getContaSaldo(conta);
            const saldoClass = saldo >= 0 ? 'positive' : 'negative';

            const statusBadge = isActive
                ? `<span class="acc-badge active"><i class="fas fa-check-circle"></i> Ativa</span>`
                : `<span class="acc-badge inactive"><i class="fas fa-archive"></i> Arquivada</span>`;

            const actions = isActive ? `
            <div class="acc-actions-btn">
                <button class="btn btn-primary btn-sm btn-acc-receita" data-id="${conta.id}" title="Adicionar receita">
                    <i class="fas fa-arrow-up"></i> Receita
                </button>
                <button class="btn btn-ghost btn-sm btn-acc-despesa" data-id="${conta.id}" title="Adicionar despesa">
                    <i class="fas fa-arrow-down"></i> Despesa
                </button>
                </div>
                <button class="btn btn-ghost btn-sm btn-acc-transfer" data-id="${conta.id}" title="Transferir">
                    <i class="fas fa-right-left"></i> Transferir
                </button>
                <div class="acc-actions-btn">
                <button class="btn btn-ghost btn-sm btn-edit" data-id="${conta.id}" title="Editar conta">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="btn btn-ghost btn-sm btn-archive" data-id="${conta.id}" title="Arquivar conta">
                    <i class="fas fa-box-archive"></i>
                </button>
                </div>
            ` : `
                <button class="btn btn-primary btn-sm btn-restore" data-id="${conta.id}" title="Restaurar conta">
                    <i class="fas fa-rotate-left"></i> Restaurar
                </button>
            `;

            const card = document.createElement('div');
            card.className = 'acc-card';
            card.setAttribute('data-aos', 'flip-right');
            card.setAttribute('data-aos-delay', Math.min(index * 50, 300));
            card.setAttribute('role', 'article');
            card.setAttribute('aria-label', `Conta ${conta.nome}`);

            card.innerHTML = `
                <div class="acc-card-header">
                    <div class="acc-head">
                        <div class="acc-dot"></div>
                        <div class="acc-info">
                            <div class="acc-name">${Utils.escapeHTML(conta.nome || 'Sem nome')}</div>
                            <div class="acc-sub">${Utils.escapeHTML(conta.instituicao || '—')}</div>
                        </div>
                    </div>
                    ${statusBadge}
                </div>
                <div class="acc-balance ${saldoClass}">
                    <span class="acc-currency">R$</span>
                    ${Utils.formatMoneyBR(saldo)}
                </div>
                <div class="acc-actions">${actions}</div>
            `;

            return card;
        }
    };

    // ============================================================================
    // CARREGAMENTO DE DADOS
    // ============================================================================

    const DataLoader = {
        async load() {
            try {
                Renderer.showLoading();
                const yearMonth = new Date().toISOString().slice(0, 7);
                const data = await API.fetch(`accounts?with_balances=1&month=${yearMonth}`, {
                    credentials: 'same-origin'
                }).then(API.processResponse);

                STATE.lastRows = Array.isArray(data) ? Utils.sortContasBySaldo(data) : [];
                Renderer.renderCards(STATE.lastRows);
            } catch (err) {
                console.error('Erro ao carregar contas:', err);
                Renderer.showError(err.message || 'Erro ao carregar contas.');
                Notify.error('Erro', err.message || 'Não foi possível carregar as contas.');
            }
        },

        async getOptions() {
            if (STATE.optionsCache) return STATE.optionsCache;
            try {
                const data = await API.fetch('options', {
                    credentials: 'same-origin'
                }).then(API.processResponse);
                STATE.optionsCache = data;
                return data;
            } catch (err) {
                console.error('Erro ao carregar opções:', err);
                throw new Error('Falha ao carregar opções');
            }
        }
    };

    // ============================================================================
    // MODAL DE CONTA
    // ============================================================================

    const ContaModal = {
        open(isEdit = false, data = null) {
            if (!DOM.modal || !DOM.form) return;
            DOM.modal.classList.add('open');
            document.body.style.overflow = 'hidden';

            const titleEl = DOM.$('#modalContaTitle', DOM.modal);
            const idInput = DOM.$('#contaId', DOM.form);
            const nomeInput = DOM.$('#nome', DOM.form);
            const instInput = DOM.$('#instituicao', DOM.form);
            const saldoInput = DOM.$('#saldo_inicial', DOM.form);

            if (isEdit && data) {
                if (titleEl) titleEl.textContent = 'Editar conta';
                if (idInput) idInput.value = data.id;
                if (nomeInput) nomeInput.value = data.nome || '';
                if (instInput) instInput.value = data.instituicao || '';
                if (saldoInput) saldoInput.value = Utils.formatMoneyBR(data.saldoInicial ?? 0);
            } else {
                if (titleEl) titleEl.textContent = 'Nova conta';
                if (idInput) idInput.value = '';
                DOM.form.reset();
                if (saldoInput) saldoInput.value = '';
            }

            setTimeout(() => nomeInput?.focus(), 40);
        },

        close() {
            if (!DOM.modal) return;
            DOM.modal.classList.remove('open');
            document.body.style.overflow = '';
        },

        async submit(e) {
            e.preventDefault();
            if (!DOM.form) return;

            const formData = new FormData(DOM.form);
            const idInput = DOM.$('#contaId', DOM.form);
            const id = idInput?.value ? Number(idInput.value) : null;

            const payload = {
                nome: (formData.get('nome') || '').trim(),
                instituicao: (formData.get('instituicao') || '').trim(),
                saldo_inicial: Utils.parseMoneyBR(formData.get('saldo_inicial') || '0')
            };

            if (!payload.nome) {
                return Notify.warning('Nome obrigatório', 'Informe o nome da conta.');
            }

            try {
                const method = id ? 'PUT' : 'POST';
                const path = id ? `accounts/${id}` : 'accounts';

                const data = await API.fetch(path, {
                    method,
                    credentials: 'same-origin',
                    headers: API.getHeaders(),
                    body: JSON.stringify(payload)
                }).then(API.processResponse);

                if (data?.status === 'error') {
                    throw new Error(data?.message || 'Erro ao salvar conta.');
                }

                Notify.success('Pronto!', 'Conta salva com sucesso.');
                ContaModal.close();
                await DataLoader.load();
                if (window.refreshDashboard) window.refreshDashboard();
            } catch (err) {
                console.error('Erro ao salvar conta:', err);
                Notify.error('Erro', err.message || 'Falha ao salvar conta.');
            }
        }
    };

    // ============================================================================
    // MODAL DE LANÇAMENTO RÁPIDO
    // ============================================================================

    const LancamentoModal = {
        async open(contaId, tipo = 'despesa') {
            if (!DOM.modalLanc || !DOM.formLanc) return;

            const id = Number(contaId);
            const conta = STATE.lastRows.find(r => r.id === id);

            const contaIdInput = DOM.$('#lanContaId', DOM.modalLanc);
            const contaNomeInput = DOM.$('#lanContaNome', DOM.modalLanc);
            const tipoInput = DOM.$('#lanTipo', DOM.modalLanc);
            const dataInput = DOM.$('#lanData', DOM.modalLanc);
            const catInput = DOM.$('#lanCategoria', DOM.modalLanc);
            const descInput = DOM.$('#lanDescricao', DOM.modalLanc);
            const valorInput = DOM.$('#lanValor', DOM.modalLanc);
            const pagoInput = DOM.$('#lanPago', DOM.modalLanc);

            if (contaIdInput) contaIdInput.value = String(id);
            if (tipoInput) tipoInput.value = tipo === 'receita' ? 'receita' : 'despesa';

            if (contaNomeInput && conta) {
                contaNomeInput.value = `${conta.nome || 'Conta sem nome'}${conta.instituicao ? ' - ' + conta.instituicao : ''}`;
            }

            if (DOM.modalLancTitle) {
                const tituloBase = tipo === 'receita' ? 'Nova receita' : 'Nova despesa';
                DOM.modalLancTitle.textContent = conta?.nome ? `${tituloBase} - ${conta.nome}` : tituloBase;
            }

            if (dataInput) dataInput.value = Utils.getTodayISO();
            if (descInput) descInput.value = '';
            if (valorInput) valorInput.value = '';
            if (pagoInput) pagoInput.checked = false;

            await this.refreshCategorias();

            DOM.modalLanc.classList.add('open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => valorInput?.focus(), 40);
        },

        close() {
            if (!DOM.modalLanc) return;
            DOM.modalLanc.classList.remove('open');
            document.body.style.overflow = '';
        },

        async refreshCategorias() {
            const tipoInput = DOM.$('#lanTipo', DOM.modalLanc);
            const catInput = DOM.$('#lanCategoria', DOM.modalLanc);
            const lblPago = DOM.$('#lanPagoLabel', DOM.modalLanc);

            if (!tipoInput || !catInput) return;

            try {
                const opts = await DataLoader.getOptions();
                const tipo = (tipoInput.value || 'despesa').toLowerCase();
                const list = tipo === 'receita'
                    ? (opts?.categorias?.receitas || [])
                    : (opts?.categorias?.despesas || []);

                catInput.innerHTML = '<option value="">Selecione uma categoria</option>';
                list.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.nome;
                    catInput.appendChild(opt);
                });

                if (lblPago) {
                    lblPago.textContent = tipo === 'receita' ? 'Foi recebido?' : 'Foi pago?';
                }
            } catch (err) {
                console.error('Erro ao carregar categorias:', err);
            }
        },

        async submit(e) {
            e.preventDefault();
            if (!DOM.formLanc) return;

            const formData = new FormData(DOM.formLanc);
            const catInput = DOM.$('#lanCategoria', DOM.formLanc);

            if (!catInput?.value) {
                return Notify.warning('Atenção', 'Selecione uma categoria.');
            }

            const payload = {
                tipo: formData.get('lanTipo'),
                data: formData.get('lanData'),
                valor: Utils.parseMoneyBR(formData.get('lanValor')),
                categoria_id: Number(catInput.value),
                conta_id: Number(formData.get('lanContaId')),
                descricao: formData.get('lanDescricao') || null,
                observacao: null
            };

            if (!payload.data || !payload.valor || payload.valor <= 0) {
                return Notify.warning('Atenção', 'Preencha data e valor válidos.');
            }

            const closeLoading = Notify.processing('Adicionando lançamento', 'Estamos salvando seu novo lançamento.');

            try {
                await API.fetch('transactions', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: API.getHeaders(),
                    body: JSON.stringify(payload)
                }).then(API.processResponse);

                closeLoading();
                Notify.success('Lançado!', '', 1300);
                LancamentoModal.close();
                await DataLoader.load();
                if (window.refreshDashboard) window.refreshDashboard();
            } catch (err) {
                closeLoading();
                console.error('Erro ao salvar lançamento:', err);
                Notify.error('Erro', err.message || 'Falha ao salvar lançamento.');
            }
        }
    };

    // ============================================================================
    // MODAL DE TRANSFERÊNCIA
    // ============================================================================

    const TransferenciaModal = {
        async open(origemId) {
            if (!DOM.modalTr || !DOM.formTr) return;

            const id = Number(origemId);
            const conta = STATE.lastRows.find(r => r.id === id);
            if (!conta) return;

            const saldoDisponivel = Utils.getContaSaldo(conta);
            const saldoNormalizado = Math.round((Number(saldoDisponivel) || 0) * 100) / 100;

            DOM.modalTr.dataset.saldoDisponivel = String(saldoNormalizado);
            DOM.modalTr.dataset.saldoCentavos = String(Math.round(saldoNormalizado * 100));

            const origemIdInput = DOM.$('#trOrigemId', DOM.modalTr);
            const origemNomeInput = DOM.$('#trOrigemNome', DOM.modalTr);
            const destinoIdInput = DOM.$('#trDestinoId', DOM.modalTr);
            const dataInput = DOM.$('#trData', DOM.modalTr);
            const valorInput = DOM.$('#trValor', DOM.modalTr);
            const descInput = DOM.$('#trDesc', DOM.modalTr);

            if (origemIdInput) origemIdInput.value = String(conta.id);
            if (origemNomeInput) {
                origemNomeInput.value = `${conta.nome}${conta.instituicao ? ' - ' + conta.instituicao : ''} (Saldo: R$ ${Utils.formatMoneyBR(saldoNormalizado)})`;
            }

            if (DOM.modalTransferTitle) {
                DOM.modalTransferTitle.textContent = conta.nome ? `Transferência - ${conta.nome}` : 'Transferência';
            }

            if (dataInput) dataInput.value = Utils.getTodayISO();
            if (valorInput) valorInput.value = '';
            if (descInput) descInput.value = '';

            try {
                const opts = await DataLoader.getOptions();
                const contas = Array.isArray(opts?.contas) ? opts.contas : [];

                if (destinoIdInput) {
                    destinoIdInput.innerHTML = '<option value="">Selecione a conta de destino</option>';
                    contas.forEach(c => {
                        if (c.id === id) return;
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.nome;
                        destinoIdInput.appendChild(opt);
                    });
                }
            } catch (err) {
                console.error('Erro ao carregar contas:', err);
            }

            DOM.modalTr.classList.add('open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => valorInput?.focus(), 40);
        },

        close() {
            if (!DOM.modalTr) return;
            DOM.modalTr.classList.remove('open');
            document.body.style.overflow = '';
        },

        async submit(e) {
            e.preventDefault();
            if (!DOM.formTr) return;

            const formData = new FormData(DOM.formTr);
            const origemId = Number(formData.get('trOrigemId'));
            const destinoId = Number(formData.get('trDestinoId'));
            const valor = Utils.parseMoneyBR(formData.get('trValor'));
            const data = formData.get('trData');

            if (!destinoId || !origemId || origemId === destinoId) {
                return Notify.warning('Atenção', 'Selecione contas de origem e destino diferentes.');
            }

            if (!data || !valor || valor <= 0) {
                return Notify.warning('Atenção', 'Preencha data e valor válidos.');
            }

            const saldoDisponivel = Number(DOM.modalTr?.dataset?.saldoDisponivel || 0);
            const saldoCentavos = Number(DOM.modalTr?.dataset?.saldoCentavos || Math.round(saldoDisponivel * 100));
            const valorCentavos = Math.round(valor * 100);

            if (valorCentavos > saldoCentavos) {
                return Notify.error(
                    'Saldo insuficiente',
                    `O valor da transferência (R$ ${Utils.formatMoneyBR(valor)}) é maior que o saldo disponível (R$ ${Utils.formatMoneyBR(saldoDisponivel)}).`
                );
            }

            const payload = {
                data, valor,
                conta_id: origemId,
                conta_id_destino: destinoId,
                descricao: formData.get('trDesc') || null,
                observacao: null
            };

            try {
                await API.fetch('transfers', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: API.getHeaders(),
                    body: JSON.stringify(payload)
                }).then(API.processResponse);

                Notify.success('Transferência registrada!', '', 1300);
                TransferenciaModal.close();
                await DataLoader.load();
                if (window.refreshDashboard) window.refreshDashboard();
            } catch (err) {
                console.error('Erro ao salvar transferência:', err);
                Notify.error('Erro', err.message || 'Falha ao salvar transferência.');
            }
        }
    };

    // ============================================================================
    // AÇÕES DE CONTA
    // ============================================================================

    const ContaActions = {
        async archive(id) {
            const confirmed = await Notify.confirm(
                'Arquivar conta?',
                'Você poderá restaurá-la depois na página "Contas arquivadas".',
                'Sim, arquivar'
            );
            if (!confirmed) return;

            try {
                await API.fetch(`accounts/${id}/archive`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: API.getHeaders(false)
                }).then(API.processResponse);

                Notify.success('Pronto!', 'Conta arquivada.');
                await DataLoader.load();
            } catch (err) {
                console.error('Erro ao arquivar conta:', err);
                Notify.error('Erro', err.message || 'Falha ao arquivar conta.');
            }
        },

        async restore(id) {
            const confirmed = await Notify.confirm(
                'Restaurar conta?',
                'A conta voltará para a lista de contas ativas.',
                'Sim, restaurar'
            );
            if (!confirmed) return;

            try {
                await API.fetch(`accounts/${id}/restore`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: API.getHeaders(false)
                }).then(API.processResponse);

                Notify.success('Pronto!', 'Conta restaurada.');
                await DataLoader.load();
            } catch (err) {
                console.error('Erro ao restaurar conta:', err);
                Notify.error('Erro', err.message || 'Falha ao restaurar conta.');
            }
        },

        edit(id) {
            const conta = STATE.lastRows.find(r => r.id === id);
            if (conta) {
                ContaModal.open(true, conta);
            }
        }
    };

    // ============================================================================
    // EVENT LISTENERS
    // ============================================================================

    const EventListeners = {
        init() {
            DOM.btnNovaConta?.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                ContaModal.open(false, null);
            });

            DOM.btnReload?.addEventListener('click', (e) => {
                e.preventDefault();
                DataLoader.load();
            });

            if (DOM.modal) {
                const closeBtn = DOM.$('#modalClose', DOM.modal);
                const cancelBtn = DOM.$('#btnCancel', DOM.modal);
                closeBtn?.addEventListener('click', () => ContaModal.close());
                cancelBtn?.addEventListener('click', () => ContaModal.close());
            }

            DOM.form?.addEventListener('submit', ContaModal.submit);

            const saldoInput = DOM.$('#saldo_inicial', DOM.form);
            saldoInput?.addEventListener('blur', () => {
                const value = Utils.parseMoneyBR(saldoInput.value);
                saldoInput.value = value ? Utils.formatMoneyBR(value) : '';
            });

            if (DOM.modalLanc) {
                const closeBtn = DOM.$('#lancClose', DOM.modalLanc);
                const cancelBtn = DOM.$('#lancCancel', DOM.modalLanc);
                closeBtn?.addEventListener('click', () => LancamentoModal.close());
                cancelBtn?.addEventListener('click', () => LancamentoModal.close());

                const tipoInput = DOM.$('#lanTipo', DOM.modalLanc);
                tipoInput?.addEventListener('change', () => LancamentoModal.refreshCategorias());

                const valorInput = DOM.$('#lanValor', DOM.modalLanc);
                valorInput?.addEventListener('blur', () => {
                    const value = Utils.parseMoneyBR(valorInput.value);
                    valorInput.value = value ? Utils.formatMoneyBR(value) : '';
                });
            }

            DOM.formLanc?.addEventListener('submit', LancamentoModal.submit);

            if (DOM.modalTr) {
                const closeBtn = DOM.$('#trClose', DOM.modalTr);
                const cancelBtn = DOM.$('#trCancel', DOM.modalTr);
                closeBtn?.addEventListener('click', () => TransferenciaModal.close());
                cancelBtn?.addEventListener('click', () => TransferenciaModal.close());

                const valorInput = DOM.$('#trValor', DOM.modalTr);
                valorInput?.addEventListener('blur', () => {
                    const value = Utils.parseMoneyBR(valorInput.value);
                    valorInput.value = value ? Utils.formatMoneyBR(value) : '';
                });
            }

            DOM.formTr?.addEventListener('submit', TransferenciaModal.submit);

            DOM.grid?.addEventListener('click', (e) => {
                const target = e.target.closest('button[data-id]');
                if (!target) return;

                const id = Number(target.dataset.id);
                if (!id) return;

                if (target.classList.contains('btn-acc-receita')) {
                    LancamentoModal.open(id, 'receita');
                } else if (target.classList.contains('btn-acc-despesa')) {
                    LancamentoModal.open(id, 'despesa');
                } else if (target.classList.contains('btn-edit')) {
                    ContaActions.edit(id);
                } else if (target.classList.contains('btn-acc-transfer')) {
                    TransferenciaModal.open(id);
                } else if (target.classList.contains('btn-archive')) {
                    ContaActions.archive(id);
                } else if (target.classList.contains('btn-restore')) {
                    ContaActions.restore(id);
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') return;

                if (e.ctrlKey && e.key === 'n' && !e.shiftKey) {
                    e.preventDefault();
                    ContaModal.open(false, null);
                }

                if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                    e.preventDefault();
                    DataLoader.load();
                }
            });
        }
    };

    // ============================================================================
    // INICIALIZAÇÃO
    // ============================================================================

    const init = async () => {
        console.log('🚀 Inicializando Sistema de Contas...');

        DOM.init();
        EventListeners.init();
        await DataLoader.load();

        console.log('✅ Sistema de Contas carregado com sucesso!');
    };

    window.refreshContas = DataLoader.load;

    init();
})();
