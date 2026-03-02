<?php

declare(strict_types=1);

namespace Application\Repositories;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * Repository para consultas de itens de fatura de cartão de crédito.
 */
class FaturaCartaoRepository
{
    /**
     * Retorna breakdown por categoria dos itens de uma fatura (cartão + mês/ano).
     *
     * @param int $cartaoCreditoId
     * @param int $userId
     * @param int $ano
     * @param int $mes
     * @return Collection Coleção com categoria_id, categoria_nome, categoria_icone, total, qtd_itens
     */
    public function getCategoryBreakdown(int $cartaoCreditoId, int $userId, int $ano, int $mes): Collection
    {
        return DB::table('fatura_cartao_itens as fi')
            ->leftJoin('categorias as c', 'c.id', '=', 'fi.categoria_id')
            ->where('fi.cartao_credito_id', $cartaoCreditoId)
            ->where('fi.user_id', $userId)
            ->whereYear('fi.data_vencimento', $ano)
            ->whereMonth('fi.data_vencimento', $mes)
            ->selectRaw('COALESCE(c.id, 0) as categoria_id')
            ->selectRaw("COALESCE(c.nome, 'Sem categoria') as categoria_nome")
            ->selectRaw("COALESCE(c.icone, '📦') as categoria_icone")
            ->selectRaw('SUM(fi.valor) as total')
            ->selectRaw('COUNT(*) as qtd_itens')
            ->groupBy('categoria_id', 'categoria_nome', 'categoria_icone')
            ->orderByDesc('total')
            ->get();
    }
}
