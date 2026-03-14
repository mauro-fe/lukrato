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
        public ?string $fileId = null,
        public ?string $mimeType = null,
        public ?int    $fileSize = null,
        public ?string $caption = null,
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

        // message (texto, comando, voice, audio, photo, document)
        if (isset($update['message'])) {
            $msg    = $update['message'];
            $from   = $msg['from'] ?? [];
            $chatId = (string) ($msg['chat']['id'] ?? '');

            if ($chatId === '') {
                return null;
            }

            $firstName = $from['first_name'] ?? '';
            $lastName  = $from['last_name'] ?? '';
            $name      = trim("{$firstName} {$lastName}") ?: null;

            // Voice message (áudio de voz gravado no app)
            if (isset($msg['voice'])) {
                $voice = $msg['voice'];
                return new self(
                    updateId: $updateId,
                    messageId: (string) ($msg['message_id'] ?? ''),
                    chatId: $chatId,
                    type: 'voice',
                    body: '',
                    displayName: $name,
                    username: $from['username'] ?? null,
                    rawPayload: $update,
                    fileId: $voice['file_id'],
                    mimeType: $voice['mime_type'] ?? 'audio/ogg',
                    fileSize: $voice['file_size'] ?? null,
                );
            }

            // Audio message (arquivo de áudio/música)
            if (isset($msg['audio'])) {
                $audio = $msg['audio'];
                return new self(
                    updateId: $updateId,
                    messageId: (string) ($msg['message_id'] ?? ''),
                    chatId: $chatId,
                    type: 'audio',
                    body: '',
                    displayName: $name,
                    username: $from['username'] ?? null,
                    rawPayload: $update,
                    fileId: $audio['file_id'],
                    mimeType: $audio['mime_type'] ?? 'audio/mpeg',
                    fileSize: $audio['file_size'] ?? null,
                );
            }

            // Photo message (foto enviada como imagem)
            if (isset($msg['photo']) && is_array($msg['photo']) && count($msg['photo']) > 0) {
                $photo = end($msg['photo']); // Maior resolução
                return new self(
                    updateId: $updateId,
                    messageId: (string) ($msg['message_id'] ?? ''),
                    chatId: $chatId,
                    type: 'photo',
                    body: $msg['caption'] ?? '',
                    displayName: $name,
                    username: $from['username'] ?? null,
                    rawPayload: $update,
                    fileId: $photo['file_id'],
                    fileSize: $photo['file_size'] ?? null,
                    caption: $msg['caption'] ?? null,
                );
            }

            // Document (imagens ou áudios enviados como arquivo)
            if (isset($msg['document'])) {
                $doc = $msg['document'];
                $docMime = $doc['mime_type'] ?? '';

                if (str_starts_with($docMime, 'image/')) {
                    return new self(
                        updateId: $updateId,
                        messageId: (string) ($msg['message_id'] ?? ''),
                        chatId: $chatId,
                        type: 'photo',
                        body: $msg['caption'] ?? '',
                        displayName: $name,
                        username: $from['username'] ?? null,
                        rawPayload: $update,
                        fileId: $doc['file_id'],
                        mimeType: $docMime,
                        fileSize: $doc['file_size'] ?? null,
                        caption: $msg['caption'] ?? null,
                    );
                }

                if (str_starts_with($docMime, 'audio/')) {
                    return new self(
                        updateId: $updateId,
                        messageId: (string) ($msg['message_id'] ?? ''),
                        chatId: $chatId,
                        type: 'audio',
                        body: '',
                        displayName: $name,
                        username: $from['username'] ?? null,
                        rawPayload: $update,
                        fileId: $doc['file_id'],
                        mimeType: $docMime,
                        fileSize: $doc['file_size'] ?? null,
                    );
                }
            }

            // Text / Command (fallback original)
            $text = $msg['text'] ?? '';

            if ($text === '') {
                return null;
            }

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
     * Verifica se é callback de seleção de conta (select_conta_X).
     */
    public function isAccountSelection(): bool
    {
        return $this->type === 'callback_query'
            && str_starts_with($this->body, 'select_conta_');
    }

    /**
     * Extrai o ID da conta selecionada via callback.
     */
    public function getSelectedAccountId(): ?int
    {
        if (!$this->isAccountSelection()) {
            return null;
        }
        $id = substr($this->body, strlen('select_conta_'));
        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * Verifica se é callback de seleção de opção genérica (select_option_X).
     */
    public function isOptionSelection(): bool
    {
        return $this->type === 'callback_query'
            && str_starts_with($this->body, 'select_option_');
    }

    /**
     * Extrai o índice da opção selecionada via callback.
     */
    public function getSelectedOptionIndex(): ?int
    {
        if (!$this->isOptionSelection()) {
            return null;
        }
        $idx = substr($this->body, strlen('select_option_'));
        return is_numeric($idx) ? (int) $idx : null;
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
     * Verifica se é mensagem de voz ou áudio.
     */
    public function isVoice(): bool
    {
        return $this->type === 'voice' || $this->type === 'audio';
    }

    /**
     * Verifica se é uma foto/imagem.
     */
    public function isPhoto(): bool
    {
        return $this->type === 'photo';
    }

    /**
     * Verifica se é qualquer tipo de media (voz, áudio, foto).
     */
    public function isMedia(): bool
    {
        return $this->isVoice() || $this->isPhoto();
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
