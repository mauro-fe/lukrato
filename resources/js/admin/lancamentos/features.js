/**
 * ============================================================================
 * LUKRATO — Lançamentos / Features (Export, Filters, Data, Parcelamento, FaturaDetalhes)
 * ============================================================================
 */

import { CONFIG, DOM, STATE, Utils, MoneyMask, Notifications, Modules } from './state.js';

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
            console.error(err);
            Notifications.toast(err?.message || 'Falha ao exportar lançamentos.', 'error');
        } finally {
            ExportManager.setLoading(false);
        }
    }
};

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

        if (searchText) badges.push({ label: `Busca: "${searchText}"`, field: 'texto' });
        if (tipo) badges.push({ label: `Tipo: ${tipo === 'receita' ? 'Receita' : tipo === 'despesa' ? 'Despesa' : tipo}`, field: 'tipo' });
        if (categoriaVal && categoriaVal !== 'none') badges.push({ label: `Cat: ${categoria}`, field: 'categoria' });
        if (categoriaVal === 'none') badges.push({ label: 'Sem categoria', field: 'categoria' });
        if (contaVal) badges.push({ label: `Conta: ${conta}`, field: 'conta' });
        if (status) badges.push({ label: `Status: ${status === 'pago' ? 'Pago' : 'Pendente'}`, field: 'status' });

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
                DataManager.load();
            });
        });
    }
};

// ============================================================================
// GERENCIAMENTO DE DADOS
// ============================================================================

export const DataManager = {
    load: async () => {
        clearTimeout(STATE.loadTimer);
        STATE.loadTimer = setTimeout(async () => {
            const month = Utils.getCurrentMonth();
            const tipo = DOM.selectTipo ? DOM.selectTipo.value : '';
            const categoria = DOM.selectCategoria ? DOM.selectCategoria.value : '';
            const conta = DOM.selectConta ? DOM.selectConta.value : '';

            // Clear table while loading
            Modules.TableManager.renderRows([]);

            // Limpa cards enquanto carrega
            Modules.MobileCards.setItems([]);

            const items = await Modules.API.fetchLancamentos({
                month,
                tipo,
                categoria,
                conta,
                limit: CONFIG.DATA_LIMIT
            });

            // Armazenar no STATE para uso do ParcelamentoGrouper
            STATE.lancamentos = items;

            Modules.TableManager.renderRows(items);
            Modules.MobileCards.setItems(items);

        }, CONFIG.DEBOUNCE_DELAY);
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
            await DataManager.load();
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
                        parcelas: []
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

    /**
     * Cria row visual de parcelamento
     */
    createRow(grupo) {
        const totalParcelas = grupo.parcelas.length;
        const parcelasPagas = grupo.parcelas.filter(p => p.pago).length;
        const valorTotal = grupo.parcelas.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);
        const percentual = totalParcelas > 0 ? (parcelasPagas / totalParcelas) * 100 : 0;

        const primeira = grupo.parcelas[0];
        const tipoClass = primeira.tipo === 'receita' ? 'success' : 'danger';
        const tipoIcon = primeira.tipo === 'receita' ? '↑' : '↓';

        const tr = document.createElement('tr');
        tr.className = 'parcelamento-grupo bg-light';
        tr.dataset.parcelamentoId = grupo.id;

        tr.innerHTML = `
            <td>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-link p-0 text-decoration-none toggle-parcelas" 
                            data-parcelamento-id="${grupo.id}"
                            title="Ver parcelas">
                        <i data-lucide="chevron-right"></i>
                    </button>
                    <div>
                        <div class="fw-bold">
                            📦 ${grupo.descricao}
                        </div>
                        <small class="text-muted">
                            ${totalParcelas}x de R$ ${(valorTotal / totalParcelas).toFixed(2)}
                        </small>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-${tipoClass}">
                    ${tipoIcon} ${primeira.tipo}
                </span>
            </td>
            <td>
                <span class="badge bg-secondary">${primeira.categoria || '-'}</span>
            </td>
            <td>
                ${primeira.conta || primeira.cartao_credito || '-'}
            </td>
            <td class="text-end">
                <div class="fw-bold">R$ ${valorTotal.toFixed(2)}</div>
                <div class="progress mt-1" style="height: 6px;">
                    <div class="progress-bar bg-${tipoClass}" 
                         role="progressbar"
                         style="width: ${percentual}%"></div>
                </div>
                <small class="text-muted">
                    ${parcelasPagas}/${totalParcelas} pagas (${Math.round(percentual)}%)
                </small>
            </td>
            <td class="text-center">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                        <i data-lucide="more-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item toggle-parcelas-menu" 
                               href="#" 
                               data-parcelamento-id="${grupo.id}">
                                <i data-lucide="list"></i> Ver Parcelas
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger delete-parcelamento" 
                               href="#" 
                               data-parcelamento-id="${grupo.id}">
                                <i data-lucide="trash-2"></i> Cancelar Parcelamento
                            </a>
                        </li>
                    </ul>
                </div>
            </td>
        `;

        return tr;
    },

    /**
     * Toggle parcelas (expandir/colapsar)
     */
    toggle(parcelamentoId) {
        const grupoRow = document.querySelector(`tr[data-parcelamento-id="${parcelamentoId}"]`);
        if (!grupoRow) return;

        const icon = grupoRow.querySelector('.toggle-parcelas i');
        const existingDetails = grupoRow.nextElementSibling;

        // Se já está expandido, colapsar
        if (existingDetails?.classList.contains('parcelas-detalhes')) {
            existingDetails.remove();
            icon.setAttribute('data-lucide', 'chevron-right');
            icon.setAttribute('class', '');
            if (window.lucide) lucide.createIcons();
            return;
        }

        // Expandir - buscar parcelas do STATE
        const data = STATE.lancamentos || [];
        const parcelas = data.filter(item => item.parcelamento_id == parcelamentoId)
            .sort((a, b) => new Date(a.data) - new Date(b.data));

        if (parcelas.length === 0) return;

        // Criar row com detalhes
        const detailsRow = document.createElement('tr');
        detailsRow.className = 'parcelas-detalhes';
        detailsRow.innerHTML = `
            <td colspan="6" class="p-0 border-0">
                <div class="bg-white p-3 border-start border-end border-bottom">
                    <h6 class="mb-3">📋 Parcelas:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Parcela</th>
                                    <th>Data</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${parcelas.map((parcela, idx) => `
                                    <tr>
                                        <td>${parcela.numero_parcela || (idx + 1)}/${parcelas.length}</td>
                                        <td>${Utils.fmtDate(parcela.data)}</td>
                                        <td class="text-end fw-bold">${Utils.fmtMoney(parcela.valor)}</td>
                                        <td class="text-center">
                                            ${parcela.pago
                ? '<span class="badge bg-success">✓ Pago</span>'
                : '<span class="badge bg-warning">⏳ Pendente</span>'}
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm ${parcela.pago ? 'btn-warning' : 'btn-success'} toggle-pago-parcela"
                                                    data-lancamento-id="${parcela.id}"
                                                    data-pago="${!parcela.pago}"
                                                    title="${parcela.pago ? 'Marcar como não pago' : 'Marcar como pago'}">
                                                <i data-lucide="${parcela.pago ? 'x' : 'check'}"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary edit-lancamento"
                                                    data-lancamento-id="${parcela.id}"
                                                    title="Editar">
                                                <i data-lucide="pencil"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
        `;

        grupoRow.after(detailsRow);
        icon.setAttribute('data-lucide', 'chevron-down');
        icon.setAttribute('class', '');
        if (window.lucide) lucide.createIcons();
    },

    /**
     * Marca/desmarca parcela como paga
     */
    async togglePago(lancamentoId, pago) {
        try {
            const response = await fetch(`${CONFIG.ENDPOINT}/${lancamentoId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ pago: pago })
            });

            const data = await response.json();

            if (response.ok) {
                await DataManager.load();
            } else {
                throw new Error(data.message || 'Erro ao atualizar status');
            }
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message
            });
        }
    },

    /**
     * Deleta parcelamento inteiro (CASCADE)
     */
    async deletar(parcelamentoId) {
        const result = await Swal.fire({
            title: 'Cancelar Parcelamento',
            html: '<p>O que deseja fazer com este parcelamento?</p>',
            icon: 'question',
            input: 'radio',
            inputOptions: {
                'unpaid': 'Apenas parcelas pendentes (não pagas)',
                'all': 'Todo o parcelamento (inclusive parcelas pagas)'
            },
            inputValue: 'unpaid',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Excluir',
            cancelButtonText: 'Voltar',
            inputValidator: (value) => !value ? 'Selecione uma opção' : undefined
        });

        if (result.isConfirmed) {
            const scope = result.value; // 'unpaid' or 'all'
            try {
                const response = await fetch(`${CONFIG.BASE_URL}api/parcelamentos/${parcelamentoId}?scope=${scope}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Concluído!',
                        text: data.message || 'Parcelamento atualizado com sucesso',
                        timer: 2000
                    });
                    await DataManager.load();
                } else {
                    throw new Error(data.message || 'Erro ao cancelar parcelamento');
                }
            } catch (error) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message
                });
            }
        }
    },

    /**
     * Instala event listeners
     */
    installListeners() {
        document.addEventListener('click', (e) => {
            // Toggle parcelas
            if (e.target.closest('.toggle-parcelas') || e.target.closest('.toggle-parcelas-menu')) {
                e.preventDefault();
                const btn = e.target.closest('.toggle-parcelas') || e.target.closest('.toggle-parcelas-menu');
                const parcelamentoId = btn.dataset.parcelamentoId;
                ParcelamentoGrouper.toggle(parcelamentoId);
            }

            // Toggle pago/não pago de parcela
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
                const response = await fetch(`${CONFIG.ENDPOINT}/${lancamentoId}/fatura-detalhes`);
                const json = await response.json();
                if (json.success && json.data) {
                    data = json.data;
                    this.cache[lancamentoId] = data;
                } else {
                    console.warn('Erro ao buscar detalhes da fatura:', json.message);
                    return;
                }
            } catch (err) {
                console.error('Erro ao buscar detalhes da fatura:', err);
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
                        <a href="${CONFIG.BASE_URL}admin/faturas" class="text-decoration-none" style="font-size:0.8rem;color:#7c3aed;">
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
                const response = await fetch(`${CONFIG.ENDPOINT}/${lancamentoId}/fatura-detalhes`);
                const json = await response.json();
                if (json.success && json.data) {
                    data = json.data;
                    this.cache[lancamentoId] = data;
                } else {
                    return;
                }
            } catch (err) {
                console.error('Erro ao buscar detalhes da fatura:', err);
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
                    <a href="${CONFIG.BASE_URL}admin/faturas" style="font-size:0.75rem;color:#7c3aed;">Ver fatura →</a>
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

// ─── Register on Modules ─────────────────────────────────────────────────────

Modules.ExportManager = ExportManager;
Modules.FilterBadges = FilterBadges;
Modules.DataManager = DataManager;
Modules.ParcelamentoGrouper = ParcelamentoGrouper;
Modules.FaturaDetalhes = FaturaDetalhes;
