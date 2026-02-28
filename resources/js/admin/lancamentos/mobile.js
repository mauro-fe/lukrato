/**
 * ============================================================================
 * LUKRATO — Lançamentos / MobileCards
 * ============================================================================
 * Card-based layout that replaces Tabulator on mobile viewports.
 * Handles paging, sorting, and inline actions for each lançamento card.
 * ============================================================================
 */

import { CONFIG, DOM, STATE, Utils, Notifications, Modules } from './state.js';

// ─── MobileCards ─────────────────────────────────────────────────────────────

const MobileCards = {
    cache: [],
    pageSize: 8,
    currentPage: 1,
    sortField: 'data',
    sortDir: 'desc',

    setItems(items) {
        this.cache = Array.isArray(items) ? items : [];
        this.currentPage = 1;
        this.renderPage();
    },

    getPagedData() {
        // Remove saldo inicial dos cards
        const base = this.cache.filter(item => !Utils.isSaldoInicial(item));

        // Ordenação
        let data = [...base];
        if (this.sortField === 'data') {
            data.sort((a, b) => {
                const da = Utils.extractYMD(a.data || a.created_at) || {};
                const db = Utils.extractYMD(b.data || b.created_at) || {};

                const ka = (da.year || 0) * 10000 + (da.month || 0) * 100 + (da.day || 0);
                const kb = (db.year || 0) * 10000 + (db.month || 0) * 100 + (db.day || 0);

                return this.sortDir === 'asc' ? (ka - kb) : (kb - ka);
            });
        } else if (this.sortField === 'tipo') {
            data.sort((a, b) => {
                const ta = (a.tipo || '').toString().toLowerCase();
                const tb = (b.tipo || '').toString().toLowerCase();

                if (this.sortDir === 'asc') {
                    return ta.localeCompare(tb);
                } else {
                    return tb.localeCompare(ta);
                }
            });
        } else if (this.sortField === 'valor') {
            data.sort((a, b) => {
                const va = Number(a.valor || 0);
                const vb = Number(b.valor || 0);
                return this.sortDir === 'asc' ? (va - vb) : (vb - va);
            });
        }


        const total = data.length;
        const totalPages = Math.max(1, Math.ceil(total / this.pageSize));
        const page = Math.min(this.currentPage, totalPages);

        const start = (page - 1) * this.pageSize;
        const end = start + this.pageSize;

        return {
            list: data.slice(start, end),
            page,
            totalPages,
            total
        };
    },


    renderPage() {
        if (!DOM.lanCards) return;

        const { list, total, page, totalPages } = this.getPagedData();

        if (!total) {
            DOM.lanCards.innerHTML = `
                <div class="lan-cards-header cards-header">
                    <span>Data</span>
                    <span>Tipo</span>
                    <span>Valor</span>
                    <span>Ações</span>
                </div>
                <div class="lan-card card-item" style="border-radius:0 0 16px 16px;">
                    <div class="empty-state" style="grid-column:1/-1;padding:2rem 1rem;text-align:center;">
                        <div class="empty-icon" style="width:100px;height:100px;margin:0 auto 1rem;background:var(--color-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="arrow-left-right" style="font-size:2.5rem;color:white;"></i>
                        </div>
                        <h3 style="color:var(--color-text);margin-bottom:0.5rem;font-size:1.25rem;font-weight:600;">Nenhum lançamento encontrado</h3>
                        <p style="color:var(--color-text-muted);margin-bottom:1.25rem;font-size:0.9rem;">Comece criando seu primeiro lançamento para gerenciar suas finanças</p>
                        <button type="button" class="btn btn-primary btn-lg" onclick="lancamentoGlobalManager.openModal()" style="background:var(--color-primary);border:none;padding:0.65rem 1.25rem;font-size:0.95rem;border-radius:var(--radius-md);color:white;font-weight:500;">
                            <i data-lucide="plus"></i> Criar primeiro lançamento
                        </button>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            this.updatePager(0, 1, 1);
            this.updateSortIndicators();
            return;
        }

        const parts = [];
        const isXs = window.matchMedia('(max-width: 414px)').matches;

        // Cabeçalho
        parts.push(`
         <div class="lan-cards-header cards-header">
                 <button type="button" class="lan-cards-header-btn cards-header-btn" data-sort="data">
                  <span>Data</span>
                  <span class="lan-sort-indicator sort-indicator" data-field="data"></span>
                 </button>
                <button type="button" class="lan-cards-header-btn cards-header-btn" data-sort="tipo">
                     <span>Tipo</span>
                    <span class="lan-sort-indicator sort-indicator" data-field="tipo"></span>
                </button>
                <button type="button" class="lan-cards-header-btn cards-header-btn" data-sort="valor">
                     <span>Valor</span>
                    <span class="lan-sort-indicator sort-indicator" data-field="valor"></span>
                </button>
             </div>
`);


        for (const item of list) {
            const id = item.id;
            const tipoRaw = String(item.tipo || '').toLowerCase();
            const tipoClass = Utils.getTipoClass(tipoRaw);
            const tipoLabel = tipoRaw
                ? tipoRaw.charAt(0).toUpperCase() + tipoRaw.slice(1)
                : '-';

            const valorFmt = Utils.fmtMoney(item.valor);
            const dataFmt = Utils.fmtDate(item.data || item.created_at);

            const categoria =
                item.categoria_nome ??
                (typeof item.categoria === 'object'
                    ? item.categoria?.nome
                    : item.categoria) ??
                '-';

            const conta =
                item.conta_nome ??
                (typeof item.conta === 'object'
                    ? item.conta?.nome
                    : item.conta) ??
                '-';

            const descRaw =
                item.descricao ??
                item.descricao_titulo ??
                (typeof item.descricao === 'object'
                    ? item.descricao?.texto
                    : '') ??
                '';
            const descricao = descRaw || '--';

            // Cartão de crédito (igual ao desktop)
            const cartaoNome = item.cartao_nome || '';
            const cartaoBandeira = item.cartao_bandeira || '';
            const cartaoDisplay = cartaoNome ? `${cartaoNome}${cartaoBandeira ? ` (${cartaoBandeira})` : ''}` : '-';

            // Forma de Pagamento
            const formaPgto = Utils.formatFormaPagamento(item.forma_pagamento);

            // Status (Pago / Pendente / Transferência)
            const isPago = Boolean(item.pago);
            const isTransfer = Boolean(item.eh_transferencia);
            let statusClass = '';
            let statusLabel = '';
            let statusLucideIcon = '';
            if (isTransfer) {
                statusClass = 'status-transferencia';
                statusLabel = 'Transferência';
                statusLucideIcon = 'repeat';
            } else if (isPago) {
                statusClass = 'status-pago';
                statusLabel = 'Pago';
                statusLucideIcon = 'circle-check';
            } else {
                statusClass = 'status-pendente';
                statusLabel = 'Pendente';
                statusLucideIcon = 'clock';
            }

            // Badges de recorrência/status para card view
            const isRecorrente = item.recorrente == 1 || item.recorrente === true;
            const isCancelado = !!item.cancelado_em;
            const dataLanc = new Date(item.data || item.created_at);
            const hojeCard = new Date();
            hojeCard.setHours(0, 0, 0, 0);
            const isFuturo = !isPago && dataLanc > hojeCard;
            let cardBadges = '';
            if (isCancelado) {
                cardBadges += ' <span class="badge bg-secondary" style="font-size:0.6rem;">Cancelado</span>';
            } else if (isRecorrente) {
                cardBadges += ` <span class="badge bg-info" style="font-size:0.6rem;">${item.recorrencia_freq || 'Recorrente'}</span>`;
            }
            if (item.canal_email) {
                cardBadges += ' <span class="badge bg-primary" style="font-size:0.6rem;">✉️ E-mail</span>';
            }
            if (item.canal_inapp) {
                cardBadges += ' <span class="badge bg-secondary" style="font-size:0.6rem;">🔔 InApp</span>';
            }
            if (isFuturo && !isCancelado) {
                cardBadges += ' <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Futuro</span>';
            }

            // Pagamento de fatura badge para mobile
            const isPagFaturaMobile = item.origem_tipo === 'pagamento_fatura';
            if (isPagFaturaMobile) {
                cardBadges += ' <span class="badge" style="font-size:0.6rem;background:#7c3aed;color:white;">💳 Fatura</span>';
            }

            // Botões de ação para desktop/tablet
            const actionsHtml = `
                ${Utils.canEditLancamento(item)
                    ? `<button class="lk-btn ghost lan-card-btn" data-action="edit" data-id="${id}" title="Editar lançamento">
                           <i data-lucide="pen"></i>
                       </button>`
                    : ''
                }
                ${!Utils.isSaldoInicial(item)
                    ? `<button class="lk-btn danger lan-card-btn" data-action="delete" data-id="${id}" title="Excluir lançamento">
                           <i data-lucide="trash-2"></i>
                       </button>`
                    : ''
                }
            `.trim();

            // Para mobile pequeno, sempre gera os botões (com ou sem permissões para garantir que apareçam)
            let mobileActionsHtml = '';
            if (isXs) {
                const canEdit = Utils.canEditLancamento(item);
                const canDelete = !Utils.isSaldoInicial(item);

                const buttonStyle = 'display: flex !important; visibility: visible !important; opacity: 1 !important; width: 30px !important; height: 36px !important; min-width: 30px !important; min-height: 36px !important; border-radius: 10px !important; padding: 0 !important; margin: 0 2px !important; align-items: center !important; justify-content: center !important; flex: 0 0 auto !important; position: relative !important; z-index: 999 !important;';

                // Se tiver ao menos uma permissão, mostra os botões permitidos
                if (canEdit || canDelete) {
                    mobileActionsHtml = `
                        ${canEdit ? `<button class="lk-btn ghost lan-card-btn" data-action="edit" data-id="${id}" title="Editar lançamento" style="${buttonStyle} background: rgba(230, 126, 34, 0.3) !important; color: #e67e22 !important; border: 1px solid #e67e22 !important;">
                               <i data-lucide="pen" style="font-size: 0.75rem; color: #e67e22;"></i>
                           </button>` : ''}
                        ${canDelete ? `<button class="lk-btn danger lan-card-btn" data-action="delete" data-id="${id}" title="Excluir lançamento" style="${buttonStyle} background: rgba(231, 76, 60, 0.3) !important; color: #e74c3c !important; border: 1px solid #e74c3c !important;">
                               <i data-lucide="trash-2" style="font-size: 0.75rem; color: #e74c3c;"></i>
                           </button>` : ''}`.trim();
                } else {
                    // Fallback: sempre mostra os botões em telas pequenas
                    mobileActionsHtml = `<button class="lk-btn ghost lan-card-btn" data-action="edit" data-id="${id}" title="Editar lançamento" style="${buttonStyle} background: rgba(230, 126, 34, 0.3) !important; color: #e67e22 !important; border: 1px solid #e67e22 !important;">
                               <i data-lucide="pen" style="font-size: 0.75rem; color: #e67e22;"></i>
                           </button>
                           <button class="lk-btn danger lan-card-btn" data-action="delete" data-id="${id}" title="Excluir lançamento" style="${buttonStyle} background: rgba(231, 76, 60, 0.3) !important; color: #e74c3c !important; border: 1px solid #e74c3c !important;">
                               <i data-lucide="trash-2" style="font-size: 0.75rem; color: #e74c3c;"></i>
                           </button>`.trim();
                }
            }

            parts.push(`
                <article class="lan-card card-item" data-id="${id}" aria-expanded="false">
                    <div class="lan-card-main card-main">
                        <span class="lan-card-date card-date">${Utils.escapeHtml(dataFmt)}</span>
                        <span class="lan-card-type card-type">
                            <span class="badge-tipo ${tipoClass}">
                                ${Utils.escapeHtml(tipoLabel)}
                            </span>
                        </span>
                        <span class="lan-card-value card-value ${tipoClass}">
                            ${Utils.escapeHtml(valorFmt)}
                        </span>
                    </div>

                    <button class="lan-card-toggle card-toggle" type="button" data-toggle="details" aria-label="Ver detalhes do lançamento">
                        <span class="lan-card-toggle-icon card-toggle-icon"><i data-lucide="chevron-right"></i></span>
                        <span class="detalhes"> Ver detalhes</span>
                    </button>

                    <div class="lan-card-details card-details">
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Categoria</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(categoria || '-')}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Conta</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(conta || '-')}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Cartão</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(cartaoDisplay)}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Forma Pgto</span>
                            <span class="lan-card-detail-value card-detail-value">${formaPgto}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Descrição</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(descricao)}${cardBadges}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Status</span>
                            <span class="lan-card-detail-value card-detail-value">
                                <span class="badge-status ${statusClass}"><i data-lucide="${statusLucideIcon}"></i> ${statusLabel}</span>
                            </span>
                        </div>
                        ${isPagFaturaMobile ? `
                        <div class="lan-card-detail-row card-detail-row" style="justify-content:center;">
                            <button class="btn btn-sm toggle-fatura-detalhes-mobile" data-lancamento-id="${id}" 
                                    style="background:#7c3aed;color:white;font-size:0.75rem;border:none;border-radius:6px;padding:4px 12px;">
                                <i data-lucide="credit-card" style="width:12px;height:12px;"></i> Ver composição da fatura
                            </button>
                        </div>
                        ` : ''}
                        <div class="lan-card-detail-row card-detail-row actions-row" style="display: flex !important;">
                            <span class="lan-card-detail-label card-detail-label">AÇÕES</span>
                            <span class="lan-card-detail-value card-detail-value actions-slot" style="display: flex !important; gap: 8px;">
                                ${actionsHtml || '<span style="color: var(--text-secondary); font-size: 0.75rem;">Nenhuma ação disponível</span>'}
                            </span>
                        </div>
                    </div>
                </article>
            `);
        }

        DOM.lanCards.innerHTML = parts.join('');
        if (window.lucide) lucide.createIcons();

        // Debug: verificar se os botões foram inseridos no DOM
        if (isXs) {
            const actionsRows = document.querySelectorAll('.actions-row');
            actionsRows.forEach((row, i) => {
                const buttons = row.querySelectorAll('.lk-btn');
            });
        }
        this.updatePager(total, page, totalPages);
        this.updateSortIndicators();
    },

    updatePager(total, page, totalPages) {
        if (!DOM.lanPager || !DOM.lanPagerInfo) return;

        // se não tiver dados
        if (!total) {
            DOM.lanPagerInfo.textContent = 'Nenhum lançamento';
            if (DOM.lanPagerFirst) DOM.lanPagerFirst.disabled = true;
            if (DOM.lanPagerPrev) DOM.lanPagerPrev.disabled = true;
            if (DOM.lanPagerNext) DOM.lanPagerNext.disabled = true;
            if (DOM.lanPagerLast) DOM.lanPagerLast.disabled = true;
            return;
        }

        DOM.lanPagerInfo.textContent = `Página ${page} de ${totalPages}`;

        if (DOM.lanPagerFirst) {
            DOM.lanPagerFirst.disabled = page <= 1;
        }
        if (DOM.lanPagerPrev) {
            DOM.lanPagerPrev.disabled = page <= 1;
        }
        if (DOM.lanPagerNext) {
            DOM.lanPagerNext.disabled = page >= totalPages;
        }
        if (DOM.lanPagerLast) {
            DOM.lanPagerLast.disabled = page >= totalPages;
        }
    },

    goToPage(page) {
        const data = this.cache.filter(item => !Utils.isSaldoInicial(item));
        const totalPages = Math.max(1, Math.ceil(data.length / this.pageSize));

        const safePage = Math.min(Math.max(1, page), totalPages);
        if (safePage === this.currentPage) return;

        this.currentPage = safePage;
        this.renderPage();
    },

    nextPage() {
        this.goToPage(this.currentPage + 1);
    },

    prevPage() {
        this.goToPage(this.currentPage - 1);
    },

    firstPage() {
        this.goToPage(1);
    },

    lastPage() {
        const data = this.cache.filter(item => !Utils.isSaldoInicial(item));
        const totalPages = Math.max(1, Math.ceil(data.length / this.pageSize));
        this.goToPage(totalPages);
    },

    setSort(field) {
        if (!field) return;

        if (this.sortField === field) {
            // Só alterna asc/desc
            this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortField = field;
            // Padrão: data e valor em desc
            this.sortDir = 'desc';
        }

        this.currentPage = 1;
        this.renderPage();
    },

    updateSortIndicators() {
        // Depois de renderizar o HTML, atualiza ▼ ▲ nos títulos
        const indicators = DOM.lanCards?.querySelectorAll('.lan-sort-indicator sort-indicator') || [];
        indicators.forEach(el => {
            const field = el.dataset.field;
            if (!field || field !== this.sortField) {
                el.textContent = '';
                return;
            }
            el.textContent = this.sortDir === 'asc' ? '\u2191' : '\u2193';
        });
    },

    handleClick(ev) {
        const target = ev.target;

        // Clique nos títulos de ordenação (Data / Valor)
        const sortBtn = target.closest('[data-sort]');
        if (sortBtn) {
            const field = sortBtn.dataset.sort;
            if (field) {
                MobileCards.setSort(field);
            }
            return;
        }

        // Toggle de detalhes
        const toggleBtn = target.closest('[data-toggle="details"]');
        if (toggleBtn) {
            const card = toggleBtn.closest('.lan-card');
            if (card) {
                const isExpanded = card.getAttribute('aria-expanded') === 'true';
                card.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            }
            return;
        }

        // Botões Editar / Excluir
        const actionBtn = target.closest('.lan-card-btn');
        if (!actionBtn) return;

        const action = actionBtn.dataset.action;
        const id = Number(actionBtn.dataset.id);
        if (!id) return;

        const item = MobileCards.cache.find(l => Number(l.id) === id);
        if (!item) return;


        if (action === 'edit') {
            if (!Utils.canEditLancamento(item)) return;
            Modules.ModalManager.openEditLancamento(item);
            return;
        }

        if (action === 'delete') {
            if (Utils.isSaldoInicial(item)) return;
            (async () => {
                let scope = 'single';
                const isRecorrente = item.recorrente && item.recorrencia_pai_id;
                const isParcelamento = !!item.parcelamento_id;

                if (isRecorrente || isParcelamento) {
                    const tipoLabel = isRecorrente ? 'recorrência' : 'parcelamento';
                    const result = await Swal.fire({
                        title: 'Excluir lançamento',
                        html: `<p>Este lançamento faz parte de uma <strong>${tipoLabel}</strong>. O que deseja fazer?</p>`,
                        icon: 'question',
                        input: 'radio',
                        inputOptions: {
                            'single': 'Apenas este lançamento',
                            'future': 'Este e todos os futuros não pagos',
                            'all': `Toda a ${tipoLabel}`
                        },
                        inputValue: 'single',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Excluir',
                        cancelButtonText: 'Cancelar',
                        inputValidator: (value) => !value ? 'Selecione uma opção' : undefined
                    });
                    if (!result.isConfirmed) return;
                    scope = result.value;
                } else {
                    const ok = await Notifications.ask(
                        'Excluir lançamento?',
                        'Esta ação não pode ser desfeita.'
                    );
                    if (!ok) return;
                }

                actionBtn.disabled = true;
                const okDel = await Modules.API.deleteOne(id, scope);
                actionBtn.disabled = false;

                if (okDel) {
                    const msgs = {
                        single: 'Lançamento excluído com sucesso!',
                        future: 'Lançamentos futuros excluídos!',
                        all: 'Toda a série excluída!'
                    };
                    Notifications.toast(msgs[scope] || 'Excluído!');
                    await Modules.DataManager.load();
                } else {
                    Notifications.toast('Falha ao excluir lançamento.', 'error');
                }
            })();
        }

        if (action === 'marcar-pago') {
            (async () => {
                const ok = await Notifications.ask(
                    'Marcar como pago?',
                    'Este lançamento será marcado como pago.'
                );
                if (!ok) return;

                actionBtn.disabled = true;
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    const response = await fetch(`${CONFIG.BASE_URL}api/lancamentos/${id}/pagar`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        }
                    });
                    if (response.ok) {
                        Notifications.toast('Lançamento marcado como pago!');
                        await Modules.DataManager.load();
                    } else {
                        const err = await response.json();
                        Notifications.toast(err.message || 'Erro ao marcar como pago.', 'error');
                    }
                } catch (error) {
                    Notifications.toast('Erro ao marcar como pago.', 'error');
                }
                actionBtn.disabled = false;
            })();
        }

        if (action === 'cancelar-recorrencia') {
            (async () => {
                const ok = await Notifications.ask(
                    'Cancelar recorrência?',
                    'Todos os lançamentos futuros não pagos desta série serão cancelados.'
                );
                if (!ok) return;

                actionBtn.disabled = true;
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    const response = await fetch(`${CONFIG.BASE_URL}api/lancamentos/${id}/cancelar-recorrencia`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        }
                    });
                    if (response.ok) {
                        Notifications.toast('Recorrência cancelada com sucesso!');
                        await Modules.DataManager.load();
                    } else {
                        const err = await response.json();
                        Notifications.toast(err.message || 'Erro ao cancelar recorrência.', 'error');
                    }
                } catch (error) {
                    Notifications.toast('Erro ao cancelar recorrência.', 'error');
                }
                actionBtn.disabled = false;
            })();
        }
    }
};

// ─── Register in Modules ─────────────────────────────────────────────────────

Modules.MobileCards = MobileCards;

export { MobileCards };
