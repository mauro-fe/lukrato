<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Request;
use Application\Core\Response;
use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;

class ReportController extends BaseController
{
    public function index(): void
    {
        if (method_exists($this, 'requireAuth')) {
            $this->requireAuth();
        }

        $req   = new Request();
        $type  = (string) $req->get('type', 'despesas_por_categoria');
        $year  = (int)($req->get('year') ?? date('Y'));
        $month = (int)($req->get('month') ?? date('n'));

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            Response::json(['error' => 'Parâmetros de data inválidos.'], 422);
            return;
        }

        $start  = Carbon::create($year, $month, 1)->startOfDay();
        $end    = (clone $start)->endOfMonth()->endOfDay();
        $userId = $this->adminId ?? ($_SESSION['usuario_id'] ?? null); // fallback

        // Escopo: inclui registros SEM user_id (NULL) + os do usuário (se houver)
        $userScope = function ($q) use ($userId) {
            $q->where(function ($q2) use ($userId) {
                $q2->whereNull('lancamentos.user_id');
                if (!empty($userId)) {
                    $q2->orWhere('lancamentos.user_id', $userId);
                }
            });
        };

        switch ($type) {
            // ------------------ PIZZA: DESPESAS POR CATEGORIA ------------------
            case 'despesas_por_categoria': {
                    $data = Lancamento::query()
                        ->leftJoin('categorias', 'categorias.id', '=', 'lancamentos.categoria_id')
                        ->selectRaw("COALESCE(categorias.nome, 'Sem categoria') as label")
                        ->selectRaw('SUM(lancamentos.valor) as total')
                        ->where($userScope)
                        ->where('lancamentos.tipo', 'despesa')
                        ->whereBetween('lancamentos.data', [$start, $end])
                        ->groupBy('label')
                        ->orderByDesc('total')
                        ->get();

                    $labels = $data->pluck('label')->values()->all();
                    $values = $data->pluck('total')->map(fn($v) => (float)$v)->values()->all();

                    Response::json([
                        'labels' => $labels,
                        'values' => $values,
                        'start'  => $start->toDateString(),
                        'end'    => $end->toDateString(),
                        'type'   => $type,
                        'total'  => array_sum($values),
                    ]);
                    return;
                }

                // ------------------ PIZZA: RECEITAS POR CATEGORIA ------------------
            case 'receitas_por_categoria': {
                    $data = Lancamento::query()
                        ->leftJoin('categorias', 'categorias.id', '=', 'lancamentos.categoria_id')
                        ->selectRaw("COALESCE(categorias.nome, 'Sem categoria') as label")
                        ->selectRaw('SUM(lancamentos.valor) as total')
                        ->where($userScope)
                        ->where('lancamentos.tipo', 'receita')
                        ->whereBetween('lancamentos.data', [$start, $end])
                        ->groupBy('label')
                        ->orderByDesc('total')
                        ->get();

                    $labels = $data->pluck('label')->values()->all();
                    $values = $data->pluck('total')->map(fn($v) => (float)$v)->values()->all();

                    Response::json([
                        'labels' => $labels,
                        'values' => $values,
                        'start'  => $start->toDateString(),
                        'end'    => $end->toDateString(),
                        'type'   => $type,
                        'total'  => array_sum($values),
                    ]);
                    return;
                }

                // ------------------ LINHA: SALDO MENSAL (por dia) ------------------
            case 'saldo_mensal': {
                    $data = Lancamento::query()
                        ->select(DB::raw('DATE(lancamentos.data) as dia'))
                        ->selectRaw("SUM(CASE WHEN lancamentos.tipo='receita' THEN lancamentos.valor ELSE -lancamentos.valor END) as total")
                        ->where($userScope)
                        ->whereBetween('lancamentos.data', [$start, $end])
                        ->groupBy(DB::raw('DATE(lancamentos.data)'))
                        ->orderBy('dia')
                        ->get();

                    $labels = [];
                    $values = [];
                    $cursor = clone $start;
                    while ($cursor <= $end) {
                        $d = $cursor->toDateString();
                        $labels[] = $cursor->format('d/m');
                        $values[] = (float) ($data->firstWhere('dia', $d)->total ?? 0);
                        $cursor->addDay();
                    }

                    Response::json([
                        'labels' => $labels,
                        'values' => $values,
                        'start'  => $start->toDateString(),
                        'end'    => $end->toDateString(),
                        'type'   => $type,
                        'total'  => array_sum($values),
                    ]);
                    return;
                }

                // --------- BARRAS: RECEITAS x DESPESAS (por dia) -------------------
            case 'receitas_despesas_diario': {
                    $rows = Lancamento::query()
                        ->selectRaw('DATE(lancamentos.data) as dia')
                        ->selectRaw("SUM(CASE WHEN lancamentos.tipo='receita' THEN lancamentos.valor ELSE 0 END) as receitas")
                        ->selectRaw("SUM(CASE WHEN lancamentos.tipo='despesa' THEN lancamentos.valor ELSE 0 END) as despesas")
                        ->where($userScope)
                        ->whereBetween('lancamentos.data', [$start, $end])
                        ->groupBy(DB::raw('DATE(lancamentos.data)'))
                        ->orderBy('dia')
                        ->get();

                    $labels = [];
                    $receitas = [];
                    $despesas = [];
                    $cursor = clone $start;
                    while ($cursor <= $end) {
                        $d = $cursor->toDateString();
                        $labels[]   = $cursor->format('d/m');
                        $receitas[] = (float) ($rows->firstWhere('dia', $d)->receitas ?? 0);
                        $despesas[] = (float) ($rows->firstWhere('dia', $d)->despesas ?? 0);
                        $cursor->addDay();
                    }

                    Response::json([
                        'labels'   => $labels,
                        'receitas' => $receitas,
                        'despesas' => $despesas,
                        'type'     => $type,
                        'start'    => $start->toDateString(),
                        'end'      => $end->toDateString(),
                    ]);
                    return;
                }

                // --------- LINHA: EVOLUÇÃO 12 MESES (saldo por mês) ----------------
            case 'evolucao_12m': {
                    $ini = (clone $start)->subMonthsNoOverflow(11)->startOfMonth();
                    $fim = (clone $end);

                    $rows = Lancamento::query()
                        ->selectRaw("DATE_FORMAT(lancamentos.data, '%Y-%m-01') as mes")
                        ->selectRaw("SUM(CASE WHEN lancamentos.tipo='receita' THEN lancamentos.valor ELSE -lancamentos.valor END) as saldo")
                        ->where($userScope)
                        ->whereBetween('lancamentos.data', [$ini, $fim])
                        ->groupBy('mes')
                        ->orderBy('mes')
                        ->get();

                    $labels = [];
                    $values = [];
                    $cursor = clone $ini;
                    while ($cursor <= $fim) {
                        $ym = $cursor->format('Y-m-01');
                        $labels[] = $cursor->format('m/Y');
                        $values[] = (float) ($rows->firstWhere('mes', $ym)->saldo ?? 0);
                        $cursor->addMonth();
                    }

                    Response::json([
                        'labels' => $labels,
                        'values' => $values,
                        'type'   => $type,
                        'start'  => $ini->toDateString(),
                        'end'    => $fim->toDateString(),
                    ]);
                    return;
                }

            default:
                Response::json(['error' => 'Tipo de relatório inválido'], 422);
                return;
        }
    }
}
