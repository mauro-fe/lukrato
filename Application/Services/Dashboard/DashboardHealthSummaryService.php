<?php

declare(strict_types=1);

namespace Application\Services\Dashboard;

use Application\Container\ApplicationContainer;

class DashboardHealthSummaryService
{
    private HealthScoreService $healthScoreService;
    private HealthScoreInsightService $healthScoreInsightService;

    public function __construct(
        ?HealthScoreService $healthScoreService = null,
        ?HealthScoreInsightService $healthScoreInsightService = null
    ) {
        $this->healthScoreService = ApplicationContainer::resolveOrNew($healthScoreService, HealthScoreService::class);
        $this->healthScoreInsightService = ApplicationContainer::resolveOrNew($healthScoreInsightService, HealthScoreInsightService::class);
    }

    public function generate(int $userId, string $month): array
    {
        // 1. Score
        $score = $this->healthScoreService->calculateUserHealthScore($userId, $month);

        // 2. Insights
        $insights = $this->healthScoreInsightService->generate($userId, $month);

        // 3. Ordenar insights por prioridade
        usort($insights, function ($a, $b) {
            return $this->priorityWeight($b['priority']) <=> $this->priorityWeight($a['priority']);
        });

        // 4. Recomendações
        $recommendations = $this->buildRecommendations($insights);

        // 5. Status do score
        $status = $this->resolveStatus($score['score'] ?? 0);

        return [
            'score' => $score,
            'status' => $status,
            'insights' => $insights,
            'recommendations' => $recommendations,
        ];
    }

    private function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'critical' => 3,
            'high' => 2,
            'medium' => 1,
            default => 0,
        };
    }

    private function resolveStatus(int $score): array
    {
        return match (true) {
            $score >= 80 => [
                'label' => 'excelente',
                'color' => 'success',
            ],
            $score >= 60 => [
                'label' => 'bom',
                'color' => 'primary',
            ],
            $score >= 40 => [
                'label' => 'regular',
                'color' => 'warning',
            ],
            default => [
                'label' => 'critico',
                'color' => 'danger',
            ],
        };
    }

    private function buildRecommendations(array $insights): array
    {
        $recommendations = [];

        foreach ($insights as $insight) {
            switch ($insight['type']) {
                case 'negative_balance':
                    $recommendations[] = 'Reduza despesas imediatamente';
                    break;

                case 'low_activity':
                    $recommendations[] = 'Registre mais transações para melhor controle';
                    break;

                case 'low_categories':
                    $recommendations[] = 'Organize seus gastos em mais categorias';
                    break;

                case 'no_goals':
                    $recommendations[] = 'Defina metas financeiras';
                    break;
            }
        }

        return array_values(array_unique($recommendations));
    }
}
