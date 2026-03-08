<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\Lancamento;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class LancamentosCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period): array
    {
        return [
            'lancamentos_recentes'     => $this->recentes($period),
            'lancamentos_por_tipo'     => $this->porTipo($period),
            'lancamentos_por_forma'    => $this->porFormaPagamento($period),
            'lancamentos_vencidos'     => $this->vencidos($period),
            'recorrencias_ativas'      => $this->recorrencias($period),
            'lancamentos_por_usuario'  => $this->porUsuario($period),
        ];
    }

    /**
     * Últimos 30 lançamentos com detalhes completos (descrição, valor, categoria, conta, etc.)
     */
    private function recentes(ContextPeriod $p): array
    {
        return DB::table('lancamentos')
            ->leftJoin('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
            ->leftJoin('contas', 'lancamentos.conta_id', '=', 'contas.id')
            ->leftJoin('cartoes_credito', 'lancamentos.cartao_credito_id', '=', 'cartoes_credito.id')
            ->whereNull('lancamentos.deleted_at')
            ->select(
                'lancamentos.descricao',
                'lancamentos.valor',
                'lancamentos.tipo',
                'lancamentos.data',
                'lancamentos.pago',
                'lancamentos.forma_pagamento',
                'lancamentos.origem_tipo',
                'lancamentos.eh_parcelado',
                'lancamentos.parcela_atual',
                'lancamentos.total_parcelas',
                'lancamentos.recorrente',
                'categorias.nome as categoria',
                'contas.nome as conta',
                'cartoes_credito.nome as cartao'
            )
            ->orderByDesc('lancamentos.data')
            ->orderByDesc('lancamentos.id')
            ->limit(30)
            ->get()
            ->map(function ($row) {
                $item = [
                    'descricao' => $row->descricao ?: '(sem descrição)',
                    'valor'     => round((float) $row->valor, 2),
                    'tipo'      => $row->tipo,
                    'data'      => $row->data,
                    'pago'      => (bool) $row->pago ? 'sim' : 'não',
                    'categoria' => $row->categoria ?: '(sem categoria)',
                ];

                if ($row->conta) {
                    $item['conta'] = $row->conta;
                }
                if ($row->cartao) {
                    $item['cartao'] = $row->cartao;
                }
                if ($row->forma_pagamento) {
                    $item['forma_pagamento'] = $row->forma_pagamento;
                }
                if ($row->eh_parcelado) {
                    $item['parcela'] = "{$row->parcela_atual}/{$row->total_parcelas}";
                }
                if ($row->recorrente) {
                    $item['recorrente'] = true;
                }

                return $item;
            })->toArray();
    }

    /**
     * Distribuição de lançamentos do mês por tipo (receita, despesa, transferência)
     */
    private function porTipo(ContextPeriod $p): array
    {
        return DB::table('lancamentos')
            ->whereNull('deleted_at')
            ->whereBetween('data', [$p->inicioMes, $p->fimMes])
            ->select(
                'tipo',
                DB::raw('COUNT(*) as qtd'),
                DB::raw('SUM(valor) as total')
            )
            ->groupBy('tipo')
            ->get()
            ->map(fn($row) => [
                'tipo'  => $row->tipo,
                'qtd'   => (int) $row->qtd,
                'total' => round((float) $row->total, 2),
            ])->toArray();
    }

    /**
     * Distribuição por forma de pagamento no mês
     */
    private function porFormaPagamento(ContextPeriod $p): array
    {
        return DB::table('lancamentos')
            ->whereNull('deleted_at')
            ->whereBetween('data', [$p->inicioMes, $p->fimMes])
            ->whereNotNull('forma_pagamento')
            ->select(
                'forma_pagamento',
                DB::raw('COUNT(*) as qtd'),
                DB::raw('SUM(valor) as total')
            )
            ->groupBy('forma_pagamento')
            ->orderByDesc('total')
            ->get()
            ->map(fn($row) => [
                'forma'  => $row->forma_pagamento,
                'qtd'    => (int) $row->qtd,
                'total'  => round((float) $row->total, 2),
            ])->toArray();
    }

    /**
     * Lançamentos vencidos não pagos (top 15 mais recentes com detalhes)
     */
    private function vencidos(ContextPeriod $p): array
    {
        return DB::table('lancamentos')
            ->leftJoin('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
            ->whereNull('lancamentos.deleted_at')
            ->whereNull('lancamentos.cancelado_em')
            ->where('lancamentos.pago', 0)
            ->where('lancamentos.data', '<', $p->hoje)
            ->select(
                'lancamentos.descricao',
                'lancamentos.valor',
                'lancamentos.tipo',
                'lancamentos.data',
                'categorias.nome as categoria'
            )
            ->orderByDesc('lancamentos.data')
            ->limit(15)
            ->get()
            ->map(fn($row) => [
                'descricao' => $row->descricao ?: '(sem descrição)',
                'valor'     => round((float) $row->valor, 2),
                'tipo'      => $row->tipo,
                'data'      => $row->data,
                'categoria' => $row->categoria ?: '(sem categoria)',
            ])->toArray();
    }

    /**
     * Recorrências ativas (lançamentos pai) com frequência e valores
     */
    private function recorrencias(ContextPeriod $p): array
    {
        return DB::table('lancamentos')
            ->leftJoin('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
            ->whereNull('lancamentos.deleted_at')
            ->whereNull('lancamentos.cancelado_em')
            ->where('lancamentos.recorrente', 1)
            ->whereNull('lancamentos.recorrencia_pai_id')
            ->select(
                'lancamentos.descricao',
                'lancamentos.valor',
                'lancamentos.tipo',
                'lancamentos.recorrencia_freq',
                'lancamentos.recorrencia_fim',
                'categorias.nome as categoria'
            )
            ->orderByDesc('lancamentos.valor')
            ->limit(20)
            ->get()
            ->map(fn($row) => [
                'descricao'  => $row->descricao ?: '(sem descrição)',
                'valor'      => round((float) $row->valor, 2),
                'tipo'       => $row->tipo,
                'frequencia' => $row->recorrencia_freq ?: 'mensal',
                'fim'        => $row->recorrencia_fim ?: 'indefinida',
                'categoria'  => $row->categoria ?: '(sem categoria)',
            ])->toArray();
    }

    /**
     * Top 10 usuários com mais lançamentos no mês (para visão admin)
     */
    private function porUsuario(ContextPeriod $p): array
    {
        return DB::table('lancamentos')
            ->join('usuarios', 'lancamentos.user_id', '=', 'usuarios.id')
            ->whereNull('lancamentos.deleted_at')
            ->whereBetween('lancamentos.data', [$p->inicioMes, $p->fimMes])
            ->select(
                'usuarios.nome',
                DB::raw('COUNT(*) as qtd_lancamentos'),
                DB::raw('SUM(CASE WHEN lancamentos.tipo = \'receita\' THEN lancamentos.valor ELSE 0 END) as total_receitas'),
                DB::raw('SUM(CASE WHEN lancamentos.tipo = \'despesa\' THEN lancamentos.valor ELSE 0 END) as total_despesas')
            )
            ->groupBy('usuarios.id', 'usuarios.nome')
            ->orderByDesc('qtd_lancamentos')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'usuario'    => $row->nome,
                'lancamentos' => (int) $row->qtd_lancamentos,
                'receitas'   => round((float) $row->total_receitas, 2),
                'despesas'   => round((float) $row->total_despesas, 2),
            ])->toArray();
    }
}
