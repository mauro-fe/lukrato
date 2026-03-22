<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Application\Lib\Auth;
use Application\Models\Usuario;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class AuthTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        Auth::resolveUserUsing(null);
    }

    protected function tearDown(): void
    {
        Auth::resolveUserUsing(null);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testGetSessionTimeoutUsesRememberMeFlag(): void
    {
        $this->startSession();
        $_SESSION['remember_me'] = false;
        $this->assertSame(Auth::SESSION_TIMEOUT, Auth::getSessionTimeout());

        $_SESSION['remember_me'] = true;
        $this->assertSame(Auth::REMEMBER_TIMEOUT, Auth::getSessionTimeout());
    }

    public function testIsLoggedInIdAndUsernameUseMinimalSessionAndDatabaseLookup(): void
    {
        $this->seedAuthenticatedUserSession(77, 'Mauro');

        $this->assertTrue(Auth::isLoggedIn());
        $this->assertSame(77, Auth::id());
        $this->assertSame('mauro', Auth::username());
    }

    public function testUserReturnsUsuarioLoadedFromDatabase(): void
    {
        $this->seedAuthenticatedUserSession(15, 'Helena');

        $user = Auth::user();

        $this->assertInstanceOf(Usuario::class, $user);
        $this->assertSame(15, $user->id);
        $this->assertSame('Helena', $user->nome);
    }

    public function testLoginStoresOnlyIdentityAndSessionControlMetadata(): void
    {
        $this->startSession();

        $user = new Usuario();
        $user->id = 91;
        $user->nome = 'Mauro Silva';

        Auth::login($user);

        $this->assertSame(91, $_SESSION['user_id']);
        $this->assertArrayHasKey('last_activity', $_SESSION);
        $this->assertArrayNotHasKey('usuario_logged_in', $_SESSION);
        $this->assertArrayNotHasKey('usuario_nome', $_SESSION);
        $this->assertArrayNotHasKey('admin_logged_in', $_SESSION);
        $this->assertArrayNotHasKey('admin_id', $_SESSION);
        $this->assertArrayNotHasKey('admin_username', $_SESSION);
        $this->assertArrayNotHasKey('usuario_cache', $_SESSION);
    }

    public function testUserIgnoresLegacySessionCacheAndUsesDatabasePermissions(): void
    {
        $this->startSession();
        $_SESSION['user_id'] = 40;
        $_SESSION['usuario_cache'] = [
            'id' => 40,
            'data' => (function (): Usuario {
                $cached = new Usuario();
                $cached->id = 40;
                $cached->nome = 'Cached Admin';
                $cached->is_admin = 1;

                return $cached;
            })(),
        ];

        $user = new Usuario();
        $user->id = 40;
        $user->nome = 'Database User';
        $user->is_admin = 0;

        Auth::resolveUserUsing(static fn (int $id): ?Usuario => $id === 40 ? $user : null);

        $resolved = Auth::user();

        $this->assertSame('Database User', $resolved?->nome);
        $this->assertSame(0, $resolved?->is_admin);
    }

    public function testCheckActivityRefreshesLastActivityWhenSessionIsStillValid(): void
    {
        $this->startSession();
        $_SESSION['user_id'] = 33;
        $_SESSION['last_activity'] = time() - 5;

        $previous = $_SESSION['last_activity'];

        $this->assertTrue(Auth::checkActivity(60));
        $this->assertGreaterThanOrEqual($previous, $_SESSION['last_activity']);
    }

    public function testCheckActivityLogsOutExpiredSession(): void
    {
        $this->startSession();
        $_SESSION['user_id'] = 33;
        $_SESSION['last_activity'] = time() - 120;

        $this->assertFalse(Auth::checkActivity(60));
        $this->assertSame([], $_SESSION);
        $this->assertFalse(Auth::isLoggedIn());
    }

    private function seedAuthenticatedUserSession(int $userId, string $name, int $isAdmin = 0): Usuario
    {
        $this->startSession();

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = $isAdmin;

        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();

        Auth::resolveUserUsing(static fn (int $id): ?Usuario => $id === $userId ? $user : null);

        return $user;
    }

    private function startSession(): void
    {
        $this->startIsolatedSession('auth-test');
    }
}
