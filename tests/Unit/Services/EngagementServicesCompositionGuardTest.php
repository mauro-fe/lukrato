<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class EngagementServicesCompositionGuardTest extends TestCase
{
    public function testEngagementServicesDoNotInstantiateDependenciesInlineInBusinessMethods(): void
    {
        $gamificationService = (string) file_get_contents('Application/Services/Gamification/GamificationService.php');
        $achievementService = (string) file_get_contents('Application/Services/Gamification/AchievementService.php');
        $feedbackService = (string) file_get_contents('Application/Services/Feedback/FeedbackService.php');
        $referralService = (string) file_get_contents('Application/Services/Referral/ReferralService.php');

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AchievementService\s*\(\s*\);/',
            $gamificationService,
            'GamificationService não deve instanciar AchievementService diretamente nos fluxos de negócio.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+StreakService\s*\(\s*\);/',
            $gamificationService,
            'GamificationService não deve instanciar StreakService diretamente nos fluxos de negócio.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+GamificationService\s*\(\s*\);/',
            $achievementService,
            'AchievementService não deve instanciar GamificationService diretamente nos fluxos de negócio.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+FeedbackRepository\s*\(\s*\);/',
            $feedbackService,
            'FeedbackService não deve instanciar FeedbackRepository diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+ReferralAntifraudService\s*\(\s*\);/',
            $referralService,
            'ReferralService não deve instanciar ReferralAntifraudService diretamente nos fluxos de negócio.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AchievementService\s*\(\s*\);/',
            $referralService,
            'ReferralService não deve instanciar AchievementService diretamente nos fluxos de negócio.'
        );
    }
}
