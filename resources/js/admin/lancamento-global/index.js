/**
 * ============================================================================
 * LUKRATO â€” LanÃ§amento Global (Header FAB Modal)
 * ============================================================================
 * Entry point Vite â€” recursos/js/admin/lancamento-global/index.js
 *
 * Refactored from public/assets/js/lancamento-global.js
 * Uses shared modules instead of duplicated utility functions.
 * ============================================================================
 */

import '../../../css/admin/modal-lancamento/index.css';
import { formatMoney, parseMoney, calcularRecorrenciaFim, escapeHtml } from '../shared/utils.js';
import { formatMoneyInput } from '../shared/utils.js';
import { apiGet, apiPost, getBaseUrl, getErrorMessage, logClientError, logClientWarning } from '../shared/api.js';
import { applyMoneyMask } from '../shared/money-mask.js';
import { refreshIcons, showToast } from '../shared/ui.js';
import { sugerirCategoriaIA as _sugerirCategoriaIA } from '../shared/ai-categorization.js';
import { loadLancamentoRecentHistory, renderLancamentoHistoryPlaceholder } from '../shared/lancamento-history.js';
import { computeAccountEffect, getPlanningAlertsStore } from '../shared/planning-alerts.js';
import { CustomSelectManager, syncCustomSelects } from '../shared/custom-select.js';

function sortByLabel(items, resolver) {
    return [...items].sort((a, b) => {
        const labelA = String(resolver(a) || '').trim();
        const labelB = String(resolver(b) || '').trim();
        return labelA.localeCompare(labelB, 'pt-BR', { sensitivity: 'base' });
    });
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
class LancamentoGlobalManager {
    // â”€â”€ State â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    constructor() {
        this.contaSelecionada = null;
        this.contas = [];
        this.categorias = [];
        this.cartoes = [];
        this.metas = [];
        this.tipoAtual = null;
        this.eventosConfigurados = false;
        this.salvando = false;
        this.isEstornoCartao = false;
        this._dataLoaded = false;
        this.pendingTipo = null;
        this.planningStore = getPlanningAlertsStore();
        this.planningRenderSeq = 0;
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

    // â”€â”€ Init â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    init() {
        if (!this.eventosConfigurados) {
            this.configurarEventos();
            this.eventosConfigurados = true;
        }

        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (overlay) {
            CustomSelectManager.init(overlay);
            this.syncEnhancedSelects();
        }
    }

    // â”€â”€ Data Loading â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
                ? 'Abrimos com a conta desta tela. Se precisar, vocÃª pode trocar antes de continuar.'
                : 'Escolha a conta para ver saldo e as ultimas movimentaÃ§Ãµes.';
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

        this.syncEnhancedSelects();
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
            renderLancamentoHistoryPlaceholder(historicoContainer, 'Selecione uma conta para ver as ultimas movimentaÃ§Ãµes.');
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
            const dataContas = await apiGet(`${base}api/contas`, { with_balances: 1 }).catch(() => null);
            if (!dataContas) {
                this.contas = [];
            } else {
                const contasArray = Array.isArray(dataContas)
                    ? dataContas
                    : (Array.isArray(dataContas?.data)
                        ? dataContas.data
                        : (Array.isArray(dataContas?.contas) ? dataContas.contas : []));

                this.contas = sortByLabel(
                    contasArray.map(conta => ({
                        ...conta,
                        saldo: conta.saldoAtual !== undefined ? conta.saldoAtual : (conta.saldo_inicial || 0)
                    })),
                    (conta) => conta?.nome || conta?.instituicao || `Conta #${conta?.id ?? ''}`
                );
            }
            this.preencherSelectContas();

            const dataCategorias = await apiGet(`${base}api/categorias`).catch(() => null);
            if (!dataCategorias) {
                this.categorias = [];
            } else {
                let categoriasData = dataCategorias.categorias || dataCategorias.data || dataCategorias;
                this.categorias = Array.isArray(categoriasData)
                    ? sortByLabel(categoriasData, (categoria) => categoria?.nome || '')
                    : [];
            }

            const dataCartoes = await apiGet(`${base}api/cartoes`).catch(() => null);
            if (!dataCartoes) {
                this.cartoes = [];
            } else {
                let cartoesData = dataCartoes.cartoes || dataCartoes.data || dataCartoes;
                this.cartoes = Array.isArray(cartoesData)
                    ? sortByLabel(cartoesData, (cartao) => cartao?.nome_cartao || cartao?.bandeira || '')
                    : [];
            }

            const dataMetas = await apiGet(`${base}api/financas/metas`).catch(() => null);
            if (!dataMetas) {
                this.metas = [];
            } else {
                const metasData = Array.isArray(dataMetas)
                    ? dataMetas
                    : (Array.isArray(dataMetas?.data) ? dataMetas.data : []);

                this.metas = sortByLabel(
                    metasData.filter((meta) => {
                        const status = String(meta?.status || '').toLowerCase();
                        return status === 'ativa' || status === 'concluida';
                    }),
                    (meta) => meta?.titulo || ''
                );
            }
            this.preencherMetas();
            this._dataLoaded = true;
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
        }
    }

    getContaById(contaId) {
        return this.contas.find((conta) => String(conta.id) === String(contaId ?? '')) || null;
    }

    syncEnhancedSelects() {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (!overlay) return;
        syncCustomSelects(overlay);
    }

    getFormaPlanejamentoAtual() {
        if (this.tipoAtual === 'receita') {
            return document.getElementById('globalFormaRecebimento')?.value || '';
        }

        if (this.tipoAtual === 'despesa') {
            return document.getElementById('globalFormaPagamento')?.value || '';
        }

        return '';
    }

    getLancamentoPagoAtual() {
        if (this.tipoAtual === 'transferencia') {
            return true;
        }

        const pagoCheck = document.getElementById('globalLancamentoPago');
        return pagoCheck ? pagoCheck.checked !== false : true;
    }

    atualizarTextosParcelamento() {
        const textoParcelamento = document.getElementById('globalParcelamentoTexto');
        const helperParcelamento = document.getElementById('globalParcelamentoHelperText');
        const labelParcelas = document.getElementById('globalNumeroParcelasLabelTexto');
        const suffixParcelas = document.getElementById('globalNumeroParcelasSuffixTexto');

        const formaPagamento = document.getElementById('globalFormaPagamento')?.value || '';

        let textoCheckbox = 'Parcelar lançamento';
        let textoHelper = 'O valor total será dividido em parcelas futuras.';
        let textoLabel = 'Número de parcelas';
        let textoSuffix = 'parcelas';

        if (this.tipoAtual === 'receita') {
            textoCheckbox = 'Receber em parcelas';
            textoHelper = 'O valor total será dividido em recebimentos futuros.';
            textoLabel = 'Número de recebimentos';
            textoSuffix = 'recebimentos';
        } else if (this.tipoAtual === 'despesa') {
            textoCheckbox = formaPagamento === 'cartao_credito'
                ? 'Parcelar compra no cartão'
                : 'Parcelar pagamento';
            textoHelper = formaPagamento === 'cartao_credito'
                ? 'O valor total será dividido entre as próximas faturas.'
                : 'O valor total será dividido em pagamentos futuros.';
        }

        if (textoParcelamento) textoParcelamento.textContent = textoCheckbox;
        if (helperParcelamento) helperParcelamento.textContent = textoHelper;
        if (labelParcelas) labelParcelas.textContent = textoLabel;
        if (suffixParcelas) suffixParcelas.textContent = textoSuffix;
    }

    resumirTitulosMetas(metas = []) {
        const titulos = metas
            .map((meta) => String(meta?.titulo || '').trim())
            .filter(Boolean);

        if (titulos.length === 0) return 'suas metas';
        if (titulos.length === 1) return titulos[0];
        if (titulos.length === 2) return `${titulos[0]} e ${titulos[1]}`;
        return `${titulos[0]}, ${titulos[1]} e mais ${titulos.length - 2}`;
    }

    buildPlanningAlertCard({ tone = 'info', icon = 'target', eyebrow, title, message }) {
        return `
            <div class="lk-planning-alert is-${tone}">
                <div class="lk-planning-alert__icon">
                    <i data-lucide="${icon}"></i>
                </div>
                <div class="lk-planning-alert__body">
                    <span class="lk-planning-alert__eyebrow">${escapeHtml(eyebrow || 'Planejamento')}</span>
                    <strong class="lk-planning-alert__title">${escapeHtml(title || '')}</strong>
                    <p class="lk-planning-alert__message">${escapeHtml(message || '')}</p>
                </div>
            </div>
        `;
    }

    setPlanningAlertsContainer(containerId, notices) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (!Array.isArray(notices) || notices.length === 0) {
            container.innerHTML = '';
            container.hidden = true;
            return;
        }

        container.innerHTML = notices.join('');
        container.hidden = false;
        refreshIcons();
    }

    clearPlanningAlerts() {
        this.setPlanningAlertsContainer('globalContaPlanningAlerts', []);
        this.setPlanningAlertsContainer('globalCategoriaPlanningAlerts', []);
    }

    buildContaMetaAlert(conta, role = 'source') {
        if (!conta?.id) return '';

        const metas = this.planningStore.getMetasByConta(conta.id);
        if (!metas.length) return '';

        const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value);
        const saldoAtual = Number(conta.saldo ?? 0);
        const principal = [...metas].sort((a, b) => (a.valor_restante || 0) - (b.valor_restante || 0))[0] || metas[0];
        const resumoMetas = this.resumirTitulosMetas(metas);

        let delta = 0;
        if (this.tipoAtual === 'transferencia') {
            delta = computeAccountEffect({ type: 'transferencia', value: valor, role });
        } else if (this.tipoAtual) {
            delta = computeAccountEffect({
                type: this.tipoAtual,
                value: valor,
                paymentMethod: this.getFormaPlanejamentoAtual(),
                isPaid: this.getLancamentoPagoAtual()
            });
        }

        const saldoProjetado = saldoAtual + delta;
        const progressoProjetado = principal?.valor_alvo > 0
            ? Math.max(0, Math.min(100, (saldoProjetado / principal.valor_alvo) * 100))
            : null;
        const isCartaoCredito = this.tipoAtual === 'despesa' && this.getFormaPlanejamentoAtual() === 'cartao_credito';
        const isPendente = this.tipoAtual && this.getLancamentoPagoAtual() === false;

        const title = metas.length === 1
            ? `${role === 'destination' ? 'Conta de destino vinculada a' : 'Conta vinculada a'} ${principal.titulo}`
            : `${role === 'destination' ? 'Conta de destino ligada a' : 'Conta ligada a'} ${metas.length} metas`;

        let tone = 'info';
        let message = '';

        if (!this.tipoAtual) {
            message = metas.length === 1
                ? `O saldo desta conta alimenta a meta automaticamente. Hoje ela acompanha ${formatMoney(saldoAtual)}.`
                : `O saldo desta conta atualiza automaticamente ${resumoMetas}.`;
        } else if (delta === 0) {
            if (isCartaoCredito) {
                message = `Como o pagamento esta no cartao de credito, o saldo da conta nao muda agora. ${metas.length === 1 ? `A meta segue sincronizada com ${formatMoney(saldoAtual)}.` : `As metas ${resumoMetas} continuam sincronizadas com o saldo atual.`}`;
            } else if (isPendente) {
                message = `Enquanto este lancamento estiver pendente, o saldo da conta nao muda. ${metas.length === 1 ? `A meta continua em ${formatMoney(saldoAtual)}.` : `As metas ${resumoMetas} so mudam quando a movimentacao for confirmada.`}`;
            } else {
                message = metas.length === 1
                    ? `Essa movimentaÃ§Ã£o nÃ£o altera o saldo da conta agora. A meta segue acompanhando ${formatMoney(saldoAtual)}.`
                    : `Essa movimentaÃ§Ã£o nÃ£o altera o saldo da conta agora. ${resumoMetas} continuam sincronizadas com o valor atual.`;
            }
        } else {
            tone = saldoProjetado < 0 ? 'danger' : (delta < 0 ? 'warning' : 'success');
            message = `Saldo estimado apos salvar: ${formatMoney(saldoProjetado)}.`;

            if (metas.length === 1 && progressoProjetado !== null) {
                message += ` ${principal.titulo} ficaria em ${progressoProjetado.toFixed(1)}% do alvo de ${formatMoney(principal.valor_alvo)}.`;
            } else {
                message += ` ${resumoMetas} vao refletir esse novo saldo automaticamente.`;
            }
        }

        return this.buildPlanningAlertCard({
            tone,
            icon: saldoProjetado < 0 ? 'triangle-alert' : 'target',
            eyebrow: role === 'destination' ? 'Meta da conta de destino' : 'Meta vinculada',
            title,
            message
        });
    }

    async renderContaPlanningAlerts(renderId = this.planningRenderSeq) {
        const notices = [];

        await this.planningStore.ensureMetas();
        if (renderId !== this.planningRenderSeq) return;

        if (this.contaSelecionada) {
            const notice = this.buildContaMetaAlert(this.contaSelecionada, 'source');
            if (notice) notices.push(notice);
        }

        if (this.tipoAtual === 'transferencia') {
            const contaDestinoId = document.getElementById('globalLancamentoContaDestino')?.value || '';
            if (contaDestinoId && String(contaDestinoId) !== String(this.contaSelecionada?.id ?? '')) {
                const contaDestino = this.getContaById(contaDestinoId);
                const notice = this.buildContaMetaAlert(contaDestino, 'destination');
                if (notice) notices.push(notice);
            }
        }

        if (renderId !== this.planningRenderSeq) return;
        this.setPlanningAlertsContainer('globalContaPlanningAlerts', notices);
    }

    async renderCategoriaPlanningAlerts(renderId = this.planningRenderSeq) {
        if (this.tipoAtual !== 'despesa') {
            this.setPlanningAlertsContainer('globalCategoriaPlanningAlerts', []);
            return;
        }

        const categoriaId = document.getElementById('globalLancamentoCategoria')?.value || '';
        if (!categoriaId) {
            this.setPlanningAlertsContainer('globalCategoriaPlanningAlerts', []);
            return;
        }

        const dataLancamento = document.getElementById('globalLancamentoData')?.value || '';
        const orcamento = await this.planningStore.getBudgetByCategoria(categoriaId, dataLancamento);
        if (renderId !== this.planningRenderSeq) return;

        if (!orcamento) {
            this.setPlanningAlertsContainer('globalCategoriaPlanningAlerts', []);
            return;
        }

        const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value);
        const gastoAtual = Number(orcamento.gasto_real ?? 0);
        const limiteEfetivo = Number(orcamento.limite_efetivo ?? orcamento.valor_limite ?? 0);
        const gastoProjetado = gastoAtual + valor;
        const restante = Math.max(0, limiteEfetivo - gastoProjetado);
        const excesso = Math.max(0, gastoProjetado - limiteEfetivo);
        const percentual = limiteEfetivo > 0 ? (gastoProjetado / limiteEfetivo) * 100 : 0;
        const rollover = Number(orcamento.rollover_valor ?? 0);
        const categoriaNome = String(orcamento.categoria_nome || orcamento.categoria?.nome || 'categoria').trim();

        let tone = 'info';
        let title = `${categoriaNome} tem orcamento ativo`;
        let message = `Limite efetivo de ${formatMoney(limiteEfetivo)}. Depois deste lancamento, restam ${formatMoney(restante)} no periodo (${percentual.toFixed(1)}% usado).`;

        if (excesso > 0) {
            tone = 'danger';
            title = `${categoriaNome} estoura o orcamento`;
            message = `Limite efetivo de ${formatMoney(limiteEfetivo)}. O gasto projetado vai para ${formatMoney(gastoProjetado)} e passa ${formatMoney(excesso)} do limite.`;
        } else if (percentual >= 80) {
            tone = 'warning';
            title = `${categoriaNome} entra em alerta`;
            message = `Limite efetivo de ${formatMoney(limiteEfetivo)}. Depois deste lancamento, sobram ${formatMoney(restante)} no periodo (${percentual.toFixed(1)}% usado).`;
        }

        if (rollover > 0) {
            message += ` O limite inclui ${formatMoney(rollover)} de rollover.`;
        }

        this.setPlanningAlertsContainer('globalCategoriaPlanningAlerts', [
            this.buildPlanningAlertCard({
                tone,
                icon: excesso > 0 ? 'triangle-alert' : 'wallet',
                eyebrow: 'Orcamento do periodo',
                title,
                message
            })
        ]);
    }

    schedulePlanningAlertsRender() {
        const renderId = ++this.planningRenderSeq;
        void Promise.all([
            this.renderContaPlanningAlerts(renderId),
            this.renderCategoriaPlanningAlerts(renderId)
        ]);
    }

    // â”€â”€ Select Population â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    preencherSelectContas() {
        const select = document.getElementById('globalContaSelect');
        if (!select) return;
        const selectedId = this.contaSelecionada?.id ? String(this.contaSelecionada.id) : null;

        const selectContainer = select.closest('.lk-select-container') || select.parentElement;
        const avisoExistente = selectContainer?.querySelector('.no-accounts-warning');
        if (avisoExistente) avisoExistente.remove();

        if (this.contas.length === 0) {
            select.innerHTML = '<option value="">Nenhuma conta disponÃ­vel</option>';
            select.disabled = true;
            const aviso = document.createElement('div');
            aviso.className = 'no-accounts-warning';
            aviso.innerHTML = `
                <div class="alert alert-info d-flex align-items-center gap-2 mt-2 mb-0 py-2 px-3" style="font-size: 0.85rem; border-radius: 8px;">
                    <i data-lucide="info"></i>
                    <span>VocÃª nÃ£o possui contas cadastradas.</span>
                    <a href="${getBaseUrl()}contas" class="btn btn-sm btn-primary ms-auto" style="font-size: 0.75rem;">
                        <i data-lucide="plus" style="width:14px;height:14px;"></i>Criar Conta
                    </a>
                </div>
            `;
            selectContainer?.appendChild(aviso);
            this.syncEnhancedSelects();
            return;
        }

        select.disabled = false;
        select.innerHTML = '<option value="">Escolha uma conta...</option>';
        this.contas.forEach(conta => {
            const option = document.createElement('option');
            option.value = conta.id;
            const saldo = conta.saldo !== undefined ? conta.saldo : (conta.saldoAtual !== undefined ? conta.saldoAtual : conta.saldo_inicial || 0);
            const nomeConta = String(conta.nome || conta.instituicao || `Conta #${conta.id}`).trim();
            option.textContent = `${nomeConta} - ${formatMoney(saldo)}`;
            option.dataset.saldo = saldo;
            option.dataset.nome = nomeConta;
            select.appendChild(option);
        });

        if (selectedId && this.contas.some(conta => String(conta.id) === selectedId)) {
            select.value = selectedId;
        }

        this.syncEnhancedSelects();
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
                const nomeConta = String(conta.nome || conta.instituicao || `Conta #${conta.id}`).trim();
                option.textContent = `${nomeConta} - ${formatMoney(saldo)}`;
                select.appendChild(option);
            }
        });

        this.syncEnhancedSelects();
    }

    preencherMetas() {
        const select = document.getElementById('globalLancamentoMeta');
        if (!select) return;

        select.innerHTML = '<option value="">Nenhuma meta</option>';

        this.metas.forEach((meta) => {
            const option = document.createElement('option');
            option.value = meta.id;
            option.textContent = meta.titulo;
            option.dataset.status = String(meta?.status || '').toLowerCase();
            option.dataset.modelo = String(meta?.modelo || '').toLowerCase();
            select.appendChild(option);
        });

        this.syncEnhancedSelects();
    }

    getMetaSelecionada(rawMetaId = null) {
        const raw = rawMetaId !== null
            ? rawMetaId
            : (document.getElementById('globalLancamentoMeta')?.value || '');
        const metaId = Number.parseInt(String(raw), 10);
        if (!Number.isFinite(metaId) || metaId <= 0) {
            return null;
        }

        return this.metas.find((meta) => Number(meta?.id ?? 0) === metaId) || null;
    }

    shouldDefaultMetaRealizacao(meta) {
        if (!meta) return false;
        const status = String(meta?.status || '').toLowerCase();
        return status === 'concluida';
    }

    syncMetaLinkFields({ preserveAmount = true } = {}) {
        const metaSelect = document.getElementById('globalLancamentoMeta');
        const metaValorGroup = document.getElementById('globalMetaValorGroup');
        const metaValorInput = document.getElementById('globalLancamentoMetaValor');
        const metaRealizacaoGroup = document.getElementById('globalMetaRealizacaoGroup');
        const metaRealizacaoCheck = document.getElementById('globalLancamentoMetaRealizacao');
        const formaPagamento = String(document.getElementById('globalFormaPagamento')?.value || '').toLowerCase();

        const metaIdRaw = metaSelect?.value || '';
        const hasMeta = metaIdRaw !== '' && Number(metaIdRaw) > 0;
        const showAmount = hasMeta && ['receita', 'despesa', 'transferencia'].includes(String(this.tipoAtual || '').toLowerCase());
        const showRealizacao = hasMeta
            && String(this.tipoAtual || '').toLowerCase() === 'despesa'
            && formaPagamento !== 'cartao_credito';

        if (metaValorGroup) metaValorGroup.style.display = showAmount ? 'block' : 'none';
        if (metaRealizacaoGroup) metaRealizacaoGroup.style.display = showRealizacao ? 'block' : 'none';

        if (!hasMeta) {
            if (metaValorInput) metaValorInput.value = '';
            if (metaRealizacaoCheck) metaRealizacaoCheck.checked = false;
            return;
        }

        const valorLancamento = parseMoney(document.getElementById('globalLancamentoValor')?.value);
        if (metaValorInput) {
            const valorAtualMeta = parseMoney(metaValorInput.value);
            if (!preserveAmount || !(valorAtualMeta > 0)) {
                const valorBase = Math.max(0, valorLancamento || 0);
                metaValorInput.value = valorBase > 0 ? formatMoneyInput(Math.round(valorBase * 100)) : '';
            }
        }

        if (metaRealizacaoCheck) {
            if (!showRealizacao) {
                metaRealizacaoCheck.checked = false;
            } else if (!preserveAmount) {
                const meta = this.getMetaSelecionada(metaIdRaw);
                metaRealizacaoCheck.checked = this.shouldDefaultMetaRealizacao(meta);
            }
        }
    }

    onMetaChange() {
        this.syncMetaLinkFields({ preserveAmount: false });
        this.schedulePlanningAlertsRender();
    }

    preencherCartoes(isEstorno = false) {
        const select = document.getElementById('globalLancamentoCartaoCredito');
        if (!select) return;

        this.isEstornoCartao = isEstorno;
        const optionVazio = isEstorno
            ? '<option value="">Selecione o cartÃ£o</option>'
            : '<option value="">NÃ£o usar cartÃ£o (dÃ©bito na conta)</option>';

        if (!Array.isArray(this.cartoes)) this.cartoes = [];
        if (this.cartoes.length === 0) {
            select.innerHTML = optionVazio;
            this.syncEnhancedSelects();
            return;
        }

        const cartoesAtivos = this.cartoes.filter(c => c.ativo);
        const optionsCartoes = cartoesAtivos
            .map(c => `<option value="${c.id}">${c.nome_cartao || c.bandeira} â€¢â€¢â€¢â€¢ ${c.ultimos_digitos}</option>`)
            .join('');
        select.innerHTML = optionVazio + optionsCartoes;

        const faturaGroup = document.getElementById('globalFaturaEstornoGroup');
        if (faturaGroup) faturaGroup.style.display = 'none';
        this.syncEnhancedSelects();
    }

    preencherCategorias(tipo) {
        const select = document.getElementById('globalLancamentoCategoria');
        if (!select) return;
        if (!Array.isArray(this.categorias)) this.categorias = [];

        if (this.categorias.length === 0) {
            select.innerHTML = '<option value="">Sem categoria</option>';
            this.syncEnhancedSelects();
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

        // Listener cascata: ao trocar categoria â†’ preencher subcategorias
        if (!select.dataset.subcatListenerAttached) {
            select.dataset.subcatListenerAttached = '1';
            select.addEventListener('change', () => this.preencherSubcategorias(select.value));
        }

        this.syncEnhancedSelects();
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
            this.syncEnhancedSelects();
            return;
        }

        try {
            const base = getBaseUrl();
            const json = await apiGet(`${base}api/categorias/${categoriaId}/subcategorias`);
            const rawSubs = json?.data?.subcategorias ?? (Array.isArray(json?.data) ? json.data : []);
            const subs = sortByLabel(Array.isArray(rawSubs) ? rawSubs : [], (sub) => sub?.nome || '');

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

        this.syncEnhancedSelects();
    }

    resetSubcategoriaSelect() {
        const select = document.getElementById('globalLancamentoSubcategoria');
        if (select) select.innerHTML = '<option value="">Sem subcategoria</option>';
        this.syncEnhancedSelects();
    }

    // â”€â”€ Fatura Estorno â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
        this.syncEnhancedSelects();

        const cartao = this.cartoes.find(c => c.id == cartaoId);
        if (!cartao) {
            this.syncEnhancedSelects();
        }
        if (!cartao) { faturaSelect.innerHTML = '<option value="">Erro ao carregar cartÃ£o</option>'; return; }

        const hoje = new Date();
        const mesAtual = hoje.getMonth() + 1;
        const anoAtual = hoje.getFullYear();
        const meses = ['Janeiro', 'Fevereiro', 'MarÃ§o', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

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
        this.syncEnhancedSelects();
    }

    // â”€â”€ Event Setup â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    configurarEventos() {
        const valorInput = document.getElementById('globalLancamentoValor');
        if (valorInput) {
            valorInput.addEventListener('input', (e) => {
                applyMoneyMask(e.target);
                const metaValorInput = document.getElementById('globalLancamentoMetaValor');
                if (!metaValorInput || parseMoney(metaValorInput.value) <= 0) {
                    this.syncMetaLinkFields({ preserveAmount: false });
                }
                this.atualizarPreviewParcelamento();
                this.schedulePlanningAlertsRender();
            });
            valorInput.addEventListener('focus', (e) => {
                if (e.target.value === '0,00') e.target.value = '';
            });
        }

        const metaValorInput = document.getElementById('globalLancamentoMetaValor');
        if (metaValorInput) {
            metaValorInput.addEventListener('input', (e) => {
                applyMoneyMask(e.target);
                this.schedulePlanningAlertsRender();
            });
        }

        const metaRealizacaoCheck = document.getElementById('globalLancamentoMetaRealizacao');
        if (metaRealizacaoCheck) {
            metaRealizacaoCheck.addEventListener('change', () => this.schedulePlanningAlertsRender());
        }

        const categoriaSelect = document.getElementById('globalLancamentoCategoria');
        if (categoriaSelect && !categoriaSelect.dataset.planningListenerAttached) {
            categoriaSelect.dataset.planningListenerAttached = '1';
            categoriaSelect.addEventListener('change', () => this.schedulePlanningAlertsRender());
        }

        const contaDestinoSelect = document.getElementById('globalLancamentoContaDestino');
        if (contaDestinoSelect && !contaDestinoSelect.dataset.planningListenerAttached) {
            contaDestinoSelect.dataset.planningListenerAttached = '1';
            contaDestinoSelect.addEventListener('change', () => this.schedulePlanningAlertsRender());
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

                this.atualizarTextosParcelamento();
                this.atualizarPreviewParcelamento();
                this.syncReminderVisibility();
                this.schedulePlanningAlertsRender();
            });
        }

        const parceladoCheck = document.getElementById('globalLancamentoParcelado');
        if (parceladoCheck) {
            parceladoCheck.addEventListener('change', (e) => {
                document.getElementById('globalNumeroParcelasGroup').style.display = e.target.checked ? 'block' : 'none';
                this.atualizarPreviewParcelamento();
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

            // Interceptar Enter nos inputs para avanÃ§ar etapa em vez de submeter o form
            form.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    this.nextStep();
                }
            });
        }

        // Data e hora padrÃ£o
        const hoje = new Date();
        const dataInput = document.getElementById('globalLancamentoData');
        if (dataInput && !dataInput.value) {
            dataInput.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;
        }
        if (dataInput && !dataInput.dataset.planningListenerAttached) {
            dataInput.dataset.planningListenerAttached = '1';
            dataInput.addEventListener('change', () => this.schedulePlanningAlertsRender());
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
            this.schedulePlanningAlertsRender();
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
        this.schedulePlanningAlertsRender();

        if (this.pendingTipo && this.currentStep === 1) {
            const tipoPendente = this.pendingTipo;
            this.pendingTipo = null;
            await this.mostrarFormulario(tipoPendente);
        }
    }

    // â”€â”€ Modal Management â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

            this.syncEnhancedSelects();

            this.pendingTipo = contexto.tipo;
            await this.onContaChange();
            this.schedulePlanningAlertsRender();
        }
    }

    closeModal() {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            this.pendingTipo = null;
            this.restaurarCabecalhoPadrao();
            this.initWizard();
            this.clearPlanningAlerts();
        }
    }

    restaurarCabecalhoPadrao() {
        const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
        if (tituloEl) tituloEl.textContent = 'Nova MovimentaÃ§Ã£o';

        const headerGradient = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-header-gradient');
        if (headerGradient) {
            headerGradient.classList.remove('receita', 'despesa', 'transferencia', 'agendamento');
            headerGradient.style.removeProperty('background');
        }
    }

    voltarEscolhaTipo() {
        this.goToStep(1);
        this.restaurarCabecalhoPadrao();
        this.resetarFormulario();
    }

    // â”€â”€ Wizard Step Engine â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    initWizard() {
        this.currentStep = 1;
        this.tipoAtual = null;
        this.totalSteps = 5;
        this.resetarFormulario();
        this.restaurarCabecalhoPadrao();

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
                renderLancamentoHistoryPlaceholder(historicoContainer, 'Selecione uma conta para ver as ultimas movimentaÃ§Ãµes.');
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
            const stepNum = i + 2; // dot 0 â†’ step 2, dot 1 â†’ step 3, etc.
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

        // For transferÃªncia: skip step 3 (payment) and step 5 (category/recurrence)
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

        // For transferÃªncia: skip step 3 back to 2
        if (this.tipoAtual === 'transferencia') {
            // no special skip needed going back
        }

        if (prev < 1) prev = 1;
        if (prev === 1) {
            this.voltarEscolhaTipo();
            return;
        }
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
                Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Informe a descriÃ§Ã£o', customClass: { container: 'swal-above-modal' } });
                return false;
            }
            if (!valor || valor <= 0) {
                Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Informe um valor vÃ¡lido', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }

        if (step === 3) {
            if (this.tipoAtual === 'transferencia') {
                const contaDest = document.getElementById('globalLancamentoContaDestino')?.value;
                if (!contaDest) {
                    Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Selecione a conta de destino', customClass: { container: 'swal-above-modal' } });
                    return false;
                }
            }
            // Validate credit card limit if cartÃ£o selected
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
                                html: `<p>O valor (${formatMoney(valor)}) excede o limite disponÃ­vel.</p><p><strong>Limite:</strong> ${formatMoney(limiteDisponivel)}</p>`,
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
                        Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Selecione o cartÃ£o para o estorno', customClass: { container: 'swal-above-modal' } });
                        return false;
                    }
                }
            }
        }

        if (step === 4) {
            const data = document.getElementById('globalLancamentoData')?.value || '';
            if (!data) {
                Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Informe a data', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }

        // Step 5: validar parcelas e recorrÃªncia
        if (step === 5) {
            const parcelado = document.getElementById('globalLancamentoParcelado')?.checked;
            if (parcelado) {
                const totalParcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 0;
                if (totalParcelas < 2 || totalParcelas > 48) {
                    Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'O nÃºmero de parcelas deve ser entre 2 e 48', customClass: { container: 'swal-above-modal' } });
                    return false;
                }
            }
            const recorrente = document.getElementById('globalLancamentoRecorrente')?.checked;
            if (recorrente) {
                const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value;
                if (modo === 'quantidade') {
                    const total = parseInt(document.getElementById('globalLancamentoRecorrenciaTotal')?.value) || 0;
                    if (total < 2 || total > 120) {
                        Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'A quantidade de repetiÃ§Ãµes deve ser entre 2 e 120', customClass: { container: 'swal-above-modal' } });
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

        // RecorrÃªncia
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

        const metaGroup = document.getElementById('globalMetaGroup');
        if (metaGroup) metaGroup.style.display = 'none';
        const metaSelect = document.getElementById('globalLancamentoMeta');
        if (metaSelect) metaSelect.value = '';
        const metaValorGroup = document.getElementById('globalMetaValorGroup');
        if (metaValorGroup) metaValorGroup.style.display = 'none';
        const metaValorInput = document.getElementById('globalLancamentoMetaValor');
        if (metaValorInput) metaValorInput.value = '';
        const metaRealizacaoGroup = document.getElementById('globalMetaRealizacaoGroup');
        if (metaRealizacaoGroup) metaRealizacaoGroup.style.display = 'none';
        const metaRealizacaoCheck = document.getElementById('globalLancamentoMetaRealizacao');
        if (metaRealizacaoCheck) metaRealizacaoCheck.checked = false;

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
        this.atualizarTextosParcelamento();
        this.atualizarPreviewParcelamento();

        // Reset wizard state
        this.currentStep = 1;
        this.totalSteps = 5;
        this.clearPlanningAlerts();
        this.syncEnhancedSelects();
    }

    // â”€â”€ Form Type Selection â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    async mostrarFormulario(tipo) {
        if (this.contas.length === 0) {
            const result = await Swal.fire({
                icon: 'info',
                title: 'Nenhuma conta cadastrada',
                html: `<p>VocÃª ainda nÃ£o possui nenhuma conta bancÃ¡ria cadastrada.</p>
                       <p class="text-muted mt-2">Ã‰ necessÃ¡rio criar pelo menos uma conta para registrar lanÃ§amentos.</p>`,
                showCancelButton: true,
                confirmButtonText: '<i data-lucide="plus" style="width:16px;height:16px;display:inline-block;"></i> Criar Conta',
                cancelButtonText: 'Agora nÃ£o',
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
            Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Selecione uma conta primeiro!', customClass: { container: 'swal-above-modal' } });
            return;
        }

        // Guard: transferÃªncia requer pelo menos 2 contas
        if (tipo === 'transferencia' && this.contas.length < 2) {
            Swal.fire({
                icon: 'warning',
                title: 'NÃ£o Ã© possÃ­vel transferir',
                text: 'VocÃª precisa ter pelo menos duas contas cadastradas para realizar uma transferÃªncia.',
                confirmButtonText: 'Criar outra conta',
                showCancelButton: true,
                cancelButtonText: 'Agora nÃ£o',
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

        // Restaurar seleÃ§Ã£o da conta no select apÃ³s possÃ­vel re-fetch
        if (this.contaSelecionada) {
            const select = document.getElementById('globalContaSelect');
            if (select && select.value !== String(this.contaSelecionada.id)) {
                select.value = this.contaSelecionada.id;
            }
        }

        this.syncEnhancedSelects();

        this.tipoAtual = tipo;

        const tipoInput = document.getElementById('globalLancamentoTipo');
        if (tipoInput) tipoInput.value = tipo;
        const contaIdInput = document.getElementById('globalLancamentoContaId');
        if (contaIdInput) contaIdInput.value = this.contaSelecionada.id;

        // Set total steps based on type
        this.totalSteps = tipo === 'transferencia' ? 4 : 5;

        this.configurarCamposPorTipo(tipo);

        const titulos = { receita: 'Nova Receita', despesa: 'Nova Despesa', transferencia: 'Nova TransferÃªncia' };
        const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
        if (tituloEl) tituloEl.textContent = titulos[tipo] || 'Nova MovimentaÃ§Ã£o';

        // Update step 2 question text
        const step2Title = document.getElementById('globalStep2Title');
        const step2Subtitle = document.getElementById('globalStep2Subtitle');
        if (tipo === 'receita') {
            if (step2Title) step2Title.textContent = 'O que vocÃª recebeu?';
            if (step2Subtitle) step2Subtitle.textContent = 'Descreva e informe o valor recebido';
        } else if (tipo === 'transferencia') {
            if (step2Title) step2Title.textContent = 'Quanto quer transferir?';
            if (step2Subtitle) step2Subtitle.textContent = 'Descreva e informe o valor da transferÃªncia';
        } else {
            if (step2Title) step2Title.textContent = 'Com o que vocÃª gastou?';
            if (step2Subtitle) step2Subtitle.textContent = 'Descreva e informe o valor';
        }

        // Update step 3 question text
        const step3Title = document.getElementById('globalStep3Title');
        const step3Subtitle = document.getElementById('globalStep3Subtitle');
        if (tipo === 'receita') {
            if (step3Title) step3Title.textContent = 'Como vocÃª recebeu?';
            if (step3Subtitle) step3Subtitle.textContent = 'Escolha a forma de recebimento';
        } else if (tipo === 'transferencia') {
            if (step3Title) step3Title.textContent = 'Para onde vai?';
            if (step3Subtitle) step3Subtitle.textContent = 'Escolha a conta de destino';
        } else {
            if (step3Title) step3Title.textContent = 'Como vocÃª pagou?';
            if (step3Subtitle) step3Subtitle.textContent = 'Escolha a forma de pagamento';
        }

        // Update step 4 question text
        const step4Title = document.getElementById('globalStep4Title');
        if (tipo === 'receita') {
            if (step4Title) step4Title.textContent = 'Quando recebeu?';
        } else if (tipo === 'transferencia') {
            if (step4Title) step4Title.textContent = 'Quando serÃ¡ a transferÃªncia?';
        } else {
            if (step4Title) step4Title.textContent = 'Quando aconteceu?';
        }

        // For transferÃªncia: step 4 is the last, show Salvar instead of PrÃ³ximo
        const step4NavRight = document.getElementById('globalStep4NavRight');
        if (step4NavRight) {
            if (tipo === 'transferencia') {
                step4NavRight.innerHTML = `
                    <button type="submit" class="lk-btn lk-btn-primary" form="globalFormLancamento">
                        <i data-lucide="check"></i>
                        Salvar TransferÃªncia
                    </button>`;
            } else {
                step4NavRight.innerHTML = `
                    <button type="button" class="lk-btn lk-btn-primary" onclick="lancamentoGlobalManager.nextStep()">
                        PrÃ³ximo
                        <i data-lucide="arrow-right"></i>
                    </button>`;
            }
            refreshIcons();
        }

        // Navigate to step 2
        this.goToStep(2);
        this.schedulePlanningAlertsRender();
    }

    // â”€â”€ Field Configuration by Type â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

        // â”€â”€ Step 3 visibility per type â”€â”€
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

        // â”€â”€ Step 5 visibility per type â”€â”€
        // Categoria
        this.preencherCategorias(tipo === 'receita' ? 'receita' : 'despesa');

        const showStep5Fields = (tipo === 'receita' || tipo === 'despesa');
        const categoriaGroup = document.getElementById('globalCategoriaGroup');
        const subcategoriaGroup = document.getElementById('globalSubcategoriaGroup');
        if (categoriaGroup) categoriaGroup.style.display = showStep5Fields ? 'block' : 'none';
        if (subcategoriaGroup) subcategoriaGroup.style.display = 'none';

        // RecorrÃªncia e Lembrete (receita/despesa only)
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
        this.atualizarTextosParcelamento();

        // â”€â”€ Step 4: Pago toggle â”€â”€
        const pagoGroup = document.getElementById('globalPagoGroup');
        const pagoCheck = document.getElementById('globalLancamentoPago');
        if (pagoGroup) pagoGroup.style.display = showStep5Fields ? 'block' : 'none';
        if (pagoCheck) pagoCheck.checked = true;
        const quickSaveButton = document.getElementById('globalBtnQuickSave');
        if (quickSaveButton) quickSaveButton.style.display = showStep5Fields ? 'inline-flex' : 'none';

        const metaGroup = document.getElementById('globalMetaGroup');
        const metaSelect = document.getElementById('globalLancamentoMeta');
        const metaHelper = document.getElementById('globalMetaHelperText');
        const podeVincularMeta = tipo === 'receita' || tipo === 'despesa' || tipo === 'transferencia';
        if (metaGroup) metaGroup.style.display = podeVincularMeta ? 'block' : 'none';
        if (metaSelect) metaSelect.value = '';
        if (metaHelper) {
            if (tipo === 'transferencia') {
                metaHelper.textContent = 'Deseja guardar parte deste valor em uma meta?';
            } else if (tipo === 'despesa') {
                metaHelper.textContent = 'Usou dinheiro guardado em meta nesta despesa?';
            } else {
                metaHelper.textContent = 'Deseja guardar parte desta receita em uma meta?';
            }
        }
        this.syncMetaLinkFields({ preserveAmount: false });

        const pagoLabel = document.getElementById('globalPagoLabel');
        const pagoHelper = document.getElementById('globalPagoHelperText');
        if (tipo === 'receita') {
            if (pagoLabel) pagoLabel.textContent = 'JÃ¡ foi recebido';
            if (pagoHelper) pagoHelper.textContent = 'Desmarque se ainda nÃ£o foi recebido.';
        } else {
            if (pagoLabel) pagoLabel.textContent = 'JÃ¡ foi pago';
            if (pagoHelper) pagoHelper.textContent = 'Desmarque se ainda nÃ£o foi pago.';
        }

        // RecorrÃªncia sub-groups
        const totalGroup = document.getElementById('globalRecorrenciaTotalGroup');
        const fimGroup = document.getElementById('globalRecorrenciaFimGroup');
        if (totalGroup) totalGroup.style.display = 'none';
        if (fimGroup) fimGroup.style.display = 'none';

        const radioInfinito = document.getElementById('globalRecorrenciaRadioInfinito');
        const defaultModo = 'infinito';
        if (radioInfinito) radioInfinito.style.display = '';
        const radios = document.querySelectorAll('input[name="global_recorrencia_modo"]');
        radios.forEach(r => r.checked = r.value === defaultModo);

        this.syncPagoRecorrenciaState();
        this.configurarEventosLembrete();
        this.syncReminderVisibility();
        this.schedulePlanningAlertsRender();
    }

    // â”€â”€ Recurrence Toggles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

        this.syncPagoRecorrenciaState();
        this.syncReminderVisibility();
        this.schedulePlanningAlertsRender();
    }

    syncPagoRecorrenciaState() {
        const recorrente = document.getElementById('globalLancamentoRecorrente')?.checked === true;
        const pagoCheck = document.getElementById('globalLancamentoPago');
        const pagoGroup = document.getElementById('globalPagoGroup');
        const pagoHelper = document.getElementById('globalPagoHelperText');

        if (!pagoCheck || !pagoGroup || !pagoHelper) {
            return;
        }

        if (recorrente) {
            pagoCheck.checked = false;
            pagoCheck.disabled = true;
            pagoGroup.classList.add('lk-form-group-disabled');
            pagoHelper.textContent = 'Recorrencias comecam como pendentes. Voce pode marcar cada ocorrencia como paga depois.';
            this.schedulePlanningAlertsRender();
            return;
        }

        pagoCheck.disabled = false;
        pagoGroup.classList.remove('lk-form-group-disabled');
        pagoHelper.textContent = this.tipoAtual === 'receita'
            ? 'Desmarque se ainda nao foi recebido.'
            : 'Desmarque se ainda nao foi pago.';
        this.schedulePlanningAlertsRender();
    }

    toggleRecorrenciaFim() {
        const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value || 'infinito';
        const totalGroup = document.getElementById('globalRecorrenciaTotalGroup');
        const fimGroup = document.getElementById('globalRecorrenciaFimGroup');
        if (totalGroup) totalGroup.style.display = modo === 'quantidade' ? 'block' : 'none';
        if (fimGroup) fimGroup.style.display = modo === 'data' ? 'block' : 'none';
    }

    // â”€â”€ Card Subscription Toggles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    toggleAssinaturaCartao() {
        const checkbox = document.getElementById('globalLancamentoAssinaturaCartao');
        const detalhes = document.getElementById('globalAssinaturaCartaoDetalhes');
        if (detalhes) detalhes.style.display = checkbox?.checked ? 'block' : 'none';

        // Assinatura e parcelamento sÃ£o mutuamente exclusivos
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
                this.schedulePlanningAlertsRender();
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
        this.syncEnhancedSelects();
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
        this.schedulePlanningAlertsRender();
    }

    /**
     * Sugerir categoria usando IA com base na descriÃ§Ã£o do lanÃ§amento
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
        this.schedulePlanningAlertsRender();
    }

    // â”€â”€ Forma de Pagamento / Recebimento â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
        this.atualizarTextosParcelamento();
        this.atualizarPreviewParcelamento();
        this.syncEnhancedSelects();
    }

    selecionarFormaPagamento(forma) {
        document.querySelectorAll('#globalFormaPagamentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        const btnSelecionado = document.querySelector(`#globalFormaPagamentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        const formaPagInput = document.getElementById('globalFormaPagamento');
        if (formaPagInput) formaPagInput.value = forma;

        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
        const metaGroup = document.getElementById('globalMetaGroup');
        const metaSelect = document.getElementById('globalLancamentoMeta');
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
            if (metaGroup) metaGroup.style.display = 'none';
            if (metaSelect) metaSelect.value = '';
            this.syncMetaLinkFields({ preserveAmount: false });
        } else {
            if (cartaoGroup) { cartaoGroup.classList.remove('active'); cartaoGroup.style.display = 'none'; }
            if (parcelamentoGroup) {
                parcelamentoGroup.style.display = 'block';
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
            if (metaGroup && (this.tipoAtual === 'receita' || this.tipoAtual === 'despesa')) {
                metaGroup.style.display = 'block';
            }
            this.syncMetaLinkFields({ preserveAmount: true });
        }

        this.atualizarTextosParcelamento();
        this.atualizarPreviewParcelamento();
        this.syncPagoRecorrenciaState();
        this.syncReminderVisibility();
        this.schedulePlanningAlertsRender();
        this.syncEnhancedSelects();
    }

    selecionarFormaRecebimento(forma) {
        document.querySelectorAll('#globalFormaRecebimentoGrid .lk-forma-btn').forEach(btn => btn.classList.remove('active'));
        const btnSelecionado = document.querySelector(`#globalFormaRecebimentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        const formaRecInput = document.getElementById('globalFormaRecebimento');
        if (formaRecInput) formaRecInput.value = forma;

        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
        const metaGroup = document.getElementById('globalMetaGroup');
        const metaSelect = document.getElementById('globalLancamentoMeta');

        if (forma === 'estorno_cartao') {
            if (cartaoGroup) { cartaoGroup.classList.add('active'); cartaoGroup.style.display = 'block'; }
            this.preencherCartoes(true);
            if (metaGroup) metaGroup.style.display = 'none';
            if (metaSelect) metaSelect.value = '';
            this.syncMetaLinkFields({ preserveAmount: false });
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
            if (metaGroup && (this.tipoAtual === 'receita' || this.tipoAtual === 'despesa')) {
                metaGroup.style.display = 'block';
            }
            this.syncMetaLinkFields({ preserveAmount: true });
            const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
            if (parcelamentoGroup) parcelamentoGroup.style.display = 'block';
            const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
            const pagoGroup = document.getElementById('globalPagoGroup');
            if (pagoGroup) pagoGroup.style.display = 'block';
        }

        this.atualizarTextosParcelamento();
        this.atualizarPreviewParcelamento();
        this.syncPagoRecorrenciaState();
        this.syncReminderVisibility();
        this.schedulePlanningAlertsRender();
        this.syncEnhancedSelects();
    }

    // â”€â”€ Validation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    validarFormulario() {
        if (!this.tipoAtual) {
            Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Selecione o tipo de lanÃ§amento', customClass: { container: 'swal-above-modal' } });
            return false;
        }

        const contaId = this.contaSelecionada?.id || document.getElementById('globalContaSelect')?.value;
        if (!contaId) {
            Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Selecione a conta', customClass: { container: 'swal-above-modal' } });
            return false;
        }

        const descricao = document.getElementById('globalLancamentoDescricao')?.value.trim() || '';
        const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value);
        const data = document.getElementById('globalLancamentoData')?.value || '';

        if (!descricao) {
            Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Informe a descriÃ§Ã£o', customClass: { container: 'swal-above-modal' } });
            return false;
        }
        if (!valor || valor <= 0) {
            Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Informe um valor vÃ¡lido', customClass: { container: 'swal-above-modal' } });
            return false;
        }
        if (!data) {
            Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Informe a data', customClass: { container: 'swal-above-modal' } });
            return false;
        }


        const metaIdRaw = document.getElementById('globalLancamentoMeta')?.value || '';
        const metaSelecionada = Number(metaIdRaw) > 0;
        if (metaSelecionada) {
            const metaValor = parseMoney(document.getElementById('globalLancamentoMetaValor')?.value || '');
            if (!metaValor || metaValor <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Informe quanto deste lancamento foi para a meta.',
                    customClass: { container: 'swal-above-modal' }
                });
                return false;
            }

            if (metaValor > valor) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'O valor da meta nao pode ser maior que o valor total do lancamento.',
                    customClass: { container: 'swal-above-modal' }
                });
                return false;
            }
        }

        // Validar limite do cartÃ£o
        if (this.tipoAtual === 'despesa') {
            const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
            if (cartaoId) {
                const cartao = this.cartoes.find(c => c.id == cartaoId);
                if (cartao) {
                    const limiteDisponivel = parseFloat(cartao.limite_disponivel || 0);
                    if (valor > limiteDisponivel) {
                        Swal.fire({
                            icon: 'error', title: 'Limite Insuficiente',
                            html: `<p>O valor da compra (${formatMoney(valor)}) excede o limite disponÃ­vel do cartÃ£o.</p>
                                   <p><strong>Limite disponÃ­vel:</strong> ${formatMoney(limiteDisponivel)}</p>`,
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
                Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'Selecione a conta de destino', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }

        // Validar ranges de parcelas e recorrÃªncia
        const parcelado = document.getElementById('globalLancamentoParcelado')?.checked;
        if (parcelado) {
            const totalParcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 0;
            if (totalParcelas < 2 || totalParcelas > 48) {
                Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'O nÃºmero de parcelas deve ser entre 2 e 48', customClass: { container: 'swal-above-modal' } });
                return false;
            }
        }
        const recorrente = document.getElementById('globalLancamentoRecorrente')?.checked;
        if (recorrente) {
            const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value;
            if (modo === 'quantidade') {
                const total = parseInt(document.getElementById('globalLancamentoRecorrenciaTotal')?.value) || 0;
                if (total < 2 || total > 120) {
                    Swal.fire({ icon: 'warning', title: 'AtenÃ§Ã£o', text: 'A quantidade de repetiÃ§Ãµes deve ser entre 2 e 120', customClass: { container: 'swal-above-modal' } });
                    return false;
                }
            }
        }

        return true;
    }

    // â”€â”€ Data Collection â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    coletarDadosFormulario() {
        const contaId = this.contaSelecionada?.id;
        if (!contaId) throw new Error('Conta nÃ£o selecionada');

        const dados = {
            conta_id: parseInt(contaId),
            tipo: document.getElementById('globalLancamentoTipo').value,
            descricao: document.getElementById('globalLancamentoDescricao').value.trim(),
            valor: parseMoney(document.getElementById('globalLancamentoValor').value),
            data: document.getElementById('globalLancamentoData').value,
            hora_lancamento: document.getElementById('globalLancamentoHora')?.value || null,
            categoria_id: document.getElementById('globalLancamentoCategoria').value || null,
            subcategoria_id: document.getElementById('globalLancamentoSubcategoria')?.value || null,
            meta_id: null,
            meta_operacao: null,
            meta_valor: null,
            pago: true
        };

        const metaIdRaw = document.getElementById('globalLancamentoMeta')?.value || '';
        const metaId = Number.parseInt(String(metaIdRaw), 10);
        if (Number.isFinite(metaId) && metaId > 0) {
            const metaValorInformado = parseMoney(document.getElementById('globalLancamentoMetaValor')?.value || '');
            const metaValor = Math.max(0, Math.min(dados.valor, metaValorInformado || dados.valor));
            dados.meta_id = metaId;
            dados.meta_valor = Number(metaValor.toFixed(2));

            if (this.tipoAtual === 'despesa') {
                dados.meta_operacao = document.getElementById('globalLancamentoMetaRealizacao')?.checked
                    ? 'realizacao'
                    : 'resgate';
            } else {
                dados.meta_operacao = 'aporte';
            }
        }

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

                    // Assinatura/recorrÃªncia no cartÃ£o
                    const assinaturaCheck = document.getElementById('globalLancamentoAssinaturaCartao');
                    if (assinaturaCheck?.checked) {
                        dados.recorrente = '1';
                        dados.recorrencia_freq = document.getElementById('globalLancamentoAssinaturaFreq')?.value || 'mensal';
                        dados.eh_parcelado = false; // Assinatura nÃ£o Ã© parcelamento

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

        // Parcelamento despesa sem cartÃ£o
        if (this.tipoAtual === 'despesa' && !dados.cartao_credito_id) {
            if (document.getElementById('globalLancamentoParcelado')?.checked) {
                dados.eh_parcelado = true;
                dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 1;
            }
        }

        // RecorrÃªncia + Lembrete + Pago
        if (this.tipoAtual === 'receita' || this.tipoAtual === 'despesa') {
            dados.pago = document.getElementById('globalLancamentoPago')?.checked ? true : false;

            if (document.getElementById('globalLancamentoRecorrente')?.checked) {
                dados.pago = false;
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

    // â”€â”€ Form Submission â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    async salvarLancamento() {
        if (this.salvando) return;
        if (!this.validarFormulario()) return;

        this.salvando = true;
        const base = getBaseUrl();
        let result = null;

        try {
            const dados = this.coletarDadosFormulario();

            const btnSalvar = document.getElementById('globalBtnSalvar');
            if (btnSalvar) {
                btnSalvar.disabled = true;
                btnSalvar.innerHTML = '<i data-lucide="loader-2" class="icon-spin" style="width:16px;height:16px;display:inline-block;"></i> Salvando...';
                refreshIcons();
            }

            let apiUrl = `${base}api/lancamentos`;
            let requestData = dados;

            if (this.tipoAtual === 'transferencia') {
                apiUrl = `${base}api/transfers`;
                requestData = {
                    conta_id: dados.conta_id,
                    conta_id_destino: dados.conta_destino_id,
                    meta_id: dados.meta_id,
                    meta_operacao: dados.meta_operacao,
                    meta_valor: dados.meta_valor,
                    valor: dados.valor,
                    data: dados.data,
                    descricao: dados.descricao
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

            result = await apiPost(apiUrl, requestData);
            const isSuccess = result?.success === true;

            if (isSuccess) {
                await this.handleSuccessfulSave(result);
                return;
            }

            if (isSuccess) {
                const tipoLancamento = this.tipoAtual;

                // GamificaÃ§Ã£o
                if (result.data?.gamification) {
                    try {
                        const gamif = result.data.gamification;
                        if (gamif.achievements?.length > 0 && typeof window.notifyMultipleAchievements === 'function') {
                            window.notifyMultipleAchievements(gamif.achievements);
                        }
                        if (gamif.level_up && typeof window.notifyLevelUp === 'function') {
                            window.notifyLevelUp(gamif.level);
                        }
                    } catch (e) { console.error('Erro gamificaÃ§Ã£o:', e); }
                }

                this.closeModal();
                showToast(result.message || 'LanÃ§amento salvo com sucesso!', 'success');

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
                let errorMessage = result.message || 'Erro ao salvar lanÃ§amento';
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
            logClientError('Erro ao salvar lancamento', error, 'Falha ao salvar lancamento');
            this.salvando = false;
            this._resetBtnSalvar();
            Swal.fire({ icon: 'error', title: 'Erro', text: getErrorMessage(error, 'Erro ao salvar lancamento.'), confirmButtonText: 'OK', customClass: { container: 'swal-above-modal' } });
        }
    }

    async handleSuccessfulSave(result) {
        const payload = result?.data ?? null;

        try {
            if (payload?.gamification) {
                const gamif = payload.gamification;
                if (gamif.achievements?.length > 0 && typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(gamif.achievements);
                }
                if (gamif.level_up && typeof window.notifyLevelUp === 'function') {
                    window.notifyLevelUp(gamif.level);
                }
            }

            this.closeModal();
            showToast(result?.message || 'LanÃ§amento salvo com sucesso!', 'success');

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
                    payload
                }
            }));
            window.dispatchEvent(new CustomEvent('lancamento-created', { detail: payload }));
        } catch (postSaveError) {
            logClientWarning('Erro ao atualizar UI apos salvar lancamento', postSaveError, 'Falha ao atualizar a tela');
        } finally {
            this.salvando = false;
            this._resetBtnSalvar();
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

    // â”€â”€ Parcelamento Preview â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    atualizarPreviewParcelamento() {
        const preview = document.getElementById('globalParcelamentoPreview');
        if (!preview) return;

        const valor = parseMoney(document.getElementById('globalLancamentoValor')?.value || '');
        const parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value || '0', 10);
        const parcelado = document.getElementById('globalLancamentoParcelado')?.checked === true;

        if (parcelado && valor > 0 && parcelas >= 2) {
            const valorParcela = valor / parcelas;
            const descricaoParcelamento = this.tipoAtual === 'receita'
                ? `${parcelas} recebimentos de ${formatMoney(valorParcela)}`
                : `${parcelas} parcelas de ${formatMoney(valorParcela)}`;

            preview.innerHTML = `
                <div class="preview-info">
                    <i data-lucide="calculator" style="width:16px;height:16px;display:inline-block;"></i>
                    <span>${descricaoParcelamento}</span>
                </div>`;
            preview.style.display = 'block';
            refreshIcons();
        } else {
            preview.style.display = 'none';
        }
    }
}

// â”€â”€ Singleton & Backward Compat â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const manager = new LancamentoGlobalManager();

// Expose on window for inline onclick handlers in PHP views
window.lancamentoGlobalManager = manager;
window.LK = window.LK || {};
window.LK.modals = window.LK.modals || {};
window.LK.modals.openLancamentoModal = (options = {}) => manager.openModal(options);

// â”€â”€ Bootstrap â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => manager.init());
} else {
    manager.init();
}

export default manager;
