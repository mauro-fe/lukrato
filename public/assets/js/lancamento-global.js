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

                this.contas = [];
            } else {
                const dataContas = await resContas.json();
                const contasArray = dataContas.contas || dataContas || [];

                // Garantir que cada conta tem um saldo (usar saldoAtual se disponível, senão saldo_inicial)
                this.contas = contasArray.map(conta => ({
                    ...conta,
                    saldo: conta.saldoAtual !== undefined ? conta.saldoAtual : (conta.saldo_inicial || 0)
                }));

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

        // Recorrência - mostrar campo de repetições
        const recorrenciaSelect = document.getElementById('globalLancamentoRecorrencia');
        if (recorrenciaSelect) {
            recorrenciaSelect.addEventListener('change', (e) => {
                const repeticoesGroup = document.getElementById('globalRepeticoesGroup');
                if (repeticoesGroup) {
                    repeticoesGroup.style.display = e.target.value !== '' ? 'block' : 'none';
                    if (e.target.value === '') {
                        const repeticoesInput = document.getElementById('globalLancamentoRepeticoes');
                        if (repeticoesInput) repeticoesInput.value = '';
                    }
                }
            });
        }

        // Submit do formulário
        const form = document.getElementById('globalFormLancamento');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.salvarLancamento();
            });
        }

        // Data padrão (usando data local, não UTC)
        const dataInput = document.getElementById('globalLancamentoData');
        if (dataInput && !dataInput.value) {
            const hoje = new Date();
            dataInput.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;
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

            // Resetar cor do header para o padrão (laranja)
            const headerGradient = overlay.querySelector('.lk-modal-header-gradient');
            if (headerGradient) {
                headerGradient.style.setProperty('background', 'var(--color-primary)', 'important');
            }

            this.resetarFormulario();
        }
    },

    async mostrarFormulario(tipo) {
        if (!this.contaSelecionada) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Selecione uma conta primeiro!',
                customClass: {
                    container: 'swal-above-modal'
                }
            });
            return;
        }

        // Garantir que categorias e cartões estejam carregados
        if (this.categorias.length === 0 || this.cartoes.length === 0) {
            await this.carregarDados();
        }

        this.tipoAtual = tipo;

        // Esconder seleção de tipo
        const tipoSection = document.getElementById('globalTipoSection');
        if (tipoSection) tipoSection.style.display = 'none';

        // Mostrar formulário
        const formSection = document.getElementById('globalFormSection');
        if (formSection) formSection.style.display = 'block';

        // Configurar tipo
        const tipoInput = document.getElementById('globalLancamentoTipo');
        if (tipoInput) tipoInput.value = tipo === 'agendamento' ? 'despesa' : tipo;

        const contaIdInput = document.getElementById('globalLancamentoContaId');
        if (contaIdInput) contaIdInput.value = this.contaSelecionada.id;

        // Configurar campos específicos por tipo
        this.configurarCamposPorTipo(tipo);

        // Atualizar título
        const titulos = {
            receita: 'Nova Receita',
            despesa: 'Nova Despesa',
            transferencia: 'Nova Transferência',
            agendamento: 'Novo Agendamento'
        };
        const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
        if (tituloEl) tituloEl.textContent = titulos[tipo] || 'Nova Movimentação';
    },

    configurarCamposPorTipo(tipo) {

        // Mudar cor do header conforme o tipo
        const headerGradient = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-header-gradient');
        if (headerGradient) {
            // Remover classes anteriores
            headerGradient.classList.remove('receita', 'despesa', 'transferencia', 'agendamento');

            // Aplicar cor conforme o tipo
            if (tipo === 'receita') {
                headerGradient.style.setProperty('background', 'linear-gradient(135deg, #28a745 0%, #20c997 100%)', 'important');
            } else if (tipo === 'despesa') {
                headerGradient.style.setProperty('background', 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)', 'important');
            } else if (tipo === 'transferencia') {
                headerGradient.style.setProperty('background', 'linear-gradient(135deg, #3498db 0%, #2980b9 100%)', 'important');
            } else if (tipo === 'agendamento') {
                headerGradient.style.setProperty('background', 'linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%)', 'important');
            }
        }

        // Conta Destino (apenas transferência)
        const contaDestinoGroup = document.getElementById('globalContaDestinoGroup');
        if (contaDestinoGroup) {
            contaDestinoGroup.style.display = tipo === 'transferencia' ? 'block' : 'none';
        }

        if (tipo === 'transferencia') {
            this.preencherContasDestino();
        }

        // Cartão de crédito (apenas despesa, NÃO para agendamento)
        const cartaoGroup = document.getElementById('globalCartaoCreditoGroup');
        if (cartaoGroup) {
            cartaoGroup.style.display = tipo === 'despesa' ? 'block' : 'none';
        }

        if (tipo === 'despesa') {
            this.preencherCartoes();
        }

        // Categoria
        const tipoCategoriaABuscar = tipo === 'receita' ? 'receita' : 'despesa';
        this.preencherCategorias(tipoCategoriaABuscar);

        // Mostrar/ocultar seleção de tipo de agendamento
        const tipoAgGroup = document.getElementById('globalTipoAgendamentoGroup');
        if (tipoAgGroup) {
            tipoAgGroup.style.display = tipo === 'agendamento' ? 'block' : 'none';
        }

        // Mostrar/ocultar campo de hora (apenas agendamento)
        const horaGroup = document.getElementById('globalHoraGroup');
        if (horaGroup) {
            horaGroup.style.display = tipo === 'agendamento' ? 'block' : 'none';
        }

        // Mostrar/ocultar campos de recorrência (apenas agendamento)
        const recorrenciaGroup = document.getElementById('globalRecorrenciaGroup');
        if (recorrenciaGroup) {
            recorrenciaGroup.style.display = tipo === 'agendamento' ? 'block' : 'none';
        }

        // Mostrar/ocultar campo de tempo de aviso (apenas agendamento)
        const tempoAvisoGroup = document.getElementById('globalTempoAvisoGroup');
        if (tempoAvisoGroup) {
            tempoAvisoGroup.style.display = tipo === 'agendamento' ? 'block' : 'none';
        }

        // Mostrar/ocultar canais de notificação (apenas agendamento)
        const canaisNotificacaoGroup = document.getElementById('globalCanaisNotificacaoGroup');
        if (canaisNotificacaoGroup) {
            canaisNotificacaoGroup.style.display = tipo === 'agendamento' ? 'block' : 'none';
        }
    },

    selecionarTipoAgendamento(tipo) {
        // Atualizar campo hidden
        const input = document.getElementById('globalLancamentoTipoAgendamento');
        if (input) input.value = tipo;

        // Atualizar visual dos botões
        const btns = document.querySelectorAll('#globalTipoAgendamentoGroup .lk-btn-tipo-ag');
        btns.forEach(btn => {
            btn.classList.remove('active');
            if (btn.classList.contains(`lk-btn-tipo-${tipo}`)) {
                btn.classList.add('active');
            }
        });

        // Atualizar categorias para o tipo selecionado
        this.preencherCategorias(tipo);
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

        const optionsCartoes = cartoesAtivos
            .map(cartao => `<option value="${cartao.id}">${cartao.nome_cartao || cartao.bandeira} •••• ${cartao.ultimos_digitos}</option>`)
            .join('');

        select.innerHTML = optionVazio + optionsCartoes;
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

    },

    voltarEscolhaTipo() {
        const formSection = document.getElementById('globalFormSection');
        if (formSection) formSection.style.display = 'none';

        const tipoSection = document.getElementById('globalTipoSection');
        if (tipoSection) tipoSection.style.display = 'block';

        const tituloEl = document.getElementById('modalLancamentoGlobalTitulo');
        if (tituloEl) tituloEl.textContent = 'Nova Movimentação';

        // Resetar cor do header para o padrão (laranja)
        const headerGradient = document.querySelector('#modalLancamentoGlobalOverlay .lk-modal-header-gradient');
        if (headerGradient) {
            headerGradient.style.setProperty('background', 'var(--color-primary)', 'important');
        }

        this.resetarFormulario();
    },

    resetarFormulario() {
        const form = document.getElementById('globalFormLancamento');
        if (form) {
            form.reset();
        }

        const valorInput = document.getElementById('globalLancamentoValor');
        if (valorInput) valorInput.value = '0,00';

        // Usar data local, não UTC (evita pular um dia em fusos negativos)
        const hoje = new Date();
        const dataInput = document.getElementById('globalLancamentoData');
        if (dataInput) dataInput.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;

        const parcelamentoGroup = document.getElementById('globalParcelamentoGroup');
        if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';

        const numParcelasGroup = document.getElementById('globalNumeroParcelasGroup');
        if (numParcelasGroup) numParcelasGroup.style.display = 'none';

        // Resetar tipo de agendamento
        const tipoAgGroup = document.getElementById('globalTipoAgendamentoGroup');
        if (tipoAgGroup) tipoAgGroup.style.display = 'none';
        const tipoAgInput = document.getElementById('globalLancamentoTipoAgendamento');
        if (tipoAgInput) tipoAgInput.value = 'despesa';

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

            // Determinar endpoint e dados baseado no tipo
            let apiUrl = `${this.baseUrl}api/lancamentos`;
            let requestData = dados;


            if (this.tipoAtual === 'agendamento') {
                // Usar endpoint de agendamentos
                apiUrl = `${this.baseUrl}api/agendamentos`;
                const tipoAgendamento = document.getElementById('globalLancamentoTipoAgendamento')?.value || 'despesa';

                // Montar data com hora do campo de hora
                let dataPagamento = dados.data;
                const horaInput = document.getElementById('globalLancamentoHora');
                const hora = horaInput?.value || '12:00';
                if (dataPagamento && !dataPagamento.includes(' ') && !dataPagamento.includes('T')) {
                    dataPagamento = dataPagamento + ' ' + hora + ':00';
                }

                // Coletar campos de recorrência
                const recorrenciaFreq = document.getElementById('globalLancamentoRecorrencia')?.value || '';
                const repeticoes = document.getElementById('globalLancamentoRepeticoes')?.value || '';
                const recorrente = recorrenciaFreq !== '' ? '1' : '0';

                // Calcular recorrencia_fim se tiver repetições
                let recorrenciaFim = null;
                if (recorrente === '1' && repeticoes && parseInt(repeticoes) > 0) {
                    recorrenciaFim = this.calcularRecorrenciaFim(dataPagamento, recorrenciaFreq, parseInt(repeticoes));
                }

                // Coletar tempo de aviso (converter de minutos para segundos)
                const tempoAvisoMinutos = parseInt(document.getElementById('globalLancamentoTempoAviso')?.value || '0');
                const lembrarAntesSegundos = tempoAvisoMinutos * 60;

                // Coletar canais de notificação
                const canalInapp = document.getElementById('globalCanalInapp')?.checked ? '1' : '0';
                const canalEmail = document.getElementById('globalCanalEmail')?.checked ? '1' : '0';

                requestData = {
                    titulo: dados.descricao,
                    tipo: tipoAgendamento,
                    valor: dados.valor,
                    valor_centavos: Math.round(dados.valor * 100),
                    data_pagamento: dataPagamento,
                    categoria_id: dados.categoria_id,
                    conta_id: dados.conta_id,
                    descricao: dados.observacao || '',
                    recorrente: recorrente,
                    recorrencia_freq: recorrenciaFreq || null,
                    recorrencia_intervalo: recorrente === '1' ? 1 : null,
                    recorrencia_fim: recorrenciaFim,
                    lembrar_antes_segundos: lembrarAntesSegundos,
                    canal_inapp: canalInapp,
                    canal_email: canalEmail
                };
            } else if (this.tipoAtual === 'transferencia') {
                apiUrl = `${this.baseUrl}api/transfers`;
                requestData = {
                    conta_id: dados.conta_id,
                    conta_id_destino: dados.conta_destino_id,
                    valor: dados.valor,
                    data: dados.data,
                    descricao: dados.descricao,
                    observacao: dados.observacao
                };
            }


            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(requestData)
            });


            const result = await response.json();

            // Verificar se foi sucesso (status 200/201 ou result.success/result.status === 'success')
            const isSuccess = response.ok && (
                result.success === true ||
                result.status === 'success' ||
                response.status === 201
            );

            if (isSuccess) {
                // Guardar tipo atual ANTES de fechar o modal (pois closeModal reseta)
                const tipoLancamento = this.tipoAtual;

                // Processar gamificação ANTES de fechar o modal
                if (result.data?.gamification?.points) {
                    try {
                        const gamif = result.data.gamification.points;

                        if (gamif.new_achievements && Array.isArray(gamif.new_achievements) && gamif.new_achievements.length > 0) {
                            gamif.new_achievements.forEach(ach => {
                                try {
                                    if (!ach || typeof ach !== 'object') {
                                        console.warn('Conquista inválida:', ach);
                                        return;
                                    }

                                    if (typeof window.notifyAchievementUnlocked === 'function') {
                                        window.notifyAchievementUnlocked(ach);
                                    }
                                } catch (error) {
                                    console.error('Erro ao exibir conquista:', error, ach);
                                }
                            });
                        }

                        if (gamif.level_up) {
                            try {
                                if (typeof window.notifyLevelUp === 'function') {
                                    window.notifyLevelUp(gamif.level);
                                }
                            } catch (error) {
                                console.error('Erro ao exibir level up:', error);
                            }
                        }
                    } catch (error) {
                        console.error('Erro ao processar gamificação:', error, result.data.gamification);
                    }
                }

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
                const titulo = titulos[tipoLancamento] || 'Lançamento Criado!';

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
                        container: 'swal-above-modal',
                        popup: 'animated fadeInDown faster'
                    }
                });

                // Recarregar página se estiver em página relevante
                const currentPath = window.location.pathname.toLowerCase();


                // Sempre recarregar se criou agendamento
                if (tipoLancamento === 'agendamento') {
                    window.location.reload();
                    return;
                }

                // Recarregar para contas ou lançamentos
                if (currentPath.includes('contas') || currentPath.includes('lancamentos')) {
                    window.location.reload();
                    return;
                }

                // Disparar eventos para atualizar outras partes da página
                window.dispatchEvent(new CustomEvent('lancamento-created', { detail: result.data }));

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

                // Se for erro de limite, mostrar com mais destaque
                if (errorMessage.toLowerCase().includes('limite')) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Limite Insuficiente',
                        text: errorMessage,
                        confirmButtonText: 'Entendi',
                        confirmButtonColor: '#d33',
                        customClass: {
                            container: 'swal-above-modal'
                        }
                    });
                    this.salvando = false;
                    const btnSalvar = document.getElementById('globalBtnSalvar');
                    if (btnSalvar) {
                        btnSalvar.disabled = false;
                        btnSalvar.innerHTML = '<i class="fas fa-save"></i> Salvar';
                    }
                    return;
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
                confirmButtonText: 'OK',
                customClass: {
                    container: 'swal-above-modal'
                }
            });
        }
    },

    validarFormulario() {
        const descricaoEl = document.getElementById('globalLancamentoDescricao');
        const valorEl = document.getElementById('globalLancamentoValor');
        const dataEl = document.getElementById('globalLancamentoData');

        const descricao = descricaoEl ? descricaoEl.value.trim() : '';
        const valor = valorEl ? this.parseMoney(valorEl.value) : 0;
        const data = dataEl ? dataEl.value : '';

        if (!descricao) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Informe a descrição',
                customClass: { container: 'swal-above-modal' }
            });
            return false;
        }

        if (!valor || valor <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Informe um valor válido',
                customClass: { container: 'swal-above-modal' }
            });
            return false;
        }

        if (!data) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Informe a data',
                customClass: { container: 'swal-above-modal' }
            });
            return false;
        }

        // Validar limite do cartão de crédito se houver
        if (this.tipoAtual === 'despesa') {
            const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
            if (cartaoId) {
                const cartao = this.cartoes.find(c => c.id == cartaoId);
                if (cartao) {
                    const limiteDisponivel = parseFloat(cartao.limite_disponivel || 0);
                    if (valor > limiteDisponivel) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Limite Insuficiente',
                            html: `
                                <p>O valor da compra (${this.formatMoney(valor)}) excede o limite disponível do cartão.</p>
                                <p><strong>Limite disponível:</strong> ${this.formatMoney(limiteDisponivel)}</p>
                            `,
                            confirmButtonText: 'Entendi',
                            customClass: {
                                container: 'swal-above-modal'
                            }
                        });
                        return false;
                    }
                }
            }
        }

        if (this.tipoAtual === 'transferencia') {
            const contaDestinoEl = document.getElementById('globalLancamentoContaDestino');
            const contaDestino = contaDestinoEl ? contaDestinoEl.value : null;
            if (!contaDestino) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Selecione a conta de destino',
                    customClass: { container: 'swal-above-modal' }
                });
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
            pago: true
        };

        if (this.tipoAtual === 'transferencia') {
            const contaDestinoEl = document.getElementById('globalLancamentoContaDestino');
            dados.conta_destino_id = contaDestinoEl ? parseInt(contaDestinoEl.value) : null;
            dados.eh_transferencia = true;
        }

        if (this.tipoAtual === 'despesa' || this.tipoAtual === 'agendamento') {
            const cartaoEl = document.getElementById('globalLancamentoCartaoCredito');
            const cartaoId = cartaoEl ? cartaoEl.value : null;
            if (cartaoId) {
                dados.cartao_credito_id = parseInt(cartaoId);
                const parceladoEl = document.getElementById('globalLancamentoParcelado');
                dados.eh_parcelado = parceladoEl ? parceladoEl.checked : false;

                if (dados.eh_parcelado) {
                    const parcelasEl = document.getElementById('globalLancamentoTotalParcelas');
                    dados.total_parcelas = parcelasEl ? parseInt(parcelasEl.value) : 1;
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
    },

    calcularRecorrenciaFim(dataInicio, frequencia, repeticoes) {
        if (!dataInicio || !frequencia || !repeticoes || repeticoes < 1) {
            return null;
        }

        try {
            // Parse date - handle both formats: "YYYY-MM-DD" and "YYYY-MM-DD HH:MM:SS"
            const datePart = dataInicio.split(' ')[0].split('T')[0];
            const [year, month, day] = datePart.split('-').map(Number);

            let dataFim = new Date(year, month - 1, day);

            switch (frequencia) {
                case 'diario':
                    dataFim.setDate(dataFim.getDate() + repeticoes);
                    break;
                case 'semanal':
                    dataFim.setDate(dataFim.getDate() + (repeticoes * 7));
                    break;
                case 'mensal':
                    dataFim.setMonth(dataFim.getMonth() + repeticoes);
                    break;
                case 'anual':
                    dataFim.setFullYear(dataFim.getFullYear() + repeticoes);
                    break;
                default:
                    return null;
            }

            // Format as YYYY-MM-DD
            const yyyy = dataFim.getFullYear();
            const mm = String(dataFim.getMonth() + 1).padStart(2, '0');
            const dd = String(dataFim.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        } catch (e) {
            console.error('Erro ao calcular recorrencia_fim:', e);
            return null;
        }
    }
};

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => lancamentoGlobalManager.init());
} else {
    lancamentoGlobalManager.init();
}
