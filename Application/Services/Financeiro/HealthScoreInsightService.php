<?php

namespace Application\Services\Financeiro;

use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;

class HealthScoreInsightService
{
    public function __construct(
        private LancamentoRepository $lancamentoRepo,
        private MetaRepository $metaRepo
    ) {}

    public function generate(int $userId, string $month): array
    {
        $data = $this->lancamentoRepo->getResumoMes($userId, $month);

        $insights = [];

        $receitas = (float)($data['receitas'] ?? 0);
        $despesas = (float)($data['despesas'] ?? 0);
        $saldo = (float)($data['saldo_atual'] ?? 0);
        $lancamentos = (int)($data['count'] ?? 0);
        $categories = (int)($data['categories'] ?? 0);

        if ($saldo < 0) {
            $insights[] = [
                'type' => 'negative_balance',
                'priority' => 'critical',
                'message' => 'Seu saldo está negativo',
            ];
        }

        if ($lancamentos < 5) {
            $insights[] = [
                'type' => 'low_activity',
                'priority' => 'high',
                'message' => 'Você registrou poucas transações',
            ];
        }

        if ($categories < 3) {
            $insights[] = [
                'type' => 'low_categories',
                'priority' => 'medium',
                'message' => 'Você usa poucas categorias',
            ];
        }

        if ($this->metaRepo->countAtivas($userId) === 0) {
            $insights[] = [
                'type' => 'no_goals',
                'priority' => 'medium',
                'message' => 'Você não tem metas financeiras',
            ];
        }

        return $insights;
    }
}
