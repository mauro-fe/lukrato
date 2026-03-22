<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use Application\Models\Usuario;
use Application\Services\Auth\SessionManager;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class SessionManagerTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $this->startIsolatedSession('session-manager-test');
    }

    protected function tearDown(): void
    {
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testCreateSessionStoresOnlyIdentityAndSessionControlMetadata(): void
    {
        $manager = new SessionManager();
        $user = new Usuario();
        $user->id = 501;
        $user->nome = 'Session User';

        $manager->createSession($user);

        $this->assertSame(501, $_SESSION['user_id']);
        $this->assertArrayHasKey('last_activity', $_SESSION);
        $this->assertArrayNotHasKey('usuario_nome', $_SESSION);
        $this->assertArrayNotHasKey('admin_id', $_SESSION);
        $this->assertArrayNotHasKey('admin_username', $_SESSION);
        $this->assertArrayNotHasKey('usuario_cache', $_SESSION);
    }

    public function testCreateSessionStoresRememberMeWithoutPrivilegeState(): void
    {
        $manager = new SessionManager();
        $user = new Usuario();
        $user->id = 502;
        $user->nome = 'Remember User';

        $manager->createSession($user, true);

        $this->assertSame(502, $_SESSION['user_id']);
        $this->assertTrue($_SESSION['remember_me']);
        $this->assertArrayNotHasKey('admin_id', $_SESSION);
        $this->assertArrayNotHasKey('admin_username', $_SESSION);
    }
}
