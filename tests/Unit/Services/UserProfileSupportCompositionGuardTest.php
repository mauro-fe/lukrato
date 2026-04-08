<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class UserProfileSupportCompositionGuardTest extends TestCase
{
    public function testUserProfileSupportDoesNotInstantiateDependenciesInline(): void
    {
        $avatarUseCase = (string) file_get_contents('Application/UseCases/Perfil/AvatarUseCase.php');
        $perfilService = (string) file_get_contents('Application/Services/User/PerfilService.php');
        $documentoRepository = (string) file_get_contents('Application/Repositories/DocumentoRepository.php');
        $usuarioModel = (string) file_get_contents('Application/Models/Usuario.php');

        $this->assertDoesNotMatchRegularExpression(
            '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
            $avatarUseCase,
            'AvatarUseCase não deve usar default inline com new no construtor.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\?\?=\s*new\s+EmailVerificationService\s*\(/',
            $perfilService,
            'PerfilService não deve instanciar EmailVerificationService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AsaasService\s*\(/',
            $perfilService,
            'PerfilService não deve instanciar AsaasService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\?\?=\s*new\s+CpfProtectionService\s*\(/',
            $documentoRepository,
            'DocumentoRepository não deve instanciar CpfProtectionService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\?\?=\s*new\s+DocumentFormatter\s*\(/',
            $documentoRepository,
            'DocumentoRepository não deve instanciar DocumentFormatter diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\?\?=\s*new\s+TokenPairService\s*\(/',
            $usuarioModel,
            'Usuario não deve instanciar TokenPairService diretamente para credenciais de verificação.'
        );
    }
}
