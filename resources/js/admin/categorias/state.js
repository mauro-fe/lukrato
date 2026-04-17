/**
 * ============================================================================
 * LUKRATO — Categorias / State & Config
 * ============================================================================
 * CONFIG, STATE, data constants (icons, suggestions, color maps),
 * Modules registry, and utility helpers.
 * All categorias modules import from here.
 * ============================================================================
 */

import { formatMoney, parseMoney, escapeHtml } from '../shared/utils.js';
import { toastSuccess, toastError } from '../shared/ui.js';
import { getBaseUrl, getCSRFToken } from '../shared/api.js';

// Re-export shared utilities for convenience
export { formatMoney, parseMoney, escapeHtml, getBaseUrl, getCSRFToken };
export { toastSuccess, toastError };

// ─── Modules Registry ────────────────────────────────────────────────────────
export const Modules = {};

// ─── CONFIG ──────────────────────────────────────────────────────────────────

export const CONFIG = {
    BASE_URL: (() => {
        const base = getBaseUrl();
        return base.replace(/\/?$/, '/');
    })(),
};

// ─── Mutable State ───────────────────────────────────────────────────────────

export const STATE = {
    categorias: [],
    orcamentos: [],
    categoriaEmEdicao: null,
    selectedIcon: '',        // ícone selecionado no form de criação
    editSelectedIcon: '',    // ícone selecionado no modal de edição
    mesSelecionado: new Date().getMonth() + 1,
    anoSelecionado: new Date().getFullYear(),
    _iconGridCreateReady: false,
    _iconGridEditReady: false,
    // Subcategorias
    subcategoriasCache: {},          // { [categoriaId]: subcategoria[] }
    expandedCategorias: new Set(),   // IDs das categorias com acordeão aberto
    subcategoriaEmEdicao: null,      // subcategoria sendo editada no modal
    editSubcategoriaIcon: '',        // ícone selecionado no form de subcategoria (modal)
    inlineSubcategoriaIcon: {},      // { [categoriaId]: iconName } — ícone selecionado no form inline
    filterQuery: '',                   // texto de busca para filtrar categorias/subcategorias
    editingSubcatId: null,             // ID da subcategoria sendo editada inline
    searchMatches: {},                 // { [categoriaId]: { categoryMatch: bool, subMatches: string[] } }
    lastLoadError: null,
    isLoading: false,
    isRefreshing: false,
};

// ─── Available Icons (grouped by context / search label) ─────────────────────

export const ICON_GROUPS = [
    { label: 'Casa & Moradia', icons: ['house', 'building-2', 'key', 'sofa'] },
    { label: 'Alimentação', icons: ['utensils', 'coffee', 'apple', 'wine', 'pizza', 'sandwich', 'beef', 'cookie', 'cup-soda'] },
    { label: 'Transporte', icons: ['car', 'bus', 'bike', 'plane', 'fuel', 'train-front'] },
    { label: 'Contas & Serviços', icons: ['lightbulb', 'droplets', 'flame', 'wifi', 'smartphone', 'tv', 'receipt'] },
    { label: 'Saúde', icons: ['heart-pulse', 'pill', 'stethoscope', 'dumbbell', 'activity'] },
    { label: 'Educação', icons: ['graduation-cap', 'book-open', 'pencil', 'library'] },
    { label: 'Trabalho & Renda', icons: ['briefcase', 'laptop', 'building', 'wallet', 'banknote', 'piggy-bank', 'landmark', 'calculator'] },
    { label: 'Patrimônio', icons: ['trending-up', 'bar-chart-3', 'coins', 'bitcoin'] },
    { label: 'Lazer', icons: ['clapperboard', 'music', 'gamepad-2', 'palette', 'camera', 'headphones', 'ticket'] },
    { label: 'Compras', icons: ['shopping-cart', 'shopping-bag', 'shirt', 'scissors', 'gem', 'gift'] },
    { label: 'Finanças', icons: ['credit-card', 'percent', 'shield-check', 'calendar-check', 'pie-chart'] },
    { label: 'Família & Pessoal', icons: ['baby', 'dog', 'cat', 'heart', 'church', 'users'] },
    { label: 'Outros', icons: ['trophy', 'wrench', 'zap', 'star', 'tag', 'archive', 'package', 'map-pin', 'globe', 'umbrella', 'cigarette', 'bed'] },
];

export const AVAILABLE_ICONS = [
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
    { name: 'trending-up', label: 'crescimento rendimento lucro' },
    { name: 'bar-chart-3', label: 'gráfico patrimônio desempenho' },
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
    { name: 'pie-chart', label: 'planejamento divisão' },
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

// ─── Suggestions (quick-add by type) ────────────────────────────────────────

export const SUGGESTIONS = {
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
        { nome: 'Vendas', icone: 'banknote' },
        { nome: 'Bônus', icone: 'gift' },
        { nome: 'Prêmios', icone: 'trophy' },
        { nome: 'Outras Receitas', icone: 'wallet' },
        { nome: 'Aluguel', icone: 'key' },
        { nome: 'Cashback', icone: 'percent' },
    ]
};

// ─── Icon Map (default category name → Lucide icon) ─────────────────────────

export const ICON_MAP = {
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
    'bônus': 'gift',
    'vendas': 'banknote',
    'prêmios': 'trophy',
    'outras receitas': 'wallet',
};

// ─── Icon Colors ─────────────────────────────────────────────────────────────

export const ICON_COLORS = {
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

// ─── Utility Helpers ─────────────────────────────────────────────────────────

export const Utils = {
    /**
     * Obter CSRF Token (fallback via input hidden)
     */
    getCsrfToken() {
        const token = getCSRFToken();
        if (token) return token;
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    },

    /**
     * Formatar moeda (alias for formatMoney)
     */
    formatCurrency(val) {
        return formatMoney(val || 0);
    },

    /**
     * Formatar valor para exibição no input (1500.50 → "1.500,50")
     */
    formatOrcamentoInput(value) {
        const num = parseFloat(value);
        if (isNaN(num)) return '';
        return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

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
    },

    /**
     * Parse valor formatado BR para float ("1.500,50" → 1500.50)
     */
    parseCurrencyInput(str) {
        if (!str) return 0;
        const cleaned = str.replace(/\./g, '').replace(',', '.');
        return parseFloat(cleaned);
    },

    /**
     * Sincronizar mês/ano com o LukratoHeader global
     */
    syncMesFromHeader() {
        if (window.LukratoHeader?.getMonth) {
            const ym = window.LukratoHeader.getMonth(); // "2026-02"
            const [y, m] = ym.split('-').map(Number);
            STATE.mesSelecionado = m || (new Date().getMonth() + 1);
            STATE.anoSelecionado = y || new Date().getFullYear();
        } else {
            STATE.mesSelecionado = new Date().getMonth() + 1;
            STATE.anoSelecionado = new Date().getFullYear();
        }
    },

    /**
     * Processa APENAS ícones Lucide <i data-lucide> que ainda não foram convertidos em SVG.
     * Evita que lucide.createIcons() global re-processe SVGs existentes e corrompa o DOM.
     */
    processNewIcons() {
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
    },
};
