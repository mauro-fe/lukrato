<?php

declare(strict_types=1);
// ========================================
// 5. APPLICATION/SERVICES/AUTH/ (REFATORADOS)
// ========================================

// Application/Services/Auth/LoginHandler.php
namespace Application\Services\Auth;

use Application\Container\ApplicationContainer;
use Application\Contracts\Auth\AuthHandlerInterface;
use Application\DTO\Auth\CredentialsDTO;
use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogCategory;
use Application\Lib\Helpers;
use Application\Models\Usuario;
use Throwable;

class LoginHandler implements AuthHandlerInterface
{
    private Request $request;
    private CredentialsValidationStrategy $validationStrategy;
    private SessionManager $sessionManager;
    private AbstractSecurityCheck $csrfCheck;
    private AbstractSecurityCheck $rateLimitCheck;

    public function __construct(
        ?Request $request = null,
        ?CacheService $cache = null,
        ?CredentialsValidationStrategy $validationStrategy = null,
        ?SessionManager $sessionManager = null,
        ?AbstractSecurityCheck $csrfCheck = null,
        ?AbstractSecurityCheck $rateLimitCheck = null
    ) {
        $this->request = ApplicationContainer::resolveOrNew($request, Request::class);
        $this->validationStrategy = ApplicationContainer::resolveOrNew(
            $validationStrategy,
            CredentialsValidationStrategy::class
        );
        $this->sessionManager = ApplicationContainer::resolveOrNew($sessionManager, SessionManager::class);
        $this->csrfCheck = ApplicationContainer::resolveOrNew($csrfCheck, CsrfSecurityCheck::class);
        $this->rateLimitCheck = ApplicationContainer::resolveOrNew($rateLimitCheck, RateLimitSecurityCheck::class);
    }

    public function handle(CredentialsDTO $credentials, bool $remember = false): array
    {
        try {
            LogService::info('[LOGIN_HANDLER DEBUG] Início handle', ['email' => $credentials->email]);

            $this->validationStrategy->validate($credentials);
            LogService::info('[LOGIN_HANDLER DEBUG] Validação OK');

            $user = Usuario::authenticate(
                $credentials->email,
                $credentials->password
            );

            LogService::info('[LOGIN_HANDLER DEBUG] Usuario::authenticate retornou', [
                'user_found' => $user !== null,
                'user_id' => $user ? $user->id : null
            ]);

            if (!$user) {
                throw new ValidationException(
                    ['credentials' => 'E-mail ou senha inválidos.'],
                    'Credenciais inválidas'
                );
            }

            LogService::info('[LOGIN_HANDLER DEBUG] Criando sessão');
            $this->sessionManager->createSession($user, $remember);

            LogService::info('Login success', [
                'user_id' => $user->id,
                'ip' => $this->request->ip(),
                'remember' => $remember
            ]);

            return [
                'redirect' => Helpers::baseUrl('dashboard')
            ];
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                LogService::warning($e->getMessage() !== '' ? $e->getMessage() : 'Falha de validação no login', [
                    'action' => 'login_handler',
                    'email' => $credentials->email,
                    'errors' => $e->getErrors(),
                ]);
            } else {
                LogService::captureException($e, LogCategory::AUTH, [
                    'action' => 'login_handler',
                    'email' => $credentials->email,
                ]);
            }
            throw $e;
        }
    }


    private function findUser(string $email)
    {
        return \Application\Models\Usuario::whereRaw('LOWER(email) = ?', [$email])->first();
    }
}
