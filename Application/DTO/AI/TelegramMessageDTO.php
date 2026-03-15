<?php

declare(strict_types=1);

namespace Application\DTO\AI;

use Application\Services\AI\IntentRules\ConfirmationIntentRule;

/**
 * DTO para updates do Telegram.
 * Normaliza texto, callbacks e anexos em uma estrutura previsivel.
 */
readonly class TelegramMessageDTO
{
    public function __construct(
        public string $updateId,
        public string $messageId,
        public string $chatId,
        public string $type,
        public string $body,
        public ?string $callbackQueryId = null,
        public ?string $displayName = null,
        public ?string $username = null,
        public array $rawPayload = [],
        public ?string $fileId = null,
        public ?string $mimeType = null,
        public ?int $fileSize = null,
        public ?string $caption = null,
        public ?string $filename = null,
    ) {}

    public static function fromTelegramUpdate(array $update): ?self
    {
        $updateId = (string) ($update['update_id'] ?? '');
        if ($updateId === '') {
            return null;
        }

        if (isset($update['callback_query'])) {
            $cb = $update['callback_query'];
            $msg = $cb['message'] ?? [];
            $from = $cb['from'] ?? [];
            $chatId = (string) ($msg['chat']['id'] ?? $from['id'] ?? '');
            $name = self::buildDisplayName($from);

            return new self(
                updateId: $updateId,
                messageId: (string) ($msg['message_id'] ?? ''),
                chatId: $chatId,
                type: 'callback_query',
                body: (string) ($cb['data'] ?? ''),
                callbackQueryId: $cb['id'] ?? null,
                displayName: $name,
                username: $from['username'] ?? null,
                rawPayload: $update,
            );
        }

        if (!isset($update['message']) || !is_array($update['message'])) {
            return null;
        }

        $msg = $update['message'];
        $from = $msg['from'] ?? [];
        $chatId = (string) ($msg['chat']['id'] ?? '');
        if ($chatId === '') {
            return null;
        }

        $name = self::buildDisplayName($from);
        $caption = $msg['caption'] ?? null;

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
                fileId: $voice['file_id'] ?? null,
                mimeType: $voice['mime_type'] ?? 'audio/ogg',
                fileSize: isset($voice['file_size']) ? (int) $voice['file_size'] : null,
            );
        }

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
                fileId: $audio['file_id'] ?? null,
                mimeType: $audio['mime_type'] ?? 'audio/mpeg',
                fileSize: isset($audio['file_size']) ? (int) $audio['file_size'] : null,
                filename: $audio['file_name'] ?? null,
            );
        }

        if (isset($msg['photo']) && is_array($msg['photo']) && count($msg['photo']) > 0) {
            $photo = end($msg['photo']);

            return new self(
                updateId: $updateId,
                messageId: (string) ($msg['message_id'] ?? ''),
                chatId: $chatId,
                type: 'photo',
                body: (string) ($caption ?? ''),
                displayName: $name,
                username: $from['username'] ?? null,
                rawPayload: $update,
                fileId: $photo['file_id'] ?? null,
                fileSize: isset($photo['file_size']) ? (int) $photo['file_size'] : null,
                caption: $caption,
            );
        }

        if (isset($msg['video'])) {
            $video = $msg['video'];

            return new self(
                updateId: $updateId,
                messageId: (string) ($msg['message_id'] ?? ''),
                chatId: $chatId,
                type: 'video',
                body: (string) ($caption ?? ''),
                displayName: $name,
                username: $from['username'] ?? null,
                rawPayload: $update,
                fileId: $video['file_id'] ?? null,
                mimeType: $video['mime_type'] ?? 'video/mp4',
                fileSize: isset($video['file_size']) ? (int) $video['file_size'] : null,
                caption: $caption,
                filename: $video['file_name'] ?? null,
            );
        }

        if (isset($msg['video_note'])) {
            $videoNote = $msg['video_note'];

            return new self(
                updateId: $updateId,
                messageId: (string) ($msg['message_id'] ?? ''),
                chatId: $chatId,
                type: 'video',
                body: '',
                displayName: $name,
                username: $from['username'] ?? null,
                rawPayload: $update,
                fileId: $videoNote['file_id'] ?? null,
                mimeType: 'video/mp4',
                fileSize: isset($videoNote['file_size']) ? (int) $videoNote['file_size'] : null,
                filename: 'video-note.mp4',
            );
        }

        if (isset($msg['document'])) {
            $doc = $msg['document'];
            $docMime = strtolower((string) ($doc['mime_type'] ?? 'application/octet-stream'));
            $type = str_starts_with($docMime, 'image/') ? 'photo' : (
                str_starts_with($docMime, 'audio/') ? 'audio' : (
                    str_starts_with($docMime, 'video/') ? 'video' : 'document'
                )
            );

            return new self(
                updateId: $updateId,
                messageId: (string) ($msg['message_id'] ?? ''),
                chatId: $chatId,
                type: $type,
                body: $type === 'audio' ? '' : (string) ($caption ?? ''),
                displayName: $name,
                username: $from['username'] ?? null,
                rawPayload: $update,
                fileId: $doc['file_id'] ?? null,
                mimeType: $docMime !== '' ? $docMime : null,
                fileSize: isset($doc['file_size']) ? (int) $doc['file_size'] : null,
                caption: $caption,
                filename: $doc['file_name'] ?? null,
            );
        }

        $text = (string) ($msg['text'] ?? '');
        if ($text === '') {
            return null;
        }

        $type = str_starts_with($text, '/') ? 'command' : 'text';

        return new self(
            updateId: $updateId,
            messageId: (string) ($msg['message_id'] ?? ''),
            chatId: $chatId,
            type: $type,
            body: $text,
            displayName: $name,
            username: $from['username'] ?? null,
            rawPayload: $update,
        );
    }

    public function isConfirmationReply(): bool
    {
        if ($this->isConfirmationCallback()) {
            return true;
        }

        return ConfirmationIntentRule::isAffirmative($this->body)
            || ConfirmationIntentRule::isNegative($this->body);
    }

    public function isConfirmationCallback(): bool
    {
        return $this->type === 'callback_query'
            && in_array($this->body, ['confirm_yes', 'confirm_no'], true);
    }

    public function isAccountSelection(): bool
    {
        return $this->type === 'callback_query'
            && str_starts_with($this->body, 'select_conta_');
    }

    public function getSelectedAccountId(): ?int
    {
        if (!$this->isAccountSelection()) {
            return null;
        }

        $id = substr($this->body, strlen('select_conta_'));
        return is_numeric($id) ? (int) $id : null;
    }

    public function isOptionSelection(): bool
    {
        return $this->type === 'callback_query'
            && str_starts_with($this->body, 'select_option_');
    }

    public function getSelectedOptionIndex(): ?int
    {
        if (!$this->isOptionSelection()) {
            return null;
        }

        $idx = substr($this->body, strlen('select_option_'));
        return is_numeric($idx) ? (int) $idx : null;
    }

    public function isAffirmative(): bool
    {
        if ($this->isConfirmationCallback()) {
            return $this->body === 'confirm_yes';
        }

        return ConfirmationIntentRule::isAffirmative($this->body);
    }

    public function isCommand(): bool
    {
        return $this->type === 'command';
    }

    public function isVoice(): bool
    {
        return in_array($this->type, ['voice', 'audio'], true);
    }

    public function isPhoto(): bool
    {
        return $this->type === 'photo';
    }

    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isMedia(): bool
    {
        return $this->isVoice() || $this->isPhoto() || $this->isDocument() || $this->isVideo();
    }

    public function getCommand(): ?string
    {
        if (!$this->isCommand()) {
            return null;
        }

        $parts = explode(' ', $this->body, 2);
        $cmd = ltrim($parts[0], '/');
        $cmd = explode('@', $cmd, 2)[0];

        return mb_strtolower($cmd);
    }

    public function getCommandArg(): ?string
    {
        if (!$this->isCommand()) {
            return null;
        }

        $parts = explode(' ', $this->body, 2);
        return isset($parts[1]) ? trim($parts[1]) : null;
    }

    private static function buildDisplayName(array $from): ?string
    {
        $firstName = (string) ($from['first_name'] ?? '');
        $lastName = (string) ($from['last_name'] ?? '');

        $name = trim("{$firstName} {$lastName}");
        return $name !== '' ? $name : null;
    }
}
