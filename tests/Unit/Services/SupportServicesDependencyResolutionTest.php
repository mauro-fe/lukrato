<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Container\ApplicationContainer;
use Application\Contracts\Auth\PasswordResetNotificationInterface;
use Application\Contracts\Auth\PasswordResetRepositoryInterface;
use Application\Contracts\Auth\TokenGeneratorInterface;
use Application\Core\Request;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Auth\MailPasswordResetNotification;
use Application\Services\Auth\PasswordResetService;
use Application\Services\Auth\RateLimitSecurityCheck;
use Application\Services\Auth\TokenPairService;
use Application\Services\Communication\MailService;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\TurnstileService;
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

        $container = new IlluminateContainer();
        $container->instance(MailService::class, $mailService);
        $container->instance(TokenPairService::class, $tokenPairService);
        $container->instance(CacheService::class, $cacheService);
        ApplicationContainer::setInstance($container);

        $emailVerificationService = new EmailVerificationService();
        $rateLimitSecurityCheck = new RateLimitSecurityCheck($request);
        $passwordResetService = new PasswordResetService($repository, $tokenGenerator, $notifier);
        $mailPasswordResetNotification = new MailPasswordResetNotification();
        $turnstileService = new TurnstileService();

        $this->assertSame($mailService, $this->readProperty($emailVerificationService, 'mailService'));
        $this->assertSame($tokenPairService, $this->readProperty($emailVerificationService, 'tokenPairService'));
        $this->assertSame($cacheService, $this->readProperty($rateLimitSecurityCheck, 'cache'));
        $this->assertSame($tokenPairService, $this->readProperty($passwordResetService, 'tokenPairService'));
        $this->assertSame($mailService, $this->readProperty($mailPasswordResetNotification, 'mail'));
        $this->assertSame($cacheService, $this->readProperty($turnstileService, 'cache'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
