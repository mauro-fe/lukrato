<?php

declare(strict_types=1);

namespace Application\Services\Lancamento;

use Application\Enums\LogCategory;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Models\Parcelamento;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Financeiro\MetaProgressService;
use Application\Services\Infrastructure\LogService;
use Application\Services\User\OnboardingProgressService;
use Application\Support\FaturaHelper;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service responsavel pela exclusao de lancamentos.
 *
 * Encapsula toda a logica de negocio para delecao:
 * - Exclusao simples (single)
 * - Exclusao de todas as ocorrencias de recorrencia (all)
 * - Exclusao de ocorrencias futuras de recorrencia (future)
 * - Exclusao de parcelas de parcelamento (all/future)
 * - Reversao de pagamento de fatura ao excluir lancamento de pagamento
 */
class LancamentoDeletionService
{
    private LancamentoRepository $lancamentoRepo;
    private ParcelamentoRepository $parcelamentoRepo;
    private OnboardingProgressService $onboardingProgressService;
    private MetaProgressService $metaProgressService;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?ParcelamentoRepository $parcelamentoRepo = null,
        ?OnboardingProgressService $onboardingProgressService = null,
        ?MetaProgressService $metaProgressService = null
    ) {
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->parcelamentoRepo = $parcelamentoRepo ?? new ParcelamentoRepository();
        $this->onboardingProgressService = $onboardingProgressService ?? new OnboardingProgressService();
        $this->metaProgressService = $metaProgressService ?? new MetaProgressService();
    }

    /**
     * Exclui lancamento(s) conforme o escopo informado.
     *
     * @param Lancamento $lancamento O lancamento-alvo
     * @param int $userId ID do usuario autenticado
     * @param string $scope 'single', 'all' ou 'future'
     * @return array{ok: bool, message: string, excluidos: int}
     */
    public function delete(Lancamento $lancamento, int $userId, string $scope = 'single'): array
    {
        $transactionResult = DB::transaction(function () use ($lancamento, $userId, $scope): array {
            $affectedMetaRefs = [];

            if ($lancamento->origem_tipo === 'pagamento_fatura' && $lancamento->cartao_credito_id) {
                $this->reverterPagamentoFatura($lancamento);
            }

            if ($scope !== 'single' && $lancamento->recorrente && $lancamento->recorrencia_pai_id) {
                $result = $this->deleteRecorrencia($lancamento, $userId, $scope, $affectedMetaRefs);

                return [
                    'result' => $result,
                    'affected_meta_refs' => $affectedMetaRefs,
                ];
            }

            if ($scope !== 'single' && $lancamento->parcelamento_id) {
                $result = $this->deleteParcelamento($lancamento, $userId, $scope, $affectedMetaRefs);

                return [
                    'result' => $result,
                    'affected_meta_refs' => $affectedMetaRefs,
                ];
            }

            $result = $this->deleteSingle($lancamento, $userId, $affectedMetaRefs);

            return [
                'result' => $result,
                'affected_meta_refs' => $affectedMetaRefs,
            ];
        });

        $this->recalculateAffectedMetas($transactionResult['affected_meta_refs'] ?? []);
        $this->syncOnboardingStateAfterDeletion($userId);

        return $transactionResult['result'] ?? [
            'ok' => false,
            'message' => 'Erro ao excluir lancamento.',
            'excluidos' => 0,
        ];
    }

    /**
     * Exclui lancamentos de recorrencia conforme escopo (all ou future).
     */
    private function deleteRecorrencia(Lancamento $lancamento, int $userId, string $scope, array &$affectedMetaRefs): array
    {
        $paiId = $lancamento->recorrencia_pai_id;

        if ($scope === 'all') {
            $query = Lancamento::where(function ($q) use ($paiId) {
                $q->where('recorrencia_pai_id', $paiId)->orWhere('id', $paiId);
            })->where('user_id', $userId);

            $this->collectMetaRefsFromQuery($query, $affectedMetaRefs);
            $excluidos = $query->delete();

            return [
                'ok' => true,
                'message' => "{$excluidos} lancamentos da recorrencia excluidos",
                'excluidos' => $excluidos,
            ];
        }

        $query = Lancamento::where(function ($q) use ($paiId) {
            $q->where('recorrencia_pai_id', $paiId)->orWhere('id', $paiId);
        })
            ->where('user_id', $userId)
            ->where('data', '>=', $lancamento->data)
            ->where('pago', 0);

        $this->collectMetaRefsFromQuery($query, $affectedMetaRefs);
        $excluidos = $query->delete();

        return [
            'ok' => true,
            'message' => "{$excluidos} lancamentos futuros da recorrencia excluidos",
            'excluidos' => $excluidos,
        ];
    }

    /**
     * Exclui lancamentos de parcelamento conforme escopo (all ou future).
     */
    private function deleteParcelamento(Lancamento $lancamento, int $userId, string $scope, array &$affectedMetaRefs): array
    {
        $parcelamentoId = $lancamento->parcelamento_id;

        if ($scope === 'all') {
            $query = Lancamento::where('parcelamento_id', $parcelamentoId)
                ->where('user_id', $userId);

            $this->collectMetaRefsFromQuery($query, $affectedMetaRefs);
            $excluidos = $query->delete();

            Parcelamento::where('id', $parcelamentoId)
                ->where('user_id', $userId)
                ->delete();

            return [
                'ok' => true,
                'message' => "{$excluidos} parcelas excluidas",
                'excluidos' => $excluidos,
            ];
        }

        $query = Lancamento::where('parcelamento_id', $parcelamentoId)
            ->where('user_id', $userId)
            ->where('data', '>=', $lancamento->data)
            ->where('pago', 0);

        $this->collectMetaRefsFromQuery($query, $affectedMetaRefs);
        $excluidos = $query->delete();

        $this->ajustarParcelamentoPosExclusao($parcelamentoId, $userId);

        return [
            'ok' => true,
            'message' => "{$excluidos} parcelas futuras excluidas",
            'excluidos' => $excluidos,
        ];
    }

    /**
     * Exclui um unico lancamento e ajusta parcelamento se necessario.
     */
    private function deleteSingle(Lancamento $lancamento, int $userId, array &$affectedMetaRefs): array
    {
        $parcelamentoId = $lancamento->parcelamento_id;
        $this->appendMetaRef($affectedMetaRefs, $userId, (int) ($lancamento->meta_id ?? 0));

        $this->lancamentoRepo->delete($lancamento->id);

        if ($parcelamentoId) {
            $this->ajustarParcelamentoPosExclusao($parcelamentoId, $userId);
        }

        return [
            'ok' => true,
            'message' => 'Lancamento excluido',
            'excluidos' => 1,
        ];
    }

    /**
     * Ajusta o parcelamento apos exclusao de parcelas.
     * Se nao restam parcelas, exclui o parcelamento.
     * Se restam, atualiza o numero de parcelas e recalcula pagas.
     */
    private function ajustarParcelamentoPosExclusao(int $parcelamentoId, int $userId): void
    {
        $restantes = Lancamento::where('parcelamento_id', $parcelamentoId)->count();

        if ($restantes === 0) {
            Parcelamento::where('id', $parcelamentoId)
                ->where('user_id', $userId)
                ->delete();
            return;
        }

        $parcelamento = Parcelamento::forUser($userId)->find($parcelamentoId);
        if ($parcelamento) {
            $parcelamento->numero_parcelas = $restantes;
            $parcelamento->save();
            $this->parcelamentoRepo->atualizarParcelasPagas($parcelamentoId);
        }
    }

    /**
     * Reverte o pagamento de uma fatura de cartao de credito.
     * Desmarca os itens como pagos e atualiza status da fatura e limite do cartao.
     */
    private function reverterPagamentoFatura(Lancamento $lancamento): void
    {
        try {
            $faturaData = FaturaHelper::parseMonthYearFromObservacao($lancamento->observacao);
            if (!$faturaData) {
                return;
            }

            $itensPagos = FaturaCartaoItem::where('cartao_credito_id', $lancamento->cartao_credito_id)
                ->where('user_id', $lancamento->user_id)
                ->whereYear('data_vencimento', $faturaData['ano'])
                ->whereMonth('data_vencimento', $faturaData['mes'])
                ->where('pago', true)
                ->get();

            if ($itensPagos->isEmpty()) {
                return;
            }

            $faturaIds = $itensPagos->pluck('fatura_id')->unique()->filter()->values();
            $itemIds = $itensPagos->pluck('id')->toArray();

            FaturaCartaoItem::whereIn('id', $itemIds)->update([
                'pago' => false,
                'data_pagamento' => null,
            ]);

            foreach ($faturaIds as $faturaId) {
                $fatura = Fatura::forUser($lancamento->user_id)->find($faturaId);
                if ($fatura) {
                    $fatura->atualizarStatus();
                }
            }

            $cartao = $lancamento->cartaoCredito;
            if ($cartao) {
                $cartao->atualizarLimiteDisponivel();
            }
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::FATURA, [
                'action' => 'reverter_pagamento_fatura',
                'lancamento_id' => $lancamento->id,
                'cartao_id' => $lancamento->cartao_credito_id ?? null,
                'user_id' => $lancamento->user_id,
            ]);
        }
    }

    private function syncOnboardingStateAfterDeletion(int $userId): void
    {
        try {
            $this->onboardingProgressService->resyncState($userId);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::GENERAL, [
                'action' => 'resync_onboarding_after_lancamento_delete',
                'user_id' => $userId,
            ]);
        }
    }

    private function collectMetaRefsFromQuery($query, array &$affectedMetaRefs): void
    {
        $metaRefs = (clone $query)
            ->whereNotNull('meta_id')
            ->get(['user_id', 'meta_id']);

        foreach ($metaRefs as $metaRef) {
            $this->appendMetaRef(
                $affectedMetaRefs,
                (int) ($metaRef->user_id ?? 0),
                (int) ($metaRef->meta_id ?? 0)
            );
        }
    }

    private function appendMetaRef(array &$affectedMetaRefs, int $userId, int $metaId): void
    {
        if ($userId <= 0 || $metaId <= 0) {
            return;
        }

        $affectedMetaRefs[$userId . ':' . $metaId] = [
            'user_id' => $userId,
            'meta_id' => $metaId,
        ];
    }

    private function recalculateAffectedMetas(array $affectedMetaRefs): void
    {
        foreach ($affectedMetaRefs as $metaRef) {
            $this->metaProgressService->recalculateMeta((int) $metaRef['user_id'], (int) $metaRef['meta_id']);
        }
    }
}
