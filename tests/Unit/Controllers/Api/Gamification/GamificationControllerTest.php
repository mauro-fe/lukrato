<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Gamification;

use Application\Controllers\Api\Gamification\GamificationController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Gamification\AchievementService;
use Application\Services\Gamification\StreakService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class GamificationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_POST = [];
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testGetProgressReturnsDefaultPayloadWhenUserHasNoProgress(): void
    {
        $this->seedAuthenticatedUserSession(701, 'Gamification User');

        $achievementService = Mockery::mock(AchievementService::class);
        $achievementService
            ->shouldReceive('checkAndUnlockAchievements')
            ->once()
            ->with(701, 'dashboard_load')
            ->andReturn([]);

        $streakService = Mockery::mock(StreakService::class);
        $userProgressModel = Mockery::mock('alias:Application\Models\UserProgress');
        $userProgressModel
            ->shouldReceive('where')
            ->once()
            ->with('user_id', 701)
            ->andReturnSelf();
        $userProgressModel
            ->shouldReceive('first')
            ->once()
            ->andReturn(null);

        $controller = new GamificationController($achievementService, $streakService);

        $response = $controller->getProgress();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Progresso do usuário',
            'data' => [
                'total_points' => 0,
                'current_level' => 1,
                'points_to_next_level' => 300,
                'progress_percentage' => 0,
                'current_streak' => 0,
                'best_streak' => 0,
                'plan_tier' => 'free',
                'is_pro' => false,
                'is_ultra' => false,
                'plan_label' => 'FREE',
                'upgrade_target' => 'pro',
                'streak_protection_available' => false,
                'streak_protection_used' => false,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testMarkAchievementsSeenReturnsBadRequestWhenIdsAreMissing(): void
    {
        $this->seedAuthenticatedUserSession(702, 'Gamification Invalid');

        $controller = new GamificationController(
            Mockery::mock(AchievementService::class),
            Mockery::mock(StreakService::class),
        );

        $response = $controller->markAchievementsSeen();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'IDs de conquistas inválidos',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGetProgressReturnsProFlagWhenSessionUserHasPaidPlan(): void
    {
        $this->seedAuthenticatedUserSession(703, 'Gamification Pro', true);

        $achievementService = Mockery::mock(AchievementService::class);
        $achievementService
            ->shouldReceive('checkAndUnlockAchievements')
            ->once()
            ->with(703, 'dashboard_load')
            ->andReturn([]);

        $streakService = Mockery::mock(StreakService::class);
        $userProgressModel = Mockery::mock('alias:Application\Models\UserProgress');
        $userProgressModel
            ->shouldReceive('where')
            ->once()
            ->with('user_id', 703)
            ->andReturnSelf();
        $userProgressModel
            ->shouldReceive('first')
            ->once()
            ->andReturn(null);

        $controller = new GamificationController($achievementService, $streakService);

        $response = $controller->getProgress();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['data']['is_pro']);
        $this->assertFalse($payload['data']['is_ultra']);
        $this->assertSame('pro', $payload['data']['plan_tier']);
        $this->assertSame('PRO', $payload['data']['plan_label']);
        $this->assertSame('ultra', $payload['data']['upgrade_target']);
    }

    public function testGetProgressThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new GamificationController(
            Mockery::mock(AchievementService::class),
            Mockery::mock(StreakService::class),
        );

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->getProgress();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name, bool $isPro = false): void
    {
        $this->startIsolatedSession('gamification-controller-test');

        $user = new TestGamificationUser();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;
        $user->senha = password_hash('Senha@123', PASSWORD_DEFAULT);
        $user->pro = $isPro;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}

final class TestGamificationUser extends Usuario
{
    public bool $pro = false;
    public ?string $planCode = null;

    public function planoAtual()
    {
        return $this->planCode === null ? null : (object) ['code' => $this->planCode];
    }

    public function isPro(): bool
    {
        return $this->pro;
    }
}
