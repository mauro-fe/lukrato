import {
    resolveLancamentosEndpoint,
    resolveParcelamentosEndpoint,
    resolveTransfersEndpoint,
} from '../api/endpoints/lancamentos.js';

export function attachLancamentoGlobalSaveMethods(ManagerClass, dependencies) {
    const {
        formatMoney,
        parseMoney,
        refreshIcons,
        showToast,
        logClientWarning,
        apiPost,
        getErrorMessage,
        logClientError,
    } = dependencies;

    Object.assign(ManagerClass.prototype, {
        async salvarLancamento() {
            if (this.salvando) return;
            if (!this.validarFormulario()) return;

            this.salvando = true;
            let result = null;

            try {
                const dados = this.coletarDadosFormulario();

                const btnSalvar = document.getElementById('globalBtnSalvar');
                if (btnSalvar) {
                    btnSalvar.disabled = true;
                    btnSalvar.innerHTML = '<i data-lucide="loader-2" class="icon-spin" style="width:16px;height:16px;display:inline-block;"></i> Salvando...';
                    refreshIcons();
                }

                let apiUrl = resolveLancamentosEndpoint();
                let requestData = dados;

                if (this.tipoAtual === 'transferencia') {
                    apiUrl = resolveTransfersEndpoint();
                    requestData = {
                        conta_id: dados.conta_id,
                        conta_id_destino: dados.conta_destino_id,
                        meta_id: dados.meta_id,
                        meta_operacao: dados.meta_operacao,
                        meta_valor: dados.meta_valor,
                        valor: dados.valor,
                        data: dados.data,
                        descricao: dados.descricao
                    };
                } else if (dados.eh_parcelado && dados.total_parcelas > 1 && !dados.cartao_credito_id) {
                    apiUrl = resolveParcelamentosEndpoint();
                    requestData = {
                        descricao: dados.descricao,
                        valor_total: dados.valor,
                        numero_parcelas: dados.total_parcelas,
                        categoria_id: dados.categoria_id || null,
                        subcategoria_id: dados.subcategoria_id || null,
                        forma_pagamento: dados.forma_pagamento || null,
                        conta_id: dados.conta_id,
                        tipo: dados.tipo,
                        data_criacao: dados.data,
                    };
                    if (dados.lembrar_antes_segundos) {
                        requestData.lembrar_antes_segundos = dados.lembrar_antes_segundos;
                        requestData.canal_inapp = dados.canal_inapp || '0';
                        requestData.canal_email = dados.canal_email || '0';
                    }
                }

                result = await apiPost(apiUrl, requestData);
                const isSuccess = result?.success === true;

                if (isSuccess) {
                    await this.handleSuccessfulSave(result);
                    return;
                }

                let errorMessage = result.message || 'Erro ao salvar lancamento';
                if (result.errors) {
                    const errorList = Object.values(result.errors).flat().join('\n');
                    errorMessage = errorList || errorMessage;
                }
                if (errorMessage.toLowerCase().includes('limite')) {
                    Swal.fire({ icon: 'error', title: 'Limite Insuficiente', text: errorMessage, confirmButtonText: 'Entendi', confirmButtonColor: '#d33', customClass: { container: 'swal-above-modal' } });
                    this.salvando = false;
                    this._resetBtnSalvar();
                    return;
                }
                throw new Error(errorMessage);
            } catch (error) {
                logClientError('Erro ao salvar lancamento', error, 'Falha ao salvar lancamento');
                this.salvando = false;
                this._resetBtnSalvar();
                Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage(error, 'Erro ao salvar lancamento.'), confirmButtonText: 'OK', customClass: { container: 'swal-above-modal' } });
            }
        },

        async handleSuccessfulSave(result) {
            const payload = result?.data ?? null;

            try {
                if (payload?.gamification) {
                    const gamif = payload.gamification;
                    if (gamif.achievements?.length > 0 && typeof window.notifyMultipleAchievements === 'function') {
                        window.notifyMultipleAchievements(gamif.achievements);
                    }
                    if (gamif.level_up && typeof window.notifyLevelUp === 'function') {
                        window.notifyLevelUp(gamif.level);
                    }
                }

                this.closeModal();
                showToast(result?.message || 'Lancamento salvo com sucesso!', 'success');

                if (typeof window.refreshDashboard === 'function') {
                    window.refreshDashboard();
                } else if (window.LK?.refreshDashboard) {
                    window.LK.refreshDashboard();
                }

                const currentPath = window.location.pathname.toLowerCase();
                if (currentPath.includes('contas') && window.contasManager && typeof window.contasManager.loadContas === 'function') {
                    await window.contasManager.loadContas();
                }

                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'transactions',
                        action: 'create',
                        source: 'lancamento-global',
                        payload
                    }
                }));
                window.dispatchEvent(new CustomEvent('lancamento-created', { detail: payload }));
            } catch (postSaveError) {
                logClientWarning('Erro ao atualizar UI apos salvar lancamento', postSaveError, 'Falha ao atualizar a tela');
            } finally {
                this.salvando = false;
                this._resetBtnSalvar();
            }
        },

        _resetBtnSalvar() {
            const btnSalvar = document.getElementById('globalBtnSalvar');
            if (btnSalvar) {
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = '<i data-lucide="save" style="width:16px;height:16px;display:inline-block;"></i> Salvar';
                refreshIcons();
            }
        },

        atualizarPreviewParcelamento() {
            const preview = document.getElementById('globalParcelamentoPreview');
            if (!preview) return;

            const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value || '');
            const parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value || '0', 10);
            const parcelado = document.getElementById('globalLancamentoParcelado')?.checked === true;

            if (parcelado && valor > 0 && parcelas >= 2) {
                const valorParcela = valor / parcelas;
                const descricaoParcelamento = this.tipoAtual === 'receita'
                    ? `${parcelas} recebimentos de ${formatMoney(valorParcela)}`
                    : `${parcelas} parcelas de ${formatMoney(valorParcela)}`;

                preview.innerHTML = `
                    <div class="preview-info">
                        <i data-lucide="calculator" style="width:16px;height:16px;display:inline-block;"></i>
                        <span>${descricaoParcelamento}</span>
                    </div>`;
                preview.style.display = 'block';
                refreshIcons();
            } else {
                preview.style.display = 'none';
            }
        },
    });
}
