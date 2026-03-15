<?php

declare(strict_types=1);

namespace Application\DTO\AI;

/**
 * DTO para mensagens recebidas via WhatsApp Cloud API.
 */
readonly class WhatsAppMessageDTO
{
    public function __construct(
        public string $waMessageId,
        public string $fromPhone,
        public string $type,
        public string $body,
        public ?string $displayName = null,
        public array $rawPayload = [],
        public ?string $mediaId = null,
        public ?string $mimeType = null,
        public ?int $fileSize = null,
        public ?string $filename = null,
        public ?string $caption = null,
    ) {}

    public static function fromMetaPayload(array $entry): ?self
    {
        $messages = $entry['messages'] ?? [];
        if (empty($messages)) {
            return null;
        }

        $msg = $messages[0];
        $type = $msg['type'] ?? 'unknown';
        $fromPhone = (string) ($msg['from'] ?? '');
        if ($fromPhone === '') {
            return null;
        }

        $body = match ($type) {
            'text'        => (string) ($msg['text']['body'] ?? ''),
            'interactive' => self::extractInteractiveBody($msg),
            'image'       => (string) ($msg['image']['caption'] ?? ''),
            'video'       => (string) ($msg['video']['caption'] ?? ''),
            'document'    => (string) ($msg['document']['caption'] ?? ''),
            default       => '',
        };

        $contacts = $entry['contacts'] ?? [];
        $name = $contacts[0]['profile']['name'] ?? null;

        if (in_array($type, ['audio', 'image', 'document', 'video'], true)) {
            $media = $msg[$type] ?? [];

            return new self(
                waMessageId: (string) ($msg['id'] ?? ''),
                fromPhone: $fromPhone,
                type: $type,
                body: $body,
                displayName: $name,
                rawPayload: $entry,
                mediaId: $media['id'] ?? null,
                mimeType: $media['mime_type'] ?? null,
                fileSize: isset($media['file_size']) ? (int) $media['file_size'] : null,
                filename: $media['filename'] ?? null,
                caption: $body !== '' ? $body : null,
            );
        }

        if ($body === '') {
            return null;
        }

        return new self(
            waMessageId: (string) ($msg['id'] ?? ''),
            fromPhone: $fromPhone,
            type: $type,
            body: $body,
            displayName: $name,
            rawPayload: $entry,
        );
    }

    private static function extractInteractiveBody(array $msg): string
    {
        $interactive = $msg['interactive'] ?? [];
        $interType = $interactive['type'] ?? '';

        return match ($interType) {
            'button_reply' => $interactive['button_reply']['id'] ?? $interactive['button_reply']['title'] ?? '',
            'list_reply'   => $interactive['list_reply']['id'] ?? $interactive['list_reply']['title'] ?? '',
            default        => '',
        };
    }

    public function isConfirmationReply(): bool
    {
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

    public function isAffirmative(): bool
    {
        $normalized = mb_strtolower(trim($this->body));

        return in_array($normalized, [
            'sim',
            'confirmar',
            'yes',
            'confirm_yes',
        ], true);
    }

    public function isMedia(): bool
    {
        return in_array($this->type, ['audio', 'image', 'document', 'video'], true);
    }

    public function isAudio(): bool
    {
        return $this->type === 'audio';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }
}
