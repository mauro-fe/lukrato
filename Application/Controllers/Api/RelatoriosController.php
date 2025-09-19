<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\Lancamento;
use Carbon\Carbon;

class RelatoriosController extends BaseController
{

    private function resolvePeriod(): array
    {
        $mParam = (string)($this->request->get('month') ?? '');
        $yParam = (string)($this->request->get('year') ?? '');

        if ($mParam !== '' && preg_match('/^\d{4}-\d{2}$/', $mParam)) {
            [$y, $m] = explode('-', $mParam);
            $year = (int)$y;
            $month = (int)$m;
        } else {
            $year  = $yParam !== ''  ? (int)$yParam  : (int)date('Y');
            $month = $mParam !== ''  ? (int)$mParam  : (int)date('n');
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
        return preg_match('/\d+/', $raw, $m) ? (int)$m[0] : null;
    }

    /** Clausula de escopo por usuário (aceita null => pega NULL e do usuário). */
    private function applyUserScope($q, ?int $userId)
    {
        return $q->where(function ($q2) use ($userId) {
            $q2->whereNull('lancamentos.user_id');
            if (!empty($userId)) {
                $q2->orWhere('lancamentos.user_id', $userId);
            }
        });
    }

    private function baseLancamentos(Carbon $start, Carbon $end, ?int $accId, ?int $userId, bool $includeTransfers = false)
    {
        $q = Lancamento::query()
            ->whereBetween('lancamentos.data', [$start, $end])
            ->where('lancamentos.eh_saldo_inicial', 0);

        if (!$includeTransfers) {
            $q->where('lancamentos.eh_transferencia', 0);
        }

        $this->applyUserScope($q, $userId);

        if ($accId) {
            $q->where('lancamentos.conta_id', $accId);
        }
        return $q;
    }


    private function normalizeType(string $t): string
    {
        $t = strtolower(trim($t));
        $map = [
            'rec'       => 'receitas_por_categoria',
            'des'       => 'despesas_por_categoria',
            'saldo'     => 'saldo_mensal',
            'rd'        => 'receitas_despesas_diario',
            'recdes'    => 'receitas_despesas_diario',
            'evo'       => 'evolucao_12m',
            'conta'     => 'receitas_despesas_por_conta',
            'por_conta' => 'receitas_despesas_por_conta',
        ];
        return $map[$t] ?? $t;
    }

    /* ------------ Endpoint único (igual ao que você curtia) ------------ */

    public function index(): void
    {
        $this->requireAuth();
        $includeTransfers = ((string)$this->request->get('include_transfers') === '1');
        try {
            [,, $start, $end] = $this->resolvePeriod();
            $type  = $this->normalizeType((string)($this->request->get('type') ?? 'despesas_por_categoria'));
            $accId = $this->accountId();
            $userId = $this->adminId ?? ($_SESSION['usuario_id'] ?? null);

            switch ($type) {
                /* -------- PIZZA: DESPESAS/RECEITAS POR CATEGORIA -------- */
                case 'despesas_por_categoria':
                case 'receitas_por_categoria': {
                        $alvo = $type === 'despesas_por_categoria' ? 'despesa' : 'receita';
                        $accId = $this->accountId();
                        $userId = $this->adminId ?? ($_SESSION['usuario_id'] ?? null);

                        // Quando NÃO há conta selecionada, ignoramos transferências (evita contagem dupla)
                        if (!$accId) {
                            $data = DB::table('lancamentos as l')
                                ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
                                ->whereBetween('l.data', [$start, $end])
                                ->where('l.eh_transferencia', 0)
                                ->where('l.eh_saldo_inicial', 0)
                                ->where('l.tipo', $alvo)
                                ->when(true, function ($q) use ($userId) {
                                    $q->where(function ($q2) use ($userId) {
                                        $q2->whereNull('l.user_id');
                                        if (!empty($userId)) $q2->orWhere('l.user_id', $userId);
                                    });
                                })
                                ->selectRaw('COALESCE(c.id, 0) as cat_id')
                                ->selectRaw("COALESCE(MAX(c.nome), 'Sem categoria') as label")
                                ->selectRaw('SUM(l.valor) as total')
                                ->groupBy('cat_id')
                                ->orderByDesc('total')
                                ->get();
                        } else {
                            // Com conta selecionada: inclui transferências mapeadas para a conta.
                            // - Despesa: saída de transferência (conta_id = conta)
                            // - Receita: entrada de transferência (conta_id_destino = conta)
                            $data = DB::table('lancamentos as l')
                                ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
                                ->whereBetween('l.data', [$start, $end])
                                ->where('l.eh_saldo_inicial', 0)
                                ->when(true, function ($q) use ($userId) {
                                    $q->where(function ($q2) use ($userId) {
                                        $q2->whereNull('l.user_id');
                                        if (!empty($userId)) $q2->orWhere('l.user_id', $userId);
                                    });
                                })
                                // Filtro da conta, aceitando:
                                //   - lançamentos normais da própria conta
                                //   - E transferências de/para a conta, conforme o alvo
                                ->where(function ($w) use ($accId, $alvo) {
                                    // lançamentos normais na conta
                                    $w->where(function ($q) use ($accId) {
                                        $q->where('l.eh_transferencia', 0)
                                            ->where('l.conta_id', $accId);
                                    });
                                    // + transferências para esta conta (receita) ou desta conta (despesa)
                                    $w->orWhere(function ($q) use ($accId, $alvo) {
                                        $q->where('l.eh_transferencia', 1);
                                        if ($alvo === 'receita') {
                                            $q->where('l.conta_id_destino', $accId);
                                        } else {
                                            $q->where('l.conta_id', $accId);
                                        }
                                    });
                                })
                                // Filtra o tipo de visão:
                                // - lançamentos normais pelo tipo
                                // - transferências entram independente de tipo, mas serão rotuladas como "Transferência"
                                ->where(function ($w) use ($alvo) {
                                    $w->where(function ($q) use ($alvo) {
                                        $q->where('l.eh_transferencia', 0)
                                            ->where('l.tipo', $alvo);
                                    })
                                        ->orWhere('l.eh_transferencia', 1);
                                })
                                // Rótulo: quando transferência, usar "Transferência"; senão, nome da categoria
                                ->selectRaw('
                CASE
                    WHEN l.eh_transferencia = 1 THEN "Transferência"
                    ELSE COALESCE(MAX(c.nome), "Sem categoria")
                END as label
            ')
                                ->selectRaw('SUM(l.valor) as total')
                                // Group by “é transferência?” + categoria-id para agrupar nomes corretamente
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


                    /* -------- LINHA: SALDO DIÁRIO -------- */
                case 'saldo_mensal': {
                        $base = $this->baseLancamentos($start, $end, $accId, $userId, $includeTransfers)
                            ->select(DB::raw('DATE(lancamentos.data) as dia'))
                            ->selectRaw("SUM(CASE WHEN lancamentos.tipo='receita' THEN lancamentos.valor ELSE -lancamentos.valor END) as total")
                            ->groupBy(DB::raw('DATE(lancamentos.data)'))
                            ->orderBy('dia')
                            ->get();

                        $labels = [];
                        $values = [];
                        $cursor = clone $start;
                        while ($cursor <= $end) {
                            $d = $cursor->toDateString();
                            $labels[] = $cursor->format('d/m');
                            $values[] = (float) ($base->firstWhere('dia', $d)->total ?? 0);
                            $cursor->addDay();
                        }

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

                    /* -------- BARRAS: RECEITAS x DESPESAS DIÁRIO -------- */
                case 'receitas_despesas_diario': {
                        $rows = $this->baseLancamentos($start, $end, $accId, $userId, $includeTransfers)
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

                    /* -------- LINHA: EVOLUÇÃO 12 MESES -------- */
                case 'evolucao_12m': {
                        $ini = (clone $start)->subMonthsNoOverflow(11)->startOfMonth();
                        $fim = (clone $end);

                        $rows = $this->baseLancamentos($ini, $fim, $accId, $userId, $includeTransfers)
                            ->selectRaw("DATE_FORMAT(lancamentos.data, '%Y-%m-01') as mes")
                            ->selectRaw("SUM(CASE WHEN lancamentos.tipo='receita' THEN lancamentos.valor ELSE -lancamentos.valor END) as saldo")
                            ->groupBy('mes')
                            ->orderBy('mes')
                            ->get();


                        $labels = [];
                        $values = [];
                        $cursor = clone $ini;
                        while ($cursor <= $fim) {
                            $ym = $cursor->format('Y-m-01');
                            $labels[] = $cursor->format('m/Y');
                            $values[] = (float) ($rows->firstWhere('mes', $ym)->saldo ?? 0);
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

                    /* -------- BARRAS: RECEITAS x DESPESAS POR CONTA -------- */
                case 'receitas_despesas_por_conta': {
                        $rows = DB::table('contas')
                            // escopo de usuário
                            ->when($userId, fn($q) => $q->where('contas.user_id', $userId))
                            // filtro de uma conta específica
                            ->when($accId, fn($q) => $q->where('contas.id', $accId))

                            // JOIN em lançamentos cobrindo:
                            // - lançamentos da própria conta (conta_id = contas.id)
                            // - OU transferências cuja conta_destino é esta conta (conta_id_destino = contas.id)
                            ->leftJoin('lancamentos as l', function ($j) use ($start, $end, $userId) {
                                $j->on(DB::raw('1'), '=', DB::raw('1'))  // truque p/ poder usar OR no ON
                                    ->whereBetween('l.data', [$start, $end])
                                    ->where('l.eh_saldo_inicial', 0)
                                    ->where(function ($w) {
                                        $w->whereColumn('l.conta_id', 'contas.id')
                                            ->orWhere(function ($w2) {
                                                $w2->where('l.eh_transferencia', 1)
                                                    ->whereColumn('l.conta_id_destino', 'contas.id');
                                            });
                                    });

                                // escopo de usuário nos lançamentos
                                $j->where(function ($q2) use ($userId) {
                                    $q2->whereNull('l.user_id')
                                        ->orWhere('l.user_id', $userId);
                                });
                            })

                            ->selectRaw("COALESCE(contas.nome, contas.instituicao, 'Sem conta') as conta")

                            // RECEITAS = receitas normais da própria conta  + entradas de transferência
                            ->selectRaw("
            SUM(
                CASE
                    WHEN l.eh_transferencia = 0 AND l.tipo = 'receita' AND l.conta_id = contas.id
                        THEN l.valor
                    WHEN l.eh_transferencia = 1 AND l.conta_id_destino = contas.id
                        THEN l.valor
                    ELSE 0
                END
            ) as receitas
        ")

                            // DESPESAS = despesas normais da própria conta + saídas de transferência
                            ->selectRaw("
            SUM(
                CASE
                    WHEN l.eh_transferencia = 0 AND l.tipo = 'despesa' AND l.conta_id = contas.id
                        THEN l.valor
                    WHEN l.eh_transferencia = 1 AND l.conta_id = contas.id
                        THEN l.valor
                    ELSE 0
                END
            ) as despesas
        ")

                            ->groupBy('conta')
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


                default:
                    Response::validationError(['type' => 'Tipo de relatório inválido']);
                    return;
            }
        } catch (\InvalidArgumentException $e) {
            Response::validationError(['month' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // Logue $e->getMessage() / $e->getTraceAsString() no seu logger se tiver
            Response::error('Erro ao gerar relatório.', 500, ['exception' => $e->getMessage()]);
        }
    }
}
