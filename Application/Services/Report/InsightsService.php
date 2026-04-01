<?php

declare(strict_types=1);

namespace Application\Services\Report;

use Application\DTO\InsightItemDTO;
use Application\Enums\InsightType;
use Application\Services\Report\Insights\Concerns\HandlesInsightsBudgetAndGoals;
use Application\Services\Report\Insights\Concerns\HandlesInsightsCashflow;
use Application\Services\Report\Insights\Concerns\HandlesInsightsInitialization;
use Application\Services\Report\Insights\Concerns\HandlesInsightsSpendingPatterns;
use Carbon\Carbon;

class InsightsService
{
    use HandlesInsightsInitialization;
    use HandlesInsightsCashflow;
    use HandlesInsightsBudgetAndGoals;
    use HandlesInsightsSpendingPatterns;

    private int $userId;
    private Carbon $currentStart;
    private Carbon $currentEnd;
    private Carbon $previousStart;
    private Carbon $previousEnd;
    private float $currentReceitas = 0;
    private float $currentDespesas = 0;
    private float $previousReceitas = 0;
    private float $previousDespesas = 0;

    /**
     * Gera todos os insights financeiros para o usuário/período
     *
     * @return InsightItemDTO[]
     */
    public function generate(int $userId, int $year, int $month): array
    {
        $this->userId = $userId;
        $this->initPeriods($year, $month);
        $this->loadBaseData();

        /** @var InsightItemDTO[] $insights */
        $insights = [];

        $this->addDespesasComparison($insights);
        $this->addSaldoAnalysis($insights);
        $this->addTopCategory($insights);
        $this->addCardLimitAlert($insights);
        $this->addReceitasVariation($insights);
        $this->addBudgetAlerts($insights, $year, $month);
        $this->addGoalProgress($insights);
        $this->addLargestExpense($insights);
        $this->addDailyAverageProjection($insights, $year, $month);
        $this->addPaymentMethodPreference($insights);
        $this->addActiveInstallments($insights);
        $this->addScheduledPayments($insights);
        $this->addSpendingConcentration($insights);
        $this->addWeekendSpending($insights);
        $this->addTransactionCountVariation($insights);

        if (empty($insights)) {
            $insights[] = new InsightItemDTO(
                type: InsightType::SUCCESS,
                icon: 'check-circle',
                title: 'Tudo em ordem!',
                message: 'Suas finanças estão equilibradas neste período.',
            );
        }

        return $insights;
    }

    /**
     * Serializa os insights para resposta JSON
     *
     * @param InsightItemDTO[] $insights
     */
    public static function toArrayList(array $insights): array
    {
        return array_map(fn(InsightItemDTO $i) => $i->toArray(), $insights);
    }
}
