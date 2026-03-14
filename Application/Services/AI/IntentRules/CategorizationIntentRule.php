<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;

/**
 * Detecta intenção de categorização de lançamento.
 *
 * Padrões cobertos:
 *  - "qual a categoria disso", "categorizar"
 *  - "classificar lançamento", "sugerir categoria"
 *  - "em qual subcategoria", "que tipo de gasto é"
 */
class CategorizationIntentRule implements IntentRuleInterface
{
    private const PATTERNS = [
        'categori[za](r|ção|a)?',
        '(classificar|classifica|classificou)\s+(o\s+)?(gasto|lançamento|item|despesa)',
        'qual\s+(é\s+)?a\s+categoria',
        'sugerir\s+categoria',
        'subcategoria',
        'que\s+tipo\s+de\s+(gasto|despesa|receita|compra)',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        foreach (self::PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/iu', $normalized)) {
                return IntentResult::medium(IntentType::CATEGORIZE, 0.75);
            }
        }

        return null;
    }
}
