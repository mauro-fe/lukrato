<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Carbon\Carbon;
use Application\Lib\Auth;
use Application\Services\LogService; // <-- ADICIONADO AQUI
use ValueError;

// --- Enums para Constantes (PHP 8.1+) ---

enum ReportType: string
{
    case DESPESAS_POR_CATEGORIA = 'despesas_por_categoria';
    case RECEITAS_POR_CATEGORIA = 'receitas_por_categoria';
    case SALDO_MENSAL = 'saldo_mensal';
    case RECEITAS_DESPESAS_DIARIO = 'receitas_despesas_diario';
    case EVOLUCAO_12M = 'evolucao_12m';
    case RECEITAS_DESPESAS_POR_CONTA = 'receitas_despesas_por_conta';
    case RESUMO_ANUAL = 'resumo_anual';
}

enum LancamentoTipo: string
{
    case DESPESA = 'despesa';
    case RECEITA = 'receita';
}

class RelatoriosController extends BaseController
{
    // --- Métodos Auxiliares de Período e Filtro ---

    /**
     * Resolve e valida o período de tempo (mês/ano) a partir dos parâmetros da requisição.
     * @return array{0: int, 1: int, 2: Carbon, 3: Carbon} [year, month, start, end]
     * @throws \InvalidArgumentException
     */
    private function resolvePeriod(): array
    {
        $mParam = (string)($this->request->get('month') ?? '');
        $yParam = (string)($this->request->get('year') ?? '');

        if ($mParam !== '' && preg_match('/^(\d{4})-(\d{2})$/', $mParam, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
        } else {
            $year = $yParam !== '' ? (int)$yParam : (int)date('Y');
            $month = $mParam !== '' ? (int)$mParam : (int)date('n');
        }

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Parâmetros de data inválidos.');
        }

        // Uso de Carbon para garantir start/end do dia/mês
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();

        return [$year, $month, $start, $end];
    }

    /**
     * Extrai e valida o ID da conta.
     */
    private function accountId(): ?int
    {
        $raw = (string)($this->request->get('account_id') ?? '');
        if ($raw === '') return null;
        return preg_match('/^\d+$/', $raw) ? (int)$raw : null;
    }

    /**
     * Aplica o filtro de usuário. Permite NULL user_id para lançamentos globais/padrão.
     * @param QueryBuilder $q
     */
    private function applyUserScope(QueryBuilder $q, ?int $userId, string $tableAlias = 'lancamentos'): QueryBuilder
    {
        $alias = preg_match('/^[a-zA-Z0-9_]+$/', $tableAlias) ? $tableAlias : 'lancamentos';
        $column = "{$alias}.user_id";

        return $q->where(function (QueryBuilder $q2) use ($userId, $column) {
            $q2->whereNull($column);
            if ($userId !== null) {
                $q2->orWhere($column, $userId);
            }
        });
    }

    /**
     * Aplica o filtro de conta.
     * @param QueryBuilder $q
     */
    private function applyAccountFilter(QueryBuilder $q, ?int $accId, bool $includeTransfers): QueryBuilder
    {
        if ($accId === null) {
            return $q;
        }

        if ($includeTransfers) {
            // Conta é a origem (conta_id) OU o destino (conta_id_destino) em transferências
            return $q->where(function (QueryBuilder $w) use ($accId) {
                $w->where('lancamentos.conta_id', $accId)
                    ->orWhere(function (QueryBuilder $w2) use ($accId) {
                        $w2->where('lancamentos.eh_transferencia', 1)
                            ->where('lancamentos.conta_id_destino', $accId);
                    });
            });
        }

        // Apenas considera a conta como origem (ideal para Receitas/Despesas não-transferência)
        return $q->where('lancamentos.conta_id', $accId);
    }

    /**
     * Constrói a expressão SQL para calcular o saldo (delta).
     * @param ?int $accId ID da conta para cálculo específico (incluindo transferências como débito/crédito).
     * @param string $alias Alias para a coluna de saldo.
     * @return array{0: string, 1: array} [SQL Expression, Bindings]
     */
    private function deltaExpression(?int $accId, string $alias = 'delta'): array
    {
        // Garante que o alias é seguro
        $alias = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias) ? $alias : 'delta';

        if ($accId) {
            // Lógica complexa para saldo de UMA conta (Transf. Saída = débito, Transf. Entrada = crédito)
            $expr = "
                SUM(
                    CASE
                        WHEN lancamentos.eh_transferencia = 1 THEN
                            CASE
                                WHEN lancamentos.conta_id = ? THEN -lancamentos.valor /* Saída */
                                WHEN lancamentos.conta_id_destino = ? THEN lancamentos.valor /* Entrada */
                                ELSE 0
                            END
                        ELSE
                            CASE
                                WHEN lancamentos.tipo = 'receita' THEN lancamentos.valor
                                WHEN lancamentos.tipo = 'despesa' THEN -lancamentos.valor
                                ELSE 0
                            END
                    END
                ) as {$alias}
            ";
            return [$expr, [$accId, $accId]];
        }

        // Lógica simples para saldo GLOBAL (Transf. se anulam, Receita - Despesa)
        $expr = "
            SUM(
                CASE
                    WHEN lancamentos.eh_transferencia = 1 THEN 0
                    WHEN lancamentos.tipo = 'receita' THEN lancamentos.valor
                    WHEN lancamentos.tipo = 'despesa' THEN -lancamentos.valor
                    ELSE 0
                END
            ) as {$alias}
        ";
        return [$expr, []];
    }

    /**
     * Retorna a query base para lançamentos em um período.
     */
    private function baseLancamentos(
        Carbon $start,
        Carbon $end,
        ?int $accId,
        ?int $userId,
        bool $includeTransfers = false,
        bool $includeSaldoInicial = false
    ): QueryBuilder {
        $q = DB::table('lancamentos')
            ->whereBetween('lancamentos.data', [$start, $end]);

        if (!$includeSaldoInicial) {
            $q->where('lancamentos.eh_saldo_inicial', 0);
        }

        $useTransfers = $includeTransfers || ($accId !== null);

        if (!$useTransfers) {
            // Se não usar transfers E não tiver conta específica, podemos filtrar transfers = 0
            $q->where('lancamentos.eh_transferencia', 0);
        }

        $this->applyUserScope($q, $userId);
        $this->applyAccountFilter($q, $accId, $useTransfers);

        return $q;
    }

    /**
     * Calcula o saldo acumulado até uma data específica.
     */
    private function saldoAte(Carbon $ate, ?int $accId, ?int $userId, bool $includeTransfers): float
    {
        $useTransfers = $includeTransfers || ($accId !== null);

        // Base query, mas apenas filtra a data (e não o range)
        $q = DB::table('lancamentos')
            ->where('lancamentos.data', '<=', $ate)
            ->selectRaw(...$this->deltaExpression($accId, 'saldo'));

        if (!$useTransfers) {
            $q->where('lancamentos.eh_transferencia', 0);
        }

        $this->applyUserScope($q, $userId);
        $this->applyAccountFilter($q, $accId, $useTransfers);

        return (float) ($q->value('saldo') ?? 0.0);
    }

    /**
     * Normaliza a string de tipo de relatório para o Enum.
     * @param string $t Valor bruto do 'type'.
     */
    private function normalizeType(string $t): ReportType
    {
        $t = strtolower(trim($t));
        $map = [
            'rec'       => ReportType::RECEITAS_POR_CATEGORIA->value,
            'des'       => ReportType::DESPESAS_POR_CATEGORIA->value,
            'saldo'     => ReportType::SALDO_MENSAL->value,
            'rd'        => ReportType::RECEITAS_DESPESAS_DIARIO->value,
            'recdes'    => ReportType::RECEITAS_DESPESAS_DIARIO->value,
            'evo'       => ReportType::EVOLUCAO_12M->value,
            'conta'     => ReportType::RECEITAS_DESPESAS_POR_CONTA->value,
            'por_conta' => ReportType::RECEITAS_DESPESAS_POR_CONTA->value,
            'resumo'    => ReportType::RESUMO_ANUAL->value,
            'anual'     => ReportType::RESUMO_ANUAL->value,
        ];
        $typeString = $map[$t] ?? $t;

        try {
            return ReportType::from($typeString);
        } catch (ValueError) {
            // Retorna um tipo padrão ou lança exceção, dependendo da necessidade de fallback
            throw new \InvalidArgumentException("Tipo de relatório '{$typeString}' inválido.");
        }
    }

    // --- Endpoint Principal ---

    public function index(): void
    {
        $this->requireAuth();

        $user = Auth::user();
        if (!$user || (method_exists($user, 'podeAcessar') && !$user->podeAcessar('reports'))) {
            Response::forbidden('Relatórios são exclusivos do plano Pro.');
            return;
        }

        $includeTransfers = ((string)$this->request->get('include_transfers') === '1');

        try {
            // 1. Resolve Parâmetros
            [,, $start, $end] = $this->resolvePeriod();
            $type   = $this->normalizeType((string)($this->request->get('type') ?? ReportType::DESPESAS_POR_CATEGORIA->value));
            $accId  = $this->accountId();
            $userId = $this->userId;

            // 2. Execução do Relatório (Usando match para PHP 8.0+)
            $result = match ($type) {
                ReportType::DESPESAS_POR_CATEGORIA, ReportType::RECEITAS_POR_CATEGORIA =>
                $this->handleCategoriasReport($type, $start, $end, $accId, $userId),
                ReportType::SALDO_MENSAL =>
                $this->handleSaldoMensalReport($start, $end, $accId, $userId, $includeTransfers),
                ReportType::RECEITAS_DESPESAS_DIARIO =>
                $this->handleReceitasDespesasDiarioReport($start, $end, $accId, $userId, $includeTransfers),
                ReportType::EVOLUCAO_12M =>
                $this->handleEvolucao12MReport($start, $end, $accId, $userId, $includeTransfers),
                ReportType::RECEITAS_DESPESAS_POR_CONTA =>
                $this->handleReceitasDespesasPorContaReport($start, $end, $accId, $userId),
                ReportType::RESUMO_ANUAL =>
                $this->handleResumoAnualReport($start, $end, $accId, $userId, $includeTransfers),
                default =>
                throw new \InvalidArgumentException("Tipo de relatório '{$type->value}' não suportado."),
            };

            Response::success(array_merge($result, [
                'type' => $type->value,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ]));
        } catch (\InvalidArgumentException $e) {
            // --- LOG ADICIONADO (WARNING) ---
            LogService::warning('Falha de validação no relatório.', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId ?? null
            ]);
            Response::validationError(['params' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // --- LOG ADICIONADO (ERROR) ---
            LogService::error('Erro inesperado ao gerar relatório.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Importante para debugar o "B.O"
                'user_id' => $this->userId ?? null
            ]);
            Response::error('Erro ao gerar relatório.', 500, ['exception' => $e->getMessage()]);
        }
    }

    // --- Lógica de Relatórios (Movida para Métodos Privados) ---

    private function handleCategoriasReport(
        ReportType $type,
        Carbon $start,
        Carbon $end,
        ?int $accId,
        ?int $userId
    ): array {
        $alvo = $type === ReportType::DESPESAS_POR_CATEGORIA ? LancamentoTipo::DESPESA->value : LancamentoTipo::RECEITA->value;

        $q = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->whereBetween('l.data', [$start, $end])
            ->where('l.eh_saldo_inicial', 0)
            ->where('l.tipo', $alvo);

        $this->applyUserScope($q, $userId, 'l');

        if ($accId === null) {
            // Global: Ignora transferências (porque se anulam globalmente, mas evita poluição)
            $q->where('l.eh_transferencia', 0);

            $data = $q->selectRaw('COALESCE(c.id, 0) as cat_id')
                ->selectRaw("COALESCE(MAX(c.nome), 'Sem categoria') as label")
                ->selectRaw('SUM(l.valor) as total')
                ->groupBy('cat_id')
                ->orderByDesc('total')
                ->get();
        } else {
            // Específico para Conta: Precisa incluir transferências (como categoria separada)
            $q->where(function ($w) use ($accId, $alvo) {
                // Filtra Lançamentos Receita/Despesa COM a conta
                $w->where(function ($q1) use ($accId) {
                    $q1->where('l.eh_transferencia', 0)
                        ->where('l.conta_id', $accId);
                })
                    // OU filtra Transferências ENTRANDO/SAINDO da conta
                    ->orWhere(function ($q2) use ($accId, $alvo) {
                        $q2->where('l.eh_transferencia', 1);
                        if ($alvo === LancamentoTipo::RECEITA->value) {
                            $q2->where('l.conta_id_destino', $accId); // Transf. recebida é RECEITA
                        } else {
                            $q2->where('l.conta_id', $accId); // Transf. enviada é DESPESA
                        }
                    });
            })
                // A query não pode mais filtrar por tipo = $alvo, mas sim por tipo real OU transferência
                // O filtro de tipo original do query base é removido/refeito aqui:
                ->where(function ($w) use ($alvo) {
                    $w->where(function ($q) use ($alvo) {
                        $q->where('l.eh_transferencia', 0)
                            ->where('l.tipo', $alvo); // Tipo real para receitas/despesas
                    })
                        ->orWhere('l.eh_transferencia', 1); // Inclui transferências
                });

            $data = $q->selectRaw('
                CASE
                    WHEN l.eh_transferencia = 1 THEN "Transferência"
                    ELSE COALESCE(MAX(c.nome), "Sem categoria")
                END as label
            ')
                ->selectRaw('SUM(l.valor) as total')
                ->selectRaw('COALESCE(c.id, 0) as cat_id, l.eh_transferencia as is_transf')
                ->groupBy('is_transf', 'cat_id')
                ->orderByDesc('total')
                ->get();
        }

        $labels = $data->pluck('label')->values()->all();
        $values = $data->pluck('total')->map(fn($v) => (float)$v)->values()->all();

        return [
            'labels' => $labels,
            'values' => $values,
            'total'  => array_sum($values),
        ];
    }

    private function handleSaldoMensalReport(
        Carbon $start,
        Carbon $end,
        ?int $accId,
        ?int $userId,
        bool $includeTransfers
    ): array {
        $useTransfers = $includeTransfers || ($accId !== null);

        $base = $this->baseLancamentos($start, $end, $accId, $userId, $useTransfers, true)
            ->select(DB::raw('DATE(lancamentos.data) as dia'))
            ->selectRaw(...$this->deltaExpression($accId, 'delta'))
            ->groupBy(DB::raw('DATE(lancamentos.data)'))
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        // Saldo Acumulado ANTES do período
        $saldoAnterior = $this->saldoAte(
            (clone $start)->subDay()->endOfDay(),
            $accId,
            $userId,
            $useTransfers
        );

        $labels = [];
        $values = [];
        $running = $saldoAnterior;
        $cursor = clone $start;

        // Iteração diária para calcular o saldo corrente
        while ($cursor <= $end) {
            $d = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $running += (float)($base[$d]->delta ?? 0);
            $values[] = round($running, 2); // Arredonda para evitar problemas de float
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total'  => end($values) ?: 0.0,
        ];
    }

    private function handleReceitasDespesasDiarioReport(
        Carbon $start,
        Carbon $end,
        ?int $accId,
        ?int $userId,
        bool $includeTransfers
    ): array {
        $useTransfers = $includeTransfers || ($accId !== null);

        $rows = $this->baseLancamentos($start, $end, $accId, $userId, $useTransfers, true)
            ->selectRaw('DATE(lancamentos.data) as dia')
            ->selectRaw("SUM(CASE WHEN lancamentos.tipo='receita' THEN lancamentos.valor ELSE 0 END) as receitas")
            ->selectRaw("SUM(CASE WHEN lancamentos.tipo='despesa' THEN lancamentos.valor ELSE 0 END) as despesas")
            ->groupBy(DB::raw('DATE(lancamentos.data)'))
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        $labels = [];
        $receitas = [];
        $despesas = [];
        $cursor = clone $start;

        while ($cursor <= $end) {
            $d = $cursor->toDateString();
            $labels[]   = $cursor->format('d/m');
            // Busca o valor pela chave 'dia'
            $receitas[] = (float) ($rows[$d]->receitas ?? 0.0);
            $despesas[] = (float) ($rows[$d]->despesas ?? 0.0);
            $cursor->addDay();
        }

        return [
            'labels'   => $labels,
            'receitas' => $receitas,
            'despesas' => $despesas,
        ];
    }

    private function handleEvolucao12MReport(
        Carbon $start,
        Carbon $end,
        ?int $accId,
        ?int $userId,
        bool $includeTransfers
    ): array {
        $ini = (clone $start)->subMonthsNoOverflow(11)->startOfMonth();
        $fim = (clone $end); // Fim é o mês atual (End do index)

        $useTransfers = $includeTransfers || ($accId !== null);

        // 1. Agregação Mensal do Delta
        $rows = $this->baseLancamentos($ini, $fim, $accId, $userId, $useTransfers, true)
            ->selectRaw("DATE_FORMAT(lancamentos.data, '%Y-%m-01') as mes")
            ->selectRaw(...$this->deltaExpression($accId, 'saldo'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        // 2. Saldo Acumulado Antes do Período
        $saldoAnterior = $this->saldoAte(
            (clone $ini)->subDay()->endOfDay(),
            $accId,
            $userId,
            $useTransfers
        );

        // 3. Iteração e Acumulação
        $labels = [];
        $values = [];
        $running = $saldoAnterior;
        $cursor = clone $ini;

        while ($cursor <= $fim) {
            $ym = $cursor->format('Y-m-01');
            $labels[] = $cursor->format('m/Y');

            // Adiciona o delta do mês se existir, caso contrário 0
            $running += (float) ($rows[$ym]->saldo ?? 0.0);
            $values[] = round($running, 2);
            $cursor->addMonth();
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'start'  => $ini->toDateString(),
            'end'    => $fim->toDateString(),
        ];
    }

    private function handleReceitasDespesasPorContaReport(
        Carbon $start,
        Carbon $end,
        ?int $accId,
        ?int $userId
    ): array {
        $q = DB::table('contas')
            ->when($userId, fn($q) => $q->where('contas.user_id', $userId))
            ->when($accId, fn($q) => $q->where('contas.id', $accId))
            ->leftJoin('lancamentos as l', function ($j) use ($start, $end, $userId) {
                // Filtra Lançamentos no período e que pertencem à conta (origem OU destino)
                $j->on(DB::raw('1'), '=', DB::raw('1')) // Necessário para LEFT JOIN customizado
                    ->whereBetween('l.data', [$start, $end])
                    ->where(function ($w) {
                        $w->whereColumn('l.conta_id', 'contas.id')
                            ->orWhere(function ($w2) {
                                $w2->where('l.eh_transferencia', 1)
                                    ->whereColumn('l.conta_id_destino', 'contas.id');
                            });
                    })
                    ->where(function ($q2) use ($userId) {
                        // Scope de usuário no JOIN
                        $q2->whereNull('l.user_id')->orWhere('l.user_id', $userId);
                    });
            })
            ->selectRaw("COALESCE(contas.nome, contas.instituicao, 'Sem conta') as conta")
            // Receitas: Receita Normal OU Transf. Recebida
            ->selectRaw("
                SUM(
                    CASE
                        WHEN l.eh_transferencia = 0 AND l.tipo = 'receita' AND l.conta_id = contas.id THEN l.valor
                        WHEN l.eh_transferencia = 1 AND l.conta_id_destino = contas.id THEN l.valor
                        ELSE 0
                    END
                ) as receitas
            ")
            // Despesas: Despesa Normal OU Transf. Enviada
            ->selectRaw("
                SUM(
                    CASE
                        WHEN l.eh_transferencia = 0 AND l.tipo = 'despesa' AND l.conta_id = contas.id THEN l.valor
                        WHEN l.eh_transferencia = 1 AND l.conta_id = contas.id THEN l.valor
                        ELSE 0
                    END
                ) as despesas
            ")
            ->groupBy('contas.id', 'conta') // Agrupa pelo ID e nome da conta
            ->orderBy('conta')
            ->get();

        return [
            'labels'   => $q->pluck('conta')->values()->all(),
            'receitas' => $q->pluck('receitas')->map(fn($v) => (float)$v)->values()->all(),
            'despesas' => $q->pluck('despesas')->map(fn($v) => (float)$v)->values()->all(),
        ];
    }

    private function handleResumoAnualReport(
        Carbon $start,
        Carbon $end,
        ?int $accId,
        ?int $userId,
        bool $includeTransfers
    ): array {
        // Redefine o período para o ano completo (assumindo que $start é no ano alvo)
        $year = $start->year;
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd   = (clone $yearStart)->endOfYear()->endOfDay();

        $useTransfers = $includeTransfers || ($accId !== null);

        $rows = $this->baseLancamentos($yearStart, $yearEnd, $accId, $userId, $useTransfers, true)
            ->selectRaw('MONTH(lancamentos.data) as mes')
            ->selectRaw("SUM(CASE WHEN lancamentos.tipo = 'receita' THEN lancamentos.valor ELSE 0 END) as receitas")
            ->selectRaw("SUM(CASE WHEN lancamentos.tipo = 'despesa' THEN lancamentos.valor ELSE 0 END) as despesas")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $byMonth = [];
        foreach ($rows as $row) {
            $idx = (int)$row->mes;
            $byMonth[$idx] = [
                'receitas' => (float)$row->receitas,
                'despesas' => (float)$row->despesas,
            ];
        }

        $labels = [];
        $receitas = [];
        $despesas = [];
        $abbr = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        for ($m = 1; $m <= 12; $m++) {
            $labels[]   = sprintf('%s/%d', $abbr[$m - 1], $year);
            $receitas[] = $byMonth[$m]['receitas'] ?? 0.0;
            $despesas[] = $byMonth[$m]['despesas'] ?? 0.0;
        }

        return [
            'labels'   => $labels,
            'receitas' => $receitas,
            'despesas' => $despesas,
            'start'    => $yearStart->toDateString(),
            'end'      => $yearEnd->toDateString(),
            'year'     => $year,
        ];
    }
}
