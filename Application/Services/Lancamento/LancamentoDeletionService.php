<?php

declare(strict_types=1);

namespace Application\Services\Lancamento;

use Application\Models\Lancamento;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\Parcelamento;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Enums\LogCategory;
use Application\Support\FaturaHelper;
use Application\Services\Infrastructure\LogService;

/**
 * Service responsável pela exclusão de lançamentos.
 *
 * Encapsula toda a lógica de negócio para deleção:
 * - Exclusão simples (single)
 * - Exclusão de todas as ocorrências de recorrência (all)
 * - Exclusão de ocorrências futuras de recorrência (future)
 * - Exclusão de parcelas de parcelamento (all/future)
 * - Reversão de pagamento de fatura ao excluir lançamento de pagamento
 */
class LancamentoDeletionService
{
    private LancamentoRepository $lancamentoRepo;
    private ParcelamentoRepository $parcelamentoRepo;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?ParcelamentoRepository $parcelamentoRepo = null
    ) {
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->parcelamentoRepo = $parcelamentoRepo ?? new ParcelamentoRepository();
    }

    /**
     * Exclui lançamento(s) conforme o escopo informado.
     *
     * @param Lancamento $lancamento O lançamento-alvo
     * @param int $userId ID do usuário autenticado
     * @param string $scope 'single', 'all' ou 'future'
     * @return array{ok: bool, message: string, excluidos: int}
     */
    public function delete(Lancamento $lancamento, int $userId, string $scope = 'single'): array
    {
        // Se for um pagamento de fatura, reverter os itens antes de excluir
        if ($lancamento->origem_tipo === 'pagamento_fatura' && $lancamento->cartao_credito_id) {
            $this->reverterPagamentoFatura($lancamento);
        }

        // Recorrência (all/future)
        if ($scope !== 'single' && $lancamento->recorrente && $lancamento->recorrencia_pai_id) {
            return $this->deleteRecorrencia($lancamento, $userId, $scope);
        }

        // Parcelamento (all/future)
        if ($scope !== 'single' && $lancamento->parcelamento_id) {
            return $this->deleteParcelamento($lancamento, $userId, $scope);
        }

        // Exclusão simples
        return $this->deleteSingle($lancamento, $userId);
    }

    /**
     * Exclui lançamentos de recorrência conforme escopo (all ou future).
     */
    private function deleteRecorrencia(Lancamento $lancamento, int $userId, string $scope): array
    {
        $paiId = $lancamento->recorrencia_pai_id;

        if ($scope === 'all') {
            $excluidos = Lancamento::where(function ($q) use ($paiId) {
                $q->where('recorrencia_pai_id', $paiId)->orWhere('id', $paiId);
            })->where('user_id', $userId)->delete();

            return [
                'ok'        => true,
                'message'   => "{$excluidos} lançamentos da recorrência excluídos",
                'excluidos' => $excluidos,
            ];
        }

        // scope === 'future'
        $excluidos = Lancamento::where(function ($q) use ($paiId) {
            $q->where('recorrencia_pai_id', $paiId)->orWhere('id', $paiId);
        })
            ->where('user_id', $userId)
            ->where('data', '>=', $lancamento->data)
            ->where('pago', 0)
            ->delete();

        return [
            'ok'        => true,
            'message'   => "{$excluidos} lançamentos futuros da recorrência excluídos",
            'excluidos' => $excluidos,
        ];
    }

    /**
     * Exclui lançamentos de parcelamento conforme escopo (all ou future).
     */
    private function deleteParcelamento(Lancamento $lancamento, int $userId, string $scope): array
    {
        $parcelamentoId = $lancamento->parcelamento_id;

        if ($scope === 'all') {
            $excluidos = Lancamento::where('parcelamento_id', $parcelamentoId)
                ->where('user_id', $userId)->delete();

            Parcelamento::where('id', $parcelamentoId)
                ->where('user_id', $userId)->delete();

            return [
                'ok'        => true,
                'message'   => "{$excluidos} parcelas excluídas",
                'excluidos' => $excluidos,
            ];
        }

        // scope === 'future'
        $excluidos = Lancamento::where('parcelamento_id', $parcelamentoId)
            ->where('user_id', $userId)
            ->where('data', '>=', $lancamento->data)
            ->where('pago', 0)
            ->delete();

        $this->ajustarParcelamentoPosExclusao($parcelamentoId, $userId);

        return [
            'ok'        => true,
            'message'   => "{$excluidos} parcelas futuras excluídas",
            'excluidos' => $excluidos,
        ];
    }

    /**
     * Exclui um único lançamento e ajusta parcelamento se necessário.
     */
    private function deleteSingle(Lancamento $lancamento, int $userId): array
    {
        $parcelamentoId = $lancamento->parcelamento_id;

        $this->lancamentoRepo->delete($lancamento->id);

        if ($parcelamentoId) {
            $this->ajustarParcelamentoPosExclusao($parcelamentoId, $userId);
        }

        return [
            'ok'        => true,
            'message'   => 'Lançamento excluído',
            'excluidos' => 1,
        ];
    }

    /**
     * Ajusta o parcelamento após exclusão de parcelas.
     * Se não restam parcelas, exclui o parcelamento.
     * Se restam, atualiza o número de parcelas e recalcula pagas.
     */
    private function ajustarParcelamentoPosExclusao(int $parcelamentoId, int $userId): void
    {
        $restantes = Lancamento::where('parcelamento_id', $parcelamentoId)->count();

        if ($restantes === 0) {
            Parcelamento::where('id', $parcelamentoId)
                ->where('user_id', $userId)->delete();
            return;
        }

        $parcelamento = Parcelamento::find($parcelamentoId);
        if ($parcelamento) {
            $parcelamento->numero_parcelas = $restantes;
            $parcelamento->save();
            $this->parcelamentoRepo->atualizarParcelasPagas($parcelamentoId);
        }
    }

    /**
     * Reverte o pagamento de uma fatura de cartão de crédito.
     * Desmarca os itens como pagos e atualiza status da fatura e limite do cartão.
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
            $itemIds   = $itensPagos->pluck('id')->toArray();

            FaturaCartaoItem::whereIn('id', $itemIds)->update([
                'pago'           => false,
                'data_pagamento' => null,
            ]);

            foreach ($faturaIds as $faturaId) {
                $fatura = Fatura::find($faturaId);
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
                'action'        => 'reverter_pagamento_fatura',
                'lancamento_id' => $lancamento->id,
                'cartao_id'     => $lancamento->cartao_credito_id ?? null,
                'user_id'       => $lancamento->user_id,
            ]);
        }
    }
}
