<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\AI\AIService;
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

        $systemContext = (new SystemContextService())->gather();
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
