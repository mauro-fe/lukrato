<?php

namespace Application\Services\Auth;

use Application\Services\Auth\LoginHandler;
use Application\Services\Auth\RegistrationHandler;
use Application\Services\Auth\LogoutHandler;
use Application\Services\CacheService;
use Application\DTOs\Auth\CredentialsDTO;
use Application\DTOs\Auth\RegistrationDTO;
use Application\Core\Request;
use Application\Services\LogService; // Importante: Adicionar o LogService
use Throwable; // Importante: Adicionar Throwable

class AuthService
{
    private LoginHandler $loginHandler;
    private RegistrationHandler $registrationHandler;
    private LogoutHandler $logoutHandler;

    public function __construct(
        ?Request $request = null,
        ?CacheService $cache = null
    ) {
        $request ??= new Request();
        $this->loginHandler = new LoginHandler($request, $cache);
        $this->registrationHandler = new RegistrationHandler();
        $this->logoutHandler = new LogoutHandler();
    }

    public function login(string $email, string $password): array
    {
        // Opcional: Logar tentativa de login também
        // LogService::info('[AuthService] Tentativa de login', ['email' => $email]);

        try {
            $credentials = new CredentialsDTO($email, $password);
            return $this->loginHandler->handle($credentials);
        } catch (Throwable $e) {
            // Se quiser logar erros de login aqui também:
            // LogService::error('[AuthService] Erro no login', ['msg' => $e->getMessage()]);
            throw $e;
        }
    }

    public function register(array $data): array
    {
        // 1. LOG DE ENTRADA
        LogService::info('[AuthService] Iniciando delegação de registro.', [
            'email' => $data['email'] ?? 'não-informado'
        ]);

        try {
            // A conversão do DTO pode falhar se faltar dados
            $registration = RegistrationDTO::fromRequest($data);

            // A execução do Handler pode falhar (banco, regras, etc)
            $result = $this->registrationHandler->handle($registration);

            // Log de sucesso interno
            LogService::info('[AuthService] Handler de registro finalizado com sucesso.');

            return $result;
        } catch (Throwable $e) {
            // 2. LOG DE ERRO REAL (AQUI É O PULO DO GATO)
            // Esse log vai pegar erros dentro do RegistrationHandler ou do DTO
            LogService::error('[AuthService] Exceção capturada no processo de registro.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // 3. Relança para o Controller decidir como mostrar para o usuário
            throw $e;
        }
    }

    public function logout(): array
    {
        return $this->logoutHandler->handle();
    }
}
