<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Application\Container\ApplicationContainer;
use Application\Models\FaturaCartaoItem;
use Application\Services\Infrastructure\LogService;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;

class FaturaItemPaymentService
{
    private FaturaItemPaymentStateService $itemPaymentStateService;

    public function __construct(?FaturaItemPaymentStateService $itemPaymentStateService = null)
    {
        $this->itemPaymentStateService = ApplicationContainer::resolveOrNew($itemPaymentStateService, FaturaItemPaymentStateService::class);
    }

    public function toggleItemPago(int $faturaId, int $itemId, int $usuarioId, bool $pago): bool
    {
        DB::beginTransaction();

        try {
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('fatura_id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with('cartaoCredito')
                ->first();

            if (!$item) {
                throw new Exception("Item nao encontrado");
            }

            if ($pago) {
                $this->itemPaymentStateService->marcarItemComoPago($item, $usuarioId);
            } else {
                $this->itemPaymentStateService->desmarcarItemPago($item);
            }

            DB::commit();

            LogService::info("Item de fatura atualizado", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'pago' => $pago
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao atualizar item da fatura", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
