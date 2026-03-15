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
    use PatternMatchingRuleTrait;

    private const MONEY_PATTERN =
        '/(?:r\$\s*)?\d{1,3}(?:\.\d{3})*(?:[.,]\d{1,2})?(?:\s*(?:reais|conto[s]?|pila[s]?|k|mil))?\b/iu';

    /**
     * Perguntas e consultas geralmente não representam criação de entidade.
     * Isso evita colisões com quick query e chat em frases como:
     * "quantos lançamentos tenho", "qual meu saldo", "oq eu gasto mais".
     */
    private const QUERY_GUARDS = [
        '/^\s*(?:quanto|quantos|quantas|qual|quais|como|quando|onde|quem|por\s+que|porque|oq|o\s+que)\b/iu',
        '/^\s*(?:me\s+mostra|mostra|listar|lista)\b/iu',
    ];

    /**
     * Verbos de criação explícita.
     */
    private const EXPLICIT_CREATION_PATTERNS = [
        '/\bcri(?:ar|e|a)\b/iu',
        '/\badicion(?:ar|a|e)\b/iu',
        '/\bregistr(?:ar|a|e)\b/iu',
        '/\blan[cç](?:ar|a|e)\b/iu',
        '/\bdefin(?:ir|e)\b/iu',
        '/\bestabelec(?:er|e)\b/iu',
        '/\bcadastr(?:ar|a|e)\b/iu',
        '/\binclu(?:ir|i)\b/iu',
        '/\binser(?:ir|e)\b/iu',
        '/\banot(?:ar|a|e)\b/iu',
        '/\bbot(?:ar|a|e)\b/iu',
        '/\bcoloc(?:ar|a|e)\b/iu',
        '/\bfaz(?:er)?\b/iu',
        '/\bquero\s+(?:criar|adicionar|registrar|definir|estabelecer)\b/iu',
        '/\bsalv(?:ar|a)|\bsave\b/iu',
        '/\bnew\b/iu',
    ];

    /**
     * Verbos implícitos que indicam intenção de criar algo sem dizer "criar".
     * Mantém suporte aos fluxos atuais, mas agora com match mais controlado.
     */
    private const IMPLICIT_CREATION_PATTERNS = [
        '/\btenho\s+que\s+pagar\b/iu',
        '/\bpreciso\s+pagar\b/iu',
        '/\bvou\s+pagar\b/iu',
        '/\bpagar\b/iu',
        '/\bparcelei\b/iu',
        '/\bparcelo\b/iu',
        '/\bvence(?:u)?\b/iu',
        '/\brecebi\b/iu',
        '/\bvou\s+receber\b/iu',
        '/\bpreciso\s+(?:registrar|anotar)\b/iu',
        '/\bquero\s+(?:juntar|economizar|guardar|poupar)\b/iu',
        '/\bn[ãa]o\s+(?:quero\s+)?(?:gastar|passar)\s+(?:mais\s+(?:de|que))\b/iu',
        '/\bgastar\s+no\s+m[áa]ximo\b/iu',
    ];

    /**
     * Entidades reconhecidas e seus padrões.
     *
     * @var array<string, string[]>
     */
    private const ENTITY_PATTERNS = [
        'subcategoria' => [
            '/\bsub[\s-]?categoria\b/iu',
        ],
        'categoria' => [
            '/\bcategoria\b/iu',
        ],
        'conta' => [
            '/\bconta\s+(?:banc[áa]ria|corrente|poupan[çc]a|no\s+banco|do\s+banco|digital)\b/iu',
            '/\bconta\s+(?:no|do|da|na)\s+(?:nubank|inter|ita[úu]|itau|bradesco|santander|bb|banco\s*do\s*brasil|sicredi|sicoob|c6|neon|next|pagbank|picpay|mercado\s*pago|caixa|banrisul|safra|btg|original)\b/iu',
            '/\babrir\s+conta\b/iu',
            '/\bnova\s+conta\b/iu',
            '/\b(?:adicionar|criar|cadastrar)\s+(?:banco|conta)\b/iu',
            '/\bcarteira\b(?!\s+de\s+(?:cr[ée]dito|motorista))/iu',
        ],
        'lancamento' => [
            '/\blan[çc]amento\b/iu',
            '/\bgasto\b/iu',
            '/\bdespesa\b/iu',
            '/\breceita\b/iu',
            '/\bconta\s+(?:a\s+pagar|de\s+luz|de\s+[áa]gua)\b/iu',
            '/\bpagamento\b/iu',
            '/\bfatura\b/iu',
            '/\bboleto\b/iu',
            '/\bparcela\b/iu',
            '/\bpresta[çc][ãa]o\b/iu',
            '/\bmensalidade\b/iu',
            '/\bpix\b/iu',
            '/\btransfer[êe]ncia\b/iu',
            '/\bcompra\b/iu',
            '/\bvenda\b/iu',
            '/\bsal[áa]rio\b/iu',
            '/\bfreela\b/iu',
            '/\breembolso\b/iu',
            '/\bd[íi]vida\b/iu',
            '/\bd[ée]bito\b/iu',
            '/\bcr[ée]dito\b/iu',
            '/\bno\s+cart[ãa]o\b/iu',
            '/\bno\s+cr[ée]dito\b/iu',
            '/\bno\s+d[ée]bito\b/iu',
            '/\bparcelei\b/iu',
            '/\bparcelo\b/iu',
            '/\bem\s+\d{1,2}\s*x\b/iu',
            '/\btorrei\b/iu',
            '/\blarguei\b/iu',
        ],
        'meta' => [
            '/\bmeta\b/iu',
            '/\bobjetivo\b/iu',
            '/\bgoal\b/iu',
            '/\bmeta\s+financeira\b/iu',
            '/\bsonho\b/iu',
            '/\bplano\s+(?:financeiro|de\s+economia)\b/iu',
            '/\bquero\s+(?:juntar|economizar|guardar)\b/iu',
            '/\bpoupar\s+(?:pra|para)\b/iu',
        ],
        'orcamento' => [
            '/\bor[çc]amento\b/iu',
            '/\bbudget\b/iu',
            '/\blimite\s+(?:mensal|de\s+gasto)\b/iu',
            '/\blimite\s+de\s+(?:r\$\s*)?\d+(?:[.,]\d{1,2})?(?:\s+para\s+\p{L}[\p{L}\s-]*)?/iu',
            '/\bteto\s+de\s+gasto\b/iu',
            '/\blimite\s+pra\s+gastar\b/iu',
            '/\bcontrole\s+de\s+gasto\b/iu',
            '/\bn[ãa]o\s+(?:quero\s+)?(?:gastar|passar)\s+(?:mais\s+(?:de|que))\b/iu',
            '/\bgastar\s+no\s+m[áa]ximo\b/iu',
        ],
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        if ($this->isLikelyQuery($normalized) && !$this->hasExplicitCreationVerb($normalized)) {
            return null;
        }

        if ($this->hasExplicitCreationVerb($normalized)) {
            $entity = self::detectEntityByPatterns($normalized);
            $entity ??= self::detectEntityFromExplicitShortcut($normalized);
            if ($entity !== null) {
                return IntentResult::medium(IntentType::CREATE_ENTITY, 0.9, ['entity' => $entity]);
            }
        }

        if (self::matchesAnyPattern($normalized, self::IMPLICIT_CREATION_PATTERNS)) {
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

        $entity = self::detectEntityByPatterns($normalized);
        if ($entity !== null) {
            return $entity;
        }

        if (self::matchesAnyPattern($normalized, self::EXPLICIT_CREATION_PATTERNS)) {
            $entity = self::detectEntityFromExplicitShortcut($normalized);
            if ($entity !== null) {
                return $entity;
            }
        }

        return self::detectEntityFromImplicit($normalized);
    }

    private function hasExplicitCreationVerb(string $message): bool
    {
        return self::matchesAnyPattern($message, self::EXPLICIT_CREATION_PATTERNS);
    }

    private function isLikelyQuery(string $message): bool
    {
        return self::matchesAnyPattern($message, self::QUERY_GUARDS);
    }

    private static function detectEntityByPatterns(string $message): ?string
    {
        foreach (self::ENTITY_PATTERNS as $entity => $patterns) {
            if (self::matchesAnyPattern($message, $patterns)) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * Detecta entidade a partir de verbos implícitos.
     * Ex: "quero juntar 10k" → meta, "não quero gastar mais de 800" → orcamento
     */
    private static function detectEntityFromImplicit(string $normalized): ?string
    {
        if (preg_match('/\bquero\s+(?:juntar|economizar|guardar|poupar)\b|\bpoupar\s+(?:pra|para)\b/iu', $normalized)) {
            return 'meta';
        }

        if (preg_match('/\bn[ãa]o\s+(?:quero\s+)?(?:gastar|passar)\s+(?:mais\s+(?:de|que))\b|\bgastar\s+no\s+m[áa]ximo\b/iu', $normalized)) {
            return 'orcamento';
        }

        if (preg_match('/\b(?:tenho\s+que\s+pagar|preciso\s+pagar|vou\s+pagar|pagar|parcelei|parcelo|vence(?:u)?|recebi|vou\s+receber)\b/iu', $normalized)) {
            if (preg_match('/\d+|luz|[áa]gua|internet|aluguel|cart[ãa]o|boleto|fatura/iu', $normalized)) {
                return 'lancamento';
            }
        }

        return null;
    }

    /**
     * Frases como "registre 30 com comida" nao nomeiam a entidade,
     * mas continuam sendo um pedido explicito para criar um lancamento.
     */
    private static function detectEntityFromExplicitShortcut(string $normalized): ?string
    {
        if (!preg_match(self::MONEY_PATTERN, $normalized)) {
            return null;
        }

        return 'lancamento';
    }
}
