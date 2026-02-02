/**
 * Cartões Manager - Sistema Moderno de Gerenciamento de Cartões
 * Otimizado para performance e UX
 */

class CartoesManager {
    constructor() {
        this.cartoes = [];
        this.filteredCartoes = [];
        this.alertas = [];
        this.currentView = 'grid';
        this.currentFilter = 'all';
        this.searchTerm = '';
        this.baseUrl = this.getBaseUrl();


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
            // Usar a função global LK.getBase() se disponível
            if (window.LK && typeof window.LK.getBase === 'function') {
                const url = window.LK.getBase();
                return url;
            }

            // Fallback para meta tag
            const meta = document.querySelector('meta[name="base-url"]');
            if (meta?.content) {
                return meta.content;
            }

            if (window.BASE_URL) {
                const url = window.BASE_URL.endsWith('/') ? window.BASE_URL : window.BASE_URL + '/';
                return url;
            }

            // Fallback: detectar automaticamente
            const path = window.location.pathname;
            const publicIndex = path.indexOf('/public/');

            if (publicIndex !== -1) {
                const base = path.substring(0, publicIndex + 8);
                const url = window.location.origin + base;
                return url;
            }

            // Último fallback
            const url = window.location.origin + '/lukrato/public/';
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

            // Verificar faturas pendentes
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
            this.showToast('error', 'Erro ao carregar cartões');
            grid.innerHTML = '<p class="error-message">Erro ao carregar cartões. Tente novamente.</p>';
        }
    }

    /**
     * Verificar se cartões têm faturas pendentes
     */
    async verificarFaturasPendentes() {
        // Temporariamente desabilitado para evitar erros 404 no console
        // TODO: Implementar verificação quando a API estiver pronta
        const hoje = new Date();
        const mesAtual = hoje.getMonth() + 1;
        const anoAtual = hoje.getFullYear();

        // Marcar todos os cartões como sem fatura pendente por padrão
        this.cartoes.forEach(cartao => {
            cartao.temFaturaPendente = false;
        });

        return; // Desabilitado temporariamente

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
                    cartao.temFaturaPendente = fatura.itens && fatura.itens.length > 0 && fatura.total > 0;
                } else if (response.status === 404) {
                    // Fatura não encontrada - normal para cartões sem lançamentos
                    cartao.temFaturaPendente = false;
                } else {
                    cartao.temFaturaPendente = false;
                }
            } catch (error) {
                // Silenciar erro de rede/fetch
                cartao.temFaturaPendente = false;
            }
        });

        await Promise.all(promises);
    }

    /**
     * Carregar alertas de vencimentos e limites baixos
     */
    async carregarAlertas() {
        try {
            const response = await fetch(`${this.baseUrl}api/cartoes/alertas`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                this.alertas = data.alertas || [];
                this.renderAlertas();
            } else {
                console.warn('Erro ao carregar alertas:', response.status);
                this.alertas = [];
            }
        } catch (error) {
            console.warn('Erro ao carregar alertas:', error);
            this.alertas = [];
            // Não mostra erro para o usuário, apenas oculta o container
            const container = document.getElementById('alertasContainer');
            if (container) {
                container.style.display = 'none';
            }
        }
    }

    /**
     * Renderizar alertas na interface
     */
    renderAlertas() {
        const container = document.getElementById('alertasContainer');
        if (!container) return;

        if (this.alertas.length === 0) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        container.innerHTML = `
            <div class="alertas-list">
                ${this.alertas.map(alerta => this.criarAlertaHTML(alerta)).join('')}
            </div>
        `;
    }

    /**
     * Criar HTML para um alerta específico
     */
    criarAlertaHTML(alerta) {
        const icones = {
            vencimento_proximo: 'fa-calendar-times',
            limite_baixo: 'fa-exclamation-triangle'
        };

        const cores = {
            critico: '#e74c3c',
            atencao: '#f39c12'
        };

        let mensagem = '';
        if (alerta.tipo === 'vencimento_proximo') {
            mensagem = `Fatura de <strong>${alerta.nome_cartao}</strong> vence em <strong>${alerta.dias_faltando} dia(s)</strong> - ${this.formatMoney(alerta.valor_fatura)}`;
        } else if (alerta.tipo === 'limite_baixo') {
            mensagem = `Limite de <strong>${alerta.nome_cartao}</strong> em <strong>${alerta.percentual_disponivel.toFixed(1)}%</strong> - ${this.formatMoney(alerta.limite_disponivel)} disponível`;
        }

        return `
            <div class="alerta-item alerta-${alerta.gravidade}" data-tipo="${alerta.tipo}">
                <div class="alerta-icon" style="color: ${cores[alerta.gravidade]}">
                    <i class="fas ${icones[alerta.tipo]}"></i>
                </div>
                <div class="alerta-content">
                    <p>${mensagem}</p>
                </div>
                <button class="alerta-dismiss" onclick="cartoesManager.dismissAlerta(this)" title="Dispensar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }

    /**
     * Dispensar alerta (apenas oculta na UI)
     */
    dismissAlerta(button) {
        const alertaItem = button.closest('.alerta-item');
        if (alertaItem) {
            alertaItem.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(() => {
                alertaItem.remove();
                const container = document.getElementById('alertasContainer');
                if (container && container.querySelectorAll('.alerta-item').length === 0) {
                    container.style.display = 'none';
                }
            }, 300);
        }
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
        // Usar limite calculado (limite_disponivel_real) se disponível, senão usar limite_disponivel
        const limiteDisponivel = cartao.limite_disponivel_real ?? cartao.limite_disponivel ?? 0;
        const limiteUtilizado = cartao.limite_utilizado ?? (cartao.limite_total - limiteDisponivel);

        const percentualUso = cartao.percentual_uso ?? (cartao.limite_total > 0
            ? ((cartao.limite_total - limiteDisponivel) / cartao.limite_total * 100).toFixed(1)
            : 0);

        const brandIcon = this.getBrandIcon(cartao.bandeira);

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
        <img
            src="${brandIcon}"
            alt="${cartao.bandeira}"
            class="brand-logo"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"
        >
        <i class="brand-icon-fallback fas fa-credit-card" style="display: none;" aria-hidden="true"></i>
        <span class="card-name">
            ${this.escapeHtml(cartao.nome_cartao || cartao.nome)}
        </span>
    </div>

    <div class="card-actions">

        <!-- Tooltip de regra de exclusão -->
        <button
            type="button"
            class="lk-info"
            data-lk-tooltip-title="Exclusão de cartões"
            data-lk-tooltip="Para evitar perda de histórico e faturas, cartões só podem ser excluídos após serem arquivados. Arquive o cartão primeiro e depois realize a exclusão."
            aria-label="Ajuda: Exclusão de cartões"
        >
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        </button>

        <button
            class="card-action-btn"
            onclick="cartoesManager.verFatura(${cartao.id})"
            title="Ver Fatura"
        >
            <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
        </button>

        <button
            class="card-action-btn"
            onclick="cartoesManager.editCartao(${cartao.id})"
            title="Editar"
        >
            <i class="fas fa-edit" aria-hidden="true"></i>
        </button>

        <button
            class="card-action-btn"
            onclick="cartoesManager.arquivarCartao(${cartao.id})"
            title="Arquivar"
        >
            <i class="fas fa-archive" aria-hidden="true"></i>
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
                        <div class="card-value">${this.formatMoney(limiteDisponivel)}</div>
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
            const limiteTotal = parseFloat(cartao.limite_total) || 0;
            // Usar limite calculado (limite_disponivel_real) se disponível
            const limiteDisponivel = parseFloat(cartao.limite_disponivel_real ?? cartao.limite_disponivel) || 0;
            const limiteUtilizado = parseFloat(cartao.limite_utilizado) || Math.max(0, limiteTotal - limiteDisponivel);

            acc.total++;
            acc.limiteTotal += limiteTotal;
            acc.limiteDisponivel += limiteDisponivel;
            acc.limiteUtilizado += limiteUtilizado;
            return acc;
        }, { total: 0, limiteTotal: 0, limiteDisponivel: 0, limiteUtilizado: 0 });

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


        try {
            const url = `${this.baseUrl}api/contas?only_active=0&with_balances=1`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });


            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Erro HTTP:', response.status, errorText);
                throw new Error('Erro ao carregar contas');
            }

            const data = await response.json();


            // Tentar diferentes estruturas de resposta
            let contas = [];
            if (Array.isArray(data)) {
                contas = data;
            } else if (data.data) {
                contas = Array.isArray(data.data) ? data.data : [];
            } else if (data.contas) {
                contas = Array.isArray(data.contas) ? data.contas : [];
            }


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
                // Tentar pegar o saldo de diferentes campos possíveis (saldoAtual é o campo retornado com with_balances=1)
                const saldoValue = parseFloat(conta.saldoAtual || conta.saldo_atual || conta.saldo || conta.saldo_inicial || 0);
                const saldo = this.formatMoney(saldoValue);
                return `<option value="${conta.id}">${nome} - ${saldo}</option>`;
            }).join('');

            select.innerHTML = '<option value="">Selecione a conta</option>' + options;
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


        try {
            const url = isEdit
                ? `${window.BASE_URL}api/cartoes/${cartaoId}`
                : `${window.BASE_URL}api/cartoes`;



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

            const result = await response.json();

            // 🎮 GAMIFICAÇÃO: Exibir conquistas se houver
            if (result.gamification?.achievements && Array.isArray(result.gamification.achievements)) {
                if (typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(result.gamification.achievements);
                } else {
                    console.error('❌ notifyMultipleAchievements não está disponível');
                }
            } else {
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
     * Arquivar cartão
     */
    async arquivarCartao(id) {
        const cartao = this.cartoes.find(c => c.id === id);
        if (!cartao) return;

        // Confirmação
        const confirmacao = await this.showConfirmDialog(
            'Arquivar Cartão',
            `Tem certeza que deseja arquivar o cartão "${cartao.nome_cartao}"? Você poderá restaurá-lo depois na página de Cartões Arquivados.`,
            'Arquivar'
        );

        if (!confirmacao) return;

        try {
            const csrfToken = await this.getCSRFToken();

            const response = await fetch(`${window.BASE_URL}api/cartoes/${id}/archive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const result = await response.json().catch(() => ({}));
                throw new Error(result.message || 'Erro ao arquivar cartão');
            }

            this.showToast('success', 'Cartão arquivado com sucesso!');
            this.loadCartoes();

        } catch (error) {
            console.error('Erro ao arquivar:', error);
            this.showToast('error', error.message || 'Erro ao arquivar cartão');
        }
    }

    /**
     * Deletar cartão (método antigo - mantido por compatibilidade)
     * @deprecated Use arquivarCartao() em vez disso
     */
    async deleteCartao(id) {
        // Redireciona para arquivar
        return this.arquivarCartao(id);
    }

    /**
     * Exportar relatório em PDF
     */
    async exportarRelatorio() {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const dataAtual = new Date();
            const mesAno = dataAtual.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });

            // Calcular resumo financeiro
            const limiteTotal = this.filteredCartoes.reduce((sum, c) => sum + parseFloat(c.limite_total || 0), 0);
            const limiteDisponivel = this.filteredCartoes.reduce((sum, c) => sum + parseFloat((c.limite_disponivel_real ?? c.limite_disponivel) || 0), 0);
            const limiteUtilizado = limiteTotal - limiteDisponivel;
            const percentualGeral = limiteTotal > 0 ? (limiteUtilizado / limiteTotal * 100).toFixed(1) : 0;

            // Configurar cores
            const primaryColor = [230, 126, 34]; // Laranja
            const darkColor = [26, 31, 46];
            const lightGray = [248, 249, 250];

            // Cabeçalho do documento
            doc.setFillColor(...primaryColor);
            doc.rect(0, 0, 210, 35, 'F');

            doc.setTextColor(255, 255, 255);
            doc.setFontSize(22);
            doc.setFont(undefined, 'bold');
            doc.text('RELATÓRIO DE CARTÕES DE CRÉDITO', 105, 15, { align: 'center' });

            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text(`Período: ${mesAno}`, 105, 22, { align: 'center' });
            doc.text(`Gerado em: ${dataAtual.toLocaleDateString('pt-BR')} às ${dataAtual.toLocaleTimeString('pt-BR')}`, 105, 28, { align: 'center' });

            // Resumo Financeiro
            let yPos = 45;
            doc.setTextColor(...darkColor);
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('RESUMO FINANCEIRO', 14, yPos);

            yPos += 8;
            doc.autoTable({
                startY: yPos,
                head: [['Indicador', 'Valor']],
                body: [
                    ['Total de Cartões', this.filteredCartoes.length.toString()],
                    ['Limite Total Combinado', this.formatMoney(limiteTotal)],
                    ['Limite Utilizado', this.formatMoney(limiteUtilizado)],
                    ['Limite Disponível', this.formatMoney(limiteDisponivel)],
                    ['Percentual de Utilização', `${percentualGeral}%`]
                ],
                theme: 'grid',
                headStyles: {
                    fillColor: primaryColor,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'left'
                },
                columnStyles: {
                    0: { cellWidth: 100, fontStyle: 'bold' },
                    1: { cellWidth: 86, halign: 'right' }
                },
                styles: {
                    fontSize: 10,
                    cellPadding: 5
                },
                alternateRowStyles: {
                    fillColor: lightGray
                }
            });

            // Detalhamento por Cartão
            yPos = doc.lastAutoTable.finalY + 15;
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('DETALHAMENTO POR CARTÃO', 14, yPos);

            yPos += 5;
            const tableData = this.filteredCartoes.map(cartao => {
                const limiteDisp = cartao.limite_disponivel_real ?? cartao.limite_disponivel ?? 0;
                const percentualUso = cartao.limite_total > 0
                    ? ((cartao.limite_total - limiteDisp) / cartao.limite_total * 100).toFixed(1)
                    : 0;

                return [
                    cartao.nome_cartao,
                    this.formatBandeira(cartao.bandeira),
                    `**** ${cartao.ultimos_digitos}`,
                    this.formatMoney(cartao.limite_total),
                    this.formatMoney(limiteDisp),
                    `${percentualUso}%`,
                    cartao.ativo ? 'Ativo' : 'Inativo'
                ];
            });

            doc.autoTable({
                startY: yPos,
                head: [['Cartão', 'Bandeira', 'Final', 'Limite Total', 'Disponível', 'Uso', 'Status']],
                body: tableData,
                theme: 'grid',
                headStyles: {
                    fillColor: primaryColor,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 40 },
                    1: { cellWidth: 25, halign: 'center' },
                    2: { cellWidth: 25, halign: 'center' },
                    3: { cellWidth: 28, halign: 'right' },
                    4: { cellWidth: 28, halign: 'right' },
                    5: { cellWidth: 18, halign: 'center' },
                    6: { cellWidth: 22, halign: 'center' }
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 4
                },
                alternateRowStyles: {
                    fillColor: lightGray
                }
            });

            // Rodapé
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(
                    `Página ${i} de ${pageCount} | Lukrato - Sistema de Gestão Financeira`,
                    105,
                    287,
                    { align: 'center' }
                );
            }

            // Salvar PDF
            doc.save(`relatorio_cartoes_${dataAtual.toISOString().split('T')[0]}.pdf`);

            this.showToast('success', 'Relatório exportado com sucesso');

        } catch (error) {
            console.error('Erro ao exportar:', error);
            this.showToast('error', 'Erro ao exportar relatório');
        }
    }

    /**
     * Formatar bandeira com capitalização
     */
    formatBandeira(bandeira) {
        if (!bandeira) return 'Não informado';
        return bandeira.charAt(0).toUpperCase() + bandeira.slice(1).toLowerCase();
    }

    /**
     * Formatar dinheiro para CSV (sem símbolo)
     */
    formatMoneyForCSV(value) {
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value || 0);
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
    }

    /**
     * Obter ícone/logo da bandeira
     */
    getBrandIcon(bandeira) {
        const baseUrl = this.baseUrl.replace('/public/', '/public/assets/img/bandeiras/');
        const logos = {
            'visa': `${baseUrl}visa.png`,
            'mastercard': `${baseUrl}mastercard.png`,
            'elo': `${baseUrl}elo.png`,
            'amex': `${baseUrl}amex.png`,
            'diners': `${baseUrl}diners.png`,
            'discover': `${baseUrl}discover.png`
        };
        return logos[bandeira?.toLowerCase()] || `${baseUrl}default.png`;
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


        // Handler da máscara
        limiteInput.addEventListener('input', function (e) {
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
        });


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
     * Ver fatura do cartão - redireciona para página de faturas
     */
    verFatura(cartaoId, mes = null, ano = null) {
        // Data atual para filtros (se não especificado)
        const hoje = new Date();
        mes = mes || hoje.getMonth() + 1; // 1-12
        ano = ano || hoje.getFullYear();

        // Redirecionar para página de faturas com filtro do cartão
        window.location.href = `${this.baseUrl}faturas?cartao_id=${cartaoId}&mes=${mes}&ano=${ano}`;
    }

    /**
     * Mostrar modal da fatura
     */
    mostrarModalFatura(fatura, parcelamentos = null, statusPagamento = null, cartaoId = null) {
        // IMPORTANTE: Remover qualquer modal existente antes de criar um novo
        const modalExistente = document.querySelector('.modal-fatura-overlay');
        if (modalExistente) {
            modalExistente.remove();
        }

        const modal = this.criarModalFatura(fatura, parcelamentos, statusPagamento, cartaoId);
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

        // Gerenciar seleção de parcelas (aguardar renderização completa)
        requestAnimationFrame(() => {
            this.setupParcelaSelection(modal, fatura);
        });

        // Botão pagar parcelas selecionadas
        modal.querySelector('.btn-pagar-fatura')?.addEventListener('click', () => {
            this.pagarParcelasSelecionadas(fatura);
        });
    }

    /**
     * Configurar seleção de parcelas
     */
    setupParcelaSelection(modal, fatura) {
        const selectAll = modal.querySelector('#selectAllParcelas');
        const checkboxes = modal.querySelectorAll('.parcela-checkbox');
        const totalElement = modal.querySelector('#totalSelecionado');

        // Guard: se já foi configurado, não configurar novamente
        if (modal.dataset.parcelasConfigured === 'true') {
            return;
        }
        modal.dataset.parcelasConfigured = 'true';


        // Atualizar total quando mudar seleção
        const atualizarTotal = () => {
            let total = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseFloat(cb.dataset.valor);
                }
            });
            if (totalElement) {
                totalElement.textContent = this.formatMoney(total);
            }
        };

        // Selecionar/desselecionar todos
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {

                atualizarTotal();
            });
        }

        // Atualizar ao mudar checkbox individual
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                atualizarTotal();
                // Atualizar estado do "selecionar todos"
                if (selectAll) {
                    const todasMarcadas = Array.from(checkboxes).every(c => c.checked);
                    selectAll.checked = todasMarcadas;
                }
            });
        });

        // Inicializar total
        atualizarTotal();
    }

    /**
     * Pagar parcelas selecionadas
     */
    async pagarParcelasSelecionadas(fatura) {
        const checkboxes = document.querySelectorAll('.parcela-checkbox:checked');



        // Log detalhado de cada checkbox
        checkboxes.forEach((cb, index) => {
        });

        if (checkboxes.length === 0) {
            await Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Selecione pelo menos uma parcela para pagar.'
            });
            return;
        }

        let totalSelecionado = 0;
        checkboxes.forEach(cb => {
            const valor = parseFloat(cb.dataset.valor);
            totalSelecionado += valor;
        });


        const confirmado = await this.showConfirmDialog(
            'Confirmar Pagamento',
            `Deseja pagar ${checkboxes.length} parcela(s) no valor total de ${this.formatMoney(totalSelecionado)}?`
        );

        if (!confirmado) return;

        await this.pagarParcelasIndividuais(checkboxes, fatura);
    }

    /**
     * Pagar parcelas individuais
     */
    async pagarParcelasIndividuais(checkboxes, fatura) {
        try {
            const parcelaIds = Array.from(checkboxes).map(cb => parseInt(cb.dataset.id));

            // Obter cartao_id correto (pode estar em fatura.cartao_id ou fatura.cartao.id)
            const cartaoId = fatura.cartao_id || fatura.cartao?.id;



            if (!cartaoId) {
                throw new Error('ID do cartão não encontrado na fatura');
            }

            const csrfToken = await this.getCSRFToken();

            const response = await fetch(`${this.baseUrl}api/cartoes/${cartaoId}/parcelas/pagar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    parcela_ids: parcelaIds,
                    mes: fatura.mes,
                    ano: fatura.ano
                })
            });

            const data = await response.json();

            if (response.ok) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.message || 'Parcelas pagas com sucesso!'
                });

                // Fechar modal e recarregar
                const modal = document.querySelector('.modal-fatura-overlay');
                if (modal) {
                    this.fecharModalFatura(modal);
                }

                await this.loadCartoes();
            } else {
                throw new Error(data.message || 'Erro ao pagar parcelas');
            }
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message
            });
        }
    }

    /**
     * Criar HTML do modal da fatura
     */
    criarModalFatura(fatura, parcelamentos = null, statusPagamento = null, cartaoId = null) {
        const modal = document.createElement('div');
        modal.className = 'modal-fatura-overlay';
        modal.innerHTML = `<div class="modal-fatura-container">${this.criarConteudoModal(fatura, parcelamentos, statusPagamento, cartaoId)}</div>`;
        return modal;
    }

    /**
     * Criar conteúdo interno do modal
     */
    criarConteudoModal(fatura, parcelamentos = null, statusPagamento = null, cartaoId = null) {
        // Garantir que temos o cartaoId correto
        const idCartao = cartaoId || fatura.cartao_id || fatura.cartao?.id;



        // Se a fatura está paga, mostrar modal diferente
        if (statusPagamento && statusPagamento.pago) {
            return this.criarConteudoModalFaturaPaga(fatura, statusPagamento, parcelamentos, idCartao);
        }

        return `
                <div class="modal-fatura-header">
                    <div class="header-info">
                        <div class="cartao-info">
                            <span class="cartao-nome">${fatura.cartao.nome}</span>
                            <span class="cartao-numero">•••• ${fatura.cartao.ultimos_digitos}</span>
                        </div>
                        <div class="fatura-navegacao">
                            <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${idCartao}, ${fatura.mes}, ${fatura.ano}, -1)" title="Mês anterior">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="fatura-periodo">${this.getNomeMes(fatura.mes)}/${fatura.ano}</span>
                            <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${idCartao}, ${fatura.mes}, ${fatura.ano}, 1)" title="Próximo mês">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn-historico-toggle" onclick="cartoesManager.toggleHistoricoFatura(${idCartao})" title="Ver histórico">
                            <i class="fas fa-history"></i>
                        </button>
                        <button class="btn-fechar-fatura" title="Fechar">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-fatura-body">
                    ${(fatura.itens || []).filter(p => !p.pago).length === 0 && (fatura.itens || []).filter(p => p.pago).length === 0 ? `
                        <div class="fatura-empty">
                            <i class="fas fa-check-circle"></i>
                            <h3>Nenhuma fatura pendente</h3>
                            <p>Você não tem compras para pagar neste mês!</p>
                        </div>
                    ` : (fatura.itens || []).filter(p => !p.pago).length === 0 && (fatura.itens || []).filter(p => p.pago).length > 0 ? `
                        <!-- Todas as parcelas já foram pagas -->
                        <div class="fatura-totalmente-paga">
                            <div class="status-paga-header">
                                <i class="fas fa-check-circle"></i>
                                <h3>Fatura Totalmente Paga</h3>
                                <p>Todas as compras deste mês já foram pagas!</p>
                            </div>

                            <div class="fatura-parcelas-pagas-completa">
                                <h3 class="secao-titulo">
                                    <i class="fas fa-receipt" style="color: #10b981; margin-right: 8px;"></i>
                                    Itens Pagos (${(fatura.itens || []).filter(p => p.pago).length})
                                </h3>
                                <div class="lancamentos-lista">
                                    ${(fatura.itens || []).filter(p => p.pago).map(parcela => `
                                        <div class="lancamento-item lancamento-pago">
                                            <div class="lanc-info">
                                                <span class="lanc-desc">${this.escapeHtml(parcela.descricao)}</span>
                                                <span class="lanc-data-pagamento">
                                                    <i class="fas fa-calendar-check"></i>
                                                    Pago em ${this.formatDate(parcela.data_pagamento || parcela.data)}
                                                </span>
                                            </div>
                                            <div class="lanc-right">
                                                <span class="lanc-valor">${this.formatMoney(parcela.valor)}</span>
                                                <button class="btn-desfazer-parcela" 
                                                    onclick="cartoesManager.desfazerPagamentoParcela(${parcela.id})"
                                                    title="Desfazer pagamento desta parcela">
                                                    <i class="fas fa-undo"></i>
                                                    Desfazer
                                                </button>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    ` : `
                        <div class="fatura-resumo-principal">
                            <div class="resumo-item">
                                <span class="resumo-label">Total a Pagar</span>
                                <strong class="resumo-valor">${this.formatMoney(fatura.total)}</strong>
                            </div>
                            <div class="resumo-item">
                                <span class="resumo-label">Vencimento</span>
                                <strong class="resumo-data">${this.formatDate(fatura.vencimento)}</strong>
                            </div>
                        </div>

                        <div class="fatura-parcelas">
                            <h3 class="secao-titulo">
                                <label class="checkbox-custom">
                                    <input type="checkbox" id="selectAllParcelas">
                                    <span class="checkmark"></span>
                                </label>
                                Lançamentos Pendentes
                            </h3>
                            <div class="lancamentos-lista">
                                ${(fatura.itens || []).filter(p => !p.pago).map(parcela => `
                                    <div class="lancamento-item">
                                        <label class="checkbox-custom">
                                            <input type="checkbox" class="parcela-checkbox" data-id="${parcela.id}" data-valor="${parcela.valor}">
                                            <span class="checkmark"></span>
                                        </label>
                                        <div class="lanc-info">
                                            <span class="lanc-desc">${this.escapeHtml(parcela.descricao)}</span>
                                        </div>
                                        <span class="lanc-valor">${this.formatMoney(parcela.valor)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        ${(fatura.itens || []).filter(p => p.pago).length > 0 ? `
                            <div class="fatura-parcelas-pagas" style="margin-top: 1.5rem;">
                                <h3 class="secao-titulo">
                                    <i class="fas fa-check-circle" style="color: #10b981; margin-right: 8px;"></i>
                                    Lançamentos Pagos
                                </h3>
                                <div class="lancamentos-lista">
                                    ${(fatura.itens || []).filter(p => p.pago).map(parcela => `
                                        <div class="lancamento-item lancamento-pago">
                                            <div class="lanc-info">
                                                <span class="lanc-desc">${this.escapeHtml(parcela.descricao)}</span>
                                                <span class="lanc-data-pagamento">
                                                    <i class="fas fa-calendar-check"></i>
                                                    Pago em ${this.formatDate(parcela.data_pagamento || parcela.data)}
                                                </span>
                                            </div>
                                            <div class="lanc-right">
                                                <span class="lanc-valor">${this.formatMoney(parcela.valor)}</span>
                                                <button class="btn-desfazer-parcela" 
                                                    onclick="cartoesManager.desfazerPagamentoParcela(${parcela.id})"
                                                    title="Desfazer pagamento desta parcela">
                                                    <i class="fas fa-undo"></i>
                                                    Desfazer
                                                </button>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                    `}
                </div>

                ${(fatura.itens || []).filter(p => !p.pago).length > 0 ? `
                    <div class="modal-fatura-footer">
                        <div class="footer-info">
                            <span class="footer-label">Total selecionado:</span>
                            <strong class="footer-valor" id="totalSelecionado">${this.formatMoney(fatura.total)}</strong>
                        </div>
                        <button class="btn btn-primary btn-pagar-fatura" id="btnPagarSelecionadas">
                            <i class="fas fa-check"></i>
                            Pagar Parcelas Selecionadas
                        </button>
                    </div>
                ` : ''}
        `;
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

        // Referência ao botão
        const btnPagar = document.querySelector('.btn-pagar-fatura');
        const originalText = btnPagar ? btnPagar.innerHTML : '';

        try {
            // Ativar loading state
            if (btnPagar) {
                btnPagar.disabled = true;
                btnPagar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                btnPagar.style.opacity = '0.6';
                btnPagar.style.cursor = 'not-allowed';
            }

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

            // 🎮 GAMIFICAÇÃO: Exibir conquistas se houver
            if (resultado.gamification?.achievements && Array.isArray(resultado.gamification.achievements)) {
                if (typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(resultado.gamification.achievements);
                } else {
                    console.error('❌ notifyMultipleAchievements não está disponível');
                }
            } else {
            }

            this.showToast('success', `Fatura paga com sucesso! ${resultado.parcelas_pagas} parcela(s) quitada(s).`);

            // Fechar modal
            const modal = document.querySelector('.modal-fatura-overlay');
            if (modal) {
                this.fecharModalFatura(modal);
            }

            // Recarregar cartões para atualizar limite
            this.loadCartoes();
        } catch (error) {
            console.error('❌ Erro ao pagar fatura:', error);

            // Restaurar botão em caso de erro
            if (btnPagar) {
                btnPagar.disabled = false;
                btnPagar.innerHTML = originalText;
                btnPagar.style.opacity = '1';
                btnPagar.style.cursor = 'pointer';
            }
            this.showToast('error', error.message || 'Erro ao pagar fatura');
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
     * Criar conteúdo do modal para fatura já paga
     */
    criarConteudoModalFaturaPaga(fatura, statusPagamento, parcelamentos, cartaoId) {
        // Garantir que temos o cartaoId correto
        const idCartao = cartaoId || fatura.cartao_id || fatura.cartao?.id;



        // Usar data_pagamento do status, ou pegar da primeira parcela paga como fallback
        const dataPagamento = statusPagamento?.data_pagamento ||
            (fatura.itens || []).find(p => p.pago && p.data_pagamento)?.data_pagamento ||
            null;


        return `
            <div class="modal-fatura-header">
                <div class="header-info">
                    <div class="cartao-info">
                        <span class="cartao-nome">${fatura.cartao.nome}</span>
                        <span class="cartao-numero">•••• ${fatura.cartao.ultimos_digitos}</span>
                    </div>
                    <div class="fatura-navegacao">
                        <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${idCartao}, ${fatura.mes}, ${fatura.ano}, -1)" title="Mês anterior">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="fatura-periodo">${this.getNomeMes(fatura.mes)}/${fatura.ano}</span>
                        <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${idCartao}, ${fatura.mes}, ${fatura.ano}, 1)" title="Próximo mês">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn-fechar-fatura" title="Fechar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="modal-fatura-body">
                <div class="fatura-totalmente-paga">
                    <div class="status-paga-header">
                        <i class="fas fa-check-circle"></i>
                        <h3>Fatura Totalmente Paga</h3>
                        <p>
                            ${dataPagamento ? `Pago em ${this.formatDate(dataPagamento)} • ` : 'Fatura paga • '}
                            Valor: ${this.formatMoney(statusPagamento.valor)}
                        </p>
                    </div>

                    <div class="fatura-parcelas-pagas-completa">
                        <div class="secao-titulo-com-botao">
                            <h3 class="secao-titulo">
                                <i class="fas fa-receipt" style="color: #10b981; margin-right: 8px;"></i>
                                Itens Pagos (${(fatura.itens || []).filter(p => p.pago).length})
                            </h3>
                            <button class="btn-desfazer-todas" 
                                onclick="cartoesManager.desfazerPagamento(${idCartao}, ${fatura.mes}, ${fatura.ano})"
                                title="Desfazer pagamento de todas as parcelas">
                                <i class="fas fa-undo"></i>
                                Desfazer Todas
                            </button>
                        </div>
                        <div class="lancamentos-lista">
                            ${(fatura.itens || []).filter(p => p.pago).map(parcela => `
                                <div class="lancamento-item lancamento-pago">
                                    <div class="lanc-info">
                                        <span class="lanc-desc">${this.escapeHtml(parcela.descricao)}</span>
                                        <span class="lanc-data-pagamento">
                                            <i class="fas fa-calendar-check"></i>
                                            ${parcela.parcela_atual}/${parcela.total_parcelas}
                                        </span>
                                    </div>
                                    <div class="lanc-right">
                                        <span class="lanc-valor">${this.formatMoney(parcela.valor)}</span>
                                        <button class="btn-desfazer-parcela" 
                                            onclick="cartoesManager.desfazerPagamentoParcela(${parcela.id})"
                                            title="Desfazer pagamento desta parcela">
                                            <i class="fas fa-undo"></i>
                                            Desfazer
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Desfazer pagamento de fatura
     */
    async desfazerPagamento(cartaoId, mes, ano) {
        const confirmado = await Swal.fire({
            title: 'Desfazer pagamento?',
            html: `
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 300px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar as parcelas como não pagas</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Tem certeza?</strong></p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, desfazer',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            reverseButtons: true
        });

        if (!confirmado.isConfirmed) return;

        try {
            const csrfToken = await this.getCSRFToken();

            const response = await fetch(
                `${this.baseUrl}api/cartoes/${cartaoId}/fatura/desfazer-pagamento`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ mes, ano })
                }
            );

            const data = await response.json();

            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Pagamento desfeito!',
                    text: data.message,
                    timer: 2500,
                    showConfirmButton: false
                });

                // Fechar modal e recarregar
                const modal = document.querySelector('.modal-fatura-overlay');
                if (modal) {
                    this.fecharModalFatura(modal);
                }

                await this.loadCartoes();
            } else {
                throw new Error(data.message || 'Erro ao desfazer pagamento');
            }
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message
            });
        }
    }

    /**
     * Desfazer pagamento de uma parcela individual
     */
    async desfazerPagamentoParcela(parcelaId) {
        const confirmado = await Swal.fire({
            title: 'Desfazer pagamento desta parcela?',
            html: `
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 320px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar esta parcela como não paga</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Deseja continuar?</strong></p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, desfazer',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            reverseButtons: true
        });

        if (!confirmado.isConfirmed) return;

        try {
            const csrfToken = await this.getCSRFToken();

            const response = await fetch(
                `${this.baseUrl}api/cartoes/parcelas/${parcelaId}/desfazer-pagamento`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }
            );

            const data = await response.json();

            if (response.ok && data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Pagamento desfeito!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                // Fechar modal e recarregar
                const modal = document.querySelector('.modal-fatura-overlay');
                if (modal) {
                    this.fecharModalFatura(modal);
                }

                await this.loadCartoes();
            } else {
                throw new Error(data.message || 'Erro ao desfazer pagamento');
            }
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message
            });
        }
    }

    /**
     * Navegar entre meses na fatura
     */
    async navegarMes(cartaoId, mesAtual, anoAtual, direcao) {

        // Calcular novo mês/ano
        let novoMes = mesAtual + direcao;
        let novoAno = anoAtual;

        if (novoMes > 12) {
            novoMes = 1;
            novoAno++;
        } else if (novoMes < 1) {
            novoMes = 12;
            novoAno--;
        }

        try {
            // Buscar fatura, parcelamentos e status do novo mês
            const [faturaResponse, parcelamentosResponse, statusResponse] = await Promise.all([
                fetch(`${this.baseUrl}api/cartoes/${cartaoId}/fatura?mes=${novoMes}&ano=${novoAno}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).catch(() => ({ ok: false, status: 404 })),
                fetch(`${this.baseUrl}api/cartoes/${cartaoId}/parcelamentos-resumo?mes=${novoMes}&ano=${novoAno}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).catch(() => ({ ok: false, status: 404 })),
                fetch(`${this.baseUrl}api/cartoes/${cartaoId}/fatura/status?mes=${novoMes}&ano=${novoAno}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).catch(() => ({ ok: false, status: 404 }))
            ]);

            if (!faturaResponse.ok) {
                throw new Error('Erro ao carregar fatura');
            }

            const fatura = await faturaResponse.json();
            let parcelamentos = null;
            let statusPagamento = null;

            if (parcelamentosResponse.ok) {
                parcelamentos = await parcelamentosResponse.json();
            }

            if (statusResponse.ok) {
                statusPagamento = await statusResponse.json();
            }

            // Atualizar conteúdo do modal sem fechá-lo
            const modalContainer = document.querySelector('.modal-fatura-container');
            if (modalContainer) {
                const novoConteudo = this.criarConteudoModal(fatura, parcelamentos, statusPagamento, cartaoId);
                modalContainer.innerHTML = novoConteudo;

                // Re-adicionar event listeners
                modalContainer.querySelector('.btn-fechar-fatura')?.addEventListener('click', () => {
                    const modal = document.querySelector('.modal-fatura-overlay');
                    this.fecharModalFatura(modal);
                });

                // Botão pagar parcelas selecionadas (CORRETO)
                modalContainer.querySelector('.btn-pagar-fatura')?.addEventListener('click', () => {
                    this.pagarParcelasSelecionadas(fatura);
                });

                // Re-aplicar seleção de parcelas
                const modal = document.querySelector('.modal-fatura-overlay');
                requestAnimationFrame(() => {
                    this.setupParcelaSelection(modal, fatura);
                });
            }
        } catch (error) {
            console.error('❌ Erro ao navegar entre meses:', error);
            this.showToast('error', 'Erro ao carregar fatura');
        }
    }

    /**
     * Carregar dados da fatura
     */
    async carregarFatura(cartaoId, mes, ano) {
        const response = await fetch(`${this.baseUrl}api/cartoes/${cartaoId}/fatura?mes=${mes}&ano=${ano}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            if (response.status === 404) {
                // Fatura não encontrada - retornar objeto vazio
                return { itens: [], total: 0, pago: 0, pendente: 0 };
            }
            throw new Error('Erro ao carregar fatura');
        }

        return await response.json();
    }

    /**
     * Carregar resumo de parcelamentos
     */
    async carregarParcelamentosResumo(cartaoId, mes, ano) {
        const response = await fetch(`${this.baseUrl}api/cartoes/${cartaoId}/parcelamentos-resumo?mes=${mes}&ano=${ano}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Erro ao carregar parcelamentos');
        }

        return await response.json();
    }

    /**
     * Toggle entre fatura atual e histórico
     */
    async toggleHistoricoFatura(cartaoId) {
        try {
            const modalContainer = document.querySelector('.modal-fatura-container');
            if (!modalContainer) return;

            // Verifica se já está mostrando histórico
            const mostandoHistorico = modalContainer.querySelector('.historico-faturas');

            if (mostandoHistorico) {
                // Volta para a fatura atual
                const hoje = new Date();
                const mes = hoje.getMonth() + 1;
                const ano = hoje.getFullYear();

                const [fatura, parcelamentos, statusResponse] = await Promise.all([
                    this.carregarFatura(cartaoId, mes, ano),
                    this.carregarParcelamentosResumo(cartaoId, mes, ano).catch(() => null),
                    fetch(`${this.baseUrl}api/cartoes/${cartaoId}/fatura/status?mes=${mes}&ano=${ano}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    }).then(r => r.ok ? r.json() : null).catch(() => null)
                ]);

                const conteudo = this.criarConteudoModal(fatura, parcelamentos, statusResponse, cartaoId);
                modalContainer.innerHTML = conteudo;

                this.adicionarEventListenersModal(fatura);
            } else {
                // Mostra histórico
                const historico = await this.carregarHistoricoFaturas(cartaoId);
                const conteudo = this.criarConteudoHistorico(historico, cartaoId);
                modalContainer.innerHTML = conteudo;

                this.adicionarEventListenersModal(null);
            }
        } catch (error) {
            console.error('❌ Erro ao alternar histórico:', error);
            this.showToast('error', 'Erro ao carregar histórico');
        }
    }

    /**
     * Carregar histórico de faturas pagas
     */
    async carregarHistoricoFaturas(cartaoId, limite = 12) {
        const csrfToken = await this.getCSRFToken();

        const response = await fetch(`${this.baseUrl}api/cartoes/${cartaoId}/faturas-historico?limite=${limite}`, {
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Erro ao carregar histórico');
        }

        return await response.json();
    }

    /**
     * Criar conteúdo do histórico de faturas
     */
    criarConteudoHistorico(historico, cartaoId) {
        return `
            <div class="modal-fatura-header">
                <div class="header-info">
                    <div class="cartao-info">
                        <span class="cartao-nome">${historico.cartao.nome}</span>
                        <span class="cartao-subtitulo">Histórico de Faturas Pagas</span>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn-historico-toggle" onclick="cartoesManager.toggleHistoricoFatura(${cartaoId})" title="Voltar para fatura atual">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <button class="btn-fechar-fatura" title="Fechar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="modal-fatura-body historico-faturas">
                ${historico.historico.length === 0 ? `
                    <div class="fatura-empty">
                        <i class="fas fa-receipt"></i>
                        <h3>Nenhuma fatura paga</h3>
                        <p>Você ainda não pagou nenhuma fatura neste cartão.</p>
                    </div>
                ` : `
                    <div class="historico-lista">
                        ${historico.historico.map(item => `
                            <div class="historico-item">
                                <div class="historico-periodo">
                                    <i class="fas fa-calendar-check"></i>
                                    <div class="periodo-info">
                                        <strong>${item.mes_nome} ${item.ano}</strong>
                                        <span class="historico-data-pag">Pago em ${this.formatDate(item.data_pagamento)}</span>
                                    </div>
                                </div>
                                <div class="historico-detalhes">
                                    <div class="historico-valor">
                                        ${this.formatMoney(item.total)}
                                    </div>
                                    <div class="historico-qtd">
                                        ${item.quantidade_lancamentos} lançamento${item.quantidade_lancamentos !== 1 ? 's' : ''}
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `}
            </div>
        `;
    }

    /**
     * Adicionar event listeners aos elementos do modal
     */
    adicionarEventListenersModal(fatura) {
        const modalContainer = document.querySelector('.modal-fatura-container');
        if (!modalContainer) return;

        modalContainer.querySelector('.btn-fechar-fatura')?.addEventListener('click', () => {
            const modal = document.querySelector('.modal-fatura-overlay');
            this.fecharModalFatura(modal);
        });

        if (fatura) {
            modalContainer.querySelector('.btn-pagar-fatura')?.addEventListener('click', () => {
                this.pagarFatura(fatura);
            });
        }
    }

    /**
     * Formatar data para exibição
     */
    formatDate(dateString) {
        if (!dateString) return '-';

        // Tenta diferentes formatos
        let date;

        // Se já é um objeto Date
        if (dateString instanceof Date) {
            date = dateString;
        }
        // Se tem o formato YYYY-MM-DD ou ISO 8601 (YYYY-MM-DDTHH:MM:SS.SSSZ)
        else if (typeof dateString === 'string') {
            // Se for formato ISO completo (com T e Z), usar Date constructor diretamente
            if (dateString.includes('T')) {
                date = new Date(dateString);
            } else {
                // Remove qualquer parte de hora se houver (formato simples)
                const datePart = dateString.split(' ')[0];
                const [year, month, day] = datePart.split('-');
                date = new Date(year, month - 1, day);
            }
        }

        // Verifica se é uma data válida
        if (isNaN(date.getTime())) {
            return '-';
        }

        return date.toLocaleDateString('pt-BR');
    }
}
