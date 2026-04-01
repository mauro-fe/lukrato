/**
 * ============================================================================
 * LUKRATO — Faturas / UI
 * ============================================================================
 * All rendering, DOM manipulation, modals and user interaction for Faturas.
 * ============================================================================
 */
import { CONFIG, DOM, STATE, Utils, Modules, getCategoryIconColor } from './state.js';
import { refreshIcons } from '../shared/ui.js';
import { getApiPayload, getErrorMessage } from '../shared/api.js';
import { CardListMethods } from './ui-card-list.js';

// ─── FaturasUI ──────────────────────────────────────────────────────────────

export const FaturasUI = {
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

    ...CardListMethods,

    async showDetalhes(id) {
        try {
            const response = await Modules.API.buscarParcelamento(id);
            const parc = getApiPayload(response, null);

            if (!parc) {
                // Fatura não existe mais - fechar modal se estiver aberto
                if (STATE.modalDetalhesInstance) {
                    STATE.modalDetalhesInstance.hide();
                }
                return;
            }

            STATE.faturaAtual = parc;

            // Aplicar cor do cartão no modal
            const modalEl = DOM.modalDetalhes;
            if (modalEl && parc.cartao) {
                const accent = this.getAccentColorSolid(parc.cartao);
                const modalContent = modalEl.querySelector('.modal-content');
                if (modalContent) modalContent.style.setProperty('--card-accent', accent);
            }

            DOM.detalhesContent.innerHTML = this.renderDetalhes(parc);
            refreshIcons();
            this.attachDetalhesEventListeners(parc.id);

            // Remover foco antes de mostrar modal
            document.activeElement?.blur();;
            STATE.modalDetalhesInstance.show();
        } catch (error) {
            console.error('Erro ao abrir detalhes:', error);

            // Se erro 404, a fatura foi excluída - apenas fechar modal silenciosamente
            if (error.message && error.message.includes('404')) {
                if (STATE.modalDetalhesInstance) {
                    STATE.modalDetalhesInstance.hide();
                }
                return;
            }

            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Não foi possível carregar os detalhes da fatura')
            });
        }
    },

    attachDetalhesEventListeners(faturaId) {
        // Botões de ordenação nas colunas
        const thSortable = DOM.detalhesContent.querySelectorAll('.th-sortable');
        thSortable.forEach(th => {
            th.addEventListener('click', () => {
                const col = th.dataset.sort;
                if (STATE.sortColumn === col) {
                    STATE.sortDirection = STATE.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    STATE.sortColumn = col;
                    STATE.sortDirection = 'asc';
                }
                // Re-renderizar detalhes mantendo estado
                if (STATE.faturaAtual) {
                    DOM.detalhesContent.innerHTML = this.renderDetalhes(STATE.faturaAtual);
                    refreshIcons();
                    this.attachDetalhesEventListeners(faturaId);
                }
            });
        });

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
        // Usar data_vencimento se disponível, senão usar mes_referencia/ano_referencia
        let vencimentoFormatado = '/';

        if (parc.data_vencimento) {
            vencimentoFormatado = Utils.formatDate(parc.data_vencimento);
        } else if (parc.mes_referencia && parc.ano_referencia) {
            const mesNome = this.getNomeMes(parc.mes_referencia);
            vencimentoFormatado = `${mesNome}/${parc.ano_referencia}`;
        }

        // Verificar se pode excluir (apenas se não tiver itens pagos)
        const temItensPagos = parc.parcelas_pagas > 0;
        const faturaCompletamentePaga = parc.parcelas_pendentes === 0 && parc.parcelas_pagas > 0;

        return `
            <div class="detalhes-header">
                <div class="detalhes-header-content" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="color: #9ca3af; font-size: 0.875rem; font-weight: 500;">Vencimento</span>
                        <h3 class="detalhes-title" style="margin: 0;">${vencimentoFormatado}</h3>
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        ${temItensPendentes ? `
                            <button class="btn-pagar-fatura" 
                                    onclick="window.abrirModalPagarFatura(${parc.id}, ${valorRestante})"
                                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                                <i data-lucide="credit-card"></i>
                                <span class="btn-text-desktop">Pagar Fatura</span>
                                <span class="btn-text-mobile">Pagar</span>
                            </button>
                        ` : ''}
                        ${faturaCompletamentePaga ? `
                            <button class="btn-reverter-fatura" 
                                    onclick="window.reverterPagamentoFaturaGlobal(${parc.id})"
                                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                                <i data-lucide="undo-2"></i>
                                <span class="btn-text-desktop">Reverter Pagamento</span>
                                <span class="btn-text-mobile">Reverter</span>
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
        const sortIcon = (col) => {
            if (STATE.sortColumn === col) {
                return STATE.sortDirection === 'asc'
                    ? '<i data-lucide="arrow-up" class="sort-icon active"></i>'
                    : '<i data-lucide="arrow-down" class="sort-icon active"></i>';
            }
            return '<i data-lucide="arrow-up-down" class="sort-icon"></i>';
        };

        // Ordenar parcelas
        const parcelasOrdenadas = this.sortParcelas(parc.parcelas || []);

        // Versão desktop: tabela
        let html = `
            <h4 class="parcelas-titulo">📋 Lista de Itens</h4>
            
            <!-- Tabela Desktop -->
            <div class="parcelas-container parcelas-desktop">
                <table class="parcelas-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="th-sortable" data-sort="descricao">Descrição ${sortIcon('descricao')}</th>
                            <th class="th-sortable" data-sort="data_compra">Data Compra ${sortIcon('data_compra')}</th>
                            <th class="th-sortable" data-sort="valor">Valor ${sortIcon('valor')}</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        if (parcelasOrdenadas.length > 0) {
            parcelasOrdenadas.forEach((parcela, index) => {
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
            const parcelasOrdMobile = this.sortParcelas(parc.parcelas);
            parcelasOrdMobile.forEach((parcela, index) => {
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

    sortParcelas(parcelas) {
        if (!parcelas || parcelas.length === 0) return [];
        const sorted = [...parcelas];
        const dir = STATE.sortDirection === 'asc' ? 1 : -1;
        const col = STATE.sortColumn;

        sorted.sort((a, b) => {
            if (col === 'descricao') {
                const descA = (a.descricao || '').toLowerCase();
                const descB = (b.descricao || '').toLowerCase();
                return descA.localeCompare(descB) * dir;
            }
            if (col === 'data_compra') {
                const dA = a.data_compra || '0000-00-00';
                const dB = b.data_compra || '0000-00-00';
                return dA.localeCompare(dB) * dir;
            }
            if (col === 'valor') {
                const vA = parseFloat(a.valor_parcela || a.valor || 0);
                const vB = parseFloat(b.valor_parcela || b.valor || 0);
                return (vA - vB) * dir;
            }
            return 0;
        });
        return sorted;
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
            const iconeCategoria = parcela.categoria.icone || 'tag';
            const nomeCategoria = parcela.categoria.nome || parcela.categoria;
            categoriaInfo = `<i data-lucide="${iconeCategoria}" style="width:14px;height:14px;display:inline-block;vertical-align:middle;color:${getCategoryIconColor(iconeCategoria)}"></i> ${Utils.escapeHtml(nomeCategoria)}`;
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
                    <span class="parcela-numero">${parcela.recorrente ? '<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);margin-right:3px;"></i> Recorrente' : `${parcela.numero_parcela || (index + 1)}/${parcela.total_parcelas || 1}`}</span>
                    <span class="${statusClass}">${statusText}</span>
                </div>
                <div class="parcela-card-body">
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Descrição</span>
                        <span class="parcela-card-value">${Utils.escapeHtml(descricaoItem)}${parcela.recorrente ? ' <span class="badge-recorrente" title="Assinatura recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>' : ''}</span>
                    </div>
                    ${parcela.data_compra ? `
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Data Compra</span>
                        <span class="parcela-card-value"><i data-lucide="shopping-cart" style="margin-right: 4px; font-size: 0.75rem;"></i>${Utils.formatDate(parcela.data_compra)}</span>
                    </div>
                    ` : ''}
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
            const nomeCategoria = parcela.categoria.nome || parcela.categoria;
            descricaoItem = nomeCategoria;
        }

        // Formatar data de compra
        const dataCompraFormatada = parcela.data_compra ? Utils.formatDate(parcela.data_compra) : '-';

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
                    <td data-label="Data Compra">
                        <span style="color: #10b981; font-size: 0.85rem;">${dataCompraFormatada}</span>
                    </td>
                    <td data-label="Valor">
                        <span class="parcela-valor" style="color: #10b981; font-weight: 600;">
                            - ${Utils.formatMoney(Math.abs(parcela.valor_parcela))}
                        </span>
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
                    <span class="parcela-numero">${parcela.recorrente ? '<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);"></i>' : `${parcela.numero_parcela}/${parcela.total_parcelas}`}</span>
                </td>
                <td data-label="Descrição" class="td-descricao">
                    <div class="parcela-desc">${Utils.escapeHtml(descricaoItem)}${parcela.recorrente ? ' <span class="badge-recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>' : ''}</div>
                </td>
                <td data-label="Data Compra">
                    <span style="font-size: 0.85rem; color: #9ca3af;">${dataCompraFormatada}</span>
                </td>
                <td data-label="Valor">
                    <span class="parcela-valor">${Utils.formatMoney(parcela.valor_parcela)}</span>
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
            // Item pago: sem botões individuais (usar reverter fatura completa)
            return `
                <div class="btn-group-parcela">
                    <span class="badge-pago" style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                        <i data-lucide="check"></i> Pago
                    </span>
                </div>
            `;
        } else {
            // Item pendente: apenas botões de editar e excluir (sem pagar individual)
            const ehParcelado = parcela.total_parcelas > 1;
            return `
                <div class="btn-group-parcela">
                    <button class="btn-toggle-parcela btn-editar" 
                        data-lancamento-id="${parcela.id}"
                        data-descricao="${Utils.escapeHtml(parcela.descricao || '')}"
                        data-valor="${parcela.valor_parcela || 0}"
                        title="Editar item">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="btn-toggle-parcela btn-excluir" 
                        data-lancamento-id="${parcela.id}"
                        data-eh-parcelado="${ehParcelado}"
                        data-total-parcelas="${parcela.total_parcelas || 1}"
                        title="Excluir item">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
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

            await Modules.API.toggleItemFatura(faturaId, itemId, marcarComoPago);

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
            await Modules.App.carregarParcelamentos();

            // Reabrir o modal com dados atualizados
            setTimeout(() => {
                FaturasUI.showDetalhes(faturaId);
            }, 100);

        } catch (error) {
            console.error('Erro ao alternar status:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Erro ao processar operação'),
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
            await Modules.App.carregarParcelamentos();

            // Reabrir o modal com dados atualizados
            setTimeout(() => {
                FaturasUI.showDetalhes(faturaId);
            }, 100);

        } catch (error) {
            console.error('Erro ao editar item:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Não foi possível atualizar o item.'),
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

            // Recarregar parcelamentos
            await Modules.App.carregarParcelamentos();

            // Verificar se a fatura ainda existe antes de reabrir o modal
            const faturaAindaExiste = STATE.parcelamentos.some(p => p.id === faturaId);

            if (faturaAindaExiste) {
                // Reabrir o modal com dados atualizados
                setTimeout(() => {
                    FaturasUI.showDetalhes(faturaId);
                }, 100);
            } else {
                // Fatura foi excluída (era o último item), fechar modal
                if (STATE.modalDetalhesInstance) {
                    STATE.modalDetalhesInstance.hide();
                }
            }

        } catch (error) {
            console.error('Erro ao excluir item:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Não foi possível excluir o item.'),
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
                <p style="color: #ef4444; margin-top: 1rem;"><i data-lucide="triangle-alert"></i> Esta ação não pode ser desfeita!</p>
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
                refreshIcons();
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

            // Recarregar parcelamentos
            await Modules.App.carregarParcelamentos();

            // Verificar se a fatura ainda existe antes de reabrir o modal
            const faturaAindaExiste = STATE.parcelamentos.some(p => p.id === faturaId);

            if (faturaAindaExiste) {
                // Reabrir o modal com dados atualizados
                setTimeout(() => {
                    FaturasUI.showDetalhes(faturaId);
                }, 100);
            } else {
                // Fatura foi excluída (era o último item), fechar modal
                if (STATE.modalDetalhesInstance) {
                    STATE.modalDetalhesInstance.hide();
                }
            }

        } catch (error) {
            console.error('Erro ao excluir parcelamento:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Não foi possível excluir o parcelamento.'),
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
                Modules.API.buscarParcelamento(faturaId),
                Modules.API.listarContas()
            ]);

            const fatura = getApiPayload(faturaResponse, null);
            const contas = getApiPayload(contasResponse, []);

            if (!fatura?.cartao) {
                throw new Error('Dados da fatura incompletos');
            }

            const cartaoId = fatura.cartao.id;
            const contaPadraoId = fatura.cartao.conta_id || null;

            // Extrair mês/ano da descrição da fatura (ex: "Fatura 2/2026")
            const descricao = fatura.descricao || '';
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
                            <i data-lucide="landmark"></i> Conta para débito:
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
                confirmButtonText: '<i data-lucide="check"></i> Sim, pagar tudo',
                cancelButtonText: 'Cancelar',
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                    refreshIcons();
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
            const response = await Modules.API.pagarFaturaCompleta(cartaoId, parseInt(mes), parseInt(ano), selectedContaId);

            if (!response.success) {
                throw new Error(response.message || 'Erro ao processar pagamento');
            }

            await Swal.fire({
                icon: 'success',
                title: 'Fatura Paga!',
                html: `
                    <p>${response.message || 'Fatura paga com sucesso!'}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor debitado:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${Utils.formatMoney(getApiPayload(response, {})?.valor_pago || valorTotal)}
                        </div>
                    </div>
                    <div style="color: #059669;">
                        <i data-lucide="circle-check" style="font-size: 2rem;"></i>
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
                    refreshIcons();
                }
            });

            await Modules.App.carregarParcelamentos();

            // Fechar o modal após pagamento completo
            STATE.modalDetalhesInstance.hide();

        } catch (error) {
            console.error('Erro ao pagar fatura completa:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro ao pagar fatura',
                text: getErrorMessage(error, 'Não foi possível processar o pagamento. Tente novamente.'),
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

// ─── Register in Modules ────────────────────────────────────────────────────

Modules.UI = FaturasUI;
