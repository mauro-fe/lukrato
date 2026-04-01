<?php

declare(strict_types=1);

namespace Application\Services\Orcamentos;

use Application\Repositories\OrcamentoRepository;

class OrcamentoMetricsService
{
    public function calcularRollover(
        OrcamentoRepository $repo,
        int $userId,
        int $categoriaId,
        int $mes,
        int $ano
    ): float {
        $mesAnterior = $mes === 1 ? 12 : $mes - 1;
        $anoAnterior = $mes === 1 ? $ano - 1 : $ano;

        $orcAnterior = $repo->findByCategoriaAndMonth($userId, $categoriaId, $mesAnterior, $anoAnterior);
        if (!$orcAnterior) {
            return 0;
        }

        $gastoAnterior = $repo->getGastoRealComFallback($userId, $categoriaId, $mesAnterior, $anoAnterior);
        $sobra = $orcAnterior->valor_limite - $gastoAnterior;

        return max(0, round($sobra, 2));
    }

    public function calcularTendencia(OrcamentoRepository $repo, int $userId, int $categoriaId): string
    {
        $gastos = [];
        for ($i = 3; $i >= 1; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $gastos[] = $repo->getGastoRealComFallback(
                $userId,
                $categoriaId,
                (int) $date->format('m'),
                (int) $date->format('Y')
            );
        }

        if (count(array_filter($gastos)) < 2) {
            return 'insuficiente';
        }

        if (count($gastos) < 2) {
            return 'estavel';
        }

        $ultimo = $gastos[count($gastos) - 1];
        $penultimo = $gastos[count($gastos) - 2];

        if ($penultimo == 0) {
            return 'estavel';
        }

        $variacao = (($ultimo - $penultimo) / $penultimo) * 100;

        if ($variacao > 15) {
            return 'subindo';
        }

        if ($variacao < -15) {
            return 'descendo';
        }

        return 'estavel';
    }

    public function getStatusOrcamento(float $percentual): string
    {
        if ($percentual >= 100) {
            return 'estourado';
        }

        if ($percentual >= 80) {
            return 'alerta';
        }

        if ($percentual >= 50) {
            return 'atencao';
        }

        return 'ok';
    }

    public function calcularSaudeFinanceira(array $orcamentos): array
    {
        if (empty($orcamentos)) {
            return ['score' => 100, 'label' => 'Excelente', 'cor' => '#10b981'];
        }

        $scores = [];
        foreach ($orcamentos as $orc) {
            if ($orc['percentual'] <= 50) {
                $scores[] = 100;
            } elseif ($orc['percentual'] <= 80) {
                $scores[] = 70;
            } elseif ($orc['percentual'] <= 100) {
                $scores[] = 40;
            } else {
                $scores[] = max(0, 20 - ($orc['percentual'] - 100));
            }
        }

        $score = round(array_sum($scores) / count($scores));

        if ($score >= 80) {
            return ['score' => $score, 'label' => 'Excelente', 'cor' => '#10b981'];
        }

        if ($score >= 60) {
            return ['score' => $score, 'label' => 'Bom', 'cor' => '#3b82f6'];
        }

        if ($score >= 40) {
            return ['score' => $score, 'label' => 'Atenção', 'cor' => '#f59e0b'];
        }

        return ['score' => $score, 'label' => 'Crítico', 'cor' => '#ef4444'];
    }
}
