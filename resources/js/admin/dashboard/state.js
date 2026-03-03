/**
 * ============================================================================
 * LUKRATO — Dashboard / State & Config
 * ============================================================================
 * CONFIG, STATE, DOM references, Modules registry, utility helpers,
 * and theme-color resolver.
 * All dashboard modules import from here.
 * ============================================================================
 */

import { formatMoney, formatDate, escapeHtml } from '../shared/utils.js';
import { showToast, showConfirm, showLoading, hideLoading, toastSuccess, toastError } from '../shared/ui.js';
import { getBaseUrl, getCSRFToken } from '../shared/api.js';

// Re-export shared utilities for convenience
export { formatMoney, formatDate, escapeHtml, getBaseUrl, getCSRFToken };
export { showToast, showConfirm, showLoading, hideLoading, toastSuccess, toastError };

// ─── Modules Registry ────────────────────────────────────────────────────────
export const Modules = {};

// ─── CONFIG ──────────────────────────────────────────────────────────────────

export const CONFIG = {
    BASE_URL: (() => {
        const meta = document.querySelector('meta[name="base-url"]')?.content || '';
        let base = meta;
        if (!base) {
            const m = location.pathname.match(/^(.*\/public\/)/);
            base = m ? (location.origin + m[1]) : (location.origin + '/');
        }
        if (base && !/\/public\/?$/.test(base)) {
            const m2 = location.pathname.match(/^(.*\/public\/)/);
            if (m2) base = location.origin + m2[1];
        }
        return base.replace(/\/?$/, '/');
    })(),
    TRANSACTIONS_LIMIT: 5,
    CHART_MONTHS: 6,
    ANIMATION_DELAY: 300
};

CONFIG.API_URL = `${CONFIG.BASE_URL}api/`;

// ─── DOM References ──────────────────────────────────────────────────────────

export const DOM = {
    // KPIs
    saldoValue: document.getElementById('saldoValue'),
    receitasValue: document.getElementById('receitasValue'),
    despesasValue: document.getElementById('despesasValue'),
    saldoMesValue: document.getElementById('saldoMesValue'),

    chartCanvas: document.getElementById('evolutionChart'),
    chartLoading: document.getElementById('chartLoading'),

    tableBody: document.getElementById('transactionsTableBody'),
    table: document.getElementById('transactionsTable'),
    cardsContainer: document.getElementById('transactionsCards'),
    emptyState: document.getElementById('emptyState'),

    monthLabel: document.getElementById('currentMonthText'),

    // Gamificação
    streakDays: document.getElementById('streakDays'),
    badgesGrid: document.getElementById('badgesGrid'),
    userLevel: document.getElementById('userLevel'),
    totalLancamentos: document.getElementById('totalLancamentos'),
    totalCategorias: document.getElementById('totalCategorias'),
    mesesAtivos: document.getElementById('mesesAtivos'),
    pontosTotal: document.getElementById('pontosTotal')
};

// ─── Mutable State ───────────────────────────────────────────────────────────

export const STATE = {
    chartInstance: null,
    currentMonth: null,
    isLoading: false
};

// ─── Dashboard Utils ─────────────────────────────────────────────────────────

export const Utils = {
    money: (n) => {
        try {
            return Number(n || 0).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        } catch {
            return 'R$ 0,00';
        }
    },

    dateBR: (iso) => {
        if (!iso) return '-';
        try {
            const d = String(iso).split(/[T\s]/)[0];
            const m = d.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? `${m[3]}/${m[2]}/${m[1]}` : '-';
        } catch {
            return '-';
        }
    },

    formatMonth: (monthStr) => {
        try {
            const [y, m] = String(monthStr).split('-').map(Number);
            return new Date(y, m - 1, 1).toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
        } catch {
            return '-';
        }
    },

    formatMonthShort: (monthStr) => {
        try {
            const [y, m] = String(monthStr).split('-').map(Number);
            return new Date(y, m - 1, 1).toLocaleDateString('pt-BR', {
                month: 'short'
            });
        } catch {
            return '-';
        }
    },

    getCurrentMonth: () => {
        return window.LukratoHeader?.getMonth?.() ||
            new Date().toISOString().slice(0, 7);
    },

    getPreviousMonths: (currentMonth, count) => {
        const months = [];
        const [y, m] = currentMonth.split('-').map(Number);

        for (let i = count - 1; i >= 0; i--) {
            const date = new Date(y, m - 1 - i, 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            months.push(`${year}-${month}`);
        }

        return months;
    },

    getCssVar: (name, fallback = '') => {
        try {
            const value = getComputedStyle(document.documentElement).getPropertyValue(name);
            return (value || '').trim() || fallback;
        } catch {
            return fallback;
        }
    },

    isLightTheme: () => {
        try {
            return (document.documentElement?.getAttribute('data-theme') || 'dark') === 'light';
        } catch {
            return false;
        }
    },

    getContaLabel: (transaction) => {
        if (typeof transaction.conta === 'string' && transaction.conta.trim()) {
            return transaction.conta.trim();
        }

        const origem = transaction.conta_instituicao ??
            transaction.conta_nome ??
            transaction.conta?.instituicao ??
            transaction.conta?.nome ?? null;

        const destino = transaction.conta_destino_instituicao ??
            transaction.conta_destino_nome ??
            transaction.conta_destino?.instituicao ??
            transaction.conta_destino?.nome ?? null;

        if (transaction.eh_transferencia && (origem || destino)) {
            return `${origem || '-'}${destino || '-'}`;
        }

        if (transaction.conta_label && String(transaction.conta_label).trim()) {
            return String(transaction.conta_label).trim();
        }

        return origem || '-';
    },

    getTipoClass: (tipo) => {
        const normalized = String(tipo || '').toLowerCase();
        if (normalized === 'receita') return 'receita';
        if (normalized.includes('despesa')) return 'despesa';
        if (normalized.includes('transferencia')) return 'transferencia';
        return '';
    },

    removeLoadingClass: () => {
        setTimeout(() => {
            document.querySelectorAll('.kpi-value.loading').forEach(el => {
                el.classList.remove('loading');
            });
        }, CONFIG.ANIMATION_DELAY);
    }
};

// ─── Theme Colors ────────────────────────────────────────────────────────────

export const getThemeColors = () => {
    const isLightTheme = (document.documentElement.getAttribute('data-theme') || '').toLowerCase() === 'light'
        || Utils.isLightTheme?.();
    return {
        isLightTheme,
        axisColor: isLightTheme
            ? (Utils.getCssVar('--color-primary', '#e67e22') || '#e67e22')
            : 'rgba(255, 255, 255, 0.6)',
        yTickColor: isLightTheme ? '#000' : '#fff',
        xTickColor: isLightTheme
            ? (Utils.getCssVar('--color-text-muted', '#6c757d') || '#6c757d')
            : 'rgba(255, 255, 255, 0.6)',
        gridColor: isLightTheme
            ? 'rgba(0, 0, 0, 0.08)'
            : 'rgba(255, 255, 255, 0.05)',
        tooltipBg: isLightTheme ? 'rgba(255, 255, 255, 0.92)' : 'rgba(0, 0, 0, 0.85)',
        tooltipColor: isLightTheme ? '#0f172a' : '#f8fafc',
        labelColor: isLightTheme ? '#0f172a' : '#f8fafc'
    };
};
