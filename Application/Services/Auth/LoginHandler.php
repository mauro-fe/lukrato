<?php
// ========================================
// 5. APPLICATION/SERVICES/AUTH/ (REFATORADOS)
// ========================================

// Application/Services/Auth/LoginHandler.php
namespace Application\Services\Auth;

use Application\Contracts\Auth\AuthHandlerInterface;
use Application\DTO\Auth\CredentialsDTO;
use Application\DTO\Auth\LoginResultDTO;
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
    private CredentialsValidationStrategy $validationStrategy;
    private SessionManager $sessionManager;
    private AbstractSecurityCheck $csrfCheck;
    private AbstractSecurityCheck $rateLimitCheck;

    public function __construct(
        Request $request,
        ?CacheService $cache = null
    ) {
        $this->validationStrategy = new CredentialsValidationStrategy();
        $this->sessionManager = new SessionManager();
        $this->csrfCheck = new CsrfSecurityCheck($request, 'login_form');
        $this->rateLimitCheck = new RateLimitSecurityCheck($request, $cache, 'login');
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
                'ip' => (new Request())->ip(),
                'remember' => $remember
            ]);

            return [
                'redirect' => Helpers::baseUrl('dashboard')
            ];
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'login_handler',
                'email' => $credentials->email,
            ]);
            throw $e;
        }
    }


    private function findUser(string $email)
    {
        return \Application\Models\Usuario::whereRaw('LOWER(email) = ?', [$email])->first();
    }
}
