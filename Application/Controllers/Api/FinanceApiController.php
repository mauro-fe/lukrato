<?php

namespace Application\Controllers\Api;

use Application\Core\Request;
use Application\Core\Response;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Models\Conta;
use Carbon\Carbon;
use Application\Lib\Auth;

class FinanceApiController
{
    // GET /api/dashboard/metrics?month=YYYY-MM
    public function metrics(): void
    {
        $uid = Auth::id();

        try {
            $req   = new Request();
            $month = $req->get('month') ?? ($_GET['month'] ?? date('Y-m'));

            [$y, $m] = explode('-', $month);
            $start = Carbon::createMidnightDate((int)$y, (int)$m, 1);
            $end   = $start->copy()->endOfMonth();

            // Somatórios do mês (ignora transferências)
            $receitas = (float) Lancamento::whereBetween('data', [$start, $end])
                ->when($uid, fn($q) => $q->where('user_id', $uid))
                ->where('tipo', 'receita')
                ->where('eh_transferencia', 0)
                ->sum('valor');

            $despesas = (float) Lancamento::whereBetween('data', [$start, $end])
                ->when($uid, fn($q) => $q->where('user_id', $uid))
                ->where('tipo', 'despesa')
                ->where('eh_transferencia', 0)
                ->sum('valor');

            $resultado = $receitas - $despesas;

            // Acumulado até o fim do mês (ignora transferências)
            $acumRec = (float) Lancamento::where('data', '<=', $end)
                ->when($uid, fn($q) => $q->where('user_id', $uid))
                ->where('tipo', 'receita')
                ->where('eh_transferencia', 0)
                ->sum('valor');

            $acumDes = (float) Lancamento::where('data', '<=', $end)
                ->when($uid, fn($q) => $q->where('user_id', $uid))
                ->where('tipo', 'despesa')
                ->where('eh_transferencia', 0)
                ->sum('valor');

            Response::json([
                'saldo'          => $resultado,         // saldo do mês (igual ao resultado)
                'receitas'       => $receitas,
                'despesas'       => $despesas,
                'resultado'      => $resultado,
                'saldoAcumulado' => ($acumRec - $acumDes),
            ]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/dashboard/transactions?month=YYYY-MM&limit=50
    public function transactions(): void
    {
        $uid = Auth::id();

        try {
            $req   = new Request();
            $month = $req->get('month') ?? ($_GET['month'] ?? date('Y-m'));
            $limit = (int)($req->get('limit') ?? ($_GET['limit'] ?? 50));

            [$y, $m] = explode('-', $month);
            $start = Carbon::createMidnightDate((int)$y, (int)$m, 1)->toDateString();
            $end   = Carbon::createMidnightDate((int)$y, (int)$m, 1)->endOfMonth()->toDateString();

            $rows = Lancamento::with('categoria:id,nome')
                ->whereBetween('data', [$start, $end])
                ->when($uid, fn($q) => $q->where('user_id', $uid))
                ->where('eh_transferencia', 0) // não mostra transferências na tabela
                ->orderBy('data', 'desc')
                ->orderBy('id', 'desc')
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
                        ? ['id' => (int)$t->categoria->id, 'nome' => (string)$t->categoria->nome]
                        : null,
                ];
            });

            Response::json($out->all());
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/options  → categorias (user + globais) e contas ativas
    public function options(): void
    {
        $uid = Auth::id();

        try {
            // categorias do usuário OU globais (user_id NULL)
            $baseCats = fn($tipo) => Categoria::where(function ($q) use ($uid) {
                $q->whereNull('user_id')->orWhere('user_id', $uid);
            })
                ->whereIn('tipo', [$tipo, 'ambas'])
                ->orderBy('nome')
                ->get(['id', 'nome']);

            $catsReceita = $baseCats('receita');
            $catsDespesa = $baseCats('despesa');

            $contas = Conta::forUser($uid)->ativas()
                ->orderBy('nome')
                ->get(['id', 'nome']);

            Response::json([
                'categorias' => [
                    'receitas' => $catsReceita->map(fn($c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])->all(),
                    'despesas' => $catsDespesa->map(fn($c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])->all(),
                ],
                'contas' => $contas->map(fn($c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])->all(),
            ]);
        } catch (\Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/transactions  (receita/despesa)
    public function store(): void
    {
        try {
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];

            $uid  = Auth::id();

            // ---- validações básicas
            $tipo = strtolower(trim((string)($data['tipo'] ?? 'despesa')));
            if (!in_array($tipo, ['receita', 'despesa'], true)) {
                Response::json(['status' => 'error', 'message' => 'Tipo inválido.'], 422);
                return;
            }

            $dataStr = (string)($data['data'] ?? date('Y-m-d'));
            $dt = \DateTime::createFromFormat('Y-m-d', $dataStr);
            if (!$dt || $dt->format('Y-m-d') !== $dataStr) {
                Response::json(['status' => 'error', 'message' => 'Data inválida (YYYY-MM-DD).'], 422);
                return;
            }

            // valor
            $valor = $data['valor'] ?? 0;
            if (is_string($valor)) {
                $s = trim(str_replace(['R$', ' ', '.'], ['', '', ''], $valor));
                $s = str_replace(',', '.', $s);
                $valor = is_numeric($s) ? (float)$s : null;
            }
            // ★ exigir > 0 (não aceitar 0)
            if (!is_numeric($valor) || $valor <= 0) {
                Response::json(['status' => 'error', 'message' => 'Valor deve ser maior que zero.'], 422);
                return;
            }
            $valor = round((float)$valor, 2);

            // categoria (opcional, mas se vier precisa ser compatível e do usuário/globais)
            $categoriaId = $data['categoria_id'] ?? null;
            if ($categoriaId !== null && $categoriaId !== '') {
                // ★ valida escopo (user_id = $uid ou NULL)
                $cat = Categoria::where('id', (int)$categoriaId)
                    ->where(function ($q) use ($uid) {
                        $q->whereNull('user_id')->orWhere('user_id', $uid);
                    })
                    ->first();

                if (!$cat) {
                    Response::json(['status' => 'error', 'message' => 'Categoria inválida.'], 422);
                    return;
                }
                if (!in_array($cat->tipo, ['ambas', $tipo], true)) {
                    Response::json(['status' => 'error', 'message' => 'Categoria incompatível com o tipo.'], 422);
                    return;
                }
            } else {
                $categoriaId = null;
            }

            // conta (opcional, mas se vier tem que ser do usuário)
            $contaId = $data['conta_id'] ?? null;
            if ($contaId !== null && $contaId !== '') {
                $contaId = (int)$contaId;
                $contaOk = Conta::forUser($uid)->find($contaId);
                if (!$contaOk) {
                    Response::json(['status' => 'error', 'message' => 'Conta inválida.'], 422);
                    return;
                }
            } else {
                $contaId = null;
            }

            // ---- persiste
            $t = new Lancamento();
            $t->user_id          = $uid;
            $t->tipo             = $tipo;
            $t->data             = $dataStr;
            $t->categoria_id     = $categoriaId;
            $t->conta_id         = $contaId;
            $t->descricao        = isset($data['descricao'])  ? trim((string)$data['descricao'])  : null;
            $t->observacao       = isset($data['observacao']) ? trim((string)$data['observacao']) : null;
            $t->valor            = $valor;
            $t->eh_transferencia = 0;
            $t->save();

            Response::json(['ok' => true, 'id' => (int)$t->id]);
        } catch (\Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function transfer(): void
    {
        try {
            $uid  = \Application\Lib\Auth::id();
            $data = json_decode(file_get_contents('php://input'), true) ?: [];

            // -------- validações --------
            $dataStr = (string)($data['data'] ?? date('Y-m-d'));
            $dt = \DateTime::createFromFormat('Y-m-d', $dataStr);
            if (!$dt || $dt->format('Y-m-d') !== $dataStr) {
                Response::json(['status' => 'error', 'message' => 'Data inválida (YYYY-MM-DD).'], 422);
                return;
            }

            // valor > 0
            $valor = $data['valor'] ?? 0;
            if (is_string($valor)) {
                $s = str_replace(['R$', ' ', '.'], ['', '', ''], trim($valor));
                $s = str_replace(',', '.', $s);
                $valor = is_numeric($s) ? (float)$s : null;
            }
            if (!is_numeric($valor) || $valor <= 0) {
                Response::json(['status' => 'error', 'message' => 'Valor inválido.'], 422);
                return;
            }
            $valor = round((float)$valor, 2);

            // contas
            $origemId  = isset($data['conta_id']) ? (int)$data['conta_id'] : 0;
            $destinoId = isset($data['conta_id_destino']) ? (int)$data['conta_id_destino'] : 0;

            if ($origemId <= 0 || $destinoId <= 0 || $origemId === $destinoId) {
                Response::json(['status' => 'error', 'message' => 'Selecione contas de origem e destino diferentes.'], 422);
                return;
            }

            $origem  = \Application\Models\Conta::forUser($uid)->find($origemId);
            $destino = \Application\Models\Conta::forUser($uid)->find($destinoId);
            if (!$origem || !$destino) {
                Response::json(['status' => 'error', 'message' => 'Conta de origem ou destino inválida.'], 422);
                return;
            }

            // -------- gravação --------
            $t = new Lancamento();
            $t->user_id           = $uid;
            $t->tipo              = Lancamento::TIPO_TRANSFERENCIA; // "transferencia"
            $t->data              = $dataStr;
            $t->categoria_id      = null;
            $t->conta_id          = $origemId;        // origem
            $t->conta_id_destino  = $destinoId;       // destino
            $t->descricao         = isset($data['descricao'])  ? trim((string)$data['descricao'])  : null;
            $t->observacao        = isset($data['observacao']) ? trim((string)$data['observacao']) : null;
            $t->valor             = $valor;
            $t->eh_transferencia  = 1;
            $t->save();

            Response::json(['ok' => true, 'id' => (int)$t->id]);
        } catch (\Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
