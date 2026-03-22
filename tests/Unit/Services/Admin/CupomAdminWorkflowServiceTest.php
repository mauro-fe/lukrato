<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Models\Usuario;
use Application\Services\Admin\CupomAdminWorkflowService;
use PHPUnit\Framework\TestCase;

class CupomAdminWorkflowServiceTest extends TestCase
{
    public function testCreateCouponReturnsBadRequestWhenCodeIsMissing(): void
    {
        $service = new CupomAdminWorkflowService();

        $result = $service->createCoupon([]);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Codigo do cupom e obrigatorio', $result['message']);
    }

    public function testValidateCouponReturnsBadRequestWhenCodeIsMissing(): void
    {
        $service = new CupomAdminWorkflowService();
        $user = new Usuario();
        $user->id = 55;

        $result = $service->validateCoupon($user, '');

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Codigo do cupom e obrigatorio', $result['message']);
    }

    public function testGetStatisticsReturnsBadRequestWhenCouponIdIsMissing(): void
    {
        $service = new CupomAdminWorkflowService();

        $result = $service->getStatistics(null);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('ID do cupom e obrigatorio', $result['message']);
    }
}
