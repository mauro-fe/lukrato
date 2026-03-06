<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use Application\Validators\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;

class PasswordStrengthValidatorTest extends TestCase
{
    // ─── Senhas válidas ────────────────────────────────────

    public function testValidPasswordReturnsNoErrors(): void
    {
        $errors = PasswordStrengthValidator::validate('Abc12345!');
        $this->assertEmpty($errors);
    }

    public function testExactMinLengthIsValid(): void
    {
        // 8 chars, has lower, upper, digit, special
        $errors = PasswordStrengthValidator::validate('Aa1!xxxx');
        $this->assertEmpty($errors);
    }

    public function testExact72CharsIsValid(): void
    {
        $password = str_repeat('Aa1!', 18); // 72 chars
        $errors = PasswordStrengthValidator::validate($password);
        $this->assertEmpty($errors);
    }

    // ─── Comprimento ───────────────────────────────────────

    public function testTooShortPassword(): void
    {
        $errors = PasswordStrengthValidator::validate('Aa1!');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('mínimo', $errors[0]);
    }

    public function testEmptyPassword(): void
    {
        $errors = PasswordStrengthValidator::validate('');
        $this->assertNotEmpty($errors);
    }

    public function testTooLongPassword(): void
    {
        $password = str_repeat('Aa1!', 19); // 76 chars > 72
        $errors = PasswordStrengthValidator::validate($password);
        $found = false;
        foreach ($errors as $err) {
            if (str_contains($err, 'máximo')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Deveria conter erro de comprimento máximo');
    }

    // ─── Complexidade ──────────────────────────────────────

    public function testMissingLowercase(): void
    {
        $errors = PasswordStrengthValidator::validate('ABCDEFG1!');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('minúscula', implode(' ', $errors));
    }

    public function testMissingUppercase(): void
    {
        $errors = PasswordStrengthValidator::validate('abcdefg1!');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('maiúscula', implode(' ', $errors));
    }

    public function testMissingDigit(): void
    {
        $errors = PasswordStrengthValidator::validate('Abcdefgh!');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('número', implode(' ', $errors));
    }

    public function testMissingSpecialChar(): void
    {
        $errors = PasswordStrengthValidator::validate('Abcdefg12');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('especial', implode(' ', $errors));
    }

    public function testMultipleMissingRequirements(): void
    {
        // Only lowercase, no upper, no digit, no special
        $errors = PasswordStrengthValidator::validate('abcdefgh');
        $this->assertNotEmpty($errors);
        $combined = implode(' ', $errors);
        $this->assertStringContainsString('maiúscula', $combined);
        $this->assertStringContainsString('número', $combined);
        $this->assertStringContainsString('especial', $combined);
    }

    // ─── generateSecureRandom ──────────────────────────────

    public function testGenerateSecureRandomDefaultLength(): void
    {
        $password = PasswordStrengthValidator::generateSecureRandom();
        $this->assertEquals(32, strlen($password));
    }

    public function testGenerateSecureRandomCustomLength(): void
    {
        $password = PasswordStrengthValidator::generateSecureRandom(16);
        $this->assertEquals(16, strlen($password));
    }

    public function testGenerateSecureRandomMeetsComplexity(): void
    {
        // Generate several times to ensure consistency
        for ($i = 0; $i < 10; $i++) {
            $password = PasswordStrengthValidator::generateSecureRandom(12);
            $errors = PasswordStrengthValidator::validate($password);
            $this->assertEmpty($errors, "Senha gerada '{$password}' não passou na validação: " . implode(', ', $errors));
        }
    }

    public function testGenerateSecureRandomMinLength(): void
    {
        $password = PasswordStrengthValidator::generateSecureRandom(8);
        $this->assertEquals(8, strlen($password));
        $errors = PasswordStrengthValidator::validate($password);
        $this->assertEmpty($errors);
    }
}
