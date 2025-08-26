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

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();

        // id do usuário logado
        $userId = $this->adminId ?? ($_SESSION['usuario_id'] ?? null);
        if (!$userId) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        switch ($type) {
            // ------------------ PIZZA: DESPESAS POR CATEGORIA ------------------
            case 'despesas_por_categoria':
                $data = Lancamento::query()
                    ->select('categorias.nome as label', DB::raw('SUM(lancamentos.valor) as total'))
                    ->join('categorias', 'categorias.id', '=', 'lancamentos.categoria_id')
                    ->where('lancamentos.user_id', $userId)
                    ->where('lancamentos.tipo', 'despesa')
                    ->whereBetween('lancamentos.data', [$start, $end])
                    ->groupBy('categorias.nome')
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

                // ------------------ PIZZA: RECEITAS POR CATEGORIA ------------------
            case 'receitas_por_categoria':
                $data = Lancamento::query()
                    ->select('categorias.nome as label', DB::raw('SUM(lancamentos.valor) as total'))
                    ->join('categorias', 'categorias.id', '=', 'lancamentos.categoria_id')
                    ->where('lancamentos.user_id', $userId)
                    ->where('lancamentos.tipo', 'receita')
                    ->whereBetween('lancamentos.data', [$start, $end])
                    ->groupBy('categorias.nome')
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

                // ------------------ LINHA: SALDO MENSAL (por dia) ------------------
            case 'saldo_mensal':
                $data = Lancamento::query()
                    ->select(DB::raw('DATE(lancamentos.data) as dia'))
                    ->selectRaw("SUM(CASE WHEN lancamentos.tipo='receita' THEN lancamentos.valor ELSE -lancamentos.valor END) as total")
                    ->where('lancamentos.user_id', $userId)
                    ->whereBetween('lancamentos.data', [$start, $end])
                    ->groupBy(DB::raw('DATE(lancamentos.data)'))
                    ->orderBy('dia')
                    ->get();

                // Monta labels/values para TODOS os dias do mês
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

                // ------------------ DEFAULT ------------------
            default:
                Response::json(['error' => 'Tipo de relatório inválido'], 422);
                return;
        }
    }
}
