<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Lancamento;
use Application\Lib\Auth;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Services\LogService;

class DashboardController extends BaseController
{
    public function dashboard()
    {
        // Se não estiver logado, redireciona (defenda-se contra null)
        $userId = Auth::id();
        if (!$userId) {
            return $this->redirect('login');
        }

        $mesAtual = date('m');
        $anoAtual = date('Y');

        $receitasMes = Lancamento::where('user_id', $userId)
        $receitasMes = (float) Lancamento::where('user_id', $userId)
            ->where('tipo', 'receita')
            ->whereMonth('data', $mesAtual)
            ->whereYear('data', $anoAtual)
            ->sum('valor');

        $despesasMes = (float) Lancamento::where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->whereMonth('data', $mesAtual)
            ->whereYear('data', $anoAtual)
            ->sum('valor');

        $saldoTotal = (float) Lancamento::where('user_id', $userId)
            ->sum(DB::raw("CASE WHEN tipo='receita' THEN valor ELSE -valor END"));

        $fluxo = Lancamento::selectRaw("DATE_FORMAT(data, '%d/%m') as dia, SUM(CASE WHEN tipo='receita' THEN valor ELSE -valor END) as saldo_dia")
        // Série diária do mês atual
        $fluxo = Lancamento::selectRaw("
                DATE_FORMAT(data, '%d/%m') as dia,
                SUM(CASE WHEN tipo='receita' THEN valor ELSE -valor END) as saldo_dia
            ")
            ->where('user_id', $userId)
            ->whereMonth('data', $mesAtual)
            ->whereYear('data', $anoAtual)
            ->groupBy('data')
            ->orderBy('data')
            ->get();

        // Monta arrays para o gráfico (labels/data) — eram os que faltavam
        $labels = $fluxo->pluck('dia')->toArray();
        $data   = $fluxo->pluck('saldo_dia')->map(fn($v) => (float)$v)->toArray();

        $ultimos = Lancamento::with('categoria')
            ->where('user_id', $userId)
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        return [
            'receitasMes' => $receitasMes,
            'despesasMes' => $despesasMes,
            'saldoTotal' => $saldoTotal,
            'labels' => $fluxo->pluck('dia')->all(),
            'data' => $fluxo->pluck('saldo_dia')->map(fn($v) => (float)$v)->all(),
            'ultimos' => $ultimos
        ];
    }

    /**
     * Lida com exceções ocorridas durante o carregamento do dashboard.
     */
    private function handleDashboardError(\Throwable $e): void
    {
        LogService::critical('Erro ao carregar o dashboard', ['erro' => $e->getMessage()]);

        // Exibe uma página de erro amigável para o usuário
        // Agora todos existem no compact()
        $this->render(
            'errors/500', // Você precisará criar essa view
            ['pageTitle' => 'Erro Interno'],
            'admin/home/header',
            'dashboard/index',
            compact('receitasMes', 'despesasMes', 'saldoTotal', 'labels', 'data', 'ultimos'),
            'admin/home/header',
            'admin/footer'
        );
    }
}