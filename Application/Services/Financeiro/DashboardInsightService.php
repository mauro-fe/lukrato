<?php

namespace Application\Services\Financeiro;

use Application\Repositories\LancamentoRepository;

class DashboardInsightService
{
    private LancamentoRepository $lancamentoRepo;

    public function __construct(LancamentoRepository $lancamentoRepo)
    {
        $this->lancamentoRepo = $lancamentoRepo;
    }

    public function buildComparativoCompetenciaCaixaResponse(array $comparativo, string $month): array
    {
        $competenciaReceitas = $this->toFloat($comparativo['competencia']['receitas'] ?? 0);
        $competenciaDespesas = $this->toFloat($comparativo['competencia']['despesas'] ?? 0);
        $caixaReceitas = $this->toFloat($comparativo['caixa']['receitas'] ?? 0);
        $caixaDespesas = $this->toFloat($comparativo['caixa']['despesas'] ?? 0);
        $difReceitas = $competenciaReceitas - $caixaReceitas;
        $difDespesas = $competenciaDespesas - $caixaDespesas;

        return [
            'month' => $month,
            'competencia' => [
                'receitas' => $competenciaReceitas,
                'despesas' => $competenciaDespesas,
                'resultado' => $competenciaReceitas - $competenciaDespesas,
            ],
            'caixa' => [
                'receitas' => $caixaReceitas,
                'despesas' => $caixaDespesas,
                'resultado' => $caixaReceitas - $caixaDespesas,
            ],
            'diferenca' => [
                'receitas' => $difReceitas,
                'despesas' => $difDespesas,
                'resultado' => ($competenciaReceitas - $competenciaDespesas) - ($caixaReceitas - $caixaDespesas),
            ],
        ];
    }

    public function getRecentTransactions(int $userId, string $from, string $to, int $limit): array
    {
        $limit = max(0, $limit);
        if ($limit === 0) {
            return [];
        }

        $rows = $this->lancamentoRepo->getRecentTransactions($userId, $from, $to, $limit);

        return $rows->map(fn($r) => [
            'id' => (int)$r->id,
            'data' => (string)$r->data,
            'tipo' => (string)$r->tipo,
            'valor' => (float)$r->valor,
            'descricao' => (string)($r->descricao ?? ''),
            'categoria_id' => (int)$r->categoria_id ?: null,
            'conta_id' => (int)$r->conta_id ?: null,
            'categoria' => (string)$r->categoria,
            'conta' => (string)$r->conta,
        ])->values()->all();
    }

    public function generateGreetingInsight(int $userId, string $currentMonth, string $previousMonth): array
    {
        $currentData = $this->lancamentoRepo->getResumoMes($userId, $currentMonth);
        $previousData = $this->lancamentoRepo->getResumoMes($userId, $previousMonth);

        return $this->generateInsight($currentData, $previousData);
    }

    private function generateInsight(array $currentData, array $previousData): array
    {
        $receitas = (float)($currentData['receitas'] ?? 0);
        $despesas = (float)($currentData['despesas'] ?? 0);
        $saldo = $receitas - $despesas;

        $receitasAnterior = (float)($previousData['receitas'] ?? 0);
        $despesasAnterior = (float)($previousData['despesas'] ?? 0);
        $saldoAnterior = $receitasAnterior - $despesasAnterior;

        $insights = [];

        if ($saldoAnterior > 0 && $saldo > $saldoAnterior) {
            $crescimento = (($saldo - $saldoAnterior) / abs($saldoAnterior)) * 100;
            $insights[] = [
                'message' => "Seu saldo cresceu " . round($crescimento) . "% este mês!",
                'icon' => 'trending-up',
                'color' => '#10b981',
                'weight' => 10,
            ];
        }

        if ($despesasAnterior > 0 && $despesas < $despesasAnterior) {
            $reducao = (($despesasAnterior - $despesas) / $despesasAnterior) * 100;
            $insights[] = [
                'message' => "Você economizou " . round($reducao) . "% em despesas!",
                'icon' => 'zap',
                'color' => '#3b82f6',
                'weight' => 9,
            ];
        }

        if ($receitasAnterior > 0 && $receitas > $receitasAnterior) {
            $aumento = (($receitas - $receitasAnterior) / $receitasAnterior) * 100;
            $insights[] = [
                'message' => "Suas receitas subiram " . round($aumento) . "% em relação ao mês passado!",
                'icon' => 'arrow-up',
                'color' => '#10b981',
                'weight' => 8,
            ];
        }

        if ($saldoAnterior <= 0 && $saldo > 0) {
            $insights[] = [
                'message' => "Parabéns! Seu saldo voltou a ser positivo este mês.",
                'icon' => 'smile',
                'color' => '#10b981',
                'weight' => 9,
            ];
        }

        $lancamentos = (int)($currentData['count'] ?? 0);
        if ($lancamentos >= 30) {
            $insights[] = [
                'message' => "Ótimo controle! Você registrou " . $lancamentos . " transações.",
                'icon' => 'activity',
                'color' => '#3b82f6',
                'weight' => 7,
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'message' => "Seu saldo atual está em R$ " . number_format($saldo, 2, ',', '.'),
                'icon' => 'wallet',
                'color' => 'var(--color-primary)',
                'weight' => 1,
            ];
        }

        usort($insights, fn($a, $b) => $b['weight'] - $a['weight']);
        $topInsights = array_slice($insights, 0, 3);
        $selected = $topInsights[array_rand($topInsights)];

        unset($selected['weight']);

        return $selected;
    }

    private function toFloat(mixed $value): float
    {
        return (float) ($value ?? 0);
    }
}
