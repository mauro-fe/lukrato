<?php

declare(strict_types=1);

namespace Application\DTO\AI;

/**
 * DTO para mensagens recebidas via WhatsApp (Meta Cloud API).
 * Normaliza o payload bruto do webhook em uma estrutura previsível.
 */
readonly class WhatsAppMessageDTO
{
    public function __construct(
        public string  $waMessageId,
        public string  $fromPhone,
        public string  $type,
        public string  $body,
        public ?string $displayName = null,
        public array   $rawPayload = [],
    ) {}

    /**
     * Cria DTO a partir do payload de texto da Meta Cloud API.
     *
     * @param array $entry  Entry do webhook (changes[0].value)
     * @return self|null    null se o payload não for uma mensagem de texto processável
     */
    public static function fromMetaPayload(array $entry): ?self
    {
        $messages = $entry['messages'] ?? [];

        if (empty($messages)) {
            return null;
        }

        $msg = $messages[0];

        $type = $msg['type'] ?? 'unknown';

        // Extrair body dependendo do tipo
        $body = match ($type) {
            'text'        => $msg['text']['body'] ?? '',
            'interactive' => self::extractInteractiveBody($msg),
            default       => '',
        };

        if ($body === '') {
            return null;
        }

        // Extrair phone e display name dos contacts
        $contacts  = $entry['contacts'] ?? [];
        $fromPhone = $msg['from'] ?? '';
        $name      = $contacts[0]['profile']['name'] ?? null;

        return new self(
            waMessageId: $msg['id'] ?? '',
            fromPhone: $fromPhone,
            type: $type,
            body: $body,
            displayName: $name,
            rawPayload: $entry,
        );
    }

    /**
     * Extrai o body de uma mensagem interativa (button reply ou list reply).
     */
    private static function extractInteractiveBody(array $msg): string
    {
        $interactive = $msg['interactive'] ?? [];
        $interType   = $interactive['type'] ?? '';

        return match ($interType) {
            'button_reply' => $interactive['button_reply']['id'] ?? $interactive['button_reply']['title'] ?? '',
            'list_reply'   => $interactive['list_reply']['id'] ?? $interactive['list_reply']['title'] ?? '',
            default        => '',
        };
    }

    /**
     * Verifica se é uma resposta de confirmação (sim/não/cancelar).
     */
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

    /**
     * Retorna true se a resposta é afirmativa.
     */
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
}
