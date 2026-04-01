<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Models\CartaoCredito;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Models\Parcelamento;
use Application\Services\Infrastructure\LogService;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;

class CartaoPostSaleService
{
    public function __construct(
        private CartaoFaturaSupportService $faturaSupportService,
        private CartaoLimitUpdaterService $limitUpdaterService
    ) {}

    public function cancelarParcelamento(int $parcelamentoId, int $userId): array
    {
        try {
            DB::beginTransaction();

            $parcelamento = Parcelamento::where('id', $parcelamentoId)
                ->where('user_id', $userId)
                ->first();

            if (!$parcelamento) {
                return [
                    'success' => false,
                    'message' => 'Parcelamento não encontrado',
                ];
            }

            if ($parcelamento->status !== 'ativo') {
                return [
                    'success' => false,
                    'message' => 'Parcelamento não está ativo',
                ];
            }

            $hoje = date('Y-m-d');
            $totalCanceladas = 0;
            $valorDevolver = 0.0;

            $parcelasFuturasLanc = Lancamento::where('parcelamento_id', $parcelamento->id)
                ->where('data', '>', $hoje)
                ->get();

            if ($parcelasFuturasLanc->isNotEmpty()) {
                $valorDevolver += (float) $parcelasFuturasLanc->sum('valor');
                foreach ($parcelasFuturasLanc as $parcela) {
                    $parcela->cancelado_em = now();
                    $parcela->save();
                }
                $totalCanceladas += $parcelasFuturasLanc->count();
            }

            $parcelasFuturasItem = FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $parcelamento->cartao_credito_id)
                ->where('eh_parcelado', true)
                ->where('descricao', 'LIKE', '%' . ($parcelamento->descricao ?? '') . '%')
                ->where('item_pai_id', $parcelamento->id)
                ->whereNull('cancelado_em')
                ->where(function ($q) use ($hoje) {
                    $q->where('data_compra', '>', $hoje)
                        ->orWhere('data_vencimento', '>', $hoje);
                })
                ->where('pago', false)
                ->get();

            if ($parcelasFuturasItem->isEmpty() && $parcelamento->cartao_credito_id) {
                $parcelasFuturasItem = FaturaCartaoItem::where('user_id', $userId)
                    ->where('cartao_credito_id', $parcelamento->cartao_credito_id)
                    ->where('eh_parcelado', true)
                    ->whereNull('cancelado_em')
                    ->where('pago', false)
                    ->whereRaw('parcela_atual > 1')
                    ->where('total_parcelas', $parcelamento->numero_parcelas ?? 0)
                    ->get();
            }

            if ($parcelasFuturasItem->isNotEmpty()) {
                $valorDevolver += (float) $parcelasFuturasItem->sum('valor');
                foreach ($parcelasFuturasItem as $item) {
                    $item->cancelado_em = now();
                    $item->save();
                }
                $totalCanceladas += $parcelasFuturasItem->count();
            }

            if ($totalCanceladas === 0) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Não existem parcelas futuras para cancelar',
                ];
            }

            if ($parcelamento->cartao_credito_id && $valorDevolver > 0) {
                $this->limitUpdaterService->atualizarLimiteCartao(
                    (int) $parcelamento->cartao_credito_id,
                    $valorDevolver,
                    $userId,
                    'credito'
                );
            }

            $parcelamento->status = 'parcial';
            $parcelamento->save();

            DB::commit();

            return [
                'success' => true,
                'parcelamento_id' => $parcelamento->id,
                'parcelas_canceladas' => $totalCanceladas,
                'valor_devolvido' => $valorDevolver,
                'message' => 'Parcelamento cancelado parcialmente com sucesso',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            LogService::safeErrorLog('[CARTAO] Erro ao cancelar parcelamento: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao cancelar parcelamento',
            ];
        }
    }

    public function criarEstornoCartao(int $userId, array $data): array
    {
        try {
            DB::beginTransaction();

            $cartaoId = $data['cartao_credito_id'] ?? null;
            $valorEstorno = abs((float) ($data['valor'] ?? 0));
            $descricao = $data['descricao'] ?? 'Estorno';
            $dataEstorno = $data['data'] ?? date('Y-m-d');
            $categoriaId = $data['categoria_id'] ?? null;

            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->first();

            if (!$cartao) {
                return [
                    'success' => false,
                    'message' => 'Cartão de crédito não encontrado',
                ];
            }

            $diaFechamento = (int) $cartao->dia_fechamento;
            $diaVencimento = (int) $cartao->dia_vencimento;

            $mesReferencia = $data['mes_referencia'] ?? null;
            $anoReferencia = $data['ano_referencia'] ?? null;

            if (!$mesReferencia || !$anoReferencia) {
                $dataObj = new \DateTimeImmutable($dataEstorno);
                $diaCompra = (int) $dataObj->format('d');
                $mesCompra = (int) $dataObj->format('m');
                $anoCompra = (int) $dataObj->format('Y');

                if ($diaCompra >= $diaFechamento) {
                    $mesReferencia = $mesCompra + 1;
                    $anoReferencia = $anoCompra;
                    if ($mesReferencia > 12) {
                        $mesReferencia = 1;
                        $anoReferencia++;
                    }
                } else {
                    $mesReferencia = $mesCompra;
                    $anoReferencia = $anoCompra;
                }
            }

            if ($diaVencimento > $diaFechamento) {
                $mesVencimento = (int) $mesReferencia;
                $anoVencimento = (int) $anoReferencia;
            } else {
                $mesVencimento = (int) $mesReferencia + 1;
                $anoVencimento = (int) $anoReferencia;
                if ($mesVencimento > 12) {
                    $mesVencimento = 1;
                    $anoVencimento++;
                }
            }

            $ultimoDiaMes = (int) date('t', mktime(0, 0, 0, $mesVencimento, 1, $anoVencimento));
            $dataVencimento = sprintf(
                '%04d-%02d-%02d',
                $anoVencimento,
                $mesVencimento,
                min($diaVencimento, $ultimoDiaMes)
            );

            $fatura = $this->faturaSupportService->buscarOuCriarFatura(
                $userId,
                (int) $cartaoId,
                $mesVencimento,
                $anoVencimento
            );

            $valorEstornoFormatado = $this->faturaSupportService->moneyString(-$valorEstorno);

            $item = FaturaCartaoItem::create([
                'user_id' => $userId,
                'cartao_credito_id' => $cartaoId,
                'fatura_id' => $fatura->id,
                'descricao' => '↩️ ' . $descricao,
                'valor' => $valorEstornoFormatado,
                'tipo' => 'estorno',
                'data_compra' => $dataEstorno,
                'data_vencimento' => $dataVencimento,
                'mes_referencia' => $mesReferencia,
                'ano_referencia' => $anoReferencia,
                'categoria_id' => $categoriaId,
                'eh_parcelado' => false,
                'parcela_atual' => 1,
                'total_parcelas' => 1,
                'pago' => true,
                'data_pagamento' => $dataEstorno,
            ]);

            $novoTotal = max(0, (float) $fatura->valor_total - $valorEstorno);
            $fatura->update(['valor_total' => $this->faturaSupportService->moneyString($novoTotal)]);

            $this->limitUpdaterService->atualizarLimiteCartao(
                (int) $cartaoId,
                $valorEstorno,
                $userId,
                'credito_estorno'
            );

            $cartao = CartaoCredito::forUser($userId)->find((int) $cartaoId);

            DB::commit();

            LogService::info('[CARTAO] Estorno criado', [
                'item_id' => $item->id,
                'cartao_id' => $cartaoId,
                'valor' => -$valorEstorno,
                'mes_referencia' => $mesReferencia,
                'ano_referencia' => $anoReferencia,
                'novo_limite_disponivel' => $cartao?->limite_disponivel,
            ]);

            return [
                'success' => true,
                'item' => $item,
                'message' => 'Estorno adicionado à fatura do cartão',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            LogService::error('Erro ao criar estorno de cartão', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao criar estorno: ' . $e->getMessage(),
            ];
        }
    }
}
