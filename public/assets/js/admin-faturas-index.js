/**
 * ============================================================================
 * SISTEMA DE GERENCIAMENTO DE PARCELAMENTOS - VERSÃO MELHORADA
 * ============================================================================
 */

(() => {
    'use strict';

    // Previne inicialização dupla
    if (window.__LK_PARCELAMENTOS_LOADER__) return;
    window.__LK_PARCELAMENTOS_LOADER__ = true;

    // ============================================================================
    // CONFIGURAÇÃO
    // ============================================================================

    const CONFIG = {
        BASE_URL: (window.BASE_URL || '/').replace(/\/?$/, '/'),
        ENDPOINTS: {
            parcelamentos: 'api/faturas',
            categorias: 'api/categorias',
            contas: 'api/contas',
            cartoes: 'api/cartoes'
        },
        TIMEOUTS: {
            alert: 5000,
            successMessage: 2000
        }
    };

    // ============================================================================
    // SELETORES DOM
    // ============================================================================

    const DOM = {
        // Containers
        loadingEl: document.getElementById('loadingParcelamentos'),
        containerEl: document.getElementById('parcelamentosContainer'),
        emptyStateEl: document.getElementById('emptyState'),

        // Filtros
        filtroStatus: document.getElementById('filtroStatus'),
        filtroCartao: document.getElementById('filtroCartao'),
        filtroAno: document.getElementById('filtroAno'),
        filtroMes: document.getElementById('filtroMes'),
        btnFiltrar: document.getElementById('btnFiltrar'),
        btnLimparFiltros: document.getElementById('btnLimparFiltros'),
        filtersContainer: document.querySelector('.filters-modern'),
        filtersBody: document.getElementById('filtersBody'),
        toggleFilters: document.getElementById('toggleFilters'),
        activeFilters: document.getElementById('activeFilters'),

        // Modal Detalhes
        modalDetalhes: document.getElementById('modalDetalhesParcelamento'),
        detalhesContent: document.getElementById('detalhesParcelamentoContent')
    };

    // ============================================================================
    // ESTADO GLOBAL
    // ============================================================================

    const STATE = {
        parcelamentos: [],
        cartoes: [],
        faturaAtual: null,
        filtros: {
            status: '',
            cartao_id: '',
            ano: new Date().getFullYear(),
            mes: ''
        },
        modalDetalhesInstance: null,
        anosCarregados: false
    };

    // ============================================================================
    // UTILS
    // ============================================================================

    const Utils = {
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value || 0);
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr + 'T00:00:00');
            return date.toLocaleDateString('pt-BR');
        },

        parseMoney(str) {
            if (!str) return 0;
            return parseFloat(str.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
        },

        showAlert(element, message, type = 'danger') {
            if (!element) return;
            element.className = `alert alert-${type}`;
            element.textContent = message;
            element.style.display = 'block';
            setTimeout(() => {
                element.style.display = 'none';
            }, CONFIG.TIMEOUTS.alert);
        },

        getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        buildUrl(endpoint, params = {}) {
            const url = endpoint.startsWith('http')
                ? endpoint
                : CONFIG.BASE_URL + endpoint.replace(/^\//, '');

            const filteredParams = Object.entries(params)
                .filter(([_, value]) => value !== null && value !== undefined && value !== '')
                .map(([key, value]) => `${key}=${encodeURIComponent(value)}`);

            return filteredParams.length > 0 ? `${url}?${filteredParams.join('&')}` : url;
        },

        async apiRequest(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCSRFToken()
                }
            };

            const fullUrl = url.startsWith('http') ? url : CONFIG.BASE_URL + url.replace(/^\//, '');

            try {
                const response = await fetch(fullUrl, {
                    ...defaultOptions,
                    ...options,
                    headers: {
                        ...defaultOptions.headers,
                        ...options.headers
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || `Erro ${response.status}: ${response.statusText}`);
                }

                return data;
            } catch (error) {
                console.error('Erro na requisição:', error);
                throw error;
            }
        },

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        calcularDiferencaDias(dataVencimento, dataPagamento) {
            const dataVenc = new Date(dataVencimento + 'T00:00:00');
            const dataPag = new Date(dataPagamento + 'T00:00:00');
            return Math.floor((dataVenc - dataPag) / (1000 * 60 * 60 * 24));
        }
    };

    // ============================================================================
    // API
    // ============================================================================

    const API = {
        async listarParcelamentos(filters = {}) {
            const params = {
                status: filters.status,
                cartao_id: filters.cartao_id,
                ano: filters.ano,
                mes: filters.mes
            };

            const url = Utils.buildUrl(CONFIG.ENDPOINTS.parcelamentos, params);
            return await Utils.apiRequest(url);
        },

        async listarCartoes() {
            return await Utils.apiRequest(CONFIG.ENDPOINTS.cartoes);
        },

        async buscarParcelamento(id) {
            const parcelamentoId = parseInt(id, 10);
            if (isNaN(parcelamentoId)) {
                throw new Error('ID inválido');
            }
            return await Utils.apiRequest(`${CONFIG.ENDPOINTS.parcelamentos}/${parcelamentoId}`);
        },

        async criarParcelamento(dados) {
            return await Utils.apiRequest(CONFIG.ENDPOINTS.parcelamentos, {
                method: 'POST',
                body: JSON.stringify(dados)
            });
        },

        async cancelarParcelamento(id) {
            return await Utils.apiRequest(`${CONFIG.ENDPOINTS.parcelamentos}/${id}`, {
                method: 'DELETE'
            });
        },

        async toggleItemFatura(faturaId, itemId, pago) {
            return await Utils.apiRequest(`${CONFIG.ENDPOINTS.parcelamentos}/${faturaId}/itens/${itemId}/toggle`, {
                method: 'POST',
                body: JSON.stringify({ pago })
            });
        },

        /**
         * Pagar fatura completa - cria UM ÚNICO lançamento agrupado
         * @param {number} cartaoId - ID do cartão de crédito
         * @param {number} mes - Mês da fatura (1-12)
         * @param {number} ano - Ano da fatura
         * @param {number|null} contaId - ID da conta para débito (null = usa conta vinculada ao cartão)
         */
        async pagarFaturaCompleta(cartaoId, mes, ano, contaId = null) {
            const payload = { mes, ano };
            if (contaId) payload.conta_id = contaId;

            return await Utils.apiRequest(`${CONFIG.ENDPOINTS.cartoes}/${cartaoId}/fatura/pagar`, {
                method: 'POST',
                body: JSON.stringify(payload)
            });
        },

        /**
         * Listar contas do usuário com saldos
         */
        async listarContas() {
            return await Utils.apiRequest(`${CONFIG.ENDPOINTS.contas}?with_balances=1`);
        }
    };

    // ============================================================================
    // UI
    // ============================================================================

    const UI = {
        showLoading() {
            DOM.loadingEl.style.display = 'flex';
            DOM.containerEl.style.display = 'none';
            DOM.emptyStateEl.style.display = 'none';
        },

        hideLoading() {
            DOM.loadingEl.style.display = 'none';
        },

        showEmpty() {
            DOM.containerEl.style.display = 'none';
            DOM.emptyStateEl.style.display = 'block';
        },

        renderParcelamentos(parcelamentos) {
            if (!Array.isArray(parcelamentos) || parcelamentos.length === 0) {
                this.showEmpty();
                return;
            }

            DOM.emptyStateEl.style.display = 'none';
            DOM.containerEl.style.display = 'grid';

            // Usar DocumentFragment para melhor performance
            const fragment = document.createDocumentFragment();
            parcelamentos.forEach(parc => {
                const card = this.createParcelamentoCard(parc);
                fragment.appendChild(card);
            });

            DOM.containerEl.innerHTML = '';
            DOM.containerEl.appendChild(fragment);
        },

        createParcelamentoCard(parc) {
            const progresso = parc.progresso || 0;
            const itensPendentes = parc.parcelas_pendentes || 0;
            const itensPagos = parc.parcelas_pagas || 0;
            const totalItens = itensPagos + itensPendentes;

            const div = document.createElement('div');
            div.className = `parcelamento-card status-${parc.status}`;
            div.dataset.id = parc.id;

            const statusBadge = this.getStatusBadge(parc.status, progresso);

            // Usar mes_referencia/ano_referencia da API (mais preciso)
            const mes = parc.mes_referencia || '';
            const ano = parc.ano_referencia || '';

            div.innerHTML = this.createCardHTML({
                parc,
                statusBadge,
                mes,
                ano,
                itensPendentes,
                itensPagos,
                totalItens,
                progresso
            });

            this.attachCardEventListeners(div, parc.id);
            return div;
        },

        createCardHTML({ parc, statusBadge, mes, ano, itensPendentes, itensPagos, totalItens, progresso }) {
            const cartaoInfo = this.getCartaoInfo(parc);
            const resumoPrincipal = this.getResumoPrincipal(parc);
            const itensInfo = this.getItensInfo(itensPendentes, itensPagos);
            const progressoSection = this.getProgressoSection(totalItens, itensPagos, progresso);

            return `
                <div class="parc-card-header">
                    <div class="header-info">
                        <div class="cartao-info">${cartaoInfo}</div>
                        <div class="fatura-periodo">
                            <i class="fas fa-calendar-alt"></i>
                            ${mes}/${ano}
                        </div>
                    </div>
                    ${statusBadge}
                </div>
                <div class="fatura-resumo-principal">${resumoPrincipal}</div>
                ${itensInfo}
                ${progressoSection}
                <div class="parc-card-actions">
                    <button class="parc-btn parc-btn-view" data-action="view" data-id="${parc.id}">
                        <i class="fas fa-eye"></i>
                        <span>Ver Detalhes</span>
                    </button>
                </div>
            `;
        },

        extrairMesAno(descricao) {
            const match = descricao.match(/(\d+)\/(\d+)/);
            return match ? [match[1], match[2]] : ['', ''];
        },

        getCartaoInfo(parc) {
            if (!parc.cartao) {
                return `<span class="cartao-nome">${Utils.escapeHtml(parc.descricao)}</span>`;
            }
            return `
                <span class="cartao-nome">${Utils.escapeHtml(parc.cartao.nome || parc.cartao.bandeira)}</span>
                <span class="cartao-numero">•••• ${parc.cartao.ultimos_digitos || ''}</span>
            `;
        },

        getResumoPrincipal(parc) {
            const temEstornos = parc.total_estornos && parc.total_estornos > 0;

            return `
                <div class="resumo-item">
                    <span class="resumo-label">Total a Pagar</span>
                    <strong class="resumo-valor">${Utils.formatMoney(parc.valor_total)}</strong>
                </div>
                ${temEstornos ? `
                    <div class="resumo-item resumo-estornos">
                        <span class="resumo-label" style="color: #10b981;">↩️ Estornos</span>
                        <span class="resumo-valor" style="color: #10b981; font-size: 0.85rem;">- ${Utils.formatMoney(parc.total_estornos)}</span>
                    </div>
                ` : ''}
                ${parc.data_vencimento ? `
                    <div class="resumo-item">
                        <span class="resumo-label">Vencimento</span>
                        <strong class="resumo-data">${Utils.formatDate(parc.data_vencimento)}</strong>
                    </div>
                ` : ''}
            `;
        },

        getItensInfo(itensPendentes, itensPagos) {
            let html = '';

            if (itensPendentes > 0) {
                html += `
                    <div class="fatura-itens-info">
                        <div class="itens-badge itens-pendentes">
                            <i class="fas fa-clock"></i>
                            <span>${itensPendentes} ${itensPendentes === 1 ? 'item pendente' : 'itens pendentes'}</span>
                        </div>
                    </div>
                `;
            }

            if (itensPagos > 0) {
                html += `
                    <div class="fatura-itens-info">
                        <div class="itens-badge itens-pagos">
                            <i class="fas fa-check-circle"></i>
                            <span>${itensPagos} ${itensPagos === 1 ? 'item pago' : 'itens pagos'}</span>
                        </div>
                    </div>
                `;
            }

            return html;
        },

        getProgressoSection(totalItens, itensPagos, progresso) {
            if (totalItens === 0) return '';

            return `
                <div class="parc-progress-section">
                    <div class="parc-progress-header">
                        <span class="parc-progress-text">${itensPagos} de ${totalItens} pagos</span>
                        <span class="parc-progress-percent">${Math.round(progresso)}%</span>
                    </div>
                    <div class="parc-progress-bar">
                        <div class="parc-progress-fill" style="width: ${progresso}%"></div>
                    </div>
                </div>
            `;
        },

        attachCardEventListeners(card, id) {
            const btnView = card.querySelector('[data-action="view"]');
            if (btnView) {
                btnView.addEventListener('click', () => this.showDetalhes(id));
            }
        },

        getStatusBadge(status, progresso = null) {
            if (progresso !== null) {
                if (progresso === 0) {
                    return '<span class="parc-card-badge badge-pendente">⏳ Pendente</span>';
                } else if (progresso >= 100) {
                    return '<span class="parc-card-badge badge-paga">✅ Paga</span>';
                } else {
                    return '<span class="parc-card-badge badge-parcial">🔄 Parcialmente Paga</span>';
                }
            }

            const badges = {
                'ativo': '<span class="parc-card-badge badge-ativo">⏳ Pendente</span>',
                'paga': '<span class="parc-card-badge badge-paga">✅ Paga</span>',
                'concluido': '<span class="parc-card-badge badge-paga">✅ Paga</span>',
                'cancelado': '<span class="parc-card-badge badge-cancelado">❌ Cancelada</span>'
            };
            return badges[status] || '<span class="parc-card-badge badge-ativo">⏳ Pendente</span>';
        },

        async showDetalhes(id) {
            try {
                const response = await API.buscarParcelamento(id);
                const parc = response.data;

                if (!parc) {
                    throw new Error('Fatura não encontrada');
                }

                STATE.faturaAtual = parc;
                DOM.detalhesContent.innerHTML = this.renderDetalhes(parc);
                this.attachDetalhesEventListeners(parc.id);

                // Remover foco antes de mostrar modal
                document.activeElement?.blur();
                STATE.modalDetalhesInstance.show();
            } catch (error) {
                console.error('Erro ao abrir detalhes:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message
                });
            }
        },

        attachDetalhesEventListeners(faturaId) {
            // Botões de pagar/desfazer pagamento
            const btnToggles = DOM.detalhesContent.querySelectorAll('.btn-pagar, .btn-desfazer');
            btnToggles.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const itemId = parseInt(e.currentTarget.dataset.lancamentoId, 10);
                    const isPago = e.currentTarget.dataset.pago === 'true';
                    await this.toggleParcelaPaga(faturaId, itemId, !isPago);
                });
            });

            // Botões de editar item
            const btnEditar = DOM.detalhesContent.querySelectorAll('.btn-editar');
            btnEditar.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const itemId = parseInt(e.currentTarget.dataset.lancamentoId, 10);
                    const descricao = e.currentTarget.dataset.descricao || '';
                    const valor = parseFloat(e.currentTarget.dataset.valor) || 0;
                    await this.editarItemFatura(faturaId, itemId, descricao, valor);
                });
            });

            // Botões de excluir item
            const btnExcluir = DOM.detalhesContent.querySelectorAll('.btn-excluir');
            btnExcluir.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const itemId = parseInt(e.currentTarget.dataset.lancamentoId, 10);
                    const ehParcelado = e.currentTarget.dataset.ehParcelado === 'true';
                    const totalParcelas = parseInt(e.currentTarget.dataset.totalParcelas) || 1;
                    await this.excluirItemFatura(faturaId, itemId, ehParcelado, totalParcelas);
                });
            });
        },

        renderDetalhes(parc) {
            const progresso = parc.progresso || 0;
            const { valorPago, valorRestante } = this.calcularValores(parc);
            const temItensPendentes = parc.parcelas_pendentes > 0 && valorRestante > 0;

            return `
                ${this.renderDetalhesHeader(parc, temItensPendentes, valorRestante)}
                ${this.renderDetalhesGrid(parc, progresso)}
                ${this.renderDetalhesProgresso(parc, progresso, valorPago, valorRestante)}
                ${this.renderParcelasTabela(parc)}
            `;
        },

        calcularValores(parc) {
            let valorPago = 0;
            let valorRestante = parc.valor_total;

            if (parc.parcelas && parc.parcelas.length > 0) {
                valorPago = parc.parcelas
                    .filter(p => p.pago)
                    .reduce((sum, p) => sum + parseFloat(p.valor_parcela || p.valor || 0), 0);
                valorRestante = parc.parcelas
                    .filter(p => !p.pago)
                    .reduce((sum, p) => sum + parseFloat(p.valor_parcela || p.valor || 0), 0);
            }

            return { valorPago, valorRestante };
        },

        renderDetalhesHeader(parc, temItensPendentes, valorRestante) {
            // Usar mes_referencia/ano_referencia da API (mais preciso)
            const mes = parc.mes_referencia || '';
            const ano = parc.ano_referencia || '';
            const mesNome = this.getNomeMes(mes);
            const mesAnoFormatado = `${mesNome}/${ano}`;

            // Verificar se pode excluir (apenas se não tiver itens pagos)
            const temItensPagos = parc.parcelas_pagas > 0;

            return `
                <div class="detalhes-header">
                    <div class="detalhes-header-content">
                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <span style="color: #9ca3af; font-size: 0.875rem; font-weight: 500;">Vencimento</span>
                            <h3 class="detalhes-title" style="margin: 0;">${mesAnoFormatado}</h3>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            ${temItensPendentes ? `
                                <button class="btn-pagar-fatura-completa" 
                                        onclick="window.pagarFaturaCompletaGlobal(${parc.id}, ${valorRestante})">
                                    <i class="fas fa-check-double"></i>
                                    <span class="btn-text-desktop">Pagar Fatura Completa</span>
                                    <span class="btn-text-mobile">Pagar Tudo</span>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        },

        renderDetalhesGrid(parc, progresso) {
            const totalItens = parc.parcelas_pagas + parc.parcelas_pendentes;

            // Verificar se tem estornos
            const temEstornos = parc.total_estornos && parc.total_estornos > 0;

            return `
                <div class="detalhes-grid">
                    <div class="detalhes-item">
                        <span class="detalhes-label">💵 Valor Total a Pagar</span>
                        <span class="detalhes-value detalhes-value-highlight">${Utils.formatMoney(parc.valor_total)}</span>
                    </div>
                    ${temEstornos ? `
                    <div class="detalhes-item">
                        <span class="detalhes-label">↩️ Estornos/Créditos</span>
                        <span class="detalhes-value" style="color: #10b981;">- ${Utils.formatMoney(parc.total_estornos)}</span>
                    </div>
                    ` : ''}
                    <div class="detalhes-item">
                        <span class="detalhes-label">📦 Itens</span>
                        <span class="detalhes-value">${totalItens} itens</span>
                    </div>
                    <div class="detalhes-item">
                        <span class="detalhes-label">📊 Tipo</span>
                        <span class="detalhes-value">💸 Despesas${temEstornos ? ' + ↩️ Estornos' : ''}</span>
                    </div>
                    <div class="detalhes-item">
                        <span class="detalhes-label">🎯 Status</span>
                        <span class="detalhes-value">${this.getStatusBadge(parc.status, progresso)}</span>
                    </div>
                    ${parc.cartao ? `
                        <div class="detalhes-item">
                            <span class="detalhes-label">💳 Cartão</span>
                            <span class="detalhes-value">${parc.cartao.bandeira} ${parc.cartao.nome ? '- ' + Utils.escapeHtml(parc.cartao.nome) : ''}</span>
                        </div>
                    ` : ''}
                </div>
            `;
        },

        renderDetalhesProgresso(parc, progresso, valorPago, valorRestante) {
            const totalItens = parc.parcelas_pagas + parc.parcelas_pendentes;

            return `
                <div class="detalhes-progresso">
                    <div class="progresso-info">
                        <span><strong>${parc.parcelas_pagas}</strong> de <strong>${totalItens}</strong> itens pagos</span>
                        <span class="progresso-percent"><strong>${Math.round(progresso)}%</strong></span>
                    </div>
                    <div class="progresso-barra">
                        <div class="progresso-fill" style="width: ${progresso}%"></div>
                    </div>
                    <div class="progresso-valores">
                        <span class="valor-pago">✅ Pago: ${Utils.formatMoney(valorPago)}</span>
                        <span class="valor-restante">⏳ Restante: ${Utils.formatMoney(valorRestante)}</span>
                    </div>
                </div>
            `;
        },

        renderParcelasTabela(parc) {
            // Versão desktop: tabela
            let html = `
                <h4 class="parcelas-titulo">📋 Lista de Itens</h4>
                
                <!-- Tabela Desktop -->
                <div class="parcelas-container parcelas-desktop">
                    <table class="parcelas-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (parc.parcelas && parc.parcelas.length > 0) {
                parc.parcelas.forEach((parcela, index) => {
                    html += this.renderParcelaRow(parcela, index, parc.descricao);
                });
            } else {
                html += `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem;">
                            <p style="color: #6b7280;">Nenhuma parcela encontrada</p>
                        </td>
                    </tr>
                `;
            }

            html += `
                        </tbody>
                    </table>
                </div>
                
                <!-- Cards Mobile -->
                <div class="parcelas-container parcelas-mobile">
            `;

            if (parc.parcelas && parc.parcelas.length > 0) {
                parc.parcelas.forEach((parcela, index) => {
                    html += this.renderParcelaCard(parcela, index, parc.descricao);
                });
            } else {
                html += `
                    <div class="parcela-card-empty">
                        <p>Nenhuma parcela encontrada</p>
                    </div>
                `;
            }

            html += `</div>`;

            return html;
        },

        renderParcelaCard(parcela, index, descricaoFatura) {
            const isPaga = parcela.pago;
            const isEstorno = parcela.tipo === 'estorno';
            const statusClass = isPaga ? 'parcela-paga' : 'parcela-pendente';
            const statusText = isPaga ? '✅ Paga' : '⏳ Pendente';
            const cardClass = isPaga ? 'parcela-card-paga' : '';
            const mesAno = `${this.getNomeMes(parcela.mes_referencia)}/${parcela.ano_referencia}`;
            const cardId = `parcela-card-${parcela.id || index}`;

            // Usar a descrição da parcela ou categoria se disponível
            let descricaoItem = parcela.descricao || descricaoFatura;

            // Remover o contador de parcelas (X/Y) da descrição
            descricaoItem = descricaoItem.replace(/\s*\(\d+\/\d+\)\s*$/, '');

            // Se tiver categoria, mostrar o nome da categoria
            let categoriaInfo = '';
            if (parcela.categoria) {
                const iconeCategoria = parcela.categoria.icone || '📋';
                const nomeCategoria = parcela.categoria.nome || parcela.categoria;
                categoriaInfo = `${iconeCategoria} ${nomeCategoria}`;
            }

            // Card especial para estornos
            if (isEstorno) {
                return `
                    <div class="parcela-card" id="${cardId}" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%); border-color: rgba(16, 185, 129, 0.4);">
                        <div class="parcela-card-header">
                            <span class="parcela-numero" style="color: #10b981;">↩️ Estorno</span>
                            <span class="parcela-paga" style="background: #10b981;">✅ Creditado</span>
                        </div>
                        <div class="parcela-card-body">
                            <div class="parcela-card-info">
                                <span class="parcela-card-label">Descrição</span>
                                <span class="parcela-card-value" style="color: #10b981;">${Utils.escapeHtml(descricaoItem)}</span>
                            </div>
                            <div class="parcela-card-info">
                                <span class="parcela-card-label">Crédito na Fatura</span>
                                <span class="parcela-card-value parcela-valor" style="color: #10b981; font-weight: 600;">
                                    - ${Utils.formatMoney(Math.abs(parcela.valor_parcela))}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }

            return `
                <div class="parcela-card ${cardClass}" id="${cardId}">
                    <div class="parcela-card-header">
                        <span class="parcela-numero">${parcela.numero_parcela || (index + 1)}/${parcela.total_parcelas || 1}</span>
                        <span class="${statusClass}">${statusText}</span>
                    </div>
                    <div class="parcela-card-body">
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Descrição</span>
                            <span class="parcela-card-value">${Utils.escapeHtml(descricaoItem)}</span>
                        </div>
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Valor</span>
                            <span class="parcela-card-value parcela-valor">${Utils.formatMoney(parcela.valor_parcela)}</span>
                        </div>
                    </div>
                    
                    <!-- Detalhes expandíveis -->
                    <div class="parcela-card-detalhes" id="detalhes-${cardId}" style="display: none;">
                        ${categoriaInfo ? `
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Categoria</span>
                            <span class="parcela-card-value">${categoriaInfo}</span>
                        </div>
                        ` : ''}
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Mês/Ano</span>
                            <span class="parcela-card-value">${mesAno}</span>
                        </div>
                        ${isPaga && parcela.data_pagamento ? `
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Data Pagamento</span>
                            <span class="parcela-card-value">${parcela.data_pagamento}</span>
                        </div>
                        ` : ''}
                        ${parcela.id ? `
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">ID do Item</span>
                            <span class="parcela-card-value">#${parcela.id}</span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="parcela-card-footer">
                      
                        ${this.renderParcelaButton(parcela, isPaga)}
                    </div>
                </div>
            `;
        },

        renderParcelaRow(parcela, index, descricaoFatura) {
            const isPaga = parcela.pago;
            const isEstorno = parcela.tipo === 'estorno';
            const statusClass = isPaga ? 'parcela-paga' : 'parcela-pendente';
            const statusText = isPaga ? '✅ Paga' : '⏳ Pendente';
            const rowClass = isPaga ? 'tr-paga' : '';
            const mesAno = `${this.getNomeMes(parcela.mes_referencia)}/${parcela.ano_referencia}`;
            const dataPagamentoHtml = this.getDataPagamentoInfo(parcela);

            // Usar a descrição da parcela ou categoria se disponível
            let descricaoItem = parcela.descricao || descricaoFatura;

            // Remover o contador de parcelas (X/Y) da descrição
            descricaoItem = descricaoItem.replace(/\s*\(\d+\/\d+\)\s*$/, '');

            // Se tiver categoria, mostrar o nome da categoria
            if (parcela.categoria) {
                const iconeCategoria = parcela.categoria.icone || '📋';
                const nomeCategoria = parcela.categoria.nome || parcela.categoria;
                descricaoItem = `${iconeCategoria} ${nomeCategoria}`;
            }

            // Estornos aparecem diferente
            if (isEstorno) {
                return `
                    <tr class="tr-estorno" style="background: rgba(16, 185, 129, 0.1);">
                        <td data-label="#">
                            <span class="parcela-numero" style="color: #10b981;">↩️</span>
                        </td>
                        <td data-label="Descrição" class="td-descricao">
                            <div class="parcela-desc" style="color: #10b981;">${Utils.escapeHtml(descricaoItem)}</div>
                        </td>
                        <td data-label="Valor">
                            <span class="parcela-valor" style="color: #10b981; font-weight: 600;">
                                - ${Utils.formatMoney(Math.abs(parcela.valor_parcela))}
                            </span>
                        </td>
                        <td data-label="Status" class="td-status">
                            <span class="status-badge parcela-paga" style="background: #10b981;">✅ Creditado</span>
                        </td>
                        <td data-label="Ação" class="td-acoes">
                            <span style="color: #10b981; font-size: 0.85rem;">Estorno aplicado</span>
                        </td>
                    </tr>
                `;
            }

            return `
                <tr class="${rowClass}">
                    <td data-label="#">
                        <span class="parcela-numero">${parcela.numero_parcela}/${parcela.total_parcelas}</span>
                    </td>
                    <td data-label="Descrição" class="td-descricao">
                        <div class="parcela-desc">${Utils.escapeHtml(descricaoItem)}</div>
                    </td>
                    <td data-label="Valor">
                        <span class="parcela-valor">${Utils.formatMoney(parcela.valor_parcela)}</span>
                    </td>
                    <td data-label="Status" class="td-status">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </td>
                    <td data-label="Ação" class="td-acoes">
                        ${this.renderParcelaButton(parcela, isPaga)}
                    </td>
                </tr>
            `;
        },

        getDataPagamentoInfo(parcela) {
            if (!parcela.pago || !parcela.data_pagamento) return '';

            return `<small style="color: #10b981; display: block; margin-top: 3px;">✅ Pago em ${parcela.data_pagamento}</small>`;
        },

        renderParcelaButton(parcela, isPaga) {
            if (isPaga) {
                // Item pago: apenas botão de desfazer
                return `
                    <div class="btn-group-parcela">
                        <button class="btn-toggle-parcela btn-desfazer" 
                            data-lancamento-id="${parcela.id}" 
                            data-pago="true"
                            title="Desfazer pagamento">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                `;
            } else {
                // Item pendente: botões de pagar, editar e excluir
                const ehParcelado = parcela.total_parcelas > 1;
                return `
                    <div class="btn-group-parcela">
                        <button class="btn-toggle-parcela btn-pagar" 
                            data-lancamento-id="${parcela.id}" 
                            data-pago="false"
                            title="Marcar como pago">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-toggle-parcela btn-editar" 
                            data-lancamento-id="${parcela.id}"
                            data-descricao="${Utils.escapeHtml(parcela.descricao || '')}"
                            data-valor="${parcela.valor_parcela || 0}"
                            title="Editar item">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button class="btn-toggle-parcela btn-excluir" 
                            data-lancamento-id="${parcela.id}"
                            data-eh-parcelado="${ehParcelado}"
                            data-total-parcelas="${parcela.total_parcelas || 1}"
                            title="Excluir item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            }
        },

        getNomeMes(mes) {
            const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            return meses[mes - 1] || mes;
        },

        extrairMesAno(descricao) {
            // Extrai mês e ano de strings como "Fatura 2/2026" ou "2/2026"
            const match = descricao.match(/(\d{1,2})\/(\d{4})/);
            if (match) {
                return [parseInt(match[1], 10), match[2]];
            }
            // Fallback para data atual
            const hoje = new Date();
            return [hoje.getMonth() + 1, hoje.getFullYear()];
        },

        mostrarDetalhesParcela(parcela, descricao) {
            const isPaga = parcela.pago;
            const statusIcon = isPaga ? '✅' : '⏳';
            const statusText = isPaga ? 'Paga' : 'Pendente';
            const statusColor = isPaga ? '#10b981' : '#f59e0b';
            const mesAno = `${this.getNomeMesCompleto(parcela.mes_referencia)}/${parcela.ano_referencia}`;

            let dataPagamentoHtml = '';
            if (isPaga && parcela.data_pagamento) {
                dataPagamentoHtml = `
                    <div class="detalhes-item">
                        <span class="detalhes-label">Data de Pagamento</span>
                        <span class="detalhes-value">${Utils.formatDate(parcela.data_pagamento)}</span>
                    </div>
                `;
            }

            Swal.fire({
                title: `${statusIcon} Detalhes da Parcela`,
                html: `
                    <div style="text-align: left;">
                        <div style="background: ${statusColor}15; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid ${statusColor};">
                            <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">Status</div>
                            <div style="font-size: 1.25rem; font-weight: bold; color: ${statusColor};">${statusText}</div>
                        </div>
                        
                        <div class="detalhes-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="detalhes-item">
                                <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Parcela</span>
                                <span class="detalhes-value" style="display: block; font-weight: 600; color: #1f2937;">${parcela.numero_parcela}/${parcela.total_parcelas}</span>
                            </div>
                            
                            <div class="detalhes-item">
                                <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Valor</span>
                                <span class="detalhes-value" style="display: block; font-weight: 600; color: ${statusColor};">${Utils.formatMoney(parcela.valor)}</span>
                            </div>
                        </div>

                        <div class="detalhes-item" style="margin-bottom: 1rem;">
                            <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Descrição</span>
                            <span class="detalhes-value" style="display: block; font-weight: 500; color: #1f2937;">${Utils.escapeHtml(descricao)}</span>
                        </div>

                        <div class="detalhes-item" style="margin-bottom: 1rem;">
                            <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Mês de Referência</span>
                            <span class="detalhes-value" style="display: block; font-weight: 600; color: #1f2937;">${mesAno}</span>
                        </div>

                        ${dataPagamentoHtml}
                    </div>
                `,
                icon: false,
                confirmButtonText: 'Fechar',
                confirmButtonColor: '#6366f1',
                width: '500px'
            });
        },

        getNomeMesCompleto(mes) {
            const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            return meses[mes - 1] || mes;
        },

        async toggleParcelaPaga(faturaId, itemId, marcarComoPago) {
            try {
                const acao = marcarComoPago ? 'pagar' : 'desfazer pagamento';

                const result = await Swal.fire({
                    title: marcarComoPago ? 'Marcar como pago?' : 'Desfazer pagamento?',
                    text: `Deseja realmente ${acao} este item?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: marcarComoPago ? '#10b981' : '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: marcarComoPago ? 'Sim, marcar como pago' : 'Sim, desfazer',
                    cancelButtonText: 'Cancelar',
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });

                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Processando...',
                    allowOutsideClick: false,
                    heightAuto: false,
                    didOpen: () => {
                        Swal.showLoading();
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    },
                    customClass: {
                        container: 'swal-above-modal'
                    }
                });

                await API.toggleItemFatura(faturaId, itemId, marcarComoPago);

                await Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: marcarComoPago ? 'Item marcado como pago' : 'Pagamento desfeito',
                    timer: CONFIG.TIMEOUTS.successMessage,
                    showConfirmButton: false,
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });

                // Recarregar parcelamentos e reabrir modal atualizado
                await App.carregarParcelamentos();

                // Reabrir o modal com dados atualizados
                setTimeout(() => {
                    UI.showDetalhes(faturaId);
                }, 100);

            } catch (error) {
                console.error('Erro ao alternar status:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Erro ao processar operação',
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });
            }
        },

        async editarItemFatura(faturaId, itemId, descricaoAtual, valorAtual) {
            // Usar modal Bootstrap ao invés de SweetAlert2
            const modalEl = document.getElementById('modalEditarItemFatura');
            if (!modalEl) {
                console.error('Modal de edição não encontrado');
                return;
            }

            // Preencher os campos do formulário
            document.getElementById('editItemFaturaId').value = faturaId;
            document.getElementById('editItemId').value = itemId;
            document.getElementById('editItemDescricao').value = descricaoAtual;
            document.getElementById('editItemValor').value = valorAtual.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Abrir o modal
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        },

        async salvarItemFatura() {
            const faturaId = document.getElementById('editItemFaturaId').value;
            const itemId = document.getElementById('editItemId').value;
            const novaDescricao = document.getElementById('editItemDescricao').value.trim();
            const novoValorStr = document.getElementById('editItemValor').value;

            // Validações
            if (!novaDescricao) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Informe a descrição do item.',
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }

            const novoValor = parseFloat(novoValorStr.replace(/\./g, '').replace(',', '.')) || 0;
            if (novoValor <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Informe um valor válido.',
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }

            try {
                // Fechar o modal de edição
                const modalEl = document.getElementById('modalEditarItemFatura');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                // Mostrar loading
                Swal.fire({
                    title: 'Atualizando item...',
                    html: 'Aguarde enquanto salvamos as alterações.',
                    allowOutsideClick: false,
                    heightAuto: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Chamar API para atualizar o item da fatura
                await Utils.apiRequest(`api/faturas/${faturaId}/itens/${itemId}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        descricao: novaDescricao,
                        valor: novoValor
                    })
                });

                await Swal.fire({
                    icon: 'success',
                    title: 'Item Atualizado!',
                    text: 'O item foi atualizado com sucesso.',
                    timer: CONFIG.TIMEOUTS.successMessage,
                    showConfirmButton: false,
                    heightAuto: false
                });

                // Recarregar parcelamentos e reabrir modal atualizado
                await App.carregarParcelamentos();

                // Reabrir o modal com dados atualizados
                setTimeout(() => {
                    UI.showDetalhes(faturaId);
                }, 100);

            } catch (error) {
                console.error('Erro ao editar item:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Não foi possível atualizar o item.',
                    heightAuto: false
                });
            }
        },

        async excluirItemFatura(faturaId, itemId, ehParcelado, totalParcelas) {
            try {
                let titulo = 'Excluir Item?';
                let texto = 'Deseja realmente excluir este item da fatura?';
                let confirmBtn = 'Sim, excluir item';

                // Se for parcelado, oferecer opções
                if (ehParcelado && totalParcelas > 1) {
                    const { value: opcao } = await Swal.fire({
                        title: 'O que deseja excluir?',
                        html: `
                            <p>Este item faz parte de um parcelamento de <strong>${totalParcelas}x</strong>.</p>
                            <p style="margin-top: 1rem;">Escolha uma opção:</p>
                        `,
                        icon: 'question',
                        input: 'radio',
                        inputOptions: {
                            'item': 'Apenas esta parcela',
                            'parcelamento': `Todo o parcelamento (${totalParcelas} parcelas)`
                        },
                        inputValue: 'item',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Continuar',
                        cancelButtonText: 'Cancelar',
                        heightAuto: false,
                        customClass: {
                            container: 'swal-above-modal'
                        },
                        didOpen: () => {
                            const container = document.querySelector('.swal2-container');
                            if (container) container.style.zIndex = '99999';
                        }
                    });

                    if (!opcao) return;

                    if (opcao === 'parcelamento') {
                        return await this.excluirParcelamentoCompleto(faturaId, itemId, totalParcelas);
                    }
                }

                const result = await Swal.fire({
                    title: titulo,
                    text: texto,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: confirmBtn,
                    cancelButtonText: 'Cancelar',
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });

                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Excluindo...',
                    allowOutsideClick: false,
                    heightAuto: false,
                    didOpen: () => {
                        Swal.showLoading();
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    },
                    customClass: {
                        container: 'swal-above-modal'
                    }
                });

                await Utils.apiRequest(`api/faturas/${faturaId}/itens/${itemId}`, {
                    method: 'DELETE'
                });

                await Swal.fire({
                    icon: 'success',
                    title: 'Excluído!',
                    text: 'Item removido da fatura.',
                    timer: CONFIG.TIMEOUTS.successMessage,
                    showConfirmButton: false,
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });

                // Recarregar parcelamentos e reabrir modal atualizado
                await App.carregarParcelamentos();

                // Reabrir o modal com dados atualizados
                setTimeout(() => {
                    UI.showDetalhes(faturaId);
                }, 100);

            } catch (error) {
                console.error('Erro ao excluir item:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Não foi possível excluir o item.',
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });
            }
        },

        async excluirParcelamentoCompleto(faturaId, itemId, totalParcelas) {
            const result = await Swal.fire({
                title: 'Excluir Parcelamento Completo?',
                html: `
                    <p>Deseja realmente excluir <strong>todas as ${totalParcelas} parcelas</strong> deste parcelamento?</p>
                    <p style="color: #ef4444; margin-top: 1rem;"><i class="fas fa-exclamation-triangle"></i> Esta ação não pode ser desfeita!</p>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: `Sim, excluir ${totalParcelas} parcelas`,
                cancelButtonText: 'Cancelar',
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Excluindo parcelamento...',
                allowOutsideClick: false,
                heightAuto: false,
                didOpen: () => {
                    Swal.showLoading();
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                },
                customClass: {
                    container: 'swal-above-modal'
                }
            });

            try {
                const response = await Utils.apiRequest(`api/faturas/${faturaId}/itens/${itemId}/parcelamento`, {
                    method: 'DELETE'
                });

                await Swal.fire({
                    icon: 'success',
                    title: 'Parcelamento Excluído!',
                    text: response.message || `${totalParcelas} parcelas removidas.`,
                    timer: CONFIG.TIMEOUTS.successMessage,
                    showConfirmButton: false,
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });

                // Recarregar parcelamentos e reabrir modal atualizado
                await App.carregarParcelamentos();

                // Reabrir o modal com dados atualizados
                setTimeout(() => {
                    UI.showDetalhes(faturaId);
                }, 100);

            } catch (error) {
                console.error('Erro ao excluir parcelamento:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Não foi possível excluir o parcelamento.',
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });
            }
        },

        async pagarFaturaCompleta(faturaId, valorTotal) {
            try {
                // Primeiro buscar os dados da fatura e as contas disponíveis
                Swal.fire({
                    title: 'Carregando...',
                    html: 'Buscando informações da fatura e contas disponíveis.',
                    allowOutsideClick: false,
                    heightAuto: false,
                    didOpen: () => {
                        Swal.showLoading();
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    },
                    customClass: {
                        container: 'swal-above-modal'
                    }
                });

                // Buscar fatura e contas em paralelo
                const [faturaResponse, contasResponse] = await Promise.all([
                    API.buscarParcelamento(faturaId),
                    API.listarContas()
                ]);

                const fatura = faturaResponse;
                const contas = contasResponse.data || contasResponse || [];

                if (!fatura.data || !fatura.data.cartao) {
                    throw new Error('Dados da fatura incompletos');
                }

                const cartaoId = fatura.data.cartao.id;
                const contaPadraoId = fatura.data.cartao.conta_id || null;

                // Extrair mês/ano da descrição da fatura (ex: "Fatura 2/2026")
                const descricao = fatura.data.descricao || '';
                const match = descricao.match(/(\d+)\/(\d+)/);
                const mes = match ? match[1] : null;
                const ano = match ? match[2] : null;

                if (!mes || !ano) {
                    throw new Error('Não foi possível identificar o mês/ano da fatura');
                }

                // Montar opções do select de contas
                let contasOptions = '';
                if (Array.isArray(contas) && contas.length > 0) {
                    contas.forEach(conta => {
                        const saldo = conta.saldoAtual ?? conta.saldo_atual ?? conta.saldo ?? 0;
                        const saldoFormatado = Utils.formatMoney(saldo);
                        const isDefault = conta.id === contaPadraoId;
                        const saldoSuficiente = saldo >= valorTotal;
                        const statusClass = saldoSuficiente ? 'color: #059669;' : 'color: #dc2626;';
                        contasOptions += `<option value="${conta.id}" ${isDefault ? 'selected' : ''} ${!saldoSuficiente ? 'style="color: #dc2626;"' : ''}>
                            ${Utils.escapeHtml(conta.nome)} - ${saldoFormatado}${isDefault ? ' (vinculada ao cartão)' : ''}
                        </option>`;
                    });
                } else {
                    throw new Error('Nenhuma conta disponível para débito');
                }

                const result = await Swal.fire({
                    title: 'Pagar Fatura Completa?',
                    html: `
                        <p>Deseja realmente pagar todos os itens pendentes desta fatura?</p>
                        <div style="margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                            <div style="font-size: 0.875rem; color: #047857; margin-bottom: 0.5rem;">Valor Total:</div>
                            <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">${Utils.formatMoney(valorTotal)}</div>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; text-align: left; margin-bottom: 0.5rem; color: #374151; font-weight: 500;">
                                <i class="fas fa-university"></i> Conta para débito:
                            </label>
                            <select id="swalContaSelect" class="swal2-select" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                                ${contasOptions}
                            </select>
                        </div>
                        <p style="color: #6b7280; font-size: 0.875rem;">O valor será debitado da conta selecionada.</p>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fas fa-check"></i> Sim, pagar tudo',
                    cancelButtonText: 'Cancelar',
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    },
                    preConfirm: () => {
                        const contaSelect = document.getElementById('swalContaSelect');
                        const selectedContaId = contaSelect ? parseInt(contaSelect.value) : null;
                        if (!selectedContaId) {
                            Swal.showValidationMessage('Selecione uma conta para débito');
                            return false;
                        }
                        return { contaId: selectedContaId };
                    }
                });

                if (!result.isConfirmed) return;

                const selectedContaId = result.value.contaId;

                Swal.fire({
                    title: 'Processando pagamento...',
                    html: 'Aguarde enquanto processamos o pagamento de todos os itens.',
                    allowOutsideClick: false,
                    heightAuto: false,
                    didOpen: () => {
                        Swal.showLoading();
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    },
                    customClass: {
                        container: 'swal-above-modal'
                    }
                });

                // Chamar API que cria UM ÚNICO lançamento agrupado, passando a conta selecionada
                const response = await API.pagarFaturaCompleta(cartaoId, parseInt(mes), parseInt(ano), selectedContaId);

                if (!response.success) {
                    throw new Error(response.error || 'Erro ao processar pagamento');
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Fatura Paga!',
                    html: `
                        <p>${response.message || 'Fatura paga com sucesso!'}</p>
                        <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                            <div style="font-size: 0.875rem; color: #047857;">Valor debitado:</div>
                            <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                                ${Utils.formatMoney(response.valor_pago || valorTotal)}
                            </div>
                        </div>
                        <div style="color: #059669;">
                            <i class="fas fa-check-circle" style="font-size: 2rem;"></i>
                        </div>
                    `,
                    timer: 3000,
                    showConfirmButton: false,
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });

                await App.carregarParcelamentos();

                // Fechar o modal após pagamento completo
                STATE.modalDetalhesInstance.hide();

            } catch (error) {
                console.error('Erro ao pagar fatura completa:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao pagar fatura',
                    text: error.message || 'Não foi possível processar o pagamento. Tente novamente.',
                    heightAuto: false,
                    customClass: {
                        container: 'swal-above-modal'
                    },
                    didOpen: () => {
                        const container = document.querySelector('.swal2-container');
                        if (container) container.style.zIndex = '99999';
                    }
                });
            }
        }
    };

    // ============================================================================
    // APP
    // ============================================================================

    const App = {
        async init() {
            try {
                this.initModal();
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
                const response = await API.listarCartoes();
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
            UI.showLoading();

            try {

                const response = await API.listarParcelamentos({
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

                UI.renderParcelamentos(parcelamentos);

            } catch (error) {
                console.error('❌ Erro ao carregar parcelamentos:', error);
                UI.showEmpty();
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao Carregar',
                    text: error.message || 'Não foi possível carregar os parcelamentos'
                });
            } finally {
                UI.hideLoading();
            }
        },

        async cancelarParcelamento(id) {
            try {
                await API.cancelarParcelamento(id);

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
                    UI.salvarItemFatura();
                });
            }

            // Submit do formulário de edição (Enter)
            const formEditarItem = document.getElementById('formEditarItemFatura');
            if (formEditarItem) {
                formEditarItem.addEventListener('submit', (e) => {
                    e.preventDefault();
                    UI.salvarItemFatura();
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
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                `).join('');

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

    // ============================================================================
    // INICIALIZAÇÃO
    // ============================================================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => App.init());
    } else {
        App.init();
    }

    // ============================================================================
    // EXPOR FUNÇÕES GLOBAIS
    // ============================================================================

    window.pagarFaturaCompletaGlobal = (faturaId, valorTotal) => {
        UI.pagarFaturaCompleta(faturaId, valorTotal);
    };

    window.excluirFaturaGlobal = async (faturaId) => {
        const result = await Swal.fire({
            title: 'Excluir Fatura?',
            html: `
                <p>Você está prestes a excluir esta fatura e <strong>todos os seus itens pendentes</strong>.</p>
                <p style="color: #ef4444; font-weight: 500;">Esta ação não pode ser desfeita!</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-trash"></i> Sim, excluir',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const response = await Utils.apiRequest(`api/faturas/${faturaId}`, {
                method: 'DELETE'
            });

            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Fatura Excluída!',
                    text: 'A fatura foi excluída com sucesso.',
                    timer: 2000,
                    showConfirmButton: false
                });

                // Fechar modal e recarregar lista
                if (STATE.modalDetalhesInstance) {
                    STATE.modalDetalhesInstance.hide();
                }
                App.carregarParcelamentos();
            } else {
                throw new Error(response.error || 'Erro ao excluir fatura');
            }
        } catch (error) {
            console.error('Erro ao excluir fatura:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message || 'Não foi possível excluir a fatura.'
            });
        }
    };

    window.excluirItemFaturaGlobal = async (faturaId, itemId) => {
        const result = await Swal.fire({
            title: 'Excluir Item?',
            html: `
                <p>Você está prestes a excluir este item da fatura.</p>
                <p style="color: #ef4444; font-weight: 500;">Esta ação não pode ser desfeita!</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-trash"></i> Sim, excluir',
            cancelButtonText: 'Cancelar',
            customClass: {
                container: 'swal-above-modal'
            }
        });

        if (!result.isConfirmed) return;

        try {
            const response = await Utils.apiRequest(`api/faturas/${faturaId}/itens/${itemId}`, {
                method: 'DELETE'
            });

            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Item Excluído!',
                    text: 'O item foi excluído com sucesso.',
                    timer: 2000,
                    showConfirmButton: false,
                    customClass: {
                        container: 'swal-above-modal'
                    }
                });

                // Recarregar detalhes da fatura
                App.carregarParcelamentos();

                // Se modal estiver aberto, recarregar detalhes
                if (STATE.faturaAtual) {
                    setTimeout(() => {
                        UI.abrirDetalhes(faturaId);
                    }, 500);
                }
            } else {
                throw new Error(response.error || 'Erro ao excluir item');
            }
        } catch (error) {
            console.error('Erro ao excluir item:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message || 'Não foi possível excluir o item.',
                customClass: {
                    container: 'swal-above-modal'
                }
            });
        }
    };

    // ============================================================================
    // MÓDULO GLOBAL (exposto para uso no HTML)
    // ============================================================================

    window.FaturasModule = {
        // Função global para toggle de detalhes no card mobile
        toggleCardDetalhes: (cardId, btn) => {
            const detalhesDiv = document.getElementById(`detalhes-${cardId}`);
            if (!detalhesDiv) return;

            const isVisible = detalhesDiv.style.display !== 'none';
            detalhesDiv.style.display = isVisible ? 'none' : 'block';

            // Atualizar ícone do botão
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = isVisible ? 'fas fa-eye' : 'fas fa-eye-slash';
            }
        }
    };

})();