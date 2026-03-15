/**
 * LUKRATO — Lançamentos / ModalManager + OptionsManager
 */

import { CONFIG, DOM, STATE, Utils, MoneyMask, Notifications, Modules } from './state.js';
import { sugerirCategoriaIA as _sugerirCategoriaIA } from '../shared/ai-categorization.js';

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

        // Listener cascata subcategoria
        if (!select.dataset.subcatListenerAttached) {
            select.dataset.subcatListenerAttached = '1';
            select.addEventListener('change', () => {
                OptionsManager.populateSubcategoriaSelect(select.value);
            });
        }
    },

    /**
     * Preencher select de subcategoria com base na categoria selecionada (edit modal)
     */
    populateSubcategoriaSelect: async (categoriaId, selectedSubcatId) => {
        const select = DOM.selectLancSubcategoria;
        const group = DOM.subcategoriaGroup;
        if (!select) return;

        if (!categoriaId) {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.classList.add('hidden');
            return;
        }

        try {
            const res = await fetch(`${CONFIG.BASE_URL}api/categorias/${categoriaId}/subcategorias`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error();
            const json = await res.json();
            const subs = json?.data?.subcategorias ?? (Array.isArray(json?.data) ? json.data : []);

            const selectedVal = selectedSubcatId ? String(selectedSubcatId) : '';
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            subs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = String(sub.id);
                opt.textContent = sub.nome;
                if (selectedVal && String(sub.id) === selectedVal) opt.selected = true;
                select.appendChild(opt);
            });

            if (group) {
                if (subs.length > 0) group.classList.remove('hidden');
                else group.classList.add('hidden');
            }
        } catch {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.classList.add('hidden');
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

    /**
     * Sugerir categoria usando IA no modal de edição
     */
    sugerirCategoriaIA: async () => {
        await _sugerirCategoriaIA({
            descricaoInputId:      'editLancDescricao',
            categoriaSelectId:     'editLancCategoria',
            subcategoriaSelectId:  'editLancSubcategoria',
            subcategoriaGroupId:   'editSubcategoriaGroup',
            btnId:                 'btnEditAiSuggestCategoria',
            notify: (msg, type) => {
                const iconMap = { success: 'success', warning: 'warning', error: 'error' };
                Notifications.toast(msg, iconMap[type] || 'info');
            },
        });
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

        // Subcategoria cascata
        if (data?.categoria_id) {
            OptionsManager.populateSubcategoriaSelect(data.categoria_id, data?.subcategoria_id ?? null);
        } else {
            // Reset subcategoria
            if (DOM.selectLancSubcategoria) DOM.selectLancSubcategoria.innerHTML = '<option value="">Sem subcategoria</option>';
            if (DOM.subcategoriaGroup) DOM.subcategoriaGroup.classList.add('hidden');
        }

        if (DOM.inputLancValor) {
            const valor = Math.abs(Number(data?.valor ?? 0));
            DOM.inputLancValor.value = Number.isFinite(valor) ? MoneyMask.format(valor) : '';
        }

        if (DOM.inputLancDescricao) {
            DOM.inputLancDescricao.value = data?.descricao || '';
        }

        if (DOM.inputLancObservacao) {
            DOM.inputLancObservacao.value = data?.observacao || '';
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
            if (!res.ok || (json && !json.success)) {
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
            DOM.viewLancamentoId.textContent = data.id || '';
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
            if (data.cancelado_em) {
                statusLabel = 'Cancelado';
                statusClass = 'bg-secondary';
            } else if (!data.pago) {
                statusLabel = 'Pendente';
                statusClass = 'bg-warning text-dark';
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
            if (data.cartao_credito_id && data.cartao_nome) {
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
                DOM.viewLancParcelamentoCard.style.display = '';
                DOM.viewLancParcela.textContent = `${data.parcela_atual}/${data.total_parcelas}`;
            } else {
                DOM.viewLancParcelamentoCard.classList.add('d-none');
                DOM.viewLancParcelamentoCard.style.display = 'none';
            }
        }

        // Observação
        if (DOM.viewLancObservacaoCard && DOM.viewLancObservacao) {
            if (data.observacao && data.observacao.trim()) {
                DOM.viewLancObservacaoCard.classList.remove('d-none');
                DOM.viewLancObservacaoCard.style.display = '';
                DOM.viewLancObservacao.textContent = data.observacao;
            } else {
                DOM.viewLancObservacaoCard.classList.add('d-none');
                DOM.viewLancObservacaoCard.style.display = 'none';
            }
        }

        // Lembrete
        if (DOM.viewLancLembreteCard) {
            const segundos = data.lembrar_antes_segundos;
            if (segundos && segundos > 0) {
                DOM.viewLancLembreteCard.classList.remove('d-none');
                DOM.viewLancLembreteCard.style.display = '';
                // Formatar tempo
                let tempoLabel = '';
                if (segundos >= 604800) tempoLabel = '1 semana antes';
                else if (segundos >= 259200) tempoLabel = '3 dias antes';
                else if (segundos >= 172800) tempoLabel = '2 dias antes';
                else if (segundos >= 86400) tempoLabel = '1 dia antes';
                else tempoLabel = `${Math.round(segundos / 3600)}h antes`;
                if (DOM.viewLancLembreteTempo) DOM.viewLancLembreteTempo.textContent = tempoLabel;
                // Canais
                let canais = [];
                if (data.canal_inapp) canais.push('App');
                if (data.canal_email) canais.push('E-mail');
                if (DOM.viewLancLembreteCanais) DOM.viewLancLembreteCanais.textContent = canais.join(', ') || 'Nenhum canal';
            } else {
                DOM.viewLancLembreteCard.classList.add('d-none');
                DOM.viewLancLembreteCard.style.display = 'none';
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
        if (!Number.isFinite(valorFloat) || valorFloat <= 0) {
            return ModalManager.showLancAlert('Informe um valor válido maior que zero.');
        }

        const payload = {
            data: dataValue,
            hora_lancamento: horaValue || null,
            tipo: tipoValue,
            valor: Number(valorFloat.toFixed(2)),
            descricao: descricaoValue,
            observacao: (DOM.inputLancObservacao?.value || '').trim(),
            conta_id: Number(contaValue),
            categoria_id: categoriaValue ? Number(categoriaValue) : null,
            subcategoria_id: DOM.selectLancSubcategoria?.value ? Number(DOM.selectLancSubcategoria.value) : null,
            forma_pagamento: formaPagamentoValue || null
        };

        // Continuar com lógica normal de atualização...
        const submitBtn = DOM.formLanc.querySelector('button[type="submit"]');
        submitBtn?.setAttribute('disabled', 'disabled');

        try {
            const res = await Modules.API.updateLancamento(STATE.editingLancamentoId, payload);
            const json = await res.json().catch(() => null);

            if (!res.ok || (json && !json.success)) {
                const msg = json?.message ||
                    (json?.errors ? Object.values(json.errors).join('\n') :
                        'Falha ao atualizar lançamento.');
                throw new Error(msg);
            }

            ModalManager.ensureLancModal()?.hide();
            Notifications.toast('Lançamento atualizado com sucesso!');
            await Modules.DataManager.load();

            document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                detail: {
                    resource: 'transactions',
                    action: 'update',
                    id: Number(STATE.editingLancamentoId)
                }
            }));
        } catch (err) {
            const errorMsg = err.message || 'Falha ao atualizar lançamento.';
            ModalManager.showLancAlert(errorMsg);
            Notifications.toast(errorMsg, 'error');
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

// Expor sugerirCategoriaIA para onclick inline do modal de edição
window._editLancSugerirCategoriaIA = ModalManager.sugerirCategoriaIA;

export { OptionsManager, ModalManager };
