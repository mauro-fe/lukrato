<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\MercadoPagoService;
use Application\Lib\Auth;
use Exception;

class MercadoPagoController extends BaseController
{
    public function createCheckout(): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();
            if (!$user) {
                Response::unauthorized('Usuário não autenticado');
                return;
            }

            $mp = new MercadoPagoService();
            $pref = $mp->createCheckoutPreference([
                'user_id'  => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'name'     => $user->nome ?? $user->username,
                'amount'   => 12.00,
                'title'    => 'Assinatura Pro Lukrato',
            ]);

            Response::success([
                'preference_id' => $pref['id'],
                'init_point' => $pref['init_point'],
            ]);
        } catch (Exception $e) {
            Response::error('Falha ao criar checkout: ' . $e->getMessage(), 500);
        }
    }
}
