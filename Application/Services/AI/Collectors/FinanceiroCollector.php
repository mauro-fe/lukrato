<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\Lancamento;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class FinanceiroCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period): array
    {
        return [
            'financeiro'       => $this->resumoMensal($period),
            'categorias'       => $this->topCategorias($period),
            'lancamentos_status' => $this->statusLancamentos($period),
            'evolucao_6_meses' => $this->evolucaoSeisMeses($period),
        ];
    }

    private function resumoMensal(ContextPeriod $p): array
    {
        $receitasMes    = (float) Lancamento::where('tipo', 'receita')->whereBetween('data', [$p->inicioMes, $p->fimMes])->sum('valor');
        $despesasMes    = (float) Lancamento::where('tipo', 'despesa')->whereBetween('data', [$p->inicioMes, $p->fimMes])->sum('valor');
        $receitasMesAnt = (float) Lancamento::where('tipo', 'receita')->whereBetween('data', [$p->inicioMesAnterior, $p->fimMesAnterior])->sum('valor');
        $despesasMesAnt = (float) Lancamento::where('tipo', 'despesa')->whereBetween('data', [$p->inicioMesAnterior, $p->fimMesAnterior])->sum('valor');

        $totalLanc    = Lancamento::whereBetween('data', [$p->inicioMes, $p->fimMes])->count();
        $totalLancAnt = Lancamento::whereBetween('data', [$p->inicioMesAnterior, $p->fimMesAnterior])->count();

        return [
            'receitas_mes_atual'             => round($receitasMes, 2),
            'despesas_mes_atual'             => round($despesasMes, 2),
            'saldo_mes_atual'                => round($receitasMes - $despesasMes, 2),
            'receitas_mes_anterior'          => round($receitasMesAnt, 2),
            'despesas_mes_anterior'          => round($despesasMesAnt, 2),
            'saldo_mes_anterior'             => round($receitasMesAnt - $despesasMesAnt, 2),
            'total_lancamentos_mes'          => $totalLanc,
            'total_lancamentos_mes_anterior' => $totalLancAnt,
            'ticket_medio_despesa'           => $totalLanc > 0 ? round($despesasMes / $totalLanc, 2) : 0,
            'taxa_economia'                  => $receitasMes > 0 ? round((($receitasMes - $despesasMes) / $receitasMes) * 100, 1) : 0,
            'variacao_despesas'              => $despesasMesAnt > 0 ? round((($despesasMes - $despesasMesAnt) / $despesasMesAnt) * 100, 1) : 0,
            'variacao_receitas'              => $receitasMesAnt > 0 ? round((($receitasMes - $receitasMesAnt) / $receitasMesAnt) * 100, 1) : 0,
        ];
    }

    private function topCategorias(ContextPeriod $p): array
    {
        return DB::table('lancamentos')
            ->join('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
            ->whereBetween('lancamentos.data', [$p->inicioMes, $p->fimMes])
            ->where('lancamentos.tipo', 'despesa')
            ->select('categorias.nome', DB::raw('SUM(lancamentos.valor) as total'), DB::raw('COUNT(*) as qtd'))
            ->groupBy('categorias.nome')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'categoria'  => $row->nome,
                'total'      => round((float) $row->total, 2),
                'quantidade' => (int) $row->qtd,
            ])->toArray();
    }

    private function statusLancamentos(ContextPeriod $p): array
    {
        $pagosMes     = Lancamento::where('pago', 1)->whereBetween('data', [$p->inicioMes, $p->fimMes])->count();
        $pendentesMes = Lancamento::where('pago', 0)->whereNull('cancelado_em')->whereBetween('data', [$p->inicioMes, $p->fimMes])->count();
        $vencidos     = Lancamento::where('pago', 0)->whereNull('cancelado_em')->where('data', '<', $p->hoje)->count();
        $valorVencido = round((float) Lancamento::where('pago', 0)->whereNull('cancelado_em')->where('data', '<', $p->hoje)->sum('valor'), 2);

        return [
            'pagos_mes'      => $pagosMes,
            'pendentes_mes'  => $pendentesMes,
            'vencidos_total' => $vencidos,
            'valor_vencido'  => $valorVencido,
            'taxa_pagamento' => ($pagosMes + $pendentesMes) > 0 ? round(($pagosMes / ($pagosMes + $pendentesMes)) * 100, 1) : 0,
        ];
    }

    private function evolucaoSeisMeses(ContextPeriod $p): array
    {
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $date   = $p->now->copy()->subMonths($i);
            $inicio = $date->copy()->startOfMonth()->toDateString();
            $fim    = $date->copy()->endOfMonth()->toDateString();

            $receitas = round((float) Lancamento::where('tipo', 'receita')->whereBetween('data', [$inicio, $fim])->sum('valor'), 2);
            $despesas = round((float) Lancamento::where('tipo', 'despesa')->whereBetween('data', [$inicio, $fim])->sum('valor'), 2);

            $meses[] = [
                'mes'      => $date->translatedFormat('M/Y'),
                'receitas' => $receitas,
                'despesas' => $despesas,
                'saldo'    => round($receitas - $despesas, 2),
            ];
        }
        return $meses;
    }
}
