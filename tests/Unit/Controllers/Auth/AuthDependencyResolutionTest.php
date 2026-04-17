<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Controllers\Auth\EmailVerificationController;
use Application\Controllers\Auth\ForgotPasswordController;
use Application\Controllers\Auth\GoogleCallbackController;
use Application\Controllers\Auth\GoogleLoginController;
use Application\Controllers\Auth\LoginController;
use Application\Controllers\Auth\RegistroController;
use Application\Repositories\UsuarioRepository;
use Application\Services\Auth\AuthService;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\Auth\PasswordResetService;
use Application\Services\Auth\RegistrationResponseHandler;
use Application\Services\Infrastructure\TurnstileService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AuthDependencyResolutionTest extends TestCase
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

    public function testLoginAndRegisterControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $authService = Mockery::mock(AuthService::class);
        $turnstileService = Mockery::mock(TurnstileService::class);
        $googleAuthService = Mockery::mock(GoogleAuthService::class);
        $responseHandler = Mockery::mock(RegistrationResponseHandler::class);

        $container = new IlluminateContainer();
        $container->instance(AuthService::class, $authService);
        $container->instance(TurnstileService::class, $turnstileService);
        $container->instance(GoogleAuthService::class, $googleAuthService);
        $container->instance(RegistrationResponseHandler::class, $responseHandler);
        ApplicationContainer::setInstance($container);

        $loginController = new LoginController();
        $registroController = new RegistroController();

        $this->assertSame($authService, $this->readProperty($loginController, 'authService'));
        $this->assertSame($turnstileService, $this->readProperty($loginController, 'turnstile'));
        $this->assertSame($authService, $this->readProperty($registroController, 'authService'));
        $this->assertSame($googleAuthService, $this->readProperty($registroController, 'googleAuthService'));
        $this->assertSame($responseHandler, $this->readProperty($registroController, 'responseHandler'));
        $this->assertSame($turnstileService, $this->readProperty($registroController, 'turnstile'));
    }

    public function testPasswordAndVerificationControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $passwordResetService = Mockery::mock(PasswordResetService::class);
        $emailVerificationService = Mockery::mock(EmailVerificationService::class);
        $usuarioRepository = Mockery::mock(UsuarioRepository::class);
        $runtimeConfig = new AuthRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(PasswordResetService::class, $passwordResetService);
        $container->instance(EmailVerificationService::class, $emailVerificationService);
        $container->instance(UsuarioRepository::class, $usuarioRepository);
        $container->instance(AuthRuntimeConfig::class, $runtimeConfig);
        ApplicationContainer::setInstance($container);

        $forgotPasswordController = new ForgotPasswordController();
        $emailVerificationController = new EmailVerificationController();

        $this->assertSame($passwordResetService, $this->readProperty($forgotPasswordController, 'service'));
        $this->assertSame($runtimeConfig, $this->readProperty($forgotPasswordController, 'runtimeConfig'));
        $this->assertSame($emailVerificationService, $this->readProperty($emailVerificationController, 'verificationService'));
        $this->assertSame($usuarioRepository, $this->readProperty($emailVerificationController, 'usuarioRepo'));
        $this->assertSame($runtimeConfig, $this->readProperty($emailVerificationController, 'runtimeConfig'));
    }

    public function testGoogleControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $googleAuthService = Mockery::mock(GoogleAuthService::class);
        $runtimeConfig = new AuthRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(GoogleAuthService::class, $googleAuthService);
        $container->instance(AuthRuntimeConfig::class, $runtimeConfig);
        ApplicationContainer::setInstance($container);

        $googleLoginController = new GoogleLoginController();
        $googleCallbackController = new GoogleCallbackController();

        $this->assertSame($googleAuthService, $this->readProperty($googleLoginController, 'googleAuthService'));
        $this->assertSame($runtimeConfig, $this->readProperty($googleLoginController, 'runtimeConfig'));
        $this->assertSame($googleAuthService, $this->readProperty($googleCallbackController, 'googleAuthService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
