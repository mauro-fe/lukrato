<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Application\Models\UserCategoryRule;
use PHPUnit\Framework\TestCase;

class UserCategoryRuleTest extends TestCase
{
    public function testWeakPatternsAreRejected(): void
    {
        $this->assertTrue(UserCategoryRule::isWeakPattern('despesa'));
        $this->assertTrue(UserCategoryRule::isWeakPattern('valor'));
        $this->assertTrue(UserCategoryRule::isWeakPattern('site'));
        $this->assertFalse(UserCategoryRule::isWeakPattern('mercado'));
        $this->assertFalse(UserCategoryRule::isWeakPattern('remedio'));
        $this->assertFalse(UserCategoryRule::isWeakPattern('99'));
    }

    public function testConfirmedRuleNeedsSecondHitBeforeMatching(): void
    {
        $rule = new UserCategoryRule();
        $rule->pattern = 'mercado';
        $rule->normalized_pattern = 'mercado';
        $rule->source = 'confirmed';
        $rule->usage_count = 1;

        $this->assertTrue(UserCategoryRule::requiresMoreConfirmations($rule));
        $this->assertFalse(UserCategoryRule::shouldUseForMatching($rule));

        $rule->usage_count = 2;

        $this->assertFalse(UserCategoryRule::requiresMoreConfirmations($rule));
        $this->assertTrue(UserCategoryRule::shouldUseForMatching($rule));
    }

    public function testCorrectionRuleRemainsImmediatelyActive(): void
    {
        $rule = new UserCategoryRule();
        $rule->pattern = 'produto de limpeza';
        $rule->normalized_pattern = 'produto de limpeza';
        $rule->source = 'correction';
        $rule->usage_count = 1;

        $this->assertFalse(UserCategoryRule::requiresMoreConfirmations($rule));
        $this->assertTrue(UserCategoryRule::shouldUseForMatching($rule));
    }

    public function testAuditFlagsSeparateWeakPatternsFromWarmupRules(): void
    {
        $weakRule = new UserCategoryRule();
        $weakRule->pattern = 'despesa';
        $weakRule->normalized_pattern = 'despesa';
        $weakRule->source = 'confirmed';
        $weakRule->usage_count = 1;

        $pendingRule = new UserCategoryRule();
        $pendingRule->pattern = 'mercado';
        $pendingRule->normalized_pattern = 'mercado';
        $pendingRule->source = 'confirmed';
        $pendingRule->usage_count = 1;

        $this->assertSame(
            ['weak_pattern', 'pending_confirmation_threshold'],
            UserCategoryRule::getAuditFlags($weakRule)
        );
        $this->assertSame(
            ['pending_confirmation_threshold'],
            UserCategoryRule::getAuditFlags($pendingRule)
        );
    }
}
