<?php

namespace Application\Services;

use Application\DTO\CustomerDataDTO;
use Application\Models\Usuario;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service para gerenciar dados de clientes
 */
class CustomerService
{
    public function buildCustomerData(Usuario $usuario, array $holderInfo = []): CustomerDataDTO
    {
        $cpf = $this->getCpf($usuario->id);
        $phone = $this->getPhone($usuario->id);
        $endereco = $usuario->enderecoPrincipal;

        return new CustomerDataDTO(
            name: $holderInfo['name'] ?? $usuario->nome,
            email: $holderInfo['email'] ?? $usuario->email,
            cpf: $cpf,
            mobilePhone: $phone,
            postalCode: $endereco?->cep ? preg_replace('/\D+/', '', $endereco->cep) : null,
            addressNumber: $endereco?->numero,
            addressComplement: $endereco?->complemento
        );
    }

    public function getCpf(int $userId): ?string
    {
        $cpf = DB::table('documentos')
            ->where('id_usuario', $userId)
            ->where('id_tipo', 1)
            ->value('numero');

        return $cpf ? preg_replace('/\D+/', '', $cpf) : null;
    }

    public function getPhone(int $userId): ?string
    {
        $row = DB::table('telefones as t')
            ->leftJoin('ddd as d', 'd.id_ddd', '=', 't.id_ddd')
            ->where('t.id_usuario', $userId)
            ->orderBy('t.id_telefone')
            ->first();

        if (!$row) return null;

        $ddd = preg_replace('/\D+/', '', (string)($row->codigo ?? ''));
        $num = preg_replace('/\D+/', '', (string)($row->numero ?? ''));
        $phone = $ddd . $num;

        return $phone !== '' ? $phone : null;
    }

    public function ensureAsaasCustomer(Usuario $usuario, AsaasService $asaas): void
    {
        if (!empty($usuario->external_customer_id) && $usuario->gateway === 'asaas') {
            return;
        }

        $customerData = $this->buildCustomerData($usuario);

        $payload = [
            'name' => $usuario->nome,
            'email' => $usuario->email,
            'externalReference' => 'user:' . $usuario->id,
        ];

        if ($customerData->cpf) $payload['cpfCnpj'] = $customerData->cpf;
        if ($customerData->mobilePhone) $payload['mobilePhone'] = $customerData->mobilePhone;

        if ($customerData->postalCode) {
            $payload['postalCode'] = $customerData->postalCode;
            $payload['addressNumber'] = $customerData->addressNumber ?? 'S/N';
            if ($customerData->addressComplement) {
                $payload['addressComplement'] = $customerData->addressComplement;
            }
        }

        $customer = $asaas->createCustomer($payload);

        $usuario->external_customer_id = $customer['id'] ?? null;
        $usuario->gateway = 'asaas';
        $usuario->save();

        if (empty($usuario->external_customer_id)) {
            throw new \RuntimeException('Falha ao criar cliente no Asaas.');
        }
    }
}
