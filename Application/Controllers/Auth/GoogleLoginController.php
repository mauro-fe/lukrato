<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\Infrastructure\LogService;
use Exception;

class GoogleLoginController extends WebController
{
    private GoogleAuthService $googleAuthService;
    private AuthRuntimeConfig $runtimeConfig;

    public function __construct(
        ?GoogleAuthService $googleAuthService = null,
        ?AuthRuntimeConfig $runtimeConfig = null
    )
    {
        parent::__construct();
        $this->googleAuthService = $this->resolveOrCreate($googleAuthService, GoogleAuthService::class);
        $this->runtimeConfig = $this->resolveOrCreate($runtimeConfig, AuthRuntimeConfig::class);
    }

    public function login(): Response
    {
        if ($this->isAuthenticated()) {
            return $this->buildRedirectResponse('dashboard');
        }

        try {
            $ref = $this->request->queryString('ref');
            if ($ref !== '') {
                $_SESSION['pending_referral_code'] = strtoupper(trim($ref));
            }

            $authUrl = $this->googleAuthService->getAuthUrl();
            $clientId = $this->runtimeConfig->googleClientId();
            $redirectUri = $this->runtimeConfig->googleRedirectUri();

            LogService::info('Iniciando login com Google OAuth', [
                'client_id' => $clientId !== '' ? $clientId : null,
                'redirect_uri' => $redirectUri !== '' ? $redirectUri : null,
                'auth_url' => $authUrl,
            ]);

            return Response::redirectResponse($authUrl);
        } catch (Exception $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'google_login_init',
            ]);

            $this->setError('Não foi possível conectar ao Google. Tente novamente.');
            return $this->buildRedirectResponse('login');
        }
    }
}
