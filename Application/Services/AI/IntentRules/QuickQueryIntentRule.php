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
        'quanto\s+(gastei|gasto|recebi|ganho)',
        'total\s+(de\s+)?(gasto|receita|despesa)',

        // Saldo
        'saldo\s+(atual|total|geral|das?\s+conta)',
        'quanto\s+(tenho|sobrou|falta)',

        // Contagens
        'quantos?\s+(lançamento|transaç|registro|conta|cart[ãa]o|cartao)',
        'quantas?\s+(conta|despesa|receita)',

        // Queries específicas
        'qual\s+(meu|minha|o)\s+(saldo|gasto|receita|maior|menor)',
        'gastos?\s+(do|deste|desse|neste|nesse)\s+m[eê]s',
        'm[ée]dia\s+(de\s+)?(gasto|despesa)',
        'maior\s+gasto',
        'menor\s+gasto',

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
