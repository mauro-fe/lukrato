<?php

declare(strict_types=1);

namespace Application\Services\Dashboard;

use Application\Container\ApplicationContainer;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;

class HealthScoreInsightService
{
    private LancamentoRepository $lancamentoRepo;
    private MetaRepository $metaRepo;
    private OrcamentoRepository $orcamentoRepo;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?MetaRepository $metaRepo = null,
        ?OrcamentoRepository $orcamentoRepo = null
    ) {
        $this->lancamentoRepo = ApplicationContainer::resolveOrNew($lancamentoRepo, LancamentoRepository::class);
        $this->metaRepo = ApplicationContainer::resolveOrNew($metaRepo, MetaRepository::class);
        $this->orcamentoRepo = ApplicationContainer::resolveOrNew($orcamentoRepo, OrcamentoRepository::class);
    }

    public function generate(int $userId, string $month): array
    {
        $data = $this->lancamentoRepo->getResumoMes($userId, $month);

        $insights = [];

        $receitas = (float)($data['receitas'] ?? 0);
        $despesas = (float)($data['despesas'] ?? 0);
        $saldo = (float)($data['saldo_atual'] ?? 0);
        $lancamentos = (int)($data['count'] ?? 0);
        $categories = (int)($data['categories'] ?? 0);
        $resultado = $receitas - $despesas;

        // 1. Saldo negativo
        if ($saldo < 0) {
            $insights[] = [
                'type' => 'negative_balance',
                'priority' => 'critical',
                'title' => 'Saldo no vermelho',
                'message' => 'Revise despesas não essenciais para equilibrar suas contas.',
                'metric' => 'R$ ' . number_format(abs($saldo), 2, ',', '.'),
                'metric_label' => 'negativo',
            ];
        }

        // 2. Gastando mais do que ganha neste mês
        if ($receitas > 0 && $despesas > $receitas) {
            $excesso = round((($despesas - $receitas) / $receitas) * 100);
            $insights[] = [
                'type' => 'overspending',
                'priority' => 'critical',
                'title' => 'Gastos acima da receita',
                'message' => "Você gastou {$excesso}% a mais do que recebeu. Corte o que puder.",
                'metric' => $excesso . '%',
                'metric_label' => 'acima',
            ];
        }

        // 3. Taxa de economia
        if ($receitas > 0 && $resultado >= 0) {
            $savingsRate = round(($resultado / $receitas) * 100);
            if ($savingsRate < 10) {
                $insights[] = [
                    'type' => 'low_savings',
                    'priority' => 'high',
                    'title' => 'Economia muito baixa',
                    'message' => 'Especialistas recomendam guardar pelo menos 20% da renda.',
                    'metric' => $savingsRate . '%',
                    'metric_label' => 'guardado',
                ];
            } elseif ($savingsRate < 20) {
                $insights[] = [
                    'type' => 'moderate_savings',
                    'priority' => 'medium',
                    'title' => 'Quase lá! Aumente a economia',
                    'message' => 'Faltam ' . (20 - $savingsRate) . '% para a meta ideal de 20%.',
                    'metric' => $savingsRate . '%',
                    'metric_label' => 'guardado',
                ];
            }
        }

        // 4. Poucos registros
        if ($lancamentos < 5) {
            $insights[] = [
                'type' => 'low_activity',
                'priority' => 'high',
                'title' => 'Registre suas movimentações',
                'message' => 'Quanto mais registros, melhor o controle. Lance receitas e despesas.',
                'metric' => (string) $lancamentos,
                'metric_label' => 'lançados',
            ];
        }

        // 5. Poucas categorias
        if ($categories < 3 && $lancamentos > 0) {
            $insights[] = [
                'type' => 'low_categories',
                'priority' => 'medium',
                'title' => 'Organize por categorias',
                'message' => 'Categorizar ajuda a entender para onde vai seu dinheiro.',
                'metric' => (string) $categories,
                'metric_label' => 'categorias',
            ];
        }

        // 6. Sem metas definidas
        $metasAtivas = $this->metaRepo->countAtivas($userId);
        if ($metasAtivas === 0) {
            $insights[] = [
                'type' => 'no_goals',
                'priority' => 'medium',
                'title' => 'Crie sua primeira meta',
                'message' => 'Metas financeiras deixam o progresso visível e motivam.',
                'metric' => '0',
                'metric_label' => 'metas',
            ];
        }

        // 7. Sem orçamentos
        $monthParts = explode('-', $month);
        $orcCount = count($this->orcamentoRepo->findByUserAndMonth(
            $userId,
            (int) ($monthParts[1] ?? date('n')),
            (int) ($monthParts[0] ?? date('Y'))
        ));
        if ($orcCount === 0 && $lancamentos >= 5) {
            $insights[] = [
                'type' => 'no_budgets',
                'priority' => 'medium',
                'title' => 'Defina limites de gastos',
                'message' => 'Orçamentos por categoria ajudam a manter o controle.',
                'metric' => '0',
                'metric_label' => 'limites',
            ];
        }

        return $insights;
    }
}
