<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\Enums\AI\IntentType;

/**
 * Detecta intenção de análise financeira (insights, relatórios, tendências).
 *
 * Padrões cobertos:
 *  - "analise meus gastos", "quero uma análise"
 *  - "insights financeiros", "relatório do mês"
 *  - "como posso economizar", "sugestão financeira"
 *  - "comparar com mês passado", "evolução dos gastos"
 */
class AnalysisIntentRule implements IntentRuleInterface
{
    private const PATTERNS = [
        'analis[ea]|insight',
        'padr[ãa]o\s+de\s+gasto',
        'economizar|reduzir\s+gasto|poupar|juntar\s+dinheiro|guardar\s+dinheiro',
        'compar[ea].*m[eê]s|evolu[çc][ãa]o|tend[eê]ncia',
        'sugest[ãa]o.*financ|dica.*financ',
        'como\s+posso\s+(economizar|juntar|guardar|poupar)',
        'relat[óo]rio\s+(do|de|mensal|financ)',
        'resumo\s+(financ|do\s+m[eê]s|mensal)',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentType
    {
        $normalized = mb_strtolower(trim($message));

        foreach (self::PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/iu', $normalized)) {
                return IntentType::ANALYZE;
            }
        }

        return null;
    }
}
