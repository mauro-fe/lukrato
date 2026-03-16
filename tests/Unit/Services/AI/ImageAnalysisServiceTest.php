<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Media\ImageAnalysisService;
use PHPUnit\Framework\TestCase;

class ImageAnalysisServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['OPENAI_VISION_DETAIL'], $_ENV['OPENAI_VISION_MAX_DIMENSION']);
        parent::tearDown();
    }

    public function testLargeImagesDefaultToLowDetail(): void
    {
        $service = new ImageAnalysisService();
        $method = new \ReflectionMethod($service, 'resolveImageDetail');
        $method->setAccessible(true);

        $detail = $method->invoke($service, 2200, 1600, 2_000_000);

        $this->assertSame('low', $detail);
    }

    public function testCompactImagesDefaultToLowDetailForEconomy(): void
    {
        $service = new ImageAnalysisService();
        $method = new \ReflectionMethod($service, 'resolveImageDetail');
        $method->setAccessible(true);

        $detail = $method->invoke($service, 900, 1200, 350_000);

        $this->assertSame('low', $detail);
    }

    public function testTinyImagesCanStillUseAutoDetail(): void
    {
        $service = new ImageAnalysisService();
        $method = new \ReflectionMethod($service, 'resolveImageDetail');
        $method->setAccessible(true);

        $detail = $method->invoke($service, 640, 640, 120_000);

        $this->assertSame('auto', $detail);
    }

    public function testConfiguredVisionDetailOverridesHeuristic(): void
    {
        $_ENV['OPENAI_VISION_DETAIL'] = 'auto';

        $service = new ImageAnalysisService();
        $method = new \ReflectionMethod($service, 'resolveImageDetail');
        $method->setAccessible(true);

        $detail = $method->invoke($service, 900, 1200, 350_000);

        $this->assertSame('auto', $detail);
    }
}
