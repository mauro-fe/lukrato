/**
 * LUKRATO - Faturas / App (init + filters + event listeners)
 */
import { CONFIG, DOM, STATE, Modules } from './state.js';
import { buildAppUrl, getApiPayload, getErrorMessage } from '../shared/api.js';

export const FaturasApp = {
    async init() {
        try {
            this.attachEventListeners();

            if (this.isDetailPage()) {
                await this.carregarDetalhePagina();
                return;
            }

            this.initViewToggle();
            this.aplicarFiltrosURL();
            await this.carregarCartoes();
            await this.carregarParcelamentos();
        } catch (error) {
            console.error('Erro ao inicializar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro de Inicializacao',
                text: 'Nao foi possivel carregar a pagina. Tente recarregar.'
            });
        }
    },

    isDetailPage() {
        return Boolean(DOM.detailPageEl && DOM.detailPageContent);
    },

    isListPage() {
        return Boolean(DOM.containerEl && DOM.loadingEl && DOM.emptyStateEl);
    },

    async carregarDetalhePagina() {
        const faturaId = Number.parseInt(String(DOM.detailPageEl?.dataset.faturaId ?? ''), 10);

        if (!Number.isInteger(faturaId) || faturaId <= 0) {
            Modules.UI.renderDetailPageState({
                title: 'Fatura invalida',
                message: 'Nao foi possivel identificar a fatura solicitada.',
            });
            return;
        }

        STATE.currentDetailId = faturaId;
        await Modules.UI.showDetalhes(faturaId);
    },

    async refreshAfterMutation(faturaId = null) {
        const targetId = Number.parseInt(String(
            faturaId
            ?? STATE.currentDetailId
            ?? STATE.faturaAtual?.id
            ?? 0
        ), 10);

        if (this.isListPage()) {
            await this.carregarParcelamentos();
            return;
        }

        if (!Number.isInteger(targetId) || targetId <= 0) {
            return;
        }

        if (this.isDetailPage()) {
            STATE.currentDetailId = targetId;
            await Modules.UI.showDetalhes(targetId);
        }
    },

    goToIndex() {
        window.location.href = buildAppUrl('faturas');
    },

    initViewToggle() {
        const viewToggle = document.querySelector('.view-toggle');
        const container = DOM.containerEl;

        if (!viewToggle || !container) return;

        const viewButtons = viewToggle.querySelectorAll('.view-btn');
        const savedView = localStorage.getItem('faturas_view_mode') || 'grid';
        const listHeader = document.getElementById('faturasListHeader');

        const syncViewMode = (view) => {
            const isListView = view === 'list';

            container.classList.toggle('list-view', isListView);
            container.dataset.viewMode = view;
            viewToggle.dataset.currentView = view;

            if (listHeader) {
                listHeader.classList.toggle('visible', isListView);
            }

            localStorage.setItem('faturas_view_mode', view);
            this.updateViewToggleState(viewButtons, view);
        };

        syncViewMode(savedView);

        viewButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                syncViewMode(btn.dataset.view);
            });
        });
    },

    updateViewToggleState(buttons, activeView) {
        buttons.forEach((btn) => {
            const isActive = btn.dataset.view === activeView;

            if (isActive) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }

            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    },

    getMesLabel(value) {
        const meses = ['', 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        return meses[Number(value)] || String(value || '');
    },

    getStatusLabel(value) {
        const statusLabels = {
            pendente: 'Pendente',
            parcial: 'Parcial',
            paga: 'Paga',
            cancelado: 'Cancelado'
        };

        return statusLabels[value] || value || '';
    },

    getParcelamentoReferenceMeta(parcelamento) {
        let month = Number.parseInt(String(parcelamento?.mes_referencia ?? ''), 10);
        let year = Number.parseInt(String(parcelamento?.ano_referencia ?? ''), 10);

        if ((!Number.isInteger(month) || month < 1 || month > 12 || !Number.isInteger(year) || year < 1900) && parcelamento?.descricao) {
            const match = String(parcelamento.descricao).match(/(\d{1,2})\/(\d{4})/);

            if (match) {
                month = Number.parseInt(match[1], 10);
                year = Number.parseInt(match[2], 10);
            }
        }

        if ((!Number.isInteger(month) || month < 1 || month > 12 || !Number.isInteger(year) || year < 1900) && parcelamento?.data_vencimento) {
            const dueDate = new Date(`${parcelamento.data_vencimento}T00:00:00`);

            if (!Number.isNaN(dueDate.getTime())) {
                month = dueDate.getMonth() + 1;
                year = dueDate.getFullYear();
            }
        }

        const now = new Date();
        const currentMonth = now.getMonth() + 1;
        const currentYear = now.getFullYear();
        const isCurrentMonth = Number.isInteger(month) && Number.isInteger(year)
            && month === currentMonth && year === currentYear;

        return {
            month,
            year,
            isCurrentMonth,
        };
    },

    getCurrentFocusSummary(parcelamentos = STATE.parcelamentos) {
        const currentMonthInvoice = parcelamentos.find((parcelamento) => this.getParcelamentoReferenceMeta(parcelamento).isCurrentMonth);

        if (!currentMonthInvoice) {
            return '';
        }

        const now = new Date();
        return `${this.getMesLabel(now.getMonth() + 1)} em destaque`;
    },

    buildFilterBadges() {
        const badges = [];

        if (STATE.filtros.status) {
            badges.push({
                key: 'status',
                label: this.getStatusLabel(STATE.filtros.status)
            });
        }

        if (STATE.filtros.cartao_id) {
            const cartao = STATE.cartoes.find((item) => item.id == STATE.filtros.cartao_id);
            const nomeCartao = cartao ? (cartao.nome_cartao || cartao.nome) : 'Cartao';
            badges.push({
                key: 'cartao_id',
                label: nomeCartao
            });
        }

        if (STATE.filtros.ano) {
            badges.push({
                key: 'ano',
                label: String(STATE.filtros.ano)
            });
        }

        if (STATE.filtros.mes) {
            badges.push({
                key: 'mes',
                label: this.getMesLabel(STATE.filtros.mes)
            });
        }

        return badges;
    },

    formatResultsCount(count) {
        if (count === 0) {
            return 'Nenhuma fatura encontrada';
        }

        return count === 1 ? '1 fatura visivel' : `${count} faturas visiveis`;
    },

    buildContextSummary(parcelamentos = STATE.parcelamentos) {
        const context = [];
        const currentYear = new Date().getFullYear();

        if (STATE.filtros.status) {
            context.push(this.getStatusLabel(STATE.filtros.status));
        }

        if (STATE.filtros.cartao_id) {
            const cartao = STATE.cartoes.find((item) => item.id == STATE.filtros.cartao_id);
            const nomeCartao = cartao ? (cartao.nome_cartao || cartao.nome) : 'Cartao';
            context.push(nomeCartao);
        }

        if (STATE.filtros.mes && STATE.filtros.ano) {
            context.push(`${this.getMesLabel(STATE.filtros.mes)} de ${STATE.filtros.ano}`);
        } else if (STATE.filtros.ano) {
            context.push(`Ano ${STATE.filtros.ano}`);
        }

        if (context.length === 0) {
            return this.getCurrentFocusSummary(parcelamentos) || 'Visao completa';
        }

        if (
            context.length === 1
            && context[0] === `Ano ${currentYear}`
            && !STATE.filtros.status
            && !STATE.filtros.cartao_id
            && !STATE.filtros.mes
        ) {
            const currentFocusSummary = this.getCurrentFocusSummary(parcelamentos);

            return currentFocusSummary ? `${currentFocusSummary} · ${context[0]}` : context[0];
        }

        return context.join(' · ');
    },

    updatePageSummaries(count = STATE.parcelamentos.length, isLoading = false) {
        const activeFilters = this.buildFilterBadges().length;
        const filterWord = activeFilters === 1 ? 'filtro ativo' : 'filtros ativos';

        if (DOM.filtersSummary) {
            if (isLoading) {
                DOM.filtersSummary.textContent = activeFilters > 0
                    ? `Aplicando ${activeFilters} ${filterWord}...`
                    : 'Carregando resumo da busca...';
            } else if (activeFilters > 0) {
                DOM.filtersSummary.textContent = `${activeFilters} ${filterWord} · ${count === 1 ? '1 fatura no recorte' : `${count} faturas no recorte`}`;
            } else {
                DOM.filtersSummary.textContent = count === 1 ? '1 fatura disponivel' : `${count} faturas disponiveis`;
            }
        }

        if (DOM.resultsSummary) {
            DOM.resultsSummary.textContent = isLoading ? 'Carregando faturas...' : this.formatResultsCount(count);
        }

        if (DOM.contextSummary) {
            DOM.contextSummary.textContent = this.buildContextSummary(STATE.parcelamentos);
        }
    },

    aplicarFiltrosURL() {
        const params = new URLSearchParams(window.location.search);

        if (params.has('cartao_id')) {
            STATE.filtros.cartao_id = params.get('cartao_id');
            if (DOM.filtroCartao) {
                DOM.filtroCartao.value = STATE.filtros.cartao_id;
            }
        }

        if (params.has('mes') && params.has('ano')) {
            STATE.filtros.mes = parseInt(params.get('mes'), 10);
            STATE.filtros.ano = parseInt(params.get('ano'), 10);

            if (window.monthPicker) {
                const monthPickerDate = new Date(STATE.filtros.ano, STATE.filtros.mes - 1);
                window.monthPicker.setDate(monthPickerDate);
            }
        }

        if (params.has('status')) {
            STATE.filtros.status = params.get('status');
            if (DOM.filtroStatus) {
                DOM.filtroStatus.value = STATE.filtros.status;
            }
        }
    },

    async carregarCartoes() {
        try {
            const response = await Modules.API.listarCartoes();
            const payload = getApiPayload(response, []);
            STATE.cartoes = Array.isArray(payload) ? payload : [];

            this.preencherSelectCartoes();
            this.sincronizarFiltrosComSelects();
        } catch (error) {
            console.error('Erro ao carregar cartoes:', error);
        }
    },

    sincronizarFiltrosComSelects() {
        if (DOM.filtroStatus && STATE.filtros.status) {
            DOM.filtroStatus.value = STATE.filtros.status;
        }
        if (DOM.filtroCartao && STATE.filtros.cartao_id) {
            DOM.filtroCartao.value = STATE.filtros.cartao_id;
        }
        if (DOM.filtroAno && STATE.filtros.ano) {
            DOM.filtroAno.value = STATE.filtros.ano;
        }
        if (DOM.filtroMes && STATE.filtros.mes) {
            DOM.filtroMes.value = STATE.filtros.mes;
        }
    },

    preencherSelectCartoes() {
        if (!DOM.filtroCartao) return;

        DOM.filtroCartao.innerHTML = '<option value="">Todos os cartoes</option>';

        STATE.cartoes.forEach((cartao) => {
            const option = document.createElement('option');
            option.value = cartao.id;
            const nome = cartao.nome_cartao || cartao.nome || cartao.bandeira || 'Cartao';
            const digitos = cartao.ultimos_digitos ? ` •••• ${cartao.ultimos_digitos}` : '';
            option.textContent = nome + digitos;
            DOM.filtroCartao.appendChild(option);
        });
    },

    preencherSelectAnos(anosDisponiveis = []) {
        if (!DOM.filtroAno) return;

        const valorAtual = DOM.filtroAno.value;
        const anoAtual = new Date().getFullYear();

        DOM.filtroAno.innerHTML = '<option value="">Todos os anos</option>';

        if (anosDisponiveis.length > 0) {
            const anosOrdenados = [...anosDisponiveis].sort((a, b) => a - b);

            if (!anosOrdenados.includes(anoAtual)) {
                anosOrdenados.push(anoAtual);
                anosOrdenados.sort((a, b) => a - b);
            }

            anosOrdenados.forEach((ano) => {
                const option = document.createElement('option');
                option.value = ano;
                option.textContent = ano;
                DOM.filtroAno.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.value = anoAtual;
            option.textContent = anoAtual;
            DOM.filtroAno.appendChild(option);
        }

        if (valorAtual) {
            DOM.filtroAno.value = valorAtual;
        } else {
            DOM.filtroAno.value = anoAtual;
            STATE.filtros.ano = anoAtual;
        }

        this.sincronizarFiltrosComSelects();
    },

    extrairAnosDisponiveis(faturas) {
        const anosSet = new Set();

        faturas.forEach((fatura) => {
            const descricao = fatura.descricao || '';
            const match = descricao.match(/(\d{1,2})\/(\d{4})/);
            if (match) {
                anosSet.add(parseInt(match[2], 10));
            }

            if (fatura.data_vencimento) {
                const ano = new Date(fatura.data_vencimento).getFullYear();
                anosSet.add(ano);
            }
        });

        return Array.from(anosSet);
    },

    async carregarParcelamentos() {
        this.updatePageSummaries(STATE.parcelamentos.length, true);
        Modules.UI.showLoading();

        try {
            const response = await Modules.API.listarParcelamentos({
                status: STATE.filtros.status || '',
                cartao_id: STATE.filtros.cartao_id || '',
                mes: STATE.filtros.mes || '',
                ano: STATE.filtros.ano || ''
            });

            const payload = getApiPayload(response, {});
            const parcelamentos = payload?.faturas || [];

            STATE.parcelamentos = parcelamentos;

            if (!STATE.anosCarregados) {
                const anosDisponiveis = payload?.anos_disponiveis || this.extrairAnosDisponiveis(parcelamentos);
                this.preencherSelectAnos(anosDisponiveis);
                STATE.anosCarregados = true;
            }

            Modules.UI.renderParcelamentos(parcelamentos);
            this.updatePageSummaries(parcelamentos.length);
        } catch (error) {
            console.error('Erro ao carregar parcelamentos:', error);
            Modules.UI.showEmpty();
            this.updatePageSummaries(0);
            Swal.fire({
                icon: 'error',
                title: 'Erro ao carregar',
                text: getErrorMessage(error, 'Nao foi possivel carregar os parcelamentos')
            });
        } finally {
            Modules.UI.hideLoading();
        }
    },

    async cancelarParcelamento(id) {
        try {
            await Modules.API.cancelarParcelamento(id);

            await Swal.fire({
                icon: 'success',
                title: 'Cancelado',
                text: 'Parcelamento cancelado com sucesso',
                timer: CONFIG.TIMEOUTS.successMessage,
                showConfirmButton: false
            });

            await this.carregarParcelamentos();
        } catch (error) {
            console.error('Erro ao cancelar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro ao cancelar',
                text: getErrorMessage(error, 'Nao foi possivel cancelar o parcelamento')
            });
        }
    },

    attachEventListeners() {
        if (DOM.toggleFilters) {
            DOM.toggleFilters.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleFilters();
            });
        }

        const filtersHeader = document.querySelector('.filters-header');
        if (filtersHeader) {
            filtersHeader.addEventListener('click', () => {
                this.toggleFilters();
            });
        }

        if (DOM.btnFiltrar) {
            DOM.btnFiltrar.addEventListener('click', () => {
                this.aplicarFiltros();
            });
        }

        if (DOM.btnLimparFiltros) {
            DOM.btnLimparFiltros.addEventListener('click', () => {
                this.limparFiltros();
            });
        }

        [DOM.filtroStatus, DOM.filtroCartao, DOM.filtroAno, DOM.filtroMes].forEach((select) => {
            if (select) {
                select.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.aplicarFiltros();
                    }
                });
            }
        });

        const btnSalvarItem = document.getElementById('btnSalvarItemFatura');
        if (btnSalvarItem) {
            btnSalvarItem.addEventListener('click', () => {
                Modules.UI.salvarItemFatura();
            });
        }

        const formEditarItem = document.getElementById('formEditarItemFatura');
        if (formEditarItem) {
            formEditarItem.addEventListener('submit', (e) => {
                e.preventDefault();
                Modules.UI.salvarItemFatura();
            });
        }
    },

    toggleFilters() {
        if (DOM.filtersContainer) {
            DOM.filtersContainer.classList.toggle('collapsed');
        }
    },

    aplicarFiltros() {
        STATE.filtros.status = DOM.filtroStatus?.value || '';
        STATE.filtros.cartao_id = DOM.filtroCartao?.value || '';
        STATE.filtros.ano = DOM.filtroAno?.value || '';
        STATE.filtros.mes = DOM.filtroMes?.value || '';

        this.atualizarBadgesFiltros();
        this.carregarParcelamentos();
    },

    limparFiltros() {
        if (DOM.filtroStatus) DOM.filtroStatus.value = '';
        if (DOM.filtroCartao) DOM.filtroCartao.value = '';
        if (DOM.filtroAno) DOM.filtroAno.value = '';
        if (DOM.filtroMes) DOM.filtroMes.value = '';

        STATE.filtros = {
            status: '',
            cartao_id: '',
            ano: '',
            mes: ''
        };

        this.atualizarBadgesFiltros();
        this.carregarParcelamentos();
    },

    atualizarBadgesFiltros() {
        if (!DOM.activeFilters) return;

        const badges = this.buildFilterBadges();

        if (badges.length > 0) {
            DOM.activeFilters.style.display = 'flex';
            DOM.activeFilters.innerHTML = badges.map((badge) => `
                <span class="filter-badge">
                    ${badge.label}
                    <button class="filter-badge-remove" data-filter="${badge.key}" title="Remover filtro">
                        <i data-lucide="x"></i>
                    </button>
                </span>
            `).join('');

            if (window.lucide) lucide.createIcons();

            DOM.activeFilters.querySelectorAll('.filter-badge-remove').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    const filterKey = e.currentTarget.dataset.filter;
                    this.removerFiltro(filterKey);
                });
            });
        } else {
            DOM.activeFilters.style.display = 'none';
            DOM.activeFilters.innerHTML = '';
        }
    },

    removerFiltro(key) {
        STATE.filtros[key] = '';

        const selectMap = {
            status: DOM.filtroStatus,
            cartao_id: DOM.filtroCartao,
            ano: DOM.filtroAno,
            mes: DOM.filtroMes
        };

        if (selectMap[key]) {
            selectMap[key].value = '';
        }

        this.atualizarBadgesFiltros();
        this.carregarParcelamentos();
    }
};

Modules.App = FaturasApp;
