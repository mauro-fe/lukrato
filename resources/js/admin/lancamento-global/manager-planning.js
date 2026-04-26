import {
    buildPlanningAlertCard,
    summarizeMetaTitles,
} from '../shared/lancamento-meta.js';

export function attachLancamentoGlobalPlanningMethods(ManagerClass, dependencies) {
    const {
        refreshIcons,
        parseMoney,
        formatMoney,
        formatMoneyInput,
        computeAccountEffect,
        getBaseUrl,
    } = dependencies;

    Object.assign(ManagerClass.prototype, {
        resumirTitulosMetas(metas = []) {
            return summarizeMetaTitles(metas);
        },

        buildPlanningAlertCard({ tone = 'info', icon = 'target', eyebrow, title, message }) {
            return buildPlanningAlertCard({
                tone,
                icon,
                eyebrow,
                title,
                message
            });
        },

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
        },

        clearPlanningAlerts() {
            this.setPlanningAlertsContainer('globalContaPlanningAlerts', []);
            this.setPlanningAlertsContainer('globalCategoriaPlanningAlerts', []);
        },

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
                    message = `Como o pagamento está no cartão de crédito, o saldo da conta não muda agora. ${metas.length === 1 ? `A meta segue sincronizada com ${formatMoney(saldoAtual)}.` : `As metas ${resumoMetas} continuam sincronizadas com o saldo atual.`}`;
                } else if (isPendente) {
                    message = `Enquanto este lançamento estiver pendente, o saldo da conta não muda. ${metas.length === 1 ? `A meta continua em ${formatMoney(saldoAtual)}.` : `As metas ${resumoMetas} só mudam quando a movimentação for confirmada.`}`;
                } else {
                    message = metas.length === 1
                        ? `Essa movimentação não altera o saldo da conta agora. A meta segue acompanhando ${formatMoney(saldoAtual)}.`
                        : `Essa movimentação não altera o saldo da conta agora. ${resumoMetas} continuam sincronizadas com o valor atual.`;
                }
            } else {
                tone = saldoProjetado < 0 ? 'danger' : (delta < 0 ? 'warning' : 'success');
                message = `Saldo estimado após salvar: ${formatMoney(saldoProjetado)}.`;

                if (metas.length === 1 && progressoProjetado !== null) {
                    message += ` ${principal.titulo} ficaria em ${progressoProjetado.toFixed(1)}% do alvo de ${formatMoney(principal.valor_alvo)}.`;
                } else {
                    message += ` ${resumoMetas} vão refletir esse novo saldo automaticamente.`;
                }
            }

            return this.buildPlanningAlertCard({
                tone,
                icon: saldoProjetado < 0 ? 'triangle-alert' : 'target',
                eyebrow: role === 'destination' ? 'Meta da conta de destino' : 'Meta vinculada',
                title,
                message
            });
        },

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
        },

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
            let title = `${categoriaNome} tem orçamento ativo`;
            let message = `Limite efetivo de ${formatMoney(limiteEfetivo)}. Depois deste lançamento, restam ${formatMoney(restante)} no período (${percentual.toFixed(1)}% usado).`;

            if (excesso > 0) {
                tone = 'danger';
                title = `${categoriaNome} estoura o orçamento`;
                message = `Limite efetivo de ${formatMoney(limiteEfetivo)}. O gasto projetado vai para ${formatMoney(gastoProjetado)} e passa ${formatMoney(excesso)} do limite.`;
            } else if (percentual >= 80) {
                tone = 'warning';
                title = `${categoriaNome} entra em alerta`;
                message = `Limite efetivo de ${formatMoney(limiteEfetivo)}. Depois deste lançamento, sobram ${formatMoney(restante)} no período (${percentual.toFixed(1)}% usado).`;
            }

            if (rollover > 0) {
                message += ` O limite inclui ${formatMoney(rollover)} de rollover.`;
            }

            this.setPlanningAlertsContainer('globalCategoriaPlanningAlerts', [
                this.buildPlanningAlertCard({
                    tone,
                    icon: excesso > 0 ? 'triangle-alert' : 'wallet',
                    eyebrow: 'Orçamento do período',
                    title,
                    message
                })
            ]);
        },

        schedulePlanningAlertsRender() {
            const renderId = ++this.planningRenderSeq;
            void Promise.all([
                this.renderContaPlanningAlerts(renderId),
                this.renderCategoriaPlanningAlerts(renderId)
            ]);
        },

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
                aviso.className = 'no-accounts-warning lk-account-empty-state';
                aviso.innerHTML = `
                    <div class="lk-account-empty-state__icon">
                        <i data-lucide="wallet"></i>
                    </div>
                    <div class="lk-account-empty-state__copy">
                        <strong>Crie sua primeira conta</strong>
                        <span>Depois disso o lançamento fica liberado.</span>
                    </div>
                    <a href="${getBaseUrl()}contas" class="lk-account-empty-state__cta">
                        <i data-lucide="plus"></i>
                        Criar conta
                    </a>
                `;
                selectContainer?.appendChild(aviso);
                this.syncEnhancedSelects();
                return;
            }

            select.disabled = false;
            select.innerHTML = '<option value="">Escolha uma conta...</option>';
            this.contas.forEach((conta) => {
                const option = document.createElement('option');
                option.value = conta.id;
                const saldo = conta.saldo !== undefined ? conta.saldo : (conta.saldoAtual !== undefined ? conta.saldoAtual : conta.saldo_inicial || 0);
                const nomeConta = String(conta.nome || conta.instituicao || `Conta #${conta.id}`).trim();
                option.textContent = `${nomeConta} - ${formatMoney(saldo)}`;
                option.dataset.saldo = saldo;
                option.dataset.nome = nomeConta;
                select.appendChild(option);
            });

            if (selectedId && this.contas.some((conta) => String(conta.id) === selectedId)) {
                select.value = selectedId;
            }

            this.syncEnhancedSelects();
        },

        preencherContasDestino() {
            const select = document.getElementById('globalLancamentoContaDestino');
            if (!select) return;
            select.innerHTML = '<option value="">Selecione a conta de destino</option>';
            const origemId = this.contaSelecionada?.id ? String(this.contaSelecionada.id) : null;
            this.contas.forEach((conta) => {
                if (!origemId || String(conta.id) !== origemId) {
                    const option = document.createElement('option');
                    option.value = conta.id;
                    const saldo = conta.saldo !== undefined ? conta.saldo : (conta.saldoAtual !== undefined ? conta.saldoAtual : conta.saldo_inicial || 0);
                    const nomeConta = String(conta.nome || conta.instituicao || `Conta #${conta.id}`).trim();
                    option.textContent = `${nomeConta} - ${formatMoney(saldo)}`;
                    select.appendChild(option);
                }
            });

            this.syncEnhancedSelects();
        },

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
        },

        getMetaSelecionada(rawMetaId = null) {
            const raw = rawMetaId !== null
                ? rawMetaId
                : (document.getElementById('globalLancamentoMeta')?.value || '');
            const metaId = Number.parseInt(String(raw), 10);
            if (!Number.isFinite(metaId) || metaId <= 0) {
                return null;
            }

            return this.metas.find((meta) => Number(meta?.id ?? 0) === metaId) || null;
        },

        shouldDefaultMetaRealizacao(meta) {
            if (!meta) return false;
            const status = String(meta?.status || '').toLowerCase();
            return status === 'concluida';
        },

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
        },

        onMetaChange() {
            this.syncMetaLinkFields({ preserveAmount: false });
            this.schedulePlanningAlertsRender();
        },
    });
}
