<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Illuminate\Database\Eloquent\Builder; // Para tipagem do query builder
use DateTimeImmutable; // PHP 8+
use Illuminate\Support\Facades\DB;
use Throwable;

// --- Enums para Constantes (PHP 8.1+) ---

enum LancamentoTipo: string
{
    case DESPESA = 'despesa';
    case RECEITA = 'receita';
}

class DashboardController
{
    /**
     * Valida e normaliza o mês de entrada.
     * @param string $monthInput O mês fornecido no formato 'Y-m'.
     * @return array{month: string, year: int, monthNum: int}
     */
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
     * Aplica filtros básicos (usuário, transferências, saldo inicial) a um query builder.
     * @param int $userId ID do usuário.
     * @return Builder
     */
    private function createBaseQuery(int $userId): Builder
    {
        return Lancamento::where('user_id', $userId)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 0);
    }

    /**
     * Calcula as métricas financeiras (Receita, Despesa, Resultado) para o mês e conta selecionados.
     */
    public function metrics(): void
    {
        try {
            $userId = Auth::id();
            
            // 1. Parsing e Normalização
            $monthInput = trim($_GET['month'] ?? date('Y-m'));
            $accId      = isset($_GET['account_id']) ? (int)$_GET['account_id'] : null;
            
            $normalizedDate = $this->normalizeMonth($monthInput);
            $y = $normalizedDate['year'];
            $m = $normalizedDate['monthNum'];
            $month = $normalizedDate['month'];
            
            // 2. Validação da Conta (Se accId for fornecido)
            if ($accId) {
                if (!Conta::where('user_id', $userId)->where('id', $accId)->exists()) {
                    Response::json(['status' => 'error', 'message' => 'Conta não encontrada'], 404);
                    return;
                }
            }
            
            // --- Cálculo das Métricas Mensais (Receitas / Despesas) ---
            
            $monthlyBase = $this->createBaseQuery($userId)
                ->whereYear('data', $y)
                ->whereMonth('data', $m);

            if ($accId) {
                $monthlyBase->where('conta_id', $accId);
            }

            $receitasQuery = (clone $monthlyBase)->where('tipo', LancamentoTipo::RECEITA->value);
            $despesasQuery = (clone $monthlyBase)->where('tipo', LancamentoTipo::DESPESA->value);

            $sumReceitas = (float)$receitasQuery->sum('valor');
            $sumDespesas = (float)$despesasQuery->sum('valor');
            $resultado   = $sumReceitas - $sumDespesas;

            // --- Cálculo do Saldo Acumulado (até o final do mês) ---
            
            $ate = (new DateTimeImmutable("$month-01"))
                ->modify('last day of this month')
                ->format('Y-m-d');
            
            $saldoAcumulado = 0.0;

            if ($accId) {
                // Cálculo de saldo para UMA conta específica
                
                $movBaseAcumulado = Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('conta_id', $accId);
                    
                // Movimento Receita/Despesa (ignora transferência e saldo inicial)
                $movReceitas = (float)(clone $movBaseAcumulado)
                    ->where('eh_transferencia', 0)
                    ->where('tipo', LancamentoTipo::RECEITA->value)
                    ->sum('valor');
                    
                $movDespesas = (float)(clone $movBaseAcumulado)
                    ->where('eh_transferencia', 0)
                    ->where('tipo', LancamentoTipo::DESPESA->value)
                    ->sum('valor');

                // Transferências ENTRANDO na conta (conta_id_destino)
                $transfIn = (float)Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 1)
                    ->where('conta_id_destino', $accId)
                    ->sum('valor');

                // Transferências SAINDO da conta (conta_id)
                $transfOut = (float)Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 1)
                    ->where('conta_id', $accId)
                    ->sum('valor');
                    
                // Saldo Acumulado = Receitas - Despesas + Transf In - Transf Out
                $saldoAcumulado = $movReceitas - $movDespesas + $transfIn - $transfOut;

            } else {
                // Cálculo de saldo GLOBAL (apenas Receitas/Despesas, ignorando transferências)
                
                $movGlobal = Lancamento::where('user_id', $userId)
                    ->where('data', '<=', $ate)
                    ->where('eh_transferencia', 0); // Transferências globais se anulam
                    
                $r = (float)(clone $movGlobal)
                    ->where('tipo', LancamentoTipo::RECEITA->value)
                    ->sum('valor');
                
                $d = (float)(clone $movGlobal)
                    ->where('tipo', LancamentoTipo::DESPESA->value)
                    ->sum('valor');

                $saldoAcumulado = $r - $d;
            }

            // 4. Resposta
            Response::json([
                'saldo'          => $saldoAcumulado, // Nome original, mas é o saldo acumulado
                'receitas'       => $sumReceitas,
                'despesas'       => $sumDespesas,
                'resultado'      => $resultado, // Resultado (Receitas - Despesas) do MÊS
                'saldoAcumulado' => $saldoAcumulado, // Mantido por clareza/compatibilidade
            ]);
            
        } catch (Throwable $e) {
            // Captura qualquer exceção (do PHP 7+ e 8+)
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function transactions(): void
    {
        try {
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
            $to   = date('Y-m-t', strtotime($from));

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
        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
