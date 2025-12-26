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
            parcelamentos: '/api/parcelamentos',
            categorias: '/api/categorias',
            contas: '/api/contas'
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
            tipo: ''
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

        async apiRequest(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCSRFToken()
                }
            };

            const response = await fetch(CONFIG.BASE_URL + url.replace(/^\//, ''), {
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
        async listarParcelamentos(status = '') {
            const url = status 
                ? `${CONFIG.ENDPOINTS.parcelamentos}?status=${status}`
                : CONFIG.ENDPOINTS.parcelamentos;
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

        async marcarParcelaPaga(lancamentoId, pago) {
            return await Utils.apiRequest(
                `${CONFIG.ENDPOINTS.parcelamentos}/parcelas/${lancamentoId}/pagar`,
                {
                    method: 'PUT',
                    body: JSON.stringify({ pago })
                }
            );
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

        async showDetalhes(id) {
            try {
                const response = await API.buscarParcelamento(id);
                const parc = response.data.parcelamento;

                DOM.detalhesContent.innerHTML = this.renderDetalhes(parc);

                // Adicionar event listeners nas parcelas
                const checkboxes = DOM.detalhesContent.querySelectorAll('.parcela-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', async (e) => {
                        const lancamentoId = e.target.dataset.lancamentoId;
                        const pago = e.target.checked;
                        await this.toggleParcelaPaga(lancamentoId, pago);
                    });
                });

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

            let html = `
                <div class="detalhes-header">
                    <h3 class="detalhes-title">${parc.descricao}</h3>
                    <div class="detalhes-grid">
                        <div class="detalhes-item">
                            <span class="detalhes-label">Valor Total</span>
                            <span class="detalhes-value">${Utils.formatMoney(parc.valor_total)}</span>
                        </div>
                        <div class="detalhes-item">
                            <span class="detalhes-label">Parcelas</span>
                            <span class="detalhes-value">${parc.numero_parcelas}x de ${Utils.formatMoney(parc.valor_parcela)}</span>
                        </div>
                        <div class="detalhes-item">
                            <span class="detalhes-label">Tipo</span>
                            <span class="detalhes-value">${tipoIcon} ${tipoText}</span>
                        </div>
                        <div class="detalhes-item">
                            <span class="detalhes-label">Status</span>
                            <span class="detalhes-value">${this.getStatusBadge(parc.status)}</span>
                        </div>
                        ${parc.categoria ? `
                            <div class="detalhes-item">
                                <span class="detalhes-label">Categoria</span>
                                <span class="detalhes-value">${parc.categoria.nome}</span>
                            </div>
                        ` : ''}
                        ${parc.conta ? `
                            <div class="detalhes-item">
                                <span class="detalhes-label">Conta</span>
                                <span class="detalhes-value">${parc.conta.nome}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>

                <h4 style="margin-bottom: 1rem;">Parcelas</h4>
                <div style="overflow-x: auto;">
                    <table class="parcelas-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Descrição</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                ${parc.status === 'ativo' ? '<th>Pagar</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (parc.parcelas && parc.parcelas.length > 0) {
                parc.parcelas.forEach(parcela => {
                    const isPaga = parcela.pago;
                    const statusClass = isPaga ? 'parcela-paga' : 'parcela-pendente';
                    const statusText = isPaga ? '✅ Paga' : '⏳ Pendente';

                    html += `
                        <tr>
                            <td data-label="#">
                                <span class="parcela-numero">${parcela.numero_parcela}</span>
                            </td>
                            <td data-label="Descrição">${parcela.descricao}</td>
                            <td data-label="Vencimento">${Utils.formatDate(parcela.data)}</td>
                            <td data-label="Valor">${Utils.formatMoney(parcela.valor)}</td>
                            <td data-label="Status">
                                <span class="${statusClass}">${statusText}</span>
                            </td>
                            ${parc.status === 'ativo' ? `
                                <td data-label="Pagar">
                                    <input type="checkbox" class="parcela-checkbox" 
                                           data-lancamento-id="${parcela.id}" 
                                           ${isPaga ? 'checked' : ''}>
                                </td>
                            ` : ''}
                        </tr>
                    `;
                });
            }

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            return html;
        },

        async toggleParcelaPaga(lancamentoId, pago) {
            try {
                await API.marcarParcelaPaga(lancamentoId, pago);
                await App.carregarParcelamentos();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: pago ? 'Parcela marcada como paga' : 'Parcela marcada como não paga',
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message
                });
            }
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
                STATE.modalDetalhesInstance = new bootstrap.Modal(DOM.modalDetalhes);

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
                const response = await API.listarParcelamentos(STATE.filtros.status);
                let parcelamentos = response.data?.parcelamentos || [];

                // Filtrar por tipo se necessário
                if (STATE.filtros.tipo) {
                    parcelamentos = parcelamentos.filter(p => p.tipo === STATE.filtros.tipo);
                }

                STATE.parcelamentos = parcelamentos;
                UI.renderParcelamentos(parcelamentos);
            } catch (error) {
                console.error('Erro ao carregar parcelamentos:', error);
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
