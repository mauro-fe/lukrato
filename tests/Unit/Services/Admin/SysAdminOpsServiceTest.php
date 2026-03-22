<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Services\Admin\SysAdminOpsService;
use Application\Services\Infrastructure\MaintenanceService;
use PHPUnit\Framework\TestCase;

class SysAdminOpsServiceTest extends TestCase
{
    private string $flagFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->flagFile = BASE_PATH . '/storage/maintenance.flag';
        if (file_exists($this->flagFile)) {
            @unlink($this->flagFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->flagFile)) {
            @unlink($this->flagFile);
        }

        parent::tearDown();
    }

    public function testToggleMaintenanceActivatesAndDeactivatesMode(): void
    {
        $service = new SysAdminOpsService();

        $activateResult = $service->toggleMaintenance([
            'action' => 'activate',
            'reason' => 'Deploy',
            'estimated_minutes' => 15,
        ]);

        $this->assertTrue(MaintenanceService::isActive());
        $this->assertTrue($activateResult['data']['active']);
        $this->assertSame('Modo manutencao ativado com sucesso.', $activateResult['message']);
        $this->assertSame('Deploy', $activateResult['data']['data']['reason']);

        $deactivateResult = $service->toggleMaintenance(['action' => 'deactivate']);

        $this->assertFalse(MaintenanceService::isActive());
        $this->assertFalse($deactivateResult['data']['active']);
        $this->assertSame('Modo manutencao desativado. Sistema online.', $deactivateResult['message']);
    }
}
