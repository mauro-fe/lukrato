<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Models\Parcelamento;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class ContasCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period, ?int $userId = null): array
    {
        return [
            'contas'          => $this->contasBancarias($userId),
            'cartoes_credito' => $this->cartoes($period, $userId),
            'parcelas'        => $this->parcelas($userId),
            'recorrencias'    => $this->recorrencias($userId),
        ];
    }

    private function contasBancarias(?int $userId): array
    {
        $base  = Conta::query();
        if ($userId) $base->where('user_id', $userId);

        $total  = (clone $base)->count();
        $ativas = (clone $base)->where('ativo', 1)->count();

        $porTipo = DB::table('contas')
            ->where('ativo', 1)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->select('tipo_conta', DB::raw('COUNT(*) as qtd'))
            ->groupBy('tipo_conta')
            ->get()
            ->mapWithKeys(fn($r) => [$r->tipo_conta ?? 'outros' => (int) $r->qtd])
            ->toArray();

        $porInstituicao = DB::table('contas')
            ->leftJoin('instituicoes_financeiras', 'contas.instituicao_financeira_id', '=', 'instituicoes_financeiras.id')
            ->where('contas.ativo', 1)
            ->when($userId, fn($q) => $q->where('contas.user_id', $userId))
            ->select(
                DB::raw('COALESCE(instituicoes_financeiras.nome, "Sem instituição") as instituicao'),
                DB::raw('COUNT(*) as qtd')
            )
            ->groupBy('instituicao')
            ->orderByDesc('qtd')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn($r) => [$r->instituicao => (int) $r->qtd])
            ->toArray();

        return [
            'total'           => $total,
            'ativas'          => $ativas,
            'por_tipo'        => $porTipo,
            'por_instituicao' => $porInstituicao,
        ];
    }

    private function cartoes(ContextPeriod $p, ?int $userId): array
    {
        $base   = CartaoCredito::query();
        if ($userId) $base->where('user_id', $userId);

        $total  = (clone $base)->count();
        $ativos = (clone $base)->where('ativo', 1)->count();

        $limiteTotal      = (float) (clone $base)->where('ativo', 1)->sum('limite_total');
        $limiteDisponivel = (float) (clone $base)->where('ativo', 1)->sum('limite_disponivel');
        $limiteUtilizado  = $limiteTotal - $limiteDisponivel;

        $itensFatura = FaturaCartaoItem::where('mes_referencia', $p->mesNum)
            ->where('ano_referencia', $p->anoNum)
            ->when($userId, fn($q) => $q->whereIn('cartao_credito_id', CartaoCredito::where('user_id', $userId)->pluck('id')))
            ->where('tipo', 'despesa');

        $totalFaturasMes = round((float) $itensFatura->sum('valor'), 2);
        $qtdItensFatura  = $itensFatura->count();

        $itensPendentes = FaturaCartaoItem::where('mes_referencia', $p->mesNum)
            ->where('ano_referencia', $p->anoNum)
            ->where('pago', 0)
            ->whereNull('cancelado_em')
            ->when($userId, fn($q) => $q->whereIn('cartao_credito_id', CartaoCredito::where('user_id', $userId)->pluck('id')))
            ->count();

        return [
            'total_cartoes'        => $total,
            'ativos'               => $ativos,
            'limite_total'         => round($limiteTotal, 2),
            'limite_disponivel'    => round($limiteDisponivel, 2),
            'limite_utilizado'     => round($limiteUtilizado, 2),
            'percentual_uso'       => $limiteTotal > 0 ? round(($limiteUtilizado / $limiteTotal) * 100, 1) : 0,
            'total_faturas_mes'    => $totalFaturasMes,
            'qtd_itens_fatura_mes' => $qtdItensFatura,
            'itens_pendentes_mes'  => $itensPendentes,
        ];
    }

    private function parcelas(?int $userId): array
    {
        $base = Parcelamento::query();
        if ($userId) $base->where('user_id', $userId);

        $ativos     = (clone $base)->where('status', 'ativo')->count();
        $concluidos = (clone $base)->where('status', 'concluido')->count();
        $valorTotal = round((float) (clone $base)->where('status', 'ativo')->sum('valor_total'), 2);
        $media      = (clone $base)->where('status', 'ativo')->avg('numero_parcelas');

        return [
            'ativos'             => $ativos,
            'concluidos'         => $concluidos,
            'valor_total_ativos' => $valorTotal,
            'media_parcelas'     => $media ? round((float) $media, 1) : 0,
        ];
    }

    private function recorrencias(?int $userId): array
    {
        $base = Lancamento::where('recorrente', 1)->whereNull('cancelado_em')->whereNull('recorrencia_pai_id');
        if ($userId) $base->where('user_id', $userId);

        $totalAtivas       = $base->count();
        $valorRecorrente   = round((float) (clone $base)->where('tipo', 'despesa')->sum('valor'), 2);
        $receitaRecorrente = round((float) (clone $base)->where('tipo', 'receita')->sum('valor'), 2);

        $porFreq = Lancamento::where('recorrente', 1)
            ->whereNull('cancelado_em')
            ->whereNull('recorrencia_pai_id')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->select('recorrencia_freq', DB::raw('COUNT(*) as qtd'))
            ->groupBy('recorrencia_freq')
            ->get()
            ->mapWithKeys(fn($r) => [$r->recorrencia_freq ?? 'mensal' => (int) $r->qtd])
            ->toArray();

        return [
            'total_ativas'              => $totalAtivas,
            'despesa_recorrente_mensal' => $valorRecorrente,
            'receita_recorrente_mensal' => $receitaRecorrente,
            'por_frequencia'            => $porFreq,
        ];
    }
}
