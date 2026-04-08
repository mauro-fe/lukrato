<?php

declare(strict_types=1);

namespace Application\Services\Billing;

use Application\Builders\AsaasPaymentBuilder;
use Application\Builders\AsaasSubscriptionBuilder;
use Application\Container\ApplicationContainer;
use Application\DTO\CheckoutRequestDTO;
use Application\DTO\CustomerDataDTO;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Enums\SubscriptionCycle;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;
use Application\Models\Usuario;
use Application\Providers\PerfilControllerFactory;
use Application\Services\Gamification\AchievementService;
use Application\Services\Infrastructure\LogService;
use Application\Validators\CheckoutValidator;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class PremiumWorkflowService
{
    private AsaasService $asaas;
    private CustomerService $customerService;
    private CheckoutValidator $validator;
    private AchievementService $achievementService;

    public function __construct(
        ?AsaasService $asaas = null,
        ?CustomerService $customerService = null,
        ?CheckoutValidator $validator = null,
        ?AchievementService $achievementService = null
    ) {
        $this->asaas = ApplicationContainer::resolveOrNew($asaas, AsaasService::class);
        $this->customerService = ApplicationContainer::resolveOrNew($customerService, CustomerService::class);
        $this->validator = ApplicationContainer::resolveOrNew($validator, CheckoutValidator::class);
        $this->achievementService = ApplicationContainer::resolveOrNew($achievementService, AchievementService::class);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function checkout(int $userId, array $payload): array
    {
        try {
            $usuario = $this->getAuthenticatedUser($userId);
            $this->validateNoActiveSubscription($usuario);

            $plano = $this->getPlanoPro();
            $dto = CheckoutRequestDTO::fromRequest($payload);

            $this->validator->validate($dto, $plano);
            $this->customerService->ensureAsaasCustomer($usuario, $this->asaas, $dto->holderInfo);
            $this->refreshUser($usuario);

            if (empty($usuario->external_customer_id)) {
                throw new \RuntimeException('Não foi possível criar o cliente no gateway de pagamento.');
            }

            $customerData = $this->customerService->buildCustomerData($usuario, $dto->holderInfo);
            $result = $this->processCheckout($usuario, $plano, $dto, $customerData);

            $this->saveCheckoutDataToProfile($usuario, $dto);

            return $this->success($result);
        } catch (Throwable $e) {
            return $this->handleCheckoutError($e, $userId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getPendingPayment(int $userId): array
    {
        try {
            $usuario = $this->getAuthenticatedUser($userId);
            $assinatura = $this->findPendingPaymentSubscription($usuario);

            if (!$assinatura) {
                return $this->success(['hasPending' => false]);
            }

            $paymentData = $this->asaas->getPayment($assinatura->external_payment_id);
            $status = $paymentData['status'] ?? 'PENDING';

            if (in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true)) {
                $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
                $assinatura->save();

                return $this->success(['hasPending' => false, 'paid' => true]);
            }

            if (in_array($status, ['OVERDUE', 'REFUNDED', 'DELETED', 'REFUND_REQUESTED'], true)) {
                $assinatura->delete();

                return $this->success(['hasPending' => false, 'expired' => true]);
            }

            $plano = $this->getPlanoPro();
            $billingType = $assinatura->billing_type;

            $responseData = [
                'hasPending' => true,
                'paymentId' => $assinatura->external_payment_id,
                'billingType' => $billingType,
                'plan' => [
                    'name' => $plano->nome,
                    'price' => $plano->preco_centavos / 100,
                ],
                'createdAt' => $assinatura->created_at->format('d/m/Y H:i'),
            ];

            if ($billingType === 'PIX') {
                $pixData = $this->asaas->getPixQrCode($assinatura->external_payment_id);
                if (!empty($pixData['encodedImage'])) {
                    $responseData['pix'] = [
                        'qrCodeImage' => 'data:image/png;base64,' . $pixData['encodedImage'],
                        'payload' => $pixData['payload'] ?? null,
                        'expirationDate' => $pixData['expirationDate'] ?? null,
                    ];
                }
            } elseif ($billingType === 'BOLETO') {
                $boletoData = $this->asaas->getBoletoIdentificationField($assinatura->external_payment_id);
                $responseData['boleto'] = [
                    'identificationField' => $boletoData['identificationField'] ?? null,
                    'nossoNumero' => $boletoData['nossoNumero'] ?? null,
                    'barCode' => $boletoData['barCode'] ?? null,
                    'bankSlipUrl' => $paymentData['bankSlipUrl'] ?? null,
                ];
            }

            return $this->success($responseData);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao consultar pagamento pendente.', LogCategory::PAYMENT, [
                'action' => 'premium_get_pending_payment',
                'user_id' => $userId,
            ], 'PAYMENT_FAILED');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelPendingPayment(int $userId): array
    {
        try {
            $usuario = $this->getAuthenticatedUser($userId);
            $assinatura = $this->findPendingPaymentSubscription($usuario);

            if (!$assinatura) {
                return $this->failure('Nenhum pagamento pendente encontrado.', 404, null, 'RESOURCE_NOT_FOUND');
            }

            try {
                $this->asaas->cancelPayment($assinatura->external_payment_id);
            } catch (Throwable $e) {
                LogService::captureException($e, LogCategory::PAYMENT, [
                    'action' => 'cancel_pending_payment',
                    'payment_id' => $assinatura->external_payment_id,
                ], $usuario->id ?? null, LogLevel::WARNING);
            }

            $billingType = $assinatura->billing_type;
            $assinatura->delete();

            LogService::info('Pagamento pendente cancelado', [
                'user_id' => $usuario->id,
                'billing_type' => $billingType,
            ]);

            return $this->success([
                'message' => 'Pagamento cancelado com sucesso. Você pode escolher outro método.',
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao cancelar pagamento pendente.', LogCategory::PAYMENT, [
                'action' => 'premium_cancel_pending_payment',
                'user_id' => $userId,
            ], 'PAYMENT_FAILED');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getPendingPix(int $userId): array
    {
        try {
            $usuario = $this->getAuthenticatedUser($userId);
            $assinatura = $this->findPendingPixSubscription($usuario);

            if (!$assinatura) {
                return $this->success(['hasPending' => false]);
            }

            $paymentData = $this->asaas->getPayment($assinatura->external_payment_id);
            $status = $paymentData['status'] ?? 'PENDING';

            if (in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true)) {
                $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
                $assinatura->save();

                return $this->success(['hasPending' => false, 'paid' => true]);
            }

            if (in_array($status, ['OVERDUE', 'REFUNDED', 'DELETED', 'REFUND_REQUESTED'], true)) {
                $assinatura->delete();

                return $this->success(['hasPending' => false, 'expired' => true]);
            }

            $pixData = $this->asaas->getPixQrCode($assinatura->external_payment_id);
            if (empty($pixData['encodedImage'])) {
                return $this->success(['hasPending' => false]);
            }

            $plano = $this->getPlanoPro();

            return $this->success([
                'hasPending' => true,
                'paymentId' => $assinatura->external_payment_id,
                'pix' => [
                    'qrCodeImage' => 'data:image/png;base64,' . $pixData['encodedImage'],
                    'payload' => $pixData['payload'] ?? null,
                    'expirationDate' => $pixData['expirationDate'] ?? null,
                ],
                'plan' => [
                    'name' => $plano->nome,
                    'price' => $plano->preco_centavos / 100,
                ],
                'createdAt' => $assinatura->created_at->format('d/m/Y H:i'),
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao consultar PIX pendente.', LogCategory::PAYMENT, [
                'action' => 'premium_get_pending_pix',
                'user_id' => $userId,
            ], 'PAYMENT_FAILED');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function checkPayment(int $userId, string $paymentId): array
    {
        try {
            $usuario = $this->getAuthenticatedUser($userId);
            $assinatura = $this->findSubscriptionByPaymentId($usuario, $paymentId);

            if (!$assinatura) {
                return $this->failure('Pagamento não encontrado.', 404, null, 'RESOURCE_NOT_FOUND');
            }

            $paymentData = $this->asaas->getPayment($paymentId);
            $status = $paymentData['status'] ?? 'PENDING';
            $paid = in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true);

            if ($paid && $assinatura->status !== AssinaturaUsuario::ST_ACTIVE) {
                $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
                $assinatura->save();

                LogService::info('Pagamento confirmado via polling', [
                    'user_id' => $usuario->id,
                    'payment_id' => $paymentId,
                    'status' => $status,
                ]);
            }

            return $this->success([
                'paid' => $paid,
                'status' => $status,
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao verificar pagamento.', LogCategory::PAYMENT, [
                'action' => 'premium_check_payment',
                'user_id' => $userId,
                'payment_id' => $paymentId,
            ], 'PAYMENT_FAILED');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelSubscription(int $userId): array
    {
        try {
            $usuario = $this->getAuthenticatedUser($userId);

            DB::beginTransaction();

            try {
                $assinatura = $this->getActiveSubscription($usuario);

                if (!$assinatura) {
                    DB::rollBack();

                    return $this->failure('Nenhuma assinatura ativa encontrada.');
                }

                if ($assinatura->gateway === 'asaas' && $assinatura->external_subscription_id) {
                    $this->asaas->cancelSubscription($assinatura->external_subscription_id);
                }

                $assinatura->status = AssinaturaUsuario::ST_CANCELED;
                $assinatura->cancelada_em = now();
                $assinatura->save();

                $this->logCancellation($usuario, $assinatura);

                DB::commit();

                return $this->success(['message' => 'Assinatura cancelada com sucesso.']);
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Throwable $e) {
            return $this->handleCancelError($e, $userId);
        }
    }

    protected function getAuthenticatedUser(int $userId): Usuario
    {
        return $this->findUserById($userId);
    }

    protected function findUserById(int $userId): Usuario
    {
        return Usuario::findOrFail($userId);
    }

    protected function refreshUser(Usuario $usuario): void
    {
        $usuario->refresh();
    }

    protected function validateNoActiveSubscription(Usuario $usuario): void
    {
        $usuario->assinaturas()
            ->where('gateway', 'asaas')
            ->where('status', AssinaturaUsuario::ST_PENDING)
            ->whereNull('external_payment_id')
            ->whereNull('external_subscription_id')
            ->delete();

        $existingSubscription = $usuario->assinaturas()
            ->where('gateway', 'asaas')
            ->whereIn('status', [
                AssinaturaUsuario::ST_ACTIVE,
                AssinaturaUsuario::ST_PENDING,
                AssinaturaUsuario::ST_PAST_DUE,
            ])
            ->lockForUpdate()
            ->first();

        if (!$existingSubscription) {
            return;
        }

        if ($existingSubscription->status === AssinaturaUsuario::ST_ACTIVE) {
            $renewsAt = $existingSubscription->renova_em
                ? Carbon::parse($existingSubscription->renova_em)
                : null;

            if ($renewsAt && $renewsAt->isPast()) {
                $this->expireOldSubscription($existingSubscription);
                return;
            }

            throw new \RuntimeException('Você já possui uma assinatura ativa.');
        }

        if (
            $existingSubscription->status === AssinaturaUsuario::ST_PENDING
            && in_array($existingSubscription->billing_type, ['PIX', 'BOLETO'], true)
        ) {
            $existingSubscription->delete();
            return;
        }

        if ($existingSubscription->status === AssinaturaUsuario::ST_PAST_DUE) {
            $this->expireOldSubscription($existingSubscription);
            return;
        }

        throw new \RuntimeException('Você já possui uma assinatura em andamento.');
    }

    protected function expireOldSubscription(AssinaturaUsuario $subscription): void
    {
        if ($subscription->gateway === 'asaas' && $subscription->external_subscription_id) {
            try {
                $this->asaas->cancelSubscription($subscription->external_subscription_id);
            } catch (Throwable $e) {
                LogService::captureException($e, LogCategory::SUBSCRIPTION, [
                    'action' => 'expire_old_subscription_asaas_cancel',
                    'subscription_id' => $subscription->id,
                ], $subscription->user_id, LogLevel::WARNING);
            }
        }

        $subscription->status = AssinaturaUsuario::ST_EXPIRED;
        $subscription->save();

        LogService::info('Assinatura antiga expirada para permitir renovação', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
        ]);
    }

    protected function getPlanoPro(): Plano
    {
        $plano = $this->findPlanoPro();

        if (!$plano) {
            throw new \RuntimeException('Plano PRO não está configurado.');
        }

        return $plano;
    }

    protected function findPlanoPro(): ?Plano
    {
        return Plano::where('code', 'pro')->where('ativo', 1)->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function processCheckout(
        Usuario $usuario,
        Plano $plano,
        CheckoutRequestDTO $dto,
        CustomerDataDTO $customerData
    ): array {
        DB::beginTransaction();

        try {
            $valorMensal = $plano->preco_centavos / 100;
            $discount = $this->validator->getExpectedDiscount($dto->months);
            $total = $this->validator->calculateTotal($valorMensal, $dto->months, $discount);

            $cupomAplicado = null;
            $valorOriginal = $total;

            if ($dto->couponCode) {
                $cupom = \Application\Models\Cupom::findByCodigo($dto->couponCode, lockForUpdate: true);

                if (!$cupom) {
                    throw new \RuntimeException('Cupom não encontrado.');
                }

                if (!$cupom->isValid()) {
                    throw new \RuntimeException('Cupom inválido ou expirado.');
                }

                $jaUsou = \Application\Models\CupomUsado::where('cupom_id', $cupom->id)
                    ->where('usuario_id', $usuario->id)
                    ->exists();

                if ($jaUsou) {
                    throw new \RuntimeException('Você já utilizou este cupom anteriormente.');
                }

                if ($cupom->apenas_primeira_assinatura ?? true) {
                    $this->validarElegibilidadeCupom($usuario, $cupom);
                }

                $descontoCupom = $cupom->calcularDesconto($total);
                $total = $cupom->aplicarDesconto($total);

                $cupomAplicado = [
                    'cupom' => $cupom,
                    'desconto' => $descontoCupom,
                    'valor_original' => $valorOriginal,
                    'valor_final' => $total,
                ];
            }

            if ($dto->isPix() || $dto->isBoleto()) {
                $result = $this->createPixOrBoletoPayment($usuario, $plano, $dto, $total);
            } else {
                $result = $this->createSubscription(
                    $usuario,
                    $plano,
                    $dto,
                    $customerData,
                    $valorMensal,
                    $valorOriginal,
                    $cupomAplicado
                );
            }

            $assinatura = $this->saveAssinatura($usuario, $plano, $result, $dto->billingType);

            if ($cupomAplicado) {
                $cupom = $cupomAplicado['cupom'];

                $cupom->incrementarUso();

                \Application\Models\CupomUsado::create([
                    'cupom_id' => $cupom->id,
                    'usuario_id' => $usuario->id,
                    'assinatura_id' => $assinatura->id,
                    'desconto_aplicado' => $cupomAplicado['desconto'],
                    'valor_original' => $cupomAplicado['valor_original'],
                    'valor_final' => $cupomAplicado['valor_final'],
                    'usado_em' => now(),
                ]);
            }

            DB::commit();

            $response = [
                'message' => $result['message'],
                'asaas_id' => $result['asaas_id'],
                'asaas_status' => $result['asaas_status'],
                'months' => $dto->months,
                'discount' => $discount,
                'total' => $total,
                'paymentId' => $result['asaas_id'],
            ];

            if ($cupomAplicado) {
                $response['coupon_applied'] = [
                    'codigo' => $cupomAplicado['cupom']->codigo,
                    'desconto' => $cupomAplicado['desconto'],
                    'valor_original' => $cupomAplicado['valor_original'],
                ];
            }

            if (isset($result['pix'])) {
                $response['pix'] = $result['pix'];
            }

            if (isset($result['boleto'])) {
                $response['boleto'] = $result['boleto'];
            }

            return $response;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function createPixOrBoletoPayment(
        Usuario $usuario,
        Plano $plano,
        CheckoutRequestDTO $dto,
        float $total
    ): array {
        $desc = match ($dto->months) {
            6 => $plano->nome . ' (Semestral -10%)',
            12 => $plano->nome . ' (Anual -15%)',
            default => $plano->nome,
        };

        $dueDate = $dto->isPix()
            ? date('Y-m-d')
            : date('Y-m-d', strtotime('+3 days'));

        $builder = (new AsaasPaymentBuilder())
            ->forCustomer($usuario)
            ->withValue($total)
            ->withDescription($desc)
            ->withBillingType($dto->billingType)
            ->withDueDate($dueDate)
            ->withExternalReference("pay:user:{$usuario->id}:plano:{$plano->id}:m{$dto->months}");

        $resp = $this->asaas->createPayment($builder->build());

        $paymentId = $resp['id'] ?? null;
        $status = $resp['status'] ?? 'PENDING';

        if (!$paymentId) {
            throw new \RuntimeException('Erro ao criar pagamento no Asaas.');
        }

        $result = [
            'asaas_id' => $paymentId,
            'asaas_status' => $status,
            'status' => AssinaturaUsuario::ST_PENDING,
            'renova_em' => date('Y-m-d', strtotime(match ($dto->months) {
                6 => '+6 months',
                12 => '+1 year',
                default => '+1 month',
            })),
            'message' => $dto->isPix()
                ? 'PIX gerado com sucesso! Aguardando pagamento.'
                : 'Boleto gerado com sucesso! Aguardando pagamento.',
        ];

        if ($dto->isPix()) {
            $pixData = null;

            for ($i = 0; $i < 8; $i++) {
                usleep(500000);

                try {
                    $pixData = $this->asaas->getPixQrCode($paymentId);

                    if (!empty($pixData['encodedImage'])) {
                        break;
                    }
                } catch (Throwable $e) {
                    LogService::captureException($e, LogCategory::PAYMENT, [
                        'action' => 'pix_qrcode_retry',
                        'attempt' => $i + 1,
                        'payment_id' => $paymentId,
                    ], $usuario->id ?? null, LogLevel::WARNING);
                }
            }

            if (empty($pixData['encodedImage'])) {
                throw new \RuntimeException(
                    'PIX criado, mas o QR Code ainda não foi disponibilizado pelo gateway. Tente novamente em alguns segundos.'
                );
            }

            $result['pix'] = [
                'qrCodeImage' => 'data:image/png;base64,' . $pixData['encodedImage'],
                'payload' => $pixData['payload'] ?? null,
                'expirationDate' => $pixData['expirationDate'] ?? null,
            ];
        }

        if ($dto->isBoleto()) {
            $boletoData = null;

            for ($i = 0; $i < 5; $i++) {
                usleep(500000);

                try {
                    $boletoData = $this->asaas->getBoletoIdentificationField($paymentId);

                    if (!empty($boletoData['identificationField'])) {
                        break;
                    }
                } catch (Throwable $e) {
                    LogService::captureException($e, LogCategory::PAYMENT, [
                        'action' => 'boleto_data_retry',
                        'attempt' => $i + 1,
                        'payment_id' => $paymentId,
                    ], $usuario->id ?? null, LogLevel::WARNING);
                }
            }

            $result['boleto'] = [
                'identificationField' => $boletoData['identificationField'] ?? null,
                'nossoNumero' => $boletoData['nossoNumero'] ?? null,
                'barCode' => $boletoData['barCode'] ?? null,
                'bankSlipUrl' => $resp['bankSlipUrl'] ?? null,
            ];

            if (empty($result['boleto']['identificationField']) && empty($result['boleto']['bankSlipUrl'])) {
                throw new \RuntimeException(
                    'Boleto criado, mas os dados de pagamento ainda não foram disponibilizados pelo gateway. Tente novamente em alguns segundos.'
                );
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed>|null $cupomAplicado
     * @return array<string, mixed>
     */
    protected function createSubscription(
        Usuario $usuario,
        Plano $plano,
        CheckoutRequestDTO $dto,
        CustomerDataDTO $customerData,
        float $valorMensal,
        float $total,
        ?array $cupomAplicado = null
    ): array {
        $cycle = SubscriptionCycle::fromMonths($dto->months);

        $desc = match ($dto->months) {
            6 => $plano->nome . ' (Semestral -10%)',
            12 => $plano->nome . ' (Anual -15%)',
            default => $plano->nome,
        };

        $valorCobranca = $dto->isMensal() ? $valorMensal : $total;

        $builder = (new AsaasSubscriptionBuilder())
            ->forCustomer($usuario->external_customer_id)
            ->withValue($valorCobranca)
            ->withDescription($desc)
            ->withBillingType($dto->billingType)
            ->withCycle($cycle)
            ->withNextDueDate(date('Y-m-d'))
            ->withExternalReference("sub:user:{$usuario->id}:plano:{$plano->id}:" . match ($dto->months) {
                6 => 'm6',
                12 => 'y1',
                default => 'm1',
            });

        if ($cupomAplicado && $dto->isMensal()) {
            $cupom = $cupomAplicado['cupom'];
            $tipoDesconto = $cupom->tipo_desconto === 'percentual' ? 'PERCENTAGE' : 'FIXED';
            $valorDesconto = (float) $cupom->valor_desconto;

            $builder->withDiscount($valorDesconto, $tipoDesconto, 0);
        }

        if ($dto->hasCreditCard()) {
            $builder->withCreditCard($dto->creditCard, $customerData);
        }

        $resp = $this->asaas->createSubscription($builder->build());
        $status = $resp['status'] ?? 'PENDING';
        $renovaEm = $resp['nextDueDate'] ?? date('Y-m-d', strtotime(match ($dto->months) {
            6 => '+6 months',
            12 => '+1 year',
            default => '+1 month',
        }));

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
            'renova_em' => $renovaEm,
            'message' => 'Assinatura criada. Aguarde confirmação.',
        ];
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function saveAssinatura(
        Usuario $usuario,
        Plano $plano,
        array $result,
        string $billingType = 'CREDIT_CARD'
    ): AssinaturaUsuario {
        $data = [
            'user_id' => $usuario->id,
            'plano_id' => $plano->id,
            'gateway' => 'asaas',
            'external_customer_id' => $usuario->external_customer_id,
            'status' => $result['status'],
            'renova_em' => $result['renova_em'],
            'billing_type' => $billingType,
        ];

        if ($billingType === 'CREDIT_CARD') {
            $data['external_subscription_id'] = $result['asaas_id'];
        } else {
            $data['external_payment_id'] = $result['asaas_id'];
        }

        $assinatura = new AssinaturaUsuario($data);
        $assinatura->save();

        return $assinatura;
    }

    protected function findPendingPaymentSubscription(Usuario $usuario): ?AssinaturaUsuario
    {
        return $usuario->assinaturas()
            ->where('gateway', 'asaas')
            ->where('status', AssinaturaUsuario::ST_PENDING)
            ->whereIn('billing_type', ['PIX', 'BOLETO'])
            ->whereNotNull('external_payment_id')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    protected function findPendingPixSubscription(Usuario $usuario): ?AssinaturaUsuario
    {
        return $usuario->assinaturas()
            ->where('gateway', 'asaas')
            ->where('status', AssinaturaUsuario::ST_PENDING)
            ->where('billing_type', 'PIX')
            ->whereNotNull('external_payment_id')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    protected function findSubscriptionByPaymentId(Usuario $usuario, string $paymentId): ?AssinaturaUsuario
    {
        return $usuario->assinaturas()
            ->where('external_payment_id', $paymentId)
            ->first();
    }

    protected function getActiveSubscription(Usuario $usuario): ?AssinaturaUsuario
    {
        return $usuario->assinaturas()
            ->whereIn('status', [
                AssinaturaUsuario::ST_ACTIVE,
                AssinaturaUsuario::ST_PENDING,
                AssinaturaUsuario::ST_PAST_DUE,
            ])
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }

    protected function logCancellation(Usuario $usuario, AssinaturaUsuario $assinatura): void
    {
        LogService::info('Assinatura cancelada', [
            'user_id' => $usuario->id,
            'assinatura_id' => $assinatura->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function handleCheckoutError(Throwable $e, int $userId): array
    {
        $this->rollbackOpenTransaction();

        if ($this->isSafeCheckoutException($e)) {
            LogService::captureException($e, LogCategory::PAYMENT, [
                'action' => 'checkout_domain_error',
                'userId' => $userId,
            ], $userId, LogLevel::WARNING);

            return $this->failure($e->getMessage() ?: 'Nao foi possivel concluir o checkout.', 400, null, 'PAYMENT_FAILED');
        }

        return $this->internalFailure($e, 'Erro ao concluir checkout.', LogCategory::PAYMENT, [
            'action' => 'checkout',
            'userId' => $userId,
        ], 'PAYMENT_FAILED');

        LogService::captureException($e, LogCategory::PAYMENT, [
            'action' => 'checkout',
            'userId' => $userId,
        ], $userId);

        return $this->failure($e->getMessage() ?: 'Não foi possível concluir o checkout.', 400, null, 'PAYMENT_FAILED');
    }

    /**
     * @return array<string, mixed>
     */
    protected function handleCancelError(Throwable $e, int $userId): array
    {
        $this->rollbackOpenTransaction();

        return $this->internalFailure($e, 'Erro ao cancelar assinatura.', LogCategory::SUBSCRIPTION, [
            'action' => 'cancel_subscription',
            'userId' => $userId,
        ]);

        LogService::captureException($e, LogCategory::SUBSCRIPTION, [
            'action' => 'cancel_subscription',
            'userId' => $userId,
        ], $userId);

        return $this->failure('Erro interno no servidor.', 500);
    }

    protected function saveCheckoutDataToProfile(Usuario $usuario, CheckoutRequestDTO $dto): void
    {
        try {
            $perfilService = PerfilControllerFactory::createService();

            $dados = [
                'cpf' => $dto->holderInfo['cpfCnpj'] ?? '',
                'phone' => $dto->holderInfo['mobilePhone'] ?? '',
                'cep' => $dto->holderInfo['postalCode'] ?? '',
                'endereco' => $dto->holderInfo['address'] ?? '',
            ];

            $perfilService->salvarDadosCheckout($usuario->id, $dados);

            $this->achievementService->checkAndUnlockAchievements($usuario->id, 'checkout_profile_save');

            LogService::info('Dados do checkout salvos no perfil', ['user_id' => $usuario->id]);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::PAYMENT, [
                'action' => 'save_checkout_profile',
                'user_id' => $usuario->id,
            ], $usuario->id, LogLevel::WARNING);
        }
    }

    protected function validarElegibilidadeCupom(Usuario $usuario, \Application\Models\Cupom $cupom): void
    {
        $assinaturasEfetivas = $usuario->assinaturas()
            ->where('gateway', 'asaas')
            ->whereIn('status', [
                AssinaturaUsuario::ST_ACTIVE,
                AssinaturaUsuario::ST_CANCELED,
                AssinaturaUsuario::ST_EXPIRED,
                AssinaturaUsuario::ST_PAST_DUE,
                AssinaturaUsuario::ST_PAUSED,
            ]);

        if (!$assinaturasEfetivas->exists()) {
            return;
        }

        if ($cupom->permite_reativacao ?? false) {
            $mesesInatividade = $cupom->meses_inatividade_reativacao ?? 3;

            $temAssinaturaAtiva = $usuario->assinaturas()
                ->where('gateway', 'asaas')
                ->where('status', AssinaturaUsuario::ST_ACTIVE)
                ->exists();

            if ($temAssinaturaAtiva) {
                throw new \RuntimeException('Você já possui uma assinatura ativa.');
            }

            $ultimaAssinatura = $usuario->assinaturas()
                ->where('gateway', 'asaas')
                ->whereIn('status', [
                    AssinaturaUsuario::ST_CANCELED,
                    AssinaturaUsuario::ST_EXPIRED,
                ])
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($ultimaAssinatura) {
                $dataReferencia = $ultimaAssinatura->cancelada_em ?? $ultimaAssinatura->updated_at;
                $mesesDesdeInativacao = now()->diffInMonths($dataReferencia);

                if ($mesesDesdeInativacao >= $mesesInatividade) {
                    return;
                }

                throw new \RuntimeException(
                    "Este cupom é válido para ex-assinantes inativos há pelo menos {$mesesInatividade} meses. "
                        . "Você está inativo há apenas {$mesesDesdeInativacao} meses."
                );
            }
        }

        throw new \RuntimeException('Este cupom é válido apenas para a primeira assinatura.');
    }

    /**
     * @return array<string, mixed>
     */
    private function success(mixed $data = null, string $message = 'Success', int $status = 200): array
    {
        return [
            'success' => true,
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $message, int $status = 400, mixed $errors = null, ?string $code = null): array
    {
        $result = [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];

        if ($errors !== null) {
            $result['errors'] = $errors;
        }

        if ($code !== null) {
            $result['code'] = $code;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function internalFailure(
        Throwable $e,
        string $publicMessage,
        LogCategory $category,
        array $context = [],
        ?string $code = null
    ): array {
        $errorId = LogService::reportException(
            e: $e,
            publicMessage: $publicMessage,
            context: $context,
            category: $category,
        );

        return $this->failure($publicMessage, 500, [
            'error_id' => $errorId,
            'request_id' => LogService::currentRequestId(),
        ], $code);
    }

    private function isSafeCheckoutException(Throwable $e): bool
    {
        $message = trim((string) $e->getMessage());

        if ($message === '') {
            return false;
        }

        $safeMessages = [
            'você ja possui uma assinatura ativa.',
            'você ja possui uma assinatura em andamento.',
            'Cupom nao encontrado.',
            'Cupom invalido ou expirado.',
            'você ja utilizou este cupom anteriormente.',
            'Este cupom e valido apenas para a primeira assinatura.',
            'Este cupom e valido para ex-assinantes inativos',
        ];

        foreach ($safeMessages as $safeMessage) {
            if (str_starts_with($message, $safeMessage)) {
                return true;
            }
        }

        return false;
    }

    private function rollbackOpenTransaction(): void
    {
        try {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } catch (Throwable) {
        }
    }
}
