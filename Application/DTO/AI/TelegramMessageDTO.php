<?php

declare(strict_types=1);

namespace Application\DTO\AI;

/**
 * DTO para mensagens/updates recebidos via Telegram Bot API.
 * Normaliza o payload bruto do webhook em uma estrutura previsível.
 */
readonly class TelegramMessageDTO
{
    public function __construct(
        public string  $updateId,
        public string  $messageId,
        public string  $chatId,
        public string  $type,
        public string  $body,
        public ?string $callbackQueryId = null,
        public ?string $displayName = null,
        public ?string $username = null,
        public array   $rawPayload = [],
    ) {}

    /**
     * Cria DTO a partir do payload do webhook do Telegram.
     *
     * @param array $update  Update completo do Telegram
     * @return self|null     null se o payload não for processável
     */
    public static function fromTelegramUpdate(array $update): ?self
    {
        $updateId = (string) ($update['update_id'] ?? '');

        if ($updateId === '') {
            return null;
        }

        // callback_query (clique em botão inline)
        if (isset($update['callback_query'])) {
            $cb      = $update['callback_query'];
            $msg     = $cb['message'] ?? [];
            $from    = $cb['from'] ?? [];
            $chatId  = (string) ($msg['chat']['id'] ?? $from['id'] ?? '');

            $firstName = $from['first_name'] ?? '';
            $lastName  = $from['last_name'] ?? '';
            $name      = trim("{$firstName} {$lastName}") ?: null;

            return new self(
                updateId: $updateId,
                messageId: (string) ($msg['message_id'] ?? ''),
                chatId: $chatId,
                type: 'callback_query',
                body: $cb['data'] ?? '',
                callbackQueryId: $cb['id'] ?? null,
                displayName: $name,
                username: $from['username'] ?? null,
                rawPayload: $update,
            );
        }

        // message (texto ou comando)
        if (isset($update['message'])) {
            $msg    = $update['message'];
            $from   = $msg['from'] ?? [];
            $chatId = (string) ($msg['chat']['id'] ?? '');
            $text   = $msg['text'] ?? '';

            if ($text === '' || $chatId === '') {
                return null;
            }

            $firstName = $from['first_name'] ?? '';
            $lastName  = $from['last_name'] ?? '';
            $name      = trim("{$firstName} {$lastName}") ?: null;

            // Detectar se é comando (/start, /help, etc.)
            $type = str_starts_with($text, '/') ? 'command' : 'text';

            return new self(
                updateId: $updateId,
                messageId: (string) ($msg['message_id'] ?? ''),
                chatId: $chatId,
                type: $type,
                body: $text,
                callbackQueryId: null,
                displayName: $name,
                username: $from['username'] ?? null,
                rawPayload: $update,
            );
        }

        return null;
    }

    /**
     * Verifica se é uma resposta de confirmação (callback_query com sim/não).
     */
    public function isConfirmationReply(): bool
    {
        if ($this->type === 'callback_query') {
            return in_array($this->body, ['confirm_yes', 'confirm_no'], true);
        }

        // Texto livre de confirmação
        $normalized = mb_strtolower(trim($this->body));

        return in_array($normalized, [
            'sim',
            'confirmar',
            'yes',
            'confirm_yes',
            'não',
            'nao',
            'cancelar',
            'cancel',
            'confirm_no',
        ], true);
    }

    /**
     * Retorna true se a resposta é afirmativa.
     */
    public function isAffirmative(): bool
    {
        if ($this->type === 'callback_query') {
            return $this->body === 'confirm_yes';
        }

        $normalized = mb_strtolower(trim($this->body));

        return in_array($normalized, [
            'sim',
            'confirmar',
            'yes',
            'confirm_yes',
        ], true);
    }

    /**
     * Verifica se é um comando do bot (/start, /help, etc).
     */
    public function isCommand(): bool
    {
        return $this->type === 'command';
    }

    /**
     * Extrai o comando sem a barra (ex: "/start abc" → "start").
     */
    public function getCommand(): ?string
    {
        if (!$this->isCommand()) {
            return null;
        }

        $parts = explode(' ', $this->body, 2);
        $cmd   = ltrim($parts[0], '/');

        // Remove @botname se presente (ex: /start@LukratoBot)
        $cmd = explode('@', $cmd, 2)[0];

        return mb_strtolower($cmd);
    }

    /**
     * Extrai o argumento do comando (ex: "/start abc" → "abc").
     */
    public function getCommandArg(): ?string
    {
        if (!$this->isCommand()) {
            return null;
        }

        $parts = explode(' ', $this->body, 2);

        return isset($parts[1]) ? trim($parts[1]) : null;
    }
}
