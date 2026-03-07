<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\AI\AIService;

class AiApiController extends BaseController
{
    private function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->is_admin == 1;
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
