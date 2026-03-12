<?php

declare(strict_types=1);

namespace Application\Services\AI\Telegram;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Serviço para comunicação com a Telegram Bot API.
 *
 * Responsabilidades:
 *  - Enviar mensagens de texto
 *  - Enviar mensagens com botões inline (confirmação)
 *  - Responder callback queries
 *
 * Env vars necessárias:
 *  TELEGRAM_BOT_TOKEN=...   (Token do BotFather)
 */
class TelegramService
{
    private const BASE_URL = 'https://api.telegram.org';

    private Client $http;
    private string $token;

    public function __construct()
    {
        $this->token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN') ?: '';

        $this->http = new Client([
            'base_uri' => self::BASE_URL . '/bot' . $this->token . '/',
            'timeout'  => 10,
            'headers'  => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Verifica se o serviço está configurado.
     */
    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    /**
     * Envia mensagem de texto simples.
     */
    public function sendText(string $chatId, string $text): bool
    {
        return $this->request('sendMessage', [
            'chat_id'    => $chatId,
            'text'       => mb_substr($text, 0, 4096),
            'parse_mode' => 'HTML',
        ]);
    }

    /**
     * Envia mensagem com botões inline de confirmação (Sim / Não).
     * Usado no fluxo de confirmação de transações.
     */
    public function sendConfirmationButtons(
        string $chatId,
        string $bodyText,
        string $yesData = 'confirm_yes',
        string $noData = 'confirm_no',
    ): bool {
        return $this->sendInlineKeyboard($chatId, $bodyText, [
            [
                ['text' => '✅ Sim', 'callback_data' => $yesData],
                ['text' => '❌ Não', 'callback_data' => $noData],
            ],
        ]);
    }

    /**
     * Envia mensagem com teclado inline dinâmico.
     *
     * @param string $chatId
     * @param string $text
     * @param array  $rows  Array de rows, cada row é array de ['text' => ..., 'callback_data' => ...]
     */
    public function sendInlineKeyboard(string $chatId, string $text, array $rows): bool
    {
        return $this->request('sendMessage', [
            'chat_id'      => $chatId,
            'text'         => mb_substr($text, 0, 4096),
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => $rows,
            ],
        ]);
    }

    /**
     * Responde a um callback_query (remove o "loading" do botão).
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): bool
    {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text'              => mb_substr($text, 0, 200),
        ]);
    }

    /**
     * Registra o webhook do bot no Telegram.
     */
    public function setWebhook(string $url, string $secretToken = ''): array
    {
        $params = ['url' => $url];

        if ($secretToken !== '') {
            $params['secret_token'] = $secretToken;
        }

        $params['allowed_updates'] = ['message', 'callback_query'];

        return $this->requestWithResponse('setWebhook', $params);
    }

    /**
     * Remove o webhook do bot.
     */
    public function deleteWebhook(): array
    {
        return $this->requestWithResponse('deleteWebhook', []);
    }

    /**
     * Retorna informações do bot (getMe).
     */
    public function getMe(): array
    {
        return $this->requestWithResponse('getMe', []);
    }

    /**
     * Retorna o token secreto para validação do webhook.
     */
    public static function getWebhookSecret(): string
    {
        return $_ENV['TELEGRAM_WEBHOOK_SECRET'] ?? getenv('TELEGRAM_WEBHOOK_SECRET') ?: '';
    }

    // ─── Internal ──────────────────────────────────────────

    /**
     * Faz requisição e retorna true/false.
     */
    private function request(string $method, array $params): bool
    {
        if (!$this->isConfigured()) {
            error_log('[Telegram] Serviço não configurado. Defina TELEGRAM_BOT_TOKEN.');
            return false;
        }

        try {
            $response = $this->http->post($method, [
                'json' => $params,
            ]);

            $status = $response->getStatusCode();

            if ($status >= 200 && $status < 300) {
                return true;
            }

            error_log("[Telegram] Resposta inesperada: HTTP {$status}");
            return false;
        } catch (GuzzleException $e) {
            error_log("[Telegram] Erro ao chamar {$method}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Faz requisição e retorna o body decodificado.
     */
    private function requestWithResponse(string $method, array $params): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'description' => 'Serviço não configurado'];
        }

        try {
            $response = $this->http->post($method, [
                'json' => $params,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (GuzzleException $e) {
            error_log("[Telegram] Erro ao chamar {$method}: " . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }
}
