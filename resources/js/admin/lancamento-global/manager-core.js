export function attachLancamentoGlobalCoreMethods(ManagerClass, dependencies) {
    const {
        CustomSelectManager,
        syncCustomSelects,
        loadLancamentoRecentHistory,
        renderLancamentoHistoryPlaceholder,
        getBaseUrl,
        apiGet,
        sortByLabel,
        formatMoney,
    } = dependencies;

    Object.assign(ManagerClass.prototype, {
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
    },

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
    },

    aplicarContextoAbertura(contexto) {
        this.contextoAbertura = contexto;

        const label = document.getElementById('globalContaSelectLabelText');
        if (label) {
            label.textContent = contexto.source === 'contas' ? 'Conta atual' : 'Selecione a conta';
        }

        const hint = document.getElementById('globalContaContextHint');
        if (hint) {
            hint.textContent = contexto.source === 'contas'
                ? 'Abrimos com a conta desta tela. Se precisar, você pode trocar antes de continuar.'
                : 'Escolha a conta para ver saldo e as ultimas movimentações.';
        }

        const select = document.getElementById('globalContaSelect');
        if (select) {
            select.disabled = this.contas.length === 0 || Boolean(contexto.lockAccount && this.contaSelecionada);
        }
    },

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
    },

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
    },

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
    },

    async atualizarHistoricoContaSelecionada() {
        const historicoContainer = document.getElementById('globalLancamentoHistorico');
        if (!historicoContainer) return;

        if (!this.contaSelecionada) {
            renderLancamentoHistoryPlaceholder(historicoContainer, 'Selecione uma conta para ver as ultimas movimentações.');
            return;
        }

        await loadLancamentoRecentHistory({
            contaId: this.contaSelecionada.id,
            containerEl: historicoContainer,
            limit: 5
        });
    },

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
    },

    getContaById(contaId) {
        return this.contas.find((conta) => String(conta.id) === String(contaId ?? '')) || null;
    },

    syncEnhancedSelects() {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (!overlay) return;
        syncCustomSelects(overlay);
    },

    getFormaPlanejamentoAtual() {
        if (this.tipoAtual === 'receita') {
            return document.getElementById('globalFormaRecebimento')?.value || '';
        }

        if (this.tipoAtual === 'despesa') {
            return document.getElementById('globalFormaPagamento')?.value || '';
        }

        return '';
    },

    getLancamentoPagoAtual() {
        if (this.tipoAtual === 'transferencia') {
            return true;
        }

        const pagoCheck = document.getElementById('globalLancamentoPago');
        return pagoCheck ? pagoCheck.checked !== false : true;
    },

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
    },

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
    },

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
    });
}
