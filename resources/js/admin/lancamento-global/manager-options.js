import { resolveCategorySubcategoriesEndpoint } from '../api/endpoints/finance.js';

export function attachLancamentoGlobalOptionsMethods(ManagerClass, dependencies) {
    const {
        escapeHtml,
        apiGet,
        sortByLabel,
        _sugerirCategoriaIA,
    } = dependencies;

    Object.assign(ManagerClass.prototype, {
    preencherCartoes(isEstorno = false) {
        const select = document.getElementById('globalLancamentoCartaoCredito');
        if (!select) return;

        this.isEstornoCartao = isEstorno;
        const optionVazio = isEstorno
            ? '<option value="">Selecione o cartão</option>'
            : '<option value="">Não usar cartão (débito na conta)</option>';

        if (!Array.isArray(this.cartoes)) this.cartoes = [];
        if (this.cartoes.length === 0) {
            select.innerHTML = optionVazio;
            this.syncEnhancedSelects();
            return;
        }

        const cartoesAtivos = this.cartoes.filter(c => c.ativo);
        const optionsCartoes = cartoesAtivos
            .map(c => {
                const nomeCartao = escapeHtml(c.nome_cartao || c.bandeira || 'Cartao');
                const ultimosDigitos = escapeHtml(String(c.ultimos_digitos || ''));
                return `<option value="${c.id}">${nomeCartao} &bull;&bull;&bull;&bull; ${ultimosDigitos}</option>`;
            })
            .join('');
        select.innerHTML = optionVazio + optionsCartoes;

        const faturaGroup = document.getElementById('globalFaturaEstornoGroup');
        if (faturaGroup) faturaGroup.style.display = 'none';
        this.syncEnhancedSelects();
    },

    preencherCategorias(tipo) {
        const select = document.getElementById('globalLancamentoCategoria');
        if (!select) return;
        if (!Array.isArray(this.categorias)) this.categorias = [];

        if (this.categorias.length === 0) {
            select.innerHTML = '<option value="">Sem categoria</option>';
            this.syncEnhancedSelects();
            return;
        }

        const categoriasFiltradas = this.categorias.filter(c => c.tipo === tipo);
        select.innerHTML = '<option value="">Sem categoria</option>';
        categoriasFiltradas.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.nome;
            select.appendChild(option);
        });

        // Reset subcategoria ao trocar categorias
        this.resetSubcategoriaSelect();

        // Listener cascata: ao trocar categoria -> preencher subcategorias
        if (!select.dataset.subcatListenerAttached) {
            select.dataset.subcatListenerAttached = '1';
            select.addEventListener('change', () => this.preencherSubcategorias(select.value));
        }

        this.syncEnhancedSelects();
    },

    async preencherSubcategorias(categoriaId) {
        const select = document.getElementById('globalLancamentoSubcategoria');
        const group = document.getElementById('globalSubcategoriaGroup');
        if (!select) return;

        if (!categoriaId) {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.style.display = 'none';
            this.syncEnhancedSelects();
            return;
        }

        try {
            const json = await apiGet(resolveCategorySubcategoriesEndpoint(categoriaId));
            const rawSubs = json?.data?.subcategorias ?? (Array.isArray(json?.data) ? json.data : []);
            const subs = sortByLabel(Array.isArray(rawSubs) ? rawSubs : [], (sub) => sub?.nome || '');

            select.innerHTML = '<option value="">Sem subcategoria</option>';
            subs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.nome;
                select.appendChild(opt);
            });

            if (group) group.style.display = subs.length > 0 ? 'block' : 'none';
        } catch {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.style.display = 'none';
        }

        this.syncEnhancedSelects();
    },

    resetSubcategoriaSelect() {
        const select = document.getElementById('globalLancamentoSubcategoria');
        if (select) select.innerHTML = '<option value="">Sem subcategoria</option>';
        this.syncEnhancedSelects();
    },

    onCartaoEstornoChange() {
        const cartaoSelect = document.getElementById('globalLancamentoCartaoCredito');
        const faturaGroup = document.getElementById('globalFaturaEstornoGroup');
        if (!cartaoSelect || !faturaGroup) return;
        if (!this.isEstornoCartao) { faturaGroup.style.display = 'none'; return; }
        const cartaoId = cartaoSelect.value;
        if (!cartaoId) { faturaGroup.style.display = 'none'; return; }
        faturaGroup.style.display = 'block';
        this.carregarFaturasEstorno(cartaoId);
    },

    carregarFaturasEstorno(cartaoId) {
        const faturaSelect = document.getElementById('globalLancamentoFaturaEstorno');
        if (!faturaSelect) return;
        faturaSelect.innerHTML = '<option value="">Carregando...</option>';
        this.syncEnhancedSelects();

        const cartao = this.cartoes.find(c => c.id == cartaoId);
        if (!cartao) {
            this.syncEnhancedSelects();
        }
        if (!cartao) { faturaSelect.innerHTML = '<option value="">Erro ao carregar cartão</option>'; return; }

        const hoje = new Date();
        const mesAtual = hoje.getMonth() + 1;
        const anoAtual = hoje.getFullYear();
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        let options = '';
        for (let offset = -3; offset <= 5; offset++) {
            let mes = mesAtual + offset;
            let ano = anoAtual;
            if (mes < 1) { mes += 12; ano--; }
            else if (mes > 12) { mes -= 12; ano++; }
            const valor = `${ano}-${String(mes).padStart(2, '0')}`;
            const nomeMes = meses[mes - 1];
            const label = offset < 0 ? `${nomeMes}/${ano} (anterior)` : (offset === 0 ? `${nomeMes}/${ano} (atual)` : `${nomeMes}/${ano}`);
            options += `<option value="${valor}" ${offset === 0 ? 'selected' : ''}>${label}</option>`;
        }
        faturaSelect.innerHTML = options;
        this.syncEnhancedSelects();
    },

    async sugerirCategoriaIA() {
        await _sugerirCategoriaIA({
            descricaoInputId: 'globalLancamentoDescricao',
            categoriaSelectId: 'globalLancamentoCategoria',
            subcategoriaSelectId: 'globalLancamentoSubcategoria',
            subcategoriaGroupId: 'globalSubcategoriaGroup',
            btnId: 'btnGlobalAiSuggestCategoria',
            notify: (msg, type) => {
                const icons = { success: 'success', warning: 'warning', error: 'error' };
                Swal.fire({
                    icon: icons[type] || 'info',
                    title: type === 'error' ? 'Erro' : 'IA',
                    text: msg,
                    ...(type === 'success' ? { timer: 2000, showConfirmButton: false } : {}),
                    customClass: { container: 'swal-above-modal' },
                });
            },
        });
        this.schedulePlanningAlertsRender();
    }
    });
}
