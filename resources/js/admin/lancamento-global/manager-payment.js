export function attachLancamentoGlobalPaymentMethods(ManagerClass, dependencies) {
    const {
        formatMoney,
        parseMoney,
    } = dependencies;

    Object.assign(ManagerClass.prototype, {
    resetarFormaPagamento() {
        document.querySelectorAll('#globalFormaPagamentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('#globalFormaRecebimentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        const formaPagInput = document.getElementById('globalFormaPagamento');
        if (formaPagInput) formaPagInput.value = '';
        const formaRecInput = document.getElementById('globalFormaRecebimento');
        if (formaRecInput) formaRecInput.value = '';
        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
        if (cartaoGroup) cartaoGroup.classList.remove('active');
        const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
        if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
        const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
        if (numParcelasGroup) numParcelasGroup.style.display = 'none';
        // Reset assinatura
        const assinaturaGroup = document.getElementById('globalAssinaturaCartaoGroup');
        if (assinaturaGroup) assinaturaGroup.style.display = 'none';
        const assinaturaCheck = document.getElementById('globalLancamentoAssinaturaCartao');
        if (assinaturaCheck) assinaturaCheck.checked = false;
        const assinaturaDetalhes = document.getElementById('globalAssinaturaCartaoDetalhes');
        if (assinaturaDetalhes) assinaturaDetalhes.style.display = 'none';
        this.atualizarTextosParcelamento();
        this.atualizarPreviewParcelamento();
        this.syncEnhancedSelects();
    },

    selecionarFormaPagamento(forma) {
        document.querySelectorAll('#globalFormaPagamentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        const btnSelecionado = document.querySelector(`#globalFormaPagamentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        const formaPagInput = document.getElementById('globalFormaPagamento');
        if (formaPagInput) formaPagInput.value = forma;

        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
        const metaGroup = document.getElementById('globalMetaGroup');
        const metaSelect = document.getElementById('globalLancamentoMeta');
        const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
        const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');

        if (forma === 'cartao_credito') {
            if (cartaoGroup) { cartaoGroup.classList.add('active'); cartaoGroup.style.display = 'block'; }
            this.preencherCartoes();
            const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
            if (cartaoSelect?.value && parcelamentoGroup) parcelamentoGroup.style.display = 'block';
            // Show assinatura group for card
            const assinaturaGroup = document.getElementById('globalAssinaturaCartaoGroup');
            if (assinaturaGroup) assinaturaGroup.style.display = 'block';
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
            const recorrenteCheck = document.getElementById('globalLancamentoRecorrente');
            if (recorrenteCheck) recorrenteCheck.checked = false;
            const recorrenciaDetalhes = document.getElementById('globalRecorrenciaDetalhes');
            if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
            const lembreteGroup = document.getElementById('globalLembreteGroup');
            if (lembreteGroup) lembreteGroup.style.display = 'none';
            const pagoGroup = document.getElementById('globalPagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'none';
            if (metaGroup) metaGroup.style.display = 'none';
            if (metaSelect) metaSelect.value = '';
            this.syncMetaLinkFields({ preserveAmount: false });
        } else {
            const hasFormaPagamento = Boolean(forma);
            if (cartaoGroup) { cartaoGroup.classList.remove('active'); cartaoGroup.style.display = 'none'; }
            if (parcelamentoGroup) {
                parcelamentoGroup.style.display = hasFormaPagamento ? 'block' : 'none';
            }
            const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
            if (numParcelasGroup) numParcelasGroup.style.display = 'none';
            const parceladoCheck = document.getElementById('globalLancamentoParcelado');
            if (parceladoCheck) parceladoCheck.checked = false;
            const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
            if (cartaoSelect) cartaoSelect.value = '';
            if (this.tipoAtual !== 'transferencia') {
                if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
                const pagoGroup = document.getElementById('globalPagoGroup');
                if (pagoGroup) pagoGroup.style.display = 'block';
            }
            // Hide assinatura for non-card
            const assinaturaGroup = document.getElementById('globalAssinaturaCartaoGroup');
            if (assinaturaGroup) assinaturaGroup.style.display = 'none';
            const assinaturaCheck = document.getElementById('globalLancamentoAssinaturaCartao');
            if (assinaturaCheck) assinaturaCheck.checked = false;
            const assinaturaDetalhes = document.getElementById('globalAssinaturaCartaoDetalhes');
            if (assinaturaDetalhes) assinaturaDetalhes.style.display = 'none';
            if (metaGroup && (this.tipoAtual === 'receita' || this.tipoAtual === 'despesa')) {
                metaGroup.style.display = 'block';
            }
            this.syncMetaLinkFields({ preserveAmount: true });
        }

        this.atualizarTextosParcelamento();
        this.atualizarPreviewParcelamento();
        this.syncPagoRecorrenciaState();
        this.syncReminderVisibility();
        this.schedulePlanningAlertsRender();
        this.syncEnhancedSelects();
    },

    selecionarFormaRecebimento(forma) {
        document.querySelectorAll('#globalFormaRecebimentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        const btnSelecionado = document.querySelector(`#globalFormaRecebimentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        const formaRecInput = document.getElementById('globalFormaRecebimento');
        if (formaRecInput) formaRecInput.value = forma;

        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
        const metaGroup = document.getElementById('globalMetaGroup');
        const metaSelect = document.getElementById('globalLancamentoMeta');

        if (forma === 'estorno_cartao') {
            if (cartaoGroup) { cartaoGroup.classList.add('active'); cartaoGroup.style.display = 'block'; }
            this.preencherCartoes(true);
            if (metaGroup) metaGroup.style.display = 'none';
            if (metaSelect) metaSelect.value = '';
            this.syncMetaLinkFields({ preserveAmount: false });
            const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
            if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
            const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
            if (numParcelasGroup) numParcelasGroup.style.display = 'none';
            const parceladoCheck = document.getElementById('globalLancamentoParcelado');
            if (parceladoCheck) parceladoCheck.checked = false;
            const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
            const recorrenteCheck = document.getElementById('globalLancamentoRecorrente');
            if (recorrenteCheck) recorrenteCheck.checked = false;
            const recorrenciaDetalhes = document.getElementById('globalRecorrenciaDetalhes');
            if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
            const lembreteGroup = document.getElementById('globalLembreteGroup');
            if (lembreteGroup) lembreteGroup.style.display = 'none';
            const pagoGroup = document.getElementById('globalPagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'none';
            this.clearReminderFields();
        } else {
            const hasFormaRecebimento = Boolean(forma);
            if (cartaoGroup) { cartaoGroup.classList.remove('active'); cartaoGroup.style.display = 'none'; }
            const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
            if (cartaoSelect) cartaoSelect.value = '';
            if (metaGroup && (this.tipoAtual === 'receita' || this.tipoAtual === 'despesa')) {
                metaGroup.style.display = 'block';
            }
            this.syncMetaLinkFields({ preserveAmount: true });
            const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
            if (parcelamentoGroup) parcelamentoGroup.style.display = hasFormaRecebimento ? 'block' : 'none';
            const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
            const pagoGroup = document.getElementById('globalPagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'block';
        }

        this.atualizarTextosParcelamento();
        this.atualizarPreviewParcelamento();
        this.syncPagoRecorrenciaState();
        this.syncReminderVisibility();
        this.schedulePlanningAlertsRender();
        this.syncEnhancedSelects();
    },

    validarFormulario() {
        if (!this.tipoAtual) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o tipo de lançamento', customClass: { container: 'swal-above-modal' } });
            return false;
        }

        const contaId = this.contaSelecionada?.id || document.getElementById('globalContaSelect')?.value;
        if (!contaId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione a conta', customClass: { container: 'swal-above-modal' } });
            return false;
        }

        this.preencherDescricaoPadraoSeVazia?.();
        const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value);
        const data = document.getElementById('globalLancamentoData')?.value || '';

        if (!valor || valor <= 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe um valor válido', customClass: { container: 'swal-above-modal' } });
            return false;
        }
        if (!data) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe a data', customClass: { container: 'swal-above-modal' } });
            return false;
        }


        const metaIdRaw = document.getElementById('globalLancamentoMeta')?.value || '';
        const metaSelecionada = Number(metaIdRaw) > 0;
        if (metaSelecionada) {
            const metaValor = parseMoney(document.getElementById('globalLancamentoMetaValor')?.value || '');
            if (!metaValor || metaValor <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Informe quanto deste lancamento foi para a meta.',
                    customClass: { container: 'swal-above-modal' }
                });
                return false;
            }

            if (metaValor > valor) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'O valor da meta nao pode ser maior que o valor total do lancamento.',
                    customClass: { container: 'swal-above-modal' }
                });
                return false;
            }
        }

        // Validar limite do cartão
        if (this.tipoAtual === 'despesa') {
            const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
            if (cartaoId) {
                const cartao = this.cartoes.find(c => c.id == cartaoId);
                if (cartao) {
                    const limiteDisponivel = parseFloat(cartao.limite_disponivel || 0);
                    if (valor > limiteDisponivel) {
                        Swal.fire({
                            icon: 'error', title: 'Limite Insuficiente',
                            html: `<p>O valor da compra (${formatMoney(valor)}) excede o limite disponível do cartão.</p>
                                   <p><strong>Limite disponível:</strong> ${formatMoney(limiteDisponivel)}</p>`,
                            confirmButtonText: 'Entendi',
                            customClass: { container: 'swal-above-modal' }
                        });
                        return false;
                    }
                }
            }
        }

        if (this.tipoAtual === 'transferencia') {
            const contaDestino = document.getElementById('globalLancamentoContaDestino')?.value;
            if (!contaDestino) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione a conta de destino', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }

        // Validar ranges de parcelas e recorrência
        const parcelado = document.getElementById('globalLancamentoParcelado')?.checked;
        if (parcelado) {
            const totalParcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 0;
            if (totalParcelas < 2 || totalParcelas > 48) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'O número de parcelas deve ser entre 2 e 48', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }
        const recorrente = document.getElementById('globalLancamentoRecorrente')?.checked;
        if (recorrente) {
            const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value;
            if (modo === 'quantidade') {
                const total = parseInt(document.getElementById('globalLancamentoRecorrenciaTotal')?.value) || 0;
                if (total < 2 || total > 120) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'A quantidade de repetições deve ser entre 2 e 120', customClass: { container: 'swal-above-modal' } });
                    return false;
                }
            }
        }

        return true;
    }
    });
}
