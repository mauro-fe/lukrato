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
            ano: '',
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
            console.log('🌐 API Request URL:', url, 'Params:', params);
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
            const [mes, ano] = this.extrairMesAno(parc.descricao);

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
            return `
                <div class="resumo-item">
                    <span class="resumo-label">Total a Pagar</span>
                    <strong class="resumo-valor">${Utils.formatMoney(parc.valor_total)}</strong>
                </div>
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
            const btnToggles = DOM.detalhesContent.querySelectorAll('.btn-toggle-parcela');
            btnToggles.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const itemId = parseInt(e.currentTarget.dataset.lancamentoId, 10);
                    const isPago = e.currentTarget.dataset.pago === 'true';
                    await this.toggleParcelaPaga(faturaId, itemId, !isPago);
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
                    .reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
                valorRestante = parc.parcelas
                    .filter(p => !p.pago)
                    .reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
            }

            return { valorPago, valorRestante };
        },

        renderDetalhesHeader(parc, temItensPendentes, valorRestante) {
            return `
                <div class="detalhes-header">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 class="detalhes-title" style="margin: 0;">${Utils.escapeHtml(parc.descricao)}</h3>
                        ${temItensPendentes ? `
                            <button class="btn-pagar-fatura-completa" 
                                    onclick="window.pagarFaturaCompletaGlobal(${parc.id}, ${valorRestante})"
                                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                                           color: white; border: none; padding: 0.75rem 1.5rem;
                                           border-radius: 8px; font-weight: 600; cursor: pointer;
                                           display: flex; align-items: center; gap: 0.5rem;
                                           transition: all 0.2s; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);"
                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.4)'" 
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(16, 185, 129, 0.3)'">
                                <i class="fas fa-check-double"></i>
                                Pagar Fatura Completa
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        },

        renderDetalhesGrid(parc, progresso) {
            const totalItens = parc.parcelas_pagas + parc.parcelas_pendentes;

            return `
                <div class="detalhes-grid">
                    <div class="detalhes-item">
                        <span class="detalhes-label">💵 Valor Total a Pagar</span>
                        <span class="detalhes-value detalhes-value-highlight">${Utils.formatMoney(parc.valor_total)}</span>
                    </div>
                    <div class="detalhes-item">
                        <span class="detalhes-label">📦 Itens</span>
                        <span class="detalhes-value">${totalItens} itens</span>
                    </div>
                    <div class="detalhes-item">
                        <span class="detalhes-label">📊 Tipo</span>
                        <span class="detalhes-value">💸 Despesas</span>
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
            let html = `
                <h4 class="parcelas-titulo">📋 Lista de Itens</h4>
                <div class="parcelas-container">
                    <table class="parcelas-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Descrição</th>
                                <th style="width: 120px;">Vencimento</th>
                                <th style="width: 120px;">Valor</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 140px;">Ação</th>
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
                        <td colspan="6" style="text-align: center; padding: 2rem;">
                            <p style="color: #6b7280;">Nenhuma parcela encontrada</p>
                        </td>
                    </tr>
                `;
            }

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            return html;
        },

        renderParcelaRow(parcela, index, descricaoFatura) {
            const isPaga = parcela.pago;
            const statusClass = isPaga ? 'parcela-paga' : 'parcela-pendente';
            const statusText = isPaga ? '✅ Paga' : '⏳ Pendente';
            const rowClass = isPaga ? 'tr-paga' : '';
            const mesAno = `${this.getNomeMes(parcela.mes_referencia)}/${parcela.ano_referencia}`;
            const dataPagamentoHtml = this.getDataPagamentoInfo(parcela);

            return `
                <tr class="${rowClass}">
                    <td data-label="#">
                        <span class="parcela-numero">${parcela.numero_parcela}/${parcela.total_parcelas}</span>
                    </td>
                    <td data-label="Descrição">
                        <div class="parcela-desc">${Utils.escapeHtml(descricaoFatura)}</div>
                    </td>
                    <td data-label="Vencimento">
                        <span class="parcela-data">${mesAno}</span>
                        ${dataPagamentoHtml}
                    </td>
                    <td data-label="Valor">
                        <span class="parcela-valor">${Utils.formatMoney(parcela.valor_parcela)}</span>
                    </td>
                    <td data-label="Status">
                        <span class="${statusClass}">${statusText}</span>
                    </td>
                    <td data-label="Ação">
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
                return `
                    <button class="btn-toggle-parcela btn-desfazer" 
                        data-lancamento-id="${parcela.id}" 
                        data-pago="true"
                        title="Desfazer pagamento">
                        <i class="fas fa-undo"></i>
                        Desfazer
                    </button>
                `;
            } else {
                return `
                    <button class="btn-toggle-parcela btn-pagar" 
                        data-lancamento-id="${parcela.id}" 
                        data-pago="false"
                        title="Marcar como pago">
                        <i class="fas fa-check"></i>
                        Pagar
                    </button>
                `;
            }
        },

        getNomeMes(mes) {
            const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            return meses[mes - 1] || mes;
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
                    cancelButtonText: 'Cancelar'
                });

                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Processando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                await API.toggleItemFatura(faturaId, itemId, marcarComoPago);

                await Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: marcarComoPago ? 'Item marcado como pago' : 'Pagamento desfeito',
                    timer: CONFIG.TIMEOUTS.successMessage,
                    showConfirmButton: false
                });

                await App.carregarParcelamentos();
                STATE.modalDetalhesInstance.hide();

            } catch (error) {
                console.error('Erro ao alternar status:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Erro ao processar operação'
                });
            }
        },

        async pagarFaturaCompleta(faturaId, valorTotal) {
            try {
                const result = await Swal.fire({
                    title: 'Pagar Fatura Completa?',
                    html: `
                        <p>Deseja realmente pagar todos os itens pendentes desta fatura?</p>
                        <div style="margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                            <div style="font-size: 0.875rem; color: #047857; margin-bottom: 0.5rem;">Valor Total:</div>
                            <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">${Utils.formatMoney(valorTotal)}</div>
                        </div>
                        <p style="color: #6b7280; font-size: 0.875rem;">Todos os itens pendentes serão marcados como pagos.</p>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fas fa-check"></i> Sim, pagar tudo',
                    cancelButtonText: 'Cancelar'
                });

                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Processando pagamento...',
                    html: 'Aguarde enquanto processamos o pagamento de todos os itens.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const fatura = await API.buscarParcelamento(faturaId);
                const itensPendentes = fatura.data.parcelas.filter(p => !p.pago);

                for (const item of itensPendentes) {
                    await API.toggleItemFatura(faturaId, item.id, true);
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Fatura Paga!',
                    html: `
                        <p>Todos os itens foram pagos com sucesso!</p>
                        <div style="margin-top: 1rem; color: #059669;">
                            <i class="fas fa-check-circle" style="font-size: 3rem;"></i>
                        </div>
                    `,
                    timer: 3000,
                    showConfirmButton: false
                });

                await App.carregarParcelamentos();
                STATE.modalDetalhesInstance.hide();

            } catch (error) {
                console.error('Erro ao pagar fatura completa:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao pagar fatura',
                    text: error.message || 'Não foi possível processar o pagamento. Tente novamente.'
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

                console.log('✅ Sistema de Parcelamentos inicializado com sucesso');
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

                console.log('🃏 Cartões carregados:', STATE.cartoes.length, STATE.cartoes);

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

            console.log('📝 Select de cartões preenchido com', STATE.cartoes.length, 'opções');
        },

        preencherSelectAnos(anosDisponiveis = []) {
            if (!DOM.filtroAno) return;

            // Guardar valor selecionado atual
            const valorAtual = DOM.filtroAno.value;

            DOM.filtroAno.innerHTML = '<option value="">Todos os anos</option>';

            if (anosDisponiveis.length > 0) {
                // Usar anos das faturas
                const anosOrdenados = [...anosDisponiveis].sort((a, b) => a - b);
                anosOrdenados.forEach(ano => {
                    const option = document.createElement('option');
                    option.value = ano;
                    option.textContent = ano;
                    DOM.filtroAno.appendChild(option);
                });
            } else {
                // Fallback: ano atual
                const anoAtual = new Date().getFullYear();
                const option = document.createElement('option');
                option.value = anoAtual;
                option.textContent = anoAtual;
                DOM.filtroAno.appendChild(option);
            }

            // Restaurar valor se ainda estiver disponível
            if (valorAtual) {
                DOM.filtroAno.value = valorAtual;
            }

            // Sincronizar filtros da URL
            this.sincronizarFiltrosComSelects();

            console.log('📅 Select de anos preenchido com', anosDisponiveis.length || 1, 'opções:', anosDisponiveis);
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
                console.log('📊 Filtros aplicados:', STATE.filtros);

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

                console.log('📊 Parcelamentos carregados:', parcelamentos.length);
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

            console.log('🔍 Filtros aplicados:', STATE.filtros);

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

})();