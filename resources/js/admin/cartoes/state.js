/**
 * Cartões Manager – State, Config & Utility helpers
 * Extracted from cartoes-manager.js (monolith → modules)
 */

import { formatMoney, parseMoney, escapeHtml, debounce } from '../shared/utils.js';
import { refreshIcons } from '../shared/ui.js';

export { formatMoney as fmtMoney, escapeHtml, debounce };

// ── Configuration ──────────────────────────────────────────────
export const CONFIG = {
    BASE_URL: (() => {
        try {
            // Usar a função global LK.getBase() se disponível
            if (window.LK && typeof window.LK.getBase === 'function') {
                const url = window.LK.getBase();
                return url;
            }

            // Fallback para meta tag
            const meta = document.querySelector('meta[name="base-url"]');
            if (meta?.content) {
                return meta.content;
            }

            if (window.BASE_URL) {
                const url = window.BASE_URL.endsWith('/') ? window.BASE_URL : window.BASE_URL + '/';
                return url;
            }

            // Fallback: detectar automaticamente
            const path = window.location.pathname;
            const publicIndex = path.indexOf('/public/');

            if (publicIndex !== -1) {
                const base = path.substring(0, publicIndex + 8);
                const url = window.location.origin + base;
                return url;
            }

            // Último fallback
            const url = window.location.origin + '/lukrato/public/';
            return url;
        } catch (error) {
            console.error('❌ Erro ao obter BASE_URL:', error);
            return window.location.origin + '/lukrato/public/';
        }
    })(),
    API_URL: '',
};
CONFIG.API_URL = CONFIG.BASE_URL + 'api';

// ── Shared state ───────────────────────────────────────────────
export const STATE = {
    cartoes: [],
    filteredCartoes: [],
    alertas: [],
    currentView: 'grid',
    currentFilter: 'all',
    searchTerm: '',
};

// ── Module registry (filled by other modules) ─────────────────
export const Modules = {};

// ── Utility helpers ────────────────────────────────────────────
export const Utils = {
    /**
     * Obter token CSRF (sempre fresco)
     */
    async getCSRFToken() {
        try {
            // Tentar buscar token fresco da API
            const response = await fetch('/lukrato/public/api/csrf-token.php');
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
        if (metaToken) return metaToken;

        if (window.LK?.getCSRF) return window.LK.getCSRF();
        if (window.CSRF) return window.CSRF;

        console.warn('⚠️ Nenhum token CSRF encontrado');
        return '';
    },

    /**
     * Obter Base URL (delegated to CONFIG)
     */
    getBaseUrl() {
        return CONFIG.BASE_URL;
    },

    /**
     * Formatar dinheiro
     */
    formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    },

    /**
     * Formatar dinheiro para input (sem R$)
     */
    formatMoneyInput(value) {
        // Se o value já for uma string formatada, retorna ela
        if (typeof value === 'string' && value.includes(',')) {
            return value;
        }

        // Se for número, converte centavos para reais e formata
        if (typeof value === 'number') {
            const reais = value / 100;
            return reais.toFixed(2)
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Fallback: formata com Intl
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value || 0);
    },

    /**
     * Parse dinheiro (converter string para float)
     */
    parseMoney(value) {
        if (typeof value === 'number') return value;
        if (!value) return 0;

        // Remove R$, espaços e converte vírgula para ponto
        return parseFloat(
            value.toString()
                .replace(/[R$\s]/g, '')
                .replace(/\./g, '')
                .replace(',', '.')
        ) || 0;
    },

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    },

    /**
     * Debounce helper
     */
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

    /**
     * Toast notification
     */
    showToast(type, message) {
        // Usar SweetAlert2 se disponível
        if (window.Swal) {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Sucesso!' : 'Erro!',
                text: message,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            alert(message);
        }
    },

    /**
     * Diálogo de confirmação
     */
    async showConfirmDialog(title, message, confirmText = 'Confirmar') {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            });
            return result.isConfirmed;
        }
        return confirm(`${title}\n\n${message}`);
    },

    /**
     * Obter ícone/logo da bandeira
     */
    getBrandIcon(bandeira) {
        const baseUrl = CONFIG.BASE_URL.replace('/public/', '/public/assets/img/bandeiras/');
        const logos = {
            'visa': `${baseUrl}visa.png`,
            'mastercard': `${baseUrl}mastercard.png`,
            'elo': `${baseUrl}elo.png`,
            'amex': `${baseUrl}amex.png`,
            'diners': `${baseUrl}diners.png`,
            'discover': `${baseUrl}discover.png`
        };
        return logos[bandeira?.toLowerCase()] || `${baseUrl}default.png`;
    },

    /**
     * Obter cor padrão baseada na bandeira
     */
    getDefaultColor(bandeira) {
        const colors = {
            'visa': 'linear-gradient(135deg, #1A1F71 0%, #2D3A8C 100%)',
            'mastercard': 'linear-gradient(135deg, #EB001B 0%, #F79E1B 100%)',
            'elo': 'linear-gradient(135deg, #FFCB05 0%, #FFE600 100%)',
            'amex': 'linear-gradient(135deg, #006FCF 0%, #0099CC 100%)',
            'diners': 'linear-gradient(135deg, #0079BE 0%, #00558C 100%)',
            'discover': 'linear-gradient(135deg, #FF6000 0%, #FF8500 100%)'
        };
        return colors[bandeira?.toLowerCase()] || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    },

    /**
     * Retorna uma cor sólida (hex) para usar como accent baseada na bandeira
     */
    getAccentColor(bandeira) {
        const colors = {
            'visa': '#1A1F71',
            'mastercard': '#EB001B',
            'elo': '#00A4E0',
            'amex': '#006FCF',
            'diners': '#0079BE',
            'discover': '#FF6000',
            'hipercard': '#822124',
        };
        return colors[bandeira?.toLowerCase()] || '#e67e22';
    },

    /**
     * Resolver a cor do cartão para usar como accent
     */
    resolverCorCartao(fatura, cartaoId) {
        // 1. Cor vinda da API (cor_cartao ou instituição)
        if (fatura.cartao?.cor_cartao) {
            return fatura.cartao.cor_cartao;
        }

        // 2. Buscar no array de cartões carregados
        const id = cartaoId || fatura.cartao_id || fatura.cartao?.id;
        if (id) {
            const cartaoLocal = STATE.cartoes.find(c => c.id === id);
            if (cartaoLocal) {
                const cor = cartaoLocal.cor_cartao ||
                    cartaoLocal.conta?.instituicao_financeira?.cor_primaria ||
                    cartaoLocal.instituicao_cor;
                if (cor) return cor;
                // Fallback por bandeira
                return Utils.getAccentColor(cartaoLocal.bandeira);
            }
        }

        // 3. Fallback por bandeira da fatura
        return Utils.getAccentColor(fatura.cartao?.bandeira);
    },

    /**
     * Obter nome do mês
     */
    getNomeMes(mes) {
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        return meses[mes - 1] || 'Mês inválido';
    },

    /**
     * Retorna label da frequência de recorrência
     */
    getFreqLabel(freq) {
        const labels = {
            'mensal': 'Mensal',
            'bimestral': 'Bimestral',
            'trimestral': 'Trimestral',
            'semestral': 'Semestral',
            'anual': 'Anual'
        };
        return labels[freq] || 'Recorrente';
    },

    /**
     * Formatar data para exibição
     */
    formatDate(dateString) {
        if (!dateString) return '-';

        // Tenta diferentes formatos
        let date;

        // Se já é um objeto Date
        if (dateString instanceof Date) {
            date = dateString;
        }
        // Se tem o formato YYYY-MM-DD ou ISO 8601 (YYYY-MM-DDTHH:MM:SS.SSSZ)
        else if (typeof dateString === 'string') {
            // Se for formato ISO completo (com T e Z), usar Date constructor diretamente
            if (dateString.includes('T')) {
                date = new Date(dateString);
            } else {
                // Remove qualquer parte de hora se houver (formato simples)
                const datePart = dateString.split(' ')[0];
                const [year, month, day] = datePart.split('-');
                date = new Date(year, month - 1, day);
            }
        }

        // Verifica se é uma data válida
        if (isNaN(date.getTime())) {
            return '-';
        }

        return date.toLocaleDateString('pt-BR');
    },

    /**
     * Formatar bandeira com capitalização
     */
    formatBandeira(bandeira) {
        if (!bandeira) return 'Não informado';
        return bandeira.charAt(0).toUpperCase() + bandeira.slice(1).toLowerCase();
    },

    /**
     * Formatar dinheiro para CSV (sem símbolo)
     */
    formatMoneyForCSV(value) {
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value || 0);
    },

    /**
     * Converter para CSV
     */
    convertToCSV(data) {
        if (data.length === 0) return '';

        const headers = Object.keys(data[0]);
        const csvRows = [];

        csvRows.push(headers.join(','));

        for (const row of data) {
            const values = headers.map(header => {
                const escaped = ('' + row[header]).replace(/"/g, '\\"');
                return `"${escaped}"`;
            });
            csvRows.push(values.join(','));
        }

        return csvRows.join('\n');
    },

    /**
     * Configurar máscara de dinheiro para limite do cartão
     */
    setupLimiteMoneyMask() {
        const limiteInput = document.getElementById('limiteTotal');
        if (!limiteInput) {
            console.error('❌ Campo limiteTotal NÃO encontrado!');
            return;
        }

        // Handler da máscara
        limiteInput.addEventListener('input', function (e) {
            let value = e.target.value;

            // Remove tudo que não é número
            value = value.replace(/[^\d]/g, '');

            // Converte para número (centavos)
            let number = parseInt(value) || 0;

            // Converte centavos para reais e formata
            const reais = number / 100;
            const formatted = reais.toFixed(2)
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            e.target.value = formatted;
        });

        // Formata ao carregar
        limiteInput.value = '0,00';
    },
};
