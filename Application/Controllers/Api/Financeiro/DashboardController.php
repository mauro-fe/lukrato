<?php

namespace Application\Controllers\Api\Financeiro;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Repositories\LancamentoRepository;
use Application\Services\Financeiro\DashboardProvisaoService;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    private LancamentoRepository $lancamentoRepo;
    private DashboardProvisaoService $provisaoService;

    public function __construct()
    {
        $this->lancamentoRepo = new LancamentoRepository();
        $this->provisaoService = new DashboardProvisaoService();
    }

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


    /**
     * GET /api/dashboard/comparativo-competencia-caixa
     * 
     * Retorna comparativo entre visão de competência e caixa para o mês
     * Útil para mostrar diferença entre os dois métodos
     */
    public function comparativoCompetenciaCaixa(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::json(['status' => 'error', 'message' => 'Não autenticado'], 401);
            return;
        }

        $monthInput = trim($_GET['month'] ?? date('Y-m'));
        $normalizedDate = $this->normalizeMonth($monthInput);
        $month = $normalizedDate['month'];

        $comparativo = $this->lancamentoRepo->getResumoCompetenciaVsCaixa($userId, $month);

        // Calcular diferenças
        $difReceitas = $comparativo['competencia']['receitas'] - $comparativo['caixa']['receitas'];
        $difDespesas = $comparativo['competencia']['despesas'] - $comparativo['caixa']['despesas'];

        Response::json([
            'month' => $month,
            'competencia' => [
                'receitas' => $comparativo['competencia']['receitas'],
                'despesas' => $comparativo['competencia']['despesas'],
                'resultado' => $comparativo['competencia']['receitas'] - $comparativo['competencia']['despesas'],
            ],
            'caixa' => [
                'receitas' => $comparativo['caixa']['receitas'],
                'despesas' => $comparativo['caixa']['despesas'],
                'resultado' => $comparativo['caixa']['receitas'] - $comparativo['caixa']['despesas'],
            ],
            'diferenca' => [
                'receitas' => $difReceitas,
                'despesas' => $difDespesas,
                'resultado' => ($comparativo['competencia']['receitas'] - $comparativo['competencia']['despesas']) -
                    ($comparativo['caixa']['receitas'] - $comparativo['caixa']['despesas']),
            ],
        ]);
    }

    public function transactions(): void
    {
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
        $to = date('Y-m-t', strtotime($from));

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
    }

    /**
     * GET /api/dashboard/provisao
     * 
     * Retorna provisão financeira baseada nos agendamentos pendentes.
     * - Total a pagar (despesas agendadas no mês)
     * - Total a receber (receitas agendadas no mês)
     * - Saldo projetado (saldo atual + receitas - despesas agendadas)
     * - Próximos vencimentos (agendamentos mais próximos)
     * - Agendamentos vencidos
     */
    public function provisao(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::json(['status' => 'error', 'message' => 'Não autenticado'], 401);
            return;
        }

        $monthInput = trim($_GET['month'] ?? date('Y-m'));
        $normalized = $this->normalizeMonth($monthInput);

        $result = $this->provisaoService->generate($userId, $normalized['month']);

        Response::json($result->toArray());
    }
}
