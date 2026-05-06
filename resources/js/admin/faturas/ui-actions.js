/**
 * ============================================================================
 * LUKRATO - Faturas / UI Action Methods
 * ============================================================================
 * Methods responsible for payment, edit and delete actions from UI.
 * ============================================================================
 */

import { CONFIG, DOM, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';
import { getApiPayload, getErrorMessage } from '../shared/api.js';
import { CustomSelectManager } from '../shared/custom-select.js';
import {
    bindEditCategoriaChange,
    getSelectedCategoriaPayload,
    populateEditCategoriaControls,
} from './item-category-controls.js';

let deleteItemScopeModal = null;
let deleteItemScopeResolver = null;
let deleteItemScopeResult = null;

function getDeleteItemScopeElements() {
    return {
        modalEl: document.getElementById('modalDeleteFaturaItemScope'),
        formEl: document.getElementById('deleteFaturaItemScopeForm'),
        titleEl: document.getElementById('modalDeleteFaturaItemScopeLabel'),
        subtitleEl: document.getElementById('deleteFaturaItemScopeModalSubtitle'),
        leadEl: document.getElementById('deleteFaturaItemScopeModalLead'),
        hintEl: document.getElementById('deleteFaturaItemScopeModalHint'),
        optionsEl: document.getElementById('deleteFaturaItemScopeOptions'),
        confirmButtonEl: document.getElementById('btnConfirmDeleteFaturaItemScope'),
    };
}

function resetDeleteItemScopeModal() {
    const { formEl, optionsEl } = getDeleteItemScopeElements();

    formEl?.reset();

    const defaultInput = optionsEl?.querySelector('input[value="item"]');
    if (defaultInput) {
        defaultInput.checked = true;
    }

    if (optionsEl) {
        optionsEl.hidden = false;
    }
}

function populateDeleteItemScopeModal(totalParcelas = 1) {
    const {
        titleEl,
        subtitleEl,
        leadEl,
        hintEl,
        optionsEl,
        confirmButtonEl,
    } = getDeleteItemScopeElements();
    const isParcelado = Number(totalParcelas) > 1;

    if (titleEl) {
        titleEl.textContent = 'Excluir item da fatura';
    }

    if (subtitleEl) {
        subtitleEl.textContent = isParcelado
            ? `Este item faz parte de um parcelamento de ${totalParcelas}x.`
            : 'Revise a exclusão antes de confirmar.';
    }

    if (leadEl) {
        leadEl.textContent = isParcelado
            ? 'Escolha se deseja remover apenas esta parcela ou o parcelamento completo.'
            : 'Esta ação não pode ser desfeita.';
    }

    if (hintEl) {
        hintEl.textContent = isParcelado
            ? 'Excluir todo o parcelamento remove todas as parcelas vinculadas a esta compra.'
            : 'O item será removido permanentemente da fatura.';
    }

    if (confirmButtonEl) {
        confirmButtonEl.textContent = isParcelado ? 'Continuar' : 'Excluir item';
    }

    if (optionsEl) {
        optionsEl.hidden = !isParcelado;

        const itemTitleEl = optionsEl.querySelector('[data-delete-fatura-scope-title="item"]');
        const itemTextEl = optionsEl.querySelector('[data-delete-fatura-scope-text="item"]');
        const parcelamentoTitleEl = optionsEl.querySelector('[data-delete-fatura-scope-title="parcelamento"]');
        const parcelamentoTextEl = optionsEl.querySelector('[data-delete-fatura-scope-text="parcelamento"]');

        if (itemTitleEl) {
            itemTitleEl.textContent = 'Apenas esta parcela';
        }

        if (itemTextEl) {
            itemTextEl.textContent = 'Remove somente o item atual da fatura.';
        }

        if (parcelamentoTitleEl) {
            parcelamentoTitleEl.textContent = `Todo o parcelamento (${totalParcelas} parcelas)`;
        }

        if (parcelamentoTextEl) {
            parcelamentoTextEl.textContent = 'Remove todas as parcelas vinculadas a esta compra parcelada.';
        }
    }

    const defaultInput = optionsEl?.querySelector('input[value="item"]');
    if (defaultInput) {
        defaultInput.checked = true;
    }
}

function ensureDeleteItemScopeModal() {
    const elements = getDeleteItemScopeElements();

    if (deleteItemScopeModal) {
        return {
            modal: deleteItemScopeModal,
            ...elements,
        };
    }

    if (!elements.modalEl || !window.bootstrap?.Modal) {
        return null;
    }

    window.LK?.modalSystem?.prepareBootstrapModal(elements.modalEl, { scope: 'page' });

    if (!elements.modalEl.dataset.bound) {
        elements.modalEl.dataset.bound = '1';

        elements.formEl?.addEventListener('submit', (event) => {
            event.preventDefault();

            const selectedScope = elements.optionsEl?.querySelector('input[name="deleteFaturaItemScopeOption"]:checked');
            deleteItemScopeResult = {
                scope: selectedScope?.value || 'item',
            };

            deleteItemScopeModal?.hide();
        });

        elements.modalEl.addEventListener('hidden.bs.modal', () => {
            const resolve = deleteItemScopeResolver;
            const result = deleteItemScopeResult;

            deleteItemScopeResolver = null;
            deleteItemScopeResult = null;
            resetDeleteItemScopeModal();

            if (typeof resolve === 'function') {
                resolve(result || null);
            }
        });
    }

    deleteItemScopeModal = window.bootstrap.Modal.getOrCreateInstance(elements.modalEl, {
        backdrop: true,
        keyboard: true,
        focus: true,
    });

    return {
        modal: deleteItemScopeModal,
        ...elements,
    };
}

function openDeleteItemScopeModal(totalParcelas = 1) {
    const references = ensureDeleteItemScopeModal();

    if (!references) {
        return Promise.resolve(null);
    }

    if (typeof deleteItemScopeResolver === 'function') {
        deleteItemScopeResolver(null);
    }

    deleteItemScopeResolver = null;
    deleteItemScopeResult = null;
    populateDeleteItemScopeModal(totalParcelas);

    return new Promise((resolve) => {
        deleteItemScopeResolver = resolve;
        references.modal.show();

        requestAnimationFrame(() => {
            const focusTarget = Number(totalParcelas) > 1
                ? references.optionsEl?.querySelector('input[name="deleteFaturaItemScopeOption"]:checked')
                : references.confirmButtonEl;
            focusTarget?.focus?.();
        });
    });
}

export const ActionMethods = {
    async toggleParcelaPaga(faturaId, itemId, marcarComoPago) {
        try {
            const acao = marcarComoPago ? 'pagar' : 'desfazer pagamento';

            const result = await Swal.fire({
                title: marcarComoPago ? 'Marcar como pago?' : 'Desfazer pagamento?',
                text: `Deseja realmente ${acao} este item?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: marcarComoPago ? '#10b981' : '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: marcarComoPago ? 'Sim, marcar como pago' : 'Sim, desfazer',
                cancelButtonText: 'Cancelar',
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Processando...',
                allowOutsideClick: false,
                heightAuto: false,
                didOpen: () => {
                    Swal.showLoading();
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                },
                customClass: {
                    container: 'swal-above-modal'
                }
            });

            await Modules.API.toggleItemFatura(faturaId, itemId, marcarComoPago);

            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: marcarComoPago ? 'Item marcado como pago' : 'Pagamento desfeito',
                timer: CONFIG.TIMEOUTS.successMessage,
                showConfirmButton: false,
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });

            await Modules.App.refreshAfterMutation(faturaId);

        } catch (error) {
            console.error('Erro ao alternar status:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Erro ao processar operação'),
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });
        }
    },

    async editarItemFatura(faturaId, itemId, descricaoAtual, valorAtual, categoriaIdAtual = null, subcategoriaIdAtual = null) {
        // Usar modal Bootstrap ao invés de SweetAlert2
        const modalEl = DOM.modalEditarItemFatura || document.getElementById('modalEditarItemFatura');
        if (!modalEl) {
            console.error('Modal de edição não encontrado');
            return;
        }

        window.LK?.modalSystem?.prepareBootstrapModal(modalEl, { scope: 'page' });
        bindEditCategoriaChange();

        // Preencher os campos do formulário
        document.getElementById('editItemFaturaId').value = faturaId;
        document.getElementById('editItemId').value = itemId;
        document.getElementById('editItemDescricao').value = descricaoAtual;
        document.getElementById('editItemValor').value = valorAtual.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        await populateEditCategoriaControls(categoriaIdAtual, subcategoriaIdAtual);

        // Abrir o modal
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        modal.show();
    },

    async salvarItemFatura() {
        const faturaId = document.getElementById('editItemFaturaId').value;
        const itemId = document.getElementById('editItemId').value;
        const novaDescricao = document.getElementById('editItemDescricao').value.trim();
        const novoValorStr = document.getElementById('editItemValor').value;
        const { categoriaId, subcategoriaId } = getSelectedCategoriaPayload();

        // Validações
        if (!novaDescricao) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Informe a descrição do item.',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        const novoValor = parseFloat(novoValorStr.replace(/\./g, '').replace(',', '.')) || 0;
        if (novoValor <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Informe um valor válido.',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        try {
            // Fechar o modal de edição
            const modalEl = DOM.modalEditarItemFatura || document.getElementById('modalEditarItemFatura');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            // Mostrar loading
            Swal.fire({
                title: 'Atualizando item...',
                html: 'Aguarde enquanto salvamos as alterações.',
                allowOutsideClick: false,
                heightAuto: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Chamar API para atualizar o item da fatura
            await Modules.API.atualizarItemFatura(faturaId, itemId, {
                descricao: novaDescricao,
                valor: novoValor,
                categoria_id: categoriaId,
                subcategoria_id: subcategoriaId
            });

            await Swal.fire({
                icon: 'success',
                title: 'Item Atualizado!',
                text: 'O item foi atualizado com sucesso.',
                timer: CONFIG.TIMEOUTS.successMessage,
                showConfirmButton: false,
                heightAuto: false
            });

            await Modules.App.refreshAfterMutation(faturaId);

        } catch (error) {
            console.error('Erro ao editar item:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Não foi possível atualizar o item.'),
                heightAuto: false
            });
        }
    },

    async excluirItemFatura(faturaId, itemId, ehParcelado, totalParcelas) {
        try {
            const titulo = 'Excluir Item?';
            const texto = 'Deseja realmente excluir este item da fatura?';
            const confirmBtn = 'Sim, excluir item';

            // Se for parcelado, oferecer opções
            if (ehParcelado && totalParcelas > 1) {
                const selection = await openDeleteItemScopeModal(totalParcelas);

                if (!selection?.scope) return;

                if (selection.scope === 'parcelamento') {
                    return await this.excluirParcelamentoCompleto(faturaId, itemId, totalParcelas);
                }
            }

            const result = await Swal.fire({
                title: titulo,
                text: texto,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: confirmBtn,
                cancelButtonText: 'Cancelar',
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Excluindo...',
                allowOutsideClick: false,
                heightAuto: false,
                didOpen: () => {
                    Swal.showLoading();
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                },
                customClass: {
                    container: 'swal-above-modal'
                }
            });

            await Modules.API.excluirItemFatura(faturaId, itemId);

            await Swal.fire({
                icon: 'success',
                title: 'Excluído!',
                text: 'Item removido da fatura.',
                timer: CONFIG.TIMEOUTS.successMessage,
                showConfirmButton: false,
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });

            // Recarregar parcelamentos
            await Modules.App.refreshAfterMutation(faturaId);


            // Fatura foi excluída (era o último item), fechar modal

        } catch (error) {
            console.error('Erro ao excluir item:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Não foi possível excluir o item.'),
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });
        }
    },

    async excluirParcelamentoCompleto(faturaId, itemId, totalParcelas) {
        const result = await Swal.fire({
            title: 'Excluir Parcelamento Completo?',
            html: `
                <p>Deseja realmente excluir <strong>todas as ${totalParcelas} parcelas</strong> deste parcelamento?</p>
                <p style="color: #ef4444; margin-top: 1rem;"><i data-lucide="triangle-alert"></i> Esta ação não pode ser desfeita!</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: `Sim, excluir ${totalParcelas} parcelas`,
            cancelButtonText: 'Cancelar',
            heightAuto: false,
            customClass: {
                container: 'swal-above-modal'
            },
            didOpen: () => {
                const container = document.querySelector('.swal2-container');
                if (container) container.style.zIndex = '99999';
                refreshIcons();
            }
        });

        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Excluindo parcelamento...',
            allowOutsideClick: false,
            heightAuto: false,
            didOpen: () => {
                Swal.showLoading();
                const container = document.querySelector('.swal2-container');
                if (container) container.style.zIndex = '99999';
            },
            customClass: {
                container: 'swal-above-modal'
            }
        });

        try {
            const response = await Modules.API.excluirParcelamentoDoItem(faturaId, itemId);

            await Swal.fire({
                icon: 'success',
                title: 'Parcelamento Excluído!',
                text: response.message || `${totalParcelas} parcelas removidas.`,
                timer: CONFIG.TIMEOUTS.successMessage,
                showConfirmButton: false,
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });

            // Recarregar parcelamentos
            await Modules.App.refreshAfterMutation(faturaId);

            // Verificar se a fatura ainda existe antes de reabrir o modal

            // Reabrir o modal com dados atualizados
            // Fatura foi excluída (era o último item), fechar modal

        } catch (error) {
            console.error('Erro ao excluir parcelamento:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Não foi possível excluir o parcelamento.'),
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });
        }
    },

    async pagarFaturaCompleta(faturaId, valorTotal) {
        try {
            // Primeiro buscar os dados da fatura e as contas disponíveis
            Swal.fire({
                title: 'Carregando...',
                html: 'Buscando informações da fatura e contas disponíveis.',
                allowOutsideClick: false,
                heightAuto: false,
                didOpen: () => {
                    Swal.showLoading();
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                },
                customClass: {
                    container: 'swal-above-modal'
                }
            });

            // Buscar fatura e contas em paralelo
            const [faturaResponse, contasResponse] = await Promise.all([
                Modules.API.buscarParcelamento(faturaId),
                Modules.API.listarContas()
            ]);

            const fatura = getApiPayload(faturaResponse, null);
            const contas = getApiPayload(contasResponse, []);

            if (!fatura?.cartao) {
                throw new Error('Dados da fatura incompletos');
            }

            const cartaoId = fatura.cartao.id;
            const contaPadraoId = fatura.cartao.conta_id || null;

            // Extrair mês/ano da descrição da fatura (ex: "Fatura 2/2026")
            const descricao = fatura.descricao || '';
            const match = descricao.match(/(\d+)\/(\d+)/);
            const mes = match ? match[1] : null;
            const ano = match ? match[2] : null;

            if (!mes || !ano) {
                throw new Error('Não foi possível identificar o mês/ano da fatura');
            }

            // Montar opções do select de contas
            let contasOptions = '';
            if (Array.isArray(contas) && contas.length > 0) {
                contas.forEach(conta => {
                    const saldo = conta.saldoAtual ?? conta.saldo_atual ?? conta.saldo ?? 0;
                    const saldoFormatado = Utils.formatMoney(saldo);
                    const isDefault = conta.id === contaPadraoId;
                    const saldoSuficiente = saldo >= valorTotal;
                    const contaLabel = `${conta.nome} - ${saldoFormatado}${isDefault ? ' (vinculada ao cartão)' : ''}${!saldoSuficiente ? ' (saldo insuficiente)' : ''}`;
                    contasOptions += `<option value="${conta.id}" ${isDefault ? 'selected' : ''}>${Utils.escapeHtml(contaLabel)}</option>`;
                });
            } else {
                throw new Error('Nenhuma conta disponível para débito');
            }

            const result = await Swal.fire({
                title: 'Pagar Fatura Completa?',
                html: `
                    <div class="fatura-pay-confirm__content">
                        <p class="fatura-pay-confirm__lead">Deseja realmente pagar todos os itens pendentes desta fatura?</p>
                        <div class="fatura-pay-confirm__total-card surface-card surface-card--clip">
                            <span class="fatura-pay-confirm__total-label">Valor total</span>
                            <strong class="fatura-pay-confirm__total-value">${Utils.formatMoney(valorTotal)}</strong>
                        </div>
                        <div class="fatura-pay-confirm__field">
                            <label class="fatura-pay-confirm__field-label" for="swalContaSelect">
                                <i data-lucide="landmark"></i>
                                <span>Conta para débito</span>
                            </label>
                            <div class="lk-select-wrapper fatura-pay-confirm__select-wrap">
                                <select id="swalContaSelect" class="swal2-select fatura-pay-confirm__select" data-lk-custom-select="form" data-lk-select-sort="alpha">
                                    ${contasOptions}
                                </select>
                            </div>
                        </div>
                        <p class="fatura-pay-confirm__help">O valor será debitado da conta selecionada.</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i data-lucide="check"></i> Sim, pagar tudo',
                cancelButtonText: 'Cancelar',
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal',
                    popup: 'lk-swal-popup lk-swal-confirm fatura-pay-confirm',
                    htmlContainer: 'fatura-pay-confirm__html',
                    confirmButton: 'fatura-pay-confirm__confirm',
                    cancelButton: 'fatura-pay-confirm__cancel',
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                    const popup = Swal.getPopup();
                    if (popup) {
                        CustomSelectManager.init(popup);
                    }
                    refreshIcons();
                },
                preConfirm: () => {
                    const contaSelect = document.getElementById('swalContaSelect');
                    const selectedContaId = contaSelect ? parseInt(contaSelect.value) : null;
                    if (!selectedContaId) {
                        Swal.showValidationMessage('Selecione uma conta para débito');
                        return false;
                    }
                    return { contaId: selectedContaId };
                }
            });

            if (!result.isConfirmed) return;

            const selectedContaId = result.value.contaId;

            Swal.fire({
                title: 'Processando pagamento...',
                html: 'Aguarde enquanto processamos o pagamento de todos os itens.',
                allowOutsideClick: false,
                heightAuto: false,
                didOpen: () => {
                    Swal.showLoading();
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                },
                customClass: {
                    container: 'swal-above-modal'
                }
            });

            // Chamar API que cria UM ÚNICO lançamento agrupado, passando a conta selecionada
            const response = await Modules.API.pagarFaturaCompleta(cartaoId, parseInt(mes), parseInt(ano), selectedContaId);

            if (!response.success) {
                throw new Error(response.message || 'Erro ao processar pagamento');
            }

            await Swal.fire({
                icon: 'success',
                title: 'Fatura Paga!',
                html: `
                    <p>${response.message || 'Fatura paga com sucesso!'}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor debitado:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${Utils.formatMoney(getApiPayload(response, {})?.valor_pago || valorTotal)}
                        </div>
                    </div>
                    <div style="color: #059669;">
                        <i data-lucide="circle-check" style="font-size: 2rem;"></i>
                    </div>
                `,
                timer: 3000,
                showConfirmButton: false,
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                    refreshIcons();
                }
            });

            await Modules.App.refreshAfterMutation(faturaId);

            // Fechar o modal após pagamento completo

        } catch (error) {
            console.error('Erro ao pagar fatura completa:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro ao pagar fatura',
                text: getErrorMessage(error, 'Não foi possível processar o pagamento. Tente novamente.'),
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
                }
            });
        }
    }
};
