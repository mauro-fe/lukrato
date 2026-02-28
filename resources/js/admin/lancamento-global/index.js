/**
 * ============================================================================
 * LUKRATO — Lançamento Global (Header FAB Modal)
 * ============================================================================
 * Entry point Vite — recursos/js/admin/lancamento-global/index.js
 *
 * Refactored from public/assets/js/lancamento-global.js
 * Uses shared modules instead of duplicated utility functions.
 * ============================================================================
 */

import { formatMoney, parseMoney, calcularRecorrenciaFim } from '../shared/utils.js';
import { formatMoneyInput } from '../shared/utils.js';
import { getBaseUrl, getCSRFToken } from '../shared/api.js';
import { applyMoneyMask } from '../shared/money-mask.js';
import { refreshIcons } from '../shared/ui.js';

// ─────────────────────────────────────────────────────────────────────────────
class LancamentoGlobalManager {
    // ── State ────────────────────────────────────────────────────────────────
    constructor() {
        this.contaSelecionada = null;
        this.contas = [];
        this.categorias = [];
        this.cartoes = [];
        this.tipoAtual = null;
        this.eventosConfigurados = false;
        this.salvando = false;
        this.isEstornoCartao = false;
    }

    // ── Init ─────────────────────────────────────────────────────────────────
    init() {
        if (!this.eventosConfigurados) {
            this.configurarEventos();
            this.eventosConfigurados = true;
        }
    }

    // ── Data Loading ─────────────────────────────────────────────────────────
    async carregarDados() {
        const base = getBaseUrl();
        try {
            const resContas = await fetch(`${base}api/contas?with_balances=1`);
            if (!resContas.ok) {
                this.contas = [];
            } else {
                const dataContas = await resContas.json();
                const contasArray = dataContas.contas || dataContas || [];
                this.contas = contasArray.map(conta => ({
                    ...conta,
                    saldo: conta.saldoAtual !== undefined ? conta.saldoAtual : (conta.saldo_inicial || 0)
                }));
            }
            this.preencherSelectContas();

            const resCategorias = await fetch(`${base}api/categorias`);
            if (!resCategorias.ok) {
                this.categorias = [];
            } else {
                const dataCategorias = await resCategorias.json();
                let categoriasData = dataCategorias.categorias || dataCategorias.data || dataCategorias;
                this.categorias = Array.isArray(categoriasData) ? categoriasData : [];
            }

            const resCartoes = await fetch(`${base}api/cartoes`);
            if (!resCartoes.ok) {
                this.cartoes = [];
            } else {
                const dataCartoes = await resCartoes.json();
                let cartoesData = dataCartoes.cartoes || dataCartoes.data || dataCartoes;
                this.cartoes = Array.isArray(cartoesData) ? cartoesData : [];
            }
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
        }
    }

    // ── Select Population ────────────────────────────────────────────────────
    preencherSelectContas() {
        const select = document.getElementById('globalContaSelect');
        if (!select) return;

        const selectContainer = select.closest('.lk-select-container') || select.parentElement;
        const avisoExistente = selectContainer?.querySelector('.no-accounts-warning');
        if (avisoExistente) avisoExistente.remove();

        if (this.contas.length === 0) {
            select.innerHTML = '<option value="">Nenhuma conta disponível</option>';
            select.disabled = true;
            const aviso = document.createElement('div');
            aviso.className = 'no-accounts-warning';
            aviso.innerHTML = `
                <div class="alert alert-info d-flex align-items-center gap-2 mt-2 mb-0 py-2 px-3" style="font-size: 0.85rem; border-radius: 8px;">
                    <i data-lucide="info"></i>
                    <span>Você não possui contas cadastradas.</span>
                    <a href="${getBaseUrl()}contas" class="btn btn-sm btn-primary ms-auto" style="font-size: 0.75rem;">
                        <i data-lucide="plus" style="width:14px;height:14px;"></i>Criar Conta
                    </a>
                </div>
            `;
            selectContainer?.appendChild(aviso);
            return;
        }

        select.disabled = false;
        select.innerHTML = '<option value="">Escolha uma conta...</option>';
        this.contas.forEach(conta => {
            const option = document.createElement('option');
            option.value = conta.id;
            const saldo = conta.saldo !== undefined ? conta.saldo : (conta.saldoAtual !== undefined ? conta.saldoAtual : conta.saldo_inicial || 0);
            option.textContent = `${conta.nome} - ${formatMoney(saldo)}`;
            option.dataset.saldo = saldo;
            option.dataset.nome = conta.nome;
            select.appendChild(option);
        });
    }

    preencherContasDestino() {
        const select = document.getElementById('globalLancamentoContaDestino');
        if (!select) return;
        select.innerHTML = '<option value="">Selecione a conta de destino</option>';
        this.contas.forEach(conta => {
            if (conta.id != this.contaSelecionada.id) {
                const option = document.createElement('option');
                option.value = conta.id;
                const saldo = conta.saldo !== undefined ? conta.saldo : (conta.saldoAtual !== undefined ? conta.saldoAtual : conta.saldo_inicial || 0);
                option.textContent = `${conta.nome} - ${formatMoney(saldo)}`;
                select.appendChild(option);
            }
        });
    }

    preencherCartoes(isEstorno = false) {
        const select = document.getElementById('globalLancamentoCartaoCredito');
        if (!select) return;

        this.isEstornoCartao = isEstorno;
        const optionVazio = isEstorno
            ? '<option value="">Selecione o cartão</option>'
            : '<option value="">Não usar cartão (débito na conta)</option>';

        if (!Array.isArray(this.cartoes)) this.cartoes = [];
        if (this.cartoes.length === 0) {
            select.innerHTML = optionVazio;
            return;
        }

        const cartoesAtivos = this.cartoes.filter(c => c.ativo);
        const optionsCartoes = cartoesAtivos
            .map(c => `<option value="${c.id}">${c.nome_cartao || c.bandeira} •••• ${c.ultimos_digitos}</option>`)
            .join('');
        select.innerHTML = optionVazio + optionsCartoes;

        const faturaGroup = document.getElementById('globalFaturaEstornoGroup');
        if (faturaGroup) faturaGroup.style.display = 'none';
    }

    preencherCategorias(tipo) {
        const select = document.getElementById('globalLancamentoCategoria');
        if (!select) return;
        if (!Array.isArray(this.categorias)) this.categorias = [];

        if (this.categorias.length === 0) {
            select.innerHTML = '<option value="">Sem categoria</option>';
            return;
        }

        const categoriasFiltradas = this.categorias.filter(c => c.tipo === tipo);
        select.innerHTML = '<option value="">Sem categoria</option>';
        categoriasFiltradas.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.nome;
            select.appendChild(option);
        });
    }

    // ── Fatura Estorno ───────────────────────────────────────────────────────
    onCartaoEstornoChange() {
        const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
        const faturaGroup = document.getElementById('globalFaturaEstornoGroup');
        if (!cartaoSelect || !faturaGroup) return;
        if (!this.isEstornoCartao) { faturaGroup.style.display = 'none'; return; }
        const cartaoId = cartaoSelect.value;
        if (!cartaoId) { faturaGroup.style.display = 'none'; return; }
        faturaGroup.style.display = 'block';
        this.carregarFaturasEstorno(cartaoId);
    }

    carregarFaturasEstorno(cartaoId) {
        const faturaSelect = document.getElementById('globalLancamentoFaturaEstorno');
        if (!faturaSelect) return;
        faturaSelect.innerHTML = '<option value="">Carregando...</option>';

        const cartao = this.cartoes.find(c => c.id == cartaoId);
        if (!cartao) { faturaSelect.innerHTML = '<option value="">Erro ao carregar cartão</option>'; return; }

        const hoje = new Date();
        const mesAtual = hoje.getMonth() + 1;
        const anoAtual = hoje.getFullYear();
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        let options = '';
        for (let offset = -3; offset <= 5; offset++) {
            let mes = mesAtual + offset;
            let ano = anoAtual;
            if (mes < 1) { mes += 12; ano--; }
            else if (mes > 12) { mes -= 12; ano++; }
            const valor = `${ano}-${String(mes).padStart(2, '0')}`;
            const nomeMes = meses[mes - 1];
            const label = offset < 0 ? `${nomeMes}/${ano} (anterior)` : (offset === 0 ? `${nomeMes}/${ano} (atual)` : `${nomeMes}/${ano}`);
            options += `<option value="${valor}" ${offset === 0 ? 'selected' : ''}>${label}</option>`;
        }
        faturaSelect.innerHTML = options;
    }

    // ── Event Setup ──────────────────────────────────────────────────────────
    configurarEventos() {
        const valorInput = document.getElementById('globalLancamentoValor');
        if (valorInput) {
            valorInput.addEventListener('input', (e) => applyMoneyMask(e.target));
            valorInput.addEventListener('focus', (e) => {
                if (e.target.value === '0,00') e.target.value = '';
            });
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
                        if (lembreteGroup) lembreteGroup.style.display = 'block';
                        if (pagoGroup) pagoGroup.style.display = 'block';
                    }
                }
            });
        }

        const parceladoCheck = document.getElementById('globalLancamentoParcelado');
        if (parceladoCheck) {
            parceladoCheck.addEventListener('change', (e) => {
                document.getElementById('globalNumeroParcelasGroup').style.display = e.target.checked ? 'block' : 'none';
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
        }

        // Data e hora padrão
        const hoje = new Date();
        const dataInput = document.getElementById('globalLancamentoData');
        if (dataInput && !dataInput.value) {
            dataInput.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;
        }
        const horaInput = document.getElementById('globalLancamentoHora');
        if (horaInput && !horaInput.value) {
            horaInput.value = `${String(hoje.getHours()).padStart(2, '0')}:${String(hoje.getMinutes()).padStart(2, '0')}`;
        }
    }

    onContaChange() {
        const select = document.getElementById('globalContaSelect');
        const contaId = select.value;
        if (!contaId) {
            document.getElementById('globalContaInfo').style.display = 'none';
            this.contaSelecionada = null;
            return;
        }
        const option = select.options[select.selectedIndex];
        this.contaSelecionada = {
            id: contaId,
            nome: option.dataset.nome,
            saldo: parseFloat(option.dataset.saldo)
        };
        document.getElementById('globalContaNome').textContent = this.contaSelecionada.nome;
        document.getElementById('globalContaSaldo').textContent = formatMoney(this.contaSelecionada.saldo);
        document.getElementById('globalContaInfo').style.display = 'flex';
    }

    // ── Modal Management ─────────────────────────────────────────────────────
    async openModal() {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            await this.carregarDados();
            this.voltarEscolhaTipo();
            setTimeout(() => {
                if (this.contas.length === 1) {
                    const select = document.getElementById('globalContaSelect');
                    if (select) { select.value = this.contas[0].id; this.onContaChange(); }
                }
            }, 100);
        }
    }

    closeModal() {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            const headerGradient = overlay.querySelector('.lk-modal-header-gradient');
            if (headerGradient) headerGradient.style.setProperty('background', 'var(--color-primary)', 'important');
            this.resetarFormulario();
        }
    }

    voltarEscolhaTipo() {
        const formSection = document.getElementById('globalFormSection');
        if (formSection) formSection.style.display = 'none';
        const tipoSection = document.getElementById('globalTipoSection');
        if (tipoSection) tipoSection.style.display = 'block';
        const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
        if (tituloEl) tituloEl.textContent = 'Nova Movimentação';
        const headerGradient = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-header-gradient');
        if (headerGradient) headerGradient.style.setProperty('background', 'var(--color-primary)', 'important');
        this.resetarFormulario();
    }

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

        // Forma de pagamento
        this.resetarFormaPagamento();
        this.tipoAtual = null;
    }

    // ── Form Type Selection ──────────────────────────────────────────────────
    async mostrarFormulario(tipo) {
        if (this.contas.length === 0) {
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

        if (!this.contaSelecionada) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione uma conta primeiro!', customClass: { container: 'swal-above-modal' } });
            return;
        }

        if (this.categorias.length === 0 || this.cartoes.length === 0) {
            await this.carregarDados();
        }

        this.tipoAtual = tipo;
        const tipoSection = document.getElementById('globalTipoSection');
        if (tipoSection) tipoSection.style.display = 'none';
        const formSection = document.getElementById('globalFormSection');
        if (formSection) formSection.style.display = 'block';

        const tipoInput = document.getElementById('globalLancamentoTipo');
        if (tipoInput) tipoInput.value = tipo;
        const contaIdInput = document.getElementById('globalLancamentoContaId');
        if (contaIdInput) contaIdInput.value = this.contaSelecionada.id;

        this.configurarCamposPorTipo(tipo);

        const titulos = { receita: 'Nova Receita', despesa: 'Nova Despesa', transferencia: 'Nova Transferência' };
        const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
        if (tituloEl) tituloEl.textContent = titulos[tipo] || 'Nova Movimentação';
    }

    // ── Field Configuration by Type ──────────────────────────────────────────
    configurarCamposPorTipo(tipo) {
        // Header color
        const headerGradient = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-header-gradient');
        if (headerGradient) {
            headerGradient.classList.remove('receita', 'despesa', 'transferencia');
            const colors = {
                receita: 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
                despesa: 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)',
                transferencia: 'linear-gradient(135deg, #3498db 0%, #2980b9 100%)',
            };
            if (colors[tipo]) headerGradient.style.setProperty('background', colors[tipo], 'important');
        }

        // Conta Destino
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

        // Categoria
        this.preencherCategorias(tipo === 'receita' ? 'receita' : 'despesa');

        // Recorrência e Lembrete
        const showRecorrencia = (tipo === 'receita' || tipo === 'despesa');
        const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
        const lembreteGroup = document.getElementById('globalLembreteGroup');
        const recorrenciaDetalhes = document.getElementById('globalRecorrenciaDetalhes');
        const canaisNotificacaoInline = document.getElementById('globalCanaisNotificacaoInline');
        const recorrenteCheck = document.getElementById('globalLancamentoRecorrente');
        const tempoAvisoSelect = document.getElementById('globalLancamentoTempoAviso');

        if (recorrenciaGroup) recorrenciaGroup.style.display = showRecorrencia ? 'block' : 'none';
        if (lembreteGroup) lembreteGroup.style.display = showRecorrencia ? 'block' : 'none';
        if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
        if (canaisNotificacaoInline) canaisNotificacaoInline.style.display = 'none';
        if (recorrenteCheck) recorrenteCheck.checked = false;
        if (tempoAvisoSelect) tempoAvisoSelect.value = '';

        // Parcelamento
        const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
        const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
        const parceladoCheck = document.getElementById('globalLancamentoParcelado');

        if (showRecorrencia) {
            if (parcelamentoGroup) {
                parcelamentoGroup.style.display = 'block';
                const parcelTexto = parcelamentoGroup.querySelector('.lk-checkbox-text');
                if (tipo === 'receita') {
                    if (parcelTexto) parcelTexto.innerHTML = '<i data-lucide="calendar-days"></i> Parcelar recebimento';
                } else {
                    if (parcelTexto) parcelTexto.innerHTML = '<i data-lucide="calendar-days"></i> Parcelar pagamento';
                }
                refreshIcons();
                const parcelHelper = parcelamentoGroup.querySelector('.lk-helper-text');
                if (parcelHelper) parcelHelper.textContent = 'O valor total será dividido em parcelas mensais.';
            }
        } else {
            if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
        }
        if (numParcelasGroup) numParcelasGroup.style.display = 'none';
        if (parceladoCheck) parceladoCheck.checked = false;

        // Pago toggle
        const pagoGroup = document.getElementById('globalPagoGroup');
        const pagoCheck = document.getElementById('globalLancamentoPago');
        if (pagoGroup) pagoGroup.style.display = showRecorrencia ? 'block' : 'none';
        if (pagoCheck) pagoCheck.checked = true;

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
        const defaultModo = tipo === 'receita' ? 'quantidade' : 'infinito';
        if (radioInfinito) radioInfinito.style.display = tipo === 'receita' ? 'none' : '';
        const radios = document.querySelectorAll('input[name="global_recorrencia_modo"]');
        radios.forEach(r => r.checked = r.value === defaultModo);

        this.configurarEventosLembrete();
    }

    // ── Recurrence Toggles ───────────────────────────────────────────────────
    toggleRecorrencia() {
        const checkbox = document.getElementById('globalLancamentoRecorrente');
        const detalhes = document.getElementById('globalRecorrenciaDetalhes');
        if (detalhes) detalhes.style.display = checkbox?.checked ? 'block' : 'none';
        if (!checkbox?.checked) {
            const totalGroup = document.getElementById('globalRecorrenciaTotalGroup');
            const fimGroup = document.getElementById('globalRecorrenciaFimGroup');
            if (totalGroup) totalGroup.style.display = 'none';
            if (fimGroup) fimGroup.style.display = 'none';
            const defaultModo = this.tipoAtual === 'receita' ? 'quantidade' : 'infinito';
            document.querySelectorAll('input[name="global_recorrencia_modo"]').forEach(r => r.checked = r.value === defaultModo);
        }
    }

    toggleRecorrenciaFim() {
        const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value || 'infinito';
        const totalGroup = document.getElementById('globalRecorrenciaTotalGroup');
        const fimGroup = document.getElementById('globalRecorrenciaFimGroup');
        if (totalGroup) totalGroup.style.display = modo === 'quantidade' ? 'block' : 'none';
        if (fimGroup) fimGroup.style.display = modo === 'data' ? 'block' : 'none';
    }

    configurarEventosLembrete() {
        const tempoAviso = document.getElementById('globalLancamentoTempoAviso');
        if (tempoAviso && !tempoAviso._lkListenerAdded) {
            tempoAviso.addEventListener('change', () => {
                const canaisDiv = document.getElementById('globalCanaisNotificacaoInline');
                if (canaisDiv) canaisDiv.style.display = tempoAviso.value ? 'block' : 'none';
            });
            tempoAviso._lkListenerAdded = true;
        }
    }

    selecionarTipoAgendamento(tipo) {
        const input = document.getElementById('globalLancamentoTipoAgendamento');
        if (input) input.value = tipo;
        document.querySelectorAll('#globalTipoAgendamentoGroup .lk-btn-tipo-ag').forEach(btn => {
            btn.classList.remove('active');
            if (btn.classList.contains(`lk-btn-tipo-${tipo}`)) btn.classList.add('active');
        });
        this.preencherCategorias(tipo);
    }

    // ── Forma de Pagamento / Recebimento ─────────────────────────────────────
    resetarFormaPagamento() {
        document.querySelectorAll('#globalFormaPagamentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('#globalFormaRecebimentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        const formaPagInput = document.getElementById('globalFormaPagamento');
        if (formaPagInput) formaPagInput.value = '';
        const formaRecInput = document.getElementById('globalFormaRecebimento');
        if (formaRecInput) formaRecInput.value = '';
        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
        if (cartaoGroup) cartaoGroup.classList.remove('active');
        const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
        if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
        const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
        if (numParcelasGroup) numParcelasGroup.style.display = 'none';
    }

    selecionarFormaPagamento(forma) {
        document.querySelectorAll('#globalFormaPagamentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        const btnSelecionado = document.querySelector(`#globalFormaPagamentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        const formaPagInput = document.getElementById('globalFormaPagamento');
        if (formaPagInput) formaPagInput.value = forma;

        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
        const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
        const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');

        if (forma === 'cartao_credito') {
            if (cartaoGroup) { cartaoGroup.classList.add('active'); cartaoGroup.style.display = 'block'; }
            this.preencherCartoes();
            const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
            if (cartaoSelect?.value && parcelamentoGroup) parcelamentoGroup.style.display = 'block';
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
            const recorrenteCheck = document.getElementById('globalLancamentoRecorrente');
            if (recorrenteCheck) recorrenteCheck.checked = false;
            const recorrenciaDetalhes = document.getElementById('globalRecorrenciaDetalhes');
            if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
            const lembreteGroup = document.getElementById('globalLembreteGroup');
            if (lembreteGroup) lembreteGroup.style.display = 'none';
            const pagoGroup = document.getElementById('globalPagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'none';
        } else {
            if (cartaoGroup) { cartaoGroup.classList.remove('active'); cartaoGroup.style.display = 'none'; }
            if (parcelamentoGroup) {
                parcelamentoGroup.style.display = 'block';
                const parcelTexto = parcelamentoGroup.querySelector('.lk-parcel-texto');
                if (parcelTexto) {
                    parcelTexto.innerHTML = '<i data-lucide="split" class="icon-sm"></i> Parcelar pagamento';
                    refreshIcons();
                }
            }
            const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
            if (numParcelasGroup) numParcelasGroup.style.display = 'none';
            const parceladoCheck = document.getElementById('globalLancamentoParcelado');
            if (parceladoCheck) parceladoCheck.checked = false;
            const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
            if (cartaoSelect) cartaoSelect.value = '';
            if (this.tipoAtual !== 'transferencia') {
                if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
                const lembreteGroup = document.getElementById('globalLembreteGroup');
                if (lembreteGroup) lembreteGroup.style.display = 'block';
                const pagoGroup = document.getElementById('globalPagoGroup');
                if (pagoGroup) pagoGroup.style.display = 'block';
            }
        }
    }

    selecionarFormaRecebimento(forma) {
        document.querySelectorAll('#globalFormaRecebimentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        const btnSelecionado = document.querySelector(`#globalFormaRecebimentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        const formaRecInput = document.getElementById('globalFormaRecebimento');
        if (formaRecInput) formaRecInput.value = forma;

        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');

        if (forma === 'estorno_cartao') {
            if (cartaoGroup) { cartaoGroup.classList.add('active'); cartaoGroup.style.display = 'block'; }
            this.preencherCartoes(true);
            const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
            if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
            const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
            if (numParcelasGroup) numParcelasGroup.style.display = 'none';
            const parceladoCheck = document.getElementById('globalLancamentoParcelado');
            if (parceladoCheck) parceladoCheck.checked = false;
            const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
            const recorrenteCheck = document.getElementById('globalLancamentoRecorrente');
            if (recorrenteCheck) recorrenteCheck.checked = false;
            const recorrenciaDetalhes = document.getElementById('globalRecorrenciaDetalhes');
            if (recorrenciaDetalhes) recorrenciaDetalhes.style.display = 'none';
            const lembreteGroup = document.getElementById('globalLembreteGroup');
            if (lembreteGroup) lembreteGroup.style.display = 'none';
            const pagoGroup = document.getElementById('globalPagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'none';
        } else {
            if (cartaoGroup) { cartaoGroup.classList.remove('active'); cartaoGroup.style.display = 'none'; }
            const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
            if (cartaoSelect) cartaoSelect.value = '';
            const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
            if (parcelamentoGroup) parcelamentoGroup.style.display = 'block';
            const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
            const lembreteGroup = document.getElementById('globalLembreteGroup');
            if (lembreteGroup) lembreteGroup.style.display = 'block';
            const pagoGroup = document.getElementById('globalPagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'block';
        }
    }

    // ── Validation ───────────────────────────────────────────────────────────
    validarFormulario() {
        const descricao = document.getElementById('globalLancamentoDescricao')?.value.trim() || '';
        const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value);
        const data = document.getElementById('globalLancamentoData')?.value || '';

        if (!descricao) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe a descrição', customClass: { container: 'swal-above-modal' } });
            return false;
        }
        if (!valor || valor <= 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe um valor válido', customClass: { container: 'swal-above-modal' } });
            return false;
        }
        if (!data) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe a data', customClass: { container: 'swal-above-modal' } });
            return false;
        }

        // Validar limite do cartão
        if (this.tipoAtual === 'despesa') {
            const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
            if (cartaoId) {
                const cartao = this.cartoes.find(c => c.id == cartaoId);
                if (cartao) {
                    const limiteDisponivel = parseFloat(cartao.limite_disponivel || 0);
                    if (valor > limiteDisponivel) {
                        Swal.fire({
                            icon: 'error', title: 'Limite Insuficiente',
                            html: `<p>O valor da compra (${formatMoney(valor)}) excede o limite disponível do cartão.</p>
                                   <p><strong>Limite disponível:</strong> ${formatMoney(limiteDisponivel)}</p>`,
                            confirmButtonText: 'Entendi',
                            customClass: { container: 'swal-above-modal' }
                        });
                        return false;
                    }
                }
            }
        }

        if (this.tipoAtual === 'transferencia') {
            const contaDestino = document.getElementById('globalLancamentoContaDestino')?.value;
            if (!contaDestino) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione a conta de destino', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }
        return true;
    }

    // ── Data Collection ──────────────────────────────────────────────────────
    coletarDadosFormulario() {
        const contaId = this.contaSelecionada?.id;
        if (!contaId) throw new Error('Conta não selecionada');

        const dados = {
            conta_id: parseInt(contaId),
            tipo: document.getElementById('globalLancamentoTipo').value,
            descricao: document.getElementById('globalLancamentoDescricao').value.trim(),
            valor: parseMoney(document.getElementById('globalLancamentoValor').value),
            data: document.getElementById('globalLancamentoData').value,
            hora_lancamento: document.getElementById('globalLancamentoHora')?.value || null,
            categoria_id: document.getElementById('globalLancamentoCategoria').value || null,
            pago: true
        };

        if (this.tipoAtual === 'transferencia') {
            dados.conta_destino_id = parseInt(document.getElementById('globalLancamentoContaDestino')?.value) || null;
            dados.eh_transferencia = true;
        }

        // Forma de pagamento (despesa)
        if (this.tipoAtual === 'despesa') {
            const formaPag = document.getElementById('globalFormaPagamento')?.value || '';
            if (formaPag) dados.forma_pagamento = formaPag;
            if (formaPag === 'cartao_credito') {
                const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
                if (cartaoId) {
                    dados.cartao_credito_id = parseInt(cartaoId);
                    dados.eh_parcelado = document.getElementById('globalLancamentoParcelado')?.checked || false;
                    if (dados.eh_parcelado) {
                        dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 1;
                    }
                }
            }
        }

        // Forma de recebimento (receita)
        if (this.tipoAtual === 'receita') {
            const formaRec = document.getElementById('globalFormaRecebimento')?.value;
            if (formaRec) {
                dados.forma_pagamento = formaRec;
                if (formaRec === 'estorno_cartao') {
                    const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
                    if (cartaoId) dados.cartao_credito_id = parseInt(cartaoId);
                    const faturaVal = document.getElementById('globalLancamentoFaturaEstorno')?.value;
                    if (faturaVal) dados.fatura_mes_ano = faturaVal;
                }
            }
            if (document.getElementById('globalLancamentoParcelado')?.checked) {
                dados.eh_parcelado = true;
                dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 1;
            }
        }

        // Parcelamento despesa sem cartão
        if (this.tipoAtual === 'despesa' && !dados.cartao_credito_id) {
            if (document.getElementById('globalLancamentoParcelado')?.checked) {
                dados.eh_parcelado = true;
                dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 1;
            }
        }

        // Recorrência + Lembrete + Pago
        if (this.tipoAtual === 'receita' || this.tipoAtual === 'despesa') {
            dados.pago = document.getElementById('globalLancamentoPago')?.checked ? true : false;

            if (document.getElementById('globalLancamentoRecorrente')?.checked) {
                dados.recorrente = '1';
                dados.recorrencia_freq = document.getElementById('globalLancamentoRecorrenciaFreq')?.value || 'mensal';
                const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value || 'infinito';
                if (modo === 'quantidade') {
                    dados.recorrencia_total = parseInt(document.getElementById('globalLancamentoRecorrenciaTotal')?.value) || 12;
                } else if (modo === 'data') {
                    const recFim = document.getElementById('globalLancamentoRecorrenciaFim')?.value;
                    if (recFim) dados.recorrencia_fim = recFim;
                }
            }

            const tempoAviso = document.getElementById('globalLancamentoTempoAviso')?.value || '';
            if (tempoAviso && !dados.cartao_credito_id) {
                dados.lembrar_antes_segundos = parseInt(tempoAviso);
                dados.canal_inapp = document.getElementById('globalCanalInapp')?.checked ? '1' : '0';
                dados.canal_email = document.getElementById('globalCanalEmail')?.checked ? '1' : '0';
            }
        }

        return dados;
    }

    // ── Form Submission ──────────────────────────────────────────────────────
    async salvarLancamento() {
        if (this.salvando) return;
        if (!this.validarFormulario()) return;

        this.salvando = true;
        const base = getBaseUrl();

        try {
            const dados = this.coletarDadosFormulario();

            const btnSalvar = document.getElementById('globalBtnSalvar');
            if (btnSalvar) {
                btnSalvar.disabled = true;
                btnSalvar.innerHTML = '<i data-lucide="loader-2" class="icon-spin" style="width:16px;height:16px;display:inline-block;"></i> Salvando...';
                refreshIcons();
            }

            const csrfToken = getCSRFToken();
            let apiUrl = `${base}api/lancamentos`;
            let requestData = dados;

            if (this.tipoAtual === 'transferencia') {
                apiUrl = `${base}api/transfers`;
                requestData = {
                    conta_id: dados.conta_id,
                    conta_id_destino: dados.conta_destino_id,
                    valor: dados.valor,
                    data: dados.data,
                    descricao: dados.descricao,
                    observacao: dados.observacao
                };
            } else if (dados.eh_parcelado && dados.total_parcelas > 1 && !dados.cartao_credito_id) {
                apiUrl = `${base}api/parcelamentos`;
                requestData = {
                    descricao: dados.descricao,
                    valor_total: dados.valor,
                    numero_parcelas: dados.total_parcelas,
                    categoria_id: dados.categoria_id || null,
                    conta_id: dados.conta_id,
                    tipo: dados.tipo,
                    data_criacao: dados.data,
                };
                if (dados.lembrar_antes_segundos) {
                    requestData.lembrar_antes_segundos = dados.lembrar_antes_segundos;
                    requestData.canal_inapp = dados.canal_inapp || '0';
                    requestData.canal_email = dados.canal_email || '0';
                }
            }

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify(requestData)
            });

            const result = await response.json();
            const isSuccess = response.ok && (result.success === true || result.status === 'success' || response.status === 201);

            if (isSuccess) {
                const tipoLancamento = this.tipoAtual;

                // Gamificação
                if (result.data?.gamification) {
                    try {
                        const gamif = result.data.gamification;
                        if (gamif.achievements?.length > 0 && typeof window.notifyMultipleAchievements === 'function') {
                            window.notifyMultipleAchievements(gamif.achievements);
                        }
                        if (gamif.level_up && typeof window.notifyLevelUp === 'function') {
                            window.notifyLevelUp(gamif.level);
                        }
                    } catch (e) { console.error('Erro gamificação:', e); }
                }

                this.closeModal();
                await new Promise(r => setTimeout(r, 100));

                const titulos = { receita: 'Receita Criada!', despesa: 'Despesa Criada!', transferencia: 'Transferência Criada!' };
                await Swal.fire({
                    icon: 'success',
                    title: titulos[tipoLancamento] || 'Lançamento Criado!',
                    html: `<p style="font-size:1.1rem;margin-bottom:0.5rem;">${result.message || 'Seu lançamento foi salvo com sucesso!'}</p>
                           <p style="color:#666;font-size:0.9rem;"><i data-lucide="circle-check" style="width:16px;height:16px;display:inline-block;"></i> Dados atualizados</p>`,
                    confirmButtonText: 'Ok, entendi!',
                    confirmButtonColor: '#28a745',
                    allowOutsideClick: false,
                    customClass: { container: 'swal-above-modal', popup: 'animated fadeInDown faster' }
                });

                const currentPath = window.location.pathname.toLowerCase();
                if (currentPath.includes('contas') || currentPath.includes('lancamentos')) {
                    window.location.reload();
                    return;
                }

                window.dispatchEvent(new CustomEvent('lancamento-created', { detail: result.data }));
                this.salvando = false;
                this._resetBtnSalvar();
            } else {
                let errorMessage = result.message || 'Erro ao salvar lançamento';
                if (result.errors) {
                    const errorList = Object.values(result.errors).flat().join('\n');
                    errorMessage = errorList || errorMessage;
                }
                if (errorMessage.toLowerCase().includes('limite')) {
                    Swal.fire({ icon: 'error', title: 'Limite Insuficiente', text: errorMessage, confirmButtonText: 'Entendi', confirmButtonColor: '#d33', customClass: { container: 'swal-above-modal' } });
                    this.salvando = false;
                    this._resetBtnSalvar();
                    return;
                }
                throw new Error(errorMessage);
            }
        } catch (error) {
            console.error('Erro ao salvar lançamento:', error);
            this.salvando = false;
            this._resetBtnSalvar();
            Swal.fire({ icon: 'error', title: 'Erro', text: error.message, confirmButtonText: 'OK', customClass: { container: 'swal-above-modal' } });
        }
    }

    _resetBtnSalvar() {
        const btnSalvar = document.getElementById('globalBtnSalvar');
        if (btnSalvar) {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i data-lucide="save" style="width:16px;height:16px;display:inline-block;"></i> Salvar';
            refreshIcons();
        }
    }

    // ── Parcelamento Preview ─────────────────────────────────────────────────
    atualizarPreviewParcelamento() {
        const valor = parseMoney(document.getElementById('globalLancamentoValor').value);
        const parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas').value);
        const preview = document.getElementById('globalParcelamentoPreview');
        if (valor > 0 && parcelas >= 2) {
            const valorParcela = valor / parcelas;
            preview.innerHTML = `
                <div class="preview-info">
                    <i data-lucide="calculator" style="width:16px;height:16px;display:inline-block;"></i>
                    <span>${parcelas}x de ${formatMoney(valorParcela)}</span>
                </div>`;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }
}

// ── Singleton & Backward Compat ──────────────────────────────────────────────
const manager = new LancamentoGlobalManager();

// Expose on window for inline onclick handlers in PHP views
window.lancamentoGlobalManager = manager;

// ── Bootstrap ────────────────────────────────────────────────────────────────
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => manager.init());
} else {
    manager.init();
}

export default manager;
