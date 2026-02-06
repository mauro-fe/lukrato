<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Models\FaturaCartaoItem;
use Application\Models\Fatura;
use Application\Services\LancamentoExportService;
use Application\Services\GamificationService;
use Application\Services\CartaoCreditoLancamentoService;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;
use Application\Services\LancamentoLimitService;
use Application\Services\UserPlanService;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Enums\LancamentoTipo;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\DTO\Requests\CreateLancamentoDTO;
use Application\DTO\Requests\UpdateLancamentoDTO;
use Application\Validators\LancamentoValidator;
use Application\Services\LogService;
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
    private CartaoCreditoLancamentoService $cartaoService;

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
        $this->cartaoService = new CartaoCreditoLancamentoService();
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
            l.pago, l.parcelamento_id, l.cartao_credito_id, l.forma_pagamento,
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

        // Verificar se é estorno de cartão ANTES de validar conta
        $formaPagamento = $payload['forma_pagamento'] ?? null;
        $formaPagamento = is_string($formaPagamento) && !empty($formaPagamento) ? $formaPagamento : null;
        $tipoLancamento = strtolower(trim($payload['tipo'] ?? ''));
        $cartaoCreditoId = $payload['cartao_credito_id'] ?? null;
        $cartaoCreditoId = is_scalar($cartaoCreditoId) ? (int)$cartaoCreditoId : null;

        $ehEstornoCartao = ($cartaoCreditoId && $cartaoCreditoId > 0 && $tipoLancamento === 'receita' && $formaPagamento === 'estorno_cartao');

        // Validar com o validator
        $errors = LancamentoValidator::validateCreate($payload);

        // Validar conta e categoria (regras de negócio)
        // NOTA: Conta NÃO é obrigatória para estorno de cartão
        $contaId = $payload['conta_id'] ?? $payload['contaId'] ?? null;
        $contaId = is_scalar($contaId) ? (int)$contaId : null;

        if (!$ehEstornoCartao) {
            // Só valida conta se NÃO for estorno de cartão
            $contaId = $this->validateConta($contaId, $userId, $errors);
        }

        $categoriaId = $payload['categoria_id'] ?? $payload['categoriaId'] ?? null;
        $categoriaId = is_scalar($categoriaId) ? (int)$categoriaId : null;
        $categoriaId = $this->validateCategoria($categoriaId, $userId, $errors);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // ============================================================
        // ESTORNO DE CARTÃO DE CRÉDITO (receita + estorno_cartao)
        // Processar ANTES de criar DTO para evitar criação de lançamento
        // ============================================================
        if ($ehEstornoCartao) {
            // Verificar limite antes de criar
            try {
                $usage = $this->limitService->assertCanCreate($userId, $payload['data']);
            } catch (\DomainException $e) {
                Response::error($e->getMessage(), 402);
                return;
            }

            // Extrair mês/ano da fatura se fornecido (formato: "2026-02")
            $faturaMesAno = $payload['fatura_mes_ano'] ?? null;
            $mesReferencia = null;
            $anoReferencia = null;

            if ($faturaMesAno && preg_match('/^(\d{4})-(\d{2})$/', $faturaMesAno, $matches)) {
                $anoReferencia = (int)$matches[1];
                $mesReferencia = (int)$matches[2];
            }

            // Usar serviço especializado para criar estorno na fatura
            $resultado = $this->cartaoService->criarEstornoCartao($userId, [
                'cartao_credito_id' => $cartaoCreditoId,
                'categoria_id' => $categoriaId,
                'valor' => LancamentoValidator::sanitizeValor($payload['valor']),
                'data' => $payload['data'],
                'descricao' => mb_substr(trim($payload['descricao'] ?? ''), 0, 190),
                'mes_referencia' => $mesReferencia,
                'ano_referencia' => $anoReferencia,
            ]);

            if (!$resultado['success']) {
                Response::error($resultado['message'], 422);
                return;
            }

            Response::success([
                'item' => [
                    'id' => $resultado['item']->id ?? null,
                    'descricao' => $resultado['item']->descricao ?? '',
                    'valor' => $resultado['item']->valor ?? 0,
                ],
                'tipo' => 'estorno_cartao',
                'usage' => $usage,
                'ui_message' => $this->planService->getUsageMessage($usage),
            ], $resultado['message'], 201);
            return;
        }

        // Verificar se é agendamento (não pago)
        $pago = !isset($payload['pago']) || (bool)$payload['pago'];
        if (isset($payload['agendado']) && $payload['agendado']) {
            $pago = false;
        }

        // Criar DTO
        $dto = CreateLancamentoDTO::fromRequest($userId, [
            'tipo' => $tipoLancamento,
            'data' => $payload['data'],
            'valor' => LancamentoValidator::sanitizeValor($payload['valor']),
            'descricao' => mb_substr(trim($payload['descricao'] ?? ''), 0, 190),
            'observacao' => mb_substr(trim($payload['observacao'] ?? ''), 0, 500),
            'categoria_id' => $categoriaId,
            'conta_id' => $contaId,
            'pago' => $pago,
            'forma_pagamento' => $formaPagamento,
        ]);

        // Verificar limite antes de criar
        try {
            $usage = $this->limitService->assertCanCreate($userId, $dto->data);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 402);
            return;
        }

        // ============================================================
        // COMPRA COM CARTÃO DE CRÉDITO (despesa)
        // ============================================================
        if ($cartaoCreditoId && $cartaoCreditoId > 0 && $dto->tipo === 'despesa') {
            // Usar serviço especializado para cartão de crédito
            // IMPORTANTE: Agora cria FaturaCartaoItem, não Lancamento direto
            $resultado = $this->cartaoService->criarLancamentoCartao($userId, [
                'cartao_credito_id' => $cartaoCreditoId,
                'categoria_id' => $categoriaId,
                'valor' => $dto->valor,
                'data' => $dto->data,
                'descricao' => $dto->descricao,
                'observacao' => $dto->observacao,
                'eh_parcelado' => (bool)($payload['eh_parcelado'] ?? false),
                'total_parcelas' => (int)($payload['total_parcelas'] ?? 1),
            ]);

            if (!$resultado['success']) {
                Response::error($resultado['message'], 422);
                return;
            }

            // Para gamificação, criar um objeto compatível a partir do primeiro item
            $primeiroItem = $resultado['itens'][0] ?? null;
            $lancamentoFake = null;

            if ($primeiroItem) {
                // Criar objeto stdClass compatível com gamificação
                $lancamentoFake = (object)[
                    'id' => $primeiroItem->id,
                    'categoria' => $primeiroItem->categoria,
                ];
            }

            // Gamificação
            $gamificationResult = [];
            if ($lancamentoFake) {
                try {
                    $pointsResult = $this->gamificationService->addPoints(
                        $userId,
                        \Application\Enums\GamificationAction::CREATE_LANCAMENTO,
                        $lancamentoFake->id,
                        'lancamento'
                    );
                    $streakResult = $this->gamificationService->updateStreak($userId);
                    $gamificationResult = [
                        'points' => $pointsResult,
                        'streak' => $streakResult,
                    ];
                } catch (\Exception $e) {
                    error_log("🎮 [GAMIFICATION] Erro: " . $e->getMessage());
                }
            }

            Response::success([
                'item' => [
                    'id' => $primeiroItem->id ?? null,
                    'descricao' => $primeiroItem->descricao ?? '',
                    'valor' => $primeiroItem->valor ?? 0,
                    'data_vencimento' => $primeiroItem->data_vencimento ?? null,
                ],
                'total_itens_criados' => $resultado['total_criados'],
                'eh_parcelado' => $resultado['total_criados'] > 1,
                'usage' => $usage,
                'ui_message' => $this->planService->getUsageMessage($usage),
                'gamification' => $gamificationResult,
            ], $resultado['message'], 201);
            return;
        }

        // ============================================================
        // LANÇAMENTO NORMAL (SEM CARTÃO)
        // ============================================================

        // Verificar se é lançamento recorrente
        $recorrencia = $payload['recorrencia'] ?? null;
        $numeroRepeticoes = isset($payload['numero_repeticoes']) ? (int)$payload['numero_repeticoes'] : 12;

        $lancamentosCriados = [];

        if ($recorrencia && in_array($recorrencia, ['semanal', 'quinzenal', 'mensal', 'bimestral', 'trimestral', 'semestral', 'anual'])) {
            // Calcular intervalo de dias
            $intervalos = [
                'semanal' => 7,
                'quinzenal' => 14,
                'mensal' => 'P1M',
                'bimestral' => 'P2M',
                'trimestral' => 'P3M',
                'semestral' => 'P6M',
                'anual' => 'P1Y',
            ];

            $dataBase = new \DateTime($dto->data);

            for ($i = 0; $i < $numeroRepeticoes; $i++) {
                if ($i > 0) {
                    $intervalo = $intervalos[$recorrencia];
                    if (is_int($intervalo)) {
                        $dataBase->modify("+{$intervalo} days");
                    } else {
                        $dataBase->add(new \DateInterval($intervalo));
                    }
                }

                $dadosLancamento = $dto->toArray();
                $dadosLancamento['data'] = $dataBase->format('Y-m-d');
                $dadosLancamento['descricao'] = $dto->descricao . ($numeroRepeticoes > 1 ? " (" . ($i + 1) . "/{$numeroRepeticoes})" : '');

                $lancamento = $this->lancamentoRepo->create($dadosLancamento);
                $lancamentosCriados[] = $lancamento;
            }

            // Carregar relações do primeiro lançamento para resposta
            $lancamentosCriados[0]->loadMissing(['categoria', 'conta']);

            Response::success([
                'lancamento' => LancamentoResponseFormatter::format($lancamentosCriados[0]),
                'total_criados' => count($lancamentosCriados),
                'recorrencia' => $recorrencia,
                'usage' => $usage,
                'ui_message' => $this->planService->getUsageMessage($usage),
            ], count($lancamentosCriados) . ' lançamentos agendados com sucesso', 201);
            return;
        }

        // Criar lançamento único
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

            // Verificar e desbloquear conquistas automaticamente
            $achievementService = new \Application\Services\AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($userId, 'lancamento_created');

            $gamificationResult = [
                'points' => $pointsResult,
                'streak' => $streakResult,
            ];

            if (!empty($newAchievements)) {
                $gamificationResult['achievements'] = $newAchievements;
            }
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
            error_log("❌ [REVERTER FATURA] Erro: " . $e->getMessage());
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
}
