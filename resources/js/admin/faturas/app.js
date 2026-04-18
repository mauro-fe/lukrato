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

        if (savedView === 'list') {
            container.classList.add('list-view');
        }

        this.updateViewToggleState(viewButtons, savedView);

        const listHeader = document.getElementById('faturasListHeader');
        if (savedView === 'list' && listHeader) {
            listHeader.classList.add('visible');
        }

        viewButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.view;

                if (view === 'list') {
                    container.classList.add('list-view');
                    if (listHeader) listHeader.classList.add('visible');
                } else {
                    container.classList.remove('list-view');
                    if (listHeader) listHeader.classList.remove('visible');
                }

                localStorage.setItem('faturas_view_mode', view);
                this.updateViewToggleState(viewButtons, view);
            });
        });
    },

    updateViewToggleState(buttons, activeView) {
        buttons.forEach((btn) => {
            if (btn.dataset.view === activeView) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
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
        } catch (error) {
            console.error('Erro ao carregar parcelamentos:', error);
            Modules.UI.showEmpty();
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

        const badges = [];
        const meses = ['', 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        if (STATE.filtros.status) {
            const statusLabels = {
                pendente: 'Pendente',
                parcial: 'Parcial',
                paga: 'Paga',
                cancelado: 'Cancelado'
            };
            badges.push({
                key: 'status',
                label: statusLabels[STATE.filtros.status] || STATE.filtros.status
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
                label: meses[STATE.filtros.mes]
            });
        }

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
