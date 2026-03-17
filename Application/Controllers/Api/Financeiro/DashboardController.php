<?php

namespace Application\Controllers\Api\Financeiro;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Financeiro\DashboardProvisaoService;
use Application\Services\Infrastructure\LogService;

class DashboardController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private DashboardProvisaoService $provisaoService;
    private OrcamentoRepository $orcamentoRepo;
    private MetaRepository $metaRepo;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
        $this->provisaoService = new DashboardProvisaoService();
        $this->orcamentoRepo = new OrcamentoRepository();
        $this->metaRepo = new MetaRepository();
    }

    private function normalizeMonth(string $monthInput): array
    {
        return $this->normalizeYearMonth($monthInput);
    }


    /**
     * GET /api/dashboard/comparativo-competencia-caixa
     * 
     * Retorna comparativo entre visão de competência e caixa para o mês
     * Útil para mostrar diferença entre os dois métodos
     */
    public function comparativoCompetenciaCaixa(): void
    {
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }

        $this->releaseSession();

        $normalizedDate = $this->normalizeMonth((string) $this->getQuery('month', date('Y-m')));
        $month = $normalizedDate['month'];

        $comparativo = $this->lancamentoRepo->getResumoCompetenciaVsCaixa($userId, $month);

        // Calcular diferenças
        $difReceitas = $comparativo['competencia']['receitas'] - $comparativo['caixa']['receitas'];
        $difDespesas = $comparativo['competencia']['despesas'] - $comparativo['caixa']['despesas'];

        Response::success([
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
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }

        $this->releaseSession();

        $limit = min((int) $this->getQuery('limit', 5), 100);

        $normalized = $this->normalizeMonth((string) $this->getQuery('month', date('Y-m')));
        $from = $normalized['start'];
        $to = $normalized['end'];

        $rows = Lancamento::query()
            ->withoutGlobalScopes()
            ->from('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as a', 'a.id', '=', 'l.conta_id')
            ->where('l.user_id', $userId)
            ->whereNull('l.deleted_at')
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

        Response::success($out);
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
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }

        $this->releaseSession();

        $normalized = $this->normalizeMonth((string) $this->getQuery('month', date('Y-m')));

        $result = $this->provisaoService->generate($userId, $normalized['month']);

        Response::success($result->toArray());
    }

    /**
     * GET /api/dashboard/health-score
     *
     * Calcula e retorna o score de saúde financeira do usuário (0-100)
     * Baseado em: taxa de poupança, consistência, diversificação, saldo positivo
     */
    public function healthScore(): void
    {
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }

        $this->releaseSession();

        try {
            // Pega dados do mês atual
            $currentMonth = date('Y-m');
            $data = $this->lancamentoRepo->getResumoMes($userId, $currentMonth);

            // Calcula fatores do score
            $score = $this->calculateHealthScore($data);

            // Enriquece com dados de orçamento e metas
            $mes = (int) date('m');
            $ano = (int) date('Y');

            // Orçamentos: quantos definidos e quantos estão dentro do limite
            $orcamentos = $this->orcamentoRepo->findByUserAndMonth($userId, $mes, $ano);
            $totalOrcamentos = $orcamentos->count();
            $dentroDoLimite = 0;
            foreach ($orcamentos as $orc) {
                $gasto = $this->orcamentoRepo->getGastoRealComFallback($userId, $orc->categoria_id, $mes, $ano);
                if ($gasto <= $orc->valor_limite) {
                    $dentroDoLimite++;
                }
            }

            // Metas ativas
            $metasAtivas = $this->metaRepo->countAtivas($userId);
            $metasConcluidas = 0;
            $metas = $this->metaRepo->findByUser($userId, 'ativa');
            foreach ($metas as $meta) {
                if ($meta->valor_atual >= $meta->valor_alvo) {
                    $metasConcluidas++;
                }
            }

            $score['orcamentos'] = $totalOrcamentos;
            $score['orcamentos_ok'] = $dentroDoLimite;
            $score['metas_ativas'] = $metasAtivas;
            $score['metas_concluidas'] = $metasConcluidas;

            Response::success($score);
        } catch (\Exception $e) {
            LogService::error('Erro ao calcular health score no dashboard', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            Response::error('Erro ao calcular health score', 500);
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
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }

        $this->releaseSession();

        try {
            $currentMonth = date('Y-m');
            $previousMonth = date('Y-m', strtotime('-1 month'));

            $currentData = $this->lancamentoRepo->getResumoMes($userId, $currentMonth);
            $previousData = $this->lancamentoRepo->getResumoMes($userId, $previousMonth);

            $insight = $this->generateInsight($currentData, $previousData);

            Response::success($insight);
        } catch (\Exception $e) {
            Response::error('Erro ao gerar insight', 500);
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

    /**
     * GET /api/dashboard/health-score/insights
     *
     * Gera insights acionáveis baseado na saúde financeira
     * Mostra "como melhorar" para cada aspecto do score
     */
    public function healthScoreInsights(): void
    {
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }

        $this->releaseSession();

        try {
            $currentMonth = date('Y-m');
            $data = $this->lancamentoRepo->getResumoMes($userId, $currentMonth);

            $insights = $this->generateHealthScoreInsights($data, $userId);

            Response::success($insights);
        } catch (\Exception $e) {
            LogService::error('Erro ao gerar insights de health score', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            Response::error('Erro ao gerar insights', 500);
        }
    }

    /**
     * Gera insights acionáveis para melhorar Health Score
     */
    private function generateHealthScoreInsights(array $data, int $userId): array
    {
        $insights = [];

        $receitas = (float)($data['receitas'] ?? 0);
        $despesas = (float)($data['despesas'] ?? 0);
        $saldoAtual = (float)($data['saldo_atual'] ?? 0);
        $lancamentos = (int)($data['count'] ?? 0);
        $categories = (int)($data['categories'] ?? 0);

        // Insight 1: Taxa de Poupança
        if ($receitas > 0) {
            $savingsRate = (($receitas - $despesas) / $receitas) * 100;

            if ($savingsRate < 10) {
                $needed = $receitas * 0.10 - ($receitas - $despesas);
                $insights[] = [
                    'type' => 'savings_rate',
                    'priority' => 'high',
                    'title' => '💰 Aumente sua poupança',
                    'message' => 'Sua taxa de poupança está baixa (< 10%)',
                    'suggestion' => 'Reduza suas despesas em R$ ' . number_format($needed, 2, ',', '.') . ' para atingir 10%',
                    'action' => [
                        'label' => 'Ver gastos por categoria',
                        'url' => 'relatorios'
                    ],
                    'impact' => '+15 pontos no score',
                    'impact_points' => 15,
                ];
            }
        } else if ($receitas == 0 && $despesas > 0) {
            $insights[] = [
                'type' => 'no_income',
                'priority' => 'critical',
                'title' => '⚠️ Registre suas receitas',
                'message' => 'Você só tem despesas registradas',
                'suggestion' => 'Adicione suas receitas para calcular a taxa de poupança corretamente',
                'action' => [
                    'label' => 'Adicionar receita',
                    'url' => 'lancamentos?tipo=receita'
                ],
                'impact' => '+20 pontos no score',
                'impact_points' => 20,
            ];
        }

        // Insight 2: Consistência (número de transações)
        if ($lancamentos < 5) {
            $needed = 5 - $lancamentos;
            $insights[] = [
                'type' => 'consistency',
                'priority' => 'high',
                'title' => '📊 Registre mais transações',
                'message' => 'Você tem apenas ' . $lancamentos . ' transação(ões)',
                'suggestion' => 'Registre ' . $needed . ' mais transação(ões) para melhorar a consistência',
                'action' => [
                    'label' => 'Adicionar transação',
                    'url' => 'lancamentos'
                ],
                'impact' => '+10 pontos no score',
                'impact_points' => 10,
            ];
        }

        // Insight 3: Diversificação (categorias)
        if ($categories < 3) {
            $needed = 3 - $categories;
            $insights[] = [
                'type' => 'diversification',
                'priority' => 'medium',
                'title' => '🎨 Organize em categorias',
                'message' => 'Você usa apenas ' . $categories . ' categoria(ias)',
                'suggestion' => 'Crie ' . $needed . ' categoria(ias) adicional(is) para melhor organização',
                'action' => [
                    'label' => 'Gerenciar categorias',
                    'url' => 'categorias'
                ],
                'impact' => '+10 pontos no score',
                'impact_points' => 10,
            ];
        }

        // Insight 4: Saldo
        if ($saldoAtual < 0) {
            $insights[] = [
                'type' => 'negative_balance',
                'priority' => 'critical',
                'title' => '🚨 Saldo negativo',
                'message' => 'Seu saldo está negativo em R$ ' . number_format(abs($saldoAtual), 2, ',', '.'),
                'suggestion' => 'Aumente suas receitas ou reduza despesas urgentemente',
                'action' => [
                    'label' => 'Adicionar receita',
                    'url' => 'lancamentos?tipo=receita'
                ],
                'impact' => '+15 pontos no score',
                'impact_points' => 15,
            ];
        } else if ($saldoAtual < $despesas) {
            $insights[] = [
                'type' => 'low_balance',
                'priority' => 'medium',
                'title' => '💡 Fortaleça seu saldo',
                'message' => 'Seu saldo é inferior ao gasto mensal',
                'suggestion' => 'Economize para ter uma reserva de segurança (idealmente 2-3x suas despesas)',
                'action' => [
                    'label' => 'Ver evolução',
                    'url' => 'dashboard'
                ],
                'impact' => '+15 pontos no score',
                'impact_points' => 15,
            ];
        }

        // Insight 5: Metas
        $metasAtivas = $this->metaRepo->countAtivas($userId);
        if ($metasAtivas === 0) {
            $insights[] = [
                'type' => 'no_goals',
                'priority' => 'medium',
                'title' => '🎯 Defina suas metas',
                'message' => 'Você não tem nenhuma meta financeira ativa',
                'suggestion' => 'Crie metas para economizar, quitar dívidas ou investir',
                'action' => [
                    'label' => 'Criar meta',
                    'url' => 'financas#metas'
                ],
                'impact' => '+10 pontos no score',
                'impact_points' => 10,
            ];
        }

        // Ordenar por prioridade
        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($insights, fn($a, $b) => $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']]);

        // Soma real dos pontos dos insights exibidos (top 3)
        $topInsights = array_slice($insights, 0, 3);
        $totalPoints = array_sum(array_column($topInsights, 'impact_points'));

        return [
            'insights' => $topInsights,
            'total_possible_improvement' => $totalPoints > 0 ? '+' . $totalPoints . ' pontos' : 'Parabéns! Tudo certo',
        ];
    }
}
