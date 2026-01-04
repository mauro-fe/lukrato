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
            parcelamentos: 'api/parcelamentos',
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
        filtroTipo: document.getElementById('filtroTipo'),
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
        filtros: {
            status: 'ativo',
            tipo: '',
            mes: null,  // Será definido pelo month-picker
            ano: null   // Será definido pelo month-picker
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
        async listarParcelamentos(status = '', mes = null, ano = null) {
            let url = CONFIG.ENDPOINTS.parcelamentos;
            const params = [];

            if (status) params.push(`status=${status}`);
            if (mes && ano) {
                params.push(`mes=${mes}`);
                params.push(`ano=${ano}`);
            }

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            return await Utils.apiRequest(url);
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
            const percentualPago = parc.percentual_pago || 0;
            const proximaParcela = this.getProximaParcela(parc.parcelas);

            const div = document.createElement('div');
            div.className = `parcelamento-card status-${parc.status}`;
            div.dataset.id = parc.id;

            const statusBadge = this.getStatusBadge(parc.status);
            const tipoIcon = parc.tipo === 'entrada' ? '💰' : '💸';

            div.innerHTML = `
                <div class="parc-card-header">
                    <h3 class="parc-card-title">${parc.descricao}</h3>
                    ${statusBadge}
                </div>

                <div class="parc-card-values">
                    <div class="parc-value-row">
                        <span class="parc-value-label">Valor Total</span>
                        <span class="parc-value-amount primary">${Utils.formatMoney(parc.valor_total)}</span>
                    </div>
                    <div class="parc-value-row">
                        <span class="parc-value-label">Valor da Parcela</span>
                        <span class="parc-value-amount">${Utils.formatMoney(parc.valor_parcela)}</span>
                    </div>
                    <div class="parc-value-row">
                        <span class="parc-value-label">Valor Restante</span>
                        <span class="parc-value-amount">${Utils.formatMoney(parc.valor_restante)}</span>
                    </div>
                </div>

                <div class="parc-progress-section">
                    <div class="parc-progress-header">
                        <span class="parc-progress-text">${parc.parcelas_pagas} de ${parc.numero_parcelas} pagas</span>
                        <span class="parc-progress-percent">${Math.round(parc.percentual_pago)}%</span>
                    </div>
                    <div class="parc-progress-bar">
                        <div class="parc-progress-fill" style="width: ${parc.percentual_pago}%"></div>
                    </div>
                </div>

                <div class="parc-card-info">
                    <div class="parc-info-item">
                        <i class="fas fa-calendar"></i>
                        <span>${Utils.formatDate(parc.data_criacao)}</span>
                    </div>
                    <div class="parc-info-item">
                        ${tipoIcon}
                        <span>${parc.tipo === 'entrada' ? 'Receita' : 'Despesa'}</span>
                    </div>
                    ${parc.categoria ? `
                        <div class="parc-info-item">
                            <i class="fas fa-folder"></i>
                            <span>${parc.categoria.nome}</span>
                        </div>
                    ` : ''}
                </div>

                ${proximaParcela ? `
                    <div class="parc-proxima-parcela">
                        <i class="fas fa-clock"></i>
                        <span>Próxima: ${Utils.formatDate(proximaParcela.data)} - ${Utils.formatMoney(proximaParcela.valor)}</span>
                    </div>
                ` : ''}

                </div>

                <div class="parc-card-actions">
                    <button class="parc-btn parc-btn-view" data-action="view" data-id="${parc.id}">
                        <i class="fas fa-eye"></i>
                        <span>Ver Detalhes</span>
                    </button>
                    ${parc.status === 'ativo' ? `
                        <button class="parc-btn parc-btn-cancel" data-action="cancel" data-id="${parc.id}">
                            <i class="fas fa-times"></i>
                            <span>Cancelar</span>
                        </button>
                    ` : ''}
                </div>
            `;

            // Event listeners
            div.querySelector('[data-action="view"]')?.addEventListener('click', () => {
                this.showDetalhes(parc.id);
            });

            div.querySelector('[data-action="cancel"]')?.addEventListener('click', () => {
                this.confirmarCancelamento(parc.id, parc.descricao);
            });

            return div;
        },

        getStatusBadge(status) {
            const badges = {
                'ativo': '<span class="parc-card-badge badge-ativo">✅ Ativo</span>',
                'concluido': '<span class="parc-card-badge badge-concluido">✔️ Concluído</span>',
                'cancelado': '<span class="parc-card-badge badge-cancelado">❌ Cancelado</span>'
            };
            return badges[status] || '';
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
                const parc = response.data.parcelamento;

                DOM.detalhesContent.innerHTML = this.renderDetalhes(parc);

                // Adicionar event listeners nos botões toggle
                const btnToggles = DOM.detalhesContent.querySelectorAll('.btn-toggle-parcela');
                btnToggles.forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        const lancamentoId = parseInt(e.target.dataset.lancamentoId);
                        const isPago = e.target.dataset.pago === 'true';
                        await this.toggleParcelaPaga(lancamentoId, !isPago);
                    });
                });

                // Limpar foco antes de mostrar o modal
                if (document.activeElement) {
                    document.activeElement.blur();
                }

                STATE.modalDetalhesInstance.show();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message
                });
            }
        },

        renderDetalhes(parc) {
            const tipoText = parc.tipo === 'entrada' ? 'Receita' : 'Despesa';
            const tipoIcon = parc.tipo === 'entrada' ? '💰' : '💸';
            const percentualPago = parc.percentual_pago || 0;

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
                            <span class="detalhes-label">📦 Parcelas</span>
                            <span class="detalhes-value">${parc.numero_parcelas}x de ${Utils.formatMoney(parc.valor_parcela)}</span>
                        </div>
                        <div class="detalhes-item">
                            <span class="detalhes-label">📊 Tipo</span>
                            <span class="detalhes-value">${tipoIcon} ${tipoText}</span>
                        </div>
                        <div class="detalhes-item">
                            <span class="detalhes-label">🎯 Status</span>
                            <span class="detalhes-value">${this.getStatusBadge(parc.status)}</span>
                        </div>
                        ${parc.categoria ? `
                            <div class="detalhes-item">
                                <span class="detalhes-label">🏷️ Categoria</span>
                                <span class="detalhes-value">${Utils.escapeHtml(parc.categoria.nome)}</span>
                            </div>
                        ` : ''}
                        ${parc.conta ? `
                            <div class="detalhes-item">
                                <span class="detalhes-label">🏦 Conta</span>
                                <span class="detalhes-value">${Utils.escapeHtml(parc.conta.nome)}</span>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Barra de Progresso -->
                    <div class="detalhes-progresso">
                        <div class="progresso-info">
                            <span><strong>${parc.parcelas_pagas}</strong> de <strong>${parc.numero_parcelas}</strong> parcelas pagas</span>
                            <span class="progresso-percent"><strong>${Math.round(percentualPago)}%</strong></span>
                        </div>
                        <div class="progresso-barra">
                            <div class="progresso-fill" style="width: ${percentualPago}%"></div>
                        </div>
                        <div class="progresso-valores">
                            <span class="valor-pago">✅ Pago: ${Utils.formatMoney(parc.valor_total - parc.valor_restante)}</span>
                            <span class="valor-restante">⏳ Restante: ${Utils.formatMoney(parc.valor_restante)}</span>
                        </div>
                    </div>
                </div>

                <h4 class="parcelas-titulo">📋 Lista de Parcelas</h4>
                <div class="parcelas-container">
                    <table class="parcelas-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Descrição</th>
                                <th style="width: 120px;">Vencimento</th>
                                <th style="width: 120px;">Valor</th>
                                <th style="width: 120px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (parc.parcelas && parc.parcelas.length > 0) {
                parc.parcelas.forEach(parcela => {
                    const isPaga = parcela.pago;
                    const statusClass = isPaga ? 'parcela-paga' : 'parcela-pendente';
                    const statusText = isPaga ? '✅ Paga' : '⏳ Pendente';
                    const rowClass = isPaga ? 'tr-paga' : '';

                    // Data de pagamento (se existir)
                    let dataPagamentoHtml = '';
                    if (isPaga && parcela.data_pagamento) {
                        const dataVenc = new Date(parcela.data + 'T00:00:00');
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
                                <span class="parcela-numero">${parcela.numero_parcela}/${parc.numero_parcelas}</span>
                            </td>
                            <td data-label="Descrição">
                                <div class="parcela-desc">${Utils.escapeHtml(parcela.descricao || parc.descricao)}</div>
                            </td>
                            <td data-label="Vencimento">
                                <span class="parcela-data">${Utils.formatDate(parcela.data)}</span>
                                ${dataPagamentoHtml}
                            </td>
                            <td data-label="Valor">
                                <span class="parcela-valor">${Utils.formatMoney(parcela.valor)}</span>
                            </td>
                            <td data-label="Status">
                                <span class="${statusClass}">${statusText}</span>
                            </td>
                        </tr>
                    `;
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
            `;

            return html;
        },

        async confirmarCancelamento(id, descricao) {
            const result = await Swal.fire({
                title: 'Cancelar Parcelamento?',
                html: `
                    <p>Deseja realmente cancelar o parcelamento:</p>
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
                STATE.filtros.ano = ano;

                const response = await API.listarParcelamentos(
                    STATE.filtros.status,
                    STATE.filtros.mes,
                    STATE.filtros.ano
                );

                let parcelamentos = response.data?.parcelamentos || [];
                console.log('📊 Parcelamentos recebidos:', parcelamentos.length);

                // Filtrar por tipo se necessário
                if (STATE.filtros.tipo) {
                    parcelamentos = parcelamentos.filter(p => p.tipo === STATE.filtros.tipo);
                    console.log('🔎 Após filtro de tipo:', parcelamentos.length);
                }

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
                STATE.filtros.tipo = DOM.filtroTipo.value;
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
