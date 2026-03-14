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
    private const PATTERNS = [
        // Totais financeiros
        'quanto\s+(gastei|gasto|recebi|ganho|paguei)',
        'total\s+(de\s+)?(gasto|receita|despesa)',

        // Saldo
        'saldo\s+(atual|total|geral|das?\s+conta)',
        'quanto\s+(tenho|sobrou|falta|sobra)',
        'to\s+com\s+quanto',

        // Contagens
        'quantos?\s+(lan[çc]amento|transa[çc]|registro|conta|cart[ãa]o|cartao)',
        'quantas?\s+(conta|despesa|receita)',

        // Queries específicas
        'qual\s+(?:[ée]\s+)?(?:meu|minha|o)\s+(saldo|gasto|receita|maior|menor)',
        'gastos?\s+(do|deste|desse|neste|nesse)\s+m[eê]s',
        'm[ée]dia\s+(de\s+)?(gasto|despesa)',
        'maior\s+gasto',
        'menor\s+gasto',

        // Informal: "me diz/fala/mostra"
        'me\s+(?:diz|fala|mostra|conta)\s+(?:quanto|qual|meu|minha|o|a|os|as)',
        'mostra\s+(?:meu|minha|o|a)\s+(?:saldo|gasto|despesa|receita)',

        // Informal: "como to/ta meus gastos"
        'como\s+(?:to|ta|t[áa]|tao|est[áa]o?|anda[m]?)\s+(?:meus?|minhas?|os?|as?)?\s*(?:gastos?|finan[çc]as?|contas?|despesas?)',

        // Informal: sobrou/sobrando
        'sobr(?:ou|ando|a)\s+quanto|quanto\s+(?:ta\s+)?sobr',

        // Listar
        '(?:lista|listar|mostra|mostrar)\s+(?:meus?|minhas?)?\s*(?:gastos?|despesas?|receitas?|lan[çc]amentos?)',

        // Faturas de cartão
        'fatura.*cart[ãa]o|cart[ãa]o.*fatura|valor.*fatura|fatura.*valor',
        'quanto\s+(?:eu\s+)?devo\s+(?:no|do)\s+(?:cart[ãa]o|nubank|inter|ita[úu]|bradesco|santander|c6|next|bb)',
        'fatura\s+(?:do|da|de)\s+(?:nubank|inter|ita[úu]|itau|bradesco|santander|c6|next|bb)',
        'itens?\s+(?:da|de)\s+fatura|(?:o\s+que|oq)\s+(?:tem|t[áa])\s+na\s+fatura',

        // Próxima fatura / contas a pagar
        'pr[óo]xima\s+fatura|contas?\s+a\s+pagar|contas?\s+(?:em\s+)?atraso',
        'quanto\s+(?:eu\s+)?devo',

        // Admin
        'quantos\s+usu[áa]rio|quantos\s+usuario|total.*usu[áa]rio',
        'mrr|receita\s+recorrente',
        'erro.*cr[íi]tico|erro.*critical',
        'cadastro.*semana|registr.*semana|usu[áa]rio.*semana',
        'crescimento.*usu[áa]rio|assinante',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        foreach (self::PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/iu', $normalized)) {
                return IntentResult::medium(IntentType::QUICK_QUERY, 0.8);
            }
        }

        return null;
    }
}
