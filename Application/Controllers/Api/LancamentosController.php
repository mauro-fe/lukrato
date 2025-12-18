<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Categoria;
use Application\Models\Conta;
use Application\Models\Lancamento;
use Application\Models\Usuario;
use Application\Services\LancamentoExportService;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;
use Application\Services\LancamentoLimitService;
use InvalidArgumentException;
use ValueError;

enum LancamentoTipo: string
{
    case DESPESA = 'despesa';
    case RECEITA = 'receita';

    public static function listValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}

class LancamentosController
{
    private LancamentoLimitService $limitService;
    private LancamentoExportService $exportService;

    public function __construct(
        ?LancamentoExportService $exportService = null,
        ?LancamentoLimitService $limitService = null
    ) {
        $this->exportService = $exportService ?? new LancamentoExportService();
        $this->limitService  = $limitService ?? new LancamentoLimitService();
    }

    // =============================================================================
    // HELPERS
    // =============================================================================

    private function getRequestPayload(): array
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($payload)) {
            $payload = $_POST ?? [];
        }
        return $payload;
    }

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

    private function validateAndSanitizeValor(mixed $valorRaw, array &$errors): float
    {
        if (is_string($valorRaw)) {
            $s = trim($valorRaw);
            $s = str_replace(['R$', ' ', '.'], '', $s);
            $s = str_replace(',', '.', $s);
            $valorRaw = $s;
        }

        if (!is_numeric($valorRaw) || !is_finite((float)$valorRaw)) {
            $errors['valor'] = 'Valor inválido.';
            return 0.0;
        }

        $valor = abs((float)$valorRaw);
        return round($valor, 2);
    }

    private function validateCategoria(?int $id, int $userId, array &$errors): ?int
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        $exists = Categoria::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->exists();

        if ($exists) {
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

        if (Conta::forUser($userId)->where('id', $id)->exists()) {
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
        $errors = [];

        $tipo = strtolower(trim((string)($payload['tipo'] ?? '')));
        try {
            $tipo = LancamentoTipo::from($tipo)->value;
        } catch (ValueError) {
            $errors['tipo'] = 'Tipo invalido. Use "receita" ou "despesa".';
        }

        $data = (string)($payload['data'] ?? '');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $data)) {
            $errors['data'] = 'Data invalida. Use o formato YYYY-MM-DD.';
        }

        $valorRaw = $payload['valor'] ?? 0;
        $valor = $this->validateAndSanitizeValor($valorRaw, $errors);

        $contaId = $payload['conta_id'] ?? $payload['contaId'] ?? null;
        $contaId = is_scalar($contaId) ? (int)$contaId : null;
        $contaId = $this->validateConta($contaId, $userId, $errors);

        $categoriaId = $payload['categoria_id'] ?? $payload['categoriaId'] ?? null;
        $categoriaId = is_scalar($categoriaId) ? (int)$categoriaId : null;
        $categoriaId = $this->validateCategoria($categoriaId, $userId, $errors);

        $descricao = trim((string)($payload['descricao'] ?? ''));
        $observacao = trim((string)($payload['observacao'] ?? ''));

        $descricao = mb_substr($descricao, 0, 190);
        $observacao = mb_substr($observacao, 0, 500);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        $lancamento = Lancamento::create([
            'user_id' => $userId,
            'tipo' => $tipo,
            'data' => $data,
            'valor' => $valor,
            'descricao' => $descricao,
            'observacao' => $observacao,
            'categoria_id' => $categoriaId,
            'conta_id' => $contaId,
            'conta_id_destino' => null,
            'eh_transferencia' => 0,
            'eh_saldo_inicial' => 0,
        ]);

        $lancamento->loadMissing(['categoria', 'conta']);

        // Valida limites e obtém informações de uso
        $ym = substr($data, 0, 7);
        try {
            $usage = $this->limitService->assertCanCreate($userId, $data);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 402);
            return;
        }

        // Prepara resposta com dados do lançamento
        $lancamentoData = [
            'id'               => (int) $lancamento->id,
            'data'             => (string) $lancamento->data,
            'tipo'             => (string) $lancamento->tipo,
            'valor'            => (float) $lancamento->valor,
            'descricao'        => (string) ($lancamento->descricao ?? ''),
            'observacao'       => (string) ($lancamento->observacao ?? ''),
            'categoria_id'     => (int) $lancamento->categoria_id ?: null,
            'conta_id'         => (int) $lancamento->conta_id ?: null,
            'eh_transferencia' => (bool) $lancamento->eh_transferencia,
            'eh_saldo_inicial' => (bool) $lancamento->eh_saldo_inicial,
            'categoria'        => $lancamento->categoria?->nome ?? '',
            'categoria_nome'   => $lancamento->categoria?->nome ?? '',
            'conta'            => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
            'conta_nome'       => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
        ];

        Response::success([
            'lancamento' => $lancamentoData,
            'usage'      => $usage,
            'ui_message' => $this->limitService->getWarningMessage($usage),
            'upgrade_cta' => ($usage['should_warn'] ?? false) ? $this->limitService->getUpgradeCta() : null,
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

        $lancamento = Lancamento::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$lancamento) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        if ((bool)($lancamento->eh_saldo_inicial ?? 0) === true) {
            Response::error('Nao e possivel editar o saldo inicial.', 422);
            return;
        }
        if ((bool)($lancamento->eh_transferencia ?? 0) === true) {
            Response::error('Nao e possivel editar uma transferencia. Crie uma nova.', 422);
            return;
        }

        $errors = [];

        $tipo = strtolower(trim((string)($payload['tipo'] ?? $lancamento->tipo ?? '')));
        try {
            $tipo = LancamentoTipo::from($tipo)->value;
        } catch (ValueError) {
            $errors['tipo'] = 'Tipo invalido. Use "receita" ou "despesa".';
        }

        $data = (string)($payload['data'] ?? $lancamento->data ?? '');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $data)) {
            $errors['data'] = 'Data invalida. Use o formato YYYY-MM-DD.';
        }

        $valorRaw = $payload['valor'] ?? $lancamento->valor ?? 0;
        $valor = $this->validateAndSanitizeValor($valorRaw, $errors);

        $contaId = $payload['conta_id'] ?? $payload['contaId'] ?? $lancamento->conta_id;
        $contaId = is_scalar($contaId) ? (int)$contaId : null;
        $contaId = $this->validateConta($contaId, $userId, $errors);

        $categoriaId = $payload['categoria_id'] ?? $payload['categoriaId'] ?? $lancamento->categoria_id;
        $categoriaId = is_scalar($categoriaId) ? (int)$categoriaId : null;
        $categoriaId = $this->validateCategoria($categoriaId, $userId, $errors);

        $descricao = trim((string)($payload['descricao'] ?? $lancamento->descricao ?? ''));
        $observacao = trim((string)($payload['observacao'] ?? $lancamento->observacao ?? ''));

        $descricao = mb_substr($descricao, 0, 190);
        $observacao = mb_substr($observacao, 0, 500);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        $lancamento->tipo = $tipo;
        $lancamento->data = $data;
        $lancamento->valor = $valor;
        $lancamento->descricao = $descricao;
        $lancamento->observacao = $observacao;
        $lancamento->categoria_id = $categoriaId;
        $lancamento->conta_id = $contaId;
        $lancamento->conta_id_destino = null;
        $lancamento->eh_transferencia = 0;
        $lancamento->save();

        $lancamento->refresh()->loadMissing(['categoria', 'conta']);

        Response::success([
            'id'               => (int)$lancamento->id,
            'data'             => (string)$lancamento->data,
            'tipo'             => (string)$lancamento->tipo,
            'valor'            => (float)$lancamento->valor,
            'descricao'        => (string)($lancamento->descricao ?? ''),
            'observacao'       => (string)($lancamento->observacao ?? ''),
            'categoria_id'     => (int)$lancamento->categoria_id ?: null,
            'conta_id'         => (int)$lancamento->conta_id ?: null,
            'eh_transferencia' => (bool)$lancamento->eh_transferencia,
            'eh_saldo_inicial' => (bool)$lancamento->eh_saldo_inicial,
            'categoria'        => $lancamento->categoria?->nome ?? '',
            'categoria_nome'   => $lancamento->categoria?->nome ?? '',
            'conta'            => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
            'conta_nome'       => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
        ]);
    }

    public function destroy(int $id): void
    {
        $uid = Auth::id();
        if (!$uid) {
            Response::error('Nao autenticado', 401);
            return;
        }

        /** @var Lancamento|null $t */
        $t = Lancamento::where('user_id', $uid)
            ->where('id', $id)
            ->first();

        if (!$t) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        if ((bool)($t->eh_saldo_inicial ?? 0) === true) {
            Response::error('Nao e possivel excluir o saldo inicial.', 422);
            return;
        }

        $t->delete();
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
            'usage'       => $usage,
            'ui_message'  => $this->limitService->getWarningMessage($usage),
            'upgrade_cta' => ($usage['should_warn'] ?? false) ? $this->limitService->getUpgradeCta() : null,
        ]);
    }
}
