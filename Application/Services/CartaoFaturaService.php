<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Illuminate\Database\Capsule\Manager as DB;
use DateTime;

class CartaoFaturaService
{
    /**
     * Retorna as parcelas não pagas de um cartão em um mês específico
     * 
     * @param int $cartaoId
     * @param int $mes (1-12)
     * @param int $ano
     * @return array ['parcelas' => [...], 'total' => float, 'vencimento' => string]
     */
    public function obterFaturaMes(int $cartaoId, int $mes, int $ano): array
    {
        $cartao = CartaoCredito::findOrFail($cartaoId);

        // Busca parcelas do mês que não foram pagas ainda
        $parcelas = Lancamento::where('cartao_credito_id', $cartaoId)
            ->where('eh_parcelado', true)
            ->whereNotNull('parcela_atual') // Ignora o pai (parcela_atual = null)
            ->where('pago', false)
            ->whereYear('data', $ano)
            ->whereMonth('data', $mes)
            ->orderBy('data')
            ->get();

        $total = $parcelas->sum('valor');

        // Data de vencimento da fatura (dia do vencimento do cartão)
        $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $cartao->dia_vencimento);

        return [
            'cartao' => [
                'id' => $cartao->id,
                'nome' => $cartao->nome,
                'ultimos_digitos' => $cartao->ultimos_digitos,
                'dia_vencimento' => $cartao->dia_vencimento,
            ],
            'parcelas' => $parcelas->map(function ($lancamento) {
                return [
                    'id' => $lancamento->id,
                    'descricao' => $lancamento->descricao,
                    'valor' => $lancamento->valor,
                    'data_vencimento' => $lancamento->data,
                    'parcela_atual' => $lancamento->parcela_atual,
                    'total_parcelas' => $lancamento->total_parcelas,
                    'pago' => $lancamento->pago,
                ];
            })->toArray(),
            'total' => $total,
            'vencimento' => $dataVencimento,
            'mes' => $mes,
            'ano' => $ano,
        ];
    }

    /**
     * Paga a fatura completa do mês
     * Marca todas as parcelas como pagas e devolve o limite ao cartão
     * 
     * @param int $cartaoId
     * @param int $mes
     * @param int $ano
     * @param int $userId
     * @return array
     */
    public function pagarFatura(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Busca fatura do mês
            $fatura = $this->obterFaturaMes($cartaoId, $mes, $ano);

            if (empty($fatura['parcelas'])) {
                throw new \Exception('Não há parcelas para pagar neste mês.');
            }

            $totalPagar = $fatura['total'];

            // Pega a conta_id da primeira parcela (todas devem ter a mesma conta)
            $primeiraParcelaId = $fatura['parcelas'][0]['id'];
            $primeiraParcelaCompleta = Lancamento::findOrFail($primeiraParcelaId);
            $contaId = $primeiraParcelaCompleta->conta_id;

            // Valida se a conta existe e tem saldo suficiente
            $conta = Conta::where('id', $contaId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $saldoAtual = $conta->saldo_inicial +
                Lancamento::where('conta_id', $contaId)
                ->where('user_id', $userId)
                ->sum(DB::raw("CASE WHEN tipo = 'receita' THEN valor ELSE -valor END"));

            if ($saldoAtual < $totalPagar) {
                throw new \Exception('Saldo insuficiente na conta para pagar a fatura.');
            }

            // Marca todas as parcelas como pagas
            $parcelasIds = array_column($fatura['parcelas'], 'id');
            Lancamento::whereIn('id', $parcelasIds)->update(['pago' => true]);

            // Devolve o limite ao cartão
            $cartao->limite_disponivel += $totalPagar;
            $cartao->save();

            // Cria lançamento de DESPESA na conta (pagamento da fatura)
            $lancamentoPagamento = Lancamento::create([
                'user_id' => $userId,
                'conta_id' => $contaId,
                'categoria_id' => null, // Pode criar categoria "Pagamento Cartão" depois
                'tipo' => 'despesa',
                'valor' => $totalPagar,
                'descricao' => sprintf(
                    'Pagamento Fatura %s •••• %s - %02d/%04d',
                    $cartao->nome,
                    $cartao->ultimos_digitos,
                    $mes,
                    $ano
                ),
                'data' => now()->format('Y-m-d'),
                'observacao' => sprintf(
                    'Pagamento automático de %d parcela(s) do cartão',
                    count($parcelasIds)
                ),
                'pago' => true, // Já marca como pago pois é o pagamento em si
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Fatura paga com sucesso!',
                'valor_pago' => $totalPagar,
                'parcelas_pagas' => count($parcelasIds),
                'novo_limite_disponivel' => $cartao->limite_disponivel,
                'lancamento_id' => $lancamentoPagamento->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Retorna todos os meses que têm faturas pendentes para um cartão
     * 
     * @param int $cartaoId
     * @param int $userId
     * @return array
     */
    public function obterMesesComFaturasPendentes(int $cartaoId, int $userId): array
    {
        $parcelas = Lancamento::where('cartao_credito_id', $cartaoId)
            ->where('user_id', $userId)
            ->where('eh_parcelado', true)
            ->whereNotNull('parcela_atual')
            ->where('pago', false)
            ->select(DB::raw('YEAR(data) as ano, MONTH(data) as mes'))
            ->groupBy('ano', 'mes')
            ->orderBy('ano')
            ->orderBy('mes')
            ->get();

        return $parcelas->map(function ($item) {
            return [
                'ano' => $item->ano,
                'mes' => $item->mes,
            ];
        })->toArray();
    }
}
