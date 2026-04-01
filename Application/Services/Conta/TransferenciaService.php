<?php

declare(strict_types=1);

namespace Application\Services\Conta;

use Application\Models\Conta;
use Application\Models\Lancamento;
use Application\Repositories\ContaRepository;
use Application\Services\Metas\MetaProgressService;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;
use ValueError;

class TransferenciaService
{
    private ContaRepository $contaRepo;
    private MetaProgressService $metaProgressService;

    public function __construct(
        ?ContaRepository $contaRepo = null,
        ?MetaProgressService $metaProgressService = null
    ) {
        $this->contaRepo = $contaRepo ?? new ContaRepository();
        $this->metaProgressService = $metaProgressService ?? new MetaProgressService();
    }

    /**
     * Executa uma transferencia entre duas contas.
     *
     * @param int $userId ID do usuario
     * @param int $contaOrigemId ID da conta de origem
     * @param int $contaDestinoId ID da conta de destino
     * @param float $valor Valor da transferencia
     * @param string $data Data da transferencia (Y-m-d)
     * @param string|null $descricao Descricao da transferencia
     * @param string|null $observacao Observacoes adicionais
     * @param int|null $metaId Meta opcional para acumulo planejado
     * @param float|null $metaValor Valor parcial opcional da transferencia para meta
     * @return Lancamento Lancamento de transferencia criado
     * @throws ValueError Se as contas forem invalidas
     * @throws Throwable Se houver erro na transacao
     */
    public function executarTransferencia(
        int $userId,
        int $contaOrigemId,
        int $contaDestinoId,
        float $valor,
        string $data,
        ?string $descricao = null,
        ?string $observacao = null,
        ?int $metaId = null,
        ?float $metaValor = null
    ): Lancamento {
        if ($contaOrigemId <= 0 || $contaDestinoId <= 0 || $contaOrigemId === $contaDestinoId) {
            throw new ValueError('Selecione contas de origem e destino diferentes.');
        }

        if ($valor <= 0) {
            throw new ValueError('Valor deve ser maior que zero.');
        }

        DB::beginTransaction();

        try {
            $origem = $this->validarConta($contaOrigemId, $userId);
            $destino = $this->validarConta($contaDestinoId, $userId);

            $metaValorAplicado = null;
            if ($metaId !== null && $metaId > 0) {
                $metaValorAplicado = $metaValor ?? $valor;
                $metaValorAplicado = round(max(0, min($valor, (float) $metaValorAplicado)), 2);
                if ($metaValorAplicado <= 0) {
                    $metaValorAplicado = round($valor, 2);
                }
            }

            $transferencia = Lancamento::create([
                'user_id' => $userId,
                'tipo' => Lancamento::TIPO_TRANSFERENCIA,
                'data' => $data,
                'categoria_id' => null,
                'meta_id' => $metaId,
                'meta_operacao' => $metaId ? Lancamento::META_OPERACAO_APORTE : null,
                'meta_valor' => $metaId ? $metaValorAplicado : null,
                'conta_id' => $contaOrigemId,
                'conta_id_destino' => $contaDestinoId,
                'descricao' => $descricao ? trim($descricao) : "Transferencia: {$origem->nome} -> {$destino->nome}",
                'observacao' => $observacao ? trim($observacao) : null,
                'valor' => $valor,
                'eh_transferencia' => 1,
                'eh_saldo_inicial' => 0,
                'pago' => 1,
                'afeta_caixa' => 1,
                'origem_tipo' => Lancamento::ORIGEM_TRANSFERENCIA,
            ]);

            if ($metaId !== null && $metaId > 0) {
                $this->metaProgressService->recalculateMeta($userId, $metaId, true);
            }

            DB::commit();

            return $transferencia;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Valida se a conta existe e pertence ao usuario.
     *
     * @param int $contaId ID da conta
     * @param int $userId ID do usuario
     * @return Conta Conta validada
     * @throws ValueError Se a conta nao existir ou nao pertencer ao usuario
     */
    private function validarConta(int $contaId, int $userId): Conta
    {
        $conta = Conta::forUser($userId)->find($contaId);

        if (!$conta) {
            throw new ValueError('Conta de origem ou destino invalida.');
        }

        return $conta;
    }

    /**
     * Cancela uma transferencia (soft delete).
     *
     * @param int $transferenciaId ID da transferencia
     * @param int $userId ID do usuario
     * @return bool
     * @throws ValueError Se a transferencia nao existir ou nao pertencer ao usuario
     */
    public function cancelarTransferencia(int $transferenciaId, int $userId): bool
    {
        $transferencia = Lancamento::where('id', $transferenciaId)
            ->where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->first();

        if (!$transferencia) {
            throw new ValueError('Transferencia nao encontrada.');
        }

        $metaId = (int) ($transferencia->meta_id ?? 0);
        $deleted = $transferencia->delete();

        if ($deleted && $metaId > 0) {
            $this->metaProgressService->recalculateMeta($userId, $metaId);
        }

        return $deleted;
    }

    /**
     * Lista transferencias de um usuario em um periodo.
     *
     * @param int $userId ID do usuario
     * @param string $inicio Data de inicio (Y-m-d)
     * @param string $fim Data de fim (Y-m-d)
     * @return \Illuminate\Support\Collection
     */
    public function listarTransferencias(int $userId, string $inicio, string $fim)
    {
        return Lancamento::where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->whereBetween('data', [$inicio, $fim])
            ->with(['conta', 'contaDestino', 'meta'])
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }
}
