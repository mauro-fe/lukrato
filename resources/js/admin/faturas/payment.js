/**
 * LUKRATO — Faturas / Payment modal + global window functions
 */
import { DOM, STATE, Utils, Modules } from './state.js';
import { getApiPayload, getErrorMessage } from '../shared/api.js';
import { CustomSelectManager } from '../shared/custom-select.js';

// ============================================================================
// MODAL PAGAR FATURA - BOOTSTRAP
// ============================================================================

export const ModalPagarFatura = {
    instance: null,
    faturaId: null,
    valorTotal: null,
    cartaoId: null,
    mes: null,
    ano: null,
    contas: [],
    contaPadraoId: null,

    init() {
        const modalEl = DOM.modalPagarFatura || document.getElementById('modalPagarFatura');
        if (!modalEl) return;

        window.LK?.modalSystem?.prepareBootstrapModal(modalEl, { scope: 'page' });

        this.instance = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        this.attachEvents();
    },

    attachEvents() {
        // Botão Pagar Total
        document.getElementById('btnPagarTotal')?.addEventListener('click', () => {
            this.instance.hide();
            Modules.UI.pagarFaturaCompleta(this.faturaId, this.valorTotal);
        });

        // Botão Pagar Parcial - mostrar formulário
        document.getElementById('btnPagarParcial')?.addEventListener('click', () => {
            this.mostrarFormularioParcial();
        });

        // Botão voltar
        document.getElementById('btnVoltarEscolha')?.addEventListener('click', () => {
            this.mostrarEscolha();
        });

        // Botão confirmar pagamento
        document.getElementById('btnConfirmarPagamento')?.addEventListener('click', () => {
            this.confirmarPagamentoParcial();
        });

        // Máscara de valor
        const inputValor = document.getElementById('valorPagamentoParcial');
        if (inputValor) {
            inputValor.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value === '') {
                    e.target.value = '';
                    return;
                }
                value = (parseInt(value) / 100).toFixed(2);
                e.target.value = parseFloat(value).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            });

            inputValor.addEventListener('focus', (e) => {
                e.target.select();
            });
        }

        // Hover effects nos botões de opção
        document.querySelectorAll('.btn-opcao-pagamento').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'translateY(-2px)';
                btn.style.boxShadow = '0 8px 25px rgba(0,0,0,0.2)';
            });
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'translateY(0)';
                btn.style.boxShadow = 'none';
            });
        });
    },

    async abrir(faturaId, valorTotal) {
        this.faturaId = faturaId;
        this.valorTotal = valorTotal;

        // Atualizar displays
        document.getElementById('pagarFaturaId').value = faturaId;
        document.getElementById('pagarFaturaValorTotal').value = valorTotal;
        document.getElementById('valorTotalDisplay').textContent = Utils.formatMoney(valorTotal);
        document.getElementById('valorTotalInfo').textContent = `Valor total da fatura: ${Utils.formatMoney(valorTotal)}`;
        document.getElementById('valorPagamentoParcial').value = Utils.formatMoney(valorTotal).replace('R$ ', '');

        // Mostrar tela de escolha
        this.mostrarEscolha();

        // Carregar dados da fatura e contas
        await this.carregarDados();

        // Abrir modal
        this.instance.show();
    },

    async carregarDados() {
        try {
            const [faturaResponse, contasResponse] = await Promise.all([
                Modules.API.buscarParcelamento(this.faturaId),
                Modules.API.listarContas()
            ]);

            const fatura = getApiPayload(faturaResponse, null);
            this.contas = getApiPayload(contasResponse, []);

            if (!fatura?.cartao) {
                throw new Error('Dados da fatura incompletos');
            }

            this.cartaoId = fatura.cartao.id;
            this.contaPadraoId = fatura.cartao.conta_id || null;

            // Extrair mês/ano da descrição da fatura
            const descricao = fatura.descricao || '';
            const match = descricao.match(/(\d+)\/(\d+)/);
            this.mes = match ? parseInt(match[1]) : null;
            this.ano = match ? parseInt(match[2]) : null;

            // Popular select de contas
            this.popularSelectContas();

        } catch (error) {
            console.error('Erro ao carregar dados:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Erro ao carregar dados da fatura.')
            });
        }
    },

    popularSelectContas() {
        const select = document.getElementById('contaPagamentoFatura');
        if (!select) return;

        select.innerHTML = '';

        if (!Array.isArray(this.contas) || this.contas.length === 0) {
            select.innerHTML = '<option value="">Nenhuma conta disponível</option>';
            return;
        }

        this.contas.forEach(conta => {
            const saldo = conta.saldoAtual ?? conta.saldo_atual ?? conta.saldo ?? 0;
            const saldoFormatado = Utils.formatMoney(saldo);
            const isDefault = conta.id === this.contaPadraoId;
            const option = document.createElement('option');
            option.value = conta.id;
            option.textContent = `${conta.nome} - ${saldoFormatado}${isDefault ? ' (vinculada ao cartão)' : ''}`;
            if (isDefault) option.selected = true;
            select.appendChild(option);
        });

        CustomSelectManager.init(DOM.modalPagarFatura || select);
    },

    mostrarEscolha() {
        document.getElementById('pagarFaturaEscolha').style.display = 'block';
        document.getElementById('pagarFaturaFormParcial').style.display = 'none';
        document.getElementById('pagarFaturaFooter').style.display = 'none';
    },

    mostrarFormularioParcial() {
        document.getElementById('pagarFaturaEscolha').style.display = 'none';
        document.getElementById('pagarFaturaFormParcial').style.display = 'block';
        document.getElementById('pagarFaturaFooter').style.display = 'flex';

        // Focar no input de valor
        setTimeout(() => {
            const input = document.getElementById('valorPagamentoParcial');
            if (input) {
                input.focus();
                input.select();
            }
        }, 100);
    },

    async confirmarPagamentoParcial() {
        const valorStr = document.getElementById('valorPagamentoParcial').value;
        const contaId = document.getElementById('contaPagamentoFatura').value;

        const valorPagar = parseFloat(valorStr.replace(/\./g, '').replace(',', '.')) || 0;

        // Validações
        if (valorPagar <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Valor inválido',
                text: 'Digite um valor válido para o pagamento.',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        if (valorPagar > this.valorTotal) {
            Swal.fire({
                icon: 'warning',
                title: 'Valor inválido',
                text: `O valor não pode ser maior que ${Utils.formatMoney(this.valorTotal)}`,
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        if (!contaId) {
            Swal.fire({
                icon: 'warning',
                title: 'Conta não selecionada',
                text: 'Selecione uma conta para débito.',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        if (!this.cartaoId || !this.mes || !this.ano) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Dados da fatura incompletos. Tente novamente.',
            });
            return;
        }

        // Fechar modal de pagamento
        this.instance.hide();

        // Processar pagamento
        Swal.fire({
            title: 'Processando pagamento...',
            html: 'Aguarde enquanto processamos o pagamento.',
            allowOutsideClick: false,
            heightAuto: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const response = await Modules.API.pagarFaturaParcial(
                this.cartaoId,
                this.mes,
                this.ano,
                parseInt(contaId, 10),
                valorPagar,
            );

            if (!response.success) {
                throw new Error(getErrorMessage(response, 'Erro ao processar pagamento'));
            }

            await Swal.fire({
                icon: 'success',
                title: 'Pagamento Realizado!',
                html: `
                    <p>${response.message || 'Pagamento efetuado com sucesso!'}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor pago:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${Utils.formatMoney(valorPagar)}
                        </div>
                    </div>
                `,
                timer: 3000,
                showConfirmButton: false
            });

            await Modules.App.refreshAfterMutation(this.faturaId);

        } catch (error) {
            console.error('Erro ao pagar fatura:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro ao pagar fatura',
                text: getErrorMessage(error, 'Não foi possível processar o pagamento. Tente novamente.')
            });
        }
    }
};

// ============================================================================
// GLOBAL WINDOW FUNCTIONS (called from PHP views)
// ============================================================================

export async function reverterPagamentoFaturaGlobal(faturaId) {
    // Usar dados da fatura atual
    const fatura = STATE.faturaAtual;

    if (!fatura || !fatura.cartao || !fatura.mes_referencia || !fatura.ano_referencia) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Dados da fatura incompletos para reverter o pagamento.'
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Desfazer Pagamento?',
        html: `
            <p>Você está prestes a <strong>reverter o pagamento</strong> de todos os itens desta fatura.</p>
            <div style="margin: 1rem 0; padding: 0.75rem; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                    <i data-lucide="triangle-alert"></i> 
                    O lançamento de pagamento será excluído e o valor voltará para a conta.
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i data-lucide="undo-2"></i> Sim, reverter',
        cancelButtonText: 'Cancelar',
        didOpen: () => { if (window.lucide) lucide.createIcons(); }
    });

    if (!result.isConfirmed) return;

    try {
        Swal.fire({
            title: 'Revertendo pagamento...',
            html: 'Aguarde enquanto processamos a reversão.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const cartaoId = fatura.cartao.id;
        const mes = fatura.mes_referencia;
        const ano = fatura.ano_referencia;

        const response = await Modules.API.desfazerPagamentoFatura(cartaoId, mes, ano);

        if (response.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Pagamento Revertido!',
                html: `
                    <p>${response.message || 'O pagamento foi revertido com sucesso.'}</p>
                    <p style="color: #059669; margin-top: 0.5rem;">
                        <i data-lucide="circle-check"></i> 
                        ${getApiPayload(response, {})?.itens_revertidos || 0} item(s) voltou(aram) para pendente.
                    </p>
                `,
                timer: 3000,
                showConfirmButton: false,
                didOpen: () => { if (window.lucide) lucide.createIcons(); }
            });

            await Modules.App.refreshAfterMutation(faturaId);
        } else {
            throw new Error(getErrorMessage(response, 'Erro ao reverter pagamento'));
        }
    } catch (error) {
        console.error('Erro ao reverter pagamento:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: getErrorMessage(error, 'Não foi possível reverter o pagamento.')
        });
    }
}

Modules.ModalPagarFatura = ModalPagarFatura;

