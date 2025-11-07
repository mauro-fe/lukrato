<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\LogService;
use Application\Services\MercadoPagoService;
use Illuminate\Database\Capsule\Manager as DB;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use Throwable;

class WebhookMercadoPagoController extends BaseController
{
    public function handle(): void
    {
        // 1) Credencial
        try {
            MercadoPagoService::configureSdk();
        } catch (\Throwable $e) {
            Response::error('MP access token ausente', 500);
            return;
        }

        // 2) Coleta bruta (para log)
        $raw     = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true) ?? [];

        // 3) MP pode mandar em querystring (topic/id) ou JSON (type/data.id)
        $type = $payload['type'] ?? $payload['action'] ?? ($_GET['topic'] ?? '');
        $id   = $payload['data']['id'] ?? ($_GET['id'] ?? null);

        // 4) Logs de auditoria do webhook
        LogService::info('Webhook MP recebido', [
            'headers' => function_exists('getallheaders') ? getallheaders() : [],
            'query'   => $_GET,
            'payload' => $payload,
        ]);

        try {
            if ($type && stripos($type, 'payment') !== false && $id) {
                $client  = new PaymentClient();
                $payment = $client->get($id); // Consulta oficial

                $meta   = (array)($payment->metadata ?? []);
                $userId = isset($meta['user_id']) ? (int)$meta['user_id'] : (int)($payment->metadata->user_id ?? 0);

                if (!$userId && !empty($payment->external_reference)) {
                    if (preg_match('/user_(\d+)/', (string)$payment->external_reference, $m)) {
                        $userId = (int)$m[1];
                    }
                }

                if ($payment->status === 'approved' && $userId > 0) {
                    DB::connection()->transaction(function () use ($userId, $payment) {
                        /** @var Usuario|null $user */
                        $user = Usuario::find($userId);
                        if (!$user) {
                            throw new \RuntimeException('Usuário não encontrado para o pagamento ' . $payment->id);
                        }

                        $renovaEm = $user->plano_renova_em ? strtotime($user->plano_renova_em) : 0;
                        if ($user->plano === 'pro' && $renovaEm > time()) {
                            LogService::info('Webhook MP ignorado (já PRO vigente)', [
                                'user_id'    => $user->id,
                                'payment_id' => $payment->id,
                            ]);
                            return;
                        }

                        $user->plano = 'pro';
                        $user->plano_renova_em = date('Y-m-d H:i:s', strtotime('+30 days'));
                        $user->save();

                        // TODO: persistir pagamento em tabela própria
                    });
                }
            }

            // 200 OK — evita reentrega
            Response::success(['received' => true]);
        } catch (MPApiException $e) {
            LogService::error('Erro MP API no webhook', [
                'status'  => $e->getApiResponse()->getStatusCode(),
                'content' => $e->getApiResponse()->getContent(),
            ]);
            Response::error('Erro no webhook (MP)', 500);
        } catch (Throwable $e) {
            LogService::error('Exceção no webhook MP', ['ex' => $e->getMessage()]);
            Response::error('Erro no webhook: ' . $e->getMessage(), 500);
        }
    }
}
