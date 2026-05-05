<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Container\ApplicationContainer;
use Application\DTO\CreateCartaoCreditoDTO;
use Application\DTO\UpdateCartaoCreditoDTO;
use Application\Enums\LogCategory;
use Application\Services\Gamification\AchievementService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Plan\PlanLimitService;

/**
 * @phpstan-type WorkflowPayload array<array-key, mixed>
 */
class CartaoApiWorkflowService
{
    private readonly CartaoCreditoService $cartaoService;
    private readonly CartaoFaturaService $faturaService;
    private readonly PlanLimitService $planLimitService;
    private readonly AchievementService $achievementService;
    private readonly RecorrenciaCartaoService $recorrenciaService;

    public function __construct(
        ?CartaoCreditoService $cartaoService = null,
        ?CartaoFaturaService $faturaService = null,
        ?PlanLimitService $planLimitService = null,
        ?AchievementService $achievementService = null,
        ?RecorrenciaCartaoService $recorrenciaService = null
    ) {
        $this->cartaoService = ApplicationContainer::resolveOrNew($cartaoService, CartaoCreditoService::class);
        $this->faturaService = ApplicationContainer::resolveOrNew($faturaService, CartaoFaturaService::class);
        $this->planLimitService = ApplicationContainer::resolveOrNew($planLimitService, PlanLimitService::class);
        $this->achievementService = ApplicationContainer::resolveOrNew($achievementService, AchievementService::class);
        $this->recorrenciaService = ApplicationContainer::resolveOrNew($recorrenciaService, RecorrenciaCartaoService::class);
    }

    /**
     * @return WorkflowPayload
     */
    public function listCards(int $userId, ?int $contaId, bool $onlyActive, bool $archived): array
    {
        if ($archived) {
            return $this->cartaoService->listarCartoesArquivados($userId);
        }

        return $this->cartaoService->listarCartoes($userId, $contaId, $onlyActive);
    }

    public function showCard(int $cardId, int $userId): mixed
    {
        return $this->cartaoService->buscarCartao($cardId, $userId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createCard(int $userId, array $payload): array
    {
        $limitCheck = $this->planLimitService->canCreateCartao($userId);

        if (!$limitCheck['allowed']) {
            return [
                'success' => false,
                'status' => 403,
                'message' => $limitCheck['message'],
                'errors' => [
                    'limit_reached' => true,
                    'upgrade_url' => $limitCheck['upgrade_url'],
                    'limit_info' => [
                        'limit' => $limitCheck['limit'],
                        'used' => $limitCheck['used'],
                        'remaining' => $limitCheck['remaining'],
                    ],
                ],
            ];
        }

        $dto = CreateCartaoCreditoDTO::fromArray($payload, $userId);
        $result = $this->cartaoService->criarCartao($dto);

        if (!$result['success']) {
            return [
                'success' => false,
                'status' => 422,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null,
            ];
        }

        return [
            'success' => true,
            'status' => 201,
            'message' => 'Success',
            'data' => [
                'id' => $result['id'],
                'data' => $result['data'],
                'gamification' => $this->checkAchievements($userId, 'card_created', [
                    'action' => 'check_achievements_card_created',
                    'user_id' => $userId,
                ]),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateCard(int $cardId, int $userId, array $payload): array
    {
        $dto = UpdateCartaoCreditoDTO::fromArray($payload);
        $result = $this->cartaoService->atualizarCartao($cardId, $userId, $dto);

        if (!$result['success']) {
            return [
                'success' => false,
                'status' => $this->resolveUpdateFailureStatus($result['message'] ?? null),
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'data' => $result['data'],
            ],
        ];
    }

    /**
     * @return WorkflowPayload
     */
    public function deactivateCard(int $cardId, int $userId): array
    {
        return $this->cartaoService->desativarCartao($cardId, $userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function reactivateCard(int $cardId, int $userId): array
    {
        return $this->cartaoService->reativarCartao($cardId, $userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function archiveCard(int $cardId, int $userId): array
    {
        return $this->cartaoService->arquivarCartao($cardId, $userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function restoreCard(int $cardId, int $userId): array
    {
        return $this->cartaoService->restaurarCartao($cardId, $userId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function deleteCard(int $cardId, int $userId, array $payload): array
    {
        $force = filter_var($payload['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $this->cartaoService->excluirCartaoPermanente($cardId, $userId, $force);
    }

    /**
     * @return WorkflowPayload
     */
    public function refreshLimit(int $cardId, int $userId): array
    {
        return $this->cartaoService->atualizarLimiteDisponivel($cardId, $userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function getSummary(int $userId): array
    {
        return $this->cartaoService->obterResumo($userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function getInvoice(int $cardId, int $month, int $year, int $userId): array
    {
        return $this->faturaService->obterFaturaMes($cardId, $month, $year, $userId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return WorkflowPayload
     */
    public function payInvoice(int $cardId, int $userId, array $payload): array
    {
        $month = isset($payload['mes']) ? (int) $payload['mes'] : (int) date('n');
        $year = isset($payload['ano']) ? (int) $payload['ano'] : (int) date('Y');
        $contaId = isset($payload['conta_id']) ? (int) $payload['conta_id'] : null;
        $partialValue = isset($payload['valor_parcial']) ? (float) $payload['valor_parcial'] : null;

        $result = $this->faturaService->pagarFatura($cardId, $month, $year, $userId, $contaId, $partialValue);
        $gamification = $this->checkAchievements($userId, 'invoice_paid', [
            'action' => 'check_achievements_invoice_paid',
            'user_id' => $userId,
            'cartao_id' => $cardId,
        ]);

        if (!empty($gamification)) {
            $result['gamification'] = $gamification;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @return WorkflowPayload
     */
    public function payInstallments(int $cardId, int $userId, array $payload): array
    {
        $installmentIds = $payload['parcela_ids'] ?? [];
        $month = isset($payload['mes']) ? (int) $payload['mes'] : (int) date('n');
        $year = isset($payload['ano']) ? (int) $payload['ano'] : (int) date('Y');

        return $this->faturaService->pagarParcelas($cardId, $installmentIds, $month, $year, $userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function getPendingInvoices(int $cardId, int $userId): array
    {
        return $this->faturaService->obterMesesComFaturasPendentes($cardId, $userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function getInvoiceHistory(int $cardId, int $userId, int $limit): array
    {
        return $this->faturaService->obterHistoricoFaturasPagas($cardId, $userId, $limit);
    }

    /**
     * @return WorkflowPayload
     */
    public function getInstallmentsSummary(int $cardId, int $month, int $year, int $userId): array
    {
        try {
            return $this->faturaService->obterResumoParcelamentos($cardId, $month, $year, $userId);
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'parcelamentos_resumo',
                'cartao_id' => $cardId,
                'mes' => $month,
                'ano' => $year,
            ]);

            return [
                'total_parcelamentos' => 0,
                'parcelamentos' => [],
                'projecao' => [
                    'tres_meses' => 0.0,
                    'seis_meses' => 0.0,
                ],
            ];
        }
    }

    /**
     * @return WorkflowPayload
     */
    public function getAlerts(int $userId): array
    {
        $dueDates = [];
        $lowLimits = [];

        try {
            $dueDates = $this->faturaService->verificarVencimentosProximos($userId, 7);
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'verificar_vencimentos',
                'user_id' => $userId,
            ]);
        }

        try {
            $lowLimits = $this->cartaoService->verificarLimitesBaixos($userId);
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'verificar_limites_baixos',
                'user_id' => $userId,
            ]);
        }

        $alerts = array_merge($dueDates, $lowLimits);

        usort($alerts, static function (array $left, array $right): int {
            $severityOrder = ['critico' => 0, 'atencao' => 1];

            return ($severityOrder[$left['gravidade']] ?? 2) <=> ($severityOrder[$right['gravidade']] ?? 2);
        });

        return [
            'total' => count($alerts),
            'alertas' => $alerts,
            'por_tipo' => [
                'vencimentos' => count($dueDates),
                'limites_baixos' => count($lowLimits),
            ],
        ];
    }

    /**
     * @return WorkflowPayload
     */
    public function validateIntegrity(int $userId, bool $fix): array
    {
        return $this->cartaoService->validarIntegridadeLimites($userId, $fix);
    }

    /**
     * @return WorkflowPayload|null
     */
    public function getInvoiceStatus(int $cardId, int $month, int $year, int $userId): ?array
    {
        return $this->faturaService->faturaEstaPaga($cardId, $month, $year, $userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function undoInvoicePayment(int $cardId, int $month, int $year, int $userId): array
    {
        return $this->faturaService->desfazerPagamentoFatura($cardId, $month, $year, $userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function undoInstallmentPayment(int $installmentId, int $userId): array
    {
        return $this->faturaService->desfazerPagamentoParcela($installmentId, $userId);
    }

    /**
     * @return WorkflowPayload
     */
    public function listRecurring(int $userId, ?int $cardId = null): array
    {
        return $this->getRecorrenciaService()->listarRecorrenciasAtivas($userId, $cardId);
    }

    /**
     * @return WorkflowPayload
     */
    public function cancelRecurring(int $itemId, int $userId): array
    {
        return $this->getRecorrenciaService()->cancelarRecorrencia($itemId, $userId);
    }

    /**
     * @param array<string, mixed> $logContext
     * @return array<string, mixed>
     */
    private function checkAchievements(int $userId, string $context, array $logContext): array
    {
        try {
            $newAchievements = $this->getAchievementService()->checkAndUnlockAchievements($userId, $context);

            if (!empty($newAchievements)) {
                return ['achievements' => $newAchievements];
            }
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::GAMIFICATION, $logContext);
        }

        return [];
    }

    private function getAchievementService(): AchievementService
    {
        return $this->achievementService;
    }

    private function getRecorrenciaService(): RecorrenciaCartaoService
    {
        return $this->recorrenciaService;
    }

    private function resolveUpdateFailureStatus(mixed $message): int
    {
        if (!is_string($message) || $message === '') {
            return 422;
        }

        $normalized = mb_strtolower($message);

        if (
            str_contains($normalized, 'não encontrado')
            || str_contains($normalized, 'nao encontrado')
        ) {
            return 404;
        }

        return 422;
    }
}
