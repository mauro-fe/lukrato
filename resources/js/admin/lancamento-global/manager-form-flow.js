export function attachLancamentoGlobalFormFlowMethods(ManagerClass, dependencies) {
    const {
        parseMoney,
        formatMoney,
        getBaseUrl,
        refreshIcons,
    } = dependencies;

    Object.assign(ManagerClass.prototype, {
        validateCurrentStep() {
            const step = this.currentStep;

            if (step === 2) {
                const contaId = this.contaSelecionada?.id || document.getElementById('globalContaSelect')?.value;
                if (!contaId) {
                    return this.showFieldValidationMessage({
                        field: 'globalContaSelect',
                        text: 'Selecione a conta',
                    });
                }

                this.preencherDescricaoPadraoSeVazia?.();
                const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value);
                if (!valor || valor <= 0) {
                    return this.showFieldValidationMessage({
                        field: 'globalLancamentoValor',
                        text: 'Informe um valor válido',
                    });
                }

                const data = document.getElementById('globalLancamentoData')?.value || '';
                if (!data) {
                    return this.showFieldValidationMessage({
                        field: 'globalLancamentoData',
                        text: 'Informe a data',
                    });
                }

                if (this.tipoAtual === 'transferencia') {
                    const contaDest = document.getElementById('globalLancamentoContaDestino')?.value;
                    if (!contaDest) {
                        return this.showFieldValidationMessage({
                            field: 'globalLancamentoContaDestino',
                            text: 'Selecione a conta de destino',
                        });
                    }
                }

                if (this.tipoAtual === 'despesa') {
                    const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
                    if (cartaoId) {
                        const cartao = this.cartoes.find(c => c.id == cartaoId);
                        if (cartao) {
                            const limiteDisponivel = parseFloat(cartao.limite_disponivel || 0);
                            if (valor > limiteDisponivel) {
                                return this.showFieldValidationMessage({
                                    field: 'globalLancamentoValor',
                                    icon: 'error', title: 'Limite Insuficiente',
                                    html: `<p>O valor da compra (${formatMoney(valor)}) excede o limite disponível do cartão.</p><p><strong>Limite disponível:</strong> ${formatMoney(limiteDisponivel)}</p>`,
                                    confirmButtonText: 'Entendi',
                                });
                            }
                        }
                    }
                }

                if (this.tipoAtual === 'receita') {
                    const formaRec = document.getElementById('globalFormaRecebimento')?.value;
                    if (formaRec === 'estorno_cartao') {
                        const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
                        if (!cartaoId) {
                            return this.showFieldValidationMessage({
                                field: 'globalLancamentoCartaoCredito',
                                text: 'Selecione o cartão para o estorno',
                            });
                        }
                    }
                }
            }

            if (step === 3) {
                if (this.tipoAtual === 'transferencia') {
                    const contaDest = document.getElementById('globalLancamentoContaDestino')?.value;
                    if (!contaDest) {
                        return this.showFieldValidationMessage({
                            field: 'globalLancamentoContaDestino',
                            text: 'Selecione a conta de destino',
                        });
                    }
                }
                // Validate credit card limit if cartão selected
                if (this.tipoAtual === 'despesa') {
                    const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
                    if (cartaoId) {
                        const cartao = this.cartoes.find(c => c.id == cartaoId);
                        if (cartao) {
                            const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value);
                            const limiteDisponivel = parseFloat(cartao.limite_disponivel || 0);
                            if (valor > limiteDisponivel) {
                                return this.showFieldValidationMessage({
                                    field: 'globalLancamentoValor',
                                    icon: 'error', title: 'Limite Insuficiente',
                                    html: `<p>O valor (${formatMoney(valor)}) excede o limite disponível.</p><p><strong>Limite:</strong> ${formatMoney(limiteDisponivel)}</p>`,
                                    confirmButtonText: 'Entendi',
                                });
                            }
                        }
                    }
                }
                // Validate estorno requires card selection
                if (this.tipoAtual === 'receita') {
                    const formaRec = document.getElementById('globalFormaRecebimento')?.value;
                    if (formaRec === 'estorno_cartao') {
                        const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
                        if (!cartaoId) {
                            return this.showFieldValidationMessage({
                                field: 'globalLancamentoCartaoCredito',
                                text: 'Selecione o cartão para o estorno',
                            });
                        }
                    }
                }
            }

            if (step === 4) {
                const data = document.getElementById('globalLancamentoData')?.value || '';
                if (!data) {
                    return this.showFieldValidationMessage({
                        field: 'globalLancamentoData',
                        text: 'Informe a data',
                    });
                }
            }

            // Step 5: validar parcelas e recorrência
            if (step === 5) {
                const parcelado = document.getElementById('globalLancamentoParcelado')?.checked;
                if (parcelado) {
                    const totalParcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 0;
                    if (totalParcelas < 2 || totalParcelas > 48) {
                        return this.showFieldValidationMessage({
                            field: 'globalLancamentoTotalParcelas',
                            text: 'O número de parcelas deve ser entre 2 e 48',
                        });
                    }
                }
                const recorrente = document.getElementById('globalLancamentoRecorrente')?.checked;
                if (recorrente) {
                    const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value;
                    if (modo === 'quantidade') {
                        const total = parseInt(document.getElementById('globalLancamentoRecorrenciaTotal')?.value) || 0;
                        if (total < 2 || total > 120) {
                            return this.showFieldValidationMessage({
                                field: 'globalLancamentoRecorrenciaTotal',
                                text: 'A quantidade de repetições deve ser entre 2 e 120',
                            });
                        }
                    }
                }
            }

            return true;
        },

        showFieldValidationMessage({ field, icon = 'warning', title = 'Atenção', text = '', html = '', confirmButtonText = 'OK' }) {
            Swal.fire({
                icon,
                title,
                text: html ? undefined : text,
                html: html || undefined,
                confirmButtonText,
                customClass: { container: 'swal-above-modal' }
            }).then(() => {
                window.setTimeout(() => {
                    this.focusValidationField(field);
                }, 120);
            });

            return false;
        },

        focusValidationField(field) {
            const target = typeof field === 'string' ? document.getElementById(field) : field;
            if (!target) return;

            const fieldContainer =
                target.closest('.lk-form-group, .lk-page-step-panel') ||
                target.closest('.lk-input-money, .lk-select-wrapper') ||
                target;

            let focusTarget = target;
            if (target.matches?.('select, .lk-select')) {
                focusTarget = target.closest('.lk-select-wrapper')?.querySelector('.lk-custom-select-selected') || target;
            }

            if (!focusTarget.matches?.('input, select, textarea, button, [tabindex]')) {
                focusTarget = focusTarget.querySelector?.('input, select, textarea, button, [tabindex]:not([tabindex="-1"])') || focusTarget;
            }

            if (!(focusTarget instanceof HTMLElement)) return;

            const isPageMode = Boolean(target.closest('#modalLancamentoGlobalOverlay.lk-modal-overlay--page'));

            if (isPageMode) {
                const topbar = document.querySelector('.top-navbar, .lk-header, .lk-topbar');
                const stickyStepper = window.innerWidth <= 900
                    ? document.querySelector('.lancamento-create-page__stepper')
                    : null;
                const topbarHeight = topbar instanceof HTMLElement ? topbar.getBoundingClientRect().height : 0;
                const stepperHeight = stickyStepper instanceof HTMLElement ? stickyStepper.getBoundingClientRect().height : 0;
                const offset = topbarHeight + stepperHeight + 24;
                const rect = fieldContainer.getBoundingClientRect();
                const targetTop = Math.max(window.scrollY + rect.top - offset, 0);

                window.scrollTo({ top: targetTop, behavior: 'smooth' });
            } else {
                fieldContainer.scrollIntoView?.({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            }

            if (!focusTarget.matches('input, select, textarea, button, a, [tabindex]')) {
                focusTarget.tabIndex = -1;
            }

            window.setTimeout(() => {
                focusTarget.focus?.({ preventScroll: true });

                if ((focusTarget.tagName === 'INPUT' || focusTarget.tagName === 'TEXTAREA') && typeof focusTarget.select === 'function') {
                    focusTarget.select();
                }
            }, 40);
        },

        resolveDescricaoPadrao() {
            if (this.tipoAtual === 'receita') return 'Receita';
            if (this.tipoAtual === 'despesa') return 'Despesa';
            if (this.tipoAtual === 'transferencia') return 'Transferência';

            return 'Lançamento';
        },

        preencherDescricaoPadraoSeVazia() {
            const descricaoInput = document.getElementById('globalLancamentoDescricao');
            if (!descricaoInput) return;

            if (descricaoInput.value.trim() === '') {
                descricaoInput.value = this.resolveDescricaoPadrao();
            }
        },

        resetarFormulario() {
            const form = document.getElementById('globalFormLancamento');
            if (form) form.reset();

            const valorInput = document.getElementById('globalLancamentoValor');
            if (valorInput) valorInput.value = '0,00';

            const hoje = new Date();
            const dataInput = document.getElementById('globalLancamentoData');
            if (dataInput) dataInput.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;

            const horaInput = document.getElementById('globalLancamentoHora');
            if (horaInput) horaInput.value = '';

            // Parcelamento
            const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
            if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
            const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
            if (numParcelasGroup) numParcelasGroup.style.display = 'none';

            // Tipo agendamento (legacy)
            const tipoAgGroup = document.getElementById('globalTipoAgendamentoGroup');
            if (tipoAgGroup) tipoAgGroup.style.display = 'none';
            const tipoAgInput = document.getElementById('globalLancamentoTipoAgendamento');
            if (tipoAgInput) tipoAgInput.value = 'despesa';

            // Recorrência
            const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
            const recorrenciaDetalhes = document.getElementById('globalRecorrenciaDetalhes');
            if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
            const recorrenteCheck = document.getElementById('globalLancamentoRecorrente');
            if (recorrenteCheck) recorrenteCheck.checked = false;
            const recorrenciaTotalGroup = document.getElementById('globalRecorrenciaTotalGroup');
            if (recorrenciaTotalGroup) recorrenciaTotalGroup.style.display = 'none';
            const recorrenciaFimGroup = document.getElementById('globalRecorrenciaFimGroup');
            if (recorrenciaFimGroup) recorrenciaFimGroup.style.display = 'none';
            const modoRadios = document.querySelectorAll('input[name="global_recorrencia_modo"]');
            modoRadios.forEach(r => r.checked = r.value === 'infinito');

            // Pago
            const pagoGroup = document.getElementById('globalPagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'none';
            const pagoCheck = document.getElementById('globalLancamentoPago');
            if (pagoCheck) pagoCheck.checked = true;

            const metaGroup = document.getElementById('globalMetaGroup');
            if (metaGroup) metaGroup.style.display = 'none';
            const metaSelect = document.getElementById('globalLancamentoMeta');
            if (metaSelect) metaSelect.value = '';
            const metaValorGroup = document.getElementById('globalMetaValorGroup');
            if (metaValorGroup) metaValorGroup.style.display = 'none';
            const metaValorInput = document.getElementById('globalLancamentoMetaValor');
            if (metaValorInput) metaValorInput.value = '';
            const metaRealizacaoGroup = document.getElementById('globalMetaRealizacaoGroup');
            if (metaRealizacaoGroup) metaRealizacaoGroup.style.display = 'none';
            const metaRealizacaoCheck = document.getElementById('globalLancamentoMetaRealizacao');
            if (metaRealizacaoCheck) metaRealizacaoCheck.checked = false;

            // Lembrete
            const lembreteGroup = document.getElementById('globalLembreteGroup');
            if (lembreteGroup) lembreteGroup.style.display = 'none';
            const tempoAvisoSelect = document.getElementById('globalLancamentoTempoAviso');
            if (tempoAvisoSelect) tempoAvisoSelect.value = '';
            const canaisInline = document.getElementById('globalCanaisNotificacaoInline');
            if (canaisInline) canaisInline.style.display = 'none';

            // Fatura estorno
            const faturaGroup = document.getElementById('globalFaturaEstornoGroup');
            if (faturaGroup) faturaGroup.style.display = 'none';
            this.isEstornoCartao = false;

            // Subcategoria
            this.resetSubcategoriaSelect();

            // Forma de pagamento
            this.resetarFormaPagamento();
            this.tipoAtual = null;
            this.atualizarTextosParcelamento();
            this.atualizarPreviewParcelamento();

            // Reset wizard state
            this.currentStep = 1;
            this.totalSteps = this.isPageMode?.() ? 3 : 5;
            this.clearPlanningAlerts();
            this.syncEnhancedSelects();
        },

        async mostrarFormulario(tipo) {
            if (this.contas.length === 0 && !this.isPageMode?.()) {
                const result = await Swal.fire({
                    icon: 'info',
                    title: 'Nenhuma conta cadastrada',
                    html: `<p>Você ainda não possui nenhuma conta bancária cadastrada.</p>
                       <p class="text-muted mt-2">É necessário criar pelo menos uma conta para registrar lançamentos.</p>`,
                    showCancelButton: true,
                    confirmButtonText: '<i data-lucide="plus" style="width:16px;height:16px;display:inline-block;"></i> Criar Conta',
                    cancelButtonText: 'Agora não',
                    confirmButtonColor: 'var(--color-primary)',
                    customClass: { container: 'swal-above-modal', confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' }
                });
                if (result.isConfirmed) {
                    this.closeModal();
                    if (typeof window.ContasManager !== 'undefined' && typeof window.ContasManager.abrirModalNovaConta === 'function') {
                        window.ContasManager.abrirModalNovaConta();
                    } else {
                        window.location.href = getBaseUrl() + 'contas';
                    }
                }
                return;
            }

            if (!this.contaSelecionada && !this.isPageMode?.()) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione uma conta primeiro!', customClass: { container: 'swal-above-modal' } });
                return;
            }

            // Guard: transferência requer pelo menos 2 contas
            if (tipo === 'transferencia' && this.contas.length < 2 && !this.isPageMode?.()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Não é possível transferir',
                    text: 'Você precisa ter pelo menos duas contas cadastradas para realizar uma transferência.',
                    confirmButtonText: 'Criar outra conta',
                    showCancelButton: true,
                    cancelButtonText: 'Agora não',
                    customClass: { container: 'swal-above-modal' }
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href = getBaseUrl() + 'contas';
                    }
                });
                return;
            }

            if (!this._dataLoaded) {
                await this.carregarDados();
            }

            // Restaurar seleção da conta no select após possível re-fetch
            if (this.contaSelecionada) {
                const select = document.getElementById('globalContaSelect');
                if (select && select.value !== String(this.contaSelecionada.id)) {
                    select.value = this.contaSelecionada.id;
                }
            }

            this.syncEnhancedSelects();

            this.tipoAtual = tipo;

            const tipoInput = document.getElementById('globalLancamentoTipo');
            if (tipoInput) tipoInput.value = tipo;
            const contaIdInput = document.getElementById('globalLancamentoContaId');
            if (contaIdInput) contaIdInput.value = this.contaSelecionada?.id ?? '';

            // Set total steps based on type
            this.totalSteps = this.isPageMode?.() ? 3 : (tipo === 'transferencia' ? 4 : 5);

            this.configurarCamposPorTipo(tipo);

            const titulos = { receita: 'Nova Receita', despesa: 'Nova Despesa', transferencia: 'Nova Transferência' };
            const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
            if (tituloEl) tituloEl.textContent = titulos[tipo] || 'Nova Movimentação';
            this.syncQuickTypeHeading?.(tipo);
            this.resetQuickOptions?.();



            // Update step 3 question text
            const step3Title = document.getElementById('globalStep3Title');
            if (tipo === 'transferencia') {
                if (step3Title) step3Title.textContent = 'Ajustes opcionais';
            } else {
                if (step3Title) step3Title.textContent = 'Ajustes opcionais';
            }

            // Update step 4 question text
            const step4Title = document.getElementById('globalStep4Title');
            if (tipo === 'receita') {
                if (step4Title) step4Title.textContent = 'Quando recebeu?';
            } else if (tipo === 'transferencia') {
                if (step4Title) step4Title.textContent = 'Quando será a transferência?';
            } else {
                if (step4Title) step4Title.textContent = 'Quando aconteceu?';
            }

            // For transferência: step 4 is the last, show Salvar instead of Próximo
            const step4NavRight = document.getElementById('globalStep4NavRight');
            if (step4NavRight) {
                if (tipo === 'transferencia') {
                    step4NavRight.innerHTML = `
                    <button type="submit" class="lk-btn lk-btn-primary" form="globalFormLancamento">
                        <i data-lucide="check"></i>
                        Salvar Transferência
                    </button>`;
                } else {
                    step4NavRight.innerHTML = `
                    <button type="button" class="lk-btn lk-btn-primary" onclick="lancamentoGlobalManager.nextStep()">
                        Próximo
                        <i data-lucide="arrow-right"></i>
                    </button>`;
                }
                refreshIcons();
            }

            // Navigate to step 2
            this.goToStep(2);
            this.schedulePlanningAlertsRender();
        },

        configurarCamposPorTipo(tipo) {
            // Header color
            const headerGradient = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-header-gradient');
            if (headerGradient) {
                headerGradient.classList.remove('receita', 'despesa', 'transferencia');
                headerGradient.classList.add(tipo);

                if (this.isPageMode?.()) {
                    headerGradient.style.removeProperty('background');
                } else {
                    const colors = {
                        receita: 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
                        despesa: 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)',
                        transferencia: 'linear-gradient(135deg, #3498db, #2980b9)',
                    };
                    if (colors[tipo]) headerGradient.style.setProperty('background', colors[tipo], 'important');
                }
            }

            // Step 3 visibility per type
            // Conta Destino (transfer only)
            const contaDestinoGroup = document.getElementById('globalContaDestinoGroup');
            if (contaDestinoGroup) contaDestinoGroup.style.display = tipo === 'transferencia' ? 'block' : 'none';
            if (tipo === 'transferencia') this.preencherContasDestino();

            // Forma de pagamento / recebimento
            const formaPagamentoGroup = document.getElementById('globalFormaPagamentoGroup');
            const formaRecebimentoGroup = document.getElementById('globalFormaRecebimentoGroup');
            const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
            if (formaPagamentoGroup) formaPagamentoGroup.style.display = tipo === 'despesa' ? 'block' : 'none';
            if (formaRecebimentoGroup) formaRecebimentoGroup.style.display = tipo === 'receita' ? 'block' : 'none';
            if (cartaoGroup) cartaoGroup.style.display = 'none';
            this.resetarFormaPagamento();
            if (tipo === 'despesa') this.preencherCartoes();

            // Step 5 visibility per type
            // Categoria
            this.preencherCategorias(tipo === 'receita' ? 'receita' : 'despesa');

            const showStep5Fields = (tipo === 'receita' || tipo === 'despesa');
            const categoriaGroup = document.getElementById('globalCategoriaGroup');
            const subcategoriaGroup = document.getElementById('globalSubcategoriaGroup');
            if (categoriaGroup) categoriaGroup.style.display = showStep5Fields ? 'block' : 'none';
            if (subcategoriaGroup) subcategoriaGroup.style.display = 'none';

            // Recorrência e Lembrete (receita/despesa only)
            const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
            const lembreteGroup = document.getElementById('globalLembreteGroup');
            const recorrenciaDetalhes = document.getElementById('globalRecorrenciaDetalhes');
            const canaisNotificacaoInline = document.getElementById('globalCanaisNotificacaoInline');
            const recorrenteCheck = document.getElementById('globalLancamentoRecorrente');
            const tempoAvisoSelect = document.getElementById('globalLancamentoTempoAviso');

            if (recorrenciaGroup) recorrenciaGroup.style.display = showStep5Fields ? 'block' : 'none';
            if (lembreteGroup) lembreteGroup.style.display = showStep5Fields ? 'block' : 'none';
            if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
            if (canaisNotificacaoInline) canaisNotificacaoInline.style.display = 'none';
            if (recorrenteCheck) recorrenteCheck.checked = false;
            if (tempoAvisoSelect) tempoAvisoSelect.value = '';

            // Parcelamento (in step 3 for credit card payments)
            const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
            const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
            const parceladoCheck = document.getElementById('globalLancamentoParcelado');
            if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
            if (numParcelasGroup) numParcelasGroup.style.display = 'none';
            if (parceladoCheck) parceladoCheck.checked = false;
            this.atualizarTextosParcelamento();

            const pagoGroup = document.getElementById('globalPagoGroup');
            const pagoCheck = document.getElementById('globalLancamentoPago');
            if (pagoGroup) pagoGroup.style.display = showStep5Fields ? 'block' : 'none';
            if (pagoCheck) pagoCheck.checked = true;
            const quickSaveButton = document.getElementById('globalBtnQuickSave');
            if (quickSaveButton) quickSaveButton.style.display = showStep5Fields ? 'inline-flex' : 'none';

            const metaGroup = document.getElementById('globalMetaGroup');
            const metaSelect = document.getElementById('globalLancamentoMeta');
            const metaHelper = document.getElementById('globalMetaHelperText');
            const podeVincularMeta = tipo === 'receita' || tipo === 'despesa' || tipo === 'transferencia';
            if (metaGroup) metaGroup.style.display = podeVincularMeta ? 'block' : 'none';
            if (metaSelect) metaSelect.value = '';
            if (metaHelper) {
                if (tipo === 'transferencia') {
                    metaHelper.textContent = 'Deseja guardar parte deste valor em uma meta?';
                } else if (tipo === 'despesa') {
                    metaHelper.textContent = 'Usou dinheiro guardado em meta nesta despesa?';
                } else {
                    metaHelper.textContent = 'Deseja guardar parte desta receita em uma meta?';
                }
            }
            this.syncMetaLinkFields({ preserveAmount: false });

            const pagoLabel = document.getElementById('globalPagoLabel');
            const pagoHelper = document.getElementById('globalPagoHelperText');
            if (tipo === 'receita') {
                if (pagoLabel) pagoLabel.textContent = 'Já foi recebido';
                if (pagoHelper) pagoHelper.textContent = 'Desmarque se ainda não foi recebido.';
            } else {
                if (pagoLabel) pagoLabel.textContent = 'Já foi pago';
                if (pagoHelper) pagoHelper.textContent = 'Desmarque se ainda não foi pago.';
            }

            // Recorrência sub-groups
            const totalGroup = document.getElementById('globalRecorrenciaTotalGroup');
            const fimGroup = document.getElementById('globalRecorrenciaFimGroup');
            if (totalGroup) totalGroup.style.display = 'none';
            if (fimGroup) fimGroup.style.display = 'none';

            const radioInfinito = document.getElementById('globalRecorrenciaRadioInfinito');
            const defaultModo = 'infinito';
            if (radioInfinito) radioInfinito.style.display = '';
            const radios = document.querySelectorAll('input[name="global_recorrencia_modo"]');
            radios.forEach(r => r.checked = r.value === defaultModo);

            this.syncPagoRecorrenciaState();
            this.configurarEventosLembrete();
            this.syncReminderVisibility();
            this.schedulePlanningAlertsRender();
        },

        toggleRecorrencia() {
            const checkbox = document.getElementById('globalLancamentoRecorrente');
            const detalhes = document.getElementById('globalRecorrenciaDetalhes');
            if (detalhes) detalhes.style.display = checkbox?.checked ? 'block' : 'none';
            if (!checkbox?.checked) {
                const totalGroup = document.getElementById('globalRecorrenciaTotalGroup');
                const fimGroup = document.getElementById('globalRecorrenciaFimGroup');
                if (totalGroup) totalGroup.style.display = 'none';
                if (fimGroup) fimGroup.style.display = 'none';
                const defaultModo = 'infinito';
                document.querySelectorAll('input[name="global_recorrencia_modo"]').forEach(r => r.checked = r.value === defaultModo);
            }

            this.syncPagoRecorrenciaState();
            this.syncReminderVisibility();
            this.schedulePlanningAlertsRender();
        },

        syncPagoRecorrenciaState() {
            const recorrente = document.getElementById('globalLancamentoRecorrente')?.checked === true;
            const pagoCheck = document.getElementById('globalLancamentoPago');
            const pagoGroup = document.getElementById('globalPagoGroup');
            const pagoHelper = document.getElementById('globalPagoHelperText');

            if (!pagoCheck || !pagoGroup || !pagoHelper) {
                return;
            }

            if (recorrente) {
                pagoCheck.checked = false;
                pagoCheck.disabled = true;
                pagoGroup.classList.add('lk-form-group-disabled');
                pagoHelper.textContent = 'Recorrências começam como pendentes. Você pode marcar cada ocorrência como paga depois.';
                this.schedulePlanningAlertsRender();
                return;
            }

            pagoCheck.disabled = false;
            pagoGroup.classList.remove('lk-form-group-disabled');
            pagoHelper.textContent = this.tipoAtual === 'receita'
                ? 'Desmarque se ainda não foi recebido.'
                : 'Desmarque se ainda não foi pago.';
            this.schedulePlanningAlertsRender();
        },

        toggleRecorrenciaFim() {
            const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value || 'infinito';
            const totalGroup = document.getElementById('globalRecorrenciaTotalGroup');
            const fimGroup = document.getElementById('globalRecorrenciaFimGroup');
            if (totalGroup) totalGroup.style.display = modo === 'quantidade' ? 'block' : 'none';
            if (fimGroup) fimGroup.style.display = modo === 'data' ? 'block' : 'none';
        },

        toggleAssinaturaCartao() {
            const checkbox = document.getElementById('globalLancamentoAssinaturaCartao');
            const detalhes = document.getElementById('globalAssinaturaCartaoDetalhes');
            if (detalhes) detalhes.style.display = checkbox?.checked ? 'block' : 'none';

            // Assinatura e parcelamento são mutuamente exclusivos
            if (checkbox?.checked) {
                const parceladoCheck = document.getElementById('globalLancamentoParcelado');
                if (parceladoCheck) parceladoCheck.checked = false;
                const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
                if (numParcelasGroup) numParcelasGroup.style.display = 'none';
            }
        },

        toggleAssinaturaCartaoFim() {
            const modo = document.querySelector('input[name="global_assinatura_modo"]:checked')?.value || 'infinito';
            const fimGroup = document.getElementById('globalAssinaturaCartaoFimGroup');
            if (fimGroup) fimGroup.style.display = modo === 'data' ? 'block' : 'none';
        },

        configurarEventosLembrete() {
            const tempoAviso = document.getElementById('globalLancamentoTempoAviso');
            if (tempoAviso && !tempoAviso._lkListenerAdded) {
                tempoAviso.addEventListener('change', () => {
                    const canaisDiv = document.getElementById('globalCanaisNotificacaoInline');
                    if (canaisDiv) canaisDiv.style.display = tempoAviso.value ? 'block' : 'none';
                });
                tempoAviso._lkListenerAdded = true;
            }

            const pagoCheck = document.getElementById('globalLancamentoPago');
            if (pagoCheck && !pagoCheck._lkListenerAdded) {
                pagoCheck.addEventListener('change', () => {
                    this.syncReminderVisibility();
                    this.schedulePlanningAlertsRender();
                });
                pagoCheck._lkListenerAdded = true;
            }
        },

        clearReminderFields() {
            const tempoAviso = document.getElementById('globalLancamentoTempoAviso');
            const canalInapp = document.getElementById('globalCanalInapp');
            const canalEmail = document.getElementById('globalCanalEmail');
            const canaisDiv = document.getElementById('globalCanaisNotificacaoInline');

            if (tempoAviso) tempoAviso.value = '';
            if (canalInapp) canalInapp.checked = true;
            if (canalEmail) canalEmail.checked = true;
            if (canaisDiv) canaisDiv.style.display = 'none';
            this.syncEnhancedSelects();
        },

        syncReminderVisibility() {
            const lembreteGroup = document.getElementById('globalLembreteGroup');
            if (!lembreteGroup) return;

            const tipo = this.tipoAtual;
            const pago = document.getElementById('globalLancamentoPago')?.checked === true;
            const cartaoSelecionado = !!document.getElementById('globalLancamentoCartaoCredito')?.value;
            const formaRecebimento = document.getElementById('globalFormaRecebimento')?.value;
            const isEstornoCartao = formaRecebimento === 'estorno_cartao';

            const canShowByType = (tipo === 'receita' || tipo === 'despesa');
            const shouldShow = canShowByType && !pago && !cartaoSelecionado && !isEstornoCartao;

            if (shouldShow) {
                lembreteGroup.style.display = 'block';
                return;
            }

            lembreteGroup.style.display = 'none';
            this.clearReminderFields();
        },

        selecionarTipoAgendamento(tipo) {
            const input = document.getElementById('globalLancamentoTipoAgendamento');
            if (input) input.value = tipo;
            document.querySelectorAll('#globalTipoAgendamentoGroup .lk-btn-tipo-ag').forEach(btn => {
                btn.classList.remove('active');
                if (btn.classList.contains(`lk-btn-tipo-${tipo}`)) btn.classList.add('active');
            });
            this.preencherCategorias(tipo);
            this.schedulePlanningAlertsRender();
        }
    });
}
