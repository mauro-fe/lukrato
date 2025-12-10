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

            // ---------------------------------------------------------------------
            // BUSCA DADOS COMPLEMENTARES NO BANCO (CPF, TELEFONE, ENDEREÇO)
            // ---------------------------------------------------------------------

            // CPF (tabela documentos, tipo = 1 = CPF)
            $cpf = \Illuminate\Database\Capsule\Manager::table('documentos')
                ->where('id_usuario', $usuario->id)
                ->where('id_tipo', 1) // 1 = CPF
                ->value('numero');

            $cpf = $cpf ? preg_replace('/\D+/', '', $cpf) : null;

            // Telefone (telefones + ddd)
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

            // Endereço principal (relacionamento já existe no model Usuario)
            $endereco = $usuario->enderecoPrincipal; // tabela enderecos (tipo = 'principal')

            $postalCode        = null;
            $addressNumber     = null;
            $addressComplement = null;

            if ($endereco) {
                $postalCode        = $endereco->cep ? preg_replace('/\D+/', '', $endereco->cep) : null;
                $addressNumber     = $endereco->numero ?: null;
                $addressComplement = $endereco->complemento ?: null;
            }

            // ---------------------------------------------------------------------
            // GARANTE CLIENTE NO ASAAS (COM CPF, TELEFONE, CEP...)
            // ---------------------------------------------------------------------
            $customerPayload = [
                'name'              => $usuario->nome,
                'email'             => $usuario->email,
                'externalReference' => 'user:' . $usuario->id,
            ];

            if ($cpf) {
                $customerPayload['cpfCnpj'] = $cpf;
            }
            if ($mobilePhone) {
                $customerPayload['mobilePhone'] = $mobilePhone;
            }
            if ($postalCode) {
                $customerPayload['postalCode']    = $postalCode;
                $customerPayload['addressNumber'] = $addressNumber ?: 'S/N';
                if ($addressComplement) {
                    $customerPayload['addressComplement'] = $addressComplement;
                }
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

            // ---------------------------------------------------------------------
            // DADOS VINDOS DO FRONT (tipo de cobrança + cartão)
            // ---------------------------------------------------------------------
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $billingType = $body['billingType'] ?? 'CREDIT_CARD';
            $creditCard  = $body['creditCard']  ?? null;
            $holderInfo  = $body['creditCardHolderInfo'] ?? [];

            $valorMensal = $planoPro->preco_centavos / 100;

            $subscriptionData = [
                'customerId'        => $usuario->external_customer_id,
                'value'             => $valorMensal,
                'description'       => $planoPro->nome,
                'billingType'       => $billingType,
                'cycle'             => 'MONTHLY',
                'nextDueDate'       => date('Y-m-d'),
                'externalReference' => 'sub:user:' . $usuario->id . ':plano:' . $planoPro->id,
            ];

            if ($billingType === 'CREDIT_CARD' && $creditCard) {
                $holderInfoData = [
                    'name'  => $holderInfo['name']  ?? $usuario->nome,
                    'email' => $holderInfo['email'] ?? $usuario->email,
                ];

                if ($cpf) {
                    $holderInfoData['cpfCnpj'] = $cpf;
                }
                if ($postalCode) {
                    $holderInfoData['postalCode']    = $postalCode;
                    $holderInfoData['addressNumber'] = $addressNumber ?: 'S/N';
                    if ($addressComplement) {
                        $holderInfoData['addressComplement'] = $addressComplement;
                    }
                }
                if ($mobilePhone) {
                    $holderInfoData['mobilePhone'] = $mobilePhone;
                }

                // Preenche no array que será enviado para o AsaasService
                $subscriptionData['creditCard']           = $creditCard;
                $subscriptionData['creditCardHolderInfo'] = $holderInfoData;
                $subscriptionData['remoteIp']             = $_SERVER['REMOTE_ADDR'] ?? null;
            }



            // Cria assinatura no Asaas
            $asaasSub = $this->asaas->createSubscription($subscriptionData);

            // Cria registro local da assinatura
            // Status retornado pelo Asaas (ACTIVE, PENDING, EXPIRED, SUSPENDED, CANCELED...)
            $asaasStatus = $asaasSub['status'] ?? null;

            // Converte para o status interno do seu sistema
            $internalStatus = match ($asaasStatus) {
                'ACTIVE'    => AssinaturaUsuario::ST_ACTIVE,
                'PENDING'   => AssinaturaUsuario::ST_PENDING,
                'EXPIRED',
                'SUSPENDED' => AssinaturaUsuario::ST_PAST_DUE,
                'CANCELED'  => AssinaturaUsuario::ST_CANCELED,
                default     => AssinaturaUsuario::ST_PENDING,
            };

            // Salva no banco
            $assinatura = new AssinaturaUsuario([
                'user_id'                  => $usuario->id,
                'plano_id'                 => $planoPro->id,
                'gateway'                  => 'asaas',
                'external_customer_id'     => $usuario->external_customer_id,
                'external_subscription_id' => $asaasSub['id'] ?? null,
                'status'                   => $internalStatus,
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
