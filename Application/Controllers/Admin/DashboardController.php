<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Lancamento;
use Application\Lib\Auth;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Services\LogService;

class DashboardController extends BaseController
{
    /**
     * Ponto de entrada para a rota do dashboard.
     * Orquestra a autorização, busca de dados e renderização.
     */
    public function dashboard()
    {
        try {
            // 2. Carrega os dados
            $data = $this->loadDashboardData(Auth::id());

            // Adiciona dados extras para a view
            $data['pageTitle'] = 'Dashboard';
            $data['username']  = Auth::user()->username;
            $data['menu']      = 'dashboard';

            // 3. Renderiza a view com os dados
            $this->render(
                'dashboard/index',
                $data,
                'admin/home/header',
                null
            );
        } catch (\Throwable $e) {
            // 4. Lida com qualquer erro que ocorrer
            $this->handleDashboardError($e);
        }
    }

    /**
     * Busca e processa todos os dados necessários para o dashboard.
     * @return array
     */
    private function loadDashboardData(int $userId): array
    {
        $mesAtual = date('m');
        $anoAtual = date('Y');

        $receitasMes = Lancamento::where('user_id', $userId)
            ->where('tipo', 'receita')
            ->whereMonth('data', $mesAtual)
            ->whereYear('data', $anoAtual)
            ->sum('valor');

        $despesasMes = Lancamento::where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->whereMonth('data', $mesAtual)
            ->whereYear('data', $anoAtual)
            ->sum('valor');

        $saldoTotal = Lancamento::where('user_id', $userId)
            ->sum(DB::raw("CASE WHEN tipo='receita' THEN valor ELSE -valor END"));

        $fluxo = Lancamento::selectRaw("DATE_FORMAT(data, '%d/%m') as dia, SUM(CASE WHEN tipo='receita' THEN valor ELSE -valor END) as saldo_dia")
            ->where('user_id', $userId)
            ->whereMonth('data', $mesAtual)
            ->whereYear('data', $anoAtual)
            ->groupBy('data')
            ->orderBy('data')
            ->get();

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
        $this->render(
            'errors/500', // Você precisará criar essa view
            ['pageTitle' => 'Erro Interno'],
            'admin/home/header',
            'admin/footer'
        );
    }
}
