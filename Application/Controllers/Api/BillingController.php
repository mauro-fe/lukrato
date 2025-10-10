<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\PagarmeService;

class BillingController extends BaseController
{
    public function createCheckout(): void
    {
        $this->requireAuth();

        $user = Auth::user();
        if (!$user) {
            Response::unauthorized('Usuário não autenticado'); // 401
            return;
        }

        try {
            $service = new PagarmeService();

            $session = $service->createSubscriptionCheckout([
                'id'    => $user->id,
                'name'  => $user->nome ?? $user->username ?? 'Usuário',
                'email' => $user->email,
                'pagarme_customer_id' => $user->pagarme_cliente_id, // nome PT-BR do model/tabela
            ], $_ENV['BASE_URL'] . 'billing');

            // Salva o customer_id no primeiro retorno
            if (!empty($session['customer_id']) && empty($user->pagarme_cliente_id)) {
                $user->pagarme_cliente_id = $session['customer_id'];
                $user->gateway = 'pagarme';
                $user->save();
            }

            if (!empty($session['checkout_url'])) {
                Response::success(['checkout_url' => $session['checkout_url']]); // 200
                return;
            }

            Response::error('Não foi possível gerar o checkout', 400);
        } catch (\Throwable $e) {
            Response::error('Falha ao iniciar assinatura: ' . $e->getMessage(), 500);
        }
    }
}
