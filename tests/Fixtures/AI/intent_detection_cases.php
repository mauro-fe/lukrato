<?php

/**
 * Benchmark dataset para detecção de intent.
 *
 * Cada caso: [mensagem, intent_esperado, confidence_minima, tags[], notas]
 *
 * Tags: 'whatsapp', 'informal', 'ambiguous', 'edge_case', 'colloquial',
 *       'abbreviation', 'negation', 'multi_intent', 'admin'
 *
 * Como adicionar novos casos:
 *  1. Adicione ao array na seção correta
 *  2. Rode: php vendor/bin/phpunit --filter=AIBenchmark
 *  3. Verifique se o intent correto é retornado
 */

return [
    // ════════════════════════════════════════════════════════════════
    // EXTRACT_TRANSACTION — Mensagens que devem virar lançamento
    // ════════════════════════════════════════════════════════════════

    // Verbo + valor + descrição (padrão clássico)
    ['gastei 40 no uber', 'extract_transaction', 0.7, ['informal'], 'Padrão mais comum BR'],
    ['paguei 32.50 de luz', 'extract_transaction', 0.7, [], 'Valor decimal com ponto'],
    ['paguei 32,50 de luz', 'extract_transaction', 0.7, [], 'Valor decimal com vírgula BR'],
    ['comprei 120 de mercado', 'extract_transaction', 0.7, [], 'Compra genérica'],
    ['torrei 200 no shopping', 'extract_transaction', 0.7, ['informal'], 'Gíria torrei'],
    ['recebi 5000 de salário', 'create_entity', 0.7, [], 'Receita explícita — EntityCreation wins due to implicit lancamento'],
    ['ganhei 1500 de freelance', 'extract_transaction', 0.7, [], 'Receita freelance'],
    ['custou 89 reais', 'extract_transaction', 0.6, [], '3a pessoa - custou'],

    // Descrição + valor (sem verbo)
    ['uber 32', 'extract_transaction', 0.6, ['whatsapp'], 'WhatsApp curto desc+val'],
    ['ifood 45.90', 'extract_transaction', 0.6, ['whatsapp'], 'WhatsApp ifood'],
    ['mercado 120', 'extract_transaction', 0.6, ['whatsapp'], 'WhatsApp mercado'],
    ['gasolina 80', 'extract_transaction', 0.6, ['whatsapp'], 'WhatsApp gasolina'],
    ['luz 150', 'extract_transaction', 0.6, ['whatsapp'], 'WhatsApp conta de luz'],
    ['farmacia 45', 'extract_transaction', 0.6, ['whatsapp'], 'WhatsApp farmácia'],

    // Valor + descrição
    ['40 uber', 'extract_transaction', 0.6, ['whatsapp'], 'WhatsApp val+desc'],
    ['32.50 ifood', 'extract_transaction', 0.6, ['whatsapp'], 'Valor primeiro'],
    ['200 reais no mercado', 'extract_transaction', 0.6, ['colloquial'], 'Valor coloquial'],
    ['50 conto de luz', 'extract_transaction', 0.6, ['colloquial'], 'Gíria conto'],

    // Cartão / parcelamento
    ['parcelei 3000 em 12x no inter', 'extract_transaction', 0.8, [], 'Parcelamento explícito'],
    ['comprei no cartão 500 de sapato', 'extract_transaction', 0.8, [], 'Compra no cartão'],
    ['12x de 99 geladeira', 'extract_transaction', 0.8, [], 'Parcelas primeiro'],

    // PIX
    ['mandei pix 200 pro joão', 'extract_transaction', 0.7, ['informal'], 'PIX informal'],
    ['fiz pix de 150 pra maria', 'extract_transaction', 0.7, ['informal'], 'PIX com nome'],

    // Fatura / boleto
    ['fatura de 800 do nubank', 'extract_transaction', 0.6, [], 'Fatura cartão'],
    ['boleto de 230 da internet', 'extract_transaction', 0.6, [], 'Boleto'],

    // Coloquial / gíria
    ['gastei 2k no mercado', 'extract_transaction', 0.6, ['colloquial'], 'Valor 2k'],
    ['torrei mil reais ontem', 'extract_transaction', 0.6, ['colloquial'], 'Mil reais'],
    ['uns 30 de uber', 'extract_transaction', 0.6, ['colloquial'], 'Valor aproximado'],
    ['salário 5000', 'extract_transaction', 0.6, ['whatsapp'], 'Salário curto'],

    // WhatsApp abreviações
    ['gastei 50 conto hj no mercado', 'extract_transaction', 0.6, ['whatsapp', 'abbreviation'], 'hj=hoje, conto=reais'],

    // Perífrases verbais
    ['acabei de pagar 200 de luz', 'create_entity', 0.7, ['informal'], 'Perífrase — EntityCreation wins'],
    ['to pagando 100 de internet', 'extract_transaction', 0.7, ['informal'], 'Gerúndio'],
    ['vou pagar 300 de aluguel', 'create_entity', 0.7, ['informal'], 'Futuro perifrástico — EntityCreation wins'],

    // ════════════════════════════════════════════════════════════════
    // QUICK_QUERY — Consultas que podem ser respondidas com SQL
    // ════════════════════════════════════════════════════════════════

    ['quanto gastei esse mês', 'quick_query', 0.7, [], 'Query padrão'],
    ['quanto recebi', 'quick_query', 0.7, [], 'Query receita'],
    ['qual meu saldo', 'quick_query', 0.7, [], 'Query saldo'],
    ['quanto tenho', 'quick_query', 0.7, [], 'Query saldo informal'],
    ['quantos lançamentos tenho', 'quick_query', 0.7, [], 'Contagem — KNOWN ISSUE: EntityCreation may win'],
    ['qual meu maior gasto', 'quick_query', 0.7, [], 'Query top gasto'],
    ['gastos do mês', 'quick_query', 0.7, [], 'Gastos período'],
    ['me mostra meu saldo', 'quick_query', 0.7, ['informal'], 'Me mostra'],
    ['como to de gastos', 'quick_query', 0.0, ['informal'], 'Informal como to — may fallback to chat'],
    ['sobrou quanto', 'quick_query', 0.7, ['informal'], 'Sobrou quanto'],
    ['quanto eu devo', 'quick_query', 0.7, [], 'Dívida'],
    ['contas a pagar', 'quick_query', 0.7, [], 'Contas a pagar'],
    ['lista meus gastos', 'quick_query', 0.7, [], 'Listar gastos'],
    ['média de gasto', 'quick_query', 0.7, [], 'Média'],
    ['quanto sobrou esse mês', 'quick_query', 0.7, [], 'Sobra mensal'],

    // Faturas de cartão
    ['qual o valor da fatura', 'quick_query', 0.7, [], 'Fatura valor genérico'],
    ['fatura do nubank', 'quick_query', 0.7, [], 'Fatura cartão específico'],
    ['quanto devo no cartão', 'quick_query', 0.7, [], 'Dívida cartão'],
    ['quanto devo no nubank', 'quick_query', 0.7, [], 'Dívida cartão específico'],
    ['itens da fatura', 'quick_query', 0.7, [], 'Itens fatura'],
    ['o que tem na fatura', 'quick_query', 0.7, [], 'Itens fatura informal'],
    ['fatura do cartão esse mês', 'quick_query', 0.7, [], 'Fatura período'],
    ['proxima fatura', 'quick_query', 0.7, [], 'Próxima fatura'],

    // ════════════════════════════════════════════════════════════════
    // PAY_FATURA — Pagamento de fatura de cartão
    // ════════════════════════════════════════════════════════════════

    ['pagar fatura do nubank', 'pay_fatura', 0.7, [], 'Pagar fatura específica'],
    ['quero pagar a fatura', 'pay_fatura', 0.7, [], 'Pagar fatura genérica'],
    ['paga a fatura do cartão', 'pay_fatura', 0.7, [], 'Pagar cartão informal'],
    ['quitar fatura do itaú', 'pay_fatura', 0.7, [], 'Quitar fatura banco'],
    ['pagar cartão', 'pay_fatura', 0.7, [], 'Pagar cartão curto'],

    // ════════════════════════════════════════════════════════════════
    // CREATE_ENTITY — Criação explícita de entidades
    // ════════════════════════════════════════════════════════════════

    ['criar meta de viagem de 5000', 'create_entity', 0.8, [], 'Criar meta explícito'],
    ['registrar despesa de 100 de supermercado', 'create_entity', 0.8, [], 'Registrar despesa'],
    ['definir orçamento de 800 para alimentação', 'create_entity', 0.8, [], 'Definir orçamento'],
    ['adicionar categoria Pets tipo despesa', 'create_entity', 0.8, [], 'Criar categoria'],
    ['criar subcategoria Ração', 'create_entity', 0.8, [], 'Criar subcategoria'],
    ['quero criar uma conta no Nubank', 'create_entity', 0.8, [], 'Criar conta'],
    ['registrar receita de 3000 de freelance', 'create_entity', 0.8, [], 'Registrar receita'],

    // ════════════════════════════════════════════════════════════════
    // ANALYZE — Análise financeira / insights
    // ════════════════════════════════════════════════════════════════

    ['analise meus gastos', 'analyze', 0.7, [], 'Análise explícita'],
    ['quero uma análise financeira', 'analyze', 0.7, [], 'Pedido de análise'],
    ['como posso economizar', 'analyze', 0.7, [], 'Dica economia'],
    ['relatório do mês', 'analyze', 0.7, [], 'Relatório'],
    ['resumo financeiro', 'analyze', 0.7, [], 'Resumo'],
    ['comparar com mês passado', 'analyze', 0.7, [], 'Comparação'],
    ['to no vermelho', 'analyze', 0.7, ['informal'], 'Informal endividado'],
    ['minha saúde financeira', 'analyze', 0.7, [], 'Saúde financeira'],
    ['previsão dos meus gastos', 'analyze', 0.7, [], 'Previsão'],
    ['me ajuda a entender meus gastos', 'analyze', 0.7, ['informal'], 'Pedir ajuda'],

    // ════════════════════════════════════════════════════════════════
    // CHAT — Conversa geral / saudações / off-topic
    // ════════════════════════════════════════════════════════════════

    ['olá', 'chat', 0.0, [], 'Saudação simples'],
    ['bom dia', 'chat', 0.0, [], 'Saudação bom dia'],
    ['oi, tudo bem?', 'chat', 0.0, [], 'Saudação com pergunta'],
    ['obrigado', 'chat', 0.0, [], 'Agradecimento'],
    ['quem é você?', 'chat', 0.0, [], 'Pergunta identidade'],
    ['o que você faz?', 'chat', 0.0, [], 'Pergunta funcionalidade'],
    ['me conta uma piada', 'chat', 0.0, [], 'Off-topic'],
    ['qual a previsão do tempo', 'chat', 0.0, [], 'Off-topic clima'],
    ['tudo certo por aqui', 'chat', 0.0, [], 'Conversa genérica'],

    // ════════════════════════════════════════════════════════════════
    // AMBIGUOUS — Frases que são armadilhas para o NLP
    // ════════════════════════════════════════════════════════════════

    // Estas NÃO devem ser extract_transaction:
    ['quanto custa um uber', 'quick_query', 0.0, ['ambiguous', 'edge_case'], 'Pergunta, não transação'],
    ['quando entra o salário', 'chat', 0.0, ['ambiguous'], 'Pergunta sobre data, não transação'],
    ['moro na rua 5', 'chat', 0.0, ['ambiguous', 'edge_case'], 'Número que não é valor'],
    ['tenho 2 filhos', 'chat', 0.0, ['ambiguous', 'edge_case'], 'Número que não é valor'],
    ['vi 3 episodios da netflix', 'chat', 0.0, ['ambiguous', 'edge_case'], 'Netflix sem valor monetário'],

    // Estas devem ser reconhecidas mesmo sendo ambíguas:
    ['netflix 15', 'extract_transaction', 0.6, ['ambiguous'], 'Assinatura curta'],
    ['uber 99', 'extract_transaction', 0.6, ['ambiguous', 'edge_case'], '99 pode ser a marca de app'],

    // ════════════════════════════════════════════════════════════════
    // CATEGORIZE — Pedidos de categorização
    // ════════════════════════════════════════════════════════════════

    ['categoriza esse gasto', 'categorize', 0.6, [], 'Categorização explícita'],
    ['classifica essa despesa', 'categorize', 0.6, [], 'Classificar despesa'],
    ['qual a categoria de supermercado', 'categorize', 0.6, [], 'Qual categoria'],
    ['sugere uma categoria pra isso', 'categorize', 0.6, [], 'Sugerir categoria'],
    ['que tipo de gasto é esse', 'categorize', 0.6, [], 'Que tipo de gasto'],

    // ════════════════════════════════════════════════════════════════
    // EDGE CASES — Frases com abreviações pesadas WhatsApp
    // ════════════════════════════════════════════════════════════════

    ['gastei 50 conto hj', 'extract_transaction', 0.6, ['whatsapp', 'abbreviation'], 'Abrev hj + gíria conto'],
    ['qto gastei esse mes', 'quick_query', 0.6, ['abbreviation'], 'qto = quanto'],
    ['vc pode analisar meus gastos', 'analyze', 0.6, ['abbreviation'], 'vc = você'],
    ['nn quero mais', 'chat', 0.0, ['abbreviation', 'ambiguous'], 'nn = não, sem pending action'],
    ['oq eu gasto mais', 'quick_query', 0.6, ['abbreviation'], 'oq = o que'],

    // ════════════════════════════════════════════════════════════════
    // MORE EDGE CASES — Falsos positivos comuns
    // ════════════════════════════════════════════════════════════════

    ['vim na página 3', 'chat', 0.0, ['edge_case'], 'Número que não é valor monetário'],
    ['assisti 5 episódios', 'chat', 0.0, ['edge_case'], 'Número que não é valor monetário'],
    ['tenho 3 metas ativas', 'chat', 0.0, ['ambiguous', 'edge_case'], 'Número que não é valor monetário'],
    ['meu cep é 01310-100', 'chat', 0.0, ['edge_case'], 'CEP não é valor monetário'],
    ['ligaram 2 vezes', 'chat', 0.0, ['edge_case'], 'Contagem, não valor'],
    ['pedi 2 pizzas', 'chat', 0.0, ['ambiguous', 'edge_case'], 'Contagem sem valor monetário'],

    // ════════════════════════════════════════════════════════════════
    // MORE TRANSACTIONS — Formatos variados
    // ════════════════════════════════════════════════════════════════

    ['gastei R$ 42,50 no almoço', 'extract_transaction', 0.7, [], 'R$ com vírgula'],
    ['paguei a conta de água 95 reais', 'extract_transaction', 0.6, [], 'Conta de água com reais'],
    ['depositaram 3000 na minha conta', 'extract_transaction', 0.6, [], 'Depositaram = receita'],
    ['transferi 500 pro João', 'extract_transaction', 0.7, [], 'Transferência'],
    ['deu 120 reais o jantar', 'extract_transaction', 0.6, ['informal'], '"deu" como verbo de custo'],
];
