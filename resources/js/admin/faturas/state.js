/**
 * LUKRATO — Faturas / State & Config
 */
import { apiFetch, getApiBaseUrl, getCSRFToken as getSharedCSRFToken } from '../shared/api.js';
import {
    resolveAccountsEndpoint,
    resolveCardsEndpoint,
    resolveCategoriesEndpoint,
} from '../api/endpoints/finance.js';
import { resolveFaturasEndpoint } from '../api/endpoints/faturas.js';
import { formatMoney, escapeHtml, debounce } from '../shared/utils.js';

export { formatMoney, escapeHtml, debounce };

// ─── getCategoryIconColor ───────────────────────────────────────────────────

export function getCategoryIconColor(icon) {
    const colors = {
        'house': '#f97316', 'utensils': '#ef4444', 'car': '#3b82f6',
        'lightbulb': '#eab308', 'heart-pulse': '#ef4444', 'graduation-cap': '#6366f1',
        'shirt': '#ec4899', 'clapperboard': '#a855f7', 'credit-card': '#0ea5e9',
        'smartphone': '#6366f1', 'shopping-cart': '#f97316', 'coins': '#eab308',
        'briefcase': '#3b82f6', 'laptop': '#06b6d4', 'trending-up': '#22c55e',
        'gift': '#ec4899', 'banknote': '#22c55e', 'trophy': '#f59e0b',
        'wallet': '#14b8a6', 'tag': '#94a3b8', 'pie-chart': '#8b5cf6',
        'piggy-bank': '#ec4899', 'plane': '#0ea5e9', 'gamepad-2': '#a855f7',
        'baby': '#f472b6', 'dog': '#92400e', 'wrench': '#64748b',
        'church': '#6366f1', 'dumbbell': '#ef4444', 'music': '#a855f7',
        'book-open': '#3b82f6', 'scissors': '#ec4899', 'building-2': '#64748b',
        'landmark': '#3b82f6', 'receipt': '#14b8a6'
    };
    return colors[icon] || '#f97316';
}

// ─── CONFIG ─────────────────────────────────────────────────────────────────

export const CONFIG = {
    BASE_URL: getApiBaseUrl(),
    ENDPOINTS: {
        parcelamentos: resolveFaturasEndpoint(),
        categorias: resolveCategoriesEndpoint(),
        contas: resolveAccountsEndpoint(),
        cartoes: resolveCardsEndpoint()
    },
    TIMEOUTS: {
        alert: 5000,
        successMessage: 2000
    }
};

// ─── DOM ────────────────────────────────────────────────────────────────────

export const DOM = {};

function preparePageModal(id) {
    const modalEl = document.getElementById(id);

    if (!modalEl) {
        return null;
    }

    window.LK?.modalSystem?.prepareBootstrapModal(modalEl, { scope: 'page' });

    return modalEl;
}

export function initDOM() {
    // Containers
    DOM.loadingEl = document.getElementById('loadingParcelamentos');
    DOM.containerEl = document.getElementById('parcelamentosContainer');
    DOM.emptyStateEl = document.getElementById('emptyState');
    DOM.detailPageEl = document.getElementById('faturaDetalhePage');
    DOM.detailPageShell = document.getElementById('faturaDetalheShell');
    DOM.detailPageLoading = document.getElementById('faturaDetalheLoading');
    DOM.detailPageContent = document.getElementById('faturaDetalheContent');
    DOM.detailPageTitle = document.getElementById('faturaDetalheTitle');
    DOM.detailPageSubtitle = document.getElementById('faturaDetalheSubtitle');

    // Filtros
    DOM.filtroStatus = document.getElementById('filtroStatus');
    DOM.filtroCartao = document.getElementById('filtroCartao');
    DOM.filtroAno = document.getElementById('filtroAno');
    DOM.filtroMes = document.getElementById('filtroMes');
    DOM.btnFiltrar = document.getElementById('btnFiltrar');
    DOM.btnLimparFiltros = document.getElementById('btnLimparFiltros');
    DOM.filtersContainer = document.querySelector('.filters-modern');
    DOM.filtersBody = document.getElementById('filtersBody');
    DOM.toggleFilters = document.getElementById('toggleFilters');
    DOM.activeFilters = document.getElementById('activeFilters');
    DOM.filtersSummary = document.getElementById('faturasFiltersSummary');
    DOM.resultsSummary = document.getElementById('faturasResultsSummary');
    DOM.contextSummary = document.getElementById('faturasContextSummary');

    // Modais auxiliares
    DOM.modalPagarFatura = preparePageModal('modalPagarFatura');
    DOM.modalEditarItemFatura = preparePageModal('modalEditarItemFatura');
}

// ─── STATE ──────────────────────────────────────────────────────────────────

export const STATE = {
    parcelamentos: [],
    cartoes: [],
    faturaAtual: null,
    currentDetailId: null,
    sortColumn: 'data_compra',
    sortDirection: 'asc',
    filtros: {
        status: '',
        cartao_id: '',
        ano: new Date().getFullYear(),
        mes: ''
    },
    anosCarregados: false
};

// ─── Modules registry ───────────────────────────────────────────────────────

export const Modules = {};

// ─── Utils (backward-compat façade) ─────────────────────────────────────────

export const Utils = {
    formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    },

    formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('pt-BR');
    },

    parseMoney(str) {
        if (!str) return 0;
        return parseFloat(str.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
    },

    showAlert(element, message, type = 'danger') {
        if (!element) return;
        element.className = `alert alert-${type}`;
        element.textContent = message;
        element.style.display = 'block';
        setTimeout(() => {
            element.style.display = 'none';
        }, CONFIG.TIMEOUTS.alert);
    },

    getCSRFToken() {
        return getSharedCSRFToken();
    },

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    buildUrl(endpoint, params = {}) {
        const url = endpoint.startsWith('http')
            ? endpoint
            : CONFIG.BASE_URL + endpoint.replace(/^\//, '');

        const filteredParams = Object.entries(params)
            .filter(([_, value]) => value !== null && value !== undefined && value !== '')
            .map(([key, value]) => `${key}=${encodeURIComponent(value)}`);

        return filteredParams.length > 0 ? `${url}?${filteredParams.join('&')}` : url;
    },

    async apiRequest(url, options = {}) {
        const fullUrl = url.startsWith('http') ? url : CONFIG.BASE_URL + url.replace(/^\//, '');

        try {
            return await apiFetch(fullUrl, {
                ...options,
                headers: {
                    'X-CSRF-Token': this.getCSRFToken(),
                    ...options.headers
                }
            });
        } catch (error) {
            console.error('Erro na requisição:', error);
            throw error;
        }
    },

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
    },

    calcularDiferencaDias(dataVencimento, dataPagamento) {
        const dataVenc = new Date(dataVencimento + 'T00:00:00');
        const dataPag = new Date(dataPagamento + 'T00:00:00');
        return Math.floor((dataVenc - dataPag) / (1000 * 60 * 60 * 24));
    }
};
