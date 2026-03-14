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

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
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

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
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

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $monthInput = trim($_GET['month'] ?? date('Y-m'));
        $normalized = $this->normalizeMonth($monthInput);

        $result = $this->provisaoService->generate($userId, $normalized['month']);

        Response::json($result->toArray());
    }

    /**
     * GET /api/dashboard/health-score
     *
     * Calcula e retorna o score de saúde financeira do usuário (0-100)
     * Baseado em: taxa de poupança, consistência, diversificação, saldo positivo
     */
    public function healthScore(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::json([
                'success' => false,
                'message' => 'Não autenticado',
            ], 401);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            // Pega dados do mês atual
            $currentMonth = date('Y-m');
            $data = $this->lancamentoRepo->getResumoMes($userId, $currentMonth);

            // Calcula fatores do score
            $score = $this->calculateHealthScore($data);

            Response::json([
                'success' => true,
                'data' => $score,
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Erro ao calcular health score',
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/greeting-insight
     *
     * Retorna um insight dinâmico baseado em dados financeiros atuais
     * Para exibição na saudação do dashboard
     */
    public function greetingInsight(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::json([
                'success' => false,
                'message' => 'Não autenticado',
            ], 401);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            $currentMonth = date('Y-m');
            $previousMonth = date('Y-m', strtotime('-1 month'));

            $currentData = $this->lancamentoRepo->getResumoMes($userId, $currentMonth);
            $previousData = $this->lancamentoRepo->getResumoMes($userId, $previousMonth);

            $insight = $this->generateInsight($currentData, $previousData);

            Response::json([
                'success' => true,
                'data' => $insight,
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Erro ao gerar insight',
            ], 500);
        }
    }

    /**
     * Calcula o score de saúde financeira do usuário
     *
     * @param array $data Dados financeiros do mês
     * @return array Score e breakdown dos fatores
     */
    private function calculateHealthScore(array $data): array
    {
        $score = 0;

        // Fator 1: Taxa de Poupança (0-35 pontos)
        // Se receita - despesa / receita >= 20%, score máximo
        $receitas = (float)($data['receitas'] ?? 0);
        $despesas = (float)($data['despesas'] ?? 0);

        if ($receitas > 0) {
            $savingsRate = (($receitas - $despesas) / $receitas) * 100;
            if ($savingsRate >= 20) {
                $score += 35;
            } elseif ($savingsRate >= 10) {
                $score += 25;
            } elseif ($savingsRate >= 0) {
                $score += 15;
            }
        }

        // Fator 2: Consistência (0-30 pontos)
        // Quanto mais lançamentos, melhor a consistência
        $lancamentos = (int)($data['count'] ?? 0);
        if ($lancamentos >= 20) {
            $score += 30;
        } elseif ($lancamentos >= 10) {
            $score += 20;
        } elseif ($lancamentos >= 5) {
            $score += 15;
        } elseif ($lancamentos > 0) {
            $score += 8;
        }

        // Fator 3: Diversificação (0-20 pontos)
        // Número de categorias usadas
        $categories = (int)($data['categories'] ?? 0);
        if ($categories >= 8) {
            $score += 20;
        } elseif ($categories >= 5) {
            $score += 15;
        } elseif ($categories >= 3) {
            $score += 10;
        } elseif ($categories >= 1) {
            $score += 5;
        }

        // Fator 4: Saldo Positivo (0-15 pontos)
        $saldoAtual = (float)($data['saldo_atual'] ?? 0);
        if ($saldoAtual > $despesas * 2) {
            $score += 15; // Saldo é 2x as despesas do mês
        } elseif ($saldoAtual > $despesas) {
            $score += 10;
        } elseif ($saldoAtual > 0) {
            $score += 5;
        }

        // Garante que score fica entre 0 e 100
        $score = min($score, 100);
        $score = max($score, 0);

        // Determina status
        $consistency = 'Regular';
        if ($lancamentos >= 15) {
            $consistency = 'Excelente';
        } elseif ($lancamentos >= 10) {
            $consistency = 'Ótima';
        } elseif ($lancamentos >= 5) {
            $consistency = 'Boa';
        }

        return [
            'score' => (int)$score,
            'savingsRate' => (int)round($savingsRate ?? 0),
            'consistency' => $consistency,
            'categories' => $categories,
            'lancamentos' => $lancamentos,
        ];
    }

    /**
     * Gera um insight dinâmico para exibição na saudação
     *
     * @param array $currentData Dados do mês atual
     * @param array $previousData Dados do mês anterior
     * @return array Insight com mensagem, ícone e cor
     */
    private function generateInsight(array $currentData, array $previousData): array
    {
        $receitas = (float)($currentData['receitas'] ?? 0);
        $despesas = (float)($currentData['despesas'] ?? 0);
        $saldo = $receitas - $despesas;

        $receitasAnterior = (float)($previousData['receitas'] ?? 0);
        $despesasAnterior = (float)($previousData['despesas'] ?? 0);
        $saldoAnterior = $receitasAnterior - $despesasAnterior;

        // Define insights com base em cenários
        $insights = [];

        // Insight 1: Crescimento de saldo
        if ($saldoAnterior > 0 && $saldo > $saldoAnterior) {
            $crescimento = (($saldo - $saldoAnterior) / abs($saldoAnterior)) * 100;
            $insights[] = [
                'message' => "Seu saldo cresceu " . round($crescimento) . "% este mês!",
                'icon' => 'trending-up',
                'color' => '#10b981',
                'weight' => 10,
            ];
        }

        // Insight 2: Despesas reduzidas
        if ($despesasAnterior > 0 && $despesas < $despesasAnterior) {
            $reducao = (($despesasAnterior - $despesas) / $despesasAnterior) * 100;
            $insights[] = [
                'message' => "Você economizou " . round($reducao) . "% em despesas!",
                'icon' => 'zap',
                'color' => '#3b82f6',
                'weight' => 9,
            ];
        }

        // Insight 3: Receita em alta
        if ($receitasAnterior > 0 && $receitas > $receitasAnterior) {
            $aumento = (($receitas - $receitasAnterior) / $receitasAnterior) * 100;
            $insights[] = [
                'message' => "Suas receitas subiram " . round($aumento) . "% em relação ao mês passado!",
                'icon' => 'arrow-up',
                'color' => '#10b981',
                'weight' => 8,
            ];
        }

        // Insight 4: Saldo chegou positivo
        if ($saldoAnterior <= 0 && $saldo > 0) {
            $insights[] = [
                'message' => "Parabéns! Seu saldo voltou a ser positivo este mês.",
                'icon' => 'smile',
                'color' => '#10b981',
                'weight' => 9,
            ];
        }

        // Insight 5: Muitos lançamentos
        $lancamentos = (int)($currentData['count'] ?? 0);
        if ($lancamentos >= 30) {
            $insights[] = [
                'message' => "Ótimo controle! Você registrou " . $lancamentos . " transações.",
                'icon' => 'activity',
                'color' => '#3b82f6',
                'weight' => 7,
            ];
        }

        // Se não há insights específicos, usa fallback
        if (empty($insights)) {
            $insights[] = [
                'message' => "Seu saldo atual está em R$ " . number_format($saldo, 2, ',', '.'),
                'icon' => 'wallet',
                'color' => 'var(--color-primary)',
                'weight' => 1,
            ];
        }

        // Seleciona o insight de maior peso (aleatório entre os top 3)
        usort($insights, fn($a, $b) => $b['weight'] - $a['weight']);
        $topInsights = array_slice($insights, 0, 3);
        $selected = $topInsights[array_rand($topInsights)];

        unset($selected['weight']);

        return $selected;
    }
}
