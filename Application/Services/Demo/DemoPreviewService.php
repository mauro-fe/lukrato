<?php

declare(strict_types=1);

namespace Application\Services\Demo;

use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Application\Models\Fatura;
use Application\Models\Lancamento;
use Application\Models\Meta;
use Application\Models\OrcamentoCategoria;
use DateTimeImmutable;

class DemoPreviewService
{
    private const SOURCE = 'demo_preview';

    /**
     * @var array<int, bool>
     */
    private static array $eligibilityCache = [];

    public function shouldUsePreview(int $userId): bool
    {
        if (array_key_exists($userId, self::$eligibilityCache)) {
            return self::$eligibilityCache[$userId];
        }

        $hasRealData = Conta::forUser($userId)->exists()
            || CartaoCredito::forUser($userId)->exists()
            || Lancamento::where('user_id', $userId)->exists()
            || Meta::where('user_id', $userId)->exists()
            || OrcamentoCategoria::where('user_id', $userId)->exists()
            || Fatura::where('user_id', $userId)->exists();

        self::$eligibilityCache[$userId] = !$hasRealData;

        return self::$eligibilityCache[$userId];
    }

    public function dashboardOverview(string $month, int $limit, array $planSummary): array
    {
        $transactions = $this->buildMonthlyTransactions($month);
        $recentTransactions = array_slice($transactions, 0, max(1, $limit));
        $accounts = $this->buildAccounts();
        $budgets = $this->buildBudgets((int) substr($month, 5, 2), (int) substr($month, 0, 4));
        $goals = $this->buildGoals();

        return [
            'month' => $month,
            'metrics' => [
                'saldo' => 5380.00,
                'receitas' => 6200.00,
                'despesas' => 4180.00,
                'resultado' => 2020.00,
                'saldoAcumulado' => 5380.00,
                'view' => 'caixa',
                'count' => count($transactions),
                'categories' => 6,
            ],
            'accounts_balances' => $accounts,
            'recent_transactions' => $recentTransactions,
            'chart' => $this->buildDashboardChart($month),
            'despesas_por_categoria' => [
                ['categoria' => 'Moradia', 'icone' => 'house', 'valor' => 1450.00],
                ['categoria' => 'Alimentação', 'icone' => 'utensils', 'valor' => 860.00],
                ['categoria' => 'Lazer', 'icone' => 'clapperboard', 'valor' => 620.00],
                ['categoria' => 'Transporte', 'icone' => 'car', 'valor' => 540.00],
                ['categoria' => 'Saúde', 'icone' => 'heart-pulse', 'valor' => 330.00],
                ['categoria' => 'Assinaturas', 'icone' => 'credit-card', 'valor' => 180.00],
            ],
            'provisao' => $this->buildProvisao($month),
            'health_score' => [
                'score' => 72,
                'lancamentos' => count($transactions),
                'orcamentos' => count($budgets),
                'orcamentos_ok' => 3,
                'metas_ativas' => 2,
                'metas_concluidas' => 1,
                'savingsRate' => 32.6,
            ],
            'health_score_insights' => [
                [
                    'type' => 'savings_rate',
                    'priority' => 'high',
                    'title' => 'Sua margem de economia esta forte',
                    'message' => 'O resultado do mês esta positivo e abre espaço para acelerar metas.',
                    'impact' => '+8 pontos',
                    'action' => ['url' => 'financas#metas'],
                ],
                [
                    'type' => 'consistency',
                    'priority' => 'medium',
                    'title' => 'Seu histórico ja mostra consistência',
                    'message' => 'Manter registros recorrentes ajuda a prever o fim do mês com mais clareza.',
                    'impact' => '+5 pontos',
                    'action' => ['url' => 'lancamentos'],
                ],
                [
                    'type' => 'diversification',
                    'priority' => 'medium',
                    'title' => 'As categorias estão dando contexto bom aos gastos',
                    'message' => 'Separar alimentação, moradia e lazer facilita decidir onde cortar.',
                    'impact' => '+4 pontos',
                    'action' => ['url' => 'categorias'],
                ],
            ],
            'greeting_insight' => [
                'message' => 'Seu resultado melhorou e o mês está com folga para decidir o próximo passo.',
                'icon' => 'sparkles',
                'color' => '#10b981',
            ],
            'plan' => $planSummary,
            'meta' => $this->buildMeta('dashboard', [
                'real_account_count' => 0,
                'real_transaction_count' => 0,
                'real_category_count' => 0,
            ]),
        ];
    }

    public function dashboardEvolucao(string $month): array
    {
        return [
            'month' => $month,
            'mensal' => [
                ['label' => '02', 'receitas' => 4200.00, 'despesas' => 0.00],
                ['label' => '05', 'receitas' => 0.00, 'despesas' => 860.00],
                ['label' => '09', 'receitas' => 0.00, 'despesas' => 540.00],
                ['label' => '14', 'receitas' => 0.00, 'despesas' => 1450.00],
                ['label' => '18', 'receitas' => 900.00, 'despesas' => 0.00],
                ['label' => '21', 'receitas' => 0.00, 'despesas' => 200.00],
                ['label' => '26', 'receitas' => 0.00, 'despesas' => 620.00],
                ['label' => '28', 'receitas' => 0.00, 'despesas' => 330.00],
                ['label' => '29', 'receitas' => 1100.00, 'despesas' => 0.00],
                ['label' => '30', 'receitas' => 0.00, 'despesas' => 180.00],
            ],
            'anual' => $this->buildAnnualEvolution($month),
            'meta' => $this->buildMeta('dashboard'),
        ];
    }

    public function financeSummary(int $mes, int $ano): array
    {
        $orcamento = $this->buildBudgetSummary($mes, $ano);
        $metas = $this->buildGoalsSummary();

        return [
            'orcamento' => $orcamento,
            'metas' => $metas,
            'insights' => $this->buildBudgetInsights($mes, $ano),
            'mes' => $mes,
            'ano' => $ano,
            'meta' => $this->buildMeta('financas'),
        ];
    }

    public function metas(?string $status = null): array
    {
        $metas = $this->buildGoals();

        if (is_string($status) && $status !== '') {
            $metas = array_values(array_filter($metas, static fn(array $meta): bool => $meta['status'] === $status));
        }

        return [
            'metas' => $metas,
            'meta' => $this->buildMeta('metas'),
        ];
    }

    public function orcamentos(int $mes, int $ano): array
    {
        return [
            'orcamentos' => $this->buildBudgets($mes, $ano),
            'meta' => $this->buildMeta('orcamentos'),
        ];
    }

    public function financeInsights(int $mes, int $ano): array
    {
        return [
            'insights' => $this->buildBudgetInsights($mes, $ano),
            'meta' => $this->buildMeta('insights'),
        ];
    }

    public function contas(?string $month = null): array
    {
        return [
            'contas' => $this->buildAccounts(),
            'meta' => $this->buildMeta('contas'),
        ];
    }

    public function cartoes(): array
    {
        return [
            'cartoes' => $this->buildCards(),
            'meta' => $this->buildMeta('cartoes'),
        ];
    }

    public function cartoesResumo(): array
    {
        $cartoes = $this->buildCards();
        $limiteTotal = array_sum(array_map(static fn(array $cartao): float => (float) $cartao['limite_total'], $cartoes));
        $limiteDisponivel = array_sum(array_map(static fn(array $cartao): float => (float) $cartao['limite_disponivel'], $cartoes));
        $limiteUtilizado = $limiteTotal - $limiteDisponivel;

        return [
            'total_cartoes' => count($cartoes),
            'limite_total' => $limiteTotal,
            'limite_disponivel' => $limiteDisponivel,
            'limite_utilizado' => $limiteUtilizado,
            'percentual_uso' => $limiteTotal > 0 ? round(($limiteUtilizado / $limiteTotal) * 100, 2) : 0.0,
            'fatura_aberta' => 620.00,
            'cartoes' => $cartoes,
            'meta' => $this->buildMeta('cartoes'),
        ];
    }

    private function buildMeta(string $context, array $extra = []): array
    {
        return array_merge([
            'is_demo' => true,
            'source' => self::SOURCE,
            'context' => $context,
            'primary_action' => 'create_account',
            'title' => 'Dados de exemplo',
            'message' => 'Esses dados existem só para mostrar como o Lukrato funciona. Assim que você criar seus primeiros registros reais, a demonstração desaparece automaticamente.',
            'cta_label' => 'Criar primeira conta',
            'cta_url' => 'contas',
            'real_account_count' => 0,
        ], $extra);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAccounts(): array
    {
        return [
            [
                'id' => -101,
                'nome' => 'Conta principal',
                'instituicao' => 'Nubank',
                'instituicao_financeira_id' => 0,
                'instituicao_financeira' => [
                    'id' => 0,
                    'nome' => 'Nubank',
                    'cor_primaria' => '#8A05BE',
                    'logo_url' => null,
                ],
                'tipo_conta' => 'conta_corrente',
                'saldo_inicial' => 1200.00,
                'saldoInicial' => 1200.00,
                'saldoAtual' => 2480.00,
                'entradasTotal' => 6200.00,
                'saidasTotal' => 4920.00,
                'ativo' => true,
                'is_demo' => true,
            ],
            [
                'id' => -102,
                'nome' => 'Reserva',
                'instituicao' => 'Inter',
                'instituicao_financeira_id' => 0,
                'instituicao_financeira' => [
                    'id' => 0,
                    'nome' => 'Inter',
                    'cor_primaria' => '#FF7A00',
                    'logo_url' => null,
                ],
                'tipo_conta' => 'conta_investimento',
                'saldo_inicial' => 1900.00,
                'saldoInicial' => 1900.00,
                'saldoAtual' => 1900.00,
                'entradasTotal' => 0.00,
                'saidasTotal' => 0.00,
                'ativo' => true,
                'is_demo' => true,
            ],
            [
                'id' => -103,
                'nome' => 'Carteira do dia a dia',
                'instituicao' => 'Dinheiro',
                'instituicao_financeira_id' => 0,
                'instituicao_financeira' => [
                    'id' => 0,
                    'nome' => 'Dinheiro',
                    'cor_primaria' => '#F59E0B',
                    'logo_url' => null,
                ],
                'tipo_conta' => 'dinheiro',
                'saldo_inicial' => 1000.00,
                'saldoInicial' => 1000.00,
                'saldoAtual' => 1000.00,
                'entradasTotal' => 0.00,
                'saidasTotal' => 0.00,
                'ativo' => true,
                'is_demo' => true,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildCards(): array
    {
        $accounts = $this->buildAccounts();

        return [
            [
                'id' => -201,
                'conta_id' => -101,
                'nome_cartao' => 'Nubank Platinum',
                'bandeira' => 'visa',
                'ultimos_digitos' => '4821',
                'limite_total' => 7000.00,
                'limite_disponivel' => 4380.00,
                'limite_disponivel_real' => 4380.00,
                'limite_utilizado' => 2620.00,
                'percentual_uso' => 37.43,
                'dia_vencimento' => 12,
                'dia_fechamento' => 5,
                'cor_cartao' => '#8A05BE',
                'ativo' => true,
                'arquivado' => false,
                'numero_mascarado' => '**** **** **** 4821',
                'proximo_vencimento' => $this->nextMonthDay(12),
                'temFaturaPendente' => true,
                'conta' => $accounts[0],
                'is_demo' => true,
            ],
            [
                'id' => -202,
                'conta_id' => -101,
                'nome_cartao' => 'Inter Black',
                'bandeira' => 'mastercard',
                'ultimos_digitos' => '7714',
                'limite_total' => 4500.00,
                'limite_disponivel' => 3710.00,
                'limite_disponivel_real' => 3710.00,
                'limite_utilizado' => 790.00,
                'percentual_uso' => 17.56,
                'dia_vencimento' => 20,
                'dia_fechamento' => 10,
                'cor_cartao' => '#1F2937',
                'ativo' => true,
                'arquivado' => false,
                'numero_mascarado' => '**** **** **** 7714',
                'proximo_vencimento' => $this->nextMonthDay(20),
                'temFaturaPendente' => false,
                'conta' => $accounts[0],
                'is_demo' => true,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildGoals(): array
    {
        return [
            $this->goal(
                -301,
                'Reserva de emergencia',
                'economia',
                12000.00,
                4800.00,
                '#10b981',
                'alta',
                'ativa',
                'shield',
                'Guardar 6 meses de custos essenciais.',
                null,
                '2026-01-05',
                '2026-11-30'
            ),
            $this->goal(
                -302,
                'Viagem de férias',
                'viagem',
                5000.00,
                2800.00,
                '#3b82f6',
                'media',
                'ativa',
                'plane',
                'Meta para viajar sem apertar o caixa.',
                null,
                '2026-02-01',
                '2026-09-15'
            ),
            $this->goal(
                -303,
                'Notebook novo',
                'compra',
                3500.00,
                3500.00,
                '#8b5cf6',
                'media',
                'concluida',
                'laptop',
                'Meta concluída no mês passado.',
                null,
                '2025-09-01',
                '2026-02-10'
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBudgets(int $mes, int $ano): array
    {
        return [
            $this->budget(-401, -11, 'Moradia', 'house', 1800.00, 1450.00, $mes, $ano),
            $this->budget(-402, -12, 'Alimentacao', 'utensils', 1200.00, 860.00, $mes, $ano),
            $this->budget(-403, -13, 'Transporte', 'car', 600.00, 540.00, $mes, $ano),
            $this->budget(-404, -14, 'Lazer', 'clapperboard', 500.00, 620.00, $mes, $ano),
            $this->budget(-405, -15, 'Assinaturas', 'credit-card', 250.00, 180.00, $mes, $ano, true, 70.00),
        ];
    }

    private function buildGoalsSummary(): array
    {
        $goals = array_values(array_filter($this->buildGoals(), static fn(array $goal): bool => $goal['status'] === 'ativa'));
        $totalAlvo = array_sum(array_column($goals, 'valor_alvo'));
        $totalAtual = array_sum(array_column($goals, 'valor_atual'));

        return [
            'total_metas' => count($goals),
            'total_alvo' => round($totalAlvo, 2),
            'total_atual' => round($totalAtual, 2),
            'progresso_geral' => $totalAlvo > 0 ? round(($totalAtual / $totalAlvo) * 100, 1) : 0.0,
            'atrasadas' => 0,
            'proxima_concluir' => $goals[1] ?? null,
        ];
    }

    private function buildBudgetSummary(int $mes, int $ano): array
    {
        $orcamentos = $this->buildBudgets($mes, $ano);
        $totalLimite = array_sum(array_column($orcamentos, 'valor_limite'));
        $totalGasto = array_sum(array_column($orcamentos, 'gasto_real'));
        $totalDisponivel = array_sum(array_column($orcamentos, 'disponivel'));

        return [
            'total_categorias' => count($orcamentos),
            'total_limite' => round($totalLimite, 2),
            'total_gasto' => round($totalGasto, 2),
            'total_disponivel' => round($totalDisponivel, 2),
            'percentual_geral' => $totalLimite > 0 ? round(($totalGasto / $totalLimite) * 100, 1) : 0.0,
            'em_alerta' => 2,
            'estourados' => 1,
            'saude_financeira' => [
                'score' => 64,
                'label' => 'Bom',
                'cor' => '#3b82f6',
            ],
            'orcamentos' => $orcamentos,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBudgetInsights(int $mes, int $ano): array
    {
        return [
            [
                'tipo' => 'alerta',
                'icone' => 'triangle-alert',
                'cor' => '#f59e0b',
                'titulo' => 'Transporte está em 90%',
                'mensagem' => 'Restam R$ 60,00 para fechar o mês dentro do limite.',
            ],
            [
                'tipo' => 'perigo',
                'icone' => 'circle-alert',
                'cor' => '#ef4444',
                'titulo' => 'Lazer estourou o orçamento',
                'mensagem' => 'Excedido em R$ 120,00 no mês de referência.',
            ],
            [
                'tipo' => 'positivo',
                'icone' => 'circle-check',
                'cor' => '#10b981',
                'titulo' => 'Alimentação segue sob controle',
                'mensagem' => 'O uso ficou abaixo de 75% com margem para o restante do mês.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthlyTransactions(string $month): array
    {
        $transactions = [
            $this->transaction(-501, $month, 30, 'despesa', 180.00, 'Streaming e apps', 'Assinaturas', 'credit-card', 'Conta principal'),
            $this->transaction(-502, $month, 29, 'receita', 1100.00, 'Freelance do mês', 'Trabalho extra', 'briefcase', 'Conta principal'),
            $this->transaction(-503, $month, 28, 'despesa', 330.00, 'Farmácia e consulta', 'Saude', 'heart-pulse', 'Conta principal'),
            $this->transaction(-504, $month, 26, 'despesa', 620.00, 'Lazer do fim de semana', 'Lazer', 'clapperboard', 'Conta principal'),
            $this->transaction(-505, $month, 21, 'despesa', 200.00, 'Taxas e imprevistos', 'Taxas', 'receipt', 'Conta principal'),
            $this->transaction(-506, $month, 18, 'receita', 900.00, 'Reembolso corporativo', 'Reembolso', 'banknote', 'Conta principal'),
            $this->transaction(-507, $month, 14, 'despesa', 1450.00, 'Aluguel e condominio', 'Moradia', 'house', 'Conta principal'),
            $this->transaction(-508, $month, 9, 'despesa', 540.00, 'Combustivel e app', 'Transporte', 'car', 'Conta principal'),
            $this->transaction(-509, $month, 5, 'despesa', 860.00, 'Supermercado do mes', 'Alimentacao', 'utensils', 'Conta principal'),
            $this->transaction(-510, $month, 2, 'receita', 4200.00, 'Salario principal', 'Salario', 'briefcase', 'Conta principal'),
        ];

        usort($transactions, static function (array $left, array $right): int {
            return strcmp($right['data'], $left['data']);
        });

        return $transactions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDashboardChart(string $month): array
    {
        $months = $this->previousMonths($month, 6);
        $results = [-320.00, 480.00, 910.00, 760.00, 1540.00, 2020.00];

        return array_map(static function (string $chartMonth, float $resultado): array {
            return [
                'month' => $chartMonth,
                'resultado' => $resultado,
            ];
        }, $months, $results);
    }

    private function buildProvisao(string $month): array
    {
        return [
            'provisao' => [
                'saldo_atual' => 5380.00,
                'a_pagar' => 1890.00,
                'a_receber' => 1220.00,
                'saldo_projetado' => 4710.00,
                'count_pagar' => 4,
                'count_receber' => 2,
                'count_faturas' => 1,
            ],
            'vencidos' => [
                'count' => 2,
                'total' => 709.90,
                'despesas' => [
                    'count' => 1,
                    'total' => 89.90,
                ],
                'receitas' => [
                    'count' => 0,
                    'total' => 0.00,
                ],
                'count_faturas' => 1,
                'total_faturas' => 620.00,
            ],
            'proximos' => [
                [
                    'titulo' => 'Internet residencial',
                    'valor' => 129.90,
                    'tipo' => 'despesa',
                    'categoria' => 'Moradia',
                    'data_pagamento' => $this->dateInMonth($month, 24),
                    'recorrente' => true,
                    'eh_parcelado' => false,
                    'numero_parcelas' => 1,
                    'is_fatura' => false,
                ],
                [
                    'titulo' => 'Fatura Nubank Platinum',
                    'valor' => 620.00,
                    'tipo' => 'despesa',
                    'categoria' => 'Cartao',
                    'data_pagamento' => $this->dateInMonth($month, 27),
                    'is_fatura' => true,
                    'cartao_id' => -201,
                    'cartao_ultimos_digitos' => '4821',
                ],
                [
                    'titulo' => 'Salario complementar',
                    'valor' => 1220.00,
                    'tipo' => 'receita',
                    'categoria' => 'Receitas',
                    'data_pagamento' => $this->dateInMonth($month, 28),
                    'recorrente' => false,
                    'eh_parcelado' => false,
                    'numero_parcelas' => 1,
                    'is_fatura' => false,
                ],
            ],
            'parcelas' => [
                'ativas' => 2,
                'total_mensal' => 218.70,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAnnualEvolution(string $month): array
    {
        $months = $this->previousMonths($month, 12);
        $series = [
            ['receitas' => 4700.00, 'despesas' => 5020.00],
            ['receitas' => 4900.00, 'despesas' => 4680.00],
            ['receitas' => 5100.00, 'despesas' => 4560.00],
            ['receitas' => 5400.00, 'despesas' => 4830.00],
            ['receitas' => 5600.00, 'despesas' => 4520.00],
            ['receitas' => 5750.00, 'despesas' => 4710.00],
            ['receitas' => 5900.00, 'despesas' => 4980.00],
            ['receitas' => 6100.00, 'despesas' => 4850.00],
            ['receitas' => 6000.00, 'despesas' => 5240.00],
            ['receitas' => 6150.00, 'despesas' => 4610.00],
            ['receitas' => 6050.00, 'despesas' => 4540.00],
            ['receitas' => 6200.00, 'despesas' => 4180.00],
        ];

        return array_map(function (string $yearMonth, array $row): array {
            $label = $this->formatMonthLabel($yearMonth);
            $saldo = (float) $row['receitas'] - (float) $row['despesas'];

            return [
                'label' => $label,
                'month' => $yearMonth,
                'receitas' => (float) $row['receitas'],
                'despesas' => (float) $row['despesas'],
                'saldo' => $saldo,
            ];
        }, $months, $series);
    }

    private function budget(
        int $id,
        int $categoriaId,
        string $categoriaNome,
        string $categoriaIcone,
        float $limite,
        float $gasto,
        int $mes,
        int $ano,
        bool $rollover = false,
        float $rolloverValor = 0.0
    ): array {
        $limiteEfetivo = $limite + $rolloverValor;
        $percentual = $limiteEfetivo > 0 ? round(($gasto / $limiteEfetivo) * 100, 1) : 0.0;
        $disponivel = round(max(0, $limiteEfetivo - $gasto), 2);
        $excedido = round(max(0, $gasto - $limiteEfetivo), 2);

        return [
            'id' => $id,
            'categoria_id' => $categoriaId,
            'categoria_nome' => $categoriaNome,
            'categoria' => [
                'id' => $categoriaId,
                'nome' => $categoriaNome,
                'icone' => $categoriaIcone,
            ],
            'valor_limite' => $limite,
            'rollover' => $rollover,
            'rollover_valor' => $rolloverValor,
            'limite_efetivo' => $limiteEfetivo,
            'gasto_real' => $gasto,
            'disponivel' => $disponivel,
            'excedido' => $excedido,
            'percentual' => min($percentual, 999.0),
            'status' => $percentual > 100 ? 'estourado' : ($percentual >= 80 ? 'alerta' : 'ok'),
            'alerta_80' => true,
            'alerta_100' => true,
            'mes' => $mes,
            'ano' => $ano,
            'is_demo' => true,
        ];
    }

    private function goal(
        int $id,
        string $titulo,
        string $tipo,
        float $valorAlvo,
        float $valorAtual,
        string $cor,
        string $prioridade,
        string $status,
        string $icone,
        string $descricao,
        ?int $contaId,
        string $dataInicio,
        ?string $dataPrazo
    ): array {
        $progresso = $valorAlvo > 0 ? round(($valorAtual / $valorAlvo) * 100, 1) : 0.0;
        $valorRestante = round(max(0, $valorAlvo - $valorAtual), 2);
        $diasRestantes = null;
        $aporteMensal = null;

        if (is_string($dataPrazo) && $dataPrazo !== '') {
            $today = new DateTimeImmutable('today');
            $prazo = new DateTimeImmutable($dataPrazo);
            $diff = $today->diff($prazo);
            $diasRestantes = $diff->invert ? -$diff->days : $diff->days;

            if ($diasRestantes > 0 && $valorRestante > 0) {
                $mesesRestantes = max(1, (int) ceil($diasRestantes / 30));
                $aporteMensal = round($valorRestante / $mesesRestantes, 2);
            } elseif ($valorRestante <= 0) {
                $aporteMensal = 0.0;
            }
        }

        return [
            'id' => $id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'tipo' => $tipo,
            'valor_alvo' => $valorAlvo,
            'valor_atual' => $valorAtual,
            'data_inicio' => $dataInicio,
            'data_prazo' => $dataPrazo,
            'icone' => $icone,
            'cor' => $cor,
            'conta_id' => $contaId,
            'prioridade' => $prioridade,
            'status' => $status,
            'progresso' => $progresso,
            'valor_restante' => $valorRestante,
            'dias_restantes' => $diasRestantes,
            'aporte_mensal_sugerido' => $aporteMensal,
            'is_atrasada' => $diasRestantes !== null && $diasRestantes < 0 && $status === 'ativa',
            'is_completa' => $valorAtual >= $valorAlvo,
            'conta_nome' => null,
            'created_at' => $dataInicio . ' 08:00:00',
            'is_demo' => true,
        ];
    }

    private function transaction(
        int $id,
        string $month,
        int $day,
        string $tipo,
        float $valor,
        string $descricao,
        string $categoria,
        string $categoriaIcone,
        string $conta
    ): array {
        return [
            'id' => $id,
            'data' => $this->dateInMonth($month, $day),
            'tipo' => $tipo,
            'valor' => $valor,
            'descricao' => $descricao,
            'categoria_id' => $id * -1,
            'conta_id' => -101,
            'categoria' => $categoria,
            'categoria_icone' => $categoriaIcone,
            'conta' => $conta,
            'pago' => true,
            'is_demo' => true,
        ];
    }

    private function previousMonths(string $month, int $count): array
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m', $month) ?: new DateTimeImmutable('first day of this month');
        $months = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $months[] = $date->modify(sprintf('-%d month', $i))->format('Y-m');
        }

        return $months;
    }

    private function dateInMonth(string $month, int $day): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m', $month) ?: new DateTimeImmutable('first day of this month');
        $lastDay = (int) $date->format('t');
        $safeDay = max(1, min($day, $lastDay));

        return $date->setDate((int) $date->format('Y'), (int) $date->format('m'), $safeDay)->format('Y-m-d');
    }

    private function nextMonthDay(int $day): string
    {
        $nextMonth = new DateTimeImmutable('first day of next month');
        $lastDay = (int) $nextMonth->format('t');
        $safeDay = max(1, min($day, $lastDay));

        return $nextMonth->setDate((int) $nextMonth->format('Y'), (int) $nextMonth->format('m'), $safeDay)->format('Y-m-d');
    }

    private function formatMonthLabel(string $yearMonth): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m', $yearMonth);
        if (!$date) {
            return $yearMonth;
        }

        $labels = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];

        $monthNum = (int) $date->format('n');

        return sprintf('%s/%s', $labels[$monthNum], $date->format('y'));
    }
}
