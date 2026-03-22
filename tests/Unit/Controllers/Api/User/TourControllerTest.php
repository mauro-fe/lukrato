<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\User;

use Application\Controllers\Api\User\TourController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class TourControllerTest extends TestCase
{
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

    public function testCompleteReturnsAlreadyCompletedResponse(): void
    {
        $user = new TourTestUser();
        $user->id = 21;
        $user->nome = 'Tour Done';
        $user->is_admin = 0;
        $user->tour_completed_at = '2026-03-01 10:00:00';

        $this->seedAuthenticatedUserSession($user);

        $controller = new TourController();

        $response = $controller->complete();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => ['already_completed' => true],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertFalse($user->saved);
    }

    public function testCompleteReturnsSuccessResponseWhenUserCanBeSaved(): void
    {
        $user = new TourTestUser();
        $user->id = 22;
        $user->nome = 'Tour Fresh';
        $user->is_admin = 0;
        $user->tour_completed_at = null;

        $this->seedAuthenticatedUserSession($user);

        $controller = new TourController();

        $response = $controller->complete();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => ['tour_completed' => true],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertTrue($user->saved);
        $this->assertNotNull($user->tour_completed_at);
    }

    public function testCompleteThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new TourController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->complete();
    }

    private function seedAuthenticatedUserSession(Usuario $user): void
    {
        $this->startIsolatedSession('tour-controller-test');

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $user->id;
        $_SESSION['usuario_nome'] = $user->nome;
        $_SESSION['usuario_cache'] = [
            'id' => $user->id,
            'data' => $user,
        ];
    }
}

final class TourTestUser extends Usuario
{
    public bool $saved = false;

    public function save(array $options = []): bool
    {
        $this->saved = true;

        return true;
    }
}
