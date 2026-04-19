<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Services\AI\AiLogService;
use Application\Services\AI\Telegram\TelegramService;
use Application\Services\AI\Telegram\TelegramWebhookWorkflowService;
use Application\Services\Infrastructure\LogService;

/**
 * Controller para o webhook do Telegram Bot API.
 *
 * Endpoint:
 *  POST /api/webhook/telegram  -> Recepcao de updates
 */
class TelegramWebhookController extends ApiController
{
    private ?TelegramWebhookWorkflowService $workflowService;

    public function __construct(?TelegramWebhookWorkflowService $workflowService = null)
    {
        parent::__construct();
        $this->workflowService = $workflowService;
    }

    public function receive(): Response
    {
        $expectedSecret = TelegramService::getWebhookSecret();
        $secretHeader = $this->requestSecretHeader();

        if ($expectedSecret !== '' && $secretHeader !== $expectedSecret) {
            return $this->forbiddenWebhook('Telegram webhook secret invalido');
        }

        $this->workflowService()->handleWebhookBody($this->readRawBody());

        return Response::jsonResponse(['ok' => true]);
    }

    protected function readRawBody(): string
    {
        return $this->request->rawInput();
    }

    private function workflowService(): TelegramWebhookWorkflowService
    {
        return $this->workflowService ??= $this->resolveOrCreate(
            null,
            TelegramWebhookWorkflowService::class
        );
    }

    private function plainTextResponse(string $content, int $statusCode = 200): Response
    {
        return Response::htmlResponse($content, $statusCode)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function requestSecretHeader(): string
    {
        return $this->request->header('x-telegram-bot-api-secret-token') ?? '';
    }

    private function requestIp(): string
    {
        return $this->request->ip();
    }

    private function forbiddenWebhook(string $message): Response
    {
        LogService::persist(
            LogLevel::WARNING,
            LogCategory::WEBHOOK,
            $message,
            ['ip' => $this->requestIp()],
        );

        AiLogService::log([
            'user_id' => null,
            'type' => 'chat',
            'channel' => 'telegram',
            'prompt' => 'Webhook Telegram rejeitado',
            'response' => null,
            'provider' => 'internal',
            'model' => 'webhook',
            'tokens_prompt' => 0,
            'tokens_completion' => 0,
            'tokens_total' => 0,
            'response_time_ms' => 0,
            'success' => false,
            'error_message' => $message,
            'source' => 'webhook',
            'confidence' => 0,
            'prompt_version' => 'telegram_webhook_v1',
        ]);

        return $this->plainTextResponse('Forbidden', 403);
    }
}
