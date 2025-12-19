<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Repositories\ContaRepository;
use Illuminate\Database\Capsule\Manager as DB;
use ValueError;
use Throwable;

class TransferenciaService
{
    private ContaRepository $contaRepo;

    public function __construct(?ContaRepository $contaRepo = null)
    {
        $this->contaRepo = $contaRepo ?? new ContaRepository();
    }

    /**
     * Executa uma transferência entre duas contas
     * 
     * @param int $userId ID do usuário
     * @param int $contaOrigemId ID da conta de origem
     * @param int $contaDestinoId ID da conta de destino
     * @param float $valor Valor da transferência
     * @param string $data Data da transferência (Y-m-d)
     * @param string|null $descricao Descrição da transferência
     * @param string|null $observacao Observações adicionais
     * @return Lancamento Lançamento de transferência criado
     * @throws ValueError Se as contas forem inválidas
     * @throws Throwable Se houver erro na transação
     */
    public function executarTransferencia(
        int $userId,
        int $contaOrigemId,
        int $contaDestinoId,
        float $valor,
        string $data,
        ?string $descricao = null,
        ?string $observacao = null
    ): Lancamento {
        // Validar IDs
        if ($contaOrigemId <= 0 || $contaDestinoId <= 0 || $contaOrigemId === $contaDestinoId) {
            throw new ValueError('Selecione contas de origem e destino diferentes.');
        }

        // Validar valor
        if ($valor <= 0) {
            throw new ValueError('Valor deve ser maior que zero.');
        }

        DB::beginTransaction();
        try {
            // Validar contas
            $origem = $this->validarConta($contaOrigemId, $userId);
            $destino = $this->validarConta($contaDestinoId, $userId);

            // Criar lançamento de transferência
            $transferencia = Lancamento::create([
                'user_id'           => $userId,
                'tipo'              => Lancamento::TIPO_TRANSFERENCIA,
                'data'              => $data,
                'categoria_id'      => null,
                'conta_id'          => $contaOrigemId,
                'conta_id_destino'  => $contaDestinoId,
                'descricao'         => $descricao ? trim($descricao) : "Transferência: {$origem->nome} → {$destino->nome}",
                'observacao'        => $observacao ? trim($observacao) : null,
                'valor'             => $valor,
                'eh_transferencia'  => 1,
                'eh_saldo_inicial'  => 0,
            ]);

            DB::commit();
            return $transferencia;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Valida se a conta existe e pertence ao usuário
     * 
     * @param int $contaId ID da conta
     * @param int $userId ID do usuário
     * @return Conta Conta validada
     * @throws ValueError Se a conta não existir ou não pertencer ao usuário
     */
    private function validarConta(int $contaId, int $userId): Conta
    {
        $conta = Conta::forUser($userId)->find($contaId);
        
        if (!$conta) {
            throw new ValueError('Conta de origem ou destino inválida.');
        }

        return $conta;
    }

    /**
     * Cancela uma transferência (soft delete)
     * 
     * @param int $transferenciaId ID da transferência
     * @param int $userId ID do usuário
     * @return bool
     * @throws ValueError Se a transferência não existir ou não pertencer ao usuário
     */
    public function cancelarTransferencia(int $transferenciaId, int $userId): bool
    {
        $transferencia = Lancamento::where('id', $transferenciaId)
            ->where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->first();

        if (!$transferencia) {
            throw new ValueError('Transferência não encontrada.');
        }

        return $transferencia->delete();
    }

    /**
     * Lista transferências de um usuário em um período
     * 
     * @param int $userId ID do usuário
     * @param string $inicio Data de início (Y-m-d)
     * @param string $fim Data de fim (Y-m-d)
     * @return \Illuminate\Support\Collection
     */
    public function listarTransferencias(int $userId, string $inicio, string $fim)
    {
        return Lancamento::where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->whereBetween('data', [$inicio, $fim])
            ->with(['conta', 'contaDestino'])
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }
}
