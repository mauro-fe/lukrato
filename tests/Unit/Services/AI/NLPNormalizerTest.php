<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\NLP\TextNormalizer;
use Application\Services\AI\NLP\NumberNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Testa TextNormalizer e NumberNormalizer com fixtures.
 */
class NLPNormalizerTest extends TestCase
{
    private static function fixturesPath(): string
    {
        return dirname(__DIR__, 3) . '/Fixtures/AI';
    }

    private array $cases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cases = require self::fixturesPath() . '/nlp_normalization_cases.php';
    }

    // ─── TextNormalizer ──────────────────────────────────────────

    /**
     * @dataProvider textNormalizerProvider
     */
    public function testTextNormalizer(string $input, string $expected, string $notes): void
    {
        $result = TextNormalizer::normalize($input);
        $this->assertEquals($expected, $result, "TextNormalizer falhou: {$notes}");
    }

    public static function textNormalizerProvider(): \Generator
    {
        $cases = require self::fixturesPath() . '/nlp_normalization_cases.php';
        foreach ($cases as $case) {
            if ($case[2] === 'text') {
                yield $case[4] => [$case[0], $case[1], $case[4]];
            }
        }
    }

    // ─── NumberNormalizer::normalize ─────────────────────────────

    /**
     * @dataProvider numberNormalizerProvider
     */
    public function testNumberNormalizer(string $input, string $expected, string $notes): void
    {
        $result = NumberNormalizer::normalize($input);
        $this->assertEquals($expected, $result, "NumberNormalizer falhou: {$notes}");
    }

    public static function numberNormalizerProvider(): \Generator
    {
        $cases = require self::fixturesPath() . '/nlp_normalization_cases.php';
        foreach ($cases as $case) {
            if ($case[2] === 'number') {
                yield $case[4] => [$case[0], $case[1], $case[4]];
            }
        }
    }

    // ─── NumberNormalizer::parseValue ────────────────────────────

    /**
     * @dataProvider parseValueProvider
     */
    public function testParseValue(string $input, float $expected, string $notes): void
    {
        $result = NumberNormalizer::parseValue($input);
        $this->assertEqualsWithDelta($expected, $result, 0.01, "parseValue falhou: {$notes}");
    }

    public static function parseValueProvider(): \Generator
    {
        $cases = require self::fixturesPath() . '/nlp_normalization_cases.php';
        foreach ($cases as $case) {
            if ($case[2] === 'parse_value') {
                yield $case[4] => [$case[0], $case[1], $case[4]];
            }
        }
    }

    // ─── Testes de comportamento adicionais ──────────────────────

    public function testNormalizePreservesCase(): void
    {
        // Quando input é lowercase, output é lowercase
        $this->assertEquals('você', TextNormalizer::normalize('vc'));
    }

    public function testNormalizeUppercaseInput(): void
    {
        // Quando input é uppercase, output é uppercase
        $this->assertEquals('VOCÊ', TextNormalizer::normalize('VC'));
    }

    public function testNormalizeIdempotent(): void
    {
        $input = 'gastei 50 no uber';
        $once = TextNormalizer::normalize($input);
        $twice = TextNormalizer::normalize($once);
        $this->assertEquals($once, $twice, 'TextNormalizer deve ser idempotente');
    }

    public function testNumberNormalizerIdempotent(): void
    {
        $input = 'gastei 2000 no mercado';
        $once = NumberNormalizer::normalize($input);
        $twice = NumberNormalizer::normalize($once);
        $this->assertEquals($once, $twice, 'NumberNormalizer deve ser idempotente');
    }

    public function testStripEmojis(): void
    {
        $input = '😀 gastei 50 🎉 no mercado 💰';
        $result = TextNormalizer::stripEmojis($input);
        $this->assertStringNotContainsString('😀', $result);
        $this->assertStringContainsString('gastei 50', $result);
        $this->assertStringContainsString('no mercado', $result);
    }

    public function testEmptyInput(): void
    {
        $this->assertEquals('', TextNormalizer::normalize(''));
        $this->assertEquals('', NumberNormalizer::normalize(''));
    }

    public function testNumberNormalizerDoesNotBreakMilho(): void
    {
        $result = NumberNormalizer::normalize('comprei milho no mercado');
        $this->assertStringContainsString('milho', $result, 'milho não deve virar 1000');
    }
}
