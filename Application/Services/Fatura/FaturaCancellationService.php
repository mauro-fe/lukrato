<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Services\Infrastructure\LogService;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use InvalidArgumentException;

class FaturaCancellationService
{
    public function cancelar(int $faturaId, int $usuarioId): bool
    {
        DB::beginTransaction();

        try {
            $fatura = Fatura::where('id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with('itens')
                ->first();

            if (!$fatura) {
                throw new Exception("Fatura não encontrada");
            }

            $itensPagos = $fatura->itens->where('pago', 1)->count();
            if ($itensPagos > 0) {
                throw new InvalidArgumentException(
                    "Não é possível cancelar fatura com {$itensPagos} parcela(s) já paga(s)"
                );
            }

            FaturaCartaoItem::where('fatura_id', $faturaId)
                ->where('pago', 0)
                ->delete();

            $fatura->delete();

            DB::commit();

            LogService::info("Fatura cancelada", [
                'fatura_id' => $faturaId,
                'usuario_id' => $usuarioId
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao cancelar fatura", [
                'fatura_id' => $faturaId,
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
