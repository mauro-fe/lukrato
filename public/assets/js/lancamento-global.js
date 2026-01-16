/**
 * Gerenciador de Lançamento Global (Header FAB)
 */
const lancamentoGlobalManager = {
    get baseUrl() {
        // Usar a função global LK.getBase() se disponível
        if (window.LK && typeof window.LK.getBase === 'function') {
            return window.LK.getBase();
        }
        // Fallback para meta tag
        const meta = document.querySelector('meta[name="base-url"]');
        if (meta?.content) return meta.content;
        // Fallback para window.BASE_URL
        return (window.BASE_URL || '/').replace(/\/?$/, '/');
    },
    contaSelecionada: null,
    contas: [],
    categorias: [],
    cartoes: [],
    tipoAtual: null,
    eventosConfigurados: false,
    salvando: false,

    init() {
        if (!this.eventosConfigurados) {
            this.configurarEventos();
            this.eventosConfigurados = true;
        }
        // Não carregar dados aqui, apenas quando abrir o modal
    },

    async carregarDados() {
        try {
            // Carregar contas com saldos calculados
            const resContas = await fetch(`${this.baseUrl}api/contas?with_balances=1`);
            if (!resContas.ok) {
                console.error('Erro ao carregar contas:', resContas.status);
                this.contas = [];
            } else {
                const dataContas = await resContas.json();
                const contasArray = dataContas.contas || dataContas || [];
                
                // Garantir que cada conta tem um saldo (usar saldoAtual se disponível, senão saldo_inicial)
                this.contas = contasArray.map(conta => ({
                    ...conta,
                    saldo: conta.saldoAtual !== undefined ? conta.saldoAtual : (conta.saldo_inicial || 0)
                }));
                
                console.log('Contas carregadas:', this.contas);
            }
            this.preencherSelectContas();

            // Carregar categorias
            const resCategorias = await fetch(`${this.baseUrl}api/categorias`);
            if (!resCategorias.ok) {
                console.error('Erro ao carregar categorias:', resCategorias.status);
                this.categorias = [];
            } else {
                const dataCategorias = await resCategorias.json();
                // Garantir que seja sempre um array
                let categoriasData = dataCategorias.categorias || dataCategorias.data || dataCategorias;
                this.categorias = Array.isArray(categoriasData) ? categoriasData : [];
                console.log('Categorias carregadas:', this.categorias);
                console.log('Tipo:', Array.isArray(this.categorias) ? 'Array' : typeof this.categorias);
            }

            // Carregar cartões
            const resCartoes = await fetch(`${this.baseUrl}api/cartoes`);
            if (!resCartoes.ok) {
                console.error('Erro ao carregar cartões:', resCartoes.status);
                this.cartoes = [];
            } else {
                const dataCartoes = await resCartoes.json();
                // Garantir que seja sempre um array
                let cartoesData = dataCartoes.cartoes || dataCartoes.data || dataCartoes;
                this.cartoes = Array.isArray(cartoesData) ? cartoesData : [];
                console.log('Cartões carregados:', this.cartoes);
            }

        } catch (error) {
            console.error('Erro ao carregar dados:', error);
        }
    },

    preencherSelectContas() {
        const select = document.getElementById('globalContaSelect');
        if (!select) {
            console.warn('Select globalContaSelect não encontrado');
            return;
        }

        select.innerHTML = '<option value="">Escolha uma conta...</option>';

        if (this.contas.length === 0) {
            console.warn('Nenhuma conta disponível para preencher');
            return;
        }

        this.contas.forEach(conta => {
            const option = document.createElement('option');
            option.value = conta.id;
            const saldo = conta.saldo !== undefined ? conta.saldo : (conta.saldoAtual !== undefined ? conta.saldoAtual : conta.saldo_inicial || 0);
            option.textContent = `${conta.nome} - ${this.formatMoney(saldo)}`;
            option.dataset.saldo = saldo;
            option.dataset.nome = conta.nome;
            select.appendChild(option);
        });
        
        console.log('Select preenchido com', this.contas.length, 'contas');
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

    async openModal() {
        const overlay = document.getElementById('modalLancamentoGlobalOverlay');
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Sempre recarregar dados para garantir saldos atualizados
            await this.carregarDados();

            // Resetar para escolha de tipo
            this.voltarEscolhaTipo();

            // Se tiver apenas uma conta, selecionar automaticamente
            setTimeout(() => {
                if (this.contas.length === 1) {
                    const select = document.getElementById('globalContaSelect');
                    if (select) {
                        select.value = this.contas[0].id;
                        this.onContaChange();
                    }
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

    async mostrarFormulario(tipo) {
        if (!this.contaSelecionada) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Selecione uma conta primeiro!'
            });
            return;
        }

        // Garantir que categorias e cartões estejam carregados
        if (this.categorias.length === 0 || this.cartoes.length === 0) {
            console.log('Recarregando categorias e cartões...');
            await this.carregarDados();
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
        console.log('Configurando campos para o tipo:', tipo);
        console.log('Categorias disponíveis:', this.categorias);
        
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
        const tipoCategoriaABuscar = tipo === 'receita' ? 'receita' : 'despesa';
        console.log('Tipo de categoria a buscar:', tipoCategoriaABuscar);
        this.preencherCategorias(tipoCategoriaABuscar);

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
                const saldo = conta.saldo !== undefined ? conta.saldo : (conta.saldoAtual !== undefined ? conta.saldoAtual : conta.saldo_inicial || 0);
                option.textContent = `${conta.nome} - ${this.formatMoney(saldo)}`;
                select.appendChild(option);
            }
        });
    },

    preencherCartoes() {
        const select = document.getElementById('globalLancamentoCartaoCredito');
        if (!select) {
            console.warn('Select globalLancamentoCartaoCredito não encontrado');
            return;
        }

        const optionVazio = '<option value="">Não usar cartão (débito na conta)</option>';
        
        // Garantir que cartoes seja um array
        if (!Array.isArray(this.cartoes)) {
            console.error('this.cartoes não é um array:', this.cartoes);
            this.cartoes = [];
        }
        
        if (this.cartoes.length === 0) {
            console.warn('Nenhum cartão carregado');
            select.innerHTML = optionVazio;
            return;
        }
        
        const cartoesAtivos = this.cartoes.filter(c => c.ativo);
        console.log('Cartões ativos:', cartoesAtivos);
        
        const optionsCartoes = cartoesAtivos
            .map(cartao => `<option value="${cartao.id}">${cartao.nome_cartao || cartao.bandeira} •••• ${cartao.ultimos_digitos}</option>`)
            .join('');

        select.innerHTML = optionVazio + optionsCartoes;
        console.log('Select de cartões preenchido com', cartoesAtivos.length, 'cartões');
    },

    preencherCategorias(tipo) {
        const select = document.getElementById('globalLancamentoCategoria');
        if (!select) {
            console.warn('Select globalLancamentoCategoria não encontrado');
            return;
        }

        // Garantir que categorias seja um array
        if (!Array.isArray(this.categorias)) {
            console.error('this.categorias não é um array:', this.categorias);
            this.categorias = [];
        }

        // Verificar se há categorias carregadas
        if (this.categorias.length === 0) {
            console.warn('Nenhuma categoria carregada ainda. Total:', this.categorias.length);
            select.innerHTML = '<option value="">Sem categoria</option>';
            return;
        }

        const categoriasFiltradas = this.categorias.filter(c => c.tipo === tipo);
        console.log(`Preenchendo categorias do tipo "${tipo}":`, categoriasFiltradas);

        select.innerHTML = '<option value="">Sem categoria</option>';
        
        if (categoriasFiltradas.length === 0) {
            console.warn(`Nenhuma categoria do tipo "${tipo}" encontrada`);
        } else {
            categoriasFiltradas.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = `${cat.icone || ''} ${cat.nome}`.trim();
                select.appendChild(option);
            });
        }
        
        console.log('Select de categorias preenchido com', categoriasFiltradas.length, 'categorias');
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
        // Prevenir múltiplas submissões
        if (this.salvando) {
            console.warn('Já existe uma submissão em andamento');
            return;
        }

        if (!this.validarFormulario()) {
            return;
        }

        this.salvando = true;

        try {
            const dados = this.coletarDadosFormulario();

            // Desabilitar botão de submit
            const btnSalvar = document.getElementById('globalBtnSalvar');
            if (btnSalvar) {
                btnSalvar.disabled = true;
                btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            console.log('CSRF Token:', csrfToken);
            console.log('URL da API:', `${this.baseUrl}api/lancamentos`);

            const response = await fetch(`${this.baseUrl}api/lancamentos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(dados)
            });

            console.log('Response status:', response.status);
            
            const result = await response.json();
            console.log('Response data:', result);

            // Verificar se foi sucesso (status 200/201 ou result.success/result.status === 'success')
            const isSuccess = response.ok && (
                result.success === true || 
                result.status === 'success' || 
                response.status === 201
            );

            if (isSuccess) {
                // Fechar modal ANTES de mostrar o Sweet Alert
                this.closeModal();
                
                // Pequeno delay para garantir que o modal feche completamente
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Determinar o título baseado no tipo de lançamento
                const titulos = {
                    'receita': 'Receita Criada!',
                    'despesa': 'Despesa Criada!',
                    'transferencia': 'Transferência Criada!',
                    'agendamento': 'Agendamento Criado!'
                };
                const titulo = titulos[this.tipoAtual] || 'Lançamento Criado!';
                
                await Swal.fire({
                    icon: 'success',
                    title: titulo,
                    html: `
                        <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                            ${result.message || 'Seu lançamento foi salvo com sucesso!'}
                        </p>
                        <p style="color: #666; font-size: 0.9rem;">
                            <i class="fas fa-check-circle"></i> Dados atualizados
                        </p>
                    `,
                    confirmButtonText: 'Ok, entendi!',
                    confirmButtonColor: '#28a745',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'animated fadeInDown faster'
                    }
                });

                // Recarregar página se estiver em página de contas ou lançamentos
                if (window.location.pathname.includes('contas') || window.location.pathname.includes('lancamentos')) {
                    window.location.reload();
                } else {
                    // Disparar evento para atualizar outras partes da página
                    window.dispatchEvent(new CustomEvent('lancamento-created', { detail: result.data }));
                }
                
                this.salvando = false;
                
                // Reabilitar botão
                const btnSalvar = document.getElementById('globalBtnSalvar');
                if (btnSalvar) {
                    btnSalvar.disabled = false;
                    btnSalvar.innerHTML = '<i class="fas fa-save"></i> Salvar';
                }
            } else {
                // Mostrar erros específicos da validação
                let errorMessage = result.message || 'Erro ao salvar lançamento';
                
                if (result.errors) {
                    const errorList = Object.values(result.errors).flat().join('\n');
                    errorMessage = errorList || errorMessage;
                }
                
                throw new Error(errorMessage);
            }
        } catch (error) {
            console.error('Erro ao salvar lançamento:', error);
            this.salvando = false;
            
            // Reabilitar botão
            const btnSalvar = document.getElementById('globalBtnSalvar');
            if (btnSalvar) {
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = '<i class="fas fa-save"></i> Salvar';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message,
                confirmButtonText: 'OK'
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
        const contaId = this.contaSelecionada?.id;
        
        if (!contaId) {
            console.error('Conta não selecionada!');
            throw new Error('Conta não selecionada');
        }

        const dados = {
            conta_id: parseInt(contaId),
            tipo: document.getElementById('globalLancamentoTipo').value,
            descricao: document.getElementById('globalLancamentoDescricao').value.trim(),
            valor: this.parseMoney(document.getElementById('globalLancamentoValor').value),
            data: document.getElementById('globalLancamentoData').value,
            categoria_id: document.getElementById('globalLancamentoCategoria').value || null,
            observacao: document.getElementById('globalLancamentoObservacao').value.trim() || null,
            pago: document.getElementById('globalLancamentoPago').checked
        };

        console.log('Dados base coletados:', dados);

        if (this.tipoAtual === 'transferencia') {
            dados.conta_destino_id = parseInt(document.getElementById('globalLancamentoContaDestino').value);
            dados.eh_transferencia = true;
            console.log('Transferência - conta_destino_id:', dados.conta_destino_id);
        }

        if (this.tipoAtual === 'despesa' || this.tipoAtual === 'agendamento') {
            const cartaoId = document.getElementById('globalLancamentoCartaoCredito').value;
            if (cartaoId) {
                dados.cartao_credito_id = parseInt(cartaoId);
                dados.eh_parcelado = document.getElementById('globalLancamentoParcelado').checked;

                if (dados.eh_parcelado) {
                    dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas').value);
                }
                console.log('Cartão selecionado:', cartaoId, 'Parcelado:', dados.eh_parcelado);
            }
        }

        console.log('Dados finais para enviar:', dados);
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
