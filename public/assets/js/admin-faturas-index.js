/**
 * ============================================================================
 * SISTEMA DE GERENCIAMENTO DE PARCELAMENTOS
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
            contas: 'api/contas'
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
        btnFiltrar: document.getElementById('btnFiltrar'),

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
        faturaAtual: null, // Armazena a fatura que está sendo visualizada
        filtros: {
            status: '',
            cartao_id: '',
            ano: '',
            mes: null,  // Será definido pelo month-picker
            anoMes: null   // Será definido pelo month-picker
        },
        modalDetalhesInstance: null
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
            element.className = `alert alert-${type}`;
            element.textContent = message;
            element.style.display = 'block';
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        },

        getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        async apiRequest(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCSRFToken()
                }
            };

            // Se a URL já começa com http, usar direto. Caso contrário, concatenar com BASE_URL
            const fullUrl = url.startsWith('http') ? url : CONFIG.BASE_URL + url.replace(/^\//, '');

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
                throw new Error(data.message || 'Erro na requisição');
            }

            return data;
        }
    };

    // ============================================================================
    // API
    // ============================================================================

    const API = {
        async listarParcelamentos(status = '', cartaoId = '', mes = null, ano = null, anoFiltro = '') {
            let url = CONFIG.ENDPOINTS.parcelamentos;
            const params = [];

            if (status) params.push(`status=${status}`);
            if (cartaoId) params.push(`cartao_id=${cartaoId}`);

            // Se tem filtro de ano específico, usar ele
            if (anoFiltro) {
                params.push(`ano=${anoFiltro}`);
            } else if (mes && ano) {
                // Senão, usar mês e ano do seletor de mês
                params.push(`mes=${mes}`);
                params.push(`ano=${ano}`);
            }

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            return await Utils.apiRequest(url);
        },

        async listarCartoes() {
            return await Utils.apiRequest(CONFIG.BASE_URL + 'api/cartoes');
        },

        async buscarParcelamento(id) {
            return await Utils.apiRequest(`${CONFIG.ENDPOINTS.parcelamentos}/${id}`);
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
            return await Utils.apiRequest(`${CONFIG.BASE_URL}api/faturas/${faturaId}/itens/${itemId}/toggle`, {
                method: 'POST',
                body: JSON.stringify({ pago: pago })
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
            if (!parcelamentos || parcelamentos.length === 0) {
                this.showEmpty();
                return;
            }

            DOM.emptyStateEl.style.display = 'none';
            DOM.containerEl.style.display = 'grid';
            DOM.containerEl.innerHTML = '';

            parcelamentos.forEach(parc => {
                const card = this.createParcelamentoCard(parc);
                DOM.containerEl.appendChild(card);
            });
        },

        createParcelamentoCard(parc) {
            const progresso = parc.progresso || 0;
            const totalItens = (parc.parcelas_pagas || 0) + (parc.parcelas_pendentes || 0);
            const itensPendentes = parc.parcelas_pendentes || 0;
            const itensPagos = parc.parcelas_pagas || 0;

            const div = document.createElement('div');
            div.className = `parcelamento-card status-${parc.status}`;
            div.dataset.id = parc.id;

            const statusBadge = this.getStatusBadge(parc.status, progresso);

            // Extrair mês/ano da descrição "Fatura 1/2026"
            const mesAnoMatch = parc.descricao.match(/(\d+)\/(\d+)/);
            const mes = mesAnoMatch ? mesAnoMatch[1] : '';
            const ano = mesAnoMatch ? mesAnoMatch[2] : '';

            div.innerHTML = `
                <div class="parc-card-header">
                    <div class="header-info">
                        <div class="cartao-info">
                            ${parc.cartao ? `
                                <span class="cartao-nome">${Utils.escapeHtml(parc.cartao.nome || parc.cartao.bandeira)}</span>
                                <span class="cartao-numero">•••• ${parc.cartao.ultimos_digitos || ''}</span>
                            ` : `
                                <span class="cartao-nome">${Utils.escapeHtml(parc.descricao)}</span>
                            `}
                        </div>
                        <div class="fatura-periodo">
                            <i class="fas fa-calendar-alt"></i>
                            ${mes}/${ano}
                        </div>
                    </div>
                    ${statusBadge}
                </div>

                <div class="fatura-resumo-principal">
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
                </div>

                ${itensPendentes > 0 ? `
                    <div class="fatura-itens-info">
                        <div class="itens-badge itens-pendentes">
                            <i class="fas fa-clock"></i>
                            <span>${itensPendentes} ${itensPendentes === 1 ? 'item pendente' : 'itens pendentes'}</span>
                        </div>
                    </div>
                ` : ''}

                ${itensPagos > 0 ? `
                    <div class="fatura-itens-info">
                        <div class="itens-badge itens-pagos">
                            <i class="fas fa-check-circle"></i>
                            <span>${itensPagos} ${itensPagos === 1 ? 'item pago' : 'itens pagos'}</span>
                        </div>
                    </div>
                ` : ''}

                ${totalItens > 0 ? `
                    <div class="parc-progress-section">
                        <div class="parc-progress-header">
                            <span class="parc-progress-text">${itensPagos} de ${totalItens} pagos</span>
                            <span class="parc-progress-percent">${Math.round(progresso)}%</span>
                        </div>
                        <div class="parc-progress-bar">
                            <div class="parc-progress-fill" style="width: ${progresso}%"></div>
                        </div>
                    </div>
                ` : ''}

                <div class="parc-card-actions">
                    <button class="parc-btn parc-btn-view" data-action="view" data-id="${parc.id}">
                        <i class="fas fa-eye"></i>
                        <span>Ver Detalhes</span>
                    </button>
                </div>
            `;

            // Event listeners
            div.querySelector('[data-action="view"]')?.addEventListener('click', () => {
                this.showDetalhes(parc.id);
            });

            return div;
        },

        getStatusBadge(status, progresso = null) {
            // Se temos progresso, usar status mais descritivo
            if (progresso !== null) {
                if (progresso === 0) {
                    return '<span class="parc-card-badge badge-pendente">⏳ Pendente</span>';
                } else if (progresso === 100) {
                    return '<span class="parc-card-badge badge-paga">✅ Paga</span>';
                } else {
                    return '<span class="parc-card-badge badge-parcial">🔄 Parcialmente Paga</span>';
                }
            }

            // Fallback para status antigos
            const badges = {
                'ativo': '<span class="parc-card-badge badge-ativo">⏳ Pendente</span>',
                'paga': '<span class="parc-card-badge badge-paga">✅ Paga</span>',
                'concluido': '<span class="parc-card-badge badge-paga">✅ Paga</span>',
                'cancelado': '<span class="parc-card-badge badge-cancelado">❌ Cancelada</span>'
            };
            return badges[status] || '<span class="parc-card-badge badge-ativo">⏳ Pendente</span>';
        },

        getProximaParcela(parcelas) {
            if (!parcelas || parcelas.length === 0) return null;

            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);

            // Buscar primeira parcela não paga com data futura ou mais próxima
            const naoPagas = parcelas.filter(p => !p.pago)
                .sort((a, b) => new Date(a.data) - new Date(b.data));

            return naoPagas.length > 0 ? naoPagas[0] : null;
        },

        async showDetalhes(id) {
            try {
                // Garantir que o ID é um número inteiro
                const parcelamentoId = parseInt(id, 10);
                if (isNaN(parcelamentoId)) {
                    throw new Error('ID inválido');
                }

                console.log('showDetalhes chamado com ID:', parcelamentoId);
                const response = await API.buscarParcelamento(parcelamentoId);
                console.log('Resposta da API:', response);

                // A API retorna { success: true, data: { fatura_object } }
                const parc = response.data;

                if (!parc) {
                    throw new Error('Fatura não encontrada');
                }

                // Armazenar fatura atual no estado
                STATE.faturaAtual = parc;

                DOM.detalhesContent.innerHTML = this.renderDetalhes(parc);

                // Adicionar event listeners nos botões toggle
                const btnToggles = DOM.detalhesContent.querySelectorAll('.btn-toggle-parcela');
                btnToggles.forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        const itemId = parseInt(e.target.dataset.lancamentoId);
                        const isPago = e.target.dataset.pago === 'true';

                        await this.toggleParcelaPaga(parcelamentoId, itemId, !isPago);
                    });
                });

                // Limpar foco antes de mostrar o modal
                if (document.activeElement) {
                    document.activeElement.blur();
                }

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

        renderDetalhes(parc) {
            const tipoText = 'Despesas';
            const tipoIcon = '💸';
            const progresso = parc.progresso || 0;
            const valorPago = parc.valor_total * progresso / 100;
            const valorRestante = parc.valor_total - valorPago;

            let html = `
                <div class="detalhes-header">
                    <h3 class="detalhes-title">${Utils.escapeHtml(parc.descricao)}</h3>
                    
                    <!-- Informações Principais -->
                    <div class="detalhes-grid">
                        <div class="detalhes-item">
                            <span class="detalhes-label">💵 Valor Total</span>
                            <span class="detalhes-value detalhes-value-highlight">${Utils.formatMoney(parc.valor_total)}</span>
                        </div>
                        <div class="detalhes-item">
                            <span class="detalhes-label">📦 Itens</span>
                            <span class="detalhes-value">${parc.parcelas_pagas + parc.parcelas_pendentes} itens</span>
                        </div>
                        <div class="detalhes-item">
                            <span class="detalhes-label">📊 Tipo</span>
                            <span class="detalhes-value">${tipoIcon} ${tipoText}</span>
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

                    <!-- Barra de Progresso -->
                    <div class="detalhes-progresso">
                        <div class="progresso-info">
                            <span><strong>${parc.parcelas_pagas}</strong> de <strong>${parc.parcelas_pagas + parc.parcelas_pendentes}</strong> itens pagos</span>
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
                </div>

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
                    const isPaga = parcela.pago;
                    const statusClass = isPaga ? 'parcela-paga' : 'parcela-pendente';
                    const statusText = isPaga ? '✅ Paga' : '⏳ Pendente';
                    const rowClass = isPaga ? 'tr-paga' : '';

                    // Data de pagamento (se existir)
                    let dataPagamentoHtml = '';
                    if (isPaga && parcela.data_pagamento) {
                        const dataVenc = new Date(parcela.data_vencimento + 'T00:00:00');
                        const dataPag = new Date(parcela.data_pagamento + 'T00:00:00');
                        const diasDiff = Math.floor((dataVenc - dataPag) / (1000 * 60 * 60 * 24));

                        if (diasDiff > 0) {
                            dataPagamentoHtml = `<small style="color: #10b981; display: block; margin-top: 3px;">💚 Pago ${diasDiff} dia(s) antes</small>`;
                        } else if (diasDiff < 0) {
                            dataPagamentoHtml = `<small style="color: #ef4444; display: block; margin-top: 3px;">⚠️ Pago ${Math.abs(diasDiff)} dia(s) atrasado</small>`;
                        } else {
                            dataPagamentoHtml = `<small style="color: #3b82f6; display: block; margin-top: 3px;">🎯 Pago no dia do vencimento</small>`;
                        }
                    }

                    html += `
                        <tr class="${rowClass}">
                            <td data-label="#">
                                <span class="parcela-numero">${index + 1}</span>
                            </td>
                            <td data-label="Descrição">
                                <div class="parcela-desc">${Utils.escapeHtml(parcela.descricao || parc.descricao)}</div>
                            </td>
                            <td data-label="Vencimento">
                                <span class="parcela-data">${Utils.formatDate(parcela.data_vencimento)}</span>
                                ${dataPagamentoHtml}
                            </td>
                            <td data-label="Valor">
                                <span class="parcela-valor">${Utils.formatMoney(parcela.valor)}</span>
                            </td>
                            <td data-label="Status">
                                <span class="${statusClass}">${statusText}</span>
                            </td>
                            <td data-label="Ação">
                                ${isPaga ? `
                                    <button class="btn-toggle-parcela btn-desfazer" 
                                        data-lancamento-id="${parcela.id}" 
                                        data-pago="true"
                                        title="Desfazer pagamento">
                                        <i class="fas fa-undo"></i>
                                        Desfazer
                                    </button>
                                ` : `
                                    <button class="btn-toggle-parcela btn-pagar" 
                                        data-lancamento-id="${parcela.id}" 
                                        data-pago="false"
                                        title="Marcar como pago">
                                        <i class="fas fa-check"></i>
                                        Pagar
                                    </button>
                                `}
                            </td>
                        </tr>
                    `;
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

                if (!result.isConfirmed) {
                    return;
                }

                // Mostrar loading
                Swal.fire({
                    title: 'Processando...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                await API.toggleItemFatura(faturaId, itemId, marcarComoPago);

                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: marcarComoPago ? 'Item marcado como pago' : 'Pagamento desfeito',
                    timer: 2000,
                    showConfirmButton: false
                });

                // Recarregar a página de listagem
                await App.carregarParcelamentos();

                // Fechar o modal de detalhes
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

        async confirmarCancelamento(id, descricao) {
            const result = await Swal.fire({
                title: 'Cancelar Parcelamento?',
                html: `
        < p > Deseja realmente cancelar o parcelamento:</p >
                    <strong>${descricao}</strong>
                    <p class="text-muted mt-2">As parcelas não pagas serão removidas.</p>
    `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sim, cancelar',
                cancelButtonText: 'Não'
            });

            if (result.isConfirmed) {
                await App.cancelarParcelamento(id);
            }
        }
    };

    // ============================================================================
    // APP
    // ============================================================================

    const App = {
        async init() {
            try {
                // Inicializar modal de detalhes
                STATE.modalDetalhesInstance = new bootstrap.Modal(DOM.modalDetalhes, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });

                // Event listeners do modal para gerenciar foco corretamente
                DOM.modalDetalhes.addEventListener('show.bs.modal', () => {
                    // Remove foco de elementos externos antes de abrir
                    document.activeElement?.blur();
                });

                DOM.modalDetalhes.addEventListener('hidden.bs.modal', () => {
                    // Limpa qualquer foco residual
                    document.activeElement?.blur();
                });

                // Carregar filtros da URL
                this.aplicarFiltrosURL();

                // Carregar cartões
                await this.carregarCartoes();

                // Carregar dados
                await this.carregarParcelamentos();

                // Event listeners
                this.attachEventListeners();

                console.log('✅ Parcelamentos inicializado');
            } catch (error) {
                console.error('Erro ao inicializar:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro ao carregar página'
                });
            }
        },

        aplicarFiltrosURL() {
            const params = new URLSearchParams(window.location.search);

            // Aplicar filtro de cartão
            if (params.has('cartao_id')) {
                const cartaoId = params.get('cartao_id');
                STATE.filtros.cartao_id = cartaoId;
                if (DOM.filtroCartao) {
                    DOM.filtroCartao.value = cartaoId;
                }
            }

            // Aplicar filtro de mês/ano
            if (params.has('mes') && params.has('ano')) {
                STATE.filtros.mes = parseInt(params.get('mes'));
                STATE.filtros.ano = parseInt(params.get('ano'));

                // Atualizar o month-picker se existir
                if (window.monthPicker) {
                    const monthPickerDate = new Date(STATE.filtros.ano, STATE.filtros.mes - 1);
                    window.monthPicker.setDate(monthPickerDate);
                }
            }

            // Aplicar filtro de status
            if (params.has('status')) {
                STATE.filtros.status = params.get('status');
                if (DOM.filtroStatus) {
                    DOM.filtroStatus.value = params.get('status');
                }
            }
        },

        async carregarCartoes() {
            try {
                const response = await API.listarCartoes();
                STATE.cartoes = response.data || [];

                // Preencher select de cartões
                if (DOM.filtroCartao) {
                    DOM.filtroCartao.innerHTML = '<option value="">Todos os cartões</option>';
                    STATE.cartoes.forEach(cartao => {
                        const option = document.createElement('option');
                        option.value = cartao.id;
                        option.textContent = cartao.nome || `${cartao.bandeira} **** ${cartao.ultimos_digitos || ''} `;
                        DOM.filtroCartao.appendChild(option);
                    });
                }

                // Preencher select de anos (últimos 5 anos)
                if (DOM.filtroAno) {
                    const anoAtual = new Date().getFullYear();
                    DOM.filtroAno.innerHTML = '<option value="">Todos os anos</option>';
                    for (let i = 0; i < 5; i++) {
                        const ano = anoAtual - i;
                        const option = document.createElement('option');
                        option.value = ano;
                        option.textContent = ano;
                        DOM.filtroAno.appendChild(option);
                    }
                }
            } catch (error) {
                console.error('❌ Erro ao carregar cartões:', error);
            }
        },

        async carregarParcelamentos() {
            UI.showLoading();

            try {
                // Pegar mês e ano do sessionStorage (month-picker)
                const monthKey = sessionStorage.getItem('lukrato.month.dashboard');
                let mes = null;
                let ano = null;

                if (monthKey) {
                    const [anoStr, mesStr] = monthKey.split('-');
                    mes = parseInt(mesStr, 10);
                    ano = parseInt(anoStr, 10);
                    console.log('📅 Filtro de mês/ano ativo:', { mes, ano, monthKey });
                } else {
                    console.log('📅 Sem filtro de mês/ano - mostrando todos');
                }

                STATE.filtros.mes = mes;
                STATE.filtros.anoMes = ano;

                const response = await API.listarParcelamentos(
                    STATE.filtros.status,
                    STATE.filtros.cartao_id,
                    STATE.filtros.mes,
                    STATE.filtros.anoMes,
                    STATE.filtros.ano  // Filtro de ano separado
                );

                let parcelamentos = response.data?.faturas || [];
                console.log('📊 Faturas recebidas:', parcelamentos.length);

                STATE.parcelamentos = parcelamentos;
                UI.renderParcelamentos(parcelamentos);
            } catch (error) {
                console.error('❌ Erro ao carregar parcelamentos:', error);
                UI.showEmpty();
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message
                });
            } finally {
                UI.hideLoading();
            }
        },

        async cancelarParcelamento(id) {
            try {
                await API.cancelarParcelamento(id);

                Swal.fire({
                    icon: 'success',
                    title: 'Cancelado!',
                    text: 'Parcelamento cancelado com sucesso',
                    timer: 2000,
                    showConfirmButton: false
                });

                await this.carregarParcelamentos();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message
                });
            }
        },

        attachEventListeners() {
            // Filtros
            DOM.btnFiltrar?.addEventListener('click', () => {
                STATE.filtros.status = DOM.filtroStatus.value;
                STATE.filtros.cartao_id = DOM.filtroCartao?.value || '';
                STATE.filtros.ano = DOM.filtroAno?.value || '';
                this.carregarParcelamentos();
            });

            // Listener para mudança de mês do month-picker
            document.addEventListener('lukrato:month-changed', () => {
                console.log('Mês alterado, recarregando parcelamentos...');
                this.carregarParcelamentos();
            });
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

})();
