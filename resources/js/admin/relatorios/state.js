/**
 * ============================================================================
 * LUKRATO — Relatórios / State & Config
 * ============================================================================
 * Shared state, configuration, and page-specific utilities.
 * All relatórios modules import from here.
 * ============================================================================
 */

import { formatMoney, escapeHtml as sharedEscapeHtml } from '../shared/utils.js';
import { refreshIcons } from '../shared/ui.js';

// Re-export shared utilities for convenience
export { formatMoney, refreshIcons };

// ─── PAYWALL ─────────────────────────────────────────────────────────────────

export const PAYWALL_MESSAGE = 'Relatórios são exclusivos do plano Pro.';

// ─── CONFIG ──────────────────────────────────────────────────────────────────

export const CONFIG = {
    // Detecta BASE_URL do DOM ou usa padrão
    BASE_URL: (() => {
        const meta = document.querySelector('meta[name="base-url"]');
        if (meta) return meta.content.replace(/\/?$/, '/');

        const base = document.querySelector('base[href]');
        if (base) return base.href.replace(/\/?$/, '/');

        return window.BASE_URL ? String(window.BASE_URL).replace(/\/?$/, '/') : '/';
    })(),

    CHART_COLORS: [
        '#E67E22', '#2C3E50', '#2ECC71', '#F39C12',
        '#9B59B6', '#1ABC9C', '#E74C3C', '#3498DB'
    ],

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

// ─── STATE ───────────────────────────────────────────────────────────────────

export const STATE = {
    currentView: CONFIG.VIEWS.CATEGORY,
    categoryType: 'despesas_por_categoria',
    annualCategoryType: 'despesas_anuais_por_categoria',
    currentMonth: computeInitialMonth(),
    currentAccount: null,
    chart: null,
    accounts: [],
    accessRestricted: false
};

// ─── UTILITIES ───────────────────────────────────────────────────────────────

export const Utils = {
    getCurrentMonth: computeInitialMonth,

    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(Number(value) || 0);
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

    isYearlyView(view = STATE.currentView) {
        return YEARLY_VIEWS.has(view);
    },

    extractFilename(disposition) {
        if (!disposition) return null;

        const utf8Match = /filename\*=UTF-8''([^;]+)/i.exec(disposition);
        if (utf8Match) {
            try {
                return decodeURIComponent(utf8Match[1]);
            } catch (e) {
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
