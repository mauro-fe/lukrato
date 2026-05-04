<?php

declare(strict_types=1);

namespace Application\Services\Plan;

use Application\Container\ApplicationContainer;

/**
 * Serviço para gerenciar plano e limites de usuários.
 */
class UserPlanService
{
    private array $config;
    private PlanContextResolver $planResolver;

    public function __construct(?PlanContextResolver $planResolver = null)
    {
        $this->config = PlanContext::config();
        $this->planResolver = ApplicationContainer::resolveOrNew($planResolver, PlanContextResolver::class);
    }

    /**
     * Verifica se o usuário tem plano Pro.
     */
    public function isProUser(int $userId): bool
    {
        return $this->planResolver->isPro($userId);
    }

    public function getPlanTier(int $userId): string
    {
        return $this->planResolver->tier($userId);
    }

    /**
     * Obtém o limite de lançamentos para usuários free.
     */
    public function getFreeLancamentosLimit(): int
    {
        return (int) ($this->config['limits']['free']['lancamentos_per_month'] ?? 30);
    }

    /**
     * Obtém o limite de aviso para usuários free.
     */
    public function getFreeLancamentosWarningAt(): int
    {
        return (int) ($this->config['limits']['free']['warning_at'] ?? 20);
    }

    /**
     * Constrói metadados de uso para o mês.
     */
    public function buildUsageMeta(int $userId, string $month, int $usedCount): array
    {
        $plan = $this->planResolver->resolve($userId);
        $planSummary = $plan?->summary() ?? PlanContext::summaryForTier('free');
        $planTier = (string) $planSummary['plan'];
        $isPaidPlan = $planTier !== 'free';
        $limit = $this->getFreeLancamentosLimit();
        $warn = $this->getFreeLancamentosWarningAt();

        return [
            'month' => $month,
            ...$planSummary,
            'limit' => $isPaidPlan ? null : $limit,
            'used' => $usedCount,
            'remaining' => $isPaidPlan ? null : max(0, $limit - $usedCount),
            'warning_at' => $warn,
            'should_warn' => (!$isPaidPlan && $usedCount >= $warn && $usedCount < $limit),
            'blocked' => (!$isPaidPlan && $usedCount >= $limit),
            'percentage' => $isPaidPlan ? null : (int) (($usedCount / $limit) * 100),
        ];
    }

    /**
     * Gera mensagem de UI para o usuário baseado no uso.
     */
    public function getUsageMessage(array $usage): ?string
    {
        if (!($usage['should_warn'] ?? false)) {
            return null;
        }

        $limit = $this->getFreeLancamentosLimit();
        return "⚠️ Atenção: você já usou {$usage['used']} de {$limit} lançamentos do plano gratuito. " .
            "Faltam {$usage['remaining']} este mês.";
    }
}
