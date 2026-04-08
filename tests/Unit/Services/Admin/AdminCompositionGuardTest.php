<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use PHPUnit\Framework\TestCase;

class AdminCompositionGuardTest extends TestCase
{
    public function testAdminServicesDoNotInstantiateCoreDependenciesInline(): void
    {
        $aiAdminWorkflowService = (string) file_get_contents('Application/Services/Admin/AiAdminWorkflowService.php');
        $sysAdminOpsService = (string) file_get_contents('Application/Services/Admin/SysAdminOpsService.php');

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AIService\s*\(/',
            $aiAdminWorkflowService,
            'AiAdminWorkflowService não deve instanciar AIService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+SystemContextService\s*\(/',
            $aiAdminWorkflowService,
            'AiAdminWorkflowService não deve instanciar SystemContextService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CacheService\s*\(/',
            $aiAdminWorkflowService,
            'AiAdminWorkflowService não deve instanciar CacheService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\?\?=?\s*new\s+CacheService\s*\(/',
            $sysAdminOpsService,
            'SysAdminOpsService não deve instanciar CacheService diretamente.'
        );
    }
}
