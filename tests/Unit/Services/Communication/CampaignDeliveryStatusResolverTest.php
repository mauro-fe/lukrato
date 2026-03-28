<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Communication;

use Application\Models\MessageCampaign;
use Application\Services\Communication\CampaignDeliveryStatusResolver;
use PHPUnit\Framework\TestCase;

class CampaignDeliveryStatusResolverTest extends TestCase
{
    public function testResolvesSentForNotificationOnlyCampaign(): void
    {
        $resolver = new CampaignDeliveryStatusResolver();

        $status = $resolver->resolve(true, 25, false, 0, 0, 0);

        $this->assertSame(MessageCampaign::STATUS_SENT, $status);
    }

    public function testResolvesPartialWhenNotificationSucceedsButEmailFails(): void
    {
        $resolver = new CampaignDeliveryStatusResolver();

        $status = $resolver->resolve(true, 25, true, 25, 0, 25);

        $this->assertSame(MessageCampaign::STATUS_PARTIAL, $status);
    }

    public function testResolvesFailedWhenEmailOnlyCampaignFailsCompletely(): void
    {
        $resolver = new CampaignDeliveryStatusResolver();

        $status = $resolver->resolve(false, 0, true, 18, 0, 18);

        $this->assertSame(MessageCampaign::STATUS_FAILED, $status);
    }

    public function testResolvesSentWhenEmailOnlyCampaignSucceeds(): void
    {
        $resolver = new CampaignDeliveryStatusResolver();

        $status = $resolver->resolve(false, 0, true, 18, 18, 0);

        $this->assertSame(MessageCampaign::STATUS_SENT, $status);
    }
}
