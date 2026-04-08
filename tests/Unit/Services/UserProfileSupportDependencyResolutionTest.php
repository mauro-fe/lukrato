<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Builders\PerfilPayloadBuilder;
use Application\Container\ApplicationContainer;
use Application\Formatters\DocumentFormatter;
use Application\Formatters\TelefoneFormatter;
use Application\Models\Usuario;
use Application\Repositories\DocumentoRepository;
use Application\Repositories\EnderecoRepository;
use Application\Repositories\TelefoneRepository;
use Application\Repositories\UsuarioRepository;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Auth\TokenPairService;
use Application\Services\Billing\AsaasService;
use Application\Services\Infrastructure\CpfProtectionService;
use Application\Services\User\PerfilAvatarService;
use Application\Services\User\PerfilService;
use Application\UseCases\Perfil\AvatarUseCase;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UserProfileSupportDependencyResolutionTest extends TestCase
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

    public function testUserProfileSupportResolvesDependenciesFromContainerWhenAvailable(): void
    {
        $avatarService = Mockery::mock(PerfilAvatarService::class);
        $cpfProtectionService = Mockery::mock(CpfProtectionService::class);
        $documentFormatter = Mockery::mock(DocumentFormatter::class);
        $emailVerificationService = Mockery::mock(EmailVerificationService::class);
        $asaasService = Mockery::mock(AsaasService::class);
        $tokenPairService = Mockery::mock(TokenPairService::class);
        $tokenPairService->shouldReceive('issue')->once()->andReturn([
            'selector' => 'selector-12345678',
            'validator' => 'validator-1234567890',
            'token_hash' => 'hash-123',
        ]);

        $container = new IlluminateContainer();
        $container->instance(PerfilAvatarService::class, $avatarService);
        $container->instance(CpfProtectionService::class, $cpfProtectionService);
        $container->instance(DocumentFormatter::class, $documentFormatter);
        $container->instance(EmailVerificationService::class, $emailVerificationService);
        $container->instance(AsaasService::class, $asaasService);
        $container->instance(TokenPairService::class, $tokenPairService);
        ApplicationContainer::setInstance($container);

        $avatarUseCase = new AvatarUseCase();
        $documentoRepository = new DocumentoRepository();
        $perfilService = new PerfilService(
            Mockery::mock(UsuarioRepository::class),
            $documentoRepository,
            Mockery::mock(TelefoneRepository::class),
            Mockery::mock(EnderecoRepository::class),
            Mockery::mock(PerfilPayloadBuilder::class),
            Mockery::mock(DocumentFormatter::class),
            Mockery::mock(TelefoneFormatter::class),
        );

        $user = new class extends Usuario {
            public function save(array $options = []): bool
            {
                return true;
            }
        };

        $credentials = $user->generateEmailVerificationCredentials();

        $this->assertSame($avatarService, $this->readProperty($avatarUseCase, 'avatarService'));
        $this->assertSame($cpfProtectionService, $this->readProperty($documentoRepository, 'cpfProtectionService'));
        $this->assertSame($documentFormatter, $this->readProperty($documentoRepository, 'documentFormatter'));
        $this->assertSame($emailVerificationService, $this->invokePrivateMethod($perfilService, 'verificationService'));
        $this->assertSame($asaasService, $this->invokePrivateMethod($perfilService, 'asaasService'));
        $this->assertSame('selector-12345678', $credentials['selector']);
        $this->assertSame('validator-1234567890', $credentials['validator']);
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
