/**
 * LUKRATO — Faturas / App (init + filters + event listeners)
 */
import { CONFIG, DOM, STATE, Utils, Modules, initDOM } from './state.js';
import { refreshIcons } from '../shared/ui.js';

export const FaturasApp = {
    async init() {
        try {
            this.initModal();
            this.initViewToggle();
            this.aplicarFiltrosURL();
            await this.carregarCartoes();
            await this.carregarParcelamentos();
            this.attachEventListeners();

        } catch (error) {
            console.error('❌ Erro ao inicializar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro de Inicialização',
                text: 'Não foi possível carregar a página. Tente recarregar.'
            });
        }
    },

    /**
     * Inicializar toggle de visualização (Cards/Lista)
     */
    initViewToggle() {
        const viewToggle = document.querySelector('.view-toggle');
        const container = DOM.containerEl;

        if (!viewToggle || !container) return;

        const viewButtons = viewToggle.querySelectorAll('.view-btn');

        // Restaurar preferência salva
        const savedView = localStorage.getItem('faturas_view_mode') || 'grid';
        if (savedView === 'list') {
            container.classList.add('list-view');
        }

        // Atualizar estado dos botões
        this.updateViewToggleState(viewButtons, savedView);

        // Referência ao header da lista
        const listHeader = document.getElementById('faturasListHeader');

        // Mostrar/ocultar header conforme view inicial
        if (savedView === 'list' && listHeader) {
            listHeader.classList.add('visible');
        }

        // Adicionar listeners aos botões
        viewButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.view;

                if (view === 'list') {
                    container.classList.add('list-view');
                    if (listHeader) listHeader.classList.add('visible');
                } else {
                    container.classList.remove('list-view');
                    if (listHeader) listHeader.classList.remove('visible');
                }

                // Salvar preferência
                localStorage.setItem('faturas_view_mode', view);

                // Atualizar estado dos botões
                this.updateViewToggleState(viewButtons, view);
            });
        });
    },

    /**
     * Atualizar estado visual dos botões de toggle
     */
    updateViewToggleState(buttons, activeView) {
        buttons.forEach(btn => {
            if (btn.dataset.view === activeView) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    },

    initModal() {
        STATE.modalDetalhesInstance = new bootstrap.Modal(DOM.modalDetalhes, {
            backdrop: true,
            keyboard: true,
            focus: true
        });

        DOM.modalDetalhes.addEventListener('show.bs.modal', () => {
            document.activeElement?.blur();
        });

        DOM.modalDetalhes.addEventListener('hidden.bs.modal', () => {
            document.activeElement?.blur();
        });

        // Listener delegado para botões de ver detalhes de parcela
        DOM.modalDetalhes.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-ver-detalhes-parcela');
            if (btn) {
                e.preventDefault();
                const parcelaData = JSON.parse(btn.dataset.parcela);
                const descricao = btn.dataset.descricao;
                this.mostrarDetalhesParcela(parcelaData, descricao);
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
            // API de cartões retorna array diretamente, não { data: [...] }
            STATE.cartoes = Array.isArray(response) ? response : (response.data || []);

            // Preencher o select de cartões
            this.preencherSelectCartoes();

            // Reaplicar filtros da URL nos selects após preencher
            this.sincronizarFiltrosComSelects();
        } catch (error) {
            console.error('❌ Erro ao carregar cartões:', error);
        }
    },

    sincronizarFiltrosComSelects() {
        // Sincronizar valores dos selects com o estado dos filtros
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

        DOM.filtroCartao.innerHTML = '<option value="">Todos os cartões</option>';

        STATE.cartoes.forEach(cartao => {
            const option = document.createElement('option');
            option.value = cartao.id;
            // Tentar diferentes campos de nome
            const nome = cartao.nome_cartao || cartao.nome || cartao.bandeira || 'Cartão';
            const digitos = cartao.ultimos_digitos ? ` •••• ${cartao.ultimos_digitos}` : '';
            option.textContent = nome + digitos;
            DOM.filtroCartao.appendChild(option);
        });
    },

    preencherSelectAnos(anosDisponiveis = []) {
        if (!DOM.filtroAno) return;

        // Guardar valor selecionado atual
        const valorAtual = DOM.filtroAno.value;
        const anoAtual = new Date().getFullYear();

        DOM.filtroAno.innerHTML = '<option value="">Todos os anos</option>';

        if (anosDisponiveis.length > 0) {
            // Usar anos das faturas
            const anosOrdenados = [...anosDisponiveis].sort((a, b) => a - b);

            // Garantir que o ano atual está na lista
            if (!anosOrdenados.includes(anoAtual)) {
                anosOrdenados.push(anoAtual);
                anosOrdenados.sort((a, b) => a - b);
            }

            anosOrdenados.forEach(ano => {
                const option = document.createElement('option');
                option.value = ano;
                option.textContent = ano;
                DOM.filtroAno.appendChild(option);
            });
        } else {
            // Fallback: ano atual
            const option = document.createElement('option');
            option.value = anoAtual;
            option.textContent = anoAtual;
            DOM.filtroAno.appendChild(option);
        }

        // Restaurar valor se ainda estiver disponível, ou selecionar ano atual
        if (valorAtual) {
            DOM.filtroAno.value = valorAtual;
        } else {
            // Selecionar ano atual por padrão
            DOM.filtroAno.value = anoAtual;
            STATE.filtros.ano = anoAtual;
        }

        // Sincronizar filtros da URL
        this.sincronizarFiltrosComSelects();
    },

    extrairAnosDisponiveis(faturas) {
        const anosSet = new Set();

        faturas.forEach(fatura => {
            // Extrair ano da descrição (formato "Mês/Ano")
            const descricao = fatura.descricao || '';
            const match = descricao.match(/(\d{1,2})\/(\d{4})/);
            if (match) {
                anosSet.add(parseInt(match[2], 10));
            }

            // Também verificar data_vencimento
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

            let parcelamentos = response.data?.faturas || [];

            STATE.parcelamentos = parcelamentos;

            // Usar anos da API (somente na primeira carga)
            if (!STATE.anosCarregados) {
                const anosDisponiveis = response.data?.anos_disponiveis || this.extrairAnosDisponiveis(parcelamentos);
                this.preencherSelectAnos(anosDisponiveis);
                STATE.anosCarregados = true;
            }

            Modules.UI.renderParcelamentos(parcelamentos);

        } catch (error) {
            console.error('❌ Erro ao carregar parcelamentos:', error);
            Modules.UI.showEmpty();
            Swal.fire({
                icon: 'error',
                title: 'Erro ao Carregar',
                text: error.message || 'Não foi possível carregar os parcelamentos'
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
                title: 'Cancelado!',
                text: 'Parcelamento cancelado com sucesso',
                timer: CONFIG.TIMEOUTS.successMessage,
                showConfirmButton: false
            });

            await this.carregarParcelamentos();
        } catch (error) {
            console.error('Erro ao cancelar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro ao Cancelar',
                text: error.message
            });
        }
    },

    attachEventListeners() {
        // Toggle filtros (expandir/colapsar)
        if (DOM.toggleFilters) {
            DOM.toggleFilters.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleFilters();
            });
        }

        // Click no header também expande/colapsa
        const filtersHeader = document.querySelector('.filters-header');
        if (filtersHeader) {
            filtersHeader.addEventListener('click', () => {
                this.toggleFilters();
            });
        }

        // Botão Filtrar
        if (DOM.btnFiltrar) {
            DOM.btnFiltrar.addEventListener('click', () => {
                this.aplicarFiltros();
            });
        }

        // Botão Limpar Filtros
        if (DOM.btnLimparFiltros) {
            DOM.btnLimparFiltros.addEventListener('click', () => {
                this.limparFiltros();
            });
        }

        // Enter nos selects aplica filtro
        [DOM.filtroStatus, DOM.filtroCartao, DOM.filtroAno, DOM.filtroMes].forEach(select => {
            if (select) {
                select.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.aplicarFiltros();
                    }
                });
            }
        });

        // Botão Salvar do Modal de Edição de Item
        const btnSalvarItem = document.getElementById('btnSalvarItemFatura');
        if (btnSalvarItem) {
            btnSalvarItem.addEventListener('click', () => {
                Modules.UI.salvarItemFatura();
            });
        }

        // Submit do formulário de edição (Enter)
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
        // Resetar selects
        if (DOM.filtroStatus) DOM.filtroStatus.value = '';
        if (DOM.filtroCartao) DOM.filtroCartao.value = '';
        if (DOM.filtroAno) DOM.filtroAno.value = '';
        if (DOM.filtroMes) DOM.filtroMes.value = '';

        // Resetar estado
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
        const meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        // Status
        if (STATE.filtros.status) {
            const statusLabels = {
                'pendente': '⏳ Pendente',
                'parcial': '🔄 Parcial',
                'paga': '✅ Paga',
                'cancelado': '❌ Cancelado'
            };
            badges.push({
                key: 'status',
                label: statusLabels[STATE.filtros.status] || STATE.filtros.status
            });
        }

        // Cartão
        if (STATE.filtros.cartao_id) {
            const cartao = STATE.cartoes.find(c => c.id == STATE.filtros.cartao_id);
            const nomeCartao = cartao ? (cartao.nome_cartao || cartao.nome) : 'Cartão';
            badges.push({
                key: 'cartao_id',
                label: `💳 ${nomeCartao}`
            });
        }

        // Ano
        if (STATE.filtros.ano) {
            badges.push({
                key: 'ano',
                label: `📅 ${STATE.filtros.ano}`
            });
        }

        // Mês
        if (STATE.filtros.mes) {
            badges.push({
                key: 'mes',
                label: `📆 ${meses[STATE.filtros.mes]}`
            });
        }

        // Renderizar badges
        if (badges.length > 0) {
            DOM.activeFilters.style.display = 'flex';
            DOM.activeFilters.innerHTML = badges.map(badge => `
                <span class="filter-badge">
                    ${badge.label}
                    <button class="filter-badge-remove" data-filter="${badge.key}" title="Remover filtro">
                        <i data-lucide="x"></i>
                    </button>
                </span>
            `).join('');

            if (window.lucide) lucide.createIcons();

            // Adicionar eventos de remover
            DOM.activeFilters.querySelectorAll('.filter-badge-remove').forEach(btn => {
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
        // Resetar o filtro específico
        STATE.filtros[key] = '';

        // Resetar o select correspondente
        const selectMap = {
            'status': DOM.filtroStatus,
            'cartao_id': DOM.filtroCartao,
            'ano': DOM.filtroAno,
            'mes': DOM.filtroMes
        };

        if (selectMap[key]) {
            selectMap[key].value = '';
        }

        this.atualizarBadgesFiltros();
        this.carregarParcelamentos();
    }
};

Modules.App = FaturasApp;
