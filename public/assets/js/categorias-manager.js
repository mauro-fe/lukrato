/**
 * Gerenciador de Categorias - Lukrato
 * Carrega e gerencia categorias de receitas e despesas
 */

class CategoriasManager {
    constructor() {
        this.categorias = [];
        this.categoriaEmEdicao = null;
        this.init();
    }

    /**
     * Inicializar manager
     */
    init() {
        console.log('🚀 Inicializando CategoriasManager...');
        this.attachEventListeners();
        this.loadCategorias();
    }

    /**
     * Anexar event listeners
     */
    attachEventListeners() {
        // Formulário de nova categoria
        const formNova = document.getElementById('formNova');
        if (formNova) {
            formNova.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleNovaCategoria(e.target);
            });
        }

        // Formulário de edição
        const formEdit = document.getElementById('formEditCategoria');
        if (formEdit) {
            formEdit.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleEditarCategoria(e.target);
            });
        }
    }

    /**
     * Obter CSRF Token
     */
    getCsrfToken() {
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    /**
     * Obter base URL
     */
    getBaseUrl() {
        // Pegar do elemento BASE_URL global ou construir do pathname
        return window.BASE_URL || window.location.pathname.split('/categorias')[0] + '/';
    }

    /**
     * Carregar categorias da API
     */
    async loadCategorias() {
        try {
            console.log('📡 Carregando categorias...');

            const baseUrl = this.getBaseUrl();
            const response = await fetch(`${baseUrl}api/categorias`);

            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }

            const result = await response.json();
            console.log('✅ Resposta da API:', result);
            console.log('Tipo de result:', typeof result);
            console.log('result.data:', result.data);
            console.log('É Array?', Array.isArray(result.data));

            // Processar resposta
            if (result.success && result.data) {
                this.categorias = result.data;
            } else if (Array.isArray(result.data)) {
                this.categorias = result.data;
            } else if (Array.isArray(result)) {
                this.categorias = result;
            } else if (result.categorias) {
                this.categorias = result.categorias;
            } else {
                this.categorias = [];
            }

            console.log('Categorias após processamento:', this.categorias);
            console.log(`✅ ${this.categorias.length} categorias carregadas`);
            this.renderCategorias();

        } catch (error) {
            console.error('❌ Erro ao carregar categorias:', error);
            this.showError('Erro ao carregar categorias. Tente novamente.');
        }
    }

    /**
     * Renderizar categorias na tela
     */
    renderCategorias() {
        const receitas = this.categorias.filter(c => c.tipo === 'receita');
        const despesas = this.categorias.filter(c => c.tipo === 'despesa');

        // Atualizar contadores
        document.getElementById('receitasCount').textContent = receitas.length;
        document.getElementById('despesasCount').textContent = despesas.length;

        // Renderizar listas
        this.renderListaReceitas(receitas);
        this.renderListaDespesas(despesas);
    }

    /**
     * Renderizar lista de receitas
     */
    renderListaReceitas(receitas) {
        const container = document.getElementById('receitasList');

        if (receitas.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma categoria de receita cadastrada</p>
                </div>
            `;
            return;
        }

        container.innerHTML = receitas.map(cat => this.renderCategoriaItem(cat, 'receita')).join('');
    }

    /**
     * Renderizar lista de despesas
     */
    renderListaDespesas(despesas) {
        const container = document.getElementById('despesasList');

        if (despesas.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma categoria de despesa cadastrada</p>
                </div>
            `;
            return;
        }

        container.innerHTML = despesas.map(cat => this.renderCategoriaItem(cat, 'despesa')).join('');
    }

    /**
     * Renderizar item de categoria
     */
    renderCategoriaItem(categoria, tipo) {
        // Verificar se o nome já tem emoji (caracteres Unicode > U+1F300)
        const hasEmoji = /[\u{1F300}-\u{1F9FF}]/u.test(categoria.nome);

        return `
            <div class="category-item" data-id="${categoria.id}">
                <span class="category-name">
                    ${hasEmoji ? '' : '<i class="fas fa-tag"></i>'}
                    ${this.escapeHtml(categoria.nome)}
                </span>
                <div class="category-actions">
                    <button type="button" class="action-btn edit" 
                            onclick="categoriasManager.editarCategoria(${categoria.id})"
                            title="Editar categoria">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="action-btn delete" 
                            onclick="categoriasManager.excluirCategoria(${categoria.id})"
                            title="Excluir categoria">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Criar nova categoria
     */
    async handleNovaCategoria(form) {
        try {
            const formData = new FormData(form);
            const data = {
                nome: formData.get('nome'),
                tipo: formData.get('tipo')
            };

            console.log('Criando categoria:', data);

            const baseUrl = this.getBaseUrl();
            const response = await fetch(`${baseUrl}api/categorias`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Erro ao criar categoria');
            }

            const result = await response.json();
            console.log('✅ Categoria criada:', result);

            this.showSuccess('Categoria criada com sucesso!');
            form.reset();

            // Recarregar categorias
            await this.loadCategorias();

        } catch (error) {
            console.error('❌ Erro ao criar categoria:', error);
            this.showError(error.message || 'Erro ao criar categoria. Tente novamente.');
        }
    }

    /**
     * Editar categoria
     */
    editarCategoria(id) {
        const categoria = this.categorias.find(c => c.id === id);
        if (!categoria) return;

        this.categoriaEmEdicao = categoria;

        // Preencher formulário
        document.getElementById('editCategoriaNome').value = categoria.nome;
        document.getElementById('editCategoriaTipo').value = categoria.tipo;

        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('modalEditCategoria'));
        modal.show();
    }

    /**
     * Salvar edição de categoria
     */
    async handleEditarCategoria(form) {
        if (!this.categoriaEmEdicao) return;

        try {
            const formData = new FormData(form);
            const data = {
                nome: formData.get('nome'),
                tipo: formData.get('tipo')
            };

            console.log('Editando categoria:', this.categoriaEmEdicao.id, data);

            const baseUrl = this.getBaseUrl();
            const response = await fetch(`${baseUrl}api/categorias/${this.categoriaEmEdicao.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Erro ao editar categoria');
            }

            const result = await response.json();
            console.log('✅ Categoria editada:', result);

            this.showSuccess('Categoria atualizada com sucesso!');

            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditCategoria'));
            modal.hide();

            // Recarregar categorias
            await this.loadCategorias();

        } catch (error) {
            console.error('❌ Erro ao editar categoria:', error);
            this.showError(error.message || 'Erro ao editar categoria. Tente novamente.');
        }
    }

    /**
     * Excluir categoria
     */
    async excluirCategoria(id) {
        const categoria = this.categorias.find(c => c.id === id);
        if (!categoria) return;

        const confirmacao = await Swal.fire({
            title: 'Confirmar exclusão',
            html: `Deseja realmente excluir a categoria <strong>${categoria.nome}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacao.isConfirmed) return;

        try {
            console.log('Excluindo categoria:', id);

            const baseUrl = this.getBaseUrl();
            const response = await fetch(`${baseUrl}api/categorias/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                }
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Erro ao excluir categoria');
            }

            console.log('✅ Categoria excluída');

            this.showSuccess('Categoria excluída com sucesso!');

            // Recarregar categorias
            await this.loadCategorias();

        } catch (error) {
            console.error('❌ Erro ao excluir categoria:', error);
            this.showError(error.message || 'Erro ao excluir categoria. Pode haver lançamentos vinculados.');
        }
    }

    /**
     * Mostrar mensagem de sucesso
     */
    showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }

    /**
     * Mostrar mensagem de erro
     */
    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: message,
            confirmButtonText: 'OK'
        });
    }
}

// Inicializar quando DOM estiver pronto
let categoriasManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        categoriasManager = new CategoriasManager();
    });
} else {
    categoriasManager = new CategoriasManager();
}
