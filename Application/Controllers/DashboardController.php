<?php

namespace Application\Controllers;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Lib\Helpers;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Pega o mês da query string ou usa o atual
        $mesParam = $request->get('mes') ?: Carbon::now()->format('Y-m');

        // Sanitiza o parâmetro de mês
        if (!preg_match('/^\d{4}-\d{2}$/', $mesParam)) {
            $mesParam = Carbon::now()->format('Y-m');
        }

        try {
            $mesAtual = Carbon::createFromFormat('Y-m', $mesParam);
        } catch (\Exception $e) {
            $mesAtual = Carbon::now();
        }

        $inicioMes = $mesAtual->copy()->startOfMonth();
        $fimMes = $mesAtual->copy()->endOfMonth();

        // Pega o usuário autenticado (assumindo que está na sessão)
        $userId = $_SESSION['user_id'] ?? 1; // Fallback para desenvolvimento

        // KPIs do mês
        $receitasMes = Lancamento::where('user_id', $userId)
            ->where('tipo', 'receita')
            ->whereBetween('data', [$inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d')])
            ->sum('valor') ?? 0;

        $despesasMes = Lancamento::where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->whereBetween('data', [$inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d')])
            ->sum('valor') ?? 0;

        // Saldo total = (receitas pagas - despesas pagas) + soma dos saldos iniciais das contas
        $receitasTotalPagas = Lancamento::where('user_id', $userId)
            ->where('tipo', 'receita')
            ->where('status', 'pago')
            ->where('data', '<=', Carbon::now()->format('Y-m-d'))
            ->sum('valor') ?? 0;

        $despesasTotalPagas = Lancamento::where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->where('status', 'pago')
            ->where('data', '<=', Carbon::now()->format('Y-m-d'))
            ->sum('valor') ?? 0;

        $saldoInicialContas = Conta::where('user_id', $userId)
            ->sum('saldo_inicial') ?? 0;

        $saldoTotal = ($receitasTotalPagas - $despesasTotalPagas) + $saldoInicialContas;

        // Últimos 10 lançamentos
        $ultimos = Lancamento::where('user_id', $userId)
            ->with(['conta', 'categoria'])
            ->orderBy('data', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Dados para o gráfico - últimos 6 meses
        $labels = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i);
            $inicioMesGrafico = $mes->copy()->startOfMonth();
            $fimMesGrafico = $mes->copy()->endOfMonth();

            $receitasMesGrafico = Lancamento::where('user_id', $userId)
                ->where('tipo', 'receita')
                ->where('status', 'pago')
                ->whereBetween('data', [$inicioMesGrafico->format('Y-m-d'), $fimMesGrafico->format('Y-m-d')])
                ->sum('valor') ?? 0;

            $despesasMesGrafico = Lancamento::where('user_id', $userId)
                ->where('tipo', 'despesa')
                ->where('status', 'pago')
                ->whereBetween('data', [$inicioMesGrafico->format('Y-m-d'), $fimMesGrafico->format('Y-m-d')])
                ->sum('valor') ?? 0;

            $labels[] = $mes->format('M/y');
            $data[] = $receitasMesGrafico - $despesasMesGrafico;
        }

        // Helpers de formatação
        $fmt = fn($valor) => Helpers::formatMoneyBRL((float)($valor ?? 0));
        $fmtDate = function ($date) {
            if ($date instanceof \DateTimeInterface) return $date->format('d/m/Y');
            if (!$date) return '—';
            try {
                return Carbon::parse($date)->format('d/m/Y');
            } catch (\Throwable $e) {
                return '—';
            }
        };

        // Renderiza a view
        $this->view('dashboard', compact(
            'receitasMes',
            'despesasMes',
            'saldoTotal',
            'ultimos',
            'labels',
            'data',
            'fmt',
            'fmtDate',
            'mesParam'
        ));
    }

    public function metrics(Request $request)
    {
        header('Content-Type: application/json');

        // Pega o mês da query string ou usa o atual
        $mesParam = $request->get('mes') ?: Carbon::now()->format('Y-m');

        // Sanitiza o parâmetro de mês
        if (!preg_match('/^\d{4}-\d{2}$/', $mesParam)) {
            $mesParam = Carbon::now()->format('Y-m');
        }

        try {
            $mesAtual = Carbon::createFromFormat('Y-m', $mesParam);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'error' => 'Mês inválido']);
            return;
        }

        $inicioMes = $mesAtual->copy()->startOfMonth();
        $fimMes = $mesAtual->copy()->endOfMonth();

        // Pega o usuário autenticado
        $userId = $_SESSION['user_id'] ?? 1; // Fallback para desenvolvimento

        try {
            // KPIs do mês
            $receitasMes = Lancamento::where('user_id', $userId)
                ->where('tipo', 'receita')
                ->whereBetween('data', [$inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d')])
                ->sum('valor') ?? 0;

            $despesasMes = Lancamento::where('user_id', $userId)
                ->where('tipo', 'despesa')
                ->whereBetween('data', [$inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d')])
                ->sum('valor') ?? 0;

            // Saldo total
            $receitasTotalPagas = Lancamento::where('user_id', $userId)
                ->where('tipo', 'receita')
                ->where('status', 'pago')
                ->where('data', '<=', Carbon::now()->format('Y-m-d'))
                ->sum('valor') ?? 0;

            $despesasTotalPagas = Lancamento::where('user_id', $userId)
                ->where('tipo', 'despesa')
                ->where('status', 'pago')
                ->where('data', '<=', Carbon::now()->format('Y-m-d'))
                ->sum('valor') ?? 0;

            $saldoInicialContas = Conta::where('user_id', $userId)
                ->sum('saldo_inicial') ?? 0;

            $saldoTotal = ($receitasTotalPagas - $despesasTotalPagas) + $saldoInicialContas;

            // Últimos 10 lançamentos
            $ultimos = Lancamento::where('user_id', $userId)
                ->with(['conta', 'categoria'])
                ->orderBy('data', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($l) {
                    return [
                        'id' => $l->id,
                        'data' => $l->data,
                        'tipo' => $l->tipo,
                        'valor' => (float)$l->valor,
                        'descricao' => $l->descricao,
                        'categoria' => $l->categoria ? $l->categoria->nome : null,
                        'conta' => $l->conta ? $l->conta->nome : null
                    ];
                });

            // Dados para o gráfico - últimos 6 meses
            $labels = [];
            $data = [];

            for ($i = 5; $i >= 0; $i--) {
                $mes = Carbon::now()->subMonths($i);
                $inicioMesGrafico = $mes->copy()->startOfMonth();
                $fimMesGrafico = $mes->copy()->endOfMonth();

                $receitasMesGrafico = Lancamento::where('user_id', $userId)
                    ->where('tipo', 'receita')
                    ->where('status', 'pago')
                    ->whereBetween('data', [$inicioMesGrafico->format('Y-m-d'), $fimMesGrafico->format('Y-m-d')])
                    ->sum('valor') ?? 0;

                $despesasMesGrafico = Lancamento::where('user_id', $userId)
                    ->where('tipo', 'despesa')
                    ->where('status', 'pago')
                    ->whereBetween('data', [$inicioMesGrafico->format('Y-m-d'), $fimMesGrafico->format('Y-m-d')])
                    ->sum('valor') ?? 0;

                $labels[] = $mes->format('M/y');
                $data[] = (float)($receitasMesGrafico - $despesasMesGrafico);
            }

            echo json_encode([
                'ok' => true,
                'kpis' => [
                    'receitas' => (float)$receitasMes,
                    'despesas' => (float)$despesasMes,
                    'saldo_total' => (float)$saldoTotal,
                    'resultado_mes' => (float)($receitasMes - $despesasMes)
                ],
                'chart' => [
                    'labels' => $labels,
                    'data' => $data
                ],
                'ultimos' => $ultimos
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'error' => 'Erro interno do servidor']);
        }
    }
}
