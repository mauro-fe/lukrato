<?php

namespace Application\Services\Financeiro;

use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;

class DashboardInsightService
{
    private LancamentoRepository $lancamentoRepo;
    private MetaRepository $metaRepo;

    public function __construct(
        LancamentoRepository $lancamentoRepo,
        MetaRepository $metaRepo
    ) {
        $this->lancamentoRepo = $lancamentoRepo;
        $this->metaRepo = $metaRepo;
    }

    public function buildComparativoCompetenciaCaixaResponse(array $comparativo, string $month): array
    {
        $difReceitas = $comparativo['competencia']['receitas'] - $comparativo['caixa']['receitas'];
        $difDespesas = $comparativo['competencia']['despesas'] - $comparativo['caixa']['despesas'];

        return [
            'month' => $month,
            'competencia' => [
                'receitas' => $comparativo['competencia']['receitas'],
                'despesas' => $comparativo['competencia']['despesas'],
                'resultado' => $comparativo['competencia']['receitas'] - $comparativo['competencia']['despesas'],
            ],
            'caixa' => [
                'receitas' => $comparativo['caixa']['receitas'],
                'despesas' => $comparativo['caixa']['despesas'],
                'resultado' => $comparativo['caixa']['receitas'] - $comparativo['caixa']['despesas'],
            ],
            'diferenca' => [
                'receitas' => $difReceitas,
                'despesas' => $difDespesas,
                'resultado' => ($comparativo['competencia']['receitas'] - $comparativo['competencia']['despesas']) -
                    ($comparativo['caixa']['receitas'] - $comparativo['caixa']['despesas']),
            ],
        ];
    }

    public function getRecentTransactions(int $userId, string $from, string $to, int $limit): array
    {
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
}
