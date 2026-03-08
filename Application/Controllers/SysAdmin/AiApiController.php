<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\AI\AIService;
use Application\Services\AI\Providers\OpenAIProvider;
use Application\Services\AI\SystemContextService;
use GuzzleHttp\Client;

class AiApiController extends BaseController
{
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
     * Faz uma chamada mínima à OpenAI para retornar os rate limits atuais.
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

        try {
            $client = new Client([
                'base_uri'        => 'https://api.openai.com/v1/',
                'timeout'         => 10,
                'connect_timeout' => 5,
            ]);

            $model = $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini';

            // Chamada mínima: 1 token de resposta
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

            Response::success([
                'provider'            => 'openai',
                'model'               => $model,
                'status'              => 'active',
                'requests_limit'      => (int) ($response->getHeaderLine('x-ratelimit-limit-requests') ?: 0),
                'requests_remaining'  => (int) ($response->getHeaderLine('x-ratelimit-remaining-requests') ?: 0),
                'tokens_limit'        => (int) ($response->getHeaderLine('x-ratelimit-limit-tokens') ?: 0),
                'tokens_remaining'    => (int) ($response->getHeaderLine('x-ratelimit-remaining-tokens') ?: 0),
                'reset_requests'      => $response->getHeaderLine('x-ratelimit-reset-requests') ?: null,
                'reset_tokens'        => $response->getHeaderLine('x-ratelimit-reset-tokens') ?: null,
                'test_tokens_used'    => $usage['total_tokens'] ?? 0,
            ]);
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
                    'model'    => $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini',
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

        try {
            $systemContext = (new SystemContextService())->gather();
        } catch (\Throwable) {
            $systemContext = [];
        }
        $context = array_merge($systemContext, $context);

        $ai       = new AIService();
        $response = $ai->chat($message, $context);

        Response::success(['response' => $response]);
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

        $ai       = new AIService();
        $category = $ai->suggestCategory($description, $categories);

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

        $ai     = new AIService();
        $result = $ai->analyzeSpending($lancamentos, $period);

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
