import {
    calcularRecorrenciaFim,
    escapeHtml,
    formatDate,
    getTipoClass,
    normalizeText,
    parseMoney,
    todayISO,
} from './utils.js';

describe('shared/utils', () => {
    it('parseMoney converte formato BR para numero', () => {
        expect(parseMoney('1.234,56')).toBe(1234.56);
        expect(parseMoney('')).toBe(0);
    });

    it('escapeHtml protege caracteres especiais', () => {
        expect(escapeHtml('<script>"x"&\'y\'</script>'))
            .toBe('&lt;script&gt;&quot;x&quot;&amp;&#39;y&#39;&lt;/script&gt;');
    });

    it('normalizeText remove acentos e baixa caixa', () => {
        expect(normalizeText('Árvore Financeira')).toBe('arvore financeira');
    });

    it('formatDate normaliza ISO para dd/mm/yyyy', () => {
        expect(formatDate('2026-04-01')).toBe('01/04/2026');
    });

    it('calcularRecorrenciaFim calcula data final mensal', () => {
        expect(calcularRecorrenciaFim('2026-01-15', 'mensal', 2)).toBe('2026-03-15');
    });

    it('getTipoClass resolve tipo por descricao', () => {
        expect(getTipoClass('Transferencia interna')).toBe('transferencia');
        expect(getTipoClass('Receita de salario')).toBe('receita');
    });

    it('todayISO retorna formato YYYY-MM-DD', () => {
        expect(todayISO()).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    });
});
