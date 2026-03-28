/**
 * ============================================================================
 * LUKRATO - Contas / Modal
 * ============================================================================
 * All modal operations for contas: open/close, institution sub-modal,
 * form submissions and context menu.
 * ============================================================================
 */

import { STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';
import { getApiPayload, getErrorMessage } from '../shared/api.js';

export const ContasModal = {
    syncScrollLock() {
        const hasActiveOverlay = document.querySelector(
            '#modalContaOverlay.active, #modalNovaInstituicaoOverlay.active'
        );
        const overflowValue = hasActiveOverlay ? 'hidden' : '';

        document.body.style.overflow = overflowValue;
        document.documentElement.style.overflow = overflowValue;
    },

    bindOverlayClose(overlayId, closeHandler) {
        const overlay = document.getElementById(overlayId);
        if (!overlay) return;

        if (!overlay.dataset.overlayCloseBound) {
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) {
                    closeHandler();
                }
            });
            overlay.dataset.overlayCloseBound = 'true';
        }

        overlay.querySelectorAll('.modal-close, .modal-close-btn').forEach((button) => {
            if (button.dataset.closeHandlerBound) return;

            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                closeHandler();
            });
            button.dataset.closeHandlerBound = 'true';
        });
    },

    attachCloseModalListeners() {
        ContasModal.bindOverlayClose('modalContaOverlay', () => ContasModal.closeModal());
        ContasModal.bindOverlayClose('modalNovaInstituicaoOverlay', () => ContasModal.closeNovaInstituicaoModal());
    },

    closeActiveOverlay() {
        const instituicaoOverlay = document.getElementById('modalNovaInstituicaoOverlay');
        if (instituicaoOverlay?.classList.contains('active')) {
            ContasModal.closeNovaInstituicaoModal();
            return true;
        }

        const contaOverlay = document.getElementById('modalContaOverlay');
        if (contaOverlay?.classList.contains('active')) {
            ContasModal.closeModal();
            return true;
        }

        return false;
    },

    openModal(mode = 'create', data = null) {
        const modalOverlay = document.getElementById('modalContaOverlay');
        const modal = document.getElementById('modalConta');
        const titulo = document.getElementById('modalContaTitulo');

        if (!modalOverlay || !modal) return;

        if (titulo) {
            titulo.textContent = mode === 'edit' ? 'Editar Conta' : 'Nova Conta';
        }

        const modalHeader = modal.querySelector('.modal-header');
        if (mode === 'edit' && data) {
            const cor = data.instituicao_financeira?.cor_primaria || '#667eea';
            if (modalHeader) modalHeader.style.cssText = `background: ${cor} !important`;
        } else if (modalHeader) {
            modalHeader.style.cssText = '';
        }

        if (mode === 'edit' && data) {
            document.getElementById('contaId').value = data.id;
            document.getElementById('nomeConta').value = data.nome;

            const instituicaoId = data.instituicao_financeira_id || data.instituicao_financeira?.id || '';
            document.getElementById('instituicaoFinanceiraSelect').value = instituicaoId;
            document.getElementById('tipoContaSelect').value = data.tipo_conta || 'conta_corrente';
            document.getElementById('moedaSelect').value = data.moeda || 'BRL';

            Utils.updateCurrencySymbol(data.moeda || 'BRL');

            const saldo = data.saldoInicial || data.saldo_inicial || 0;
            const isNegative = saldo < 0;
            const valorCentavos = Math.round(Math.abs(saldo) * 100);
            document.getElementById('saldoInicial').value = Utils.formatMoneyInput(valorCentavos, isNegative);
        } else {
            document.getElementById('formConta')?.reset();
            document.getElementById('contaId').value = '';
            document.getElementById('saldoInicial').value = '0,00';
            Utils.updateCurrencySymbol('BRL');
        }

        modalOverlay.classList.add('active');
        ContasModal.syncScrollLock();
        ContasModal.attachCloseModalListeners();

        setTimeout(() => {
            document.getElementById('nomeConta')?.focus();
        }, 300);
    },

    closeModal() {
        const modalOverlay = document.getElementById('modalContaOverlay');
        if (!modalOverlay) return;

        modalOverlay.classList.remove('active');
        ContasModal.syncScrollLock();
        STATE.isSubmitting = false;

        const modalHeader = document.querySelector('#modalConta .modal-header');
        if (modalHeader) modalHeader.style.cssText = '';

        setTimeout(() => {
            document.getElementById('formConta')?.reset();
            document.getElementById('contaId').value = '';
        }, 300);
    },

    openNovaInstituicaoModal() {
        const overlay = document.getElementById('modalNovaInstituicaoOverlay');
        if (!overlay) return;

        overlay.classList.add('active');
        ContasModal.syncScrollLock();
        ContasModal.attachCloseModalListeners();

        setTimeout(() => {
            document.getElementById('nomeInstituicao')?.focus();
        }, 100);
    },

    closeNovaInstituicaoModal() {
        const overlay = document.getElementById('modalNovaInstituicaoOverlay');
        if (!overlay) return;

        overlay.classList.remove('active');
        ContasModal.syncScrollLock();

        document.getElementById('formNovaInstituicao')?.reset();
        document.getElementById('corInstituicao').value = '#3498db';
        ContasModal.updateColorPreview('#3498db');
    },

    updateColorPreview(color) {
        const preview = document.getElementById('colorPreview');
        const value = document.getElementById('colorValue');
        if (preview) preview.style.background = color;
        if (value) value.textContent = color;
    },

    async handleNovaInstituicaoSubmit(form) {
        const formData = new FormData(form);
        const data = {
            nome: formData.get('nome'),
            tipo: formData.get('tipo'),
            cor_primaria: formData.get('cor_primaria'),
            cor_secundaria: '#FFFFFF'
        };

        try {
            const result = await Modules.API.createInstituicao(data);
            const instituicao = getApiPayload(result, null);

            if (instituicao) {
                STATE.instituicoes.push(instituicao);
                Modules.Render.renderInstituicoesSelect();

                const select = document.getElementById('instituicaoFinanceiraSelect');
                if (select) {
                    select.value = instituicao.id;
                }
            }

            ContasModal.closeNovaInstituicaoModal();
            Utils.showToast('Instituicao criada com sucesso!', 'success');
        } catch (error) {
            Utils.showToast(getErrorMessage(error, 'Erro ao criar instituicao'), 'error');
        }
    },

    moreConta(contaId, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }

        const conta = STATE.contas.find((item) => item.id === contaId);
        if (!conta) return;
        if (conta.is_demo) {
            Utils.showToast('Essa conta e apenas um exemplo. Crie uma conta real para editar ou arquivar.', 'info');
            return;
        }

        document.querySelectorAll('.context-menu').forEach((menu) => menu.remove());

        const menuEl = document.createElement('div');
        menuEl.className = 'context-menu';
        menuEl.innerHTML = `
            <div class="menu-option" data-action="edit">
                <i data-lucide="pencil"></i>
                <span>Editar</span>
            </div>
            <div class="menu-option" data-action="archive">
                <i data-lucide="archive"></i>
                <span>Arquivar</span>
            </div>
        `;

        document.body.appendChild(menuEl);
        refreshIcons();

        if (event?.target) {
            const button = event.target.closest('.btn-icon');
            if (button) {
                const rect = button.getBoundingClientRect();
                menuEl.style.position = 'absolute';
                menuEl.style.top = `${rect.bottom + window.scrollY + 5}px`;
                menuEl.style.left = `${rect.left + window.scrollX - 150}px`;
            }
        }

        menuEl.querySelectorAll('.menu-option').forEach((option) => {
            option.addEventListener('click', (clickEvent) => {
                clickEvent.stopPropagation();

                switch (option.dataset.action) {
                    case 'edit':
                        Modules.API.editConta(contaId);
                        break;
                    case 'archive':
                        Modules.API.archiveConta(contaId);
                        break;
                    case 'delete':
                        Modules.API.deleteConta(contaId);
                        break;
                    default:
                        break;
                }

                menuEl.remove();
            });
        });

        setTimeout(() => {
            const closeMenu = (closeEvent) => {
                if (!menuEl.contains(closeEvent.target)) {
                    menuEl.remove();
                    document.removeEventListener('click', closeMenu);
                }
            };
            document.addEventListener('click', closeMenu);
        }, 100);
    },

    async handleFormSubmit(form) {
        if (STATE.isSubmitting) {
            return;
        }

        STATE.isSubmitting = true;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn?.innerHTML;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="icon-spin"></i> Salvando...';
            refreshIcons();
        }

        try {
            const formData = new FormData(form);
            const contaId = document.getElementById('contaId')?.value;
            const saldoFormatado = formData.get('saldo_inicial');
            const instituicaoId = formData.get('instituicao_financeira_id');

            const data = {
                nome: formData.get('nome'),
                instituicao_financeira_id: instituicaoId && instituicaoId !== '' && instituicaoId !== '0'
                    ? parseInt(instituicaoId, 10)
                    : null,
                tipo_conta: formData.get('tipo_conta'),
                moeda: formData.get('moeda'),
                saldo_inicial: Utils.parseMoneyInput(saldoFormatado)
            };

            if (contaId) {
                await Modules.API.updateConta(parseInt(contaId, 10), data);
            } else {
                await Modules.API.createConta(data);
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    }
};

Modules.Modal = ContasModal;
