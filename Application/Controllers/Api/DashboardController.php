<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Application\Models\Conta;

class DashboardController
{
    public function metrics(): void
    {
        try {
            $userId = Auth::id();
            $month  = trim($_GET['month'] ?? date('Y-m'));
            $accId  = isset($_GET['account_id']) ? (int)$_GET['account_id'] : null;

            // valida mês
            $dt = \DateTime::createFromFormat('Y-m', $month);
            if (!$dt || $dt->format('Y-m') !== $month) {
                $month = date('Y-m');
                $dt = new \DateTime("$month-01");
            }
            $y = (int)$dt->format('Y');
            $m = (int)$dt->format('m');

            // receitas/despesas do mês (ignora transferências e saldo inicial)
            $base = Lancamento::where('user_id', $userId)
                ->whereYear('data', $y)
                ->whereMonth('data', $m);

            $receitas = (clone $base)
                ->where('tipo', 'receita')
                ->where('eh_transferencia', 0)
                ->where('eh_saldo_inicial', 0);

            $despesas = (clone $base)
                ->where('tipo', 'despesa')
                ->where('eh_transferencia', 0)
                ->where('eh_saldo_inicial', 0);

            if ($accId) {
                $exists = Conta::where('user_id', $userId)->where('id', $accId)->exists();
                if (!$exists) {
                    Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
                    return;
                }
                $receitas->where('conta_id', $accId);
                $despesas->where('conta_id', $accId);
            }

            $sumReceitas = (float)$receitas->sum('valor');
            $sumDespesas = (float)$despesas->sum('valor');
            $resultado   = $sumReceitas - $sumDespesas;

            $ate = (new \DateTimeImmutable("$month-01"))->modify('last day of this month')->format('Y-m-d');

            if ($accId) {
                $movBase = Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 0)
                    ->where('conta_id', $accId);

                $movReceitas = (float)(clone $movBase)->where('tipo', 'receita')->sum('valor');
                $movDespesas = (float)(clone $movBase)->where('tipo', 'despesa')->sum('valor');

                $transfIn  = (float)Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 1)
                    ->where('conta_id_destino', $accId)
                    ->sum('valor');

                $transfOut = (float)Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 1)
                    ->where('conta_id', $accId)
                    ->sum('valor');

                $saldo = $movReceitas - $movDespesas + $transfIn - $transfOut;
                $saldoAcumulado = $saldo;
            } else {
                $movGlobal = Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 0);

                $r = (float)(clone $movGlobal)->where('tipo', 'receita')->sum('valor');
                $d = (float)(clone $movGlobal)->where('tipo', 'despesa')->sum('valor');

                $saldo = $r - $d;
                $saldoAcumulado = $saldo;
            }

            Response::json([
                'saldo'          => $saldo,
                'receitas'       => $sumReceitas,
                'despesas'       => $sumDespesas,
                'resultado'      => $resultado,
                'saldoAcumulado' => $saldoAcumulado,
            ]);
        } catch (\Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}