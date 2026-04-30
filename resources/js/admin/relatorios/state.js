/**
 * ============================================================================
 * LUKRATO — Relatórios / State & Config
 * ============================================================================
 * Shared state, configuration, and page-specific utilities.
 * All relatórios modules import from here.
 * ============================================================================
 */

import { getBaseUrl } from '../shared/api.js';
import { formatMoney } from '../shared/utils.js';
import { refreshIcons } from '../shared/ui.js';

// Re-export shared utilities for convenience
export { formatMoney, refreshIcons };

// ─── PAYWALL ─────────────────────────────────────────────────────────────────

export const PAYWALL_MESSAGE = 'Relatórios são exclusivos do plano Pro.';

// ─── CONFIG ──────────────────────────────────────────────────────────────────

export const CONFIG = {
    BASE_URL: getBaseUrl(),

    CHART_COLORS: [
        '#E67E22', '#2C3E50', '#2ECC71', '#F39C12',
        '#9B59B6', '#1ABC9C', '#E74C3C', '#3498DB'
    ],

    /** Default timeout for API requests (ms) */
    FETCH_TIMEOUT: 30000,

    VIEWS: {
        CATEGORY: 'category',
        BALANCE: 'balance',
        COMPARISON: 'comparison',
        ACCOUNTS: 'accounts',
        CARDS: 'cards',
        EVOLUTION: 'evolution',
        ANNUAL_SUMMARY: 'annual_summary',
        ANNUAL_CATEGORY: 'annual_category'
    }
};

export const YEARLY_VIEWS = new Set([
    CONFIG.VIEWS.ANNUAL_SUMMARY,
    CONFIG.VIEWS.ANNUAL_CATEGORY
]);

export const TYPE_OPTIONS = {
    [CONFIG.VIEWS.CATEGORY]: [
        { value: 'despesas_por_categoria', label: 'Despesas por categoria' },
        { value: 'receitas_por_categoria', label: 'Receitas por categoria' }
    ],
    [CONFIG.VIEWS.ANNUAL_CATEGORY]: [
        { value: 'despesas_anuais_por_categoria', label: 'Despesas anuais por categoria' },
        { value: 'receitas_anuais_por_categoria', label: 'Receitas anuais por categoria' }
    ]
};

export const STORAGE_KEYS = {
    ACTIVE_SECTION: 'rel_active_section',
    ACTIVE_VIEW: 'rel_active_view',
    CATEGORY_TYPE: 'rel_category_type',
    ANNUAL_CATEGORY_TYPE: 'rel_annual_category_type'
};

export const SECTION_META = {
    overview: {
        kicker: 'Painel consolidado',
        title: 'Leia seu mês com contexto',
        description: 'Veja seu pulso financeiro, identifique sinais importantes e acompanhe a evolução do período em um resumo rápido.'
    },
    relatorios: {
        kicker: 'Relatório ativo',
        title: 'Transforme lançamentos em decisão',
        description: 'Explore seus números por categoria, conta, cartão e evolução para descobrir onde agir.'
    },
    insights: {
        kicker: 'Leitura automática',
        title: 'Insights que ajudam a agir',
        description: 'Receba sinais claros sobre gastos, saldo, concentrações e oportunidades sem precisar interpretar tudo manualmente.'
    },
    comparativos: {
        kicker: 'Comparação temporal',
        title: 'Compare e ajuste sua rota',
        description: 'Entenda o que melhorou, piorou ou estagnou em relação ao mês e ao ano anteriores.'
    }
};

export const VIEW_META = {
    [CONFIG.VIEWS.CATEGORY]: {
        title: 'Categorias do período',
        description: 'Encontre rapidamente onde seu dinheiro está concentrado por categoria.'
    },
    [CONFIG.VIEWS.BALANCE]: {
        title: 'Saldo diário',
        description: 'Acompanhe como seu caixa evolui ao longo do período.'
    },
    [CONFIG.VIEWS.COMPARISON]: {
        title: 'Receitas x despesas',
        description: 'Compare entradas e saídas para entender pressão ou folga no caixa.'
    },
    [CONFIG.VIEWS.ACCOUNTS]: {
        title: 'Desempenho por conta',
        description: 'Descubra quais contas concentram mais entradas e saídas.'
    },
    [CONFIG.VIEWS.CARDS]: {
        title: 'Saúde dos cartões',
        description: 'Monitore faturas, uso de limite e sinais de atenção nos cartões.'
    },
    [CONFIG.VIEWS.EVOLUTION]: {
        title: 'Evolução em 12 meses',
        description: 'Observe tendência, sazonalidade e ritmo financeiro ao longo do último ano.'
    },
    [CONFIG.VIEWS.ANNUAL_SUMMARY]: {
        title: 'Resumo anual',
        description: 'Compare mês a mês como receitas, despesas e saldo se comportaram no ano.'
    },
    [CONFIG.VIEWS.ANNUAL_CATEGORY]: {
        title: 'Categorias do ano',
        description: 'Veja quais categorias dominaram seu ano e onde houve maior concentração.'
    }
};

// ─── UTILITY FUNCTIONS ───────────────────────────────────────────────────────

export function computeInitialMonth() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

export const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, function (match) {
    const replacements = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    };

    return replacements[match] ?? match;
});

/**
 * Validate a hex color string. Returns safe fallback if invalid.
 */
export function safeColor(color, fallback = '#cccccc') {
    return /^#[0-9A-Fa-f]{6}$/.test(color) ? color : fallback;
}

/**
 * Get a chart color by index, generating new ones via HSL rotation when the
 * fixed palette is exhausted.
 */
export function getChartColor(index) {
    if (index < CONFIG.CHART_COLORS.length) {
        return CONFIG.CHART_COLORS[index];
    }
    const hue = (index * 137.508) % 360; // golden-angle spread
    return `hsl(${Math.round(hue)}, 65%, 50%)`;
}

// ─── STATE ───────────────────────────────────────────────────────────────────

export const STATE = {
    activeSection: 'overview',
    currentView: CONFIG.VIEWS.CATEGORY,
    categoryType: 'despesas_por_categoria',
    annualCategoryType: 'despesas_anuais_por_categoria',
    currentMonth: computeInitialMonth(),
    currentAccount: null,
    chart: null,
    accounts: [],
    accessRestricted: false,
    lastReportError: null,
    // Subcategory drill-down state
    activeDrilldown: null,       // cat_id of expanded category (or null)
    reportDetails: null,         // details[] from API response (PRO only)
};

// ─── UTILITIES ───────────────────────────────────────────────────────────────

export const Utils = {
    getCurrentMonth: computeInitialMonth,

    formatCurrency(value) {
        return formatMoney(value);
    },

    formatMonthLabel(yearMonth) {
        const [year, month] = yearMonth.split('-');
        const date = new Date(year, month - 1);
        return date.toLocaleDateString('pt-BR', {
            month: 'long',
            year: 'numeric'
        });
    },

    addMonths(yearMonth, delta) {
        const [year, month] = yearMonth.split('-').map(Number);
        const date = new Date(year, month - 1 + delta);
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    },

    hexToRgba(hex, alpha = 0.25) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },

    /**
     * Generate lighter/darker shades of a hex color for subcategory palette.
     * @param {string} hex - Parent hex color
     * @param {number} count - Number of shades needed
     * @returns {string[]} Array of hex colors
     */
    generateShades(hex, count) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);

        const shades = [];
        for (let i = 0; i < count; i++) {
            // Distribute from light to dark: factor goes from 0.3 to -0.3
            const factor = 0.35 - (i / Math.max(count - 1, 1)) * 0.7;
            const adjust = (c) => Math.min(255, Math.max(0, Math.round(c + (factor > 0 ? (255 - c) * factor : c * factor))));
            const nr = adjust(r);
            const ng = adjust(g);
            const nb = adjust(b);
            shades.push(`#${nr.toString(16).padStart(2, '0')}${ng.toString(16).padStart(2, '0')}${nb.toString(16).padStart(2, '0')}`);
        }
        return shades;
    },

    isYearlyView(view = STATE.currentView) {
        return YEARLY_VIEWS.has(view);
    },

    extractFilename(disposition) {
        if (!disposition) return null;

        const utf8Match = /filename\*=UTF-8''([^;]+)/i.exec(disposition);
        if (utf8Match) {
            try {
                return decodeURIComponent(utf8Match[1]);
            } catch {
                return utf8Match[1];
            }
        }

        const simpleMatch = /filename="?([^";]+)"?/i.exec(disposition);
        return simpleMatch ? simpleMatch[1] : null;
    },

    getCssVar(name, fallback = '') {
        try {
            const value = getComputedStyle(document.documentElement).getPropertyValue(name);
            return (value || '').trim() || fallback;
        } catch {
            return fallback;
        }
    },

    isLightTheme() {
        try {
            return (document.documentElement?.getAttribute('data-theme') || 'dark') === 'light';
        } catch {
            return false;
        }
    },

    getReportType() {
        const typeMap = {
            [CONFIG.VIEWS.CATEGORY]: STATE.categoryType,
            [CONFIG.VIEWS.ANNUAL_CATEGORY]: STATE.annualCategoryType,
            [CONFIG.VIEWS.BALANCE]: 'saldo_mensal',
            [CONFIG.VIEWS.COMPARISON]: 'receitas_despesas_diario',
            [CONFIG.VIEWS.ACCOUNTS]: 'receitas_despesas_por_conta',
            [CONFIG.VIEWS.CARDS]: 'cartoes_credito',
            [CONFIG.VIEWS.EVOLUTION]: 'evolucao_12m',
            [CONFIG.VIEWS.ANNUAL_SUMMARY]: 'resumo_anual'
        };
        return typeMap[STATE.currentView] ?? STATE.categoryType;
    },

    getActiveCategoryType() {
        return STATE.currentView === CONFIG.VIEWS.ANNUAL_CATEGORY
            ? STATE.annualCategoryType
            : STATE.categoryType;
    }
};

// ─── MODULES REGISTRY (cross-module late-binding) ────────────────────────────
// Each module registers itself here after definition.
// Cross-module calls go through Modules.X.method() to avoid circular imports.
export const Modules = {};
