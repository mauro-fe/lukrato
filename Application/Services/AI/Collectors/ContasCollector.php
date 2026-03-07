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
    public function collect(ContextPeriod $period): array
    {
        return [
            'contas'          => $this->contasBancarias(),
            'cartoes_credito' => $this->cartoes($period),
            'parcelas'        => $this->parcelas(),
            'recorrencias'    => $this->recorrencias(),
        ];
    }

    private function contasBancarias(): array
    {
        $total  = Conta::count();
        $ativas = Conta::where('ativo', 1)->count();

        $porTipo = DB::table('contas')
            ->where('ativo', 1)
            ->select('tipo_conta', DB::raw('COUNT(*) as qtd'))
            ->groupBy('tipo_conta')
            ->get()
            ->mapWithKeys(fn($r) => [$r->tipo_conta ?? 'outros' => (int) $r->qtd])
            ->toArray();

        return [
            'total'    => $total,
            'ativas'   => $ativas,
            'por_tipo' => $porTipo,
        ];
    }

    private function cartoes(ContextPeriod $p): array
    {
        $total  = CartaoCredito::count();
        $ativos = CartaoCredito::where('ativo', 1)->count();

        $limiteTotal      = (float) CartaoCredito::where('ativo', 1)->sum('limite_total');
        $limiteDisponivel = (float) CartaoCredito::where('ativo', 1)->sum('limite_disponivel');
        $limiteUtilizado  = $limiteTotal - $limiteDisponivel;

        $itensFatura = FaturaCartaoItem::where('mes_referencia', $p->mesNum)
            ->where('ano_referencia', $p->anoNum)
            ->where('tipo', 'despesa');

        $totalFaturasMes = round((float) $itensFatura->sum('valor'), 2);
        $qtdItensFatura  = $itensFatura->count();

        $itensPendentes = FaturaCartaoItem::where('mes_referencia', $p->mesNum)
            ->where('ano_referencia', $p->anoNum)
            ->where('pago', 0)
            ->whereNull('cancelado_em')
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

    private function parcelas(): array
    {
        $ativos     = Parcelamento::where('status', 'ativo')->count();
        $concluidos = Parcelamento::where('status', 'concluido')->count();
        $valorTotal = round((float) Parcelamento::where('status', 'ativo')->sum('valor_total'), 2);
        $media      = Parcelamento::where('status', 'ativo')->avg('numero_parcelas');

        return [
            'ativos'             => $ativos,
            'concluidos'         => $concluidos,
            'valor_total_ativos' => $valorTotal,
            'media_parcelas'     => $media ? round((float) $media, 1) : 0,
        ];
    }

    private function recorrencias(): array
    {
        $base = Lancamento::where('recorrente', 1)->whereNull('cancelado_em')->whereNull('recorrencia_pai_id');

        $totalAtivas       = $base->count();
        $valorRecorrente   = round((float) (clone $base)->where('tipo', 'despesa')->sum('valor'), 2);
        $receitaRecorrente = round((float) (clone $base)->where('tipo', 'receita')->sum('valor'), 2);

        $porFreq = Lancamento::where('recorrente', 1)
            ->whereNull('cancelado_em')
            ->whereNull('recorrencia_pai_id')
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
