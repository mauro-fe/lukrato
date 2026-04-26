export function attachLancamentoGlobalWizardMethods(ManagerClass, dependencies) {
    const {
        refreshIcons,
        renderLancamentoHistoryPlaceholder,
    } = dependencies;

    Object.assign(ManagerClass.prototype, {
        syncPageModeState() {
            const root = this.getRootElement?.();
            if (!root || !this.isPageMode?.()) {
                return;
            }

            const page = root.closest('.lancamento-create-page');
            const currentStep = String(this.currentStep || 1);
            const currentType = typeof this.tipoAtual === 'string' ? this.tipoAtual : '';

            [root, page].forEach((element) => {
                if (!element) {
                    return;
                }

                element.dataset.wizardStep = currentStep;

                if (currentType !== '') {
                    element.dataset.wizardTipo = currentType;
                } else {
                    delete element.dataset.wizardTipo;
                }
            });
        },

        closeModal() {
            const root = this.getRootElement?.();
            if (!root) {
                return;
            }

            if (this.isPageMode?.()) {
                window.location.href = this.resolveCloseUrl();
                return;
            }

            root.classList.remove('active');
            document.body.style.overflow = '';
            this.pendingTipo = null;
            this.restaurarCabecalhoPadrao();
            this.initWizard();
            this.clearPlanningAlerts();
        },

        restaurarCabecalhoPadrao() {
            const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
            if (tituloEl) tituloEl.textContent = 'Nova Transação';

            const headerGradient = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-header-gradient');
            if (headerGradient) {
                headerGradient.classList.remove('receita', 'despesa', 'transferencia', 'agendamento');
                headerGradient.style.removeProperty('background');
            }
        },

        voltarEscolhaTipo() {
            this.goToStep(1);
            this.restaurarCabecalhoPadrao();
            this.resetarFormulario();
            this.resetQuickOptions?.();
            this.syncPageModeState();
        },

        initWizard() {
            this.currentStep = 1;
            this.tipoAtual = null;
            this.resetarFormulario();
            this.totalSteps = this.isPageMode?.() ? 2 : 5;
            this.restaurarCabecalhoPadrao();
            this.resetQuickOptions?.();

            const contaInfo = document.getElementById('globalContaInfo');
            if (contaInfo) {
                contaInfo.classList.remove('lk-conta-info--compact');
            }

            const contaSelect = document.getElementById('globalContaSelect');
            if (contaSelect) {
                contaSelect.value = this.contaSelecionada?.id ? String(this.contaSelecionada.id) : '';
                const contaSelectGroup = contaSelect.closest('.lk-form-group');
                if (contaSelectGroup) {
                    contaSelectGroup.classList.remove('lk-conta-select--hidden');
                }
            }

            this.syncEnhancedSelects();

            this.aplicarContextoAbertura(this.contextoAbertura);
            this.atualizarContaSelecionadaUI();
            if (!this.contaSelecionada) {
                const historicoContainer = document.getElementById('globalLancamentoHistorico');
                if (historicoContainer) {
                    renderLancamentoHistoryPlaceholder(historicoContainer, 'Escolha uma conta para ver o histórico.');
                }
            }
            const progress = document.getElementById('globalWizardProgress');
            if (progress) progress.style.display = 'none';
            for (let i = 1; i <= 5; i++) {
                const step = document.getElementById(`globalStep${i}`);
                if (step) {
                    step.classList.remove('active');
                    step.style.display = 'none';
                }
            }
            const step1 = document.getElementById('globalStep1');
            if (step1) {
                step1.classList.add('active');
                step1.style.display = '';
            }

            this.syncPageModeState();
        },

        renderProgress() {
            const container = document.getElementById('globalWizardProgress');
            if (!container) return;

            if (this.isPageMode?.() || this.currentStep <= 1) {
                container.style.display = 'none';
                return;
            }
            container.style.display = 'flex';

            const dotCount = this.totalSteps - 1;
            let html = '';
            for (let i = 0; i < dotCount; i++) {
                const stepNum = i + 2;
                const progressLabel = i + 1;
                let stateClass = 'pending';
                if (stepNum < this.currentStep) stateClass = 'completed';
                else if (stepNum === this.currentStep) stateClass = 'active';

                if (i > 0) {
                    const lineClass = stepNum <= this.currentStep ? 'completed' : '';
                    html += `<div class="lk-wizard-line ${lineClass}"></div>`;
                }
                html += `
                    <div class="lk-wizard-dot ${stateClass}">
                        <span class="lk-wizard-dot-number">${progressLabel}</span>
                        <span class="lk-wizard-dot-check" aria-hidden="true">
                            <i data-lucide="check"></i>
                        </span>
                    </div>
                `;
            }
            container.innerHTML = html;
        },

        goToStep(n) {
            if (n < 1 || n > this.totalSteps) return;

            this.currentStep = n;

            for (let i = 1; i <= 5; i++) {
                const step = document.getElementById(`globalStep${i}`);
                if (!step) continue;

                if (i !== n) {
                    step.classList.remove('active');
                    step.style.display = 'none';
                }
            }

            const newStep = document.getElementById(`globalStep${n}`);
            if (newStep) {
                newStep.style.display = '';
                newStep.classList.add('active');
            }

            this.renderProgress();
            this.syncPageModeState();

            const contaInfo = document.getElementById('globalContaInfo');
            if (contaInfo) {
                contaInfo.classList.toggle('lk-conta-info--compact', n > 1);
            }
            const contaSelectGroup = document.getElementById('globalContaSelect')?.closest('.lk-form-group');
            if (contaSelectGroup) {
                if (this.isPageMode?.()) {
                    contaSelectGroup.classList.remove('lk-conta-select--hidden');
                } else {
                    contaSelectGroup.classList.toggle('lk-conta-select--hidden', n > 1);
                }
            }

            const body = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-body-modern');
            if (body) body.scrollTop = 0;

            refreshIcons();
        },

        nextStep() {
            if (!this.validateCurrentStep()) return;

            let next = this.currentStep + 1;

            if (this.tipoAtual === 'transferencia') {
                if (next === 5) next = this.totalSteps + 1;
            }

            if (next > this.totalSteps) {
                this.salvarLancamento();
                return;
            }
            this.goToStep(next);
        },

        prevStep() {
            let prev = this.currentStep - 1;

            if (this.tipoAtual === 'transferencia') {
                // no-op
            }

            if (prev < 1) prev = 1;
            if (prev === 1) {
                this.voltarEscolhaTipo();
                return;
            }
            this.goToStep(prev);
        },

        skipAndSave() {
            if (!this.validarFormulario()) return;
            this.salvarLancamento();
        },

        saveQuick() {
            if (this.tipoAtual === 'transferencia') {
                this.nextStep();
                return;
            }

            if (this.currentStep !== 2) {
                this.goToStep(2);
            }

            if (!this.validateCurrentStep()) return;

            const dataInput = document.getElementById('globalLancamentoData');
            if (dataInput && !dataInput.value) {
                const hoje = new Date();
                dataInput.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;
            }

            this.salvarLancamento();
        },

        resetQuickOptions() {
            const panel = document.getElementById('globalQuickOptions');
            const button = document.getElementById('globalQuickMoreOptionsBtn');

            if (panel) {
                panel.hidden = true;
            }

            if (button) {
                button.setAttribute('aria-expanded', 'false');
                button.classList.remove('is-open');
                button.innerHTML = '<i data-lucide="sliders-horizontal"></i> Mais opções';
            }

            this.resetQuickHistory?.();
            refreshIcons();
        },

        toggleQuickOptions(force = null) {
            const panel = document.getElementById('globalQuickOptions');
            const button = document.getElementById('globalQuickMoreOptionsBtn');
            if (!panel) return;

            const shouldOpen = force === null ? panel.hidden : Boolean(force);
            panel.hidden = !shouldOpen;

            if (button) {
                button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                button.classList.toggle('is-open', shouldOpen);
                button.innerHTML = shouldOpen
                    ? '<i data-lucide="chevron-up"></i> Menos opções'
                    : '<i data-lucide="sliders-horizontal"></i> Mais opções';
            }

            refreshIcons();
        },

        resetQuickHistory() {
            this.toggleQuickHistory(false);
        },

        toggleQuickHistory(force = null) {
            const panel = document.getElementById('globalQuickHistoryPanel');
            const button = document.getElementById('globalQuickHistoryBtn');
            if (!panel) return;

            const shouldOpen = force === null ? panel.hidden : Boolean(force);
            panel.hidden = !shouldOpen;

            if (button) {
                button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                button.classList.toggle('is-open', shouldOpen);
            }

            refreshIcons();
        },

        syncQuickTypeHeading(tipo = this.tipoAtual) {
            const labels = {
                receita: {
                    title: 'Nova receita',
                    submit: 'Salvar receita'
                },
                despesa: {
                    title: 'Nova despesa',
                    submit: 'Salvar despesa'
                },
                transferencia: {
                    title: 'Nova transferência',
                    submit: 'Salvar transferência'
                }
            };
            const copy = labels[tipo] || {
                title: 'Nova transação',
                submit: 'Salvar transação'
            };

            const kicker = document.getElementById('globalQuickTypeKicker');
            const title = document.getElementById('modalLancamentoGlobalTituloInline');
            const button = document.getElementById('globalBtnSalvar');
            const submitLabels = document.querySelectorAll('.lk-page-submit-label');

            if (kicker) kicker.hidden = true;
            if (title) title.textContent = copy.title;
            if (button) {
                button.innerHTML = `<i data-lucide="check"></i> <span class="lk-page-submit-label">${copy.submit}</span>`;
            }
            submitLabels.forEach((label) => {
                label.textContent = copy.submit;
            });

            document.querySelectorAll('.lk-page-submit-btn:not(#globalBtnSalvar)').forEach((submitButton) => {
                submitButton.innerHTML = `<i data-lucide="check"></i> <span class="lk-page-submit-label">${copy.submit}</span>`;
            });

            refreshIcons();
        },
    });
}
