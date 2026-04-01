<?php

declare(strict_types=1);

namespace Application\Services\Metas;

use Application\Models\Lancamento;
use Application\Models\Meta;
use Illuminate\Database\Capsule\Manager as DB;

class MetaProgressService
{
    public function recalculateMeta(int $userId, int $metaId, bool $lockMeta = false): ?Meta
    {
        if ($metaId <= 0) {
            return null;
        }

        $query = Meta::where('id', $metaId)->where('user_id', $userId);
        if ($lockMeta) {
            $query->lockForUpdate();
        }

        /** @var Meta|null $meta */
        $meta = $query->first();
        if (!$meta) {
            return null;
        }

        $linkedTotals = $this->getLinkedTotals($userId, $metaId, (string) ($meta->modelo ?? Meta::MODELO_RESERVA));
        $valorAporteManual = (float) ($meta->valor_aporte_manual ?? 0);

        $valorAlocado = round(max(0, $linkedTotals['reservado']) + $valorAporteManual, 2);
        $valorRealizado = round(max(0, $linkedTotals['realizado']), 2);
        $status = $this->resolveStatus($meta, $valorAlocado, $valorRealizado);

        $meta->valor_alocado = $valorAlocado;
        $meta->valor_realizado = $valorRealizado;
        $meta->status = $status;
        $meta->save();

        return $meta;
    }

    public function incrementManualAllocation(int $userId, int $metaId, float $amount): ?Meta
    {
        $amount = round(max(0, $amount), 2);

        if ($amount <= 0) {
            throw new \DomainException('O valor do aporte deve ser maior que zero.');
        }

        return DB::transaction(function () use ($userId, $metaId, $amount) {
            /** @var Meta|null $meta */
            $meta = Meta::where('id', $metaId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$meta) {
                return null;
            }

            $meta->valor_aporte_manual = round((float) ($meta->valor_aporte_manual ?? 0) + $amount, 2);
            $meta->save();

            return $this->recalculateMeta($userId, $metaId, true);
        });
    }

    public function syncManualAllocationToTarget(int $userId, int $metaId, float $desiredTotal): ?Meta
    {
        $desiredTotal = round(max(0, $desiredTotal), 2);

        return DB::transaction(function () use ($userId, $metaId, $desiredTotal) {
            /** @var Meta|null $meta */
            $meta = Meta::where('id', $metaId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$meta) {
                return null;
            }

            $linkedTotals = $this->getLinkedTotals($userId, $metaId, (string) ($meta->modelo ?? Meta::MODELO_RESERVA));
            $linkedReservado = max(0, $linkedTotals['reservado']);
            if ($desiredTotal + 0.001 < $linkedReservado) {
                throw new \DomainException('O valor alocado nao pode ficar abaixo do total vinculado por lancamentos pagos.');
            }

            $meta->valor_aporte_manual = round($desiredTotal - $linkedReservado, 2);
            $meta->save();

            return $this->recalculateMeta($userId, $metaId, true);
        });
    }

    /**
     * @return array{reservado: float, realizado: float}
     */
    private function getLinkedTotals(int $userId, int $metaId, string $metaModelo): array
    {
        $rows = Lancamento::where('user_id', $userId)
            ->where('meta_id', $metaId)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->get(['tipo', 'eh_transferencia', 'valor', 'meta_operacao', 'meta_valor']);

        $aporte = 0.0;
        $resgate = 0.0;
        $realizado = 0.0;

        foreach ($rows as $row) {
            $valor = $this->resolveLinkedValue($row);
            if ($valor <= 0) {
                continue;
            }

            $operacao = $this->resolveMetaOperation(
                (string) ($row->meta_operacao ?? ''),
                (string) ($row->tipo ?? ''),
                (bool) ($row->eh_transferencia ?? false),
                $metaModelo
            );

            if ($operacao === Lancamento::META_OPERACAO_APORTE) {
                $aporte += $valor;
                continue;
            }

            if ($operacao === Lancamento::META_OPERACAO_RESGATE) {
                $resgate += $valor;
                continue;
            }

            if ($operacao === Lancamento::META_OPERACAO_REALIZACAO) {
                $realizado += $valor;
            }
        }

        return [
            'reservado' => round($aporte - $resgate, 2),
            'realizado' => round($realizado, 2),
        ];
    }

    private function resolveLinkedValue(Lancamento $lancamento): float
    {
        $raw = $lancamento->meta_valor;
        if ($raw === null || (float) $raw <= 0) {
            $raw = $lancamento->valor;
        }

        return round(max(0, (float) $raw), 2);
    }

    private function resolveMetaOperation(string $metaOperacao, string $tipo, bool $ehTransferencia, string $metaModelo): string
    {
        $metaOperacao = strtolower(trim($metaOperacao));
        if (in_array($metaOperacao, [
            Lancamento::META_OPERACAO_APORTE,
            Lancamento::META_OPERACAO_RESGATE,
            Lancamento::META_OPERACAO_REALIZACAO,
        ], true)) {
            return $metaOperacao;
        }

        if ($ehTransferencia || $tipo === Lancamento::TIPO_RECEITA) {
            return Lancamento::META_OPERACAO_APORTE;
        }

        if ($tipo === Lancamento::TIPO_DESPESA) {
            return $metaModelo === Meta::MODELO_REALIZACAO
                ? Lancamento::META_OPERACAO_REALIZACAO
                : Lancamento::META_OPERACAO_RESGATE;
        }

        return Lancamento::META_OPERACAO_APORTE;
    }

    private function resolveStatus(Meta $meta, float $valorAlocado, float $valorRealizado): string
    {
        if ($meta->status === Meta::STATUS_CANCELADA) {
            return Meta::STATUS_CANCELADA;
        }

        if ($valorRealizado > 0.0001) {
            return Meta::STATUS_REALIZADA;
        }

        $base = ($meta->modelo ?? Meta::MODELO_RESERVA) === Meta::MODELO_REALIZACAO
            ? $valorAlocado + $valorRealizado
            : $valorAlocado;

        if ($base >= (float) $meta->valor_alvo) {
            return Meta::STATUS_CONCLUIDA;
        }

        if ($meta->status === Meta::STATUS_PAUSADA) {
            return Meta::STATUS_PAUSADA;
        }

        return Meta::STATUS_ATIVA;
    }
}
