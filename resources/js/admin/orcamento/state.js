/**
 * Orçamento – State, Config & Utility helpers
 */

import { formatMoney, parseMoney, escapeHtml, debounce, formatarDinheiro } from '../shared/utils.js';
import { refreshIcons } from '../shared/ui.js';

export { formatMoney, parseMoney, escapeHtml, debounce, formatarDinheiro, refreshIcons };

// ── Configuration ──────────────────────────────────────────────
export const CONFIG = {
    BASE_URL: (() => {
        try {
            if (window.LK && typeof window.LK.getBase === 'function') return window.LK.getBase();
            const meta = document.querySelector('meta[name="base-url"]');
            if (meta?.content) return meta.content;
            if (window.BASE_URL) return window.BASE_URL.endsWith('/') ? window.BASE_URL : window.BASE_URL + '/';
            return '/lukrato/public/';
        } catch (error) {
            console.error('❌ Erro ao obter BASE_URL:', error);
            return '/lukrato/public/';
        }
    })(),
};

// ── Shared state ───────────────────────────────────────────────
export const STATE = {
    currentMonth: new Date().getMonth() + 1,
    currentYear: new Date().getFullYear(),
    orcamentos: [],
    categorias: [],
    sugestoes: [],
    editingOrcamentoId: null,
    previewMeta: null,
};

// ── Category icon color map ────────────────────────────────────
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

// ── Utility helpers ────────────────────────────────────────────
export const Utils = {
    getCsrfToken() {
        const input = document.querySelector('input[name="csrf_token"]');
        if (input?.value) return input.value;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta?.content) return meta.content;
        if (window.LK?.getCSRF) return window.LK.getCSRF();
        return window.CSRF || '';
    },

    formatCurrency(value) {
        if (Math.abs(value) < 0.01) value = 0;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    },

    formatNumber(value) {
        return parseFloat(value || 0).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    },

    parseMoney(str) {
        if (!str) return 0;
        return parseFloat(String(str).replace(/[R$\s]/g, '').replace(/\./g, '').replace(',', '.')) || 0;
    },

    formatarDinheiro(input) {
        let valor = input.value.replace(/\D/g, '');
        if (!valor) { input.value = ''; return; }
        valor = (parseInt(valor) / 100).toFixed(2);
        valor = valor.replace('.', ',');
        valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        input.value = valor;
    },

    escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    },

    setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    },

    getInsightIcon(tipo) {
        const map = {
            alerta_80: 'triangle-alert', alerta_100: 'circle-alert',
            economia: 'thumbs-up', tendencia_alta: 'trending-up',
            tendencia_baixa: 'trending-down', comparativo: 'scale',
            sem_orcamento: 'circle-help', meta_atrasada: 'clock'
        };
        return map[tipo] || 'lightbulb';
    },

    showToast(message, type = 'info') {
        const iconMap = { success: 'success', error: 'error', info: 'info', warning: 'warning' };
        Swal.fire({
            icon: iconMap[type] || 'info',
            title: type === 'error' ? 'Erro!' : (type === 'success' ? 'Sucesso!' : 'Aviso'),
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    },
};
