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
use Application\Services\AchievementService;
use Application\Validators\CheckoutValidator;
use Application\DTO\CheckoutRequestDTO;
use Application\Builders\AsaasPaymentBuilder;
use Application\Builders\AsaasSubscriptionBuilder;
use Application\Enums\SubscriptionCycle;
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

                if (class_exists(LogService::class)) {
                    LogService::info('Pagamento confirmado via polling', [
                        'user_id' => $usuario->id,
                        'payment_id' => $paymentId,
                        'status' => $status,
                    ]);
                }
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

        if ($existingSubscription) {
            // Se é uma assinatura ativa de verdade, bloqueia
            if ($existingSubscription->status === AssinaturaUsuario::ST_ACTIVE) {
                throw new \RuntimeException('Você já possui uma assinatura ativa.');
            }

            // Se é PENDING com PIX/Boleto aguardando, permite deletar e recriar
            // (usuário pode querer trocar método de pagamento)
            if (
                $existingSubscription->status === AssinaturaUsuario::ST_PENDING
                && in_array($existingSubscription->billing_type, ['PIX', 'BOLETO'])
            ) {
                // Deleta a assinatura pendente para permitir nova tentativa
                $existingSubscription->delete();
                return;
            }

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

            // PIX e Boleto: criar pagamento avulso primeiro
            // Cartão: criar subscription diretamente (cobrança recorrente automática)
            if ($dto->isPix() || $dto->isBoleto()) {
                $result = $this->createPixOrBoletoPayment($usuario, $plano, $dto, $total);
            } else {
                $result = $this->createSubscription($usuario, $plano, $dto, $customerData, $valorMensal, $total);
            }

            $this->saveAssinatura($usuario, $plano, $result, $dto->billingType);

            DB::commit();

            $response = [
                'message' => $result['message'],
                'asaas_id' => $result['asaas_id'],
                'asaas_status' => $result['asaas_status'],
                'months' => $dto->months,
                'discount' => $discount,
                'total' => $total,
            ];

            // Adicionar dados específicos de PIX ou Boleto
            if (isset($result['pix'])) {
                $response['data'] = [
                    'paymentId' => $result['asaas_id'],
                    'pix' => $result['pix'],
                ];
            }

            if (isset($result['boleto'])) {
                $response['data'] = [
                    'paymentId' => $result['asaas_id'],
                    'boleto' => $result['boleto'],
                ];
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
                    error_log("⚠️ [CHECKOUT] Tentativa " . ($i + 1) . " falhou: " . $e->getMessage());
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

        // 🧾 Boleto
        if ($dto->isBoleto()) {
            try {
                $boletoData = $this->asaas->getBoletoIdentificationField($paymentId);
                $result['boleto'] = [
                    'identificationField' => $boletoData['identificationField'] ?? null,
                    'nossoNumero' => $boletoData['nossoNumero'] ?? null,
                    'barCode' => $boletoData['barCode'] ?? null,
                    'bankSlipUrl' => $resp['bankSlipUrl'] ?? null,
                ];
            } catch (\Throwable $e) {
                LogService::error('Erro ao buscar dados do boleto', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
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

    private function saveAssinatura(Usuario $usuario, Plano $plano, array $result, string $billingType = 'CREDIT_CARD'): void
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

        error_log("🔴 [CHECKOUT] Erro: " . $e->getMessage());
        error_log("🔴 [CHECKOUT] Stack trace: " . $e->getTraceAsString());

        if (class_exists(LogService::class)) {
            LogService::error('Erro no checkout', [
                'userId' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        Response::error($e->getMessage() ?: 'Não foi possível concluir o checkout.');
    }

    private function handleCancelError(\Throwable $e): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        error_log("🔴 [CANCEL] Erro ao cancelar assinatura: " . $e->getMessage());
        error_log("🔴 [CANCEL] Stack trace: " . $e->getTraceAsString());

        if (class_exists(LogService::class)) {
            LogService::error('Erro ao cancelar', [
                'userId' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }

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
            ];

            $perfilService->salvarDadosCheckout($usuario->id, $dados);

            // Verificar conquista de perfil completo
            $achievementService = new AchievementService();
            $achievementService->checkAndUnlockAchievements($usuario->id, 'checkout_profile_save');

            error_log("✅ [CHECKOUT] Dados salvos no perfil do usuário {$usuario->id}");
        } catch (\Throwable $e) {
            // Não falhar o checkout por causa disso
            error_log("⚠️ [CHECKOUT] Erro ao salvar dados no perfil: " . $e->getMessage());
        }
    }
}
