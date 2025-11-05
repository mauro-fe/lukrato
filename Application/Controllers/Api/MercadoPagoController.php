<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\MercadoPagoService;
use Application\Services\LogService;
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

            $env = strtolower(trim($_ENV['MP_ENV'] ?? 'production'));
            $isSandbox = ($env === 'sandbox'); // não depende mais do prefixo do token

            $checkoutUrl = $isSandbox
                ? ($pref['sandbox_init_point'] ?? $pref['init_point'])
                : $pref['init_point'];

            // (log opcional)
            LogService::info('Checkout URL escolhido', [
                'env' => $env,
                'checkoutUrl' => $checkoutUrl
            ]);

            Response::success([
                'preference_id' => $pref['id'],
                'init_point'    => $checkoutUrl,
                'env'           => $env,
            ]);
        } catch (Exception $e) {
            Response::error('Falha ao criar checkout: ' . $e->getMessage(), 500);
        }
    }
    
}