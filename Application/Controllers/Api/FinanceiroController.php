<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Request;
use Application\Core\Response;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Services\LancamentoLimitService;
use Application\Models\Conta;
use Application\Enums\LancamentoTipo;
use Application\Enums\CategoriaTipo;
use Carbon\Carbon;
use Application\Lib\Auth;
use ValueError;
use Throwable;
use Illuminate\Database\Eloquent\Builder;
use DomainException;

class FinanceiroController extends BaseController
{
    private LancamentoLimitService $limitService;

    public function __construct(?LancamentoLimitService $limitService = null)
    {
        $this->limitService = $limitService ?? new LancamentoLimitService();
    }

    private function validateTipo(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));

        if (!in_array($tipo, [LancamentoTipo::RECEITA->value, LancamentoTipo::DESPESA->value], true)) {
            throw new ValueError('Tipo inválido. Use "receita" ou "despesa".');
        }
        return $tipo;
    }


    private function validateAndSanitizeValor(mixed $valorRaw): float
    {
        if (is_string($valorRaw)) {
            $s = trim(str_replace(['R$', ' ', '.'], '', $valorRaw));
            $s = str_replace(',', '.', $s);
            $valor = is_numeric($s) ? (float)$s : null;
        } else {
            $valor = is_numeric($valorRaw) ? (float)$valorRaw : null;
        }

        if ($valor === null || !is_finite($valor) || $valor <= 0) {
            throw new ValueError('Valor deve ser um número maior que zero.');
        }

        return round($valor, 2);
    }


    private function validateData(string $dataStr): string
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $dataStr);
        if (!$dt || $dt->format('Y-m-d') !== $dataStr) {
            throw new ValueError('Data inválida (YYYY-MM-DD).');
        }
        return $dataStr;
    }


    public function metrics(): void
    {
        $uid = Auth::id();

        try {
            $req = new Request();
            $month = $req->get('month') ?? $_GET['month'] ?? date('Y-m');

            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new ValueError('Formato de mês inválido (YYYY-MM).');
            }

            [$y, $m] = array_map('intval', explode('-', $month));
            $start = Carbon::createMidnightDate($y, $m, 1);
            $end   = (clone $start)->endOfMonth();

            $baseQuery = fn(string $tipo) => Lancamento::where('tipo', $tipo)
                ->where('eh_transferencia', 0)
                ->when($uid, fn(Builder $q) => $q->where('user_id', $uid));

            $receitas = (float)$baseQuery(LancamentoTipo::RECEITA->value)
                ->whereBetween('data', [$start, $end])
                ->sum('valor');

            $despesas = (float)$baseQuery(LancamentoTipo::DESPESA->value)
                ->whereBetween('data', [$start, $end])
                ->sum('valor');

            $resultado = $receitas - $despesas;

            $acumRec = (float)$baseQuery(LancamentoTipo::RECEITA->value)
                ->where('data', '<=', $end)
                ->sum('valor');

            $acumDes = (float)$baseQuery(LancamentoTipo::DESPESA->value)
                ->where('data', '<=', $end)
                ->sum('valor');

            Response::json([
                'saldo'          => $resultado,
                'receitas'       => $receitas,
                'despesas'       => $despesas,
                'resultado'      => $resultado,
                'saldoAcumulado' => ($acumRec - $acumDes),
            ]);
        } catch (Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function transactions(): void
    {
        $uid = Auth::id();

        try {
            $req = new Request();
            $month = $req->get('month') ?? $_GET['month'] ?? date('Y-m');
            $limit = min((int)($req->get('limit') ?? $_GET['limit'] ?? 50), 1000);

            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new ValueError('Formato de mês inválido (YYYY-MM).');
            }

            [$y, $m] = array_map('intval', explode('-', $month));
            $start = Carbon::createMidnightDate($y, $m, 1)->toDateString();
            $end   = Carbon::createMidnightDate($y, $m, 1)->endOfMonth()->toDateString();

            $rows = Lancamento::with('categoria:id,nome')
                ->whereBetween('data', [$start, $end])
                ->when($uid, fn($q) => $q->where('user_id', $uid))
                ->where('eh_transferencia', 0)
                ->orderBy('data', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            $out = $rows->map(function (Lancamento $t) {
                return [
                    'id'               => (int) $t->id,
                    'data'             => (string) $t->data,
                    'tipo'             => (string) $t->tipo,
                    'descricao'        => (string) ($t->descricao ?? ''),
                    'observacao'       => (string) ($t->observacao ?? ''),
                    'valor'            => (float)  $t->valor,
                    'eh_transferencia' => (bool) ($t->eh_transferencia ?? 0),
                    'eh_saldo_inicial' => (bool) ($t->eh_saldo_inicial ?? 0),
                    'categoria'        => $t->categoria
                        ? ['id' => (int)$t->categoria->id, 'nome' => (string)$t->categoria->nome]
                        : null,
                ];
            })->all();

            Response::json($out);
        } catch (Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
    public function options(): void
    {
        $uid = Auth::id();

        try {
            $baseCatsQuery = fn(string $tipo) => Categoria::where(function (Builder $q) use ($uid) {
                $q->whereNull('user_id')->orWhere('user_id', $uid);
            })
                ->whereIn('tipo', [$tipo, CategoriaTipo::AMBAS->value])
                ->orderBy('nome')
                ->get(['id', 'nome']);

            $catsReceita = $baseCatsQuery(LancamentoTipo::RECEITA->value);
            $catsDespesa = $baseCatsQuery(LancamentoTipo::DESPESA->value);

            $contas = Conta::forUser($uid)->ativas()
                ->orderBy('nome')
                ->get(['id', 'nome']);

            Response::json([
                'categorias' => [
                    'receitas' => $catsReceita->map(fn(Categoria $c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])->all(),
                    'despesas' => $catsDespesa->map(fn(Categoria $c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])->all(),
                ],
                'contas' => $contas->map(fn(Conta $c) => ['id' => (int)$c->id, 'nome' => (string)$c->nome])->all(),
            ]);
        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    public function store(): void
    {
        try {
            $data = $this->getRequestPayload();
            $uid  = Auth::id();

            if (!$uid) {
                Response::error('Nao autenticado', 401);
                return;
            }

            $tipo    = $this->validateTipo($data['tipo'] ?? LancamentoTipo::DESPESA->value);
            $dataStr = $this->validateData($data['data'] ?? date('Y-m-d'));
            $valor   = $this->validateAndSanitizeValor($data['valor'] ?? 0);

            // ============================
            // LIMITE MENSAL (FREE) - CENTRALIZADO
            // ============================
            try {
                $usage = $this->limitService->assertCanCreate($uid, $dataStr);
            } catch (DomainException $e) {
                Response::error($e->getMessage(), 402);
                return;
            }

            $categoriaId = $data['categoria_id'] ?? null;
            if ($categoriaId !== null && $categoriaId !== '') {
                $categoriaId = (int)$categoriaId;

                /** @var Categoria|null $cat */
                $cat = Categoria::where('id', $categoriaId)
                    ->where(fn(Builder $q) => $q->whereNull('user_id')->orWhere('user_id', $uid))
                    ->first();

                if (!$cat) {
                    throw new ValueError('Categoria inválida.');
                }

                if (!in_array($cat->tipo, [CategoriaTipo::AMBAS->value, $tipo], true)) {
                    throw new ValueError('Categoria incompatível com o tipo de lançamento.');
                }
            } else {
                $categoriaId = null;
            }

            $contaId = $data['conta_id'] ?? null;
            if ($contaId !== null && $contaId !== '') {
                $contaId = (int)$contaId;

                if (!Conta::forUser($uid)->find($contaId)) {
                    throw new ValueError('Conta inválida.');
                }
            } else {
                $contaId = null;
            }

            $t = new Lancamento([
                'user_id'           => $uid,
                'tipo'              => $tipo,
                'data'              => $dataStr,
                'categoria_id'      => $categoriaId,
                'conta_id'          => $contaId,
                'descricao'         => isset($data['descricao']) ? trim((string)$data['descricao']) : null,
                'observacao'        => isset($data['observacao']) ? trim((string)$data['observacao']) : null,
                'valor'             => $valor,
                'eh_transferencia'  => 0,
                'eh_saldo_inicial'  => 0, // garante que entra na contagem (se seu default já é 0, ok)
            ]);

            $t->save();

            // Atualiza usage depois de salvar, pra front já mostrar warning certinho
            $usage = $this->limitService->usage($uid, substr($dataStr, 0, 7));

            Response::success([
                'ok' => true,
                'id' => (int)$t->id,
                'usage' => $usage,
                'ui_message' => ($usage['should_warn'] ?? false)
                    ? "⚠️ Atenção: você já usou {$usage['used']} de {$this->limitService->getFreeLimit()} lançamentos do plano gratuito. Faltam {$usage['remaining']} este mês."
                    : null
            ], 'Lancamento criado', 201);
        } catch (ValueError $e) {
            Response::validationError(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            Response::error('Erro ao salvar lancamento.', 500);
        }
    }



    public function update(mixed $routeParam = null): void
    {
        try {
            $uid = Auth::id();
            if (!$uid) {
                Response::json(['status' => 'error', 'message' => 'Não autenticado'], 401);
                return;
            }

            $id = (int)(is_array($routeParam) ? ($routeParam['id'] ?? 0) : $routeParam);

            if ($id <= 0) {
                throw new ValueError('ID inválido.');
            }

            $lanc = Lancamento::where('user_id', $uid)->find($id);
            if (!$lanc) {
                Response::json(['status' => 'error', 'message' => 'Lançamento não encontrado.'], 404);
                return;
            }

            if ((bool)($lanc->eh_transferencia ?? 0) === true) {
                throw new ValueError('Transferências não podem ser editadas aqui.');
            }
            if ((bool)($lanc->eh_saldo_inicial ?? 0) === true) {
                throw new ValueError('Saldo inicial não pode ser editado.');
            }

            $data = $this->getRequestPayload();

            $tipo = $this->validateTipo($data['tipo'] ?? $lanc->tipo ?? LancamentoTipo::DESPESA->value);
            $dataStr = $this->validateData($data['data'] ?? $lanc->data ?? date('Y-m-d'));

            $valorRaw = $data['valor'] ?? $lanc->valor;
            $valor = $this->validateAndSanitizeValor($valorRaw);

            $categoriaId = $data['categoria_id'] ?? $lanc->categoria_id;
            if ($categoriaId !== null && $categoriaId !== '') {
                $categoriaId = (int)$categoriaId;
                /** @var Categoria|null $cat */
                $cat = Categoria::where('id', $categoriaId)
                    ->where(fn(Builder $q) => $q->whereNull('user_id')->orWhere('user_id', $uid))
                    ->first();

                if (!$cat) {
                    throw new ValueError('Categoria inválida.');
                }
                if (!in_array($cat->tipo, [CategoriaTipo::AMBAS->value, $tipo], true)) {
                    throw new ValueError('Categoria incompatível com o tipo de lançamento.');
                }
            } else {
                $categoriaId = null;
            }

            $contaId = $data['conta_id'] ?? $lanc->conta_id;
            if ($contaId !== null && $contaId !== '') {
                $contaId = (int)$contaId;
                if (!Conta::forUser($uid)->find($contaId)) {
                    throw new ValueError('Conta inválida.');
                }
            } else {
                throw new ValueError('Conta é obrigatória.');
            }

            $lanc->tipo = $tipo;
            $lanc->data = $dataStr;
            $lanc->valor = $valor;
            $lanc->categoria_id = $categoriaId;
            $lanc->conta_id = $contaId;
            $lanc->descricao = isset($data['descricao']) ? trim((string)$data['descricao']) : $lanc->descricao;
            $lanc->observacao = isset($data['observacao']) ? trim((string)$data['observacao']) : $lanc->observacao;
            $lanc->save();

            Response::json(['ok' => true, 'id' => (int)$lanc->id]);
        } catch (ValueError $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    public function transfer(): void
    {
        try {
            $uid = Auth::id();
            $data = $this->getRequestPayload();

            $dataStr = $this->validateData($data['data'] ?? date('Y-m-d'));
            $valor = $this->validateAndSanitizeValor($data['valor'] ?? 0);

            $origemId  = (int)($data['conta_id'] ?? 0);
            $destinoId = (int)($data['conta_id_destino'] ?? 0);

            if ($origemId <= 0 || $destinoId <= 0 || $origemId === $destinoId) {
                throw new ValueError('Selecione contas de origem e destino diferentes.');
            }

            $origem  = Conta::forUser($uid)->find($origemId);
            $destino = Conta::forUser($uid)->find($destinoId);
            if (!$origem || !$destino) {
                throw new ValueError('Conta de origem ou destino inválida.');
            }

            $t = new Lancamento([
                'user_id'           => $uid,
                'tipo'              => Lancamento::TIPO_TRANSFERENCIA,
                'data'              => $dataStr,
                'categoria_id'      => null,
                'conta_id'          => $origemId,
                'conta_id_destino'  => $destinoId,
                'descricao'         => isset($data['descricao']) ? trim((string)$data['descricao']) : null,
                'observacao'        => isset($data['observacao']) ? trim((string)$data['observacao']) : null,
                'valor'             => $valor,
                'eh_transferencia'  => 1,
            ]);
            $t->save();

            Response::json(['ok' => true, 'id' => (int)$t->id], 201);
        } catch (ValueError $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
