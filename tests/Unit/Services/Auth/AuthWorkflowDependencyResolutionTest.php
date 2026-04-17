<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Services\Auth\AuthService;
use Application\Services\Auth\CredentialsValidationStrategy;
use Application\Services\Auth\CsrfSecurityCheck;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\Auth\LoginHandler;
use Application\Services\Auth\LogoutHandler;
use Application\Services\Auth\RateLimitSecurityCheck;
use Application\Services\Auth\RegistrationHandler;
use Application\Services\Auth\RegistrationValidationStrategy;
use Application\Services\Auth\SessionManager;
use Application\Services\Auth\TokenPairService;
use Application\Services\Communication\MailService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Referral\ReferralAntifraudService;
use Application\Services\Referral\ReferralService;
use Google_Client;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AuthWorkflowDependencyResolutionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::flush();
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        parent::tearDown();
    }

    public function testAuthServiceResolvesHandlersAndLazyFlowDependenciesFromContainerWhenAvailable(): void
    {
        $loginHandler = Mockery::mock(LoginHandler::class);
        $registrationHandler = Mockery::mock(RegistrationHandler::class);
        $logoutHandler = Mockery::mock(LogoutHandler::class);
        $emailVerificationService = Mockery::mock(EmailVerificationService::class);
        $referralService = Mockery::mock(ReferralService::class);

        $container = new IlluminateContainer();
        $container->instance(LoginHandler::class, $loginHandler);
        $container->instance(RegistrationHandler::class, $registrationHandler);
        $container->instance(LogoutHandler::class, $logoutHandler);
        $container->instance(EmailVerificationService::class, $emailVerificationService);
        $container->instance(ReferralService::class, $referralService);
        ApplicationContainer::setInstance($container);

        $service = new AuthService();

        $this->assertSame($loginHandler, $this->readProperty($service, 'loginHandler'));
        $this->assertSame($registrationHandler, $this->readProperty($service, 'registrationHandler'));
        $this->assertSame($logoutHandler, $this->readProperty($service, 'logoutHandler'));
        $this->assertSame($emailVerificationService, $this->invokePrivateMethod($service, 'emailVerificationService'));
        $this->assertSame($referralService, $this->invokePrivateMethod($service, 'referralService'));
    }

    public function testAuthHandlersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $request = Mockery::mock(Request::class);
        $cache = Mockery::mock(CacheService::class);
        $credentialsValidation = Mockery::mock(CredentialsValidationStrategy::class);
        $sessionManager = Mockery::mock(SessionManager::class);
        $csrfCheck = Mockery::mock(CsrfSecurityCheck::class);
        $rateLimitCheck = Mockery::mock(RateLimitSecurityCheck::class);
        $registrationValidation = Mockery::mock(RegistrationValidationStrategy::class);
        $antifraudService = Mockery::mock(ReferralAntifraudService::class);

        $container = new IlluminateContainer();
        $container->instance(Request::class, $request);
        $container->instance(CacheService::class, $cache);
        $container->instance(CredentialsValidationStrategy::class, $credentialsValidation);
        $container->instance(SessionManager::class, $sessionManager);
        $container->instance(CsrfSecurityCheck::class, $csrfCheck);
        $container->instance(RateLimitSecurityCheck::class, $rateLimitCheck);
        $container->instance(RegistrationValidationStrategy::class, $registrationValidation);
        $container->instance(ReferralAntifraudService::class, $antifraudService);
        ApplicationContainer::setInstance($container);

        $loginHandler = new LoginHandler();
        $logoutHandler = new LogoutHandler();
        $registrationHandler = new RegistrationHandler();

        $this->assertSame($request, $this->readProperty($loginHandler, 'request'));
        $this->assertSame($credentialsValidation, $this->readProperty($loginHandler, 'validationStrategy'));
        $this->assertSame($sessionManager, $this->readProperty($loginHandler, 'sessionManager'));
        $this->assertSame($csrfCheck, $this->readProperty($loginHandler, 'csrfCheck'));
        $this->assertSame($rateLimitCheck, $this->readProperty($loginHandler, 'rateLimitCheck'));

        $this->assertSame($request, $this->readProperty($logoutHandler, 'request'));
        $this->assertSame($sessionManager, $this->readProperty($logoutHandler, 'sessionManager'));

        $this->assertSame($registrationValidation, $this->readProperty($registrationHandler, 'validationStrategy'));
        $this->assertSame($antifraudService, $this->readProperty($registrationHandler, 'antifraudService'));
        $this->assertSame($request, $this->readProperty($registrationHandler, 'request'));
    }

    public function testGoogleAndVerificationServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $googleClient = Mockery::mock(Google_Client::class);
        $authService = Mockery::mock(AuthService::class);
        $mailService = Mockery::mock(MailService::class);
        $sessionManager = Mockery::mock(SessionManager::class);
        $tokenPairService = Mockery::mock(TokenPairService::class);
        $referralService = Mockery::mock(ReferralService::class);
        $achievementService = Mockery::mock(AchievementService::class);
        $authRuntimeConfig = new AuthRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(AuthRuntimeConfig::class, $authRuntimeConfig);
        $container->instance(Google_Client::class, $googleClient);
        $container->instance(AuthService::class, $authService);
        $container->instance(MailService::class, $mailService);
        $container->instance(SessionManager::class, $sessionManager);
        $container->instance(TokenPairService::class, $tokenPairService);
        $container->instance(ReferralService::class, $referralService);
        $container->instance(AchievementService::class, $achievementService);
        ApplicationContainer::setInstance($container);

        $googleAuthService = new GoogleAuthService();
        $verificationService = new EmailVerificationService();

        $this->assertSame($googleClient, $this->readProperty($googleAuthService, 'client'));
        $this->assertSame($authService, $this->readProperty($googleAuthService, 'authService'));
        $this->assertSame($mailService, $this->readProperty($googleAuthService, 'mailService'));
        $this->assertSame($sessionManager, $this->readProperty($googleAuthService, 'sessionManager'));
        $this->assertSame($authRuntimeConfig, $this->readProperty($googleAuthService, 'runtimeConfig'));

        $this->assertSame($mailService, $this->readProperty($verificationService, 'mailService'));
        $this->assertSame($tokenPairService, $this->readProperty($verificationService, 'tokenPairService'));
        $this->assertSame($authRuntimeConfig, $this->readProperty($verificationService, 'runtimeConfig'));
        $this->assertSame($referralService, $this->invokePrivateMethod($verificationService, 'referralService'));
        $this->assertSame($achievementService, $this->invokePrivateMethod($verificationService, 'achievementService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    private function invokePrivateMethod(object $object, string $method): mixed
    {
        return \Closure::bind(function () use ($method) {
            return $this->{$method}();
        }, $object, $object::class)();
    }
}
