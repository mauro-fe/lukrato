<?php

namespace Application\Controllers\Api\Financeiro;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Services\Conta\TransferenciaService;
use Application\Models\Conta;
use Application\Enums\LancamentoTipo;
use Application\Enums\CategoriaTipo;
use Application\Validators\LancamentoValidator;
use Application\DTO\Requests\CreateLancamentoDTO;
use Application\DTO\Requests\UpdateLancamentoDTO;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Services\User\OnboardingProgressService;
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
    private OnboardingProgressService $onboardingProgressService;

    public function __construct(
        ?LancamentoLimitService $limitService = null,
        ?TransferenciaService $transferenciaService = null,
        ?LancamentoRepository $lancamentoRepo = null,
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null,
        ?OnboardingProgressService $onboardingProgressService = null
    ) {
        parent::__construct();
        $this->limitService = $limitService ?? new LancamentoLimitService();
        $this->transferenciaService = $transferenciaService ?? new TransferenciaService();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->categoriaRepo = $categoriaRepo ?? new CategoriaRepository();
        $this->contaRepo = $contaRepo ?? new ContaRepository();
        $this->onboardingProgressService = $onboardingProgressService ?? new OnboardingProgressService();
    }

    /**
     * GET /api/dashboard/metrics
     *
     * Suporta visualizacao por competencia ou caixa.
     */
    public function metrics(): Response
    {
        $uid = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $period = $this->parseYearMonth($this->getStringQuery('month', date('Y-m')));
            $viewType = $this->getStringQuery('view', 'caixa');
            $startStr = $period['start'];
            $endStr = $period['end'];

            if ($viewType === 'competencia') {
                $receitas = $this->lancamentoRepo->sumReceitasCompetencia($uid, $startStr, $endStr);
                $despesas = $this->lancamentoRepo->sumDespesasCompetencia($uid, $startStr, $endStr);
            } else {
                $receitas = $this->lancamentoRepo->sumReceitasCaixa($uid, $startStr, $endStr);
                $despesas = $this->lancamentoRepo->sumDespesasCaixa($uid, $startStr, $endStr);
            }

            $resultado = $receitas - $despesas;
            $saldoAcumulado = $this->calcularSaldoAcumulado($uid, $endStr);

            return Response::successResponse([
                'saldo' => $saldoAcumulado,
                'receitas' => $receitas,
                'despesas' => $despesas,
                'resultado' => $resultado,
                'saldoAcumulado' => $saldoAcumulado,
                'view' => $viewType,
            ]);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar metricas.');
        }
    }

    private function calcularSaldoAcumulado(int $userId, string $ate): float
    {
        $saldosIniciais = (float) Conta::forUser($userId)
            ->ativas()
            ->sum('saldo_inicial');

        $receitas = (float) Lancamento::where('user_id', $userId)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('data', '<=', $ate)
            ->sum('valor');

        $despesas = (float) Lancamento::where('user_id', $userId)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('data', '<=', $ate)
            ->sum('valor');

        return $saldosIniciais + $receitas - $despesas;
    }

    public function transactions(): Response
    {
        $uid = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $period = $this->parseYearMonth($this->getStringQuery('month', date('Y-m')));
            $limit = min($this->getIntQuery('limit', 50), 1000);

            $rows = Lancamento::with('categoria:id,nome')
                ->whereBetween('data', [$period['start'], $period['end']])
                ->when($uid, fn($q) => $q->where('user_id', $uid))
                ->where('eh_transferencia', 0)
                ->orderBy('data', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            $out = $rows->map(function (Lancamento $t) {
                return [
                    'id' => (int) $t->id,
                    'data' => (string) $t->data,
                    'tipo' => (string) $t->tipo,
                    'descricao' => (string) ($t->descricao ?? ''),
                    'observacao' => (string) ($t->observacao ?? ''),
                    'valor' => (float) $t->valor,
                    'eh_transferencia' => (bool) ($t->eh_transferencia ?? 0),
                    'eh_saldo_inicial' => (bool) ($t->eh_saldo_inicial ?? 0),
                    'categoria' => $t->categoria
                        ? ['id' => (int) $t->categoria->id, 'nome' => (string) $t->categoria->nome]
                        : null,
                ];
            })->all();

            return Response::successResponse($out);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar transacoes.');
        }
    }

    public function options(): Response
    {
        $uid = $this->requireApiUserIdAndReleaseSessionOrFail();

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

            return Response::successResponse([
                'categorias' => [
                    'receitas' => $catsReceita->map(fn(Categoria $c) => ['id' => (int) $c->id, 'nome' => (string) $c->nome])->all(),
                    'despesas' => $catsDespesa->map(fn(Categoria $c) => ['id' => (int) $c->id, 'nome' => (string) $c->nome])->all(),
                ],
                'contas' => $contas->map(fn(Conta $c) => ['id' => (int) $c->id, 'nome' => (string) $c->nome])->all(),
            ]);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao carregar opcoes.');
        }
    }

    public function store(): Response
    {
        try {
            $uid = $this->requireApiUserIdOrFail();

            $payload = $this->getRequestPayload();
            $errors = LancamentoValidator::validateCreate($payload);

            $contaId = $this->normalizeOptionalId($payload['conta_id'] ?? null);
            $categoriaId = $this->normalizeOptionalId($payload['categoria_id'] ?? null);
            $errors = array_merge($errors, $this->validateLancamentoRelations($uid, $contaId, $categoriaId));

            if (!empty($errors)) {
                return Response::validationErrorResponse($errors);
            }

            try {
                $usage = $this->limitService->assertCanCreate($uid, (string) ($payload['data'] ?? ''));
            } catch (DomainException $e) {
                return $this->domainErrorResponse($e, 'Nao foi possivel criar o lancamento.', 402);
            }

            $dto = CreateLancamentoDTO::fromRequest(
                $uid,
                $this->buildLancamentoWriteData($payload, $contaId, $categoriaId)
            );

            $lancamento = $this->lancamentoRepo->create($dto->toArray());
            $this->syncOnboardingLancamentoCreated($uid, $lancamento->created_at);
            $usage = $this->limitService->usage($uid, substr((string) ($payload['data'] ?? ''), 0, 7));

            return Response::successResponse([
                'ok' => true,
                'id' => (int) $lancamento->id,
                'usage' => $usage,
                'ui_message' => $this->limitService->getWarningMessage($usage),
                'upgrade_cta' => ($usage['should_warn'] ?? false) ? $this->limitService->getUpgradeCta() : null,
            ], 'Lancamento criado', 201);
        } catch (ValueError $e) {
            return $this->domainErrorResponse($e, 'Dados invalidos para criar lancamento.', 422);
        } catch (Throwable $e) {
            return Response::errorResponse('Erro ao salvar lancamento.', 500);
        }
    }

    public function update(mixed $routeParam = null): Response
    {
        try {
            $uid = $this->requireApiUserIdOrFail();

            $id = $this->extractLancamentoId($routeParam);
            if ($id <= 0) {
                throw new ValueError('ID invalido.');
            }

            $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $uid);
            if (!$lancamento) {
                return Response::errorResponse('Lancamento nao encontrado.', 404);
            }

            if ((bool) ($lancamento->eh_transferencia ?? 0) === true) {
                throw new ValueError('Transferencias nao podem ser editadas aqui.');
            }

            $payload = $this->getRequestPayload();
            $mergedData = $this->mergeLancamentoPayload($payload, $lancamento);
            $errors = LancamentoValidator::validateUpdate($mergedData);

            $contaId = $this->normalizeOptionalId($mergedData['conta_id'] ?? null);
            $categoriaId = $this->normalizeOptionalId($mergedData['categoria_id'] ?? null);
            $errors = array_merge($errors, $this->validateLancamentoRelations($uid, $contaId, $categoriaId));

            if (!empty($errors)) {
                return Response::validationErrorResponse($errors);
            }

            $dto = UpdateLancamentoDTO::fromRequest(
                $this->buildLancamentoWriteData($mergedData, $contaId, $categoriaId)
            );

            $this->lancamentoRepo->update($lancamento->id, $dto->toArray());
            return Response::successResponse(['id' => (int) $lancamento->id]);
        } catch (ValueError $e) {
            return $this->domainErrorResponse($e, 'Dados invalidos para atualizar lancamento.', 422);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao atualizar lancamento.');
        }
    }

    public function transfer(): Response
    {
        try {
            $uid = $this->requireApiUserIdOrFail();

            $data = $this->getRequestPayload();
            $dataStr = trim((string) ($data['data'] ?? date('Y-m-d')));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataStr)) {
                throw new ValueError('Data invalida (YYYY-MM-DD).');
            }

            $valor = LancamentoValidator::sanitizeValor($data['valor'] ?? 0);
            if ($valor <= 0) {
                throw new ValueError('Valor deve ser maior que zero.');
            }

            $origemId = (int) ($data['conta_id'] ?? 0);
            $destinoId = (int) ($data['conta_id_destino'] ?? 0);

            $transferencia = $this->transferenciaService->executarTransferencia(
                userId: $uid,
                contaOrigemId: $origemId,
                contaDestinoId: $destinoId,
                valor: $valor,
                data: $dataStr,
                descricao: $data['descricao'] ?? null,
                observacao: $data['observacao'] ?? null
            );

            return Response::successResponse(['id' => (int) $transferencia->id], 'Success', 201);
        } catch (ValueError $e) {
            return $this->domainErrorResponse($e, 'Dados invalidos para realizar transferencia.', 422);
        } catch (Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao realizar transferencia.');
        }
    }

    private function normalizeOptionalId(mixed $value): ?int
    {
        if (!is_scalar($value)) {
            return null;
        }

        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    private function validateLancamentoRelations(int $userId, ?int $contaId, ?int $categoriaId): array
    {
        $errors = [];

        if ($contaId !== null && !$this->contaRepo->belongsToUser($contaId, $userId)) {
            $errors['conta_id'] = 'Conta invalida.';
        }

        if ($categoriaId !== null && !$this->categoriaRepo->belongsToUser($categoriaId, $userId)) {
            $errors['categoria_id'] = 'Categoria invalida.';
        }

        return $errors;
    }

    private function buildLancamentoWriteData(array $data, ?int $contaId, ?int $categoriaId): array
    {
        return [
            'tipo' => strtolower(trim((string) ($data['tipo'] ?? ''))),
            'data' => (string) ($data['data'] ?? ''),
            'valor' => LancamentoValidator::sanitizeValor($data['valor'] ?? 0),
            'descricao' => mb_substr(trim((string) ($data['descricao'] ?? '')), 0, 190),
            'observacao' => mb_substr(trim((string) ($data['observacao'] ?? '')), 0, 500),
            'categoria_id' => $categoriaId,
            'conta_id' => $contaId,
        ];
    }

    private function extractLancamentoId(mixed $routeParam): int
    {
        return (int) (is_array($routeParam) ? ($routeParam['id'] ?? 0) : $routeParam);
    }

    private function mergeLancamentoPayload(array $payload, Lancamento $lancamento): array
    {
        return [
            'tipo' => $payload['tipo'] ?? $lancamento->tipo,
            'data' => $payload['data'] ?? $lancamento->data,
            'valor' => $payload['valor'] ?? $lancamento->valor,
            'descricao' => $payload['descricao'] ?? $lancamento->descricao,
            'observacao' => $payload['observacao'] ?? $lancamento->observacao,
            'conta_id' => $payload['conta_id'] ?? $lancamento->conta_id,
            'categoria_id' => $payload['categoria_id'] ?? $lancamento->categoria_id,
        ];
    }

    private function syncOnboardingLancamentoCreated(int $userId, \DateTimeInterface|string|null $createdAt = null): void
    {
        try {
            $this->onboardingProgressService->markLancamentoCreated($userId, $createdAt);
        } catch (Throwable $e) {
            \Application\Services\Infrastructure\LogService::captureException($e, \Application\Enums\LogCategory::GENERAL, [
                'action' => 'sync_onboarding_lancamento_created_legacy',
                'user_id' => $userId,
            ]);
        }
    }
}
