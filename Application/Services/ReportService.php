<?php

namespace Application\Services;

use Application\Enums\ReportType;
use Application\Enums\LancamentoTipo;
use Application\DTO\ReportParameters;
use Application\Repositories\ReportRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ReportService
{
    private ReportRepository $repository;

    public function __construct(?ReportRepository $repository = null)
    {
        $this->repository = $repository ?? new ReportRepository();
    }

    /**
     * Ponto de entrada do serviço.
     */
    public function generateReport(ReportType $type, ReportParameters $params): array
    {
        return match ($type) {
            ReportType::DESPESAS_POR_CATEGORIA =>
            $this->handleCategoriasReport(LancamentoTipo::DESPESA, $params),

            ReportType::DESPESAS_ANUAIS_POR_CATEGORIA =>
            $this->handleAnnualCategoriasReport(LancamentoTipo::DESPESA, $params),

            ReportType::RECEITAS_ANUAIS_POR_CATEGORIA =>
            $this->handleAnnualCategoriasReport(LancamentoTipo::RECEITA, $params),

            ReportType::RECEITAS_POR_CATEGORIA =>
            $this->handleCategoriasReport(LancamentoTipo::RECEITA, $params),

            ReportType::SALDO_MENSAL =>
            $this->handleSaldoMensalReport($params),

            ReportType::RECEITAS_DESPESAS_DIARIO =>
            $this->handleReceitasDespesasDiarioReport($params),

            ReportType::EVOLUCAO_12M =>
            $this->handleEvolucao12MReport($params),

            ReportType::RECEITAS_DESPESAS_POR_CONTA =>
            $this->handleReceitasDespesasPorContaReport($params),

            ReportType::RESUMO_ANUAL =>
            $this->handleResumoAnualReport($params),

            ReportType::CARTOES_CREDITO =>
            $this->handleCartoesCreditoReport($params),

            default =>
            throw new InvalidArgumentException("Tipo de relatório '{$type->value}' não suportado."),
        };
    }

    // --- Relatórios de Categoria ---

    private function handleCategoriasReport(LancamentoTipo $tipo, ReportParameters $params): array
    {
        $data = $this->repository->getCategoryTotals($tipo->value, $params);

        return [
            'labels' => $data->pluck('label')->values()->all(),
            'values' => $data->pluck('total')->map(fn($v) => (float)$v)->values()->all(),
            'total' => $data->sum(fn($row) => (float)$row->total),
        ];
    }

    private function handleAnnualCategoriasReport(LancamentoTipo $tipo, ReportParameters $params): array
    {
        [$yearStart, $yearEnd, $year] = $this->getYearRange($params);

        $annualParams = new ReportParameters(
            $yearStart,
            $yearEnd,
            $params->accountId,
            $params->userId,
            $params->includeTransfers
        );

        $report = $this->handleCategoriasReport($tipo, $annualParams);

        return array_merge($report, [
            'year' => $year,
            'start' => $yearStart->toDateString(),
            'end' => $yearEnd->toDateString(),
            'kind' => $tipo->value,
        ]);
    }

    // --- Relatórios de Saldo ---

    private function handleSaldoMensalReport(ReportParameters $params): array
    {
        $deltas = $this->repository->getDailyDelta($params, $params->useTransfers());
        $saldoInicial = $this->getSaldoInicial($params);

        $series = $this->buildDailySeries($params, $deltas, $saldoInicial);

        return [
            'labels' => $series['labels'],
            'values' => $series['values'],
            'total' => end($series['values']) ?: 0.0,
        ];
    }

    private function handleEvolucao12MReport(ReportParameters $params): array
    {
        [$ini, $fim] = $this->get12MonthsRange($params);

        $deltas = $this->repository->getMonthlyDelta($ini, $fim, $params, $params->useTransfers());
        $saldoInicial = $this->getSaldoInicial($params, $ini);

        $series = $this->buildMonthlySeries($ini, $fim, $deltas, $saldoInicial);

        return [
            'labels' => $series['labels'],
            'values' => $series['values'],
            'start' => $ini->toDateString(),
            'end' => $fim->toDateString(),
        ];
    }

    // --- Relatórios de Receitas/Despesas ---

    private function handleReceitasDespesasDiarioReport(ReportParameters $params): array
    {
        $rows = $this->repository->getDailyRecDes($params, $params->useTransfers());

        $data = $this->buildDailyRecDesData($params, $rows);

        return [
            'labels' => $data['labels'],
            'receitas' => $data['receitas'],
            'despesas' => $data['despesas'],
        ];
    }

    private function handleReceitasDespesasPorContaReport(ReportParameters $params): array
    {
        $data = $this->repository->getTotalsByAccount($params);

        return [
            'labels' => $data->pluck('conta')->values()->all(),
            'receitas' => $data->pluck('receitas')->map(fn($v) => (float)$v)->values()->all(),
            'despesas' => $data->pluck('despesas')->map(fn($v) => (float)$v)->values()->all(),
        ];
    }

    private function handleResumoAnualReport(ReportParameters $params): array
    {
        [$yearStart, $yearEnd, $year] = $this->getYearRange($params);

        $rows = $this->repository->getMonthlyRecDesForYear(
            $yearStart,
            $yearEnd,
            $params,
            $params->useTransfers()
        );

        $data = $this->buildYearlyRecDesData($rows, $year);

        return [
            'labels' => $data['labels'],
            'receitas' => $data['receitas'],
            'despesas' => $data['despesas'],
            'start' => $yearStart->toDateString(),
            'end' => $yearEnd->toDateString(),
            'year' => $year,
        ];
    }

    // --- Helpers de Construção de Séries ---

    private function buildDailySeries(
        ReportParameters $params,
        Collection $deltas,
        float $saldoInicial
    ): array {
        return $this->buildRunningTotalSeries(
            start: $params->start,
            end: $params->end,
            deltas: $deltas,
            initialBalance: $saldoInicial,
            labelFormat: 'd/m',
            dateKeyFormat: 'Y-m-d',
            incrementFn: fn(Carbon $c) => $c->addDay()
        );
    }

    private function buildMonthlySeries(
        Carbon $start,
        Carbon $end,
        Collection $deltas,
        float $saldoInicial
    ): array {
        return $this->buildRunningTotalSeries(
            start: $start,
            end: $end,
            deltas: $deltas,
            initialBalance: $saldoInicial,
            labelFormat: 'm/Y',
            dateKeyFormat: 'Y-m-01',
            incrementFn: fn(Carbon $c) => $c->addMonth()
        );
    }

    private function buildRunningTotalSeries(
        Carbon $start,
        Carbon $end,
        Collection $deltas,
        float $initialBalance,
        string $labelFormat,
        string $dateKeyFormat,
        \Closure $incrementFn
    ): array {
        $labels = [];
        $values = [];
        $running = $initialBalance;
        $cursor = clone $start;

        while ($cursor <= $end) {
            $key = $cursor->format($dateKeyFormat);
            $labels[] = $cursor->format($labelFormat);

            $delta = $deltas->get($key);
            $running += (float)($delta?->delta ?? $delta?->saldo ?? 0.0);

            $values[] = round($running, 2);
            $incrementFn($cursor);
        }

        return compact('labels', 'values');
    }

    private function buildDailyRecDesData(ReportParameters $params, Collection $rows): array
    {
        $labels = [];
        $receitas = [];
        $despesas = [];
        $cursor = clone $params->start;

        while ($cursor <= $params->end) {
            $key = $cursor->toDateString();
            $row = $rows->get($key);

            $labels[] = $cursor->format('d/m');
            $receitas[] = (float)($row?->receitas ?? 0.0);
            $despesas[] = (float)($row?->despesas ?? 0.0);

            $cursor->addDay();
        }

        return compact('labels', 'receitas', 'despesas');
    }

    private function buildYearlyRecDesData(Collection $rows, int $year): array
    {
        $byMonth = $rows->keyBy('mes')->map(fn($row) => [
            'receitas' => (float)$row->receitas,
            'despesas' => (float)$row->despesas,
        ])->all();

        $monthNames = [
            'Jan',
            'Fev',
            'Mar',
            'Abr',
            'Mai',
            'Jun',
            'Jul',
            'Ago',
            'Set',
            'Out',
            'Nov',
            'Dez'
        ];

        $labels = [];
        $receitas = [];
        $despesas = [];

        for ($m = 1; $m <= 12; $m++) {
            $labels[] = sprintf('%s/%d', $monthNames[$m - 1], $year);
            $receitas[] = $byMonth[$m]['receitas'] ?? 0.0;
            $despesas[] = $byMonth[$m]['despesas'] ?? 0.0;
        }

        return compact('labels', 'receitas', 'despesas');
    }

    // --- Helpers de Data e Saldo ---

    private function getSaldoInicial(ReportParameters $params, ?Carbon $customStart = null): float
    {
        $start = $customStart ?? $params->start;
        $dataAnterior = (clone $start)->subDay()->endOfDay();

        return $this->repository->saldoAte(
            $dataAnterior,
            $params,
            $params->useTransfers()
        );
    }

    private function get12MonthsRange(ReportParameters $params): array
    {
        $ini = (clone $params->start)->subMonthsNoOverflow(11)->startOfMonth();
        $fim = clone $params->end;

        return [$ini, $fim];
    }

    private function getYearRange(ReportParameters $params): array
    {
        $year = $params->start->year;
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = (clone $yearStart)->endOfYear()->endOfDay();

        return [$yearStart, $yearEnd, $year];
    }

    // --- Relatório de Cartões de Crédito ---

    /**
     * Calcula o status de saúde financeira de um cartão
     */
    private function calculateCardHealth(float $percentual, float $fatura, float $mediaHistorica): array
    {
        $status = 'saudavel';
        $cor = '#2ecc71';
        $texto = 'Cartão saudável';
        $insight = '';
        $recomendacao = '';

        if ($percentual >= 85) {
            $status = 'critico';
            $cor = '#e74c3c';
            $texto = 'Limite Crítico';
            $insight = '⚠️ Seu limite está quase esgotado! Evite novos gastos para não pagar juros.';
            $recomendacao = 'Evite novos gastos até o próximo vencimento.';
        } elseif ($percentual >= 70) {
            $status = 'alto_uso';
            $cor = '#f39c12';
            $texto = 'Uso Elevado';
            $insight = '📉 Você já usou mais de 70% do limite. Cuidado para não comprometer seu orçamento.';
            $recomendacao = 'Considere reduzir gastos nos próximos dias.';
        } elseif ($percentual >= 50) {
            $status = 'atencao';
            $cor = '#e67e22';
            $texto = 'Atenção';
            $insight = '👀 Você está usando metade do limite. Monitore seus gastos.';
            $recomendacao = 'Tente manter abaixo de 50% para uma saúde financeira melhor.';
        } elseif ($percentual >= 30) {
            $status = 'moderado';
            $cor = '#3498db';
            $texto = 'Uso Moderado';
            $insight = '👍 Seu uso está dentro de um padrão saudável.';
            $recomendacao = 'Continue monitorando seus gastos regularmente.';
        } else {
            $status = 'saudavel';
            $cor = '#2ecc71';
            $texto = 'Ótimo Controle';
            $insight = '🎉 Excelente! Você está mantendo um ótimo controle dos gastos.';
            $recomendacao = 'Continue assim! Manter abaixo de 30% é ideal.';
        }

        // Comparação com média
        $comparacao = '';
        if ($mediaHistorica > 0) {
            $diferenca = (($fatura - $mediaHistorica) / $mediaHistorica) * 100;
            if (abs($diferenca) > 5) {
                if ($diferenca > 0) {
                    $comparacao = sprintf('📈 Você gastou %.0f%% a mais que sua média.', abs($diferenca));
                } else {
                    $comparacao = sprintf('📉 Você gastou %.0f%% a menos que sua média!', abs($diferenca));
                }
            }
        }

        return [
            'status' => $status,
            'cor' => $cor,
            'texto' => $texto,
            'percentual' => round($percentual, 1),
            'insight' => $insight,
            'recomendacao' => $recomendacao,
            'comparacao' => $comparacao
        ];
    }

    /**
     * Gera insights acionáveis baseados nos dados do cartão
     */
    private function generateCardInsights(array $cardData, array $historicoMeses): array
    {
        $insights = [
            'tendencia' => null,
            'parcelamentos' => null,
            'limite' => null
        ];

        // Insight de tendência
        if (count($historicoMeses) >= 3) {
            $ultimos3 = array_slice($historicoMeses, -3);
            $valores = array_column($ultimos3, 'valor');

            if ($valores[2] > $valores[1] && $valores[1] > $valores[0]) {
                // Evita divisão por zero
                $valorBase = max($valores[0], 0.01);
                $variacao = (($valores[2] - $valores[0]) / $valorBase) * 100;
                $insights['tendencia'] = [
                    'type' => 'warning',
                    'icon' => 'fa-arrow-trend-up',
                    'status' => 'Gastos Aumentando',
                    'variacao' => sprintf('+%.1f%%', $variacao),
                    'descricao' => 'Seus gastos aumentaram nos últimos 3 meses',
                    'recomendacao' => 'Revise suas despesas e identifique onde você pode economizar'
                ];
            } elseif ($valores[2] < $valores[1] && $valores[1] < $valores[0]) {
                // Evita divisão por zero
                $valorBase = max($valores[0], 0.01);
                $variacao = (($valores[0] - $valores[2]) / $valorBase) * 100;
                $insights['tendencia'] = [
                    'type' => 'success',
                    'icon' => 'fa-arrow-trend-down',
                    'status' => 'Gastos Reduzindo',
                    'variacao' => sprintf('-%.1f%%', $variacao),
                    'descricao' => 'Você está controlando bem seus gastos',
                    'recomendacao' => 'Continue assim! Seu controle financeiro está melhorando'
                ];
            } else {
                $insights['tendencia'] = [
                    'type' => 'info',
                    'icon' => 'fa-minus',
                    'status' => 'Gastos Estáveis',
                    'variacao' => '~0%',
                    'descricao' => 'Seus gastos estão consistentes',
                    'recomendacao' => 'Mantenha o controle para evitar surpresas'
                ];
            }
        }

        // Insight de parcelamentos
        if (isset($cardData['parcelamentos']) && $cardData['parcelamentos']['ativos'] > 0) {
            $totalComprometido = $cardData['parcelamentos']['valor_total'];
            $qtdParcelas = $cardData['parcelamentos']['ativos'];
            $percentualComprometido = ($totalComprometido / $cardData['limite']) * 100;

            $insights['parcelamentos'] = [
                'type' => $percentualComprometido > 30 ? 'warning' : 'info',
                'icon' => 'fa-calendar-days',
                'status' => sprintf('%d Parcelamento%s Ativo%s', $qtdParcelas, $qtdParcelas > 1 ? 's' : '', $qtdParcelas > 1 ? 's' : ''),
                'valor' => sprintf('R$ %.2f', $totalComprometido),
                'descricao' => sprintf('%.1f%% do limite comprometido mensalmente', $percentualComprometido),
                'recomendacao' => $percentualComprometido > 30
                    ? 'Evite novos parcelamentos até reduzir o comprometimento'
                    : 'Seus parcelamentos estão em níveis controlados'
            ];
        }

        // Insight de limite
        $percentualUso = $cardData['percentual'];
        if ($percentualUso < 30) {
            $insights['limite'] = [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'status' => 'Uso Excelente',
                'percentual' => sprintf('%.1f%%', $percentualUso),
                'descricao' => 'Você está usando pouco do seu limite',
                'recomendacao' => 'Isso é ótimo para seu score de crédito!'
            ];
        } elseif ($percentualUso < 50) {
            $insights['limite'] = [
                'type' => 'info',
                'icon' => 'fa-circle-info',
                'status' => 'Uso Moderado',
                'percentual' => sprintf('%.1f%%', $percentualUso),
                'descricao' => 'Você está usando uma parte razoável do limite',
                'recomendacao' => 'Tente manter abaixo de 30% para melhorar seu score'
            ];
        } elseif ($percentualUso < 80) {
            $insights['limite'] = [
                'type' => 'warning',
                'icon' => 'fa-triangle-exclamation',
                'status' => 'Uso Elevado',
                'percentual' => sprintf('%.1f%%', $percentualUso),
                'descricao' => 'Você está usando mais da metade do limite',
                'recomendacao' => 'Reduza o uso para evitar impacto no score de crédito'
            ];
        } else {
            $insights['limite'] = [
                'type' => 'danger',
                'icon' => 'fa-circle-exclamation',
                'status' => 'Uso Crítico',
                'percentual' => sprintf('%.1f%%', $percentualUso),
                'descricao' => 'Você está próximo ou acima do limite recomendado',
                'recomendacao' => 'URGENTE: Reduza gastos e evite novos lançamentos'
            ];
        }

        return $insights;
    }

    private function handleCartoesCreditoReport(ReportParameters $params): array
    {
        $userId = $params->userId;
        $month = $params->start->format('Y-m');
        $dataInicio = $params->start->toDateString();
        $dataFim = $params->end->toDateString();

        // Busca os cartões do usuário
        $query = \Application\Models\CartaoCredito::where('user_id', $userId)
            ->where('ativo', 1);

        // Aplica filtro de conta se especificado
        if ($params->accountId) {
            $query->where('conta_id', $params->accountId);
        }

        $cartoes = $query->get();

        $cards = [];
        foreach ($cartoes as $cartao) {
            // Soma os lançamentos do cartão no mês
            $totalMes = \Application\Models\Lancamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->where('tipo', 'despesa')
                ->whereBetween('data', [$dataInicio, $dataFim])
                ->sum('valor');

            // Busca parcelamentos ativos deste cartão
            $parcelamentosAtivos = \Application\Models\Parcelamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->where('status', 'ativo')
                ->get();

            $totalParcelamentos = $parcelamentosAtivos->sum('valor_total');
            $qtdParcelamentos = $parcelamentosAtivos->count();

            // Calcula impacto nos próximos 3 meses
            $proximosMeses = [];
            for ($i = 1; $i <= 3; $i++) {
                $mesAnalise = $params->start->copy()->addMonths($i);
                $valorMes = \Application\Models\Lancamento::where('user_id', $userId)
                    ->where('cartao_credito_id', $cartao->id)
                    ->where('tipo', 'despesa')
                    ->whereYear('data', $mesAnalise->year)
                    ->whereMonth('data', $mesAnalise->month)
                    ->sum('valor');

                $proximosMeses[] = [
                    'mes' => $mesAnalise->locale('pt_BR')->isoFormat('MMM/YY'),
                    'valor' => (float) $valorMes
                ];
            }

            $limite = (float) ($cartao->limite_total ?? $cartao->limite ?? 0);
            $faturaAtual = (float) $totalMes;
            $disponivel = max(0, $limite - $faturaAtual);
            $percentual = $limite > 0 ? ($faturaAtual / $limite) * 100 : 0;

            // Busca histórico dos últimos 6 meses para cálculo de média e tendência
            $historicoMeses = [];
            $totalHistorico = 0;
            for ($i = 5; $i >= 0; $i--) {
                $mesHistorico = $params->start->copy()->subMonths($i);
                $valorMes = \Application\Models\Lancamento::where('user_id', $userId)
                    ->where('cartao_credito_id', $cartao->id)
                    ->where('tipo', 'despesa')
                    ->whereYear('data', $mesHistorico->year)
                    ->whereMonth('data', $mesHistorico->month)
                    ->sum('valor');

                $historicoMeses[] = [
                    'mes' => $mesHistorico->locale('pt_BR')->isoFormat('MMM'),
                    'mes_completo' => $mesHistorico->locale('pt_BR')->isoFormat('MMM/YY'),
                    'valor' => (float) $valorMes
                ];
                $totalHistorico += (float) $valorMes;
            }
            $mediaHistorica = count($historicoMeses) > 0 ? $totalHistorico / count($historicoMeses) : 0;

            // Calcular tendência
            $tendencia = 'estavel';
            if (count($historicoMeses) >= 3) {
                $ultimos3 = array_slice($historicoMeses, -3);
                $valores = array_column($ultimos3, 'valor');
                if ($valores[2] > $valores[1] && $valores[1] > $valores[0]) {
                    $tendencia = 'subindo';
                } elseif ($valores[2] < $valores[1] && $valores[1] < $valores[0]) {
                    $tendencia = 'caindo';
                }
            }

            // Calcular status de saúde
            $statusSaude = $this->calculateCardHealth($percentual, $faturaAtual, $mediaHistorica);

            $disponivel = max(0, $limite - $faturaAtual);
            $percentual = $limite > 0 ? ($faturaAtual / $limite) * 100 : 0;

            // Gera alertas
            $alertas = [];

            // Verifica se há lançamentos futuros próximos
            $lancamentosFuturosProximos = \Application\Models\Lancamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->where('tipo', 'despesa')
                ->where('data', '>', $dataFim)
                ->where('data', '<=', $params->start->copy()->addMonth()->endOfMonth()->toDateString())
                ->sum('valor');

            if ($lancamentosFuturosProximos > 0) {
                $alertas[] = [
                    'type' => 'info',
                    'message' => 'Próximo mês: ' . number_format($lancamentosFuturosProximos, 2, ',', '.')
                ];
            }

            if ($percentual > 80) {
                $alertas[] = [
                    'type' => 'danger',
                    'message' => 'Limite quase esgotado'
                ];
            } elseif ($percentual > 50) {
                $alertas[] = [
                    'type' => 'warning',
                    'message' => 'Mais de 50% do limite usado'
                ];
            }

            if ($qtdParcelamentos > 0) {
                $alertas[] = [
                    'type' => 'info',
                    'message' => "{$qtdParcelamentos} parcelamento" . ($qtdParcelamentos > 1 ? 's' : '') . " ativo" . ($qtdParcelamentos > 1 ? 's' : '')
                ];
            }

            // Busca o nome e cor da conta
            $conta = $cartao->conta;
            $nomeConta = $conta ? ($conta->apelido ?? $conta->nome ?? 'Conta') : 'Sem conta';
            $corConta = $conta && $conta->cor ? $conta->cor : '#E67E22';

            $cardData = [
                'id' => $cartao->id,
                'nome' => $cartao->nome_cartao ?? $cartao->nome ?? 'Cartão',
                'bandeira' => strtolower($cartao->bandeira ?? 'outros'),
                'conta' => $nomeConta,
                'cor' => $corConta,
                'limite' => $limite,
                'fatura_atual' => $faturaAtual,
                'disponivel' => $disponivel,
                'percentual' => round($percentual, 1),
                'dia_vencimento' => $cartao->dia_vencimento,
                'alertas' => $alertas,
                'parcelamentos' => [
                    'ativos' => $qtdParcelamentos,
                    'valor_total' => $totalParcelamentos
                ],
                'proximos_meses' => $proximosMeses,
                'status_saude' => $statusSaude,
                'media_historica' => round($mediaHistorica, 2),
                'tendencia' => $tendencia,
                'historico_6_meses' => $historicoMeses,
            ];

            // Gera insights acionáveis
            $cardData['insights'] = $this->generateCardInsights($cardData, $historicoMeses);

            $cards[] = $cardData;
        }

        // Calcular resumo consolidado
        $totalFaturas = array_sum(array_column($cards, 'fatura_atual'));
        $totalLimites = array_sum(array_column($cards, 'limite'));
        $utilizacaoGeral = $totalLimites > 0 ? ($totalFaturas / $totalLimites) * 100 : 0;

        // Ordenar por utilização (maior primeiro) para identificar cartões que precisam atenção
        usort($cards, function ($a, $b) {
            return $b['percentual'] <=> $a['percentual'];
        });

        $melhorCartao = null;
        $piorCartao = null;

        foreach ($cards as $card) {
            if ($card['fatura_atual'] > 0) {
                if (!$melhorCartao || $card['percentual'] < $melhorCartao['percentual']) {
                    $melhorCartao = $card;
                }
                if (!$piorCartao || $card['percentual'] > $piorCartao['percentual']) {
                    $piorCartao = $card;
                }
            }
        }

        $resumoConsolidado = [
            'total_faturas' => round($totalFaturas, 2),
            'total_limites' => round($totalLimites, 2),
            'total_disponivel' => round($totalLimites - $totalFaturas, 2),
            'utilizacao_geral' => round($utilizacaoGeral, 1),
            'melhor_cartao' => $melhorCartao ? [
                'nome' => $melhorCartao['nome'],
                'percentual' => $melhorCartao['percentual']
            ] : null,
            'requer_atencao' => $piorCartao && $piorCartao['percentual'] > 70 ? [
                'nome' => $piorCartao['nome'],
                'percentual' => $piorCartao['percentual'],
                'status' => $piorCartao['status_saude']['status']
            ] : null,
            'total_parcelamentos' => array_sum(array_column(array_column($cards, 'parcelamentos'), 'ativos')),
            'valor_parcelamentos' => round(array_sum(array_column(array_column($cards, 'parcelamentos'), 'valor_total')), 2)
        ];

        return [
            'cards' => $cards,
            'total' => count($cards),
            'resumo_consolidado' => $resumoConsolidado
        ];
    }

    /**
     * Gera relatório detalhado de um cartão específico
     */
    public function getCardDetailedReport(int $userId, int $cardId, string $mes, string $ano): array
    {
        // Busca o cartão
        $cartao = \Application\Models\CartaoCredito::where('id', $cardId)
            ->where('user_id', $userId)
            ->first();

        if (!$cartao) {
            throw new \Exception('Cartão não encontrado');
        }

        $mesInt = (int) $mes;
        $anoInt = (int) $ano;
        $dataInicio = "{$anoInt}-{$mes}-01";
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        // 1. FATURA DO MÊS
        $lancamentos = \Application\Models\Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cardId)
            ->where('tipo', 'despesa')
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->with('categoria')
            ->orderBy('data', 'desc')
            ->get();

        $aVista = $lancamentos->where('eh_parcelado', false)->sum('valor');
        $parcelado = $lancamentos->where('eh_parcelado', true)->sum('valor');
        $totalFatura = $lancamentos->sum('valor');

        // Calcular limite e percentual de utilização (necessário para o status de saúde)
        $limite = (float) ($cartao->limite_total ?? $cartao->limite ?? 0);
        $percentualUtilizacao = $limite > 0 ? ($totalFatura / $limite) * 100 : 0;

        // Agrupar por categoria
        $porCategoria = [];
        foreach ($lancamentos as $lanc) {
            $catId = $lanc->categoria_id;
            $catNome = $lanc->categoria ? $lanc->categoria->nome : 'Sem categoria';
            $catCor = $lanc->categoria ? ($lanc->categoria->cor ?? '#E67E22') : '#E67E22';

            if (!isset($porCategoria[$catId])) {
                $porCategoria[$catId] = [
                    'nome' => $catNome,
                    'cor' => $catCor,
                    'valor' => 0,
                    'quantidade' => 0
                ];
            }

            $porCategoria[$catId]['valor'] += (float) $lanc->valor;
            $porCategoria[$catId]['quantidade']++;
        }

        $porCategoria = array_values($porCategoria);

        // 2. EVOLUÇÃO MENSAL (últimos 6 meses)
        $evolucao = [];
        for ($i = 5; $i >= 0; $i--) {
            $dataAnalise = new \DateTime("{$anoInt}-{$mesInt}-01");
            $dataAnalise->modify("-{$i} months");

            $mesAnalise = $dataAnalise->format('m');
            $anoAnalise = $dataAnalise->format('Y');
            $inicioMes = $dataAnalise->format('Y-m-01');
            $fimMes = $dataAnalise->format('Y-m-t');

            $valorMes = \Application\Models\Lancamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cardId)
                ->where('tipo', 'despesa')
                ->whereBetween('data', [$inicioMes, $fimMes])
                ->sum('valor');

            $evolucao[] = [
                'mes' => $dataAnalise->format('M/y'),
                'mes_completo' => ucfirst(\IntlDateFormatter::formatObject(
                    $dataAnalise,
                    "MMMM 'de' yyyy",
                    'pt_BR'
                )),
                'valor' => (float) $valorMes
            ];
        }

        // Calcular tendência
        $valores = array_column($evolucao, 'valor');
        $media = count($valores) > 0 ? array_sum($valores) / count($valores) : 0;
        $ultimoValor = end($valores);
        $tendencia = $ultimoValor > $media * 1.1 ? 'subindo' : ($ultimoValor < $media * 0.9 ? 'caindo' : 'estável');

        // COMPARATIVO COM MÊS ANTERIOR
        $mesAnterior = new \DateTime("{$anoInt}-{$mesInt}-01");
        $mesAnterior->modify("-1 month");
        $inicioMesAnterior = $mesAnterior->format('Y-m-01');
        $fimMesAnterior = $mesAnterior->format('Y-m-t');

        $faturaAnterior = \Application\Models\Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cardId)
            ->where('tipo', 'despesa')
            ->whereBetween('data', [$inicioMesAnterior, $fimMesAnterior])
            ->sum('valor');

        $diferencaAbsoluta = $totalFatura - $faturaAnterior;
        $diferencaPercentual = $faturaAnterior > 0
            ? round(($diferencaAbsoluta / $faturaAnterior) * 100, 1)
            : 0;

        // STATUS DE SAÚDE
        if ($percentualUtilizacao <= 30) {
            $statusSaude = ['status' => 'saudavel', 'cor' => '#2ecc71', 'texto' => 'Saudável'];
        } elseif ($percentualUtilizacao <= 60) {
            $statusSaude = ['status' => 'atencao', 'cor' => '#f39c12', 'texto' => 'Atenção'];
        } else {
            $statusSaude = ['status' => 'risco', 'cor' => '#e74c3c', 'texto' => 'Risco'];
        }

        // 3. PARCELAMENTOS ATIVOS
        $parcelamentos = \Application\Models\Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cardId)
            ->where('eh_parcelado', true)
            ->where('pago', false)
            ->with('categoria')
            ->orderBy('data', 'asc')
            ->get();

        $parcelamentosAtivos = [];
        $parcelamentosAgrupados = [];

        foreach ($parcelamentos as $lanc) {
            $paiId = $lanc->lancamento_pai_id ?? $lanc->id;

            if (!isset($parcelamentosAgrupados[$paiId])) {
                // Extrair descrição base (sem número de parcela)
                $descricao = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $lanc->descricao);

                $parcelamentosAgrupados[$paiId] = [
                    'descricao' => $descricao,
                    'categoria' => $lanc->categoria ? $lanc->categoria->nome : 'Sem categoria',
                    'categoria_cor' => $lanc->categoria ? ($lanc->categoria->cor ?? '#E67E22') : '#E67E22',
                    'parcela_atual' => $lanc->parcela_atual ?? 1,
                    'total_parcelas' => $lanc->total_parcelas ?? 1,
                    'valor_parcela' => (float) $lanc->valor,
                    'parcelas_restantes' => 0,
                    'valor_total_restante' => 0,
                    'data_final' => null,
                    'lancamentos' => []
                ];
            }

            $parcelamentosAgrupados[$paiId]['lancamentos'][] = $lanc;
        }

        // Calcular totais
        foreach ($parcelamentosAgrupados as $paiId => &$grupo) {
            $grupo['parcelas_restantes'] = count($grupo['lancamentos']);
            $grupo['valor_total_restante'] = array_sum(array_column($grupo['lancamentos'], 'valor'));

            // Data final é a data do último lançamento
            $ultimaData = max(array_column($grupo['lancamentos'], 'data'));
            $grupo['data_final'] = date('d/m/Y', strtotime($ultimaData));

            unset($grupo['lancamentos']); // Remove para não enviar ao frontend
        }

        $parcelamentosAtivos = array_values($parcelamentosAgrupados);
        $totalComprometidoFuturo = array_sum(array_column($parcelamentosAtivos, 'valor_total_restante'));

        // 4. IMPACTO FUTURO (próximos 6 meses)
        $impactoFuturo = [];
        for ($i = 1; $i <= 6; $i++) {
            $dataProjecao = new \DateTime("{$anoInt}-{$mesInt}-01");
            $dataProjecao->modify("+{$i} months");

            $mesProjecao = $dataProjecao->format('m');
            $anoProjecao = $dataProjecao->format('Y');
            $inicioMes = $dataProjecao->format('Y-m-01');
            $fimMes = $dataProjecao->format('Y-m-t');

            $valorMes = \Application\Models\Lancamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cardId)
                ->where('tipo', 'despesa')
                ->whereBetween('data', [$inicioMes, $fimMes])
                ->sum('valor');

            $impactoFuturo[] = [
                'mes' => $dataProjecao->format('M/y'),
                'mes_completo' => ucfirst(\IntlDateFormatter::formatObject(
                    $dataProjecao,
                    "MMMM 'de' yyyy",
                    'pt_BR'
                )),
                'valor' => (float) $valorMes
            ];
        }

        // Insight automático
        $insight = $this->gerarInsightCartao(
            $totalComprometidoFuturo,
            $impactoFuturo,
            $tendencia,
            $percentualUtilizacao,
            $totalFatura,
            $media,
            $diferencaAbsoluta,
            count($parcelamentosAtivos)
        );

        // Gerar insights estruturados
        $cardData = [
            'limite' => $limite,
            'fatura_atual' => $totalFatura,
            'percentual' => $percentualUtilizacao,
            'parcelamentos' => [
                'ativos' => count($parcelamentosAtivos),
                'valor_total' => $totalComprometidoFuturo
            ]
        ];

        $historicoMeses = array_map(function ($item) {
            return ['valor' => $item['valor']];
        }, $evolucao);

        $insightsEstruturados = $this->generateCardInsights($cardData, $historicoMeses);

        return [
            'cartao' => [
                'id' => $cartao->id,
                'nome' => $cartao->nome_cartao ?? $cartao->nome,
                'bandeira' => $cartao->bandeira ?? 'outros',
                'limite' => $limite,
                'dia_vencimento' => $cartao->dia_vencimento,
                'cor' => $cartao->conta ? ($cartao->conta->cor ?? '#E67E22') : '#E67E22',
                'status_saude' => $statusSaude
            ],
            'fatura_mes' => [
                'mes' => $mes,
                'ano' => $ano,
                'total' => $totalFatura,
                'a_vista' => $aVista,
                'parcelado' => $parcelado,
                'fatura_anterior' => $faturaAnterior,
                'diferenca_absoluta' => $diferencaAbsoluta,
                'diferenca_percentual' => $diferencaPercentual,
                'lancamentos' => $lancamentos->map(function ($lanc) {
                    return [
                        'id' => $lanc->id,
                        'descricao' => $lanc->descricao,
                        'valor' => (float) $lanc->valor,
                        'data' => $lanc->data,
                        'categoria' => $lanc->categoria ? $lanc->categoria->nome : 'Sem categoria',
                        'categoria_cor' => $lanc->categoria ? ($lanc->categoria->cor ?? '#E67E22') : '#E67E22',
                        'eh_parcelado' => (bool) $lanc->eh_parcelado,
                        'parcela_info' => $lanc->eh_parcelado ? "{$lanc->parcela_atual}/{$lanc->total_parcelas}" : null
                    ];
                })->values()->toArray(),
                'por_categoria' => $porCategoria,
                'percentual_limite' => round($percentualUtilizacao, 1)
            ],
            'evolucao' => [
                'meses' => $evolucao,
                'tendencia' => $tendencia,
                'media' => round($media, 2)
            ],
            'parcelamentos' => [
                'ativos' => $parcelamentosAtivos,
                'total_comprometido' => $totalComprometidoFuturo,
                'quantidade' => count($parcelamentosAtivos)
            ],
            'impacto_futuro' => [
                'meses' => $impactoFuturo,
                'insight' => $insight
            ],
            'insights' => $insightsEstruturados
        ];
    }

    /**
     * Gera insight automático sobre o cartão
     */
    private function gerarInsightCartao(
        float $comprometido,
        array $impacto,
        string $tendencia,
        float $percentualUtilizacao,
        float $totalFatura = 0,
        float $media = 0,
        float $diferencaAbsoluta = 0,
        int $qtdParcelamentos = 0
    ): string {
        $insights = [];

        // Insight sobre tendência e comparação com média
        if ($tendencia === 'subindo') {
            $insights[] = "🔴 Seus gastos com este cartão <strong>aumentaram</strong> nos últimos meses.";

            if ($totalFatura > $media * 1.2) {
                $insights[] = "Este mês está <strong>acima da média</strong> dos últimos 6 meses.";
            }
        } elseif ($tendencia === 'caindo') {
            $insights[] = "🟢 Seus gastos com este cartão estão <strong>diminuindo</strong>. Continue assim!";
        } else {
            $insights[] = "🟡 Seu uso está <strong>controlado</strong> e dentro da média.";
        }

        // Insight sobre comparativo mensal
        if (abs($diferencaAbsoluta) > 50) {
            $sinal = $diferencaAbsoluta > 0 ? 'aumento' : 'redução';
            $classe = $diferencaAbsoluta > 0 ? 'text-danger' : 'text-success';
            $percentual = abs($diferencaAbsoluta / max($totalFatura - $diferencaAbsoluta, 1) * 100);

            $insights[] = "Comparado ao mês anterior, houve <strong class='$classe'>$sinal de " .
                number_format(abs($diferencaAbsoluta), 2, ',', '.') .
                " (" . number_format($percentual, 1) . "%)</strong>.";
        }

        // Insight sobre utilização
        if ($percentualUtilizacao > 60) {
            $insights[] = "⚠️ <strong>Atenção:</strong> Você está utilizando mais de 60% do limite do cartão.";
        } elseif ($percentualUtilizacao < 30) {
            $insights[] = "✅ Você está utilizando menos de 30% do limite. Excelente controle financeiro!";
        }

        // Insight sobre parcelamentos
        if ($qtdParcelamentos > 0) {
            $insights[] = "Você possui <strong>$qtdParcelamentos parcelamento(s) ativo(s)</strong> neste cartão.";

            if ($comprometido > 0) {
                $insights[] = "Mesmo sem novas compras, haverá <strong>R$ " .
                    number_format($comprometido, 2, ',', '.') .
                    "</strong> comprometidos nos próximos meses.";
            }
        }

        // Dica Lukrato (CTA inteligente)
        $dica = $this->gerarDicaLukrato($percentualUtilizacao, $tendencia, $qtdParcelamentos, $comprometido);
        if ($dica) {
            $insights[] = "<br><div style='margin-top: 1rem; padding: 1rem; background: rgba(52, 152, 219, 0.1); border-left: 3px solid #3498db; border-radius: 4px;'>" .
                "💡 <strong>Dica Lukrato:</strong> $dica</div>";
        }

        return implode(' ', $insights);
    }

    /**
     * Gera dica contextual baseada nos dados do cartão
     */
    private function gerarDicaLukrato(float $percentualUtilizacao, string $tendencia, int $qtdParcelamentos, float $comprometido): ?string
    {
        // Prioridade 1: Limite alto
        if ($percentualUtilizacao > 70) {
            return "Evite novas compras neste cartão até reduzir a utilização abaixo de 50% do limite.";
        }

        // Prioridade 2: Tendência subindo + parcelamentos
        if ($tendencia === 'subindo' && $qtdParcelamentos > 2) {
            return "Evitar novos parcelamentos neste cartão pode ajudar a reduzir sua fatura média mensal.";
        }

        // Prioridade 3: Muitos parcelamentos
        if ($qtdParcelamentos >= 3) {
            return "Considere antecipar alguns parcelamentos para liberar espaço no seu orçamento futuro.";
        }

        // Prioridade 4: Comprometimento alto
        if ($comprometido > 500) {
            return "Seu orçamento futuro está comprometido. Planeje suas próximas compras com atenção.";
        }

        // Prioridade 5: Tudo bem (feedback positivo)
        if ($percentualUtilizacao < 30 && $tendencia !== 'subindo') {
            return "Continue mantendo esse controle! Seu uso do cartão está saudável.";
        }

        return null;
    }
}
