<?php

declare(strict_types=1);

namespace Application\Services\AI\NLP;

/**
 * Normaliza descrições de transações separando item principal e contexto.
 *
 * Exemplo:
 * - "produto de limpeza no mercado" => descricao="Produto De Limpeza", contexto="Mercado"
 * - "almoço no restaurante" => descricao="Almoço", contexto="Restaurante"
 */
class TransactionDescriptionNormalizer
{
    private const LEADING_FILLERS = '/^(?:no|na|de|do|da|em|com|pro|pra|para|uns?)\s+/iu';
    private const TRAILING_VALUE_FILLERS = '/\s*(?:conto[s]?|pila[s]?|reais)\s*$/iu';
    private const TRAILING_PAYMENT_HINTS = '/\s+(?:no|na|do|da|pelo|pela)\s+(?:cart[ãa]o|cr[ée]dito|d[ée]bito)\s*$/iu';
    private const TRAILING_CONTEXT_PATTERN = '/\s+(?:no|na|do|da|em)\s+((?:super\s*)?mercado|supermercado|farm[áa]cia|drogaria|posto|shopping|restaurante|padaria|loja|ifood|rappi|uber\s*eats|carrefour|extra|assa[íi]|atacad[ãa]o|dia\b|amazon|mercado\s*livre|shopee)\s*$/iu';

    /**
     * @return array{descricao: string, categoria_contexto?: string}
     */
    public static function normalize(string $description): array
    {
        $description = trim($description);
        if ($description === '') {
            return ['descricao' => ''];
        }

        $description = preg_replace(self::LEADING_FILLERS, '', $description);
        $description = preg_replace(self::TRAILING_VALUE_FILLERS, '', $description);
        $description = preg_replace(self::TRAILING_PAYMENT_HINTS, '', $description);
        $description = trim((string) $description, " \t\n\r\0\x0B,.");

        $context = null;
        if (preg_match(self::TRAILING_CONTEXT_PATTERN, $description, $matches)) {
            $candidateContext = trim($matches[1]);
            $candidateDescription = trim((string) preg_replace(self::TRAILING_CONTEXT_PATTERN, '', $description));

            if ($candidateDescription !== '' && $candidateDescription !== $description) {
                $description = $candidateDescription;
                $context = self::title($candidateContext);
            }
        }

        $description = preg_replace('/\s+/u', ' ', trim($description));
        $description = self::title($description);

        if ($description === '') {
            return ['descricao' => ''];
        }

        return $context !== null
            ? ['descricao' => $description, 'categoria_contexto' => $context]
            : ['descricao' => $description];
    }

    private static function title(string $value): string
    {
        return mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');
    }
}
