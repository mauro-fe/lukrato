<?php

declare(strict_types=1);

namespace Application\Services\Lancamento;

use Application\Container\ApplicationContainer;
use Application\Models\Lancamento;
use Application\Services\Plan\PlanContext;
use Application\Services\Plan\PlanContextResolver;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Serviço responsável por gerenciar limites de lançamentos por plano
 */
class LancamentoLimitService
{
    private array $config;
    private PlanContextResolver $planResolver;

    public function __construct(?PlanContextResolver $planResolver = null)
    {
        $this->config = PlanContext::config();
        $this->planResolver = ApplicationContainer::resolveOrNew($planResolver, PlanContextResolver::class);
    }

    /**
     * Obtém o limite de lançamentos do plano gratuito
     */
    public function getFreeLimit(): int
    {
        return (int) ($this->config['limits']['free']['lancamentos_per_month'] ?? 50);
    }

    /**
     * Obtém o threshold para exibir aviso de limite
     */
    public function getWarningAt(): int
    {
        return (int) ($this->config['limits']['free']['warning_at'] ?? 40);
    }

    private function resolvePlanContext(int $userId): ?PlanContext
    {
        return $this->planResolver->resolve($userId);
    }

    /**
     * Verifica se o usuário possui plano Pro ativo.
     */
    public function isPro(int $userId): bool
    {
        return $this->planResolver->isPro($userId);
    }

    public function getPlanTier(int $userId): string
    {
        return $this->planResolver->tier($userId);
    }

    /**
     * Conta quantos lançamentos o usuário criou no mês especificado
     */
    public function countUsedInMonth(int $userId, string $ym): int
    {
        $from = $ym . '-01';
        $to   = date('Y-m-t', strtotime($from));

        return (int) Lancamento::where('user_id', $userId)
            ->whereBetween('data', [$from, $to])
            ->where('eh_transferencia', 0)
            ->count();
    }

    /**
     * Retorna informações de uso do mês atual para o usuário
     */
    public function usage(int $userId, string $ym): array
    {
        $plan = $this->resolvePlanContext($userId);
        $planSummary = $plan?->summary();

        if ($planSummary === null) {
            $planSummary = PlanContext::summaryForTier('free');
        }

        $planTier = (string) $planSummary['plan'];
        $isPaidPlan = $planTier !== 'free';
        $limit = $this->getFreeLimit();
        $warn  = $this->getWarningAt();
        $used  = $this->countUsedInMonth($userId, $ym);
        $remaining = $isPaidPlan ? null : max(0, $limit - $used);

        return [
            'month'       => $ym,
            ...$planSummary,
            'limit'       => $isPaidPlan ? null : $limit,
            'used'        => $used,
            'remaining'   => $remaining,
            'warning_at'  => $warn,
            'should_warn' => (!$isPaidPlan && $used >= $warn && $used < $limit),
            'blocked'     => (!$isPaidPlan && $used >= $limit),
            'percentage'  => $isPaidPlan ? null : (int) (($used / $limit) * 100),
        ];
    }

    /**
     * Valida se o usuário pode criar um lançamento no mês especificado
     * 
     * @throws \DomainException quando o limite for atingido
     * @return array Informações de uso atualizadas
     */
    public function assertCanCreate(int $userId, string $dateYmd): array
    {
        $ym = substr($dateYmd, 0, 7); // YYYY-MM
        $usage = $this->usage($userId, $ym);

        if ($usage['blocked'] ?? false) {
            $message = $this->getBlockedMessage($usage);
            throw new \DomainException($message);
        }

        return $usage;
    }

    /**
     * Obtém a mensagem de bloqueio personalizada
     */
    private function getBlockedMessage(array $usage): string
    {
        $template = $this->config['messages']['limit_reached'] ??
            'Você atingiu o limite de {limit} lançamentos deste mês no plano gratuito.';

        return $this->interpolateMessage($template, $usage);
    }

    /**
     * Gera mensagem de aviso apropriada baseada no uso atual
     */
    public function getWarningMessage(array $usage): ?string
    {
        if (!($usage['should_warn'] ?? false)) {
            return null;
        }

        $percentage = $usage['percentage'] ?? 0;
        $criticalThreshold = $this->config['limits']['free']['warning_critical_at'] ?? 45;

        // Determina qual template usar baseado na severidade
        if ($percentage >= 90 || $usage['used'] >= $criticalThreshold) {
            $template = $this->config['messages']['warning_critical'] ??
                '🔴 Atenção crítica! Você já usou {used} de {limit} lançamentos ({percentage}%).';
        } else {
            $template = $this->config['messages']['warning_normal'] ??
                '⚠️ Atenção: Você já usou {used} de {limit} lançamentos ({percentage}%).';
        }

        return $this->interpolateMessage($template, $usage);
    }

    /**
     * Substitui variáveis no template de mensagem
     */
    private function interpolateMessage(string $template, array $usage): string
    {
        $replacements = [
            '{used}'       => $usage['used'] ?? 0,
            '{limit}'      => $usage['limit'] ?? $this->getFreeLimit(),
            '{remaining}'  => $usage['remaining'] ?? 0,
            '{percentage}' => $usage['percentage'] ?? 0,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    /**
     * Retorna a CTA (Call-to-Action) para upgrade
     */
    public function getUpgradeCta(): string
    {
        return $this->config['messages']['upgrade_cta'] ??
            'Assine o Lukrato Pro e tenha lançamentos ilimitados!';
    }
}
