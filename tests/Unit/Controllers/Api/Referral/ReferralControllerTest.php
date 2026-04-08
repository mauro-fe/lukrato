<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Referral;

use Application\Controllers\Api\Referral\ReferralController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Referral\ReferralService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class ReferralControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testGetStatsReturnsSuccessResponse(): void
    {
        $user = $this->seedAuthenticatedUserSession(61, 'Referral User');

        $service = Mockery::mock(ReferralService::class);
        $service
            ->shouldReceive('ensureUserHasReferralCode')
            ->once()
            ->with($user);
        $service
            ->shouldReceive('getUserStats')
            ->once()
            ->with($user)
            ->andReturn(['total_indicacoes' => 3]);

        $controller = new ReferralController($service);

        $response = $controller->getStats();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Estatísticas de indicação',
            'data' => ['total_indicacoes' => 3],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testValidateCodeReturnsErrorWhenCodeIsMissing(): void
    {
        $controller = new ReferralController(Mockery::mock(ReferralService::class));

        $response = $controller->validateCode();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Código de indicação não informado',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGetCodeThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new ReferralController(Mockery::mock(ReferralService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->getCode();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): Usuario
    {
        $this->startIsolatedSession('referral-controller-test');

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

        return $user;
    }
}
