/**
 * ============================================================================
 * LUKRATO - Faturas / UI Action Methods
 * ============================================================================
 * Methods responsible for payment, edit and delete actions from UI.
 * ============================================================================
 */

import { CONFIG, DOM, STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';
import { getApiPayload, getErrorMessage } from '../shared/api.js';

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

            // Recarregar parcelamentos e reabrir modal atualizado
            await Modules.App.carregarParcelamentos();

            // Reabrir o modal com dados atualizados
            setTimeout(() => {
                this.showDetalhes(faturaId);
            }, 100);

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

    async editarItemFatura(faturaId, itemId, descricaoAtual, valorAtual) {
        // Usar modal Bootstrap ao invés de SweetAlert2
        const modalEl = DOM.modalEditarItemFatura || document.getElementById('modalEditarItemFatura');
        if (!modalEl) {
            console.error('Modal de edição não encontrado');
            return;
        }

        // Preencher os campos do formulário
        document.getElementById('editItemFaturaId').value = faturaId;
        document.getElementById('editItemId').value = itemId;
        document.getElementById('editItemDescricao').value = descricaoAtual;
        document.getElementById('editItemValor').value = valorAtual.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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
            await Utils.apiRequest(`api/faturas/${faturaId}/itens/${itemId}`, {
                method: 'PUT',
                body: JSON.stringify({
                    descricao: novaDescricao,
                    valor: novoValor
                })
            });

            await Swal.fire({
                icon: 'success',
                title: 'Item Atualizado!',
                text: 'O item foi atualizado com sucesso.',
                timer: CONFIG.TIMEOUTS.successMessage,
                showConfirmButton: false,
                heightAuto: false
            });

            // Recarregar parcelamentos e reabrir modal atualizado
            await Modules.App.carregarParcelamentos();

            // Reabrir o modal com dados atualizados
            setTimeout(() => {
                this.showDetalhes(faturaId);
            }, 100);

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
            let titulo = 'Excluir Item?';
            let texto = 'Deseja realmente excluir este item da fatura?';
            let confirmBtn = 'Sim, excluir item';

            // Se for parcelado, oferecer opções
            if (ehParcelado && totalParcelas > 1) {
                const { value: opcao } = await Swal.fire({
                    title: 'O que deseja excluir?',
                    html: `
                        <p>Este item faz parte de um parcelamento de <strong>${totalParcelas}x</strong>.</p>
                        <p style="margin-top: 1rem;">Escolha uma opção:</p>
                    `,
                    icon: 'question',
                    input: 'radio',
                    inputOptions: {
                        'item': 'Apenas esta parcela',
                        'parcelamento': `Todo o parcelamento (${totalParcelas} parcelas)`
                    },
                    inputValue: 'item',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Continuar',
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

                if (!opcao) return;

                if (opcao === 'parcelamento') {
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

            await Utils.apiRequest(`api/faturas/${faturaId}/itens/${itemId}`, {
                method: 'DELETE'
            });

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
            await Modules.App.carregarParcelamentos();

            // Verificar se a fatura ainda existe antes de reabrir o modal
            const faturaAindaExiste = STATE.parcelamentos.some(p => p.id === faturaId);

            if (faturaAindaExiste) {
                // Reabrir o modal com dados atualizados
                setTimeout(() => {
                    this.showDetalhes(faturaId);
                }, 100);
            } else {
                // Fatura foi excluída (era o último item), fechar modal
                if (STATE.modalDetalhesInstance) {
                    STATE.modalDetalhesInstance.hide();
                }
            }

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
            const response = await Utils.apiRequest(`api/faturas/${faturaId}/itens/${itemId}/parcelamento`, {
                method: 'DELETE'
            });

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
            await Modules.App.carregarParcelamentos();

            // Verificar se a fatura ainda existe antes de reabrir o modal
            const faturaAindaExiste = STATE.parcelamentos.some(p => p.id === faturaId);

            if (faturaAindaExiste) {
                // Reabrir o modal com dados atualizados
                setTimeout(() => {
                    this.showDetalhes(faturaId);
                }, 100);
            } else {
                // Fatura foi excluída (era o último item), fechar modal
                if (STATE.modalDetalhesInstance) {
                    STATE.modalDetalhesInstance.hide();
                }
            }

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
                    const statusClass = saldoSuficiente ? 'color: #059669;' : 'color: #dc2626;';
                    contasOptions += `<option value="${conta.id}" ${isDefault ? 'selected' : ''} ${!saldoSuficiente ? 'style="color: #dc2626;"' : ''}>
                        ${Utils.escapeHtml(conta.nome)} - ${saldoFormatado}${isDefault ? ' (vinculada ao cartão)' : ''}
                    </option>`;
                });
            } else {
                throw new Error('Nenhuma conta disponível para débito');
            }

            const result = await Swal.fire({
                title: 'Pagar Fatura Completa?',
                html: `
                    <p>Deseja realmente pagar todos os itens pendentes desta fatura?</p>
                    <div style="margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                        <div style="font-size: 0.875rem; color: #047857; margin-bottom: 0.5rem;">Valor Total:</div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">${Utils.formatMoney(valorTotal)}</div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; text-align: left; margin-bottom: 0.5rem; color: #374151; font-weight: 500;">
                            <i data-lucide="landmark"></i> Conta para débito:
                        </label>
                        <select id="swalContaSelect" class="swal2-select" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                            ${contasOptions}
                        </select>
                    </div>
                    <p style="color: #6b7280; font-size: 0.875rem;">O valor será debitado da conta selecionada.</p>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i data-lucide="check"></i> Sim, pagar tudo',
                cancelButtonText: 'Cancelar',
                heightAuto: false,
                customClass: {
                    container: 'swal-above-modal'
                },
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '99999';
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

            await Modules.App.carregarParcelamentos();

            // Fechar o modal após pagamento completo
            STATE.modalDetalhesInstance.hide();

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
