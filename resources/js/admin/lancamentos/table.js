/**
 * ============================================================================
 * LUKRATO — Lançamentos / TableManager (Card Feed)
 * ============================================================================
 * Renders financial transactions as a modern card+timeline feed.
 * Handles sorting, pagination, selection, expand/collapse, and row actions.
 * ============================================================================
 */

import { CONFIG, DOM, STATE, Utils, Notifications, Modules } from './state.js';
import { handleMarcarPago, handleDesmarcarPago, handleCancelarRecorrencia, handleDelete, handleEdit } from './actions.js';
import { closeAllDropdownMenus, closeDropdownMenu, resolveDropdownMenu, toggleDropdownMenu } from './dropdown.js';

/* Category → Lucide icon mapping */
const CATEGORY_ICONS = {
    'alimentação': 'utensils', 'alimentacao': 'utensils',
    'moradia': 'home', 'casa': 'home', 'aluguel': 'home',
    'transporte': 'car', 'uber': 'car', 'combustível': 'fuel', 'combustivel': 'fuel',
    'saúde': 'heart-pulse', 'saude': 'heart-pulse',
    'educação': 'graduation-cap', 'educacao': 'graduation-cap',
    'lazer': 'gamepad-2', 'entretenimento': 'gamepad-2',
    'vestuário': 'shirt', 'vestuario': 'shirt', 'roupas': 'shirt',
    'salário': 'briefcase', 'salario': 'briefcase',
    'freelance': 'laptop', 'trabalho': 'laptop',
    'investimento': 'trending-up', 'investimentos': 'trending-up',
    'assinatura': 'repeat', 'assinaturas': 'repeat',
    'mercado': 'shopping-cart', 'supermercado': 'shopping-cart', 'compras': 'shopping-bag',
    'telefone': 'smartphone', 'internet': 'wifi', 'celular': 'smartphone',
    'energia': 'zap', 'luz': 'zap', 'água': 'droplets', 'agua': 'droplets',
    'pets': 'paw-print', 'pet': 'paw-print',
    'presente': 'gift', 'doação': 'heart-handshake', 'doacao': 'heart-handshake',
    'viagem': 'plane', 'férias': 'palmtree', 'ferias': 'palmtree',
    'imposto': 'landmark', 'impostos': 'landmark', 'taxa': 'landmark',
    'seguro': 'shield', 'seguros': 'shield',
    'eventual': 'zap', 'outros': 'circle-dot', 'sem categoria': 'minus',
};

function getCategoryIcon(categoryName) {
    const key = (categoryName || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    return CATEGORY_ICONS[key] || 'receipt';
}

export const TableManager = {
    handleActionButton(btn) {
        if (!btn) return;

        const action = btn.dataset.action;
        const id = btn.dataset.id;
        if (!id) return;

        const item = STATE.filteredData.find(i => String(i.id) === String(id));
        if (!item) return;

        if (action === 'expand') { this.toggleDetailPanel(id, btn); return; }
        if (action === 'edit') handleEdit(item);
        if (action === 'delete') handleDelete(id, item, btn);
        if (action === 'marcar-pago') handleMarcarPago(id, btn);
        if (action === 'desmarcar-pago') handleDesmarcarPago(id, btn);
        if (action === 'cancelar-recorrencia') handleCancelarRecorrencia(id, btn);
    },

    init() {
        // Sortable buttons (feed toolbar)
        const sortableHeaders = document.querySelectorAll('.lk-feed-sort-btn.sortable[data-sort], .sortable[data-sort]');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const field = header.dataset.sort;
                if (!field) return;
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
                    const card = cb.closest('.lk-txn-card');
                    const id = card?.dataset.id;
                    if (!id) return;
                    const item = STATE.filteredData.find(i => String(i.id) === String(id));
                    if (item && !Utils.isSaldoInicial(item) && !item._isParcelamentoGroup) {
                        cb.checked = checked;
                        if (checked) {
                            STATE.selectedIds.add(id);
                            card.classList.add('selected');
                        } else {
                            STATE.selectedIds.delete(id);
                            card.classList.remove('selected');
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
                if (STATE.currentPage > 1) this.goToPage(STATE.currentPage - 1);
            });
        }
        if (DOM.nextPage) {
            DOM.nextPage.addEventListener('click', () => {
                const totalPages = Math.ceil(STATE.filteredData.length / STATE.pageSize);
                if (STATE.currentPage < totalPages) this.goToPage(STATE.currentPage + 1);
            });
        }

        // Delegated event handlers for clicks and checkbox changes
        if (DOM.tableBody) {
            DOM.tableBody.addEventListener('click', (e) => this.handleTableClick(e));
            DOM.tableBody.addEventListener('change', (e) => this.handleCheckboxChange(e));
        }
    },

    updateSortIndicators() {
        const sortBtns = document.querySelectorAll('.lk-feed-sort-btn.sortable[data-sort], .sortable[data-sort]');
        sortBtns.forEach(btn => {
            const field = btn.dataset.sort;
            const icon = btn.querySelector('.sort-icon');
            if (!icon) return;
            if (field === STATE.sortField) {
                icon.setAttribute('data-lucide', STATE.sortDirection === 'asc' ? 'arrow-up' : 'arrow-down');
                icon.setAttribute('class', 'sort-icon active');
                btn.classList.add('active');
            } else {
                icon.setAttribute('data-lucide', 'arrow-up-down');
                icon.setAttribute('class', 'sort-icon');
                btn.classList.remove('active');
            }
        });
        if (window.lucide) lucide.createIcons();
    },

    setData(items, options = {}) {
        const { resetPage = true, clearSelection = true } = options;
        STATE.allData = Array.isArray(items) ? items : [];
        STATE.filteredData = Modules.ParcelamentoGrouper.processForTable(STATE.allData);
        Modules.FilterBadges.update();
        this.sortData();
        if (resetPage) STATE.currentPage = 1;
        if (clearSelection) STATE.selectedIds.clear();
        if (DOM.selectAllCheckbox) DOM.selectAllCheckbox.checked = false;
        this.updateSortIndicators();
    },

    sortData() {
        const field = STATE.sortField;
        const dir = STATE.sortDirection;
        STATE.filteredData.sort((a, b) => {
            let valA, valB;
            if (field === 'data') {
                valA = new Date(a.data || a.created_at || '').getTime() || 0;
                valB = new Date(b.data || b.created_at || '').getTime() || 0;
            } else if (field === 'valor') {
                valA = parseFloat(a.valor) || 0;
                valB = parseFloat(b.valor) || 0;
                if (a._isParcelamentoGroup && a._parcelas)
                    valA = a._parcelas.reduce((s, p) => s + parseFloat(p.valor || 0), 0);
                if (b._isParcelamentoGroup && b._parcelas)
                    valB = b._parcelas.reduce((s, p) => s + parseFloat(p.valor || 0), 0);
            } else if (field === 'tipo') {
                valA = String(a.tipo || '').toLowerCase();
                valB = String(b.tipo || '').toLowerCase();
            } else {
                valA = String(a[field] || '');
                valB = String(b[field] || '');
            }
            if (typeof valA === 'string' && typeof valB === 'string')
                return dir === 'asc' ? valA.localeCompare(valB, 'pt-BR') : valB.localeCompare(valA, 'pt-BR');
            return dir === 'asc' ? (valA - valB) : (valB - valA);
        });
    },

    render() {
        if (!DOM.tableBody) return;
        closeAllDropdownMenus();
        const total = STATE.filteredData.length;
        const totalPages = Math.max(1, Math.ceil(total / STATE.pageSize));
        STATE.currentPage = Math.min(STATE.currentPage, totalPages);
        const start = (STATE.currentPage - 1) * STATE.pageSize;
        const end = Math.min(start + STATE.pageSize, total);
        const pageData = STATE.filteredData.slice(start, end);

        if (total === 0) {
            const emptyState = Utils.getListEmptyStateMeta();
            DOM.tableBody.innerHTML = `
                <div class="lk-feed-empty">
                    <div class="lk-empty-icon">
                        <i data-lucide="arrow-left-right" style="font-size:3rem;color:white;"></i>
                    </div>
                    <h3 class="lk-empty-title">${Utils.escapeHtml(emptyState.title)}</h3>
                    <p class="lk-empty-description">${Utils.escapeHtml(emptyState.description)}</p>
                    <div class="lk-empty-actions">
                        <button type="button" class="modern-btn primary" onclick="lancamentoGlobalManager.openModal()">
                            <i data-lucide="plus"></i> Criar primeiro lançamento
                        </button>
                        ${emptyState.showClearFilters ? `
                            <button type="button" class="lk-empty-secondary-btn" onclick="document.getElementById('btnLimparFiltros')?.click()">
                                Limpar filtros
                            </button>` : ''}
                    </div>
                </div>`;
            if (window.lucide) lucide.createIcons();
            this.updatePagination();
            this.updateSelectionInfo();
            return;
        }

        const parts = [];
        let lastDateKey = null;
        pageData.forEach(item => {
            if (STATE.sortField === 'data') {
                const dateKey = this.getDateGroupKey(item);
                if (dateKey !== lastDateKey) {
                    parts.push(this.renderDateGroupRow(item));
                    lastDateKey = dateKey;
                }
            }
            parts.push(this.renderRow(item));
        });
        DOM.tableBody.innerHTML = parts.join('');
        // Render lucide icons — use LK.refreshIcons (patched) or fallback to direct call
        if (window.LK?.refreshIcons) {
            LK.refreshIcons();
        } else if (window.lucide) {
            lucide.createIcons();
        }
        // Deferred pass to catch any icons missed in the initial synchronous call
        requestAnimationFrame(() => {
            const pending = DOM.tableBody.querySelectorAll('i[data-lucide]');
            if (pending.length) {
                if (window.LK?.refreshIcons) LK.refreshIcons();
                else if (window.lucide) lucide.createIcons();
            }
        });
        this.updatePagination();
        this.updateSelectionInfo();
        this.updateSortIndicators();
    },

    renderLoading() {
        if (!DOM.tableBody) return;
        closeAllDropdownMenus();
        DOM.tableBody.innerHTML = `
            <div class="lk-feed-loading">
                <div class="spinner-border" role="status" style="width:2rem;height:2rem;color:var(--color-primary);">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p>Atualizando lançamentos...</p>
            </div>`;
        if (window.lucide) lucide.createIcons();
        this.updatePagination();
        this.updateSelectionInfo();
    },

    getDateGroupKey(item) {
        const raw = item?.data || item?.created_at || '';
        if (!raw) return 'sem-data';
        if (typeof raw === 'string' && /^\d{4}-\d{2}-\d{2}/.test(raw)) return raw.slice(0, 10);
        const parsed = new Date(raw);
        if (Number.isNaN(parsed.getTime())) return 'sem-data';
        return `${parsed.getFullYear()}-${String(parsed.getMonth() + 1).padStart(2, '0')}-${String(parsed.getDate()).padStart(2, '0')}`;
    },

    formatDateGroupLabel(item) {
        const key = this.getDateGroupKey(item);
        if (key === 'sem-data') return { day: '--', month: 'SEM DATA', full: 'Sem data', friendly: '' };
        const [year, month, day] = key.split('-').map(Number);
        const date = new Date(year, month - 1, day);
        const monthLabel = new Intl.DateTimeFormat('pt-BR', { month: 'short' }).format(date).replace('.', '').toUpperCase();
        const friendly = this._getFriendlyDateLabel(date);
        return { day: String(day).padStart(2, '0'), month: monthLabel, full: `${String(day).padStart(2, '0')} ${monthLabel}`, friendly };
    },

    _getFriendlyDateLabel(date) {
        const hoje = new Date(); hoje.setHours(0, 0, 0, 0);
        const target = new Date(date); target.setHours(0, 0, 0, 0);
        const diffDays = Math.round((target - hoje) / 86400000);
        if (diffDays === 0) return 'Hoje';
        if (diffDays === -1) return 'Ontem';
        if (diffDays === 1) return 'Amanhã';
        if (diffDays > 1 && diffDays <= 6) return new Intl.DateTimeFormat('pt-BR', { weekday: 'long' }).format(target);
        if (diffDays < -1 && diffDays >= -6) {
            const weekday = new Intl.DateTimeFormat('pt-BR', { weekday: 'long' }).format(target);
            return `${weekday} passada`;
        }
        return '';
    },

    renderDateGroupRow(item) {
        const label = this.formatDateGroupLabel(item);
        const friendlyHtml = label.friendly ? `<span class="lk-feed-date-friendly">${label.friendly}</span>` : '';
        return `
            <div class="lk-feed-date-group" role="separator">
                <div class="lk-feed-date-dot"></div>
                <div class="lk-feed-date-content">
                    <span class="lk-feed-date-day">${label.day}</span>
                    <span class="lk-feed-date-month">${label.month}</span>
                    ${friendlyHtml}
                </div>
                <div class="lk-feed-date-line"></div>
            </div>`;
    },

    renderRow(item) {
        const id = item.id;
        const isGroup = item._isParcelamentoGroup;
        const isSaldoInicial = Utils.isSaldoInicial(item);
        const isSelectable = !isSaldoInicial && !isGroup;
        const isSelected = STATE.selectedIds.has(String(id));

        // Data
        const timelineMeta = Utils.getLancamentoTimelineMeta(item);

        // Tipo
        const tipoRaw = String(item.tipo || '').toLowerCase();
        const tipoClass = Utils.getTipoClass(tipoRaw);
        const tipoLabel = tipoRaw ? tipoRaw.charAt(0).toUpperCase() + tipoRaw.slice(1) : '-';

        // Categoria
        let categoria = item.categoria_nome ?? (typeof item.categoria === 'object' ? item.categoria?.nome : item.categoria) ?? '-';
        if (categoria && typeof categoria === 'object') categoria = categoria.nome ?? categoria.label ?? '-';
        categoria = categoria || '-';

        // Conta
        let conta = item.conta_nome ?? (typeof item.conta === 'object' ? item.conta?.nome : item.conta) ?? '-';
        if (conta && typeof conta === 'object') conta = conta.nome ?? conta.label ?? '-';
        conta = conta || '-';

        // Descrição
        let descricao = item.descricao ?? item.descricao_titulo ?? '';
        if (descricao && typeof descricao === 'object') descricao = descricao.texto ?? descricao.value ?? '';
        descricao = String(descricao || '-').trim();

        // Valor
        let valor = parseFloat(item.valor) || 0;
        if (isGroup && item._parcelas) valor = item._parcelas.reduce((s, p) => s + parseFloat(p.valor || 0), 0);

        // Flags
        const isRecorrente = item.recorrente == 1 || item.recorrente === true;
        const isCancelado = !!item.cancelado_em;
        const isPago = Boolean(item.pago);
        const isTransfer = Boolean(item.eh_transferencia);
        const isFuturo = timelineMeta.isFuturo;
        const temLembrete = item.lembrar_antes_segundos > 0;
        const isPagamentoFatura = item.origem_tipo === 'pagamento_fatura';
        const isOverdue = timelineMeta.isOverdue;

        // Card classes
        const cardClasses = ['lk-txn-card'];
        if (isSaldoInicial) cardClasses.push('lk-txn-saldo-inicial');
        if (isGroup) cardClasses.push('lk-txn-group');
        if (isSelected) cardClasses.push('selected');

        // ─── CHECKBOX ─────────────────────────────────────
        const selectHtml = isSelectable
            ? `<div class="lk-txn-select"><input type="checkbox" class="lk-checkbox row-checkbox" ${isSelected ? 'checked' : ''}></div>`
            : '';

        // ─── CATEGORY ICON ────────────────────────────────
        const iconName = getCategoryIcon(categoria);
        const iconHtml = `<div class="lk-txn-icon ${tipoClass}"><i data-lucide="${iconName}"></i></div>`;

        // ─── DESCRIPTION + META ───────────────────────────
        let contentHtml;
        if (isGroup) {
            const totalP = item._totalParcelas || item._parcelas.length;
            const pagasP = item._parcelasPagas ?? item._parcelas.filter(p => p.pago).length;
            const valorP = totalP > 0 ? valor / item._parcelas.length : 0;
            const pctP = totalP > 0 ? Math.round((pagasP / totalP) * 100) : 0;
            contentHtml = `
                <div class="lk-txn-content">
                    <span class="lk-txn-desc">📦 ${Utils.escapeHtml(descricao)}</span>
                    <span class="lk-txn-meta">
                        <span class="lk-txn-category">${Utils.escapeHtml(categoria)}</span>
                        <span class="lk-txn-dot">·</span>
                        <span>${totalP}x de R$ ${valorP.toFixed(2)} · ${pagasP}/${totalP} pagas (${pctP}%)</span>
                    </span>
                    <div class="lk-txn-progress"><div class="lk-txn-progress-bar ${tipoClass}" style="width:${pctP}%"></div></div>
                </div>`;
        } else {
            const paymentMetaHtml = timelineMeta.shouldShowSettlementInline
                ? `
                        <span class="lk-txn-dot">·</span>
                        <span class="lk-txn-meta-payment">${Utils.escapeHtml(timelineMeta.settlementInlineText)}</span>
                    `
                : '';
            contentHtml = `
                <div class="lk-txn-content">
                    <span class="lk-txn-desc">${Utils.escapeHtml(descricao)}</span>
                    <span class="lk-txn-meta">
                        <span class="lk-txn-category">${Utils.escapeHtml(categoria)}</span>
                        <span class="lk-txn-dot">·</span>
                        <span class="lk-txn-date-text">${Utils.escapeHtml(timelineMeta.dataLancamentoComHora)}</span>
                        ${paymentMetaHtml}
                        <span class="lk-txn-badge-tipo ${tipoClass}">${Utils.escapeHtml(tipoLabel)}</span>
                    </span>
                </div>`;
        }

        // ─── SIGNAL TAGS ──────────────────────────────────
        let tagIcons = '';
        if (isCancelado) tagIcons += '<span class="lk-tag-icon lk-tag-canceled" title="Cancelado"><i data-lucide="x-circle"></i></span>';
        else if (isRecorrente) tagIcons += `<span class="lk-tag-icon lk-tag-recorrente" title="Recorrente (${item.recorrencia_freq || ''})"><i data-lucide="refresh-cw"></i></span>`;
        if (temLembrete) tagIcons += '<span class="lk-tag-icon lk-tag-lembrete" title="Lembrete ativo"><i data-lucide="bell"></i></span>';
        if (isFuturo && !isCancelado) tagIcons += '<span class="lk-tag-icon lk-tag-futuro" title="Futuro"><i data-lucide="clock"></i></span>';
        if (isPagamentoFatura) tagIcons += '<span class="lk-tag-icon lk-tag-fatura" title="Pagamento de fatura"><i data-lucide="credit-card"></i></span>';
        const signalsHtml = tagIcons ? `<div class="lk-txn-signals">${tagIcons}</div>` : '';

        // ─── AMOUNT + STATUS ──────────────────────────────
        let statusBadge;
        if (isGroup) {
            const cp = item._parcelas?.[0];
            const cpPago = cp && (cp.pago == 1 || cp.pago === true);
            statusBadge = cpPago
                ? '<span class="lk-pago-badge lk-pago-ok"><i data-lucide="circle-check"></i> Pago</span>'
                : '<span class="lk-pago-badge lk-pago-pendente"><i data-lucide="clock"></i> Pendente</span>';
        } else if (isTransfer) {
            statusBadge = '<span class="lk-pago-badge lk-pago-transfer"><i data-lucide="repeat"></i> Transferência</span>';
        } else if (isOverdue) {
            statusBadge = '<span class="lk-pago-badge lk-pago-atrasado"><i data-lucide="triangle-alert"></i> Atrasado</span>';
        } else if (isPago) {
            statusBadge = '<span class="lk-pago-badge lk-pago-ok"><i data-lucide="circle-check"></i> Pago</span>';
        } else {
            statusBadge = '<span class="lk-pago-badge lk-pago-pendente"><i data-lucide="clock"></i> Pendente</span>';
        }

        const amountHtml = `
            <div class="lk-txn-amount-wrap">
                <span class="lk-txn-amount ${tipoClass}">${Utils.fmtMoney(valor)}</span>
                ${statusBadge}
            </div>`;

        // ─── ACTIONS (3-dot) ──────────────────────────────
        let actionsHtml = '';
        if (!isSaldoInicial) {
            let menuItems = '';
            if (isGroup) {
                const pid = String(id).replace('grupo_', '');
                menuItems = `
                    <button class="lk-dropdown-item toggle-parcelas-menu" data-parcelamento-id="${pid}"><i data-lucide="list"></i> Ver Parcelas</button>
                    <div class="lk-dropdown-divider"></div>
                    <button class="lk-dropdown-item lk-dropdown-danger delete-parcelamento" data-parcelamento-id="${pid}"><i data-lucide="trash-2"></i> Cancelar Parcelamento</button>`;
            } else {
                if (Utils.canEditLancamento(item))
                    menuItems += `<button class="lk-dropdown-item" data-action="edit" data-id="${id}"><i data-lucide="pen"></i> Editar</button>`;
                if (!isCancelado && !isTransfer) {
                    if (!isPago) menuItems += `<button class="lk-dropdown-item lk-dropdown-success" data-action="marcar-pago" data-id="${id}"><i data-lucide="circle-check"></i> Marcar como Pago</button>`;
                    else menuItems += `<button class="lk-dropdown-item lk-dropdown-warning" data-action="desmarcar-pago" data-id="${id}"><i data-lucide="clock"></i> Marcar como Pendente</button>`;
                }
                if (isRecorrente && !isCancelado) {
                    menuItems += '<div class="lk-dropdown-divider"></div>';
                    menuItems += `<button class="lk-dropdown-item lk-dropdown-danger" data-action="cancelar-recorrencia" data-id="${id}"><i data-lucide="x-circle"></i> Cancelar Recorrência</button>`;
                }
                menuItems += '<div class="lk-dropdown-divider"></div>';
                menuItems += `<button class="lk-dropdown-item lk-dropdown-danger" data-action="delete" data-id="${id}"><i data-lucide="trash-2"></i> Excluir</button>`;
            }
            actionsHtml = `
                <div class="lk-txn-actions">
                    <div class="lk-dropdown">
                        <button class="lk-dropdown-trigger" title="Mais ações" aria-label="Mais ações"><i data-lucide="more-vertical"></i></button>
                        <div class="lk-dropdown-menu">${menuItems}</div>
                    </div>
                </div>`;
        }

        // ─── EXPAND BUTTON ────────────────────────────────
        let expandBtnHtml = '';
        if (!isSaldoInicial) {
            if (isGroup) {
                expandBtnHtml = `<button class="lk-txn-expand-btn toggle-parcelas" data-parcelamento-id="${String(id).replace('grupo_', '')}" title="Ver parcelas"><i data-lucide="layers"></i><span>Ver parcelas</span></button>`;
            } else {
                expandBtnHtml = `<button class="lk-txn-expand-btn" data-action="expand" data-id="${id}" title="Ver detalhes" aria-expanded="false"><i data-lucide="chevron-down"></i><span>Detalhes</span></button>`;
            }
        }

        // ─── DETAIL PANEL ─────────────────────────────────
        let detailHtml = '';
        if (!isSaldoInicial && !isGroup) {
            const cartaoNome = item.cartao_nome || '';
            const cartaoBandeira = item.cartao_bandeira || '';
            const cartaoDisplay = cartaoNome ? `${cartaoNome}${cartaoBandeira ? ` (${cartaoBandeira})` : ''}` : '';
            const formaPgto = Utils.formatFormaPagamento(item.forma_pagamento);

            let chips = '';
            chips += `<div class="lk-detail-chip lk-chip-date"><i data-lucide="calendar-days"></i><span class="lk-detail-label">${Utils.escapeHtml(timelineMeta.labelPrimaria)}</span><span class="lk-detail-value">${Utils.escapeHtml(timelineMeta.dataLancamentoComHora)}</span></div>`;
            if (timelineMeta.hasSettlementDate) chips += `<div class="lk-detail-chip lk-chip-paid-date"><i data-lucide="badge-check"></i><span class="lk-detail-label">${Utils.escapeHtml(timelineMeta.labelLiquidacao)}</span><span class="lk-detail-value">${Utils.escapeHtml(timelineMeta.dataPagamento)}</span></div>`;
            chips += `<div class="lk-detail-chip"><i data-lucide="wallet"></i><span class="lk-detail-label">Conta</span><span class="lk-detail-value">${Utils.escapeHtml(conta)}</span></div>`;
            if (formaPgto && formaPgto !== '-') chips += `<div class="lk-detail-chip"><i data-lucide="banknote"></i><span class="lk-detail-label">Pagamento</span><span class="lk-detail-value">${formaPgto}</span></div>`;
            if (cartaoDisplay) chips += `<div class="lk-detail-chip"><i data-lucide="credit-card"></i><span class="lk-detail-label">Cartão</span><span class="lk-detail-value">${Utils.escapeHtml(cartaoDisplay)}</span></div>`;
            if (isRecorrente && !isCancelado) {
                const freq = item.recorrencia_freq || 'mensal';
                let recInfo = freq.charAt(0).toUpperCase() + freq.slice(1);
                if (item.recorrencia_fim) recInfo += ` · até ${Utils.fmtDate(item.recorrencia_fim)}`;
                else if (item.recorrencia_total) recInfo += ` · ${item.recorrencia_total}x`;
                else recInfo += ' · sem fim';
                chips += `<div class="lk-detail-chip lk-chip-recorrente"><i data-lucide="refresh-cw"></i><span class="lk-detail-label">Recorrência</span><span class="lk-detail-value">${recInfo}</span></div>`;
            }
            if (isCancelado) chips += `<div class="lk-detail-chip lk-chip-cancelado"><i data-lucide="x-circle"></i><span class="lk-detail-label">Cancelado</span><span class="lk-detail-value">${Utils.fmtDate(item.cancelado_em)}</span></div>`;
            if (temLembrete) {
                const segs = parseInt(item.lembrar_antes_segundos);
                let t = '';
                if (segs >= 604800) t = Math.round(segs / 604800) + ' semana(s) antes';
                else if (segs >= 86400) t = Math.round(segs / 86400) + ' dia(s) antes';
                else if (segs >= 3600) t = Math.round(segs / 3600) + ' hora(s) antes';
                else t = Math.round(segs / 60) + ' min antes';
                let canais = [];
                if (item.canal_inapp) canais.push('App');
                if (item.canal_email) canais.push('E-mail');
                chips += `<div class="lk-detail-chip lk-chip-lembrete"><i data-lucide="bell"></i><span class="lk-detail-label">Lembrete</span><span class="lk-detail-value">${t} · ${canais.join(', ') || 'Nenhum canal'}</span></div>`;
            }
            if (item.parcela_atual && item.total_parcelas) chips += `<div class="lk-detail-chip"><i data-lucide="layers"></i><span class="lk-detail-label">Parcela</span><span class="lk-detail-value">${item.parcela_atual}/${item.total_parcelas}</span></div>`;
            detailHtml = `
                <div class="lk-txn-detail-panel" data-detail-for="${id}" aria-hidden="true">
                    <div class="lk-txn-detail-panel-inner">
                        <div class="lk-detail-chips">${chips}</div>
                    </div>
                </div>`;
        }

        // ─── ASSEMBLE CARD ────────────────────────────────
        return `
            <article class="${cardClasses.join(' ')}" data-id="${id}" data-tipo="${tipoRaw}" role="listitem">
                <div class="lk-txn-stripe ${tipoClass}"></div>
                <div class="lk-txn-body">
                    ${selectHtml}
                    ${iconHtml}
                    ${contentHtml}
                    ${signalsHtml}
                    ${amountHtml}
                    ${actionsHtml}
                </div>
                ${expandBtnHtml}
                ${detailHtml}
            </article>`;
    },

    goToPage(page) {
        const totalPages = Math.max(1, Math.ceil(STATE.filteredData.length / STATE.pageSize));
        const safePage = Math.min(Math.max(1, page), totalPages);
        if (safePage !== STATE.currentPage) {
            STATE.currentPage = safePage;
            this.render();
        }
    },

    updatePagination() {
        const total = STATE.filteredData.length;
        const totalPages = Math.max(1, Math.ceil(total / STATE.pageSize));
        const start = total > 0 ? (STATE.currentPage - 1) * STATE.pageSize + 1 : 0;
        const end = Math.min(STATE.currentPage * STATE.pageSize, total);

        if (DOM.paginationInfo) {
            DOM.paginationInfo.textContent = total === 0 ? '0 lançamentos' : `${start}-${end} de ${total} lançamentos`;
        }
        if (DOM.prevPage) DOM.prevPage.disabled = STATE.currentPage <= 1;
        if (DOM.nextPage) DOM.nextPage.disabled = STATE.currentPage >= totalPages;

        if (DOM.pageNumbers) {
            const pages = [];
            const maxVisible = 5;
            let startPage = Math.max(1, STATE.currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);
            if (endPage - startPage + 1 < maxVisible) startPage = Math.max(1, endPage - maxVisible + 1);
            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === STATE.currentPage;
                pages.push(`<button type="button" class="page-number-btn ${isActive ? 'active' : ''}" data-page="${i}" ${isActive ? 'disabled' : ''}>${i}</button>`);
            }
            DOM.pageNumbers.innerHTML = pages.join('');
            DOM.pageNumbers.querySelectorAll('.page-number-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const p = parseInt(btn.dataset.page);
                    if (p) this.goToPage(p);
                });
            });
        }
    },

    updateSelectionInfo() {
        const count = STATE.selectedIds.size;
        if (DOM.selCountSpan) DOM.selCountSpan.textContent = String(count);
        if (DOM.btnExcluirSel) DOM.btnExcluirSel.toggleAttribute('disabled', count === 0);
        if (DOM.btnEditarSel) {
            DOM.btnEditarSel.toggleAttribute('disabled', count !== 1);
            DOM.btnEditarSel.title = count === 1 ? 'Editar lancamento selecionado' : 'Selecione apenas 1 lancamento para editar';
        }
        if (DOM.selectionBulkBar) DOM.selectionBulkBar.hidden = count === 0;
        if (DOM.selectionBulkCount) DOM.selectionBulkCount.textContent = `${count} selecionado${count === 1 ? '' : 's'}`;
        if (DOM.selectionBulkText) {
            DOM.selectionBulkText.textContent = count === 1 ? 'Edite ou exclua o lancamento selecionado.'
                : count > 1 ? 'Exclusao em massa disponivel. Para editar, selecione apenas 1 item.'
                    : 'Acoes rapidas para os itens selecionados nesta pagina.';
        }
        if (DOM.selectionScopeHint) {
            DOM.selectionScopeHint.textContent = count > 0
                ? `${count} item${count > 1 ? 's' : ''} selecionado${count > 1 ? 's' : ''} nesta pagina.`
                : 'A selecao em massa vale apenas para a pagina atual.';
        }
        if (DOM.selectAllCheckbox && DOM.tableBody) {
            const checkboxes = DOM.tableBody.querySelectorAll('.row-checkbox');
            const checkedCount = DOM.tableBody.querySelectorAll('.row-checkbox:checked').length;
            const totalSelectable = checkboxes.length;
            if (totalSelectable === 0 || checkedCount === 0) {
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

    handleTableClick(e) {
        // Dropdown toggle
        const dropdownTrigger = e.target.closest('.lk-dropdown-trigger');
        if (dropdownTrigger) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = dropdownTrigger.closest('.lk-dropdown');
            const menu = resolveDropdownMenu(dropdown);
            if (!menu) return;
            const card = dropdownTrigger.closest('.lk-txn-card');
            const opened = toggleDropdownMenu({
                trigger: dropdownTrigger,
                dropdown,
                menu,
                card,
                onItemClick: (itemBtn) => this.handleActionButton(itemBtn)
            });
            if (!opened) return;
            if (window.lucide) lucide.createIcons();
            return;
        }

        const dropdownItem = e.target.closest('.lk-dropdown-item');
        if (dropdownItem) {
            closeDropdownMenu(dropdownItem.closest('.lk-dropdown-menu'));
        }

        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        this.handleActionButton(btn);
    },

    toggleDetailPanel(id, btn) {
        const panel = DOM.tableBody.querySelector(`.lk-txn-detail-panel[data-detail-for="${id}"]`);
        if (!panel) return;
        const card = btn.closest('.lk-txn-card');
        const isOpen = panel.classList.contains('is-open');

        panel.classList.toggle('is-open', !isOpen);
        panel.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        btn.classList.toggle('expanded', !isOpen);
        btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        card?.classList.toggle('details-open', !isOpen);

        if (!isOpen) {
            if (window.LK?.refreshIcons) LK.refreshIcons();
            else if (typeof lucide !== 'undefined') lucide.createIcons({ nodes: [panel] });
        }
    },

    handleCheckboxChange(e) {
        const checkbox = e.target.closest('.row-checkbox');
        if (!checkbox) return;
        const card = checkbox.closest('.lk-txn-card');
        const id = card?.dataset.id;
        if (!id) return;
        if (checkbox.checked) { STATE.selectedIds.add(id); card.classList.add('selected'); }
        else { STATE.selectedIds.delete(id); card.classList.remove('selected'); }
        this.updateSelectionInfo();
    },

    renderRows(items, options = {}) { this.setData(items, options); this.render(); },
    getSelectedIds() { return Array.from(STATE.selectedIds); },

    editSelected() {
        const ids = this.getSelectedIds();
        if (ids.length !== 1) return;
        const item = STATE.filteredData.find(e => String(e.id) === String(ids[0]));
        if (!item || !Utils.canEditLancamento(item)) return;
        handleEdit(item);
    },

    clearSelection() {
        STATE.selectedIds.clear();
        if (DOM.selectAllCheckbox) { DOM.selectAllCheckbox.checked = false; DOM.selectAllCheckbox.indeterminate = false; }
        const cbs = DOM.tableBody?.querySelectorAll('.row-checkbox') || [];
        cbs.forEach(cb => cb.checked = false);
        const cards = DOM.tableBody?.querySelectorAll('.selected') || [];
        cards.forEach(c => c.classList.remove('selected'));
        this.updateSelectionInfo();
    }
};

// Register in shared Modules registry for cross-module access
Modules.TableManager = TableManager;
