<?php

declare(strict_types=1);

namespace Application\Services\Lancamento;

use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Application\Services\Metas\MetaProgressService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service para transições de estado de lançamentos (pago/não pago).
 *
 * Centraliza a lógica de marcar/desmarcar como pago, garantindo
 * que side effects (data_pagamento) fiquem num único lugar.
 */
class LancamentoStatusService
{
    private LancamentoRepository $lancamentoRepo;
    private MetaProgressService $metaProgressService;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?MetaProgressService $metaProgressService = null
    )
    {
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->metaProgressService = $metaProgressService ?? new MetaProgressService();
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

        DB::transaction(function () use ($lancamento) {
            $this->lancamentoRepo->update($lancamento->id, [
                'pago'            => 1,
                'data_pagamento'  => date('Y-m-d'),
                'afeta_caixa'     => 1,
                'lembrar_antes_segundos' => null,
                'canal_email'     => 0,
                'canal_inapp'     => 0,
            ]);
        });

        $updated = $lancamento->fresh();
        $metaId = (int) ($updated->meta_id ?? 0);
        if ($metaId > 0) {
            $this->metaProgressService->recalculateMeta((int) $updated->user_id, $metaId);
        }

        return $updated;
    }

    /**
     * Desmarca um lançamento como pago (volta para pendente).
     *
     * @param Lancamento $lancamento
     * @return Lancamento O lançamento atualizado
     * @throws \DomainException Se o lançamento não estiver pago
     */
    public function desmarcarPago(Lancamento $lancamento): Lancamento
    {
        if (!$lancamento->pago) {
            throw new \DomainException('Lançamento já está pendente.');
        }

        // Não permitir desmarcar lançamentos de origem fatura/cartão
        $origensProtegidas = [
            Lancamento::ORIGEM_PAGAMENTO_FATURA,
        ];
        if (in_array($lancamento->origem_tipo, $origensProtegidas, true)) {
            throw new \DomainException('Este lançamento não pode ser desmarcado como pago.');
        }

        DB::transaction(function () use ($lancamento) {
            $this->lancamentoRepo->update($lancamento->id, [
                'pago'            => 0,
                'data_pagamento'  => null,
                'afeta_caixa'     => 0,
            ]);
        });

        $updated = $lancamento->fresh();
        $metaId = (int) ($updated->meta_id ?? 0);
        if ($metaId > 0) {
            $this->metaProgressService->recalculateMeta((int) $updated->user_id, $metaId);
        }

        return $updated;
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
        $payload = [
            'pago'           => $novoPago ? 1 : 0,
            'data_pagamento' => $novoPago ? date('Y-m-d') : null,
            'afeta_caixa'    => $novoPago ? 1 : 0,
        ];

        if ($novoPago) {
            $payload['lembrar_antes_segundos'] = null;
            $payload['canal_email'] = 0;
            $payload['canal_inapp'] = 0;
        }

        return $payload;
    }
}
