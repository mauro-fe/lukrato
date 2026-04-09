<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use PHPUnit\Framework\TestCase;

class AuthWorkflowCompositionGuardTest extends TestCase
{
    public function testAuthWorkflowServicesDoNotReintroduceInlineCompositionPatterns(): void
    {
        $files = [
            'Application/Services/Auth/AuthService.php',
            'Application/Services/Auth/LoginHandler.php',
            'Application/Services/Auth/LogoutHandler.php',
            'Application/Services/Auth/RegistrationHandler.php',
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

        $authService = (string) file_get_contents('Application/Services/Auth/AuthService.php');
        $loginHandler = (string) file_get_contents('Application/Services/Auth/LoginHandler.php');
        $logoutHandler = (string) file_get_contents('Application/Services/Auth/LogoutHandler.php');
        $registrationHandler = (string) file_get_contents('Application/Services/Auth/RegistrationHandler.php');
        $emailVerificationService = (string) file_get_contents('Application/Services/Auth/EmailVerificationService.php');
        $googleAuthService = (string) file_get_contents('Application/Services/Auth/GoogleAuthService.php');

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+EmailVerificationService\s*\(/',
            $authService,
            'AuthService não deve instanciar EmailVerificationService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+ReferralService\s*\(/',
            $authService,
            'AuthService não deve instanciar ReferralService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CredentialsValidationStrategy\s*\(/',
            $loginHandler,
            'LoginHandler não deve instanciar CredentialsValidationStrategy diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+SessionManager\s*\(/',
            $loginHandler,
            'LoginHandler não deve instanciar SessionManager diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+Request\s*\(/',
            $loginHandler,
            'LoginHandler não deve instanciar Request diretamente no fluxo.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+SessionManager\s*\(/',
            $logoutHandler,
            'LogoutHandler não deve instanciar SessionManager diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+Request\s*\(/',
            $logoutHandler,
            'LogoutHandler não deve instanciar Request diretamente no fluxo.'
        );

        $this->assertStringNotContainsString(
            'fn(): LoginHandler => new LoginHandler($resolvedRequest, $cache)',
            $authService,
            'AuthService não deve montar LoginHandler inline.'
        );

        $this->assertStringNotContainsString(
            'fn(): LogoutHandler => new LogoutHandler($resolvedRequest)',
            $authService,
            'AuthService não deve montar LogoutHandler inline.'
        );

        $this->assertStringNotContainsString(
            "fn(): CsrfSecurityCheck => new CsrfSecurityCheck(",
            $loginHandler,
            'LoginHandler não deve montar CsrfSecurityCheck inline.'
        );

        $this->assertStringNotContainsString(
            "fn(): RateLimitSecurityCheck => new RateLimitSecurityCheck(",
            $loginHandler,
            'LoginHandler não deve montar RateLimitSecurityCheck inline.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+RegistrationValidationStrategy\s*\(/',
            $registrationHandler,
            'RegistrationHandler não deve instanciar RegistrationValidationStrategy diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+ReferralAntifraudService\s*\(/',
            $registrationHandler,
            'RegistrationHandler não deve instanciar ReferralAntifraudService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+ReferralService\s*\(/',
            $emailVerificationService,
            'EmailVerificationService não deve instanciar ReferralService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AchievementService\s*\(/',
            $emailVerificationService,
            'EmailVerificationService não deve instanciar AchievementService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+MailService\s*\(/',
            $googleAuthService,
            'GoogleAuthService não deve instanciar MailService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+SessionManager\s*\(/',
            $googleAuthService,
            'GoogleAuthService não deve instanciar SessionManager diretamente.'
        );
    }
}
