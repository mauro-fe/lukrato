<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Enums\LancamentoTipo;
use Application\Repositories\LancamentoRepository;
use Illuminate\Database\Eloquent\Builder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    private LancamentoRepository $lancamentoRepo;

    public function __construct()
    {
        $this->lancamentoRepo = new LancamentoRepository();
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

    private function createBaseQuery(int $userId): Builder
    {
        return Lancamento::where('user_id', $userId)
            ->where('eh_transferencia', 0);
    }


    /**
     * GET /api/dashboard/metrics
     * 
     * Parâmetros:
     * - month: Mês no formato Y-m (ex: 2026-01)
     * - account_id: ID da conta (opcional)
     * - view: 'caixa' ou 'competencia' (padrão: 'caixa')
     * 
     * REFATORAÇÃO: Suporta visualização por competência ou caixa
     */
    public function metrics(): void
    {
        $userId = Auth::id();
        $monthInput = trim($_GET['month'] ?? date('Y-m'));
        $accId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : null;
        $viewType = trim($_GET['view'] ?? 'caixa'); // 'caixa' ou 'competencia'

        $normalizedDate = $this->normalizeMonth($monthInput);
        $y = $normalizedDate['year'];
        $m = $normalizedDate['monthNum'];
        $month = $normalizedDate['month'];

        if ($accId && !Conta::where('user_id', $userId)->where('id', $accId)->exists()) {
            Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
            return;
        }

        // Calcular início e fim do mês
        $start = "{$month}-01";
        $end = date('Y-m-t', strtotime($start));

        // REFATORAÇÃO: Usar repository com suporte a competência
        if ($viewType === 'competencia') {
            // Visão de COMPETÊNCIA (mês da despesa real)
            $sumReceitas = $this->lancamentoRepo->sumReceitasCompetencia($userId, $start, $end);
            $sumDespesas = $this->lancamentoRepo->sumDespesasCompetencia($userId, $start, $end);
        } else {
            // Visão de CAIXA (comportamento original)
            if ($accId) {
                // Filtro por conta específica (mantém comportamento original)
                $monthlyBase = $this->createBaseQuery($userId)
                    ->whereYear('data', $y)
                    ->whereMonth('data', $m)
                    ->where('conta_id', $accId);

                $sumReceitas = (float)(clone $monthlyBase)->where('tipo', LancamentoTipo::RECEITA->value)->sum('valor');
                $sumDespesas = (float)(clone $monthlyBase)->where('tipo', LancamentoTipo::DESPESA->value)->sum('valor');
            } else {
                $sumReceitas = $this->lancamentoRepo->sumReceitasCaixa($userId, $start, $end);
                $sumDespesas = $this->lancamentoRepo->sumDespesasCaixa($userId, $start, $end);
            }
        }

        $resultado = $sumReceitas - $sumDespesas;

        $ate = (new DateTimeImmutable("$month-01"))
            ->modify('last day of this month')
            ->format('Y-m-d');

        // Saldo sempre é calculado por CAIXA (saldo real na conta)
        $saldoAcumulado = $accId
            ? $this->calcularSaldoConta($userId, $accId, $ate)
            : $this->calcularSaldoGlobal($userId, $ate);

        Response::json([
            'saldo' => $saldoAcumulado,
            'receitas' => $sumReceitas,
            'despesas' => $sumDespesas,
            'resultado' => $resultado,
            'saldoAcumulado' => $saldoAcumulado,
            'view' => $viewType, // Informar qual visão está sendo usada
        ]);
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

    private function calcularSaldoConta(int $userId, int $contaId, string $ate): float
    {
        $movBaseAcumulado = Lancamento::where('user_id', $userId)
            ->where('data', '<=', $ate)
            ->where('conta_id', $contaId);

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
            ->where('conta_id_destino', $contaId)
            ->sum('valor');

        $transfOut = (float)Lancamento::where('user_id', $userId)
            ->where('data', '<=', $ate)
            ->where('eh_transferencia', 1)
            ->where('conta_id', $contaId)
            ->sum('valor');

        return $movReceitas - $movDespesas + $transfIn - $transfOut;
    }

    private function calcularSaldoGlobal(int $userId, string $ate): float
    {
        $movGlobal = Lancamento::where('user_id', $userId)
            ->where('data', '<=', $ate)
            ->where('eh_transferencia', 0);

        $r = (float)(clone $movGlobal)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->sum('valor');

        $d = (float)(clone $movGlobal)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->sum('valor');

        return $r - $d;
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
}
