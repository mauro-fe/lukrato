<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Admin\FeedbackAdminController;
use Application\Controllers\Api\Categoria\CategoriaController;
use Application\Controllers\Api\Categoria\SubcategoriaController;
use Application\Controllers\Api\Configuracoes\PreferenciaUsuarioController;
use Application\Controllers\Api\Feedback\FeedbackController;
use Application\Controllers\Api\Gamification\GamificationController;
use Application\Controllers\Api\Notification\NotificationController;
use Application\Controllers\Api\Plan\PlanController;
use Application\Controllers\Api\Referral\ReferralController;
use Application\Controllers\Api\User\ContactController;
use Application\Controllers\Api\User\SupportController;
use Application\Controllers\Settings\AccountController;
use Application\Controllers\Site\AprendaController;
use Application\Repositories\BlogPostRepository;
use Application\Repositories\CategoriaRepository;
use Application\Services\Categoria\SubcategoriaService;
use Application\Services\Communication\MailService;
use Application\Services\Communication\NotificationService;
use Application\Services\Feedback\FeedbackService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Gamification\MissionService;
use Application\Services\Gamification\StreakService;
use Application\Services\Plan\PlanLimitService;
use Application\Services\Referral\ReferralAntifraudService;
use Application\Services\Referral\ReferralService;
use Application\UseCases\Configuracoes\PreferenciasUsuarioUseCase;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SimpleControllerDependencyResolutionTest extends TestCase
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

    public function testSimpleApiControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $preferencesUseCase = Mockery::mock(PreferenciasUsuarioUseCase::class);
        $mailService = Mockery::mock(MailService::class);
        $planLimitService = Mockery::mock(PlanLimitService::class);
        $referralService = Mockery::mock(ReferralService::class);
        $subcategoriaService = Mockery::mock(SubcategoriaService::class);
        $categoriaRepository = Mockery::mock(CategoriaRepository::class);
        $gamificationService = Mockery::mock(GamificationService::class);
        $achievementService = Mockery::mock(AchievementService::class);
        $notificationService = Mockery::mock(NotificationService::class);
        $feedbackService = Mockery::mock(FeedbackService::class);
        $streakService = Mockery::mock(StreakService::class);
        $missionService = Mockery::mock(MissionService::class);

        $container = new IlluminateContainer();
        $container->instance(PreferenciasUsuarioUseCase::class, $preferencesUseCase);
        $container->instance(MailService::class, $mailService);
        $container->instance(PlanLimitService::class, $planLimitService);
        $container->instance(ReferralService::class, $referralService);
        $container->instance(SubcategoriaService::class, $subcategoriaService);
        $container->instance(CategoriaRepository::class, $categoriaRepository);
        $container->instance(GamificationService::class, $gamificationService);
        $container->instance(AchievementService::class, $achievementService);
        $container->instance(NotificationService::class, $notificationService);
        $container->instance(FeedbackService::class, $feedbackService);
        $container->instance(StreakService::class, $streakService);
        $container->instance(MissionService::class, $missionService);
        ApplicationContainer::setInstance($container);

        $preferencesController = new PreferenciaUsuarioController();
        $supportController = new SupportController();
        $contactController = new ContactController();
        $planController = new PlanController();
        $referralController = new ReferralController();
        $subcategoriaController = new SubcategoriaController();
        $categoriaController = new CategoriaController();
        $notificationController = new NotificationController();
        $feedbackAdminController = new FeedbackAdminController();
        $feedbackController = new FeedbackController();
        $gamificationController = new GamificationController();

        $this->assertSame($preferencesUseCase, $this->readProperty($preferencesController, 'useCase'));
        $this->assertSame($mailService, $this->readProperty($supportController, 'mailService'));
        $this->assertSame($mailService, $this->readProperty($contactController, 'mail'));
        $this->assertSame($planLimitService, $this->readProperty($planController, 'limitService'));
        $this->assertSame($referralService, $this->readProperty($referralController, 'referralService'));
        $this->assertSame($subcategoriaService, $this->readProperty($subcategoriaController, 'service'));
        $this->assertSame($categoriaRepository, $this->readProperty($categoriaController, 'categoriaRepo'));
        $this->assertSame($planLimitService, $this->readProperty($categoriaController, 'planLimitService'));
        $this->assertSame($gamificationService, $this->readProperty($categoriaController, 'gamificationService'));
        $this->assertSame($achievementService, $this->readProperty($categoriaController, 'achievementService'));
        $this->assertSame($notificationService, $this->readProperty($notificationController, 'notificationService'));
        $this->assertSame($feedbackService, $this->readProperty($feedbackAdminController, 'service'));
        $this->assertSame($feedbackService, $this->readProperty($feedbackController, 'service'));
        $this->assertSame($achievementService, $this->readProperty($gamificationController, 'achievementService'));
        $this->assertSame($streakService, $this->readProperty($gamificationController, 'streakService'));
        $this->assertSame($missionService, $this->readProperty($gamificationController, 'missionService'));
    }

    public function testSiteAndSettingsControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $blogRepository = Mockery::mock(BlogPostRepository::class);
        $antifraudService = Mockery::mock(ReferralAntifraudService::class);

        $container = new IlluminateContainer();
        $container->instance(BlogPostRepository::class, $blogRepository);
        $container->instance(ReferralAntifraudService::class, $antifraudService);
        ApplicationContainer::setInstance($container);

        $aprendaController = new AprendaController();
        $accountController = new AccountController();

        $this->assertSame($blogRepository, $this->readProperty($aprendaController, 'repo'));
        $this->assertSame($antifraudService, $this->readProperty($accountController, 'antifraudService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
