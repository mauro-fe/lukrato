<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\DTO\AI\AIRequestDTO;
use Application\Lib\Auth;
use Application\Services\AI\AIService;
use Application\Services\AI\AiLogService;
use Application\Services\AI\ContextCompressor;
use Application\Services\AI\Providers\OpenAIProvider;
use Application\Services\AI\SystemContextService;
use Application\Services\Infrastructure\CacheService;
use GuzzleHttp\Client;

class AiApiController extends BaseController
{
    private function logLegacyAiCall(
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
                'user_id'           => $this->userId,
                'type'              => $type,
                'prompt'            => mb_substr($prompt, 0, 5000),
                'response'          => $response !== null ? mb_substr($response, 0, 10000) : null,
                'provider'          => $_ENV['AI_PROVIDER'] ?? 'openai',
                'model'             => $provider->getModel(),
                'tokens_prompt'     => $meta['tokens_prompt'] ?? 0,
                'tokens_completion' => $meta['tokens_completion'] ?? 0,
                'tokens_total'      => $meta['tokens_total'] ?? 0,
                'response_time_ms'  => (int) round((microtime(true) - $start) * 1000),
                'success'           => $success,
                'error_message'     => $success ? null : mb_substr((string) ($errorMessage ?? 'Erro desconhecido'), 0, 1000),
            ]);
        } catch (\Throwable) {
            // Nunca interromper a resposta da API por falha de logging
        }
    }

    private function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->is_admin == 1;
    }

    /**
     * GET /api/sysadmin/ai/health-proxy
     */
    public function healthProxy(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        $provider = strtolower($_ENV['AI_PROVIDER'] ?? 'openai');

        if ($provider === 'openai') {
            $hasKey = !empty($_ENV['OPENAI_API_KEY']);
            $model  = $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini';

            Response::success([
                'status'   => $hasKey ? 'ok' : 'error',
                'service'  => 'lukrato-ai',
                'provider' => 'openai',
                'model'    => $model,
                'message'  => $hasKey ? 'OpenAI configurada' : 'OPENAI_API_KEY não configurada',
            ]);
            return;
        }

        // Fallback para serviço Python (Ollama)
        $serviceUrl = rtrim($_ENV['AI_SERVICE_URL'] ?? 'http://127.0.0.1:8002', '/');

        try {
            $client = new Client(['timeout' => 5, 'connect_timeout' => 3]);
            $res    = $client->get("{$serviceUrl}/health");
            $data   = json_decode($res->getBody()->getContents(), true);

            Response::success($data ?? ['status' => 'ok']);
        } catch (\Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Serviço Python offline',
            ], 503);
        }
    }

    /**
     * GET /api/sysadmin/ai/quota
     * Retorna rate limits cacheados da OpenAI. Se não houver cache, faz chamada mínima.
     */
    public function quota(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        $provider = strtolower($_ENV['AI_PROVIDER'] ?? 'openai');

        if ($provider !== 'openai') {
            Response::success([
                'provider' => $provider,
                'message'  => 'Rate limits não disponíveis para este provider',
            ]);
            return;
        }

        $apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        if (empty($apiKey)) {
            Response::error('OPENAI_API_KEY não configurada', 400);
            return;
        }

        $model = $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini';

        // Tentar ler do cache primeiro (salvo pelo OpenAIProvider após cada chamada real)
        $cache = new CacheService();
        $cached = $cache->get('ai:openai_rate_limits');

        if (is_array($cached) && !empty($cached['requests_limit'])) {
            Response::success([
                'provider'            => 'openai',
                'model'               => $model,
                'status'              => 'active',
                'requests_limit'      => $cached['requests_limit'] ?? 0,
                'requests_remaining'  => $cached['requests_remaining'] ?? 0,
                'tokens_limit'        => $cached['tokens_limit'] ?? 0,
                'tokens_remaining'    => $cached['tokens_remaining'] ?? 0,
                'reset_requests'      => $cached['reset_requests'] ?? null,
                'reset_tokens'        => $cached['reset_tokens'] ?? null,
                'source'              => 'cache',
            ]);
            return;
        }

        // Fallback: chamada mínima à API
        try {
            $client = new Client([
                'base_uri'        => 'https://api.openai.com/v1/',
                'timeout'         => 10,
                'connect_timeout' => 5,
            ]);

            $response = $client->post('chat/completions', [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'      => $model,
                    'messages'   => [['role' => 'user', 'content' => 'hi']],
                    'max_tokens' => 1,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $usage = $body['usage'] ?? [];

            $rateLimits = [
                'requests_limit'      => (int) ($response->getHeaderLine('x-ratelimit-limit-requests') ?: 0),
                'requests_remaining'  => (int) ($response->getHeaderLine('x-ratelimit-remaining-requests') ?: 0),
                'tokens_limit'        => (int) ($response->getHeaderLine('x-ratelimit-limit-tokens') ?: 0),
                'tokens_remaining'    => (int) ($response->getHeaderLine('x-ratelimit-remaining-tokens') ?: 0),
                'reset_requests'      => $response->getHeaderLine('x-ratelimit-reset-requests') ?: null,
                'reset_tokens'        => $response->getHeaderLine('x-ratelimit-reset-tokens') ?: null,
            ];

            $cache->set('ai:openai_rate_limits', $rateLimits, 300);

            Response::success(array_merge([
                'provider'         => 'openai',
                'model'            => $model,
                'status'           => 'active',
                'test_tokens_used' => $usage['total_tokens'] ?? 0,
                'source'           => 'api',
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

            Response::json([
                'success' => true,
                'data'    => [
                    'provider' => 'openai',
                    'model'    => $model,
                    'status'   => $code,
                    'message'  => $errMsg,
                    'requests_limit'     => 0,
                    'requests_remaining' => 0,
                    'tokens_limit'       => 0,
                    'tokens_remaining'   => 0,
                ],
            ]);
        } catch (\Throwable $e) {
            Response::error('Erro ao verificar quota: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/sysadmin/ai/chat
     *
     * Body: { "message": string, "context"?: object }
     */
    public function chat(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        $payload = $this->getRequestPayload();
        $message = trim($payload['message'] ?? '');
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

        if ($message === '') {
            Response::error('Mensagem não pode ser vazia', 422);
            return;
        }

        if (mb_strlen($message) > 2000) {
            Response::error('Mensagem muito longa (máximo 2000 caracteres)', 422);
            return;
        }

        $start = microtime(true);

        try {
            $systemContext = (new SystemContextService())->gather();
        } catch (\Throwable) {
            $systemContext = [];
        }
        $context = ContextCompressor::compress(
            array_merge($systemContext, $context),
            $message
        );

        $ai = new AIService();

        try {
            $request = AIRequestDTO::adminChat($message, $context);
            $aiResponse = $ai->dispatch($request);

            if (!$aiResponse->success) {
                Response::error($aiResponse->message, 500);
                return;
            }
        } catch (\Throwable $e) {
            Response::error('Erro ao processar chat de IA: ' . $e->getMessage(), 500);
            return;
        }

        Response::success(['response' => $aiResponse->message]);
    }

    /**
     * POST /api/sysadmin/ai/suggest-category
     *
     * Body: { "description": string, "categories"?: string[] }
     */
    public function suggestCategory(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        $payload     = $this->getRequestPayload();
        $description = trim($payload['description'] ?? '');
        $categories  = is_array($payload['categories'] ?? null) ? $payload['categories'] : [];

        if (mb_strlen($description) < 3) {
            Response::json(['category' => null]);
            return;
        }

        $start = microtime(true);
        $ai = new AIService();

        try {
            $category = $ai->suggestCategory($description, $categories);
            $this->logLegacyAiCall(
                'suggest_category',
                $description,
                $category,
                true,
                $start,
                null,
                $ai
            );
        } catch (\Throwable $e) {
            $this->logLegacyAiCall('suggest_category', $description, null, false, $start, $e->getMessage(), $ai);
            Response::error('Erro ao sugerir categoria: ' . $e->getMessage(), 500);
            return;
        }

        Response::json(['category' => $category]);
    }

    /**
     * POST /api/sysadmin/ai/analyze-spending
     *
     * Body: { "lancamentos": array, "period"?: string }
     */
    public function analyzeSpending(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        $payload     = $this->getRequestPayload();
        $lancamentos = $payload['lancamentos'] ?? [];
        $period      = trim($payload['period'] ?? 'último mês');

        if (empty($lancamentos) || !is_array($lancamentos)) {
            Response::error('Nenhum dado fornecido para análise', 422);
            return;
        }

        $start = microtime(true);
        $ai = new AIService();

        try {
            $result = $ai->analyzeSpending($lancamentos, $period);
        } catch (\Throwable $e) {
            $this->logLegacyAiCall('analyze_spending', "period={$period}", null, false, $start, $e->getMessage(), $ai);
            Response::error('Erro ao gerar análise de gastos: ' . $e->getMessage(), 500);
            return;
        }

        $resultPreview = is_array($result)
            ? json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        $this->logLegacyAiCall(
            'analyze_spending',
            "period={$period}",
            $resultPreview,
            !empty($result),
            $start,
            empty($result) ? 'Análise indisponível' : null,
            $ai
        );

        if (empty($result)) {
            Response::json([
                'success' => false,
                'message' => 'Análise de IA indisponível no momento. Verifique se o serviço está rodando.',
            ], 503);
            return;
        }

        Response::success($result);
    }
}
