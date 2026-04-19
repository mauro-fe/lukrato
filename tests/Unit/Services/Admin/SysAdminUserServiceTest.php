<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Core\Exceptions\ClientErrorException;
use Application\Services\Admin\SysAdminUserService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SysAdminUserServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGrantAccessThrowsClientErrorWhenUserIdentifierIsMissing(): void
    {
        $service = new SysAdminUserService();

        $this->expectException(ClientErrorException::class);
        $this->expectExceptionMessage('E-mail ou ID do usuário é obrigatório');

        $service->grantAccess(1, 'Admin', ['days' => 7]);
    }

    public function testUpdateUserPreventsRemovingOwnAdminPrivileges(): void
    {
        $targetUser = new class {
            public int $id = 10;
            public string $nome = 'Admin';
            public string $email = 'admin@example.com';
            public int $is_admin = 1;
        };

        $userModel = Mockery::mock('alias:Application\Models\Usuario');
        $userModel->shouldReceive('find')->once()->with(10)->andReturn($targetUser);

        $service = new SysAdminUserService();

        $this->expectException(ClientErrorException::class);
        $this->expectExceptionMessage('Você não pode remover seu próprio status de administrador');

        $service->updateUser(10, 'Admin', 10, ['is_admin' => 0]);
    }

    public function testDeleteUserPreventsDeletingOwnAccount(): void
    {
        $targetUser = new class {
            public int $id = 20;
            public string $nome = 'Admin';
            public string $email = 'admin@example.com';

            public function delete(): void {}
        };

        $userModel = Mockery::mock('alias:Application\Models\Usuario');
        $userModel->shouldReceive('find')->once()->with(20)->andReturn($targetUser);

        $service = new SysAdminUserService();

        $this->expectException(ClientErrorException::class);
        $this->expectExceptionMessage('Você não pode excluir sua própria conta');

        $service->deleteUser(20, 'Admin', 20);
    }
}
