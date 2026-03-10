<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;
use PHPUnit\Framework\TestCase;

class IntentResultTest extends TestCase
{
    // ─── Confidence Threshold ──────────────────────────────

    public function testHighConfidenceIsConfident(): void
    {
        $result = IntentResult::high(IntentType::CHAT);
        $this->assertTrue($result->isConfident());
        $this->assertEquals(1.0, $result->confidence);
    }

    public function testMediumAboveThresholdIsConfident(): void
    {
        $result = IntentResult::medium(IntentType::ANALYZE, 0.8);
        $this->assertTrue($result->isConfident());
    }

    public function testExactlyAtThresholdIsConfident(): void
    {
        $result = IntentResult::medium(IntentType::CHAT, 0.6);
        $this->assertTrue($result->isConfident());
    }

    public function testBelowThresholdIsNotConfident(): void
    {
        $result = IntentResult::low(IntentType::CHAT, 0.5);
        $this->assertFalse($result->isConfident());
    }

    public function testVeryLowConfidenceIsNotConfident(): void
    {
        $result = IntentResult::low(IntentType::CHAT, 0.1);
        $this->assertFalse($result->isConfident());
    }

    public function testThresholdConstantIs06(): void
    {
        $this->assertEquals(0.6, IntentResult::CONFIDENCE_THRESHOLD);
    }

    // ─── Factory methods ───────────────────────────────────

    public function testHighFactoryReturnsConfidence1(): void
    {
        $result = IntentResult::high(IntentType::CONFIRM_ACTION);
        $this->assertEquals(1.0, $result->confidence);
        $this->assertEquals(IntentType::CONFIRM_ACTION, $result->intent);
    }

    public function testMetadataIsPreserved(): void
    {
        $meta = ['source' => 'cache', 'matched_pattern' => 'test'];
        $result = IntentResult::high(IntentType::CHAT, $meta);
        $this->assertEquals($meta, $result->metadata);
    }

    public function testLowFactoryPreservesConfidence(): void
    {
        $result = IntentResult::low(IntentType::CHAT, 0.3);
        $this->assertEquals(0.3, $result->confidence);
    }
}
