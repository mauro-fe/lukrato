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

        this.init();
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

        // Form submit
        const form = document.getElementById('formCartao');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveCartao();
            });
        }

        // Máscara de dinheiro no limite total
        const limiteInput = document.getElementById('limiteTotal');
        if (limiteInput) {
            limiteInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                value = (parseInt(value) || 0) / 100;
                e.target.value = value.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            });
        }

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

        return `
            <div class="credit-card" data-id="${cartao.id}" data-brand="${cartao.bandeira?.toLowerCase() || 'outros'}">
                <div class="card-header">
                    <div class="card-brand">
                        <i class="brand-icon ${brandIcon}"></i>
                        <span class="card-name">${this.escapeHtml(cartao.nome_cartao)}</span>
                    </div>
                    <div class="card-actions">
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
        document.getElementById('limiteTotal').textContent = this.formatMoney(stats.limiteTotal);
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
            document.getElementById('limiteTotal').value = this.formatMoney(cartaoData.limite_total);
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
            console.error('Select contaVinculada não encontrado!');
            return;
        }

        console.log('Carregando contas no select...');

        try {
            const response = await fetch(`${window.BASE_URL}api/contas`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            console.log('Response status:', response.status);

            if (!response.ok) throw new Error('Erro ao carregar contas');

            const data = await response.json();
            console.log('Dados recebidos da API:', data);

            const contas = data.data || data.contas || [];
            console.log('Array de contas:', contas);
            console.log('Total de contas:', contas.length);

            if (contas.length === 0) {
                select.innerHTML = '<option value="">Nenhuma conta cadastrada</option>';
                console.warn('Nenhuma conta encontrada');
                return;
            }

            const options = contas.map(conta => {
                const nome = this.escapeHtml(conta.descricao || conta.nome || 'Sem nome');
                const saldo = this.formatMoney(conta.saldo_atual || conta.saldo || 0);
                console.log(`Conta: ID=${conta.id}, Nome=${nome}, Saldo=${saldo}`);
                return `<option value="${conta.id}">${nome} - ${saldo}</option>`;
            }).join('');

            select.innerHTML = '<option value="">Selecione a conta</option>' + options;
            console.log('Select preenchido com sucesso. HTML:', select.innerHTML);
        } catch (error) {
            console.error('Erro ao carregar contas:', error);
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

        const data = {
            nome_cartao: document.getElementById('nomeCartao').value,
            conta_id: document.getElementById('contaVinculada').value,
            bandeira: document.getElementById('bandeira').value,
            ultimos_digitos: document.getElementById('ultimosDigitos').value,
            limite_total: this.parseMoney(document.getElementById('limiteTotal').value),
            dia_fechamento: document.getElementById('diaFechamento').value || null,
            dia_vencimento: document.getElementById('diaVencimento').value || null
        };

        try {
            const url = isEdit
                ? `${window.BASE_URL}api/cartoes/${cartaoId}`
                : `${window.BASE_URL}api/cartoes`;

            const response = await fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Erro ao salvar cartão');
            }

            this.showToast('success', isEdit ? 'Cartão atualizado com sucesso!' : 'Cartão criado com sucesso!');
            this.closeModal();
            this.loadCartoes();
        } catch (error) {
            console.error('Erro ao salvar cartão:', error);
            this.showToast('error', error.message || 'Erro ao salvar cartão');
        }
    }

    /**
     * Editar cartão
     */
    async editCartao(id) {
        const cartao = this.cartoes.find(c => c.id === id);
        if (cartao) {
            this.openModal('edit', id);
        }
    }

    /**
     * Deletar cartão
     */
    async deleteCartao(id) {
        const cartao = this.cartoes.find(c => c.id === id);
        if (!cartao) return;

        const confirmacao = await this.showConfirmDialog(
            'Excluir Cartão',
            `Tem certeza que deseja excluir o cartão "${cartao.nome_cartao}"?`,
            'Excluir'
        );

        if (!confirmacao) return;

        try {
            const response = await fetch(`${window.BASE_URL}api/cartoes/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Erro ao excluir cartão');
            }

            this.showToast('Cartão excluído com sucesso', 'success');
            this.loadCartoes();

        } catch (error) {
            console.error('Erro ao excluir:', error);
            this.showToast('Erro ao excluir cartão', 'error');
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
     * Formatar dinheiro
     */
    formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
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
        return confirm(`${title}\n\n${message}`);
    }
}
