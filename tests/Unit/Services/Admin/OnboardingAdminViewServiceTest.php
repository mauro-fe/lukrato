<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Models\Categoria;
use Application\Models\Conta;
use Application\Models\InstituicaoFinanceira;
use Application\Models\OnboardingProgress;
use Application\Models\Usuario;
use Application\Services\Admin\OnboardingAdminViewService;
use Application\Services\User\OnboardingProgressService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class OnboardingAdminViewServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildViewDataReturnsRedirectWhenUserAlreadyCompletedOnboarding(): void
    {
        $user = new Usuario();
        $user->id = 10;
        $user->onboarding_completed_at = '2026-03-22 10:00:00';

        $progressService = Mockery::mock(OnboardingProgressService::class);
        $progressService->shouldNotReceive('getProgress');

        $service = new OnboardingAdminViewService($progressService);

        $result = $service->buildViewData($user);

        $this->assertSame(['redirect' => 'dashboard'], $result);
    }

    public function testBuildViewDataMovesSerializedConfigOutOfTheView(): void
    {
        $user = new Usuario();
        $user->id = 11;
        $user->nome = 'Maria Teste';
        $user->theme_preference = 'light';
        $user->onboarding_goal = 'economizar';

        $progress = new OnboardingProgress([
            'user_id' => 11,
            'has_conta' => false,
            'has_lancamento' => false,
            'onboarding_completed_at' => null,
        ]);

        $progressService = Mockery::mock(OnboardingProgressService::class);
        $progressService
            ->shouldReceive('getProgress')
            ->once()
            ->with(11)
            ->andReturn($progress);

        $nubank = new InstituicaoFinanceira([
            'nome' => 'Nubank',
            'codigo' => '260',
            'tipo' => 'banco',
            'cor_primaria' => '#820ad1',
            'cor_secundaria' => '#ffffff',
            'logo_path' => null,
        ]);
        $nubank->id = 1;

        $categoria = new Categoria([
            'nome' => 'Salario',
            'tipo' => 'receita',
        ]);
        $categoria->id = 9;

        $service = new class ($progressService, [$nubank], [$categoria]) extends OnboardingAdminViewService {
            public function __construct(
                OnboardingProgressService $progressService,
                private readonly array $institutions,
                private readonly array $categories
            ) {
                parent::__construct($progressService);
            }

            protected function fetchInstitutions(): iterable
            {
                return $this->institutions;
            }

            protected function fetchCategories(int $userId): iterable
            {
                return $this->categories;
            }

            protected function fetchFirstAccount(int $userId): ?Conta
            {
                return null;
            }
        };

        $result = $service->buildViewData($user);
        $globalConfig = json_decode($result['globalConfigJson'], true, 512, JSON_THROW_ON_ERROR);
        $onboardingConfig = json_decode($result['onboardingConfigJson'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('light', $result['theme']);
        $this->assertSame('Lukrato - Bem-vindo', $result['pageTitle']);
        $this->assertSame('Maria Teste', $globalConfig['userName']);
        $this->assertSame(11, $globalConfig['userId']);
        $this->assertArrayHasKey('csrfToken', $globalConfig);
        $this->assertSame('welcome', $onboardingConfig['initialStep']);
        $this->assertSame('economizar', $onboardingConfig['goal']);
        $this->assertSame('Nubank', $onboardingConfig['instituicoes'][0]['nome']);
        $this->assertSame('Salario', $onboardingConfig['categorias'][0]['nome']);
        $this->assertNull($onboardingConfig['conta']);
    }

    public function testBuildViewDataUsesAccountInstitutionFallbackAndTransactionStep(): void
    {
        $user = new Usuario();
        $user->id = 12;
        $user->nome = 'Usuario Conta';

        $progress = new OnboardingProgress([
            'user_id' => 12,
            'has_conta' => true,
            'has_lancamento' => false,
            'onboarding_completed_at' => null,
        ]);

        $progressService = Mockery::mock(OnboardingProgressService::class);
        $progressService
            ->shouldReceive('getProgress')
            ->once()
            ->with(12)
            ->andReturn($progress);

        $inter = new InstituicaoFinanceira([
            'nome' => 'Inter',
            'codigo' => '077',
            'tipo' => 'banco',
        ]);
        $inter->id = 33;

        $conta = new Conta([
            'nome' => 'Conta principal',
            'saldo_inicial' => 1200.50,
            'instituicao' => '',
            'instituicao_financeira_id' => 33,
        ]);
        $conta->id = 88;
        $conta->setRelation('instituicaoFinanceira', $inter);

        $service = new class ($progressService, $conta) extends OnboardingAdminViewService {
            public function __construct(
                OnboardingProgressService $progressService,
                private readonly Conta $account
            ) {
                parent::__construct($progressService);
            }

            protected function fetchInstitutions(): iterable
            {
                return [];
            }

            protected function fetchCategories(int $userId): iterable
            {
                return [];
            }

            protected function fetchFirstAccount(int $userId): ?Conta
            {
                return $this->account;
            }
        };

        $result = $service->buildViewData($user);
        $onboardingConfig = json_decode($result['onboardingConfigJson'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('transaction', $onboardingConfig['initialStep']);
        $this->assertSame('Inter', $onboardingConfig['conta']['instituicao']);
        $this->assertSame(88, $onboardingConfig['conta']['id']);
        $this->assertSame(33, $onboardingConfig['conta']['instituicao_financeira_id']);
    }
}
