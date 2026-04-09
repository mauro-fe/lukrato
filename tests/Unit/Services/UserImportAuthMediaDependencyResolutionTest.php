<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Config\AuthRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Services\AI\Media\AudioTranscriptionService;
use Application\Services\AI\Media\ImageAnalysisService;
use Application\Services\AI\Media\MediaRouterService;
use Application\Services\AI\Media\VideoTranscriptionService;
use Application\Services\Auth\AuthService;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Importacao\ImportPreviewService;
use Application\Services\Importacao\ImportRowCategorizationService;
use Application\Services\Importacao\OfxImportTargetDetector;
use Application\Services\User\PerfilApiWorkflowService;
use Application\Services\User\PerfilService;
use Application\Validators\PerfilValidator;
use Google_Client;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UserImportAuthMediaDependencyResolutionTest extends TestCase
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

    public function testUserImportAuthAndMediaServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $achievementService = Mockery::mock(AchievementService::class);
        $ofxImportTargetDetector = new OfxImportTargetDetector();
        $rowCategorizationService = Mockery::mock(ImportRowCategorizationService::class);
        $googleClient = Mockery::mock(Google_Client::class);
        $authService = Mockery::mock(AuthService::class);
        $audioTranscriber = Mockery::mock(AudioTranscriptionService::class);
        $imageAnalysisService = Mockery::mock(ImageAnalysisService::class);
        $authRuntimeConfig = new AuthRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(AuthRuntimeConfig::class, $authRuntimeConfig);
        $container->instance(AchievementService::class, $achievementService);
        $container->instance(OfxImportTargetDetector::class, $ofxImportTargetDetector);
        $container->instance(ImportRowCategorizationService::class, $rowCategorizationService);
        $container->instance(Google_Client::class, $googleClient);
        $container->instance(AuthService::class, $authService);
        $container->instance(AudioTranscriptionService::class, $audioTranscriber);
        $container->instance(ImageAnalysisService::class, $imageAnalysisService);
        ApplicationContainer::setInstance($container);

        $perfilApiWorkflowService = new PerfilApiWorkflowService(
            Mockery::mock(PerfilService::class),
            Mockery::mock(PerfilValidator::class)
        );
        $importPreviewService = new ImportPreviewService();
        $googleAuthService = new GoogleAuthService();
        $mediaRouterService = new MediaRouterService();
        $videoTranscriptionService = new VideoTranscriptionService();

        $this->assertSame($achievementService, $this->readProperty($perfilApiWorkflowService, 'achievementService'));
        $this->assertSame($ofxImportTargetDetector, $this->readProperty($importPreviewService, 'ofxImportTargetDetector'));
        $this->assertSame($rowCategorizationService, $this->readProperty($importPreviewService, 'rowCategorizationService'));
        $this->assertSame($googleClient, $this->readProperty($googleAuthService, 'client'));
        $this->assertSame($authService, $this->readProperty($googleAuthService, 'authService'));
        $this->assertSame($authRuntimeConfig, $this->readProperty($googleAuthService, 'runtimeConfig'));
        $this->assertSame($audioTranscriber, $this->readProperty($mediaRouterService, 'audioTranscriber'));
        $this->assertSame($imageAnalysisService, $this->readProperty($mediaRouterService, 'receiptAnalyzer'));
        $this->assertSame($audioTranscriber, $this->readProperty($videoTranscriptionService, 'transcriber'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
