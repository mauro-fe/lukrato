<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\Infrastructure\LogService;
use Exception;

class GoogleLoginController extends WebController
{
    private GoogleAuthService $googleAuthService;

    public function __construct(?GoogleAuthService $googleAuthService = null)
    {
        parent::__construct();
        $this->googleAuthService = $googleAuthService ?? new GoogleAuthService();
    }

    public function login(): Response
    {
        if ($this->isAuthenticated()) {
            return $this->buildRedirectResponse('dashboard');
        }

        try {
            $ref = $_GET['ref'] ?? '';
            if ($ref !== '') {
                $_SESSION['pending_referral_code'] = strtoupper(trim($ref));
            }

            $authUrl = $this->googleAuthService->getAuthUrl();

            LogService::info('Iniciando login com Google OAuth', [
                'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? null,
                'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? null,
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
