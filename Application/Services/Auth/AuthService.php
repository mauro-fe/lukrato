<?php

namespace Application\Services\Auth;

use Application\Services\Auth\LoginHandler;
use Application\Services\Auth\RegistrationHandler;
use Application\Services\Auth\LogoutHandler;
use Application\Services\CacheService;
use Application\DTOs\Auth\CredentialsDTO;
use Application\DTOs\Auth\RegistrationDTO;
use Application\Core\Request;
use Application\Services\LogService;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;
use Throwable;

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
        $this->loginHandler       = new LoginHandler($request, $cache);
        $this->registrationHandler = new RegistrationHandler();
        $this->logoutHandler      = new LogoutHandler();
    }

    public function login(string $email, string $password): array
    {
        try {
            $credentials = new CredentialsDTO($email, $password);
            return $this->loginHandler->handle($credentials);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function register(array $data): array
    {
        LogService::info('[AuthService] Iniciando delegação de registro.', [
            'email' => $data['email'] ?? 'não-informado'
        ]);

        try {
            $registration = RegistrationDTO::fromRequest($data);

            $result = $this->registrationHandler->handle($registration);

            if (!empty($result['user_id'])) {
                $this->criarAssinaturaPadrao((int) $result['user_id']);
            } else {
                LogService::warning('[AuthService] Registro retornou sem user_id, não foi possível criar assinatura.', [
                    'email' => $data['email'] ?? 'não-informado'
                ]);
            }

            LogService::info('[AuthService] Handler de registro + assinatura finalizados com sucesso.', [
                'user_id' => $result['user_id'] ?? null,
            ]);

            return $result;
        } catch (Throwable $e) {
            LogService::error('[AuthService] Exceção capturada no processo de registro.', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function logout(): array
    {
        return $this->logoutHandler->handle();
    }

    private function criarAssinaturaPadrao(int $userId): void
    {
        try {

            $plano = Plano::where('slug', 'free')->first();

            if (!$plano) {
                LogService::warning('[AuthService] Plano FREE não encontrado ao criar assinatura padrão.', [
                    'user_id' => $userId,
                ]);
                return;
            }

            AssinaturaUsuario::create([
                'user_id'                  => $userId,
                'plano_id'                 => $plano->id,
                'gateway'                  => 'interno', 
                'external_customer_id'     => null,
                'external_subscription_id' => null,
                'status'                   => AssinaturaUsuario::ST_ACTIVE,
                'renova_em'                => null, 
                'cancelada_em'             => null,
            ]);

            LogService::info('[AuthService] Assinatura padrão criada com sucesso.', [
                'user_id' => $userId,
                'plano_id' => $plano->id,
            ]);
        } catch (Throwable $e) {
            LogService::error('[AuthService] Erro ao criar assinatura padrão.', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }
}
