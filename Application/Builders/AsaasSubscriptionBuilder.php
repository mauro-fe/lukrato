<?php

namespace Application\Builders;

use Application\DTO\CustomerDataDTO;
use Application\Enums\SubscriptionCycle;

/**
 * Builder para criar payloads de assinatura Asaas
 */
class AsaasSubscriptionBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function forCustomer(string $customerId): self
    {
        $this->data['customerId'] = $customerId;
        return $this;
    }

    public function withValue(float $value): self
    {
        $this->data['value'] = $value;
        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->data['description'] = $description;
        return $this;
    }

    public function withBillingType(string $billingType): self
    {
        $this->data['billingType'] = $billingType;
        return $this;
    }

    public function withCycle(SubscriptionCycle $cycle): self
    {
        $this->data['cycle'] = $cycle->value;
        return $this;
    }

    public function withNextDueDate(string $date): self
    {
        $this->data['nextDueDate'] = $date;
        return $this;
    }

    public function withExternalReference(string $ref): self
    {
        $this->data['externalReference'] = $ref;
        return $this;
    }

    /**
     * Adiciona desconto na primeira cobrança
     * @param float $value Valor ou percentual do desconto
     * @param string $type 'FIXED' ou 'PERCENTAGE' 
     * @param int $dueDateLimitDays Dias de antecedência para aplicar o desconto (0 = sempre aplicar)
     */
    public function withDiscount(float $value, string $type = 'FIXED', int $dueDateLimitDays = 0): self
    {
        $this->data['discount'] = [
            'value' => $value,
            'dueDateLimitDays' => $dueDateLimitDays,
            'type' => $type
        ];
        return $this;
    }

    /**
     * @param array<string, mixed> $creditCard
     */
    public function withCreditCard(array $creditCard, CustomerDataDTO $holderInfo): self
    {
        $this->data['creditCard'] = $creditCard;
        $this->data['creditCardHolderInfo'] = $holderInfo->toArray();
        $this->data['remoteIp'] = $_SERVER['REMOTE_ADDR'] ?? null;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return $this->data;
    }
}
