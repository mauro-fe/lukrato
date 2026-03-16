<?php

/**
 * Benchmark dataset para extração de transações.
 *
 * Cada caso: [
 *   'input'    => mensagem original,
 *   'expected' => campos que DEVEM estar presentes no resultado,
 *   'tags'     => tags para filtragem,
 *   'notes'    => notas explicativas,
 * ]
 *
 * Campos possíveis em expected:
 *   descricao, valor, tipo (despesa/receita), forma_pagamento,
 *   eh_parcelado, total_parcelas, nome_cartao
 *
 * Valor null em expected significa "campo não deve existir ou é irrelevante"
 */

return [
    // ═══ Verbo + valor + descrição ═══
    [
        'input'    => 'gastei 40 no uber',
        'expected' => ['descricao' => 'Uber', 'valor' => 40.0, 'tipo' => 'despesa'],
        'tags'     => ['basic'],
        'notes'    => 'Padrão mais comum',
    ],
    [
        'input'    => 'paguei 32.50 de luz',
        'expected' => ['descricao' => 'Luz', 'valor' => 32.50, 'tipo' => 'despesa'],
        'tags'     => ['basic', 'decimal'],
        'notes'    => 'Decimal com ponto',
    ],
    [
        'input'    => 'paguei 32,50 de luz',
        'expected' => ['descricao' => 'Luz', 'valor' => 32.50, 'tipo' => 'despesa'],
        'tags'     => ['basic', 'br_format'],
        'notes'    => 'Decimal com vírgula BR',
    ],
    [
        'input'    => 'comprei 120 de mercado',
        'expected' => ['descricao' => 'Mercado', 'valor' => 120.0, 'tipo' => 'despesa'],
        'tags'     => ['basic'],
        'notes'    => 'Verbo comprei',
    ],
    [
        'input'    => 'gastei 30 com produto de limpeza no mercado',
        'expected' => ['descricao' => 'Produto De Limpeza', 'valor' => 30.0, 'tipo' => 'despesa'],
        'tags'     => ['context'],
        'notes'    => 'Descricao deve focar no item e nao no estabelecimento',
    ],
    [
        'input'    => 'recebi 5000 de salário',
        'expected' => ['descricao' => 'Salário', 'valor' => 5000.0, 'tipo' => 'receita'],
        'tags'     => ['income'],
        'notes'    => 'Receita explícita',
    ],
    [
        'input'    => 'ganhei 1500 freelance',
        'expected' => ['descricao' => 'Freelance', 'valor' => 1500.0, 'tipo' => 'receita'],
        'tags'     => ['income'],
        'notes'    => 'Freelance sem preposição',
    ],

    // ═══ Descrição + valor (sem verbo) ═══
    [
        'input'    => 'uber 32',
        'expected' => ['descricao' => 'Uber', 'valor' => 32.0, 'tipo' => 'despesa'],
        'tags'     => ['compact', 'whatsapp'],
        'notes'    => 'Formato compacto desc+val',
    ],
    [
        'input'    => 'ifood 45.90',
        'expected' => ['descricao' => 'Ifood', 'valor' => 45.90, 'tipo' => 'despesa'],
        'tags'     => ['compact', 'decimal'],
        'notes'    => 'iFood com decimal',
    ],
    [
        'input'    => 'mercado 120',
        'expected' => ['descricao' => 'Mercado', 'valor' => 120.0, 'tipo' => 'despesa'],
        'tags'     => ['compact'],
        'notes'    => 'Mercado valor inteiro',
    ],

    // ═══ Valor + descrição ═══
    [
        'input'    => '40 uber',
        'expected' => ['descricao' => 'Uber', 'valor' => 40.0, 'tipo' => 'despesa'],
        'tags'     => ['inverted'],
        'notes'    => 'Valor primeiro',
    ],
    [
        'input'    => '50 conto de luz',
        'expected' => ['descricao' => 'Luz', 'valor' => 50.0, 'tipo' => 'despesa'],
        'tags'     => ['colloquial'],
        'notes'    => 'Gíria conto + preposição',
    ],

    // ═══ PIX ═══
    [
        'input'    => 'mandei pix 200 pro joão',
        'expected' => ['valor' => 200.0, 'tipo' => 'despesa', 'forma_pagamento' => 'pix'],
        'tags'     => ['pix'],
        'notes'    => 'PIX informal',
    ],
    [
        'input'    => 'fiz pix de 150 pra maria',
        'expected' => ['valor' => 150.0, 'tipo' => 'despesa', 'forma_pagamento' => 'pix'],
        'tags'     => ['pix'],
        'notes'    => 'PIX com preposição',
    ],

    // ═══ Cartão / parcelamento ═══
    [
        'input'    => 'parcelei 3000 em 12x no inter',
        'expected' => ['valor' => 3000.0, 'tipo' => 'despesa', 'eh_parcelado' => true, 'total_parcelas' => 12],
        'tags'     => ['installment'],
        'notes'    => 'Parcelamento completo',
    ],
    [
        'input'    => '12x de 99 geladeira',
        'expected' => ['valor' => 1188.0, 'tipo' => 'despesa', 'eh_parcelado' => true, 'total_parcelas' => 12],
        'tags'     => ['installment'],
        'notes'    => '12x99 = total 1188',
    ],
    [
        'input'    => 'comprei geladeira no cartão por 1500',
        'expected' => ['valor' => 1500.0, 'tipo' => 'despesa', 'forma_pagamento' => 'cartao_credito'],
        'tags'     => ['credit_card'],
        'notes'    => 'Compra no cartão',
    ],

    // ═══ Fatura / boleto ═══
    [
        'input'    => 'fatura de 800 do nubank',
        'expected' => ['valor' => 800.0, 'tipo' => 'despesa'],
        'tags'     => ['bill'],
        'notes'    => 'Fatura de cartão',
    ],
    [
        'input'    => 'boleto de 230 da internet',
        'expected' => ['valor' => 230.0, 'tipo' => 'despesa'],
        'tags'     => ['bill'],
        'notes'    => 'Boleto',
    ],

    // ═══ Valores coloquiais / gíria ═══
    [
        'input'    => 'gastei 2 mil no mercado',
        'expected' => ['valor' => 2000.0, 'tipo' => 'despesa'],
        'tags'     => ['colloquial'],
        'notes'    => '2 mil = 2000 via NumberNormalizer',
    ],
    [
        'input'    => 'uns 30 de uber',
        'expected' => ['valor' => 30.0, 'tipo' => 'despesa'],
        'tags'     => ['colloquial'],
        'notes'    => 'Valor aproximado "uns"',
    ],
    [
        'input'    => 'salário 5000',
        'expected' => ['descricao' => 'Salário', 'valor' => 5000.0, 'tipo' => 'receita'],
        'tags'     => ['income', 'compact'],
        'notes'    => 'Salário curto',
    ],

    // ═══ Formato monetário BR ═══
    [
        'input'    => 'gastei R$ 1.560,00 no mercado',
        'expected' => ['valor' => 1560.0, 'tipo' => 'despesa'],
        'tags'     => ['br_format'],
        'notes'    => 'Formato BR completo com R$',
    ],
    [
        'input'    => 'paguei 1.500 de aluguel',
        'expected' => ['valor' => 1500.0, 'tipo' => 'despesa'],
        'tags'     => ['br_format'],
        'notes'    => '1.500 = milhar BR',
    ],

    // ═══ Casos que NÃO devem extrair ═══
    [
        'input'    => 'olá tudo bem',
        'expected' => null,
        'tags'     => ['negative'],
        'notes'    => 'Saudação, não transação',
    ],
    [
        'input'    => 'quanto custa um uber',
        'expected' => null,
        'tags'     => ['negative', 'edge_case'],
        'notes'    => 'Pergunta, não registro',
    ],
    [
        'input'    => 'bom dia',
        'expected' => null,
        'tags'     => ['negative'],
        'notes'    => 'Saudação curta',
    ],
    [
        'input'    => 'sim',
        'expected' => null,
        'tags'     => ['negative'],
        'notes'    => 'Confirmação, não transação',
    ],
    [
        'input'    => 'ab',
        'expected' => null,
        'tags'     => ['negative'],
        'notes'    => 'Msg muito curta',
    ],
];
