/**
 * ============================================================================
 * LUKRATO — Lançamentos / Features (Export, Filters, Data, Parcelamento, FaturaDetalhes)
 * ============================================================================
 */

import { CONFIG, DOM, STATE, Utils, Notifications, Modules } from './state.js';
import {
    resolveLancamentoFaturaDetailsEndpoint,
    resolveLancamentoPayEndpoint,
    resolveLancamentoUnpayEndpoint,
    resolveParcelamentoEndpoint,
} from '../api/endpoints/lancamentos.js';

import { apiDelete, apiGet, apiPut, getErrorMessage, logClientError, logClientWarning } from '../shared/api.js';
// ============================================================================
// GERENCIAMENTO DE EXPORTAÇÃO
// ============================================================================

export const ExportManager = {
    initDefaults: () => {
        const inputs = [DOM.inputExportStart, DOM.inputExportEnd].filter(Boolean);
        if (!inputs.length) return;

        const now = new Date();
        const isoToday = now.toISOString().slice(0, 10);

        inputs.forEach((input) => {
            if (input.dataset.defaultToday === '1' && !input.value) {
                input.value = isoToday;
                input.dataset.autofilled = '1';
            }
        });
    },

    setLoading: (isLoading) => {
        if (!DOM.btnExportar) return;
        DOM.btnExportar.disabled = isLoading;
        DOM.btnExportar.innerHTML = isLoading ?
            '<i data-lucide="loader-2" class="icon-spin"></i> Exportando...' :
            '<i data-lucide="file-output"></i> Exportar';
        if (window.lucide) lucide.createIcons();
    },

    export: async (forcedFormat) => {
        const month = Utils.getCurrentMonth();
        // Use export-specific filters if available, fallback to main filters
        const tipo = DOM.exportTipo ? DOM.exportTipo.value : (DOM.selectTipo ? DOM.selectTipo.value : '');
        const categoria = DOM.exportCategoria ? DOM.exportCategoria.value : (DOM.selectCategoria ? DOM.selectCategoria.value : '');
        const conta = DOM.exportConta ? DOM.exportConta.value : (DOM.selectConta ? DOM.selectConta.value : '');
        const startDate = Utils.getTrimmedDateValue(DOM.inputExportStart);
        const endDate = Utils.getTrimmedDateValue(DOM.inputExportEnd);

        // Validações
        if ((startDate && !endDate) || (!startDate && endDate)) {
            Notifications.toast('Informe tanto a data inicial quanto final para exportar.', 'error');
            return;
        }

        if (startDate && endDate && endDate < startDate) {
            Notifications.toast('A data final deve ser posterior ou igual à inicial.', 'error');
            return;
        }

        const format = forcedFormat ||
            (DOM.selectExportFormat ? (DOM.selectExportFormat.value || 'excel') : 'excel');

        ExportManager.setLoading(true);

        try {
            const res = await Modules.API.exportLancamentos({
                month,
                tipo,
                categoria,
                conta,
                startDate,
                endDate
            }, format);

            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const disposition = res.headers.get('Content-Disposition');
            const suffixDate = startDate && endDate ?
                `${startDate}_a_${endDate}` :
                (month || 'periodo');
            const fallback = `lancamentos-${suffixDate}.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
            const filename = Utils.parseDownloadFilename(disposition) || fallback;

            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            Notifications.toast('Exportação concluída com sucesso!');
        } catch (err) {
            logClientError('Erro ao exportar lançamentos', err, 'Falha ao exportar lançamentos');
            Notifications.toast(getErrorMessage(err, 'Falha ao exportar lançamentos.'), 'error');
        } finally {
            ExportManager.setLoading(false);
        }
    }
};

// ============================================================================
// FILTROS E CONTEXTO DA LISTA
// ============================================================================

export const ListFilters = {
    collect() {
        const startDate = Utils.getTrimmedDateValue(DOM.filtroDataInicio);
        const endDate = Utils.getTrimmedDateValue(DOM.filtroDataFim);
        const hasCustomPeriod = Boolean(startDate && endDate);

        return {
            month: hasCustomPeriod ? '' : Utils.getCurrentMonth(),
            tipo: DOM.selectTipo?.value || '',
            categoria: DOM.selectCategoria?.value || '',
            conta: DOM.selectConta?.value || '',
            search: (DOM.filtroTexto?.value || '').trim(),
            status: DOM.filtroStatus?.value || '',
            startDate,
            endDate,
            hasCustomPeriod
        };
    },

    hasIncompletePeriod() {
        const startDate = Utils.getTrimmedDateValue(DOM.filtroDataInicio);
        const endDate = Utils.getTrimmedDateValue(DOM.filtroDataFim);
        return Boolean((startDate && !endDate) || (!startDate && endDate));
    }
};

export const ListContext = {
    setRefreshLoading(isLoading) {
        if (!DOM.btnRefreshPage) return;

        DOM.btnRefreshPage.disabled = isLoading;
        DOM.btnRefreshPage.innerHTML = isLoading
            ? '<i data-lucide="loader-2" class="icon-spin"></i>'
            : '<i data-lucide="refresh-cw"></i>';

        if (window.lucide) lucide.createIcons();
    },

    update({ loading = STATE.isLoading } = {}) {
        const textEl = DOM.lancamentosContextText;
        const limitEl = DOM.lancamentosLimitNotice;
        const hintEl = DOM.selectionScopeHint;
        if (!textEl) return;

        const draftPeriod = ListFilters.hasIncompletePeriod();
        const filters = STATE.lastAppliedFilters || ListFilters.collect();
        const periodLabel = Utils.getAppliedPeriodLabel(filters);
        const total = STATE.filteredData.length;
        const activeFilters = Utils.countAppliedListFilters(filters);

        if (loading && total === 0) {
            textEl.textContent = 'Carregando lançamentos...';
        } else if (loading) {
            textEl.textContent = `Atualizando ${periodLabel}...`;
        } else if (draftPeriod) {
            textEl.textContent = `Complete a data inicial e final para usar um período personalizado. Exibindo ${periodLabel}.`;
        } else {
            const label = total === 1 ? 'lançamento' : 'lançamentos';
            const filterSuffix = activeFilters > 0
                ? ` • ${activeFilters} filtro${activeFilters > 1 ? 's' : ''} ativo${activeFilters > 1 ? 's' : ''}`
                : '';
            textEl.textContent = `${total} ${label} em ${periodLabel}${filterSuffix}`;
        }

        if (limitEl) {
            const showLimit = !loading && STATE.isDataLimitWarning;
            limitEl.style.display = showLimit ? 'inline-flex' : 'none';
            if (showLimit) {
                limitEl.textContent = `Exibindo até ${CONFIG.DATA_LIMIT} resultados. Refine os filtros para ver tudo.`;
            }
        }

        if (hintEl) {
            const selectedCount = STATE.selectedIds?.size || 0;
            hintEl.textContent = selectedCount > 0
                ? `${selectedCount} item${selectedCount > 1 ? 's' : ''} selecionado${selectedCount > 1 ? 's' : ''} nesta página.`
                : 'A seleção em massa vale apenas para a página atual.';
        }

        this.setRefreshLoading(loading);

        SummaryCards.update();
    }
};

Modules.ListContext = ListContext;

// ============================================================================
// SUMMARY CARDS (PREMIUM)
// ============================================================================

export const SummaryCards = {
    getMetaCoverage(item, valorBase) {
        const tipo = String(item?.tipo || '').toLowerCase();
        if (tipo !== 'despesa') return 0;

        const metaId = Number(item?.meta_id ?? 0);
        if (!(metaId > 0)) return 0;

        const operacao = String(item?.meta_operacao || '').toLowerCase();
        if (operacao && operacao !== 'resgate' && operacao !== 'realizacao') {
            return 0;
        }

        const metaValorBruto = Number(item?.meta_valor ?? valorBase);
        if (!Number.isFinite(metaValorBruto) || metaValorBruto <= 0) return 0;

        return Math.max(0, Math.min(Math.abs(valorBase), Math.abs(metaValorBruto)));
    },

    update() {
        const items = STATE.filteredData?.length ? STATE.filteredData : STATE.lancamentos || [];
        let totalReceitas = 0;
        let totalDespesas = 0;
        const count = items.length;

        for (const item of items) {
            const tipo = String(item.tipo || '').toLowerCase();
            const valor = Math.abs(parseFloat(item.valor) || 0);
            if (tipo === 'receita') totalReceitas += valor;
            else if (tipo === 'despesa') {
                const coberturaMeta = SummaryCards.getMetaCoverage(item, valor);
                const despesaMes = Math.max(0, valor - coberturaMeta);
                totalDespesas += despesaMes;
            }
        }

        const saldo = totalReceitas - totalDespesas;

        // Hero dynamic stats
        if (DOM.lanHeroDynamic) DOM.lanHeroDynamic.style.display = count > 0 ? '' : 'none';
        if (DOM.lanHeroTotalCount) DOM.lanHeroTotalCount.textContent = `${count} lançamento${count !== 1 ? 's' : ''}`;
        if (DOM.lanHeroReceitas) DOM.lanHeroReceitas.textContent = Utils.fmtMoney(totalReceitas);
        if (DOM.lanHeroDespesas) DOM.lanHeroDespesas.textContent = Utils.fmtMoney(totalDespesas);

        // Summary strip cards
        if (DOM.lanSummaryReceitas) DOM.lanSummaryReceitas.textContent = Utils.fmtMoney(totalReceitas);
        if (DOM.lanSummaryDespesas) DOM.lanSummaryDespesas.textContent = Utils.fmtMoney(totalDespesas);
        if (DOM.lanSummarySaldo) {
            DOM.lanSummarySaldo.textContent = Utils.fmtMoney(saldo);
            const card = DOM.lanSummarySaldo.closest('.lan-summary-card');
            if (card) {
                card.classList.toggle('is-positive', saldo >= 0);
                card.classList.toggle('is-negative', saldo < 0);
            }
        }
    }
};

Modules.SummaryCards = SummaryCards;

// ============================================================================
// BADGES DE FILTROS ATIVOS
// ============================================================================

export const FilterBadges = {
    update() {
        // Update chip-select active visual states
        document.querySelectorAll('.lk-filter-chip-select select').forEach(sel => {
            sel.closest('.lk-filter-chip-select')?.classList.toggle('active', sel.value !== '');
        });

        const container = DOM.activeFilterBadges;
        if (!container) return;

        const badges = [];
        const searchText = (DOM.filtroTexto?.value || '').trim();
        const tipo = DOM.selectTipo?.value || '';
        const categoria = DOM.selectCategoria?.selectedOptions?.[0]?.textContent || '';
        const categoriaVal = DOM.selectCategoria?.value || '';
        const conta = DOM.selectConta?.selectedOptions?.[0]?.textContent || '';
        const contaVal = DOM.selectConta?.value || '';
        const status = DOM.filtroStatus?.value || '';
        const startDate = Utils.getTrimmedDateValue(DOM.filtroDataInicio);
        const endDate = Utils.getTrimmedDateValue(DOM.filtroDataFim);

        if (searchText) badges.push({ label: `Busca: "${searchText}"`, field: 'texto' });
        if (tipo) badges.push({ label: `Tipo: ${tipo === 'receita' ? 'Receita' : tipo === 'despesa' ? 'Despesa' : tipo}`, field: 'tipo' });
        if (categoriaVal && categoriaVal !== 'none') badges.push({ label: `Cat: ${categoria}`, field: 'categoria' });
        if (categoriaVal === 'none') badges.push({ label: 'Sem categoria', field: 'categoria' });
        if (contaVal) badges.push({ label: `Conta: ${conta}`, field: 'conta' });
        if (status) badges.push({ label: `Status: ${status === 'pago' ? 'Pago' : 'Pendente'}`, field: 'status' });
        if (startDate && endDate) badges.push({ label: `Período: ${Utils.formatDateRangeLabel(startDate, endDate)}`, field: 'periodo' });

        if (!badges.length) {
            container.style.display = 'none';
            container.innerHTML = '';
            return;
        }

        container.style.display = 'flex';
        container.innerHTML = badges.map(b =>
            `<span class="lk-filter-badge">${Utils.escapeHtml(b.label)}<button type="button" data-clear-filter="${b.field}" aria-label="Remover">&times;</button></span>`
        ).join('');

        // Bind remove handlers
        container.querySelectorAll('[data-clear-filter]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const field = e.currentTarget.dataset.clearFilter;
                if (field === 'texto' && DOM.filtroTexto) DOM.filtroTexto.value = '';
                if (field === 'tipo' && DOM.selectTipo) DOM.selectTipo.value = '';
                if (field === 'categoria' && DOM.selectCategoria) DOM.selectCategoria.value = '';
                if (field === 'conta' && DOM.selectConta) DOM.selectConta.value = '';
                if (field === 'status' && DOM.filtroStatus) DOM.filtroStatus.value = '';
                if (field === 'periodo') {
                    if (DOM.filtroDataInicio) DOM.filtroDataInicio.value = '';
                    if (DOM.filtroDataFim) DOM.filtroDataFim.value = '';
                }
                document.dispatchEvent(new CustomEvent('lk:custom-select-sync'));
                DataManager.load();
            });
        });
    }
};

// ============================================================================
// GERENCIAMENTO DE DADOS
// ============================================================================

export const DataManager = {
    load: async (options = {}) => {
        const { immediate = false, preservePage = false, showToast = false } = options;
        clearTimeout(STATE.loadTimer);
        const execute = async () => {
            const filters = ListFilters.collect();
            STATE.lastAppliedFilters = filters;
            Modules.FilterBadges.update();

            if (ListFilters.hasIncompletePeriod()) {
                ListContext.update();
                if (showToast) {
                    Notifications.toast('Informe a data inicial e final para usar período personalizado.', 'info');
                }
                return;
            }

            if (filters.startDate && filters.endDate && filters.endDate < filters.startDate) {
                ListContext.update();
                Notifications.toast('A data final deve ser posterior ou igual à inicial.', 'error');
                return;
            }

            STATE.abortController?.abort?.();
            STATE.abortController = new AbortController();
            STATE.isLoading = true;
            ListContext.update({ loading: true });

            if (!STATE.allData.length) {
                Modules.TableManager.renderLoading();
                Modules.MobileCards.renderLoading();
            }

            const requestId = ++STATE.requestSeq;

            try {
                const items = await Modules.API.fetchLancamentos({
                    month: filters.month,
                    tipo: filters.tipo,
                    categoria: filters.categoria,
                    conta: filters.conta,
                    search: filters.search,
                    status: filters.status,
                    startDate: filters.startDate,
                    endDate: filters.endDate,
                    limit: CONFIG.DATA_LIMIT
                }, {
                    signal: STATE.abortController.signal
                });

                if (requestId !== STATE.requestSeq) return;

                STATE.lancamentos = items;
                STATE.lastFetchCount = items.length;
                STATE.isDataLimitWarning = items.length >= CONFIG.DATA_LIMIT;
                STATE.isLoading = false;

                Modules.TableManager.renderRows(items, {
                    resetPage: !preservePage,
                    clearSelection: true
                });
                Modules.MobileCards.setItems(items, {
                    resetPage: !preservePage
                });
                ListContext.update();

                if (showToast) {
                    Notifications.toast('Lista atualizada com sucesso!');
                }
            } catch (error) {
                if (error?.name === 'AbortError') return;

                if (requestId !== STATE.requestSeq) return;

                STATE.lancamentos = [];
                STATE.lastFetchCount = 0;
                STATE.isDataLimitWarning = false;
                STATE.isLoading = false;

                Modules.TableManager.renderRows([], {
                    resetPage: !preservePage,
                    clearSelection: true
                });
                Modules.MobileCards.setItems([], {
                    resetPage: !preservePage
                });
                ListContext.update();
                Notifications.toast(error?.message || 'Falha ao carregar lançamentos.', 'error');
            }
        };

        if (immediate) {
            await execute();
            return;
        }

        STATE.loadTimer = setTimeout(execute, CONFIG.DEBOUNCE_DELAY);
    },

    bulkDelete: async () => {
        const ids = Modules.TableManager.getSelectedIds();

        if (!ids.length) return;

        const ok = await Notifications.ask(
            `Excluir ${ids.length} lançamento(s)?`,
            'Esta ação não pode ser desfeita.'
        );
        if (!ok) return;

        DOM.btnExcluirSel.disabled = true;
        const done = await Modules.API.bulkDelete(ids);
        DOM.btnExcluirSel.disabled = false;

        if (done) {
            Modules.TableManager.clearSelection();
            Notifications.toast('Lançamentos excluídos com sucesso!');
            // Recarrega dados para manter cards em sincronia
            await DataManager.load({ immediate: true });
        } else {
            Notifications.toast('Alguns itens não foram excluídos.', 'error');
        }
    }
};

// ============================================================================
// AGRUPAMENTO DE PARCELAMENTOS
// ============================================================================

export const ParcelamentoGrouper = {
    /**
     * Processa itens para a tabela (agrupa parcelamentos)
     */
    processForTable(items) {
        const { agrupados, simples } = this.agrupar(items);

        // Retornar simples + grupos marcados
        return [
            ...simples,
            ...agrupados.map(g => ({
                ...g,
                _isParcelamentoGroup: true,
                _parcelas: g.parcelas,
                _totalParcelas: g.totalParcelas,
                _parcelasPagas: g.parcelasPagas,
                // Para compatibilidade com Tabulator
                id: `grupo_${g.id}`,
                data: g.parcelas[0].data,
                pago: false
            }))
        ];
    },

    /**
     * Interceptar renderização para agrupar parcelas
     */
    installInterceptor() {
        // Não precisamos mais interceptar, processamos direto no renderRows
    },

    /**
     * Agrupa itens por parcelamento_id
     */
    agrupar(items) {
        const grupos = {};
        const simples = [];

        items.forEach(item => {
            if (item.parcelamento_id) {
                if (!grupos[item.parcelamento_id]) {
                    grupos[item.parcelamento_id] = {
                        id: item.parcelamento_id,
                        descricao: item.descricao.replace(/ \(\d+\/\d+\)$/, ''),
                        tipo: item.tipo,
                        categoria: item.categoria,
                        conta: item.conta,
                        cartao_credito: item.cartao_credito,
                        parcelas: [],
                        totalParcelas: item.total_parcelas || 0,
                        parcelasPagas: item.parcelas_pagas ?? null
                    };
                }
                grupos[item.parcelamento_id].parcelas.push(item);
            } else {
                simples.push(item);
            }
        });

        return {
            agrupados: Object.values(grupos),
            simples
        };
    },

    closeModal() {
        const overlay = document.querySelector('.parcelas-modal-overlay');
        if (!overlay) return;

        if (typeof overlay._parcelasCleanup === 'function') {
            overlay._parcelasCleanup();
        }

        overlay.remove();
    },

    buildParcelaItem(parcela, idx, totalParcelas) {
        const isPago = parcela.pago === true || parcela.pago == 1;
        const num = parcela.numero_parcela || (idx + 1);
        const dataPagFmt = isPago && parcela.data_pagamento ? Utils.fmtDate(parcela.data_pagamento) : '';
        const statusText = isPago
            ? (dataPagFmt ? `Pago em ${dataPagFmt}` : 'Pago')
            : 'Pendente';
        const statusIcon = isPago ? 'circle-check' : 'clock';
        const toggleLabel = isPago ? 'Marcar como pendente' : 'Marcar como paga';
        const toggleIcon = isPago ? 'clock' : 'circle-check';

        return `
            <li class="parcela-item ${isPago ? 'parcela-paga' : 'parcela-pendente'}">
                <span class="parcela-num">${num}/${totalParcelas}</span>
                <div class="parcela-info">
                    <strong class="parcela-data">${Utils.fmtDate(parcela.data)}</strong>
                    <span class="parcela-valor">${Utils.fmtMoney(parcela.valor)}</span>
                </div>
                <span class="parcela-status ${isPago ? 'parcela-status-pago' : 'parcela-status-pendente'}">
                    <i data-lucide="${statusIcon}"></i>
                    <span>${Utils.escapeHtml(statusText)}</span>
                </span>
                <button class="parcela-toggle-btn ${isPago ? 'toggle-despagar' : 'toggle-pagar'}"
                        data-lancamento-id="${parcela.id}"
                        data-pago="${isPago ? '0' : '1'}"
                        title="${toggleLabel}"
                        aria-label="${toggleLabel}">
                    <i data-lucide="${toggleIcon}"></i>
                    <span>${toggleLabel}</span>
                </button>
            </li>
        `;
    },

    buildModalMarkup({ parcelamentoId, descricao, totalParcelas, pagas, abertas, pctPago, totalValor, totalPago, totalAberto, itemsHtml }) {
        const progressoHint = abertas > 0
            ? `${abertas} ${abertas === 1 ? 'parcela em aberto' : 'parcelas em aberto'}`
            : 'Tudo quitado';

        return `
            <div class="parcelas-modal" data-parcelamento-id="${parcelamentoId}" role="dialog" aria-modal="true" aria-labelledby="parcelasModalTitle">
                <div class="parcelas-modal-header">
                    <div class="parcelas-modal-header-main">
                        <div class="modal-icon parcelas-modal-icon">
                            <i data-lucide="layers"></i>
                        </div>
                        <div class="parcelas-modal-header-copy">
                            <span class="parcelas-modal-eyebrow">Parcelamento</span>
                            <h3 class="parcelas-modal-title" id="parcelasModalTitle">${Utils.escapeHtml(descricao)} — ${totalParcelas}x</h3>
                            <p class="parcelas-modal-subtitle">Acompanhe cada parcela e ajuste o status sem sair da lista.</p>
                        </div>
                    </div>
                    <button class="parcelas-modal-close" type="button" data-action="close-parcelas" aria-label="Fechar modal">
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <div class="parcelas-modal-summary">
                    <article class="parcelas-summary-card">
                        <span class="parcelas-summary-label">Parcelas</span>
                        <strong class="parcelas-summary-value">${totalParcelas}</strong>
                        <span class="parcelas-summary-meta">${abertas} em aberto</span>
                    </article>
                    <article class="parcelas-summary-card is-success">
                        <span class="parcelas-summary-label">Pago</span>
                        <strong class="parcelas-summary-value">${Utils.fmtMoney(totalPago)}</strong>
                        <span class="parcelas-summary-meta">${pagas} quitadas</span>
                    </article>
                    <article class="parcelas-summary-card is-warning">
                        <span class="parcelas-summary-label">Em aberto</span>
                        <strong class="parcelas-summary-value">${Utils.fmtMoney(totalAberto)}</strong>
                        <span class="parcelas-summary-meta">${progressoHint}</span>
                    </article>
                </div>

                <div class="parcelas-modal-progress">
                    <div class="parcelas-progress-copy">
                        <span class="parcelas-progress-label">Progresso do parcelamento</span>
                        <strong class="parcelas-progress-value">${pagas} de ${totalParcelas} pagas</strong>
                    </div>
                    <div class="parcelas-progress-track">
                        <div class="parcelas-progress-bar" aria-hidden="true">
                            <div class="parcelas-progress-fill" style="width:${pctPago}%"></div>
                        </div>
                        <span class="parcelas-progress-hint">${progressoHint}</span>
                    </div>
                    <span class="parcelas-progress-percent">${pctPago}%</span>
                </div>

                <div class="parcelas-modal-body">
                    <div class="parcelas-list-head" aria-hidden="true">
                        <span>Parcela</span>
                        <span>Resumo</span>
                        <span>Status</span>
                        <span>Ação</span>
                    </div>
                    <ul class="parcela-list">${itemsHtml}</ul>
                </div>

                <div class="parcelas-modal-footer">
                    <span class="parcelas-modal-total">Total comprometido: <strong>${Utils.fmtMoney(totalValor)}</strong></span>
                    <button type="button" class="btn btn-secondary parcelas-modal-footer-btn" data-action="close-parcelas">Fechar</button>
                </div>
            </div>
        `;
    },

    /**
     * Abre modal de parcelas buscando TODAS do endpoint da API
     */
    async toggle(parcelamentoId) {
        this.closeModal();

        try {
            // Buscar TODAS as parcelas da API (não apenas as do mês atual)
            const json = await apiGet(resolveParcelamentoEndpoint(parcelamentoId));
            const parcelamento = json.data || json;
            const parcelas = (parcelamento.parcelas || []).sort((a, b) => new Date(a.data) - new Date(b.data));

            if (parcelas.length === 0) return;

            const totalParcelas = parcelamento.numero_parcelas || parcelas.length;
            const pagas = parcelamento.parcelas_pagas ?? parcelas.filter(p => p.pago === true || p.pago == 1).length;
            const pctPago = totalParcelas > 0 ? Math.round((pagas / totalParcelas) * 100) : 0;
            const totalValor = parcelas.reduce((s, p) => s + Number(p.valor || 0), 0);
            const totalPago = parcelas.filter(p => p.pago === true || p.pago == 1)
                .reduce((s, p) => s + Number(p.valor || 0), 0);
            const totalAberto = Math.max(0, totalValor - totalPago);
            const abertas = Math.max(0, totalParcelas - pagas);

            const descricao = parcelamento.descricao || 'Parcelamento';

            const itemsHtml = parcelas.map((parcela, idx) => this.buildParcelaItem(parcela, idx, totalParcelas)).join('');

            const overlay = document.createElement('div');
            overlay.className = 'parcelas-modal-overlay';
            overlay.innerHTML = this.buildModalMarkup({
                parcelamentoId,
                descricao,
                totalParcelas,
                pagas,
                abertas,
                pctPago,
                totalValor,
                totalPago,
                totalAberto,
                itemsHtml
            });

            if (window.LK?.modalSystem) {
                window.LK.modalSystem.prepareOverlay(overlay, { scope: 'page' });
            } else {
                document.body.appendChild(overlay);
            }

            const previousOverflow = document.body.style.overflow;
            if (!window.LK?.modalSystem) {
                document.body.style.overflow = 'hidden';
            }

            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    this.closeModal();
                }
            };

            overlay._parcelasCleanup = () => {
                document.removeEventListener('keydown', escHandler);
                if (!window.LK?.modalSystem) {
                    document.body.style.overflow = previousOverflow;
                }
                overlay._parcelasCleanup = null;
            };

            if (window.LK?.refreshIcons) window.LK.refreshIcons();
            else if (window.lucide) lucide.createIcons();

            // Fechar ao clicar no backdrop
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) this.closeModal();
            });

            document.addEventListener('keydown', escHandler);
            requestAnimationFrame(() => {
                overlay.querySelector('.parcelas-modal-close')?.focus?.();
            });

        } catch (error) {
            logClientError('Erro ao abrir modal de parcelas', error, 'Erro ao carregar parcelas');
            LKFeedback.error('Erro ao carregar parcelas', { toast: true });
        }
    },

    /**
     * Marca/desmarca parcela como paga via endpoints dedicados
     */
    async togglePago(lancamentoId, pago) {
        try {
            const endpoint = pago
                ? resolveLancamentoPayEndpoint(lancamentoId)
                : resolveLancamentoUnpayEndpoint(lancamentoId);

            await apiPut(endpoint, {});

            // Reload data da tabela principal (para atualizar a tabela por trás)
            DataManager.load();
            // Reabrir modal buscando parcelas atualizadas da API
            const modal = document.querySelector('.parcelas-modal');
            const parcelamentoId = modal?.dataset.parcelamentoId;
            if (parcelamentoId) {
                await this.toggle(parcelamentoId);
            }
        } catch (error) {
            LKFeedback.error(getErrorMessage(error, 'Erro ao atualizar status'), { toast: true });
        }
    },

    /**
     * Deleta parcelamento inteiro (CASCADE)
     */
    async deletar(parcelamentoId) {
        const result = await Modules.ModalManager.openDeleteScopeModal({ mode: 'parcelamentoCascade' });
        if (!result?.scope) return;

        const scope = result.scope;
        try {
            const data = await apiDelete(`${resolveParcelamentoEndpoint(parcelamentoId)}?scope=${scope}`);
            LKFeedback.success(data?.message || 'Parcelamento atualizado com sucesso', { toast: true });
            await DataManager.load();
        } catch (error) {
            LKFeedback.error(getErrorMessage(error, 'Erro ao cancelar parcelamento'), { toast: true });
        }
    },

    /**
     * Instala event listeners
     */
    installListeners() {
        document.addEventListener('click', (e) => {
            // Toggle parcelas (abre modal)
            if (e.target.closest('.toggle-parcelas') || e.target.closest('.toggle-parcelas-menu') || e.target.closest('[data-action="ver-parcelas"]')) {
                e.preventDefault();
                const btn = e.target.closest('.toggle-parcelas') || e.target.closest('.toggle-parcelas-menu') || e.target.closest('[data-action="ver-parcelas"]');
                const parcelamentoId = btn.dataset.parcelamentoId || btn.dataset.parcelamento;
                if (parcelamentoId) ParcelamentoGrouper.toggle(parcelamentoId);
            }

            // Fechar modal de parcelas
            if (e.target.closest('[data-action="close-parcelas"]')) {
                e.preventDefault();
                ParcelamentoGrouper.closeModal();
            }

            // Toggle pago/não pago de parcela (dentro do modal)
            if (e.target.closest('.parcela-toggle-btn')) {
                e.preventDefault();
                const btn = e.target.closest('.parcela-toggle-btn');
                const lancamentoId = btn.dataset.lancamentoId;
                const pago = btn.dataset.pago === '1';
                btn.disabled = true;
                ParcelamentoGrouper.togglePago(lancamentoId, pago);
            }

            // Legacy: toggle-pago-parcela (tabela inline, se usada em algum lugar)
            if (e.target.closest('.toggle-pago-parcela')) {
                e.preventDefault();
                const btn = e.target.closest('.toggle-pago-parcela');
                const lancamentoId = btn.dataset.lancamentoId;
                const pago = btn.dataset.pago === 'true';
                ParcelamentoGrouper.togglePago(lancamentoId, pago);
            }

            // Editar parcela
            if (e.target.closest('.edit-lancamento')) {
                e.preventDefault();
                const btn = e.target.closest('.edit-lancamento');
                const lancamentoId = btn.dataset.lancamentoId;
                // Buscar item completo
                const item = STATE.lancamentos.find(l => l.id == lancamentoId);
                if (item) {
                    Modules.ModalManager.openEditLancamento(item);
                }
            }

            // Deletar parcelamento
            if (e.target.closest('.delete-parcelamento')) {
                e.preventDefault();
                const btn = e.target.closest('.delete-parcelamento');
                const parcelamentoId = btn.dataset.parcelamentoId;
                ParcelamentoGrouper.deletar(parcelamentoId);
            }
        });
    }
};

// ============================================================================
// FATURA DETALHES (expandir detalhes de pagamento de fatura)
// ============================================================================

export const FaturaDetalhes = {
    cache: {},

    /**
     * Toggle detalhes da fatura (expandir/colapsar)
     */
    async toggle(lancamentoId) {
        const row = document.querySelector(`tr[data-id="${lancamentoId}"]`);
        if (!row) return;

        const icon = row.querySelector('.toggle-fatura-detalhes i');
        const existingDetails = row.nextElementSibling;

        // Se já está expandido, colapsar
        if (existingDetails?.classList.contains('fatura-detalhes-row')) {
            existingDetails.remove();
            if (icon) {
                icon.setAttribute('data-lucide', 'chevron-right');
                icon.setAttribute('class', '');
            }
            if (window.lucide) lucide.createIcons();
            return;
        }

        // Buscar dados (com cache)
        let data = this.cache[lancamentoId];
        if (!data) {
            try {
                const json = await apiGet(resolveLancamentoFaturaDetailsEndpoint(lancamentoId));
                if (json.success && json.data) {
                    data = json.data;
                    this.cache[lancamentoId] = data;
                } else {
                    logClientWarning('Erro ao buscar detalhes da fatura', { message: json.message }, 'Erro ao buscar detalhes da fatura');
                    return;
                }
            } catch (err) {
                logClientError('Erro ao buscar detalhes da fatura', err, 'Erro ao buscar detalhes da fatura');
                return;
            }

        }
        const categorias = data.categorias || [];
        if (categorias.length === 0) return;

        // Montar HTML dos detalhes por categoria
        const catRows = categorias.map(cat => {
            const barWidth = Math.min(cat.percentual, 100);
            const icone = cat.categoria_icone || '📦';
            return `
                <div class="fatura-cat-row d-flex align-items-center gap-2 py-1" style="font-size:0.85rem;">
                    <span style="width:24px;text-align:center;">${icone}</span>
                    <span class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-medium">${Utils.escapeHtml(cat.categoria_nome)}</span>
                            <span class="text-muted" style="font-size:0.8rem;">${cat.qtd_itens} item${cat.qtd_itens !== 1 ? 's' : ''} · ${cat.percentual}%</span>
                        </div>
                        <div class="progress" style="height:4px;margin-top:2px;">
                            <div class="progress-bar" style="width:${barWidth}%;background:#7c3aed;"></div>
                        </div>
                    </span>
                    <span class="fw-bold text-nowrap" style="min-width:90px;text-align:right;">${Utils.fmtMoney(cat.total)}</span>
                </div>
            `;
        }).join('');

        const mesAno = String(data.mes).padStart(2, '0') + '/' + data.ano;

        // Criar row com detalhes
        const detailsRow = document.createElement('tr');
        detailsRow.className = 'fatura-detalhes-row';
        detailsRow.innerHTML = `
            <td colspan="8" class="p-0 border-0">
                <div style="background:var(--color-bg-secondary, #f8f9fa);padding:12px 16px;border-left:3px solid #7c3aed;margin:0 8px 4px;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0" style="font-size:0.9rem;color:#7c3aed;">
                            <i data-lucide="credit-card" style="width:14px;height:14px;vertical-align:middle;"></i>
                            Composição da Fatura ${mesAno}
                        </h6>
                        <span class="text-muted" style="font-size:0.8rem;">${categorias.length} categoria${categorias.length !== 1 ? 's' : ''}</span>
                    </div>
                    ${catRows}
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2" style="border-top:1px solid rgba(0,0,0,0.1);font-size:0.85rem;">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold">${Utils.fmtMoney(data.total)}</span>
                    </div>
                    <div class="mt-2 text-end">
                        <a href="${CONFIG.BASE_URL}faturas" class="text-decoration-none" style="font-size:0.8rem;color:#7c3aed;">
                            Ver fatura completa <i data-lucide="arrow-right" style="width:12px;height:12px;vertical-align:middle;"></i>
                        </a>
                    </div>
                </div>
            </td>
        `;

        row.after(detailsRow);
        if (icon) {
            icon.setAttribute('data-lucide', 'chevron-down');
            icon.setAttribute('class', '');
        }
        if (window.lucide) lucide.createIcons();
    },

    /**
     * Toggle detalhes no mobile card
     */
    async toggleMobile(lancamentoId, containerEl) {
        const existing = containerEl.querySelector('.fatura-detalhes-mobile');

        if (existing) {
            existing.remove();
            return;
        }

        let data = this.cache[lancamentoId];
        if (!data) {
            try {
                const json = await apiGet(resolveLancamentoFaturaDetailsEndpoint(lancamentoId));
                if (json.success && json.data) {
                    data = json.data;
                    this.cache[lancamentoId] = data;
                } else {
                    return;
                }
            } catch (err) {
                logClientError('Erro ao buscar detalhes da fatura', err, 'Erro ao buscar detalhes da fatura');
                return;
            }

        }
        const categorias = data.categorias || [];
        if (categorias.length === 0) return;

        const mesAno = String(data.mes).padStart(2, '0') + '/' + data.ano;

        const catItems = categorias.map(cat => {
            const icone = cat.categoria_icone || '📦';
            return `
                <div class="d-flex justify-content-between align-items-center py-1" style="font-size:0.8rem;">
                    <span>${icone} ${Utils.escapeHtml(cat.categoria_nome)} <span class="text-muted">(${cat.qtd_itens})</span></span>
                    <span class="fw-bold">${Utils.fmtMoney(cat.total)}</span>
                </div>
            `;
        }).join('');

        const detailsDiv = document.createElement('div');
        detailsDiv.className = 'fatura-detalhes-mobile';
        detailsDiv.innerHTML = `
            <div style="background:var(--color-bg-secondary, #f8f9fa);padding:10px 12px;border-left:3px solid #7c3aed;border-radius:0 8px 8px 0;margin:8px 0;">
                <div style="font-size:0.8rem;color:#7c3aed;font-weight:600;margin-bottom:6px;">
                    💳 Composição da Fatura ${mesAno}
                </div>
                ${catItems}
                <div class="d-flex justify-content-between mt-1 pt-1" style="border-top:1px solid rgba(0,0,0,0.1);font-size:0.8rem;">
                    <span class="fw-bold">Total</span>
                    <span class="fw-bold">${Utils.fmtMoney(data.total)}</span>
                </div>
                <div class="text-end mt-1">
                    <a href="${CONFIG.BASE_URL}faturas" style="font-size:0.75rem;color:#7c3aed;">Ver fatura →</a>
                </div>
            </div>
        `;

        const detailsContainer = containerEl.querySelector('.lan-card-details, .card-details');
        if (detailsContainer) {
            detailsContainer.appendChild(detailsDiv);
        } else {
            containerEl.appendChild(detailsDiv);
        }
    },

    /**
     * Instala event listeners
     */
    installListeners() {
        document.addEventListener('click', (e) => {
            // Desktop: toggle fatura detalhes
            if (e.target.closest('.toggle-fatura-detalhes')) {
                e.preventDefault();
                const btn = e.target.closest('.toggle-fatura-detalhes');
                const lancamentoId = btn.dataset.lancamentoId;
                FaturaDetalhes.toggle(lancamentoId);
            }

            // Mobile: toggle fatura detalhes
            if (e.target.closest('.toggle-fatura-detalhes-mobile')) {
                e.preventDefault();
                const btn = e.target.closest('.toggle-fatura-detalhes-mobile');
                const lancamentoId = btn.dataset.lancamentoId;
                const card = btn.closest('.lan-card, .card-item');
                if (card) {
                    FaturaDetalhes.toggleMobile(lancamentoId, card);
                }
            }
        });
    }
};

Modules.ExportManager = ExportManager;
Modules.FilterBadges = FilterBadges;
Modules.DataManager = DataManager;
Modules.ParcelamentoGrouper = ParcelamentoGrouper;
Modules.FaturaDetalhes = FaturaDetalhes;
