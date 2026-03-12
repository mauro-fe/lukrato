<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;

/**
 * Detecta intenção explícita de criar entidades financeiras.
 *
 * Padrões cobertos:
 *  - "cria/adiciona/registra um lançamento de 100 de luz"
 *  - "quero criar uma meta de 5000 para viagem"
 *  - "define orçamento de 800 para alimentação"
 *  - "cria categoria Pets tipo despesa"
 *  - "adiciona subcategoria Ração em Pets"
 *  - "tenho que pagar 150 de luz" (verbo implícito)
 *  - "parcelei 3000 em 12x no nubank" (cartão de crédito)
 *  - "quero juntar 10k pra uma viagem" (meta implícita)
 *  - "não quero gastar mais de 800 com alimentação" (orçamento implícito)
 */
class EntityCreationIntentRule implements IntentRuleInterface
{
    /**
     * Verbos de criação explícita.
     */
    private const CREATION_VERBS =
    'cri(?:ar?|e|a)|adicionar?|registrar?|lan[çc]ar?|definir?|estabelecer?|cadastrar?|incluir?|inserir?|anota[r]?|bota[r]?|coloca[r]?|faz(?:er)?|quero\s+(?:criar|adicionar|registrar|definir)|sav[ea]|new';

    /**
     * Verbos implícitos que indicam criação sem dizer "criar".
     * Ex: "tenho que pagar 150", "preciso pagar a luz", "parcelei 3000"
     */
    private const IMPLICIT_CREATION_VERBS =
    'tenho\s+que\s+pagar|preciso\s+pagar|vou\s+pagar|pagar|parcelei|parcelo|vence(?:u)?|recebi|vou\s+receber|preciso\s+registrar|preciso\s+anotar|quero\s+juntar|quero\s+economizar|quero\s+guardar|quero\s+poupar|n[ãa]o\s+(?:quero\s+)?(?:gastar|passar)\s+(?:mais\s+(?:de|que))|gastar\s+no\s+m[áa]ximo';

    /**
     * Entidades reconhecidas e seus padrões.
     */
    private const ENTITY_PATTERNS = [
        // Subcategoria (mais específico, antes de categoria e lancamento)
        'subcategoria' => 'subcategoria|sub[\s-]?categoria',

        // Categoria (antes de lancamento para evitar match com "tipo despesa")
        'categoria'  => 'categoria',

        // Conta bancária (antes de lancamento para evitar match com "conta de luz")
        'conta' => 'conta\s+(?:banc[áa]ria|corrente|poupan[çc]a|no\s+banco|do\s+banco|digital)'
            . '|conta\s+(?:no|do|da|na)\s+(?:nubank|inter|ita[úu]|itau|bradesco|santander|bb|banco\s*do\s*brasil|sicredi|sicoob|c6|neon|next|pagbank|picpay|mercado\s*pago|caixa|banrisul|safra|btg|original)'
            . '|abrir\s+conta|nova\s+conta|adicionar\s+(?:banco|conta)|criar\s+(?:banco|conta)|cadastrar\s+(?:banco|conta)'
            . '|\bcarteira\b(?!\s+de\s+(?:cr[ée]dito|motorista))',

        // Lançamento / despesa / receita / cartão de crédito
        'lancamento' => 'lan[çc]amento|gasto|despesa|receita|conta\s+(?:a\s+pagar|de\s+luz|de\s+[áa]gua)|pagamento'
            . '|fatura|boleto|parcela|presta[çc][ãa]o|mensalidade|pix|transfer[eê]ncia'
            . '|compra|venda|sal[áa]rio|freela|reembolso|d[íi]vida|d[ée]bito|cr[ée]dito'
            . '|no\s+cart[ãa]o|no\s+cr[ée]dito|no\s+d[ée]bito|parcelei|parcelo'
            . '|em\s+\d{1,2}\s*x|torrei|larguei',

        // Meta / objetivo / sonho
        'meta'       => 'meta|objetivo|goal|meta\s+financeira|sonho|plano\s+(?:financeiro|de\s+economia)'
            . '|quero\s+juntar|quero\s+economizar|quero\s+guardar|poupar\s+(?:pra|para)',

        // Orçamento / budget / limite
        'orcamento'  => 'or[çc]amento|budget|limite\s+(?:mensal|de\s+gasto)'
            . '|teto\s+de\s+gasto|limite\s+pra\s+gastar|controle\s+de\s+gasto'
            . '|n[ãa]o\s+(?:quero\s+)?(?:gastar|passar)\s+(?:mais\s+(?:de|que))|gastar\s+no\s+m[áa]ximo',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        // 1. Verbo explícito de criação + entidade = alta confiança
        $verbPattern = '/(?:' . self::CREATION_VERBS . ')/iu';
        if (preg_match($verbPattern, $normalized)) {
            foreach (self::ENTITY_PATTERNS as $entity => $pattern) {
                if (preg_match('/(?:' . $pattern . ')/iu', $normalized)) {
                    return IntentResult::medium(IntentType::CREATE_ENTITY, 0.9, ['entity' => $entity]);
                }
            }
        }

        // 2. Verbo implícito + contexto financeiro = confiança média-alta
        $implicitPattern = '/(?:' . self::IMPLICIT_CREATION_VERBS . ')/iu';
        if (preg_match($implicitPattern, $normalized)) {
            // Para verbos implícitos, detectar entidade pelo contexto
            $entity = self::detectEntityFromImplicit($normalized);
            if ($entity !== null) {
                return IntentResult::medium(IntentType::CREATE_ENTITY, 0.85, [
                    'entity' => $entity,
                    'source' => 'implicit_verb',
                ]);
            }
        }

        return null;
    }

    /**
     * Detecta qual tipo de entidade o usuário quer criar.
     * Usado pelo EntityCreationHandler após o intent ser detectado.
     */
    public static function detectEntityType(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));

        // Ordem importa: subcategoria antes de categoria
        foreach (self::ENTITY_PATTERNS as $entity => $pattern) {
            if (preg_match('/(?:' . $pattern . ')/iu', $normalized)) {
                return $entity;
            }
        }

        // Fallback: detectar por verbos implícitos
        return self::detectEntityFromImplicit($normalized);
    }

    /**
     * Detecta entidade a partir de verbos implícitos.
     * Ex: "quero juntar 10k" → meta, "não quero gastar mais de 800" → orcamento
     */
    private static function detectEntityFromImplicit(string $normalized): ?string
    {
        // Meta implícita: "quero juntar", "quero economizar", "quero guardar", "poupar pra"
        if (preg_match('/quero\s+(?:juntar|economizar|guardar|poupar)|poupar\s+(?:pra|para)/iu', $normalized)) {
            return 'meta';
        }

        // Orçamento implícito: "não quero gastar mais de", "gastar no máximo"
        if (preg_match('/n[ãa]o\s+(?:quero\s+)?(?:gastar|passar)\s+(?:mais\s+(?:de|que))|gastar\s+no\s+m[áa]ximo/iu', $normalized)) {
            return 'orcamento';
        }

        // Lançamento implícito: "tenho que pagar", "parcelei", "vence"
        if (preg_match('/tenho\s+que\s+pagar|preciso\s+pagar|vou\s+pagar|pagar|parcelei|parcelo|vence(?:u)?|recebi|vou\s+receber/iu', $normalized)) {
            // Só se tiver algum contexto financeiro (valor ou descrição)
            if (preg_match('/\d+|luz|[áa]gua|internet|aluguel|cart[ãa]o|boleto|fatura/iu', $normalized)) {
                return 'lancamento';
            }
        }

        return null;
    }
}
