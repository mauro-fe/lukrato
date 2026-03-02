/**
 * ============================================================================
 * LUKRATO — Contas / Lancamento
 * ============================================================================
 * Transaction creation modal: open/close, type selection, payment forms,
 * credit card handling, installments, subscriptions, recurrence, and
 * the full handleLancamentoSubmit flow.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';
import { setupMoneyMask, setMoneyValue, getMoneyValue, applyMoneyMask } from '../shared/money-mask.js';
import { calcularRecorrenciaFim } from '../shared/utils.js';

// ─── ContasLancamento ────────────────────────────────────────────────────────

export const ContasLancamento = {

    // ── Wizard State ─────────────────────────────────────────────────────────
    _currentStep: 1,
    _totalSteps: 5,
    _tipoAtual: null,

    /**
     * Abrir modal de lançamento com histórico
     */
    async openLancamentoModal(contaId, nomeConta) {
        const conta = STATE.contas.find(c => c.id == contaId);
        if (!conta) {
            Utils.showToast('Conta não encontrada', 'error');
            return;
        }

        const modalOverlay = document.getElementById('modalLancamentoOverlay');
        if (!modalOverlay) {
            Utils.showToast('Modal de lançamento não encontrado', 'error');
            return;
        }

        // Preencher informações da conta
        const saldo = conta.saldoAtual ?? conta.saldo_atual ?? conta.saldo ?? 0;
        document.getElementById('lancamentoContaNome').textContent = conta.nome;
        document.getElementById('lancamentoContaSaldo').textContent = Utils.formatCurrency(saldo);

        // Armazenar conta selecionada
        STATE.contaSelecionadaLancamento = conta;

        // Carregar histórico recente
        await Modules.API.carregarHistoricoRecente(contaId);

        // Init wizard (show step 1)
        ContasLancamento.initWizard();

        // Mostrar modal
        modalOverlay.classList.add('active');
    },

    // ── Wizard Step Engine ────────────────────────────────────────────────────
    initWizard() {
        ContasLancamento._currentStep = 1;
        ContasLancamento._tipoAtual = null;
        ContasLancamento._totalSteps = 5;

        // Hide progress
        const progress = document.getElementById('contasWizardProgress');
        if (progress) progress.style.display = 'none';

        // Reset form
        const form = document.getElementById('formLancamento');
        if (form) form.reset();

        // Show step 1, hide others
        for (let i = 1; i <= 5; i++) {
            const step = document.getElementById(`contasStep${i}`);
            if (step) {
                step.classList.remove('active');
                step.style.display = 'none';
            }
        }
        const step1 = document.getElementById('contasStep1');
        if (step1) {
            step1.classList.add('active');
            step1.style.display = '';
        }

        // Reset header
        const titulo = document.getElementById('modalLancamentoTitulo');
        if (titulo) titulo.textContent = 'Nova Movimentação';
        const headerGradient = document.querySelector('#modalLancamentoOverlay .lk-modal-header-gradient');
        if (headerGradient) headerGradient.style.removeProperty('background');
    },

    renderProgress() {
        const container = document.getElementById('contasWizardProgress');
        if (!container) return;

        if (ContasLancamento._currentStep <= 1) {
            container.style.display = 'none';
            return;
        }
        container.style.display = 'flex';

        const dotCount = ContasLancamento._totalSteps - 1;
        let html = '';
        for (let i = 0; i < dotCount; i++) {
            const stepNum = i + 2;
            let stateClass = 'pending';
            if (stepNum < ContasLancamento._currentStep) stateClass = 'completed';
            else if (stepNum === ContasLancamento._currentStep) stateClass = 'active';

            if (i > 0) {
                const lineClass = stepNum <= ContasLancamento._currentStep ? 'completed' : '';
                html += `<div class="lk-wizard-line ${lineClass}"></div>`;
            }
            html += `<div class="lk-wizard-dot ${stateClass}"></div>`;
        }
        container.innerHTML = html;
    },

    goToStep(n) {
        if (n < 1 || n > ContasLancamento._totalSteps) return;

        const prev = ContasLancamento._currentStep;
        ContasLancamento._currentStep = n;

        for (let i = 1; i <= 5; i++) {
            const step = document.getElementById(`contasStep${i}`);
            if (!step) continue;
            if (i === prev && i !== n) {
                step.classList.remove('active');
                step.style.display = 'none';
            }
        }

        const newStep = document.getElementById(`contasStep${n}`);
        if (newStep) {
            newStep.style.display = '';
            newStep.classList.add('active');
        }

        ContasLancamento.renderProgress();

        // Scroll modal body to top
        const body = document.querySelector('#modalLancamentoOverlay .lk-modal-body-modern');
        if (body) body.scrollTop = 0;

        refreshIcons();
    },

    nextStep() {
        if (!ContasLancamento.validateCurrentStep()) return;

        let next = ContasLancamento._currentStep + 1;

        // For transferência: skip step 5 (category/recurrence)
        if (ContasLancamento._tipoAtual === 'transferencia' && next === 5) {
            next = ContasLancamento._totalSteps + 1;
        }

        if (next > ContasLancamento._totalSteps) {
            // Submit the form
            const form = document.getElementById('formLancamento');
            if (form) form.requestSubmit();
            return;
        }
        ContasLancamento.goToStep(next);
    },

    prevStep() {
        let prev = ContasLancamento._currentStep - 1;
        if (prev < 1) prev = 1;
        ContasLancamento.goToStep(prev);
    },

    skipAndSave() {
        const form = document.getElementById('formLancamento');
        if (form) form.requestSubmit();
    },

    validateCurrentStep() {
        const step = ContasLancamento._currentStep;

        if (step === 2) {
            const descricao = document.getElementById('lancamentoDescricao')?.value.trim() || '';
            const valor = Utils.parseMoneyInput(document.getElementById('lancamentoValor')?.value || '0');
            if (!descricao) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe a descrição', customClass: { container: 'swal-above-modal' } });
                return false;
            }
            if (!valor || valor <= 0) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe um valor válido', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }

        if (step === 3) {
            if (ContasLancamento._tipoAtual === 'transferencia') {
                const contaDest = document.getElementById('lancamentoContaDestino')?.value;
                if (!contaDest) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione a conta de destino', customClass: { container: 'swal-above-modal' } });
                    return false;
                }
            }
            // Validate credit card limit
            if (ContasLancamento._tipoAtual === 'despesa') {
                const cartaoId = document.getElementById('lancamentoCartaoCredito')?.value;
                if (cartaoId && STATE.cartoes && Array.isArray(STATE.cartoes)) {
                    const cartao = STATE.cartoes.find(c => c.id == cartaoId);
                    if (cartao) {
                        const valor = Utils.parseMoneyInput(document.getElementById('lancamentoValor')?.value || '0');
                        const limiteDisponivel = parseFloat(cartao.limite_disponivel || 0);
                        if (valor > limiteDisponivel) {
                            Swal.fire({
                                icon: 'error', title: 'Limite Insuficiente',
                                html: `<p>O valor (${Utils.formatCurrency(valor)}) excede o limite disponível.</p><p><strong>Limite:</strong> ${Utils.formatCurrency(limiteDisponivel)}</p>`,
                                confirmButtonText: 'Entendi', customClass: { container: 'swal-above-modal' }
                            });
                            return false;
                        }
                    }
                }
            }
        }

        if (step === 4) {
            const data = document.getElementById('lancamentoData')?.value || '';
            if (!data) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe a data', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }

        // Step 5: validar parcelas e recorrência
        if (step === 5) {
            const parcelado = document.getElementById('parceladoCheck')?.checked;
            if (parcelado) {
                const totalParcelas = parseInt(document.getElementById('totalParcelas')?.value) || 0;
                if (totalParcelas < 2 || totalParcelas > 48) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'O número de parcelas deve ser entre 2 e 48', customClass: { container: 'swal-above-modal' } });
                    return false;
                }
            }
            const recorrente = document.getElementById('recorrenteCheck')?.checked;
            if (recorrente) {
                const modo = document.querySelector('input[name="recorrencia_modo"]:checked')?.value;
                if (modo === 'quantidade') {
                    const total = parseInt(document.getElementById('recorrenciaTotal')?.value) || 0;
                    if (total < 2 || total > 120) {
                        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'A quantidade de repetições deve ser entre 2 e 120', customClass: { container: 'swal-above-modal' } });
                        return false;
                    }
                }
            }
        }

        return true;
    },

    /**
     * Mostrar formulário de lançamento (navigates to step 2)
     */
    mostrarFormularioLancamento(tipo) {
        ContasLancamento._tipoAtual = tipo;

        // Preencher dados
        const elContaId = document.getElementById('lancamentoContaId');
        const elTipo = document.getElementById('lancamentoTipo');
        const elData = document.getElementById('lancamentoData');

        if (elContaId) elContaId.value = STATE.contaSelecionadaLancamento?.id ?? '';
        if (elTipo) elTipo.value = tipo;

        // Data e hora de agora
        const hoje = new Date();
        if (elData) elData.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;
        const horaField = document.getElementById('lancamentoHora');
        if (horaField) horaField.value = `${String(hoje.getHours()).padStart(2, '0')}:${String(hoje.getMinutes()).padStart(2, '0')}`;

        // Set total steps based on type
        ContasLancamento._totalSteps = tipo === 'transferencia' ? 4 : 5;

        // ── Configure header ──
        const titulo = document.getElementById('modalLancamentoTitulo');
        const headerGradient = document.querySelector('#modalLancamentoOverlay .lk-modal-header-gradient');

        if (tipo === 'receita') {
            if (titulo) titulo.textContent = 'Nova Receita';
            if (headerGradient) headerGradient.style.setProperty('background', 'linear-gradient(135deg, #28a745 0%, #20c997 100%)', 'important');
        } else if (tipo === 'despesa') {
            if (titulo) titulo.textContent = 'Nova Despesa';
            if (headerGradient) headerGradient.style.setProperty('background', 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)', 'important');
        } else if (tipo === 'transferencia') {
            if (titulo) titulo.textContent = 'Nova Transferência';
            if (headerGradient) headerGradient.style.setProperty('background', 'linear-gradient(135deg, #17a2b8 0%, #3498db 100%)', 'important');
        }

        // ── Update step 2 question ──
        const step2Title = document.getElementById('contasStep2Title');
        const step2Subtitle = document.getElementById('contasStep2Subtitle');
        if (tipo === 'receita') {
            if (step2Title) step2Title.textContent = 'O que você recebeu?';
            if (step2Subtitle) step2Subtitle.textContent = 'Descreva e informe o valor recebido';
        } else if (tipo === 'transferencia') {
            if (step2Title) step2Title.textContent = 'Quanto quer transferir?';
            if (step2Subtitle) step2Subtitle.textContent = 'Descreva e informe o valor da transferência';
        } else {
            if (step2Title) step2Title.textContent = 'Com o que você gastou?';
            if (step2Subtitle) step2Subtitle.textContent = 'Descreva e informe o valor';
        }

        // ── Update step 3 question ──
        const step3Title = document.getElementById('contasStep3Title');
        const step3Subtitle = document.getElementById('contasStep3Subtitle');
        if (tipo === 'receita') {
            if (step3Title) step3Title.textContent = 'Como você recebeu?';
            if (step3Subtitle) step3Subtitle.textContent = 'Escolha a forma de recebimento';
        } else if (tipo === 'transferencia') {
            if (step3Title) step3Title.textContent = 'Para onde vai?';
            if (step3Subtitle) step3Subtitle.textContent = 'Escolha a conta de destino';
        } else {
            if (step3Title) step3Title.textContent = 'Como você pagou?';
            if (step3Subtitle) step3Subtitle.textContent = 'Escolha a forma de pagamento';
        }

        // ── Update step 4 question ──
        const step4Title = document.getElementById('contasStep4Title');
        if (tipo === 'receita') {
            if (step4Title) step4Title.textContent = 'Quando recebeu?';
        } else if (tipo === 'transferencia') {
            if (step4Title) step4Title.textContent = 'Quando será a transferência?';
        } else {
            if (step4Title) step4Title.textContent = 'Quando aconteceu?';
        }

        // ── Step 4 nav: Salvar for transfer (last step) vs Próximo ──
        const step4NavRight = document.getElementById('contasStep4NavRight');
        if (step4NavRight) {
            if (tipo === 'transferencia') {
                step4NavRight.innerHTML = `
                    <button type="submit" class="lk-btn lk-btn-primary">
                        <i data-lucide="check"></i>
                        Salvar Transferência
                    </button>`;
            } else {
                step4NavRight.innerHTML = `
                    <button type="button" class="lk-btn lk-btn-primary" onclick="contasManager.nextStep()">
                        Próximo
                        <i data-lucide="arrow-right"></i>
                    </button>`;
            }
            refreshIcons();
        }

        // ── Configure fields per type (step 3) ──
        const contaDestinoGroup = document.getElementById('contaDestinoGroup');
        const formaPagamentoGroup = document.getElementById('formaPagamentoGroup');
        const formaRecebimentoGroup = document.getElementById('formaRecebimentoGroup');
        const cartaoCreditoGroup = document.getElementById('cartaoCreditoGroup');

        if (contaDestinoGroup) contaDestinoGroup.style.display = tipo === 'transferencia' ? 'block' : 'none';
        if (formaPagamentoGroup) formaPagamentoGroup.style.display = tipo === 'despesa' ? 'block' : 'none';
        if (formaRecebimentoGroup) formaRecebimentoGroup.style.display = tipo === 'receita' ? 'block' : 'none';
        if (cartaoCreditoGroup) cartaoCreditoGroup.classList.remove('active');
        ContasLancamento.resetarFormaPagamento();

        if (tipo === 'transferencia') Modules.API.preencherContasDestino();
        if (tipo === 'despesa') Modules.API.carregarCartoesCredito();

        // ── Configure fields step 5 ──
        const showStep5 = (tipo === 'receita' || tipo === 'despesa');
        const categoriaGroup = document.getElementById('categoriaGroup');
        const subcategoriaGroup = document.getElementById('subcategoriaGroup');
        const recorrenciaGroup = document.getElementById('recorrenciaGroup');
        const lembreteGroup = document.getElementById('lembreteGroup');

        if (tipo !== 'transferencia') {
            Modules.API.preencherCategorias(tipo);
            if (categoriaGroup) categoriaGroup.style.display = 'block';
        } else {
            if (categoriaGroup) categoriaGroup.style.display = 'none';
        }
        if (subcategoriaGroup) subcategoriaGroup.style.display = 'none';
        Modules.API.resetSubcategoriaSelect();

        // Recorrência
        const recorrenciaDetalhes = document.getElementById('recorrenciaDetalhes');
        const canaisNotificacaoInline = document.getElementById('canaisNotificacaoInline');
        const lancamentoRecorrente = document.getElementById('lancamentoRecorrente');
        const lancamentoTempoAviso = document.getElementById('lancamentoTempoAviso');

        if (recorrenciaGroup) recorrenciaGroup.style.display = showStep5 ? 'block' : 'none';
        if (lembreteGroup) lembreteGroup.style.display = showStep5 ? 'block' : 'none';
        if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
        if (canaisNotificacaoInline) canaisNotificacaoInline.style.display = 'none';
        if (lancamentoRecorrente) lancamentoRecorrente.checked = false;
        if (lancamentoTempoAviso) lancamentoTempoAviso.value = '';

        // Parcelamento (in step 3, controlled by forma pagamento)
        const parcelamentoGroup = document.getElementById('parcelamentoGroup');
        const numParcelasGroup = document.getElementById('numeroParcelasGroup');
        const parceladoCheck = document.getElementById('lancamentoParcelado');
        if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
        if (numParcelasGroup) numParcelasGroup.style.display = 'none';
        if (parceladoCheck) parceladoCheck.checked = false;

        // ── Step 4: Pago toggle ──
        const pagoGroup = document.getElementById('pagoGroup');
        const pagoCheckbox = document.getElementById('lancamentoPago');
        if (pagoGroup) pagoGroup.style.display = showStep5 ? 'block' : 'none';
        if (pagoCheckbox) pagoCheckbox.checked = true;

        const pagoLabel = document.getElementById('pagoLabel');
        const pagoHelper = document.getElementById('pagoHelperText');
        if (tipo === 'receita') {
            if (pagoLabel) pagoLabel.textContent = 'Já foi recebido';
            if (pagoHelper) pagoHelper.textContent = 'Desmarque se ainda não foi recebido.';
            // Receita: mostrar todas as opções de recorrência, incluindo infinito
            const radioInfinito = document.getElementById('recorrenciaRadioInfinito');
            if (radioInfinito) radioInfinito.style.display = '';
            const radios = document.querySelectorAll('input[name="recorrencia_modo"]');
            radios.forEach(r => r.checked = r.value === 'infinito');
        } else {
            if (pagoLabel) pagoLabel.textContent = 'Já foi pago';
            if (pagoHelper) pagoHelper.textContent = 'Desmarque se ainda não foi pago.';
            const radioInfinito = document.getElementById('recorrenciaRadioInfinito');
            if (radioInfinito) radioInfinito.style.display = '';
            const radios = document.querySelectorAll('input[name="recorrencia_modo"]');
            radios.forEach(r => r.checked = r.value === 'infinito');
        }

        // Configure recurrence & reminder events
        ContasLancamento.configurarEventosRecorrencia();

        // Navigate to step 2
        ContasLancamento.goToStep(2);

        // Focus on description
        setTimeout(() => {
            document.getElementById('lancamentoDescricao')?.focus();
        }, 100);
    },

    /**
     * Configurar eventos de recorrência e lembrete
     */
    configurarEventosRecorrencia() {
        // Listener para o select de tempo de aviso (lembrete)
        const tempoAviso = document.getElementById('lancamentoTempoAviso');
        if (tempoAviso && !tempoAviso._lkListenerAdded) {
            tempoAviso.addEventListener('change', () => {
                const canaisDiv = document.getElementById('canaisNotificacaoInline');
                if (canaisDiv) {
                    canaisDiv.style.display = tempoAviso.value ? 'block' : 'none';
                }
            });
            tempoAviso._lkListenerAdded = true;
        }
    },

    /**
     * Toggle visibilidade dos detalhes de recorrência
     * Chamado pelo onchange do checkbox lancamentoRecorrente
     */
    toggleRecorrencia() {
        const checkbox = document.getElementById('lancamentoRecorrente');
        const detalhes = document.getElementById('recorrenciaDetalhes');
        if (detalhes) {
            detalhes.style.display = checkbox?.checked ? 'block' : 'none';
        }
        // Reset sub-groups when unchecked
        if (!checkbox?.checked) {
            const totalGroup = document.getElementById('recorrenciaTotalGroup');
            const fimGroup = document.getElementById('recorrenciaFimGroup');
            if (totalGroup) totalGroup.style.display = 'none';
            if (fimGroup) fimGroup.style.display = 'none';
            // Reset radio baseado no tipo atual
            const tipoAtual = document.getElementById('lancamentoTipo')?.value;
            const defaultModo = 'infinito';
            const radios = document.querySelectorAll('input[name="recorrencia_modo"]');
            radios.forEach(r => r.checked = r.value === defaultModo);
        }
    },

    /**
     * Toggle visibilidade dos sub-grupos de fim de recorrência
     * Chamado pelo onchange dos radios recorrencia_modo
     */
    toggleRecorrenciaFim() {
        const modo = document.querySelector('input[name="recorrencia_modo"]:checked')?.value || 'infinito';
        const totalGroup = document.getElementById('recorrenciaTotalGroup');
        const fimGroup = document.getElementById('recorrenciaFimGroup');
        if (totalGroup) totalGroup.style.display = modo === 'quantidade' ? 'block' : 'none';
        if (fimGroup) fimGroup.style.display = modo === 'data' ? 'block' : 'none';
    },

    /**
     * Selecionar tipo de agendamento (receita/despesa) - legacy
     */
    selecionarTipoAgendamento(tipo) {
        const btnReceita = document.querySelector('#tipoAgendamentoGroup .lk-btn-tipo-receita');
        const btnDespesa = document.querySelector('#tipoAgendamentoGroup .lk-btn-tipo-despesa');
        const inputTipo = document.getElementById('lancamentoTipoAgendamento');

        if (tipo === 'receita') {
            btnReceita?.classList.add('active');
            btnDespesa?.classList.remove('active');
            Modules.API.preencherCategorias('receita');
        } else {
            btnDespesa?.classList.add('active');
            btnReceita?.classList.remove('active');
            Modules.API.preencherCategorias('despesa');
        }

        if (inputTipo) inputTipo.value = tipo;
    },

    /**
     * Resetar seleção de forma de pagamento/recebimento
     */
    resetarFormaPagamento() {
        // Limpar seleção dos botões de pagamento
        document.querySelectorAll('#formaPagamentoGrid .lk-forma-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('#formaRecebimentoGrid .lk-forma-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Limpar inputs hidden
        const formaPagInput = document.getElementById('formaPagamento');
        if (formaPagInput) formaPagInput.value = '';
        const formaRecInput = document.getElementById('formaRecebimento');
        if (formaRecInput) formaRecInput.value = '';

        // Ocultar seleção de cartão
        const cartaoGroup = document.getElementById('cartaoCreditoGroup');
        if (cartaoGroup) cartaoGroup.classList.remove('active');

        // Ocultar parcelamento
        const parcelamentoGroup = document.getElementById('parcelamentoGroup');
        if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
        const numParcelasGroup = document.getElementById('numeroParcelasGroup');
        if (numParcelasGroup) numParcelasGroup.style.display = 'none';
    },

    /**
     * Selecionar forma de pagamento (despesas)
     */
    selecionarFormaPagamento(forma) {
        // Atualizar visual
        document.querySelectorAll('#formaPagamentoGrid .lk-forma-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        const btnSelecionado = document.querySelector(`#formaPagamentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        // Atualizar input hidden
        const formaPagInput = document.getElementById('formaPagamento');
        if (formaPagInput) formaPagInput.value = forma;

        // Mostrar/ocultar seleção de cartão
        const cartaoGroup = document.getElementById('cartaoCreditoGroup');
        const parcelamentoGroup = document.getElementById('parcelamentoGroup');
        const recorrenciaGroup = document.getElementById('recorrenciaGroup');

        if (forma === 'cartao_credito') {
            if (cartaoGroup) {
                cartaoGroup.classList.add('active');
                cartaoGroup.style.display = 'block';
            }
            // Carregar cartões disponíveis
            Modules.API.carregarCartoesCredito();
            // Verificar se tem cartão selecionado para mostrar parcelamento
            const cartaoSelect = document.getElementById('lancamentoCartaoCredito');
            if (cartaoSelect && cartaoSelect.value) {
                if (parcelamentoGroup) parcelamentoGroup.style.display = 'block';
            }
            // Ocultar recorrência, lembrete e pago (cartão vai para fatura)
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
            const lancamentoRecorrente = document.getElementById('lancamentoRecorrente');
            if (lancamentoRecorrente) lancamentoRecorrente.checked = false;
            const recorrenciaDetalhes = document.getElementById('recorrenciaDetalhes');
            if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
            const lembreteGroup = document.getElementById('lembreteGroup');
            if (lembreteGroup) lembreteGroup.style.display = 'none';
            const pagoGroup = document.getElementById('pagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'none';
        } else {
            if (cartaoGroup) {
                cartaoGroup.classList.remove('active');
                cartaoGroup.style.display = 'none';
            }
            // Resetar parcelas mas manter grupo visível para parcelamento sem cartão
            const numParcelasGroup = document.getElementById('numeroParcelasGroup');
            if (numParcelasGroup) numParcelasGroup.style.display = 'none';
            const parceladoCheck = document.getElementById('lancamentoParcelado');
            if (parceladoCheck) parceladoCheck.checked = false;
            if (parcelamentoGroup) {
                parcelamentoGroup.style.display = 'block';
                const parcelTexto = parcelamentoGroup.querySelector('.lk-checkbox-text');
                if (parcelTexto) parcelTexto.innerHTML = '<i data-lucide="calendar-days"></i> Parcelar pagamento';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                const parcelHelper = parcelamentoGroup.querySelector('.lk-helper-text');
                if (parcelHelper) parcelHelper.textContent = 'O valor total será dividido em parcelas mensais.';
            }
            // Limpar seleção de cartão
            const cartaoSelect = document.getElementById('lancamentoCartaoCredito');
            if (cartaoSelect) cartaoSelect.value = '';
            // Ocultar assinatura (somente para cartão)
            const assinaturaGroup = document.getElementById('assinaturaCartaoGroup');
            if (assinaturaGroup) assinaturaGroup.style.display = 'none';
            // Restaurar recorrência, lembrete e pago para despesas
            const tipo = document.getElementById('lancamentoTipo')?.value;
            if (tipo === 'despesa' || tipo === 'receita') {
                if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
                const lembreteGroup = document.getElementById('lembreteGroup');
                if (lembreteGroup) lembreteGroup.style.display = 'block';
                const pagoGroup = document.getElementById('pagoGroup');
                if (pagoGroup) pagoGroup.style.display = 'block';
            }
        }
    },

    /**
     * Selecionar forma de recebimento (receitas)
     */
    selecionarFormaRecebimento(forma) {
        // Atualizar visual
        document.querySelectorAll('#formaRecebimentoGrid .lk-forma-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        const btnSelecionado = document.querySelector(`#formaRecebimentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        // Atualizar input hidden
        const formaRecInput = document.getElementById('formaRecebimento');
        if (formaRecInput) formaRecInput.value = forma;

        // Guardar se é estorno
        STATE.isEstornoCartao = (forma === 'estorno_cartao');

        // Se for estorno de cartão, mostrar seleção de cartão
        const cartaoGroup = document.getElementById('cartaoCreditoGroup');
        const faturaEstornoGroup = document.getElementById('faturaEstornoGroup');

        if (forma === 'estorno_cartao') {
            if (cartaoGroup) {
                cartaoGroup.classList.add('active');
                cartaoGroup.style.display = 'block';
            }
            Modules.API.carregarCartoesCredito();
            // Ocultar recorrência, lembrete e pago (estorno vai para fatura)
            const recorrenciaGroup = document.getElementById('recorrenciaGroup');
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
            const lancamentoRecorrente = document.getElementById('lancamentoRecorrente');
            if (lancamentoRecorrente) lancamentoRecorrente.checked = false;
            const recorrenciaDetalhes = document.getElementById('recorrenciaDetalhes');
            if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
            const lembreteGroup = document.getElementById('lembreteGroup');
            if (lembreteGroup) lembreteGroup.style.display = 'none';
            const pagoGroup = document.getElementById('pagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'none';
        } else {
            if (cartaoGroup) {
                cartaoGroup.classList.remove('active');
                cartaoGroup.style.display = 'none';
            }
            if (faturaEstornoGroup) {
                faturaEstornoGroup.style.display = 'none';
            }
            const cartaoSelect = document.getElementById('lancamentoCartaoCredito');
            if (cartaoSelect) cartaoSelect.value = '';
            // Restaurar recorrência, lembrete e pago
            const recorrenciaGroup = document.getElementById('recorrenciaGroup');
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
            const lembreteGroup = document.getElementById('lembreteGroup');
            if (lembreteGroup) lembreteGroup.style.display = 'block';
            const pagoGroup = document.getElementById('pagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'block';
            // Restaurar parcelamento para receita
            const parcelamentoGroupRec = document.getElementById('parcelamentoGroup');
            if (parcelamentoGroupRec) parcelamentoGroupRec.style.display = 'block';
        }
    },

    /**
     * Callback quando o cartão é alterado
     */
    onCartaoChange() {
        const cartaoSelect = document.getElementById('lancamentoCartaoCredito');
        const cartaoId = cartaoSelect?.value;
        const faturaEstornoGroup = document.getElementById('faturaEstornoGroup');


        if (STATE.isEstornoCartao && cartaoId) {
            // Carregar faturas do cartão selecionado
            Modules.API.carregarFaturasCartao(cartaoId);
            if (faturaEstornoGroup) {
                faturaEstornoGroup.style.display = 'block';
            }
        } else {
            if (faturaEstornoGroup) {
                faturaEstornoGroup.style.display = 'none';
            }
        }
    },

    /**
     * Ao selecionar cartão de crédito
     */
    aoSelecionarCartao() {
        const selectCartao = document.getElementById('lancamentoCartaoCredito');
        const parcelamentoGroup = document.getElementById('parcelamentoGroup');
        const recorrenciaGroup = document.getElementById('recorrenciaGroup');
        const assinaturaCartaoGroup = document.getElementById('assinaturaCartaoGroup');

        if (selectCartao.value) {
            // Mostrar opção de parcelamento e assinatura
            parcelamentoGroup.style.display = 'block';
            if (assinaturaCartaoGroup) assinaturaCartaoGroup.style.display = 'block';

            // Ocultar recorrência normal (substituída pela assinatura do cartão)
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
            const lancamentoRecorrente = document.getElementById('lancamentoRecorrente');
            if (lancamentoRecorrente) lancamentoRecorrente.checked = false;
            const recorrenciaDetalhes = document.getElementById('recorrenciaDetalhes');
            if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';

            // Listener do checkbox de parcelamento agora é via onchange no HTML
        } else {
            // Ocultar parcelamento e assinatura de cartão
            parcelamentoGroup.style.display = 'none';
            if (assinaturaCartaoGroup) assinaturaCartaoGroup.style.display = 'none';
            document.getElementById('numeroParcelasGroup').style.display = 'none';
            document.getElementById('lancamentoParcelado').checked = false;

            // Limpar assinatura do cartão
            const checkAssinatura = document.getElementById('lancamentoAssinaturaCartao');
            if (checkAssinatura) checkAssinatura.checked = false;
            const detalhesAssinatura = document.getElementById('assinaturaCartaoDetalhes');
            if (detalhesAssinatura) detalhesAssinatura.style.display = 'none';

            // Restaurar recorrência normal
            const tipo = document.getElementById('lancamentoTipo')?.value;
            if (recorrenciaGroup && (tipo === 'despesa' || tipo === 'receita')) {
                recorrenciaGroup.style.display = 'block';
            }
        }
    },

    /**
     * Ao marcar/desmarcar parcelado
     */
    aoMarcarParcelado() {
        const checkbox = document.getElementById('lancamentoParcelado');
        const numeroParcelasGroup = document.getElementById('numeroParcelasGroup');

        if (checkbox.checked) {
            numeroParcelasGroup.style.display = 'block';

            // Desmarcar assinatura (mutuamente exclusivo)
            const checkAssinatura = document.getElementById('lancamentoAssinaturaCartao');
            if (checkAssinatura) {
                checkAssinatura.checked = false;
                const detalhes = document.getElementById('assinaturaCartaoDetalhes');
                if (detalhes) detalhes.style.display = 'none';
            }

            // Adicionar listeners para calcular preview
            const inputValor = document.getElementById('lancamentoValor');
            const inputParcelas = document.getElementById('lancamentoTotalParcelas');

            if (inputValor && !inputValor.dataset.parcelaListenerAdded) {
                inputValor.addEventListener('input', () => ContasLancamento.calcularPreviewParcelas());
                inputValor.dataset.parcelaListenerAdded = 'true';
            }

            if (inputParcelas && !inputParcelas.dataset.listenerAdded) {
                inputParcelas.addEventListener('input', () => ContasLancamento.calcularPreviewParcelas());
                inputParcelas.dataset.listenerAdded = 'true';
            }

            // Calcular imediatamente
            ContasLancamento.calcularPreviewParcelas();
        } else {
            numeroParcelasGroup.style.display = 'none';
            const preview = document.getElementById('parcelamentoPreview');
            if (preview) {
                preview.style.display = 'none';
            }
        }
    },

    /**
     * Toggle assinatura/recorrência no cartão de crédito
     */
    toggleAssinaturaCartao() {
        const checkbox = document.getElementById('lancamentoAssinaturaCartao');
        const detalhes = document.getElementById('assinaturaCartaoDetalhes');

        if (checkbox.checked) {
            detalhes.style.display = 'block';

            // Desmarcar parcelamento (mutuamente exclusivo)
            const checkParcelado = document.getElementById('lancamentoParcelado');
            if (checkParcelado) {
                checkParcelado.checked = false;
                const numParcelasGroup = document.getElementById('numeroParcelasGroup');
                if (numParcelasGroup) numParcelasGroup.style.display = 'none';
                const preview = document.getElementById('parcelamentoPreview');
                if (preview) preview.style.display = 'none';
            }
        } else {
            detalhes.style.display = 'none';
        }
    },

    /**
     * Toggle data fim da assinatura no cartão
     */
    toggleAssinaturaCartaoFim() {
        const modo = document.querySelector('input[name="assinatura_modo"]:checked')?.value || 'infinito';
        const fimGroup = document.getElementById('assinaturaCartaoFimGroup');

        if (fimGroup) {
            fimGroup.style.display = modo === 'data' ? 'block' : 'none';
        }
    },

    /**
     * Calcular e exibir preview das parcelas
     */
    calcularPreviewParcelas() {
        const valorInput = document.getElementById('lancamentoValor');
        const parcelasInput = document.getElementById('lancamentoTotalParcelas');
        const preview = document.getElementById('parcelamentoPreview');

        if (!valorInput || !parcelasInput || !preview) return;

        const valorStr = valorInput.value || '0,00';
        const valor = Utils.parseMoneyInput(valorStr);
        const parcelas = parseInt(parcelasInput.value) || 2;

        if (valor <= 0 || parcelas < 2) {
            preview.style.display = 'none';
            return;
        }

        const valorParcela = valor / parcelas;
        const selectCartao = document.getElementById('lancamentoCartaoCredito');
        const selectedOption = selectCartao.options[selectCartao.selectedIndex];
        const diaVencimento = selectedOption.dataset.diaVencimento || '10';

        preview.style.display = 'block';
        preview.innerHTML = `
            <div class="lk-parcelamento-preview-title">Preview do Parcelamento</div>
            <div class="lk-parcelamento-valor">${parcelas}x de ${Utils.formatCurrency(valorParcela)}</div>
            <div class="lk-parcelamento-detalhes">
                <span><strong>Valor total:</strong> ${Utils.formatCurrency(valor)}</span>
                <span><strong>Vencimento:</strong> Todo dia ${diaVencimento}</span>
                <span><strong>Primeira parcela:</strong> ${ContasLancamento.calcularProximaFatura(diaVencimento)}</span>
            </div>
        `;
    },

    /**
     * Calcular data da próxima fatura
     */
    calcularProximaFatura(diaVencimento) {
        const hoje = new Date();
        const dia = parseInt(diaVencimento);
        const mesAtual = hoje.getMonth();
        const anoAtual = hoje.getFullYear();

        let proximaFatura = new Date(anoAtual, mesAtual, dia);

        // Se já passou o dia neste mês, próxima fatura é mês que vem
        if (proximaFatura < hoje) {
            proximaFatura = new Date(anoAtual, mesAtual + 1, dia);
        }

        return proximaFatura.toLocaleDateString('pt-BR');
    },

    /**
     * Voltar para escolha de tipo (step 1)
     */
    voltarEscolhaTipo() {
        ContasLancamento.goToStep(1);

        // Reset form
        const form = document.getElementById('formLancamento');
        if (form) form.reset();

        // Reset specific fields
        const cartaoCreditoGroup = document.getElementById('cartaoCreditoGroup');
        const parcelamentoGroup = document.getElementById('parcelamentoGroup');
        const numeroParcelasGroup = document.getElementById('numeroParcelasGroup');
        const contaDestinoGroup = document.getElementById('contaDestinoGroup');
        if (cartaoCreditoGroup) cartaoCreditoGroup.style.display = 'none';
        if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
        if (numeroParcelasGroup) numeroParcelasGroup.style.display = 'none';
        if (contaDestinoGroup) contaDestinoGroup.style.display = 'none';

        const tipoAgendamentoGroup = document.getElementById('tipoAgendamentoGroup');
        const recorrenciaGroup = document.getElementById('recorrenciaGroup');
        const lembreteGroup = document.getElementById('lembreteGroup');
        const recorrenciaDetalhes = document.getElementById('recorrenciaDetalhes');
        const canaisNotificacaoInline = document.getElementById('canaisNotificacaoInline');
        const lancamentoRecorrente = document.getElementById('lancamentoRecorrente');
        const lancamentoTempoAviso = document.getElementById('lancamentoTempoAviso');

        if (tipoAgendamentoGroup) tipoAgendamentoGroup.style.display = 'none';
        if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
        if (lembreteGroup) lembreteGroup.style.display = 'none';
        if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
        if (canaisNotificacaoInline) canaisNotificacaoInline.style.display = 'none';
        if (lancamentoRecorrente) lancamentoRecorrente.checked = false;
        if (lancamentoTempoAviso) lancamentoTempoAviso.value = '';

        const pagoGroup = document.getElementById('pagoGroup');
        if (pagoGroup) pagoGroup.style.display = 'none';

        const recorrenciaTotalGroup = document.getElementById('recorrenciaTotalGroup');
        const recorrenciaFimGroup = document.getElementById('recorrenciaFimGroup');
        if (recorrenciaTotalGroup) recorrenciaTotalGroup.style.display = 'none';
        if (recorrenciaFimGroup) recorrenciaFimGroup.style.display = 'none';
        const radios = document.querySelectorAll('input[name="recorrencia_modo"]');
        radios.forEach(r => r.checked = r.value === 'infinito');

        // Restore title and header
        const tituloEl = document.getElementById('modalLancamentoTitulo');
        if (tituloEl) tituloEl.textContent = 'Nova Movimentação';
        const headerGradient = document.querySelector('#modalLancamentoOverlay .lk-modal-header-gradient');
        if (headerGradient) headerGradient.style.removeProperty('background');

        ContasLancamento._tipoAtual = null;
    },

    /**
     * Selecionar tipo de lançamento (método antigo - agora redireciona)
     */
    selecionarTipoLancamento(tipo) {
        ContasLancamento.mostrarFormularioLancamento(tipo);
    },

    /**
     * Fechar modal de lançamento
     */
    closeLancamentoModal() {
        const modalOverlay = document.getElementById('modalLancamentoOverlay');
        if (!modalOverlay) return;

        modalOverlay.classList.remove('active');

        // Restaurar scroll
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';

        // Limpar conta selecionada
        STATE.contaSelecionadaLancamento = null;

        // Reset wizard to step 1
        setTimeout(() => {
            ContasLancamento.initWizard();

            // Reset card subscription fields
            const assinaturaGroup = document.getElementById('assinaturaCartaoGroup');
            if (assinaturaGroup) assinaturaGroup.style.display = 'none';
            const assinaturaDetalhes = document.getElementById('assinaturaCartaoDetalhes');
            if (assinaturaDetalhes) assinaturaDetalhes.style.display = 'none';
            const assinaturaFimGroup = document.getElementById('assinaturaCartaoFimGroup');
            if (assinaturaFimGroup) assinaturaFimGroup.style.display = 'none';
        }, 300);
    },

    /**
     * Manipular submissão do formulário de lançamento
     */
    async handleLancamentoSubmit(form) {
        if (STATE.isSubmitting) {
            return;
        }

        STATE.isSubmitting = true;

        // Desabilitar botão submit
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn?.innerHTML;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="icon-spin"></i> Processando...';
            refreshIcons();
        }

        try {
            const formData = new FormData(form);
            const tipo = formData.get('tipo');
            const contaId = formData.get('conta_id');
            const valorFormatado = formData.get('valor');
            const contaDestinoId = formData.get('conta_destino_id');



            // Validações
            if (tipo === 'transferencia' && !contaDestinoId) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Selecione a conta de destino',
                    customClass: { container: 'swal-above-modal' }
                });
                throw new Error('Conta destino obrigatória para transferências');
            }

            // Validação extra: garantir que as contas são diferentes
            if (tipo === 'transferencia' && String(contaId) === String(contaDestinoId)) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Conta de origem e destino devem ser diferentes',
                    customClass: { container: 'swal-above-modal' }
                });
                throw new Error('Selecione contas de origem e destino diferentes.');
            }

            // Converter valor formatado para float
            const valor = Utils.parseMoneyInput(valorFormatado);

            if (valor <= 0) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Informe um valor válido',
                    customClass: { container: 'swal-above-modal' }
                });
                throw new Error('Valor inválido');
            }

            const cartaoCreditoId = formData.get('cartao_credito_id') || null;
            const ehParcelado = formData.get('eh_parcelado') === 'on' || formData.get('eh_parcelado') === true;
            const totalParcelas = formData.get('total_parcelas') ? parseInt(formData.get('total_parcelas')) : null;

            // Validar limite do cartão de crédito se houver
            if (cartaoCreditoId && tipo === 'despesa' && STATE.cartoes && Array.isArray(STATE.cartoes)) {
                const cartao = STATE.cartoes.find(c => c.id == cartaoCreditoId);
                if (cartao) {
                    const limiteDisponivel = parseFloat(cartao.limite_disponivel || 0);
                    if (valor > limiteDisponivel) {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Limite Insuficiente',
                            html: `
                                <p>O valor da compra (${Utils.formatCurrency(valor)}) excede o limite disponível do cartão.</p>
                                <p><strong>Limite disponível:</strong> ${Utils.formatCurrency(limiteDisponivel)}</p>
                            `,
                            confirmButtonText: 'Entendi',
                            customClass: { container: 'swal-above-modal' }
                        });
                        throw new Error('Limite do cartão insuficiente');
                    }
                }
            }

            // Coletar forma de pagamento/recebimento
            let formaPagamento = null;
            let faturaEstornoMesAno = null;

            // Validar ranges de parcelas e recorrência
            const parceladoCheck = form.querySelector('#parceladoCheck');
            if (parceladoCheck?.checked) {
                const totalParcelas = parseInt(document.getElementById('totalParcelas')?.value) || 0;
                if (totalParcelas < 2 || totalParcelas > 48) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'O número de parcelas deve ser entre 2 e 48', customClass: { container: 'swal-above-modal' } });
                    return;
                }
            }
            const recorrenteCheck = form.querySelector('#recorrenteCheck');
            if (recorrenteCheck?.checked) {
                const modo = document.querySelector('input[name="recorrencia_modo"]:checked')?.value;
                if (modo === 'quantidade') {
                    const total = parseInt(document.getElementById('recorrenciaTotal')?.value) || 0;
                    if (total < 2 || total > 120) {
                        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'A quantidade de repetições deve ser entre 2 e 120', customClass: { container: 'swal-above-modal' } });
                        return;
                    }
                }
            }

            if (tipo === 'receita') {
                const formaRecEl = document.getElementById('formaRecebimento');
                formaPagamento = formaRecEl?.value || null;

                // Se for estorno de cartão, pegar o mês/ano da fatura
                if (formaPagamento === 'estorno_cartao') {
                    const faturaEstornoEl = document.getElementById('lancamentoFaturaEstorno');
                    faturaEstornoMesAno = faturaEstornoEl?.value || null;
                }
            } else if (tipo === 'despesa') {
                const formaPagEl = document.getElementById('formaPagamento');
                formaPagamento = formaPagEl?.value || null;
            }

            // Hora opcional
            const horaInput = document.getElementById('lancamentoHora');
            const horaLancamento = horaInput?.value || null;

            const data = {
                conta_id: contaId,
                tipo: tipo,
                descricao: formData.get('descricao'),
                valor: valor,
                data: formData.get('data'),
                hora_lancamento: horaLancamento,
                categoria_id: formData.get('categoria_id') || null,
                subcategoria_id: formData.get('subcategoria_id') || null,
                observacao: formData.get('observacoes') || null,
                forma_pagamento: formaPagamento,
                fatura_mes_ano: faturaEstornoMesAno,
                // Campos de cartão de crédito
                cartao_credito_id: cartaoCreditoId,
                eh_parcelado: ehParcelado,
                total_parcelas: totalParcelas,
            };

            // Campos de assinatura/recorrência no cartão de crédito
            if (cartaoCreditoId && tipo === 'despesa') {
                const assinaturaCheck = document.getElementById('lancamentoAssinaturaCartao');
                if (assinaturaCheck?.checked) {
                    data.recorrente = '1';
                    data.recorrencia_freq = document.getElementById('lancamentoAssinaturaFreq')?.value || 'mensal';
                    data.eh_parcelado = false; // Assinatura não é parcelamento

                    const modoAssinatura = document.querySelector('input[name="assinatura_modo"]:checked')?.value || 'infinito';
                    if (modoAssinatura === 'data') {
                        const fimAssinatura = document.getElementById('lancamentoAssinaturaFim')?.value || null;
                        if (fimAssinatura) {
                            data.recorrencia_fim = fimAssinatura;
                        }
                    }
                }
            }

            // Campos de recorrência (para receita e despesa)
            if (tipo === 'receita' || tipo === 'despesa') {
                // Campo de pago
                const pagoCheck = document.getElementById('lancamentoPago');
                data.pago = pagoCheck?.checked ? '1' : '0';

                const recorrenteCheck = document.getElementById('lancamentoRecorrente');
                if (recorrenteCheck?.checked) {
                    data.recorrente = '1';
                    data.recorrencia_freq = document.getElementById('lancamentoRecorrenciaFreq')?.value || 'mensal';

                    // Determinar modo de fim
                    const modo = document.querySelector('input[name="recorrencia_modo"]:checked')?.value || 'infinito';
                    if (modo === 'quantidade') {
                        const total = parseInt(document.getElementById('lancamentoRecorrenciaTotal')?.value) || 12;
                        data.recorrencia_total = total;
                    } else if (modo === 'data') {
                        const recorrenciaFim = document.getElementById('lancamentoRecorrenciaFim')?.value || null;
                        if (recorrenciaFim) {
                            data.recorrencia_fim = recorrenciaFim;
                        }
                    }
                    // modo === 'infinito' → não envia nem total nem fim
                }

                // Campos de lembrete (apenas para lançamentos sem cartão - cartão lembra pela fatura)
                const tempoAviso = document.getElementById('lancamentoTempoAviso')?.value || '';
                if (tempoAviso && !cartaoCreditoId) {
                    data.lembrar_antes_segundos = parseInt(tempoAviso);
                    data.canal_inapp = document.getElementById('lancamentoCanalInapp')?.checked ? '1' : '0';
                    data.canal_email = document.getElementById('lancamentoCanalEmail')?.checked ? '1' : '0';
                }
            }

            let apiUrl = `${CONFIG.API_URL}/lancamentos`;
            let requestData = data;

            // Se for transferência, usar endpoint específico
            if (tipo === 'transferencia') {
                apiUrl = `${CONFIG.API_URL}/transfers`;
                requestData = {
                    conta_id: contaId,
                    conta_id_destino: contaDestinoId,
                    valor: valor,
                    data: formData.get('data'),
                    hora_lancamento: horaLancamento,
                    descricao: formData.get('descricao'),
                    observacao: formData.get('observacao') || null,
                };
            }
            // Se for PARCELAMENTO SEM CARTÃO (conta bancária), usar endpoint de parcelamentos
            else if (ehParcelado && totalParcelas && totalParcelas > 1 && !cartaoCreditoId) {
                apiUrl = `${CONFIG.API_URL}/parcelamentos`;
                requestData = {
                    descricao: formData.get('descricao'),
                    valor_total: valor,
                    numero_parcelas: totalParcelas,
                    categoria_id: formData.get('categoria_id') || null,
                    subcategoria_id: formData.get('subcategoria_id') || null,
                    forma_pagamento: formaPagamento || null,
                    conta_id: contaId,
                    tipo: tipo,
                    data_criacao: formData.get('data'),
                };

                // Incluir campos de lembrete se preenchidos
                if (data.lembrar_antes_segundos) {
                    requestData.lembrar_antes_segundos = data.lembrar_antes_segundos;
                    requestData.canal_inapp = data.canal_inapp || '0';
                    requestData.canal_email = data.canal_email || '0';
                }
            }
            // Se tem CARTÃO, sempre usar endpoint de lancamentos (ele detecta o cartao_credito_id)
            // Isso vale para cartão à vista ou parcelado
            // Enviar para API
            const csrfToken = await Utils.getCSRFToken();
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Erro ao criar lançamento');
            }

            const result = await response.json();

            // Fechar modal primeiro
            ContasLancamento.closeLancamentoModal();

            // Exibir Sweet Alert de sucesso
            const tiposTexto = {
                'receita': 'Receita',
                'despesa': 'Despesa',
                'transferencia': 'Transferência'
            };
            const tipoTexto = tiposTexto[tipo] || 'Lançamento';
            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                html: `<strong>${tipoTexto}</strong> criada com sucesso!`,
                timer: 2000,
                showConfirmButton: false,
                toast: false,
                position: 'center'
            });

            // Exibir dados de gamificação se disponíveis
            if (result.data?.gamification) {
                try {
                    const gamif = result.data.gamification;

                    // Verificar conquistas desbloqueadas (suporta ambos formatos)
                    const achievements = gamif.achievements || gamif.new_achievements || [];
                    if (Array.isArray(achievements) && achievements.length > 0) {
                        if (typeof window.notifyMultipleAchievements === 'function') {
                            window.notifyMultipleAchievements(achievements);
                        } else {
                            // Fallback para notificação individual
                            achievements.forEach(ach => {
                                try {
                                    if (!ach || typeof ach !== 'object') {
                                        console.warn('Conquista inválida:', ach);
                                        return;
                                    }
                                    if (typeof window.notifyAchievementUnlocked === 'function') {
                                        window.notifyAchievementUnlocked(ach);
                                    } else {
                                        Utils.showNotification(`🏆 ${ach.name || 'Conquista'} desbloqueada!`, 'success');
                                    }
                                } catch (error) {
                                    console.error('Erro ao exibir conquista:', error, ach);
                                }
                            });
                        }
                    }

                    // Processar pontos se houver
                    const points = gamif.points || gamif;
                    if (points.points_gained > 0) {
                        // Pontos ganhos
                    }

                    if (gamif.level_up) {
                        try {
                            // Exibir modal grande de level up
                            if (typeof window.notifyLevelUp === 'function') {
                                window.notifyLevelUp(gamif.level);
                            } else {
                                // Fallback para notificação simples
                                Utils.showNotification(`🎉 Subiu para o Nível ${gamif.level}!`, 'success');
                            }
                        } catch (error) {
                            console.error('Erro ao exibir level up:', error);
                        }
                    }
                } catch (error) {
                    console.error('Erro ao processar gamificação:', error, result.data.gamification);
                }
            }

            // Recarregar contas para atualizar saldo
            await Modules.API.loadContas();

            // Atualizar dashboard se estiver disponível
            if (typeof window.refreshDashboard === 'function') {
                window.refreshDashboard();
            } else if (window.LK?.refreshDashboard) {
                window.LK.refreshDashboard();
            }

            // Disparar eventos customizados para outros componentes
            document.dispatchEvent(new CustomEvent('lukrato:data-changed'));

            // Disparar evento específico de lançamento criado para onboarding
            window.dispatchEvent(new CustomEvent('lancamento-created', { detail: result.data }));

        } catch (error) {
            console.error('❌ Erro ao criar lançamento:', error);

            // Lista de erros que já foram mostrados ao usuário antes
            const errosJaMostrados = [
                'Conta destino obrigatória para transferências',
                'Selecione contas de origem e destino diferentes.',
                'Valor inválido',
                'Limite do cartão insuficiente' // Já foi mostrado na validação
            ];

            // Mostrar erro se não foi mostrado anteriormente
            if (!errosJaMostrados.includes(error.message)) {
                // Para erros de limite do backend, mostrar com destaque
                if (error.message && error.message.toLowerCase().includes('limite')) {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Limite Insuficiente',
                        text: error.message,
                        confirmButtonText: 'Entendi',
                        confirmButtonColor: '#d33',
                        customClass: {
                            container: 'swal-above-modal'
                        }
                    });
                } else {
                    Utils.showNotification(
                        error.message || 'Erro ao criar lançamento. Tente novamente.',
                        'error'
                    );
                }
            }
        } finally {
            // Reabilitar botão
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }

            STATE.isSubmitting = false;
        }
    },
};

// ─── Register ────────────────────────────────────────────────────────────────

Modules.Lancamento = ContasLancamento;
