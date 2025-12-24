<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\LancamentoExportService;
use Application\Services\GamificationService;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;
use Application\Services\LancamentoLimitService;
use Application\Services\UserPlanService;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Enums\LancamentoTipo;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\DTOs\Requests\CreateLancamentoDTO;
use Application\DTOs\Requests\UpdateLancamentoDTO;
use Application\Validators\LancamentoValidator;
use InvalidArgumentException;
use ValueError;

class LancamentosController extends BaseController
{
    private LancamentoLimitService $limitService;
    private LancamentoExportService $exportService;
    private LancamentoRepository $lancamentoRepo;
    private CategoriaRepository $categoriaRepo;
    private ContaRepository $contaRepo;
    private UserPlanService $planService;
    private GamificationService $gamificationService;

    public function __construct(
        ?LancamentoExportService $exportService = null,
        ?LancamentoLimitService $limitService = null
    ) {
        $this->exportService = $exportService ?? new LancamentoExportService();
        $this->limitService  = $limitService ?? new LancamentoLimitService();
        $this->lancamentoRepo = new LancamentoRepository();
        $this->categoriaRepo = new CategoriaRepository();
        $this->contaRepo = new ContaRepository();
        $this->planService = new UserPlanService();
        $this->gamificationService = new GamificationService();
    }
    // =============================================================================
    // HELPERS
    // =============================================================================

    private function parseCategoriaParam(string $param): array
    {
        $id = null;
        $isNull = false;

        if (in_array(strtolower($param), ['none', 'null', '0'], true)) {
            $isNull = true;
        } elseif (is_numeric($param) && (int)$param > 0) {
            $id = (int)$param;
        }

        return ['id' => $id, 'isNull' => $isNull];
    }

    private function validateCategoria(?int $id, int $userId, array &$errors): ?int
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        if ($this->categoriaRepo->belongsToUser($id, $userId)) {
            return $id;
        }

        $errors['categoria_id'] = 'Categoria invalida.';
        return null;
    }

    private function validateConta(?int $id, int $userId, array &$errors): ?int
    {
        if ($id === null) {
            return null;
        }

        if ($this->contaRepo->belongsToUser($id, $userId)) {
            return $id;
        }

        $errors['conta_id'] = 'Conta invalida.';
        return null;
    }

    // =============================================================================
    // ENDPOINTS
    // =============================================================================

    public function index(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        if (!DB::schema()->hasTable('lancamentos')) {
            Response::success([]);
            return;
        }

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            Response::validationError(['month' => 'Formato invalido (YYYY-MM)']);
            return;
        }

        [$y, $m] = array_map('intval', explode('-', $month));
        $from = sprintf('%04d-%02d-01', $y, $m);
        $to   = date('Y-m-t', strtotime($from));

        $accId = (int)($_GET['account_id'] ?? 0) ?: null;
        $limit = min((int)($_GET['limit'] ?? 500), 1000); // Max 1000

        $categoriaParams = $this->parseCategoriaParam((string)($_GET['categoria_id'] ?? ''));
        $tipo = strtolower($_GET['tipo'] ?? '');

        try {
            $tipo = LancamentoTipo::from($tipo)->value;
        } catch (ValueError) {
            $tipo = null;
        }

        $q = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as a',     'a.id', '=', 'l.conta_id')
            ->where('l.user_id', $userId)
            ->whereBetween('l.data', [$from, $to])
            ->when($accId, fn($w) => $w->where(function (Builder $s) use ($accId) {
                $s->where('l.conta_id', $accId)
                    ->orWhere('l.conta_id_destino', $accId);
            }))
            ->when($categoriaParams['isNull'], fn($w) => $w->whereNull('l.categoria_id'))
            ->when($categoriaParams['id'], fn($w) => $w->where('l.categoria_id', $categoriaParams['id']))
            ->when($tipo, fn($w) => $w->where('l.tipo', $tipo))
            ->orderBy('l.data', 'desc')
            ->orderBy('l.id', 'desc')
            ->limit($limit);

        $rows = $q->selectRaw('
            l.id, l.data, l.tipo, l.valor, l.descricao, l.observacao, 
            l.categoria_id, l.conta_id, l.conta_id_destino, l.eh_transferencia, l.eh_saldo_inicial,
            COALESCE(c.nome, "") as categoria,
            COALESCE(a.nome, "") as conta_nome,
            COALESCE(a.instituicao, "") as conta_instituicao,
            COALESCE(a.nome, a.instituicao, "") as conta
        ')->get();

        $out = $rows->map(fn($r) => [
            'id'               => (int)$r->id,
            'data'             => (string)$r->data,
            'tipo'             => (string)$r->tipo,
            'valor'            => (float)$r->valor,
            'descricao'        => (string)($r->descricao ?? ''),
            'observacao'       => (string)($r->observacao ?? ''),
            'categoria_id'     => (int)$r->categoria_id ?: null,
            'conta_id'         => (int)$r->conta_id ?: null,
            'conta_id_destino' => (int)$r->conta_id_destino ?: null,
            'eh_transferencia' => (bool) ($r->eh_transferencia ?? 0),
            'eh_saldo_inicial' => (bool)($r->eh_saldo_inicial ?? 0),
            'categoria'        => (string)$r->categoria,
            'conta'            => (string)$r->conta,
            'conta_nome'       => (string)$r->conta_nome,
            'conta_instituicao' => (string)$r->conta_instituicao,
        ])->values()->all();

        Response::success($out);
    }

    public function export(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $filters = [
            'month' => $_GET['month'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'tipo' => $_GET['tipo'] ?? null,
            'categoria_id' => $_GET['categoria_id'] ?? null,
            'account_id' => $_GET['account_id'] ?? null,
            'include_transfers' => $_GET['include_transfers'] ?? null,
            'format' => $_GET['format'] ?? null,
        ];

        try {
            $result = $this->exportService->export($userId, $filters);
        } catch (InvalidArgumentException $e) {
            Response::validationError(['export' => $e->getMessage()]);
            return;
        } catch (\Throwable) {
            Response::error('Erro ao gerar exportacao.', 500);
            return;
        }

        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $result['mime']);
        header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');
        header('Content-Length', (string) mb_strlen($result['binary'], '8bit'));
        echo $result['binary'];
        exit;
    }

    public function store(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $payload = $this->getRequestPayload();

        // Validar com o validator
        $errors = LancamentoValidator::validateCreate($payload);

        // Validar conta e categoria (regras de negócio)
        $contaId = $payload['conta_id'] ?? $payload['contaId'] ?? null;
        $contaId = is_scalar($contaId) ? (int)$contaId : null;
        $contaId = $this->validateConta($contaId, $userId, $errors);

        $categoriaId = $payload['categoria_id'] ?? $payload['categoriaId'] ?? null;
        $categoriaId = is_scalar($categoriaId) ? (int)$categoriaId : null;
        $categoriaId = $this->validateCategoria($categoriaId, $userId, $errors);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Criar DTO
        $dto = CreateLancamentoDTO::fromRequest($userId, [
            'tipo' => strtolower(trim($payload['tipo'])),
            'data' => $payload['data'],
            'valor' => LancamentoValidator::sanitizeValor($payload['valor']),
            'descricao' => mb_substr(trim($payload['descricao'] ?? ''), 0, 190),
            'observacao' => mb_substr(trim($payload['observacao'] ?? ''), 0, 500),
            'categoria_id' => $categoriaId,
            'conta_id' => $contaId,
        ]);

        // Verificar limite antes de criar
        try {
            $usage = $this->limitService->assertCanCreate($userId, $dto->data);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 402);
            return;
        }

        // Criar lançamento
        $lancamento = $this->lancamentoRepo->create($dto->toArray());
        $lancamento->loadMissing(['categoria', 'conta']);

        // 🎮 GAMIFICAÇÃO: Adicionar pontos e atualizar streak
        $gamificationResult = [];
        try {
            // Adicionar pontos por criar lançamento
            $pointsResult = $this->gamificationService->addPoints(
                $userId,
                \Application\Enums\GamificationAction::CREATE_LANCAMENTO,
                $lancamento->id,
                'lancamento'
            );

            // Atualizar streak diária
            $streakResult = $this->gamificationService->updateStreak($userId);

            $gamificationResult = [
                'points' => $pointsResult,
                'streak' => $streakResult,
            ];
        } catch (\Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao processar gamificação: " . $e->getMessage());
            // Não falhar a requisição por erro na gamificação
        }

        Response::success([
            'lancamento' => LancamentoResponseFormatter::format($lancamento),
            'usage' => $usage,
            'ui_message' => $this->planService->getUsageMessage($usage),
            'gamification' => $gamificationResult, // Dados de gamificação para o frontend
        ], 'Lancamento criado', 201);
    }

    public function update(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $payload = $this->getRequestPayload();

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);

        if (!$lancamento) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        if ((bool)($lancamento->eh_transferencia ?? 0) === true) {
            Response::error('Nao e possivel editar uma transferencia. Crie uma nova.', 422);
            return;
        }

        // Mesclar dados atuais com novos
        $mergedData = [
            'tipo' => $payload['tipo'] ?? $lancamento->tipo,
            'data' => $payload['data'] ?? $lancamento->data,
            'valor' => $payload['valor'] ?? $lancamento->valor,
            'descricao' => $payload['descricao'] ?? $lancamento->descricao,
            'observacao' => $payload['observacao'] ?? $lancamento->observacao,
            'conta_id' => $payload['conta_id'] ?? $payload['contaId'] ?? $lancamento->conta_id,
            'categoria_id' => $payload['categoria_id'] ?? $payload['categoriaId'] ?? $lancamento->categoria_id,
        ];

        // Validar
        $errors = LancamentoValidator::validateUpdate($mergedData);

        // Validar conta e categoria (regras de negócio)
        $contaId = is_scalar($mergedData['conta_id']) ? (int)$mergedData['conta_id'] : null;
        $contaId = $this->validateConta($contaId, $userId, $errors);

        $categoriaId = is_scalar($mergedData['categoria_id']) ? (int)$mergedData['categoria_id'] : null;
        $categoriaId = $this->validateCategoria($categoriaId, $userId, $errors);

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
        $this->lancamentoRepo->update($id, $dto->toArray());

        $lancamento = $this->lancamentoRepo->find($id);
        $lancamento->loadMissing(['categoria', 'conta']);

        Response::success(LancamentoResponseFormatter::format($lancamento));
    }

    public function destroy(int $id): void
    {
        $uid = Auth::id();
        if (!$uid) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $t = $this->lancamentoRepo->findByIdAndUser($id, $uid);

        if (!$t) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        $this->lancamentoRepo->delete($id);
        Response::success(['ok' => true]);
    }

    /**
     * Endpoint para o front consultar uso do mês (mostrar banner no dashboard, etc.)
     * GET /api/lancamentos/usage?month=YYYY-MM
     */
    public function usage(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            Response::validationError(['month' => 'Formato invalido (YYYY-MM)']);
            return;
        }

        $usage = $this->limitService->usage($userId, $month);

        Response::success([
            'usage' => $usage,
            'ui_message' => $this->limitService->getWarningMessage($usage),
            'upgrade_cta' => ($usage['should_warn'] ?? false) ? $this->limitService->getUpgradeCta() : null
        ]);
    }
}
