<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\Meta;
use Application\Models\OrcamentoCategoria;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class MetasOrcamentosCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period): array
    {
        return [
            'metas'      => $this->metas(),
            'orcamentos' => $this->orcamentos($period),
        ];
    }

    private function metas(): array
    {
        $ativas     = Meta::where('status', 'ativa')->count();
        $concluidas = Meta::where('status', 'concluida')->count();
        $pausadas   = Meta::where('status', 'pausada')->count();

        $totalAlvo  = round((float) Meta::where('status', 'ativa')->sum('valor_alvo'), 2);
        $totalAtual = round((float) Meta::where('status', 'ativa')->sum('valor_atual'), 2);

        $porTipo = Meta::where('status', 'ativa')
            ->select('tipo', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(valor_alvo) as alvo'), DB::raw('SUM(valor_atual) as atual'))
            ->groupBy('tipo')
            ->get()
            ->map(fn($r) => [
                'tipo'       => $r->tipo,
                'quantidade' => (int) $r->qtd,
                'alvo'       => round((float) $r->alvo, 2),
                'atual'      => round((float) $r->atual, 2),
                'progresso'  => $r->alvo > 0 ? round(($r->atual / $r->alvo) * 100, 1) : 0,
            ])->toArray();

        return [
            'ativas'            => $ativas,
            'concluidas'        => $concluidas,
            'pausadas'          => $pausadas,
            'valor_total_alvo'  => $totalAlvo,
            'valor_total_atual' => $totalAtual,
            'progresso_geral'   => $totalAlvo > 0 ? round(($totalAtual / $totalAlvo) * 100, 1) : 0,
            'por_tipo'          => $porTipo,
        ];
    }

    private function orcamentos(ContextPeriod $p): array
    {
        $orcamentos  = OrcamentoCategoria::where('mes', $p->mesNum)->where('ano', $p->anoNum);
        $total       = $orcamentos->count();
        $limiteTotal = round((float) (clone $orcamentos)->sum('valor_limite'), 2);

        $inicioMesStr = sprintf('%04d-%02d-01', $p->anoNum, $p->mesNum);
        $fimMesStr    = date('Y-m-t', strtotime($inicioMesStr));

        $gastoReal = (float) DB::table('lancamentos')
            ->join('orcamentos_categoria', function ($join) use ($p) {
                $join->on('lancamentos.categoria_id', '=', 'orcamentos_categoria.categoria_id')
                    ->on('lancamentos.user_id', '=', 'orcamentos_categoria.user_id')
                    ->where('orcamentos_categoria.mes', $p->mesNum)
                    ->where('orcamentos_categoria.ano', $p->anoNum);
            })
            ->where('lancamentos.tipo', 'despesa')
            ->whereBetween('lancamentos.data', [$inicioMesStr, $fimMesStr])
            ->sum('lancamentos.valor');

        $estourados = (int) (DB::select("
            SELECT COUNT(*) as total FROM (
                SELECT oc.id, oc.valor_limite, COALESCE(SUM(l.valor), 0) as gasto
                FROM orcamentos_categoria oc
                LEFT JOIN lancamentos l ON l.categoria_id = oc.categoria_id
                    AND l.user_id = oc.user_id
                    AND l.tipo = 'despesa'
                    AND l.data BETWEEN ? AND ?
                WHERE oc.mes = ? AND oc.ano = ?
                GROUP BY oc.id, oc.valor_limite
                HAVING gasto > oc.valor_limite
            ) sub
        ", [$inicioMesStr, $fimMesStr, $p->mesNum, $p->anoNum])[0]->total ?? 0);

        return [
            'total_orcamentos'      => $total,
            'limite_total'          => $limiteTotal,
            'gasto_real'            => round($gastoReal, 2),
            'percentual_geral'      => $limiteTotal > 0 ? round(($gastoReal / $limiteTotal) * 100, 1) : 0,
            'orcamentos_estourados' => $estourados,
        ];
    }
}
