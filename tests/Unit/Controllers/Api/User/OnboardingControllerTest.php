<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\User;

use Application\Controllers\Api\User\OnboardingController;
use Application\Core\Exceptions\AuthException;
use Application\Services\User\OnboardingWorkflowService;
use Application\Models\Usuario;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class OnboardingControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testStatusReturnsNotFoundWhenUserDoesNotExist(): void
    {
        $this->seedAuthenticatedUserSession(801, 'Onboarding Missing');

        $workflow = Mockery::mock(OnboardingWorkflowService::class);
        $workflow
            ->shouldReceive('getStatus')
            ->once()
            ->with(801)
            ->andReturn([
                'success' => false,
                'status' => 404,
                'message' => 'UsuÃ¡rio nÃ£o encontrado',
            ]);

        $controller = new OnboardingController($workflow);
        $response = $controller->status();

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'UsuÃ¡rio nÃ£o encontrado',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStatusReturnsChecklistFlagsWhenUserExists(): void
    {
        $this->seedAuthenticatedUserSession(802, 'Onboarding User');

        $workflow = Mockery::mock(OnboardingWorkflowService::class);
        $workflow
            ->shouldReceive('getStatus')
            ->once()
            ->with(802)
            ->andReturn([
                'success' => true,
                'data' => [
                    'tem_conta' => true,
                    'tem_lancamento' => false,
                    'onboarding_completo' => false,
                ],
            ]);

        $controller = new OnboardingController($workflow);
        $response = $controller->status();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'tem_conta' => true,
                'tem_lancamento' => false,
                'onboarding_completo' => false,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testCompleteReturnsValidationErrorWhenUserHasNoAccount(): void
    {
        $this->seedAuthenticatedUserSession(803, 'Onboarding Complete');

        $workflow = Mockery::mock(OnboardingWorkflowService::class);
        $workflow
            ->shouldReceive('complete')
            ->once()
            ->with(803)
            ->andReturn([
                'success' => false,
                'status' => 422,
                'message' => 'VocÃª precisa criar pelo menos uma conta antes de continuar.',
            ]);

        $controller = new OnboardingController($workflow);
        $response = $controller->complete();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'VocÃª precisa criar pelo menos uma conta antes de continuar.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStoreContaReturnsRedirectAndFlashWhenNameIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(804, 'Onboarding Web');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'nome' => '',
            'instituicao_financeira_id' => '12',
            'saldo_inicial' => '100,00',
        ];

        $workflow = Mockery::mock(OnboardingWorkflowService::class);
        $workflow
            ->shouldReceive('storeConta')
            ->once()
            ->with(804, $_POST)
            ->andReturn([
                'success' => false,
                'redirect' => 'onboarding',
                'error_message' => 'O nome da conta Ã© obrigatÃ³rio.',
            ]);

        $controller = new OnboardingController($workflow);
        $response = $controller->storeConta();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(BASE_URL . 'onboarding', $response->getHeaders()['Location'] ?? null);
        $this->assertSame('O nome da conta Ã© obrigatÃ³rio.', $_SESSION['error'] ?? null);
    }

    public function testStoreLancamentoReturnsRedirectAndFlashWhenFieldsAreMissing(): void
    {
        $this->seedAuthenticatedUserSession(805, 'Onboarding Web');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'tipo' => 'despesa',
            'valor' => '',
            'categoria_id' => '',
            'descricao' => '',
            'conta_id' => '',
        ];

        $workflow = Mockery::mock(OnboardingWorkflowService::class);
        $workflow
            ->shouldReceive('storeLancamento')
            ->once()
            ->with(805, $_POST)
            ->andReturn([
                'success' => false,
                'redirect' => 'onboarding',
                'error_message' => 'Todos os campos sÃ£o obrigatÃ³rios.',
            ]);

        $controller = new OnboardingController($workflow);
        $response = $controller->storeLancamento();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(BASE_URL . 'onboarding', $response->getHeaders()['Location'] ?? null);
        $this->assertSame('Todos os campos sÃ£o obrigatÃ³rios.', $_SESSION['error'] ?? null);
    }

    public function testStatusThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new OnboardingController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->status();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('onboarding-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;
        $user->senha = password_hash('Senha@123', PASSWORD_DEFAULT);

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
