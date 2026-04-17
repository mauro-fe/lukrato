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
use Throwable;

class GoogleCallbackController extends WebController
{
    private GoogleAuthService $googleAuthService;
    private AuthRuntimeConfig $runtimeConfig;

    public function __construct(
        ?GoogleAuthService $googleAuthService = null,
        ?AuthRuntimeConfig $runtimeConfig = null
    ) {
        parent::__construct();
        $this->googleAuthService = $this->resolveOrCreate($googleAuthService, GoogleAuthService::class);
        $this->runtimeConfig = $this->resolveOrCreate($runtimeConfig, AuthRuntimeConfig::class);
    }

    public function callback(): Response
    {
        try {
            $callbackUrl = $this->runtimeConfig->googleCallbackUrl();

            LogService::info('Google callback iniciado', [
                'query_params' => $_GET,
                'env_redirect_uri' => $this->runtimeConfig->googleRedirectUri() !== '' ? $this->runtimeConfig->googleRedirectUri() : 'NAO DEFINIDO',
                'callback_url' => $callbackUrl,
                'base_url' => BASE_URL,
            ]);

            if ($this->isAuthenticated()) {
                return $this->buildRedirectResponse($this->runtimeConfig->dashboardUrl());
            }

            $this->validateCallbackParams();
            $code = $this->getQuery('code');

            LogService::info('Processando callback do Google', [
                'redirect_uri' => $callbackUrl,
                'base_url' => BASE_URL,
                'code_received' => !empty($code),
            ]);

            $result = $this->googleAuthService->handleCallback($code);

            if ($result['needs_confirmation'] ?? false) {
                $_SESSION['google_pending_user'] = $result['user_info'];
                return $this->buildRedirectResponse($this->runtimeConfig->googleConfirmPageUrl());
            }

            $usuario = $result['usuario'];
            $isNew = $result['is_new'];

            $userInfo = [
                'id' => $usuario->google_id,
                'email' => $usuario->email,
                'picture' => $_SESSION['google_user_picture'] ?? null,
            ];

            $this->googleAuthService->loginUser($usuario, $userInfo);

            $intended = $_SESSION['login_intended'] ?? '';
            unset($_SESSION['login_intended']);

            if ($isNew) {
                return $this->buildRedirectResponse($this->runtimeConfig->welcomeUrl());
            }

            if ($intended !== '') {
                return $this->buildRedirectResponse(
                    $this->runtimeConfig->intendedUrl($intended, $this->runtimeConfig->dashboardUrl())
                );
            }

            return $this->buildRedirectResponse($this->runtimeConfig->dashboardUrl());
        } catch (Exception $e) {
            return $this->handleCallbackError($e);
        } catch (Throwable $e) {
            return $this->handleCallbackError($e);
        }
    }

    public function pending(): Response
    {
        $pendingUser = $this->pendingGoogleUser();
        if ($pendingUser === null) {
            return $this->fail('Nenhum cadastro Google pendente encontrado.', 404);
        }

        return $this->ok([
            'message' => 'Cadastro Google pendente encontrado.',
            'pending_user' => $pendingUser,
            'actions' => [
                'confirm_url' => $this->runtimeConfig->googleConfirmUrl(),
                'cancel_url' => $this->runtimeConfig->googleCancelUrl(),
            ],
        ]);
    }

    public function confirmPage(): Response
    {
        if ($this->isAuthenticated()) {
            return $this->buildRedirectResponse($this->runtimeConfig->dashboardUrl());
        }

        if (!$this->pendingGoogleUser()) {
            return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
        }

        if ($this->runtimeConfig->hasConfiguredGoogleConfirmPageUrl()) {
            return $this->buildRedirectResponse($this->runtimeConfig->googleConfirmPageUrl());
        }

        return $this->renderResponse('site/auth/google-confirm', [
            'googleLoginUrl' => $this->runtimeConfig->loginUrl(),
        ]);
    }

    public function confirm(): Response
    {
        try {
            if ($this->isAuthenticated()) {
                return $this->respondWithRedirectOrJson(
                    $this->runtimeConfig->dashboardUrl(),
                    'Você já está autenticado.'
                );
            }

            $pendingUser = $this->pendingGoogleUser();
            if (!$pendingUser) {
                if ($this->request->isAjax()) {
                    return $this->fail('Sessão expirada. Tente novamente.', 410, [
                        'redirect' => $this->runtimeConfig->loginUrl(),
                    ]);
                }

                $this->setError('Sessão expirada. Tente novamente.');
                return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
            }

            $referralCode = $_SESSION['pending_referral_code'] ?? '';
            $usuario = $this->googleAuthService->createUserFromPending($pendingUser, $referralCode);

            unset($_SESSION['google_pending_user'], $_SESSION['pending_referral_code']);

            $userInfo = [
                'id' => $usuario->google_id,
                'email' => $usuario->email,
                'picture' => $pendingUser['picture'] ?? null,
            ];
            $this->googleAuthService->loginUser($usuario, $userInfo);

            unset($_SESSION['login_intended']);

            LogService::info('Conta criada via Google após confirmação', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
            ]);

            return $this->respondWithRedirectOrJson(
                $this->runtimeConfig->welcomeUrl(),
                'Conta criada via Google com sucesso!'
            );
        } catch (Exception $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'google_confirm_account',
            ]);
            $message = $this->internalErrorMessage(
                $e,
                'Erro ao criar conta com Google. Tente novamente.',
                ['action' => 'google_confirm_account']
            );

            if ($this->request->isAjax()) {
                return $this->fail($message, 500, [
                    'redirect' => $this->runtimeConfig->loginUrl(),
                ]);
            }

            $this->setError($message);

            return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
        }
    }

    public function cancel(): Response
    {
        unset($_SESSION['google_pending_user']);

        if ($this->request->isAjax()) {
            return $this->ok([
                'message' => 'Cadastro cancelado.',
                'redirect' => $this->runtimeConfig->loginUrl(),
            ]);
        }

        $this->setError('Cadastro cancelado.');

        return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
    }

    /**
     * @throws Exception
     */
    private function validateCallbackParams(): void
    {
        $error = $this->getQuery('error');

        if ($error) {
            $errorDescription = $this->getQuery('error_description', 'Erro desconhecido');

            LogService::error('Erro no callback do Google - OAuth error', [
                'error' => $error,
                'error_description' => $errorDescription,
            ]);

            throw new Exception("Erro na autenticação com Google: {$error}");
        }

        $code = $this->getQuery('code');

        if (!$code) {
            throw new Exception('Nenhum código de autorização recebido');
        }
    }

    private function handleCallbackError(Throwable $e): Response
    {
        LogService::captureException($e, LogCategory::AUTH, [
            'action' => 'google_callback',
            'code' => $this->getQuery('code', 'N/A'),
        ]);

        $this->setError($this->internalErrorMessage(
            $e,
            'Erro ao processar login com Google. Tente novamente.',
            ['action' => 'google_callback']
        ));

        return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
    }

    private function respondWithRedirectOrJson(string $redirectUrl, string $message): Response
    {
        if ($this->request->isAjax()) {
            return $this->ok([
                'message' => $message,
                'redirect' => $redirectUrl,
            ]);
        }

        return $this->buildRedirectResponse($redirectUrl);
    }

    /**
     * @return array{name:string,email:string,picture:?string}|null
     */
    private function pendingGoogleUser(): ?array
    {
        $pendingUser = $_SESSION['google_pending_user'] ?? null;
        if (!is_array($pendingUser) || $pendingUser === []) {
            return null;
        }

        return [
            'name' => (string) ($pendingUser['name'] ?? ''),
            'email' => (string) ($pendingUser['email'] ?? ''),
            'picture' => isset($pendingUser['picture']) && $pendingUser['picture'] !== ''
                ? (string) $pendingUser['picture']
                : null,
        ];
    }
}
