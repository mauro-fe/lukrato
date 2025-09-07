<?php

namespace Application\Controllers;

use Application\Controllers\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Core\Response as HttpResponse;

class RelatoriosController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function view(): void
    {
        $this->requireAuth();
        $this->renderAdmin('admin/relatorios', [
            'username' => $this->adminUsername ?? 'usuário',
            'menu'     => 'relatorios',

        ]);
    }

    // GET /api/reports/overview?month=YYYY-MM[&tipo=receita|despesa][&account_id=]
    public function overview(): void
    {
        $this->requireAuth();
        $month = $this->request->get('month') ?? date('Y-m');
        [$y, $m] = explode('-', $month);
        $from = "$y-$m-01";
        $to   = date('Y-m-t', strtotime($from));

        $tipoFiltro = $this->request->get('tipo'); // opcional
        $acc        = $this->request->get('account_id');
        $accId      = is_null($acc) || $acc === '' ? null : (int)$acc;

        // Totais por tipo
        $base = DB::table('lancamentos')->whereBetween('data', [$from, $to]);
        if ($tipoFiltro) $base->where('tipo', $tipoFiltro);
        if ($accId) $base->where('conta_id', $accId);

        $totalReceitas = (clone $base)->where('tipo', 'receita')->sum('valor');
        $totalDespesas = (clone $base)->where('tipo', 'despesa')->sum('valor');

        // Por categoria
        $porCategoria = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->whereBetween('l.data', [$from, $to])
            ->when($tipoFiltro, fn($q) => $q->where('l.tipo', $tipoFiltro))
            ->when($accId, fn($q) => $q->where('l.conta_id', $accId))
            ->selectRaw('l.categoria_id, COALESCE(c.nome,"—") as categoria, l.tipo, SUM(l.valor) as total')
            ->groupBy('l.categoria_id', 'c.nome', 'l.tipo')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'categoria_id' => (int)$r->categoria_id,
                'categoria'    => (string)$r->categoria,
                'tipo'         => (string)$r->tipo,
                'total'        => (float)$r->total,
            ]);

        // Por conta (NOVO)
        $porContaRows = DB::table('lancamentos as l')
            ->leftJoin('contas as a', 'a.id', '=', 'l.conta_id')
            ->whereBetween('l.data', [$from, $to])
            ->when($tipoFiltro, fn($q) => $q->where('l.tipo', $tipoFiltro))
            ->when($accId, fn($q) => $q->where('l.conta_id', $accId))
            ->selectRaw('COALESCE(a.nome, a.instituicao, "—") as conta')
            ->selectRaw('SUM(CASE WHEN l.tipo="receita" THEN l.valor ELSE 0 END) as receitas')
            ->selectRaw('SUM(CASE WHEN l.tipo="despesa" THEN l.valor ELSE 0 END) as despesas')
            ->groupBy('conta')
            ->orderBy('conta')
            ->get();

        $porConta = $porContaRows->map(fn($r) => [
            'conta'    => (string)$r->conta,
            'receitas' => (float)$r->receitas,
            'despesas' => (float)$r->despesas,
        ]);

        HttpResponse::json([
            'month' => $month,
            'resumo' => [
                'total_receitas' => (float)$totalReceitas,
                'total_despesas' => (float)$totalDespesas,
                'saldo'          => (float)$totalReceitas - (float)$totalDespesas,
            ],
            'por_categoria' => $porCategoria,
            'por_conta'     => $porConta,
        ]);
    }

    // GET /api/reports/table?month=YYYY-MM[&tipo=][&account_id=]
    public function table(): void
    {
        $this->requireAuth();
        $month = $this->request->get('month') ?? date('Y-m');
        [$y, $m] = explode('-', $month);
        $from = "$y-$m-01";
        $to   = date('Y-m-t', strtotime($from));
        $tipo = $this->request->get('tipo');
        $acc  = $this->request->get('account_id');
        $accId = is_null($acc) || $acc === '' ? null : (int)$acc;

        $q = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as a', 'a.id', '=', 'l.conta_id')
            ->whereBetween('l.data', [$from, $to])
            ->when($tipo, fn($q) => $q->where('l.tipo', $tipo))
            ->when($accId, fn($q) => $q->where('l.conta_id', $accId))
            ->orderBy('l.data', 'asc');

        $items = $q->selectRaw('l.id, l.data, l.tipo, COALESCE(c.nome,"—") as categoria, COALESCE(a.nome,a.instituicao,"—") as conta, l.valor, l.descricao')
            ->get()
            ->map(fn($l) => [
                'id'        => (int)$l->id,
                'data'      => (string)$l->data,
                'tipo'      => (string)$l->tipo,
                'categoria' => (string)$l->categoria,
                'conta'     => (string)$l->conta,
                'valor'     => (float)$l->valor,
                'descricao' => (string)($l->descricao ?? ''),
            ]);

        $totaisReceitas = (clone $q)->where('l.tipo', 'receita')->sum('l.valor');
        $totaisDespesas = (clone $q)->where('l.tipo', 'despesa')->sum('l.valor');

        HttpResponse::json([
            'items'  => $items,
            'totais' => [
                'receitas' => (float)$totaisReceitas,
                'despesas' => (float)$totaisDespesas,
                'saldo'    => (float)$totaisReceitas - (float)$totaisDespesas,
            ],
            'pagination' => ['page' => 1, 'per_page' => count($items), 'total' => count($items), 'has_next' => false]
        ]);
    }

    // GET /api/reports/timeseries?month=YYYY-MM[&tipo=][&account_id=]
    public function timeseries(): void
    {
        $this->requireAuth();
        $month = $this->request->get('month') ?? date('Y-m');
        [$y, $m] = explode('-', $month);
        $from = "$y-$m-01";
        $to   = date('Y-m-t', strtotime($from));
        $tipo = $this->request->get('tipo');
        $acc  = $this->request->get('account_id');
        $accId = is_null($acc) || $acc === '' ? null : (int)$acc;

        $rows = DB::table('lancamentos')
            ->whereBetween('data', [$from, $to])
            ->when($tipo, fn($q) => $q->where('tipo', $tipo))
            ->when($accId, fn($q) => $q->where('conta_id', $accId))
            ->selectRaw("
                DATE(data) as dia,
                SUM(CASE WHEN tipo='receita' THEN valor ELSE 0 END) as receitas,
                SUM(CASE WHEN tipo='despesa' THEN valor ELSE 0 END) as despesas
            ")
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $labels = [];
        $receitas = [];
        $despesas = [];
        $saldoAc = [];
        $acum = 0.0;

        foreach ($rows as $r) {
            $labels[]   = (string)$r->dia; // YYYY-MM-DD
            $receitas[] = (float)$r->receitas;
            $despesas[] = (float)$r->despesas;
            $acum += ((float)$r->receitas - (float)$r->despesas);
            $saldoAc[]  = $acum;
        }

        HttpResponse::json([
            'labels'          => $labels,
            'receitas'        => $receitas,
            'despesas'        => $despesas,
            'saldo_acumulado' => $saldoAc,
        ]);
    }
}
