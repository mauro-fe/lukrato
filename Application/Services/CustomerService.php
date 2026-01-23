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

    public function ensureAsaasCustomer(Usuario $usuario, AsaasService $asaas, array $holderInfo = []): void
    {
        $customerData = $this->buildCustomerData($usuario, $holderInfo);
        
        // Fallback: usar CPF do formulário se não tiver no banco
        $cpf = $customerData->cpf ?: ($holderInfo['cpfCnpj'] ?? null);
        $phone = $customerData->mobilePhone ?: ($holderInfo['mobilePhone'] ?? null);
        $cep = $customerData->postalCode ?: ($holderInfo['postalCode'] ?? null);

        // Se já tem cliente no Asaas, verifica se precisa atualizar o CPF
        if (!empty($usuario->external_customer_id) && $usuario->gateway === 'asaas') {
            // Se temos CPF, garante que está atualizado no Asaas
            if ($cpf) {
                try {
                    $existingCustomer = $asaas->getCustomer($usuario->external_customer_id);
                    
                    // Se o cliente no Asaas não tem CPF, atualiza
                    if (empty($existingCustomer['cpfCnpj'])) {
                        $updatePayload = ['cpfCnpj' => $cpf];
                        
                        if ($phone) {
                            $updatePayload['mobilePhone'] = $phone;
                        }
                        if ($cep) {
                            $updatePayload['postalCode'] = $cep;
                            $updatePayload['addressNumber'] = $customerData->addressNumber ?? 'S/N';
                        }
                        
                        $asaas->updateCustomer($usuario->external_customer_id, $updatePayload);
                    }
                } catch (\Throwable $e) {
                    // Log mas não falha - cliente já existe
                }
            }
            return;
        }

        $payload = [
            'name' => $usuario->nome,
            'email' => $usuario->email,
            'externalReference' => 'user:' . $usuario->id,
        ];

        if ($cpf) $payload['cpfCnpj'] = $cpf;
        if ($phone) $payload['mobilePhone'] = $phone;

        if ($cep) {
            $payload['postalCode'] = $cep;
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
