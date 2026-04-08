<?php

declare(strict_types=1);

namespace Application\Services\Billing;

use Application\Container\ApplicationContainer;
use Application\DTO\CustomerDataDTO;
use Application\Models\Usuario;
use Application\Repositories\DocumentoRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service para gerenciar dados de clientes
 */
class CustomerService
{
    private DocumentoRepository $documentoRepo;

    public function __construct(
        ?DocumentoRepository $documentoRepo = null
    ) {
        $this->documentoRepo = ApplicationContainer::resolveOrNew($documentoRepo, DocumentoRepository::class);
    }

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
        $cpf = $this->getDocumentoRepo()->getCpf($userId);

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

        // Se já tem cliente no Asaas, verifica se ainda é válido e atualiza se necessário
        if (!empty($usuario->external_customer_id) && $usuario->gateway === 'asaas') {
            try {
                $existingCustomer = $asaas->getCustomer($usuario->external_customer_id);

                // Cliente existe no Asaas — atualizar CPF se necessário
                if ($cpf && empty($existingCustomer['cpfCnpj'])) {
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
                return;
            } catch (\Throwable $e) {
                // Customer inválido no Asaas — limpar e recriar abaixo
                \Application\Services\Infrastructure\LogService::safeErrorLog("⚠️ [CUSTOMER] Customer {$usuario->external_customer_id} inválido no Asaas para user {$usuario->id}. Recriando...");
                $usuario->external_customer_id = null;
                $usuario->gateway = null;
                $usuario->save();
            }
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

    private function getDocumentoRepo(): DocumentoRepository
    {
        return $this->documentoRepo;
    }
}
