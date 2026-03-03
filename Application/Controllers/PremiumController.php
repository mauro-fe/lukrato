<?php

namespace Application\Controllers;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\Plano;
use Application\Models\AssinaturaUsuario;
use Application\Services\Billing\AsaasService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Billing\CustomerService;
use Application\Services\Gamification\AchievementService;
use Application\Validators\CheckoutValidator;
use Application\DTO\CheckoutRequestDTO;
use Application\Builders\AsaasPaymentBuilder;
use Application\Builders\AsaasSubscriptionBuilder;
use Application\Enums\SubscriptionCycle;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;
use Application\Providers\PerfilControllerFactory;
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
     * Suporta: CREDIT_CARD, PIX, BOLETO
     */
    public function checkout(): void
    {
        $this->requireAuthApi();

        error_log("🔵 [CHECKOUT] Iniciando checkout para usuário: " . ($this->userId ?? 'N/A'));

        try {
            $usuario = $this->getAuthenticatedUser();
            error_log("🔵 [CHECKOUT] Usuário autenticado: {$usuario->id} - {$usuario->email}");

            $this->validateNoActiveSubscription($usuario);

            $plano = $this->getPlanoPro();
            $dto = CheckoutRequestDTO::fromRequest($this->getRequestBody());

            error_log("🔵 [CHECKOUT] Plano: {$plano->nome}, BillingType: {$dto->billingType}, Meses: {$dto->months}");

            $this->validator->validate($dto, $plano);

            // Passa holderInfo do formulário para usar como fallback se não tiver dados no banco
            $this->customerService->ensureAsaasCustomer($usuario, $this->asaas, $dto->holderInfo);

            // Refresh para garantir que external_customer_id está atualizado
            $usuario->refresh();

            if (empty($usuario->external_customer_id)) {
                throw new \RuntimeException('Não foi possível criar o cliente no gateway de pagamento.');
            }

            $customerData = $this->customerService->buildCustomerData($usuario, $dto->holderInfo);

            $result = $this->processCheckout($usuario, $plano, $dto, $customerData);

            // Salvar dados do checkout no perfil (CPF, telefone, CEP)
            $this->saveCheckoutDataToProfile($usuario, $dto);

            Response::success($result);
        } catch (\Throwable $e) {
            $this->handleCheckoutError($e);
        }
    }

    /**
     * Verifica se existe pagamento pendente (PIX ou Boleto) não vencido para o usuário
     * Retorna dados do pagamento pendente (QR Code PIX ou linha digitável do Boleto)
     */
    public function getPendingPayment(): void
    {
        $this->requireAuthApi();

        try {
            $usuario = $this->getAuthenticatedUser();

            // Buscar assinatura PENDING com PIX ou Boleto
            $assinatura = $usuario->assinaturas()
                ->where('gateway', 'asaas')
                ->where('status', AssinaturaUsuario::ST_PENDING)
                ->whereIn('billing_type', ['PIX', 'BOLETO'])
                ->whereNotNull('external_payment_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$assinatura) {
                Response::success(['hasPending' => false]);
                return;
            }

            // Verificar status no Asaas
            $paymentData = $this->asaas->getPayment($assinatura->external_payment_id);
            $status = $paymentData['status'] ?? 'PENDING';

            // Se já foi pago, atualiza e retorna
            if (in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])) {
                $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
                $assinatura->save();
                Response::success(['hasPending' => false, 'paid' => true]);
                return;
            }

            // Se foi cancelado ou expirou, limpa e retorna
            if (in_array($status, ['OVERDUE', 'REFUNDED', 'DELETED', 'REFUND_REQUESTED'])) {
                $assinatura->delete();
                Response::success(['hasPending' => false, 'expired' => true]);
                return;
            }

            // Calcular informações do plano
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

            // Buscar dados específicos do método de pagamento
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

            Response::success($responseData);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Cancela o pagamento pendente atual (PIX ou Boleto)
     * Permite que o usuário escolha outro método de pagamento
     */
    public function cancelPendingPayment(): void
    {
        $this->requireAuthApi();

        try {
            $usuario = $this->getAuthenticatedUser();

            // Buscar assinatura PENDING com PIX ou Boleto
            $assinatura = $usuario->assinaturas()
                ->where('gateway', 'asaas')
                ->where('status', AssinaturaUsuario::ST_PENDING)
                ->whereIn('billing_type', ['PIX', 'BOLETO'])
                ->whereNotNull('external_payment_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$assinatura) {
                Response::error('Nenhum pagamento pendente encontrado.', 404);
                return;
            }

            // Tentar cancelar no Asaas
            try {
                $this->asaas->cancelPayment($assinatura->external_payment_id);
            } catch (\Throwable $e) {
                // Se falhar no Asaas, continua (pode já estar expirado)
                LogService::captureException($e, LogCategory::PAYMENT, [
                    'action' => 'cancel_pending_payment',
                    'payment_id' => $assinatura->external_payment_id,
                ], $usuario->id ?? null, LogLevel::WARNING);
            }

            // Deletar assinatura local
            $billingType = $assinatura->billing_type;
            $assinatura->delete();

            LogService::info('Pagamento pendente cancelado', [
                'user_id' => $usuario->id,
                'billing_type' => $billingType,
            ]);

            Response::success([
                'message' => 'Pagamento cancelado com sucesso. Você pode escolher outro método.',
            ]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Verifica se existe PIX pendente não vencido para o usuário
     * Se existir, retorna os dados do QR Code
     * @deprecated Use getPendingPayment() instead
     */
    public function getPendingPix(): void
    {
        $this->requireAuthApi();

        try {
            $usuario = $this->getAuthenticatedUser();

            // Buscar assinatura PENDING com PIX
            $assinatura = $usuario->assinaturas()
                ->where('gateway', 'asaas')
                ->where('status', AssinaturaUsuario::ST_PENDING)
                ->where('billing_type', 'PIX')
                ->whereNotNull('external_payment_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$assinatura) {
                Response::success(['hasPending' => false]);
                return;
            }

            // Verificar status no Asaas
            $paymentData = $this->asaas->getPayment($assinatura->external_payment_id);
            $status = $paymentData['status'] ?? 'PENDING';

            // Se já foi pago, atualiza e retorna
            if (in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])) {
                $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
                $assinatura->save();
                Response::success(['hasPending' => false, 'paid' => true]);
                return;
            }

            // Se foi cancelado ou expirou, limpa e retorna
            if (in_array($status, ['OVERDUE', 'REFUNDED', 'DELETED', 'REFUND_REQUESTED'])) {
                $assinatura->delete();
                Response::success(['hasPending' => false, 'expired' => true]);
                return;
            }

            // PIX ainda pendente - buscar QR Code
            $pixData = $this->asaas->getPixQrCode($assinatura->external_payment_id);

            if (empty($pixData['encodedImage'])) {
                // QR Code não disponível, algo deu errado
                Response::success(['hasPending' => false]);
                return;
            }

            // Calcular informações do plano
            $plano = $this->getPlanoPro();

            Response::success([
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
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Verificar status de um pagamento (para polling do frontend)
     */
    public function checkPayment(string $paymentId): void
    {
        $this->requireAuthApi();

        try {
            $usuario = $this->getAuthenticatedUser();

            // Buscar assinatura do usuário com este payment
            $assinatura = $usuario->assinaturas()
                ->where('external_payment_id', $paymentId)
                ->first();

            if (!$assinatura) {
                Response::error('Pagamento não encontrado.', 404);
                return;
            }

            // Verificar status no Asaas
            $paymentData = $this->asaas->getPayment($paymentId);
            $status = $paymentData['status'] ?? 'PENDING';

            $paid = in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH']);

            if ($paid && $assinatura->status !== AssinaturaUsuario::ST_ACTIVE) {
                $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
                $assinatura->save();

                LogService::info('Pagamento confirmado via polling', [
                    'user_id' => $usuario->id,
                    'payment_id' => $paymentId,
                    'status' => $status,
                ]);
            }

            Response::success([
                'paid' => $paid,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Cancelar assinatura PRO
     */
    public function cancel(): void
    {
        $this->requireAuthApi();

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

                // Só tenta cancelar no Asaas se for uma assinatura do gateway Asaas
                if ($assinatura->gateway === 'asaas' && $assinatura->external_subscription_id) {
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
        if (!$userId) {
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
        // Primeiro, limpar assinaturas PENDING sem pagamento (tentativas que falharam antes de gerar pagamento)
        $usuario->assinaturas()
            ->where('gateway', 'asaas')
            ->where('status', AssinaturaUsuario::ST_PENDING)
            ->whereNull('external_payment_id')
            ->whereNull('external_subscription_id')
            ->delete();

        // Verificar se existe assinatura ativa ou em andamento
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

        // ──────────────────────────────────────────────────────────────
        // STATUS ACTIVE — verificar se está genuinamente ativo ou vencido
        // ──────────────────────────────────────────────────────────────
        if ($existingSubscription->status === AssinaturaUsuario::ST_ACTIVE) {
            $renewsAt = $existingSubscription->renova_em
                ? \Carbon\Carbon::parse($existingSubscription->renova_em)
                : null;

            // Se renova_em já passou, está em período de carência ou expirado
            // → permitir renovação expirando a assinatura antiga
            if ($renewsAt && $renewsAt->isPast()) {
                $this->expireOldSubscription($existingSubscription);
                return;
            }

            // Genuinamente ativa e dentro do prazo
            throw new \RuntimeException('Você já possui uma assinatura ativa.');
        }

        // ──────────────────────────────────────────────────────────────
        // STATUS PENDING — PIX/Boleto aguardando → permitir trocar método
        // ──────────────────────────────────────────────────────────────
        if (
            $existingSubscription->status === AssinaturaUsuario::ST_PENDING
            && in_array($existingSubscription->billing_type, ['PIX', 'BOLETO'])
        ) {
            $existingSubscription->delete();
            return;
        }

        // ──────────────────────────────────────────────────────────────
        // STATUS PAST_DUE — pagamento falhou → permitir nova tentativa
        // ──────────────────────────────────────────────────────────────
        if ($existingSubscription->status === AssinaturaUsuario::ST_PAST_DUE) {
            $this->expireOldSubscription($existingSubscription);
            return;
        }

        throw new \RuntimeException('Você já possui uma assinatura em andamento.');
    }

    /**
     * Expira uma assinatura antiga para permitir renovação.
     * Cancela no Asaas se houver subscription vinculada.
     */
    private function expireOldSubscription(AssinaturaUsuario $subscription): void
    {
        // Cancelar no Asaas se for assinatura de cartão recorrente
        if ($subscription->gateway === 'asaas' && $subscription->external_subscription_id) {
            try {
                $this->asaas->cancelSubscription($subscription->external_subscription_id);
            } catch (\Throwable $e) {
                // Não falhar — assinatura pode já estar cancelada no Asaas
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

            // ========================================================================
            // APLICAR CUPOM DE DESCONTO
            // ========================================================================
            $cupomAplicado = null;
            $valorOriginal = $total;

            if ($dto->couponCode) {
                $cupom = \Application\Models\Cupom::findByCodigo($dto->couponCode);

                if (!$cupom) {
                    throw new \RuntimeException('Cupom não encontrado.');
                }

                if (!$cupom->isValid()) {
                    throw new \RuntimeException('Cupom inválido ou expirado.');
                }

                // Verificar se o usuário já usou este cupom
                $jaUsou = \Application\Models\CupomUsado::where('cupom_id', $cupom->id)
                    ->where('usuario_id', $usuario->id)
                    ->exists();

                if ($jaUsou) {
                    throw new \RuntimeException('Você já utilizou este cupom anteriormente.');
                }

                // Verificar se o cupom é apenas para primeira assinatura
                if ($cupom->apenas_primeira_assinatura ?? true) {
                    // Verificar elegibilidade do usuário para usar o cupom
                    $this->validarElegibilidadeCupom($usuario, $cupom);
                }

                // Calcular desconto do cupom
                $descontoCupom = $cupom->calcularDesconto($total);
                $total = $cupom->aplicarDesconto($total);

                $cupomAplicado = [
                    'cupom' => $cupom,
                    'desconto' => $descontoCupom,
                    'valor_original' => $valorOriginal,
                    'valor_final' => $total
                ];

                error_log("✨ [CHECKOUT] Cupom aplicado: {$cupom->codigo} - Desconto: R$ {$descontoCupom}");
            }

            // PIX e Boleto: criar pagamento avulso primeiro
            // Cartão: criar subscription diretamente (cobrança recorrente automática)
            if ($dto->isPix() || $dto->isBoleto()) {
                $result = $this->createPixOrBoletoPayment($usuario, $plano, $dto, $total);
            } else {
                // Para cartão de crédito, passamos o valor original e o desconto separadamente
                // para que o desconto seja aplicado apenas na primeira cobrança
                $result = $this->createSubscription($usuario, $plano, $dto, $customerData, $valorMensal, $valorOriginal, $cupomAplicado);
            }

            $assinatura = $this->saveAssinatura($usuario, $plano, $result, $dto->billingType);

            // ========================================================================
            // REGISTRAR USO DO CUPOM
            // ========================================================================
            if ($cupomAplicado) {
                $cupom = $cupomAplicado['cupom'];

                // Incrementar uso do cupom
                $cupom->incrementarUso();

                // Registrar no histórico
                \Application\Models\CupomUsado::create([
                    'cupom_id' => $cupom->id,
                    'usuario_id' => $usuario->id,
                    'assinatura_id' => $assinatura->id,
                    'desconto_aplicado' => $cupomAplicado['desconto'],
                    'valor_original' => $cupomAplicado['valor_original'],
                    'valor_final' => $cupomAplicado['valor_final'],
                    'usado_em' => now()
                ]);

                error_log("✅ [CHECKOUT] Uso do cupom registrado no histórico");
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

            // Adicionar informações do cupom na resposta
            if ($cupomAplicado) {
                $response['coupon_applied'] = [
                    'codigo' => $cupomAplicado['cupom']->codigo,
                    'desconto' => $cupomAplicado['desconto'],
                    'valor_original' => $cupomAplicado['valor_original']
                ];
            }

            // Adicionar dados específicos de PIX ou Boleto diretamente na response
            if (isset($result['pix'])) {
                $response['pix'] = $result['pix'];
            }

            if (isset($result['boleto'])) {
                $response['boleto'] = $result['boleto'];
            }

            return $response;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cria pagamento via PIX ou Boleto
     */
    private function createPixOrBoletoPayment(
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

        // 📌 PIX = hoje | Boleto = +3 dias
        $dueDate = $dto->isPix()
            ? date('Y-m-d')
            : date('Y-m-d', strtotime('+3 days'));

        // Criar pagamento avulso
        $builder = (new AsaasPaymentBuilder())
            ->forCustomer($usuario)
            ->withValue($total)
            ->withDescription($desc)
            ->withBillingType($dto->billingType)
            ->withDueDate($dueDate)
            ->withExternalReference("pay:user:{$usuario->id}:plano:{$plano->id}:m{$dto->months}");

        $resp = $this->asaas->createPayment($builder->build());

        $paymentId = $resp['id'] ?? null;
        $status    = $resp['status'] ?? 'PENDING';

        if (!$paymentId) {
            throw new \RuntimeException('Erro ao criar pagamento no Asaas.');
        }

        $result = [
            'asaas_id'     => $paymentId,
            'asaas_status' => $status,
            'status'       => AssinaturaUsuario::ST_PENDING,
            'renova_em'    => date('Y-m-d', strtotime(match ($dto->months) {
                6 => '+6 months',
                12 => '+1 year',
                default => '+1 month',
            })),
            'message' => $dto->isPix()
                ? 'PIX gerado com sucesso! Aguardando pagamento.'
                : 'Boleto gerado com sucesso! Aguardando pagamento.',
        ];

        error_log("✅ [CHECKOUT] Pagamento criado no Asaas: {$paymentId}, status: {$status}");

        // 🔥 PIX: buscar QR Code com retry (OBRIGATÓRIO)
        if ($dto->isPix()) {
            error_log("🔄 [CHECKOUT] Buscando QR Code PIX para pagamento: {$paymentId}");
            $pixData = null;

            // Aumentado para 8 tentativas com 500ms = até 4 segundos de espera
            for ($i = 0; $i < 8; $i++) {
                usleep(500000); // 500ms
                try {
                    $pixData = $this->asaas->getPixQrCode($paymentId);
                    error_log("🔄 [CHECKOUT] Tentativa " . ($i + 1) . " - encodedImage: " . (!empty($pixData['encodedImage']) ? 'OK' : 'VAZIO'));

                    if (!empty($pixData['encodedImage'])) {
                        break;
                    }
                } catch (\Throwable $e) {
                    LogService::captureException($e, LogCategory::PAYMENT, [
                        'action' => 'pix_qrcode_retry',
                        'attempt' => $i + 1,
                        'payment_id' => $paymentId,
                    ], $usuario->id ?? null, LogLevel::WARNING);
                }
            }

            if (empty($pixData['encodedImage'])) {
                error_log("🔴 [CHECKOUT] QR Code não disponível após 8 tentativas");
                throw new \RuntimeException(
                    'PIX criado, mas o QR Code ainda não foi disponibilizado pelo gateway. Tente novamente em alguns segundos.'
                );
            }

            error_log("✅ [CHECKOUT] QR Code PIX obtido com sucesso!");

            $result['pix'] = [
                'qrCodeImage' => 'data:image/png;base64,' . $pixData['encodedImage'],
                'payload' => $pixData['payload'] ?? null,
                'expirationDate' => $pixData['expirationDate'] ?? null,
            ];
        }

        // 🧾 Boleto: buscar linha digitável com retry
        if ($dto->isBoleto()) {
            $boletoData = null;

            for ($i = 0; $i < 5; $i++) {
                usleep(500000); // 500ms
                try {
                    $boletoData = $this->asaas->getBoletoIdentificationField($paymentId);

                    if (!empty($boletoData['identificationField'])) {
                        break;
                    }
                } catch (\Throwable $e) {
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

            // Se não conseguiu obter a linha digitável E nem a URL do boleto, falha
            if (empty($result['boleto']['identificationField']) && empty($result['boleto']['bankSlipUrl'])) {
                error_log("🔴 [CHECKOUT] Dados do boleto não disponíveis após 5 tentativas");
                throw new \RuntimeException(
                    'Boleto criado, mas os dados de pagamento ainda não foram disponibilizados pelo gateway. Tente novamente em alguns segundos.'
                );
            }
        }

        return $result;
    }

    private function createSubscription(Usuario $usuario, Plano $plano, CheckoutRequestDTO $dto, $customerData, float $valorMensal, float $total, ?array $cupomAplicado = null): array
    {
        $cycle = SubscriptionCycle::fromMonths($dto->months);

        // Define descrição e valor baseado no período
        $desc = match ($dto->months) {
            6 => $plano->nome . ' (Semestral -10%)',
            12 => $plano->nome . ' (Anual -15%)',
            default => $plano->nome,
        };

        // Para semestral e anual, cobra o valor total do período
        // Para mensal, cobra o valor mensal
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

        // Se há cupom de desconto, aplicar apenas na primeira cobrança
        // Para planos mensais: desconto na primeira fatura, depois volta ao valor integral
        // Para planos semestrais/anuais: desconto já está aplicado em $total (cobrança única por período)
        if ($cupomAplicado && $dto->isMensal()) {
            $cupom = $cupomAplicado['cupom'];
            $tipoDesconto = $cupom->tipo_desconto === 'percentual' ? 'PERCENTAGE' : 'FIXED';
            $valorDesconto = (float) $cupom->valor_desconto;

            // dueDateLimitDays = 0 significa aplicar sempre (na primeira cobrança)
            $builder->withDiscount($valorDesconto, $tipoDesconto, 0);

            error_log("✨ [CHECKOUT] Desconto configurado no Asaas: {$valorDesconto} ({$tipoDesconto}) - apenas primeira cobrança");
        }

        if ($dto->hasCreditCard()) {
            $builder->withCreditCard($dto->creditCard, $customerData);
        }

        $resp = $this->asaas->createSubscription($builder->build());
        $status = $resp['status'] ?? 'PENDING';

        // Calcula próxima renovação baseada no ciclo
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

    private function saveAssinatura(Usuario $usuario, Plano $plano, array $result, string $billingType = 'CREDIT_CARD'): AssinaturaUsuario
    {
        $data = [
            'user_id' => $usuario->id,
            'plano_id' => $plano->id,
            'gateway' => 'asaas',
            'external_customer_id' => $usuario->external_customer_id,
            'status' => $result['status'],
            'renova_em' => $result['renova_em'],
            'billing_type' => $billingType,
        ];

        // Para cartão: external_subscription_id
        // Para PIX/Boleto: external_payment_id (pagamento avulso)
        if ($billingType === 'CREDIT_CARD') {
            $data['external_subscription_id'] = $result['asaas_id'];
        } else {
            $data['external_payment_id'] = $result['asaas_id'];
        }

        $assinatura = new AssinaturaUsuario($data);
        $assinatura->save();

        return $assinatura;
    }

    private function getActiveSubscription(Usuario $usuario): ?AssinaturaUsuario
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

    private function logCancellation(Usuario $usuario, AssinaturaUsuario $assinatura): void
    {
        LogService::info('Assinatura cancelada', [
            'user_id' => $usuario->id,
            'assinatura_id' => $assinatura->id,
        ]);
    }

    private function handleCheckoutError(\Throwable $e): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        LogService::captureException($e, LogCategory::PAYMENT, [
            'action' => 'checkout',
            'userId' => $this->userId,
        ], $this->userId);

        Response::error($e->getMessage() ?: 'Não foi possível concluir o checkout.');
    }

    private function handleCancelError(\Throwable $e): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        LogService::captureException($e, LogCategory::SUBSCRIPTION, [
            'action' => 'cancel_subscription',
            'userId' => $this->userId,
        ], $this->userId);

        Response::error('Erro interno no servidor.', 500);
    }

    /**
     * Salva dados do checkout (CPF, telefone, CEP) no perfil do usuário
     * e verifica conquista de perfil completo
     */
    private function saveCheckoutDataToProfile(Usuario $usuario, CheckoutRequestDTO $dto): void
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

            // Verificar conquista de perfil completo
            $achievementService = new AchievementService();
            $achievementService->checkAndUnlockAchievements($usuario->id, 'checkout_profile_save');

            LogService::info('Dados do checkout salvos no perfil', ['user_id' => $usuario->id]);
        } catch (\Throwable $e) {
            // Não falhar o checkout por causa disso
            LogService::captureException($e, LogCategory::PAYMENT, [
                'action' => 'save_checkout_profile',
                'user_id' => $usuario->id,
            ], $usuario->id, LogLevel::WARNING);
        }
    }

    /**
     * Valida se o usuário é elegível para usar um cupom de primeira assinatura
     * 
     * Regras:
     * - Assinaturas que nunca foram pagas (status pending) não contam
     * - Se permite_reativacao = true, ex-assinantes inativos há X meses podem usar
     */
    private function validarElegibilidadeCupom(Usuario $usuario, \Application\Models\Cupom $cupom): void
    {
        // Buscar assinaturas que realmente foram pagas (active, canceled, expired, past_due)
        // Ignorar pending pois nunca chegaram a pagar
        $assinaturasEfetivas = $usuario->assinaturas()
            ->where('gateway', 'asaas')
            ->whereIn('status', [
                AssinaturaUsuario::ST_ACTIVE,
                AssinaturaUsuario::ST_CANCELED,
                AssinaturaUsuario::ST_EXPIRED,
                AssinaturaUsuario::ST_PAST_DUE,
                AssinaturaUsuario::ST_PAUSED,
            ]);

        // Se não tem nenhuma assinatura efetiva, é novo cliente - pode usar o cupom
        if (!$assinaturasEfetivas->exists()) {
            return;
        }

        // Usuário já foi assinante pago
        // Verificar se o cupom permite reativação
        if ($cupom->permite_reativacao ?? false) {
            $mesesInatividade = $cupom->meses_inatividade_reativacao ?? 3;

            // Verificar se tem assinatura ativa atualmente
            $temAssinaturaAtiva = $usuario->assinaturas()
                ->where('gateway', 'asaas')
                ->where('status', AssinaturaUsuario::ST_ACTIVE)
                ->exists();

            if ($temAssinaturaAtiva) {
                throw new \RuntimeException('Você já possui uma assinatura ativa.');
            }

            // Verificar última assinatura (mais recente)
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
                    // Ex-assinante inativo há tempo suficiente - pode usar o cupom de win-back
                    error_log("✨ [CUPOM] Win-back: usuário {$usuario->id} inativo há {$mesesDesdeInativacao} meses (mínimo: {$mesesInatividade})");
                    return;
                }

                throw new \RuntimeException(
                    "Este cupom é válido para ex-assinantes inativos há pelo menos {$mesesInatividade} meses. " .
                        "Você está inativo há apenas {$mesesDesdeInativacao} meses."
                );
            }
        }

        // Cupom não permite reativação e usuário já foi assinante
        throw new \RuntimeException('Este cupom é válido apenas para a primeira assinatura.');
    }
}
