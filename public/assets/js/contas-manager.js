/**
 * Gerenciador moderno de contas
 * Integração com instituições financeiras e cartões de crédito
 */

class ContasManager {
    constructor() {
        this.baseUrl = this.getBaseUrl() + 'api';
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
                    return data.token;
                }
            }
        } catch (error) {
            console.warn('Erro ao buscar token fresco, usando fallback:', error);
        }

        // Fallback: tentar meta tag
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (metaToken) {
            return metaToken;
        }

        if (window.LK?.getCSRF) {
            const token = window.LK.getCSRF();
            return token;
        }

        if (window.CSRF) {
            return window.CSRF;
        }

        console.error('❌ CSRF token não encontrado!');
        return '';
    }

    updateCSRFToken(newToken) {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }
        if (window.LK) {
            window.LK.csrf = newToken;
        }
        if (typeof window.CSRF !== 'undefined') {
            window.CSRF = newToken;
        }
    }

    /**
     * Exibir notificação para o usuário
     */
    showNotification(message, type = 'info') {
        // Se houver função global showNotification, usar ela
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }



        // Criar notificação toast simples
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);

        // Remover após 3 segundos
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    async init() {
        await this.loadInstituicoes();
        await this.loadContas();
        this.attachEventListeners();
        this.initKeyboardShortcuts();
    }

    /**
     * Inicializar atalhos de teclado
     */
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ignorar se estiver em input ou modal aberto
            const activeEl = document.activeElement;
            const isInputFocused = activeEl && (
                activeEl.tagName === 'INPUT' ||
                activeEl.tagName === 'TEXTAREA' ||
                activeEl.tagName === 'SELECT' ||
                activeEl.isContentEditable
            );
            const isModalOpen = document.querySelector('.modal.show, .modal-overlay.active');

            if (isInputFocused || isModalOpen) return;

            // N = Nova conta
            if (e.key.toLowerCase() === 'n' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                this.openModal('create');
            }
        });
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
            // Garante que termina com barra
            return window.BASE_URL.endsWith('/') ? window.BASE_URL : window.BASE_URL + '/';
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
            let data;
            
            // Usar lkFetch se disponível (com timeout e retry)
            if (window.lkFetch) {
                const result = await window.lkFetch.get(`${this.baseUrl}/instituicoes`, {
                    timeout: 15000,
                    maxRetries: 2,
                    showLoading: false
                });
                data = result.data;
            } else {
                // Fallback com timeout manual
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 15000);
                
                const response = await fetch(`${this.baseUrl}/instituicoes`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) throw new Error('Erro ao carregar instituições');
                data = await response.json();
            }

            this.instituicoes = Array.isArray(data) ? data : (data.data || []);

            if (this.instituicoes.length > 0) {
                const nubank = this.instituicoes.find(i => i.codigo === 'nubank');
                if (nubank) {
                    // Nubank encontrado
                }
            }

            this.renderInstituicoesSelect();
        } catch (error) {
            console.error('Erro ao carregar instituições:', error);
            
            // Mensagem mais amigável para timeout
            let message = 'Erro ao carregar instituições financeiras';
            if (error.name === 'AbortError' || error.message?.includes('demorou')) {
                message = 'A conexão está lenta. Tente novamente.';
            } else if (!navigator.onLine) {
                message = 'Sem conexão com a internet';
            }
            
            this.showToast(message, 'error');
        }
    }

    /**
     * Carregar contas do usuário
     */
    async loadContas() {
        const grid = document.getElementById('accountsGrid');
        
        try {
            this.showLoading(true);

            const params = new URLSearchParams({
                with_balances: '1',
                month: this.currentMonth,
                only_active: '1'
            });

            let data;
            
            // Usar lkFetch se disponível (com timeout, retry e indicadores)
            if (window.lkFetch) {
                const result = await window.lkFetch.get(`${this.baseUrl}/contas?${params}`, {
                    timeout: 20000,
                    maxRetries: 2,
                    showLoading: true,
                    loadingTarget: '#accountsGrid'
                });
                data = result.data;
            } else {
                // Fallback com timeout manual
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 20000);
                
                const response = await fetch(`${this.baseUrl}/contas?${params}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Erro na resposta:', errorText);
                    throw new Error(`Erro ao carregar contas: ${response.status}`);
                }
                
                data = await response.json();
            }

            // A resposta pode ser um array direto ou um objeto com data
            this.contas = Array.isArray(data) ? data : (data.data || data.contas || []);

            if (this.contas.length > 0) {
                // Contas carregadas
            }

            this.renderContas();
            this.updateStats();
        } catch (error) {
            console.error('Erro ao carregar contas:', error);
            
            // Mensagem mais amigável para timeout
            let message = 'Erro ao carregar contas';
            if (error.name === 'AbortError' || error.message?.includes('demorou')) {
                message = 'A conexão está lenta. Tente novamente.';
            } else if (!navigator.onLine) {
                message = 'Sem conexão com a internet';
            }
            
            this.showToast(message, 'error');
            
            // Mostrar estado de erro com botão de retry
            if (grid) {
                grid.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p class="error-message">${message}</p>
                        <button class="btn btn-primary btn-retry" onclick="window.contasManager.loadContas()">
                            <i class="fas fa-redo"></i> Tentar novamente
                        </button>
                    </div>
                `;
            }
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
                    <div class="empty-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3>Nenhuma conta cadastrada</h3>
                    <p>Comece criando sua primeira conta bancária para gerenciar suas finanças</p>
                    <button class="btn btn-primary btn-lg" id="btnCriarPrimeiraConta">
                        <i class="fas fa-plus"></i> Criar primeira conta
                    </button>
                </div>
            `;
            // Anexar listener para o botão de criar primeira conta
            setTimeout(() => {
                const btnCriarPrimeira = document.getElementById('btnCriarPrimeiraConta');
                if (btnCriarPrimeira) {
                    btnCriarPrimeira.addEventListener('click', () => {
                        this.openModal('create');
                    });
                }
            }, 100);
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
        // Normalizar saldo: valores muito próximos de zero são tratados como zero
        let saldo = conta.saldo_atual || conta.saldoAtual || 0;
        if (Math.abs(saldo) < 0.01) saldo = 0;
        const saldoClass = saldo >= 0 ? 'positive' : 'negative';
        
        // Badge do tipo de conta para list view
        const tipoConta = conta.tipo_conta || conta.tipo || 'conta_corrente';
        const tipoLabel = this.formatTipoConta(tipoConta);
        const tipoClass = this.getTipoContaClass(tipoConta);

        return `
            <div class="account-card" data-account-id="${conta.id}">
                <div class="account-header" style="background: ${corPrimaria};">
                    <div class="account-logo">
                        <img src="${logoUrl}" alt="${conta.nome}" />
                    </div>
                    <div class="account-actions">
                     <button
                type="button"
                class="lk-info"
                data-lk-tooltip-title="Exclusão de contas"
                data-lk-tooltip="Para manter a integridade dos seus dados, contas só podem ser excluídas após serem arquivadas. Arquive a conta primeiro e depois realize a exclusão."
                aria-label="Ajuda: Exclusão de contas"
            >
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            </button>
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
                    <span class="account-type-badge ${tipoClass}">${tipoLabel}</span>
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
                <div class="account-list-actions">
                    <button class="btn-icon" onclick="contasManager.editConta(${conta.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon" onclick="contasManager.moreConta(${conta.id}, event)" title="Mais opções">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Formatar label do tipo de conta
     */
    formatTipoConta(tipo) {
        const labels = {
            'conta_corrente': 'Corrente',
            'conta_poupanca': 'Poupança',
            'conta_investimento': 'Investimento',
            'carteira_digital': 'Carteira',
            'dinheiro': 'Dinheiro'
        };
        return labels[tipo] || 'Conta';
    }

    /**
     * Obter classe CSS do tipo de conta
     */
    getTipoContaClass(tipo) {
        const classes = {
            'conta_corrente': 'tipo-corrente',
            'conta_poupanca': 'tipo-poupanca',
            'conta_investimento': 'tipo-investimento',
            'carteira_digital': 'tipo-carteira',
            'dinheiro': 'tipo-carteira'
        };
        return classes[tipo] || 'tipo-corrente';
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
     * Abrir modal de nova instituição
     */
    openNovaInstituicaoModal() {
        const overlay = document.getElementById('modalNovaInstituicaoOverlay');
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Focar no campo de nome
            setTimeout(() => {
                document.getElementById('nomeInstituicao')?.focus();
            }, 100);
        }
    }

    /**
     * Fechar modal de nova instituição
     */
    closeNovaInstituicaoModal() {
        const overlay = document.getElementById('modalNovaInstituicaoOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';

            // Limpar formulário
            document.getElementById('formNovaInstituicao')?.reset();
            document.getElementById('corInstituicao').value = '#3498db';
            this.updateColorPreview('#3498db');
        }
    }

    /**
     * Atualizar preview de cor
     */
    updateColorPreview(color) {
        const preview = document.getElementById('colorPreview');
        const value = document.getElementById('colorValue');
        if (preview) preview.style.background = color;
        if (value) value.textContent = color;
    }

    /**
     * Criar nova instituição
     */
    async createInstituicao(data) {
        try {
            const csrfToken = await this.getCSRFToken();
            const response = await fetch(`${this.baseUrl}/instituicoes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Erro ao criar instituição');
            }

            return result;
        } catch (error) {
            console.error('Erro ao criar instituição:', error);
            throw error;
        }
    }

    /**
     * Handler do formulário de nova instituição
     */
    async handleNovaInstituicaoSubmit(form) {
        const formData = new FormData(form);
        const data = {
            nome: formData.get('nome'),
            tipo: formData.get('tipo'),
            cor_primaria: formData.get('cor_primaria'),
            cor_secundaria: '#FFFFFF'
        };

        try {
            const result = await this.createInstituicao(data);

            // Adicionar a nova instituição à lista
            if (result.data) {
                this.instituicoes.push(result.data);
                this.renderInstituicoesSelect();

                // Selecionar a nova instituição no select
                const select = document.getElementById('instituicaoFinanceiraSelect');
                if (select) {
                    select.value = result.data.id;
                }
            }

            this.closeNovaInstituicaoModal();
            this.showToast('Instituição criada com sucesso!', 'success');

        } catch (error) {
            this.showToast(error.message, 'error');
        }
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
            'cooperativa': 'Cooperativas de Crédito',
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
        // Normalizar valores muito próximos de zero para evitar -R$ 0,00
        if (Math.abs(value) < 0.01) value = 0;
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    /**
     * Calcular data de fim da recorrência baseado em repetições
     */
    calcularRecorrenciaFim(dataInicio, frequencia, repeticoes) {
        if (!dataInicio || !frequencia || !repeticoes || repeticoes < 1) {
            return null;
        }

        try {
            // Parse date - handle formats: "YYYY-MM-DD", "YYYY-MM-DD HH:MM:SS"
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

    /**
     * Criar nova conta
     */
    async createConta(data) {
        const requestId = 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        try {

            const csrfToken = await this.getCSRFToken();
            const response = await fetch(`${this.baseUrl}/contas`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

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

        // Preencher formulário de edição
        this.openModal('edit', conta);
    }

    /**
     * Atualizar conta
     */
    async updateConta(contaId, data) {
        try {
            const csrfToken = await this.getCSRFToken();
            const response = await fetch(`${this.baseUrl}/contas/${contaId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: JSON.stringify(data)
            });

            // Capturar o texto da resposta primeiro
            const responseText = await response.text();

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
        const conta = this.contas.find(c => c.id === contaId);
        const nomeConta = conta ? conta.nome : 'esta conta';

        const result = await Swal.fire({
            title: 'Arquivar conta?',
            html: `Deseja realmente arquivar <strong>${nomeConta}</strong>?<br><small class="text-muted">A conta ficará oculta mas pode ser restaurada depois.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e67e22',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-archive"></i> Sim, arquivar',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: true,
            customClass: {
                popup: 'swal-custom-popup',
                confirmButton: 'swal-confirm-btn',
                cancelButton: 'swal-cancel-btn'
            }
        });

        if (!result.isConfirmed) return;

        try {
            const csrfToken = await this.getCSRFToken();

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

            Swal.fire({
                title: 'Arquivada!',
                text: 'A conta foi arquivada com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            await this.loadContas();
        } catch (error) {
            console.error('Erro ao arquivar conta:', error);
            Swal.fire({
                title: 'Erro!',
                text: error.message,
                icon: 'error',
                confirmButtonColor: '#e67e22'
            });
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
        `;

        document.body.appendChild(menuEl);

        // Posicionar relativo ao botão clicado
        if (event && event.target) {
            const button = event.target.closest('.btn-icon');
            if (button) {
                const rect = button.getBoundingClientRect();
                menuEl.style.position = 'absolute';
                menuEl.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                menuEl.style.left = (rect.left + window.scrollX - 150) + 'px'; // 150px é a largura aproximada do menu
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

            document.getElementById('contaId').value = data.id;
            document.getElementById('nomeConta').value = data.nome;

            // Instituicao financeira - garantir que preenche corretamente
            const instituicaoId = data.instituicao_financeira_id || data.instituicao_financeira?.id || '';
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

        // Anexar listeners de fechar
        this.attachCloseModalListeners();

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
     * Anexar listeners para fechar modal
     */
    attachCloseModalListeners() {
        // Remover listeners antigos e adicionar novos
        document.querySelectorAll('.modal-close-btn, .modal-close').forEach(btn => {
            // Clonar o botão para remover todos os listeners antigos
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            // Adicionar novo listener
            newBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeModal();
            });
        });

        // Fechar ao clicar fora (no overlay)
        const modalOverlay = document.getElementById('modalContaOverlay');
        if (modalOverlay) {
            modalOverlay.onclick = (e) => {
                if (e.target === modalOverlay) {
                    this.closeModal();
                }
            };
        }
    }

    /**
     * Anexar event listeners
     */
    attachEventListeners() {
        // Botões de fechar modal - Re-anexar sempre
        this.attachCloseModalListeners();

        // Botão nova conta
        const btnNovaConta = document.getElementById('btnNovaConta');
        if (btnNovaConta && !btnNovaConta.dataset.listenerAdded) {
            btnNovaConta.addEventListener('click', () => {
                this.openModal('create');
            });
            btnNovaConta.dataset.listenerAdded = 'true';
        }

        // Botão reload
        const btnReload = document.getElementById('btnReload');
        if (btnReload && !btnReload.dataset.listenerAdded) {
            btnReload.addEventListener('click', () => {
                this.loadContas();
            });
            btnReload.dataset.listenerAdded = 'true';
        }

        // Formulário de nova instituição
        const formNovaInstituicao = document.getElementById('formNovaInstituicao');
        if (formNovaInstituicao && !formNovaInstituicao.dataset.listenerAdded) {
            formNovaInstituicao.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleNovaInstituicaoSubmit(e.target);
            });
            formNovaInstituicao.dataset.listenerAdded = 'true';
        }

        // Input de cor da instituição
        const corInstituicao = document.getElementById('corInstituicao');
        if (corInstituicao && !corInstituicao.dataset.listenerAdded) {
            corInstituicao.addEventListener('input', (e) => {
                this.updateColorPreview(e.target.value);
            });
            corInstituicao.dataset.listenerAdded = 'true';
        }

        // Fechar modal de nova instituição ao clicar no overlay
        const modalNovaInstituicaoOverlay = document.getElementById('modalNovaInstituicaoOverlay');
        if (modalNovaInstituicaoOverlay && !modalNovaInstituicaoOverlay.dataset.listenerAdded) {
            modalNovaInstituicaoOverlay.addEventListener('click', (e) => {
                if (e.target.id === 'modalNovaInstituicaoOverlay') {
                    this.closeNovaInstituicaoModal();
                }
            });
            modalNovaInstituicaoOverlay.dataset.listenerAdded = 'true';
        }

        // Botão novo cartão
        const btnNovoCartao = document.getElementById('btnNovoCartao');
        if (btnNovoCartao && !btnNovoCartao.dataset.listenerAdded) {
            btnNovoCartao.addEventListener('click', () => {
                this.openCartaoModal('create');
            });
            btnNovoCartao.dataset.listenerAdded = 'true';
        }

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

            // Re-adicionar listener do botão de nova instituição após clonar o form
            const btnAddInstituicaoNew = document.getElementById('btnAddInstituicao');
            if (btnAddInstituicaoNew) {
                btnAddInstituicaoNew.addEventListener('click', () => {
                    this.openNovaInstituicaoModal();
                });
            }
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

        // View Toggle (Cards/Lista)
        this.initViewToggle();

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
     * Inicializar toggle de visualização (Cards/Lista)
     */
    initViewToggle() {
        const viewToggle = document.querySelector('.view-toggle');
        const accountsGrid = document.getElementById('accountsGrid');
        const listHeader = document.getElementById('contasListHeader');

        if (!viewToggle || !accountsGrid) return;

        const viewButtons = viewToggle.querySelectorAll('.view-btn');

        // Restaurar preferência salva
        const savedView = localStorage.getItem('contas_view_mode') || 'grid';
        if (savedView === 'list') {
            accountsGrid.classList.add('list-view');
            if (listHeader) listHeader.classList.add('visible');
        }

        // Atualizar estado dos botões
        this.updateViewToggleState(viewButtons, savedView);

        // Adicionar listeners aos botões
        viewButtons.forEach(btn => {
            if (btn.dataset.listenerAdded) return;

            btn.addEventListener('click', () => {
                const view = btn.dataset.view;

                if (view === 'list') {
                    accountsGrid.classList.add('list-view');
                    if (listHeader) listHeader.classList.add('visible');
                } else {
                    accountsGrid.classList.remove('list-view');
                    if (listHeader) listHeader.classList.remove('visible');
                }

                // Salvar preferência
                localStorage.setItem('contas_view_mode', view);

                // Atualizar estado dos botões
                this.updateViewToggleState(viewButtons, view);
            });

            btn.dataset.listenerAdded = 'true';
        });
    }

    /**
     * Atualizar estado visual dos botões de toggle
     */
    updateViewToggleState(buttons, activeView) {
        buttons.forEach(btn => {
            if (btn.dataset.view === activeView) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
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

        // Preencher informações da conta - o campo é saldoAtual (com A maiúsculo)
        const saldo = conta.saldoAtual ?? conta.saldo_atual ?? conta.saldo ?? 0;
        document.getElementById('lancamentoContaNome').textContent = conta.nome;
        document.getElementById('lancamentoContaSaldo').textContent = this.formatCurrency(saldo);

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

            const result = await response.json();

            // A resposta pode vir como array direto ou dentro de result.data
            const lancamentos = Array.isArray(result) ? result : (result.data || result.lancamentos || []);

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
        // Ocultar seção de escolha
        document.getElementById('tipoSection').style.display = 'none';

        // Mostrar formulário
        const formSection = document.getElementById('formSection');
        formSection.style.display = 'block';

        // Preencher dados
        document.getElementById('lancamentoContaId').value = this.contaSelecionadaLancamento.id;
        document.getElementById('lancamentoTipo').value = tipo;

        // Data de hoje (ou amanhã para agendamento)
        const hoje = new Date();
        if (tipo === 'agendamento') {
            hoje.setDate(hoje.getDate() + 1); // Amanhã como padrão para agendamento
        }
        // Usar data local, não UTC (evita pular um dia em fusos negativos)
        document.getElementById('lancamentoData').value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;

        // Grupos específicos
        const tipoAgendamentoGroup = document.getElementById('tipoAgendamentoGroup');
        const recorrenciaGroup = document.getElementById('recorrenciaGroup');
        const horaAgendamentoGroup = document.getElementById('horaAgendamentoGroup');
        const tempoAvisoGroup = document.getElementById('tempoAvisoGroup');
        const canaisNotificacaoGroup = document.getElementById('canaisNotificacaoGroup');
        const formaPagamentoAgendamentoGroup = document.getElementById('formaPagamentoAgendamentoGroup');
        const labelData = document.getElementById('labelDataLancamento');

        // Ocultar grupos de agendamento por padrão
        if (tipoAgendamentoGroup) tipoAgendamentoGroup.style.display = 'none';
        if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
        if (horaAgendamentoGroup) horaAgendamentoGroup.style.display = 'none';
        if (tempoAvisoGroup) tempoAvisoGroup.style.display = 'none';
        if (canaisNotificacaoGroup) canaisNotificacaoGroup.style.display = 'none';
        if (formaPagamentoAgendamentoGroup) formaPagamentoAgendamentoGroup.style.display = 'none';
        if (labelData) labelData.textContent = 'Data';

        // Carregar categorias (exceto para transferência)
        const categoriaGroup = document.getElementById('categoriaGroup');
        if (tipo !== 'transferencia') {
            const tipoCat = tipo === 'agendamento' ? 'despesa' : tipo;
            this.preencherCategorias(tipoCat);
            if (categoriaGroup) categoriaGroup.style.display = 'block';
        } else {
            if (categoriaGroup) categoriaGroup.style.display = 'none';
        }

        // Configurar botão e título baseado no tipo
        const btnSalvar = document.getElementById('btnSalvarLancamento');
        const titulo = document.getElementById('modalLancamentoTitulo');
        const headerGradient = document.querySelector('#modalLancamentoOverlay .lk-modal-header-gradient');
        const contaDestinoGroup = document.getElementById('contaDestinoGroup');
        const cartaoCreditoGroup = document.getElementById('cartaoCreditoGroup');

        // Ocultar formas de pagamento por padrão
        const formaPagamentoGroup = document.getElementById('formaPagamentoGroup');
        const formaRecebimentoGroup = document.getElementById('formaRecebimentoGroup');
        if (formaPagamentoGroup) formaPagamentoGroup.style.display = 'none';
        if (formaRecebimentoGroup) formaRecebimentoGroup.style.display = 'none';
        if (cartaoCreditoGroup) cartaoCreditoGroup.classList.remove('active');
        this.resetarFormaPagamento();

        if (tipo === 'receita') {
            titulo.textContent = '💰 Nova Receita';
            btnSalvar.innerHTML = '<i class="fas fa-check"></i> Salvar Receita';
            btnSalvar.className = 'lk-btn lk-btn-primary';
            btnSalvar.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            if (headerGradient) {
                headerGradient.style.setProperty('background', 'linear-gradient(135deg, #28a745 0%, #20c997 100%)', 'important');
            }
            contaDestinoGroup.style.display = 'none';
            // Mostrar forma de recebimento
            if (formaRecebimentoGroup) formaRecebimentoGroup.style.display = 'block';
        } else if (tipo === 'despesa') {
            titulo.textContent = '💸 Nova Despesa';
            btnSalvar.innerHTML = '<i class="fas fa-check"></i> Salvar Despesa';
            btnSalvar.className = 'lk-btn lk-btn-primary';
            btnSalvar.style.background = 'linear-gradient(135deg, #dc3545, #e74c3c)';
            if (headerGradient) {
                headerGradient.style.setProperty('background', 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)', 'important');
            }
            contaDestinoGroup.style.display = 'none';
            // Mostrar forma de pagamento
            if (formaPagamentoGroup) formaPagamentoGroup.style.display = 'block';

            // Carregar cartões de crédito
            this.carregarCartoesCredito();
        } else if (tipo === 'transferencia') {
            titulo.textContent = '🔄 Nova Transferência';
            btnSalvar.innerHTML = '<i class="fas fa-check"></i> Salvar Transferência';
            btnSalvar.className = 'lk-btn lk-btn-primary';
            btnSalvar.style.background = 'linear-gradient(135deg, #17a2b8, #3498db)';
            if (headerGradient) {
                headerGradient.style.setProperty('background', 'linear-gradient(135deg, #17a2b8 0%, #3498db 100%)', 'important');
            }
            contaDestinoGroup.style.display = 'block';
            cartaoCreditoGroup.style.display = 'none';

            // Preencher select de contas destino
            this.preencherContasDestino();
        } else if (tipo === 'agendamento') {
            titulo.textContent = '📅 Novo Agendamento';
            btnSalvar.innerHTML = '<i class="fas fa-calendar-check"></i> Agendar';
            btnSalvar.className = 'lk-btn lk-btn-primary';
            btnSalvar.style.background = 'linear-gradient(135deg, #e67e22, #d35400)';
            if (headerGradient) {
                headerGradient.style.setProperty('background', 'linear-gradient(135deg, #e67e22 0%, #d35400 100%)', 'important');
            }
            contaDestinoGroup.style.display = 'none';
            cartaoCreditoGroup.style.display = 'none';

            // Mostrar campos específicos de agendamento
            if (tipoAgendamentoGroup) tipoAgendamentoGroup.style.display = 'block';
            if (recorrenciaGroup) recorrenciaGroup.style.display = 'block';
            if (labelData) labelData.textContent = 'Data do Agendamento';

            // Mostrar campo de hora
            const horaAgendamentoGroup = document.getElementById('horaAgendamentoGroup');
            if (horaAgendamentoGroup) horaAgendamentoGroup.style.display = 'block';

            // Mostrar campo de tempo de aviso
            const tempoAvisoGroup = document.getElementById('tempoAvisoGroup');
            if (tempoAvisoGroup) tempoAvisoGroup.style.display = 'block';

            // Mostrar canais de notificação
            const canaisNotificacaoGroup = document.getElementById('canaisNotificacaoGroup');
            if (canaisNotificacaoGroup) canaisNotificacaoGroup.style.display = 'block';

            // Mostrar forma de pagamento para agendamento
            const formaPagamentoAgendamentoGroup = document.getElementById('formaPagamentoAgendamentoGroup');
            if (formaPagamentoAgendamentoGroup) formaPagamentoAgendamentoGroup.style.display = 'block';

            // Configurar evento de recorrência
            this.configurarEventosRecorrencia();
        }

        // Focar no primeiro campo
        setTimeout(() => {
            document.getElementById('lancamentoDescricao')?.focus();
        }, 100);
    }

    /**
     * Configurar eventos de recorrência
     * Agora a recorrência é sempre indefinida, não precisa de lógica adicional
     */
    configurarEventosRecorrencia() {
        // Recorrência agora repete para sempre até ser cancelada
        // Não precisa mais do campo "quantas vezes repetir"
    }

    /**
     * Selecionar tipo de agendamento (receita/despesa)
     */
    selecionarTipoAgendamento(tipo) {
        const btnReceita = document.querySelector('#tipoAgendamentoGroup .lk-btn-tipo-receita');
        const btnDespesa = document.querySelector('#tipoAgendamentoGroup .lk-btn-tipo-despesa');
        const inputTipo = document.getElementById('lancamentoTipoAgendamento');

        if (tipo === 'receita') {
            btnReceita?.classList.add('active');
            btnDespesa?.classList.remove('active');
            this.preencherCategorias('receita');
        } else {
            btnDespesa?.classList.add('active');
            btnReceita?.classList.remove('active');
            this.preencherCategorias('despesa');
        }

        if (inputTipo) inputTipo.value = tipo;
    }

    /**
     * Resetar seleção de forma de pagamento/recebimento
     */
    resetarFormaPagamento() {
        // Limpar seleção dos botões de pagamento
        document.querySelectorAll('#formaPagamentoGrid .lk-forma-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('#formaRecebimentoGrid .lk-forma-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Limpar inputs hidden
        const formaPagInput = document.getElementById('formaPagamento');
        if (formaPagInput) formaPagInput.value = '';
        const formaRecInput = document.getElementById('formaRecebimento');
        if (formaRecInput) formaRecInput.value = '';

        // Ocultar seleção de cartão
        const cartaoGroup = document.getElementById('cartaoCreditoGroup');
        if (cartaoGroup) cartaoGroup.classList.remove('active');

        // Ocultar parcelamento
        const parcelamentoGroup = document.getElementById('parcelamentoGroup');
        if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
        const numParcelasGroup = document.getElementById('numeroParcelasGroup');
        if (numParcelasGroup) numParcelasGroup.style.display = 'none';
    }

    /**
     * Selecionar forma de pagamento (despesas)
     */
    selecionarFormaPagamento(forma) {
        // Atualizar visual
        document.querySelectorAll('#formaPagamentoGrid .lk-forma-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        const btnSelecionado = document.querySelector(`#formaPagamentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        // Atualizar input hidden
        const formaPagInput = document.getElementById('formaPagamento');
        if (formaPagInput) formaPagInput.value = forma;

        // Mostrar/ocultar seleção de cartão
        const cartaoGroup = document.getElementById('cartaoCreditoGroup');
        const parcelamentoGroup = document.getElementById('parcelamentoGroup');

        if (forma === 'cartao_credito') {
            if (cartaoGroup) {
                cartaoGroup.classList.add('active');
                cartaoGroup.style.display = 'block';
            }
            // Carregar cartões disponíveis
            this.carregarCartoesCredito();
            // Verificar se tem cartão selecionado para mostrar parcelamento
            const cartaoSelect = document.getElementById('lancamentoCartaoCredito');
            if (cartaoSelect && cartaoSelect.value) {
                if (parcelamentoGroup) parcelamentoGroup.style.display = 'block';
            }
        } else {
            if (cartaoGroup) {
                cartaoGroup.classList.remove('active');
                cartaoGroup.style.display = 'none';
            }
            if (parcelamentoGroup) parcelamentoGroup.style.display = 'none';
            const numParcelasGroup = document.getElementById('numeroParcelasGroup');
            if (numParcelasGroup) numParcelasGroup.style.display = 'none';
            // Limpar seleção de cartão
            const cartaoSelect = document.getElementById('lancamentoCartaoCredito');
            if (cartaoSelect) cartaoSelect.value = '';
        }
    }

    /**
     * Selecionar forma de recebimento (receitas)
     */
    selecionarFormaRecebimento(forma) {
        // Atualizar visual
        document.querySelectorAll('#formaRecebimentoGrid .lk-forma-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        const btnSelecionado = document.querySelector(`#formaRecebimentoGrid .lk-forma-btn[data-forma="${forma}"]`);
        if (btnSelecionado) btnSelecionado.classList.add('active');

        // Atualizar input hidden
        const formaRecInput = document.getElementById('formaRecebimento');
        if (formaRecInput) formaRecInput.value = forma;

        // Guardar se é estorno
        this.isEstornoCartao = (forma === 'estorno_cartao');

        // Se for estorno de cartão, mostrar seleção de cartão
        const cartaoGroup = document.getElementById('cartaoCreditoGroup');
        const faturaEstornoGroup = document.getElementById('faturaEstornoGroup');

        if (forma === 'estorno_cartao') {
            if (cartaoGroup) {
                cartaoGroup.classList.add('active');
                cartaoGroup.style.display = 'block';
            }
            this.carregarCartoesCredito();
        } else {
            if (cartaoGroup) {
                cartaoGroup.classList.remove('active');
                cartaoGroup.style.display = 'none';
            }
            if (faturaEstornoGroup) {
                faturaEstornoGroup.style.display = 'none';
            }
            const cartaoSelect = document.getElementById('lancamentoCartaoCredito');
            if (cartaoSelect) cartaoSelect.value = '';
        }
    }

    /**
     * Callback quando o cartão é alterado
     */
    onCartaoChange() {
        const cartaoSelect = document.getElementById('lancamentoCartaoCredito');
        const cartaoId = cartaoSelect?.value;
        const faturaEstornoGroup = document.getElementById('faturaEstornoGroup');


        if (this.isEstornoCartao && cartaoId) {
            // Carregar faturas do cartão selecionado
            this.carregarFaturasCartao(cartaoId);
            if (faturaEstornoGroup) {
                faturaEstornoGroup.style.display = 'block';
            }
        } else {
            if (faturaEstornoGroup) {
                faturaEstornoGroup.style.display = 'none';
            }
        }
    }

    /**
     * Carregar faturas disponíveis de um cartão
     */
    async carregarFaturasCartao(cartaoId) {
        const select = document.getElementById('lancamentoFaturaEstorno');
        if (!select) {
            console.error('[ESTORNO] Select lancamentoFaturaEstorno não encontrado');
            return;
        }

        // Gerar lista de meses diretamente (sem depender da API)
        const hoje = new Date();
        const mesAtual = hoje.getMonth() + 1;
        const anoAtual = hoje.getFullYear();

        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        select.innerHTML = '';

        // Gerar opções: mês atual e próximos 5 meses
        for (let i = 0; i < 6; i++) {
            let mes = mesAtual + i;
            let ano = anoAtual;
            if (mes > 12) {
                mes -= 12;
                ano++;
            }

            const option = document.createElement('option');
            option.value = `${ano}-${String(mes).padStart(2, '0')}`;
            option.textContent = i === 0
                ? `${meses[mes - 1]} / ${ano} (atual)`
                : `${meses[mes - 1]} / ${ano}`;

            // Marcar fatura atual como selecionada
            if (i === 0) {
                option.selected = true;
            }

            select.appendChild(option);
        }

        // Adicionar meses anteriores (últimos 3)
        for (let i = 1; i <= 3; i++) {
            let mes = mesAtual - i;
            let ano = anoAtual;
            if (mes < 1) {
                mes += 12;
                ano--;
            }

            const option = document.createElement('option');
            option.value = `${ano}-${String(mes).padStart(2, '0')}`;
            option.textContent = `${meses[mes - 1]} / ${ano} (anterior)`;
            select.appendChild(option);
        }
    }

    /**
     * Preencher select de contas destino (exceto a origem)
     */
    preencherContasDestino() {
        const select = document.getElementById('lancamentoContaDestino');
        const contaOrigemId = this.contaSelecionadaLancamento.id;


        select.innerHTML = '<option value="">Selecione a conta de destino</option>';

        let contasAdicionadas = 0;
        this.contas.forEach(conta => {
            if (conta.id != contaOrigemId) {
                const option = document.createElement('option');
                option.value = conta.id;
                option.textContent = conta.nome;
                select.appendChild(option);
                contasAdicionadas++;
            }
        });

    }

    /**
     * Carregar cartões de crédito no select
     */
    async carregarCartoesCredito() {
        const select = document.getElementById('lancamentoCartaoCredito');
        if (!select) return;

        try {
            const baseUrl = this.getBaseUrl();
            const url = `${baseUrl}api/cartoes`;

            const response = await fetch(url);
            if (!response.ok) throw new Error('Erro ao carregar cartões');

            const cartoes = await response.json();

            select.innerHTML = '<option value="">Não usar cartão (débito na conta)</option>';

            cartoes.forEach(cartao => {
                const option = document.createElement('option');
                option.value = cartao.id;
                option.textContent = `${cartao.nome_cartao} •••• ${cartao.ultimos_digitos}`;
                option.dataset.diaVencimento = cartao.dia_vencimento;
                select.appendChild(option);
            });

            // Adicionar listener para mudança
            select.addEventListener('change', () => this.aoSelecionarCartao());

        } catch (error) {
            console.error('Erro ao carregar cartões:', error);
        }
    }

    /**
     * Ao selecionar cartão de crédito
     */
    aoSelecionarCartao() {
        const selectCartao = document.getElementById('lancamentoCartaoCredito');
        const parcelamentoGroup = document.getElementById('parcelamentoGroup');

        if (selectCartao.value) {
            // Mostrar opção de parcelamento
            parcelamentoGroup.style.display = 'block';

            // Adicionar listener no checkbox
            const checkboxParcelado = document.getElementById('lancamentoParcelado');
            if (checkboxParcelado && !checkboxParcelado.dataset.listenerAdded) {
                checkboxParcelado.addEventListener('change', () => this.aoMarcarParcelado());
                checkboxParcelado.dataset.listenerAdded = 'true';
            }
        } else {
            // Ocultar parcelamento
            parcelamentoGroup.style.display = 'none';
            document.getElementById('numeroParcelasGroup').style.display = 'none';
            document.getElementById('lancamentoParcelado').checked = false;
        }
    }

    /**
     * Ao marcar/desmarcar parcelado
     */
    aoMarcarParcelado() {
        const checkbox = document.getElementById('lancamentoParcelado');
        const numeroParcelasGroup = document.getElementById('numeroParcelasGroup');

        if (checkbox.checked) {
            numeroParcelasGroup.style.display = 'block';

            // Adicionar listeners para calcular preview
            const inputValor = document.getElementById('lancamentoValor');
            const inputParcelas = document.getElementById('lancamentoTotalParcelas');

            if (inputValor && !inputValor.dataset.parcelaListenerAdded) {
                inputValor.addEventListener('input', () => this.calcularPreviewParcelas());
                inputValor.dataset.parcelaListenerAdded = 'true';
            }

            if (inputParcelas && !inputParcelas.dataset.listenerAdded) {
                inputParcelas.addEventListener('input', () => this.calcularPreviewParcelas());
                inputParcelas.dataset.listenerAdded = 'true';
            }

            // Calcular imediatamente
            this.calcularPreviewParcelas();
        } else {
            numeroParcelasGroup.style.display = 'none';
            const preview = document.getElementById('parcelamentoPreview');
            if (preview) {
                preview.style.display = 'none';
            }
        }
    }

    /**
     * Calcular e exibir preview das parcelas
     */
    calcularPreviewParcelas() {
        const valorInput = document.getElementById('lancamentoValor');
        const parcelasInput = document.getElementById('lancamentoTotalParcelas');
        const preview = document.getElementById('parcelamentoPreview');

        if (!valorInput || !parcelasInput || !preview) return;

        const valorStr = valorInput.value || '0,00';
        const valor = this.parseMoneyInput(valorStr);
        const parcelas = parseInt(parcelasInput.value) || 2;

        if (valor <= 0 || parcelas < 2) {
            preview.style.display = 'none';
            return;
        }

        const valorParcela = valor / parcelas;
        const selectCartao = document.getElementById('lancamentoCartaoCredito');
        const selectedOption = selectCartao.options[selectCartao.selectedIndex];
        const diaVencimento = selectedOption.dataset.diaVencimento || '10';

        preview.style.display = 'block';
        preview.innerHTML = `
            <div class="lk-parcelamento-preview-title">Preview do Parcelamento</div>
            <div class="lk-parcelamento-valor">${parcelas}x de ${this.formatCurrency(valorParcela)}</div>
            <div class="lk-parcelamento-detalhes">
                <span><strong>Valor total:</strong> ${this.formatCurrency(valor)}</span>
                <span><strong>Vencimento:</strong> Todo dia ${diaVencimento}</span>
                <span><strong>Primeira parcela:</strong> ${this.calcularProximaFatura(diaVencimento)}</span>
            </div>
        `;
    }

    /**
     * Calcular data da próxima fatura
     */
    calcularProximaFatura(diaVencimento) {
        const hoje = new Date();
        const dia = parseInt(diaVencimento);
        const mesAtual = hoje.getMonth();
        const anoAtual = hoje.getFullYear();

        let proximaFatura = new Date(anoAtual, mesAtual, dia);

        // Se já passou o dia neste mês, próxima fatura é mês que vem
        if (proximaFatura < hoje) {
            proximaFatura = new Date(anoAtual, mesAtual + 1, dia);
        }

        return proximaFatura.toLocaleDateString('pt-BR');
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


        try {
            // Se as categorias já foram carregadas, usar cache
            if (!this.categorias) {
                const baseUrl = this.getBaseUrl();
                const url = `${baseUrl}api/categorias`;

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();


                // A resposta vem como { status: 'success', data: [...] }
                if (result.status === 'success' && result.data) {
                    this.categorias = result.data;
                } else if (result.success && result.data) {
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

            // Preencher select
            select.innerHTML = '<option value="">Selecione a categoria (opcional)</option>';

            categoriasFiltradas.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.nome;
                select.appendChild(option);
            });

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

        // Ocultar campos específicos
        document.getElementById('cartaoCreditoGroup').style.display = 'none';
        document.getElementById('parcelamentoGroup').style.display = 'none';
        document.getElementById('numeroParcelasGroup').style.display = 'none';
        document.getElementById('contaDestinoGroup').style.display = 'none';

        // Ocultar campos de agendamento
        const tipoAgendamentoGroup = document.getElementById('tipoAgendamentoGroup');
        const recorrenciaGroup = document.getElementById('recorrenciaGroup');
        const labelData = document.getElementById('labelDataLancamento');

        if (tipoAgendamentoGroup) tipoAgendamentoGroup.style.display = 'none';
        if (recorrenciaGroup) recorrenciaGroup.style.display = 'none';
        if (labelData) labelData.textContent = 'Data';

        // Restaurar título
        document.getElementById('modalLancamentoTitulo').textContent = 'Nova Movimentação';

        // Restaurar cor laranja do header
        const headerGradient = document.querySelector('#modalLancamentoOverlay .lk-modal-header-gradient');
        if (headerGradient) {
            headerGradient.style.removeProperty('background');
        }

        // Restaurar botão salvar original
        const btnSalvar = document.getElementById('btnSalvarLancamento');
        if (btnSalvar) {
            btnSalvar.className = 'lk-btn lk-btn-primary';
            btnSalvar.style.removeProperty('background');
            btnSalvar.innerHTML = '<i class="fas fa-check"></i> Salvar Lançamento';
        }
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
                await Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Selecione a conta de destino',
                    customClass: { container: 'swal-above-modal' }
                });
                throw new Error('Conta destino obrigatória para transferências');
            }

            // Validação extra: garantir que as contas são diferentes
            if (tipo === 'transferencia' && String(contaId) === String(contaDestinoId)) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Conta de origem e destino devem ser diferentes',
                    customClass: { container: 'swal-above-modal' }
                });
                throw new Error('Selecione contas de origem e destino diferentes.');
            }

            // Converter valor formatado para float
            const valor = this.parseMoneyInput(valorFormatado);

            if (valor <= 0) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Informe um valor válido',
                    customClass: { container: 'swal-above-modal' }
                });
                throw new Error('Valor inválido');
            }

            const cartaoCreditoId = formData.get('cartao_credito_id') || null;
            const ehParcelado = formData.get('eh_parcelado') === 'on' || formData.get('eh_parcelado') === true;
            const totalParcelas = formData.get('total_parcelas') ? parseInt(formData.get('total_parcelas')) : null;

            // Validar limite do cartão de crédito se houver
            if (cartaoCreditoId && tipo === 'despesa' && this.cartoes && Array.isArray(this.cartoes)) {
                const cartao = this.cartoes.find(c => c.id == cartaoCreditoId);
                if (cartao) {
                    const limiteDisponivel = parseFloat(cartao.limite_disponivel || 0);
                    if (valor > limiteDisponivel) {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Limite Insuficiente',
                            html: `
                                <p>O valor da compra (${this.formatCurrency(valor)}) excede o limite disponível do cartão.</p>
                                <p><strong>Limite disponível:</strong> ${this.formatCurrency(limiteDisponivel)}</p>
                            `,
                            confirmButtonText: 'Entendi',
                            customClass: { container: 'swal-above-modal' }
                        });
                        throw new Error('Limite do cartão insuficiente');
                    }
                }
            }

            // Coletar forma de pagamento/recebimento
            let formaPagamento = null;
            let faturaEstornoMesAno = null;

            if (tipo === 'receita') {
                const formaRecEl = document.getElementById('formaRecebimento');
                formaPagamento = formaRecEl?.value || null;

                // Se for estorno de cartão, pegar o mês/ano da fatura
                if (formaPagamento === 'estorno_cartao') {
                    const faturaEstornoEl = document.getElementById('lancamentoFaturaEstorno');
                    faturaEstornoMesAno = faturaEstornoEl?.value || null;
                }
            } else if (tipo === 'despesa') {
                const formaPagEl = document.getElementById('formaPagamento');
                formaPagamento = formaPagEl?.value || null;
            }

            const data = {
                conta_id: contaId,
                tipo: tipo,
                descricao: formData.get('descricao'),
                valor: valor,
                data: formData.get('data'),
                categoria_id: formData.get('categoria_id') || null,
                observacao: formData.get('observacoes') || null,
                forma_pagamento: formaPagamento,
                fatura_mes_ano: faturaEstornoMesAno,
                // Campos de cartão de crédito
                cartao_credito_id: cartaoCreditoId,
                eh_parcelado: ehParcelado,
                total_parcelas: totalParcelas,
            };

            let apiUrl = `${this.baseUrl}/lancamentos`;
            let requestData = data;

            // Se for transferência, usar endpoint específico
            if (tipo === 'transferencia') {
                apiUrl = `${this.baseUrl}/transfers`;
                requestData = {
                    conta_id: contaId,
                    conta_id_destino: contaDestinoId,
                    valor: valor,
                    data: formData.get('data'),
                    descricao: formData.get('descricao'),
                    observacao: formData.get('observacoes') || null,
                };
            }
            // Se for AGENDAMENTO, usar endpoint específico de agendamentos
            else if (tipo === 'agendamento') {
                apiUrl = `${this.baseUrl}/agendamentos`;
                const tipoAgendamento = formData.get('tipo_agendamento') || 'despesa';
                const recorrencia = formData.get('recorrencia') || null;
                const repeticoes = formData.get('numero_repeticoes') || null;

                // Montar data com hora
                let dataPagamento = formData.get('data');
                const hora = formData.get('hora') || '12:00';
                if (dataPagamento && !dataPagamento.includes(' ') && !dataPagamento.includes('T')) {
                    dataPagamento = dataPagamento + ' ' + hora + ':00';
                }

                // Calcular recorrencia_fim se tiver repetições
                let recorrenciaFim = null;
                if (recorrencia && repeticoes && parseInt(repeticoes) > 0) {
                    recorrenciaFim = this.calcularRecorrenciaFim(dataPagamento, recorrencia, parseInt(repeticoes));
                }

                // Tempo de aviso (minutos -> segundos)
                const tempoAvisoMinutos = parseInt(formData.get('tempo_aviso') || '0');
                const lembrarAntesSegundos = tempoAvisoMinutos * 60;

                // Canais de notificação
                const canalInapp = document.getElementById('lancamentoCanalInapp')?.checked ? '1' : '0';
                const canalEmail = document.getElementById('lancamentoCanalEmail')?.checked ? '1' : '0';

                // Forma de pagamento para agendamento
                const formaPagamentoAg = document.getElementById('lancamentoFormaPagamentoAg')?.value || null;

                requestData = {
                    titulo: formData.get('descricao'),
                    tipo: tipoAgendamento,
                    valor: valor,
                    valor_centavos: Math.round(valor * 100),
                    data_pagamento: dataPagamento,
                    categoria_id: formData.get('categoria_id') || null,
                    conta_id: contaId,
                    descricao: formData.get('observacoes') || null,
                    recorrente: recorrencia ? '1' : '0',
                    recorrencia_freq: recorrencia || null,
                    recorrencia_intervalo: recorrencia ? 1 : null,
                    recorrencia_fim: recorrenciaFim,
                    lembrar_antes_segundos: lembrarAntesSegundos,
                    canal_inapp: canalInapp,
                    canal_email: canalEmail,
                    forma_pagamento: formaPagamentoAg
                };
            }
            // Se for PARCELAMENTO SEM CARTÃO (conta bancária), usar endpoint de parcelamentos
            else if (ehParcelado && totalParcelas && totalParcelas > 1 && !cartaoCreditoId) {
                apiUrl = `${this.baseUrl}/parcelamentos`;
                requestData = {
                    descricao: formData.get('descricao'),
                    valor_total: valor,
                    numero_parcelas: totalParcelas,
                    categoria_id: formData.get('categoria_id') || null,
                    conta_id: contaId,
                    tipo: tipo,
                    data_criacao: formData.get('data'),
                };
            }
            // Se tem CARTÃO, sempre usar endpoint de lancamentos (ele detecta o cartao_credito_id)
            // Isso vale para cartão à vista ou parcelado
            // Enviar para API
            const csrfToken = await this.getCSRFToken();
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Erro ao criar lançamento');
            }

            const result = await response.json();

            // Fechar modal primeiro
            this.closeLancamentoModal();

            // Exibir Sweet Alert de sucesso
            const tiposTexto = {
                'receita': 'Receita',
                'despesa': 'Despesa',
                'transferencia': 'Transferência',
                'agendamento': 'Agendamento'
            };
            const tipoTexto = tiposTexto[tipo] || 'Lançamento';
            const mensagem = tipo === 'agendamento' ? 'agendado' : 'criada';
            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                html: `<strong>${tipoTexto}</strong> ${mensagem} com sucesso!`,
                timer: 2000,
                showConfirmButton: false,
                toast: false,
                position: 'center'
            });

            // Exibir dados de gamificação se disponíveis
            if (result.data?.gamification) {
                try {
                    const gamif = result.data.gamification;

                    // Verificar conquistas desbloqueadas (suporta ambos formatos)
                    const achievements = gamif.achievements || gamif.new_achievements || [];
                    if (Array.isArray(achievements) && achievements.length > 0) {
                        if (typeof window.notifyMultipleAchievements === 'function') {
                            window.notifyMultipleAchievements(achievements);
                        } else {
                            // Fallback para notificação individual
                            achievements.forEach(ach => {
                                try {
                                    if (!ach || typeof ach !== 'object') {
                                        console.warn('Conquista inválida:', ach);
                                        return;
                                    }
                                    if (typeof window.notifyAchievementUnlocked === 'function') {
                                        window.notifyAchievementUnlocked(ach);
                                    } else {
                                        this.showNotification(`🏆 ${ach.name || 'Conquista'} desbloqueada!`, 'success');
                                    }
                                } catch (error) {
                                    console.error('Erro ao exibir conquista:', error, ach);
                                }
                            });
                        }
                    }

                    // Processar pontos se houver
                    const points = gamif.points || gamif;
                    if (points.points_gained > 0) {
                        // Pontos ganhos
                    }

                    if (gamif.level_up) {
                        try {
                            // Exibir modal grande de level up
                            if (typeof window.notifyLevelUp === 'function') {
                                window.notifyLevelUp(gamif.level);
                            } else {
                                // Fallback para notificação simples
                                this.showNotification(`🎉 Subiu para o Nível ${gamif.level}!`, 'success');
                            }
                        } catch (error) {
                            console.error('Erro ao exibir level up:', error);
                        }
                    }
                } catch (error) {
                    console.error('Erro ao processar gamificação:', error, result.data.gamification);
                }
            }

            // Recarregar contas para atualizar saldo
            await this.loadContas();

            // Atualizar dashboard se estiver disponível
            if (typeof window.refreshDashboard === 'function') {
                window.refreshDashboard();
            } else if (window.LK?.refreshDashboard) {
                window.LK.refreshDashboard();
            }

            // Disparar eventos customizados para outros componentes
            document.dispatchEvent(new CustomEvent('lukrato:data-changed'));

            // Disparar evento específico de lançamento criado para onboarding
            if (tipo !== 'agendamento') {
                window.dispatchEvent(new CustomEvent('lancamento-created', { detail: result.data }));
            }

        } catch (error) {
            console.error('❌ Erro ao criar lançamento:', error);

            // Lista de erros que já foram mostrados ao usuário antes
            const errosJaMostrados = [
                'Conta destino obrigatória para transferências',
                'Selecione contas de origem e destino diferentes.',
                'Valor inválido',
                'Limite do cartão insuficiente' // Já foi mostrado na validação
            ];

            // Mostrar erro se não foi mostrado anteriormente
            if (!errosJaMostrados.includes(error.message)) {
                // Para erros de limite do backend, mostrar com destaque
                if (error.message && error.message.toLowerCase().includes('limite')) {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Limite Insuficiente',
                        text: error.message,
                        confirmButtonText: 'Entendi',
                        confirmButtonColor: '#d33',
                        customClass: {
                            container: 'swal-above-modal'
                        }
                    });
                } else {
                    this.showNotification(
                        error.message || 'Erro ao criar lançamento. Tente novamente.',
                        'error'
                    );
                }
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


            const data = {
                nome: formData.get('nome'),
                instituicao_financeira_id: instituicaoId && instituicaoId !== '' && instituicaoId !== '0' ? parseInt(instituicaoId) : null,
                tipo_conta: formData.get('tipo_conta'),
                moeda: formData.get('moeda'),
                saldo_inicial: this.parseMoneyInput(saldoFormatado)
            };



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

        contasManager = new ContasManager();
        window.contasManager = contasManager; // Expor globalmente para debug
    });
} else {
    console.warn('⚠️ ContasManager já foi inicializado. Ignorando segunda chamada.');
}
