export function attachLancamentoGlobalEventsMethods(ManagerClass, dependencies) {
    const {
        applyMoneyMask,
        parseMoney,
        formatMoney,
        refreshIcons,
    } = dependencies;

    Object.assign(ManagerClass.prototype, {
        configurarEventos() {
            const valorInput = document.getElementById('globalLancamentoValor');
            if (valorInput) {
                valorInput.addEventListener('input', (e) => {
                    applyMoneyMask(e.target);
                    const metaValorInput = document.getElementById('globalLancamentoMetaValor');
                    if (!metaValorInput || parseMoney(metaValorInput.value) <= 0) {
                        this.syncMetaLinkFields({ preserveAmount: false });
                    }
                    this.atualizarPreviewParcelamento();
                    this.schedulePlanningAlertsRender();
                });
                valorInput.addEventListener('focus', (e) => {
                    if (e.target.value === '0,00') e.target.value = '';
                });
            }

            const metaValorInput = document.getElementById('globalLancamentoMetaValor');
            if (metaValorInput) {
                metaValorInput.addEventListener('input', (e) => {
                    applyMoneyMask(e.target);
                    this.schedulePlanningAlertsRender();
                });
            }

            const metaRealizacaoCheck = document.getElementById('globalLancamentoMetaRealizacao');
            if (metaRealizacaoCheck) {
                metaRealizacaoCheck.addEventListener('change', () => this.schedulePlanningAlertsRender());
            }

            const categoriaSelect = document.getElementById('globalLancamentoCategoria');
            if (categoriaSelect && !categoriaSelect.dataset.planningListenerAttached) {
                categoriaSelect.dataset.planningListenerAttached = '1';
                categoriaSelect.addEventListener('change', () => this.schedulePlanningAlertsRender());
            }

            const contaDestinoSelect = document.getElementById('globalLancamentoContaDestino');
            if (contaDestinoSelect && !contaDestinoSelect.dataset.planningListenerAttached) {
                contaDestinoSelect.dataset.planningListenerAttached = '1';
                contaDestinoSelect.addEventListener('change', () => this.schedulePlanningAlertsRender());
            }

            const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
            if (cartaoSelect) {
                cartaoSelect.addEventListener('change', () => {
                    const temCartao = cartaoSelect.value !== '';
                    const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
                    const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');

                    if (parcelamentoGroup) parcelamentoGroup.style.display = temCartao ? 'block' : 'none';
                    if (!temCartao) {
                        document.getElementById('globalLancamentoParcelado').checked = false;
                        document.getElementById('globalNumeroParcelasGroup').style.display = 'none';
                    }

                    const lembreteGroup = document.getElementById('globalLembreteGroup');
                    const pagoGroup = document.getElementById('globalPagoGroup');
                    if (temCartao) {
                        if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
                        const recorrenteCheck = document.getElementById('globalLancamentoRecorrente');
                        if (recorrenteCheck) recorrenteCheck.checked = false;
                        const recorrenciaDetalhes = document.getElementById('globalRecorrenciaDetalhes');
                        if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
                        if (lembreteGroup) lembreteGroup.style.display = 'none';
                        if (pagoGroup) pagoGroup.style.display = 'none';
                    } else {
                        if (this.tipoAtual !== 'transferencia') {
                            if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
                            if (pagoGroup) pagoGroup.style.display = 'block';
                        }
                    }

                    this.atualizarTextosParcelamento();
                    this.atualizarPreviewParcelamento();
                    this.syncReminderVisibility();
                    this.schedulePlanningAlertsRender();
                });
            }

            const parceladoCheck = document.getElementById('globalLancamentoParcelado');
            if (parceladoCheck) {
                parceladoCheck.addEventListener('change', (e) => {
                    document.getElementById('globalNumeroParcelasGroup').style.display = e.target.checked ? 'block' : 'none';
                    this.atualizarPreviewParcelamento();
                });
            }

            const totalParcelasInput = document.getElementById('globalLancamentoTotalParcelas');
            if (totalParcelasInput) {
                totalParcelasInput.addEventListener('input', () => this.atualizarPreviewParcelamento());
            }

            const form = document.getElementById('globalFormLancamento');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.salvarLancamento();
                });

                // Interceptar Enter nos inputs para avançar etapa em vez de submeter o form
                form.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                        e.preventDefault();
                        e.stopImmediatePropagation();

                        if (this.isPageMode?.() && this.currentStep === 2) {
                            this.salvarLancamento();
                            return;
                        }

                        this.nextStep();
                    }
                });
            }

            // Data e hora padrão
            const hoje = new Date();
            const dataInput = document.getElementById('globalLancamentoData');
            if (dataInput && !dataInput.value) {
                dataInput.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;
            }
            if (dataInput && !dataInput.dataset.planningListenerAttached) {
                dataInput.dataset.planningListenerAttached = '1';
                dataInput.addEventListener('change', () => this.schedulePlanningAlertsRender());
            }
            const horaInput = document.getElementById('globalLancamentoHora');
            if (horaInput && !horaInput.value) {
                horaInput.value = `${String(hoje.getHours()).padStart(2, '0')}:${String(hoje.getMinutes()).padStart(2, '0')}`;
            }

            // Fechar modal com tecla ESC
            document.addEventListener('keydown', (e) => {
                const overlay = document.getElementById('modalLancamentoGlobalOverlay');
                if (e.key === 'Escape' && overlay && overlay.classList.contains('active')) {
                    this.closeModal();
                }
            });
        }
    });
}
