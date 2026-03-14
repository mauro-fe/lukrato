<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

trait PatternMatchingRuleTrait
{
    /**
     * Retorna true quando qualquer regex do conjunto casa com a mensagem.
     *
     * @param string[] $patterns
     */
    private static function matchesAnyPattern(string $message, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return true;
            }
        }

        return false;
    }
}
