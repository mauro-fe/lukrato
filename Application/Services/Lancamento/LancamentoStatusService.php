<?php

declare(strict_types=1);

namespace Application\Services\Lancamento;

use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;

/**
 * Service para transições de estado de lançamentos (pago/não pago).
 *
 * Centraliza a lógica de marcar/desmarcar como pago, garantindo
 * que side effects (data_pagamento) fiquem num único lugar.
 */
class LancamentoStatusService
{
    private LancamentoRepository $lancamentoRepo;

    public function __construct(?LancamentoRepository $lancamentoRepo = null)
    {
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
    }

    /**
     * Marca um lançamento como pago.
     *
     * @param Lancamento $lancamento
     * @return Lancamento O lançamento atualizado
     * @throws \DomainException Se o lançamento já estiver pago ou cancelado
     */
    public function marcarPago(Lancamento $lancamento): Lancamento
    {
        if ($lancamento->pago) {
            throw new \DomainException('Lançamento já está pago.');
        }

        if ($lancamento->cancelado_em) {
            throw new \DomainException('Lançamento cancelado não pode ser marcado como pago.');
        }

        $this->lancamentoRepo->update($lancamento->id, [
            'pago'            => 1,
            'data_pagamento'  => date('Y-m-d'),
        ]);

        return $lancamento->fresh();
    }

    /**
     * Alterna o estado pago/não pago conforme o valor recebido.
     * Usado pelo UpdateController quando o campo `pago` é enviado no payload.
     *
     * @param bool $novoPago Novo estado desejado
     * @return array Dados parciais a aplicar no update
     */
    public function buildPagoPayload(bool $novoPago): array
    {
        return [
            'pago'           => $novoPago ? 1 : 0,
            'data_pagamento' => $novoPago ? date('Y-m-d') : null,
        ];
    }
}
