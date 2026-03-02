/**
 * ============================================================================
 * LUKRATO — Shared Utilities
 * ============================================================================
 * Funções utilitárias compartilhadas entre todos os módulos.
 * Importar apenas o que precisar: import { formatMoney, parseMoney } from '../shared/utils';
 * ============================================================================
 */

// ─── Formatação de Moeda ────────────────────────────────────────────────────

/**
 * Formata valor numérico para moeda brasileira (R$ 1.234,56)
 * @param {number} value
 * @returns {string}
 */
export function formatMoney(value) {
    const num = Number(value) || 0;
    if (Math.abs(num) < 0.01) return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(0);
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(num);
}

/**
 * Parse string de moeda brasileira para float (1.234,56 → 1234.56)
 * @param {string} str
 * @returns {number}
 */
export function parseMoney(str) {
    if (!str) return 0;
    return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
}

/**
 * Formata valor inteiro (centavos) para exibição em input (12345 → 123,45)
 * @param {number} value - Valor em centavos
 * @param {boolean} isNegative
 * @returns {string}
 */
export function formatMoneyInput(value, isNegative = false) {
    const reais = value / 100;
    let formatted = reais
        .toFixed(2)
        .replace('.', ',')
        .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return isNegative ? '-' + formatted : formatted;
}

/**
 * Formata input de dinheiro em tempo real (event handler)
 * @param {HTMLInputElement} input
 */
export function formatarDinheiro(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = (parseInt(valor) / 100).toFixed(2);
    valor = valor.replace('.', ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = valor;
}

// ─── Formatação de Data ─────────────────────────────────────────────────────

/**
 * Formata ISO date string para dd/mm/yyyy
 * @param {string} iso - Data ISO (YYYY-MM-DD ou YYYY-MM-DDTHH:MM:SS)
 * @returns {string}
 */
export function formatDate(iso) {
    if (!iso) return '-';
    if (typeof iso === 'string') {
        const normalized = iso.trim();
        const datePart = normalized.includes('T') ? normalized.split('T')[0] : normalized.split(' ')[0];
        if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
            const [year, month, day] = datePart.split('-');
            if (year && month && day) return `${day}/${month}/${year}`;
        }
    }
    const d = new Date(iso);
    return isNaN(d) ? '-' : d.toLocaleDateString('pt-BR');
}

/**
 * Retorna a data atual formatada como YYYY-MM-DD
 * @returns {string}
 */
export function todayISO() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/**
 * Retorna a hora atual formatada como HH:MM
 * @returns {string}
 */
export function nowTime() {
    const d = new Date();
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

// ─── Strings ────────────────────────────────────────────────────────────────

/**
 * Escapa HTML para prevenir XSS
 * @param {*} value
 * @returns {string}
 */
export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[m] || m));
}

/**
 * Normaliza texto removendo acentos e convertendo para minúsculo
 * @param {string} str
 * @returns {string}
 */
export function normalizeText(str) {
    return String(str ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
}

// ─── Funções de Tempo ───────────────────────────────────────────────────────

/**
 * Debounce — atrasa execução até parar de chamar
 * @param {Function} fn
 * @param {number} ms - Delay em millisegundos
 * @returns {Function}
 */
export function debounce(fn, ms) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), ms);
    };
}

// ─── Cálculos de Recorrência ────────────────────────────────────────────────

/**
 * Calcula a data fim de uma recorrência
 * @param {string} dataInicio - YYYY-MM-DD
 * @param {string} frequencia - diario|semanal|mensal|anual
 * @param {number} repeticoes
 * @returns {string|null} - YYYY-MM-DD ou null
 */
export function calcularRecorrenciaFim(dataInicio, frequencia, repeticoes) {
    if (!dataInicio || !frequencia || !repeticoes || repeticoes < 1) {
        return null;
    }
    try {
        const datePart = dataInicio.split(' ')[0].split('T')[0];
        const [year, month, day] = datePart.split('-').map(Number);
        const dataFim = new Date(year, month - 1, day);

        switch (frequencia) {
            case 'diario':
                dataFim.setDate(dataFim.getDate() + repeticoes);
                break;
            case 'semanal':
                dataFim.setDate(dataFim.getDate() + (repeticoes * 7));
                break;
            case 'mensal':
                dataFim.setMonth(dataFim.getMonth() + repeticoes);
                break;
            case 'anual':
                dataFim.setFullYear(dataFim.getFullYear() + repeticoes);
                break;
            default:
                return null;
        }

        const yyyy = dataFim.getFullYear();
        const mm = String(dataFim.getMonth() + 1).padStart(2, '0');
        const dd = String(dataFim.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    } catch (e) {
        console.error('Erro ao calcular recorrencia_fim:', e);
        return null;
    }
}

// ─── Tipo / Classificação ───────────────────────────────────────────────────

/**
 * Retorna a classe CSS para um tipo de lançamento
 * @param {string} tipo
 * @returns {string}
 */
export function getTipoClass(tipo) {
    const normalized = String(tipo || '').toLowerCase();
    if (normalized.includes('receita')) return 'receita';
    if (normalized.includes('despesa')) return 'despesa';
    if (normalized.includes('transfer')) return 'transferencia';
    return '';
}

/**
 * Retorna o nome do mês
 * @param {number} mes - 1-12
 * @returns {string}
 */
export function getNomeMes(mes) {
    const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    return meses[(mes - 1)] || '';
}

/**
 * Nome completo do mês
 * @param {number} mes - 1-12
 * @returns {string}
 */
export function getNomeMesCompleto(mes) {
    const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return meses[(mes - 1)] || '';
}
