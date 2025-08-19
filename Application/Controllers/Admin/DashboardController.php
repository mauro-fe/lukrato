<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Lancamento;        // <<< use correto
use Application\Lib\Auth;                 // para pegar o user logado
use Application\Core\View;                 // para pegar o user logado
use Illuminate\Database\Capsule\Manager as DB;

class DashboardController extends BaseController
{
    // sua rota chama @dashboard('{username}')
    public function dashboard()
    {
        $userId   = Auth::id();
        $mesAtual = date('m');
        $anoAtual = date('Y');

        // KPIs (sempre filtrando por user_id!)
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

        // Fluxo diário (labels + data)
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

        $labels = $fluxo->pluck('dia')->all();
        $data   = $fluxo->pluck('saldo_dia')->map(fn($v) => (float)$v)->all();

        // Últimos lançamentos
        $ultimos = Lancamento::with('categoria')
            ->where('user_id', $userId)
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Dados para a view
        // Dentro de um método do seu controller
        $viewData = [
            'pageTitle' => 'Dashboard',
            'username'  => $_SESSION['admin_username'] ?? 'Usuário',
            'menu'      => 'dashboard' // Variável para o menu ativo
        ];

        $this->render(
            'dashboard/index',
            $viewData,
            'admin/home/header', // Este é o arquivo que você enviou
            'admin/footer'
        );
    }
}
