/**
 * Gerenciador de Categorias - Lukrato
 * Carrega e gerencia categorias de receitas e despesas
 */

class CategoriasManager {
    constructor() {
        this.categorias = [];
        this.orcamentos = [];
        this.categoriaEmEdicao = null;
        this.selectedIcon = '';       // ícone selecionado no form de criação
        this.editSelectedIcon = '';   // ícone selecionado no modal de edição

        // Ícones disponíveis agrupados por contexto (label de busca)
        this.availableIcons = [
            // Casa & Moradia
            { name: 'house', label: 'casa moradia aluguel' },
            { name: 'building-2', label: 'prédio apartamento condomínio' },
            { name: 'key', label: 'chave aluguel imóvel' },
            { name: 'sofa', label: 'sofá móveis decoração' },
            // Alimentação
            { name: 'utensils', label: 'alimentação comida refeição restaurante' },
            { name: 'coffee', label: 'café lanche bebida' },
            { name: 'apple', label: 'fruta mercado feira' },
            { name: 'wine', label: 'vinho bebida bar' },
            { name: 'pizza', label: 'pizza fast food delivery' },
            { name: 'sandwich', label: 'lanche sanduíche' },
            { name: 'beef', label: 'carne açougue churrasco' },
            { name: 'cookie', label: 'doce sobremesa' },
            { name: 'cup-soda', label: 'refrigerante bebida' },
            // Transporte
            { name: 'car', label: 'carro veículo transporte combustível' },
            { name: 'bus', label: 'ônibus transporte público' },
            { name: 'bike', label: 'bicicleta ciclismo' },
            { name: 'plane', label: 'avião viagem aéreo' },
            { name: 'fuel', label: 'combustível gasolina posto' },
            { name: 'train-front', label: 'trem metrô transporte' },
            // Contas & Serviços
            { name: 'lightbulb', label: 'luz energia elétrica conta' },
            { name: 'droplets', label: 'água conta' },
            { name: 'flame', label: 'gás conta' },
            { name: 'wifi', label: 'internet wifi' },
            { name: 'smartphone', label: 'telefone celular' },
            { name: 'tv', label: 'televisão streaming' },
            { name: 'receipt', label: 'conta fatura boleto' },
            // Saúde
            { name: 'heart-pulse', label: 'saúde médico consulta' },
            { name: 'pill', label: 'remédio farmácia medicamento' },
            { name: 'stethoscope', label: 'médico consulta saúde' },
            { name: 'dumbbell', label: 'academia exercício fitness' },
            { name: 'activity', label: 'atividade saúde' },
            // Educação
            { name: 'graduation-cap', label: 'educação faculdade curso' },
            { name: 'book-open', label: 'livro leitura estudo' },
            { name: 'pencil', label: 'escola material escolar' },
            { name: 'library', label: 'biblioteca estudo' },
            // Trabalho & Renda
            { name: 'briefcase', label: 'trabalho salário emprego' },
            { name: 'laptop', label: 'freelance computador trabalho' },
            { name: 'building', label: 'empresa escritório' },
            { name: 'wallet', label: 'carteira dinheiro renda' },
            { name: 'banknote', label: 'dinheiro venda receita' },
            { name: 'piggy-bank', label: 'poupança economia guardar' },
            { name: 'landmark', label: 'banco instituição' },
            { name: 'calculator', label: 'cálculo contabilidade imposto' },
            // Investimentos
            { name: 'trending-up', label: 'investimento rendimento lucro' },
            { name: 'bar-chart-3', label: 'gráfico investimento ação' },
            { name: 'coins', label: 'moedas dinheiro' },
            { name: 'bitcoin', label: 'cripto bitcoin' },
            // Lazer & Entretenimento
            { name: 'clapperboard', label: 'cinema filme entretenimento' },
            { name: 'music', label: 'música show evento' },
            { name: 'gamepad-2', label: 'jogo game videogame' },
            { name: 'palette', label: 'arte hobby criativo' },
            { name: 'camera', label: 'foto fotografia' },
            { name: 'headphones', label: 'fone áudio música podcast' },
            { name: 'ticket', label: 'ingresso evento show' },
            // Compras
            { name: 'shopping-cart', label: 'compras supermercado' },
            { name: 'shopping-bag', label: 'compras sacola loja' },
            { name: 'shirt', label: 'roupa vestuário' },
            { name: 'scissors', label: 'cabelo beleza salão' },
            { name: 'gem', label: 'jóia acessório presente' },
            { name: 'gift', label: 'presente bônus' },
            // Finanças
            { name: 'credit-card', label: 'cartão crédito' },
            { name: 'percent', label: 'juros desconto' },
            { name: 'shield-check', label: 'seguro proteção' },
            { name: 'calendar-check', label: 'planejamento agenda compromisso' },
            { name: 'pie-chart', label: 'investimento divisão' },
            // Família & Pessoal
            { name: 'baby', label: 'bebê filho criança' },
            { name: 'dog', label: 'pet animal cachorro' },
            { name: 'cat', label: 'gato pet animal' },
            { name: 'heart', label: 'amor doação caridade' },
            { name: 'church', label: 'igreja dízimo religião' },
            { name: 'users', label: 'família pessoas' },
            // Outros
            { name: 'trophy', label: 'prêmio conquista' },
            { name: 'wrench', label: 'manutenção conserto reparo' },
            { name: 'zap', label: 'urgente rápido' },
            { name: 'star', label: 'favorito destaque especial' },
            { name: 'tag', label: 'etiqueta geral outros' },
            { name: 'archive', label: 'arquivo guardar' },
            { name: 'package', label: 'pacote entrega frete' },
            { name: 'map-pin', label: 'local endereço viagem' },
            { name: 'globe', label: 'mundo internacional viagem' },
            { name: 'umbrella', label: 'proteção seguro' },
            { name: 'cigarette', label: 'cigarro vício' },
            { name: 'bed', label: 'hotel hospedagem' },
        ];

        // Sugestões pré-prontas por tipo
        this.suggestions = {
            despesa: [
                { nome: 'Alimentação', icone: 'utensils' },
                { nome: 'Moradia', icone: 'house' },
                { nome: 'Transporte', icone: 'car' },
                { nome: 'Contas e Serviços', icone: 'lightbulb' },
                { nome: 'Saúde', icone: 'heart-pulse' },
                { nome: 'Educação', icone: 'graduation-cap' },
                { nome: 'Vestuário', icone: 'shirt' },
                { nome: 'Lazer', icone: 'clapperboard' },
                { nome: 'Assinaturas', icone: 'smartphone' },
                { nome: 'Compras', icone: 'shopping-cart' },
                { nome: 'Pet', icone: 'dog' },
                { nome: 'Academia', icone: 'dumbbell' },
                { nome: 'Delivery', icone: 'pizza' },
                { nome: 'Farmácia', icone: 'pill' },
                { nome: 'Combustível', icone: 'fuel' },
                { nome: 'Internet', icone: 'wifi' },
                { nome: 'Manutenção', icone: 'wrench' },
            ],
            receita: [
                { nome: 'Salário', icone: 'briefcase' },
                { nome: 'Freelance', icone: 'laptop' },
                { nome: 'Investimentos', icone: 'trending-up' },
                { nome: 'Vendas', icone: 'banknote' },
                { nome: 'Bônus', icone: 'gift' },
                { nome: 'Prêmios', icone: 'trophy' },
                { nome: 'Outras Receitas', icone: 'wallet' },
                { nome: 'Dividendos', icone: 'coins' },
                { nome: 'Aluguel', icone: 'key' },
                { nome: 'Cashback', icone: 'percent' },
            ]
        };

        // Mapeamento de nomes de categorias padrão → ícones Lucide
        this.iconMap = {
            // Despesas
            'moradia': 'house',
            'alimentação': 'utensils',
            'transporte': 'car',
            'contas e serviços': 'lightbulb',
            'saúde': 'heart-pulse',
            'educação': 'graduation-cap',
            'vestuário': 'shirt',
            'lazer': 'clapperboard',
            'cartão de crédito': 'credit-card',
            'assinaturas': 'smartphone',
            'compras': 'shopping-cart',
            'outros gastos': 'coins',
            // Receitas
            'salário': 'briefcase',
            'freelance': 'laptop',
            'investimentos': 'trending-up',
            'bônus': 'gift',
            'vendas': 'banknote',
            'prêmios': 'trophy',
            'outras receitas': 'wallet',
        };

        // Mapeamento de cores para ícones de categorias
        this.iconColors = {
            'house': '#f97316', 'utensils': '#ef4444', 'car': '#3b82f6',
            'lightbulb': '#eab308', 'heart-pulse': '#ef4444', 'graduation-cap': '#6366f1',
            'shirt': '#ec4899', 'clapperboard': '#a855f7', 'credit-card': '#0ea5e9',
            'smartphone': '#6366f1', 'shopping-cart': '#f97316', 'coins': '#eab308',
            'briefcase': '#3b82f6', 'laptop': '#06b6d4', 'trending-up': '#22c55e',
            'gift': '#ec4899', 'banknote': '#22c55e', 'trophy': '#f59e0b',
            'wallet': '#14b8a6', 'tag': '#94a3b8', 'pie-chart': '#8b5cf6',
            'piggy-bank': '#ec4899', 'plane': '#0ea5e9', 'gamepad-2': '#a855f7',
            'baby': '#f472b6', 'dog': '#92400e', 'wrench': '#64748b',
            'church': '#6366f1', 'cigarette': '#64748b', 'dumbbell': '#ef4444',
            'music': '#a855f7', 'book-open': '#3b82f6', 'scissors': '#ec4899',
            'building-2': '#64748b', 'landmark': '#3b82f6', 'receipt': '#14b8a6',
            'calendar-check': '#22c55e', 'shield-check': '#22c55e'
        };

        this.syncMesFromHeader();
        this.init();
    }

    /**
     * Sincronizar mês/ano com o LukratoHeader global
     */
    syncMesFromHeader() {
        if (window.LukratoHeader?.getMonth) {
            const ym = window.LukratoHeader.getMonth(); // "2026-02"
            const [y, m] = ym.split('-').map(Number);
            this.mesSelecionado = m || (new Date().getMonth() + 1);
            this.anoSelecionado = y || new Date().getFullYear();
        } else {
            this.mesSelecionado = new Date().getMonth() + 1;
            this.anoSelecionado = new Date().getFullYear();
        }
    }

    /**
     * Inicializar manager
     */
    init() {
        this.attachEventListeners();
        this.loadAll();
    }

    /**
     * Processa APENAS ícones Lucide <i data-lucide> que ainda não foram convertidos em SVG.
     * Evita que lucide.createIcons() global re-processe SVGs existentes e corrompa o DOM.
     */
    _processNewIcons() {
        if (!window.lucide) return;

        // Seleciona apenas <i> com data-lucide (não SVGs já processados)
        const unprocessed = document.querySelectorAll('i[data-lucide]');
        if (unprocessed.length === 0) return;

        // Remove data-lucide dos SVGs já processados para protegê-los
        const processed = document.querySelectorAll('svg[data-lucide]');
        processed.forEach(svg => {
            svg.dataset.lucideProcessed = svg.dataset.lucide;
            svg.removeAttribute('data-lucide');
        });

        // Agora createIcons() só encontra os <i> novos
        lucide.createIcons();

        // Restaura data-lucide nos SVGs para manter compatibilidade
        processed.forEach(svg => {
            if (svg.dataset.lucideProcessed) {
                svg.setAttribute('data-lucide', svg.dataset.lucideProcessed);
                delete svg.dataset.lucideProcessed;
            }
        });
    }

    /**
     * Carregar tudo em paralelo
     */
    async loadAll() {
        const page = document.querySelector('.cat-page');
        const isFirstLoad = page && !page.classList.contains('is-ready');

        await Promise.all([this.loadCategorias(), this.loadOrcamentos()]);
        this.renderCategorias();

        // Na primeira carga, revela a página (remove visibility:hidden)
        if (isFirstLoad && page) {
            requestAnimationFrame(() => page.classList.add('is-ready'));
        }
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

        // Tipo toggle → atualizar sugestões
        document.querySelectorAll('input[name="tipo"]').forEach(radio => {
            radio.addEventListener('change', () => {
                this.renderSuggestions();
                this._processNewIcons();
            });
        });

        // Icon Picker — Create form
        const btnIconPicker = document.getElementById('btnIconPicker');
        const btnCloseIconPicker = document.getElementById('btnCloseIconPicker');
        const iconSearchInput = document.getElementById('iconSearchInput');

        if (btnIconPicker) {
            btnIconPicker.addEventListener('click', () => this.toggleIconPicker());
        }
        if (btnCloseIconPicker) {
            btnCloseIconPicker.addEventListener('click', () => this.closeIconPicker());
        }
        if (iconSearchInput) {
            iconSearchInput.addEventListener('input', (e) => this.filterIcons(e.target.value));
        }

        // Icon Picker — Edit modal
        const btnEditIconPicker = document.getElementById('btnEditIconPicker');
        const editIconSearchInput = document.getElementById('editIconSearchInput');

        if (btnEditIconPicker) {
            btnEditIconPicker.addEventListener('click', () => this.toggleEditIconPicker());
        }
        if (editIconSearchInput) {
            editIconSearchInput.addEventListener('input', (e) => this.filterEditIcons(e.target.value));
        }

        // Render sugestões iniciais
        this.renderSuggestions();
        this._processNewIcons();

        // Icon grids são carregados sob demanda (lazy) para evitar FOUC
        this._iconGridCreateReady = false;
        this._iconGridEditReady = false;

        // Escutar mudança de mês do header global
        document.addEventListener('lukrato:month-changed', (e) => {
            const ym = e.detail?.month; // "2026-02"
            if (ym) {
                const [y, m] = ym.split('-').map(Number);
                this.mesSelecionado = m;
                this.anoSelecionado = y;
                this.loadOrcamentos().then(() => this.renderCategorias());
            }
        });
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

            const baseUrl = this.getBaseUrl();
            const response = await fetch(`${baseUrl}api/categorias`);

            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }

            const result = await response.json();

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

            // Não renderizar aqui — loadAll() faz após ambas cargas
        } catch (error) {
            console.error('❌ Erro ao carregar categorias:', error);
            this.showError('Erro ao carregar categorias. Tente novamente.');
        }
    }

    /**
     * Carregar orçamentos do mês atual
     */
    async loadOrcamentos() {
        try {
            const mes = this.mesSelecionado;
            const ano = this.anoSelecionado;
            const baseUrl = this.getBaseUrl();
            const response = await fetch(`${baseUrl}api/financas/orcamentos?mes=${mes}&ano=${ano}`);
            if (!response.ok) return;
            const result = await response.json();
            if (result.success !== false && Array.isArray(result.data)) {
                this.orcamentos = result.data;
            }
        } catch (e) {
            console.error('Erro ao carregar orçamentos:', e);
        }
    }

    /**
     * Obter orçamento de uma categoria pelo ID
     */
    getOrcamento(categoriaId) {
        return this.orcamentos.find(o => Number(o.categoria_id) === Number(categoriaId)) || null;
    }

    /**
     * Formatar moeda
     */
    formatCurrency(val) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val || 0);
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

        // Preparar HTML antes de inserir no DOM (evita flash de conteúdo sem ícones)
        const receitasContainer = document.getElementById('receitasList');
        const despesasContainer = document.getElementById('despesasList');

        // Construir HTML em memória
        const receitasHtml = receitas.length === 0
            ? '<div class="empty-state"><i data-lucide="inbox"></i><p>Nenhuma categoria de receita cadastrada</p></div>'
            : receitas.map(cat => this.renderCategoriaItem(cat, 'receita')).join('');

        const despesasHtml = despesas.length === 0
            ? '<div class="empty-state"><i data-lucide="inbox"></i><p>Nenhuma categoria de despesa cadastrada</p></div>'
            : despesas.map(cat => this.renderCategoriaItem(cat, 'despesa')).join('');

        // Inserir tudo no DOM de uma vez
        receitasContainer.innerHTML = receitasHtml;
        despesasContainer.innerHTML = despesasHtml;

        // Atualizar sugestões (marca as já existentes)
        this.renderSuggestions();

        // Processar ícones Lucide APENAS nos elementos <i> não processados
        this._processNewIcons();
    }

    /**
     * Renderizar lista de despesas
     */
    renderListaDespesas(despesas) {
        const container = document.getElementById('despesasList');

        if (despesas.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i data-lucide="inbox"></i>
                    <p>Nenhuma categoria de despesa cadastrada</p>
                </div>
            `;
            return;
        }

        container.innerHTML = despesas.map(cat => this.renderCategoriaItem(cat, 'despesa')).join('');
    }

    /**
     * Renderizar item de categoria como card
     */
    renderCategoriaItem(categoria, tipo) {
        // Remover emoji se presente no nome (legacy)
        const displayName = categoria.nome.replace(/[\u{1F300}-\u{1F9FF}]\s*/gu, '').trim() || categoria.nome;

        // Prioridade: icone do banco → iconMap por nome → fallback 'tag'
        const lucideIcon = categoria.icone || this.iconMap[displayName.toLowerCase()] || 'tag';
        const iconColor = this.iconColors[lucideIcon] || '#f97316';
        const iconHtml = `<i data-lucide="${lucideIcon}" style="color:${iconColor}"></i>`;

        // Seção de orçamento (apenas despesas)
        let budgetHtml = '';
        if (tipo === 'despesa') {
            const orc = this.getOrcamento(categoria.id);
            if (orc) {
                const pct = Math.round(orc.percentual || 0);
                const statusClass = pct >= 100 ? 'over' : pct >= 80 ? 'warn' : 'ok';
                budgetHtml = `
                    <div class="cat-card-budget has-budget ${statusClass}" onclick="categoriasManager.editarOrcamento(${categoria.id}, event)" title="Clique para editar orçamento">
                        <div class="cat-budget-info">
                            <span class="cat-budget-text">${this.formatCurrency(orc.gasto_real)} / ${this.formatCurrency(orc.valor_limite)}</span>
                            <span class="cat-budget-pct ${statusClass}">${pct}%</span>
                        </div>
                        <div class="cat-budget-bar">
                            <div class="cat-budget-fill ${statusClass}" style="width: ${Math.min(pct, 100)}%"></div>
                        </div>
                    </div>`;
            } else {
                budgetHtml = `
                    <div class="cat-card-budget no-budget" onclick="categoriasManager.editarOrcamento(${categoria.id}, event)" title="Defina quanto deseja gastar no máximo por mês nesta categoria">
                        <i data-lucide="pie-chart"></i>
                        <span>Definir orçamento mensal</span>
                    </div>`;
            }
        }

        return `
            <div class="cat-card ${tipo}" data-id="${categoria.id}">
                <div class="cat-card-header">
                    <div class="cat-card-icon ${tipo}">
                        ${iconHtml}
                    </div>
                    <span class="cat-card-name">${this.escapeHtml(displayName)}</span>
                    <div class="cat-card-actions">
                        <button type="button" class="cat-card-btn edit" 
                                onclick="categoriasManager.editarCategoria(${categoria.id})"
                                title="Editar">
                            <i data-lucide="pen"></i>
                        </button>
                        <button type="button" class="cat-card-btn delete" 
                                onclick="categoriasManager.excluirCategoria(${categoria.id})"
                                title="Excluir">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
                ${budgetHtml}
            </div>
        `;
    }

    /**
     * Editar/criar orçamento via Modal Bootstrap
     */
    editarOrcamento(categoriaId, event) {
        if (event) event.stopPropagation();

        const cat = this.categorias.find(c => c.id === categoriaId);
        if (!cat) return;

        const orc = this.getOrcamento(categoriaId);
        const currentValue = orc ? parseFloat(orc.valor_limite) : 0;

        // Preencher modal
        document.getElementById('orcCategoriaNome').textContent = cat.nome;
        const gastoEl = document.getElementById('orcGastoAtual');
        const gastoValorEl = document.getElementById('orcGastoValor');
        const btnRemover = document.getElementById('btnRemoverOrcamento');
        const btnText = document.getElementById('btnOrcText');
        const inputValor = document.getElementById('orcValorLimite');
        const alertEl = document.getElementById('orcAlertError');

        // Reset
        alertEl.classList.add('d-none');
        inputValor.value = currentValue > 0 ? this.formatOrcamentoInput(currentValue) : '';

        if (orc) {
            gastoEl.classList.remove('d-none');
            gastoValorEl.textContent = this.formatCurrency(orc.gasto_real);
            btnRemover.classList.remove('d-none');
            btnText.textContent = 'Atualizar';
        } else {
            gastoEl.classList.add('d-none');
            btnRemover.classList.add('d-none');
            btnText.textContent = 'Definir';
        }

        // Salvar categoriaId no form
        const form = document.getElementById('formOrcamento');
        form.dataset.categoriaId = categoriaId;

        // Eventos (remover anteriores para evitar duplicatas)
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);

        // Máscara de moeda no input
        const newInput = newForm.querySelector('#orcValorLimite');
        newInput.addEventListener('input', () => {
            this.applyCurrencyMask(newInput);
        });

        newForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const raw = document.getElementById('orcValorLimite').value;
            const val = this.parseCurrencyInput(raw);
            const errEl = document.getElementById('orcAlertError');
            if (!val || isNaN(val) || val <= 0) {
                errEl.textContent = 'Informe um valor maior que zero';
                errEl.classList.remove('d-none');
                return;
            }
            errEl.classList.add('d-none');
            await this.salvarOrcamento(parseInt(newForm.dataset.categoriaId), val);
            bootstrap.Modal.getInstance(document.getElementById('modalOrcamento'))?.hide();
        });

        // Botão remover
        const newBtnRemover = document.getElementById('btnRemoverOrcamento');
        const clonedBtn = newBtnRemover.cloneNode(true);
        newBtnRemover.parentNode.replaceChild(clonedBtn, newBtnRemover);
        clonedBtn.addEventListener('click', async () => {
            if (orc) {
                await this.removerOrcamento(orc.id);
                bootstrap.Modal.getInstance(document.getElementById('modalOrcamento'))?.hide();
            }
        });

        // Re-apontar o botão submit ao novo form
        document.getElementById('btnSalvarOrcamento').setAttribute('form', 'formOrcamento');

        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('modalOrcamento'));
        modal.show();

        // Focar no input após abrir
        document.getElementById('modalOrcamento').addEventListener('shown.bs.modal', () => {
            document.getElementById('orcValorLimite').focus();
        }, { once: true });
    }

    /**
     * Salvar orçamento via API (usa o mesmo endpoint de financas)
     */
    async salvarOrcamento(categoriaId, valorLimite) {
        try {
            const mes = this.mesSelecionado;
            const ano = this.anoSelecionado;
            const baseUrl = this.getBaseUrl();

            const response = await fetch(`${baseUrl}api/financas/orcamentos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify({
                    categoria_id: categoriaId,
                    valor_limite: valorLimite,
                    mes: mes,
                    ano: ano
                })
            });

            if (!response.ok) {
                throw new Error('Erro ao salvar orçamento');
            }

            this.showSuccess('Limite atualizado!');
            await this.loadOrcamentos();
            this.renderCategorias();
        } catch (e) {
            console.error('Erro ao salvar orçamento:', e);
            this.showError('Erro ao salvar limite. Tente novamente.');
        }
    }

    /**
     * Remover orçamento via API
     */
    async removerOrcamento(orcamentoId) {
        try {
            const baseUrl = this.getBaseUrl();
            const response = await fetch(`${baseUrl}api/financas/orcamentos/${orcamentoId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                }
            });

            if (!response.ok) {
                throw new Error('Erro ao remover orçamento');
            }

            this.showSuccess('Limite removido!');
            await this.loadOrcamentos();
            this.renderCategorias();
        } catch (e) {
            console.error('Erro ao remover orçamento:', e);
            this.showError('Erro ao remover limite. Tente novamente.');
        }
    }

    /**
     * Formatar valor para exibição no input (1500.50 → "1.500,50")
     */
    formatOrcamentoInput(value) {
        const num = parseFloat(value);
        if (isNaN(num)) return '';
        return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * Aplicar máscara de moeda no input (aceita só números, formata automaticamente)
     */
    applyCurrencyMask(input) {
        // Remove tudo que não é dígito
        let digits = input.value.replace(/\D/g, '');
        // Remove zeros à esquerda (mantém pelo menos 1)
        digits = digits.replace(/^0+(?=\d)/, '');
        if (!digits) {
            input.value = '';
            return;
        }
        // Converter para centavos → reais
        const value = parseInt(digits) / 100;
        input.value = value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * Parse valor formatado BR para float ("1.500,50" → 1500.50)
     */
    parseCurrencyInput(str) {
        if (!str) return 0;
        const cleaned = str.replace(/\./g, '').replace(',', '.');
        return parseFloat(cleaned);
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // =========================================================================
    // ICON PICKER — CREATE FORM
    // =========================================================================

    /**
     * Renderizar grid de ícones em um container
     */
    renderIconGrid(containerId, onSelect) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = this.availableIcons.map(icon => `
            <button type="button" class="icon-pick-item" data-icon="${icon.name}" title="${icon.name}">
                <i data-lucide="${icon.name}"></i>
            </button>
        `).join('');

        // Event delegation (apenas uma vez por container)
        if (!container._lkDelegated) {
            container.addEventListener('click', (e) => {
                const item = e.target.closest('.icon-pick-item');
                if (!item) return;
                onSelect(item.dataset.icon);
            });
            container._lkDelegated = true;
        }

        this._processNewIcons();
    }

    /**
     * Toggle icon picker drawer
     */
    toggleIconPicker() {
        const drawer = document.getElementById('iconPickerDrawer');
        if (!drawer) return;

        // Lazy-load icon grid na primeira abertura
        if (!this._iconGridCreateReady) {
            this.renderIconGrid('iconPickerGrid', (icon) => this.selectIcon(icon));
            this._iconGridCreateReady = true;
        }

        drawer.classList.toggle('open');
        if (drawer.classList.contains('open')) {
            const input = document.getElementById('iconSearchInput');
            if (input) { input.value = ''; input.focus(); }
            this.filterIcons('');
            this.highlightSelectedIcon('iconPickerGrid', this.selectedIcon);
        }
    }

    closeIconPicker() {
        const drawer = document.getElementById('iconPickerDrawer');
        if (drawer) drawer.classList.remove('open');
    }

    /**
     * Selecionar ícone no form de criação
     */
    selectIcon(iconName) {
        this.selectedIcon = iconName;
        document.getElementById('catIcone').value = iconName;

        // Atualizar preview
        this.updateIconPreview(iconName);

        // Highlight
        this.highlightSelectedIcon('iconPickerGrid', iconName);

        // Fechar picker
        this.closeIconPicker();
    }

    /**
     * Atualizar preview do ícone no create-icon-area
     */
    updateIconPreview(iconName) {
        const inner = document.querySelector('#iconPreviewRing .create-icon-inner');
        if (!inner) return;

        // Recria o <i> para que Lucide processe corretamente
        inner.innerHTML = `<i data-lucide="${iconName}" class="create-main-icon" id="iconPreview"></i>`;
        this._processNewIcons();

        // Efeito visual de troca
        const ring = document.getElementById('iconPreviewRing');
        if (ring) {
            ring.style.transform = 'scale(1.1)';
            setTimeout(() => ring.style.transform = '', 300);
        }
    }

    /**
     * Highlight icon selecionado no grid
     */
    highlightSelectedIcon(containerId, iconName) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.querySelectorAll('.icon-pick-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.icon === iconName);
        });
    }

    /**
     * Filtrar ícones por busca
     */
    filterIcons(query) {
        const container = document.getElementById('iconPickerGrid');
        if (!container) return;
        const q = query.toLowerCase().trim();

        container.querySelectorAll('.icon-pick-item').forEach(item => {
            if (!q) {
                item.style.display = '';
                return;
            }
            const iconName = item.dataset.icon;
            const iconData = this.availableIcons.find(i => i.name === iconName);
            const searchText = `${iconName} ${iconData?.label || ''}`.toLowerCase();
            item.style.display = searchText.includes(q) ? '' : 'none';
        });
    }

    // =========================================================================
    // ICON PICKER — EDIT MODAL
    // =========================================================================

    toggleEditIconPicker() {
        const panel = document.getElementById('editIconPickerPanel');
        if (!panel) return;

        // Lazy-load icon grid na primeira abertura
        if (!this._iconGridEditReady) {
            this.renderIconGrid('editIconPickerGrid', (icon) => this.selectEditIcon(icon));
            this._iconGridEditReady = true;
        }

        panel.classList.toggle('d-none');
        if (!panel.classList.contains('d-none')) {
            const input = document.getElementById('editIconSearchInput');
            if (input) { input.value = ''; input.focus(); }
            this.filterEditIcons('');
            this.highlightSelectedIcon('editIconPickerGrid', this.editSelectedIcon);
        }
    }

    selectEditIcon(iconName) {
        this.editSelectedIcon = iconName;
        document.getElementById('editCategoriaIcone').value = iconName;

        // Atualizar preview
        const preview = document.getElementById('editIconPreview');
        if (preview) {
            preview.innerHTML = `<i data-lucide="${iconName}"></i>`;
            this._processNewIcons();
        }

        // Highlight
        this.highlightSelectedIcon('editIconPickerGrid', iconName);

        // Fechar panel
        const panel = document.getElementById('editIconPickerPanel');
        if (panel) panel.classList.add('d-none');
    }

    filterEditIcons(query) {
        const container = document.getElementById('editIconPickerGrid');
        if (!container) return;
        const q = query.toLowerCase().trim();

        container.querySelectorAll('.icon-pick-item').forEach(item => {
            if (!q) {
                item.style.display = '';
                return;
            }
            const iconName = item.dataset.icon;
            const iconData = this.availableIcons.find(i => i.name === iconName);
            const searchText = `${iconName} ${iconData?.label || ''}`.toLowerCase();
            item.style.display = searchText.includes(q) ? '' : 'none';
        });
    }

    // =========================================================================
    // SUGESTÕES RÁPIDAS
    // =========================================================================

    /**
     * Renderizar sugestões baseadas no tipo selecionado
     */
    renderSuggestions() {
        const container = document.getElementById('suggestionsChips');
        if (!container) return;

        const tipo = document.querySelector('input[name="tipo"]:checked')?.value || 'despesa';
        const items = this.suggestions[tipo] || [];

        // Verificar quais já existem
        const existingNames = this.categorias
            .filter(c => c.tipo === tipo)
            .map(c => c.nome.toLowerCase());

        container.innerHTML = items.map(item => {
            const isUsed = existingNames.includes(item.nome.toLowerCase());
            return `
                <button type="button" class="suggestion-chip ${isUsed ? 'used' : ''}"
                    data-nome="${item.nome}" data-icone="${item.icone}" data-tipo="${tipo}"
                    ${isUsed ? 'disabled title="Já criada"' : ''}>
                    <i data-lucide="${item.icone}"></i>
                    <span>${item.nome}</span>
                </button>
            `;
        }).join('');

        // Event listeners
        container.querySelectorAll('.suggestion-chip:not(.used)').forEach(chip => {
            chip.addEventListener('click', () => {
                const nome = chip.dataset.nome;
                const icone = chip.dataset.icone;

                // Preencher formulário
                const nomeInput = document.getElementById('catNome');
                if (nomeInput) nomeInput.value = nome;

                // Selecionar ícone
                this.selectIcon(icone);

                // Efeito visual no chip
                chip.style.transform = 'scale(0.95)';
                chip.style.background = 'color-mix(in srgb, var(--color-primary) 20%, transparent)';
                setTimeout(() => {
                    chip.style.transform = '';
                    chip.style.background = '';
                }, 300);

                // Focus no botão de submit
                const submitBtn = document.querySelector('.create-submit-btn');
                if (submitBtn) submitBtn.focus();
            });
        });

    }

    /**
     * Reset form state (icon, suggestions)
     */
    resetCreateForm() {
        this.selectedIcon = '';
        const catIcone = document.getElementById('catIcone');
        if (catIcone) catIcone.value = '';

        // Recriar preview do ícone padrão
        const inner = document.querySelector('#iconPreviewRing .create-icon-inner');
        if (inner) {
            inner.innerHTML = `<i data-lucide="tag" class="create-main-icon" id="iconPreview"></i>`;
        }

        this.closeIconPicker();
        this.renderSuggestions();

        // Processar ícones novos
        this._processNewIcons();
    }

    /**
     * Criar nova categoria
     */
    async handleNovaCategoria(form) {
        try {
            const formData = new FormData(form);
            const data = {
                nome: formData.get('nome'),
                tipo: formData.get('tipo'),
                icone: formData.get('icone') || null
            };

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

            // 🎮 GAMIFICAÇÃO: Exibir conquistas se houver
            if (result.data?.gamification?.achievements && Array.isArray(result.data.gamification.achievements)) {
                if (typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(result.data.gamification.achievements);
                }
            }

            this.showSuccess('Categoria criada com sucesso!');
            form.reset();
            this.resetCreateForm();

            // Recarregar tudo
            await this.loadAll();

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

        // Preencher ícone
        const currentIcon = categoria.icone || 'tag';
        this.editSelectedIcon = currentIcon;
        document.getElementById('editCategoriaIcone').value = currentIcon;
        const editPreview = document.getElementById('editIconPreview');
        if (editPreview) {
            editPreview.innerHTML = `<i data-lucide="${currentIcon}"></i>`;
            this._processNewIcons();
        }
        // Esconder panel de ícones
        const editIconPanel = document.getElementById('editIconPickerPanel');
        if (editIconPanel) editIconPanel.classList.add('d-none');
        this.highlightSelectedIcon('editIconPickerGrid', currentIcon);

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
                tipo: formData.get('tipo'),
                icone: formData.get('icone') || null
            };


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

            this.showSuccess('Categoria atualizada com sucesso!');

            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditCategoria'));
            modal.hide();

            // Recarregar tudo
            await this.loadAll();

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


            this.showSuccess('Categoria excluída com sucesso!');

            // Recarregar tudo
            await this.loadAll();

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
