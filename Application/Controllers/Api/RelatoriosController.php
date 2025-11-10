<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\Lancamento;
use Carbon\Carbon;
use Application\Services\FeatureGate;
use Application\Lib\Auth;

class RelatoriosController extends BaseController
{
    /**
     * month: aceita "YYYY-MM" ou números (1-12).
     * year: sobrescreve o ano se fornecido (quando month não vier no formato YYYY-MM).
     */
    private function resolvePeriod(): array
    {
        $mParam = (string)($this->request->get('month') ?? '');
        $yParam = (string)($this->request->get('year') ?? '');

        if ($mParam !== '' && preg_match('/^\d{4}-\d{2}$/', $mParam)) {
            [$y, $m] = explode('-', $mParam);
            $year  = (int)$y;
            $month = (int)$m;
        } else {
            $year  = $yParam !== '' ? (int)$yParam : (int)date('Y');
            $month = $mParam !== '' ? (int)$mParam : (int)date('n');
        }

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Parâmetros de data inválidos.');
        }

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();
        return [$year, $month, $start, $end];
    }

    private function accountId(): ?int
    {
        $raw = (string)($this->request->get('account_id') ?? '');
        if ($raw === '') return null;
        return preg_match('/^\d+$/', $raw) ? (int)$raw : null;
    }

    private function applyUserScope($q, ?int $userId)
    {
        return $q->where(function ($q2) use ($userId) {
            $q2->whereNull('lancamentos.user_id');
            if (!empty($userId)) {
                $q2->orWhere('lancamentos.user_id', $userId);
            }
        });
    }

    private function applyAccountFilter($q, ?int $accId, bool $includeTransfers)
    {
        if (empty($accId)) {
            return $q;
        }

        if ($includeTransfers) {
            return $q->where(function ($w) use ($accId) {
                $w->where('lancamentos.conta_id', $accId)
                    ->orWhere(function ($w2) use ($accId) {
                        $w2->where('lancamentos.eh_transferencia', 1)
                           ->where('lancamentos.conta_id_destino', $accId);
                    });
            });
        }

        return $q->where('lancamentos.conta_id', $accId);
    }

    private function deltaExpression(?int $accId, string $alias = 'delta'): array
    {
        $alias = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias) ? $alias : 'delta';

        if ($accId) {
            $expr = "
                SUM(
                    CASE
                        WHEN lancamentos.eh_transferencia = 1 THEN
                            CASE
                                WHEN lancamentos.conta_id = ? THEN -lancamentos.valor
                                WHEN lancamentos.conta_id_destino = ? THEN lancamentos.valor
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

    private function baseLancamentos(
        Carbon $start,
        Carbon $end,
        ?int $accId,
        ?int $userId,
        bool $includeTransfers = false,
        bool $includeSaldoInicial = false
    ) {
        $useTransfers = $includeTransfers || !empty($accId);

        $q = Lancamento::query()
            ->whereBetween('lancamentos.data', [$start, $end]);

        if (!$includeSaldoInicial) {
            $q->where('lancamentos.eh_saldo_inicial', 0);
        }

        if (!$useTransfers) {
            $q->where('lancamentos.eh_transferencia', 0);
        }

        $this->applyUserScope($q, $userId);
        $this->applyAccountFilter($q, $accId, $useTransfers);

        return $q;
    }

    private function saldoAte(Carbon $ate, ?int $accId, ?int $userId, bool $includeTransfers): float
    {
        $useTransfers = $includeTransfers || !empty($accId);

        $q = Lancamento::query()
            ->where('lancamentos.data', '<=', $ate)
            ->selectRaw(...$this->deltaExpression($accId, 'saldo'));

        if (!$useTransfers) {
            $q->where('lancamentos.eh_transferencia', 0);
        }

        $this->applyUserScope($q, $userId);
        $this->applyAccountFilter($q, $accId, $useTransfers);

        return (float) ($q->value('saldo') ?? 0.0);
    }

    private function normalizeType(string $t): string
    {
        $t = strtolower(trim($t));
        $map = [
            'rec'        => 'receitas_por_categoria',
            'des'        => 'despesas_por_categoria',
            'saldo'      => 'saldo_mensal',
            'rd'         => 'receitas_despesas_diario',
            'recdes'     => 'receitas_despesas_diario',
            'evo'        => 'evolucao_12m',
            'conta'      => 'receitas_despesas_por_conta',
            'por_conta'  => 'receitas_despesas_por_conta',
            'resumo'     => 'resumo_anual',
            'anual'      => 'resumo_anual',
        ];
        return $map[$t] ?? $t;
    }

    public function index(): void
    {
        $this->requireAuth();

        // Checagem de feature/plano dentro do método (nada fora da classe)
        $user = Auth::user();
        if (!$user || (method_exists($user, 'podeAcessar') && !$user->podeAcessar('reports'))) {
            Response::forbidden('Relatórios são exclusivos do plano Pro.');
            return;
        }
        // Caso utilize um feature gate central: FeatureGate::assert('reports'); (opcional)

        $includeTransfers = ((string)$this->request->get('include_transfers') === '1');

        try {
            [,, $start, $end] = $this->resolvePeriod();
            $type  = $this->normalizeType((string)($this->request->get('type') ?? 'despesas_por_categoria'));
            $accId = $this->accountId();
            $userId = $this->userId;

            switch ($type) {
                case 'despesas_por_categoria':
                case 'receitas_por_categoria': {
                    $alvo  = $type === 'despesas_por_categoria' ? 'despesa' : 'receita';
                    $accId = $this->accountId();
                    $userId = $this->userId;

                    if (!$accId) {
                        $data = DB::table('lancamentos as l')
                            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
                            ->whereBetween('l.data', [$start, $end])
                            ->where('l.eh_saldo_inicial', 0)
                            ->where('l.eh_transferencia', 0) // ignora transferências no "por categoria"
                            ->where('l.tipo', $alvo)
                            ->where(function ($q2) use ($userId) {
                                $q2->whereNull('l.user_id')->orWhere('l.user_id', $userId);
                            })
                            ->selectRaw('COALESCE(c.id, 0) as cat_id')
                            ->selectRaw("COALESCE(MAX(c.nome), 'Sem categoria') as label")
                            ->selectRaw('SUM(l.valor) as total')
                            ->groupBy('cat_id')
                            ->orderByDesc('total')
                            ->get();
                    } else {
                        $data = DB::table('lancamentos as l')
                            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
                            ->whereBetween('l.data', [$start, $end])
                            ->where('l.eh_saldo_inicial', 0)
                            ->where(function ($q) use ($userId) {
                                $q->where(function ($q2) use ($userId) {
                                    $q2->whereNull('l.user_id');
                                    if (!empty($userId)) $q2->orWhere('l.user_id', $userId);
                                });
                            })
                            ->where(function ($w) use ($accId, $alvo) {
                                $w->where(function ($q) use ($accId) {
                                    $q->where('l.eh_transferencia', 0)
                                      ->where('l.conta_id', $accId);
                                })
                                ->orWhere(function ($q) use ($accId, $alvo) {
                                    $q->where('l.eh_transferencia', 1);
                                    if ($alvo === 'receita') {
                                        $q->where('l.conta_id_destino', $accId);
                                    } else {
                                        $q->where('l.conta_id', $accId);
                                    }
                                });
                            })
                            ->where(function ($w) use ($alvo) {
                                $w->where(function ($q) use ($alvo) {
                                    $q->where('l.eh_transferencia', 0)
                                      ->where('l.tipo', $alvo);
                                })
                                ->orWhere('l.eh_transferencia', 1);
                            })
                            ->selectRaw("
                                CASE
                                    WHEN l.eh_transferencia = 1 THEN 'Transferência'
                                    ELSE COALESCE(MAX(c.nome), 'Sem categoria')
                                END as label
                            ")
                            ->selectRaw('SUM(l.valor) as total')
                            ->selectRaw('COALESCE(c.id, 0) as cat_id, l.eh_transferencia as is_transf')
                            ->groupBy('is_transf', 'cat_id')
                            ->orderByDesc('total')
                            ->get();
                    }

                    $labels = $data->pluck('label')->values()->all();
                    $values = $data->pluck('total')->map(fn($v) => (float)$v)->values()->all();

                    Response::success([
                        'labels' => $labels,
                        'values' => $values,
                        'start'  => $start->toDateString(),
                        'end'    => $end->toDateString(),
                        'type'   => $type,
                        'total'  => array_sum($values),
                    ]);
                    return;
                }

                case 'saldo_mensal': {
                    $useTransfers = $includeTransfers || !empty($accId);

                    $base = $this->baseLancamentos($start, $end, $accId, $userId, $useTransfers, true)
                        ->select(DB::raw('DATE(lancamentos.data) as dia'))
                        ->selectRaw(...$this->deltaExpression($accId, 'delta'))
                        ->groupBy(DB::raw('DATE(lancamentos.data)'))
                        ->orderBy('dia')
                        ->get()
                        ->keyBy('dia');

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
                    while ($cursor <= $end) {
                        $d = $cursor->toDateString();
                        $labels[] = $cursor->format('d/m');
                        $running += (float)($base[$d]->delta ?? 0);
                        $values[] = $running;
                        $cursor->addDay();
                    }

                    Response::success([
                        'labels' => $labels,
                        'values' => $values,
                        'start'  => $start->toDateString(),
                        'end'    => $end->toDateString(),
                        'type'   => $type,
                        'total'  => end($values) ?: 0,
                    ]);
                    return;
                }

                case 'receitas_despesas_diario': {
                    $rows = $this->baseLancamentos($start, $end, $accId, $userId, $includeTransfers, true)
                        ->selectRaw('DATE(lancamentos.data) as dia')
                        ->selectRaw("SUM(CASE WHEN lancamentos.tipo='receita' THEN lancamentos.valor ELSE 0 END) as receitas")
                        ->selectRaw("SUM(CASE WHEN lancamentos.tipo='despesa' THEN lancamentos.valor ELSE 0 END) as despesas")
                        ->groupBy(DB::raw('DATE(lancamentos.data)'))
                        ->orderBy('dia')
                        ->get();

                    $labels = [];
                    $receitas = [];
                    $despesas = [];
                    $cursor = clone $start;
                    while ($cursor <= $end) {
                        $d = $cursor->toDateString();
                        $labels[]   = $cursor->format('d/m');
                        $receitas[] = (float) ($rows->firstWhere('dia', $d)->receitas ?? 0);
                        $despesas[] = (float) ($rows->firstWhere('dia', $d)->despesas ?? 0);
                        $cursor->addDay();
                    }

                    Response::success([
                        'labels'   => $labels,
                        'receitas' => $receitas,
                        'despesas' => $despesas,
                        'type'     => $type,
                        'start'    => $start->toDateString(),
                        'end'      => $end->toDateString(),
                    ]);
                    return;
                }

                case 'evolucao_12m': {
                    $ini = (clone $start)->subMonthsNoOverflow(11)->startOfMonth();
                    $fim = (clone $end);

                    $useTransfers = $includeTransfers || !empty($accId);

                    $rows = $this->baseLancamentos($ini, $fim, $accId, $userId, $useTransfers, true)
                        ->selectRaw("DATE_FORMAT(lancamentos.data, '%Y-%m-01') as mes")
                        ->selectRaw(...$this->deltaExpression($accId, 'saldo'))
                        ->groupBy('mes')
                        ->orderBy('mes')
                        ->get();

                    $saldoAnterior = $this->saldoAte(
                        (clone $ini)->subDay()->endOfDay(),
                        $accId,
                        $userId,
                        $useTransfers
                    );

                    $labels = [];
                    $values = [];
                    $running = $saldoAnterior;
                    $cursor = clone $ini;
                    while ($cursor <= $fim) {
                        $ym = $cursor->format('Y-m-01');
                        $labels[] = $cursor->format('m/Y');
                        $running += (float) ($rows->firstWhere('mes', $ym)->saldo ?? 0);
                        $values[] = $running;
                        $cursor->addMonth();
                    }

                    Response::success([
                        'labels' => $labels,
                        'values' => $values,
                        'type'   => $type,
                        'start'  => $ini->toDateString(),
                        'end'    => $fim->toDateString(),
                    ]);
                    return;
                }

                case 'receitas_despesas_por_conta': {
                    $rows = DB::table('contas')
                        ->when($userId, fn($q) => $q->where('contas.user_id', $userId))
                        ->when($accId, fn($q) => $q->where('contas.id', $accId))
                        ->leftJoin('lancamentos as l', function ($j) use ($start, $end, $userId) {
                            $j->on(DB::raw('1'), '=', DB::raw('1'))
                              ->whereBetween('l.data', [$start, $end])
                              ->where(function ($w) {
                                  $w->whereColumn('l.conta_id', 'contas.id')
                                    ->orWhere(function ($w2) {
                                        $w2->where('l.eh_transferencia', 1)
                                           ->whereColumn('l.conta_id_destino', 'contas.id');
                                    });
                              })
                              ->where(function ($q2) use ($userId) {
                                  $q2->whereNull('l.user_id')->orWhere('l.user_id', $userId);
                              });
                        })
                        ->selectRaw('contas.id as conta_id')
                        ->selectRaw("COALESCE(contas.nome, contas.instituicao, 'Sem conta') as conta")
                        ->selectRaw("
                            SUM(
                                CASE
                                    WHEN l.eh_transferencia = 0 AND l.tipo = 'receita' AND l.conta_id = contas.id THEN l.valor
                                    WHEN l.eh_transferencia = 1 AND l.conta_id_destino = contas.id THEN l.valor
                                    ELSE 0
                                END
                            ) as receitas
                        ")
                        ->selectRaw("
                            SUM(
                                CASE
                                    WHEN l.eh_transferencia = 0 AND l.tipo = 'despesa' AND l.conta_id = contas.id THEN l.valor
                                    WHEN l.eh_transferencia = 1 AND l.conta_id = contas.id THEN l.valor
                                    ELSE 0
                                END
                            ) as despesas
                        ")
                        ->groupBy('conta_id', 'conta')
                        ->orderBy('conta')
                        ->get();

                    Response::success([
                        'labels'   => $rows->pluck('conta')->values()->all(),
                        'receitas' => $rows->pluck('receitas')->map(fn($v) => (float)$v)->values()->all(),
                        'despesas' => $rows->pluck('despesas')->map(fn($v) => (float)$v)->values()->all(),
                        'type'     => $type,
                        'start'    => $start->toDateString(),
                        'end'      => $end->toDateString(),
                    ]);
                    return;
                }

                case 'resumo_anual': {
                    // aceita year=YYYY ou month=YYYY-MM (para extrair o ano)
                    $yearParam = (string)($this->request->get('year') ?? '');
                    if ($yearParam === '' && preg_match('/^(\d{4})/', (string)($this->request->get('month') ?? ''), $m)) {
                        $yearParam = $m[1];
                    }

                    $year = $yearParam !== '' ? (int)$yearParam : (int)date('Y');
                    if ($year < 2000 || $year > 2100) {
                        Response::validationError(['year' => 'Ano inválido.']);
                        return;
                    }

                    $yearStart = Carbon::create($year, 1, 1)->startOfDay();
                    $yearEnd   = (clone $yearStart)->endOfYear()->endOfDay();
                    $useTransfers = $includeTransfers || !empty($accId);

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

                    Response::success([
                        'labels'   => $labels,
                        'receitas' => $receitas,
                        'despesas' => $despesas,
                        'type'     => $type,
                        'start'    => $yearStart->toDateString(),
                        'end'      => $yearEnd->toDateString(),
                        'year'     => $year,
                    ]);
                    return;
                }

                default:
                    Response::validationError(['type' => 'Tipo de relatório inválido']);
                    return;
            }
        } catch (\InvalidArgumentException $e) {
            Response::validationError(['month' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Response::error('Erro ao gerar relatório.', 500, ['exception' => $e->getMessage()]);
        }
    }
}
