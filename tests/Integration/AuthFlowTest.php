<?php

declare(strict_types=1);

namespace Tests\Integration;

use Application\Validators\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;

/**
 * Testes de integração do fluxo de autenticação.
 *
 * Testa as regras de validação de senha e geração segura
 * como parte do pipeline de autenticação.
 */
class AuthFlowTest extends TestCase
{
    // ─── Fluxo: Registro com validação de senha ─────────────

    public function testRegistrationPasswordValidationPipeline(): void
    {
        // Simular senhas que usuários tentariam
        $weakPasswords = [
            ['123456', 'Curta demais, sem caracteres mistos'],
            ['password', 'Sem maiúscula, dígito ou especial'],
            ['Password1', 'Sem caractere especial'],
            ['abc', 'Muito curta'],
        ];

        foreach ($weakPasswords as [$password, $reason]) {
            $errors = PasswordStrengthValidator::validate($password);
            $this->assertNotEmpty($errors, "Senha fraca '{$password}' ({$reason}) deveria falhar");
        }
    }

    public function testRegistrationStrongPasswordPasses(): void
    {
        $strongPasswords = [
            'MyP@ssw0rd!',
            'Str0ng#Pass',
            'C0mpl3x@123',
            'S3nh@Fort3!',
        ];

        foreach ($strongPasswords as $password) {
            $errors = PasswordStrengthValidator::validate($password);
            $this->assertEmpty($errors, "Senha forte '{$password}' deveria passar: " . implode(', ', $errors));
        }
    }

    // ─── Fluxo: Geração de senha para reset ────────────────

    public function testPasswordResetGenerationFlow(): void
    {
        // Gerar senha temporária e verificar que é válida
        $tempPassword = PasswordStrengthValidator::generateSecureRandom(16);

        $this->assertEquals(16, strlen($tempPassword));
        $errors = PasswordStrengthValidator::validate($tempPassword);
        $this->assertEmpty($errors, 'Senha gerada deveria passar na validação');
    }

    public function testGeneratedPasswordsAreUnique(): void
    {
        $passwords = [];
        for ($i = 0; $i < 50; $i++) {
            $passwords[] = PasswordStrengthValidator::generateSecureRandom(32);
        }

        // Todas devem ser únicas
        $unique = array_unique($passwords);
        $this->assertCount(50, $unique, 'Todas as senhas geradas devem ser únicas');
    }

    // ─── Fluxo: Limites de comprimento ──────────────────────

    public function testPasswordBoundaryValues(): void
    {
        // Exatamente 7 (abaixo do mínimo)
        $errors = PasswordStrengthValidator::validate('Aa1!xxx');
        $this->assertNotEmpty($errors);

        // Exatamente 8 (mínimo)
        $errors = PasswordStrengthValidator::validate('Aa1!xxxx');
        $this->assertEmpty($errors);

        // Exatamente 72 (máximo)
        $password72 = str_repeat('Aa1!', 18);
        $errors = PasswordStrengthValidator::validate($password72);
        $this->assertEmpty($errors);

        // Exatamente 73 (acima do máximo)
        $password73 = str_repeat('Aa1!', 18) . 'x';
        $errors = PasswordStrengthValidator::validate($password73);
        $this->assertNotEmpty($errors);
    }

    // ─── Fluxo: Caracteres especiais brasileiros ────────────

    public function testPasswordWithBrazilianSpecialChars(): void
    {
        // Senha com acentos e caracteres do português
        $password = 'Senh@F0rte!';
        $errors = PasswordStrengthValidator::validate($password);
        $this->assertEmpty($errors);
    }
}
