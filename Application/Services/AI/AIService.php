<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\DTO\AI\IntentResult;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\Handlers\AIHandlerInterface;
use Application\Services\AI\Handlers\CategorizationHandler;
use Application\Services\AI\Handlers\ChatHandler;
use Application\Services\AI\Handlers\ConfirmationHandler;
use Application\Services\AI\Handlers\EntityCreationHandler;
use Application\Services\AI\Handlers\FinancialAnalysisHandler;
use Application\Services\AI\Handlers\QuickQueryHandler;
use Application\Services\AI\Handlers\PayFaturaHandler;
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
            IntentType::CREATE_ENTITY->value       => new EntityCreationHandler(),
            IntentType::CONFIRM_ACTION->value      => new ConfirmationHandler(),
            IntentType::PAY_FATURA->value          => new PayFaturaHandler(),
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
            if ($request->intent !== null) {
                $intent = $request->intent;
                $confidence = 1.0;
            } else {
                $intentResult = $this->intentRouter->detect(
                    $request->message,
                    $request->channel === AIChannel::WHATSAPP,
                    $request->userId,
                    $request->context['conversation_id'] ?? null
                );
                $intent = $intentResult->intent;
                $confidence = $intentResult->confidence;

                // Se confidence abaixo do threshold, redirecionar para ChatHandler
                if (!$intentResult->isConfident()) {
                    $intent = IntentType::CHAT;
                }
            }

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

            // 4. Log (inclui confidence para debug)
            $this->logDispatch($request, $response, $intent, microtime(true) - $start, $confidence ?? 1.0);

            return $response;
        } catch (Throwable $e) {
            $elapsedErr = microtime(true) - $start;

            LogService::error('AIService.dispatch', [
                'error'   => $e->getMessage(),
                'intent'  => isset($intent) ? $intent->value : 'unknown',
                'user_id' => $request->userId,
            ]);

            AiLogService::log([
                'user_id'          => $request->userId,
                'type'             => isset($intent) ? $this->normalizeLogType($intent) : 'chat',
                'prompt'           => mb_substr($request->message, 0, 5000),
                'response'         => null,
                'provider'         => $_ENV['AI_PROVIDER'] ?? 'openai',
                'model'            => $this->provider->getModel(),
                'tokens_prompt'    => 0,
                'tokens_completion' => 0,
                'tokens_total'     => 0,
                'response_time_ms' => (int) round($elapsedErr * 1000),
                'success'          => false,
                'error_message'    => mb_substr($e->getMessage(), 0, 1000),
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
        float $elapsed,
        float $confidence = 1.0
    ): void {
        $elapsedMs = (int) round($elapsed * 1000);

        LogService::info('ai.dispatch', [
            'intent'     => $intent->value,
            'confidence' => $confidence,
            'channel'    => $request->channel->value,
            'user_id'    => $request->userId,
            'cached'     => $response->cached,
            'source'     => $response->source,
            'tokens'     => $response->tokensUsed,
            'elapsed_ms' => $elapsedMs,
        ]);

        // Persistir no ai_logs para a página de logs do sysadmin
        $meta  = $this->provider->getLastMeta();
        $model = $this->provider->getModel();
        $logType = $this->normalizeLogType($intent);

        AiLogService::log([
            'user_id'           => $request->userId,
            'type'              => $logType,
            'channel'           => $request->channel->value,
            'prompt'            => mb_substr($request->message, 0, 5000),
            'response'          => mb_substr($response->message, 0, 10000),
            'provider'          => $_ENV['AI_PROVIDER'] ?? 'openai',
            'model'             => $model,
            'tokens_prompt'     => $meta['tokens_prompt'] ?? 0,
            'tokens_completion' => $meta['tokens_completion'] ?? 0,
            'tokens_total'      => $meta['tokens_total'] ?? $response->tokensUsed,
            'response_time_ms'  => $elapsedMs,
            'success'           => $response->success,
            'error_message'     => $response->success ? null : $response->message,
            'source'            => $response->source,
            'confidence'        => $confidence,
            'prompt_version'    => $this->resolvePromptVersion($intent),
        ]);
    }

    /**
     * Converte IntentType para o valor correspondente no enum da tabela ai_logs.
     */
    private function normalizeLogType(IntentType $intent): string
    {
        return match ($intent) {
            IntentType::CATEGORIZE          => 'suggest_category',
            IntentType::ANALYZE             => 'analyze_spending',
            IntentType::EXTRACT_TRANSACTION => 'extract_transaction',
            IntentType::QUICK_QUERY         => 'quick_query',
            IntentType::CREATE_ENTITY       => 'create_entity',
            IntentType::CONFIRM_ACTION      => 'confirm_action',
            IntentType::PAY_FATURA          => 'pay_fatura',
            IntentType::CHAT                => 'chat',
        };
    }

    /**
     * Resolve a versão do prompt usado para este intent.
     */
    private function resolvePromptVersion(IntentType $intent): ?string
    {
        return match ($intent) {
            IntentType::CHAT                => PromptBuilder::getVersion('user_chat_system'),
            IntentType::CATEGORIZE          => PromptBuilder::getVersion('category_system'),
            IntentType::ANALYZE             => PromptBuilder::getVersion('analysis_system'),
            IntentType::EXTRACT_TRANSACTION => PromptBuilder::getVersion('transaction_extraction'),
            IntentType::QUICK_QUERY         => PromptBuilder::getVersion('quick_query_system'),
            default                         => null,
        };
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
