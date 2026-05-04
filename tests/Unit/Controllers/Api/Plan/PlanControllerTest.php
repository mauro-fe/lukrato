<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Plan;

use Application\Controllers\Api\Plan\PlanController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Plan\PlanLimitService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class PlanControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
    }

    protected function tearDown(): void
    {
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testLimitsReturnsSummaryResponse(): void
    {
        $this->seedAuthenticatedUserSession(12, 'Plan User');

        $summary = [
            'plan' => 'pro',
            'is_pro' => true,
        ];

        $service = Mockery::mock(PlanLimitService::class);
        $service
            ->shouldReceive('getLimitsSummary')
            ->once()
            ->with(12)
            ->andReturn($summary);

        $controller = new PlanController($service);

        $response = $controller->limits();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => $summary,
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testLimitsReturnsFreeFallbackWhenServiceFails(): void
    {
        $this->seedAuthenticatedUserSession(13, 'Plan Fallback');

        $service = Mockery::mock(PlanLimitService::class);
        $service
            ->shouldReceive('getLimitsSummary')
            ->once()
            ->with(13)
            ->andThrow(new \RuntimeException('boom'));
        $service
            ->shouldReceive('getConfig')
            ->once()
            ->andReturn([
                'limits' => [
                    'free' => [
                        'max_contas' => 3,
                        'max_cartoes' => 2,
                        'max_categorias_custom' => 9,
                        'max_metas' => 4,
                        'historico_meses' => 6,
                        'import_conta_ofx' => 1,
                        'import_conta_csv' => 1,
                        'import_cartao_ofx' => 1,
                    ],
                ],
            ]);

        $controller = new PlanController($service);

        $response = $controller->limits();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($payload['data']['is_pro']);
        $this->assertSame('free', $payload['data']['plan']);
        $this->assertSame(3, $payload['data']['contas']['limit']);
        $this->assertSame(6, $payload['data']['historico']['months_limit']);
        $this->assertSame(1, $payload['data']['importacoes']['import_conta_ofx']['remaining']);
    }

    public function testFeaturesThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new PlanController(Mockery::mock(PlanLimitService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->features();
    }

    public function testFeaturesReturnsTierAwarePayload(): void
    {
        $this->seedAuthenticatedUserSession(14, 'Ultra User');

        $service = Mockery::mock(PlanLimitService::class);
        $service
            ->shouldReceive('getPlanTier')
            ->once()
            ->with(14)
            ->andReturn('ultra');
        $service
            ->shouldReceive('getFeatures')
            ->once()
            ->with(14)
            ->andReturn([
                'reports' => true,
                'previsao_saldo' => true,
            ]);

        $controller = new PlanController($service);

        $response = $controller->features();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ultra', $payload['data']['plan']);
        $this->assertTrue($payload['data']['is_pro']);
        $this->assertTrue($payload['data']['is_ultra']);
        $this->assertSame('ULTRA', $payload['data']['plan_label']);
        $this->assertNull($payload['data']['upgrade_target']);
        $this->assertTrue($payload['data']['features']['reports']);
        $this->assertTrue($payload['data']['features']['previsao_saldo']);
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('plan-controller-test');

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
