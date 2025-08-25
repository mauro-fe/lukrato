<?php

namespace Application\Controllers\Api;

use Application\Core\Request;
use Application\Core\Response;
use Application\Models\Lancamento;   // <- seu model
use Application\Models\Categoria;
use Carbon\Carbon;

class FinanceApiController
{
    // GET /api/dashboard/metrics?month=YYYY-MM
    public function metrics(): void
    {
        try {
            $req   = new Request();
            $month = $req->get('month') ?? ($_GET['month'] ?? date('Y-m'));

            [$y, $m] = explode('-', $month);
            $start = Carbon::createMidnightDate((int)$y, (int)$m, 1);
            $end   = $start->copy()->endOfMonth();

            // Somatórios do mês
            $receitas = (float) Lancamento::whereBetween('data', [$start->toDateString(), $end->toDateString()])
                ->where('tipo', 'receita')
                ->sum('valor');

            $despesas = (float) Lancamento::whereBetween('data', [$start->toDateString(), $end->toDateString()])
                ->where('tipo', 'despesa')
                ->sum('valor');

            $resultado = $receitas - $despesas;

            // Saldo acumulado até o fim do mês
            $acumRec = (float) Lancamento::where('data', '<=', $end->toDateString())
                ->where('tipo', 'receita')
                ->sum('valor');

            $acumDes = (float) Lancamento::where('data', '<=', $end->toDateString())
                ->where('tipo', 'despesa')
                ->sum('valor');

            $saldoAcumulado = $acumRec - $acumDes;

            Response::json([
                'saldo'          => $resultado,    // usado no card "Saldo Atual"
                'receitas'       => $receitas,
                'despesas'       => $despesas,
                'resultado'      => $resultado,    // usado no gráfico
                'saldoAcumulado' => $saldoAcumulado,
            ]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/dashboard/transactions?month=YYYY-MM&limit=50
    public function transactions(): void
    {
        try {
            $req   = new Request();
            $month = $req->get('month') ?? ($_GET['month'] ?? date('Y-m'));
            $limit = (int)($req->get('limit') ?? ($_GET['limit'] ?? 50));

            [$y, $m] = explode('-', $month);
            $start = Carbon::createMidnightDate((int)$y, (int)$m, 1);
            $end   = $start->copy()->endOfMonth();

            $rows = Lancamento::with('categoria:id,nome')
                ->whereBetween('data', [$start->toDateString(), $end->toDateString()])
                ->orderBy('data', 'desc')
                ->limit($limit)
                ->get(['id', 'data', 'tipo', 'categoria_id', 'descricao', 'observacao', 'valor']);

            $out = $rows->map(function ($t) {
                return [
                    'id'         => (int) $t->id,
                    'data'       => (string) $t->data,
                    'tipo'       => (string) $t->tipo,
                    'descricao'  => (string) ($t->descricao ?? ''),
                    'observacao' => (string) ($t->observacao ?? ''),
                    'valor'      => (float)  $t->valor,
                    'categoria'  => $t->categoria
                        ? ['id' => $t->categoria->id, 'nome' => $t->categoria->nome]
                        : null,
                ];
            });
            Response::json($out->all());
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/options
    public function options(): void
    {
        try {
            $cats = Categoria::orderBy('nome')->get(['id', 'nome']);
            $arr  = $cats->map(function ($c) {
                return ['id' => $c->id, 'nome' => $c->nome];
            })->all();

            Response::json(['categorias' => ['todas' => $arr]]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    // POST /api/transactions
    public function store(): void
    {
        try {
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];

            $t = new Lancamento();
            $t->tipo         = $data['tipo'] ?? 'despesa';
            $t->data         = $data['data'] ?? date('Y-m-d');
            $t->categoria_id = $data['categoria_id'] ?? null;
            $t->observacao = $data['observacao'] ?? null;
            $t->descricao    = $data['descricao'] ?? null;
            $t->valor        = (float) ($data['valor'] ?? 0);
            $t->save();

            Response::json(['ok' => true, 'id' => $t->id]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
