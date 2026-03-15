/**
 * ============================================================================
 * LUKRATO — Contas / Modal
 * ============================================================================
 * All modal operations: open/close conta modal, institution sub-modal,
 * credit card modal, form submissions, context menu (more options).
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { refreshIcons, showConfirm } from '../shared/ui.js';
import { setupMoneyMask, setMoneyValue, getMoneyValue } from '../shared/money-mask.js';

// ─── ContasModal ─────────────────────────────────────────────────────────────

export const ContasModal = {

    // =====================================================================
    //  Conta Modal (create / edit)
    // =====================================================================

    /**
     * Abrir modal
     */
    openModal(mode = 'create', data = null) {
        const modalOverlay = document.getElementById('modalContaOverlay');
        const modal = document.getElementById('modalConta');
        const titulo = document.getElementById('modalContaTitulo');

        if (!modalOverlay || !modal) return;

        // Atualizar título
        if (titulo) {
            titulo.textContent = mode === 'edit' ? 'Editar Conta' : 'Nova Conta';
        }

        // Aplicar cor da conta no header do modal
        const modalHeader = modal.querySelector('.modal-header');
        if (mode === 'edit' && data) {
            const cor = data.instituicao_financeira?.cor_primaria || '#667eea';
            if (modalHeader) modalHeader.style.cssText = 'background: ' + cor + ' !important';
        } else {
            if (modalHeader) modalHeader.style.cssText = '';
        }

        // Preencher formulário se for edição
        if (mode === 'edit' && data) {

            document.getElementById('contaId').value = data.id;
            document.getElementById('nomeConta').value = data.nome;

            // Instituicao financeira - garantir que preenche corretamente
            const instituicaoId = data.instituicao_financeira_id || data.instituicao_financeira?.id || '';
            document.getElementById('instituicaoFinanceiraSelect').value = instituicaoId;

            document.getElementById('tipoContaSelect').value = data.tipo_conta || 'conta_corrente';
            document.getElementById('moedaSelect').value = data.moeda || 'BRL';

            // Atualizar símbolo da moeda
            Utils.updateCurrencySymbol(data.moeda || 'BRL');

            // Formatar saldo inicial
            const saldo = data.saldoInicial || data.saldo_inicial || 0;
            const isNegative = saldo < 0;
            const valorCentavos = Math.abs(saldo) * 100;
            document.getElementById('saldoInicial').value = Utils.formatMoneyInput(valorCentavos, isNegative);
        } else {
            // Limpar formulário para novo cadastro
            document.getElementById('formConta')?.reset();
            document.getElementById('contaId').value = '';
            document.getElementById('saldoInicial').value = '0,00';

            // Garantir que o símbolo seja BRL ao criar nova conta
            Utils.updateCurrencySymbol('BRL');
        }

        // Mostrar modal
        modalOverlay.classList.add('active');

        // Anexar listeners de fechar
        ContasModal.attachCloseModalListeners();

        // Focar no primeiro campo após animação
        setTimeout(() => {
            document.getElementById('nomeConta')?.focus();
        }, 300);
    },

    /**
     * Fechar modal
     */
    closeModal() {
        const modalOverlay = document.getElementById('modalContaOverlay');
        if (!modalOverlay) return;

        modalOverlay.classList.remove('active');

        // Restaurar scroll do body
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';

        // Resetar flag de submissão
        STATE.isSubmitting = false;

        // Resetar cor do header
        const modalHeader = document.querySelector('#modalConta .modal-header');
        if (modalHeader) modalHeader.style.cssText = '';

        // Limpar formulário após fechar
        setTimeout(() => {
            document.getElementById('formConta')?.reset();
            document.getElementById('contaId').value = '';
        }, 300);
    },

    /**
     * Anexar listeners para fechar modal
     */
    attachCloseModalListeners() {
        // Remover listeners antigos e adicionar novos
        document.querySelectorAll('.modal-close-btn, .modal-close').forEach(btn => {
            // Clonar o botão para remover todos os listeners antigos
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            // Adicionar novo listener
            newBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                ContasModal.closeModal();
            });
        });
    },

    // =====================================================================
    //  Instituição Sub-Modal
    // =====================================================================

    /**
     * Abrir modal de nova instituição
     */
    openNovaInstituicaoModal() {
        const overlay = document.getElementById('modalNovaInstituicaoOverlay');
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Focar no campo de nome
            setTimeout(() => {
                document.getElementById('nomeInstituicao')?.focus();
            }, 100);
        }
    },

    /**
     * Fechar modal de nova instituição
     */
    closeNovaInstituicaoModal() {
        const overlay = document.getElementById('modalNovaInstituicaoOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';

            // Limpar formulário
            document.getElementById('formNovaInstituicao')?.reset();
            document.getElementById('corInstituicao').value = '#3498db';
            ContasModal.updateColorPreview('#3498db');
        }
    },

    /**
     * Atualizar preview de cor
     */
    updateColorPreview(color) {
        const preview = document.getElementById('colorPreview');
        const value = document.getElementById('colorValue');
        if (preview) preview.style.background = color;
        if (value) value.textContent = color;
    },

    /**
     * Handler do formulário de nova instituição
     */
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

            // Adicionar a nova instituição à lista
            if (result.data) {
                STATE.instituicoes.push(result.data);
                Modules.Render.renderInstituicoesSelect();

                // Selecionar a nova instituição no select
                const select = document.getElementById('instituicaoFinanceiraSelect');
                if (select) {
                    select.value = result.data.id;
                }
            }

            ContasModal.closeNovaInstituicaoModal();
            Utils.showToast('Instituição criada com sucesso!', 'success');

        } catch (error) {
            Utils.showToast(error.message, 'error');
        }
    },

    // =====================================================================
    //  Context Menu (More Options)
    // =====================================================================

    /**
     * Mais opções da conta
     */
    moreConta(contaId, event) {
        // Prevenir propagação
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }

        const conta = STATE.contas.find(c => c.id === contaId);
        if (!conta) return;

        // Remover menus anteriores
        document.querySelectorAll('.context-menu').forEach(m => m.remove());

        // Criar menu
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

        // Posicionar relativo ao botão clicado
        if (event && event.target) {
            const button = event.target.closest('.btn-icon');
            if (button) {
                const rect = button.getBoundingClientRect();
                menuEl.style.position = 'absolute';
                menuEl.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                menuEl.style.left = (rect.left + window.scrollX - 150) + 'px'; // 150px é a largura aproximada do menu
            }
        }

        // Adicionar listeners
        menuEl.querySelectorAll('.menu-option').forEach(opt => {
            opt.addEventListener('click', (e) => {
                e.stopPropagation();
                const action = opt.dataset.action;

                switch (action) {
                    case 'edit':
                        Modules.API.editConta(contaId);
                        break;
                    case 'archive':
                        Modules.API.archiveConta(contaId);
                        break;
                    case 'delete':
                        Modules.API.deleteConta(contaId);
                        break;
                }

                menuEl.remove();
            });
        });

        // Fechar ao clicar fora
        setTimeout(() => {
            const closeMenu = (e) => {
                if (!menuEl.contains(e.target)) {
                    menuEl.remove();
                    document.removeEventListener('click', closeMenu);
                }
            };
            document.addEventListener('click', closeMenu);
        }, 100);
    },

    // =====================================================================
    //  Conta Form Submit
    // =====================================================================

    /**
     * Manipular submissão do formulário
     */
    async handleFormSubmit(form) {
        if (STATE.isSubmitting) {
            return;
        }

        STATE.isSubmitting = true;

        // Desabilitar botão submit
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
                instituicao_financeira_id: instituicaoId && instituicaoId !== '' && instituicaoId !== '0' ? parseInt(instituicaoId) : null,
                tipo_conta: formData.get('tipo_conta'),
                moeda: formData.get('moeda'),
                saldo_inicial: Utils.parseMoneyInput(saldoFormatado)
            };



            if (contaId) {
                await Modules.API.updateConta(parseInt(contaId), data);
            } else {
                await Modules.API.createConta(data);
            }
        } finally {
            // Restaurar botão
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    },

    // =====================================================================
    //  Cartão de Crédito Modal
    // =====================================================================

    /**
     * Abrir modal de cartão de crédito
     */
    openCartaoModal(mode = 'create', cartao = null) {
        const modalOverlay = document.getElementById('modalCartaoOverlay');
        const modal = document.getElementById('modalCartao');
        const titulo = document.getElementById('modalCartaoTitulo');

        if (!modalOverlay || !modal) {
            Utils.showToast('Modal de cartão não encontrado', 'error');
            return;
        }

        // Atualizar título
        if (titulo) {
            titulo.textContent = mode === 'edit' ? 'Editar Cartão de Crédito' : 'Novo Cartão de Crédito';
        }

        // Popular select de contas
        const contaSelect = document.getElementById('contaVinculada');
        if (contaSelect) {
            contaSelect.innerHTML = '<option value="">Selecione uma conta</option>';
            STATE.contas.forEach(conta => {
                const option = document.createElement('option');
                option.value = conta.id;
                option.textContent = conta.nome;
                contaSelect.appendChild(option);
            });
        }

        // Preencher dados se for edição
        if (mode === 'edit' && cartao) {
            document.getElementById('cartaoId').value = cartao.id;
            document.getElementById('nomeCartao').value = cartao.nome_cartao || '';
            document.getElementById('contaVinculada').value = cartao.conta_id || '';
            document.getElementById('bandeira').value = cartao.bandeira || 'visa';
            document.getElementById('ultimosDigitos').value = cartao.ultimos_digitos || '';

            // Formatar limite
            const limite = cartao.limite_total || 0;
            const limiteFormatado = Utils.formatMoneyInput(limite * 100, false);
            document.getElementById('limiteTotal').value = limiteFormatado;

            document.getElementById('diaFechamento').value = cartao.dia_fechamento || '';
            document.getElementById('diaVencimento').value = cartao.dia_vencimento || '';
        } else {
            // Limpar formulário para novo cadastro
            document.getElementById('formCartao')?.reset();
            document.getElementById('cartaoId').value = '';
            document.getElementById('limiteTotal').value = '0,00';
        }

        // Mostrar modal
        modalOverlay.classList.add('active');

        // Focar no primeiro campo após animação
        setTimeout(() => {
            document.getElementById('nomeCartao')?.focus();
        }, 300);
    },

    /**
     * Fechar modal de cartão
     */
    closeCartaoModal() {
        const modalOverlay = document.getElementById('modalCartaoOverlay');
        if (!modalOverlay) return;

        modalOverlay.classList.remove('active');

        // Restaurar scroll do body
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';

        setTimeout(() => {
            document.getElementById('formCartao')?.reset();
            document.getElementById('cartaoId').value = '';
            document.getElementById('limiteTotal').value = '0,00';
        }, 300);
    },

    /**
     * Manipular submissão do formulário de cartão
     */
    async handleCartaoSubmit(form) {
        const cartaoId = document.getElementById('cartaoId').value;
        const isEdit = !!cartaoId;

        const formData = {
            nome_cartao: document.getElementById('nomeCartao').value,
            conta_id: parseInt(document.getElementById('contaVinculada').value),
            bandeira: document.getElementById('bandeira').value,
            ultimos_digitos: document.getElementById('ultimosDigitos').value,
            limite_total: Utils.parseMoneyInput(document.getElementById('limiteTotal').value),
            dia_fechamento: parseInt(document.getElementById('diaFechamento').value) || null,
            dia_vencimento: parseInt(document.getElementById('diaVencimento').value) || null,
        };

        try {
            const csrfToken = await Utils.getCSRFToken();
            const url = isEdit
                ? `${CONFIG.API_URL}/cartoes/${cartaoId}`
                : `${CONFIG.API_URL}/cartoes`;

            const method = isEdit ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-HTTP-Method-Override': method
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erro ao salvar cartão');
            }

            Utils.showToast(
                isEdit ? 'Cartão atualizado com sucesso!' : 'Cartão criado com sucesso!',
                'success'
            );

            ContasModal.closeCartaoModal();
            Modules.API.loadContas(); // Recarregar para mostrar cartão vinculado

        } catch (error) {
            console.error('❌ Erro ao salvar cartão:', error);
            Utils.showToast(error.message || 'Erro ao salvar cartão', 'error');
        }
    }
};

// ─── Register in Modules ─────────────────────────────────────────────────────
Modules.Modal = ContasModal;
