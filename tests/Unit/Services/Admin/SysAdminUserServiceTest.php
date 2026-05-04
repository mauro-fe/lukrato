<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Core\Exceptions\ClientErrorException;
use Application\Services\Admin\SysAdminUserService;
use Illuminate\Support\Collection;
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

    public function testListUsersReturnsRichPlanMetadataForMappedItems(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('count')->once()->andReturn(1);
        $query->shouldReceive('orderByDesc')->once()->with('id')->andReturnSelf();
        $query->shouldReceive('limit')->once()->with(10)->andReturnSelf();
        $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();
        $query->shouldReceive('with')->once()->with(['assinaturaAtiva.plano'])->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(new Collection([
            new TestSysAdminListedUser(),
        ]));

        $userModel = Mockery::mock('alias:Application\Models\Usuario');
        $userModel->shouldReceive('query')->once()->andReturn($query);

        $service = new SysAdminUserService();

        $result = $service->listUsers([]);
        $users = $result['users']->all();

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(10, $result['perPage']);
        $this->assertCount(1, $users);
        $this->assertSame(55, $users[0]['id']);
        $this->assertTrue($users[0]['is_pro']);
        $this->assertTrue($users[0]['is_ultra']);
        $this->assertSame('ultra', $users[0]['plan_tier']);
        $this->assertSame('ULTRA', $users[0]['plan_label']);
        $this->assertNull($users[0]['upgrade_target']);
        $this->assertSame('Ultra', $users[0]['plano_nome']);
    }
}

final class TestSysAdminListedUser
{
    public int $id = 55;
    public string $support_code = 'SUP-55';
    public string $nome = 'Usuário Ultra';
    public string $email = 'ultra@example.com';
    public string $avatar = '';
    public int $is_admin = 0;
    public ?string $email_verified_at = '2026-05-01 10:00:00';
    public string $created_at = '2026-04-01 09:00:00';

    public function plan(): object
    {
        return new class {
            public function summary(string $tierKey = 'plan'): array
            {
                return [
                    $tierKey => 'ultra',
                    'is_pro' => true,
                    'is_ultra' => true,
                    'plan_label' => 'ULTRA',
                    'upgrade_target' => null,
                ];
            }

            public function isPro(): bool
            {
                return true;
            }

            public function tier(): string
            {
                return 'ultra';
            }
        };
    }
}
