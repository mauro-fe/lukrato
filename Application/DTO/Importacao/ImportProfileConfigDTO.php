<?php

declare(strict_types=1);

namespace Application\DTO\Importacao;

readonly class ImportProfileConfigDTO
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public int $contaId,
        public string $sourceType = 'ofx',
        public ?string $label = null,
        public ?string $bankName = null,
        public ?string $agencia = null,
        public ?string $numeroConta = null,
        public array $options = [],
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $contaId = (int) ($payload['conta_id'] ?? 0);
        if ($contaId <= 0) {
            throw new \InvalidArgumentException('conta_id é obrigatório para configurar importação.');
        }

        $sourceType = strtolower(trim((string) ($payload['source_type'] ?? 'ofx')));
        if ($sourceType === '') {
            $sourceType = 'ofx';
        }

        return new self(
            contaId: $contaId,
            sourceType: $sourceType,
            label: self::optionalString($payload['label'] ?? null),
            bankName: self::optionalString($payload['bank_name'] ?? null),
            agencia: self::optionalString($payload['agencia'] ?? null),
            numeroConta: self::optionalString($payload['numero_conta'] ?? null),
            options: is_array($payload['options'] ?? null) ? $payload['options'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'conta_id' => $this->contaId,
            'source_type' => $this->sourceType,
            'label' => $this->label,
            'bank_name' => $this->bankName,
            'agencia' => $this->agencia,
            'numero_conta' => $this->numeroConta,
            'options' => $this->options,
        ];
    }

    private static function optionalString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
