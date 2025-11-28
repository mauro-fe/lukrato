<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;
use Application\Services\LogService;
use Application\Services\MercadoPagoService;
use Illuminate\Database\Capsule\Manager as DB;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use Throwable;
use Carbon\Carbon;

enum PaymentStatus: string
{
    case APPROVED = 'approved';
}

enum UserPlan: string
{
    case PRO = 'pro';
    case FREE = 'free';
}

class WebhookMercadoPagoController extends BaseController
{
    private function extractUserIdFromPayment(\MercadoPago\Resources\Payment $payment): ?int
    {
        if (isset($payment->metadata)) {
            if (is_object($payment->metadata) && isset($payment->metadata->user_id)) {
                return (int)$payment->metadata->user_id;
            }
            if (is_array($payment->metadata) && isset($payment->metadata['user_id'])) {
                return (int)$payment->metadata['user_id'];
            }
        }

        if (!empty($payment->external_reference)) {
            if (preg_match('/user_(\d+)/', (string)$payment->external_reference, $matches)) {
                return (int)$matches[1];
            }
        }

        return null;
    }


    public function handle(): void
    {
        try {
            MercadoPagoService::configureSdk();
        } catch (Throwable $e) {
            Response::error('MP access token ausente', 500);
            return;
        }

        $raw     = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true) ?? [];

        $type = (string)($payload['type'] ?? $payload['action'] ?? ($_GET['topic'] ?? ''));
        $id   = (string)($payload['data']['id'] ?? ($_GET['id'] ?? null));

        LogService::info('Webhook MP recebido', [
            'headers' => function_exists('getallheaders') ? getallheaders() : [],
            'query'   => $_GET,
            'payload' => $payload,
        ]);

        LogService::info('Webhook MP parsed', [
            'type' => $type,
            'id'   => $id,
        ]);

        try {
            if ($id !== '' && str_contains(strtolower($type), 'payment')) {
                
                $client  = new PaymentClient();
                $payment = $client->get($id);

                $userId = $this->extractUserIdFromPayment($payment);
                LogService::info('Webhook MP payment carregado', [
                    'payment_id' => $payment->id,
                    'status'     => $payment->status,
                    'user_id'    => $userId,
                    'ext_ref'    => $payment->external_reference ?? null,
                    'metadata'   => $payment->metadata ?? null,
                ]);

                if ($payment->status === PaymentStatus::APPROVED->value && $userId !== null) {
                    $planoPro = Plano::where('code', UserPlan::PRO->value)->first();
                    
                    DB::connection()->transaction(function () use ($userId, $payment, $planoPro) {
                        $user = Usuario::find($userId);
                        if (!$user) {
                            throw new \RuntimeException('Usuário não encontrado para o pagamento ' . $payment->id);
                        }

                        $renovaEm = $user->plano_renova_em ? strtotime($user->plano_renova_em) : 0;
                        if ($user->plano === UserPlan::PRO->value && $renovaEm > time()) {
                            LogService::info('Webhook MP ignorado (usuário já possui plano PRO vigente)', [
                                'user_id'    => $user->id,
                                'payment_id' => $payment->id,
                            ]);
                            return; 
                        }

                        $renovaEm = Carbon::now()->addDays(30);

                        if ($planoPro) {
                            // Atualiza ou cria assinatura local para refletir o pagamento aprovado
                            AssinaturaUsuario::updateOrCreate(
                                [
                                    'user_id'  => $user->id,
                                    'gateway'  => 'mercadopago',
                                ],
                                [
                                    'plano_id'                => $planoPro->id,
                                    'status'                  => AssinaturaUsuario::ST_ACTIVE,
                                    'renova_em'               => $renovaEm,
                                    'external_subscription_id'=> (string)$payment->id,
                                ]
                            );
                            LogService::info('Webhook MP assinatura gravada', [
                                'user_id' => $user->id,
                                'plano_id'=> $planoPro->id,
                                'renova_em' => $renovaEm->toDateTimeString(),
                            ]);
                        } else {
                            LogService::warning('Plano PRO não encontrado na tabela planos', [
                                'user_id' => $user->id,
                                'payment_id' => $payment->id,
                            ]);
                        }

                        $user->plano = UserPlan::PRO->value;
                        $user->plano_renova_em = $renovaEm->toDateTimeString();
                        $user->gateway = 'mercadopago';
                        $user->save();
                        LogService::info('Webhook MP usuário atualizado para PRO', [
                            'user_id' => $user->id,
                            'renova_em' => $user->plano_renova_em,
                        ]);
                        
                    });
                }
            }

            Response::success(['received' => true]);

        } catch (MPApiException $e) {
            $apiResponse = $e->getApiResponse();
            $statusCode  = $apiResponse?->getStatusCode() ?? 400;
            $content     = $apiResponse?->getContent();

            LogService::error('Erro MPApiException no webhook', [
                'status'  => $statusCode,
                'content' => $content,
            ]);
            Response::error('Erro no webhook (MP)', 500); 

        } catch (Throwable $e) {
            LogService::error('Exceção no webhook MP', ['ex' => $e->getMessage()]);
            Response::error('Erro no processamento do webhook: ' . $e->getMessage(), 500);
        }
    }
}
