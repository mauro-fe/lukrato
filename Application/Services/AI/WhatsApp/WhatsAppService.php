<?php

declare(strict_types=1);

namespace Application\Services\AI\WhatsApp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Serviço para comunicação com a Meta Cloud API (WhatsApp Business).
 *
 * Responsabilidades:
 *  - Enviar mensagens de texto
 *  - Enviar mensagens interativas (botões de confirmação)
 *  - Marcar mensagens como lidas
 *
 * Env vars necessárias:
 *  WHATSAPP_TOKEN=...          (Bearer token permanente)
 *  WHATSAPP_PHONE_ID=...       (Phone Number ID da Meta)
 *  WHATSAPP_VERIFY_TOKEN=...   (Token para verificação do webhook)
 */
class WhatsAppService
{
    private const API_VERSION = 'v21.0';
    private const BASE_URL    = 'https://graph.facebook.com';

    private Client $http;
    private string $token;
    private string $phoneId;

    public function __construct()
    {
        $this->token   = $_ENV['WHATSAPP_TOKEN']    ?? getenv('WHATSAPP_TOKEN')    ?: '';
        $this->phoneId = $_ENV['WHATSAPP_PHONE_ID'] ?? getenv('WHATSAPP_PHONE_ID') ?: '';

        $this->http = new Client([
            'base_uri' => self::BASE_URL . '/' . self::API_VERSION . '/',
            'timeout'  => 10,
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Verifica se o serviço está configurado.
     */
    public function isConfigured(): bool
    {
        return $this->token !== '' && $this->phoneId !== '';
    }

    /**
     * Envia mensagem de texto simples.
     */
    public function sendText(string $toPhone, string $text): bool
    {
        return $this->sendMessage($toPhone, [
            'type' => 'text',
            'text' => ['body' => mb_substr($text, 0, 4096)],
        ]);
    }

    /**
     * Envia mensagem com botões de confirmação (Sim / Não).
     * Usado no fluxo de confirmação de transações.
     *
     * @param string $toPhone   Número do destinatário (formato 5511999999999)
     * @param string $bodyText  Texto do corpo da mensagem
     * @param string $yesId     ID do botão "Sim" (usado no interactive reply)
     * @param string $noId      ID do botão "Não"
     */
    public function sendConfirmationButtons(
        string $toPhone,
        string $bodyText,
        string $yesId = 'confirm_yes',
        string $noId = 'confirm_no',
    ): bool {
        return $this->sendMessage($toPhone, [
            'type'        => 'interactive',
            'interactive' => [
                'type'   => 'button',
                'body'   => ['text' => mb_substr($bodyText, 0, 1024)],
                'action' => [
                    'buttons' => [
                        [
                            'type'  => 'reply',
                            'reply' => ['id' => $yesId, 'title' => '✅ Sim'],
                        ],
                        [
                            'type'  => 'reply',
                            'reply' => ['id' => $noId, 'title' => '❌ Não'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Marca uma mensagem recebida como lida.
     */
    public function markAsRead(string $waMessageId): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $this->http->post("{$this->phoneId}/messages", [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'status'            => 'read',
                    'message_id'        => $waMessageId,
                ],
            ]);

            return true;
        } catch (GuzzleException) {
            return false;
        }
    }

    /**
     * Envia mensagem para um número.
     */
    private function sendMessage(string $toPhone, array $messagePayload): bool
    {
        if (!$this->isConfigured()) {
            error_log('[WhatsApp] Serviço não configurado. Defina WHATSAPP_TOKEN e WHATSAPP_PHONE_ID.');
            return false;
        }

        $payload = array_merge([
            'messaging_product' => 'whatsapp',
            'to'                => $toPhone,
        ], $messagePayload);

        try {
            $response = $this->http->post("{$this->phoneId}/messages", [
                'json' => $payload,
            ]);

            $status = $response->getStatusCode();

            if ($status >= 200 && $status < 300) {
                return true;
            }

            error_log("[WhatsApp] Resposta inesperada: HTTP {$status}");
            return false;
        } catch (GuzzleException $e) {
            error_log("[WhatsApp] Erro ao enviar mensagem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna o verify token configurado (para validação do webhook).
     */
    public static function getVerifyToken(): string
    {
        return $_ENV['WHATSAPP_VERIFY_TOKEN'] ?? getenv('WHATSAPP_VERIFY_TOKEN') ?: '';
    }
}
