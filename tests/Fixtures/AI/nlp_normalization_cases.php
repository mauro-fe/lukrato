<?php

/**
 * Benchmark dataset para TextNormalizer e NumberNormalizer.
 *
 * Formato: [input, expected_output, component, tags[], notes]
 * component: 'text' | 'number' | 'both' (pipeline completa)
 */

return [
    // ═══ TextNormalizer — Abreviações WhatsApp ═══
    ['vc gastou mt', 'você gastou muito', 'text', ['abbreviation'], 'Abreviação vc+mt'],
    ['hj gastei 50', 'hoje gastei 50', 'text', ['abbreviation'], 'hj=hoje'],
    ['ss pode fazer', 'sim pode fazer', 'text', ['abbreviation'], 'ss=sim'],
    ['nn quero', 'não quero', 'text', ['abbreviation'], 'nn=não'],
    ['blz vlw', 'beleza valeu', 'text', ['abbreviation'], 'blz+vlw'],
    ['qto gastei', 'quanto gastei', 'text', ['abbreviation'], 'qto=quanto'],
    ['pfv me ajuda', 'por favor me ajuda', 'text', ['abbreviation'], 'pfv=por favor'],
    ['oq vc acha', 'o que você acha', 'text', ['abbreviation'], 'oq+vc'],
    ['td certo', 'tudo certo', 'text', ['abbreviation'], 'td=tudo'],
    ['tb quero', 'também quero', 'text', ['abbreviation'], 'tb=também'],

    // TextNormalizer — Pontuação excessiva
    ['gastei 50!!!', 'gastei 50!', 'text', ['punctuation'], 'Excessivo !!!'],
    ['quanto???', 'quanto?', 'text', ['punctuation'], 'Excessivo ???'],
    ['oi.....', 'oi...', 'text', ['punctuation'], 'Excessivo ....'],

    // TextNormalizer — Espaços
    ['gastei   50   no   uber', 'gastei 50 no uber', 'text', ['whitespace'], 'Espaços múltiplos'],

    // TextNormalizer — NÃO deve alterar
    ['assistente virtual', 'assistente virtual', 'text', ['negative'], 'ss dentro de palavra — não expandir'],
    ['possível solução', 'possível solução', 'text', ['negative'], 'ss em "possível" — não expandir'],

    // ═══ NumberNormalizer — "X mil" ═══
    ['2 mil', '2000', 'number', ['mil'], '2 mil'],
    ['5 mil', '5000', 'number', ['mil'], '5 mil'],
    ['1.5 mil', '1500', 'number', ['mil'], '1.5 mil com ponto'],
    ['2,5 mil', '2500', 'number', ['mil'], '2,5 mil com vírgula'],
    ['mil reais', '1000 reais', 'number', ['mil'], 'mil isolado'],

    // NumberNormalizer — "Xk"
    ['2k', '2000', 'number', ['k_suffix'], '2k'],
    ['1.5k', '1500', 'number', ['k_suffix'], '1.5k'],
    ['10k', '10000', 'number', ['k_suffix'], '10k'],

    // NumberNormalizer — Extenso
    ['duzentos', '200', 'number', ['extenso'], 'duzentos'],
    ['quinhentos', '500', 'number', ['extenso'], 'quinhentos'],
    ['trezentos', '300', 'number', ['extenso'], 'trezentos'],
    ['cem', '100', 'number', ['extenso'], 'cem'],
    ['cinquenta', '50', 'number', ['extenso'], 'cinquenta'],
    ['vinte', '20', 'number', ['extenso'], 'vinte'],
    ['cento e cinquenta', '150', 'number', ['extenso'], 'cento e cinquenta'],

    // NumberNormalizer — Gírias monetárias
    ['50 conto', '50', 'number', ['slang'], 'conto removido'],
    ['100 pila', '100', 'number', ['slang'], 'pila removido'],
    ['50 paus', '50', 'number', ['slang'], 'paus removido'],
    ['30 mango', '30', 'number', ['slang'], 'mango removido'],
    ['uma nota', '100', 'number', ['slang'], 'uma nota = R$100'],

    // NumberNormalizer — NÃO deve alterar
    ['milho', 'milho', 'number', ['negative'], 'milho ≠ mil'],
    ['milagre', 'milagre', 'number', ['negative'], 'milagre ≠ mil'],
    ['similar', 'similar', 'number', ['negative'], 'similar ≠ mil'],

    // ═══ NumberNormalizer::parseValue — Parsing de valores BR ═══
    // Formato: [input, expected_float, 'parse_value', tags[], notes]
    ['1.560,00', 1560.0, 'parse_value', ['br_format'], 'BR completo'],
    ['42,35', 42.35, 'parse_value', ['br_format'], 'Vírgula decimal'],
    ['1560', 1560.0, 'parse_value', ['integer'], 'Inteiro'],
    ['1.500', 1500.0, 'parse_value', ['br_format'], 'Milhar BR'],
    ['42.35', 42.35, 'parse_value', ['en_format'], 'Ponto decimal EN'],
    ['R$ 50', 50.0, 'parse_value', ['currency_prefix'], 'Com R$'],
    ['R$50', 50.0, 'parse_value', ['currency_prefix'], 'Sem espaço R$'],
    ['0', 0.0, 'parse_value', ['edge_case'], 'Zero'],
    ['', 0.0, 'parse_value', ['edge_case'], 'Vazio'],
];
