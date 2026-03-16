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
use Application\Services\AI\IntentRules\PayFaturaIntentRule;
use Application\Services\AI\IntentRules\QuickQueryIntentRule;
use Application\Services\AI\IntentRules\SmartFallbackRule;
use Application\Services\AI\IntentRules\TransactionIntentRule;
use Application\Services\AI\NLP\NumberNormalizer;
use Application\Services\AI\NLP\TextNormalizer;
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
        IntentType::PAY_FATURA,
    ];
    private const CACHE_VERSION = 'v2';

    /** States that indicate an active multi-turn flow */
    private const ACTIVE_STATES = ['collecting_entity', 'awaiting_selection'];

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
            new PayFaturaIntentRule(),
            new TransactionIntentRule(),
            new QuickQueryIntentRule(),
            new AnalysisIntentRule(),
            new CategorizationIntentRule(),
            new SmartFallbackRule(),        // Prioridade 8: captura transações que escaparam dos rules acima
        ];
    }

    /**
     * Detecta o intent a partir da mensagem do usuário.
     * Coleta todos os matches com confidence, retorna o melhor.
     */
    public function detect(string $message, bool $isWhatsApp = false, ?int $userId = null, ?int $conversationId = null): IntentResult
    {
        // Normalizar texto: expandir abreviações WhatsApp, limpar pontuação
        $preprocessed = TextNormalizer::normalize($message);
        // Normalizar números: "2 mil" → "2000", "duzentos" → "200", etc.
        $preprocessed = NumberNormalizer::normalize($preprocessed);

        $normalized = mb_strtolower(trim($preprocessed));

        // Check active multi-turn flow FIRST (highest priority)
        if ($conversationId !== null) {
            $state = ConversationStateService::getState($conversationId);
            if (in_array($state['state'], self::ACTIVE_STATES, true)) {
                // If state expired, clear it and proceed normally
                if (ConversationStateService::isExpired($conversationId)) {
                    ConversationStateService::clearState($conversationId);
                } else {
                    // Check if user wants to cancel
                    if (preg_match('/\b(cancel|cancela|parar?|desist|sair|deixa\s*pra\s*l[áa]|esquece)\b/iu', $normalized)) {
                        ConversationStateService::clearState($conversationId);
                        return IntentResult::high(IntentType::CHAT, ['source' => 'cancel_flow', 'cancelled' => true]);
                    }

                    // Route to CREATE_ENTITY to continue the multi-turn flow
                    return IntentResult::high(IntentType::CREATE_ENTITY, [
                        'source' => 'multi_turn',
                        'conversation_state' => $state['state'],
                    ]);
                }
            }
        }

        // Injetar userId na ConfirmationIntentRule
        $this->confirmationRule->setUserId($userId);

        // Verificar cache de intent para mensagens similares
        $cacheScope = $isWhatsApp ? 'whatsapp' : 'default';
        $cacheKey = 'ai:intent:' . self::CACHE_VERSION . ':' . $cacheScope . ':' . md5($normalized);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $intent = IntentType::tryFrom($cached);
            if ($intent !== null) {
                return IntentResult::medium($intent, 0.95, ['source' => 'cache']);
            }
        }

        // Coletar todos os matches com confidence
        $matches = [];
        foreach ($this->rules as $priority => $rule) {
            $detected = $rule->match($normalized, $isWhatsApp);
            if ($detected !== null) {
                $matches[] = [
                    'priority' => $priority,
                    'result'   => $detected,
                ];
            }
        }

        if (empty($matches)) {
            return IntentResult::low(IntentType::CHAT, 0.5, ['source' => 'fallback']);
        }

        // Escolher match com maior confidence; em empate, respeitar a ordem de prioridade.
        usort($matches, function (array $left, array $right): int {
            $confidenceComparison = $right['result']->confidence <=> $left['result']->confidence;

            if ($confidenceComparison !== 0) {
                return $confidenceComparison;
            }

            return $left['priority'] <=> $right['priority'];
        });
        $best = $matches[0]['result'];

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
