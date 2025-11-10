<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\PagarmeService;
use Application\Models\AssinaturaUsuario;

// class BillingController extends BaseController
// {
//     // Application/Controllers/Api/BillingController.php
//     // Application/Controllers/Api/BillingController.php
//     public function createCheckout(): void
//     {
//         $this->requireAuthApi(); // <- em API, não redirecione

//         $user = Auth::user();
//         if (!$user) {
//             Response::unauthorized('Usuário não autenticado');
//             return;
//         }

//         $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
//         $cardToken = $input['card_token'] ?? null;
//         if (!$cardToken) {
//             Response::validationError(['card_token' => 'Token do cartão é obrigatório']);
//             return;
//         }

//         $planId = $_ENV['PAGARME_PLAN_ID'] ?? null;
//         if (!$planId) {
//             Response::error('Plano da assinatura não configurado (PAGARME_PLAN_ID).', 500);
//             return;
//         }

//         try {
//             $service = new PagarmeService();

//             // Application/Controllers/Api/BillingController.php (createCheckout)
//             $result = $service->createPlanSubscription([
//                 'id'    => $user->id,
//                 'name'  => $user->nome ?? $user->username ?? 'Usuário',
//                 'email' => $user->email,
//                 'pagarme_customer_id' => $user->pagarme_cliente_id,
//             ], $planId, ['card_token' => $cardToken]);

//             // Atualiza/guarda o customer_id, se veio
//             if (!empty($result['customer_id']) && empty($user->pagarme_cliente_id)) {
//                 $user->pagarme_cliente_id = $result['customer_id'];
//                 $user->gateway = 'pagarme';
//                 $user->save();
//             }

//             // 🔴 NOVO: gravar o id da assinatura no usuário
//             if (!empty($result['subscription_id'])) {
//                 $user->pagarme_assinatura_id = $result['subscription_id'];
//                 $user->save();
//             }

//             // 🔴 IMPORTANTE: Criar (ou atualizar) a assinatura local como "pending"
//             $ass = AssinaturaUsuario::updateOrCreate(
//                 [
//                     'user_id' => $user->id,
//                     'external_subscription_id' => $result['subscription_id'] ?? null,
//                 ],
//                 [
//                     'gateway'               => 'pagarme',
//                     'external_customer_id'  => $result['customer_id'] ?? $user->pagarme_cliente_id,
//                     'status'                => 'pending',
//                 ]
//             );

//             Response::success([
//                 'subscription_id' => $result['subscription_id'] ?? null,
//                 'message' => 'Assinatura criada. Após confirmação pelo webhook, seu plano ficará PRO.'
//             ]);
//         } catch (\Throwable $e) {
//             Response::error('Falha ao criar assinatura: ' . $e->getMessage(), 500);
//         }
//     }
// }
