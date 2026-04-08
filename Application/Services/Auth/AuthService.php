<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Container\ApplicationContainer;
use Application\Services\Auth\LoginHandler;
use Application\Services\Auth\RegistrationHandler;
use Application\Services\Auth\LogoutHandler;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Referral\ReferralService;
use Application\DTO\Auth\CredentialsDTO;
use Application\DTO\Auth\RegistrationDTO;
use Application\Core\Request;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogCategory;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;
use Application\Models\Usuario;
use Application\Models\Categoria;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

use Throwable;

class AuthService
{
    private LoginHandler $loginHandler;
    private RegistrationHandler $registrationHandler;
    private LogoutHandler $logoutHandler;
    private ?EmailVerificationService $emailVerificationService = null;
    private ?ReferralService $referralService = null;

    public function __construct(
        ?Request $request = null,
        ?CacheService $cache = null,
        ?LoginHandler $loginHandler = null,
        ?RegistrationHandler $registrationHandler = null,
        ?LogoutHandler $logoutHandler = null
    ) {
        $resolvedRequest = ApplicationContainer::resolveOrNew($request, Request::class);

        $this->loginHandler = ApplicationContainer::resolveOrNew(
            $loginHandler,
            LoginHandler::class,
            fn(): LoginHandler => new LoginHandler($resolvedRequest, $cache)
        );
        $this->registrationHandler = ApplicationContainer::resolveOrNew(
            $registrationHandler,
            RegistrationHandler::class
        );
        $this->logoutHandler = ApplicationContainer::resolveOrNew(
            $logoutHandler,
            LogoutHandler::class,
            fn(): LogoutHandler => new LogoutHandler($resolvedRequest)
        );
    }

    public function login(string $email, string $password, bool $remember = false): array
    {
        try {
            $credentials = new CredentialsDTO($email, $password);
            return $this->loginHandler->handle($credentials, $remember);
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

                // Envia email de verificação (pula se registro via Google, pois o Google já verifica o email)
                if (empty($data['skip_email_verification'])) {
                    $this->enviarEmailBoasVindas((int) $result['user_id'], $data['email'] ?? '', $data['name'] ?? '');
                }
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
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'registro',
                'email' => $data['email'] ?? 'não-informado',
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
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'criar_assinatura_padrao',
                'user_id' => $userId,
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

            // Categorias de Despesa: [nome, ícone Lucide]
            $categoriasDespesa = [
                ['Moradia',           'house'],
                ['Alimentação',       'utensils'],
                ['Transporte',        'car'],
                ['Contas e Serviços', 'lightbulb'],
                ['Saúde',             'heart-pulse'],
                ['Educação',          'graduation-cap'],
                ['Vestuário',         'shirt'],
                ['Lazer',             'clapperboard'],
                ['Cartão de Crédito', 'credit-card'],
                ['Assinaturas',       'smartphone'],
                ['Compras',           'shopping-cart'],
                ['Outros Gastos',     'coins'],
            ];

            // Categorias de Receita: [nome, ícone Lucide]
            $categoriasReceita = [
                ['Salário',           'briefcase'],
                ['Freelance',         'laptop'],
                ['Investimentos',     'trending-up'],
                ['Bônus',             'gift'],
                ['Vendas',            'banknote'],
                ['Prêmios',           'trophy'],
                ['Outras Receitas',   'wallet'],
            ];

            // Mapa de subcategorias padrão por nome da categoria pai
            $subcategoriasPadrao = self::getSubcategoriasPadrao();

            // Envolver tudo em transação para garantir atomicidade
            DB::connection()->transaction(function () use ($userId, $categoriasDespesa, $categoriasReceita, $subcategoriasPadrao) {
                $criadas = 0;
                $subcriadas = 0;

                // ── Criar categorias raiz + subcategorias ──

                // Despesas
                foreach ($categoriasDespesa as [$nome, $icone]) {
                    $cat = Categoria::create([
                        'nome'      => $nome,
                        'icone'     => $icone,
                        'tipo'      => 'despesa',
                        'user_id'   => $userId,
                        'is_seeded' => true,
                    ]);
                    $criadas++;

                    // Criar subcategorias se houver para esta categoria
                    if (isset($subcategoriasPadrao[$nome])) {
                        foreach ($subcategoriasPadrao[$nome] as $sub) {
                            Categoria::create([
                                'nome'      => $sub['nome'],
                                'icone'     => $sub['icone'],
                                'tipo'      => 'despesa',
                                'user_id'   => $userId,
                                'parent_id' => $cat->id,
                                'is_seeded' => true,
                            ]);
                            $subcriadas++;
                        }
                    }
                }

                // Receitas
                foreach ($categoriasReceita as [$nome, $icone]) {
                    $cat = Categoria::create([
                        'nome'      => $nome,
                        'icone'     => $icone,
                        'tipo'      => 'receita',
                        'user_id'   => $userId,
                        'is_seeded' => true,
                    ]);
                    $criadas++;

                    // Criar subcategorias se houver para esta categoria
                    if (isset($subcategoriasPadrao[$nome])) {
                        foreach ($subcategoriasPadrao[$nome] as $sub) {
                            Categoria::create([
                                'nome'      => $sub['nome'],
                                'icone'     => $sub['icone'],
                                'tipo'      => 'receita',
                                'user_id'   => $userId,
                                'parent_id' => $cat->id,
                                'is_seeded' => true,
                            ]);
                            $subcriadas++;
                        }
                    }
                }

                LogService::info('[AuthService] Categorias e subcategorias padrão criadas com sucesso.', [
                    'user_id'          => $userId,
                    'categorias'       => $criadas,
                    'subcategorias'    => $subcriadas,
                    'total'            => $criadas + $subcriadas,
                ]);
            });
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'criar_categorias_padrao',
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Retorna o mapa de subcategorias padrão agrupadas pelo nome da categoria pai.
     * Utilizado no registro de novos usuários e no script retroativo.
     *
     * @return array<string, array<int, array{nome: string, icone: string}>>
     */
    public static function getSubcategoriasPadrao(): array
    {
        return [
            // ── Despesa ──
            'Alimentação' => [
                ['nome' => 'Restaurantes',          'icone' => 'utensils'],
                ['nome' => 'Supermercado',           'icone' => 'shopping-cart'],
                ['nome' => 'Delivery',               'icone' => 'bike'],
                ['nome' => 'Padaria',                'icone' => 'croissant'],
                ['nome' => 'Lanches',                'icone' => 'sandwich'],
            ],
            'Transporte' => [
                ['nome' => 'Combustível',            'icone' => 'fuel'],
                ['nome' => 'Uber / 99',              'icone' => 'car'],
                ['nome' => 'Transporte Público',     'icone' => 'bus'],
                ['nome' => 'Estacionamento',         'icone' => 'parking-circle'],
                ['nome' => 'Manutenção Veículo',     'icone' => 'wrench'],
            ],
            'Moradia' => [
                ['nome' => 'Aluguel',                'icone' => 'home'],
                ['nome' => 'Condomínio',             'icone' => 'building'],
                ['nome' => 'Energia',                'icone' => 'zap'],
                ['nome' => 'Água',                   'icone' => 'droplets'],
                ['nome' => 'Internet',               'icone' => 'wifi'],
                ['nome' => 'Gás',                    'icone' => 'flame'],
            ],
            'Saúde' => [
                ['nome' => 'Farmácia',               'icone' => 'pill'],
                ['nome' => 'Consultas',              'icone' => 'stethoscope'],
                ['nome' => 'Plano de Saúde',         'icone' => 'heart-pulse'],
                ['nome' => 'Academia',               'icone' => 'dumbbell'],
            ],
            'Educação' => [
                ['nome' => 'Cursos',                 'icone' => 'graduation-cap'],
                ['nome' => 'Livros',                 'icone' => 'book-open'],
                ['nome' => 'Mensalidade',            'icone' => 'school'],
            ],
            'Lazer' => [
                ['nome' => 'Cinema',                 'icone' => 'popcorn'],
                ['nome' => 'Viagens',                'icone' => 'plane'],
                ['nome' => 'Jogos',                  'icone' => 'gamepad-2'],
                ['nome' => 'Streaming',              'icone' => 'tv'],
            ],
            'Compras' => [
                ['nome' => 'Roupas',                 'icone' => 'shirt'],
                ['nome' => 'Eletrônicos',            'icone' => 'smartphone'],
                ['nome' => 'Casa e Decoração',       'icone' => 'sofa'],
            ],
            'Contas e Serviços' => [
                ['nome' => 'Telefone',               'icone' => 'phone'],
                ['nome' => 'Impostos',               'icone' => 'landmark'],
                ['nome' => 'Seguros',                'icone' => 'shield-check'],
            ],
            'Assinaturas' => [
                ['nome' => 'Música',                 'icone' => 'music'],
                ['nome' => 'Armazenamento',          'icone' => 'cloud'],
                ['nome' => 'Software',               'icone' => 'laptop'],
            ],
            // ── Receita ──
            'Salário' => [
                ['nome' => 'Salário Fixo',           'icone' => 'banknote'],
                ['nome' => 'Hora Extra',             'icone' => 'clock'],
                ['nome' => '13º Salário',            'icone' => 'gift'],
            ],
            'Freelance' => [
                ['nome' => 'Projetos',               'icone' => 'briefcase'],
                ['nome' => 'Consultoria',            'icone' => 'message-square'],
            ],
            'Investimentos' => [
                ['nome' => 'Dividendos',             'icone' => 'trending-up'],
                ['nome' => 'Renda Fixa',             'icone' => 'landmark'],
                ['nome' => 'Ações',                  'icone' => 'bar-chart-2'],
            ],
        ];
    }

    /**
     * Envia email de verificação para o novo usuário.
     * 
     * O email de boas-vindas será enviado após a verificação.
     * Não lança exceção para não interromper o fluxo de registro.
     */
    private function enviarEmailBoasVindas(int $userId, string $email, string $name): void
    {
        try {
            $user = Usuario::find($userId);

            if (!$user) {
                LogService::warning('[AuthService] Usuário não encontrado para enviar email de verificação.', [
                    'user_id' => $userId,
                ]);
                return;
            }

            // Envia email de verificação
            $sent = $this->emailVerificationService()->sendVerificationEmail($user);

            if ($sent) {
                LogService::info('[AuthService] Email de verificação enviado com sucesso.', [
                    'user_id' => $userId,
                    'email' => $email,
                ]);
            } else {
                LogService::warning('[AuthService] Email de verificação não foi enviado.', [
                    'user_id' => $userId,
                    'email' => $email,
                ]);
            }
        } catch (Throwable $e) {
            // Não propaga erro - email de verificação não deve impedir o registro
            LogService::captureException($e, LogCategory::NOTIFICATION, [
                'action' => 'enviar_email_verificacao',
                'user_id' => $userId,
                'email' => $email,
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

            $referralService = $this->referralService();

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
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'processar_indicacao',
                'user_id' => $userId,
                'referral_code' => $referralCode,
            ]);
        }
    }

    private function emailVerificationService(): EmailVerificationService
    {
        return $this->emailVerificationService ??= ApplicationContainer::resolveOrNew(
            null,
            EmailVerificationService::class
        );
    }

    private function referralService(): ReferralService
    {
        return $this->referralService ??= ApplicationContainer::resolveOrNew(null, ReferralService::class);
    }
}
