<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Enums\LancamentoTipo;
use DateTimeImmutable;
use DateTime;

/**
 * Serviço responsável por calcular saldos e movimentações de contas.
 */
class ContaBalanceService
{
    private int $userId;
    private array $accountIds;
    private string $endDate;

    /**
     * @param int $userId ID do usuário
     * @param array $accountIds IDs das contas
     * @param string $month Mês no formato Y-m (ex: 2025-12)
     */
    public function __construct(int $userId, array $accountIds, string $month)
    {
        $this->userId = $userId;
        $this->accountIds = $accountIds;
        $this->endDate = $this->calculateEndDate($month);
    }

    /**
     * Calcula a data final do mês.
     */
    private function calculateEndDate(string $month): string
    {
        $dt = DateTime::createFromFormat('Y-m', $month);
        if (!$dt || $dt->format('Y-m') !== $month) {
            $dt = new DateTime(date('Y-m') . '-01');
        }

        return (new DateTimeImmutable($dt->format('Y-m-01')))
            ->modify('last day of this month')
            ->format('Y-m-d');
    }

    /**
     * Retorna os saldos iniciais de cada conta.
     * ATUALIZADO: Busca do campo contas.saldo_inicial
     * 
     * @return array Array associativo [conta_id => saldo_inicial]
     */
    public function getInitialBalances(): array
    {
        if (empty($this->accountIds)) {
            return [];
        }

        return Conta::whereIn('id', $this->accountIds)
            ->pluck('saldo_inicial', 'id')
            ->all();
    }

    /**
     * Calcula os saldos finais e totais de movimentação.
     * 
     * @param array $initialBalances Saldos iniciais das contas
     * @return array Array com saldoAtual, entradasTotal, saidasTotal e saldoInicial por conta
     */
    public function calculateFinalBalances(array $initialBalances): array
    {
        if (empty($this->accountIds)) {
            return [];
        }

        $receitas = $this->getReceitas();
        $despesas = $this->getDespesas();
        $transferenciasIn = $this->getTransferenciasIn();
        $transferenciasOut = $this->getTransferenciasOut();

        return $this->aggregateBalances($initialBalances, $receitas, $despesas, $transferenciasIn, $transferenciasOut);
    }

    /**
     * Obtém total de receitas por conta.
     */
    private function getReceitas(): array
    {
        return Lancamento::where('user_id', $this->userId)
            ->whereIn('conta_id', $this->accountIds)
            ->where('eh_transferencia', 0)
            ->where('data', '<=', $this->endDate)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->selectRaw('conta_id, SUM(valor) as tot')
            ->groupBy('conta_id')
            ->pluck('tot', 'conta_id')
            ->all();
    }

    /**
     * Obtém total de despesas por conta.
     */
    private function getDespesas(): array
    {
        return Lancamento::where('user_id', $this->userId)
            ->whereIn('conta_id', $this->accountIds)
            ->where('eh_transferencia', 0)
            ->where('data', '<=', $this->endDate)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->selectRaw('conta_id, SUM(valor) as tot')
            ->groupBy('conta_id')
            ->pluck('tot', 'conta_id')
            ->all();
    }

    /**
     * Obtém total de transferências recebidas por conta.
     */
    private function getTransferenciasIn(): array
    {
        return Lancamento::where('user_id', $this->userId)
            ->whereIn('conta_id_destino', $this->accountIds)
            ->where('eh_transferencia', 1)
            ->where('data', '<=', $this->endDate)
            ->selectRaw('conta_id_destino as cid, SUM(valor) as tot')
            ->groupBy('cid')
            ->pluck('tot', 'cid')
            ->all();
    }

    /**
     * Obtém total de transferências enviadas por conta.
     */
    private function getTransferenciasOut(): array
    {
        return Lancamento::where('user_id', $this->userId)
            ->whereIn('conta_id', $this->accountIds)
            ->where('eh_transferencia', 1)
            ->where('data', '<=', $this->endDate)
            ->selectRaw('conta_id as cid, SUM(valor) as tot')
            ->groupBy('cid')
            ->pluck('tot', 'cid')
            ->all();
    }

    /**
     * Agrega todos os valores e calcula saldos finais.
     */
    private function aggregateBalances(
        array $initialBalances,
        array $receitas,
        array $despesas,
        array $transferenciasIn,
        array $transferenciasOut
    ): array {
        $result = [];

        foreach ($this->accountIds as $contaId) {
            $saldoInicial = (float)($initialBalances[$contaId] ?? 0);
            $receitasTotal = (float)($receitas[$contaId] ?? 0);
            $despesasTotal = (float)($despesas[$contaId] ?? 0);
            $transIn = (float)($transferenciasIn[$contaId] ?? 0);
            $transOut = (float)($transferenciasOut[$contaId] ?? 0);

            $saldoAtual = $saldoInicial + $receitasTotal - $despesasTotal + $transIn - $transOut;

            $result[$contaId] = [
                'saldoAtual'    => round($saldoAtual, 2),
                'entradasTotal' => round($receitasTotal + $transIn, 2),
                'saidasTotal'   => round($despesasTotal + $transOut, 2),
                'saldoInicial'  => round($saldoInicial, 2),
            ];
        }

        return $result;
    }
}
