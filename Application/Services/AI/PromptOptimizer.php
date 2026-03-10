<?php

declare(strict_types=1);

namespace Application\Services\AI;

/**
 * Otimiza contexto antes de enviar ao LLM para reduzir tokens.
 * Remove redundâncias, trunca arrays, limpa valores vazios.
 */
class PromptOptimizer
{
    /** Tamanho máximo de arrays de listagem */
    private const MAX_ARRAY_SIZE = 10;

    /** Tamanho máximo de sub-arrays (ex: top_categorias) */
    private const MAX_SUB_ARRAY_SIZE = 8;

    /** Chaves cujos arrays devem ser limitados */
    private const TRUNCATABLE_KEYS = [
        'lancamentos_recentes'    => 8,
        'lancamentos_vencidos'    => 5,
        'recorrencias_ativas'     => 5,
        'lancamentos_por_usuario' => 3,
        'top_categorias_gasto'    => 5,
        'evolucao_6_meses'        => 4,
        'lancamentos_por_forma'   => 5,
    ];

    /**
     * Otimiza o contexto para reduzir consumo de tokens.
     */
    public static function optimize(array $context): array
    {
        $context = self::truncateArrays($context);
        $context = self::removeEmptyValues($context);
        $context = self::compactWhitespace($context);

        return $context;
    }

    /**
     * Trunca arrays grandes para limites configurados.
     */
    private static function truncateArrays(array $context): array
    {
        foreach (self::TRUNCATABLE_KEYS as $key => $limit) {
            if (isset($context[$key]) && is_array($context[$key])) {
                $context[$key] = array_slice($context[$key], 0, $limit);
            }
        }

        // Truncar qualquer array que excedat o limite geral
        foreach ($context as $key => $value) {
            if (is_array($value) && array_is_list($value) && count($value) > self::MAX_ARRAY_SIZE) {
                if (!isset(self::TRUNCATABLE_KEYS[$key])) {
                    $context[$key] = array_slice($value, 0, self::MAX_ARRAY_SIZE);
                }
            }
        }

        return $context;
    }

    /**
     * Remove valores null, strings vazias e arrays vazios recursivamente.
     */
    private static function removeEmptyValues(array $context): array
    {
        $cleaned = [];

        foreach ($context as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if (is_array($value) && !array_is_list($value)) {
                $value = self::removeEmptyValues($value);
                if (!empty($value)) {
                    $cleaned[$key] = $value;
                }
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }

    /**
     * Compacta strings com whitespace excessivo.
     */
    private static function compactWhitespace(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $context[$key] = preg_replace('/\s{2,}/', ' ', trim($value));
            } elseif (is_array($value)) {
                $context[$key] = self::compactWhitespace($value);
            }
        }

        return $context;
    }

    /**
     * Estima o número de tokens de um contexto.
     * Aproximação: 1 token ≈ 4 caracteres em português.
     */
    public static function estimateTokens(array $context): int
    {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE);
        return (int) ceil(mb_strlen($json) / 4);
    }
}
