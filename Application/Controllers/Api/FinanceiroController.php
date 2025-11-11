<?php

namespace Application\Controllers\Api;

use Application\Core\Request;
use Application\Core\Response;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Models\Conta;
use Carbon\Carbon;
use Application\Lib\Auth;
use ValueError;
use Throwable;
use Illuminate\Database\Eloquent\Builder;

// --- Enums para Constantes (PHP 8.1+) ---

enum LancamentoTipo: string
{
    case DESPESA = 'despesa';
    case RECEITA = 'receita';
    // Nota: O tipo 'transferencia' é usado no Model Lancamento::TIPO_TRANSFERENCIA
    case AMBAS = 'ambas';
}

class FinanceiroController
{
    // --- Métodos de Utilidade e Sanitização ---

    /**
     * Obtém o payload da requisição (JSON ou POST).
     */
    private function getRequestPayload(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        
        if (empty($data)) {
            $data = $_POST ?? [];
        }
        return $data;
    }

    /**
     * Valida e normaliza o tipo de lançamento.
     * @throws ValueError se o tipo for inválido.
     */
    private function validateTipo(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));
        
        // Verifica se é RECEITA ou DESPESA (usados no CRUD padrão)
        if (!in_array($tipo, [LancamentoTipo::RECEITA->value, LancamentoTipo::DESPESA->value], true)) {
            throw new ValueError('Tipo inválido. Use "receita" ou "despesa".');
        }
        return $tipo;
    }

    /**
     * Valida e sanitiza o valor monetário.
     * @param mixed $valorRaw O valor bruto (float, string com/sem vírgula).
     * @return float O valor sanitizado (round 2).
     * @throws ValueError Se o valor for inválido ou <= 0.
     */
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
    
    /**
     * Valida a data.
     * @throws ValueError Se a data não for válida no formato YYYY-MM-DD.
     */
    private function validateData(string $dataStr): string
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $dataStr);
        if (!$dt || $dt->format('Y-m-d') !== $dataStr) {
            throw new ValueError('Data inválida (YYYY-MM-DD).');
        }
        return $dataStr;
    }

    // --- Métricas e Transações ---

    /**
     * Retorna métricas financeiras (Receita, Despesa, Resultado e Saldo Acumulado) para o mês.
     */
    public function metrics(): void
    {
        $uid = Auth::id();

        try {
            $req = new Request();
            // Uso do operador nullsafe e fallback
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

            // Métricas do Mês
            $receitas = (float)$baseQuery(LancamentoTipo::RECEITA->value)
                ->whereBetween('data', [$start, $end])
                ->sum('valor');
                
            $despesas = (float)$baseQuery(LancamentoTipo::DESPESA->value)
                ->whereBetween('data', [$start, $end])
                ->sum('valor');

            $resultado = $receitas - $despesas;

            // Saldo Acumulado (Global, ignorando transfers/saldos iniciais na métrica original)
            $acumRec = (float)$baseQuery(LancamentoTipo::RECEITA->value)
                ->where('data', '<=', $end)
                ->sum('valor');

            $acumDes = (float)$baseQuery(LancamentoTipo::DESPESA->value)
                ->where('data', '<=', $end)
                ->sum('valor');

            Response::json([
                'saldo'          => $resultado,          // Resultado do mês
                'receitas'       => $receitas,
                'despesas'       => $despesas,
                'resultado'      => $resultado,          // Mantido por compatibilidade
                'saldoAcumulado' => ($acumRec - $acumDes), // Resultado acumulado até o final do mês
            ]);
        } catch (Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Lista lançamentos do mês, excluindo transferências.
     */
    public function transactions(): void
    {
        $uid = Auth::id();

        try {
            $req = new Request();
            $month = $req->get('month') ?? $_GET['month'] ?? date('Y-m');
            $limit = min((int)($req->get('limit') ?? $_GET['limit'] ?? 50), 1000); // Limite máximo de 1000

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
                ->get(); // Não precisa de selectRaw, pois já tem with() e limit()

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
                    // Uso do operador nullsafe para evitar erros se a categoria não existir
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
    
    /**
     * Retorna as opções de categorias e contas para formulários.
     */
    public function options(): void
    {
        $uid = Auth::id();

        try {
            $baseCatsQuery = fn(string $tipo) => Categoria::where(function (Builder $q) use ($uid) {
                $q->whereNull('user_id')->orWhere('user_id', $uid);
            })
                ->whereIn('tipo', [$tipo, LancamentoTipo::AMBAS->value])
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

    /**
     * Cria um novo lançamento (Receita/Despesa).
     */
    public function store(): void
    {
        try {
            $data = $this->getRequestPayload();
            $uid = Auth::id();

            $tipo = $this->validateTipo($data['tipo'] ?? LancamentoTipo::DESPESA->value);
            $dataStr = $this->validateData($data['data'] ?? date('Y-m-d'));
            $valor = $this->validateAndSanitizeValor($data['valor'] ?? 0);

            // 1. Validação de Categoria
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
                if (!in_array($cat->tipo, [LancamentoTipo::AMBAS->value, $tipo], true)) {
                    throw new ValueError('Categoria incompatível com o tipo de lançamento.');
                }
            } else {
                $categoriaId = null;
            }

            // 2. Validação de Conta
            $contaId = $data['conta_id'] ?? null;
            if ($contaId !== null && $contaId !== '') {
                $contaId = (int)$contaId;
                if (!Conta::forUser($uid)->find($contaId)) {
                    throw new ValueError('Conta inválida.');
                }
            } else {
                $contaId = null;
            }

            // 3. Criação
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
            ]);
            $t->save();

            Response::json(['ok' => true, 'id' => (int)$t->id], 201);
        } catch (ValueError $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Atualiza um lançamento (Receita/Despesa).
     * @param mixed $routeParam ID do lançamento (vindo da rota).
     */
    public function update(mixed $routeParam = null): void
    {
        try {
            $uid = Auth::id();
            if (!$uid) {
                Response::json(['status' => 'error', 'message' => 'Não autenticado'], 401);
                return;
            }

            // 1. Extração do ID da Rota
            $id = (int)(is_array($routeParam) ? ($routeParam['id'] ?? 0) : $routeParam);
            
            if ($id <= 0) {
                throw new ValueError('ID inválido.');
            }

            /** @var Lancamento|null $lanc */
            $lanc = Lancamento::where('user_id', $uid)->find($id);
            if (!$lanc) {
                Response::json(['status' => 'error', 'message' => 'Lançamento não encontrado.'], 404);
                return;
            }

            // Validação de restrições de edição
            if ((bool)($lanc->eh_transferencia ?? 0) === true) {
                throw new ValueError('Transferências não podem ser editadas aqui.');
            }
            if ((bool)($lanc->eh_saldo_inicial ?? 0) === true) {
                throw new ValueError('Saldo inicial não pode ser editado.');
            }

            $data = $this->getRequestPayload();

            // 2. Validação e Sanitização (usando valor atual como fallback)
            $tipo = $this->validateTipo($data['tipo'] ?? $lanc->tipo ?? LancamentoTipo::DESPESA->value);
            $dataStr = $this->validateData($data['data'] ?? $lanc->data ?? date('Y-m-d'));
            
            // Tenta validar e sanitizar o valor se ele foi fornecido, senão usa o valor existente
            $valorRaw = $data['valor'] ?? $lanc->valor;
            $valor = $this->validateAndSanitizeValor($valorRaw);
            
            // 3. Validação de Categoria (permite NULL)
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
                if (!in_array($cat->tipo, [LancamentoTipo::AMBAS->value, $tipo], true)) {
                    throw new ValueError('Categoria incompatível com o tipo de lançamento.');
                }
            } else {
                $categoriaId = null;
            }

            // 4. Validação de Conta
            $contaId = $data['conta_id'] ?? $lanc->conta_id;
            if ($contaId !== null && $contaId !== '') {
                $contaId = (int)$contaId;
                if (!Conta::forUser($uid)->find($contaId)) {
                    throw new ValueError('Conta inválida.');
                }
            } else {
                 throw new ValueError('Conta é obrigatória.');
            }

            // 5. Atualização
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

    /**
     * Cria uma transferência entre contas.
     */
    public function transfer(): void
    {
        try {
            $uid = Auth::id();
            $data = $this->getRequestPayload();

            $dataStr = $this->validateData($data['data'] ?? date('Y-m-d'));
            $valor = $this->validateAndSanitizeValor($data['valor'] ?? 0);

            // 1. Validação de Contas
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

            // 2. Criação da Transferência (é um Lançamento único com campos extras)
            $t = new Lancamento([
                'user_id'           => $uid,
                // Tipo é definido pelo Model Lancamento::TIPO_TRANSFERENCIA (assumindo que existe)
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