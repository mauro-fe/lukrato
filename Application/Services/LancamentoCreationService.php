<?php

namespace Application\Services;

use Application\DTO\ServiceResultDTO;
use Application\Enums\GamificationAction;
use Application\Enums\LogCategory;
use Application\Enums\Recorrencia;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Repositories\LancamentoRepository;
use Application\DTO\Requests\CreateLancamentoDTO;
use Application\Validators\LancamentoValidator;

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
     */
    public function createNormal(int $userId, CreateLancamentoDTO $dto, ?string $recorrencia, int $numeroRepeticoes, array $usage): ServiceResultDTO
    {
        $freq = Recorrencia::tryFromString($recorrencia);

        if ($freq !== null) {
            return $this->createRecurrent($userId, $dto, $freq, $numeroRepeticoes, $usage);
        }

        return $this->createSingle($userId, $dto, $usage);
    }

    // ─── Fluxos internos ───────────────────────────────────

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
}
