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

            // Já tem assinatura ativa ou pendente com Asaas?
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

            // Busca plano PRO
            /** @var Plano $planoPro */
            $planoPro = Plano::where('code', 'pro')
                ->where('ativo', 1)
                ->first();

            if (!$planoPro) {
                Response::error('Plano PRO não está configurado. Contate o suporte.');
                return;
            }

            // Garante cliente no Asaas
            if (empty($usuario->external_customer_id) || $usuario->gateway !== 'asaas') {
                $customer = $this->asaas->createCustomer([
                    'name'              => $usuario->nome,
                    'email'             => $usuario->email,
                    // Se um dia adicionar telefone/cpf:
                    // 'mobilePhone'       => $usuario->telefone ?? null,
                    // 'cpfCnpj'           => $usuario->cpf ?? null,
                    'externalReference' => 'user:' . $usuario->id,
                ]);

                $usuario->external_customer_id = $customer['id'] ?? null;
                $usuario->gateway              = 'asaas';
                $usuario->save();
            }

            if (empty($usuario->external_customer_id)) {
                Response::error('Falha ao criar cliente no Asaas. Tente novamente.');
                return;
            }

            // Dados do front
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $billingType = $body['billingType'] ?? 'CREDIT_CARD';
            $creditCard  = $body['creditCard']  ?? null;
            $holderInfo  = $body['creditCardHolderInfo'] ?? null;

            $valorMensal = $planoPro->preco_centavos / 100;

            $subscriptionPayload = [
                'customerId'        => $usuario->external_customer_id,
                'value'             => $valorMensal,
                'description'       => $planoPro->nome,
                'billingType'       => $billingType,
                'cycle'             => 'MONTHLY',
                'nextDueDate'       => date('Y-m-d'),
                'externalReference' => 'sub:user:' . $usuario->id . ':plano:' . $planoPro->id,
            ];

            if ($billingType === 'CREDIT_CARD' && $creditCard && $holderInfo) {
                $subscriptionPayload['creditCard']           = $creditCard;
                $subscriptionPayload['creditCardHolderInfo'] = $holderInfo;
            }

            $asaasSub = $this->asaas->createSubscription($subscriptionPayload);

            // Cria registro local
            $assinatura = new AssinaturaUsuario([
                'user_id'                  => $usuario->id,
                'plano_id'                 => $planoPro->id,
                'gateway'                  => 'asaas',
                'external_customer_id'     => $usuario->external_customer_id,
                'external_subscription_id' => $asaasSub['id'] ?? null,
                'status'                   => AssinaturaUsuario::ST_PENDING,
                'renova_em'                => $asaasSub['nextDueDate'] ?? null,
            ]);

            $assinatura->save();

            Response::success([
                'message'         => 'Assinatura criada. Aguarde a confirmação do pagamento.',
                'subscription_id' => $asaasSub['id'] ?? null,
                'asaas_status'    => $asaasSub['status'] ?? null,
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
