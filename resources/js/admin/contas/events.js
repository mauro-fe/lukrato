/**
 * ============================================================================
 * LUKRATO — Contas / Events & Money Masks
 * ============================================================================
 * Master event wiring, keyboard shortcuts, view toggle, card listeners,
 * and money mask helpers for conta / cartão / lançamento inputs.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';
import { setupMoneyMask, applyMoneyMask } from '../shared/money-mask.js';

// ─── ContasEvents ────────────────────────────────────────────────────────────

export const ContasEvents = {

    /**
     * Master event wiring — attach all buttons, form handlers, delegates.
     */
    attachEventListeners() {
        // Botões de fechar modal - Re-anexar sempre
        Modules.Modal.attachCloseModalListeners();

        // Botão nova conta
        const btnNovaConta = document.getElementById('btnNovaConta');
        if (btnNovaConta && !btnNovaConta.dataset.listenerAdded) {
            btnNovaConta.addEventListener('click', () => {
                Modules.Modal.openModal('create');
            });
            btnNovaConta.dataset.listenerAdded = 'true';
        }

        // Botão reload
        const btnReload = document.getElementById('btnReload');
        if (btnReload && !btnReload.dataset.listenerAdded) {
            btnReload.addEventListener('click', () => {
                Modules.API.loadContas();
            });
            btnReload.dataset.listenerAdded = 'true';
        }

        // Formulário de nova instituição
        const formNovaInstituicao = document.getElementById('formNovaInstituicao');
        if (formNovaInstituicao && !formNovaInstituicao.dataset.listenerAdded) {
            formNovaInstituicao.addEventListener('submit', (e) => {
                e.preventDefault();
                Modules.Modal.handleNovaInstituicaoSubmit(e.target);
            });
            formNovaInstituicao.dataset.listenerAdded = 'true';
        }

        // Input de cor da instituição
        const corInstituicao = document.getElementById('corInstituicao');
        if (corInstituicao && !corInstituicao.dataset.listenerAdded) {
            corInstituicao.addEventListener('input', (e) => {
                Modules.Modal.updateColorPreview(e.target.value);
            });
            corInstituicao.dataset.listenerAdded = 'true';
        }

        // Backdrop bloqueado - nova instituição modal fecha apenas pelo botão X

        // Botão novo cartão
        const btnNovoCartao = document.getElementById('btnNovoCartao');
        if (btnNovoCartao && !btnNovoCartao.dataset.listenerAdded) {
            btnNovoCartao.addEventListener('click', () => {
                Modules.Modal.openCartaoModal('create');
            });
            btnNovoCartao.dataset.listenerAdded = 'true';
        }

        // Formulário de cartão
        const formCartao = document.getElementById('formCartao');
        if (formCartao) {
            // Remover qualquer listener anterior
            const newFormCartao = formCartao.cloneNode(true);
            formCartao.parentNode.replaceChild(newFormCartao, formCartao);

            // Adicionar novo listener
            newFormCartao.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation();
                Modules.Modal.handleCartaoSubmit(e.target);
            });

            // Re-aplicar máscara de dinheiro após clonar
            ContasMoneyMask.setupCartaoMoneyMask();
        }

        // Formulário de conta - com proteção contra duplicação
        const formConta = document.getElementById('formConta');
        if (formConta) {
            // Remover qualquer listener anterior
            const newForm = formConta.cloneNode(true);
            formConta.parentNode.replaceChild(newForm, formConta);

            // Adicionar novo listener
            newForm.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation(); // Impede múltiplos listeners
                Modules.Modal.handleFormSubmit(e.target);
            });

            // Re-aplicar máscara de dinheiro após clonar
            ContasMoneyMask.setupMoneyMask();

            // Re-adicionar listener de mudança de moeda
            document.getElementById('moedaSelect')?.addEventListener('change', (e) => {
                Utils.updateCurrencySymbol(e.target.value);
            });

            // Re-adicionar listener do botão de nova instituição após clonar o form
            const btnAddInstituicaoNew = document.getElementById('btnAddInstituicao');
            if (btnAddInstituicaoNew) {
                btnAddInstituicaoNew.addEventListener('click', () => {
                    Modules.Modal.openNovaInstituicaoModal();
                });
            }
        }

        // Formulário de lançamento
        const formLancamento = document.getElementById('formLancamento');
        if (formLancamento) {
            // Remover qualquer listener anterior
            const newFormLancamento = formLancamento.cloneNode(true);
            formLancamento.parentNode.replaceChild(newFormLancamento, formLancamento);

            // Adicionar novo listener
            newFormLancamento.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation();
                Modules.Lancamento.handleLancamentoSubmit(e.target);
            });

            // Interceptar Enter nos inputs para avançar etapa em vez de submeter o form
            newFormLancamento.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    Modules.Lancamento.nextStep();
                }
            });

            // Re-aplicar máscara de dinheiro após clonar
            ContasMoneyMask.setupLancamentoMoneyMask();
        }

        // Botão voltar no formulário de lançamento
        document.getElementById('btnVoltarTipo')?.addEventListener('click', () => {
            Modules.Lancamento.voltarEscolhaTipo();
        });

        // Backdrop bloqueado - lançamento modal fecha apenas pelo botão X

        // View Toggle (Cards/Lista)
        ContasEvents.initViewToggle();

        // Backdrop bloqueado - modal fecha apenas pelo botão X

        // Fechar modal com tecla ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                Modules.Modal.closeModal();
            }
        });
    },

    /**
     * Inicializar atalhos de teclado
     */
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ignorar se estiver em input ou modal aberto
            const activeEl = document.activeElement;
            const isInputFocused = activeEl && (
                activeEl.tagName === 'INPUT' ||
                activeEl.tagName === 'TEXTAREA' ||
                activeEl.tagName === 'SELECT' ||
                activeEl.isContentEditable
            );
            const isModalOpen = document.querySelector('.modal.show, .lk-modal-overlay.active');

            if (isInputFocused || isModalOpen) return;

            // N = Nova conta
            if (e.key.toLowerCase() === 'n' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                Modules.Modal.openModal('create');
            }
        });
    },

    /**
     * Inicializar toggle de visualização (Cards/Lista)
     */
    initViewToggle() {
        const viewToggle = document.querySelector('.view-toggle');
        const accountsGrid = document.getElementById('accountsGrid');
        const listHeader = document.getElementById('contasListHeader');

        if (!viewToggle || !accountsGrid) return;

        const viewButtons = viewToggle.querySelectorAll('.view-btn');

        // Restaurar preferência salva
        const savedView = localStorage.getItem('contas_view_mode') || 'grid';
        if (savedView === 'list') {
            accountsGrid.classList.add('list-view');
            if (listHeader) listHeader.classList.add('visible');
        }

        // Atualizar estado dos botões
        ContasEvents.updateViewToggleState(viewButtons, savedView);

        // Adicionar listeners aos botões
        viewButtons.forEach(btn => {
            if (btn.dataset.listenerAdded) return;

            btn.addEventListener('click', () => {
                const view = btn.dataset.view;

                if (view === 'list') {
                    accountsGrid.classList.add('list-view');
                    if (listHeader) listHeader.classList.add('visible');
                } else {
                    accountsGrid.classList.remove('list-view');
                    if (listHeader) listHeader.classList.remove('visible');
                }

                // Salvar preferência
                localStorage.setItem('contas_view_mode', view);

                // Atualizar estado dos botões
                ContasEvents.updateViewToggleState(viewButtons, view);
            });

            btn.dataset.listenerAdded = 'true';
        });
    },

    /**
     * Atualizar estado visual dos botões de toggle
     */
    updateViewToggleState(buttons, activeView) {
        buttons.forEach(btn => {
            if (btn.dataset.view === activeView) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    },

    /**
     * Anexar listeners nos cards de contas (reattach after re-render)
     */
    attachContaCardListeners() {
        // Botões de novo lançamento
        document.querySelectorAll('.btn-new-transaction').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                const contaId = btn.dataset.contaId;
                const conta = STATE.contas.find(c => c.id == contaId);
                Modules.Lancamento.openLancamentoModal(contaId, conta?.nome || 'Conta');
            });
        });
    },
};

// ─── ContasMoneyMask ─────────────────────────────────────────────────────────

export const ContasMoneyMask = {

    /**
     * Configurar máscara de dinheiro para saldo da conta
     */
    setupMoneyMask() {
        const saldoInput = document.getElementById('saldoInicial');
        if (!saldoInput) return;

        saldoInput.addEventListener('input', (e) => {
            let value = e.target.value;

            // Remove tudo que não é número ou sinal de menos
            value = value.replace(/[^\d-]/g, '');

            // Verifica se é negativo
            const isNegative = value.startsWith('-');

            // Remove o sinal para processar
            value = value.replace('-', '');

            // Converte para número
            let number = parseInt(value) || 0;

            // Formata como moeda
            const formatted = Utils.formatMoneyInput(number, isNegative);

            e.target.value = formatted;
        });

        // Formata ao carregar
        saldoInput.value = '0,00';
    },

    /**
     * Configurar máscara de dinheiro para limite do cartão
     */
    setupCartaoMoneyMask() {
        const limiteInput = document.getElementById('limiteTotal');
        if (!limiteInput) return;

        limiteInput.addEventListener('input', (e) => {
            let value = e.target.value;

            // Remove tudo que não é número
            value = value.replace(/[^\d]/g, '');

            // Converte para número
            let number = parseInt(value) || 0;

            // Formata como moeda
            const formatted = Utils.formatMoneyInput(number, false);

            e.target.value = formatted;
        });

        // Formata ao carregar
        limiteInput.value = '0,00';
    },

    /**
     * Configura máscara de dinheiro para input de lançamento
     */
    setupLancamentoMoneyMask() {
        const valorInput = document.getElementById('lancamentoValor');
        if (!valorInput) return;

        valorInput.addEventListener('input', (e) => {
            let value = e.target.value;

            // Remove tudo que não é número
            value = value.replace(/[^\d]/g, '');

            // Converte para número
            let number = parseInt(value) || 0;

            // Formata como moeda
            const formatted = Utils.formatMoneyInput(number, false);

            e.target.value = formatted;
        });

        // Formata ao carregar
        valorInput.value = '0,00';
    },
};

// ─── Register modules ────────────────────────────────────────────────────────

Modules.Events = ContasEvents;
Modules.MoneyMask = ContasMoneyMask;
