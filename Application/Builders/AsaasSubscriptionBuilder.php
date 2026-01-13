<?php

namespace Application\Builders;

use Application\DTO\CustomerDataDTO;
use Application\Enums\SubscriptionCycle;

/**
 * Builder para criar payloads de assinatura Asaas
 */
class AsaasSubscriptionBuilder
{
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

    public function withCreditCard(array $creditCard, CustomerDataDTO $holderInfo): self
    {
        $this->data['creditCard'] = $creditCard;
        $this->data['creditCardHolderInfo'] = $holderInfo->toArray();
        $this->data['remoteIp'] = $_SERVER['REMOTE_ADDR'] ?? null;
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }
}
