<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Services\AI\WhatsApp\WhatsAppService;
use Application\Services\AI\WhatsApp\WhatsAppWebhookWorkflowService;
use Application\Services\Infrastructure\LogService;

/**
 * Controller para o webhook do WhatsApp (Meta Cloud API).
 *
 * Endpoints:
 *  GET  /api/webhook/whatsapp  -> Verificacao do webhook (hub.challenge)
 *  POST /api/webhook/whatsapp  -> Recepcao de mensagens
 */
class WhatsAppWebhookController extends ApiController
{
    private ?WhatsAppWebhookWorkflowService $workflowService;

    public function __construct(?WhatsAppWebhookWorkflowService $workflowService = null)
    {
        parent::__construct();
        $this->workflowService = $workflowService;
    }

    public function verify(): Response
    {
        $mode = $this->getStringQuery('hub_mode', $this->getStringQuery('hub.mode', ''));
        $token = $this->getStringQuery('hub_verify_token', $this->getStringQuery('hub.verify_token', ''));
        $challenge = $this->getStringQuery('hub_challenge', $this->getStringQuery('hub.challenge', ''));

        $expectedToken = WhatsAppService::getVerifyToken();

        if ($mode === 'subscribe' && $token === $expectedToken && $expectedToken !== '') {
            return $this->plainTextResponse($challenge);
        }

        LogService::persist(
            LogLevel::WARNING,
            LogCategory::WEBHOOK,
            'WhatsApp webhook verify falhou',
            ['mode' => $mode, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
        );

        return $this->plainTextResponse('Forbidden', 403);
    }

    public function receive(): Response
    {
        $rawBody = $this->readRawBody();

        if (!$this->isValidWebhookSignature($rawBody)) {
            LogService::persist(
                LogLevel::WARNING,
                LogCategory::WEBHOOK,
                'WhatsApp webhook signature invalida',
                ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
            );

            return $this->plainTextResponse('Forbidden', 403);
        }

        $this->workflowService()->handleWebhookBody($rawBody);

        return $this->plainTextResponse('OK');
    }

    protected function readRawBody(): string
    {
        $rawBody = file_get_contents('php://input');
        return is_string($rawBody) ? $rawBody : '';
    }

    private function workflowService(): WhatsAppWebhookWorkflowService
    {
        return $this->workflowService ??= new WhatsAppWebhookWorkflowService();
    }

    private function isValidWebhookSignature(string $rawBody): bool
    {
        $appSecret = WhatsAppService::getAppSecret();
        if ($appSecret === '') {
            return true;
        }

        $signature = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
        if ($signature === '' || !str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
        return hash_equals($expected, $signature);
    }

    private function plainTextResponse(string $content, int $statusCode = 200): Response
    {
        return Response::htmlResponse($content, $statusCode)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
