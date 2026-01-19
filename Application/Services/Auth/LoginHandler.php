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
use Application\Services\CacheService;
use Application\Services\LogService;
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
    public function handle(CredentialsDTO $credentials): array
    {
        try {
            $this->validationStrategy->validate($credentials);

            $user = Usuario::authenticate(
                $credentials->email,
                $credentials->password
            );

            if (!$user) {
                throw new ValidationException(
                    ['credentials' => 'E-mail ou senha inválidos.'],
                    'Credenciais inválidas'
                );
            }

            $this->sessionManager->createSession($user);

            LogService::info('Login success', [
                'user_id' => $user->id,
                'ip' => (new Request())->ip()
            ]);

            return [
                'redirect' => Helpers::baseUrl('dashboard')
            ];
        } catch (Throwable $e) {
            LogService::error('Login failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }


    private function findUser(string $email)
    {
        return \Application\Models\Usuario::whereRaw('LOWER(email) = ?', [$email])->first();
    }
}
