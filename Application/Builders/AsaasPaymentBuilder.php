<?php

namespace Application\Builders;

use Application\DTO\CheckoutRequestDTO;
use Application\DTO\CustomerDataDTO;
use Application\Models\Usuario;
use Application\Models\Plano;

/**
 * Builder para criar payloads de pagamento Asaas
 */
class AsaasPaymentBuilder
{
    private array $data = [];

    public function forCustomer(Usuario $usuario): self
    {
        $this->data['customer'] = $usuario->external_customer_id;
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

    public function withDueDate(string $date): self
    {
        $this->data['dueDate'] = $date;
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
