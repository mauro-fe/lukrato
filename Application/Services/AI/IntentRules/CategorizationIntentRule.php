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
    use PatternMatchingRuleTrait;

    private const CREATION_GUARDS = [
        '/\b(?:criar|adicionar|inserir|cadastrar|definir)\s+sub[\s-]?categoria\b/iu',
        '/\b(?:criar|adicionar|inserir|cadastrar|definir)\s+categoria\b/iu',
    ];

    private const PATTERNS = [
        '/\bcategori(?:zar|za[çc][ãa]o|za)\b/iu',
        '/\b(?:classificar|classifica|classificou)\s+(?:(?:o|a|esse|essa|este|esta|isso)\s+)?(?:gasto|lan[çc]amento|item|despesa)\b/iu',
        '/\bqual\s+(?:[ée]\s+)?a\s+categoria\b/iu',
        '/\b(?:sugerir|sugere|suger[ea])\s+(?:uma\s+)?categoria\b/iu',
        '/\bem\s+qual\s+subcategoria\b|\bsubcategoria\b/iu',
        '/\bque\s+tipo\s+de\s+(?:gasto|despesa|receita|compra)\b/iu',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        if (self::matchesAnyPattern($normalized, self::CREATION_GUARDS)) {
            return null;
        }

        if (self::matchesAnyPattern($normalized, self::PATTERNS)) {
            return IntentResult::medium(IntentType::CATEGORIZE, 0.75);
        }

        return null;
    }
}
