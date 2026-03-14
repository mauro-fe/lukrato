<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;

/**
 * Detecta intenção de consulta rápida respondível com SQL.
 *
 * Padrões cobertos:
 *  - "quanto gastei esse mês", "quanto recebi"
 *  - "qual meu saldo", "quanto tenho"
 *  - "quantos lançamentos", "quantas contas"
 *  - "qual meu maior gasto", "gastos do mês"
 *  - Admin: "quantos usuários", "mrr", "erros críticos"
 */
class QuickQueryIntentRule implements IntentRuleInterface
{
    use PatternMatchingRuleTrait;

    private const PATTERNS = [
        '/\bquanto\s+(?:gastei|gasto|recebi|ganho|paguei)\b/iu',
        '/\bqto\s+(?:gastei|gasto|recebi|ganho|paguei|tenho|sobrou|falta)\b/iu',
        '/\btotal\s+(?:de\s+)?(?:gasto|receita|despesa)\b/iu',

        '/\bsaldo\s+(?:atual|total|geral|das?\s+conta(?:s)?)\b/iu',
        '/\bquanto\s+(?:tenho|sobrou|falta|sobra)\b/iu',
        '/\bto\s+com\s+quanto\b/iu',

        '/\bquantos?\s+(?:lan[çc]amentos?|transa[çc](?:ões|oes|ao)|registros?|contas?|cart[ãa]o(?:es|s)?)\b/iu',
        '/\bqtos?\s+(?:lan[çc]amentos?|registros?|contas?|cart[ãa]o(?:es|s)?)\b/iu',
        '/\bquantas?\s+(?:contas?|despesas?|receitas?)\b/iu',

        '/\bqual\s+(?:[ée]\s+)?(?:meu|minha|o)\s+(?:saldo|gasto|receita|maior|menor)\b/iu',
        '/\bgastos?\s+(?:do|deste|desse|neste|nesse)\s+m[eê]s\b/iu',
        '/\bm[ée]dia\s+(?:de\s+)?(?:gasto|despesa)\b/iu',
        '/\bmaior\s+gasto\b/iu',
        '/\bmenor\s+gasto\b/iu',
        '/\b(?:oq|o\s+que|no\s+que|com\s+o\s+que|onde)\s+(?:eu\s+)?gasto\s+mais\b/iu',

        '/\bme\s+(?:diz|fala|mostra|conta)\s+(?:quanto|qual|meu|minha|o|a|os|as)\b/iu',
        '/\bmostra\s+(?:meu|minha|o|a)\s+(?:saldo|gasto|despesa|receita)\b/iu',

        '/\bcomo\s+(?:to|ta|t[áa]|tao|est[áa]o?|anda(?:m)?)\s+(?:meus?|minhas?|os?|as?)?\s*(?:gastos?|finan[çc]as?|contas?|despesas?)\b/iu',
        '/\bsobr(?:ou|ando|a)\s+quanto\b|\bquanto\s+(?:ta\s+)?sobr(?:ando|ou|a)\b/iu',

        '/\b(?:lista|listar|mostra|mostrar)\s+(?:meus?|minhas?)?\s*(?:gastos?|despesas?|receitas?|lan[çc]amentos?)\b/iu',

        '/\bfatura.*cart[ãa]o\b|\bcart[ãa]o.*fatura\b|\bvalor.*fatura\b|\bfatura.*valor\b/iu',
        '/\bquanto\s+(?:eu\s+)?devo\s+(?:no|do)\s+(?:cart[ãa]o|nubank|inter|ita[úu]|itau|bradesco|santander|c6|next|bb)\b/iu',
        '/\bfatura\s+(?:do|da|de)\s+(?:nubank|inter|ita[úu]|itau|bradesco|santander|c6|next|bb)\b/iu',
        '/\bitens?\s+(?:da|de)\s+fatura\b|\b(?:o\s+que|oq)\s+(?:tem|t[áa])\s+na\s+fatura\b/iu',
        '/\bpr[óo]xima\s+fatura\b|\bcontas?\s+a\s+pagar\b|\bcontas?\s+(?:em\s+)?atraso\b/iu',
        '/\bquanto\s+(?:eu\s+)?devo\b/iu',

        '/\bquantos\s+usu[áa]rio(?:s)?\b|\btotal.*usu[áa]rio(?:s)?\b/iu',
        '/\bmrr\b|\breceita\s+recorrente\b/iu',
        '/\berro.*cr[íi]tico\b|\berro.*critical\b/iu',
        '/\bcadastro.*semana\b|\bregistr.*semana\b|\busu[áa]rio.*semana\b/iu',
        '/\bcrescimento.*usu[áa]rio\b|\bassinante(?:s)?\b/iu',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        if (self::matchesAnyPattern($normalized, self::PATTERNS)) {
            return IntentResult::medium(IntentType::QUICK_QUERY, 0.8);
        }

        return null;
    }
}
