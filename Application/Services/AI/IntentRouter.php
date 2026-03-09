<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Enums\AI\IntentType;
use Application\Services\AI\IntentRules\AnalysisIntentRule;
use Application\Services\AI\IntentRules\CategorizationIntentRule;
use Application\Services\AI\IntentRules\IntentRuleInterface;
use Application\Services\AI\IntentRules\QuickQueryIntentRule;
use Application\Services\AI\IntentRules\TransactionIntentRule;
use Application\Services\Infrastructure\CacheService;

/**
 * Detecta a intenção do usuário a partir da mensagem.
 *
 * Pipeline de regras (0 tokens):
 *  1. TransactionIntentRule  → detecta lançamentos financeiros
 *  2. QuickQueryIntentRule   → detecta consultas respondíveis com SQL
 *  3. AnalysisIntentRule     → detecta pedidos de análise/insights
 *  4. CategorizationIntentRule → detecta pedidos de categorização
 *  5. Fallback               → IntentType::CHAT
 *
 * Adicionar novos intents: criar classe IntentRule e registrar em registerRules().
 */
class IntentRouter
{
    /** @var IntentRuleInterface[] */
    private array $rules = [];

    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
        $this->registerRules();
    }

    /**
     * Registra as regras na ordem de prioridade.
     * A ordem importa: o primeiro match vence.
     */
    private function registerRules(): void
    {
        $this->rules = [
            new TransactionIntentRule(),
            new QuickQueryIntentRule(),
            new AnalysisIntentRule(),
            new CategorizationIntentRule(),
        ];
    }

    /**
     * Detecta o intent a partir da mensagem do usuário.
     * Percorre as regras registradas em ordem; se nenhuma matchou, retorna CHAT.
     */
    public function detect(string $message, bool $isWhatsApp = false): IntentType
    {
        $normalized = mb_strtolower(trim($message));

        // Verificar cache de intent para mensagens similares
        $cacheKey = 'ai:intent:' . md5($normalized);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $intent = IntentType::tryFrom($cached);
            if ($intent !== null) {
                return $intent;
            }
        }

        // Percorrer regras registradas (0 tokens)
        foreach ($this->rules as $rule) {
            $detected = $rule->match($normalized, $isWhatsApp);
            if ($detected !== null) {
                $this->cache->set($cacheKey, $detected->value, 86400);
                return $detected;
            }
        }

        // Default: conversa geral
        return IntentType::CHAT;
    }

    /**
     * Retorna as regras registradas (para testes/debug).
     *
     * @return IntentRuleInterface[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
