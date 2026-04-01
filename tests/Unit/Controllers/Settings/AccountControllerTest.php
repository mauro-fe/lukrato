<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Settings;

use Application\Controllers\Settings\AccountController;
use Application\Core\Exceptions\AuthException;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Referral\ReferralAntifraudService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class AccountControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        Auth::resolveUserUsing(null);
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        Auth::resolveUserUsing(null);
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REMOTE_ADDR']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testDeleteThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new AccountController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->delete();
    }

    public function testDeleteReturnsNotFoundWhenUserCannotBeLoaded(): void
    {
        $this->seedAuthenticatedUserSession(45, 'Conta Teste');

        $antifraud = Mockery::mock(ReferralAntifraudService::class);
        $antifraud->shouldNotReceive('onAccountDeleted');

        $controller = new AccountController(
            $antifraud,
            static fn (int $userId): ?Usuario => null,
            static function (): void {
            }
        );

        $response = $controller->delete();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Usuario nao encontrado.', $payload['message']);
    }

    public function testDeleteAnonymizesAndDeletesUserThenLogsOut(): void
    {
        $this->seedAuthenticatedUserSession(78, 'Conta Remocao');

        $user = new FakeDeleteUsuario();
        $user->id = 78;
        $user->email = 'conta@lukrato.com';
        $user->nome = 'Conta Remocao';
        $user->google_id = 'gid-abc';

        $antifraud = Mockery::mock(ReferralAntifraudService::class);
        $antifraud
            ->shouldReceive('onAccountDeleted')
            ->once()
            ->with('conta@lukrato.com', 78, '127.0.0.1');

        $logoutCalled = false;

        $controller = new AccountController(
            $antifraud,
            static fn (int $userId): ?Usuario => $userId === 78 ? $user : null,
            static function () use (&$logoutCalled): void {
                $logoutCalled = true;
            }
        );

        $response = $controller->delete();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Conta excluida com sucesso.', $payload['message']);
        $this->assertTrue($logoutCalled);
        $this->assertTrue($user->saveCalled);
        $this->assertTrue($user->deleteCalled);
        $this->assertNull($user->google_id);
        $this->assertSame('Usuario Removido', $user->nome);
        $this->assertStringStartsWith('deleted_', (string) $user->email);
        $this->assertStringEndsWith('@anonimizado.local', (string) $user->email);
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('settings-account-controller-test');

        $authUser = new Usuario();
        $authUser->id = $userId;
        $authUser->nome = $name;
        $authUser->is_admin = 0;

        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();

        Auth::resolveUserUsing(static fn (int $id): ?Usuario => $id === $userId ? $authUser : null);
    }
}

final class FakeDeleteUsuario extends Usuario
{
    public bool $saveCalled = false;
    public bool $deleteCalled = false;

    public function save(array $options = []): bool
    {
        $this->saveCalled = true;
        return true;
    }

    public function delete(): ?bool
    {
        $this->deleteCalled = true;
        return true;
    }
}
