/**
 * Gerenciador de Lançamento Global (Header FAB)
 */
const lancamentoGlobalManager = {
    baseUrl: window.BASE_URL || '/',
    contaSelecionada: null,
    contas: [],
    categorias: [],
    cartoes: [],
    tipoAtual: null,

    init() {
        this.configurarEventos();
        // Não carregar dados aqui, apenas quando abrir o modal
    },

    async carregarDados() {
        try {
            // Carregar contas
            const resContas = await fetch(`${this.baseUrl}api/contas`);
            if (!resContas.ok) {
                console.error('Erro ao carregar contas:', resContas.status);
                this.contas = [];
            } else {
                const dataContas = await resContas.json();
                this.contas = dataContas.contas || dataContas || [];
            }
            this.preencherSelectContas();

            // Carregar categorias
            const resCategorias = await fetch(`${this.baseUrl}api/categorias`);
            if (!resCategorias.ok) {
                console.error('Erro ao carregar categorias:', resCategorias.status);
                this.categorias = [];
            } else {
                const dataCategorias = await resCategorias.json();
                this.categorias = dataCategorias.categorias || dataCategorias || [];
            }

            // Carregar cartões
            const resCartoes = await fetch(`${this.baseUrl}api/cartoes`);
            if (!resCartoes.ok) {
                console.error('Erro ao carregar cartões:', resCartoes.status);
                this.cartoes = [];
            } else {
                const dataCartoes = await resCartoes.json();
                this.cartoes = dataCartoes.cartoes || dataCartoes || [];
            }

        } catch (error) {
            console.error('Erro ao carregar dados:', error);
        }
    },

    preencherSelectContas() {
        const select = document.getElementById('globalContaSelect');
        if (!select) return;

        select.innerHTML = '<option value="">Escolha uma conta...</option>';

        this.contas.forEach(conta => {
            const option = document.createElement('option');
            option.value = conta.id;
            option.textContent = `${conta.nome} - ${this.formatMoney(conta.saldo)}`;
            option.dataset.saldo = conta.saldo;
            option.dataset.nome = conta.nome;
            select.appendChild(option);
        });
    },

    onContaChange() {
        const select = document.getElementById('globalContaSelect');
        const contaId = select.value;

        if (!contaId) {
            document.getElementById('globalContaInfo').style.display = 'none';
            this.contaSelecionada = null;
            return;
        }

        const option = select.options[select.selectedIndex];
        const contaNome = option.dataset.nome;
        const contaSaldo = parseFloat(option.dataset.saldo);

        this.contaSelecionada = {
            id: contaId,
            nome: contaNome,
            saldo: contaSaldo
        };

        // Atualizar info da conta
        document.getElementById('globalContaNome').textContent = contaNome;
        document.getElementById('globalContaSaldo').textContent = this.formatMoney(contaSaldo);
        document.getElementById('globalContaInfo').style.display = 'flex';
    },

    configurarEventos() {
        // Máscara de dinheiro
        const valorInput = document.getElementById('globalLancamentoValor');
        if (valorInput) {
            valorInput.addEventListener('input', (e) => this.formatarDinheiro(e.target));
            valorInput.addEventListener('focus', (e) => {
                if (e.target.value === '0,00') {
                    e.target.value = '';
                }
            });
        }

        // Cartão de crédito - mostrar parcelamento
        const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
        if (cartaoSelect) {
            cartaoSelect.addEventListener('change', () => {
                const temCartao = cartaoSelect.value !== '';
                document.getElementById('globalParcelamentoGroup').style.display = temCartao ? 'block' : 'none';
                if (!temCartao) {
                    document.getElementById('globalLancamentoParcelado').checked = false;
                    document.getElementById('globalNumeroParcelasGroup').style.display = 'none';
                }
            });
        }

        // Parcelamento checkbox
        const parceladoCheck = document.getElementById('globalLancamentoParcelado');
        if (parceladoCheck) {
            parceladoCheck.addEventListener('change', (e) => {
                document.getElementById('globalNumeroParcelasGroup').style.display = e.target.checked ? 'block' : 'none';
            });
        }

        // Preview de parcelamento
        const totalParcelasInput = document.getElementById('globalLancamentoTotalParcelas');
        if (totalParcelasInput) {
            totalParcelasInput.addEventListener('input', () => this.atualizarPreviewParcelamento());
        }

        // Submit do formulário
        const form = document.getElementById('globalFormLancamento');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.salvarLancamento();
            });
        }

        // Data padrão
        const dataInput = document.getElementById('globalLancamentoData');
        if (dataInput && !dataInput.value) {
            dataInput.value = new Date().toISOString().split('T')[0];
        }
    },

    openModal() {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (overlay) {
            // Carregar dados ao abrir o modal (se ainda não foram carregados)
            if (this.contas.length === 0) {
                this.carregarDados();
            }

            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Resetar para escolha de tipo
            this.voltarEscolhaTipo();

            // Se tiver apenas uma conta, selecionar automaticamente
            setTimeout(() => {
                if (this.contas.length === 1) {
                    document.getElementById('globalContaSelect').value = this.contas[0].id;
                    this.onContaChange();
                }
            }, 100);
        }
    },

    closeModal() {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            this.resetarFormulario();
        }
    },

    mostrarFormulario(tipo) {
        if (!this.contaSelecionada) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Selecione uma conta primeiro!'
            });
            return;
        }

        this.tipoAtual = tipo;

        // Esconder seleção de tipo
        document.getElementById('globalTipoSection').style.display = 'none';

        // Mostrar formulário
        document.getElementById('globalFormSection').style.display = 'block';

        // Configurar tipo
        document.getElementById('globalLancamentoTipo').value = tipo === 'agendamento' ? 'despesa' : tipo;
        document.getElementById('globalLancamentoContaId').value = this.contaSelecionada.id;

        // Configurar campos específicos por tipo
        this.configurarCamposPorTipo(tipo);

        // Atualizar título
        const titulos = {
            receita: 'Nova Receita',
            despesa: 'Nova Despesa',
            transferencia: 'Nova Transferência',
            agendamento: 'Novo Agendamento'
        };
        document.getElementById('modalLancamentoGlobalTitulo').textContent = titulos[tipo] || 'Nova Movimentação';
    },

    configurarCamposPorTipo(tipo) {
        // Conta Destino (apenas transferência)
        const contaDestinoGroup = document.getElementById('globalContaDestinoGroup');
        contaDestinoGroup.style.display = tipo === 'transferencia' ? 'block' : 'none';

        if (tipo === 'transferencia') {
            this.preencherContasDestino();
        }

        // Cartão de crédito (apenas despesa/agendamento)
        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
        cartaoGroup.style.display = (tipo === 'despesa' || tipo === 'agendamento') ? 'block' : 'none';

        if (tipo === 'despesa' || tipo === 'agendamento') {
            this.preencherCartoes();
        }

        // Categoria
        this.preencherCategorias(tipo === 'receita' ? 'receita' : 'despesa');

        // Pago (ocultar para agendamento)
        const pagoGroup = document.getElementById('globalPagoGroup');
        if (tipo === 'agendamento') {
            pagoGroup.style.display = 'none';
            document.getElementById('globalLancamentoPago').checked = false;
        } else {
            pagoGroup.style.display = 'block';
            document.getElementById('globalLancamentoPago').checked = true;
        }
    },

    preencherContasDestino() {
        const select = document.getElementById('globalLancamentoContaDestino');
        if (!select) return;

        select.innerHTML = '<option value="">Selecione a conta de destino</option>';

        this.contas.forEach(conta => {
            if (conta.id != this.contaSelecionada.id) {
                const option = document.createElement('option');
                option.value = conta.id;
                option.textContent = `${conta.nome} - ${this.formatMoney(conta.saldo)}`;
                select.appendChild(option);
            }
        });
    },

    preencherCartoes() {
        const select = document.getElementById('globalLancamentoCartaoCredito');
        if (!select) return;

        const optionVazio = '<option value="">Não usar cartão (débito na conta)</option>';
        const optionsCartoes = this.cartoes
            .filter(c => c.ativo)
            .map(cartao => `<option value="${cartao.id}">${cartao.nome_cartao || cartao.bandeira} •••• ${cartao.ultimos_digitos}</option>`)
            .join('');

        select.innerHTML = optionVazio + optionsCartoes;
    },

    preencherCategorias(tipo) {
        const select = document.getElementById('globalLancamentoCategoria');
        if (!select) return;

        const categoriasFiltradas = this.categorias.filter(c => c.tipo === tipo);

        select.innerHTML = '<option value="">Sem categoria</option>';
        categoriasFiltradas.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = `${cat.icone || ''} ${cat.nome}`;
            select.appendChild(option);
        });
    },

    voltarEscolhaTipo() {
        document.getElementById('globalFormSection').style.display = 'none';
        document.getElementById('globalTipoSection').style.display = 'block';
        document.getElementById('modalLancamentoGlobalTitulo').textContent = 'Nova Movimentação';
        this.resetarFormulario();
    },

    resetarFormulario() {
        const form = document.getElementById('globalFormLancamento');
        if (form) {
            form.reset();
        }

        document.getElementById('globalLancamentoValor').value = '0,00';
        document.getElementById('globalLancamentoData').value = new Date().toISOString().split('T')[0];
        document.getElementById('globalParcelamentoGroup').style.display = 'none';
        document.getElementById('globalNumeroParcelasGroup').style.display = 'none';

        this.tipoAtual = null;
    },

    async salvarLancamento() {
        if (!this.validarFormulario()) {
            return;
        }

        try {
            const dados = this.coletarDadosFormulario();

            Swal.fire({
                title: 'Salvando...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const response = await fetch(`${this.baseUrl}api/lancamentos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(dados)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Lançamento salvo com sucesso!',
                    timer: 2000,
                    showConfirmButton: false
                });

                this.closeModal();

                // Recarregar página se estiver em página de contas ou lançamentos
                if (window.location.pathname.includes('contas') || window.location.pathname.includes('lancamentos')) {
                    window.location.reload();
                }
            } else {
                throw new Error(result.message || 'Erro ao salvar lançamento');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message
            });
        }
    },

    validarFormulario() {
        const descricao = document.getElementById('globalLancamentoDescricao').value.trim();
        const valor = this.parseMoney(document.getElementById('globalLancamentoValor').value);
        const data = document.getElementById('globalLancamentoData').value;

        if (!descricao) {
            Swal.fire('Atenção', 'Informe a descrição', 'warning');
            return false;
        }

        if (!valor || valor <= 0) {
            Swal.fire('Atenção', 'Informe um valor válido', 'warning');
            return false;
        }

        if (!data) {
            Swal.fire('Atenção', 'Informe a data', 'warning');
            return false;
        }

        if (this.tipoAtual === 'transferencia') {
            const contaDestino = document.getElementById('globalLancamentoContaDestino').value;
            if (!contaDestino) {
                Swal.fire('Atenção', 'Selecione a conta de destino', 'warning');
                return false;
            }
        }

        return true;
    },

    coletarDadosFormulario() {
        const dados = {
            conta_id: this.contaSelecionada.id,
            tipo: document.getElementById('globalLancamentoTipo').value,
            descricao: document.getElementById('globalLancamentoDescricao').value.trim(),
            valor: this.parseMoney(document.getElementById('globalLancamentoValor').value),
            data: document.getElementById('globalLancamentoData').value,
            categoria_id: document.getElementById('globalLancamentoCategoria').value || null,
            observacao: document.getElementById('globalLancamentoObservacao').value.trim() || null,
            pago: document.getElementById('globalLancamentoPago').checked
        };

        if (this.tipoAtual === 'transferencia') {
            dados.conta_destino_id = document.getElementById('globalLancamentoContaDestino').value;
        }

        if (this.tipoAtual === 'despesa' || this.tipoAtual === 'agendamento') {
            const cartaoId = document.getElementById('globalLancamentoCartaoCredito').value;
            if (cartaoId) {
                dados.cartao_credito_id = cartaoId;
                dados.eh_parcelado = document.getElementById('globalLancamentoParcelado').checked;

                if (dados.eh_parcelado) {
                    dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas').value);
                }
            }
        }

        return dados;
    },

    formatarDinheiro(input) {
        let valor = input.value.replace(/\D/g, '');
        valor = (parseInt(valor) / 100).toFixed(2);
        valor = valor.replace('.', ',');
        valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        input.value = valor;
    },

    parseMoney(str) {
        if (!str) return 0;
        return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
    },

    formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    },

    atualizarPreviewParcelamento() {
        const valor = this.parseMoney(document.getElementById('globalLancamentoValor').value);
        const parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas').value);
        const preview = document.getElementById('globalParcelamentoPreview');

        if (valor > 0 && parcelas >= 2) {
            const valorParcela = valor / parcelas;
            preview.innerHTML = `
                <div class="preview-info">
                    <i class="fas fa-calculator"></i>
                    <span>${parcelas}x de ${this.formatMoney(valorParcela)}</span>
                </div>
            `;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }
};

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => lancamentoGlobalManager.init());
} else {
    lancamentoGlobalManager.init();
}
