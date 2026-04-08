<?php

declare(strict_types=1);

namespace Application\Services\Dashboard;

use Application\Container\ApplicationContainer;
use Application\Models\Meta;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Repositories\MetaRepository;
use DateTimeImmutable;
use InvalidArgumentException;

class HealthScoreService
{
    private LancamentoRepository $lancamentoRepo;
    private OrcamentoRepository $orcamentoRepo;
    private MetaRepository $metaRepo;

    private const SCORE_RULES = [
        'savings' => [
            ['min' => 20, 'points' => 35],
            ['min' => 10, 'points' => 25],
            ['min' => 0,  'points' => 15],
        ],
        'lancamentos' => [
            ['min' => 20, 'points' => 30],
            ['min' => 10, 'points' => 20],
            ['min' => 5,  'points' => 15],
            ['min' => 1,  'points' => 8],
        ],
        'categories' => [
            ['min' => 8, 'points' => 20],
            ['min' => 5, 'points' => 15],
            ['min' => 3, 'points' => 10],
            ['min' => 1, 'points' => 5],
        ],
    ];

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?OrcamentoRepository $orcamentoRepo = null,
        ?MetaRepository $metaRepo = null
    ) {
        $this->lancamentoRepo = ApplicationContainer::resolveOrNew($lancamentoRepo, LancamentoRepository::class);
        $this->orcamentoRepo = ApplicationContainer::resolveOrNew($orcamentoRepo, OrcamentoRepository::class);
        $this->metaRepo = ApplicationContainer::resolveOrNew($metaRepo, MetaRepository::class);
    }

    public function calculateUserHealthScore(int $userId, string $month): array
    {
        $data = $this->lancamentoRepo->getResumoMes($userId, $month);

        $scoreData = $this->calculateScore($data);
        $orcamentoData = $this->calculateOrcamentos($userId, $month);
        $metasData = $this->calculateMetas($userId);

        return array_merge($scoreData, $orcamentoData, $metasData);
    }

    // =========================
    // SCORE PRINCIPAL
    // =========================

    private function calculateScore(array $data): array
    {
        $score = 0;

        $receitas = (float) ($data['receitas'] ?? 0);
        $despesas = (float) ($data['despesas'] ?? 0);
        $lancamentos = (int) ($data['count'] ?? 0);
        $categories = (int) ($data['categories'] ?? 0);
        $saldo = (float) ($data['saldo_atual'] ?? 0);

        $savingsRate = $this->calculateSavingsRate($receitas, $despesas);

        $score += $this->applyRule('savings', $savingsRate);
        $score += $this->applyRule('lancamentos', $lancamentos);
        $score += $this->applyRule('categories', $categories);

        $score += $this->calculateSaldoScore($saldo, $despesas);

        return [
            'score' => min(max($score, 0), 100),
            'savingsRate' => (int) round($savingsRate),
            'consistency' => $this->getConsistencyLabel($lancamentos),
            'categories' => $categories,
            'lancamentos' => $lancamentos,
        ];
    }

    private function applyRule(string $ruleKey, float|int $value): int
    {
        foreach (self::SCORE_RULES[$ruleKey] as $rule) {
            if ($value >= $rule['min']) {
                return $rule['points'];
            }
        }
        return 0;
    }

    private function calculateSaldoScore(float $saldo, float $despesas): int
    {
        if ($saldo > $despesas * 2) return 15;
        if ($saldo > $despesas) return 10;
        if ($saldo > 0) return 5;
        return 0;
    }

    private function getConsistencyLabel(int $lancamentos): string
    {
        return match (true) {
            $lancamentos >= 15 => 'Excelente',
            $lancamentos >= 10 => 'Ótima',
            $lancamentos >= 5  => 'Boa',
            default => 'Regular'
        };
    }

    // =========================
    // ORÇAMENTOS (SEM N+1)
    // =========================

    private function calculateOrcamentos(int $userId, string $month): array
    {
        $period = $this->parseYearMonth($month);

        $orcamentos = $this->orcamentoRepo->findByUserAndMonth(
            $userId,
            $period['monthNum'],
            $period['year']
        );

        if ($orcamentos->isEmpty()) {
            return ['orcamentos' => 0, 'orcamentos_ok' => 0];
        }

        // 🔥 BUSCA TODOS OS GASTOS DE UMA VEZ
        $gastosReais = $this->lancamentoRepo->getSomaGastosPorCategoria(
            $userId,
            $period['monthNum'],
            $period['year']
        );

        $ok = 0;
        foreach ($orcamentos as $orc) {
            $categoriaId = (int) $orc->categoria_id;
            $gasto = $gastosReais[$categoriaId] ?? 0.0;

            if ($gasto <= (float) $orc->valor_limite) {
                $ok++;
            }
        }

        return [
            'orcamentos' => $orcamentos->count(),
            'orcamentos_ok' => $ok,
        ];
    }

    // =========================
    // METAS
    // =========================

    private function calculateMetas(int $userId): array
    {
        $metas = $this->metaRepo->findByUser($userId, Meta::STATUS_ATIVA);

        $concluidas = $metas->filter(function ($meta) {
            return (float) $meta->valor_atual >= (float) $meta->valor_alvo;
        })->count();

        return [
            'metas_ativas' => $metas->count(),
            'metas_concluidas' => $concluidas,
        ];
    }

    // =========================
    // HELPERS
    // =========================

    private function calculateSavingsRate(float $receitas, float $despesas): float
    {
        if ($receitas <= 0) return 0;
        return (($receitas - $despesas) / $receitas) * 100;
    }

    private function parseYearMonth(string $month): array
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m', $month);

        if (!$date || $date->format('Y-m') !== $month) {
            throw new InvalidArgumentException('Formato de mes invalido');
        }

        return [
            'year' => (int) $date->format('Y'),
            'monthNum' => (int) $date->format('m'),
        ];
    }
}
