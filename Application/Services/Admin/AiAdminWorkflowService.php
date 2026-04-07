<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\DTO\AI\AIRequestDTO;
use Application\Enums\LogCategory;
use Application\Services\AI\AIService;
use Application\Services\AI\AiLogService;
use Application\Services\AI\ContextCompressor;
use Application\Services\AI\SystemContextService;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use GuzzleHttp\Client;
use Throwable;

class AiAdminWorkflowService
{
    /**
     * @return array<string, mixed>
     */
    public function healthProxy(): array
    {
        $provider = strtolower($_ENV['AI_PROVIDER'] ?? 'openai');

        if ($provider === 'openai') {
            $hasKey = !empty($_ENV['OPENAI_API_KEY']);
            $model = $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini';

            return $this->success([
                'status' => $hasKey ? 'ok' : 'error',
                'service' => 'lukrato-ai',
                'provider' => 'openai',
                'model' => $model,
                'message' => $hasKey ? 'OpenAI configurada' : 'OPENAI_API_KEY nao configurada',
            ]);
        }

        $serviceUrl = rtrim($_ENV['AI_SERVICE_URL'] ?? 'http://127.0.0.1:8002', '/');

        try {
            $client = $this->createHttpClient([
                'timeout' => 5,
                'connect_timeout' => 3,
            ]);

            $res = $client->get("{$serviceUrl}/health");
            $data = json_decode($res->getBody()->getContents(), true);

            return $this->success($data ?? ['status' => 'ok']);
        } catch (\Throwable) {
            return $this->failure('Servico Python offline', 503);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function quota(): array
    {
        $provider = strtolower($_ENV['AI_PROVIDER'] ?? 'openai');

        if ($provider !== 'openai') {
            return $this->success([
                'provider' => $provider,
                'message' => 'Rate limits nao disponiveis para este provider',
            ]);
        }

        $apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        if ($apiKey === '') {
            return $this->failure('OPENAI_API_KEY nao configurada', 400);
        }

        $model = $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini';
        $cache = $this->cache();
        $cached = $cache->get('ai:openai_rate_limits');

        if (is_array($cached) && array_key_exists('requests_limit', $cached)) {
            return $this->success([
                'provider' => 'openai',
                'model' => $model,
                'status' => 'active',
                'requests_limit' => $cached['requests_limit'] ?? 0,
                'requests_remaining' => $cached['requests_remaining'] ?? 0,
                'tokens_limit' => $cached['tokens_limit'] ?? 0,
                'tokens_remaining' => $cached['tokens_remaining'] ?? 0,
                'reset_requests' => $cached['reset_requests'] ?? null,
                'reset_tokens' => $cached['reset_tokens'] ?? null,
                'source' => 'cache',
            ]);
        }

        try {
            $client = $this->createHttpClient([
                'base_uri' => 'https://api.openai.com/v1/',
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            $response = $client->post('chat/completions', [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => 'hi']],
                    'max_tokens' => 1,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $usage = $body['usage'] ?? [];

            $rateLimits = [
                'requests_limit' => (int) ($response->getHeaderLine('x-ratelimit-limit-requests') ?: 0),
                'requests_remaining' => (int) ($response->getHeaderLine('x-ratelimit-remaining-requests') ?: 0),
                'tokens_limit' => (int) ($response->getHeaderLine('x-ratelimit-limit-tokens') ?: 0),
                'tokens_remaining' => (int) ($response->getHeaderLine('x-ratelimit-remaining-tokens') ?: 0),
                'reset_requests' => $response->getHeaderLine('x-ratelimit-reset-requests') ?: null,
                'reset_tokens' => $response->getHeaderLine('x-ratelimit-reset-tokens') ?: null,
            ];

            $cache->set('ai:openai_rate_limits', $rateLimits, 300);

            return $this->success(array_merge([
                'provider' => 'openai',
                'model' => $model,
                'status' => 'active',
                'test_tokens_used' => $usage['total_tokens'] ?? 0,
                'source' => 'api',
            ], $rateLimits));
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status = $e->getResponse()->getStatusCode();
            $errBody = json_decode($e->getResponse()->getBody()->getContents(), true);
            $errMsg = $errBody['error']['message'] ?? $e->getMessage();

            $code = match ($status) {
                401 => 'invalid_key',
                429 => 'quota_exceeded',
                default => 'error',
            };

            return $this->failure($errMsg, $status, [
                'provider' => 'openai',
                'model' => $model,
                'status' => $code,
                'requests_limit' => 0,
                'requests_remaining' => 0,
                'tokens_limit' => 0,
                'tokens_remaining' => 0,
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao verificar quota de IA.', [
                'action' => 'ai_admin_quota',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function chat(array $payload): array
    {
        $message = trim((string) ($payload['message'] ?? ''));
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

        if ($message === '') {
            return $this->failure('Mensagem não pode ser vazia', 422);
        }

        if (mb_strlen($message) > 2000) {
            return $this->failure('Mensagem muito longa (máximo 2000 caracteres)', 422);
        }

        try {
            $systemContext = $this->gatherSystemContext();
        } catch (\Throwable) {
            $systemContext = [];
        }

        $context = ContextCompressor::compress(array_merge($systemContext, $context), $message);
        $ai = $this->createAiService();

        try {
            $request = AIRequestDTO::adminChat($message, $context);
            $aiResponse = $ai->dispatch($request);

            if (!$aiResponse->success) {
                return $this->failure($aiResponse->message, 500);
            }
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao processar chat de IA.', [
                'action' => 'ai_admin_chat',
            ]);
        }

        return $this->success(['response' => $aiResponse->message]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function suggestCategory(int $userId, array $payload): array
    {
        $description = trim((string) ($payload['description'] ?? ''));
        $categories = is_array($payload['categories'] ?? null) ? $payload['categories'] : [];

        if (mb_strlen($description) < 3) {
            return $this->success(['category' => null]);
        }

        $start = microtime(true);
        $ai = $this->createAiService();

        try {
            $category = $ai->suggestCategory($description, $categories);
            $this->logLegacyAiCall($userId, 'suggest_category', $description, $category, true, $start, null, $ai);
        } catch (Throwable $e) {
            $this->logLegacyAiCall($userId, 'suggest_category', $description, null, false, $start, $e->getMessage(), $ai);

            return $this->internalFailure($e, 'Erro ao sugerir categoria.', [
                'action' => 'ai_admin_suggest_category',
                'user_id' => $userId,
            ]);
        }

        return $this->success(['category' => $category]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function analyzeSpending(int $userId, array $payload): array
    {
        $lancamentos = $payload['lancamentos'] ?? [];
        $period = trim((string) ($payload['period'] ?? 'ultimo mes'));

        if (empty($lancamentos) || !is_array($lancamentos)) {
            return $this->failure('Nenhum dado fornecido para analise', 422);
        }

        $start = microtime(true);
        $ai = $this->createAiService();

        try {
            $result = $ai->analyzeSpending($lancamentos, $period);
        } catch (Throwable $e) {
            $this->logLegacyAiCall($userId, 'analyze_spending', "period={$period}", null, false, $start, $e->getMessage(), $ai);

            return $this->internalFailure($e, 'Erro ao gerar analise de gastos.', [
                'action' => 'ai_admin_analyze_spending',
                'user_id' => $userId,
            ]);
        }

        $resultPreview = is_array($result)
            ? json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        $this->logLegacyAiCall(
            $userId,
            'analyze_spending',
            "period={$period}",
            $resultPreview,
            !empty($result),
            $start,
            empty($result) ? 'Analise indisponivel' : null,
            $ai
        );

        if (empty($result)) {
            return $this->failure('Analise de IA indisponivel no momento. Verifique se o servico esta rodando.', 503);
        }

        return $this->success($result);
    }

    protected function createAiService(): AIService
    {
        return new AIService();
    }

    protected function gatherSystemContext(): array
    {
        return (new SystemContextService())->gather();
    }

    protected function createHttpClient(array $config): Client
    {
        return new Client($config);
    }

    protected function cache(): CacheService
    {
        return new CacheService();
    }

    private function logLegacyAiCall(
        int $userId,
        string $type,
        string $prompt,
        ?string $response,
        bool $success,
        float $start,
        ?string $errorMessage,
        AIService $ai
    ): void {
        try {
            $provider = $ai->getProvider();
            $meta = $provider->getLastMeta();

            AiLogService::log([
                'user_id' => $userId,
                'type' => $type,
                'prompt' => mb_substr($prompt, 0, 5000),
                'response' => $response !== null ? mb_substr($response, 0, 10000) : null,
                'provider' => $_ENV['AI_PROVIDER'] ?? 'openai',
                'model' => $provider->getModel(),
                'tokens_prompt' => $meta['tokens_prompt'] ?? 0,
                'tokens_completion' => $meta['tokens_completion'] ?? 0,
                'tokens_total' => $meta['tokens_total'] ?? 0,
                'response_time_ms' => (int) round((microtime(true) - $start) * 1000),
                'success' => $success,
                'error_message' => $success ? null : mb_substr((string) ($errorMessage ?? 'Erro desconhecido'), 0, 1000),
            ]);
        } catch (\Throwable) {
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function success(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $message, int $status, mixed $errors = null): array
    {
        $result = [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];

        if ($errors !== null) {
            $result['errors'] = $errors;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function internalFailure(Throwable $e, string $publicMessage, array $context = []): array
    {
        $errorId = LogService::reportException(
            e: $e,
            publicMessage: $publicMessage,
            context: $context,
            category: LogCategory::GENERAL
        );

        return $this->failure($publicMessage, 500, [
            'error_id' => $errorId,
            'request_id' => $errorId,
        ]);
    }
}
