<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
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
        $secretHeader = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
        $expectedSecret = TelegramService::getWebhookSecret();

        if ($expectedSecret !== '' && $secretHeader !== $expectedSecret) {
            LogService::persist(
                LogLevel::WARNING,
                LogCategory::WEBHOOK,
                'Telegram webhook secret invalido',
                ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
            );

            return $this->plainTextResponse('Forbidden', 403);
        }

        $this->workflowService()->handleWebhookBody($this->readRawBody());

        return Response::jsonResponse(['ok' => true]);
    }

    protected function readRawBody(): string
    {
        $rawBody = file_get_contents('php://input');
        return is_string($rawBody) ? $rawBody : '';
    }

    private function workflowService(): TelegramWebhookWorkflowService
    {
        return $this->workflowService ??= new TelegramWebhookWorkflowService();
    }

    private function plainTextResponse(string $content, int $statusCode = 200): Response
    {
        return Response::htmlResponse($content, $statusCode)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
