<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\Handlers\AIHandlerInterface;
use Application\Services\AI\Handlers\CategorizationHandler;
use Application\Services\AI\Handlers\ChatHandler;
use Application\Services\AI\Handlers\FinancialAnalysisHandler;
use Application\Services\AI\Handlers\QuickQueryHandler;
use Application\Services\AI\Handlers\TransactionExtractorHandler;
use Application\Services\AI\Providers\OllamaProvider;
use Application\Services\AI\Providers\OpenAIProvider;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Throwable;

/**
 * ┌─────────────────────────────────────────────────────────┐
 * │  AIService — Gateway unificado para todas as            │
 * │  interações de IA do Lukrato                            │
 * │                                                         │
 * │  Pipeline: Request → IntentRouter → Handler → Response  │
 * │                                                         │
 * │  Métodos legados (chat, suggestCategory, etc.)          │
 * │  mantidos para retrocompatibilidade                     │
 * └─────────────────────────────────────────────────────────┘
 */
class AIService
{
    private AIProvider $provider;
    private CacheService $cache;
    private IntentRouter $intentRouter;

    /** @var array<string, AIHandlerInterface> */
    private array $handlers = [];

    public function __construct()
    {
        $providerName = $_ENV['AI_PROVIDER'] ?? 'openai';

        $this->provider = match ($providerName) {
            'ollama' => new OllamaProvider(),
            default  => new OpenAIProvider(),
        };

        $this->cache        = new CacheService();
        $this->intentRouter = new IntentRouter();

        $this->registerHandlers();
    }

    // ─── Handler Registry ──────────────────────────────────

    private function registerHandlers(): void
    {
        $handlers = [
            IntentType::CHAT->value                => new ChatHandler(),
            IntentType::QUICK_QUERY->value         => new QuickQueryHandler(),
            IntentType::CATEGORIZE->value          => new CategorizationHandler(),
            IntentType::EXTRACT_TRANSACTION->value => new TransactionExtractorHandler(),
            IntentType::ANALYZE->value             => new FinancialAnalysisHandler(),
        ];

        // Injetar provider em cada handler (evita instanciação circular)
        foreach ($handlers as $handler) {
            $handler->setProvider($this->provider);
        }

        $this->handlers = $handlers;
    }

    // ─── Pipeline Principal ─────────────────────────────────

    /**
     * dispatch() — Ponto de entrada unificado
     *
     * Recebe um AIRequestDTO, detecta intent (se não vier explícito),
     * resolve o handler adequado e retorna AIResponseDTO.
     */
    public function dispatch(AIRequestDTO $request): AIResponseDTO
    {
        $start = microtime(true);

        try {
            // 1. Detectar intent se não vier explícito
            $intent = $request->intent
                ?? $this->intentRouter->detect(
                    $request->message,
                    $request->channel === AIChannel::WHATSAPP
                );

            // 2. Resolver handler
            $handler = $this->resolveHandler($intent);

            if ($handler === null) {
                return AIResponseDTO::fail(
                    'Nenhum handler disponível para a intent: ' . $intent->value,
                    $intent
                );
            }

            // 3. Executar handler
            $response = $handler->handle($request);

            // 4. Log
            $this->logDispatch($request, $response, $intent, microtime(true) - $start);

            return $response;

        } catch (Throwable $e) {
            LogService::error('AIService.dispatch', [
                'error'   => $e->getMessage(),
                'intent'  => isset($intent) ? $intent->value : 'unknown',
                'user_id' => $request->userId,
            ]);

            return AIResponseDTO::fail(
                'Erro interno no processamento de IA.',
                $intent ?? IntentType::CHAT
            );
        }
    }

    private function resolveHandler(IntentType $intent): ?AIHandlerInterface
    {
        return $this->handlers[$intent->value] ?? null;
    }

    private function logDispatch(
        AIRequestDTO $request,
        AIResponseDTO $response,
        IntentType $intent,
        float $elapsed
    ): void {
        LogService::info('ai.dispatch', [
            'intent'     => $intent->value,
            'channel'    => $request->channel->value,
            'user_id'    => $request->userId,
            'cached'     => $response->cached,
            'source'     => $response->source,
            'tokens'     => $response->tokensUsed,
            'elapsed_ms' => round($elapsed * 1000, 2),
        ]);
    }

    // ─── Métodos Legados (Retrocompatibilidade) ─────────────

    /**
     * chat() — Método legado mantido para AiApiController.
     *
     * O provider internamente monta o system prompt via PromptBuilder::chatSystem($context).
     * Retorna a string de resposta da IA.
     */
    public function chat(string $message, array $context, bool $isAdmin = false): string
    {
        $cacheKey = 'ai_chat_' . md5($message . serialize($context) . ($isAdmin ? '_admin' : ''));
        $cached   = $this->cache->get($cacheKey);

        if ($cached !== null && is_string($cached)) {
            return $cached;
        }

        $result = $this->provider->chat($message, $context);

        $this->cache->set($cacheKey, $result, 1800);

        return $result;
    }

    /**
     * suggestCategory() — Método legado.
     *
     * O provider internamente monta os prompts via PromptBuilder::categorySystem/categoryUser.
     * Retorna o nome da categoria sugerida ou null.
     */
    public function suggestCategory(string $description, array $categorias = []): ?string
    {
        $cacheKey = 'ai_cat_' . md5($description);
        $cached   = $this->cache->get($cacheKey);

        if ($cached !== null && is_string($cached)) {
            return $cached;
        }

        $result = $this->provider->suggestCategory($description, $categorias);

        if ($result !== null) {
            $this->cache->set($cacheKey, $result, 86400);
        }

        return $result;
    }

    /**
     * analyzeSpending() — Método legado.
     *
     * O provider internamente monta os prompts via PromptBuilder::analysisSystem/analysisUser.
     * Retorna array com 'insights' e 'resumo'.
     */
    public function analyzeSpending(array $data, string $period = 'último mês'): array
    {
        $cacheKey = 'ai_analysis_' . md5(serialize($data) . $period);
        $cached   = $this->cache->get($cacheKey);

        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        $result = $this->provider->analyzeSpending($data, $period);

        if (!empty($result)) {
            $this->cache->set($cacheKey, $result, 21600);
        }

        return $result;
    }

    // ─── Accessors ───────────────────────────────────────────

    public function getProvider(): AIProvider
    {
        return $this->provider;
    }

    public function getIntentRouter(): IntentRouter
    {
        return $this->intentRouter;
    }
}
