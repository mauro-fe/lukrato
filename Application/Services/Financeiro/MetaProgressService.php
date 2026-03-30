<?php

declare(strict_types=1);

namespace Application\Services\Financeiro;

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

        $valorAlocado = round($this->getLinkedAllocationTotal($userId, $metaId) + (float) ($meta->valor_aporte_manual ?? 0), 2);
        $status = $this->resolveStatus($meta, $valorAlocado);

        $meta->valor_alocado = $valorAlocado;
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

            $linkedTotal = $this->getLinkedAllocationTotal($userId, $metaId);
            if ($desiredTotal + 0.001 < $linkedTotal) {
                throw new \DomainException('O valor alocado nao pode ficar abaixo do total ja vinculado por lancamentos.');
            }

            $meta->valor_aporte_manual = round($desiredTotal - $linkedTotal, 2);
            $meta->save();

            return $this->recalculateMeta($userId, $metaId, true);
        });
    }

    private function getLinkedAllocationTotal(int $userId, int $metaId): float
    {
        return round((float) Lancamento::where('user_id', $userId)
            ->where('meta_id', $metaId)
            ->where(function ($query) {
                $query->where(function ($receitas) {
                    $receitas->where('eh_transferencia', 0)
                        ->where('tipo', Lancamento::TIPO_RECEITA);
                })->orWhere('eh_transferencia', 1);
            })
            ->sum('valor'), 2);
    }

    private function resolveStatus(Meta $meta, float $valorAlocado): string
    {
        if ($meta->status === Meta::STATUS_CANCELADA) {
            return Meta::STATUS_CANCELADA;
        }

        if ($valorAlocado >= (float) $meta->valor_alvo) {
            return Meta::STATUS_CONCLUIDA;
        }

        if ($meta->status === Meta::STATUS_PAUSADA) {
            return Meta::STATUS_PAUSADA;
        }

        return Meta::STATUS_ATIVA;
    }
};
