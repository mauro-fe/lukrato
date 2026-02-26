<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Models\FaturaCartaoItem;
use Application\Models\Fatura;
use Application\Services\LancamentoExportService;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;
use Application\Services\LancamentoLimitService;
use Application\Services\LancamentoCreationService;
use Application\Services\UserPlanService;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Enums\LancamentoTipo;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\CategoriaRepository;
use Application\Services\LogService;
use Application\Enums\LogCategory;
use Application\Repositories\ContaRepository;
use Application\DTO\Requests\CreateLancamentoDTO;
use Application\DTO\Requests\UpdateLancamentoDTO;
use Application\DTO\ServiceResultDTO;
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
    private LancamentoCreationService $creationService;

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
        $this->creationService = new LancamentoCreationService();
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

        // Liberar lock da sessão para permitir requisições paralelas
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
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

        // Listagem de lançamentos por mês (mantemos comportamento padrão: mostrar
        // todos os lançamentos do mês). Se for necessário ocultar pagos em uma
        // tela específica, o frontend deve passar `hide_paid=1` ou `include_paid=0`.
        $q = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as a',     'a.id', '=', 'l.conta_id')
            ->leftJoin('cartoes_credito as cc', 'cc.id', '=', 'l.cartao_credito_id')
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
            l.pago, l.parcelamento_id, l.cartao_credito_id, l.forma_pagamento, l.origem_tipo,
            l.recorrente, l.recorrencia_freq, l.recorrencia_fim, l.recorrencia_total, l.recorrencia_pai_id, l.cancelado_em,
            l.lembrar_antes_segundos, l.canal_email, l.canal_inapp,
            COALESCE(c.nome, "") as categoria,
            COALESCE(a.nome, "") as conta_nome,
            COALESCE(a.instituicao, "") as conta_instituicao,
            COALESCE(a.nome, a.instituicao, "") as conta,
            COALESCE(cc.nome_cartao, "") as cartao_nome,
            COALESCE(cc.bandeira, "") as cartao_bandeira
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
            'pago'             => (bool)($r->pago ?? 0),
            'parcelamento_id'  => (int)$r->parcelamento_id ?: null,
            'cartao_credito_id' => (int)$r->cartao_credito_id ?: null,
            'categoria'        => (string)$r->categoria,
            'conta'            => (string)$r->conta,
            'conta_nome'       => (string)$r->conta_nome,
            'conta_instituicao' => (string)$r->conta_instituicao,
            'cartao_nome'      => (string)($r->cartao_nome ?? ''),
            'cartao_bandeira'  => (string)($r->cartao_bandeira ?? ''),
            'forma_pagamento'  => (string)($r->forma_pagamento ?? ''),
            'origem_tipo'      => (string)($r->origem_tipo ?? ''),
            // Recorrência
            'recorrente'       => (bool)($r->recorrente ?? 0),
            'recorrencia_freq' => $r->recorrencia_freq ?? null,
            'recorrencia_fim'  => $r->recorrencia_fim ?? null,
            'recorrencia_total' => $r->recorrencia_total ? (int)$r->recorrencia_total : null,
            'recorrencia_pai_id' => $r->recorrencia_pai_id ? (int)$r->recorrencia_pai_id : null,
            'cancelado_em'     => $r->cancelado_em ?? null,
            // Lembretes
            'lembrar_antes_segundos' => $r->lembrar_antes_segundos ? (int)$r->lembrar_antes_segundos : null,
            'canal_email'      => (bool)($r->canal_email ?? 0),
            'canal_inapp'      => (bool)($r->canal_inapp ?? 0),
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

        // Verificar se usuário é PRO
        $user = Usuario::find($userId);
        if (!$user || !$user->isPro()) {
            Response::error('Exportação de lançamentos é um recurso exclusivo do plano PRO.', 403);
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

        // Parsear campos comuns
        $formaPagamento = $payload['forma_pagamento'] ?? null;
        $formaPagamento = is_string($formaPagamento) && !empty($formaPagamento) ? $formaPagamento : null;
        $tipoLancamento = strtolower(trim($payload['tipo'] ?? ''));
        $cartaoCreditoId = $payload['cartao_credito_id'] ?? null;
        $cartaoCreditoId = is_scalar($cartaoCreditoId) ? (int)$cartaoCreditoId : null;

        $ehEstornoCartao = ($cartaoCreditoId && $cartaoCreditoId > 0 && $tipoLancamento === 'receita' && $formaPagamento === 'estorno_cartao');

        // Validar campos
        $errors = LancamentoValidator::validateCreate($payload);

        $contaId = $payload['conta_id'] ?? $payload['contaId'] ?? null;
        $contaId = is_scalar($contaId) ? (int)$contaId : null;
        if (!$ehEstornoCartao) {
            $contaId = $this->validateConta($contaId, $userId, $errors);
        }

        $categoriaId = $payload['categoria_id'] ?? $payload['categoriaId'] ?? null;
        $categoriaId = is_scalar($categoriaId) ? (int)$categoriaId : null;
        $categoriaId = $this->validateCategoria($categoriaId, $userId, $errors);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        try {
            // ── Fluxo 1: Estorno de cartão ──
            if ($ehEstornoCartao) {
                $result = $this->creationService->createEstorno($userId, $payload, $cartaoCreditoId, $categoriaId);
                $this->sendCreationResponse($result);
                return;
            }

            // Verificar limite
            $pago = !isset($payload['pago']) || (bool)$payload['pago'];
            if (isset($payload['agendado']) && $payload['agendado']) {
                $pago = false;
            }

            $dto = CreateLancamentoDTO::fromRequest($userId, [
                'tipo'             => $tipoLancamento,
                'data'             => $payload['data'],
                'valor'            => LancamentoValidator::sanitizeValor($payload['valor']),
                'descricao'        => mb_substr(trim($payload['descricao'] ?? ''), 0, 190),
                'observacao'       => mb_substr(trim($payload['observacao'] ?? ''), 0, 500),
                'categoria_id'     => $categoriaId,
                'conta_id'         => $contaId,
                'pago'             => $pago,
                'forma_pagamento'  => $formaPagamento,
                // Recorrência
                'recorrente'             => (bool)($payload['recorrente'] ?? false),
                'recorrencia_freq'       => $payload['recorrencia_freq'] ?? null,
                'recorrencia_fim'        => $payload['recorrencia_fim'] ?? null,
                'recorrencia_total'      => isset($payload['recorrencia_total']) ? (int)$payload['recorrencia_total'] : null,
                // Lembretes
                'lembrar_antes_segundos' => $payload['lembrar_antes_segundos'] ?? null,
                'canal_email'            => (bool)($payload['canal_email'] ?? false),
                'canal_inapp'            => (bool)($payload['canal_inapp'] ?? false),
            ]);

            $usage = $this->limitService->assertCanCreate($userId, $dto->data);

            // ── Fluxo 2: Compra com cartão de crédito ──
            if ($cartaoCreditoId && $cartaoCreditoId > 0 && $dto->tipo === 'despesa') {
                $result = $this->creationService->createCartaoExpense($userId, $dto, $payload, $cartaoCreditoId, $categoriaId, $usage);
                $this->sendCreationResponse($result);
                return;
            }

            // ── Fluxo 3: Lançamento normal ──
            $recorrencia = $payload['recorrencia'] ?? null;
            $numeroRepeticoes = isset($payload['numero_repeticoes']) ? (int)$payload['numero_repeticoes'] : 12;

            $result = $this->creationService->createNormal($userId, $dto, $recorrencia, $numeroRepeticoes, $usage);
            $this->sendCreationResponse($result);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 402);
        }
    }

    private function sendCreationResponse(ServiceResultDTO $result): void
    {
        if ($result->isError()) {
            Response::error($result->message, $result->httpCode);
            return;
        }
        Response::success($result->data, $result->message, 201);
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
            'forma_pagamento' => array_key_exists('forma_pagamento', $payload) ? $payload['forma_pagamento'] : $lancamento->forma_pagamento,
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
            'forma_pagamento' => $mergedData['forma_pagamento'] ?? null,
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

        // Se for um pagamento de fatura, reverter os itens da fatura
        if ($t->origem_tipo === 'pagamento_fatura' && $t->cartao_credito_id) {
            $this->reverterPagamentoFatura($t);
        }

        $this->lancamentoRepo->delete($id);
        Response::success(['ok' => true]);
    }

    /**
     * Reverte o pagamento de uma fatura quando o lançamento é excluído
     */
    private function reverterPagamentoFatura($lancamento): void
    {
        try {
            $cartaoId = $lancamento->cartao_credito_id;
            $userId = $lancamento->user_id;

            error_log("🔄 [REVERTER FATURA] Iniciando reversão - Lançamento ID: {$lancamento->id}, Cartão: {$cartaoId}");

            // Extrair mês/ano da observação (ex: "14 item(s) pago(s) - Fatura 02/2026" ou "Fatura 2/2026")
            $mes = null;
            $ano = null;

            if (preg_match('/Fatura (\d{1,2})\/(\d{4})/', $lancamento->observacao, $matches)) {
                $mes = (int)$matches[1];
                $ano = (int)$matches[2];
            }

            if (!$mes || !$ano) {
                error_log("⚠️ [REVERTER FATURA] Não foi possível extrair mês/ano do lançamento ID: {$lancamento->id}");
                return;
            }

            error_log("🔄 [REVERTER FATURA] Mês/Ano extraído: {$mes}/{$ano}");

            // Buscar itens pagos usando data_vencimento (mesmo critério usado ao pagar)
            // O pagamento busca por whereMonth/whereYear de data_vencimento
            $itensPagos = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
                ->where('user_id', $userId)
                ->whereYear('data_vencimento', $ano)
                ->whereMonth('data_vencimento', $mes)
                ->where('pago', true)
                ->get();

            if ($itensPagos->isEmpty()) {
                error_log("⚠️ [REVERTER FATURA] Nenhum item pago encontrado para cartão {$cartaoId}, vencimento {$mes}/{$ano}");
                return;
            }

            error_log("📋 [REVERTER FATURA] Encontrados {$itensPagos->count()} itens pagos para reverter");

            // Coletar IDs de faturas para atualizar depois
            $faturaIds = $itensPagos->pluck('fatura_id')->unique()->filter()->values();
            $itemIds = $itensPagos->pluck('id')->toArray();

            // Reverter os itens encontrados (usando IDs específicos para segurança)
            $itensRevertidos = FaturaCartaoItem::whereIn('id', $itemIds)
                ->update([
                    'pago' => false,
                    'data_pagamento' => null
                ]);

            error_log("📊 [REVERTER FATURA] {$itensRevertidos} itens revertidos para não pago");

            // Atualizar status de todas as faturas afetadas
            foreach ($faturaIds as $faturaId) {
                $fatura = Fatura::find($faturaId);
                if ($fatura) {
                    $fatura->atualizarStatus();
                    error_log("📊 [REVERTER FATURA] Fatura {$faturaId} atualizada para status: {$fatura->status}");
                }
            }

            // Recalcular limite do cartão
            $cartao = $lancamento->cartaoCredito;
            if ($cartao) {
                $cartao->atualizarLimiteDisponivel();
                error_log("💳 [REVERTER FATURA] Limite do cartão recalculado");
            }

            error_log("✅ [REVERTER FATURA] Reversão concluída com sucesso");
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::FATURA, [
                'action' => 'reverter_pagamento_fatura',
                'lancamento_id' => $lancamento->id,
                'cartao_id' => $cartaoId ?? null,
                'user_id' => $lancamento->user_id,
            ]);
        }
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

    /**
     * Cancela recorrência de um lançamento (todos os futuros não pagos).
     * POST /api/lancamentos/{id}/cancelar-recorrencia
     */
    public function cancelarRecorrencia(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $result = $this->creationService->cancelarRecorrencia($id, $userId);
        if ($result->isError()) {
            Response::error($result->message, $result->httpCode);
            return;
        }

        Response::success($result->data, $result->message);
    }

    /**
     * Marca um lançamento futuro como pago.
     * PUT /api/lancamentos/{id}/pagar
     */
    public function marcarPago(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        if ($lancamento->pago) {
            Response::error('Lançamento já está pago.', 422);
            return;
        }

        if ($lancamento->cancelado_em) {
            Response::error('Lançamento cancelado não pode ser marcado como pago.', 422);
            return;
        }

        $lancamento->pago = true;
        $lancamento->data_pagamento = date('Y-m-d');
        $lancamento->save();

        $lancamento->loadMissing(['categoria', 'conta']);

        Response::success(LancamentoResponseFormatter::format($lancamento), 'Lançamento marcado como pago.');
    }

    /**
     * Retorna os itens de uma fatura agrupados por categoria
     * GET /api/lancamentos/{id}/fatura-detalhes
     */
    public function faturaDetalhes(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        if ($lancamento->origem_tipo !== 'pagamento_fatura' || !$lancamento->cartao_credito_id) {
            Response::error('Este lançamento não é um pagamento de fatura.', 422);
            return;
        }

        // Extrair mês/ano da observação (ex: "14 item(s) pago(s) - Fatura 02/2026")
        $mes = null;
        $ano = null;
        if (preg_match('/Fatura (\d{1,2})\/(\d{4})/', $lancamento->observacao ?? '', $matches)) {
            $mes = (int)$matches[1];
            $ano = (int)$matches[2];
        }

        if (!$mes || !$ano) {
            Response::error('Não foi possível identificar a fatura.', 422);
            return;
        }

        // Buscar itens da fatura agrupados por categoria
        $itens = DB::table('fatura_cartao_itens as fi')
            ->leftJoin('categorias as c', 'c.id', '=', 'fi.categoria_id')
            ->where('fi.cartao_credito_id', $lancamento->cartao_credito_id)
            ->where('fi.user_id', $userId)
            ->whereYear('fi.data_vencimento', $ano)
            ->whereMonth('fi.data_vencimento', $mes)
            ->selectRaw('COALESCE(c.id, 0) as categoria_id')
            ->selectRaw("COALESCE(c.nome, 'Sem categoria') as categoria_nome")
            ->selectRaw("COALESCE(c.icone, '📦') as categoria_icone")
            ->selectRaw('SUM(fi.valor) as total')
            ->selectRaw('COUNT(*) as qtd_itens')
            ->groupBy('categoria_id', 'categoria_nome', 'categoria_icone')
            ->orderByDesc('total')
            ->get();

        $totalGeral = $itens->sum('total');

        $categorias = $itens->map(fn($row) => [
            'categoria_id'    => (int)$row->categoria_id,
            'categoria_nome'  => (string)$row->categoria_nome,
            'categoria_icone' => (string)$row->categoria_icone,
            'total'           => round((float)$row->total, 2),
            'qtd_itens'       => (int)$row->qtd_itens,
            'percentual'      => $totalGeral > 0 ? round(((float)$row->total / $totalGeral) * 100, 1) : 0,
        ])->values()->all();

        Response::success([
            'lancamento_id'    => $lancamento->id,
            'cartao_credito_id' => $lancamento->cartao_credito_id,
            'mes'              => $mes,
            'ano'              => $ano,
            'total'            => round($totalGeral, 2),
            'categorias'       => $categorias,
        ]);
    }
}