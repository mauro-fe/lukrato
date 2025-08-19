<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Lancamento;        // <<< use correto
use Application\Lib\Auth;                 // para pegar o user logado
use Illuminate\Database\Capsule\Manager as DB;
use Application\Services\LogService;

class DashboardController extends BaseController
{
    // sua rota chama @dashboard('{username}')
    public function dashboard(string $username)
    {
        $userId   = Auth::id();
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

        // Render com header/footer (agora que já testou sem)
        $this->render(
            'dashboard/index',
            compact('receitasMes', 'despesasMes', 'saldoTotal', 'labels', 'data', 'ultimos'),
            'admin/home/header',
            'admin/footer'
        );
    }
}
