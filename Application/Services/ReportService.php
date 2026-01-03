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

            $cards[] = [
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
                'proximos_meses' => $proximosMeses
            ];
        }

        return [
            'cards' => $cards,
            'total' => count($cards)
        ];
    }
}
