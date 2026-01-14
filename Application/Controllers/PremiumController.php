<?php

namespace Application\Controllers;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\Plano;
use Application\Models\AssinaturaUsuario;
use Application\Services\AsaasService;
use Application\Services\LogService;
use Application\Services\CustomerService;
use Application\Validators\CheckoutValidator;
use Application\DTO\CheckoutRequestDTO;
use Application\Builders\AsaasPaymentBuilder;
use Application\Builders\AsaasSubscriptionBuilder;
use Application\Enums\SubscriptionCycle;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Controller Premium - Arquitetura Limpa
 * 
 * Usa padrões do projeto:
 * - DTOs para dados estruturados
 * - Builders para payloads complexos
 * - Validators para validações
 * - Enums para constantes
 * - Services para lógica de negócio
 */
class PremiumController extends BaseController
{
    private AsaasService $asaas;
    private CustomerService $customerService;
    private CheckoutValidator $validator;

    public function __construct()
    {
        parent::__construct();
        $this->asaas = new AsaasService();
        $this->customerService = new CustomerService();
        $this->validator = new CheckoutValidator();
    }

    /**
     * Checkout do plano PRO
     */
    public function checkout(): void
    {
        $this->requireAuth();

        try {
            $usuario = $this->getAuthenticatedUser();
            $this->validateNoActiveSubscription($usuario);

            $plano = $this->getPlanoPro();
            $dto = CheckoutRequestDTO::fromRequest($this->getRequestBody());

            $this->validator->validate($dto, $plano);

            $this->customerService->ensureAsaasCustomer($usuario, $this->asaas);
            $customerData = $this->customerService->buildCustomerData($usuario, $dto->holderInfo);

            $result = $this->processCheckout($usuario, $plano, $dto, $customerData);

            Response::success($result);
        } catch (\Throwable $e) {
            $this->handleCheckoutError($e);
        }
    }

    /**
     * Cancelar assinatura PRO
     */
    public function cancel(): void
    {
        $this->requireAuth();

        try {
            $usuario = $this->getAuthenticatedUser();

            DB::beginTransaction();

            try {
                $assinatura = $this->getActiveSubscription($usuario);

                if (!$assinatura) {
                    DB::rollBack();
                    Response::error('Nenhuma assinatura ativa encontrada.');
                    return;
                }

                if ($assinatura->external_subscription_id) {
                    $this->asaas->cancelSubscription($assinatura->external_subscription_id);
                }

                $assinatura->status = AssinaturaUsuario::ST_CANCELED;
                $assinatura->cancelada_em = now();
                $assinatura->save();

                $this->logCancellation($usuario, $assinatura);

                DB::commit();
                Response::success(['message' => 'Assinatura cancelada com sucesso.']);
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->handleCancelError($e);
        }
    }

    // ========================================================================
    // MÉTODOS PRIVADOS - Clean Code
    // ========================================================================

    private function getAuthenticatedUser(): Usuario
    {
        $userId = $this->userId;
            throw new \RuntimeException('Usuário não identificado na sessão.');
        }

        return Usuario::findOrFail($userId);
    }

    private function getRequestBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? $_POST;
    }

    private function validateNoActiveSubscription(Usuario $usuario): void
    {
        $exists = $usuario->assinaturas()
            ->where('gateway', 'asaas')
            ->whereIn('status', [
                AssinaturaUsuario::ST_ACTIVE,
                AssinaturaUsuario::ST_PENDING,
                AssinaturaUsuario::ST_PAST_DUE,
            ])
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw new \RuntimeException('Você já possui uma assinatura em andamento.');
        }
    }

    private function getPlanoPro(): Plano
    {
        $plano = Plano::where('code', 'pro')->where('ativo', 1)->first();

        if (!$plano) {
            throw new \RuntimeException('Plano PRO não está configurado.');
        }

        return $plano;
    }

    private function processCheckout(Usuario $usuario, Plano $plano, CheckoutRequestDTO $dto, $customerData): array
    {
        DB::beginTransaction();

        try {
            $valorMensal = $plano->preco_centavos / 100;
            $discount = $this->validator->getExpectedDiscount($dto->months);
            $total = $this->validator->calculateTotal($valorMensal, $dto->months, $discount);

            $result = $dto->isSemestral()
                ? $this->createPayment($usuario, $plano, $dto, $customerData, $total)
                : $this->createSubscription($usuario, $plano, $dto, $customerData, $valorMensal, $total);

            $this->saveAssinatura($usuario, $plano, $result);

            DB::commit();

            return [
                'message' => $result['message'],
                'asaas_id' => $result['asaas_id'],
                'asaas_status' => $result['asaas_status'],
                'months' => $dto->months,
                'discount' => $discount,
                'total' => $total,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createPayment(Usuario $usuario, Plano $plano, CheckoutRequestDTO $dto, $customerData, float $total): array
    {
        $builder = (new AsaasPaymentBuilder())
            ->forCustomer($usuario)
            ->withValue($total)
            ->withDescription($plano->nome . ' (Semestral -10%)')
            ->withBillingType($dto->billingType)
            ->withDueDate(date('Y-m-d'))
            ->withExternalReference("pay:user:{$usuario->id}:plano:{$plano->id}:m6");

        if ($dto->hasCreditCard()) {
            $builder->withCreditCard($dto->creditCard, $customerData);
        }

        $resp = $this->asaas->createPayment($builder->build());
        $status = $resp['status'] ?? 'PENDING';

        return [
            'asaas_id' => $resp['id'] ?? null,
            'asaas_status' => $status,
            'status' => match ($status) {
                'CONFIRMED', 'RECEIVED' => AssinaturaUsuario::ST_ACTIVE,
                default => AssinaturaUsuario::ST_PENDING,
            },
            'renova_em' => date('Y-m-d', strtotime('+6 months')),
            'message' => 'Cobrança semestral criada. Aguarde confirmação.',
        ];
    }

    private function createSubscription(Usuario $usuario, Plano $plano, CheckoutRequestDTO $dto, $customerData, float $valorMensal, float $total): array
    {
        $cycle = SubscriptionCycle::fromMonths($dto->months);
        $desc = $plano->nome . ($dto->isAnual() ? ' (Anual -15%)' : '');

        $builder = (new AsaasSubscriptionBuilder())
            ->forCustomer($usuario->external_customer_id)
            ->withValue($dto->isAnual() ? $total : $valorMensal)
            ->withDescription($desc)
            ->withBillingType($dto->billingType)
            ->withCycle($cycle)
            ->withNextDueDate(date('Y-m-d'))
            ->withExternalReference("sub:user:{$usuario->id}:plano:{$plano->id}:" . ($dto->isAnual() ? 'y1' : 'm1'));

        if ($dto->hasCreditCard()) {
            $builder->withCreditCard($dto->creditCard, $customerData);
        }

        $resp = $this->asaas->createSubscription($builder->build());
        $status = $resp['status'] ?? 'PENDING';

        return [
            'asaas_id' => $resp['id'] ?? null,
            'asaas_status' => $status,
            'status' => match ($status) {
                'ACTIVE' => AssinaturaUsuario::ST_ACTIVE,
                'PENDING' => AssinaturaUsuario::ST_PENDING,
                'EXPIRED', 'SUSPENDED' => AssinaturaUsuario::ST_PAST_DUE,
                'CANCELED' => AssinaturaUsuario::ST_CANCELED,
                default => AssinaturaUsuario::ST_PENDING,
            },
            'renova_em' => $resp['nextDueDate'] ?? date('Y-m-d', strtotime($dto->isAnual() ? '+1 year' : '+1 month')),
            'message' => 'Assinatura criada. Aguarde confirmação.',
        ];
    }

    private function saveAssinatura(Usuario $usuario, Plano $plano, array $result): void
    {
        $assinatura = new AssinaturaUsuario([
            'user_id' => $usuario->id,
            'plano_id' => $plano->id,
            'gateway' => 'asaas',
            'external_customer_id' => $usuario->external_customer_id,
            'external_subscription_id' => $result['asaas_id'],
            'status' => $result['status'],
            'renova_em' => $result['renova_em'],
        ]);
        $assinatura->save();
    }

    private function getActiveSubscription(Usuario $usuario): ?AssinaturaUsuario
    {
        return $usuario->assinaturas()
            ->where('gateway', 'asaas')
            ->whereIn('status', [
                AssinaturaUsuario::ST_ACTIVE,
                AssinaturaUsuario::ST_PENDING,
                AssinaturaUsuario::ST_PAST_DUE,
            ])
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }

    private function logCancellation(Usuario $usuario, AssinaturaUsuario $assinatura): void
    {
        if (class_exists(LogService::class)) {
            LogService::info('Assinatura cancelada', [
                'user_id' => $usuario->id,
                'assinatura_id' => $assinatura->id,
            ]);
        }
    }

    private function handleCheckoutError(\Throwable $e): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        if (class_exists(LogService::class)) {
            LogService::error('Erro no checkout', [
                'userId' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }

        Response::error($e->getMessage() ?: 'Não foi possível concluir o checkout.');
    }

    private function handleCancelError(\Throwable $e): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        if (class_exists(LogService::class)) {
            LogService::error('Erro ao cancelar', [
                'userId' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }

        Response::error('Não foi possível cancelar agora.');
    }
}