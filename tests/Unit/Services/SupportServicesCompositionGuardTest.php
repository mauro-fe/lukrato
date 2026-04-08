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
    }
}
