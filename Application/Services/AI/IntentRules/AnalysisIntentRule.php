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
    use PatternMatchingRuleTrait;

    private const DIRECT_PATTERNS = [
        '/\ban[áa]lis(?:e|ar|a)\b|\binsight(?:s)?\b/iu',
        '/\bpadr[ãa]o\s+de\s+gasto\b/iu',
        '/\bcompar(?:a|e|ar).*\bm[eê]s\b|\bevolu[çc][ãa]o\b|\btend[eê]ncia\b/iu',
        '/\bsugest[ãa]o.*financ\w*\b|\bdica.*financ\w*\b/iu',
        '/\bcomo\s+posso\s+(?:economizar|juntar|guardar|poupar)\b/iu',
        '/\brelat[óo]rio\s+(?:do|de|mensal|financ)\b/iu',
        '/\bresumo\s+(?:financ\w*|do\s+m[eê]s|mensal)\b/iu',
        '/\bcomo\s+(?:to|ta|t[áa])\s+(?:gastando|financeiramente)\b/iu',
        '/\bto\s+(?:endividado|quebrado|no\s+vermelho|lascado|ferrado)\b/iu',
        '/\bminha\s+(?:situa[çc][ãa]o|sa[úu]de)\s+financeira\b/iu',
        '/\b(?:vc|você)\s+pode\s+analisar\b.*\b(?:gastos?|finan[çc]as?|contas?|despesas?)\b/iu',
        '/\bme\s+ajuda\s+(?:a\s+)?(?:entender|analisar|organizar|controlar)\b.*\b(?:gastos?|finan[çc]as?|contas?|despesas?)\b/iu',
        '/\bcomo\s+anda(?:m)?\s+(?:meus?|minhas?)?\s*(?:gastos?|finan[çc]as?)\b/iu',
    ];

    private const PROJECTION_PATTERNS = [
        '/\b(?:previs[ãa]o|proje[çc][ãa]o|forecast|projetar)\b/iu',
    ];

    private const FINANCIAL_CONTEXT_PATTERNS = [
        '/\b(?:gasto|gastos|despesa|despesas|receita|receitas|saldo|finan[çc]as?|financeir[ao]|contas?|dinheiro|or[çc]amento|fatura|m[eê]s)\b/iu',
    ];

    private const OFF_TOPIC_GUARDS = [
        '/\b(?:tempo|clima|chuva|sol|frio|calor|temperatura)\b/iu',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        if (self::matchesAnyPattern($normalized, self::OFF_TOPIC_GUARDS) && !self::hasFinancialContext($normalized)) {
            return null;
        }

        if (self::matchesAnyPattern($normalized, self::DIRECT_PATTERNS)) {
            return IntentResult::medium(IntentType::ANALYZE, 0.75);
        }

        if (self::matchesAnyPattern($normalized, self::PROJECTION_PATTERNS) && self::hasFinancialContext($normalized)) {
            return IntentResult::medium(IntentType::ANALYZE, 0.75);
        }

        return null;
    }

    private static function hasFinancialContext(string $message): bool
    {
        return self::matchesAnyPattern($message, self::FINANCIAL_CONTEXT_PATTERNS);
    }
}
