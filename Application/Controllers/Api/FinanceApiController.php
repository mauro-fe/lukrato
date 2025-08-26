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
            // Requer que o Model Categoria tenha os scopes ->receitas() e ->despesas()
            $catsReceita = Categoria::receitas()->orderBy('nome')->get(['id', 'nome']);
            $catsDespesa = Categoria::despesas()->orderBy('nome')->get(['id', 'nome']);

            Response::json([
                'categorias' => [
                    'receitas' => $catsReceita->map(fn($c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])->all(),
                    'despesas' => $catsDespesa->map(fn($c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])->all(),
                ],
            ]);
        } catch (\Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/transactions
    public function store(): void
    {
        try {
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];

            // -------- Validações básicas --------
            $tipo = strtolower(trim((string)($data['tipo'] ?? 'despesa')));
            if (!in_array($tipo, ['receita', 'despesa'], true)) {
                Response::json(['status' => 'error', 'message' => 'Tipo inválido. Use "receita" ou "despesa".'], 422);
                return;
            }

            $dataStr = (string)($data['data'] ?? date('Y-m-d'));
            $dt = \DateTime::createFromFormat('Y-m-d', $dataStr);
            if (!$dt || $dt->format('Y-m-d') !== $dataStr) {
                Response::json(['status' => 'error', 'message' => 'Data inválida. Formato esperado: YYYY-MM-DD.'], 422);
                return;
            }

            $valor = (float)($data['valor'] ?? 0);
            if (!is_numeric($data['valor'] ?? null) && !is_string($data['valor'] ?? null)) {
                Response::json(['status' => 'error', 'message' => 'Valor inválido.'], 422);
                return;
            }
            // Permite zero e negativos? Se não, bloqueie negativos:
            if ($valor < 0) {
                Response::json(['status' => 'error', 'message' => 'Valor não pode ser negativo.'], 422);
                return;
            }
            // Normaliza para 2 casas
            $valor = round($valor, 2);

            // Categoria (opcional), mas se vier precisa existir e ser compatível
            $categoriaId = $data['categoria_id'] ?? null;
            if ($categoriaId !== null && $categoriaId !== '') {
                $categoriaId = (int)$categoriaId;
                $cat = Categoria::find($categoriaId);
                if (!$cat) {
                    Response::json(['status' => 'error', 'message' => 'Categoria inválida.'], 422);
                    return;
                }
                // Compatibilidade: cat.tipo deve ser 'ambas' ou igual ao $tipo
                if (!in_array($cat->tipo, ['ambas', $tipo], true)) {
                    Response::json(['status' => 'error', 'message' => 'Categoria não compatível com o tipo do lançamento.'], 422);
                    return;
                }
            } else {
                $categoriaId = null;
            }

            // -------- Persistência --------
            $t = new Lancamento();
            $t->tipo         = $tipo;
            $t->data         = $dataStr;
            $t->categoria_id = $categoriaId;
            $t->descricao    = isset($data['descricao'])   ? trim((string)$data['descricao'])   : null;
            $t->observacao   = isset($data['observacao'])  ? trim((string)$data['observacao'])  : null;
            $t->valor        = $valor;
            $t->save();

            Response::json(['ok' => true, 'id' => (int)$t->id]);
        } catch (\Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
