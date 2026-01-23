<?php

namespace Application\DTO;

/**
 * DTO para requisição de checkout
 * Suporta múltiplos métodos de pagamento: Cartão, PIX e Boleto
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
        // Combina creditCardHolderInfo e holderInfo (para PIX/Boleto)
        $holderInfo = $body['creditCardHolderInfo'] ?? $body['holderInfo'] ?? [];

        return new self(
            billingType: $body['billingType'] ?? 'CREDIT_CARD',
            creditCard: $body['creditCard'] ?? null,
            holderInfo: $holderInfo,
            months: (int)($body['months'] ?? 1),
            discount: (int)($body['discount'] ?? 0)
        );
    }

    public function isCreditCard(): bool
    {
        return $this->billingType === 'CREDIT_CARD';
    }

    public function isPix(): bool
    {
        return $this->billingType === 'PIX';
    }

    public function isBoleto(): bool
    {
        return $this->billingType === 'BOLETO';
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

    /**
     * Retorna CPF/CNPJ do pagador
     */
    public function getCpfCnpj(): ?string
    {
        return $this->holderInfo['cpfCnpj'] ?? null;
    }

    /**
     * Retorna telefone do pagador
     */
    public function getMobilePhone(): ?string
    {
        return $this->holderInfo['mobilePhone'] ?? null;
    }

    /**
     * Retorna CEP do pagador
     */
    public function getPostalCode(): ?string
    {
        return $this->holderInfo['postalCode'] ?? null;
    }

    /**
     * Retorna endereço do pagador
     */
    public function getAddress(): ?string
    {
        return $this->holderInfo['address'] ?? null;
    }

    /**
     * Retorna email do pagador
     */
    public function getEmail(): ?string
    {
        return $this->holderInfo['email'] ?? null;
    }
}
