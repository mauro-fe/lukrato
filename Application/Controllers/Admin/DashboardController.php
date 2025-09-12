<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Lib\Auth;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Services\LogService;
use Application\Core\Response;

class DashboardController extends BaseController
{
    /**
     * View do dashboard. Quem busca dados é o JS via /api (abaixo).
     */
    public function dashboard()
    {
        try {
            $this->render(
                'dashboard/index',
                [
                    'pageTitle' => 'Dashboard',
                    'username'  => Auth::user()->username,
                    'menu'      => 'dashboard',
                ],
                'admin/home/header',
                null
            );
        } catch (\Throwable $e) {
            $this->handleDashboardError($e);
        }
    }

    /**
     * GET /api/dashboard/metrics?month=YYYY-MM&account_id=ID (opcional)
     * Retorna: saldo, receitas, despesas, resultado, saldoAcumulado
     *
     * Regras:
     * - receitas/despesas ignoram transferências;
     * - quando filtra por conta, saldo considera:
     *   saldo_inicial + receitas - despesas + transf_in - transf_out (até o fim do mês).
     */
    public function apiMetrics(): void
    {
        try {
            $userId = Auth::id();
            $month  = trim($_GET['month'] ?? date('Y-m'));
            $accId  = isset($_GET['account_id']) ? (int)$_GET['account_id'] : null;

            // valida mês (YYYY-MM)
            $dt = \DateTime::createFromFormat('Y-m', $month);
            if (!$dt || $dt->format('Y-m') !== $month) {
                $month = date('Y-m');
                $dt = new \DateTime("$month-01");
            }
            $y = (int)$dt->format('Y');
            $m = (int)$dt->format('m');

            // filtros base (mês/ano)
            $base = Lancamento::where('user_id', $userId)
                ->whereYear('data', $y)
                ->whereMonth('data', $m);

            // receitas/despesas do mês (ignora transferências)
            $receitas = (clone $base)->where('tipo', 'receita')->where('eh_transferencia', 0);
            $despesas = (clone $base)->where('tipo', 'despesa')->where('eh_transferencia', 0);

            if ($accId) {
                // garante ownership da conta ANTES de consultar
                $exists = Conta::where('user_id', $userId)->where('id', $accId)->exists();
                if (!$exists) {
                    $this->json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
                }
                $receitas->where('conta_id', $accId);
                $despesas->where('conta_id', $accId);
            }

            $sumReceitas = (float)$receitas->sum('valor');
            $sumDespesas = (float)$despesas->sum('valor');
            $resultado   = $sumReceitas - $sumDespesas;

            // Saldo:
            // - sem conta: saldo "global" = receitas-acum - despesas-acum (ignora transferências)
            // - com conta: saldo_inicial + movimentos + transfer in/out até o fim do mês
            $ate = (new \DateTimeImmutable("$month-01"))->modify('last day of this month')->format('Y-m-d');

            if ($accId) {
                $conta = Conta::where('user_id', $userId)->where('id', $accId)->first();
                if (!$conta) {
                    $this->json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
                }
                $saldoInicial = (float)$conta->saldo_inicial;

                $movBase = Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 0)
                    ->where('conta_id', $accId);

                $movReceitas = (float)(clone $movBase)->where('tipo', 'receita')->sum('valor');
                $movDespesas = (float)(clone $movBase)->where('tipo', 'despesa')->sum('valor');

                // transferências envolvendo a conta
                $transfIn  = (float)Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 1)
                    ->where('conta_destino_id', $accId)
                    ->sum('valor');

                $transfOut = (float)Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 1)
                    ->where('conta_id', $accId)
                    ->sum('valor');

                $saldo = $saldoInicial + $movReceitas - $movDespesas + $transfIn - $transfOut;
                $saldoAcumulado = $saldo;
            } else {
                // Global acumulado até fim do mês (ignora transferências)
                $movGlobal = Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 0);

                $r = (float)(clone $movGlobal)->where('tipo', 'receita')->sum('valor');
                $d = (float)(clone $movGlobal)->where('tipo', 'despesa')->sum('valor');

                $saldo = $r - $d;
                $saldoAcumulado = $saldo;
            }

            $this->json([
                'saldo'          => $saldo,
                'receitas'       => $sumReceitas,
                'despesas'       => $sumDespesas,
                'resultado'      => $resultado,
                'saldoAcumulado' => $saldoAcumulado,
            ]);
        } catch (\Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/dashboard/transactions?month=YYYY-MM&limit=N&account_id=ID (opcional)
     * Lista últimos lançamentos do mês (p/ tabela do dashboard).
     */
    public function apiTransactions(): void
    {
        try {
            $userId = Auth::id();
            $month  = trim($_GET['month'] ?? date('Y-m'));
            $accId  = isset($_GET['account_id']) ? (int)$_GET['account_id'] : null;

            // clamp do limit: 1..100
            $limit  = (int)($_GET['limit'] ?? 50);
            $limit  = max(1, min($limit, 100));

            // valida mês
            $dt = \DateTime::createFromFormat('Y-m', $month);
            if (!$dt || $dt->format('Y-m') !== $month) {
                $month = date('Y-m');
                $dt = new \DateTime("$month-01");
            }
            $y = (int)$dt->format('Y');
            $m = (int)$dt->format('m');

            // base query
            $q = Lancamento::with(['categoria', 'conta', 'contaDestino'])
                ->where('user_id', $userId)
                ->whereYear('data', $y)
                ->whereMonth('data', $m)
                ->orderBy('data', 'desc')
                ->orderBy('id', 'desc');

            if ($accId) {
                // verifica ownership antes
                $exists = Conta::where('user_id', $userId)->where('id', $accId)->exists();
                if (!$exists) {
                    $this->json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
                }

                // mostra lançamentos da conta e transferências onde a conta participa
                $q->where(function ($w) use ($accId) {
                    $w->where('conta_id', $accId)
                      ->orWhere('conta_destino_id', $accId);
                });
            }

            $rows = $q->limit($limit)->get();

            $this->json($rows->map(function ($t) {
                return [
                    'id'               => (int)$t->id,
                    'data'             => $t->data,
                    'tipo'             => $t->tipo,
                    'valor'            => (float)$t->valor,
                    'descricao'        => $t->descricao,
                    'observacao'       => $t->observacao,
                    'eh_transferencia' => (int)$t->eh_transferencia === 1,
                    'categoria'        => $t->categoria ? [
                        'id'   => (int)$t->categoria->id,
                        'nome' => $t->categoria->nome
                    ] : null,
                    'conta'            => $t->conta ? [
                        'id'   => (int)$t->conta->id,
                        'nome' => $t->conta->nome
                    ] : null,
                    'conta_destino'    => $t->contaDestino ? [
                        'id'   => (int)$t->contaDestino->id,
                        'nome' => $t->contaDestino->nome
                    ] : null,
                ];
            })->all());
        } catch (\Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function handleDashboardError(\Throwable $e): void
    {
        LogService::critical('Erro ao carregar o dashboard', ['erro' => $e->getMessage()]);

        $this->render(
            'errors/500',
            ['pageTitle' => 'Erro Interno'],
            'admin/home/header',
            null
        );
    }

    // ===== Helpers de resposta JSON =====
    protected function json(array $data, int $statusCode = 200): void
    {
        Response::json($data, $statusCode);
        exit;
    }

    private function jsonError(string $message, int $code = 400, array $errors = []): void
    {
        Response::error($message, $code, $errors); // já envia e finaliza
    }

    protected function jsonSuccess(string $message, array $data = []): void
    {
        Response::success($data, $message); // já envia e finaliza
    }
}
