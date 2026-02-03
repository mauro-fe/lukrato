<?php

namespace Application\Services\Auth;

use Application\Services\Auth\LoginHandler;
use Application\Services\Auth\RegistrationHandler;
use Application\Services\Auth\LogoutHandler;
use Application\Services\CacheService;
use Application\Services\MailService;
use Application\Services\ReferralService;
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

                // Processa código de indicação se fornecido
                $referralCode = trim($data['referral_code'] ?? '');
                if (!empty($referralCode)) {
                    $this->processarIndicacao((int) $result['user_id'], $referralCode);
                }

                // Envia email de boas-vindas (em background para não bloquear)
                $this->enviarEmailBoasVindas((int) $result['user_id'], $data['email'] ?? '', $data['name'] ?? '');
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

            // Criar despesas (sem acionar gamificação)
            foreach ($categoriasDespesa as $nome) {
                Categoria::create([
                    'nome' => $nome,
                    'tipo' => 'despesa',
                    'user_id' => $userId,
                    'is_auto_seed' => true, // Flag para não dar pontos
                ]);
                $criadas++;
            }

            // Criar receitas (sem acionar gamificação)
            foreach ($categoriasReceita as $nome) {
                Categoria::create([
                    'nome' => $nome,
                    'tipo' => 'receita',
                    'user_id' => $userId,
                    'is_auto_seed' => true, // Flag para não dar pontos
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

    /**
     * Envia email de boas-vindas para o novo usuário.
     * 
     * Não lança exceção para não interromper o fluxo de registro.
     */
    private function enviarEmailBoasVindas(int $userId, string $email, string $name): void
    {
        try {
            $mailService = new MailService();

            if (!$mailService->isConfigured()) {
                LogService::warning('[AuthService] Email de boas-vindas não enviado: SMTP não configurado.', [
                    'user_id' => $userId,
                ]);
                return;
            }

            // Se não temos o nome, tenta buscar do banco
            if (empty($name)) {
                $user = Usuario::find($userId);
                $name = $user?->nome ?? 'Usuário';
            }

            $sent = $mailService->sendWelcomeEmail($email, $name);

            if ($sent) {
                LogService::info('[AuthService] Email de boas-vindas enviado com sucesso.', [
                    'user_id' => $userId,
                    'email' => $email,
                ]);
            } else {
                LogService::warning('[AuthService] Email de boas-vindas não foi enviado (retorno false).', [
                    'user_id' => $userId,
                    'email' => $email,
                ]);
            }
        } catch (Throwable $e) {
            // Não propaga erro - email de boas-vindas não deve impedir o registro
            LogService::error('[AuthService] Erro ao enviar email de boas-vindas.', [
                'user_id' => $userId,
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Processa a indicação de um novo usuário.
     * 
     * Não lança exceção para não interromper o fluxo de registro.
     */
    private function processarIndicacao(int $userId, string $referralCode): void
    {
        try {
            $user = Usuario::find($userId);

            if (!$user) {
                LogService::warning('[AuthService] Usuário não encontrado ao processar indicação.', [
                    'user_id' => $userId,
                    'referral_code' => $referralCode,
                ]);
                return;
            }

            $referralService = new ReferralService();

            // Garante que o novo usuário tenha seu próprio código de indicação
            $referralService->ensureUserHasReferralCode($user);

            // Processa a indicação
            $indicacao = $referralService->processReferral($user, $referralCode);

            if ($indicacao) {
                LogService::info('[AuthService] Indicação processada com sucesso.', [
                    'user_id' => $userId,
                    'referrer_id' => $indicacao->referrer_id,
                    'referral_code' => $referralCode,
                    'referrer_reward_days' => $indicacao->referrer_reward_days,
                    'referred_reward_days' => $indicacao->referred_reward_days,
                ]);
            } else {
                LogService::warning('[AuthService] Indicação não foi processada (código inválido ou já indicado).', [
                    'user_id' => $userId,
                    'referral_code' => $referralCode,
                ]);
            }
        } catch (Throwable $e) {
            // Não propaga erro - indicação não deve impedir o registro
            LogService::error('[AuthService] Erro ao processar indicação.', [
                'user_id' => $userId,
                'referral_code' => $referralCode,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
