<?php

declare(strict_types=1);

namespace Application\Services\AI\Schemas;

/**
 * JSON Schemas para extração estruturada de entidades financeiras via OpenAI function calling.
 *
 * Usados como "tools" no endpoint chat/completions para garantir
 * respostas 100% formatadas e parseáveis, eliminando erros de parsing JSON.
 */
class EntitySchemas
{
    /**
     * Schema para extração de lançamento/transação.
     * Usado quando regex falha e precisamos do LLM.
     */
    public static function lancamento(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'create_lancamento',
                'description' => 'Cria um lançamento financeiro (despesa, receita ou compra no cartão de crédito) extraído da mensagem do usuário.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => [
                            'type' => 'string',
                            'enum' => ['despesa', 'receita'],
                            'description' => 'Tipo: despesa (gasto) ou receita (ganho/entrada).',
                        ],
                        'valor' => [
                            'type' => 'number',
                            'description' => 'Valor total da transação em reais (BRL). Ex: 150.50',
                        ],
                        'descricao' => [
                            'type' => 'string',
                            'description' => 'Descrição curta da transação. Ex: "Uber para o trabalho", "Almoço no restaurante".',
                        ],
                        'data' => [
                            'type' => 'string',
                            'description' => 'Data no formato YYYY-MM-DD. Se não mencionada, usar a data de hoje.',
                        ],
                        'forma_pagamento' => [
                            'type' => ['string', 'null'],
                            'enum' => ['pix', 'cartao_credito', 'cartao_debito', 'dinheiro', 'boleto', null],
                            'description' => 'Forma de pagamento, se mencionada. null se não identificada.',
                        ],
                        'eh_parcelado' => [
                            'type' => 'boolean',
                            'description' => 'Se a compra é parcelada (em X vezes).',
                        ],
                        'total_parcelas' => [
                            'type' => ['integer', 'null'],
                            'description' => 'Número de parcelas se parcelado. null se à vista.',
                        ],
                        'nome_cartao' => [
                            'type' => ['string', 'null'],
                            'description' => 'Nome do cartão ou banco mencionado (nubank, inter, itaú, etc). null se não mencionado.',
                        ],
                        'categoria_sugerida' => [
                            'type' => ['string', 'null'],
                            'description' => 'Categoria sugerida: Alimentação, Transporte, Moradia, Saúde, Educação, Lazer, Assinaturas, Vestuário, Salário, Investimentos, etc. null se incerto.',
                        ],
                    ],
                    'required' => ['tipo', 'valor', 'descricao', 'data', 'forma_pagamento', 'eh_parcelado', 'total_parcelas', 'nome_cartao', 'categoria_sugerida'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * Schema para extração de meta financeira.
     */
    public static function meta(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'create_meta',
                'description' => 'Cria uma meta financeira (objetivo de poupança/investimento) extraída da mensagem do usuário.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Nome/título da meta. Ex: "Viagem para Europa", "Carro novo".',
                        ],
                        'valor_alvo' => [
                            'type' => 'number',
                            'description' => 'Valor alvo da meta em reais (BRL).',
                        ],
                    ],
                    'required' => ['titulo', 'valor_alvo'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * Schema para extração de orçamento.
     */
    public static function orcamento(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'create_orcamento',
                'description' => 'Cria um orçamento mensal (limite de gasto por categoria) extraído da mensagem do usuário.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'valor_limite' => [
                            'type' => 'number',
                            'description' => 'Valor limite do orçamento em reais (BRL).',
                        ],
                        'categoria_nome' => [
                            'type' => ['string', 'null'],
                            'description' => 'Nome da categoria do orçamento (Alimentação, Transporte, etc). null se não mencionada.',
                        ],
                        'mes' => [
                            'type' => 'integer',
                            'description' => 'Mês do orçamento (1-12). Usar mês atual se não mencionado.',
                        ],
                        'ano' => [
                            'type' => 'integer',
                            'description' => 'Ano do orçamento (ex: 2025). Usar ano atual se não mencionado.',
                        ],
                    ],
                    'required' => ['valor_limite', 'categoria_nome', 'mes', 'ano'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * Schema para extração de categoria.
     */
    public static function categoria(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'create_categoria',
                'description' => 'Cria uma categoria financeira extraída da mensagem do usuário.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'nome' => [
                            'type' => 'string',
                            'description' => 'Nome da categoria. Ex: "Pets", "Freelance".',
                        ],
                        'tipo' => [
                            'type' => 'string',
                            'enum' => ['receita', 'despesa', 'transferencia', 'ambas'],
                            'description' => 'Tipo da categoria.',
                        ],
                    ],
                    'required' => ['nome', 'tipo'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * Retorna o schema adequado para o tipo de entidade.
     */
    public static function forEntity(string $entityType): ?array
    {
        return match ($entityType) {
            'lancamento'   => self::lancamento(),
            'meta'         => self::meta(),
            'orcamento'    => self::orcamento(),
            'categoria'    => self::categoria(),
            'conta'        => self::conta(),
            default        => null,
        };
    }

    /**
     * Schema para extração de conta bancária.
     */
    public static function conta(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'create_conta',
                'description' => 'Cria uma conta bancária/financeira extraída da mensagem do usuário brasileiro.',
                'strict' => true,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'nome' => [
                            'type' => 'string',
                            'description' => 'Nome da conta. Ex: "Nubank", "Conta Itaú", "Carteira".',
                        ],
                        'instituicao' => [
                            'type' => ['string', 'null'],
                            'description' => 'Nome da instituição financeira (banco). Ex: "Nubank", "Itaú", "Bradesco". null se não mencionado.',
                        ],
                        'tipo_conta' => [
                            'type' => 'string',
                            'enum' => ['conta_corrente', 'conta_poupanca', 'carteira', 'investimento', 'outro'],
                            'description' => 'Tipo da conta. Padrão: conta_corrente.',
                        ],
                        'saldo_inicial' => [
                            'type' => 'number',
                            'description' => 'Saldo inicial em reais (BRL). Padrão: 0.',
                        ],
                    ],
                    'required' => ['nome', 'instituicao', 'tipo_conta', 'saldo_inicial'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
