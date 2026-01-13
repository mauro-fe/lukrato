<?php

namespace Application\DTO;

/**
 * DTO para requisição de checkout
 */
class CheckoutRequestDTO
{
    public function __construct(
        public readonly string $billingType,
        public readonly ?array $creditCard,
        public readonly array $holderInfo,
        public readonly int $months,
        public readonly int $discount
    ) {}

    public static function fromRequest(array $body): self
    {
        return new self(
            billingType: $body['billingType'] ?? 'CREDIT_CARD',
            creditCard: $body['creditCard'] ?? null,
            holderInfo: $body['creditCardHolderInfo'] ?? [],
            months: (int)($body['months'] ?? 1),
            discount: (int)($body['discount'] ?? 0)
        );
    }

    public function isCreditCard(): bool
    {
        return $this->billingType === 'CREDIT_CARD';
    }

    public function hasCreditCard(): bool
    {
        return $this->isCreditCard() && !empty($this->creditCard);
    }

    public function isSemestral(): bool
    {
        return $this->months === 6;
    }

    public function isAnual(): bool
    {
        return $this->months === 12;
    }

    public function isMensal(): bool
    {
        return $this->months === 1;
    }
}
