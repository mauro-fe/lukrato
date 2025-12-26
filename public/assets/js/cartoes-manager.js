/**
 * Cartões Manager - Sistema Moderno de Gerenciamento de Cartões
 * Otimizado para performance e UX
 */

class CartoesManager {
    constructor() {
        this.cartoes = [];
        this.filteredCartoes = [];
        this.currentView = 'grid';
        this.currentFilter = 'all';
        this.searchTerm = '';
        this.baseUrl = this.getBaseUrl();

        console.log('🚀 CartoesManager inicializado');
        console.log('📍 Base URL:', this.baseUrl);

        this.init();
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
        if (metaToken) return metaToken;

        if (window.LK?.getCSRF) return window.LK.getCSRF();
        if (window.CSRF) return window.CSRF;

        console.warn('⚠️ Nenhum token CSRF encontrado');
        return '';
    }

    /**
     * Obter Base URL
     */
    getBaseUrl() {
        try {
            if (window.BASE_URL) {
                const url = window.BASE_URL.endsWith('/') ? window.BASE_URL : window.BASE_URL + '/';
                console.log('✅ BASE_URL encontrado:', url);
                return url;
            }

            // Fallback: detectar automaticamente
            const path = window.location.pathname;
            const publicIndex = path.indexOf('/public/');

            if (publicIndex !== -1) {
                const base = path.substring(0, publicIndex + 8);
                const url = window.location.origin + base;
                console.log('⚠️ BASE_URL detectado automaticamente:', url);
                return url;
            }

            // Último fallback
            const url = window.location.origin + '/lukrato/public/';
            console.log('⚠️ Usando fallback padrão:', url);
            return url;
        } catch (error) {
            console.error('❌ Erro ao obter BASE_URL:', error);
            return window.location.origin + '/lukrato/public/';
        }
    }

    /**
     * Inicialização
     */
    init() {
        this.setupEventListeners();
        this.loadCartoes();
    }

    /**
     * Setup Event Listeners
     */
    setupEventListeners() {
        // Botão novo cartão
        document.getElementById('btnNovoCartao')?.addEventListener('click', () => {
            this.openModal('create');
        });

        document.getElementById('btnNovoCartaoEmpty')?.addEventListener('click', () => {
            this.openModal('create');
        });

        // Modal close buttons
        const modalOverlay = document.getElementById('modalCartaoOverlay');
        const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');

        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    this.closeModal();
                }
            });
        }

        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => this.closeModal());
        });

        // Event delegation para máscara de limite total
        document.addEventListener('input', (e) => {
            if (e.target && e.target.id === 'limiteTotal') {
                let value = e.target.value;

                // Remove tudo que não é número
                value = value.replace(/[^\d]/g, '');

                // Converte para número (centavos)
                let number = parseInt(value) || 0;

                // Converte centavos para reais e formata
                const reais = number / 100;
                const formatted = reais.toFixed(2)
                    .replace('.', ',')
                    .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                e.target.value = formatted;
            }

            // Validação para últimos 4 dígitos - apenas números
            if (e.target && e.target.id === 'ultimosDigitos') {
                let value = e.target.value;

                // Remove tudo que não é número
                value = value.replace(/\D/g, '');

                // Limita a 4 dígitos
                if (value.length > 4) {
                    value = value.substring(0, 4);
                }

                e.target.value = value;
            }
        });

        // Form submit
        const form = document.getElementById('formCartao');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveCartao();
            });
        }

        // Máscara para dias (fechamento e vencimento) - apenas 2 dígitos
        const diaFechamentoInput = document.getElementById('diaFechamento');
        const diaVencimentoInput = document.getElementById('diaVencimento');

        [diaFechamentoInput, diaVencimentoInput].forEach(input => {
            if (input) {
                input.addEventListener('input', (e) => {
                    // Remove tudo que não é número
                    let value = e.target.value.replace(/\D/g, '');
                    // Limita a 2 dígitos
                    if (value.length > 2) {
                        value = value.substring(0, 2);
                    }
                    // Limita de 1 a 31
                    if (value !== '' && parseInt(value) > 31) {
                        value = '31';
                    }
                    e.target.value = value;
                });
            }
        });

        // Reload
        document.getElementById('btnReload')?.addEventListener('click', () => {
            this.loadCartoes();
        });

        // Search
        const searchInput = document.getElementById('searchCartoes');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.searchTerm = e.target.value.toLowerCase();
                this.filterCartoes();
            }, 300));
        }

        // Filters
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentFilter = e.target.dataset.filter;
                this.filterCartoes();
            });
        });

        // View toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentView = e.target.dataset.view;
                this.updateView();
            });
        });

        // Exportar
        document.getElementById('btnExportar')?.addEventListener('click', () => {
            this.exportarRelatorio();
        });
    }

    /**
     * Carregar cartões do servidor
     */
    async loadCartoes() {
        const grid = document.getElementById('cartoesGrid');
        const emptyState = document.getElementById('emptyState');

        try {
            // Mostrar skeleton
            grid.innerHTML = `
                <div class="card-skeleton"></div>
                <div class="card-skeleton"></div>
                <div class="card-skeleton"></div>
            `;
            emptyState.style.display = 'none';

            const response = await fetch(`${window.BASE_URL}api/cartoes`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Erro ao carregar cartões');
            }

            const data = await response.json();
            this.cartoes = Array.isArray(data) ? data : (data.data || []);

            // Verificar faturas pendentes para cada cartão
            await this.verificarFaturasPendentes();

            this.filteredCartoes = [...this.cartoes];

            if (this.cartoes.length === 0) {
                grid.innerHTML = '';
                emptyState.style.display = 'block';
            } else {
                this.renderCartoes();
                this.updateStats();
            }

        } catch (error) {
            console.error('Erro ao carregar cartões:', error);
            this.showToast('Erro ao carregar cartões', 'error');
            grid.innerHTML = '<p class="error-message">Erro ao carregar cartões. Tente novamente.</p>';
        }
    }

    /**
     * Verificar se cartões têm faturas pendentes
     */
    async verificarFaturasPendentes() {
        const hoje = new Date();
        const mesAtual = hoje.getMonth() + 1;
        const anoAtual = hoje.getFullYear();

        // Verificar para cada cartão se tem fatura pendente no mês atual
        const promises = this.cartoes.map(async (cartao) => {
            try {
                const response = await fetch(`${this.baseUrl}api/cartoes/${cartao.id}/fatura?mes=${mesAtual}&ano=${anoAtual}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const fatura = await response.json();
                    // Se tem parcelas não pagas e total > 0, marca como pendente
                    cartao.temFaturaPendente = fatura.parcelas && fatura.parcelas.length > 0 && fatura.total > 0;
                } else {
                    cartao.temFaturaPendente = false;
                }
            } catch (error) {
                console.warn(`Erro ao verificar fatura do cartão ${cartao.id}:`, error);
                cartao.temFaturaPendente = false;
            }
        });

        await Promise.all(promises);
    }

    /**
     * Filtrar cartões
     */
    filterCartoes() {
        this.filteredCartoes = this.cartoes.filter(cartao => {
            // Filtro de busca
            const matchSearch = !this.searchTerm ||
                cartao.nome_cartao.toLowerCase().includes(this.searchTerm) ||
                cartao.ultimos_digitos?.includes(this.searchTerm);

            // Filtro de bandeira
            const matchFilter = this.currentFilter === 'all' ||
                cartao.bandeira?.toLowerCase() === this.currentFilter;

            return matchSearch && matchFilter;
        });

        this.renderCartoes();
    }

    /**
     * Renderizar cartões
     */
    renderCartoes() {
        const grid = document.getElementById('cartoesGrid');
        const emptyState = document.getElementById('emptyState');

        if (this.filteredCartoes.length === 0) {
            grid.innerHTML = '';
            emptyState.style.display = 'block';
            emptyState.querySelector('h3').textContent =
                this.searchTerm || this.currentFilter !== 'all'
                    ? 'Nenhum cartão encontrado'
                    : 'Nenhum cartão cadastrado';
            return;
        }

        emptyState.style.display = 'none';

        grid.innerHTML = this.filteredCartoes.map(cartao => this.createCardHTML(cartao)).join('');

        // Add event listeners para ações
        this.setupCardActions();
    }

    /**
     * Criar HTML do cartão
     */
    createCardHTML(cartao) {
        const percentualUso = cartao.limite_total > 0
            ? ((cartao.limite_total - cartao.limite_disponivel) / cartao.limite_total * 100).toFixed(1)
            : 0;

        const brandIcon = this.getBrandIcon(cartao.bandeira);
        const limiteUtilizado = cartao.limite_total - cartao.limite_disponivel;

        // Obter cor da instituição
        const corBg = cartao.conta?.instituicao_financeira?.cor_primaria ||
            cartao.instituicao_cor ||
            this.getDefaultColor(cartao.bandeira);

        return `
            <div class="credit-card" data-id="${cartao.id}" data-brand="${cartao.bandeira?.toLowerCase() || 'outros'}" style="background: ${corBg};">
                ${cartao.temFaturaPendente ? `
                    <div class="card-badge-fatura" title="Fatura pendente">
                        <i class="fas fa-exclamation-circle"></i>
                        Fatura Pendente
                    </div>
                ` : ''}
                <div class="card-header">
                    <div class="card-brand">
                        <i class="brand-icon ${brandIcon}"></i>
                        <span class="card-name">${this.escapeHtml(cartao.nome_cartao)}</span>
                    </div>
                    <div class="card-actions">
                        <button class="card-action-btn" onclick="cartoesManager.verFatura(${cartao.id})" title="Ver Fatura">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </button>
                        <button class="card-action-btn" onclick="cartoesManager.editCartao(${cartao.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="card-action-btn" onclick="cartoesManager.deleteCartao(${cartao.id})" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="card-number">
                    •••• •••• •••• ${cartao.ultimos_digitos || '0000'}
                </div>

                <div class="card-footer">
                    <div class="card-holder">
                        <div class="card-label">Vencimento</div>
                        <div class="card-value">Dia ${cartao.dia_vencimento}</div>
                    </div>
                    <div class="card-limit">
                        <div class="card-label">Disponível</div>
                        <div class="card-value">${this.formatMoney(cartao.limite_disponivel)}</div>
                        <div class="limit-bar">
                            <div class="limit-fill" style="width: ${100 - percentualUso}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Atualizar estatísticas
     */
    updateStats() {
        const stats = this.cartoes.reduce((acc, cartao) => {
            acc.total++;
            acc.limiteTotal += parseFloat(cartao.limite_total) || 0;
            acc.limiteDisponivel += parseFloat(cartao.limite_disponivel) || 0;
            return acc;
        }, { total: 0, limiteTotal: 0, limiteDisponivel: 0 });

        stats.limiteUtilizado = stats.limiteTotal - stats.limiteDisponivel;

        document.getElementById('totalCartoes').textContent = stats.total;
        document.getElementById('statLimiteTotal').textContent = this.formatMoney(stats.limiteTotal);
        document.getElementById('limiteDisponivel').textContent = this.formatMoney(stats.limiteDisponivel);
        document.getElementById('limiteUtilizado').textContent = this.formatMoney(stats.limiteUtilizado);

        // Animar números
        this.animateStats();
    }

    /**
     * Animar estatísticas
     */
    animateStats() {
        document.querySelectorAll('.stat-card').forEach((card, index) => {
            card.style.animation = 'none';
            setTimeout(() => {
                card.style.animation = 'fadeIn 0.5s ease forwards';
            }, index * 100);
        });
    }

    /**
     * Atualizar visualização (grid/list)
     */
    updateView() {
        const grid = document.getElementById('cartoesGrid');

        if (this.currentView === 'list') {
            grid.classList.add('list-view');
        } else {
            grid.classList.remove('list-view');
        }
    }

    /**
     * Abrir modal
     */
    async openModal(mode = 'create', cartaoData = null) {
        const overlay = document.getElementById('modalCartaoOverlay');
        const modal = document.getElementById('modalCartao');
        const form = document.getElementById('formCartao');
        const titulo = document.getElementById('modalCartaoTitulo');

        if (!overlay || !modal || !form) return;

        // Resetar formulário
        form.reset();
        document.getElementById('cartaoId').value = '';

        // Carregar contas no select PRIMEIRO
        await this.loadContasSelect();

        if (mode === 'edit' && cartaoData) {
            // Modo edição
            titulo.textContent = 'Editar Cartão de Crédito';
            document.getElementById('cartaoId').value = cartaoData.id;
            document.getElementById('nomeCartao').value = cartaoData.nome_cartao;
            document.getElementById('contaVinculada').value = cartaoData.conta_id;
            document.getElementById('bandeira').value = cartaoData.bandeira;
            document.getElementById('ultimosDigitos').value = cartaoData.ultimos_digitos;

            // Formata o limite total (converte para float primeiro)
            const limiteValue = parseFloat(cartaoData.limite_total || 0);
            const limiteFormatado = limiteValue.toFixed(2)
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            document.getElementById('limiteTotal').value = limiteFormatado;

            document.getElementById('diaFechamento').value = cartaoData.dia_fechamento;
            document.getElementById('diaVencimento').value = cartaoData.dia_vencimento;
        } else {
            // Modo criação
            titulo.textContent = 'Novo Cartão de Crédito';
            document.getElementById('limiteTotal').value = '0,00';
        }

        // Mostrar modal
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Fechar modal
     */
    closeModal() {
        const overlay = document.getElementById('modalCartaoOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    /**
     * Carregar contas no select
     */
    async loadContasSelect() {
        const select = document.getElementById('contaVinculada');
        if (!select) {
            console.error('❌ Select contaVinculada não encontrado!');
            return;
        }

        console.log('🔄 Carregando contas no select...');

        try {
            const url = `${this.baseUrl}api/contas?only_active=0`;
            console.log('📡 URL completa:', url);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            console.log('📥 Response status:', response.status);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Erro HTTP:', response.status, errorText);
                throw new Error('Erro ao carregar contas');
            }

            const data = await response.json();
            console.log('✅ Dados recebidos da API:', data);
            console.log('📊 Tipo de data:', typeof data);
            console.log('📊 É array?', Array.isArray(data));
            console.log('📊 Keys do objeto:', Object.keys(data));
            console.log('📊 JSON completo:', JSON.stringify(data, null, 2));

            // Tentar diferentes estruturas de resposta
            let contas = [];
            if (Array.isArray(data)) {
                contas = data;
            } else if (data.data) {
                contas = Array.isArray(data.data) ? data.data : [];
            } else if (data.contas) {
                contas = Array.isArray(data.contas) ? data.contas : [];
            }

            console.log('📊 Array de contas extraído:', contas);
            console.log('📊 Total de contas:', contas.length);

            if (contas.length === 0) {
                select.innerHTML = '<option value="">Nenhuma conta cadastrada</option>';
                console.warn('⚠️ Nenhuma conta encontrada');
                return;
            }

            const options = contas.map(conta => {
                // Pegar nome da instituição de diferentes estruturas possíveis
                const instituicao = conta.instituicao_financeira?.nome ||
                    conta.instituicao?.nome ||
                    conta.nome ||
                    'Sem instituição';
                const nome = this.escapeHtml(instituicao);
                // Tentar pegar o saldo de diferentes campos possíveis
                const saldoValue = parseFloat(conta.saldo_atual || conta.saldo || conta.saldo_inicial || 0);
                const saldo = this.formatMoney(saldoValue);
                console.log(`  → Conta: ID=${conta.id}, Instituição=${nome}, Saldo=${saldo}`);
                return `<option value="${conta.id}">${nome} - ${saldo}</option>`;
            }).join('');

            select.innerHTML = '<option value="">Selecione a conta</option>' + options;
            console.log('✅ Select preenchido com', contas.length, 'conta(s)');
        } catch (error) {
            console.error('❌ Erro ao carregar contas:', error);
            console.error('Stack:', error.stack);
            select.innerHTML = '<option value="">Erro ao carregar contas</option>';
        }
    }

    /**
     * Salvar cartão
     */
    async saveCartao() {
        const form = document.getElementById('formCartao');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const cartaoId = document.getElementById('cartaoId').value;
        const isEdit = !!cartaoId;

        // Obter token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
            document.querySelector('input[name="csrf_token"]')?.value ||
            '';

        const limiteOriginal = document.getElementById('limiteTotal').value;
        const limiteParsed = this.parseMoney(limiteOriginal);

        const data = {
            nome_cartao: document.getElementById('nomeCartao').value,
            conta_id: document.getElementById('contaVinculada').value,
            bandeira: document.getElementById('bandeira').value,
            ultimos_digitos: document.getElementById('ultimosDigitos').value,
            limite_total: limiteParsed,
            dia_fechamento: document.getElementById('diaFechamento').value || null,
            dia_vencimento: document.getElementById('diaVencimento').value || null,
            csrf_token: csrfToken
        };

        console.log('💾 Salvando cartão:', data);
        console.log('📝 Validações:', {
            nome_cartao: data.nome_cartao,
            conta_id: data.conta_id,
            bandeira: data.bandeira,
            ultimos_digitos: data.ultimos_digitos,
            limite_total: data.limite_total,
            limite_total_type: typeof data.limite_total,
            limite_total_original: limiteOriginal,
            csrf_token: csrfToken ? '✅ Presente' : '❌ Ausente'
        });

        try {
            const url = isEdit
                ? `${window.BASE_URL}api/cartoes/${cartaoId}`
                : `${window.BASE_URL}api/cartoes`;

            console.log('📡 URL:', url);
            console.log('📤 Método:', isEdit ? 'PUT' : 'POST');

            const response = await fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            console.log('📥 Response status:', response.status);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Erro HTTP:', response.status);
                console.error('📄 Resposta completa:', errorText);

                let error;
                try {
                    error = JSON.parse(errorText);
                } catch (e) {
                    error = { message: errorText };
                }

                // Se for erro de CSRF, recarregar a página
                if (error.errors?.csrf_expired) {
                    this.showToast('error', 'Sessão expirada. Recarregando página...');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                throw new Error(error.message || error.error || 'Erro ao salvar cartão');
            }

            this.showToast('success', isEdit ? 'Cartão atualizado com sucesso!' : 'Cartão criado com sucesso!');
            this.closeModal();
            this.loadCartoes();
        } catch (error) {
            console.error('❌ Erro ao salvar cartão:', error);
            console.error('Stack:', error.stack);
            this.showToast('error', error.message || 'Erro ao salvar cartão');
        }
    }

    /**
     * Editar cartão
     */
    async editCartao(id) {
        const cartao = this.cartoes.find(c => c.id === id);
        if (cartao) {
            this.openModal('edit', cartao);
        }
    }

    /**
     * Deletar cartão
     */
    async deleteCartao(id) {
        const cartao = this.cartoes.find(c => c.id === id);
        if (!cartao) return;

        // Confirmação inicial
        const confirmacao = await this.showConfirmDialog(
            'Excluir Cartão',
            `Tem certeza que deseja excluir o cartão "${cartao.nome_cartao}"?`,
            'Excluir'
        );

        if (!confirmacao) return;

        try {
            const csrfToken = await this.getCSRFToken();

            // Primeira tentativa de exclusão
            const response = await fetch(`${window.BASE_URL}api/cartoes/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok) {
                // Se retornar 422, é porque tem lançamentos vinculados
                if (response.status === 422 && (result.requires_confirmation || result.status === 'confirm_delete')) {
                    const total = result.total_lancamentos || 0;
                    const mensagem = total > 0
                        ? `Este cartão possui ${total} lançamento(s) vinculado(s).\n\nAo excluir o cartão, todos os lançamentos também serão excluídos.\n\nDeseja realmente continuar?`
                        : `${result.message}\n\nDeseja realmente excluir este cartão?`;

                    const confirmarExclusao = await this.showConfirmDialog(
                        '⚠️ Atenção',
                        mensagem,
                        'Sim, excluir tudo'
                    );

                    if (!confirmarExclusao) return;

                    // Segunda tentativa com force=true
                    const forceResponse = await fetch(`${window.BASE_URL}api/cartoes/${id}?force=1`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-Token': csrfToken
                        },
                        credentials: 'same-origin'
                    });

                    if (!forceResponse.ok) {
                        const forceResult = await forceResponse.json().catch(() => ({}));
                        throw new Error(forceResult.message || 'Erro ao excluir cartão');
                    }

                    this.showToast('success', 'Cartão excluído com sucesso!');
                    this.loadCartoes();
                    return;
                }

                throw new Error(result.message || 'Erro ao excluir cartão');
            }

            this.showToast('success', 'Cartão excluído com sucesso!');
            this.loadCartoes();

        } catch (error) {
            console.error('Erro ao excluir:', error);
            this.showToast('error', error.message || 'Erro ao excluir cartão');
        }
    }

    /**
     * Exportar relatório
     */
    async exportarRelatorio() {
        try {
            const data = this.filteredCartoes.map(cartao => ({
                'Nome': cartao.nome_cartao,
                'Bandeira': cartao.bandeira,
                'Final': cartao.ultimos_digitos,
                'Limite Total': this.formatMoney(cartao.limite_total),
                'Limite Disponível': this.formatMoney(cartao.limite_disponivel),
                'Vencimento': `Dia ${cartao.dia_vencimento}`,
                'Fechamento': `Dia ${cartao.dia_fechamento}`
            }));

            // Criar CSV
            const csv = this.convertToCSV(data);
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.setAttribute('href', url);
            link.setAttribute('download', `cartoes_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            this.showToast('Relatório exportado com sucesso', 'success');

        } catch (error) {
            console.error('Erro ao exportar:', error);
            this.showToast('Erro ao exportar relatório', 'error');
        }
    }

    /**
     * Converter para CSV
     */
    convertToCSV(data) {
        if (data.length === 0) return '';

        const headers = Object.keys(data[0]);
        const csvRows = [];

        csvRows.push(headers.join(','));

        for (const row of data) {
            const values = headers.map(header => {
                const escaped = ('' + row[header]).replace(/"/g, '\\"');
                return `"${escaped}"`;
            });
            csvRows.push(values.join(','));
        }

        return csvRows.join('\n');
    }

    /**
     * Setup ações dos cartões
     */
    setupCardActions() {
        document.querySelectorAll('.credit-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.card-action-btn')) {
                    const id = parseInt(card.dataset.id);
                    this.showCardDetails(id);
                }
            });
        });
    }

    /**
     * Mostrar detalhes do cartão
     */
    async showCardDetails(id) {
        const cartao = this.cartoes.find(c => c.id === id);
        if (!cartao) return;

        // Implementar modal de detalhes (futuro)
        console.log('Detalhes do cartão:', cartao);
    }

    /**
     * Obter ícone da bandeira
     */
    getBrandIcon(bandeira) {
        const icons = {
            'visa': 'fab fa-cc-visa',
            'mastercard': 'fab fa-cc-mastercard',
            'elo': 'fas fa-credit-card',
            'amex': 'fab fa-cc-amex',
            'diners': 'fab fa-cc-diners-club',
            'discover': 'fab fa-cc-discover'
        };
        return icons[bandeira?.toLowerCase()] || 'fas fa-credit-card';
    }

    /**
     * Obter cor padrão baseada na bandeira
     */
    getDefaultColor(bandeira) {
        const colors = {
            'visa': 'linear-gradient(135deg, #1A1F71 0%, #2D3A8C 100%)',
            'mastercard': 'linear-gradient(135deg, #EB001B 0%, #F79E1B 100%)',
            'elo': 'linear-gradient(135deg, #FFCB05 0%, #FFE600 100%)',
            'amex': 'linear-gradient(135deg, #006FCF 0%, #0099CC 100%)',
            'diners': 'linear-gradient(135deg, #0079BE 0%, #00558C 100%)',
            'discover': 'linear-gradient(135deg, #FF6000 0%, #FF8500 100%)'
        };
        return colors[bandeira?.toLowerCase()] || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    }

    /**
     * Formatar dinheiro
     */
    formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    }

    /**
     * Formatar dinheiro para input (sem R$)
     */
    formatMoneyInput(value) {
        // Se o value já for uma string formatada, retorna ela
        if (typeof value === 'string' && value.includes(',')) {
            return value;
        }

        // Se for número, converte centavos para reais e formata
        if (typeof value === 'number') {
            const reais = value / 100;
            return reais.toFixed(2)
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Fallback: formata com Intl
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value || 0);
    }

    /**
     * Configurar máscara de dinheiro para limite do cartão
     */
    setupLimiteMoneyMask() {
        const limiteInput = document.getElementById('limiteTotal');
        if (!limiteInput) {
            console.error('❌ Campo limiteTotal NÃO encontrado!');
            return;
        }

        console.log('✅ Campo limiteTotal encontrado:', limiteInput);

        // Handler da máscara
        limiteInput.addEventListener('input', function (e) {
            let value = e.target.value;

            console.log('🔍 Input detectado:', value);

            // Remove tudo que não é número
            value = value.replace(/[^\d]/g, '');

            // Converte para número (centavos)
            let number = parseInt(value) || 0;

            // Converte centavos para reais e formata
            const reais = number / 100;
            const formatted = reais.toFixed(2)
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            console.log('✅ Valor formatado:', formatted);
            e.target.value = formatted;
        });

        console.log('✅ Máscara de dinheiro aplicada com sucesso!');

        // Formata ao carregar
        limiteInput.value = '0,00';
    }

    /**
     * Parse dinheiro (converter string para float)
     */
    parseMoney(value) {
        if (typeof value === 'number') return value;
        if (!value) return 0;

        // Remove R$, espaços e converte vírgula para ponto
        return parseFloat(
            value.toString()
                .replace(/[R$\s]/g, '')
                .replace(/\./g, '')
                .replace(',', '.')
        ) || 0;
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Debounce helper
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Toast notification
     */
    showToast(type, message) {
        // Usar SweetAlert2 se disponível
        if (window.Swal) {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Sucesso!' : 'Erro!',
                text: message,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            alert(message);
        }
    }

    /**
     * Diálogo de confirmação
     */
    async showConfirmDialog(title, message, confirmText = 'Confirmar') {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            });
            return result.isConfirmed;
        }
        return confirm(`${title}\n\n${message}`);
    }

    /**
     * ===================================
     * FATURA DO CARTÃO
     * ===================================
     */

    /**
     * Ver fatura do cartão
     */
    async verFatura(cartaoId) {
        try {
            // Data atual para carregar fatura do mês
            const hoje = new Date();
            const mes = hoje.getMonth() + 1; // 1-12
            const ano = hoje.getFullYear();

            console.log(`📄 Carregando fatura - Cartão ID: ${cartaoId}, Mês: ${mes}/${ano}`);

            const response = await fetch(`${this.baseUrl}api/cartoes/${cartaoId}/fatura?mes=${mes}&ano=${ano}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Erro ao carregar fatura');
            }

            const fatura = await response.json();
            console.log('✅ Fatura carregada:', fatura);

            this.mostrarModalFatura(fatura);
        } catch (error) {
            console.error('❌ Erro ao carregar fatura:', error);
            this.showToast(error.message || 'Erro ao carregar fatura', 'error');
        }
    }

    /**
     * Mostrar modal da fatura
     */
    mostrarModalFatura(fatura) {
        const modal = this.criarModalFatura(fatura);
        document.body.appendChild(modal);

        // Animar entrada
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);

        // Fechar ao clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.fecharModalFatura(modal);
            }
        });

        // Botão fechar
        modal.querySelector('.btn-fechar-fatura')?.addEventListener('click', () => {
            this.fecharModalFatura(modal);
        });

        // Botão pagar
        modal.querySelector('.btn-pagar-fatura')?.addEventListener('click', () => {
            this.pagarFatura(fatura);
        });
    }

    /**
     * Criar HTML do modal da fatura
     */
    criarModalFatura(fatura) {
        const modal = document.createElement('div');
        modal.className = 'modal-fatura-overlay';
        modal.innerHTML = `
            <div class="modal-fatura-container">
                <div class="modal-fatura-header">
                    <div>
                        <h2>
                            <i class="fas fa-file-invoice-dollar"></i>
                            Fatura ${fatura.cartao.nome} •••• ${fatura.cartao.ultimos_digitos}
                        </h2>
                        <p class="fatura-periodo">${this.getNomeMes(fatura.mes)}/${fatura.ano}</p>
                    </div>
                    <button class="btn-fechar-fatura" title="Fechar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-fatura-body">
                    ${fatura.parcelas.length === 0 ? `
                        <div class="fatura-empty">
                            <i class="fas fa-check-circle"></i>
                            <h3>Nenhuma fatura pendente</h3>
                            <p>Você não tem compras para pagar neste mês!</p>
                        </div>
                    ` : `
                        <div class="fatura-resumo">
                            <div class="fatura-total">
                                <span>Total a Pagar:</span>
                                <strong>${this.formatMoney(fatura.total)}</strong>
                            </div>
                            <div class="fatura-vencimento">
                                <i class="fas fa-calendar-alt"></i>
                                Vencimento: ${this.formatDate(fatura.vencimento)}
                            </div>
                        </div>

                        <div class="fatura-parcelas">
                            <h3>Parcelas desta Fatura</h3>
                            <table class="table-parcelas">
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Parcela</th>
                                        <th>Vencimento</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${fatura.parcelas.map(parcela => `
                                        <tr>
                                            <td>${this.escapeHtml(parcela.descricao)}</td>
                                            <td>${parcela.parcela_atual}/${parcela.total_parcelas}</td>
                                            <td>${this.formatDate(parcela.data_vencimento)}</td>
                                            <td class="valor">${this.formatMoney(parcela.valor)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `}
                </div>

                ${fatura.parcelas.length > 0 ? `
                    <div class="modal-fatura-footer">
                        <button class="btn btn-ghost btn-fechar-fatura">Fechar</button>
                        <button class="btn btn-primary btn-pagar-fatura">
                            <i class="fas fa-check"></i>
                            Pagar Fatura (${this.formatMoney(fatura.total)})
                        </button>
                    </div>
                ` : ''}
            </div>
        `;

        return modal;
    }

    /**
     * Fechar modal da fatura
     */
    fecharModalFatura(modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }

    /**
     * Pagar fatura
     */
    async pagarFatura(fatura) {
        const confirmado = await this.showConfirmDialog(
            'Confirmar Pagamento',
            `Deseja pagar a fatura de ${this.formatMoney(fatura.total)}?\n\nEsta ação criará um lançamento de despesa na conta vinculada e liberará o limite do cartão.`,
            'Sim, Pagar'
        );

        if (!confirmado) return;

        try {
            const csrfToken = await this.getCSRFToken();

            const response = await fetch(`${this.baseUrl}api/cartoes/${fatura.cartao.id}/fatura/pagar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    mes: fatura.mes,
                    ano: fatura.ano
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Erro ao pagar fatura');
            }

            const resultado = await response.json();
            console.log('✅ Fatura paga:', resultado);

            this.showToast(`Fatura paga com sucesso! ${resultado.parcelas_pagas} parcela(s) quitada(s).`, 'success');

            // Fechar modal
            const modal = document.querySelector('.modal-fatura-overlay');
            if (modal) {
                this.fecharModalFatura(modal);
            }

            // Recarregar cartões para atualizar limite
            this.loadCartoes();
        } catch (error) {
            console.error('❌ Erro ao pagar fatura:', error);
            this.showToast(error.message || 'Erro ao pagar fatura', 'error');
        }
    }

    /**
     * Obter nome do mês
     */
    getNomeMes(mes) {
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        return meses[mes - 1] || 'Mês inválido';
    }

    /**
     * Formatar data para exibição
     */
    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString + 'T00:00:00');
        return date.toLocaleDateString('pt-BR');
    }
}
