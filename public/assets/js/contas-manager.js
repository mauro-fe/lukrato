/**
 * Gerenciador moderno de contas
 * Integração com instituições financeiras e cartões de crédito
 */

class ContasManager {
    constructor() {
        this.baseUrl = '/lukrato/public/api';
        this.instituicoes = [];
        this.contas = [];
        this.categorias = null; // Cache de categorias
        this.currentEditId = null;
        this.currentMonth = this.getCurrentMonth();
        this.isSubmitting = false;
        this.contaSelecionadaLancamento = null;

        this.init();
        this.setupMoneyMask();
        this.setupCartaoMoneyMask();
        this.setupLancamentoMoneyMask();
    }

    /**
     * Obter token CSRF (sempre fresco)
     */
    async getCSRFToken() {
        try {
            // Tentar buscar token fresco da API
            const response = await fetch('/lukrato/public/api/csrf-token.php');
            if (response.ok) {
                const data = await response.json();
                if (data.token) {
                    // Atualizar meta tag
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.setAttribute('content', data.token);
                    }
                    console.log('✓ Token CSRF fresco obtido da API:', data.token.substring(0, 20) + '...');
                    return data.token;
                }
            }
        } catch (error) {
            console.warn('Erro ao buscar token fresco, usando fallback:', error);
        }

        // Fallback: tentar meta tag
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (metaToken) {
            console.log('✓ CSRF token obtido da meta tag:', metaToken.substring(0, 20) + '...');
            return metaToken;
        }

        if (window.LK?.getCSRF) {
            const token = window.LK.getCSRF();
            console.log('✓ CSRF token obtido do LK.getCSRF:', token.substring(0, 20) + '...');
            return token;
        }

        if (window.CSRF) {
            console.log('✓ CSRF token obtido do window.CSRF');
            return window.CSRF;
        }

        console.error('❌ CSRF token não encontrado!');
        return '';
    }

    updateCSRFToken(newToken) {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
            console.log('✓ CSRF token atualizado:', newToken.substring(0, 20) + '...');
        }
        if (window.LK) {
            window.LK.csrf = newToken;
        }
        if (typeof window.CSRF !== 'undefined') {
            window.CSRF = newToken;
        }
    }

    async init() {
        await this.loadInstituicoes();
        await this.loadContas();
        this.attachEventListeners();
    }

    getCurrentMonth() {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    }

    /**
     * Obter baseUrl de forma consistente
     */
    getBaseUrl() {
        // Tentar usar a variável global primeiro
        if (window.BASE_URL) {
            return window.BASE_URL;
        }

        // Fallback: extrair do pathname
        const path = window.location.pathname;
        if (path.includes('/lukrato/public/')) {
            return '/lukrato/public/';
        }

        // Extrair dinamicamente até /contas
        if (path.includes('/contas')) {
            return path.split('/contas')[0] + '/';
        }

        // Último fallback
        return '/lukrato/public/';
    }

    /**
     * Carregar instituições financeiras
     */
    async loadInstituicoes() {
        try {
            const response = await fetch(`${this.baseUrl}/instituicoes`);
            if (!response.ok) throw new Error('Erro ao carregar instituições');

            this.instituicoes = await response.json();

            console.log('=== INSTITUIÇÕES CARREGADAS ===');
            console.log('Total:', this.instituicoes.length);
            if (this.instituicoes.length > 0) {
                const nubank = this.instituicoes.find(i => i.codigo === 'nubank');
                if (nubank) {
                    console.log('Nubank encontrado:', nubank);
                    console.log('Logo URL:', nubank.logo_url);
                }
            }

            this.renderInstituicoesSelect();
        } catch (error) {
            console.error('Erro ao carregar instituições:', error);
            this.showToast('Erro ao carregar instituições financeiras', 'error');
        }
    }

    /**
     * Carregar contas do usuário
     */
    async loadContas() {
        try {
            this.showLoading(true);

            const params = new URLSearchParams({
                with_balances: '1',
                month: this.currentMonth,
                only_active: '1'
            });

            const response = await fetch(`${this.baseUrl}/contas?${params}`);
            if (!response.ok) throw new Error('Erro ao carregar contas');

            this.contas = await response.json();

            console.log('=== CONTAS CARREGADAS ===');
            console.log('Total:', this.contas.length);
            if (this.contas.length > 0) {
                console.log('Primeira conta:', this.contas[0]);
                console.log('Instituição da primeira:', this.contas[0].instituicao_financeira);
            }

            this.renderContas();
            this.updateStats();
        } catch (error) {
            console.error('Erro ao carregar contas:', error);
            this.showToast('Erro ao carregar contas', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Renderizar lista de contas
     */
    renderContas() {
        const container = document.getElementById('accountsGrid');
        if (!container) return;

        if (this.contas.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-wallet fa-3x mb-3 text-muted"></i>
                    <p class="text-muted">Nenhuma conta cadastrada</p>
                    <button class="btn btn-primary" id="btnNovaConta">
                        <i class="fas fa-plus"></i> Criar primeira conta
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = this.contas.map(conta => this.createContaCard(conta)).join('');
        this.attachContaCardListeners();
    }

    /**
     * Criar card de conta
     */
    createContaCard(conta) {
        // Buscar instituição do objeto conta ou da lista
        let instituicao = conta.instituicao_financeira || this.getInstituicao(conta.instituicao_financeira_id);

        const logoUrl = instituicao?.logo_url || `${this.baseUrl}assets/img/banks/default.svg`;
        const corPrimaria = instituicao?.cor_primaria || '#667eea';
        const saldo = conta.saldo_atual || conta.saldoAtual || 0;
        const saldoClass = saldo >= 0 ? 'positive' : 'negative';

        console.log('=== CRIANDO CARD ===');
        console.log('Conta:', conta.nome);
        console.log('ID Instituição:', conta.instituicao_financeira_id);
        console.log('Objeto instituicao_financeira:', conta.instituicao_financeira);
        console.log('Instituição encontrada:', instituicao);
        console.log('Logo URL final:', logoUrl);
        console.log('Cor:', corPrimaria);

        return `
            <div class="account-card" data-account-id="${conta.id}">
                <div class="account-header" style="background: ${corPrimaria};">
                    <div class="account-logo">
                        <img src="${logoUrl}" alt="${conta.nome}" />
                    </div>
                    <div class="account-actions">
                        <button class="btn-icon" onclick="contasManager.editConta(${conta.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon" onclick="contasManager.moreConta(${conta.id}, event)" title="Mais opções">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>
                <div class="account-body">
                    <h3 class="account-name">${conta.nome}</h3>
                    <div class="account-institution">${instituicao ? instituicao.nome : 'Instituição não definida'}</div>
                    <div class="account-balance ${saldoClass}">
                        ${this.formatCurrency(saldo)}
                    </div>
                    <div class="account-info">
                        <button class="btn-new-transaction" data-conta-id="${conta.id}" title="Novo Lançamento">
                            <i class="fas fa-plus-circle"></i> Novo Lançamento
                        </button>
                    </div>
                    ${this.renderCartoesBadge(conta)}
                </div>
            </div>
        `;
    }

    /**
     * Renderizar badge de cartões vinculados
     */
    renderCartoesBadge(conta) {
        // TODO: Implementar contagem de cartões vinculados
        return '';
    }

    /**
     * Renderizar select de instituições
     */
    renderInstituicoesSelect() {
        const select = document.getElementById('instituicaoFinanceiraSelect');
        if (!select) return;

        const grupos = this.groupByTipo(this.instituicoes);

        select.innerHTML = '<option value="">Selecione uma instituição</option>';

        Object.keys(grupos).forEach(tipo => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = this.formatTipo(tipo);

            grupos[tipo].forEach(inst => {
                const option = document.createElement('option');
                option.value = inst.id;
                option.textContent = inst.nome;
                option.dataset.codigo = inst.codigo;
                option.dataset.cor = inst.cor_primaria;
                optgroup.appendChild(option);
            });

            select.appendChild(optgroup);
        });
    }

    /**
     * Agrupar instituições por tipo
     */
    groupByTipo(instituicoes) {
        return instituicoes.reduce((acc, inst) => {
            if (!acc[inst.tipo]) acc[inst.tipo] = [];
            acc[inst.tipo].push(inst);
            return acc;
        }, {});
    }

    /**
     * Formatar tipo de instituição
     */
    formatTipo(tipo) {
        const tipos = {
            'banco': 'Bancos',
            'fintech': 'Fintechs',
            'carteira_digital': 'Carteiras Digitais',
            'corretora': 'Corretoras',
            'fisica': 'Dinheiro Físico',
            'outro': 'Outros'
        };
        return tipos[tipo] || tipo;
    }

    /**
     * Buscar instituição por ID
     */
    getInstituicao(id) {
        return this.instituicoes.find(inst => inst.id === id);
    }

    /**
     * Atualizar estatísticas
     */
    updateStats() {
        const totalContas = this.contas.length;
        const saldoTotal = this.contas.reduce((sum, c) => sum + (c.saldoAtual || 0), 0);

        const totalContasEl = document.getElementById('totalContas');
        const saldoTotalEl = document.getElementById('saldoTotal');

        if (totalContasEl) totalContasEl.textContent = totalContas;
        if (saldoTotalEl) saldoTotalEl.textContent = this.formatCurrency(saldoTotal);
    }

    /**
     * Formatar moeda
     */
    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    /**
     * Criar nova conta
     */
    async createConta(data) {
        const requestId = 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        try {
            console.log('🚀 [' + requestId + '] INÍCIO - Criar conta', data);

            const csrfToken = await this.getCSRFToken();
            console.log('🔐 [' + requestId + '] CSRF token obtido:', csrfToken.substring(0, 20) + '...');

            console.log('📤 [' + requestId + '] Enviando POST para:', `${this.baseUrl}/contas`);

            const response = await fetch(`${this.baseUrl}/contas`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            console.log('📥 [' + requestId + '] Resposta recebida - Status:', response.status);
            console.log('📥 [' + requestId + '] response.ok:', response.ok);

            const result = await response.json();
            console.log('📋 [' + requestId + '] Resultado parseado:', result);
            console.log('📋 [' + requestId + '] result.ok:', result.ok);
            console.log('📋 [' + requestId + '] result.success:', result.success);

            if (!response.ok || (!result.ok && !result.success)) {
                console.error('❌ [' + requestId + '] Erro na resposta - Condição falhou');
                console.error('❌ [' + requestId + '] !response.ok:', !response.ok);
                console.error('❌ [' + requestId + '] !result.ok:', !result.ok);
                console.error('❌ [' + requestId + '] !result.success:', !result.success);
                throw new Error(result.message || 'Erro ao criar conta');
            }

            // Atualizar token CSRF para próxima requisição
            if (result.csrf_token) {
                this.updateCSRFToken(result.csrf_token);
            }

            console.log('✅ [' + requestId + '] Conta criada com sucesso!');
            this.showToast('Conta criada com sucesso!', 'success');
            this.closeModal();
            await this.loadContas();

            // Scroll ao topo da página (modo seguro)
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        } catch (error) {
            console.error('💥 [' + requestId + '] EXCEPTION:', error);
            this.showToast(error.message, 'error');
        } finally {
            this.isSubmitting = false;
            console.log('🏁 [' + requestId + '] FIM - isSubmitting resetado');
        }
    }

    /**
     * Editar conta
     */
    async editConta(contaId) {
        const conta = this.contas.find(c => c.id === contaId);
        if (!conta) {
            console.error('Conta não encontrada:', contaId);
            return;
        }

        console.log('Editando conta:', conta);

        // Preencher formulário de edição
        this.openModal('edit', conta);
    }

    /**
     * Atualizar conta
     */
    async updateConta(contaId, data) {
        try {
            const csrfToken = await this.getCSRFToken();
            console.log('🔐 Usando CSRF token para atualizar conta:', csrfToken.substring(0, 20) + '...');

            const response = await fetch(`${this.baseUrl}/contas/${contaId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: JSON.stringify(data)
            });

            console.log('📡 Response status:', response.status);
            console.log('📡 Response headers:', Object.fromEntries(response.headers.entries()));

            // Capturar o texto da resposta primeiro
            const responseText = await response.text();
            console.log('📡 Response text:', responseText);

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ Erro ao fazer parse do JSON:', parseError);
                console.error('📄 Resposta recebida:', responseText);
                throw new Error('Resposta inválida do servidor. Verifique o console.');
            }

            if (!response.ok || (!result.ok && !result.success)) {
                throw new Error(result.message || 'Erro ao atualizar conta');
            }

            // Atualizar token CSRF para próxima requisição
            if (result.csrf_token) {
                this.updateCSRFToken(result.csrf_token);
            }

            this.showToast('Conta atualizada com sucesso!', 'success');
            await this.loadContas();
            this.closeModal();
        } catch (error) {
            console.error('Erro ao atualizar conta:', error);
            this.showToast(error.message, 'error');
        } finally {
            this.isSubmitting = false;
        }
    }

    /**
     * Arquivar conta
     */
    async archiveConta(contaId) {
        if (!confirm('Deseja realmente arquivar esta conta?')) return;

        try {
            const csrfToken = await this.getCSRFToken();
            console.log('🔐 Usando CSRF token para arquivar conta:', csrfToken.substring(0, 20) + '...');

            const response = await fetch(`${this.baseUrl}/contas/${contaId}/archive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Erro ao arquivar conta');
            }

            this.showToast('Conta arquivada com sucesso!', 'success');
            await this.loadContas();
        } catch (error) {
            console.error('Erro ao arquivar conta:', error);
            this.showToast(error.message, 'error');
        }
    }

    /**
     * Excluir conta com modal moderno
     */
    async deleteConta(contaId) {
        const conta = this.contas.find(c => c.id === contaId);
        const nomeConta = conta ? conta.nome : 'esta conta';

        this.showDeleteConfirmation(nomeConta, async () => {
            try {
                const csrfToken = await this.getCSRFToken();
                console.log('🔐 Usando CSRF token para deletar conta:', csrfToken.substring(0, 20) + '...');

                const response = await fetch(`${this.baseUrl}/contas/${contaId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-HTTP-Method-Override': 'DELETE'
                    }
                });

                const result = await response.json();

                // Se precisa de confirmação de força (tem lançamentos)
                if (result.status === 'confirm_delete') {
                    this.showDeleteConfirmation(
                        nomeConta + ' (tem lançamentos vinculados)',
                        async () => {
                            await this.forceDeleteConta(contaId);
                        },
                        'Esta conta possui lançamentos vinculados. Ao excluí-la, todos os lançamentos também serão removidos. Deseja continuar?'
                    );
                    return;
                }

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Erro ao excluir conta');
                }

                this.showToast('Conta excluída com sucesso!', 'success');
                await this.loadContas();
            } catch (error) {
                console.error('Erro ao excluir conta:', error);
                this.showToast(error.message, 'error');
            }
        });
    }

    /**
     * Forçar exclusão de conta (com lançamentos)
     */
    async forceDeleteConta(contaId) {
        try {
            const csrfToken = await this.getCSRFToken();

            const response = await fetch(`${this.baseUrl}/contas/${contaId}?force=1`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-HTTP-Method-Override': 'DELETE'
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Erro ao excluir conta');
            }

            this.showToast('Conta e lançamentos excluídos com sucesso!', 'success');
            await this.loadContas();
        } catch (error) {
            console.error('Erro ao excluir conta:', error);
            this.showToast(error.message, 'error');
        }
    }

    /**
     * Mostrar modal de confirmação de exclusão
     */
    showDeleteConfirmation(nomeConta, onConfirm, customMessage = null) {
        const overlay = document.getElementById('confirmDeleteOverlay');
        const messageEl = document.getElementById('confirmDeleteMessage');
        const btnConfirm = document.getElementById('btnConfirmDelete');
        const btnCancel = document.getElementById('btnCancelDelete');

        // Atualizar mensagem
        if (customMessage) {
            messageEl.textContent = customMessage;
        } else {
            messageEl.innerHTML = `Tem certeza que deseja excluir <strong>${nomeConta}</strong>?<br>Esta ação não pode ser desfeita.`;
        }

        // Mostrar modal
        overlay.style.display = 'flex';

        // Handlers
        const closeModal = () => {
            overlay.style.display = 'none';
            btnConfirm.onclick = null;
            btnCancel.onclick = null;
            overlay.onclick = null;
        };

        btnCancel.onclick = closeModal;
        overlay.onclick = (e) => {
            if (e.target === overlay) closeModal();
        };

        btnConfirm.onclick = async () => {
            closeModal();
            await onConfirm();
        };

        // ESC para fechar
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }

    /**
     * Mais opções da conta
     */
    moreConta(contaId, event) {
        // Prevenir propagação
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }

        const conta = this.contas.find(c => c.id === contaId);
        if (!conta) return;

        // Remover menus anteriores
        document.querySelectorAll('.context-menu').forEach(m => m.remove());

        // Criar menu
        const menuEl = document.createElement('div');
        menuEl.className = 'context-menu';
        menuEl.innerHTML = `
            <div class="menu-option" data-action="edit">
                <i class="fas fa-edit"></i>
                <span>Editar</span>
            </div>
            <div class="menu-option" data-action="archive">
                <i class="fas fa-archive"></i>
                <span>Arquivar</span>
            </div>
            <div class="menu-separator"></div>
            <div class="menu-option danger" data-action="delete">
                <i class="fas fa-trash"></i>
                <span>Excluir</span>
            </div>
        `;

        document.body.appendChild(menuEl);

        // Posicionar relativo ao botão clicado
        if (event && event.target) {
            const button = event.target.closest('.btn-icon');
            if (button) {
                const rect = button.getBoundingClientRect();
                menuEl.style.position = 'fixed';
                menuEl.style.top = (rect.bottom + 5) + 'px';
                menuEl.style.left = (rect.left - 150) + 'px'; // 150px é a largura aproximada do menu
            }
        }

        // Adicionar listeners
        menuEl.querySelectorAll('.menu-option').forEach(opt => {
            opt.addEventListener('click', (e) => {
                e.stopPropagation();
                const action = opt.dataset.action;

                switch (action) {
                    case 'edit':
                        this.editConta(contaId);
                        break;
                    case 'archive':
                        this.archiveConta(contaId);
                        break;
                    case 'delete':
                        this.deleteConta(contaId);
                        break;
                }

                menuEl.remove();
            });
        });

        // Fechar ao clicar fora
        setTimeout(() => {
            const closeMenu = (e) => {
                if (!menuEl.contains(e.target)) {
                    menuEl.remove();
                    document.removeEventListener('click', closeMenu);
                }
            };
            document.addEventListener('click', closeMenu);
        }, 100);
    }

    /**
     * Abrir modal
     */
    openModal(mode = 'create', data = null) {
        const modalOverlay = document.getElementById('modalContaOverlay');
        const modal = document.getElementById('modalConta');
        const titulo = document.getElementById('modalContaTitulo');

        if (!modalOverlay || !modal) return;

        // Atualizar título
        if (titulo) {
            titulo.textContent = mode === 'edit' ? 'Editar Conta' : 'Nova Conta';
        }

        // Preencher formulário se for edição
        if (mode === 'edit' && data) {
            console.log('Preenchendo modal com:', data);

            document.getElementById('contaId').value = data.id;
            document.getElementById('nomeConta').value = data.nome;

            // Instituicao financeira - garantir que preenche corretamente
            const instituicaoId = data.instituicao_financeira_id || data.instituicao_financeira?.id || '';
            console.log('Instituicao ID a preencher:', instituicaoId);
            document.getElementById('instituicaoFinanceiraSelect').value = instituicaoId;

            document.getElementById('tipoContaSelect').value = data.tipo_conta || 'conta_corrente';
            document.getElementById('moedaSelect').value = data.moeda || 'BRL';

            // Atualizar símbolo da moeda
            this.updateCurrencySymbol(data.moeda || 'BRL');

            // Formatar saldo inicial
            const saldo = data.saldoInicial || data.saldo_inicial || 0;
            const isNegative = saldo < 0;
            const valorCentavos = Math.abs(saldo) * 100;
            document.getElementById('saldoInicial').value = this.formatMoneyInput(valorCentavos, isNegative);
        } else {
            // Limpar formulário para novo cadastro
            document.getElementById('formConta')?.reset();
            document.getElementById('contaId').value = '';
            document.getElementById('saldoInicial').value = '0,00';

            // Garantir que o símbolo seja BRL ao criar nova conta
            this.updateCurrencySymbol('BRL');
        }

        // Mostrar modal
        modalOverlay.classList.add('active');

        // Focar no primeiro campo após animação
        setTimeout(() => {
            document.getElementById('nomeConta')?.focus();
        }, 300);
    }

    /**
     * Fechar modal
     */
    closeModal() {
        const modalOverlay = document.getElementById('modalContaOverlay');
        if (!modalOverlay) return;

        modalOverlay.classList.remove('active');

        // Restaurar scroll do body
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';

        // Resetar flag de submissão
        this.isSubmitting = false;

        // Limpar formulário após fechar
        setTimeout(() => {
            document.getElementById('formConta')?.reset();
            document.getElementById('contaId').value = '';
        }, 300);
    }

    /**
     * Anexar event listeners
     */
    attachEventListeners() {
        // Botão nova conta
        document.getElementById('btnNovaConta')?.addEventListener('click', () => {
            this.openModal('create');
        });

        // Botão reload
        document.getElementById('btnReload')?.addEventListener('click', () => {
            this.loadContas();
        });

        // Botão novo cartão
        document.getElementById('btnNovoCartao')?.addEventListener('click', () => {
            this.openCartaoModal('create');
        });

        // Formulário de cartão
        const formCartao = document.getElementById('formCartao');
        if (formCartao) {
            // Remover qualquer listener anterior
            const newFormCartao = formCartao.cloneNode(true);
            formCartao.parentNode.replaceChild(newFormCartao, formCartao);

            // Adicionar novo listener
            newFormCartao.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation();
                this.handleCartaoSubmit(e.target);
            });

            // Re-aplicar máscara de dinheiro após clonar
            this.setupCartaoMoneyMask();
        }

        // Formulário de conta - com proteção contra duplicação
        const formConta = document.getElementById('formConta');
        if (formConta) {
            // Remover qualquer listener anterior
            const newForm = formConta.cloneNode(true);
            formConta.parentNode.replaceChild(newForm, formConta);

            // Adicionar novo listener
            newForm.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation(); // Impede múltiplos listeners
                this.handleFormSubmit(e.target);
            });

            // Re-aplicar máscara de dinheiro após clonar
            this.setupMoneyMask();

            // Re-adicionar listener de mudança de moeda
            document.getElementById('moedaSelect')?.addEventListener('change', (e) => {
                this.updateCurrencySymbol(e.target.value);
            });
        }

        // Formulário de lançamento
        const formLancamento = document.getElementById('formLancamento');
        if (formLancamento) {
            // Remover qualquer listener anterior
            const newFormLancamento = formLancamento.cloneNode(true);
            formLancamento.parentNode.replaceChild(newFormLancamento, formLancamento);

            // Adicionar novo listener
            newFormLancamento.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation();
                this.handleLancamentoSubmit(e.target);
            });

            // Re-aplicar máscara de dinheiro após clonar
            this.setupLancamentoMoneyMask();
        }

        // Botão voltar no formulário de lançamento
        document.getElementById('btnVoltarTipo')?.addEventListener('click', () => {
            this.voltarEscolhaTipo();
        });

        // Fechar modal de lançamento ao clicar no overlay
        document.getElementById('modalLancamentoOverlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'modalLancamentoOverlay') {
                this.closeLancamentoModal();
            }
        });

        // Fechar modal ao clicar no overlay
        document.getElementById('modalContaOverlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'modalContaOverlay') {
                this.closeModal();
            }
        });

        // Fechar modal com tecla ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    /**
     * Anexar listeners nos cards de contas
     */
    attachContaCardListeners() {
        // Botões de novo lançamento
        document.querySelectorAll('.btn-new-transaction').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                const contaId = btn.dataset.contaId;
                const conta = this.contas.find(c => c.id == contaId);
                this.openLancamentoModal(contaId, conta?.nome || 'Conta');
            });
        });
    }

    /**
     * Abrir modal de lançamento com histórico
     */
    async openLancamentoModal(contaId, nomeConta) {
        const conta = this.contas.find(c => c.id == contaId);
        if (!conta) {
            this.showToast('Conta não encontrada', 'error');
            return;
        }

        const modalOverlay = document.getElementById('modalLancamentoOverlay');
        if (!modalOverlay) {
            this.showToast('Modal de lançamento não encontrado', 'error');
            return;
        }

        // Preencher informações da conta
        document.getElementById('lancamentoContaNome').textContent = conta.nome;
        document.getElementById('lancamentoContaSaldo').textContent = this.formatCurrency(conta.saldo_atual || 0);

        // Armazenar conta selecionada
        this.contaSelecionadaLancamento = conta;

        // Carregar histórico recente
        await this.carregarHistoricoRecente(contaId);

        // Mostrar modal
        modalOverlay.classList.add('active');
    }

    /**
     * Carregar histórico recente de movimentações
     */
    async carregarHistoricoRecente(contaId) {
        const historicoContainer = document.getElementById('lancamentoHistorico');

        try {
            // Buscar últimas 5 movimentações da conta
            const params = new URLSearchParams({
                account_id: contaId,
                limit: '5',
                month: new Date().toISOString().slice(0, 7) // Mês atual YYYY-MM
            });

            const response = await fetch(`${this.baseUrl}/lancamentos?${params}`);
            if (!response.ok) {
                throw new Error('Erro ao carregar histórico');
            }

            const lancamentos = await response.json();

            if (!lancamentos || lancamentos.length === 0) {
                historicoContainer.innerHTML = `
                    <div class="lk-historico-empty">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhuma movimentação recente</p>
                    </div>
                `;
                return;
            }

            // Renderizar histórico
            historicoContainer.innerHTML = lancamentos.map(l => {
                const tipoClass = l.tipo === 'receita' ? 'receita' : l.tipo === 'despesa' ? 'despesa' : 'transferencia';
                const tipoIcon = l.tipo === 'receita' ? 'arrow-down' : l.tipo === 'despesa' ? 'arrow-up' : 'exchange-alt';
                const sinal = l.tipo === 'receita' ? '+' : '-';
                const valorFormatado = this.formatCurrency(Math.abs(l.valor));
                const dataFormatada = new Date(l.data + 'T00:00:00').toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: 'short'
                });

                return `
                    <div class="lk-historico-item lk-historico-${tipoClass}">
                        <div class="lk-historico-icon">
                            <i class="fas fa-${tipoIcon}"></i>
                        </div>
                        <div class="lk-historico-info">
                            <div class="lk-historico-desc">${l.descricao || 'Sem descrição'}</div>
                            <div class="lk-historico-cat">${l.categoria || 'Sem categoria'}</div>
                        </div>
                        <div class="lk-historico-right">
                            <div class="lk-historico-valor">${sinal} ${valorFormatado}</div>
                            <div class="lk-historico-data">${dataFormatada}</div>
                        </div>
                    </div>
                `;
            }).join('');

        } catch (error) {
            console.error('Erro ao carregar histórico:', error);
            historicoContainer.innerHTML = `
                <div class="lk-historico-empty">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Erro ao carregar histórico</p>
                </div>
            `;
        }
    }

    /**
     * Mostrar formulário de lançamento
     */
    mostrarFormularioLancamento(tipo) {
        console.log('🚀 Abrindo formulário de lançamento, tipo:', tipo);

        // Ocultar seção de escolha
        document.getElementById('tipoSection').style.display = 'none';

        // Mostrar formulário
        const formSection = document.getElementById('formSection');
        formSection.style.display = 'block';

        // Preencher dados
        document.getElementById('lancamentoContaId').value = this.contaSelecionadaLancamento.id;
        document.getElementById('lancamentoTipo').value = tipo;

        // Data de hoje
        const hoje = new Date().toISOString().split('T')[0];
        document.getElementById('lancamentoData').value = hoje;

        // Carregar categorias
        console.log('📞 Chamando preencherCategorias...');
        this.preencherCategorias(tipo);

        // Configurar botão e título baseado no tipo
        const btnSalvar = document.getElementById('btnSalvarLancamento');
        const titulo = document.getElementById('modalLancamentoTitulo');

        const contaDestinoGroup = document.getElementById('contaDestinoGroup');

        if (tipo === 'receita') {
            titulo.textContent = '💰 Nova Receita';
            btnSalvar.innerHTML = '<i class="fas fa-check"></i> Salvar Receita';
            btnSalvar.className = 'lk-btn lk-btn-primary';
            btnSalvar.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            contaDestinoGroup.style.display = 'none';
        } else if (tipo === 'despesa') {
            titulo.textContent = '💸 Nova Despesa';
            btnSalvar.innerHTML = '<i class="fas fa-check"></i> Salvar Despesa';
            btnSalvar.className = 'lk-btn lk-btn-primary';
            btnSalvar.style.background = 'linear-gradient(135deg, #dc3545, #e74c3c)';
            contaDestinoGroup.style.display = 'none';
        } else if (tipo === 'transferencia') {
            titulo.textContent = '🔄 Nova Transferência';
            btnSalvar.innerHTML = '<i class="fas fa-check"></i> Salvar Transferência';
            btnSalvar.className = 'lk-btn lk-btn-primary';
            btnSalvar.style.background = 'linear-gradient(135deg, #17a2b8, #3498db)';
            contaDestinoGroup.style.display = 'block';

            // Preencher select de contas destino
            this.preencherContasDestino();
        }

        // Focar no primeiro campo
        setTimeout(() => {
            document.getElementById('lancamentoDescricao')?.focus();
        }, 100);
    }

    /**
     * Preencher select de contas destino (exceto a origem)
     */
    preencherContasDestino() {
        const select = document.getElementById('lancamentoContaDestino');
        const contaOrigemId = this.contaSelecionadaLancamento.id;

        select.innerHTML = '<option value="">Selecione a conta de destino</option>';

        this.contas.forEach(conta => {
            if (conta.id != contaOrigemId) {
                const option = document.createElement('option');
                option.value = conta.id;
                option.textContent = conta.nome;
                select.appendChild(option);
            }
        });
    }

    /**
     * Preencher categorias no formulário de lançamento
     */
    async preencherCategorias(tipo) {
        const select = document.getElementById('lancamentoCategoria');
        if (!select) {
            console.error('❌ Select de categoria não encontrado');
            return;
        }

        console.log('🔍 Preenchendo categorias para tipo:', tipo);

        try {
            // Se as categorias já foram carregadas, usar cache
            if (!this.categorias) {
                const baseUrl = this.getBaseUrl();
                const url = `${baseUrl}api/categorias`;
                console.log('📡 Buscando categorias em:', url);

                const response = await fetch(url);
                console.log('📥 Response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('✅ Resposta da API de categorias:', result);

                // A resposta pode vir como { success: true, data: [...] } ou diretamente [...]
                if (result.success && result.data) {
                    this.categorias = result.data;
                } else if (Array.isArray(result)) {
                    this.categorias = result;
                } else if (result.categorias) {
                    this.categorias = result.categorias;
                } else {
                    console.warn('⚠️ Formato de resposta inesperado:', result);
                    this.categorias = [];
                }
            }

            console.log('📦 Total de categorias no cache:', this.categorias?.length || 0);

            if (!this.categorias || this.categorias.length === 0) {
                console.warn('⚠️ Nenhuma categoria disponível');
                select.innerHTML = '<option value="">Nenhuma categoria cadastrada</option>';
                return;
            }

            // Filtrar categorias por tipo
            const categoriasFiltradas = this.categorias.filter(cat => {
                if (tipo === 'receita') return cat.tipo === 'receita';
                if (tipo === 'despesa') return cat.tipo === 'despesa';
                return true; // transferência pode usar qualquer
            });

            console.log(`✅ ${categoriasFiltradas.length} categorias filtradas para ${tipo}:`, categoriasFiltradas);

            // Preencher select
            select.innerHTML = '<option value="">Selecione a categoria (opcional)</option>';

            categoriasFiltradas.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.nome;
                select.appendChild(option);
            });

            console.log('✅ Select preenchido com', categoriasFiltradas.length, 'opções');

        } catch (error) {
            console.error('❌ Erro ao carregar categorias:', error);
            console.error('Stack:', error.stack);
            select.innerHTML = '<option value="">Erro ao carregar categorias</option>';

            // Mostrar erro visual para o usuário
            Swal.fire({
                icon: 'error',
                title: 'Erro ao carregar categorias',
                text: error.message || 'Não foi possível carregar as categorias. Tente novamente.',
                confirmButtonColor: '#3085d6'
            });
        }
    }

    /**
     * Voltar para escolha de tipo
     */
    voltarEscolhaTipo() {
        // Mostrar seção de escolha
        document.getElementById('tipoSection').style.display = 'block';

        // Ocultar formulário
        document.getElementById('formSection').style.display = 'none';

        // Limpar formulário
        document.getElementById('formLancamento').reset();

        // Restaurar título
        document.getElementById('modalLancamentoTitulo').textContent = 'Nova Movimentação';
    }

    /**
     * Selecionar tipo de lançamento (método antigo - agora redireciona)
     */
    selecionarTipoLancamento(tipo) {
        this.mostrarFormularioLancamento(tipo);
    }

    /**
     * Fechar modal de lançamento
     */
    closeLancamentoModal() {
        const modalOverlay = document.getElementById('modalLancamentoOverlay');
        if (!modalOverlay) return;

        modalOverlay.classList.remove('active');

        // Restaurar scroll
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';

        // Limpar conta selecionada
        this.contaSelecionadaLancamento = null;

        // Resetar para tela inicial
        setTimeout(() => {
            document.getElementById('tipoSection').style.display = 'block';
            document.getElementById('formSection').style.display = 'none';
            document.getElementById('formLancamento').reset();
            document.getElementById('modalLancamentoTitulo').textContent = 'Nova Movimentação';
        }, 300);
    }

    /**
     * Manipular submissão do formulário de lançamento
     */
    async handleLancamentoSubmit(form) {
        if (this.isSubmitting) {
            console.log('⚠️ Submissão já em andamento, ignorando...');
            return;
        }

        this.isSubmitting = true;

        // Desabilitar botão submit
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn?.innerHTML;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        }

        try {
            const formData = new FormData(form);
            const tipo = formData.get('tipo');
            const contaId = formData.get('conta_id');
            const valorFormatado = formData.get('valor');
            const contaDestinoId = formData.get('conta_destino_id');

            // Validações
            if (tipo === 'transferencia' && !contaDestinoId) {
                this.showNotification('Selecione a conta de destino', 'error');
                throw new Error('Conta destino obrigatória para transferências');
            }

            if (tipo === 'transferencia' && contaId === contaDestinoId) {
                this.showNotification('Conta de origem e destino devem ser diferentes', 'error');
                throw new Error('Contas iguais');
            }

            // Converter valor formatado para float
            const valor = this.parseMoneyInput(valorFormatado);

            if (valor <= 0) {
                this.showNotification('O valor deve ser maior que zero', 'error');
                throw new Error('Valor inválido');
            }

            const data = {
                conta_id: contaId,
                tipo: tipo,
                descricao: formData.get('descricao'),
                valor: valor,
                data: formData.get('data'),
                categoria_id: formData.get('categoria_id') || null,
                observacao: formData.get('observacoes') || null,
            };

            let apiUrl = '/api/lancamentos';
            let requestData = data;

            // Se for transferência, usar endpoint específico
            if (tipo === 'transferencia') {
                apiUrl = '/api/transfers';
                requestData = {
                    origem_id: contaId,
                    destino_id: contaDestinoId,
                    valor: valor,
                    data: formData.get('data'),
                    descricao: formData.get('descricao'),
                    observacao: formData.get('observacoes') || null,
                };
            }

            console.log('Enviando lançamento:', requestData);

            // Enviar para API
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Erro ao criar lançamento');
            }

            const result = await response.json();
            console.log('✅ Lançamento criado:', result);

            this.showNotification('Lançamento criado com sucesso!', 'success');

            // Fechar modal
            this.closeLancamentoModal();

            // Recarregar contas para atualizar saldo
            await this.loadContas();

        } catch (error) {
            console.error('❌ Erro ao criar lançamento:', error);

            if (error.message !== 'Conta destino obrigatória para transferências' &&
                error.message !== 'Contas iguais' &&
                error.message !== 'Valor inválido') {
                this.showNotification(
                    error.message || 'Erro ao criar lançamento. Tente novamente.',
                    'error'
                );
            }
        } finally {
            // Reabilitar botão
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }

            this.isSubmitting = false;
        }
    }

    /**
     * Manipular submissão do formulário
     */
    async handleFormSubmit(form) {
        if (this.isSubmitting) {
            console.log('⚠️ Submissão já em andamento, ignorando...');
            return;
        }

        this.isSubmitting = true;

        // Desabilitar botão submit
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn?.innerHTML;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        }

        try {
            const formData = new FormData(form);
            const contaId = document.getElementById('contaId')?.value;
            const saldoFormatado = formData.get('saldo_inicial');
            const instituicaoId = formData.get('instituicao_financeira_id');

            console.log('Form data:', {
                nome: formData.get('nome'),
                instituicao_raw: instituicaoId,
                tipo_conta: formData.get('tipo_conta'),
                moeda: formData.get('moeda'),
                saldo_formatado: saldoFormatado
            });

            const data = {
                nome: formData.get('nome'),
                instituicao_financeira_id: instituicaoId && instituicaoId !== '' && instituicaoId !== '0' ? parseInt(instituicaoId) : null,
                tipo_conta: formData.get('tipo_conta'),
                moeda: formData.get('moeda'),
                saldo_inicial: this.parseMoneyInput(saldoFormatado)
            };

            console.log('Dados a enviar:', data);
            console.log('Instituição ID (tipo):', typeof data.instituicao_financeira_id, data.instituicao_financeira_id);

            if (contaId) {
                await this.updateConta(parseInt(contaId), data);
            } else {
                await this.createConta(data);
            }
        } finally {
            // Restaurar botão
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    }

    /**
     * Configurar máscara de dinheiro
     */
    setupMoneyMask() {
        const saldoInput = document.getElementById('saldoInicial');
        if (!saldoInput) return;

        saldoInput.addEventListener('input', (e) => {
            let value = e.target.value;

            // Remove tudo que não é número ou sinal de menos
            value = value.replace(/[^\d-]/g, '');

            // Verifica se é negativo
            const isNegative = value.startsWith('-');

            // Remove o sinal para processar
            value = value.replace('-', '');

            // Converte para número
            let number = parseInt(value) || 0;

            // Formata como moeda
            const formatted = this.formatMoneyInput(number, isNegative);

            e.target.value = formatted;
        });

        // Formata ao carregar
        saldoInput.value = '0,00';
    }

    /**
     * Configurar máscara de dinheiro para limite do cartão
     */
    setupCartaoMoneyMask() {
        const limiteInput = document.getElementById('limiteTotal');
        if (!limiteInput) return;

        limiteInput.addEventListener('input', (e) => {
            let value = e.target.value;

            // Remove tudo que não é número
            value = value.replace(/[^\d]/g, '');

            // Converte para número
            let number = parseInt(value) || 0;

            // Formata como moeda
            const formatted = this.formatMoneyInput(number, false);

            e.target.value = formatted;
        });

        // Formata ao carregar
        limiteInput.value = '0,00';
    }

    /**
     * Configura máscara de dinheiro para input de lançamento
     */
    setupLancamentoMoneyMask() {
        const valorInput = document.getElementById('lancamentoValor');
        if (!valorInput) return;

        valorInput.addEventListener('input', (e) => {
            let value = e.target.value;

            // Remove tudo que não é número
            value = value.replace(/[^\d]/g, '');

            // Converte para número
            let number = parseInt(value) || 0;

            // Formata como moeda
            const formatted = this.formatMoneyInput(number, false);

            e.target.value = formatted;
        });

        // Formata ao carregar
        valorInput.value = '0,00';
    }

    /**
     * Formatar input de dinheiro
     */
    formatMoneyInput(value, isNegative = false) {
        // Converte centavos para reais
        const reais = value / 100;

        // Formata com 2 casas decimais
        let formatted = reais.toFixed(2)
            .replace('.', ',')
            .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return isNegative ? '-' + formatted : formatted;
    }

    /**
     * Converter valor formatado para número
     */
    parseMoneyInput(value) {
        if (!value) return 0;

        // Remove pontos de milhar e substitui vírgula por ponto
        const cleaned = value
            .replace(/\./g, '')
            .replace(',', '.');

        return parseFloat(cleaned) || 0;
    }

    /**
     * Atualizar símbolo da moeda no input
     */
    updateCurrencySymbol(currency) {
        const symbolElement = document.querySelector('.lk-currency-symbol');
        if (!symbolElement) return;

        const symbols = {
            'BRL': 'R$',
            'USD': '$',
            'EUR': '€'
        };

        symbolElement.textContent = symbols[currency] || 'R$';
    }

    /**
     * Mostrar/ocultar loading
     */
    showLoading(show) {
        const grid = document.getElementById('accountsGrid');
        if (!grid) return;

        if (show) {
            grid.innerHTML = `
                <div class="acc-skeleton"></div>
                <div class="acc-skeleton"></div>
                <div class="acc-skeleton"></div>
            `;
        }
    }

    /**
     * Mostrar toast/notificação
     */
    showToast(message, type = 'info') {
        // Criar elemento de toast
        const toast = document.createElement('div');
        toast.className = `lk-toast lk-toast-${type}`;
        toast.innerHTML = `
            <div class="lk-toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        // Adicionar ao body
        document.body.appendChild(toast);

        // Animar entrada
        setTimeout(() => toast.classList.add('lk-toast-show'), 10);

        // Remover após 4 segundos
        setTimeout(() => {
            toast.classList.remove('lk-toast-show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    /**
     * Abrir modal de cartão de crédito
     */
    openCartaoModal(mode = 'create', cartao = null) {
        const modalOverlay = document.getElementById('modalCartaoOverlay');
        const modal = document.getElementById('modalCartao');
        const titulo = document.getElementById('modalCartaoTitulo');

        if (!modalOverlay || !modal) {
            this.showToast('Modal de cartão não encontrado', 'error');
            return;
        }

        // Atualizar título
        if (titulo) {
            titulo.textContent = mode === 'edit' ? 'Editar Cartão de Crédito' : 'Novo Cartão de Crédito';
        }

        // Popular select de contas
        const contaSelect = document.getElementById('contaVinculada');
        if (contaSelect) {
            contaSelect.innerHTML = '<option value="">Selecione uma conta</option>';
            this.contas.forEach(conta => {
                const option = document.createElement('option');
                option.value = conta.id;
                option.textContent = conta.nome;
                contaSelect.appendChild(option);
            });
        }

        // Preencher dados se for edição
        if (mode === 'edit' && cartao) {
            document.getElementById('cartaoId').value = cartao.id;
            document.getElementById('nomeCartao').value = cartao.nome_cartao || '';
            document.getElementById('contaVinculada').value = cartao.conta_id || '';
            document.getElementById('bandeira').value = cartao.bandeira || 'visa';
            document.getElementById('ultimosDigitos').value = cartao.ultimos_digitos || '';

            // Formatar limite
            const limite = cartao.limite_total || 0;
            const limiteFormatado = this.formatMoneyInput(limite * 100, false);
            document.getElementById('limiteTotal').value = limiteFormatado;

            document.getElementById('diaFechamento').value = cartao.dia_fechamento || '';
            document.getElementById('diaVencimento').value = cartao.dia_vencimento || '';
        } else {
            // Limpar formulário para novo cadastro
            document.getElementById('formCartao')?.reset();
            document.getElementById('cartaoId').value = '';
            document.getElementById('limiteTotal').value = '0,00';
        }

        // Mostrar modal
        modalOverlay.classList.add('active');

        // Focar no primeiro campo após animação
        setTimeout(() => {
            document.getElementById('nomeCartao')?.focus();
        }, 300);
    }

    /**
     * Fechar modal de cartão
     */
    closeCartaoModal() {
        const modalOverlay = document.getElementById('modalCartaoOverlay');
        if (!modalOverlay) return;

        modalOverlay.classList.remove('active');

        // Restaurar scroll do body
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';

        setTimeout(() => {
            document.getElementById('formCartao')?.reset();
            document.getElementById('cartaoId').value = '';
            document.getElementById('limiteTotal').value = '0,00';
        }, 300);
    }

    /**
     * Manipular submissão do formulário de cartão
     */
    async handleCartaoSubmit(form) {
        const cartaoId = document.getElementById('cartaoId').value;
        const isEdit = !!cartaoId;

        const formData = {
            nome_cartao: document.getElementById('nomeCartao').value,
            conta_id: parseInt(document.getElementById('contaVinculada').value),
            bandeira: document.getElementById('bandeira').value,
            ultimos_digitos: document.getElementById('ultimosDigitos').value,
            limite_total: this.parseMoneyInput(document.getElementById('limiteTotal').value),
            dia_fechamento: parseInt(document.getElementById('diaFechamento').value) || null,
            dia_vencimento: parseInt(document.getElementById('diaVencimento').value) || null,
        };

        console.log('📤 Enviando cartão:', formData);

        try {
            const csrfToken = await this.getCSRFToken();
            const url = isEdit
                ? `${this.baseUrl}/cartoes/${cartaoId}`
                : `${this.baseUrl}/cartoes`;

            const method = isEdit ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-HTTP-Method-Override': method
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Erro ao salvar cartão');
            }

            this.showToast(
                isEdit ? 'Cartão atualizado com sucesso!' : 'Cartão criado com sucesso!',
                'success'
            );

            this.closeCartaoModal();
            this.loadContas(); // Recarregar para mostrar cartão vinculado

        } catch (error) {
            console.error('❌ Erro ao salvar cartão:', error);
            this.showToast(error.message || 'Erro ao salvar cartão', 'error');
        }
    }
}

// Inicializar quando o DOM estiver pronto
let contasManager;

// Prevenir múltiplas inicializações
if (!window.__CONTAS_MANAGER_INITIALIZED__) {
    window.__CONTAS_MANAGER_INITIALIZED__ = true;

    document.addEventListener('DOMContentLoaded', () => {
        // Remover qualquer instância anterior
        if (window.contasManager) {
            console.warn('⚠️ Removendo instância anterior do ContasManager');
        }

        console.log('🚀 Inicializando ContasManager v2.0');
        contasManager = new ContasManager();
        window.contasManager = contasManager; // Expor globalmente para debug
    });
} else {
    console.warn('⚠️ ContasManager já foi inicializado. Ignorando segunda chamada.');
}
