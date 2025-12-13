<?php

namespace Application\Controllers;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\Plano;
use Application\Models\AssinaturaUsuario;
use Application\Services\AsaasService;
use Application\Services\LogService;

class PremiumController extends BaseController
{
    private AsaasService $asaas;

    public function __construct()
    {
        parent::__construct();
        $this->asaas = new AsaasService();
    }

    /**
     * Inicia o checkout do plano PRO (assinatura Asaas).
     */
    public function checkout(): void
    {
        $this->requireAuth();

        try {
            $userId = $this->userId ?? $this->adminId ?? null;
            if (!$userId) {
                Response::error('Usuário não identificado na sessão.');
                return;
            }

            /** @var Usuario $usuario */
            $usuario = Usuario::findOrFail($userId);

            // Já tem assinatura ativa/pendente?
            $assinaturaExistente = $usuario->assinaturas()
                ->where('gateway', 'asaas')
                ->whereIn('status', [
                    AssinaturaUsuario::ST_ACTIVE,
                    AssinaturaUsuario::ST_PENDING,
                    AssinaturaUsuario::ST_PAST_DUE,
                ])
                ->latest('id')
                ->first();

            if ($assinaturaExistente) {
                Response::error('Você já possui uma assinatura em andamento.');
                return;
            }

            /** @var Plano $planoPro */
            $planoPro = Plano::where('code', 'pro')
                ->where('ativo', 1)
                ->first();

            if (!$planoPro) {
                Response::error('Plano PRO não está configurado. Contate o suporte.');
                return;
            }

            // BODY
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $billingType = $body['billingType'] ?? 'CREDIT_CARD';
            $creditCard  = $body['creditCard']  ?? null;
            $holderInfo  = $body['creditCardHolderInfo'] ?? [];

            // ============================
            // NOVOS CAMPOS (ciclo)
            // ============================
            $months   = (int)($body['months'] ?? 1);
            $discount = (int)($body['discount'] ?? 0);
            $cycleUI  = trim((string)($body['cycle'] ?? 'monthly')); // monthly|semiannual|annual

            // Valida months
            if (!in_array($months, [1, 6, 12], true)) {
                Response::error('Período inválido. Selecione mensal, semestral ou anual.');
                return;
            }

            // Desconto esperado
            $expectedDiscount = match ($months) {
                1 => 0,
                6 => 10,
                12 => 15,
                default => 0
            };

            if ($discount !== $expectedDiscount) {
                Response::error('Desconto inválido para o período selecionado.');
                return;
            }

            // Base mensal oficial (do banco)
            $valorMensal = $planoPro->preco_centavos / 100;
            if ($valorMensal <= 0) {
                Response::error('Preço do plano PRO inválido.');
                return;
            }

            // Total calculado no servidor
            $total = round($valorMensal * $months * (1 - ($expectedDiscount / 100)), 2);
            if ($total <= 0) {
                Response::error('Valor total inválido. Tente novamente.');
                return;
            }

            // ---------------------------------------------------------------------
            // BUSCA DADOS COMPLEMENTARES (CPF/TELEFONE/ENDEREÇO) - seu código
            // ---------------------------------------------------------------------
            $cpf = \Illuminate\Database\Capsule\Manager::table('documentos')
                ->where('id_usuario', $usuario->id)
                ->where('id_tipo', 1)
                ->value('numero');

            $cpf = $cpf ? preg_replace('/\D+/', '', $cpf) : null;

            $telefoneRow = \Illuminate\Database\Capsule\Manager::table('telefones as t')
                ->leftJoin('ddd as d', 'd.id_ddd', '=', 't.id_ddd')
                ->where('t.id_usuario', $usuario->id)
                ->orderBy('t.id_telefone')
                ->first();

            $mobilePhone = null;
            if ($telefoneRow) {
                $ddd  = preg_replace('/\D+/', '', (string)($telefoneRow->codigo ?? ''));
                $num  = preg_replace('/\D+/', '', (string)($telefoneRow->numero ?? ''));
                $fone = $ddd . $num;
                $mobilePhone = $fone !== '' ? $fone : null;
            }

            $endereco = $usuario->enderecoPrincipal;

            $postalCode        = null;
            $addressNumber     = null;
            $addressComplement = null;

            if ($endereco) {
                $postalCode        = $endereco->cep ? preg_replace('/\D+/', '', $endereco->cep) : null;
                $addressNumber     = $endereco->numero ?: null;
                $addressComplement = $endereco->complemento ?: null;
            }

            // ---------------------------------------------------------------------
            // GARANTE CUSTOMER NO ASAAS
            // ---------------------------------------------------------------------
            $customerPayload = [
                'name'              => $usuario->nome,
                'email'             => $usuario->email,
                'externalReference' => 'user:' . $usuario->id,
            ];

            if ($cpf) $customerPayload['cpfCnpj'] = $cpf;
            if ($mobilePhone) $customerPayload['mobilePhone'] = $mobilePhone;

            if ($postalCode) {
                $customerPayload['postalCode']    = $postalCode;
                $customerPayload['addressNumber'] = $addressNumber ?: 'S/N';
                if ($addressComplement) $customerPayload['addressComplement'] = $addressComplement;
            }

            if (empty($usuario->external_customer_id) || $usuario->gateway !== 'asaas') {
                $customer = $this->asaas->createCustomer($customerPayload);

                $usuario->external_customer_id = $customer['id'] ?? null;
                $usuario->gateway              = 'asaas';
                $usuario->save();
            }

            if (empty($usuario->external_customer_id)) {
                Response::error('Falha ao criar cliente no Asaas. Tente novamente.');
                return;
            }

            // HolderInfo final (prioriza o que você já tem)
            $holderInfoData = [
                'name'  => $holderInfo['name']  ?? $usuario->nome,
                'email' => $holderInfo['email'] ?? $usuario->email,
            ];

            if ($cpf) $holderInfoData['cpfCnpj'] = $cpf;
            if ($postalCode) {
                $holderInfoData['postalCode']    = $postalCode;
                $holderInfoData['addressNumber'] = $addressNumber ?: 'S/N';
                if ($addressComplement) $holderInfoData['addressComplement'] = $addressComplement;
            }
            if ($mobilePhone) $holderInfoData['mobilePhone'] = $mobilePhone;

            // ---------------------------------------------------------------------
            // DECISÃO:
            // - months=1 : assinatura MONTHLY
            // - months=12: assinatura YEARLY (valor anual já com desconto)
            // - months=6 : cobrança única (payment) com desconto
            // ---------------------------------------------------------------------
            $asaasResp = null;
            $internalStatus = AssinaturaUsuario::ST_PENDING;
            $renovaEm = null;

            if ($months === 6) {
                // Cobrança única (não existe ciclo semestral em assinatura padrão)
                $paymentData = [
                    'customer'          => $usuario->external_customer_id,
                    'billingType'       => $billingType,
                    'value'             => $total,
                    'dueDate'           => date('Y-m-d'),
                    'description'       => $planoPro->nome . ' (Semestral -10%)',
                    'externalReference' => 'pay:user:' . $usuario->id . ':plano:' . $planoPro->id . ':m6',
                ];

                if ($billingType === 'CREDIT_CARD' && $creditCard) {
                    $paymentData['creditCard'] = $creditCard;
                    $paymentData['creditCardHolderInfo'] = $holderInfoData;
                    $paymentData['remoteIp'] = $_SERVER['REMOTE_ADDR'] ?? null;
                }

                // ✅ precisa existir no seu AsaasService
                $asaasResp = $this->asaas->createPayment($paymentData);

                $asaasStatus = $asaasResp['status'] ?? null;
                $internalStatus = match ($asaasStatus) {
                    'CONFIRMED', 'RECEIVED' => AssinaturaUsuario::ST_ACTIVE,
                    'PENDING'              => AssinaturaUsuario::ST_PENDING,
                    default                => AssinaturaUsuario::ST_PENDING,
                };

                $renovaEm = date('Y-m-d', strtotime('+6 months'));
            } else {
                // Assinatura
                $cycle = ($months === 12) ? 'YEARLY' : 'MONTHLY';
                $desc  = $planoPro->nome . ($months === 12 ? ' (Anual -15%)' : '');

                $subscriptionData = [
                    'customerId'        => $usuario->external_customer_id,
                    'value'             => ($months === 12 ? $total : $valorMensal),
                    'description'       => $desc,
                    'billingType'       => $billingType,
                    'cycle'             => $cycle,
                    'nextDueDate'       => date('Y-m-d'),
                    'externalReference' => 'sub:user:' . $usuario->id . ':plano:' . $planoPro->id . ($months === 12 ? ':y1' : ':m1'),
                ];

                if ($billingType === 'CREDIT_CARD' && $creditCard) {
                    $subscriptionData['creditCard'] = $creditCard;
                    $subscriptionData['creditCardHolderInfo'] = $holderInfoData;
                    $subscriptionData['remoteIp'] = $_SERVER['REMOTE_ADDR'] ?? null;
                }

                $asaasResp = $this->asaas->createSubscription($subscriptionData);

                $asaasStatus = $asaasResp['status'] ?? null;
                $internalStatus = match ($asaasStatus) {
                    'ACTIVE'    => AssinaturaUsuario::ST_ACTIVE,
                    'PENDING'   => AssinaturaUsuario::ST_PENDING,
                    'EXPIRED',
                    'SUSPENDED' => AssinaturaUsuario::ST_PAST_DUE,
                    'CANCELED'  => AssinaturaUsuario::ST_CANCELED,
                    default     => AssinaturaUsuario::ST_PENDING,
                };

                $renovaEm = $asaasResp['nextDueDate'] ?? (($months === 12) ? date('Y-m-d', strtotime('+1 year')) : date('Y-m-d', strtotime('+1 month')));
            }

            // ---------------------------------------------------------------------
            // REGISTRO LOCAL
            // Observação: você só tem external_subscription_id no model.
            // Para semestral (payment), vamos salvar o ID do pagamento nesse mesmo campo.
            // Se quiser “perfeito”, depois criamos external_payment_id.
            // ---------------------------------------------------------------------
            $assinatura = new AssinaturaUsuario([
                'user_id'                  => $usuario->id,
                'plano_id'                 => $planoPro->id,
                'gateway'                  => 'asaas',
                'external_customer_id'     => $usuario->external_customer_id,
                'external_subscription_id' => $asaasResp['id'] ?? null,
                'status'                   => $internalStatus,
                'renova_em'                => $renovaEm,
            ]);
            $assinatura->save();

            Response::success([
                'message'      => ($months === 6)
                    ? 'Cobrança semestral criada. Aguarde a confirmação do pagamento.'
                    : 'Assinatura criada. Aguarde a confirmação do pagamento.',
                'asaas_id'     => $asaasResp['id'] ?? null,
                'asaas_status' => $asaasResp['status'] ?? null,
                'months'       => $months,
                'discount'     => $expectedDiscount,
                'total'        => $total,
            ]);
        } catch (\Throwable $e) {
            if (class_exists(LogService::class)) {
                LogService::error('Erro no checkout Asaas', [
                    'userId' => $this->userId ?? $this->adminId ?? null,
                    'error'  => $e->getMessage(),
                ]);
            }

            Response::error('Não foi possível concluir o checkout agora. Tente novamente mais tarde.');
        }
    }


    /**
     * Cancelar assinatura PRO do usuário logado.
     */
    public function cancel(): void
    {
        $this->requireAuth();

        try {
            $userId = $this->userId ?? $this->adminId ?? null;
            if (!$userId) {
                Response::error('Usuário não identificado na sessão.');
                return;
            }

            /** @var Usuario $usuario */
            $usuario = Usuario::findOrFail($userId);

            $assinatura = $usuario->assinaturas()
                ->where('gateway', 'asaas')
                ->whereIn('status', [
                    AssinaturaUsuario::ST_ACTIVE,
                    AssinaturaUsuario::ST_PENDING,
                    AssinaturaUsuario::ST_PAST_DUE,
                ])
                ->latest('id')
                ->first();

            if (!$assinatura) {
                Response::error('Nenhuma assinatura ativa encontrada para cancelar.');
                return;
            }

            if ($assinatura->external_subscription_id) {
                $this->asaas->cancelSubscription($assinatura->external_subscription_id);
            }

            $assinatura->status       = AssinaturaUsuario::ST_CANCELED;
            $assinatura->cancelada_em = now();
            $assinatura->save();

            Response::success(['message' => 'Assinatura cancelada com sucesso.']);
        } catch (\Throwable $e) {
            if (class_exists(LogService::class)) {
                LogService::error('Erro ao cancelar assinatura Asaas', [
                    'userId' => $this->userId ?? $this->adminId ?? null,
                    'error'  => $e->getMessage(),
                ]);
            }

            Response::error('Não foi possível cancelar agora. Tente novamente em instantes.');
        }
    }
}
