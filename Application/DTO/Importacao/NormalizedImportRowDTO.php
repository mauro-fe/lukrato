<?php

declare(strict_types=1);

namespace Application\DTO\Importacao;

use Application\Services\Importacao\ImportSanitizer;

readonly class NormalizedImportRowDTO
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $date,
        public float $amount,
        public string $type,
        public string $description,
        public ?string $memo = null,
        public ?string $externalId = null,
        public ?string $accountHint = null,
        public array $raw = [],
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $date = trim((string) ($payload['date'] ?? ''));
        if ($date === '') {
            throw new \InvalidArgumentException('A linha normalizada exige o campo date.');
        }

        $amount = abs((float) ($payload['amount'] ?? 0));
        if ($amount <= 0) {
            throw new \InvalidArgumentException('A linha normalizada exige um amount maior que zero.');
        }

        $description = ImportSanitizer::sanitizeText((string) ($payload['description'] ?? ''), 190);
        if ($description === '') {
            throw new \InvalidArgumentException('A linha normalizada exige descrição.');
        }

        $type = self::normalizeType((string) ($payload['type'] ?? ''));

        $memo = ImportSanitizer::sanitizeText((string) ($payload['memo'] ?? ''), 500, true);
        $externalId = ImportSanitizer::sanitizeText((string) ($payload['external_id'] ?? ''), 120);
        $accountHint = ImportSanitizer::sanitizeText((string) ($payload['account_hint'] ?? ''), 190);
        $raw = is_array($payload['raw'] ?? null)
            ? ImportSanitizer::sanitizeMixed($payload['raw'])
            : [];

        return new self(
            $date,
            $amount,
            $type,
            $description,
            $memo !== '' ? $memo : null,
            $externalId !== '' ? $externalId : null,
            $accountHint !== '' ? $accountHint : null,
            is_array($raw) ? $raw : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'amount' => $this->amount,
            'type' => $this->type,
            'description' => $this->description,
            'memo' => $this->memo,
            'external_id' => $this->externalId,
            'account_hint' => $this->accountHint,
            'raw' => $this->raw,
        ];
    }

    private static function normalizeType(string $type): string
    {
        $normalized = strtolower(trim($type));

        return match ($normalized) {
            'income', 'receita', 'credit', 'credito', 'cr' => 'receita',
            default => 'despesa',
        };
    }
}
