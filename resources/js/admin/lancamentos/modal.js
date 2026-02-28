/**
 * LUKRATO — Lançamentos / ModalManager + OptionsManager
 */

import { CONFIG, DOM, STATE, Utils, MoneyMask, Notifications, Modules } from './state.js';

// ============================================================================
// GERENCIAMENTO DE OPÇÕES (SELECTS)
// ============================================================================

const OptionsManager = {
    populateCategoriaSelect: (select, tipo, selectedId) => {
        if (!select) return;

        const normalized = (tipo || '').toLowerCase();
        const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';

        select.innerHTML = '<option value="">Sem categoria</option>';

        const items = STATE.categoriaOptions.filter((item) => {
            if (!normalized) return true;
            return item.tipo === normalized;
        });

        items.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = item.nome;
            opt.dataset.tipo = item.tipo || '';
            if (currentValue && String(item.id) === currentValue) opt.selected = true;
            select.appendChild(opt);
        });

        if (currentValue && select.value !== currentValue) {
            const fallback = document.createElement('option');
            fallback.value = currentValue;
            fallback.textContent = 'Categoria indisponível';
            fallback.selected = true;
            select.appendChild(fallback);
        }
    },

    populateContaSelect: (select, selectedId) => {
        if (!select) return;

        const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';

        select.innerHTML = '<option value="">Selecione</option>';

        STATE.contaOptions.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = item.label;
            if (currentValue && String(item.id) === currentValue) opt.selected = true;
            select.appendChild(opt);
        });

        if (currentValue && select.value !== currentValue) {
            const fallback = document.createElement('option');
            fallback.value = currentValue;
            fallback.textContent = 'Conta indisponível';
            fallback.selected = true;
            select.appendChild(fallback);
        }
    },

    loadFilterOptions: async () => {
        const [categorias, contas] = await Promise.all([
            DOM.selectCategoria ? Modules.API.fetchJsonList(`${CONFIG.BASE_URL}api/categorias`) : Promise.resolve([]),
            DOM.selectConta ? Modules.API.fetchJsonList(`${CONFIG.BASE_URL}api/contas?only_active=1`) : Promise.resolve([])
        ]);

        // Inicializar seletores de filtro
        if (DOM.selectCategoria) {
            DOM.selectCategoria.innerHTML = '<option value="">Categoria</option><option value="none">Sem categoria</option>';
        }
        if (DOM.selectConta) {
            DOM.selectConta.innerHTML = '<option value="">Conta</option>';
        }

        // Processar categorias
        if (DOM.selectCategoria && categorias.length) {
            STATE.categoriaOptions = categorias
                .map((cat) => ({
                    id: Number(cat?.id ?? 0),
                    nome: String(cat?.nome ?? '').trim(),
                    tipo: String(cat?.tipo ?? '').trim().toLowerCase()
                }))
                .filter((cat) => Number.isFinite(cat.id) && cat.id > 0 && cat.nome)
                .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', { sensitivity: 'base' }));

            const options = STATE.categoriaOptions
                .map((cat) => `<option value="${cat.id}">${Utils.escapeHtml(cat.nome)}</option>`)
                .join('');
            DOM.selectCategoria.insertAdjacentHTML('beforeend', options);
        }

        // Processar contas
        if (DOM.selectConta && contas.length) {
            STATE.contaOptions = contas
                .map((acc) => {
                    const id = Number(acc?.id ?? 0);
                    const nome = String(acc?.nome ?? '').trim();
                    const instituicao = String(acc?.instituicao ?? '').trim();
                    const label = nome || instituicao || `Conta #${id}`;
                    return { id, label };
                })
                .filter((acc) => Number.isFinite(acc.id) && acc.id > 0 && acc.label)
                .sort((a, b) => a.label.localeCompare(b.label, 'pt-BR', { sensitivity: 'base' }));

            const options = STATE.contaOptions
                .map((acc) => `<option value="${acc.id}">${Utils.escapeHtml(acc.label)}</option>`)
                .join('');
            DOM.selectConta.insertAdjacentHTML('beforeend', options);
        }

        // Populate export selects with same data
        if (DOM.exportConta) {
            DOM.exportConta.innerHTML = '<option value="">Todas</option>';
            if (STATE.contaOptions.length) {
                const opts = STATE.contaOptions
                    .map((acc) => `<option value="${acc.id}">${Utils.escapeHtml(acc.label)}</option>`)
                    .join('');
                DOM.exportConta.insertAdjacentHTML('beforeend', opts);
            }
        }
        if (DOM.exportCategoria) {
            DOM.exportCategoria.innerHTML = '<option value="">Todas</option>';
            if (STATE.categoriaOptions.length) {
                const opts = STATE.categoriaOptions
                    .map((cat) => `<option value="${cat.id}">${Utils.escapeHtml(cat.nome)}</option>`)
                    .join('');
                DOM.exportCategoria.insertAdjacentHTML('beforeend', opts);
            }
        }

        // Atualizar selects do modal se existirem
        if (DOM.selectLancConta) {
            OptionsManager.populateContaSelect(DOM.selectLancConta, DOM.selectLancConta.value || null);
        }
        if (DOM.selectTransConta) {
            OptionsManager.populateContaSelect(DOM.selectTransConta, DOM.selectTransConta.value || null);
        }
        if (DOM.selectTransContaDestino) {
            OptionsManager.populateContaSelect(DOM.selectTransContaDestino, DOM.selectTransContaDestino.value || null);
        }
        if (DOM.selectLancCategoria) {
            OptionsManager.populateCategoriaSelect(
                DOM.selectLancCategoria,
                DOM.selectLancTipo?.value || '',
                DOM.selectLancCategoria.value || null
            );
        }
    }
};

// ============================================================================
// GERENCIAMENTO DE MODAIS
// ============================================================================

const ModalManager = {
    ensureLancModal: () => {
        if (STATE.modalEditLanc) return STATE.modalEditLanc;
        if (!DOM.modalEditLancEl) return null;

        if (window.bootstrap?.Modal) {
            if (DOM.modalEditLancEl.parentElement && DOM.modalEditLancEl.parentElement !== document.body) {
                document.body.appendChild(DOM.modalEditLancEl);
            }
            STATE.modalEditLanc = window.bootstrap.Modal.getOrCreateInstance(DOM.modalEditLancEl);
            return STATE.modalEditLanc;
        }
        return null;
    },

    clearLancAlert: () => {
        if (!DOM.editLancAlert) return;
        DOM.editLancAlert.classList.add('d-none');
        DOM.editLancAlert.textContent = '';
    },

    showLancAlert: (msg) => {
        if (!DOM.editLancAlert) return;
        DOM.editLancAlert.textContent = msg;
        DOM.editLancAlert.classList.remove('d-none');
    },

    openEditLancamento: (data) => {
        const modal = ModalManager.ensureLancModal();
        if (!modal || !Utils.canEditLancamento(data)) return;

        STATE.editingLancamentoId = data?.id ?? null;
        if (!STATE.editingLancamentoId) return;

        ModalManager.clearLancAlert();

        // Preencher campos
        if (DOM.inputLancData) {
            DOM.inputLancData.value = (data?.data || '').slice(0, 10);
        }

        if (DOM.inputLancHora) {
            DOM.inputLancHora.value = data?.hora_lancamento || '';
        }

        if (DOM.selectLancTipo) {
            const tipo = String(data?.tipo || '').toLowerCase();
            DOM.selectLancTipo.value = ['receita', 'despesa'].includes(tipo) ? tipo : 'despesa';
        }

        OptionsManager.populateContaSelect(DOM.selectLancConta, data?.conta_id ?? null);
        OptionsManager.populateCategoriaSelect(
            DOM.selectLancCategoria,
            DOM.selectLancTipo?.value || '',
            data?.categoria_id ?? null
        );

        if (DOM.inputLancValor) {
            const valor = Math.abs(Number(data?.valor ?? 0));
            DOM.inputLancValor.value = Number.isFinite(valor) ? MoneyMask.format(valor) : '';
        }

        if (DOM.inputLancDescricao) {
            DOM.inputLancDescricao.value = data?.descricao || '';
        }

        if (DOM.selectLancFormaPagamento) {
            DOM.selectLancFormaPagamento.value = data?.forma_pagamento || '';
        }

        modal.show();
    },

    // Transfer modal helpers
    ensureTransModal: () => {
        if (STATE.modalEditTrans) return STATE.modalEditTrans;
        if (!DOM.modalEditTransEl) return null;
        if (window.bootstrap?.Modal) {
            if (DOM.modalEditTransEl.parentElement && DOM.modalEditTransEl.parentElement !== document.body) {
                document.body.appendChild(DOM.modalEditTransEl);
            }
            STATE.modalEditTrans = window.bootstrap.Modal.getOrCreateInstance(DOM.modalEditTransEl);
            return STATE.modalEditTrans;
        }
        return null;
    },

    openEditTransferencia: (data) => {
        const modal = ModalManager.ensureTransModal();
        if (!modal || !Utils.canEditLancamento(data)) return;

        STATE.editingLancamentoId = data?.id ?? null;
        if (!STATE.editingLancamentoId) return;

        // clear alerts
        if (DOM.editTransAlert) {
            DOM.editTransAlert.classList.add('d-none');
            DOM.editTransAlert.textContent = '';
        }

        // preencher campos
        if (DOM.inputTransData) DOM.inputTransData.value = (data?.data || '').slice(0, 10);
        if (DOM.inputTransValor) DOM.inputTransValor.value = MoneyMask.format(Math.abs(Number(data?.valor || 0)));
        if (DOM.inputTransDescricao) DOM.inputTransDescricao.value = data?.descricao || '';

        // popular selects de conta
        if (DOM.selectTransConta) OptionsManager.populateContaSelect(DOM.selectTransConta, data?.conta_id ?? null);
        if (DOM.selectTransContaDestino) OptionsManager.populateContaSelect(DOM.selectTransContaDestino, data?.conta_id_destino ?? null);

        modal.show();
    },

    submitTransForm: async (ev) => {
        ev.preventDefault();
        if (!STATE.editingLancamentoId) return;

        if (DOM.editTransAlert) {
            DOM.editTransAlert.classList.add('d-none');
            DOM.editTransAlert.textContent = '';
        }

        const dataValue = DOM.inputTransData?.value || '';
        const valorValue = DOM.inputTransValor?.value || '';
        const contaOrigem = DOM.selectTransConta?.value || '';
        const contaDestino = DOM.selectTransContaDestino?.value || '';
        const descricao = (DOM.inputTransDescricao?.value || '').trim();

        if (!dataValue) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Informe a data.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }
        if (!contaOrigem) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Selecione a conta de origem.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }
        if (!contaDestino) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Selecione a conta de destino.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }
        if (contaOrigem === contaDestino) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Conta de destino deve ser diferente da origem.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }

        const valorFloat = Math.abs(Number(MoneyMask.unformat(valorValue)));
        if (!Number.isFinite(valorFloat) || valorFloat <= 0) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = 'Informe um valor válido.'; DOM.editTransAlert.classList.remove('d-none'); }
            return;
        }

        const payload = {
            data: dataValue,
            tipo: 'transferencia',
            valor: Number(valorFloat.toFixed(2)),
            descricao: descricao,
            conta_id: Number(contaOrigem),
            conta_id_destino: Number(contaDestino),
            forma_pagamento: 'transferencia'
        };

        const submitBtn = DOM.formTrans?.querySelector('button[type="submit"]');
        submitBtn?.setAttribute('disabled', 'disabled');
        try {
            const res = await Modules.API.updateLancamento(STATE.editingLancamentoId, payload);
            const json = await res.json().catch(() => null);
            if (!res.ok || (json && json.status !== 'success')) {
                const msg = json?.message || (json?.errors ? Object.values(json.errors).join('\n') : 'Falha ao atualizar transferência.');
                throw new Error(msg);
            }

            ModalManager.ensureTransModal()?.hide();
            Notifications.toast('Transferência atualizada com sucesso!');
            await Modules.DataManager.load();
        } catch (err) {
            if (DOM.editTransAlert) { DOM.editTransAlert.textContent = err.message || 'Falha ao atualizar.'; DOM.editTransAlert.classList.remove('d-none'); }
        } finally {
            submitBtn?.removeAttribute('disabled');
        }
    },

    // Inicializa modal de visualização
    ensureViewModal: () => {
        if (!STATE.modalViewLanc && DOM.modalViewLancEl) {
            STATE.modalViewLanc = new bootstrap.Modal(DOM.modalViewLancEl);
        }
        return STATE.modalViewLanc;
    },

    // Abre modal de visualização de lançamento
    openViewLancamento: (data) => {
        const modal = ModalManager.ensureViewModal();
        if (!modal || !data) return;

        STATE.viewingLancamento = data;

        // Preencher ID oculto
        if (DOM.viewLancamentoId) {
            DOM.viewLancamentoId.value = data.id || '';
        }

        // Data
        if (DOM.viewLancData) {
            DOM.viewLancData.textContent = Utils.fmtDate(data.data);
        }

        // Tipo
        if (DOM.viewLancTipo) {
            const tipo = String(data.tipo || '').toLowerCase();
            const isTipoReceita = tipo === 'receita';
            DOM.viewLancTipo.textContent = isTipoReceita ? 'Receita' : 'Despesa';
            DOM.viewLancTipo.className = 'badge ' + (isTipoReceita ? 'bg-success' : 'bg-danger');
        }

        // Valor
        if (DOM.viewLancValor) {
            DOM.viewLancValor.textContent = Utils.fmtMoney(Math.abs(data.valor || 0));
        }

        // Status
        if (DOM.viewLancStatus) {
            let statusLabel = 'Efetivado';
            let statusClass = 'bg-success';
            if (data.status === 'pendente') {
                statusLabel = 'Pendente';
                statusClass = 'bg-warning text-dark';
            } else if (data.status === 'cancelado') {
                statusLabel = 'Cancelado';
                statusClass = 'bg-secondary';
            }
            DOM.viewLancStatus.textContent = statusLabel;
            DOM.viewLancStatus.className = 'badge ' + statusClass;
        }

        // Categoria
        if (DOM.viewLancCategoria) {
            DOM.viewLancCategoria.textContent = data.categoria_nome || data.categoria || '-';
        }

        // Conta
        if (DOM.viewLancConta) {
            DOM.viewLancConta.textContent = data.conta_nome || data.conta || '-';
        }

        // Cartão
        if (DOM.viewLancCartaoItem && DOM.viewLancCartao) {
            if (data.cartao_id && data.cartao_nome) {
                DOM.viewLancCartaoItem.classList.remove('d-none');
                DOM.viewLancCartao.textContent = data.cartao_nome;
            } else {
                DOM.viewLancCartaoItem.classList.add('d-none');
            }
        }

        // Forma de Pagamento
        if (DOM.viewLancFormaPgtoItem && DOM.viewLancFormaPgto) {
            if (data.forma_pagamento) {
                DOM.viewLancFormaPgtoItem.classList.remove('d-none');
                DOM.viewLancFormaPgto.textContent = Utils.formatFormaPagamento ? Utils.formatFormaPagamento(data.forma_pagamento) : data.forma_pagamento;
            } else {
                DOM.viewLancFormaPgtoItem.classList.add('d-none');
            }
        }

        // Descrição
        if (DOM.viewLancDescricaoCard && DOM.viewLancDescricao) {
            if (data.descricao && data.descricao.trim()) {
                DOM.viewLancDescricaoCard.classList.remove('d-none');
                DOM.viewLancDescricao.textContent = data.descricao;
            } else {
                DOM.viewLancDescricaoCard.classList.add('d-none');
            }
        }

        // Parcelamento
        if (DOM.viewLancParcelamentoCard && DOM.viewLancParcela) {
            if (data.parcela_atual && data.total_parcelas) {
                DOM.viewLancParcelamentoCard.classList.remove('d-none');
                DOM.viewLancParcela.textContent = `${data.parcela_atual}/${data.total_parcelas}`;
            } else {
                DOM.viewLancParcelamentoCard.classList.add('d-none');
            }
        }

        modal.show();
    },

    submitEditForm: async (ev) => {
        ev.preventDefault();
        if (!STATE.editingLancamentoId) return;

        ModalManager.clearLancAlert();

        // Validar e coletar dados
        const dataValue = DOM.inputLancData?.value || '';
        const horaValue = DOM.inputLancHora?.value || '';
        const tipoValue = DOM.selectLancTipo?.value || '';
        const contaValue = DOM.selectLancConta?.value || '';
        const categoriaValue = DOM.selectLancCategoria?.value || '';
        const valorValue = DOM.inputLancValor?.value || '';
        const descricaoValue = (DOM.inputLancDescricao?.value || '').trim();
        const formaPagamentoValue = DOM.selectLancFormaPagamento?.value || '';

        if (!dataValue) return ModalManager.showLancAlert('Informe a data do lançamento.');
        if (!tipoValue) return ModalManager.showLancAlert('Selecione o tipo do lançamento.');
        if (!contaValue) return ModalManager.showLancAlert('Selecione a conta.');

        const valorFloat = Math.abs(Number(MoneyMask.unformat(valorValue)));
        if (!Number.isFinite(valorFloat)) {
            return ModalManager.showLancAlert('Informe um valor válido.');
        }

        const payload = {
            data: dataValue,
            hora_lancamento: horaValue || null,
            tipo: tipoValue,
            valor: Number(valorFloat.toFixed(2)),
            descricao: descricaoValue,
            conta_id: Number(contaValue),
            categoria_id: categoriaValue ? Number(categoriaValue) : null,
            forma_pagamento: formaPagamentoValue || null
        };

        // payload remains unchanged for normal edit

        // Detectar se é um parcelamento
        const ehParcelado = payload.eh_parcelado == 1 || payload.eh_parcelado === '1';
        const numeroParcelas = parseInt(payload.numero_parcelas) || 0;

        // Se for parcelamento com múltiplas parcelas E não for edição (sem id), redirecionar para API de parcelamentos
        if (!STATE.editingLancamentoId && ehParcelado && numeroParcelas > 1) {
            try {
                const parcelamentoData = {
                    descricao: payload.descricao,
                    valor: parseFloat(payload.valor) || 0,
                    numero_parcelas: numeroParcelas,
                    categoria_id: payload.categoria_id,
                    conta_id: payload.conta_id,
                    tipo: payload.tipo,
                    data: payload.data
                };

                const response = await fetch('/api/parcelamentos', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(parcelamentoData)
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Erro ao criar parcelamento');
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: result.message || `Parcelamento criado! ${numeroParcelas} parcelas foram geradas.`,
                    timer: 3000
                });

                bootstrap.Modal.getInstance(DOM.modalEdit).hide();
                await Modules.DataManager.load();
                return;
            } catch (error) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Erro ao criar parcelamento'
                });
                return;
            }
        }

        // Continuar com lógica normal de lançamento simples...
        const submitBtn = DOM.formLanc.querySelector('button[type="submit"]');
        submitBtn?.setAttribute('disabled', 'disabled');

        try {
            const res = await Modules.API.updateLancamento(STATE.editingLancamentoId, payload);
            const json = await res.json().catch(() => null);

            if (!res.ok || (json && json.status !== 'success')) {
                const msg = json?.message ||
                    (json?.errors ? Object.values(json.errors).join('\n') :
                        'Falha ao atualizar lançamento.');
                throw new Error(msg);
            }

            ModalManager.ensureLancModal()?.hide();
            Notifications.toast('lançamento atualizado com sucesso!');
            await Modules.DataManager.load();

            document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                detail: {
                    resource: 'transactions',
                    action: 'update',
                    id: Number(STATE.editingLancamentoId)
                }
            }));
        } catch (err) {
            ModalManager.showLancAlert(err.message || 'Falha ao atualizar lançamento.');
        } finally {
            submitBtn?.removeAttribute('disabled');
        }
    }
};

// ============================================================================
// REGISTRO NO MÓDULO GLOBAL
// ============================================================================

Modules.OptionsManager = OptionsManager;
Modules.ModalManager = ModalManager;

export { OptionsManager, ModalManager };
