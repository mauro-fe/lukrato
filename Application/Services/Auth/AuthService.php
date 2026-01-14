<?php

namespace Application\Services\Auth;

use Application\Services\Auth\LoginHandler;
use Application\Services\Auth\RegistrationHandler;
use Application\Services\Auth\LogoutHandler;
use Application\Services\CacheService;
use Application\DTO\Auth\CredentialsDTO;
use Application\DTO\Auth\RegistrationDTO;
use Application\Core\Request;
use Application\Services\LogService;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;
use Application\Models\Usuario;
use Application\Models\Categoria;
use Carbon\Carbon;


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
                $this->criarCategoriasPadrao((int) $result['user_id']);
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
            $user = Usuario::find($userId);

            if (!$user) {
                LogService::warning('[AuthService] Usuário não encontrado ao criar assinatura padrão.', [
                    'user_id' => $userId,
                ]);
                return;
            }

            $plano = Plano::where('code', 'free')->first();

            if (!$plano) {
                LogService::warning('[AuthService] Plano FREE não encontrado ao criar assinatura padrão.', [
                    'user_id' => $userId,
                ]);
                return;
            }

            $user->assinaturas()->create([
                'plano_id'                 => $plano->id,
                'gateway'                  => 'interno',
                'external_customer_id'     => null,
                'external_subscription_id' => null,
                'status'                   => AssinaturaUsuario::ST_ACTIVE,
                'renova_em'                => Carbon::now()->addMonth(),
                'cancelada_em'             => null,
            ]);

            LogService::info('[AuthService] Assinatura padrão criada com sucesso.', [
                'user_id'  => $userId,
                'plano_id' => $plano->id,
                'code'     => $plano->code,
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

    /**
     * Cria categorias padrão para o novo usuário
     */
    private function criarCategoriasPadrao(int $userId): void
    {
        try {
            $user = Usuario::find($userId);

            if (!$user) {
                LogService::warning('[AuthService] Usuário não encontrado ao criar categorias padrão.', [
                    'user_id' => $userId,
                ]);
                return;
            }

            // Categorias de Despesa
            $categoriasDespesa = [
                '🏠 Moradia',
                '🍔 Alimentação',
                '🚗 Transporte',
                '💡 Contas e Serviços',
                '🏥 Saúde',
                '🎓 Educação',
                '👕 Vestuário',
                '🎬 Lazer',
                '💳 Cartão de Crédito',
                '📱 Assinaturas',
                '🛒 Compras',
                '💰 Outros Gastos',
            ];

            // Categorias de Receita
            $categoriasReceita = [
                '💼 Salário',
                '💰 Freelance',
                '📈 Investimentos',
                '🎁 Bônus',
                '💸 Vendas',
                '🏆 Prêmios',
                '💵 Outras Receitas',
            ];

            $criadas = 0;

            // Criar despesas
            foreach ($categoriasDespesa as $nome) {
                Categoria::create([
                    'nome' => $nome,
                    'tipo' => 'despesa',
                    'user_id' => $userId,
                ]);
                $criadas++;
            }

            // Criar receitas
            foreach ($categoriasReceita as $nome) {
                Categoria::create([
                    'nome' => $nome,
                    'tipo' => 'receita',
                    'user_id' => $userId,
                ]);
                $criadas++;
            }

            LogService::info('[AuthService] Categorias padrão criadas com sucesso.', [
                'user_id' => $userId,
                'total_criadas' => $criadas,
            ]);
        } catch (Throwable $e) {
            LogService::error('[AuthService] Erro ao criar categorias padrão.', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }
}
