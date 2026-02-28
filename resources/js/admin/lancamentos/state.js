/**
 * ============================================================================
 * LUKRATO — Lançamentos / State & Config
 * ============================================================================
 * Shared state, configuration, DOM refs, and page-specific utilities.
 * All lancamentos modules import from here.
 * ============================================================================
 */

import { formatMoney, parseMoney, escapeHtml, normalizeText, getTipoClass, debounce } from '../shared/utils.js';

// Re-export shared utilities for convenience
export { formatMoney as fmtMoney, escapeHtml, normalizeText, getTipoClass, debounce };

// ─── CONFIG ──────────────────────────────────────────────────────────────────

export const CONFIG = {
    BASE_URL: (window.BASE_URL || (location.pathname.includes('/public/') ?
        location.pathname.split('/public/')[0] + '/public/' : '/')).replace(/\/?$/, '/'),
    TABLE_HEIGHT: '520px',
    PAGINATION_SIZE: 10,
    PAGINATION_OPTIONS: [10, 25, 50, 100],
    DATA_LIMIT: 500,
    DEBOUNCE_DELAY: 10
};

CONFIG.ENDPOINT = `${CONFIG.BASE_URL}api/lancamentos`;
CONFIG.EXPORT_ENDPOINT = `${CONFIG.ENDPOINT}/export`;

// ─── DOM (populated on init) ─────────────────────────────────────────────────

export const DOM = {};

export function initDOM() {
    // Tabela
    DOM.tabContainer = document.getElementById('lancamentosTable');
    DOM.tableBody = document.getElementById('lancamentosTableBody');
    DOM.selectAllCheckbox = document.getElementById('selectAllLancamentos');
    DOM.paginationInfo = document.getElementById('paginationInfo');
    DOM.pageSize = document.getElementById('pageSize');
    DOM.prevPage = document.getElementById('prevPage');
    DOM.nextPage = document.getElementById('nextPage');
    DOM.pageNumbers = document.getElementById('pageNumbers');
    // Cards (mobile)
    DOM.lanCards = document.getElementById('lanCards');
    DOM.lanPager = document.getElementById('lanCardsPager');
    DOM.lanPagerFirst = document.getElementById('lanPagerFirst');
    DOM.lanPagerPrev = document.getElementById('lanPagerPrev');
    DOM.lanPagerNext = document.getElementById('lanPagerNext');
    DOM.lanPagerLast = document.getElementById('lanPagerLast');
    DOM.lanPagerInfo = document.getElementById('lanPagerInfo');
    // Filtros
    DOM.selectTipo = document.getElementById('filtroTipo');
    DOM.selectCategoria = document.getElementById('filtroCategoria');
    DOM.selectConta = document.getElementById('filtroConta');
    DOM.filtroTexto = document.getElementById('filtroTexto');
    DOM.filtroStatus = document.getElementById('filtroStatus');
    DOM.btnLimparFiltros = document.getElementById('btnLimparFiltros');
    DOM.activeFilterBadges = document.getElementById('activeFilterBadges');
    // Exportação
    DOM.btnExportar = document.getElementById('btnExportar');
    DOM.inputExportStart = document.getElementById('exportStart');
    DOM.inputExportEnd = document.getElementById('exportEnd');
    DOM.selectExportFormat = document.getElementById('exportFormat');
    DOM.exportConta = document.getElementById('exportConta');
    DOM.exportCategoria = document.getElementById('exportCategoria');
    DOM.exportTipo = document.getElementById('exportTipo');
    // Seleção e exclusão
    DOM.btnExcluirSel = document.getElementById('btnExcluirSel');
    DOM.selCountSpan = document.getElementById('selCount');
    // Modal de edição
    DOM.modalEditLancEl = document.getElementById('modalEditarLancamento');
    DOM.formLanc = document.getElementById('formLancamento');
    DOM.editLancAlert = document.getElementById('editLancAlert');
    DOM.inputLancData = document.getElementById('editLancData');
    DOM.inputLancHora = document.getElementById('editLancHora');
    DOM.selectLancTipo = document.getElementById('editLancTipo');
    DOM.selectLancConta = document.getElementById('editLancConta');
    DOM.selectLancCategoria = document.getElementById('editLancCategoria');
    // Modal de edição transferência
    DOM.modalEditTransEl = document.getElementById('modalEditarTransferencia');
    DOM.formTrans = document.getElementById('formTransLancamento');
    DOM.editTransAlert = document.getElementById('editTransAlert');
    DOM.inputTransData = document.getElementById('editTransData');
    DOM.inputTransValor = document.getElementById('editTransValor');
    DOM.selectTransConta = document.getElementById('editTransConta');
    DOM.selectTransContaDestino = document.getElementById('editTransContaDestino');
    DOM.inputTransDescricao = document.getElementById('editTransDescricao');
    DOM.inputLancValor = document.getElementById('editLancValor');
    DOM.inputLancDescricao = document.getElementById('editLancDescricao');
    DOM.selectLancFormaPagamento = document.getElementById('editLancFormaPagamento');
    // Modal de visualização
    DOM.modalViewLancEl = document.getElementById('modalViewLancamento');
    DOM.viewLancData = document.getElementById('viewLancData');
    DOM.viewLancTipo = document.getElementById('viewLancTipo');
    DOM.viewLancValor = document.getElementById('viewLancValor');
    DOM.viewLancStatus = document.getElementById('viewLancStatus');
    DOM.viewLancCategoria = document.getElementById('viewLancCategoria');
    DOM.viewLancConta = document.getElementById('viewLancConta');
    DOM.viewLancCartaoItem = document.getElementById('viewLancCartaoItem');
    DOM.viewLancCartao = document.getElementById('viewLancCartao');
    DOM.viewLancFormaPgtoItem = document.getElementById('viewLancFormaPgtoItem');
    DOM.viewLancFormaPgto = document.getElementById('viewLancFormaPgto');
    DOM.viewLancDescricaoCard = document.getElementById('viewLancDescricaoCard');
    DOM.viewLancDescricao = document.getElementById('viewLancDescricao');
    DOM.viewLancParcelamentoCard = document.getElementById('viewLancParcelamentoCard');
    DOM.viewLancParcela = document.getElementById('viewLancParcela');
    DOM.btnEditFromView = document.getElementById('btnEditFromView');
    DOM.modalViewLancamentoLabel = document.getElementById('modalViewLancamentoLabel');
    DOM.viewLancamentoId = document.getElementById('viewLancamentoId');
}

// ─── STATE ───────────────────────────────────────────────────────────────────

export const STATE = {
    table: null,
    modalEditLanc: null,
    modalViewLanc: null,
    editingLancamentoId: null,
    viewingLancamento: null,
    categoriaOptions: [],
    contaOptions: [],
    loadTimer: null,
    lancamentos: [],
    allData: [],
    filteredData: [],
    currentPage: 1,
    pageSize: 10,
    sortField: 'data',
    sortDirection: 'desc',
    selectedIds: new Set()
};

// ─── Page-Specific Utilities ─────────────────────────────────────────────────

export const Utils = {
    fmtMoney: (n) => formatMoney(n),

    fmtDate: (iso) => {
        if (!iso) return '-';
        if (typeof iso === 'string') {
            const normalized = iso.trim();
            const datePart = normalized.includes('T') ? normalized.split('T')[0] : normalized;
            if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
                const [year, month, day] = datePart.split('-');
                if (year && month && day) return `${day}/${month}/${year}`;
            }
        }
        const d = new Date(iso);
        return isNaN(d) ? '-' : d.toLocaleDateString('pt-BR');
    },

    escapeHtml,
    normalizeText,
    getTipoClass,

    isSaldoInicial: (data) => {
        if (!data) return false;
        const tipo = String(data?.tipo || '').toLowerCase();
        const descricao = String(data?.descricao || '').toLowerCase();
        return tipo === 'saldo_inicial' || tipo === 'saldo inicial' || descricao.includes('saldo inicial');
    },

    isTransferencia: (data) => Boolean(data?.eh_transferencia),
    canEditLancamento: (data) => !Utils.isSaldoInicial(data),

    formatFormaPagamento: (forma) => {
        if (!forma) return '-';
        const mapa = {
            'dinheiro': '💵 Dinheiro',
            'pix': '⚡ PIX',
            'cartao_debito': '💳 Débito',
            'cartao_credito': '💳 Crédito',
            'transferencia': '🔄 Transferência',
            'boleto': '📄 Boleto',
            'cheque': '📝 Cheque',
            'outro': '📋 Outro'
        };
        return mapa[forma] || forma.charAt(0).toUpperCase() + forma.slice(1).replace(/_/g, ' ');
    },

    parseFilterNumber: (input) => {
        if (input === undefined || input === null) return null;
        const raw = String(input).trim();
        if (!raw) return null;
        const normalized = raw.replace(/\./g, '').replace(',', '.');
        const num = Number(normalized);
        return Number.isFinite(num) ? num : null;
    },

    parseFilterDate: (input) => {
        if (!input) return null;
        const raw = String(input).trim();
        if (!raw) return null;
        if (/^\d{4}-\d{1,2}-\d{1,2}$/.test(raw)) {
            const [year, month, day] = raw.split('-').map(Number);
            return Utils.normalizeFilterDate(day, month, year);
        }
        const cleaned = raw.replace(/[-.]/g, '/');
        const match = cleaned.match(/^(\d{1,2})(?:\/(\d{1,2})(?:\/(\d{2,4}))?)?$/);
        if (!match) return null;
        const day = Number(match[1]);
        const month = match[2] !== undefined ? Number(match[2]) : null;
        const year = match[3] !== undefined ? Number(match[3]) : null;
        return Utils.normalizeFilterDate(day, month, year);
    },

    normalizeFilterDate: (day, month, year) => {
        const safeDay = Number.isFinite(day) ? day : null;
        const safeMonth = Number.isFinite(month) ? month : null;
        let safeYear = Number.isFinite(year) ? year : null;
        if (safeYear !== null && safeYear < 100) safeYear += 2000;
        if (safeDay !== null && (safeDay < 1 || safeDay > 31)) return null;
        if (safeMonth !== null && (safeMonth < 1 || safeMonth > 12)) return null;
        if (safeYear !== null && (safeYear < 1900 || safeYear > 2100)) return null;
        return { day: safeDay, month: safeMonth, year: safeYear };
    },

    extractYMD: (value) => {
        if (!value) return null;
        if (value instanceof Date && !isNaN(value)) {
            return { year: value.getFullYear(), month: value.getMonth() + 1, day: value.getDate() };
        }
        if (typeof value === 'string') {
            const trimmed = value.trim();
            if (!trimmed) return null;
            if (/^\d{4}-\d{2}-\d{2}/.test(trimmed)) {
                const [y, m, d] = trimmed.slice(0, 10).split('-').map(Number);
                return Utils.normalizeFilterDate(d, m, y);
            }
            if (/^\d{1,2}\/\d{1,2}\/\d{2,4}$/.test(trimmed)) {
                const [d, m, y] = trimmed.split('/').map(Number);
                return Utils.normalizeFilterDate(d, m, y);
            }
        }
        const d = new Date(value);
        if (isNaN(d)) return null;
        return { year: d.getFullYear(), month: d.getMonth() + 1, day: d.getDate() };
    },

    normalizeDataList: (payload) => {
        if (!payload) return [];
        if (Array.isArray(payload)) return payload;
        if (payload && Array.isArray(payload.data)) return payload.data;
        return [];
    },

    getTrimmedDateValue: (input) => {
        if (!input) return '';
        const value = (input.value || '').trim();
        return value && /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/.test(value) ? value : '';
    },

    parseDownloadFilename: (disposition) => {
        if (!disposition) return null;
        const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf8Match && utf8Match[1]) return decodeURIComponent(utf8Match[1]);
        const asciiMatch = disposition.match(/filename="?([^";]+)"?/i);
        if (asciiMatch && asciiMatch[1]) return asciiMatch[1];
        return null;
    },

    hasSwal: () => !!window.Swal,
    getCSRFToken: () => (window.LK && typeof LK.getCSRF === 'function') ? LK.getCSRF() : '',
    getCurrentMonth: () => (window.LukratoHeader?.getMonth?.()) || (new Date()).toISOString().slice(0, 7)
};

// ─── MoneyMask ───────────────────────────────────────────────────────────────

export const MoneyMask = (() => {
    const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    const format = (value) => {
        const num = Number(value);
        return Number.isFinite(num) ? formatter.format(num) : '';
    };

    const unformat = (value) => {
        const normalized = String(value || '').replace(/\s|[R$]/g, '').replace(/\./g, '').replace(',', '.');
        const num = Number(normalized);
        return Number.isFinite(num) ? num : 0;
    };

    const bind = (input) => {
        if (!input) return;
        const onInput = (e) => {
            const digits = String(e.target.value || '').replace(/[^\d]/g, '');
            const num = Number(digits || '0') / 100;
            e.target.value = format(num);
        };
        input.addEventListener('input', onInput, { passive: true });
        input.addEventListener('focus', () => { if (!input.value) input.value = format(0); });
    };

    return { format, unformat, bind };
})();

// ─── Notifications ───────────────────────────────────────────────────────────

export const Notifications = {
    ask: async (title, text = '') => {
        if (Utils.hasSwal()) {
            const result = await Swal.fire({
                title, text, icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: 'var(--color-primary)',
                cancelButtonColor: 'var(--color-text-muted)'
            });
            return result.isConfirmed;
        }
        return confirm(title || 'Confirmar ação?');
    },

    toast: (msg, icon = 'success') => {
        if (Utils.hasSwal()) {
            Swal.fire({
                toast: true, position: 'top-end', timer: 2500,
                showConfirmButton: false, icon, title: msg, timerProgressBar: true
            });
        }
    }
};

// ─── MODULES REGISTRY (cross-module late-binding) ────────────────────────────
// Each module registers itself here after definition.
// Cross-module calls go through Modules.X.method() to avoid circular imports.
export const Modules = {};
