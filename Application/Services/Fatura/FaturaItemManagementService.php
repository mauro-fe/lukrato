<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Services\Infrastructure\LogService;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use InvalidArgumentException;

class FaturaItemManagementService
{
    public function atualizarItem(int $faturaId, int $itemId, int $usuarioId, array $dados): bool
    {
        DB::beginTransaction();

        try {
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('fatura_id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with(['cartaoCredito', 'fatura'])
                ->first();

            if (!$item) {
                throw new Exception("Item não encontrado");
            }

            $valorAntigo = $item->valor;
            $diferencaValor = 0;

            if (isset($dados['descricao']) && !empty(trim($dados['descricao']))) {
                $item->descricao = trim($dados['descricao']);
            }

            if (isset($dados['valor']) && is_numeric($dados['valor']) && $dados['valor'] > 0) {
                $novoValor = (float) $dados['valor'];
                $diferencaValor = $novoValor - $valorAntigo;
                $item->valor = $novoValor;
            }

            $item->save();

            if ($diferencaValor !== 0 && $item->fatura) {
                $item->fatura->valor_total = FaturaCartaoItem::where('fatura_id', $faturaId)->sum('valor');
                $item->fatura->save();
            }

            if ($diferencaValor !== 0 && $item->cartaoCredito) {
                $item->cartaoCredito->atualizarLimiteDisponivel();
            }

            DB::commit();

            LogService::info("Item de fatura atualizado", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'usuario_id' => $usuarioId,
                'descricao' => $dados['descricao'] ?? null,
                'valor_antigo' => $valorAntigo,
                'valor_novo' => $dados['valor'] ?? null
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

    public function buscarItem(int $itemId, int $usuarioId): ?array
    {
        try {
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('user_id', $usuarioId)
                ->first();

            if (!$item) {
                return null;
            }

            return [
                'id' => $item->id,
                'fatura_id' => $item->fatura_id,
                'descricao' => $item->descricao,
                'valor' => $item->valor,
                'pago' => (bool) $item->pago,
                'parcela_atual' => $item->parcela_atual,
                'total_parcelas' => $item->total_parcelas,
                'mes_referencia' => $item->mes_referencia,
                'ano_referencia' => $item->ano_referencia,
            ];
        } catch (Exception $e) {
            LogService::error("Erro ao buscar item", [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function excluirItem(int $faturaId, int $itemId, int $usuarioId): array
    {
        DB::beginTransaction();

        try {
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('fatura_id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with('cartaoCredito')
                ->first();

            if (!$item) {
                throw new Exception("Item não encontrado");
            }

            if ($item->pago) {
                throw new InvalidArgumentException(
                    "Não é possível excluir um item já pago. Desfaça o pagamento primeiro."
                );
            }

            $cartao = $item->cartaoCredito;
            $faturaRelacionadaId = $item->fatura_id;

            $item->delete();

            if ($cartao) {
                $cartao->atualizarLimiteDisponivel();
            }

            if ($faturaRelacionadaId) {
                $this->atualizarFaturaAposExclusao($faturaRelacionadaId, $usuarioId);
            }

            DB::commit();

            LogService::info("Item de fatura excluído", [
                'item_id' => $itemId,
                'fatura_id' => $faturaRelacionadaId,
                'usuario_id' => $usuarioId
            ]);

            return ['success' => true, 'message' => 'Item excluído com sucesso'];
        } catch (InvalidArgumentException $e) {
            DB::rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao excluir item da fatura", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Erro ao excluir item'];
        }
    }

    public function excluirParcelamento(int $itemId, int $usuarioId): array
    {
        DB::beginTransaction();

        try {
            LogService::info("Iniciando exclusão de parcelamento", [
                'item_id' => $itemId,
                'usuario_id' => $usuarioId
            ]);

            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('user_id', $usuarioId)
                ->with('cartaoCredito')
                ->first();

            if (!$item) {
                LogService::warning("Item não encontrado para exclusão de parcelamento", [
                    'item_id' => $itemId,
                    'usuario_id' => $usuarioId
                ]);

                return ['success' => false, 'message' => 'Item não encontrado'];
            }

            LogService::info("Item encontrado para exclusão", [
                'item_id' => $itemId,
                'item_pai_id' => $item->item_pai_id,
                'total_parcelas' => $item->total_parcelas,
                'descricao' => $item->descricao
            ]);

            $itensParcelamento = FaturaCartaoItem::where('user_id', $usuarioId);

            if ($item->item_pai_id) {
                $itensParcelamento->where(function ($query) use ($item) {
                    $query->where('item_pai_id', $item->item_pai_id)
                        ->orWhere('id', $item->item_pai_id);
                });
            } else {
                $filhos = FaturaCartaoItem::where('item_pai_id', $item->id)->count();
                if ($filhos > 0) {
                    $itensParcelamento->where(function ($query) use ($item) {
                        $query->where('item_pai_id', $item->id)
                            ->orWhere('id', $item->id);
                    });
                } elseif ($item->total_parcelas > 1) {
                    $descricaoBase = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $item->descricao);

                    $itensParcelamento->where('cartao_credito_id', $item->cartao_credito_id)
                        ->where('total_parcelas', $item->total_parcelas)
                        ->where('data_compra', $item->data_compra)
                        ->where(function ($query) use ($descricaoBase) {
                            $query->where('descricao', 'LIKE', $descricaoBase . ' (%/%)')
                                ->orWhere('descricao', $descricaoBase);
                        });

                    LogService::info("Buscando parcelamento por descrição base", [
                        'descricao_base' => $descricaoBase,
                        'cartao_id' => $item->cartao_credito_id,
                        'total_parcelas' => $item->total_parcelas,
                        'data_compra' => $item->data_compra
                    ]);
                } else {
                    $itensParcelamento->where('id', $item->id);
                }
            }

            $itens = $itensParcelamento->get();

            $itensPagos = $itens->where('pago', true)->count();
            if ($itensPagos > 0) {
                return [
                    'success' => false,
                    'message' => "Não é possível excluir: {$itensPagos} parcela(s) já foi(foram) paga(s). Desfaça o pagamento primeiro."
                ];
            }

            $totalExcluidos = $itens->count();
            $faturasAfetadas = $itens->pluck('fatura_id')->unique()->filter()->toArray();
            $cartao = $item->cartaoCredito;

            FaturaCartaoItem::whereIn('id', $itens->pluck('id'))->delete();

            if ($cartao) {
                $cartao->atualizarLimiteDisponivel();
            }

            foreach ($faturasAfetadas as $faturaId) {
                $this->atualizarFaturaAposExclusao((int) $faturaId, $usuarioId);
            }

            DB::commit();

            LogService::info("Parcelamento excluído", [
                'item_inicial_id' => $itemId,
                'total_excluidos' => $totalExcluidos,
                'usuario_id' => $usuarioId
            ]);

            return [
                'success' => true,
                'message' => $totalExcluidos > 1
                    ? "Parcelamento excluído ({$totalExcluidos} parcelas)"
                    : 'Item excluído com sucesso',
                'itens_excluidos' => $totalExcluidos
            ];
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao excluir parcelamento", [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Erro ao excluir parcelamento'];
        }
    }

    private function atualizarFaturaAposExclusao(int $faturaId, int $usuarioId): void
    {
        $fatura = Fatura::forUser($usuarioId)->find($faturaId);
        if (!$fatura) {
            return;
        }

        $itensRestantes = FaturaCartaoItem::where('fatura_id', $faturaId)->count();
        if ($itensRestantes === 0) {
            $fatura->delete();
            return;
        }

        $novoTotal = FaturaCartaoItem::where('fatura_id', $faturaId)->sum('valor');
        $fatura->valor_total = $novoTotal;
        $fatura->save();
        $fatura->atualizarStatus();
    }
}
