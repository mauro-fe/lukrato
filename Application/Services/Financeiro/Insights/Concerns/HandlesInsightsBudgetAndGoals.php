<?php

declare(strict_types=1);

namespace Application\Services\Financeiro\Insights\Concerns;

use Application\DTO\InsightItemDTO;
use Application\Enums\InsightType;
use Application\Models\CartaoCredito;
use Application\Models\Lancamento;
use Application\Models\Meta;
use Application\Models\OrcamentoCategoria;
use Application\Models\Parcelamento;
use Carbon\Carbon;

trait HandlesInsightsBudgetAndGoals
{
    private function addCardLimitAlert(array &$insights): void
    {
        $cartoes = CartaoCredito::where('user_id', $this->userId)
            ->whereNotNull('limite_total')
            ->where('limite_total', '>', 0)
            ->get();

        $alertCount = 0;
        foreach ($cartoes as $cartao) {
            $gasto = Lancamento::where('user_id', $this->userId)
                ->where('cartao_credito_id', $cartao->id)
                ->where('pago', 1)
                ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
                ->sum('valor');

            if ($cartao->limite_total > 0 && ($gasto / $cartao->limite_total) > 0.8) {
                $alertCount++;
            }
        }

        if ($alertCount > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::WARNING,
                icon: 'credit-card',
                title: 'Atenção ao limite do cartão',
                message: sprintf(
                    '%s %s próximo%s do limite',
                    $alertCount,
                    $alertCount > 1 ? 'cartões estão' : 'cartão está',
                    $alertCount > 1 ? 's' : ''
                ),
            );
        }
    }

    private function addBudgetAlerts(array &$insights, int $year, int $month): void
    {
        $orcamentos = OrcamentoCategoria::where('user_id', $this->userId)
            ->where('mes', $month)
            ->where('ano', $year)
            ->where('valor_limite', '>', 0)
            ->with('categoria')
            ->get();

        $estourados = 0;
        $proximos = 0;
        $nomeEstourado = '';

        foreach ($orcamentos as $orc) {
            $gasto = Lancamento::where('user_id', $this->userId)
                ->where('tipo', 'despesa')
                ->where('pago', 1)
                ->where('afeta_caixa', 1)
                ->where('categoria_id', $orc->categoria_id)
                ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
                ->where(function ($q) {
                    $q->whereNull('origem_tipo')
                        ->orWhere('origem_tipo', '!=', 'pagamento_fatura');
                })
                ->sum('valor');

            $pct = ($orc->valor_limite > 0) ? ($gasto / $orc->valor_limite) * 100 : 0;

            if ($pct >= 100) {
                $estourados++;
                if (empty($nomeEstourado) && $orc->categoria) {
                    $nomeEstourado = $orc->categoria->nome;
                }
            } elseif ($pct >= 80) {
                $proximos++;
            }
        }

        if ($estourados > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::DANGER,
                icon: 'shield-alert',
                title: 'Orçamento estourado',
                message: $estourados === 1
                    ? sprintf('O orçamento de "%s" foi ultrapassado este mês', $nomeEstourado)
                    : sprintf('%d categorias ultrapassaram o orçamento definido', $estourados),
            );
        } elseif ($proximos > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::WARNING,
                icon: 'gauge',
                title: 'Orçamento quase no limite',
                message: sprintf(
                    '%d %s acima de 80%% do orçamento mensal',
                    $proximos,
                    $proximos === 1 ? 'categoria está' : 'categorias estão'
                ),
            );
        }
    }

    private function addGoalProgress(array &$insights): void
    {
        $metas = Meta::where('user_id', $this->userId)
            ->where('status', 'ativa')
            ->get();

        foreach ($metas as $meta) {
            $progresso = $meta->valor_alvo > 0
                ? ($meta->valor_atual / $meta->valor_alvo) * 100
                : 0;

            if ($progresso >= 90 && $progresso < 100) {
                $insights[] = new InsightItemDTO(
                    type: InsightType::SUCCESS,
                    icon: 'target',
                    title: 'Meta quase alcançada!',
                    message: sprintf(
                        '"%s" está em %.0f%% — faltam apenas R$ %.2f!',
                        $meta->titulo,
                        $progresso,
                        $meta->valor_alvo - $meta->valor_atual
                    ),
                    value: (float) ($meta->valor_alvo - $meta->valor_atual),
                    percentage: $progresso,
                );
                continue;
            }

            if ($meta->data_prazo) {
                $diasRestantes = Carbon::now()->diffInDays(Carbon::parse($meta->data_prazo), false);
                if ($diasRestantes > 0 && $diasRestantes <= 30 && $progresso < 70) {
                    $insights[] = new InsightItemDTO(
                        type: InsightType::WARNING,
                        icon: 'clock',
                        title: 'Meta com prazo próximo',
                        message: sprintf(
                            '"%s" vence em %d dias e está em %.0f%%. Considere aumentar os aportes.',
                            $meta->titulo,
                            $diasRestantes,
                            $progresso
                        ),
                        percentage: $progresso,
                    );
                    continue;
                }
            }
        }
    }

    private function addActiveInstallments(array &$insights): void
    {
        $parcelamentos = Parcelamento::where('user_id', $this->userId)
            ->where('status', 'ativo')
            ->get();

        if ($parcelamentos->count() === 0) {
            return;
        }

        $totalMensal = 0;
        foreach ($parcelamentos as $parc) {
            if ($parc->numero_parcelas > 0) {
                $totalMensal += ($parc->valor_total / $parc->numero_parcelas);
            }
        }

        if ($totalMensal > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::INFO,
                icon: 'layers',
                title: 'Parcelas comprometidas',
                message: sprintf(
                    'Você tem %d parcelamento%s ativo%s consumindo ~R$ %.2f/mês',
                    $parcelamentos->count(),
                    $parcelamentos->count() > 1 ? 's' : '',
                    $parcelamentos->count() > 1 ? 's' : '',
                    $totalMensal
                ),
                value: $totalMensal,
            );
        }
    }

    private function addScheduledPayments(array &$insights): void
    {
        $count = Lancamento::where('user_id', $this->userId)
            ->where('pago', false)
            ->where('tipo', 'despesa')
            ->where('data', '>=', Carbon::now()->toDateString())
            ->where('data', '<=', Carbon::now()->addDays(7)->toDateString())
            ->count();

        if ($count > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::INFO,
                icon: 'calendar-clock',
                title: 'Pagamentos agendados',
                message: sprintf(
                    '%d pagamento%s agendado%s para os próximos 7 dias',
                    $count,
                    $count > 1 ? 's' : '',
                    $count > 1 ? 's' : ''
                ),
            );
        }
    }
}
