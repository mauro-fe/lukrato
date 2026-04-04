<?php

declare(strict_types=1);

/**
 * Fixtures para testes de detecção de entity type na EntityCreationIntentRule.
 *
 * Formato: [message, expected_entity_type, confidence_min, tags[], notes]
 *
 * expected_entity_type: 'meta'|'lançamento'|'orcamento'|'categoria'|'subcategoria'|'conta'|null
 */
return [

    // ═══════════════════════════════════════════════════════════════
    // Explicit creation (using creation verbs)
    // ═══════════════════════════════════════════════════════════════

    ['criar meta de viagem de 5000', 'meta', 0.9, ['explicit'], 'criar + meta explícita'],
    ['registrar despesa de 100 de supermercado', 'lancamento', 0.9, ['explicit'], 'registrar + despesa'],
    ['definir orçamento de 800 para alimentação', 'orcamento', 0.9, ['explicit'], 'definir + orçamento'],
    ['adicionar categoria Pets tipo despesa', 'categoria', 0.9, ['explicit'], 'adicionar + categoria'],
    ['criar subcategoria Ração', 'subcategoria', 0.9, ['explicit'], 'criar + subcategoria'],
    ['criar conta no Nubank', 'conta', 0.9, ['explicit'], 'criar + conta bancária'],
    ['cadastrar meta de 10000 para carro novo', 'meta', 0.9, ['explicit'], 'cadastrar + meta'],
    ['incluir despesa de aluguel 1500', 'lancamento', 0.9, ['explicit'], 'incluir + despesa'],
    ['registrar receita de 3000 de freelance', 'lancamento', 0.9, ['explicit'], 'registrar + receita'],
    ['quero criar uma conta no Inter', 'conta', 0.9, ['explicit'], 'quero criar + conta bancária'],

    // ═══════════════════════════════════════════════════════════════
    // Implicit creation (financial context without explicit verb)
    // ═══════════════════════════════════════════════════════════════

    ['preciso pagar aluguel de 1500', 'lancamento', 0.85, ['implicit'], 'preciso pagar + valor'],
    ['quero juntar 10 mil pra viagem', 'meta', 0.85, ['implicit'], 'quero juntar + valor → meta'],
    ['quero economizar 5000 até dezembro', 'meta', 0.85, ['implicit'], 'quero economizar + valor → meta'],
    ['não quero gastar mais de 500 em alimentação', 'orcamento', 0.85, ['implicit'], 'não gastar mais de → orçamento'],
    ['gastar no máximo 300 em delivery', 'orcamento', 0.85, ['implicit'], 'gastar no máximo → orçamento'],
    ['tenho que pagar a fatura de 800', 'lancamento', 0.85, ['implicit'], 'tenho que pagar + fatura'],
    ['parcelei 3000 no nubank', 'lancamento', 0.85, ['implicit'], 'parcelei + valor → lançamento'],
    ['recebi 5000 de salário', 'lancamento', 0.85, ['implicit'], 'recebi + valor → lançamento implícito'],
    ['vou pagar 200 de internet', 'lancamento', 0.85, ['implicit'], 'vou pagar + valor'],

    // ═══════════════════════════════════════════════════════════════
    // Edge cases
    // ═══════════════════════════════════════════════════════════════

    ['criar', null, 0.0, ['edge'], 'verbo criar sem entidade especificada'],
    ['meta de vida', 'meta', 0.0, ['edge'], 'meta sem verbo — detecta pelo padrão'],
    ['abrir conta poupança no Itaú', 'conta', 0.9, ['edge'], 'abrir conta poupança — sinônimo de criar'],
    ['estabelecer limite de 2000 para lazer', 'orcamento', 0.9, ['edge'], 'estabelecer limite → orçamento'],
    ['anotar gasto de 50 de uber', 'lancamento', 0.9, ['edge'], 'anotar + gasto'],
    ['botar a despesa do mercado 120', 'lancamento', 0.9, ['edge'], 'botar + despesa (coloquial)'],
    ['lançar receita de 2000', 'lancamento', 0.9, ['explicit'], 'lançar + receita'],
    ['inserir categoria Saúde', 'categoria', 0.9, ['explicit'], 'inserir + categoria'],
    ['colocar meta de emergência de 10000', 'meta', 0.9, ['explicit'], 'colocar + meta'],
    ['quero guardar 500 por mês', 'meta', 0.85, ['implicit'], 'quero guardar → meta implícita'],
];
