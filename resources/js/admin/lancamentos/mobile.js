/**
 * ============================================================================
 * LUKRATO — Lançamentos / MobileCards
 * ============================================================================
 * Card-based layout that replaces Tabulator on mobile viewports.
 * Handles paging, sorting, and inline actions for each lançamento card.
 * ============================================================================
 */

import { CONFIG, DOM, STATE, Utils, Notifications, Modules } from './state.js';
import { handleMarcarPago, handleDesmarcarPago, handleCancelarRecorrencia, handleDelete, handleEdit } from './actions.js';

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
            const isGroup = Boolean(item._isParcelamentoGroup);
            const groupParcelas = isGroup && Array.isArray(item._parcelas) ? item._parcelas : [];
            const valorBase = isGroup
                ? groupParcelas.reduce((sum, p) => sum + (Number(p?.valor || 0)), 0)
                : Number(item.valor || 0);
            const tipoRaw = String(item.tipo || '').toLowerCase();
            const tipoClass = Utils.getTipoClass(tipoRaw);
            const tipoLabel = tipoRaw
                ? tipoRaw.charAt(0).toUpperCase() + tipoRaw.slice(1)
                : '-';

            const valorFmt = Utils.fmtMoney(valorBase);
            const dataFmt = Utils.fmtDate(item.data || item.created_at);
            const horaLanc = item.hora_lancamento || '';
            const dataMain = horaLanc ? `${dataFmt} • ${horaLanc}` : dataFmt;
            const dataPagamentoFmt = Utils.fmtDate(item.data_pagamento);

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
            const isPagamentoFatura = item.origem_tipo === 'pagamento_fatura';
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

            let pagoEmClass = '';
            let pagoEmLabel = '';
            let pagoEmLucideIcon = '';
            if (isTransfer) {
                pagoEmClass = 'status-transferencia';
                pagoEmLabel = 'Transf.';
                pagoEmLucideIcon = 'repeat';
            } else if (isPago && dataPagamentoFmt && dataPagamentoFmt !== '-') {
                pagoEmClass = 'status-pago';
                pagoEmLabel = dataPagamentoFmt;
                pagoEmLucideIcon = 'circle-check';
            } else if (isPago) {
                pagoEmClass = 'status-pago';
                pagoEmLabel = 'Pago';
                pagoEmLucideIcon = 'circle-check';
            } else {
                pagoEmClass = 'status-pendente';
                pagoEmLabel = 'Pendente';
                pagoEmLucideIcon = 'clock';
            }

            // Badges de recorrência/status para card view
            const isRecorrente = item.recorrente == 1 || item.recorrente === true;
            const isCancelado = !!item.cancelado_em;
            const dataLanc = new Date(item.data || item.created_at);
            const hojeCard = new Date();
            hojeCard.setHours(0, 0, 0, 0);
            const isFuturo = !isPago && dataLanc > hojeCard;
            const temLembrete = Number(item.lembrar_antes_segundos || 0) > 0;
            let cardBadges = '';
            if (isCancelado) {
                cardBadges += ' <span class="badge bg-secondary" style="font-size:0.6rem;">Cancelado</span>';
            } else if (isRecorrente) {
                cardBadges += ` <span class="badge bg-info" style="font-size:0.6rem;">${item.recorrencia_freq || 'Recorrente'}</span>`;
            }
            if (temLembrete) {
                cardBadges += ' <span class="badge bg-primary" style="font-size:0.6rem;">🔔 Lembrete</span>';
            }
            if (isFuturo && !isCancelado) {
                cardBadges += ' <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Futuro</span>';
            }

            // Pagamento de fatura badge para mobile
            if (isPagamentoFatura) {
                cardBadges += ' <span class="badge" style="font-size:0.6rem;background:#7c3aed;color:white;">💳 Fatura</span>';
            }
            // Parcela badge para mobile
            if (item.parcela_atual && item.total_parcelas) {
                cardBadges += ` <span class="badge bg-dark" style="font-size:0.6rem;">📦 ${item.parcela_atual}/${item.total_parcelas}</span>`;
            }

            let lembreteInfo = '-';
            if (temLembrete) {
                const segs = parseInt(item.lembrar_antes_segundos, 10);
                let tempoLabel = '';
                if (segs >= 604800) tempoLabel = Math.round(segs / 604800) + ' semana(s) antes';
                else if (segs >= 86400) tempoLabel = Math.round(segs / 86400) + ' dia(s) antes';
                else if (segs >= 3600) tempoLabel = Math.round(segs / 3600) + ' hora(s) antes';
                else tempoLabel = Math.round(segs / 60) + ' min antes';
                const canais = [];
                if (item.canal_inapp) canais.push('App');
                if (item.canal_email) canais.push('E-mail');
                lembreteInfo = `${tempoLabel} · ${canais.join(', ') || 'Nenhum canal'}`;
            }

            let recorrenciaInfo = '-';
            if (isRecorrente && !isCancelado) {
                const freq = item.recorrencia_freq || 'mensal';
                recorrenciaInfo = freq.charAt(0).toUpperCase() + freq.slice(1);
                if (item.recorrencia_fim) recorrenciaInfo += ` · até ${Utils.fmtDate(item.recorrencia_fim)}`;
                else if (item.recorrencia_total) recorrenciaInfo += ` · ${item.recorrencia_total}x`;
                else recorrenciaInfo += ' · sem fim';
            }

            const observacao = String(item.observacao || '').trim();
            const parcelaInfo = item.parcela_atual && item.total_parcelas ? `${item.parcela_atual}/${item.total_parcelas}` : '-';
            const canceladoEm = isCancelado ? Utils.fmtDate(item.cancelado_em) : '-';

            if (isGroup) {
                const parcelas = groupParcelas;
                const totalParcelas = Number(item._totalParcelas || parcelas.length || 0);
                const parcelasPagas = Number(item._parcelasPagas ?? parcelas.filter(p => Boolean(p.pago)).length);
                const valorParcela = parcelas.length > 0 ? (valorBase / parcelas.length) : 0;
                const percentual = totalParcelas > 0 ? Math.round((parcelasPagas / totalParcelas) * 100) : 0;
                const parcelamentoId = String(id).replace('grupo_', '');

                parts.push(`
                <article class="lan-card card-item" data-id="${id}" aria-expanded="false">
                    <div class="lan-card-main card-main">
                        <span class="lan-card-date card-date">${Utils.escapeHtml(dataMain)}</span>
                        <span class="lan-card-type card-type">
                            <span class="badge-tipo ${tipoClass}">
                                ${Utils.escapeHtml(tipoLabel)}
                            </span>
                        </span>
                        <span class="lan-card-value card-value ${tipoClass}">
                            ${Utils.escapeHtml(valorFmt)}
                        </span>
                    </div>

                    <button class="lan-card-toggle card-toggle" type="button" data-toggle="details" aria-label="Ver detalhes do parcelamento">
                        <span class="lan-card-toggle-icon card-toggle-icon"><i data-lucide="chevron-right"></i></span>
                        <span class="detalhes"> Ver detalhes</span>
                    </button>

                    <div class="lan-card-details card-details">
                        <div class="lan-card-detail-row card-detail-row is-multiline">
                            <span class="lan-card-detail-label card-detail-label">Descrição</span>
                            <span class="lan-card-detail-value card-detail-value">📦 ${Utils.escapeHtml(descricao)}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Categoria</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(categoria || '-')}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Conta</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(conta || '-')}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Parcelas</span>
                            <span class="lan-card-detail-value card-detail-value">${parcelasPagas}/${totalParcelas}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Valor Parcela</span>
                            <span class="lan-card-detail-value card-detail-value">R$ ${Utils.escapeHtml(valorParcela.toFixed(2))}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Progresso</span>
                            <span class="lan-card-detail-value card-detail-value">${percentual}%</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row actions-row">
                            <span class="lan-card-detail-label card-detail-label">AÇÕES</span>
                            <span class="lan-card-detail-value card-detail-value actions-slot" style="display:flex !important;gap:8px;">
                                <div class="lk-dropdown">
                                    <button class="lk-dropdown-trigger" type="button"><i data-lucide="more-vertical"></i></button>
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
                            </span>
                        </div>
                    </div>
                </article>
            `);
                continue;
            }

            // Menu dropdown 3-dot para ações
            const canEdit = Utils.canEditLancamento(item);
            const isSaldoIni = Utils.isSaldoInicial(item);

            let menuItems = '';
            if (canEdit) {
                menuItems += `<button class="lk-dropdown-item" data-action="edit" data-id="${id}"><i data-lucide="pen"></i> Editar</button>`;
            }
            if (!isCancelado && !isTransfer && !isSaldoIni) {
                if (!isPago) {
                    menuItems += `<button class="lk-dropdown-item lk-dropdown-success" data-action="marcar-pago" data-id="${id}"><i data-lucide="circle-check"></i> Marcar como Pago</button>`;
                } else {
                    menuItems += `<button class="lk-dropdown-item lk-dropdown-warning" data-action="desmarcar-pago" data-id="${id}"><i data-lucide="clock"></i> Marcar como Pendente</button>`;
                }
            }
            if (isRecorrente && !isCancelado) {
                menuItems += `<div class="lk-dropdown-divider"></div>`;
                menuItems += `<button class="lk-dropdown-item lk-dropdown-warning" data-action="cancelar-recorrencia" data-id="${id}"><i data-lucide="ban"></i> Cancelar Recorrência</button>`;
            }
            if (!isSaldoIni) {
                menuItems += `<div class="lk-dropdown-divider"></div>`;
                menuItems += `<button class="lk-dropdown-item lk-dropdown-danger" data-action="delete" data-id="${id}"><i data-lucide="trash-2"></i> Excluir</button>`;
            }

            const dropdownHtml = menuItems ? `
                <div class="lk-dropdown">
                    <button class="lk-dropdown-trigger" type="button"><i data-lucide="more-vertical"></i></button>
                    <div class="lk-dropdown-menu">${menuItems}</div>
                </div>
            ` : '';

            parts.push(`
                <article class="lan-card card-item" data-id="${id}" aria-expanded="false">
                    <div class="lan-card-main card-main">
                        <span class="lan-card-date card-date">${Utils.escapeHtml(dataMain)}</span>
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
                        <div class="lan-card-detail-row card-detail-row is-badges is-multiline">
                            <span class="lan-card-detail-label card-detail-label">Descrição</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(descricao)}${cardBadges}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Pago Em</span>
                            <span class="lan-card-detail-value card-detail-value">
                                <span class="badge-status ${pagoEmClass}"><i data-lucide="${pagoEmLucideIcon}"></i> ${Utils.escapeHtml(pagoEmLabel)}</span>
                            </span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Status</span>
                            <span class="lan-card-detail-value card-detail-value">
                                <span class="badge-status ${statusClass}"><i data-lucide="${statusLucideIcon}"></i> ${statusLabel}</span>
                            </span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Recorrência</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(recorrenciaInfo)}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Lembrete</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(lembreteInfo)}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Parcela</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(parcelaInfo)}</span>
                        </div>
                        <div class="lan-card-detail-row card-detail-row">
                            <span class="lan-card-detail-label card-detail-label">Cancelado em</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(canceladoEm)}</span>
                        </div>
                        ${observacao ? `
                        <div class="lan-card-detail-row card-detail-row is-multiline">
                            <span class="lan-card-detail-label card-detail-label">Observação</span>
                            <span class="lan-card-detail-value card-detail-value">${Utils.escapeHtml(observacao)}</span>
                        </div>
                        ` : ''}
                        ${isPagamentoFatura ? `
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
                                ${dropdownHtml || '<span style="color: var(--text-secondary); font-size: 0.75rem;">Nenhuma ação disponível</span>'}
                            </span>
                        </div>
                    </div>
                </article>
            `);
        }

        DOM.lanCards.innerHTML = parts.join('');
        if (window.lucide) lucide.createIcons();

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
        const indicators = DOM.lanCards?.querySelectorAll('.lan-sort-indicator.sort-indicator') || [];
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

        // ── Dropdown 3-dot toggle ──
        const trigger = target.closest('.lk-dropdown-trigger');
        if (trigger) {
            ev.preventDefault();
            ev.stopPropagation();
            const dropdown = trigger.closest('.lk-dropdown');
            const menu = dropdown?.querySelector('.lk-dropdown-menu');
            if (!menu) return;

            // Fecha qualquer outro dropdown aberto
            document.querySelectorAll('.lk-dropdown-menu.open').forEach(m => {
                if (m !== menu) m.classList.remove('open');
            });

            const isOpen = menu.classList.toggle('open');
            if (isOpen) {
                // Posiciona acima ou abaixo conforme espaço
                const rect = trigger.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                if (spaceBelow < 200) {
                    menu.style.bottom = '100%';
                    menu.style.top = 'auto';
                } else {
                    menu.style.top = '100%';
                    menu.style.bottom = 'auto';
                }
                if (window.lucide) lucide.createIcons({ nodes: menu.querySelectorAll('[data-lucide]') });

                // Fechar ao clicar fora
                const closeHandler = (e) => {
                    if (!dropdown.contains(e.target)) {
                        menu.classList.remove('open');
                        document.removeEventListener('click', closeHandler, true);
                    }
                };
                setTimeout(() => document.addEventListener('click', closeHandler, true), 0);
            }
            return;
        }

        // Fechar dropdown ao clicar em um item
        const dropdownItem = target.closest('.lk-dropdown-item');
        if (dropdownItem) {
            const openMenu = dropdownItem.closest('.lk-dropdown-menu');
            if (openMenu) openMenu.classList.remove('open');
        }

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

        // Botões de ação (dropdown items)
        const actionBtn = target.closest('.lk-dropdown-item');
        if (!actionBtn) return;

        const action = actionBtn.dataset.action;
        const id = Number(actionBtn.dataset.id);
        if (!id) return;

        const item = MobileCards.cache.find(l => Number(l.id) === id);
        if (!item) return;


        if (action === 'edit') {
            handleEdit(item);
            return;
        }

        if (action === 'delete') {
            handleDelete(id, item, actionBtn);
        }

        if (action === 'marcar-pago') {
            handleMarcarPago(id, actionBtn);
        }

        if (action === 'desmarcar-pago') {
            handleDesmarcarPago(id, actionBtn);
        }

        if (action === 'cancelar-recorrencia') {
            handleCancelarRecorrencia(id, actionBtn);
        }
    }
};

// ─── Register in Modules ─────────────────────────────────────────────────────

Modules.MobileCards = MobileCards;

export { MobileCards };
