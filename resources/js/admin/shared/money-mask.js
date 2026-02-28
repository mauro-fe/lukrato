/**
 * ============================================================================
 * LUKRATO — Shared Money Mask
 * ============================================================================
 * Currency input masking and formatting utilities.
 *
 * import { setupMoneyMask, applyMoneyMask } from '../shared/money-mask';
 * ============================================================================
 */

import { formatMoneyInput, parseMoney } from './utils.js';

// ─── Apply Mask ─────────────────────────────────────────────────────────────

/**
 * Formatar valor em input de dinheiro (BRL) em tempo real
 * @param {HTMLInputElement} input
 */
export function applyMoneyMask(input) {
    let value = input.value.replace(/[^\d-]/g, '');

    // Detecta negativo
    const isNegative = value.startsWith('-');
    value = value.replace('-', '');

    let number = parseInt(value) || 0;

    const formatted = formatMoneyInput(number, isNegative);
    input.value = formatted;
}

// ─── Setup ──────────────────────────────────────────────────────────────────

/**
 * Inicializa máscara monetária em um input
 * @param {HTMLInputElement|string} inputOrSelector — Elemento ou seletor CSS
 * @param {Object} [opts]
 * @param {boolean} [opts.allowNegative=true] — Permitir valores negativos
 * @param {Function} [opts.onChange] — Callback quando valor mudar
 * @returns {Function} Cleanup function para remover listener
 */
export function setupMoneyMask(inputOrSelector, opts = {}) {
    const { allowNegative = true, onChange } = opts;

    const input = typeof inputOrSelector === 'string'
        ? document.querySelector(inputOrSelector)
        : inputOrSelector;

    if (!input) return () => { };

    const handler = (e) => {
        let value = e.target.value.replace(/[^\d-]/g, '');

        // Controlar negativo
        const isNegative = allowNegative && value.startsWith('-');
        value = value.replace('-', '');

        let number = parseInt(value) || 0;
        const formatted = formatMoneyInput(number, isNegative);
        e.target.value = formatted;

        if (onChange) onChange(parseMoney(formatted));
    };

    input.addEventListener('input', handler);

    return () => input.removeEventListener('input', handler);
}

/**
 * Inicializa máscara monetária em múltiplos inputs
 * @param {string} selector — Seletor CSS (ex: '.money-input')
 * @param {Object} [opts]
 * @returns {Function} Cleanup function
 */
export function setupAllMoneyMasks(selector, opts = {}) {
    const inputs = document.querySelectorAll(selector);
    const cleanups = [];

    inputs.forEach((input) => {
        cleanups.push(setupMoneyMask(input, opts));
    });

    return () => cleanups.forEach((fn) => fn());
}

/**
 * Definir valor programaticamente em um input com máscara
 * @param {HTMLInputElement|string} inputOrSelector
 * @param {number} value — Valor numérico (ex: 1234.56)
 */
export function setMoneyValue(inputOrSelector, value) {
    const input = typeof inputOrSelector === 'string'
        ? document.querySelector(inputOrSelector)
        : inputOrSelector;

    if (!input) return;

    const isNegative = value < 0;
    const centavos = Math.round(Math.abs(value) * 100);
    input.value = formatMoneyInput(centavos, isNegative);
}

/**
 * Obter valor numérico de um input com máscara
 * @param {HTMLInputElement|string} inputOrSelector
 * @returns {number}
 */
export function getMoneyValue(inputOrSelector) {
    const input = typeof inputOrSelector === 'string'
        ? document.querySelector(inputOrSelector)
        : inputOrSelector;

    if (!input) return 0;
    return parseMoney(input.value);
}
