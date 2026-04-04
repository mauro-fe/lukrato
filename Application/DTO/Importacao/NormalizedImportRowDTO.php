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
        public ?string $rowKey = null,
        public ?int $categoriaId = null,
        public ?int $subcategoriaId = null,
        public ?string $categoriaNome = null,
        public ?string $subcategoriaNome = null,
        public ?int $categoriaSugeridaId = null,
        public ?int $subcategoriaSugeridaId = null,
        public ?string $categoriaSugeridaNome = null,
        public ?string $subcategoriaSugeridaNome = null,
        public ?string $categoriaSource = null,
        public ?string $categoriaConfidence = null,
        public bool $categoriaEditada = false,
        public ?string $categoriaLearningSource = null,
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
        $rowKey = ImportSanitizer::sanitizeText((string) ($payload['row_key'] ?? ''), 80);
        $raw = is_array($payload['raw'] ?? null)
            ? ImportSanitizer::sanitizeMixed($payload['raw'])
            : [];
        $categoriaId = self::normalizePositiveInt($payload['categoria_id'] ?? null);
        $subcategoriaId = self::normalizePositiveInt($payload['subcategoria_id'] ?? null);
        $categoriaNome = ImportSanitizer::sanitizeText((string) ($payload['categoria_nome'] ?? ''), 100);
        $subcategoriaNome = ImportSanitizer::sanitizeText((string) ($payload['subcategoria_nome'] ?? ''), 100);
        $categoriaSugeridaId = self::normalizePositiveInt($payload['categoria_sugerida_id'] ?? null);
        $subcategoriaSugeridaId = self::normalizePositiveInt($payload['subcategoria_sugerida_id'] ?? null);
        $categoriaSugeridaNome = ImportSanitizer::sanitizeText((string) ($payload['categoria_sugerida_nome'] ?? ''), 100);
        $subcategoriaSugeridaNome = ImportSanitizer::sanitizeText((string) ($payload['subcategoria_sugerida_nome'] ?? ''), 100);
        $categoriaSource = ImportSanitizer::sanitizeText((string) ($payload['categoria_source'] ?? ''), 40);
        $categoriaConfidence = ImportSanitizer::sanitizeText((string) ($payload['categoria_confidence'] ?? ''), 40);
        $categoriaEditada = filter_var($payload['categoria_editada'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $categoriaLearningSource = ImportSanitizer::sanitizeText((string) ($payload['categoria_learning_source'] ?? ''), 20);

        return new self(
            $date,
            $amount,
            $type,
            $description,
            $memo !== '' ? $memo : null,
            $externalId !== '' ? $externalId : null,
            $accountHint !== '' ? $accountHint : null,
            is_array($raw) ? $raw : [],
            $rowKey !== '' ? $rowKey : null,
            $categoriaId,
            $subcategoriaId,
            $categoriaNome !== '' ? $categoriaNome : null,
            $subcategoriaNome !== '' ? $subcategoriaNome : null,
            $categoriaSugeridaId,
            $subcategoriaSugeridaId,
            $categoriaSugeridaNome !== '' ? $categoriaSugeridaNome : null,
            $subcategoriaSugeridaNome !== '' ? $subcategoriaSugeridaNome : null,
            $categoriaSource !== '' ? $categoriaSource : null,
            $categoriaConfidence !== '' ? $categoriaConfidence : null,
            $categoriaEditada,
            $categoriaLearningSource !== '' ? $categoriaLearningSource : null,
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
            'row_key' => $this->rowKey,
            'categoria_id' => $this->categoriaId,
            'subcategoria_id' => $this->subcategoriaId,
            'categoria_nome' => $this->categoriaNome,
            'subcategoria_nome' => $this->subcategoriaNome,
            'categoria_sugerida_id' => $this->categoriaSugeridaId,
            'subcategoria_sugerida_id' => $this->subcategoriaSugeridaId,
            'categoria_sugerida_nome' => $this->categoriaSugeridaNome,
            'subcategoria_sugerida_nome' => $this->subcategoriaSugeridaNome,
            'categoria_source' => $this->categoriaSource,
            'categoria_confidence' => $this->categoriaConfidence,
            'categoria_editada' => $this->categoriaEditada,
            'categoria_learning_source' => $this->categoriaLearningSource,
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

    private static function normalizePositiveInt(mixed $value): ?int
    {
        if (!is_scalar($value) || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
