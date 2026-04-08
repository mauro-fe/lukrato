<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Categoria;

use Application\Controllers\Api\Categoria\CategoriaController;
use Application\Core\Exceptions\AuthException;
use Application\Repositories\CategoriaRepository;
use Application\Services\Gamification\AchievementService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Plan\PlanLimitService;
use Application\Models\Usuario;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class CategoriaControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testIndexReturnsSuccessResponse(): void
    {
        $this->seedAuthenticatedUserSession(71, 'Categoria User');

        $repo = Mockery::mock(CategoriaRepository::class);
        $repo
            ->shouldReceive('findRootsByUser')
            ->once()
            ->with(71)
            ->andReturn(new EloquentCollection([(object) ['id' => 1, 'nome' => 'Moradia']]));

        $controller = $this->buildController(categoriaRepo: $repo);

        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [['id' => 1, 'nome' => 'Moradia']],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStoreReturnsForbiddenResponseWhenPlanLimitIsReached(): void
    {
        $this->seedAuthenticatedUserSession(72, 'Categoria Limit');

        $planLimit = Mockery::mock(PlanLimitService::class);
        $planLimit
            ->shouldReceive('canCreateCategoria')
            ->once()
            ->with(72)
            ->andReturn([
                'allowed' => false,
                'message' => 'Limite do plano atingido',
                'upgrade_url' => '/assinatura',
                'limit' => 10,
                'used' => 10,
                'remaining' => 0,
            ]);

        $controller = $this->buildController(planLimitService: $planLimit);

        $response = $controller->store();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Limite do plano atingido',
            'errors' => [
                'limit_reached' => true,
                'upgrade_url' => '/assinatura',
                'limit_info' => [
                    'limit' => 10,
                    'used' => 10,
                    'remaining' => 0,
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = $this->buildController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    private function buildController(
        ?CategoriaRepository $categoriaRepo = null,
        ?PlanLimitService $planLimitService = null,
        ?GamificationService $gamificationService = null,
        ?AchievementService $achievementService = null
    ): CategoriaController {
        return new CategoriaController(
            $categoriaRepo ?? Mockery::mock(CategoriaRepository::class),
            $planLimitService ?? Mockery::mock(PlanLimitService::class),
            $gamificationService ?? Mockery::mock(GamificationService::class),
            $achievementService ?? Mockery::mock(AchievementService::class),
        );
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('categoria-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
