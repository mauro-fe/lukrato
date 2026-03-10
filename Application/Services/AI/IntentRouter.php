<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;
use Application\Services\AI\IntentRules\AnalysisIntentRule;
use Application\Services\AI\IntentRules\CategorizationIntentRule;
use Application\Services\AI\IntentRules\ConfirmationIntentRule;
use Application\Services\AI\IntentRules\EntityCreationIntentRule;
use Application\Services\AI\IntentRules\IntentRuleInterface;
use Application\Services\AI\IntentRules\QuickQueryIntentRule;
use Application\Services\AI\IntentRules\TransactionIntentRule;
use Application\Services\Infrastructure\CacheService;

/**
 * Detecta a intenção do usuário a partir da mensagem.
 *
 * Pipeline de regras (0 tokens) com IntentConfidence:
 *  Coleta todos os matches, retorna o de maior confidence.
 *  Se confidence < 0.6 → AIService redireciona para ChatHandler.
 *
 * Intents efêmeros (CONFIRM_ACTION, CREATE_ENTITY) não são cacheados.
 */
class IntentRouter
{
    /** @var IntentRuleInterface[] */
    private array $rules = [];

    private CacheService $cache;

    private ConfirmationIntentRule $confirmationRule;

    /** Intents que dependem de estado e não devem ser cacheados */
    private const EPHEMERAL_INTENTS = [
        IntentType::CONFIRM_ACTION,
        IntentType::CREATE_ENTITY,
    ];

    public function __construct()
    {
        $this->cache = new CacheService();
        $this->confirmationRule = new ConfirmationIntentRule();
        $this->registerRules();
    }

    /**
     * Registra as regras na ordem de prioridade.
     */
    private function registerRules(): void
    {
        $this->rules = [
            $this->confirmationRule,
            new EntityCreationIntentRule(),
            new TransactionIntentRule(),
            new QuickQueryIntentRule(),
            new AnalysisIntentRule(),
            new CategorizationIntentRule(),
        ];
    }

    /**
     * Detecta o intent a partir da mensagem do usuário.
     * Coleta todos os matches com confidence, retorna o melhor.
     */
    public function detect(string $message, bool $isWhatsApp = false, ?int $userId = null): IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        // Injetar userId na ConfirmationIntentRule
        $this->confirmationRule->setUserId($userId);

        // Verificar cache de intent para mensagens similares
        $cacheKey = 'ai:intent:' . md5($normalized);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $intent = IntentType::tryFrom($cached);
            if ($intent !== null) {
                return IntentResult::high($intent, ['source' => 'cache']);
            }
        }

        // Coletar todos os matches com confidence
        $matches = [];
        foreach ($this->rules as $rule) {
            $detected = $rule->match($normalized, $isWhatsApp);
            if ($detected !== null) {
                $matches[] = $detected;
            }
        }

        if (empty($matches)) {
            return IntentResult::low(IntentType::CHAT, 0.5, ['source' => 'fallback']);
        }

        // Escolher match com maior confidence
        usort($matches, fn(IntentResult $a, IntentResult $b) => $b->confidence <=> $a->confidence);
        $best = $matches[0];

        // Só cachear intents não-efêmeros
        if (!in_array($best->intent, self::EPHEMERAL_INTENTS, true)) {
            $this->cache->set($cacheKey, $best->intent->value, 86400);
        }

        return $best;
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
