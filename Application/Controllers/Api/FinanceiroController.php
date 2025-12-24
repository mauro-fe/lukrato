<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Request;
use Application\Core\Response;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Services\LancamentoLimitService;
use Application\Services\TransferenciaService;
use Application\Models\Conta;
use Application\Enums\LancamentoTipo;
use Application\Enums\CategoriaTipo;
use Application\Validators\LancamentoValidator;
use Application\DTOs\Requests\CreateLancamentoDTO;
use Application\DTOs\Requests\UpdateLancamentoDTO;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Carbon\Carbon;
use Application\Lib\Auth;
use ValueError;
use Throwable;
use Illuminate\Database\Eloquent\Builder;
use DomainException;

class FinanceiroController extends BaseController
{
    private LancamentoLimitService $limitService;
    private TransferenciaService $transferenciaService;
    private LancamentoRepository $lancamentoRepo;
    private CategoriaRepository $categoriaRepo;
    private ContaRepository $contaRepo;

    public function __construct(
        ?LancamentoLimitService $limitService = null,
        ?TransferenciaService $transferenciaService = null,
        ?LancamentoRepository $lancamentoRepo = null,
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null
    ) {
        $this->limitService = $limitService ?? new LancamentoLimitService();
        $this->transferenciaService = $transferenciaService ?? new TransferenciaService();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->categoriaRepo = $categoriaRepo ?? new CategoriaRepository();
        $this->contaRepo = $contaRepo ?? new ContaRepository();
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
            $payload = $this->getRequestPayload();
            $uid = Auth::id();

            if (!$uid) {
                Response::error('Nao autenticado', 401);
                return;
            }

            // Validar com o validator
            $errors = LancamentoValidator::validateCreate($payload);

            // Validar conta e categoria (regras de negócio)
            $contaId = $payload['conta_id'] ?? null;
            $contaId = is_scalar($contaId) ? (int)$contaId : null;
            if ($contaId && !$this->contaRepo->belongsToUser($contaId, $uid)) {
                $errors['conta_id'] = 'Conta inválida.';
            }

            $categoriaId = $payload['categoria_id'] ?? null;
            $categoriaId = is_scalar($categoriaId) ? (int)$categoriaId : null;
            if ($categoriaId && !$this->categoriaRepo->belongsToUser($categoriaId, $uid)) {
                $errors['categoria_id'] = 'Categoria inválida.';
            }

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            // Verificar limite antes de criar
            try {
                $usage = $this->limitService->assertCanCreate($uid, $payload['data']);
            } catch (DomainException $e) {
                Response::error($e->getMessage(), 402);
                return;
            }

            // Criar DTO
            $dto = CreateLancamentoDTO::fromRequest($uid, [
                'tipo' => strtolower(trim($payload['tipo'])),
                'data' => $payload['data'],
                'valor' => LancamentoValidator::sanitizeValor($payload['valor']),
                'descricao' => mb_substr(trim($payload['descricao'] ?? ''), 0, 190),
                'observacao' => mb_substr(trim($payload['observacao'] ?? ''), 0, 500),
                'categoria_id' => $categoriaId,
                'conta_id' => $contaId,
            ]);

            // Criar lançamento
            $lancamento = $this->lancamentoRepo->create($dto->toArray());

            // Atualizar usage
            $usage = $this->limitService->usage($uid, substr($payload['data'], 0, 7));

            Response::success([
                'ok' => true,
                'id' => (int)$lancamento->id,
                'usage' => $usage,
                'ui_message' => $this->limitService->getWarningMessage($usage),
                'upgrade_cta' => ($usage['should_warn'] ?? false) ? $this->limitService->getUpgradeCta() : null
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

            $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $uid);
            if (!$lancamento) {
                Response::json(['status' => 'error', 'message' => 'Lançamento não encontrado.'], 404);
                return;
            }

            if ((bool)($lancamento->eh_transferencia ?? 0) === true) {
                throw new ValueError('Transferências não podem ser editadas aqui.');
            }

            $payload = $this->getRequestPayload();

            // Mesclar dados atuais com novos
            $mergedData = [
                'tipo' => $payload['tipo'] ?? $lancamento->tipo,
                'data' => $payload['data'] ?? $lancamento->data,
                'valor' => $payload['valor'] ?? $lancamento->valor,
                'descricao' => $payload['descricao'] ?? $lancamento->descricao,
                'observacao' => $payload['observacao'] ?? $lancamento->observacao,
                'conta_id' => $payload['conta_id'] ?? $lancamento->conta_id,
                'categoria_id' => $payload['categoria_id'] ?? $lancamento->categoria_id,
            ];

            // Validar
            $errors = LancamentoValidator::validateUpdate($mergedData);

            // Validar conta e categoria
            $contaId = is_scalar($mergedData['conta_id']) ? (int)$mergedData['conta_id'] : null;
            if ($contaId && !$this->contaRepo->belongsToUser($contaId, $uid)) {
                $errors['conta_id'] = 'Conta inválida.';
            }

            $categoriaId = is_scalar($mergedData['categoria_id']) ? (int)$mergedData['categoria_id'] : null;
            if ($categoriaId && !$this->categoriaRepo->belongsToUser($categoriaId, $uid)) {
                $errors['categoria_id'] = 'Categoria inválida.';
            }

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            // Criar DTO
            $dto = UpdateLancamentoDTO::fromRequest([
                'tipo' => strtolower(trim($mergedData['tipo'])),
                'data' => $mergedData['data'],
                'valor' => LancamentoValidator::sanitizeValor($mergedData['valor']),
                'descricao' => mb_substr(trim($mergedData['descricao'] ?? ''), 0, 190),
                'observacao' => mb_substr(trim($mergedData['observacao'] ?? ''), 0, 500),
                'categoria_id' => $categoriaId,
                'conta_id' => $contaId,
            ]);

            // Atualizar
            $this->lancamentoRepo->update($lancamento->id, $dto->toArray());

            Response::json(['ok' => true, 'id' => (int)$lancamento->id]);
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

            $dataStr = trim($data['data'] ?? date('Y-m-d'));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataStr)) {
                throw new ValueError('Data inválida (YYYY-MM-DD).');
            }

            $valor = LancamentoValidator::sanitizeValor($data['valor'] ?? 0);
            if ($valor <= 0) {
                throw new ValueError('Valor deve ser maior que zero.');
            }

            $origemId  = (int)($data['conta_id'] ?? 0);
            $destinoId = (int)($data['conta_id_destino'] ?? 0);

            // Usar TransferenciaService
            $transferencia = $this->transferenciaService->executarTransferencia(
                userId: $uid,
                contaOrigemId: $origemId,
                contaDestinoId: $destinoId,
                valor: $valor,
                data: $dataStr,
                descricao: $data['descricao'] ?? null,
                observacao: $data['observacao'] ?? null
            );

            Response::json(['ok' => true, 'id' => (int)$transferencia->id], 201);
        } catch (ValueError $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
