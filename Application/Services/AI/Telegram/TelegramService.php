<?php

declare(strict_types=1);

namespace Application\Services\AI\Telegram;

use Application\Config\TelegramRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Services\Infrastructure\LogService;
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

    private TelegramBotClient $http;
    private string $token;
    private TelegramRuntimeConfig $runtimeConfig;
    private ?string $lastErrorMessage = null;

    public function __construct(?TelegramBotClient $http = null, ?TelegramRuntimeConfig $runtimeConfig = null)
    {
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, TelegramRuntimeConfig::class);
        $this->token = $this->runtimeConfig->botToken();

        $this->http = ApplicationContainer::resolveOrNew($http, TelegramBotClient::class);
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
            'chat_id' => $chatId,
            'text' => mb_substr($text, 0, 4096),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
    }

    /**
     * Envia texto sem parse_mode. Usado como fallback quando HTML ou reply_markup falham.
     */
    public function sendPlainText(string $chatId, string $text): bool
    {
        return $this->request('sendMessage', [
            'chat_id' => $chatId,
            'text' => mb_substr($text, 0, 4096),
            'disable_web_page_preview' => true,
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
        return self::runtimeConfig()->webhookSecret();
    }

    public function lastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    // ─── Internal ──────────────────────────────────────────

    /**
     * Faz requisição e retorna true/false.
     */
    private function request(string $method, array $params): bool
    {
        if (!$this->isConfigured()) {
            $this->logFailure($method, 'Servico Telegram nao configurado. Defina TELEGRAM_BOT_TOKEN.', [
                'reason' => 'missing_token',
            ]);

            return false;
        }

        try {
            $response = $this->http->post($method, [
                'json' => $params,
            ]);

            $status = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if (
                $status >= 200
                && $status < 300
                && is_array($body)
                && ($body['ok'] ?? false) === true
            ) {
                $this->lastErrorMessage = null;
                return true;
            }

            $description = is_array($body)
                ? (string) ($body['description'] ?? 'Resposta sem descricao.')
                : 'Resposta invalida da API.';

            $this->logFailure($method, "Telegram API retornou falha: HTTP {$status} - {$description}", [
                'http_status' => $status,
                'description' => $description,
            ]);

            return false;
        } catch (GuzzleException $e) {
            $this->logFailure($method, 'Erro ao chamar Telegram API: ' . $e->getMessage(), [
                'exception' => get_class($e),
            ]);

            return false;
        }
    }

    /**
     * Faz requisição e retorna o body decodificado.
     */
    private function requestWithResponse(string $method, array $params): array
    {
        if (!$this->isConfigured()) {
            $this->logFailure($method, 'Servico Telegram nao configurado. Defina TELEGRAM_BOT_TOKEN.', [
                'reason' => 'missing_token',
            ]);

            return ['ok' => false, 'description' => 'Serviço não configurado'];
        }

        try {
            $response = $this->http->post($method, [
                'json' => $params,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!is_array($body)) {
                $this->logFailure($method, 'Telegram API retornou resposta invalida.', [
                    'reason' => 'invalid_response',
                ]);

                return ['ok' => false, 'description' => 'Resposta invalida da API'];
            }

            if (($body['ok'] ?? false) !== true) {
                $description = (string) ($body['description'] ?? 'Sem descricao');
                $this->logFailure($method, "Telegram API retornou falha: {$description}", [
                    'description' => $description,
                ]);
            } else {
                $this->lastErrorMessage = null;
            }

            return $body;
        } catch (GuzzleException $e) {
            $this->logFailure($method, 'Erro ao chamar Telegram API: ' . $e->getMessage(), [
                'exception' => get_class($e),
            ]);

            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logFailure(string $method, string $message, array $context = []): void
    {
        $message = $this->sanitizeErrorMessage($message);
        $this->lastErrorMessage = $message;

        $context = array_merge([
            'action' => 'telegram_api_request',
            'method' => $method,
        ], $context);

        try {
            LogService::persist(
                LogLevel::WARNING,
                LogCategory::WEBHOOK,
                $message,
                $context,
            );
        } catch (\Throwable) {
            LogService::safeErrorLog("[Telegram] {$method} falhou: {$message}");
        }
    }

    private function sanitizeErrorMessage(string $message): string
    {
        if ($this->token !== '') {
            $message = str_replace($this->token, '[telegram-token]', $message);
        }

        return (string) preg_replace('/bot\d+:[A-Za-z0-9_-]+/', 'bot[telegram-token]', $message);
    }

    private static function runtimeConfig(): TelegramRuntimeConfig
    {
        return ApplicationContainer::resolveOrNew(null, TelegramRuntimeConfig::class);
    }
}
