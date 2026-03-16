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
import { refreshIcons, showToast } from '../shared/ui.js';
import { sugerirCategoriaIA as _sugerirCategoriaIA } from '../shared/ai-categorization.js';
import { loadLancamentoRecentHistory, renderLancamentoHistoryPlaceholder } from '../shared/lancamento-history.js';

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
        this._dataLoaded = false;
        this.pendingTipo = null;
        this.contextoAbertura = {
            source: 'global',
            presetAccountId: null,
            lockAccount: false,
            tipo: null
        };

        // Wizard state
        this.currentStep = 1;
        this.totalSteps = 5; // receita/despesa = 5, transferencia = 4
    }

    // ── Init ─────────────────────────────────────────────────────────────────
    init() {
        if (!this.eventosConfigurados) {
            this.configurarEventos();
            this.eventosConfigurados = true;
        }
    }

    // ── Data Loading ─────────────────────────────────────────────────────────
    normalizarContextoAbertura(options = {}) {
        const rawOptions = options && typeof options === 'object'
            ? options
            : (typeof options === 'string' || typeof options === 'number'
                ? { presetAccountId: options }
                : {});

        return {
            source: rawOptions.source === 'contas' ? 'contas' : 'global',
            presetAccountId: rawOptions.presetAccountId !== undefined && rawOptions.presetAccountId !== null && rawOptions.presetAccountId !== ''
                ? String(rawOptions.presetAccountId)
                : null,
            lockAccount: Boolean(rawOptions.lockAccount),
            tipo: typeof rawOptions.tipo === 'string' && rawOptions.tipo !== '' ? rawOptions.tipo : null
        };
    }

    aplicarContextoAbertura(contexto) {
        this.contextoAbertura = contexto;

        const label = document.getElementById('globalContaSelectLabelText');
        if (label) {
            label.textContent = contexto.source === 'contas' ? 'Conta atual' : 'Selecione a conta';
        }

        const hint = document.getElementById('globalContaContextHint');
        if (hint) {
            hint.textContent = contexto.source === 'contas'
                ? 'Abrimos com a conta desta tela. Se precisar, voce pode trocar antes de continuar.'
                : 'Escolha a conta para ver saldo e as ultimas movimentacoes.';
        }

        const select = document.getElementById('globalContaSelect');
        if (select) {
            select.disabled = this.contas.length === 0 || Boolean(contexto.lockAccount && this.contaSelecionada);
        }
    }

    atualizarEstadoTipo() {
        const hasContaSelecionada = Boolean(this.contaSelecionada);

        document.querySelectorAll('#globalStep1 [data-requires-account="1"]').forEach((button) => {
            button.disabled = !hasContaSelecionada;
            button.classList.toggle('is-disabled', !hasContaSelecionada);
        });

        const hint = document.getElementById('globalTipoContaHint');
        if (hint) {
            hint.hidden = hasContaSelecionada;
        }
    }

    sincronizarContaSelecionadaNoFormulario() {
        const contaIdInput = document.getElementById('globalLancamentoContaId');
        if (contaIdInput) {
            contaIdInput.value = this.contaSelecionada?.id ?? '';
        }

        if (this.tipoAtual === 'transferencia' && this.contaSelecionada) {
            this.preencherContasDestino();

            const contaDestinoSelect = document.getElementById('globalLancamentoContaDestino');
            if (contaDestinoSelect && contaDestinoSelect.value === String(this.contaSelecionada.id)) {
                contaDestinoSelect.value = '';
            }
        }
    }

    atualizarContaSelecionadaUI() {
        const contaInfo = document.getElementById('globalContaInfo');
        const nomeConta = document.getElementById('globalContaNome');
        const saldoConta = document.getElementById('globalContaSaldo');

        if (!this.contaSelecionada) {
            if (contaInfo) contaInfo.style.display = 'none';
            this.atualizarEstadoTipo();
            this.sincronizarContaSelecionadaNoFormulario();
            return;
        }

        if (nomeConta) nomeConta.textContent = this.contaSelecionada.nome;
        if (saldoConta) saldoConta.textContent = formatMoney(this.contaSelecionada.saldo);
        if (contaInfo) contaInfo.style.display = 'flex';

        this.atualizarEstadoTipo();
        this.sincronizarContaSelecionadaNoFormulario();
    }

    async atualizarHistoricoContaSelecionada() {
        const historicoContainer = document.getElementById('globalLancamentoHistorico');
        if (!historicoContainer) return;

        if (!this.contaSelecionada) {
            renderLancamentoHistoryPlaceholder(historicoContainer, 'Selecione uma conta para ver as ultimas movimentacoes.');
            return;
        }

        await loadLancamentoRecentHistory({
            contaId: this.contaSelecionada.id,
            containerEl: historicoContainer,
            limit: 5
        });
    }

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
            this._dataLoaded = true;
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
        }
    }

    // ── Select Population ────────────────────────────────────────────────────
    preencherSelectContas() {
        const select = document.getElementById('globalContaSelect');
        if (!select) return;
        const selectedId = this.contaSelecionada?.id ? String(this.contaSelecionada.id) : null;

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

        if (selectedId && this.contas.some(conta => String(conta.id) === selectedId)) {
            select.value = selectedId;
        }
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

        // Reset subcategoria ao trocar categorias
        this.resetSubcategoriaSelect();

        // Listener cascata: ao trocar categoria → preencher subcategorias
        if (!select.dataset.subcatListenerAttached) {
            select.dataset.subcatListenerAttached = '1';
            select.addEventListener('change', () => this.preencherSubcategorias(select.value));
        }
    }

    /**
     * Preencher select de subcategorias com base na categoria selecionada
     */
    async preencherSubcategorias(categoriaId) {
        const select = document.getElementById('globalLancamentoSubcategoria');
        const group = document.getElementById('globalSubcategoriaGroup');
        if (!select) return;

        if (!categoriaId) {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.style.display = 'none';
            return;
        }

        try {
            const base = getBaseUrl();
            const res = await fetch(`${base}api/categorias/${categoriaId}/subcategorias`);
            if (!res.ok) throw new Error();
            const json = await res.json();
            const subs = json?.data?.subcategorias ?? json?.data ?? [];

            select.innerHTML = '<option value="">Sem subcategoria</option>';
            subs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.nome;
                select.appendChild(opt);
            });

            if (group) group.style.display = subs.length > 0 ? 'block' : 'none';
        } catch {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.style.display = 'none';
        }
    }

    resetSubcategoriaSelect() {
        const select = document.getElementById('globalLancamentoSubcategoria');
        if (select) select.innerHTML = '<option value="">Sem subcategoria</option>';
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
                        if (pagoGroup) pagoGroup.style.display = 'block';
                    }
                }

                this.syncReminderVisibility();
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

            // Interceptar Enter nos inputs para avançar etapa em vez de submeter o form
            form.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    this.nextStep();
                }
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

        // Fechar modal com tecla ESC
        document.addEventListener('keydown', (e) => {
            const overlay = document.getElementById('modalLancamentoGlobalOverlay');
            if (e.key === 'Escape' && overlay && overlay.classList.contains('active')) {
                this.closeModal();
            }
        });
    }

    async onContaChange() {
        const select = document.getElementById('globalContaSelect');
        if (!select) return;

        const contaId = select.value;
        if (!contaId) {
            this.contaSelecionada = null;
            this.atualizarContaSelecionadaUI();
            await this.atualizarHistoricoContaSelecionada();
            return;
        }

        const option = select.options[select.selectedIndex];
        this.contaSelecionada = {
            id: contaId,
            nome: option?.dataset?.nome || option?.textContent || 'Conta',
            saldo: parseFloat(option?.dataset?.saldo || '0')
        };

        this.aplicarContextoAbertura(this.contextoAbertura);
        this.atualizarContaSelecionadaUI();
        await this.atualizarHistoricoContaSelecionada();

        if (this.pendingTipo && this.currentStep === 1) {
            const tipoPendente = this.pendingTipo;
            this.pendingTipo = null;
            await this.mostrarFormulario(tipoPendente);
        }
    }

    // ── Modal Management ─────────────────────────────────────────────────────
    async openModal(options = {}) {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (overlay) {
            const contexto = this.normalizarContextoAbertura(options);
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            await this.carregarDados();
            this.contaSelecionada = null;
            this.initWizard();
            this.aplicarContextoAbertura(contexto);

            const select = document.getElementById('globalContaSelect');
            const contaPadrao = contexto.presetAccountId
                || (this.contas.length === 1 ? String(this.contas[0].id) : '');

            if (select) {
                const contaExiste = contaPadrao
                    && Array.from(select.options).some((option) => String(option.value) === String(contaPadrao));
                select.value = contaExiste ? String(contaPadrao) : '';
            }

            this.pendingTipo = contexto.tipo;
            await this.onContaChange();
        }
    }

    closeModal() {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            this.pendingTipo = null;
            const headerGradient = overlay.querySelector('.lk-modal-header-gradient');
            if (headerGradient) headerGradient.style.setProperty('background', 'var(--color-primary)', 'important');
            this.initWizard();
        }
    }

    voltarEscolhaTipo() {
        this.goToStep(1);
        const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
        if (tituloEl) tituloEl.textContent = 'Nova Movimentação';
        const headerGradient = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-header-gradient');
        if (headerGradient) headerGradient.style.setProperty('background', 'var(--color-primary)', 'important');
        this.resetarFormulario();
    }

    // ── Wizard Step Engine ───────────────────────────────────────────────────
    initWizard() {
        this.currentStep = 1;
        this.tipoAtual = null;
        this.totalSteps = 5;
        this.resetarFormulario();
        this.aplicarContextoAbertura(this.contextoAbertura);
        this.atualizarContaSelecionadaUI();
        if (!this.contaSelecionada) {
            const historicoContainer = document.getElementById('globalLancamentoHistorico');
            if (historicoContainer) {
                renderLancamentoHistoryPlaceholder(historicoContainer, 'Selecione uma conta para ver as ultimas movimentacoes.');
            }
        }
        // Hide progress dots on step 1
        const progress = document.getElementById('globalWizardProgress');
        if (progress) progress.style.display = 'none';
        // Show step 1, hide others
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
    }

    renderProgress() {
        const container = document.getElementById('globalWizardProgress');
        if (!container) return;

        // Hide on step 1 (type selection)
        if (this.currentStep <= 1) {
            container.style.display = 'none';
            return;
        }
        container.style.display = 'flex';

        // Steps 2..totalSteps mapped to dot indices 0..(totalSteps-2)
        const dotCount = this.totalSteps - 1; // exclude step 1 from dots
        let html = '';
        for (let i = 0; i < dotCount; i++) {
            const stepNum = i + 2; // dot 0 → step 2, dot 1 → step 3, etc.
            let stateClass = 'pending';
            if (stepNum < this.currentStep) stateClass = 'completed';
            else if (stepNum === this.currentStep) stateClass = 'active';

            if (i > 0) {
                const lineClass = stepNum <= this.currentStep ? 'completed' : '';
                html += `<div class="lk-wizard-line ${lineClass}"></div>`;
            }
            html += `<div class="lk-wizard-dot ${stateClass}"></div>`;
        }
        container.innerHTML = html;
    }

    goToStep(n) {
        // Ensure valid range
        if (n < 1 || n > this.totalSteps) return;

        const prev = this.currentStep;
        this.currentStep = n;

        // Animate out previous step
        for (let i = 1; i <= 5; i++) {
            const step = document.getElementById(`globalStep${i}`);
            if (!step) continue;
            if (i === prev && i !== n) {
                step.classList.remove('active');
                step.style.display = 'none';
            }
        }

        // Show new step
        const newStep = document.getElementById(`globalStep${n}`);
        if (newStep) {
            newStep.style.display = '';
            newStep.classList.add('active');
        }

        this.renderProgress();

        // Compactar info da conta e esconder select nos steps 2+
        const contaInfo = document.getElementById('globalContaInfo');
        if (contaInfo) {
            contaInfo.classList.toggle('lk-conta-info--compact', n > 1);
        }
        const contaSelectGroup = document.getElementById('globalContaSelect')?.closest('.lk-form-group');
        if (contaSelectGroup) {
            contaSelectGroup.classList.toggle('lk-conta-select--hidden', n > 1);
        }

        // Scroll modal body to top
        const body = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-body-modern');
        if (body) body.scrollTop = 0;

        // Re-render icons
        refreshIcons();
    }

    nextStep() {
        if (!this.validateCurrentStep()) return;

        let next = this.currentStep + 1;

        // For transferência: skip step 3 (payment) and step 5 (category/recurrence)
        if (this.tipoAtual === 'transferencia') {
            if (next === 5) next = this.totalSteps + 1; // no step 5 for transfer
        }

        if (next > this.totalSteps) {
            // Submit the form
            this.salvarLancamento();
            return;
        }
        this.goToStep(next);
    }

    prevStep() {
        let prev = this.currentStep - 1;

        // For transferência: skip step 3 back to 2
        if (this.tipoAtual === 'transferencia') {
            // no special skip needed going back
        }

        if (prev < 1) prev = 1;
        this.goToStep(prev);
    }

    skipAndSave() {
        if (!this.validarFormulario()) return;
        this.salvarLancamento();
    }

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
    }

    validateCurrentStep() {
        const step = this.currentStep;

        if (step === 2) {
            const descricao = document.getElementById('globalLancamentoDescricao')?.value.trim() || '';
            const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value);
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
            if (this.tipoAtual === 'transferencia') {
                const contaDest = document.getElementById('globalLancamentoContaDestino')?.value;
                if (!contaDest) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione a conta de destino', customClass: { container: 'swal-above-modal' } });
                    return false;
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
                            Swal.fire({
                                icon: 'error', title: 'Limite Insuficiente',
                                html: `<p>O valor (${formatMoney(valor)}) excede o limite disponível.</p><p><strong>Limite:</strong> ${formatMoney(limiteDisponivel)}</p>`,
                                confirmButtonText: 'Entendi', customClass: { container: 'swal-above-modal' }
                            });
                            return false;
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
                        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cartão para o estorno', customClass: { container: 'swal-above-modal' } });
                        return false;
                    }
                }
            }
        }

        if (step === 4) {
            const data = document.getElementById('globalLancamentoData')?.value || '';
            if (!data) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe a data', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }

        // Step 5: validar parcelas e recorrência
        if (step === 5) {
            const parcelado = document.getElementById('globalLancamentoParcelado')?.checked;
            if (parcelado) {
                const totalParcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 0;
                if (totalParcelas < 2 || totalParcelas > 48) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'O número de parcelas deve ser entre 2 e 48', customClass: { container: 'swal-above-modal' } });
                    return false;
                }
            }
            const recorrente = document.getElementById('globalLancamentoRecorrente')?.checked;
            if (recorrente) {
                const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value;
                if (modo === 'quantidade') {
                    const total = parseInt(document.getElementById('globalLancamentoRecorrenciaTotal')?.value) || 0;
                    if (total < 2 || total > 120) {
                        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'A quantidade de repetições deve ser entre 2 e 120', customClass: { container: 'swal-above-modal' } });
                        return false;
                    }
                }
            }
        }

        return true;
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

        // Subcategoria
        this.resetSubcategoriaSelect();

        // Forma de pagamento
        this.resetarFormaPagamento();
        this.tipoAtual = null;

        // Reset wizard state
        this.currentStep = 1;
        this.totalSteps = 5;
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

        // Guard: transferência requer pelo menos 2 contas
        if (tipo === 'transferencia' && this.contas.length < 2) {
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

        this.tipoAtual = tipo;

        const tipoInput = document.getElementById('globalLancamentoTipo');
        if (tipoInput) tipoInput.value = tipo;
        const contaIdInput = document.getElementById('globalLancamentoContaId');
        if (contaIdInput) contaIdInput.value = this.contaSelecionada.id;

        // Set total steps based on type
        this.totalSteps = tipo === 'transferencia' ? 4 : 5;

        this.configurarCamposPorTipo(tipo);

        const titulos = { receita: 'Nova Receita', despesa: 'Nova Despesa', transferencia: 'Nova Transferência' };
        const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
        if (tituloEl) tituloEl.textContent = titulos[tipo] || 'Nova Movimentação';

        // Update step 2 question text
        const step2Title = document.getElementById('globalStep2Title');
        const step2Subtitle = document.getElementById('globalStep2Subtitle');
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

        // Update step 3 question text
        const step3Title = document.getElementById('globalStep3Title');
        const step3Subtitle = document.getElementById('globalStep3Subtitle');
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

        // ── Step 3 visibility per type ──
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

        // ── Step 5 visibility per type ──
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

        // ── Step 4: Pago toggle ──
        const pagoGroup = document.getElementById('globalPagoGroup');
        const pagoCheck = document.getElementById('globalLancamentoPago');
        if (pagoGroup) pagoGroup.style.display = showStep5Fields ? 'block' : 'none';
        if (pagoCheck) pagoCheck.checked = true;
        const quickSaveButton = document.getElementById('globalBtnQuickSave');
        if (quickSaveButton) quickSaveButton.style.display = showStep5Fields ? 'inline-flex' : 'none';

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

        this.configurarEventosLembrete();
        this.syncReminderVisibility();
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
            const defaultModo = 'infinito';
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

    // ── Card Subscription Toggles ────────────────────────────────────────────
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
    }

    toggleAssinaturaCartaoFim() {
        const modo = document.querySelector('input[name="global_assinatura_modo"]:checked')?.value || 'infinito';
        const fimGroup = document.getElementById('globalAssinaturaCartaoFimGroup');
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

        const pagoCheck = document.getElementById('globalLancamentoPago');
        if (pagoCheck && !pagoCheck._lkListenerAdded) {
            pagoCheck.addEventListener('change', () => {
                this.syncReminderVisibility();
            });
            pagoCheck._lkListenerAdded = true;
        }
    }

    clearReminderFields() {
        const tempoAviso = document.getElementById('globalLancamentoTempoAviso');
        const canalInapp = document.getElementById('globalCanalInapp');
        const canalEmail = document.getElementById('globalCanalEmail');
        const canaisDiv = document.getElementById('globalCanaisNotificacaoInline');

        if (tempoAviso) tempoAviso.value = '';
        if (canalInapp) canalInapp.checked = true;
        if (canalEmail) canalEmail.checked = true;
        if (canaisDiv) canaisDiv.style.display = 'none';
    }

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

    /**
     * Sugerir categoria usando IA com base na descrição do lançamento
     */
    async sugerirCategoriaIA() {
        await _sugerirCategoriaIA({
            descricaoInputId: 'globalLancamentoDescricao',
            categoriaSelectId: 'globalLancamentoCategoria',
            subcategoriaSelectId: 'globalLancamentoSubcategoria',
            subcategoriaGroupId: 'globalSubcategoriaGroup',
            btnId: 'btnGlobalAiSuggestCategoria',
            notify: (msg, type) => {
                const icons = { success: 'success', warning: 'warning', error: 'error' };
                Swal.fire({
                    icon: icons[type] || 'info',
                    title: type === 'error' ? 'Erro' : 'IA',
                    text: msg,
                    ...(type === 'success' ? { timer: 2000, showConfirmButton: false } : {}),
                    customClass: { container: 'swal-above-modal' },
                });
            },
        });
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
        // Reset assinatura
        const assinaturaGroup = document.getElementById('globalAssinaturaCartaoGroup');
        if (assinaturaGroup) assinaturaGroup.style.display = 'none';
        const assinaturaCheck = document.getElementById('globalLancamentoAssinaturaCartao');
        if (assinaturaCheck) assinaturaCheck.checked = false;
        const assinaturaDetalhes = document.getElementById('globalAssinaturaCartaoDetalhes');
        if (assinaturaDetalhes) assinaturaDetalhes.style.display = 'none';
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
            // Show assinatura group for card
            const assinaturaGroup = document.getElementById('globalAssinaturaCartaoGroup');
            if (assinaturaGroup) assinaturaGroup.style.display = 'block';
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
                const pagoGroup = document.getElementById('globalPagoGroup');
                if (pagoGroup) pagoGroup.style.display = 'block';
            }
            // Hide assinatura for non-card
            const assinaturaGroup = document.getElementById('globalAssinaturaCartaoGroup');
            if (assinaturaGroup) assinaturaGroup.style.display = 'none';
            const assinaturaCheck = document.getElementById('globalLancamentoAssinaturaCartao');
            if (assinaturaCheck) assinaturaCheck.checked = false;
            const assinaturaDetalhes = document.getElementById('globalAssinaturaCartaoDetalhes');
            if (assinaturaDetalhes) assinaturaDetalhes.style.display = 'none';
        }

        this.syncReminderVisibility();
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
            this.clearReminderFields();
        } else {
            if (cartaoGroup) { cartaoGroup.classList.remove('active'); cartaoGroup.style.display = 'none'; }
            const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
            if (cartaoSelect) cartaoSelect.value = '';
            const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
            if (parcelamentoGroup) parcelamentoGroup.style.display = 'block';
            const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
            const pagoGroup = document.getElementById('globalPagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'block';
        }

        this.syncReminderVisibility();
    }

    // ── Validation ───────────────────────────────────────────────────────────
    validarFormulario() {
        if (!this.tipoAtual) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o tipo de lançamento', customClass: { container: 'swal-above-modal' } });
            return false;
        }

        const contaId = this.contaSelecionada?.id || document.getElementById('globalContaSelect')?.value;
        if (!contaId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione a conta', customClass: { container: 'swal-above-modal' } });
            return false;
        }

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

        // Validar ranges de parcelas e recorrência
        const parcelado = document.getElementById('globalLancamentoParcelado')?.checked;
        if (parcelado) {
            const totalParcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 0;
            if (totalParcelas < 2 || totalParcelas > 48) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'O número de parcelas deve ser entre 2 e 48', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }
        const recorrente = document.getElementById('globalLancamentoRecorrente')?.checked;
        if (recorrente) {
            const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value;
            if (modo === 'quantidade') {
                const total = parseInt(document.getElementById('globalLancamentoRecorrenciaTotal')?.value) || 0;
                if (total < 2 || total > 120) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'A quantidade de repetições deve ser entre 2 e 120', customClass: { container: 'swal-above-modal' } });
                    return false;
                }
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
            subcategoria_id: document.getElementById('globalLancamentoSubcategoria')?.value || null,
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

                    // Assinatura/recorrência no cartão
                    const assinaturaCheck = document.getElementById('globalLancamentoAssinaturaCartao');
                    if (assinaturaCheck?.checked) {
                        dados.recorrente = '1';
                        dados.recorrencia_freq = document.getElementById('globalLancamentoAssinaturaFreq')?.value || 'mensal';
                        dados.eh_parcelado = false; // Assinatura não é parcelamento

                        const modoAssinatura = document.querySelector('input[name="global_assinatura_modo"]:checked')?.value || 'infinito';
                        if (modoAssinatura === 'data') {
                            const fimAssinatura = document.getElementById('globalLancamentoAssinaturaFim')?.value || null;
                            if (fimAssinatura) {
                                dados.recorrencia_fim = fimAssinatura;
                            }
                        }
                    } else {
                        dados.eh_parcelado = document.getElementById('globalLancamentoParcelado')?.checked || false;
                        if (dados.eh_parcelado) {
                            dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 1;
                        }
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
            if (tempoAviso && !dados.cartao_credito_id && !dados.pago) {
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
                    subcategoria_id: dados.subcategoria_id || null,
                    forma_pagamento: dados.forma_pagamento || null,
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
            const isSuccess = response.ok && (result.success === true || response.status === 201);

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
                showToast(result.message || 'Lançamento salvo com sucesso!', 'success');

                if (typeof window.refreshDashboard === 'function') {
                    window.refreshDashboard();
                } else if (window.LK?.refreshDashboard) {
                    window.LK.refreshDashboard();
                }

                const currentPath = window.location.pathname.toLowerCase();
                if (currentPath.includes('contas') && window.contasManager && typeof window.contasManager.loadContas === 'function') {
                    await window.contasManager.loadContas();
                }

                document.dispatchEvent(new CustomEvent('lukrato:data-changed', {
                    detail: {
                        resource: 'transactions',
                        action: 'create',
                        source: 'lancamento-global',
                        payload: result.data
                    }
                }));
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
window.LK = window.LK || {};
window.LK.modals = window.LK.modals || {};
window.LK.modals.openLancamentoModal = (options = {}) => manager.openModal(options);

// ── Bootstrap ────────────────────────────────────────────────────────────────
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => manager.init());
} else {
    manager.init();
}

export default manager;
