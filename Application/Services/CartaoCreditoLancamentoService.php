<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class CartaoCreditoLancamentoService
{
    /**
     * Criar lançamento com cartão de crédito (parcelado ou à vista)
     * 
     * @param int $userId
     * @param array $data Dados do lançamento incluindo cartao_credito_id, eh_parcelado, total_parcelas
     * @return array ['success' => bool, 'lancamentos' => array, 'message' => string]
     */
    public function criarLancamentoCartao(int $userId, array $data): array
    {
        try {
            DB::beginTransaction();

            $cartaoId = $data['cartao_credito_id'] ?? null;
            $ehParcelado = (bool)($data['eh_parcelado'] ?? false);
            $totalParcelas = (int)($data['total_parcelas'] ?? 1);

            // Buscar cartão
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->first();

            if (!$cartao) {
                return [
                    'success' => false,
                    'message' => 'Cartão de crédito não encontrado',
                ];
            }

            $lancamentos = [];

            if ($ehParcelado && $totalParcelas >= 2) {
                // Criar lançamento parcelado
                $lancamentos = $this->criarLancamentoParcelado($userId, $data, $cartao);
            } else {
                // Criar lançamento à vista no cartão
                $lancamentos[] = $this->criarLancamentoVista($userId, $data, $cartao);
            }

            DB::commit();

            return [
                'success' => true,
                'lancamentos' => $lancamentos,
                'total_criados' => count($lancamentos),
                'message' => $ehParcelado
                    ? "Compra parcelada em {$totalParcelas}x criada com sucesso"
                    : 'Compra no cartão criada com sucesso',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Erro ao criar lançamento com cartão: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao criar lançamento: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Criar lançamento à vista no cartão
     */
    private function criarLancamentoVista(int $userId, array $data, CartaoCredito $cartao): Lancamento
    {
        $dataCompra = $data['data'] ?? date('Y-m-d');
        $dataVencimento = $this->calcularDataVencimento($dataCompra, $cartao->dia_vencimento);

        $lancamento = Lancamento::create([
            'user_id' => $userId,
            'tipo' => 'despesa',
            'conta_id' => null, // Não debita da conta imediatamente
            'cartao_credito_id' => $cartao->id,
            'categoria_id' => $data['categoria_id'] ?? null,
            'valor' => $data['valor'],
            'data' => $dataVencimento, // Data de vencimento da fatura
            'descricao' => $data['descricao'],
            'observacao' => ($data['observacao'] ?? '') . "\nCompra no cartão {$cartao->nome_cartao}",
            'eh_parcelado' => false,
            'parcela_atual' => null,
            'total_parcelas' => null,
            'lancamento_pai_id' => null,
        ]);

        // Atualizar limite disponível do cartão
        $this->atualizarLimiteCartao($cartao->id, $data['valor'], 'debito');

        return $lancamento;
    }

    /**
     * Criar lançamento parcelado
     */
    private function criarLancamentoParcelado(int $userId, array $data, CartaoCredito $cartao): array
    {
        $lancamentos = [];
        $valorTotal = $data['valor'];
        $totalParcelas = (int)$data['total_parcelas'];
        $valorParcela = round($valorTotal / $totalParcelas, 2);

        // Ajustar última parcela para compensar arredondamento
        $somaParcelasAnteriores = $valorParcela * ($totalParcelas - 1);
        $valorUltimaParcela = round($valorTotal - $somaParcelasAnteriores, 2);

        $dataCompra = $data['data'] ?? date('Y-m-d');

        // Criar PARCELAMENTO (cabeçalho auxiliar)
        $parcelamento = \Application\Models\Parcelamento::create([
            'user_id' => $userId,
            'descricao' => $data['descricao'],
            'valor_total' => $valorTotal,
            'numero_parcelas' => $totalParcelas,
            'parcelas_pagas' => 0,
            'categoria_id' => $data['categoria_id'] ?? null,
            'conta_id' => null,
            'cartao_credito_id' => $cartao->id,
            'tipo' => 'saida',
            'status' => 'ativo',
            'data_criacao' => $dataCompra,
        ]);

        // Criar cada parcela
        for ($i = 1; $i <= $totalParcelas; $i++) {
            $dataVencimentoParcela = $this->calcularDataParcelaMes($dataCompra, $cartao->dia_vencimento, $i - 1);
            $valorDessaParcela = ($i === $totalParcelas) ? $valorUltimaParcela : $valorParcela;

            $parcela = Lancamento::create([
                'user_id' => $userId,
                'tipo' => 'despesa',
                'conta_id' => null, // Não debita da conta
                'cartao_credito_id' => $cartao->id,
                'categoria_id' => $data['categoria_id'] ?? null,
                'valor' => $valorDessaParcela,
                'data' => $dataVencimentoParcela,
                'descricao' => $data['descricao'] . " ({$i}/{$totalParcelas})",
                'observacao' => "Parcela {$i} de {$totalParcelas}\n" .
                    "Compra parcelada no cartão {$cartao->nome_cartao}",
                'eh_parcelado' => true,
                'parcela_atual' => $i,
                'total_parcelas' => $totalParcelas,
                'parcelamento_id' => $parcelamento->id, // Link com parcelamento
                'numero_parcela' => $i,
                'lancamento_pai_id' => null, // Não usa mais pai
            ]);

            $lancamentos[] = $parcela;

            // Atualizar limite do cartão apenas na primeira parcela
            if ($i === 1) {
                $this->atualizarLimiteCartao($cartao->id, $valorTotal, 'debito');
            }
        }

        return $lancamentos;
    }

    /**
     * Calcular data de vencimento da fatura
     * Se a compra foi antes do dia de fechamento, vence na próxima fatura
     * Se foi depois, vence na fatura seguinte
     */
    private function calcularDataVencimento(string $dataCompra, int $diaVencimento): string
    {
        $dataObj = new \DateTime($dataCompra);
        $mesAtual = (int)$dataObj->format('n');
        $anoAtual = (int)$dataObj->format('Y');
        $diaCompra = (int)$dataObj->format('j');

        // Considerar dia de fechamento = dia vencimento - 5 dias
        $diaFechamento = max(1, $diaVencimento - 5);

        // Se comprou após o fechamento, vai para próximo mês
        if ($diaCompra > $diaFechamento) {
            $mesVencimento = $mesAtual + 1;
            $anoVencimento = $anoAtual;

            if ($mesVencimento > 12) {
                $mesVencimento = 1;
                $anoVencimento++;
            }
        } else {
            // Vence no mês atual
            $mesVencimento = $mesAtual;
            $anoVencimento = $anoAtual;
        }

        // Ajustar dia para o último dia do mês se necessário
        $ultimoDiaMes = (int)date('t', mktime(0, 0, 0, $mesVencimento, 1, $anoVencimento));
        $diaFinal = min($diaVencimento, $ultimoDiaMes);

        return sprintf('%04d-%02d-%02d', $anoVencimento, $mesVencimento, $diaFinal);
    }

    /**
     * Calcular data de vencimento de uma parcela específica
     */
    private function calcularDataParcelaMes(string $dataCompra, int $diaVencimento, int $mesesAFrente): string
    {
        $dataVencimentoPrimeira = $this->calcularDataVencimento($dataCompra, $diaVencimento);

        $dataObj = new \DateTime($dataVencimentoPrimeira);
        $dataObj->modify("+{$mesesAFrente} months");

        // Ajustar para o último dia do mês se necessário
        $mesAlvo = (int)$dataObj->format('n');
        $anoAlvo = (int)$dataObj->format('Y');
        $ultimoDiaMes = (int)date('t', mktime(0, 0, 0, $mesAlvo, 1, $anoAlvo));
        $diaFinal = min($diaVencimento, $ultimoDiaMes);

        return sprintf('%04d-%02d-%02d', $anoAlvo, $mesAlvo, $diaFinal);
    }

    /**
     * Atualizar limite disponível do cartão
     */
    private function atualizarLimiteCartao(int $cartaoId, float $valor, string $operacao): void
    {
        $cartao = CartaoCredito::find($cartaoId);
        if (!$cartao) return;

        if ($operacao === 'debito') {
            $cartao->limite_disponivel = max(0, $cartao->limite_disponivel - $valor);
        } else if ($operacao === 'credito') {
            $cartao->limite_disponivel = min($cartao->limite_total, $cartao->limite_disponivel + $valor);
        }

        $cartao->save();
    }

    /**
     * Cancelar parcelas futuras de um lançamento parcelado
     */
    public function cancelarParcelasFuturas(int $lancamentoId, int $userId): array
    {
        try {
            DB::beginTransaction();

            $lancamento = Lancamento::where('id', $lancamentoId)
                ->where('user_id', $userId)
                ->first();

            if (!$lancamento) {
                return ['success' => false, 'message' => 'Lançamento não encontrado'];
            }

            if (!$lancamento->eh_parcelado) {
                return ['success' => false, 'message' => 'Lançamento não é parcelado'];
            }

            $lancamentoPaiId = $lancamento->lancamento_pai_id ?? $lancamento->id;

            // Buscar e deletar todas as parcelas futuras (com data > hoje)
            $hoje = date('Y-m-d');
            $parcelasFuturas = Lancamento::where('lancamento_pai_id', $lancamentoPaiId)
                ->where('data', '>', $hoje)
                ->get();

            $totalCanceladas = 0;
            foreach ($parcelasFuturas as $parcela) {
                // Devolver limite ao cartão
                if ($parcela->cartao_credito_id) {
                    $this->atualizarLimiteCartao($parcela->cartao_credito_id, $parcela->valor, 'credito');
                }
                $parcela->delete();
                $totalCanceladas++;
            }

            DB::commit();

            return [
                'success' => true,
                'total_canceladas' => $totalCanceladas,
                'message' => "{$totalCanceladas} parcela(s) futura(s) cancelada(s)",
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Erro ao cancelar parcelas: ' . $e->getMessage()];
        }
    }
}
