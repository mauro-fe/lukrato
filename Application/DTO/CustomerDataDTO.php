<?php

namespace Application\DTO;

use Application\Models\Usuario;

/**
 * DTO para dados do cliente (CPF, telefone, endereço)
 */
class CustomerDataDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $cpf,
        public readonly ?string $mobilePhone,
        public readonly ?string $postalCode,
        public readonly ?string $addressNumber,
        public readonly ?string $addressComplement
    ) {}

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->cpf) $data['cpfCnpj'] = $this->cpf;
        if ($this->mobilePhone) $data['mobilePhone'] = $this->mobilePhone;

        if ($this->postalCode) {
            $data['postalCode'] = $this->postalCode;
            $data['addressNumber'] = $this->addressNumber ?? 'S/N';
            if ($this->addressComplement) {
                $data['addressComplement'] = $this->addressComplement;
            }
        }

        return $data;
    }
}
