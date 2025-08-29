<?php

namespace Application\Controllers;

use Application\Controllers\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

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

    // GET /api/reports/overview?month=YYYY-MM[&tipo=receita|despesa]
    public function overview(): void
    {
        $this->requireAuth();
        $month = $this->request->get('month') ?? date('Y-m');
        [$y, $m] = explode('-', $month);
        $from   = "$y-$m-01";
        $to     = date('Y-m-t', strtotime($from));

        $tipoFiltro = $this->request->get('tipo'); // opcional

        // Totais por tipo (sem admin/conta)
        $base = DB::table('lancamentos')->whereBetween('data', [$from, $to]);
        if ($tipoFiltro) $base->where('tipo', $tipoFiltro);

        $totalReceitas = (clone $base)->where('tipo', 'receita')->sum('valor');
        $totalDespesas = (clone $base)->where('tipo', 'despesa')->sum('valor');

        // Por categoria (join para pegar nome)
        $porCategoria = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->whereBetween('l.data', [$from, $to])
            ->when($tipoFiltro, fn($q) => $q->where('l.tipo', $tipoFiltro))
            ->selectRaw('l.categoria_id, COALESCE(c.nome,"—") as categoria, l.tipo, SUM(l.valor) as total')
            ->groupBy('l.categoria_id', 'c.nome', 'l.tipo')
            ->orderByDesc('total')
            ->get()
            ->map(function ($r) {
                return [
                    'categoria_id' => (int)$r->categoria_id,
                    'categoria'    => (string)$r->categoria,
                    'tipo'         => (string)$r->tipo,
                    'total'        => (float)$r->total,
                ];
            });

        // Seu schema não tem "contas", então devolvemos vazio
        $porConta = [];

        $this->json([
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

    // GET /api/reports/table?month=YYYY-MM[&tipo=]
    public function table(): void
    {
        $this->requireAuth();
        $month = $this->request->get('month') ?? date('Y-m');
        [$y, $m] = explode('-', $month);
        $from   = "$y-$m-01";
        $to     = date('Y-m-t', strtotime($from));
        $tipo   = $this->request->get('tipo');

        $q = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->whereBetween('l.data', [$from, $to])
            ->when($tipo, fn($q) => $q->where('l.tipo', $tipo))
            ->orderBy('l.data', 'asc');

        $items = $q->selectRaw('l.id, l.data, l.tipo, COALESCE(c.nome,"—") as categoria, l.valor, l.descricao')
            ->get()
            ->map(function ($l) {
                return [
                    'id'        => (int)$l->id,
                    'data'      => (string)$l->data,        // YYYY-MM-DD
                    'tipo'      => (string)$l->tipo,        // receita|despesa
                    'categoria' => (string)$l->categoria,
                    'conta'     => '—',                     // não existe no seu schema
                    'valor'     => (float)$l->valor,
                    'descricao' => (string)($l->descricao ?? ''),
                ];
            });

        $totaisReceitas = (clone $q)->where('l.tipo', 'receita')->sum('l.valor');
        $totaisDespesas = (clone $q)->where('l.tipo', 'despesa')->sum('l.valor');

        $this->json([
            'items'  => $items,
            'totais' => [
                'receitas' => (float)$totaisReceitas,
                'despesas' => (float)$totaisDespesas,
                'saldo'    => (float)$totaisReceitas - (float)$totaisDespesas,
            ],
            'pagination' => ['page' => 1, 'per_page' => count($items), 'total' => count($items), 'has_next' => false]
        ]);
    }

    // GET /api/reports/timeseries?month=YYYY-MM[&tipo=]
    public function timeseries(): void
    {
        $this->requireAuth();
        $month = $this->request->get('month') ?? date('Y-m');
        [$y, $m] = explode('-', $month);
        $from   = "$y-$m-01";
        $to     = date('Y-m-t', strtotime($from));
        $tipo   = $this->request->get('tipo'); // opcional

        $rows = DB::table('lancamentos')
            ->whereBetween('data', [$from, $to])
            ->when($tipo, fn($q) => $q->where('tipo', $tipo))
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
            $labels[]   = (string)$r->dia;                 // YYYY-MM-DD
            $receitas[] = (float)$r->receitas;
            $despesas[] = (float)$r->despesas;
            $acum += ((float)$r->receitas - (float)$r->despesas);
            $saldoAc[]  = $acum;
        }

        $this->json([
            'labels'          => $labels,
            'receitas'        => $receitas,
            'despesas'        => $despesas,
            'saldo_acumulado' => $saldoAc,
        ]);
    }
}
