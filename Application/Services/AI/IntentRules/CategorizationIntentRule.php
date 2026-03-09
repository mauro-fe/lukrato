<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

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
        'categori[za]',
        'classific',
        'qual.*categoria',
        'suger.*categoria',
        'subcategoria',
        'que\s+tipo\s+de\s+(gasto|despesa|receita)',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentType
    {
        $normalized = mb_strtolower(trim($message));

        foreach (self::PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/iu', $normalized)) {
                return IntentType::CATEGORIZE;
            }
        }

        return null;
    }
}
