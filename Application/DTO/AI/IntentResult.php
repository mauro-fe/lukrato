<?php

declare(strict_types=1);

namespace Application\DTO\AI;

use Application\Enums\AI\IntentType;

/**
 * Resultado da detecção de intent com score de confiança.
 *
 * Se confidence < threshold → IA deve pedir clarificação ao usuário.
 */
readonly class IntentResult
{
    public const CONFIDENCE_THRESHOLD = 0.6;

    public function __construct(
        public IntentType $intent,
        public float      $confidence,
        public array      $metadata = [],
    ) {}

    public static function high(IntentType $intent, array $metadata = []): self
    {
        return new self($intent, 1.0, $metadata);
    }

    public static function medium(IntentType $intent, float $confidence, array $metadata = []): self
    {
        return new self($intent, $confidence, $metadata);
    }

    public static function low(IntentType $intent, float $confidence, array $metadata = []): self
    {
        return new self($intent, $confidence, $metadata);
    }

    public function isConfident(): bool
    {
        return $this->confidence >= self::CONFIDENCE_THRESHOLD;
    }
}
