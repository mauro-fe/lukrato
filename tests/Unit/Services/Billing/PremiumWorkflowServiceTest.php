<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Billing;

use Application\DTO\CheckoutRequestDTO;
use Application\DTO\CustomerDataDTO;
use Application\Models\Plano;
use Application\Models\Usuario;
use Application\Services\Billing\AsaasService;
use Application\Services\Billing\CustomerService;
use Application\Services\Billing\PremiumWorkflowService;
use Application\Validators\CheckoutValidator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PremiumWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetPendingPaymentReturnsFalseWhenNoPendingSubscriptionExists(): void
    {
        $user = new Usuario();
        $user->id = 10;

        $service = new class (
            Mockery::mock(AsaasService::class),
            Mockery::mock(CustomerService::class),
            Mockery::mock(CheckoutValidator::class),
            $user
        ) extends PremiumWorkflowService {
            public function __construct(
                AsaasService $asaas,
                CustomerService $customerService,
                CheckoutValidator $validator,
                private readonly Usuario $user
            ) {
                parent::__construct($asaas, $customerService, $validator);
            }

            protected function getAuthenticatedUser(int $userId): Usuario
            {
                return $this->user;
            }

            protected function findPendingPaymentSubscription(Usuario $usuario): ?\Application\Models\AssinaturaUsuario
            {
                return null;
            }
        };

        $result = $service->getPendingPayment(10);

        $this->assertTrue($result['success']);
        $this->assertSame([
            'hasPending' => false,
        ], $result['data']);
    }

    public function testCancelPendingPaymentReturnsNotFoundWhenSubscriptionDoesNotExist(): void
    {
        $user = new Usuario();
        $user->id = 20;

        $service = new class (
            Mockery::mock(AsaasService::class),
            Mockery::mock(CustomerService::class),
            Mockery::mock(CheckoutValidator::class),
            $user
        ) extends PremiumWorkflowService {
            public function __construct(
                AsaasService $asaas,
                CustomerService $customerService,
                CheckoutValidator $validator,
                private readonly Usuario $user
            ) {
                parent::__construct($asaas, $customerService, $validator);
            }

            protected function getAuthenticatedUser(int $userId): Usuario
            {
                return $this->user;
            }

            protected function findPendingPaymentSubscription(Usuario $usuario): ?\Application\Models\AssinaturaUsuario
            {
                return null;
            }
        };

        $result = $service->cancelPendingPayment(20);

        $this->assertFalse($result['success']);
        $this->assertSame(404, $result['status']);
        $this->assertSame('Nenhum pagamento pendente encontrado.', $result['message']);
    }

    public function testCheckoutReturnsSuccessPayloadWhenWorkflowCompletes(): void
    {
        $user = new Usuario();
        $user->id = 30;
        $user->email = 'premium@example.com';
        $user->external_customer_id = 'cus_123';

        $plan = new Plano();
        $plan->id = 2;
        $plan->nome = 'PRO';
        $plan->preco_centavos = 1990;

        $asaas = Mockery::mock(AsaasService::class);
        $customerService = Mockery::mock(CustomerService::class);
        $validator = Mockery::mock(CheckoutValidator::class);

        $validator
            ->shouldReceive('validate')
            ->once()
            ->with(Mockery::type(CheckoutRequestDTO::class), $plan);

        $customerService
            ->shouldReceive('ensureAsaasCustomer')
            ->once()
            ->with($user, $asaas, [
                'cpfCnpj' => '12345678901',
            ]);

        $customerService
            ->shouldReceive('buildCustomerData')
            ->once()
            ->with($user, [
                'cpfCnpj' => '12345678901',
            ])
            ->andReturn(new CustomerDataDTO(
                name: 'Premium User',
                email: 'premium@example.com',
                cpf: '12345678901',
                mobilePhone: null,
                postalCode: null,
                addressNumber: null,
                addressComplement: null
            ));

        $service = new class ($asaas, $customerService, $validator, $user, $plan) extends PremiumWorkflowService {
            public function __construct(
                AsaasService $asaas,
                CustomerService $customerService,
                CheckoutValidator $validator,
                private readonly Usuario $user,
                private readonly Plano $plan
            ) {
                parent::__construct($asaas, $customerService, $validator);
            }

            protected function getAuthenticatedUser(int $userId): Usuario
            {
                return $this->user;
            }

            protected function validateNoActiveSubscription(Usuario $usuario): void
            {
            }

            protected function getPlanoPro(): Plano
            {
                return $this->plan;
            }

            protected function refreshUser(Usuario $usuario): void
            {
            }

            protected function processCheckout(
                Usuario $usuario,
                Plano $plano,
                CheckoutRequestDTO $dto,
                CustomerDataDTO $customerData
            ): array {
                return [
                    'message' => 'Checkout concluído',
                    'paymentId' => 'pay_123',
                    'total' => 19.90,
                ];
            }

            protected function saveCheckoutDataToProfile(Usuario $usuario, CheckoutRequestDTO $dto): void
            {
            }
        };

        $result = $service->checkout(30, [
            'billingType' => 'PIX',
            'holderInfo' => [
                'cpfCnpj' => '12345678901',
            ],
            'months' => 1,
            'discount' => 0,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame([
            'message' => 'Checkout concluído',
            'paymentId' => 'pay_123',
            'total' => 19.90,
        ], $result['data']);
    }
}
