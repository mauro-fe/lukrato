export function attachLancamentosModalTransfer(ModalManager, dependencies) {
    const {
        DOM,
        STATE,
        Utils,
        MoneyMask,
        Notifications,
        Modules,
        getErrorMessage,
        syncModalSelects,
        OptionsManager,
    } = dependencies;

    Object.assign(ModalManager, {
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

        openEditTransferencia: async (data) => {
            const modal = ModalManager.ensureTransModal();
            if (!modal || !Utils.canEditLancamento(data)) return;

            STATE.editingLancamentoId = data?.id ?? null;
            STATE.editingLancamentoData = data ? { ...data } : null;
            if (!STATE.editingLancamentoId) return;

            if (DOM.editTransAlert) {
                DOM.editTransAlert.classList.add('d-none');
                DOM.editTransAlert.textContent = '';
            }

            if (DOM.inputTransData) DOM.inputTransData.value = (data?.data || '').slice(0, 10);
            if (DOM.inputTransValor) DOM.inputTransValor.value = MoneyMask.format(Math.abs(Number(data?.valor || 0)));
            if (DOM.inputTransDescricao) DOM.inputTransDescricao.value = data?.descricao || '';

            if (DOM.selectTransConta) OptionsManager.populateContaSelect(DOM.selectTransConta, data?.conta_id ?? null);
            if (DOM.selectTransContaDestino) OptionsManager.populateContaSelect(DOM.selectTransContaDestino, data?.conta_id_destino ?? null);
            await OptionsManager.populateMetaSelect(
                DOM.selectTransMeta,
                data?.meta_id ?? null,
                { fallbackLabel: Utils.getLancamentoMetaTitle(data) || 'Meta vinculada' }
            );

            syncModalSelects(DOM.modalEditTransEl);
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
            const metaId = ModalManager.getSelectedMetaId(DOM.selectTransMeta);

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

            const metaOperacao = metaId ? 'aporte' : null;
            const metaValor = metaId ? Number(valorFloat.toFixed(2)) : null;

            const payload = {
                data: dataValue,
                tipo: 'transferencia',
                valor: Number(valorFloat.toFixed(2)),
                descricao: descricao,
                conta_id: Number(contaOrigem),
                conta_id_destino: Number(contaDestino),
                forma_pagamento: 'transferencia',
                meta_id: metaId,
                meta_operacao: metaOperacao,
                meta_valor: metaValor
            };

            const submitBtn = DOM.formTrans?.querySelector('button[type="submit"]');
            submitBtn?.setAttribute('disabled', 'disabled');
            try {
                const json = await Modules.API.updateLancamento(STATE.editingLancamentoId, payload);
                if (json && json.success === false) {
                    const msg = getErrorMessage({ data: json }, 'Falha ao atualizar transferencia.');
                    throw new Error(msg);
                }

                ModalManager.ensureTransModal()?.hide();
                Notifications.toast('Transferência atualizada com sucesso!');
                await Modules.DataManager.load();

                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'transactions',
                        action: 'update',
                        id: Number(STATE.editingLancamentoId)
                    }
                }));
            } catch (err) {
                if (DOM.editTransAlert) {
                    DOM.editTransAlert.textContent = getErrorMessage(err, 'Falha ao atualizar.');
                    DOM.editTransAlert.classList.remove('d-none');
                }
            } finally {
                submitBtn?.removeAttribute('disabled');
            }
        },
    });
}
