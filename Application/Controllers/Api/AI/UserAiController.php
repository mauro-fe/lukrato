<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\DTO\AI\AIRequestDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Services\AI\AIService;
use Application\Services\AI\Context\UserContextBuilder;
use Application\Services\AI\ContextCompressor;

/**
 * Controller de IA para usuários autenticados.
 * Expõe chat, categorização e análise financeira com contexto scoped.
 */
class UserAiController extends BaseController
{
    /**
     * POST /api/ai/chat
     *
     * Body: { "message": string }
     */
    public function chat(): void
    {
        $this->requireAuthApi();

        $payload = $this->getRequestPayload();
        $message = trim($payload['message'] ?? '');

        if ($message === '') {
            Response::error('Mensagem não pode ser vazia.', 422);
            return;
        }

        if (mb_strlen($message) > 2000) {
            Response::error('Mensagem muito longa (máximo 2000 caracteres).', 422);
            return;
        }

        try {
            // Coletar contexto scoped ao usuário
            $contextBuilder = new UserContextBuilder();
            $context = $contextBuilder->build($this->userId);
        } catch (\Throwable) {
            $context = [];
        }

        // Comprimir contexto baseado na mensagem
        $context = ContextCompressor::compress($context, $message);

        // Dispatch pela nova pipeline unificada
        $ai = new AIService();
        $request = new AIRequestDTO(
            userId:  $this->userId,
            message: $message,
            context: $context,
            channel: AIChannel::WEB,
        );

        $response = $ai->dispatch($request);

        Response::json([
            'success'  => $response->success,
            'data'     => [
                'response' => $response->message,
                'intent'   => $response->intent?->value,
                'source'   => $response->source,
                'cached'   => $response->cached,
            ],
        ], $response->success ? 200 : 503);
    }

    /**
     * POST /api/ai/suggest-category
     *
     * Body: { "description": string }
     */
    public function suggestCategory(): void
    {
        $this->requireAuthApi();

        $payload     = $this->getRequestPayload();
        $description = trim($payload['description'] ?? '');

        if (mb_strlen($description) < 2) {
            Response::json(['success' => false, 'data' => ['category' => null]], 422);
            return;
        }

        $ai = new AIService();
        $request = AIRequestDTO::categorize($this->userId, $description);

        $response = $ai->dispatch($request);

        Response::json([
            'success' => $response->success,
            'data'    => $response->data,
            'source'  => $response->source,
        ]);
    }

    /**
     * POST /api/ai/analyze
     *
     * Body: { "period"?: string }
     */
    public function analyze(): void
    {
        $this->requireAuthApi();

        $payload = $this->getRequestPayload();
        $period  = trim($payload['period'] ?? 'último mês');

        try {
            $contextBuilder = new UserContextBuilder();
            $context = $contextBuilder->build($this->userId);
        } catch (\Throwable) {
            $context = [];
        }

        $ai = new AIService();
        $request = AIRequestDTO::analyze($this->userId, $context, $period);

        $response = $ai->dispatch($request);

        if (!$response->success) {
            Response::json([
                'success' => false,
                'message' => $response->message,
            ], 503);
            return;
        }

        Response::json([
            'success' => true,
            'data'    => $response->data,
            'source'  => $response->source,
            'cached'  => $response->cached,
        ]);
    }

    /**
     * POST /api/ai/extract-transaction
     *
     * Body: { "message": string }
     */
    public function extractTransaction(): void
    {
        $this->requireAuthApi();

        $payload = $this->getRequestPayload();
        $message = trim($payload['message'] ?? '');

        if (mb_strlen($message) < 3) {
            Response::error('Mensagem muito curta.', 422);
            return;
        }

        $ai = new AIService();
        $request = AIRequestDTO::extractTransaction($this->userId, $message);
        // Usar canal WEB (não WhatsApp) quando chamado pela API
        $request = new AIRequestDTO(
            userId:  $this->userId,
            message: $message,
            intent:  IntentType::EXTRACT_TRANSACTION,
            channel: AIChannel::WEB,
        );

        $response = $ai->dispatch($request);

        Response::json([
            'success' => $response->success,
            'data'    => $response->data,
            'message' => $response->message,
            'source'  => $response->source,
        ], $response->success ? 200 : 422);
    }
}
