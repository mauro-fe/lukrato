<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class UserImportAuthMediaCompositionGuardTest extends TestCase
{
    public function testRemainingUserImportAuthAndMediaHotspotsDoNotInstantiateDependenciesInline(): void
    {
        $perfilApiWorkflowService = (string) file_get_contents('Application/Services/User/PerfilApiWorkflowService.php');
        $importPreviewService = (string) file_get_contents('Application/Services/Importacao/ImportPreviewService.php');
        $googleAuthService = (string) file_get_contents('Application/Services/Auth/GoogleAuthService.php');
        $mediaRouterService = (string) file_get_contents('Application/Services/AI/Media/MediaRouterService.php');
        $videoTranscriptionService = (string) file_get_contents('Application/Services/AI/Media/VideoTranscriptionService.php');

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AchievementService\s*\(/',
            $perfilApiWorkflowService,
            'PerfilApiWorkflowService não deve instanciar AchievementService diretamente.'
        );

        $this->assertStringNotContainsString(
            'PerfilControllerFactory::buildDependencies()',
            $perfilApiWorkflowService,
            'PerfilApiWorkflowService não deve recorrer à PerfilControllerFactory.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+PerfilService\s*\(/',
            $perfilApiWorkflowService,
            'PerfilApiWorkflowService não deve instanciar PerfilService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+PerfilValidator\s*\(/',
            $perfilApiWorkflowService,
            'PerfilApiWorkflowService não deve instanciar PerfilValidator diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->ofxImportTargetDetector\s*=\s*\$ofxImportTargetDetector\s*\?\?\s*new\s+OfxImportTargetDetector\s*\(/',
            $importPreviewService,
            'ImportPreviewService não deve instanciar OfxImportTargetDetector diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->rowCategorizationService\s*=\s*\$rowCategorizationService\s*\?\?\s*new\s+ImportRowCategorizationService\s*\(/',
            $importPreviewService,
            'ImportPreviewService não deve instanciar ImportRowCategorizationService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->authService\s*=\s*\$authService\s*\?\?\s*new\s+AuthService\s*\(/',
            $googleAuthService,
            'GoogleAuthService não deve instanciar AuthService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AudioTranscriptionService\s*\(/',
            $mediaRouterService,
            'MediaRouterService não deve instanciar AudioTranscriptionService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+ImageAnalysisService\s*\(/',
            $mediaRouterService,
            'MediaRouterService não deve instanciar ImageAnalysisService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AudioTranscriptionService\s*\(/',
            $videoTranscriptionService,
            'VideoTranscriptionService não deve instanciar AudioTranscriptionService diretamente.'
        );
    }
}
