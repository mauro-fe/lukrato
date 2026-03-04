/**
 * ============================================================================
 * LUKRATO — Contas / State & Config
 * ============================================================================
 * Shared state, configuration, and page-specific utilities.
 * All contas modules import from here.
 * ============================================================================
 */

import { formatMoney, parseMoney, escapeHtml, normalizeText, getTipoClass, debounce, calcularRecorrenciaFim } from '../shared/utils.js';
import { toastSuccess, toastError, showConfirm, confirmDelete, showLoading, hideLoading, refreshIcons } from '../shared/ui.js';
import { setupMoneyMask, setMoneyValue, getMoneyValue, applyMoneyMask } from '../shared/money-mask.js';

// Re-export shared utilities for convenience
export { formatMoney, parseMoney, escapeHtml, normalizeText, getTipoClass, debounce, calcularRecorrenciaFim };
export { toastSuccess, toastError, showConfirm, confirmDelete, showLoading, hideLoading, refreshIcons };
export { setupMoneyMask, setMoneyValue, getMoneyValue, applyMoneyMask };

// ─── CONFIG ──────────────────────────────────────────────────────────────────

export const CONFIG = {
    BASE_URL: (window.BASE_URL || (location.pathname.includes('/public/') ?
        location.pathname.split('/public/')[0] + '/public/' : '/')).replace(/\/?$/, '/'),
};

CONFIG.API_URL = `${CONFIG.BASE_URL}api`;

// ─── STATE ───────────────────────────────────────────────────────────────────

export const STATE = {
    instituicoes: [],
    contas: [],
    categorias: null,
    currentEditId: null,
    isSubmitting: false,
    contaSelecionadaLancamento: null,
    isEstornoCartao: false
};

// ─── MODULES REGISTRY (cross-module late-binding) ────────────────────────────
// Each module registers itself here after definition.
// Cross-module calls go through Modules.X.method() to avoid circular imports.
export const Modules = {};

// ─── Page-Specific Utilities ─────────────────────────────────────────────────

export const Utils = {

    // ── CSRF ─────────────────────────────────────────────────────────────

    /**
     * Obter token CSRF (sempre fresco)
     */
    async getCSRFToken() {
        try {
            // Tentar buscar token fresco da API
            const response = await fetch(`${CONFIG.BASE_URL}api/csrf-token.php`);
            if (response.ok) {
                const data = await response.json();
                if (data.token) {
                    // Atualizar meta tag
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.setAttribute('content', data.token);
                    }
                    return data.token;
                }
            }
        } catch (error) {
            console.warn('Erro ao buscar token fresco, usando fallback:', error);
        }

        // Fallback: tentar meta tag
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (metaToken) {
            return metaToken;
        }

        if (window.LK?.getCSRF) {
            const token = window.LK.getCSRF();
            return token;
        }

        if (window.CSRF) {
            return window.CSRF;
        }

        console.error('❌ CSRF token não encontrado!');
        return '';
    },

    /**
     * Atualizar token CSRF em todos os locais conhecidos
     */
    updateCSRFToken(newToken) {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }
        if (window.LK) {
            window.LK.csrf = newToken;
        }
        if (typeof window.CSRF !== 'undefined') {
            window.CSRF = newToken;
        }
    },

    // ── Navigation / Date ────────────────────────────────────────────────

    /**
     * Obter mês corrente no formato YYYY-MM
     */
    getCurrentMonth() {
        if (window.LukratoHeader?.getMonth?.()) return window.LukratoHeader.getMonth();
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    },

    // ── Tipo helpers ─────────────────────────────────────────────────────

    /**
     * Formatar label do tipo de conta
     */
    formatTipoConta(tipo) {
        const labels = {
            'conta_corrente': 'Corrente',
            'conta_poupanca': 'Poupança',
            'conta_investimento': 'Investimento',
            'carteira_digital': 'Carteira',
            'dinheiro': 'Dinheiro'
        };
        return labels[tipo] || 'Conta';
    },

    /**
     * Obter classe CSS do tipo de conta
     */
    getTipoContaClass(tipo) {
        const classes = {
            'conta_corrente': 'tipo-corrente',
            'conta_poupanca': 'tipo-poupanca',
            'conta_investimento': 'tipo-investimento',
            'carteira_digital': 'tipo-carteira',
            'dinheiro': 'tipo-carteira'
        };
        return classes[tipo] || 'tipo-corrente';
    },

    // ── Instituições ─────────────────────────────────────────────────────

    /**
     * Agrupar instituições por tipo
     */
    groupByTipo(instituicoes) {
        return instituicoes.reduce((acc, inst) => {
            if (!acc[inst.tipo]) acc[inst.tipo] = [];
            acc[inst.tipo].push(inst);
            return acc;
        }, {});
    },

    /**
     * Formatar tipo de instituição
     */
    formatTipo(tipo) {
        const tipos = {
            'banco': 'Bancos',
            'fintech': 'Fintechs',
            'carteira_digital': 'Carteiras Digitais',
            'corretora': 'Corretoras',
            'cooperativa': 'Cooperativas de Crédito',
            'fisica': 'Dinheiro Físico',
            'outro': 'Outros'
        };
        return tipos[tipo] || tipo;
    },

    /**
     * Buscar instituição por ID
     */
    getInstituicao(id) {
        return STATE.instituicoes.find(inst => inst.id === id);
    },

    // ── Currency / Money ─────────────────────────────────────────────────

    /**
     * Formatar moeda (Intl.NumberFormat pt-BR BRL)
     */
    formatCurrency(value) {
        // Normalizar valores muito próximos de zero para evitar -R$ 0,00
        if (Math.abs(value) < 0.01) value = 0;
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },

    /**
     * Formatar input de dinheiro (centavos → string formatada)
     */
    formatMoneyInput(value, isNegative = false) {
        // Converte centavos para reais
        const reais = value / 100;

        // Formata com 2 casas decimais
        let formatted = reais.toFixed(2)
            .replace('.', ',')
            .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return isNegative ? '-' + formatted : formatted;
    },

    /**
     * Converter valor formatado para número
     */
    parseMoneyInput(value) {
        if (!value) return 0;

        // Remove pontos de milhar e substitui vírgula por ponto
        const cleaned = value
            .replace(/\./g, '')
            .replace(',', '.');

        return parseFloat(cleaned) || 0;
    },

    // ── Notifications ────────────────────────────────────────────────────

    /**
     * Exibir notificação para o usuário
     */
    showNotification(type, message) {
        // Se houver função global showNotification, usar ela
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }

        // Criar notificação toast simples
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);

        // Remover após 3 segundos
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    /**
     * Mostrar toast/notificação (estilo lk-toast)
     */
    showToast(message, type = 'info') {
        // Criar elemento de toast
        const toast = document.createElement('div');
        toast.className = `lk-toast lk-toast-${type}`;
        toast.innerHTML = `
            <div class="lk-toast-content">
                <i data-lucide="${type === 'success' ? 'circle-check' : type === 'error' ? 'circle-alert' : 'info'}"></i>
                <span>${message}</span>
            </div>
        `;

        // Adicionar ao body
        document.body.appendChild(toast);
        if (window.lucide) lucide.createIcons();

        // Animar entrada
        setTimeout(() => toast.classList.add('lk-toast-show'), 10);

        // Remover após 4 segundos
        setTimeout(() => {
            toast.classList.remove('lk-toast-show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    // ── Currency Symbol ──────────────────────────────────────────────────

    /**
     * Atualizar símbolo da moeda no formulário
     */
    updateCurrencySymbol(currency) {
        const symbolElement = document.querySelector('.lk-currency-symbol');
        if (!symbolElement) return;

        const symbols = {
            'BRL': 'R$',
            'USD': '$',
            'EUR': '€'
        };

        symbolElement.textContent = symbols[currency] || 'R$';
    }
};
