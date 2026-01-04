<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Application\Models\Parcelamento;
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

            // Determinar conta_id (usar do data ou do cartão)
            $contaId = $data['conta_id'] ?? $cartao->conta_id;

            LogService::info("[CARTAO] Dados recebidos", [
                'conta_id_data' => $data['conta_id'] ?? 'null',
                'conta_id_cartao' => $cartao->conta_id ?? 'null',
                'conta_id_final' => $contaId ?? 'null',
                'cartao_id' => $cartaoId,
                'user_id' => $userId
            ]);

            $lancamentos = [];

            if ($ehParcelado && $totalParcelas >= 2) {
                // Criar lançamento parcelado
                $lancamentos = $this->criarLancamentoParcelado($userId, $data, $cartao, $contaId);
            } else {
                // Criar lançamento à vista no cartão
                $lancamentos[] = $this->criarLancamentoVista($userId, $data, $cartao, $contaId);
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
            LogService::error("Erro ao criar lançamento com cartão", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao criar lançamento: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Criar lançamento à vista no cartão
     */
    private function criarLancamentoVista(int $userId, array $data, CartaoCredito $cartao, ?int $contaId): Lancamento
    {
        $dataCompra = $data['data'] ?? date('Y-m-d');
        $dataVencimento = $this->calcularDataVencimento($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento);

        $lancamento = Lancamento::create([
            'user_id' => $userId,
            'tipo' => 'despesa',
            'conta_id' => $contaId, // Conta vinculada ao cartão
            'cartao_credito_id' => $cartao->id,
            'categoria_id' => $data['categoria_id'],
            'valor' => $data['valor'],
            'data' => $dataVencimento, // Data de vencimento da fatura
            'descricao' => $data['descricao'],
            'observacao' => ($data['observacao'] ?? '') . "\nCompra no cartão {$cartao->nome_cartao}",
            'eh_parcelado' => false,
            'parcela_atual' => null,
            'total_parcelas' => null,
        ]);

        // Atualizar limite disponível do cartão
        $this->atualizarLimiteCartao($cartao->id, $data['valor'], 'debito');

        return $lancamento;
    }

    /**
     * Criar lançamento parcelado
     */
    private function criarLancamentoParcelado(int $userId, array $data, CartaoCredito $cartao, ?int $contaId): array
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
        $parcelamento = Parcelamento::create([
            'user_id' => $userId,
            'descricao' => $data['descricao'],
            'valor_total' => $valorTotal,
            'numero_parcelas' => $totalParcelas,
            'parcelas_pagas' => 0,
            'categoria_id' => $data['categoria_id'] ?? null,
            'conta_id' => $contaId, // Conta vinculada ao cartão
            'cartao_credito_id' => $cartao->id,
            'tipo' => 'saida',
            'status' => 'ativo',
            'data_criacao' => $dataCompra,
        ]);

        // Criar cada parcela
        for ($i = 1; $i <= $totalParcelas; $i++) {
            $dataVencimentoParcela = $this->calcularDataParcelaMes($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento, $i - 1);
            $valorDessaParcela = ($i === $totalParcelas) ? $valorUltimaParcela : $valorParcela;

            LogService::info("[CARTAO] Criando parcela {$i}/{$totalParcelas}", [
                'conta_id' => $contaId,
                'cartao_id' => $cartao->id,
                'valor' => $valorDessaParcela
            ]);

            $parcela = Lancamento::create([
                'user_id' => $userId,
                'tipo' => 'despesa',
                'conta_id' => $contaId, // Conta vinculada ao cartão
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
            ]);

            LogService::info("[CARTAO] Parcela criada no banco", [
                'parcela_id' => $parcela->id,
                'conta_id_salvo' => $parcela->conta_id,
                'conta_id_esperado' => $contaId
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
    private function calcularDataVencimento(string $dataCompra, int $diaVencimento, ?int $diaFechamento = null): string
    {
        $dataObj = new \DateTime($dataCompra);
        $mesAtual = (int)$dataObj->format('n');
        $anoAtual = (int)$dataObj->format('Y');
        $diaCompra = (int)$dataObj->format('j');

        // Se não informou dia de fechamento, considerar 5 dias antes do vencimento
        if ($diaFechamento === null) {
            $diaFechamento = max(1, $diaVencimento - 5);
        }

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
    private function calcularDataParcelaMes(string $dataCompra, int $diaVencimento, ?int $diaFechamento, int $mesesAFrente): string
    {
        $dataVencimentoPrimeira = $this->calcularDataVencimento($dataCompra, $diaVencimento, $diaFechamento);

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
                    'message' => 'Parcelamento não encontrado'
                ];
            }

            if ($parcelamento->status !== 'ativo') {
                return [
                    'success' => false,
                    'message' => 'Parcelamento não está ativo'
                ];
            }

            $hoje = date('Y-m-d');

            // Buscar parcelas FUTURAS (não vencidas)
            $parcelasFuturas = Lancamento::where('parcelamento_id', $parcelamento->id)
                ->where('data', '>', $hoje)
                ->get();

            if ($parcelasFuturas->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Não existem parcelas futuras para cancelar'
                ];
            }

            // Calcular valor total a devolver ao limite
            $valorDevolver = $parcelasFuturas->sum('valor');

            // Devolver limite do cartão (uma vez só)
            if ($parcelamento->cartao_credito_id) {
                $this->atualizarLimiteCartao(
                    $parcelamento->cartao_credito_id,
                    $valorDevolver,
                    'credito'
                );
            }

            // Cancelar parcelas futuras (soft delete recomendado)
            foreach ($parcelasFuturas as $parcela) {
                $parcela->status = 'cancelado'; // ou usar soft delete
                $parcela->save();
            }

            // Atualizar status do parcelamento
            $parcelamento->status = 'parcial';
            $parcelamento->save();

            DB::commit();

            return [
                'success' => true,
                'parcelamento_id' => $parcelamento->id,
                'parcelas_canceladas' => $parcelasFuturas->count(),
                'valor_devolvido' => $valorDevolver,
                'message' => 'Parcelamento cancelado parcialmente com sucesso'
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            error_log('[CARTAO] Erro ao cancelar parcelamento: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao cancelar parcelamento'
            ];
        }
    }
}
