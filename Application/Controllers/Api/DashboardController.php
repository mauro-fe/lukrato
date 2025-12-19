<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Enums\LancamentoTipo;
use Illuminate\Database\Eloquent\Builder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardController
{

    private function normalizeMonth(string $monthInput): array
    {
        $dt = \DateTime::createFromFormat('Y-m', $monthInput);

        if (!$dt || $dt->format('Y-m') !== $monthInput) {
            $month = date('Y-m');
            $dt = new \DateTime("$month-01");
        } else {
            $month = $dt->format('Y-m');
        }

        return [
            'month' => $month,
            'year' => (int)$dt->format('Y'),
            'monthNum' => (int)$dt->format('m'),
        ];
    }

    private function createBaseQuery(int $userId): Builder
    {
        return Lancamento::where('user_id', $userId)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 0);
    }


    public function metrics(): void
    {
        try {
            $userId = Auth::id();

            $monthInput = trim($_GET['month'] ?? date('Y-m'));
            $accId      = isset($_GET['account_id']) ? (int)$_GET['account_id'] : null;

            $normalizedDate = $this->normalizeMonth($monthInput);
            $y = $normalizedDate['year'];
            $m = $normalizedDate['monthNum'];
            $month = $normalizedDate['month'];

            if ($accId) {
                if (!Conta::where('user_id', $userId)->where('id', $accId)->exists()) {
                    Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
                    return;
                }
            }


            $monthlyBase = $this->createBaseQuery($userId)
                ->whereYear('data', $y)
                ->whereMonth('data', $m);

            if ($accId) {
                $monthlyBase->where('conta_id', $accId);
            }

            $receitasQuery = (clone $monthlyBase)->where('tipo', LancamentoTipo::RECEITA->value);
            $despesasQuery = (clone $monthlyBase)->where('tipo', LancamentoTipo::DESPESA->value);

            $sumReceitas = (float)$receitasQuery->sum('valor');
            $sumDespesas = (float)$despesasQuery->sum('valor');
            $resultado   = $sumReceitas - $sumDespesas;


            $ate = (new DateTimeImmutable("$month-01"))
                ->modify('last day of this month')
                ->format('Y-m-d');

            $saldoAcumulado = 0.0;

            if ($accId) {

                $movBaseAcumulado = Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('conta_id', $accId);

                $movReceitas = (float)(clone $movBaseAcumulado)
                    ->where('eh_transferencia', 0)
                    ->where('tipo', LancamentoTipo::RECEITA->value)
                    ->sum('valor');

                $movDespesas = (float)(clone $movBaseAcumulado)
                    ->where('eh_transferencia', 0)
                    ->where('tipo', LancamentoTipo::DESPESA->value)
                    ->sum('valor');

                $transfIn = (float)Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 1)
                    ->where('conta_id_destino', $accId)
                    ->sum('valor');

                $transfOut = (float)Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 1)
                    ->where('conta_id', $accId)
                    ->sum('valor');

                $saldoAcumulado = $movReceitas - $movDespesas + $transfIn - $transfOut;
            } else {

                $movGlobal = Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 0);

                $r = (float)(clone $movGlobal)
                    ->where('tipo', LancamentoTipo::RECEITA->value)
                    ->sum('valor');

                $d = (float)(clone $movGlobal)
                    ->where('tipo', LancamentoTipo::DESPESA->value)
                    ->sum('valor');

                $saldoAcumulado = $r - $d;
            }

            Response::json([
                'saldo'          => $saldoAcumulado,
                'receitas'       => $sumReceitas,
                'despesas'       => $sumDespesas,
                'resultado'      => $resultado,
                'saldoAcumulado' => $saldoAcumulado,
            ]);
        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function transactions(): void
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                Response::json(['status' => 'error', 'message' => 'Nao autenticado'], 401);
                return;
            }

            $monthInput = trim($_GET['month'] ?? date('Y-m'));
            $limit = min((int)($_GET['limit'] ?? 5), 100);

            $normalized = $this->normalizeMonth($monthInput);
            $month = $normalized['month'];
            [$y, $m] = array_map('intval', explode('-', $month));
            $from = sprintf('%04d-%02d-01', $y, $m);
            $to   = date('Y-m-t', strtotime($from));

            $rows = DB::table('lancamentos as l')
                ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
                ->leftJoin('contas as a', 'a.id', '=', 'l.conta_id')
                ->where('l.user_id', $userId)
                ->whereBetween('l.data', [$from, $to])
                ->orderBy('l.data', 'desc')
                ->orderBy('l.id', 'desc')
                ->limit($limit)
                ->selectRaw('
                    l.id, l.data, l.tipo, l.valor, l.descricao,
                    l.categoria_id, l.conta_id,
                    COALESCE(c.nome, "") as categoria,
                    COALESCE(a.nome, a.instituicao, "") as conta
                ')
                ->get();

            $out = $rows->map(fn($r) => [
                'id' => (int)$r->id,
                'data' => (string)$r->data,
                'tipo' => (string)$r->tipo,
                'valor' => (float)$r->valor,
                'descricao' => (string)($r->descricao ?? ''),
                'categoria_id' => (int)$r->categoria_id ?: null,
                'conta_id' => (int)$r->conta_id ?: null,
                'categoria' => (string)$r->categoria,
                'conta' => (string)$r->conta,
            ])->values()->all();

            Response::json($out);
        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}