<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Config\AuthRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Contracts\Auth\PasswordResetNotificationInterface;
use Application\Contracts\Auth\PasswordResetRepositoryInterface;
use Application\Contracts\Auth\TokenGeneratorInterface;
use Application\Core\Request;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Auth\MailPasswordResetNotification;
use Application\Services\Auth\PasswordResetService;
use Application\Services\Auth\RateLimitSecurityCheck;
use Application\Services\Auth\RegistrationResponseHandler;
use Application\Services\Auth\SecureTokenGenerator;
use Application\Services\Auth\TokenPairService;
use Application\Services\Communication\MailService;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\TurnstileService;
use Application\Repositories\PasswordResetRepositoryEloquent;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SupportServicesDependencyResolutionTest extends TestCase
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

    public function testSupportServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $mailService = Mockery::mock(MailService::class);
        $tokenPairService = Mockery::mock(TokenPairService::class);
        $cacheService = Mockery::mock(CacheService::class);
        $request = Mockery::mock(Request::class);
        $repository = Mockery::mock(PasswordResetRepositoryInterface::class);
        $tokenGenerator = Mockery::mock(TokenGeneratorInterface::class);
        $notifier = Mockery::mock(PasswordResetNotificationInterface::class);
        $authRuntimeConfig = new AuthRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(AuthRuntimeConfig::class, $authRuntimeConfig);
        $container->instance(MailService::class, $mailService);
        $container->instance(TokenPairService::class, $tokenPairService);
        $container->instance(CacheService::class, $cacheService);
        $container->instance(Request::class, $request);
        $container->instance(PasswordResetRepositoryInterface::class, $repository);
        $container->instance(TokenGeneratorInterface::class, $tokenGenerator);
        $container->instance(PasswordResetNotificationInterface::class, $notifier);
        ApplicationContainer::setInstance($container);

        $emailVerificationService = new EmailVerificationService();
        $rateLimitSecurityCheck = new RateLimitSecurityCheck();
        $passwordResetService = new PasswordResetService();
        $registrationResponseHandler = new RegistrationResponseHandler();
        $mailPasswordResetNotification = new MailPasswordResetNotification();
        $turnstileService = new TurnstileService();

        $this->assertSame($mailService, $this->readProperty($emailVerificationService, 'mailService'));
        $this->assertSame($tokenPairService, $this->readProperty($emailVerificationService, 'tokenPairService'));
        $this->assertSame($authRuntimeConfig, $this->readProperty($emailVerificationService, 'runtimeConfig'));
        $this->assertSame($cacheService, $this->readProperty($rateLimitSecurityCheck, 'cache'));
        $this->assertSame($repository, $this->readProperty($passwordResetService, 'repository'));
        $this->assertSame($tokenGenerator, $this->readProperty($passwordResetService, 'tokenGenerator'));
        $this->assertSame($notifier, $this->readProperty($passwordResetService, 'notifier'));
        $this->assertSame($tokenPairService, $this->readProperty($passwordResetService, 'tokenPairService'));
        $this->assertSame($authRuntimeConfig, $this->readProperty($passwordResetService, 'runtimeConfig'));
        $this->assertSame($request, $this->readProperty($registrationResponseHandler, 'request'));
        $this->assertSame($mailService, $this->readProperty($mailPasswordResetNotification, 'mail'));
        $this->assertSame($cacheService, $this->readProperty($turnstileService, 'cache'));
    }

    public function testPasswordResetServiceResolvesAuthDefaultsViaProviderWhenInterfacesAreNotBound(): void
    {
        $mailService = Mockery::mock(MailService::class);
        $tokenPairService = Mockery::mock(TokenPairService::class);

        $container = new IlluminateContainer();
        $container->instance(MailService::class, $mailService);
        $container->instance(TokenPairService::class, $tokenPairService);
        ApplicationContainer::setInstance($container);

        $passwordResetService = new PasswordResetService();

        $repository = $this->readProperty($passwordResetService, 'repository');
        $tokenGenerator = $this->readProperty($passwordResetService, 'tokenGenerator');
        $notifier = $this->readProperty($passwordResetService, 'notifier');

        $this->assertInstanceOf(PasswordResetRepositoryEloquent::class, $repository);
        $this->assertInstanceOf(SecureTokenGenerator::class, $tokenGenerator);
        $this->assertInstanceOf(MailPasswordResetNotification::class, $notifier);
        $this->assertSame($mailService, $this->readProperty($notifier, 'mail'));
        $this->assertSame($tokenPairService, $this->readProperty($passwordResetService, 'tokenPairService'));
        $this->assertInstanceOf(AuthRuntimeConfig::class, $this->readProperty($passwordResetService, 'runtimeConfig'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
