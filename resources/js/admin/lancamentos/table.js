/**
 * ============================================================================
 * LUKRATO — Lançamentos / TableManager (Desktop)
 * ============================================================================
 * Handles desktop table rendering, sorting, pagination, selection,
 * and row actions (edit, delete, mark-paid, cancel recurrence, expand).
 * ============================================================================
 */

import { CONFIG, DOM, STATE, Utils, Notifications, Modules } from './state.js';
import { handleMarcarPago, handleDesmarcarPago, handleCancelarRecorrencia, handleDelete, handleEdit } from './actions.js';

export const TableManager = {
    /**
     * Initialize table event listeners for sorting, pagination, and selection
     */
    init() {
        // Sortable headers
        const sortableHeaders = document.querySelectorAll('.sortable[data-sort]');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const field = header.dataset.sort;
                if (!field) return;

                // Toggle direction if same field, else default to desc
                if (STATE.sortField === field) {
                    STATE.sortDirection = STATE.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    STATE.sortField = field;
                    STATE.sortDirection = 'desc';
                }
                STATE.currentPage = 1;
                this.sortData();
                this.render();
                this.updateSortIndicators();
            });
        });

        // Select all checkbox
        if (DOM.selectAllCheckbox) {
            DOM.selectAllCheckbox.addEventListener('change', (e) => {
                const checked = e.target.checked;
                const checkboxes = DOM.tableBody?.querySelectorAll('.row-checkbox') || [];
                checkboxes.forEach(cb => {
                    const row = cb.closest('tr');
                    const id = row?.dataset.id;
                    if (!id) return;

                    // Find item to check if selectable
                    const item = STATE.filteredData.find(i => String(i.id) === String(id));
                    if (item && !Utils.isSaldoInicial(item) && !item._isParcelamentoGroup) {
                        cb.checked = checked;
                        if (checked) {
                            STATE.selectedIds.add(id);
                        } else {
                            STATE.selectedIds.delete(id);
                        }
                    }
                });
                this.updateSelectionInfo();
            });
        }

        // Page size selector
        if (DOM.pageSize) {
            DOM.pageSize.addEventListener('change', (e) => {
                STATE.pageSize = parseInt(e.target.value) || 10;
                STATE.currentPage = 1;
                this.render();
            });
        }

        // Prev/Next buttons
        if (DOM.prevPage) {
            DOM.prevPage.addEventListener('click', () => {
                if (STATE.currentPage > 1) {
                    this.goToPage(STATE.currentPage - 1);
                }
            });
        }
        if (DOM.nextPage) {
            DOM.nextPage.addEventListener('click', () => {
                const totalPages = Math.ceil(STATE.filteredData.length / STATE.pageSize);
                if (STATE.currentPage < totalPages) {
                    this.goToPage(STATE.currentPage + 1);
                }
            });
        }

        // Delegated event handler for table clicks
        if (DOM.tableBody) {
            DOM.tableBody.addEventListener('click', (e) => this.handleTableClick(e));
            DOM.tableBody.addEventListener('change', (e) => this.handleCheckboxChange(e));
        }
    },

    /**
     * Update sort indicators in table headers
     */
    updateSortIndicators() {
        const sortableHeaders = document.querySelectorAll('.sortable[data-sort]');
        sortableHeaders.forEach(header => {
            const field = header.dataset.sort;
            const icon = header.querySelector('.sort-icon');
            if (!icon) return;

            if (field === STATE.sortField) {
                icon.setAttribute('data-lucide', STATE.sortDirection === 'asc' ? 'arrow-up' : 'arrow-down');
                icon.setAttribute('class', 'sort-icon active');
            } else {
                icon.setAttribute('data-lucide', 'arrow-up-down');
                icon.setAttribute('class', 'sort-icon');
            }
        });
        if (window.lucide) lucide.createIcons();
    },

    /**
     * Store and prepare data for rendering
     */
    setData(items) {
        STATE.allData = Array.isArray(items) ? items : [];

        // Apply client-side filters (text search + status)
        let filtered = STATE.allData;

        // Text search filter
        const searchText = (DOM.filtroTexto?.value || '').trim().toLowerCase();
        if (searchText) {
            filtered = filtered.filter(item => {
                const desc = (item.descricao || '').toLowerCase();
                const cat = (item.categoria_nome || '').toLowerCase();
                const conta = (item.conta_nome || '').toLowerCase();
                return desc.includes(searchText) || cat.includes(searchText) || conta.includes(searchText);
            });
        }

        // Status filter (pago/pendente)
        const statusFilter = DOM.filtroStatus?.value || '';
        if (statusFilter === 'pago') {
            filtered = filtered.filter(item => item.pago == 1 || item.tipo === 'transferencia');
        } else if (statusFilter === 'pendente') {
            filtered = filtered.filter(item => item.pago != 1 && item.tipo !== 'transferencia');
        }

        // Process for parcelamento groups
        STATE.filteredData = Modules.ParcelamentoGrouper.processForTable(filtered);

        // Update active filter badges
        Modules.FilterBadges.update();

        // Sort data
        this.sortData();

        STATE.currentPage = 1;
        STATE.selectedIds.clear();

        if (DOM.selectAllCheckbox) {
            DOM.selectAllCheckbox.checked = false;
        }

        // Update sort indicators
        this.updateSortIndicators();
    },

    /**
     * Sort the filtered data based on current sort field and direction
     */
    sortData() {
        const field = STATE.sortField;
        const dir = STATE.sortDirection;

        STATE.filteredData.sort((a, b) => {
            let valA, valB;

            if (field === 'data') {
                const dateA = a.data || a.created_at || '';
                const dateB = b.data || b.created_at || '';
                valA = new Date(dateA).getTime() || 0;
                valB = new Date(dateB).getTime() || 0;
            } else if (field === 'valor') {
                valA = parseFloat(a.valor) || 0;
                valB = parseFloat(b.valor) || 0;
                // For groups, calculate total
                if (a._isParcelamentoGroup && a._parcelas) {
                    valA = a._parcelas.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
                }
                if (b._isParcelamentoGroup && b._parcelas) {
                    valB = b._parcelas.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
                }
            } else if (field === 'tipo') {
                valA = String(a.tipo || '').toLowerCase();
                valB = String(b.tipo || '').toLowerCase();
            } else {
                valA = String(a[field] || '');
                valB = String(b[field] || '');
            }

            if (typeof valA === 'string' && typeof valB === 'string') {
                return dir === 'asc'
                    ? valA.localeCompare(valB, 'pt-BR')
                    : valB.localeCompare(valA, 'pt-BR');
            }

            return dir === 'asc' ? (valA - valB) : (valB - valA);
        });
    },

    /**
     * Render the current page of data
     */
    render() {
        if (!DOM.tableBody) return;

        const total = STATE.filteredData.length;
        const totalPages = Math.max(1, Math.ceil(total / STATE.pageSize));
        STATE.currentPage = Math.min(STATE.currentPage, totalPages);

        const start = (STATE.currentPage - 1) * STATE.pageSize;
        const end = Math.min(start + STATE.pageSize, total);
        const pageData = STATE.filteredData.slice(start, end);

        // Render empty state if no data
        if (total === 0) {
            DOM.tableBody.innerHTML = `
                <tr>
                    <td colspan="10" class="empty-state-cell">
                        <div class="empty-state" style="text-align:center; padding: 3rem 1rem;">
                            <div class="empty-icon" style="width:120px;height:120px;margin:0 auto 1.5rem;background:var(--color-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="arrow-left-right" style="font-size:3rem;color:white;"></i>
                            </div>
                            <h3 style="color:var(--color-text);margin-bottom:0.75rem;font-size:1.5rem;font-weight:600;">Nenhum lançamento encontrado</h3>
                            <p style="color:var(--color-text-muted);margin-bottom:1.5rem;font-size:1rem;">Comece criando seu primeiro lançamento para gerenciar suas finanças</p>
                            <div style="display:flex;justify-content:center;">
                                <button type="button" class="btn btn-primary btn-lg" onclick="lancamentoGlobalManager.openModal()" style="background:var(--color-primary);border:none;padding:0.75rem 1.5rem;font-size:1rem;border-radius:var(--radius-md);color:white;font-weight:500;">
                                    <i data-lucide="plus"></i> Criar primeiro lançamento
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            if (window.lucide) lucide.createIcons();
            this.updatePagination();
            this.updateSelectionInfo();
            return;
        }

        // Render rows
        const rows = pageData.map(item => this.renderRow(item)).join('');
        DOM.tableBody.innerHTML = rows;
        if (window.lucide) lucide.createIcons();

        this.updatePagination();
        this.updateSelectionInfo();
        this.updateSortIndicators();
    },

    /**
     * Create HTML for a single row + expandable detail row
     */
    renderRow(item) {
        const id = item.id;
        const isGroup = item._isParcelamentoGroup;
        const isSaldoInicial = Utils.isSaldoInicial(item);
        const isSelectable = !isSaldoInicial && !isGroup;
        const isSelected = STATE.selectedIds.has(String(id));

        // Data + Hora
        const dataValue = item.data || item.created_at || '';
        const dataFormatted = Utils.fmtDate(dataValue);
        const horaLanc = item.hora_lancamento || '';

        // Tipo
        const tipoRaw = String(item.tipo || '').toLowerCase();
        const tipoClass = Utils.getTipoClass(tipoRaw);
        const tipoLabel = tipoRaw ? tipoRaw.charAt(0).toUpperCase() + tipoRaw.slice(1) : '-';

        // Categoria
        let categoria = item.categoria_nome ??
            (typeof item.categoria === 'object' ? item.categoria?.nome : item.categoria) ?? '-';
        if (categoria && typeof categoria === 'object') {
            categoria = categoria.nome ?? categoria.label ?? '-';
        }
        categoria = categoria || '-';

        // Conta
        let conta = item.conta_nome ??
            (typeof item.conta === 'object' ? item.conta?.nome : item.conta) ?? '-';
        if (conta && typeof conta === 'object') {
            conta = conta.nome ?? conta.label ?? '-';
        }
        conta = conta || '-';

        // Descrição
        let descricao = item.descricao ?? item.descricao_titulo ?? '';
        if (descricao && typeof descricao === 'object') {
            descricao = descricao.texto ?? descricao.value ?? '';
        }
        descricao = String(descricao || '-').trim();

        // Valor
        let valor = parseFloat(item.valor) || 0;
        if (isGroup && item._parcelas) {
            valor = item._parcelas.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
        }

        // Row classes
        const rowClasses = ['lk-table-row'];
        if (isSaldoInicial) rowClasses.push('lk-row-inicial');
        if (isGroup) rowClasses.push('parcelamento-grupo');
        if (isSelected) rowClasses.push('selected');

        // Checkbox cell
        const checkboxCell = isSelectable
            ? `<td class="td-checkbox">
                   <input type="checkbox" class="lk-checkbox row-checkbox" ${isSelected ? 'checked' : ''}>
               </td>`
            : `<td class="td-checkbox lk-cell-select-disabled"></td>`;

        // Expand button cell
        let expandCell;
        if (isSaldoInicial) {
            expandCell = '<td class="td-expand"></td>';
        } else if (isGroup) {
            expandCell = `<td class="td-expand">
                <button class="lk-expand-btn toggle-parcelas" data-parcelamento-id="${String(id).replace('grupo_', '')}" title="Ver parcelas">
                    <i data-lucide="chevron-right"></i>
                </button>
            </td>`;
        } else {
            expandCell = `<td class="td-expand">
                <button class="lk-expand-btn" data-action="expand" data-id="${id}" title="Ver detalhes">
                    <i data-lucide="chevron-right"></i>
                </button>
            </td>`;
        }

        // Data cell with optional time
        const dataDisplay = horaLanc
            ? `<div class="data-primary">${Utils.escapeHtml(dataFormatted)}</div><div class="data-hora">${Utils.escapeHtml(horaLanc)}</div>`
            : `<div class="data-primary">${Utils.escapeHtml(dataFormatted)}</div>`;

        // Description cell (clean, no badges)
        let descricaoCell;
        if (isGroup) {
            const totalParcelas = item._totalParcelas || item._parcelas.length;
            const parcelasPagas = item._parcelasPagas ?? item._parcelas.filter(p => p.pago).length;
            const valorParcela = totalParcelas > 0 ? valor / item._parcelas.length : 0;
            const percentual = totalParcelas > 0 ? (parcelasPagas / totalParcelas) * 100 : 0;
            descricaoCell = `
                <td class="td-descricao">
                    <div class="desc-parcelamento">
                        <div class="fw-bold">📦 ${Utils.escapeHtml(descricao)}</div>
                        <small class="text-muted">
                            ${totalParcelas}x de R$ ${valorParcela.toFixed(2)} 
                            · ${parcelasPagas}/${totalParcelas} pagas (${Math.round(percentual)}%)
                        </small>
                    </div>
                </td>`;
        } else {
            descricaoCell = `<td class="td-descricao"><span class="desc-text">${Utils.escapeHtml(descricao)}</span></td>`;
        }

        // Info/Tags icons column — small indicator icons
        const isRecorrente = item.recorrente == 1 || item.recorrente === true;
        const isCancelado = !!item.cancelado_em;
        const isPagoFlag = Boolean(item.pago);
        const dataLanc = new Date(item.data || item.created_at);
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        const isFuturo = !isPagoFlag && dataLanc > hoje;
        const temLembrete = item.lembrar_antes_segundos > 0;
        const isPagamentoFatura = item.origem_tipo === 'pagamento_fatura';

        let tagIcons = '';
        if (isCancelado) {
            tagIcons += '<span class="lk-tag-icon lk-tag-canceled" title="Cancelado"><i data-lucide="x-circle"></i></span>';
        } else if (isRecorrente) {
            tagIcons += `<span class="lk-tag-icon lk-tag-recorrente" title="Recorrente (${item.recorrencia_freq || ''})"><i data-lucide="refresh-cw"></i></span>`;
        }
        if (temLembrete) {
            tagIcons += '<span class="lk-tag-icon lk-tag-lembrete" title="Lembrete ativo"><i data-lucide="bell"></i></span>';
        }
        if (isFuturo && !isCancelado) {
            tagIcons += '<span class="lk-tag-icon lk-tag-futuro" title="Lançamento futuro"><i data-lucide="clock"></i></span>';
        }
        if (isPagamentoFatura) {
            tagIcons += '<span class="lk-tag-icon lk-tag-fatura" title="Pagamento de fatura"><i data-lucide="credit-card"></i></span>';
        }
        const tagsCell = `<td class="td-tags"><div class="lk-tags-wrap">${tagIcons}</div></td>`;

        // Pago Em column (replaces Status)
        const isPago = Boolean(item.pago);
        const isTransfer = Boolean(item.eh_transferencia);
        const dataPagamentoFormatted = Utils.fmtDate(item.data_pagamento);
        let pagoEmCell;
        if (isGroup) {
            // Mostrar status da parcela do mês atual (não o agregado — info já está na descrição)
            const currentParcela = item._parcelas && item._parcelas[0];
            const cpIsPago = currentParcela && (currentParcela.pago == 1 || currentParcela.pago === true);
            const cpDataPag = cpIsPago && currentParcela.data_pagamento ? Utils.fmtDate(currentParcela.data_pagamento) : '';
            if (cpIsPago && cpDataPag && cpDataPag !== '-') {
                pagoEmCell = `<td class="td-pago-em"><span class="lk-pago-badge lk-pago-ok"><i data-lucide="circle-check"></i> ${Utils.escapeHtml(cpDataPag)}</span></td>`;
            } else if (cpIsPago) {
                pagoEmCell = `<td class="td-pago-em"><span class="lk-pago-badge lk-pago-ok"><i data-lucide="circle-check"></i> Pago</span></td>`;
            } else {
                pagoEmCell = `<td class="td-pago-em"><span class="lk-pago-badge lk-pago-pendente"><i data-lucide="clock"></i> Pendente</span></td>`;
            }
        } else if (isTransfer) {
            pagoEmCell = `<td class="td-pago-em"><span class="lk-pago-badge lk-pago-transfer"><i data-lucide="repeat"></i> Transf.</span></td>`;
        } else if (isPago && dataPagamentoFormatted && dataPagamentoFormatted !== '-') {
            pagoEmCell = `<td class="td-pago-em"><span class="lk-pago-badge lk-pago-ok"><i data-lucide="circle-check"></i> ${Utils.escapeHtml(dataPagamentoFormatted)}</span></td>`;
        } else if (isPago) {
            pagoEmCell = `<td class="td-pago-em"><span class="lk-pago-badge lk-pago-ok"><i data-lucide="circle-check"></i> Pago</span></td>`;
        } else {
            pagoEmCell = `<td class="td-pago-em"><span class="lk-pago-badge lk-pago-pendente"><i data-lucide="clock"></i> Pendente</span></td>`;
        }

        // Valor cell
        let valorCell;
        if (isGroup) {
            const totalParcelas = item._totalParcelas || item._parcelas.length;
            const parcelasPagas = item._parcelasPagas ?? item._parcelas.filter(p => p.pago).length;
            const percentual = totalParcelas > 0 ? (parcelasPagas / totalParcelas) * 100 : 0;
            valorCell = `
                <td class="td-valor">
                    <div>
                        <div class="fw-bold valor-cell ${tipoClass}">${Utils.fmtMoney(valor)}</div>
                        <div class="progress mt-1" style="height: 4px;">
                            <div class="progress-bar bg-${tipoRaw === 'receita' ? 'success' : 'danger'}" 
                                 style="width: ${percentual}%"></div>
                        </div>
                    </div>
                </td>`;
        } else {
            valorCell = `<td class="td-valor"><span class="valor-cell ${tipoClass}">${Utils.fmtMoney(valor)}</span></td>`;
        }

        // Actions cell — unified 3-dot dropdown menu for ALL types
        let actionsCell;
        if (isSaldoInicial) {
            actionsCell = '<td class="td-acoes"></td>';
        } else if (isGroup) {
            const parcelamentoId = String(id).replace('grupo_', '');
            actionsCell = `
                <td class="td-acoes">
                    <div class="lk-dropdown">
                        <button class="lk-dropdown-trigger" title="Ações">
                            <i data-lucide="more-vertical"></i>
                        </button>
                        <div class="lk-dropdown-menu">
                            <button class="lk-dropdown-item toggle-parcelas-menu" data-parcelamento-id="${parcelamentoId}">
                                <i data-lucide="list"></i> Ver Parcelas
                            </button>
                            <div class="lk-dropdown-divider"></div>
                            <button class="lk-dropdown-item lk-dropdown-danger delete-parcelamento" data-parcelamento-id="${parcelamentoId}">
                                <i data-lucide="trash-2"></i> Cancelar Parcelamento
                            </button>
                        </div>
                    </div>
                </td>`;
        } else {
            const isPagoAction = Boolean(item.pago);
            const isCanceladoAction = !!item.cancelado_em;
            const isRecorrenteAction = item.recorrente == 1 || item.recorrente === true;

            let menuItems = '';

            // Editar
            if (Utils.canEditLancamento(item)) {
                menuItems += `<button class="lk-dropdown-item" data-action="edit" data-id="${id}"><i data-lucide="pen"></i> Editar</button>`;
            }

            // Marcar como Pago / Pendente
            if (!isCanceladoAction && !isTransfer) {
                if (!isPagoAction) {
                    menuItems += `<button class="lk-dropdown-item lk-dropdown-success" data-action="marcar-pago" data-id="${id}"><i data-lucide="circle-check"></i> Marcar como Pago</button>`;
                } else {
                    menuItems += `<button class="lk-dropdown-item lk-dropdown-warning" data-action="desmarcar-pago" data-id="${id}"><i data-lucide="clock"></i> Marcar como Pendente</button>`;
                }
            }

            // Cancelar Recorrência
            if (isRecorrenteAction && !isCanceladoAction) {
                menuItems += `<div class="lk-dropdown-divider"></div>`;
                menuItems += `<button class="lk-dropdown-item lk-dropdown-danger" data-action="cancelar-recorrencia" data-id="${id}"><i data-lucide="x-circle"></i> Cancelar Recorrência</button>`;
            }

            // Excluir
            menuItems += `<div class="lk-dropdown-divider"></div>`;
            menuItems += `<button class="lk-dropdown-item lk-dropdown-danger" data-action="delete" data-id="${id}"><i data-lucide="trash-2"></i> Excluir</button>`;

            actionsCell = `
                <td class="td-acoes">
                    <div class="lk-dropdown">
                        <button class="lk-dropdown-trigger" title="Ações">
                            <i data-lucide="more-vertical"></i>
                        </button>
                        <div class="lk-dropdown-menu">${menuItems}</div>
                    </div>
                </td>`;
        }

        // Build main row
        const mainRow = `
            <tr class="${rowClasses.join(' ')}" data-id="${id}">
                ${checkboxCell}
                ${expandCell}
                <td class="td-data">${dataDisplay}</td>
                <td class="td-tipo"><span class="badge-tipo ${tipoClass}">${Utils.escapeHtml(tipoLabel)}</span></td>
                ${descricaoCell}
                <td class="td-categoria">${Utils.escapeHtml(categoria)}</td>
                ${valorCell}
                ${tagsCell}
                ${pagoEmCell}
                ${actionsCell}
            </tr>`;

        // Build expandable detail row (hidden by default)
        if (isSaldoInicial || isGroup) {
            return mainRow;
        }

        const cartaoNome = item.cartao_nome || '';
        const cartaoBandeira = item.cartao_bandeira || '';
        const cartaoDisplay = cartaoNome ? `${cartaoNome}${cartaoBandeira ? ` (${cartaoBandeira})` : ''}` : '';
        const formaPgto = Utils.formatFormaPagamento(item.forma_pagamento);

        // Build detail chips
        let detailChips = '';
        detailChips += `<div class="lk-detail-chip"><i data-lucide="wallet"></i><span class="lk-detail-label">Conta</span><span class="lk-detail-value">${Utils.escapeHtml(conta)}</span></div>`;
        if (formaPgto && formaPgto !== '-') {
            detailChips += `<div class="lk-detail-chip"><i data-lucide="banknote"></i><span class="lk-detail-label">Pagamento</span><span class="lk-detail-value">${formaPgto}</span></div>`;
        }
        if (cartaoDisplay) {
            detailChips += `<div class="lk-detail-chip"><i data-lucide="credit-card"></i><span class="lk-detail-label">Cartão</span><span class="lk-detail-value">${Utils.escapeHtml(cartaoDisplay)}</span></div>`;
        }
        if (isRecorrente && !isCancelado) {
            const freq = item.recorrencia_freq || 'mensal';
            let recInfo = freq.charAt(0).toUpperCase() + freq.slice(1);
            if (item.recorrencia_fim) recInfo += ` · até ${Utils.fmtDate(item.recorrencia_fim)}`;
            else if (item.recorrencia_total) recInfo += ` · ${item.recorrencia_total}x`;
            else recInfo += ' · sem fim';
            detailChips += `<div class="lk-detail-chip lk-chip-recorrente"><i data-lucide="refresh-cw"></i><span class="lk-detail-label">Recorrência</span><span class="lk-detail-value">${recInfo}</span></div>`;
        }
        if (isCancelado) {
            detailChips += `<div class="lk-detail-chip lk-chip-cancelado"><i data-lucide="x-circle"></i><span class="lk-detail-label">Cancelado</span><span class="lk-detail-value">${Utils.fmtDate(item.cancelado_em)}</span></div>`;
        }
        if (temLembrete) {
            const segs = parseInt(item.lembrar_antes_segundos);
            let tempoLabel = '';
            if (segs >= 604800) tempoLabel = Math.round(segs / 604800) + ' semana(s) antes';
            else if (segs >= 86400) tempoLabel = Math.round(segs / 86400) + ' dia(s) antes';
            else if (segs >= 3600) tempoLabel = Math.round(segs / 3600) + ' hora(s) antes';
            else tempoLabel = Math.round(segs / 60) + ' min antes';
            let canais = [];
            if (item.canal_inapp) canais.push('App');
            if (item.canal_email) canais.push('E-mail');
            detailChips += `<div class="lk-detail-chip lk-chip-lembrete"><i data-lucide="bell"></i><span class="lk-detail-label">Lembrete</span><span class="lk-detail-value">${tempoLabel} · ${canais.join(', ') || 'Nenhum canal'}</span></div>`;
        }
        if (item.parcela_atual && item.total_parcelas) {
            detailChips += `<div class="lk-detail-chip"><i data-lucide="layers"></i><span class="lk-detail-label">Parcela</span><span class="lk-detail-value">${item.parcela_atual}/${item.total_parcelas}</span></div>`;
        }
        if (item.observacao) {
            detailChips += `<div class="lk-detail-chip lk-chip-full"><i data-lucide="message-square"></i><span class="lk-detail-label">Obs</span><span class="lk-detail-value">${Utils.escapeHtml(item.observacao)}</span></div>`;
        }

        const detailRow = `
            <tr class="lk-detail-row" data-detail-for="${id}" style="display: none;">
                <td colspan="10">
                    <div class="lk-detail-panel">
                        <div class="lk-detail-chips">
                            ${detailChips}
                        </div>
                    </div>
                </td>
            </tr>`;

        return mainRow + detailRow;
    },

    /**
     * Navigate to a specific page
     */
    goToPage(page) {
        const totalPages = Math.max(1, Math.ceil(STATE.filteredData.length / STATE.pageSize));
        const safePage = Math.min(Math.max(1, page), totalPages);

        if (safePage !== STATE.currentPage) {
            STATE.currentPage = safePage;
            this.render();
        }
    },

    /**
     * Update pagination controls
     */
    updatePagination() {
        const total = STATE.filteredData.length;
        const totalPages = Math.max(1, Math.ceil(total / STATE.pageSize));
        const start = total > 0 ? (STATE.currentPage - 1) * STATE.pageSize + 1 : 0;
        const end = Math.min(STATE.currentPage * STATE.pageSize, total);

        // Update info text
        if (DOM.paginationInfo) {
            if (total === 0) {
                DOM.paginationInfo.textContent = '0 lançamentos';
            } else {
                DOM.paginationInfo.textContent = `${start}-${end} de ${total} lançamentos`;
            }
        }

        // Update buttons
        if (DOM.prevPage) {
            DOM.prevPage.disabled = STATE.currentPage <= 1;
        }
        if (DOM.nextPage) {
            DOM.nextPage.disabled = STATE.currentPage >= totalPages;
        }

        // Update page numbers
        if (DOM.pageNumbers) {
            const pages = [];
            const maxVisible = 5;
            let startPage = Math.max(1, STATE.currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);

            if (endPage - startPage + 1 < maxVisible) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === STATE.currentPage;
                pages.push(`
                    <button type="button" class="page-number-btn ${isActive ? 'active' : ''}" 
                            data-page="${i}" ${isActive ? 'disabled' : ''}>
                        ${i}
                    </button>
                `);
            }

            DOM.pageNumbers.innerHTML = pages.join('');

            // Add click handlers for page numbers
            DOM.pageNumbers.querySelectorAll('.page-number-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const page = parseInt(btn.dataset.page);
                    if (page) this.goToPage(page);
                });
            });
        }
    },

    /**
     * Update selection info (count and button state)
     */
    updateSelectionInfo() {
        const count = STATE.selectedIds.size;

        if (DOM.selCountSpan) {
            DOM.selCountSpan.textContent = String(count);
        }

        if (DOM.btnExcluirSel) {
            DOM.btnExcluirSel.toggleAttribute('disabled', count === 0);
        }

        // Update select all checkbox state
        if (DOM.selectAllCheckbox && DOM.tableBody) {
            const checkboxes = DOM.tableBody.querySelectorAll('.row-checkbox');
            const checkedCount = DOM.tableBody.querySelectorAll('.row-checkbox:checked').length;
            const totalSelectable = checkboxes.length;

            if (totalSelectable === 0) {
                DOM.selectAllCheckbox.checked = false;
                DOM.selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === 0) {
                DOM.selectAllCheckbox.checked = false;
                DOM.selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === totalSelectable) {
                DOM.selectAllCheckbox.checked = true;
                DOM.selectAllCheckbox.indeterminate = false;
            } else {
                DOM.selectAllCheckbox.checked = false;
                DOM.selectAllCheckbox.indeterminate = true;
            }
        }
    },

    /**
     * Handle clicks on table (edit/delete actions)
     */
    handleTableClick(e) {
        // ─── Dropdown toggle (3-dot menu) ────────────────────────────
        const dropdownTrigger = e.target.closest('.lk-dropdown-trigger');
        if (dropdownTrigger) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = dropdownTrigger.closest('.lk-dropdown');
            const menu = dropdown?.querySelector('.lk-dropdown-menu');
            if (!menu) return;

            // Close all other open dropdowns
            document.querySelectorAll('.lk-dropdown-menu.open').forEach(m => {
                if (m !== menu) m.classList.remove('open');
            });

            menu.classList.toggle('open');

            // Position the menu (above or below depending on space)
            const rect = dropdownTrigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            if (spaceBelow < 200) {
                menu.style.bottom = '100%';
                menu.style.top = 'auto';
            } else {
                menu.style.top = '100%';
                menu.style.bottom = 'auto';
            }

            // Auto-close on outside click
            const closeHandler = (ev) => {
                if (!dropdown.contains(ev.target)) {
                    menu.classList.remove('open');
                    document.removeEventListener('click', closeHandler, true);
                }
            };
            setTimeout(() => document.addEventListener('click', closeHandler, true), 0);

            // Render lucide icons in newly opened menu
            if (menu.classList.contains('open') && window.lucide) {
                lucide.createIcons({ nodes: [menu] });
            }
            return;
        }

        // ─── Dropdown item click → close menu ───────────────────────
        const dropdownItem = e.target.closest('.lk-dropdown-item');
        if (dropdownItem) {
            const menu = dropdownItem.closest('.lk-dropdown-menu');
            if (menu) menu.classList.remove('open');
        }

        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const id = btn.dataset.id;
        if (!id) return;

        const item = STATE.filteredData.find(i => String(i.id) === String(id));
        if (!item) return;

        if (action === 'expand') {
            this.toggleDetailRow(id, btn);
            return;
        }

        if (action === 'edit') {
            handleEdit(item);
        }

        if (action === 'delete') {
            handleDelete(id, item, btn);
        }

        if (action === 'marcar-pago') {
            handleMarcarPago(id, btn);
        }

        if (action === 'desmarcar-pago') {
            handleDesmarcarPago(id, btn);
        }

        if (action === 'cancelar-recorrencia') {
            handleCancelarRecorrencia(id, btn);
        }
    },

    /**
     * Toggle expand/collapse detail row
     */
    toggleDetailRow(id, btn) {
        const detailRow = DOM.tableBody.querySelector(`tr.lk-detail-row[data-detail-for="${id}"]`);
        if (!detailRow) return;

        const isOpen = detailRow.style.display !== 'none';

        if (isOpen) {
            // Collapse
            detailRow.style.display = 'none';
            btn.classList.remove('expanded');
        } else {
            // Expand
            detailRow.style.display = '';
            btn.classList.add('expanded');
            // Render Lucide icons inside newly visible detail panel
            if (typeof lucide !== 'undefined') {
                lucide.createIcons({ nodes: [detailRow] });
            }
        }
    },

    /**
     * Handle checkbox changes for row selection
     */
    handleCheckboxChange(e) {
        const checkbox = e.target.closest('.row-checkbox');
        if (!checkbox) return;

        const row = checkbox.closest('tr');
        const id = row?.dataset.id;
        if (!id) return;

        if (checkbox.checked) {
            STATE.selectedIds.add(id);
            row.classList.add('selected');
        } else {
            STATE.selectedIds.delete(id);
            row.classList.remove('selected');
        }

        this.updateSelectionInfo();
    },

    /**
     * Compatibility method: render rows from items array
     */
    renderRows(items) {
        this.setData(items);
        this.render();
    },

    /**
     * Get selected row IDs (for bulk operations)
     */
    getSelectedIds() {
        return Array.from(STATE.selectedIds);
    },

    /**
     * Clear all selections
     */
    clearSelection() {
        STATE.selectedIds.clear();
        if (DOM.selectAllCheckbox) {
            DOM.selectAllCheckbox.checked = false;
            DOM.selectAllCheckbox.indeterminate = false;
        }
        const checkboxes = DOM.tableBody?.querySelectorAll('.row-checkbox') || [];
        checkboxes.forEach(cb => cb.checked = false);
        const rows = DOM.tableBody?.querySelectorAll('.selected') || [];
        rows.forEach(row => row.classList.remove('selected'));
        this.updateSelectionInfo();
    }
};

// Register in shared Modules registry for cross-module access
Modules.TableManager = TableManager;
