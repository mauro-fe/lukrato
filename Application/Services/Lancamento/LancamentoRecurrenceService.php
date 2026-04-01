<?php

declare(strict_types=1);

namespace Application\Services\Lancamento;

use Application\DTO\ServiceResultDTO;
use Application\Enums\LogCategory;
use Application\Enums\Recorrencia;
use Application\Repositories\LancamentoRepository;
use Application\Services\Financeiro\MetaProgressService;
use Application\Services\Infrastructure\LogService;
use Illuminate\Database\Capsule\Manager as DB;

class LancamentoRecurrenceService
{
    public function __construct(
        private LancamentoRepository $lancamentoRepo,
        private MetaProgressService $metaProgressService
    ) {}

    public function cancelarRecorrencia(int $lancamentoId, int $userId): ServiceResultDTO
    {
        $lancamento = \Application\Models\Lancamento::where('id', $lancamentoId)
            ->where('user_id', $userId)
            ->first();

        if (!$lancamento) {
            return ServiceResultDTO::fail('Lançamento não encontrado.');
        }

        if (!$lancamento->recorrente) {
            return ServiceResultDTO::fail('Este lançamento não faz parte de uma recorrência.');
        }

        $paiId = $lancamento->recorrencia_pai_id ?? $lancamento->id;
        $agora = date('Y-m-d H:i:s');

        \Application\Models\Lancamento::where('id', $paiId)
            ->where('user_id', $userId)
            ->whereNull('cancelado_em')
            ->update(['cancelado_em' => $agora]);

        $afetados = \Application\Models\Lancamento::where('recorrencia_pai_id', $paiId)
            ->where('id', '!=', $paiId)
            ->where('user_id', $userId)
            ->where('pago', 0)
            ->whereNull('cancelado_em')
            ->update(['cancelado_em' => $agora]);

        $paiCancelado = \Application\Models\Lancamento::where('id', $paiId)
            ->where('cancelado_em', $agora)
            ->where('pago', 0)
            ->exists();

        $totalAfetados = $afetados + ($paiCancelado ? 1 : 0);

        return ServiceResultDTO::ok("{$totalAfetados} lançamentos futuros cancelados.", [
            'cancelados' => $totalAfetados,
            'recorrencia_pai_id' => $paiId,
        ]);
    }

    /**
     * @param int $horizonMonths Mantido por compatibilidade (não utilizado)
     */
    public function estenderRecorrenciasInfinitas(int $horizonMonths = 3): int
    {
        $hoje = new \DateTimeImmutable('today');
        $totalCriados = 0;
        $metaRefsAfetados = [];

        $pais = \Application\Models\Lancamento::where('recorrente', 1)
            ->whereNull('cancelado_em')
            ->whereColumn('recorrencia_pai_id', 'id')
            ->get();

        foreach ($pais as $pai) {
            $freq = Recorrencia::tryFromString($pai->recorrencia_freq);
            if (!$freq) {
                continue;
            }

            try {
                DB::beginTransaction();

                $paiLocked = \Application\Models\Lancamento::where('id', $pai->id)
                    ->whereNull('cancelado_em')
                    ->lockForUpdate()
                    ->first();

                if (!$paiLocked) {
                    DB::rollBack();
                    continue;
                }

                $ultimoGerado = \Application\Models\Lancamento::withTrashed()
                    ->where('recorrencia_pai_id', $paiLocked->id)
                    ->orderBy('data', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $ultimaData = $ultimoGerado
                    ? $this->toDateTime($ultimoGerado->data)
                    : $this->toDateTime($paiLocked->data);

                while (true) {
                    $dataProx = clone $ultimaData;
                    $freq->advance($dataProx);

                    if ($dataProx > $hoje) {
                        break;
                    }

                    if ($paiLocked->recorrencia_fim !== null) {
                        $fim = $this->toDateTimeImmutable($paiLocked->recorrencia_fim);
                        if ($dataProx > $fim) {
                            break;
                        }
                    }

                    if ($paiLocked->recorrencia_total !== null && $paiLocked->recorrencia_total > 0) {
                        $totalSerie = \Application\Models\Lancamento::withTrashed()
                            ->where('recorrencia_pai_id', $paiLocked->id)
                            ->count();

                        if ($totalSerie >= (int) $paiLocked->recorrencia_total) {
                            break;
                        }
                    }

                    $dataProxFormatada = $dataProx->format('Y-m-d');

                    $jaExiste = \Application\Models\Lancamento::withTrashed()
                        ->where('recorrencia_pai_id', $paiLocked->id)
                        ->where('data', $dataProxFormatada)
                        ->exists();

                    if ($jaExiste) {
                        $ultimaData = $dataProx;
                        continue;
                    }

                    $dados = [
                        'user_id' => $paiLocked->user_id,
                        'tipo' => $paiLocked->tipo,
                        'data' => $dataProxFormatada,
                        'hora_lancamento' => $paiLocked->hora_lancamento,
                        'valor' => $paiLocked->valor,
                        'descricao' => $paiLocked->descricao,
                        'observacao' => $paiLocked->observacao,
                        'categoria_id' => $paiLocked->categoria_id,
                        'subcategoria_id' => $paiLocked->subcategoria_id,
                        'meta_id' => $paiLocked->meta_id,
                        'meta_operacao' => $paiLocked->meta_operacao,
                        'meta_valor' => $paiLocked->meta_valor,
                        'conta_id' => $paiLocked->conta_id,
                        'pago' => 0,
                        'afeta_caixa' => 0,
                        'data_pagamento' => null,
                        'forma_pagamento' => $paiLocked->forma_pagamento,
                        'recorrente' => 1,
                        'recorrencia_freq' => $paiLocked->recorrencia_freq,
                        'recorrencia_fim' => $paiLocked->recorrencia_fim,
                        'recorrencia_total' => $paiLocked->recorrencia_total,
                        'recorrencia_pai_id' => $paiLocked->id,
                        'origem_tipo' => \Application\Models\Lancamento::ORIGEM_RECORRENCIA,
                        'lembrar_antes_segundos' => $paiLocked->lembrar_antes_segundos,
                        'canal_email' => $paiLocked->canal_email,
                        'canal_inapp' => $paiLocked->canal_inapp,
                    ];

                    try {
                        $this->lancamentoRepo->create($dados);
                        $totalCriados++;
                        if (!empty($paiLocked->meta_id)) {
                            $metaRefsAfetados[$paiLocked->user_id . ':' . $paiLocked->meta_id] = [
                                'user_id' => (int) $paiLocked->user_id,
                                'meta_id' => (int) $paiLocked->meta_id,
                            ];
                        }
                    } catch (\Throwable $e) {
                        if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                            LogService::info('[RECORRENCIA] Duplicata evitada por unique key', [
                                'pai_id' => $paiLocked->id,
                                'data' => $dataProxFormatada,
                            ]);
                        } else {
                            throw $e;
                        }
                    }

                    $ultimaData = $dataProx;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                LogService::captureException($e, LogCategory::LANCAMENTO, [
                    'action' => 'estender_recorrencia',
                    'pai_id' => $pai->id,
                ]);
            }
        }

        foreach ($metaRefsAfetados as $metaRef) {
            $this->metaProgressService->recalculateMeta($metaRef['user_id'], $metaRef['meta_id']);
        }

        return $totalCriados;
    }

    private function toDateTime(\DateTimeInterface|string $value): \DateTime
    {
        $dateValue = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : $value;

        return new \DateTime($dateValue);
    }

    private function toDateTimeImmutable(\DateTimeInterface|string $value): \DateTimeImmutable
    {
        $dateValue = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : $value;

        return new \DateTimeImmutable($dateValue);
    }
}
