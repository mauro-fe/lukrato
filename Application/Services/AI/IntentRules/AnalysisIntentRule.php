<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
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
        'an[áa]lis[ea]|insight',
        'padr[ãa]o\s+de\s+gasto',
        'economizar|reduzir\s+gasto|poupar|juntar\s+dinheiro|guardar\s+dinheiro',
        'compar[ea].*m[eê]s|evolu[çc][ãa]o|tend[eê]ncia',
        'sugest[ãa]o.*financ|dica.*financ',
        'como\s+posso\s+(economizar|juntar|guardar|poupar)',
        'relat[óo]rio\s+(do|de|mensal|financ)',
        'resumo\s+(financ|do\s+m[eê]s|mensal)',
        // Informal: "como to gastando?", "to no vermelho?"
        'como\s+(?:to|ta|t[áa])\s+(?:gastando|financeiramente)',
        'to\s+(?:endividado|quebrado|no\s+vermelho|lascado|ferrado)',
        'minha\s+(?:situa[çc][ãa]o|sa[úu]de)\s+financeira',
        // Previsão / projeção
        'previs[ãa]o|proje[çc][ãa]o|forecast|projetar',
        // "me ajuda a entender/organizar"
        'me\s+ajuda\s+(?:a\s+)?(?:entender|analisar|organizar|controlar)',
        // "como anda(m) meus gastos/finanças"
        'como\s+anda[m]?\s+(?:meus?|minhas?)?\s*(?:gastos?|finan[çc]as?)',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        foreach (self::PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/iu', $normalized)) {
                return IntentResult::medium(IntentType::ANALYZE, 0.75);
            }
        }

        return null;
    }
}
