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

        $req    = new Request();
        $type   = (string)$req->get('type', 'despesas_por_categoria');
        $year   = (int)($req->get('year') ?? date('Y'));
        $month  = (int)($req->get('month') ?? date('n'));
        $acc    = $req->get('account_id'); // opcional
        $accId  = is_null($acc) || $acc === '' ? null : (int)$acc;

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            Response::json(['error' => 'Parâmetros de data inválidos.'], 422);
            return;
        }

        $start  = Carbon::create($year, $month, 1)->startOfDay();
        $end    = (clone $start)->endOfMonth()->endOfDay();
        $userId = $this->adminId ?? ($_SESSION['usuario_id'] ?? null);

        // Escopo: registros sem user_id (NULL) + do usuário
        $userScope = function ($q) use ($userId) {
            $q->where(function ($q2) use ($userId) {
                $q2->whereNull('lancamentos.user_id');
                if (!empty($userId)) {
                    $q2->orWhere('lancamentos.user_id', $userId);
                }
            });
        };

        // Filtro de conta (quando existir coluna)
        $accountScope = function ($q) use ($accId) {
            if ($accId) {
                $q->where('lancamentos.conta_id', $accId);
            }
        };

        switch ($type) {
            // ------------------ PIZZA: DESPESAS POR CATEGORIA ------------------
            case 'despesas_por_categoria': {
                    $data = Lancamento::query()
                        ->leftJoin('categorias', 'categorias.id', '=', 'lancamentos.categoria_id')
                        ->selectRaw("COALESCE(categorias.nome, 'Sem categoria') as label")
                        ->selectRaw('SUM(lancamentos.valor) as total')
                        ->where($userScope)
                        ->where($accountScope)
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
                        ->where($accountScope)
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
                        ->where($accountScope)
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
                        ->where($accountScope)
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
                        ->where($accountScope)
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

                // --------- NOVO: BARRAS POR CONTA (no mês) -------------------------
            case 'receitas_despesas_por_conta': {
                    // Para o mês atual, agrupa por conta
                    $rows = Lancamento::query()
                        ->leftJoin('contas', 'contas.id', '=', 'lancamentos.conta_id')
                        ->selectRaw("COALESCE(contas.nome, contas.instituicao, 'Sem conta') as conta")
                        ->selectRaw("SUM(CASE WHEN lancamentos.tipo='receita' THEN lancamentos.valor ELSE 0 END) as receitas")
                        ->selectRaw("SUM(CASE WHEN lancamentos.tipo='despesa' THEN lancamentos.valor ELSE 0 END) as despesas")
                        ->where($userScope)
                        // se veio account_id, mantemos filtro também (mostra só a escolhida)
                        ->where($accountScope)
                        ->whereBetween('lancamentos.data', [$start, $end])
                        ->groupBy('conta')
                        ->orderBy('conta')
                        ->get();

                    $labels   = $rows->pluck('conta')->values()->all();
                    $receitas = $rows->pluck('receitas')->map(fn($v) => (float)$v)->values()->all();
                    $despesas = $rows->pluck('despesas')->map(fn($v) => (float)$v)->values()->all();

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

            default:
                Response::json(['error' => 'Tipo de relatório inválido'], 422);
                return;
        }
    }
}
