<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\IntentRules\ConfirmationIntentRule;
use PHPUnit\Framework\TestCase;

class ConfirmationRuleTest extends TestCase
{
    private array $cases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cases = require __DIR__ . '/../../../Fixtures/AI/confirmation_cases.php';
    }

    /**
     * @dataProvider affirmativeProvider
     */
    public function testIsAffirmative(string $input, bool $expected, string $notes): void
    {
        $result = ConfirmationIntentRule::isAffirmative($input);
        $this->assertEquals($expected, $result, "isAffirmative('{$input}') should be " . ($expected ? 'true' : 'false') . " — {$notes}");
    }

    /**
     * @dataProvider negativeProvider
     */
    public function testIsNegative(string $input, bool $expected, string $notes): void
    {
        $result = ConfirmationIntentRule::isNegative($input);
        $this->assertEquals($expected, $result, "isNegative('{$input}') should be " . ($expected ? 'true' : 'false') . " — {$notes}");
    }

    public static function affirmativeProvider(): \Generator
    {
        $cases = require __DIR__ . '/../../../Fixtures/AI/confirmation_cases.php';
        foreach ($cases as $case) {
            yield $case[4] => [$case[0], $case[1], $case[4]];
        }
    }

    public static function negativeProvider(): \Generator
    {
        $cases = require __DIR__ . '/../../../Fixtures/AI/confirmation_cases.php';
        foreach ($cases as $case) {
            yield "neg_{$case[4]}" => [$case[0], $case[2], $case[4]];
        }
    }

    // Regression: strict regex must still work
    public function testStrictAffirmativesWork(): void
    {
        $strict = ['sim', 'ok', 'pode', 'beleza', 'show', 'confirma', 'bora'];
        foreach ($strict as $msg) {
            $this->assertTrue(ConfirmationIntentRule::isAffirmative($msg), "Strict '{$msg}' must be affirmative");
        }
    }

    public function testStrictNegativesWork(): void
    {
        $strict = ['não', 'nn', 'cancela', 'esquece', 'negativo'];
        foreach ($strict as $msg) {
            $this->assertTrue(ConfirmationIntentRule::isNegative($msg), "Strict '{$msg}' must be negative");
        }
    }

    // Regression: trailing text accepted
    public function testLooseAffirmativeAcceptsTrailingText(): void
    {
        $loose = ['sim, pode registrar', 'ok está bom', 'pode sim', 'beleza, faz isso'];
        foreach ($loose as $msg) {
            $this->assertTrue(ConfirmationIntentRule::isAffirmative($msg), "Loose '{$msg}' must be affirmative");
        }
    }

    public function testLooseNegativeAcceptsTrailingText(): void
    {
        $loose = ['não, obrigado', 'cancela por favor'];
        foreach ($loose as $msg) {
            $this->assertTrue(ConfirmationIntentRule::isNegative($msg), "Loose '{$msg}' must be negative");
        }
    }

    // Ambiguous cases should be neither
    public function testAmbiguousIsNeither(): void
    {
        $ambiguous = ['talvez', 'depois', 'não sei', 'hmm'];
        foreach ($ambiguous as $msg) {
            $this->assertFalse(ConfirmationIntentRule::isAffirmative($msg), "'{$msg}' must NOT be affirmative");
            $this->assertFalse(ConfirmationIntentRule::isNegative($msg), "'{$msg}' must NOT be negative");
        }
    }

    // Cross-contamination: affirmative must not be negative and vice versa
    public function testAffirmativeIsNotNegative(): void
    {
        $affirmatives = ['sim', 'ok', 'pode', 'beleza', 'show'];
        foreach ($affirmatives as $msg) {
            $this->assertFalse(ConfirmationIntentRule::isNegative($msg), "'{$msg}' must NOT be negative");
        }
    }

    public function testNegativeIsNotAffirmative(): void
    {
        $negatives = ['não', 'nn', 'cancela', 'esquece'];
        foreach ($negatives as $msg) {
            $this->assertFalse(ConfirmationIntentRule::isAffirmative($msg), "'{$msg}' must NOT be affirmative");
        }
    }
}
