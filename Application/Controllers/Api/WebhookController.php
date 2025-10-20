<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\LogWebhookCobranca;
use Application\Services\PagarmeService;
use Application\Models\AssinaturaUsuario;


class WebhookController extends BaseController
{
    public function pagarme(): void
    {
        $raw     = file_get_contents('php://input') ?: '';
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $service = new PagarmeService();

        // Validação de assinatura (em produção, exija)
        if (!$service->isValidWebhook($headers, $raw)) {
            Response::forbidden('Assinatura do webhook inválida');
            return;
        }

        $payload = json_decode($raw, true) ?? [];
        $event   = $payload['type'] ?? ($payload['event'] ?? '');
        $data    = $payload['data'] ?? ($payload['object'] ?? []);

        // Log não-bloqueante
        try {
            LogWebhookCobranca::create([
                'provedor'    => 'pagarme',
                'tipo_evento' => (string) $event,
                'payload'     => $payload,
            ]);
        } catch (\Throwable $e) {
        }

        // Helpers locais
        $parseRenewAt = function (array $d): ?string {
            $candidates = [
                $d['period_end']          ?? null,
                $d['current_period_end']  ?? null,
                $d['next_billing_at']     ?? null,
                $d['next_charge_at']      ?? null,
            ];
            foreach ($candidates as $v) {
                if (!$v) continue;
                $ts = is_numeric($v) ? (int) $v : strtotime((string) $v);
                if ($ts) return date('Y-m-d H:i:s', $ts);
            }
            return null;
        };

        $findUser = function (array $d): ?Usuario {
            $customerId     = $d['customer_id']     ?? ($d['customer']['id'] ?? null);
            $subscriptionId = $d['subscription_id'] ?? ($d['subscription']['id'] ?? null);

            if ($customerId) {
                $u = Usuario::where('pagarme_cliente_id', $customerId)->first();
                if ($u) return $u;
            }
            if ($subscriptionId) {
                $u = Usuario::where('pagarme_assinatura_id', $subscriptionId)->first();
                if ($u) return $u;
            }
            return null;
        };

        try {
            switch ($event) {
                // ===== Pagou/ativou assinatura → PRO =====
                // dentro do switch ($event)
                case 'invoice.paid':
                case 'subscription.paid': {
                        $customerId     = $data['customer_id'] ?? ($data['customer']['id'] ?? null);
                        $subscriptionId = $data['subscription_id'] ?? ($data['subscription']['id'] ?? null);

                        $periodEnd = $data['period_end'] ?? ($data['current_period_end'] ?? null);
                        $renovaEm = null;
                        if ($periodEnd) {
                            $ts = is_numeric($periodEnd) ? (int)$periodEnd : strtotime((string)$periodEnd);
                            if ($ts) $renovaEm = date('Y-m-d H:i:s', $ts);
                        }

                        $ass = \Application\Models\AssinaturaUsuario::where('external_subscription_id', $subscriptionId)
                            ->orWhere('external_customer_id', $customerId)
                            ->latest('id')
                            ->first();

                        if ($ass) {
                            // opcional: vincular o plano "pro" automaticamente
                            $planoPro = \Application\Models\Plano::where('code', 'pro')->first();
                            if ($planoPro && !$ass->plano_id) $ass->plano_id = $planoPro->id;

                            $ass->status = 'active';
                            $ass->renova_em = $renovaEm;
                            $ass->save();
                        }
                        break;
                    }

                case 'invoice.payment_failed':
                case 'subscription.canceled':
                case 'subscription.paused':
                case 'subscription.deleted': {
                        $customerId     = $data['customer_id'] ?? ($data['customer']['id'] ?? null);
                        $subscriptionId = $data['subscription_id'] ?? ($data['subscription']['id'] ?? null);

                        $ass = \Application\Models\AssinaturaUsuario::where('external_subscription_id', $subscriptionId)
                            ->orWhere('external_customer_id', $customerId)
                            ->latest('id')
                            ->first();

                        if ($ass) {
                            $ass->status = in_array($event, ['subscription.canceled', 'subscription.deleted'], true)
                                ? 'canceled' : 'past_due';
                            if ($ass->status === 'canceled' && !$ass->cancelada_em) {
                                $ass->cancelada_em = now(); // se tiver helper; senão date('Y-m-d H:i:s')
                            }
                            $ass->save();
                        }
                        break;
                    }


                    // ===== Falhou/cancelou/pausou → GRATUITO =====
                case 'invoice.payment_failed':
                case 'subscription.canceled':
                case 'subscription.paused':
                case 'subscription.deleted': {
                        $user = $findUser($data);
                        if ($user) {
                            // idempotente: só atualiza se necessário
                            if ($user->plano !== 'gratuito' || $user->plano_renova_em !== null) {
                                $user->plano = 'gratuito';
                                $user->plano_renova_em = null;
                                $user->save();
                            }
                        }
                        break;
                    }

                    // Outros eventos: apenas confirme o recebimento
                default:
                    // Sem ação
                    break;
            }

            Response::success(['received' => true]); // 200
        } catch (\Throwable $e) {
            Response::error('Erro ao processar webhook: ' . $e->getMessage(), 500);
        }
    }
}
