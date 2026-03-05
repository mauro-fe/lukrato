<?php

namespace Application\Services\Lancamento;

use Application\DTO\ServiceResultDTO;
use Application\Enums\GamificationAction;
use Application\Enums\LogCategory;
use Application\Enums\Recorrencia;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Repositories\LancamentoRepository;
use Application\DTO\Requests\CreateLancamentoDTO;
use Application\Validators\LancamentoValidator;
use Application\Services\Cartao\CartaoCreditoLancamentoService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Plan\UserPlanService;
use Application\Services\Infrastructure\LogService;
use Illuminate\Database\Capsule\Manager as DB;

class LancamentoCreationService
{
    private CartaoCreditoLancamentoService $cartaoService;
    private LancamentoRepository $lancamentoRepo;
    private GamificationService $gamificationService;
    private LancamentoLimitService $limitService;
    private UserPlanService $planService;

    public function __construct(
        ?CartaoCreditoLancamentoService $cartaoService = null,
        ?LancamentoRepository $lancamentoRepo = null,
        ?GamificationService $gamificationService = null,
        ?LancamentoLimitService $limitService = null,
        ?UserPlanService $planService = null
    ) {
        $this->cartaoService = $cartaoService ?? new CartaoCreditoLancamentoService();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->gamificationService = $gamificationService ?? new GamificationService();
        $this->limitService = $limitService ?? new LancamentoLimitService();
        $this->planService = $planService ?? new UserPlanService();
    }

    /**
     * Cria um lançamento a partir do payload bruto do request.
     *
     * Encapsula toda a orquestração: sanitização, validação, detecção de fluxo
     * (estorno, cartão, normal/recorrente) e delegação ao método específico.
     *
     * @param int $userId
     * @param array $payload Dados brutos vindos do request
     * @return ServiceResultDTO Sucesso, erro de validação (422) ou erro de domínio
     */
    public function createFromPayload(int $userId, array $payload): ServiceResultDTO
    {
        // 1. Sanitizar inputs
        $formaPagamento  = $payload['forma_pagamento'] ?? null;
        $formaPagamento  = is_string($formaPagamento) && !empty($formaPagamento) ? $formaPagamento : null;
        $tipoLancamento  = strtolower(trim($payload['tipo'] ?? ''));
        $cartaoCreditoId = $payload['cartao_credito_id'] ?? null;
        $cartaoCreditoId = is_scalar($cartaoCreditoId) ? (int) $cartaoCreditoId : null;

        $ehEstornoCartao = ($cartaoCreditoId && $cartaoCreditoId > 0
            && $tipoLancamento === 'receita'
            && $formaPagamento === 'estorno_cartao');

        // 2. Validar
        $errors = LancamentoValidator::validateCreate($payload);

        $contaId = $payload['conta_id'] ?? $payload['contaId'] ?? null;
        $contaId = is_scalar($contaId) ? (int) $contaId : null;
        if (!$ehEstornoCartao) {
            $contaId = LancamentoValidator::validateContaOwnership($contaId, $userId, $errors);
        }

        $categoriaId = $payload['categoria_id'] ?? $payload['categoriaId'] ?? null;
        $categoriaId = is_scalar($categoriaId) ? (int) $categoriaId : null;
        $categoriaId = LancamentoValidator::validateCategoriaOwnership($categoriaId, $userId, $errors);

        // Validar subcategoria (opcional)
        $subcategoriaId = $payload['subcategoria_id'] ?? $payload['subcategoriaId'] ?? null;
        $subcategoriaId = is_scalar($subcategoriaId) && !empty($subcategoriaId) ? (int) $subcategoriaId : null;
        if ($subcategoriaId && $categoriaId) {
            $subcategoriaId = LancamentoValidator::validateSubcategoriaOwnership($subcategoriaId, $categoriaId, $userId, $errors);
        }

        if (!empty($errors)) {
            return ServiceResultDTO::validationFail($errors);
        }

        // 3. Estorno de cartão
        if ($ehEstornoCartao) {
            return $this->createEstorno($userId, $payload, $cartaoCreditoId, $categoriaId);
        }

        // 4. Construir DTO
        $pago = !isset($payload['pago']) || (bool) $payload['pago'];
        if (isset($payload['agendado']) && $payload['agendado']) {
            $pago = false;
        }
        if (!empty($payload['recorrente'])) {
            // Recorrências sempre nascem pendentes; o usuário decide o pagamento depois.
            $pago = false;
        }

        // Lançamento já pago não deve manter lembrete ativo.
        $lembreteAntes = $pago ? null : ($payload['lembrar_antes_segundos'] ?? null);
        $canalEmail = $pago ? false : (bool) ($payload['canal_email'] ?? false);
        $canalInapp = $pago ? false : (bool) ($payload['canal_inapp'] ?? false);

        $dto = CreateLancamentoDTO::fromRequest($userId, [
            'tipo'                   => $tipoLancamento,
            'data'                   => $payload['data'],
            'hora_lancamento'        => $payload['hora_lancamento'] ?? null,
            'valor'                  => LancamentoValidator::sanitizeValor($payload['valor']),
            'descricao'              => mb_substr(trim($payload['descricao'] ?? ''), 0, 190),
            'observacao'             => mb_substr(trim($payload['observacao'] ?? ''), 0, 500),
            'categoria_id'           => $categoriaId,
            'subcategoria_id'        => $subcategoriaId,
            'conta_id'               => $contaId,
            'pago'                   => $pago,
            'forma_pagamento'        => $formaPagamento,
            'recorrente'             => (bool) ($payload['recorrente'] ?? false),
            'recorrencia_freq'       => $payload['recorrencia_freq'] ?? null,
            'recorrencia_fim'        => $payload['recorrencia_fim'] ?? null,
            'recorrencia_total'      => isset($payload['recorrencia_total']) ? (int) $payload['recorrencia_total'] : null,
            'lembrar_antes_segundos' => $lembreteAntes,
            'canal_email'            => $canalEmail,
            'canal_inapp'            => $canalInapp,
        ]);

        try {
            $usage = $this->limitService->assertCanCreate($userId, $dto->data);
        } catch (\DomainException $e) {
            return ServiceResultDTO::fail($e->getMessage(), 402);
        }

        // 5. Compra com cartão de crédito
        if ($cartaoCreditoId && $cartaoCreditoId > 0 && $dto->tipo === 'despesa') {
            return $this->createCartaoExpense($userId, $dto, $payload, $cartaoCreditoId, $categoriaId, $usage);
        }

        // 6. Lançamento normal (com ou sem recorrência)
        $recorrencia      = $payload['recorrencia'] ?? null;
        $numeroRepeticoes = isset($payload['numero_repeticoes']) ? (int) $payload['numero_repeticoes'] : 12;

        return $this->createNormal($userId, $dto, $recorrencia, $numeroRepeticoes, $usage);
    }

    /**
     * Processa estorno de cartão de crédito
     */
    public function createEstorno(int $userId, array $payload, int $cartaoCreditoId, int $categoriaId): ServiceResultDTO
    {
        $usage = $this->limitService->assertCanCreate($userId, $payload['data']);

        $faturaMesAno = $payload['fatura_mes_ano'] ?? null;
        $mesReferencia = null;
        $anoReferencia = null;

        if ($faturaMesAno && preg_match('/^(\d{4})-(\d{2})$/', $faturaMesAno, $matches)) {
            $anoReferencia = (int)$matches[1];
            $mesReferencia = (int)$matches[2];
        }

        $resultado = $this->cartaoService->criarEstornoCartao($userId, [
            'cartao_credito_id' => $cartaoCreditoId,
            'categoria_id'      => $categoriaId,
            'valor'             => LancamentoValidator::sanitizeValor($payload['valor']),
            'data'              => $payload['data'],
            'descricao'         => mb_substr(trim($payload['descricao'] ?? ''), 0, 190),
            'mes_referencia'    => $mesReferencia,
            'ano_referencia'    => $anoReferencia,
        ]);

        if (!$resultado['success']) {
            return ServiceResultDTO::fail($resultado['message']);
        }

        return ServiceResultDTO::ok($resultado['message'], [
            'item' => [
                'id'        => $resultado['item']->id ?? null,
                'descricao' => $resultado['item']->descricao ?? '',
                'valor'     => $resultado['item']->valor ?? 0,
            ],
            'tipo'       => 'estorno_cartao',
            'usage'      => $usage,
            'ui_message' => $this->planService->getUsageMessage($usage),
        ]);
    }

    /**
     * Processa compra com cartão de crédito (despesa)
     */
    public function createCartaoExpense(int $userId, CreateLancamentoDTO $dto, array $payload, int $cartaoCreditoId, int $categoriaId, array $usage): ServiceResultDTO
    {
        $resultado = $this->cartaoService->criarLancamentoCartao($userId, [
            'cartao_credito_id' => $cartaoCreditoId,
            'categoria_id'      => $categoriaId,
            'valor'             => $dto->valor,
            'data'              => $dto->data,
            'descricao'         => $dto->descricao,
            'observacao'        => $dto->observacao,
            'eh_parcelado'      => (bool)($payload['eh_parcelado'] ?? false),
            'total_parcelas'    => (int)($payload['total_parcelas'] ?? 1),
            // Campos de recorrência (assinatura no cartão)
            'recorrente'        => (bool)($payload['recorrente'] ?? false),
            'recorrencia_freq'  => $payload['recorrencia_freq'] ?? null,
            'recorrencia_fim'   => $payload['recorrencia_fim'] ?? null,
        ]);

        if (!$resultado['success']) {
            return ServiceResultDTO::fail($resultado['message']);
        }

        $primeiroItem = $resultado['itens'][0] ?? null;
        $gamification = $primeiroItem
            ? $this->triggerGamification($userId, $primeiroItem->id)
            : [];

        return ServiceResultDTO::ok($resultado['message'], [
            'item' => [
                'id'              => $primeiroItem->id ?? null,
                'descricao'       => $primeiroItem->descricao ?? '',
                'valor'           => $primeiroItem->valor ?? 0,
                'data_vencimento' => $primeiroItem->data_vencimento ?? null,
            ],
            'total_itens_criados' => $resultado['total_criados'],
            'eh_parcelado'        => $resultado['total_criados'] > 1,
            'usage'               => $usage,
            'ui_message'          => $this->planService->getUsageMessage($usage),
            'gamification'        => $gamification,
        ]);
    }

    /**
     * Processa lançamento normal (com ou sem recorrência)
     *
     * Se o DTO indicar recorrente=true, usa a nova lógica de recorrência (infinita ou com fim).
     * Caso contrário, mantém compatibilidade com o fluxo antigo de repetições finitas.
     */
    public function createNormal(int $userId, CreateLancamentoDTO $dto, ?string $recorrencia, int $numeroRepeticoes, array $usage): ServiceResultDTO
    {
        // Nova lógica: recorrência infinita / com data fim (vindo do DTO)
        if ($dto->recorrente && $dto->recorrenciaFreq) {
            $freq = Recorrencia::tryFromString($dto->recorrenciaFreq);
            if ($freq === null) {
                return ServiceResultDTO::fail('Frequência de recorrência inválida.');
            }
            return $this->createRecurring($userId, $dto, $freq, $usage);
        }

        // Fluxo legado: N repetições finitas (parcelamento)
        $freq = Recorrencia::tryFromString($recorrencia);
        if ($freq !== null && $numeroRepeticoes > 1) {
            LogService::warning('[RECORRENCIA] Fluxo legado createRecurrent atingido — considerar migração para createRecurring', [
                'user_id' => $userId,
                'recorrencia' => $recorrencia,
                'numero_repeticoes' => $numeroRepeticoes,
            ]);
            return $this->createRecurrent($userId, $dto, $freq, $numeroRepeticoes, $usage);
        }

        return $this->createSingle($userId, $dto, $usage);
    }

    // ─── Fluxos internos ───────────────────────────────────

    /**
     * Nova recorrência: cria apenas o lançamento-pai na data escolhida.
     *
     * As próximas ocorrências são geradas pelo cron quando o próximo ciclo vencer.
     */
    private function createRecurring(int $userId, CreateLancamentoDTO $dto, Recorrencia $freq, array $usage): ServiceResultDTO
    {
        // 1) Criar o lançamento-pai (primeira ocorrência) sempre como pendente
        $dadosPai = $dto->toArray();
        $dadosPai['recorrente'] = 1;
        $dadosPai['recorrencia_freq'] = $freq->value;
        $dadosPai['recorrencia_fim'] = $dto->recorrenciaFim;
        $dadosPai['recorrencia_total'] = $dto->recorrenciaTotal;
        $dadosPai['origem_tipo'] = \Application\Models\Lancamento::ORIGEM_RECORRENCIA;
        $dadosPai['pago'] = 0;
        $dadosPai['afeta_caixa'] = 0;
        $dadosPai['data_pagamento'] = null;

        $pai = $this->lancamentoRepo->create($dadosPai);

        // Atualizar recorrencia_pai_id para apontar para si mesmo (é a raiz)
        $pai->recorrencia_pai_id = $pai->id;
        $pai->save();

        $totalCriados = 1;
        $pai->loadMissing(['categoria', 'conta']);

        $gamification = $this->triggerGamification($userId, $pai->id);

        $infinita = $dto->recorrenciaFim === null && $dto->recorrenciaTotal === null;

        return ServiceResultDTO::ok(
            'Recorrência criada: 1 lançamento pendente. Os próximos serão gerados automaticamente no próximo ciclo.',
            [
                'lancamento'    => LancamentoResponseFormatter::format($pai),
                'total_criados' => $totalCriados,
                'recorrencia'   => $freq->value,
                'infinita'      => $infinita,
                'usage'         => $usage,
                'ui_message'    => $this->planService->getUsageMessage($usage),
                'gamification'  => $gamification,
            ]
        );
    }

    /**
     * Fluxo legado: N repetições finitas (parcelamento)
     */
    private function createRecurrent(int $userId, CreateLancamentoDTO $dto, Recorrencia $freq, int $numeroRepeticoes, array $usage): ServiceResultDTO
    {
        $dataBase = new \DateTime($dto->data);
        $lancamentos = [];

        for ($i = 0; $i < $numeroRepeticoes; $i++) {
            if ($i > 0) {
                $freq->advance($dataBase);
            }

            $dados = $dto->toArray();
            $dados['data'] = $dataBase->format('Y-m-d');
            $dados['descricao'] = $dto->descricao . ($numeroRepeticoes > 1 ? " (" . ($i + 1) . "/{$numeroRepeticoes})" : '');

            $lancamentos[] = $this->lancamentoRepo->create($dados);
        }

        $lancamentos[0]->loadMissing(['categoria', 'conta']);

        return ServiceResultDTO::ok(
            count($lancamentos) . ' lançamentos agendados com sucesso',
            [
                'lancamento'    => LancamentoResponseFormatter::format($lancamentos[0]),
                'total_criados' => count($lancamentos),
                'recorrencia'   => $freq->value,
                'usage'         => $usage,
                'ui_message'    => $this->planService->getUsageMessage($usage),
            ]
        );
    }

    private function createSingle(int $userId, CreateLancamentoDTO $dto, array $usage): ServiceResultDTO
    {
        $lancamento = $this->lancamentoRepo->create($dto->toArray());
        $lancamento->loadMissing(['categoria', 'conta']);

        $gamification = $this->triggerGamification($userId, $lancamento->id);

        // Verificar conquistas
        try {
            $achievementService = new AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($userId, 'lancamento_created');
            if (!empty($newAchievements)) {
                $gamification['achievements'] = $newAchievements;
            }
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::GAMIFICATION, [
                'action' => 'check_achievements',
                'user_id' => $userId,
            ]);
        }

        return ServiceResultDTO::ok('Lancamento criado', [
            'lancamento'   => LancamentoResponseFormatter::format($lancamento),
            'usage'        => $usage,
            'ui_message'   => $this->planService->getUsageMessage($usage),
            'gamification' => $gamification,
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────

    public function triggerGamification(int $userId, int $entityId): array
    {
        try {
            $points = $this->gamificationService->addPoints(
                $userId,
                GamificationAction::CREATE_LANCAMENTO,
                $entityId,
                'lancamento'
            );
            $streak = $this->gamificationService->updateStreak($userId);

            return ['points' => $points, 'streak' => $streak];
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::GAMIFICATION, [
                'action' => 'trigger_gamification',
                'user_id' => $userId,
                'entity_id' => $entityId,
            ]);
            return [];
        }
    }

    // ─── Recorrência: cancelar e estender ──────────────────

    /**
     * Cancela uma recorrência: marca todos os lançamentos futuros não pagos como cancelados.
     *
     * @param int $lancamentoId  Qualquer lançamento da série (pai ou filho)
     * @param int $userId        Para segurança
     * @return ServiceResultDTO
     */
    public function cancelarRecorrencia(int $lancamentoId, int $userId): ServiceResultDTO
    {
        $lancamento = \Application\Models\Lancamento::where('id', $lancamentoId)
            ->where('user_id', $userId)
            ->first();

        if (!$lancamento) {
            return ServiceResultDTO::fail('Lançamento não encontrado.');
        }

        if (!$lancamento->recorrente) {
            return ServiceResultDTO::fail('Este lançamento não faz parte de uma recorrência.');
        }

        $paiId = $lancamento->recorrencia_pai_id ?? $lancamento->id;
        $agora = date('Y-m-d H:i:s');

        // 1) Sempre marcar o pai como cancelado (mesmo se já pago)
        //    para impedir que o cron regenere filhos futuros
        \Application\Models\Lancamento::where('id', $paiId)
            ->where('user_id', $userId)
            ->whereNull('cancelado_em')
            ->update(['cancelado_em' => $agora]);

        // 2) Cancelar todos os filhos não pagos da série
        $afetados = \Application\Models\Lancamento::where('recorrencia_pai_id', $paiId)
            ->where('id', '!=', $paiId)
            ->where('user_id', $userId)
            ->where('pago', 0)
            ->whereNull('cancelado_em')
            ->update(['cancelado_em' => $agora]);

        // Contar o pai se ele foi cancelado agora (era não-pago)
        $paiCancelado = \Application\Models\Lancamento::where('id', $paiId)
            ->where('cancelado_em', $agora)
            ->where('pago', 0)
            ->exists();

        $totalAfetados = $afetados + ($paiCancelado ? 1 : 0);

        return ServiceResultDTO::ok("{$totalAfetados} lançamentos futuros cancelados.", [
            'cancelados' => $totalAfetados,
            'recorrencia_pai_id' => $paiId,
        ]);
    }

    /**
     * Gera as próximas ocorrências vencidas de recorrências ativas.
     * Chamado pelo cron `generate_recurring_lancamentos.php`.
     *
     * @param int $horizonMonths Mantido por compatibilidade (não utilizado)
     * @return int Número de lançamentos criados
     */
    public function estenderRecorrenciasInfinitas(int $horizonMonths = 3): int
    {
        $hoje = new \DateTimeImmutable('today');
        $totalCriados = 0;

        // Buscar todos os pais de recorrências ativas
        $pais = \Application\Models\Lancamento::where('recorrente', 1)
            ->whereNull('cancelado_em')
            ->whereColumn('recorrencia_pai_id', 'id') // é o pai (aponta para si)
            ->get();

        foreach ($pais as $pai) {
            $freq = Recorrencia::tryFromString($pai->recorrencia_freq);
            if (!$freq) continue;

            try {
                DB::beginTransaction();

                // Lock no pai para prevenir race condition entre execuções concorrentes
                $paiLocked = \Application\Models\Lancamento::where('id', $pai->id)
                    ->whereNull('cancelado_em')
                    ->lockForUpdate()
                    ->first();

                if (!$paiLocked) {
                    DB::rollBack();
                    continue;
                }

                // Encontrar a data do último filho gerado
                $ultimoFilho = \Application\Models\Lancamento::where('recorrencia_pai_id', $pai->id)
                    ->whereNull('cancelado_em')
                    ->orderBy('data', 'desc')
                    ->first();

                $ultimaData = $ultimoFilho
                    ? new \DateTime($ultimoFilho->data)
                    : new \DateTime($pai->data);

                // Avançar para o próximo ciclo com base no último lançamento da série.
                $dataProx = clone $ultimaData;
                $freq->advance($dataProx);

                // Ainda não chegou a data do próximo ciclo.
                if ($dataProx > $hoje) {
                    DB::commit();
                    continue;
                }

                // Recorrência por data fim: não gera além do fim.
                if ($pai->recorrencia_fim !== null) {
                    $fim = new \DateTimeImmutable($pai->recorrencia_fim);
                    if ($dataProx > $fim) {
                        DB::commit();
                        continue;
                    }
                }

                // Recorrência por quantidade: respeita o total definido.
                if ($pai->recorrencia_total !== null && $pai->recorrencia_total > 0) {
                    $totalSerie = \Application\Models\Lancamento::where('recorrencia_pai_id', $pai->id)
                        ->whereNull('cancelado_em')
                        ->count();

                    if ($totalSerie >= (int) $pai->recorrencia_total) {
                        DB::commit();
                        continue;
                    }
                }

                // Verificação de duplicata (idempotência)
                $jaExiste = \Application\Models\Lancamento::where('recorrencia_pai_id', $pai->id)
                    ->where('data', $dataProx->format('Y-m-d'))
                    ->whereNull('cancelado_em')
                    ->exists();

                if (!$jaExiste) {
                    $dados = [
                        'user_id'            => $pai->user_id,
                        'tipo'               => $pai->tipo,
                        'data'               => $dataProx->format('Y-m-d'),
                        'hora_lancamento'    => $pai->hora_lancamento,
                        'valor'              => $pai->valor,
                        'descricao'          => $pai->descricao,
                        'observacao'         => $pai->observacao,
                        'categoria_id'       => $pai->categoria_id,
                        'subcategoria_id'    => $pai->subcategoria_id,
                        'conta_id'           => $pai->conta_id,
                        'pago'               => 0,
                        'afeta_caixa'        => 0,
                        'data_pagamento'     => null,
                        'forma_pagamento'    => $pai->forma_pagamento,
                        'recorrente'         => 1,
                        'recorrencia_freq'   => $pai->recorrencia_freq,
                        'recorrencia_fim'    => $pai->recorrencia_fim,
                        'recorrencia_total'  => $pai->recorrencia_total,
                        'recorrencia_pai_id' => $pai->id,
                        'origem_tipo'        => \Application\Models\Lancamento::ORIGEM_RECORRENCIA,
                        'lembrar_antes_segundos' => $pai->lembrar_antes_segundos,
                        'canal_email'        => $pai->canal_email,
                        'canal_inapp'        => $pai->canal_inapp,
                    ];

                    try {
                        $this->lancamentoRepo->create($dados);
                        $totalCriados++;
                    } catch (\Throwable $e) {
                        // Com unique key ativa, corrida concorrente pode bater aqui.
                        // Se for duplicado, tratamos como idempotente.
                        if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                            LogService::info('[RECORRENCIA] Duplicata evitada por unique key', [
                                'pai_id' => $pai->id,
                                'data' => $dataProx->format('Y-m-d'),
                            ]);
                        } else {
                            throw $e;
                        }
                    }
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                LogService::captureException($e, LogCategory::LANCAMENTO, [
                    'action' => 'estender_recorrencia',
                    'pai_id' => $pai->id,
                ]);
            }
        }

        return $totalCriados;
    }
}
