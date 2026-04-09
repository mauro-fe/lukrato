<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class SupportServicesCompositionGuardTest extends TestCase
{
    public function testModernizedSupportServicesDoNotInstantiateDependenciesInlineInConstructors(): void
    {
        $files = [
            'Application/Services/Auth/EmailVerificationService.php',
            'Application/Services/Auth/RateLimitSecurityCheck.php',
            'Application/Services/Auth/PasswordResetService.php',
            'Application/Services/Auth/MailPasswordResetNotification.php',
            'Application/Services/Infrastructure/TurnstileService.php',
        ];

        foreach ($files as $filePath) {
            $content = (string) file_get_contents($filePath);

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve usar default inline com new: {$filePath}"
            );

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\([^)]*\)\s*\{[\s\S]*?\?\?=?\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve montar dependência inline com new: {$filePath}"
            );
        }

        $passwordResetService = (string) file_get_contents('Application/Services/Auth/PasswordResetService.php');

        $this->assertStringNotContainsString(
            'fn(): PasswordResetRepositoryInterface => new PasswordResetRepositoryEloquent()',
            $passwordResetService,
            'PasswordResetService não deve montar PasswordResetRepositoryInterface inline.'
        );

        $this->assertStringNotContainsString(
            'fn(): TokenGeneratorInterface => new SecureTokenGenerator()',
            $passwordResetService,
            'PasswordResetService não deve montar TokenGeneratorInterface inline.'
        );

        $this->assertStringNotContainsString(
            'fn(): PasswordResetNotificationInterface => new MailPasswordResetNotification()',
            $passwordResetService,
            'PasswordResetService não deve montar PasswordResetNotificationInterface inline.'
        );

        $this->assertStringNotContainsString(
            '(new AuthServiceProvider())->register(',
            $passwordResetService,
            'PasswordResetService não deve registrar AuthServiceProvider manualmente.'
        );
    }
}
