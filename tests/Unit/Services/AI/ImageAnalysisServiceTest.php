<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Media\ImageAnalysisService;
use PHPUnit\Framework\TestCase;

class ImageAnalysisServiceTest extends TestCase
{
    private string|false $originalVisionDetail = false;
    private string|false $originalVisionMaxDimension = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalVisionDetail = getenv('OPENAI_VISION_DETAIL');
        $this->originalVisionMaxDimension = getenv('OPENAI_VISION_MAX_DIMENSION');
    }

    protected function tearDown(): void
    {
        unset($_ENV['OPENAI_VISION_DETAIL'], $_ENV['OPENAI_VISION_MAX_DIMENSION']);
        $this->restoreEnv('OPENAI_VISION_DETAIL', $this->originalVisionDetail);
        $this->restoreEnv('OPENAI_VISION_MAX_DIMENSION', $this->originalVisionMaxDimension);
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
        putenv('OPENAI_VISION_DETAIL=auto');

        $service = new ImageAnalysisService();
        $method = new \ReflectionMethod($service, 'resolveImageDetail');
        $method->setAccessible(true);

        $detail = $method->invoke($service, 900, 1200, 350_000);

        $this->assertSame('auto', $detail);
    }

    private function restoreEnv(string $key, string|false $value): void
    {
        if ($value === false) {
            putenv($key);
            return;
        }

        putenv($key . '=' . $value);
    }
}
