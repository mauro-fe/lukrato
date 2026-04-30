<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use Application\Core\Request;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Infrastructure\CacheService;
use Mockery;

trait ApiControllerTestHelpers
{
    private function buildControllerWithRequest(Request $request): TestableApiController
    {
        return new TestableApiController(
            Mockery::mock(Auth::class),
            $request,
            Mockery::mock(Response::class),
            Mockery::mock(CacheService::class),
        );
    }

    private function seedAuthenticatedUserSession(int $userId, string $name, int $isAdmin = 0): Usuario
    {
        $this->startIsolatedSession('api-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = $isAdmin;

        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();

        Auth::resolveUserUsing(static fn(int $id): ?Usuario => $id === $userId ? $user : null);

        return $user;
    }
}
