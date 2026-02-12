<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Models\Agendamento;
use Application\Models\FaturaCartaoItem;
use Application\Models\CartaoCredito;
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
        // Buscar saldo inicial da conta
        $conta = Conta::find($contaId);
        $saldoInicial = (float) ($conta->saldo_inicial ?? 0);

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

        return $saldoInicial + $movReceitas - $movDespesas + $transfIn - $transfOut;
    }

    private function calcularSaldoGlobal(int $userId, string $ate): float
    {
        // Soma dos saldos iniciais de todas as contas ativas
        $saldosIniciais = (float) Conta::where('user_id', $userId)
            ->where('ativo', true)
            ->sum('saldo_inicial');

        $movGlobal = Lancamento::where('user_id', $userId)
            ->where('data', '<=', $ate)
            ->where('eh_transferencia', 0);

        $r = (float)(clone $movGlobal)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->sum('valor');

        $d = (float)(clone $movGlobal)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->sum('valor');

        return $saldosIniciais + $r - $d;
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

        $monthInput = trim($_GET['month'] ?? date('Y-m'));
        $normalized = $this->normalizeMonth($monthInput);
        $month = $normalized['month'];
        $start = "{$month}-01";
        $end = date('Y-m-t', strtotime($start));
        $now = date('Y-m-d H:i:s');

        // Agendamentos pendentes/notificados do mês (não concluídos ou recorrentes)
        $agendamentosMes = Agendamento::where('user_id', $userId)
            ->whereIn('status', ['pendente', 'notificado'])
            ->where(function ($q) {
                $q->whereNull('concluido_em')
                  ->orWhere('recorrente', true);
            })
            ->whereBetween('data_pagamento', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->get();

        $totalPagar = 0;
        $totalReceber = 0;
        $countPagar = 0;
        $countReceber = 0;

        foreach ($agendamentosMes as $ag) {
            $valor = ($ag->valor_centavos ?? 0) / 100;
            if (strtolower($ag->tipo ?? '') === 'receita') {
                $totalReceber += $valor;
                $countReceber++;
            } else {
                $totalPagar += $valor;
                $countPagar++;
            }
        }

        // Saldo atual (real das contas)
        $contas = Conta::where('user_id', $userId)
            ->where('ativo', true)
            ->get();

        $saldoAtual = 0;
        foreach ($contas as $conta) {
            $saldoAtual += $this->calcularSaldoConta($userId, $conta->id, date('Y-m-d'));
        }

        $saldoProjetado = $saldoAtual + $totalReceber - $totalPagar;

        // Próximos 5 vencimentos (a partir de agora, qualquer mês, não concluídos ou recorrentes)
        $proximos = Agendamento::with(['categoria:id,nome'])
            ->where('user_id', $userId)
            ->whereIn('status', ['pendente', 'notificado'])
            ->where(function ($q) {
                $q->whereNull('concluido_em')
                  ->orWhere('recorrente', true);
            })
            ->where('data_pagamento', '>=', $now)
            ->orderBy('data_pagamento', 'asc')
            ->limit(5)
            ->get()
            ->map(fn($ag) => [
                'id' => $ag->id,
                'titulo' => $ag->titulo,
                'tipo' => $ag->tipo,
                'valor' => ($ag->valor_centavos ?? 0) / 100,
                'data_pagamento' => $ag->data_pagamento instanceof \DateTimeInterface
                    ? $ag->data_pagamento->format('Y-m-d H:i:s')
                    : (string) $ag->data_pagamento,
                'categoria' => $ag->categoria?->nome ?? null,
                'eh_parcelado' => (bool) $ag->eh_parcelado,
                'parcela_atual' => $ag->parcela_atual,
                'numero_parcelas' => $ag->numero_parcelas,
                'recorrente' => (bool) $ag->recorrente,
            ]);

        // Agendamentos vencidos (data_pagamento já passou e não concluídos, ou recorrentes)
        $vencidosQuery = Agendamento::where('user_id', $userId)
            ->whereIn('status', ['pendente', 'notificado'])
            ->where(function ($q) {
                $q->whereNull('concluido_em')
                  ->orWhere('recorrente', true);
            })
            ->where('data_pagamento', '<', $now)
            ->orderBy('data_pagamento', 'asc')
            ->get();

        // Separar vencidos entre despesas e receitas
        $vencidosDespesas = $vencidosQuery->filter(fn($ag) => strtolower($ag->tipo ?? '') !== 'receita');
        $vencidosReceitas = $vencidosQuery->filter(fn($ag) => strtolower($ag->tipo ?? '') === 'receita');

        $vencidos = $vencidosQuery->map(fn($ag) => [
                'id' => $ag->id,
                'titulo' => $ag->titulo,
                'tipo' => $ag->tipo,
                'valor' => ($ag->valor_centavos ?? 0) / 100,
                'data_pagamento' => $ag->data_pagamento instanceof \DateTimeInterface
                    ? $ag->data_pagamento->format('Y-m-d H:i:s')
                    : (string) $ag->data_pagamento,
            ]);

        // Parcelas ativas (não concluídas)
        $parcelasAtivas = Agendamento::where('user_id', $userId)
            ->where('eh_parcelado', true)
            ->whereIn('status', ['pendente', 'notificado'])
            ->whereNull('concluido_em')
            ->where('numero_parcelas', '>', 1)
            ->get();

        $totalMensalParcelas = 0;
        foreach ($parcelasAtivas as $p) {
            $totalMensalParcelas += ($p->valor_centavos ?? 0) / 100;
        }

        // ==================== FATURAS DE CARTÃO ====================
        // Buscar faturas de cartão pendentes do mês
        $mesNum = (int) date('m', strtotime($start));
        $anoNum = (int) date('Y', strtotime($start));
        
        $faturasPendentes = FaturaCartaoItem::where('user_id', $userId)
            ->where('pago', false)
            ->whereYear('data_vencimento', $anoNum)
            ->whereMonth('data_vencimento', $mesNum)
            ->get();

        // Agrupar por cartão para mostrar como "Fatura Cartao X"
        $faturasPorCartao = [];
        foreach ($faturasPendentes as $item) {
            $cartaoId = $item->cartao_credito_id;
            if (!isset($faturasPorCartao[$cartaoId])) {
                $faturasPorCartao[$cartaoId] = [
                    'total' => 0,
                    'itens' => 0,
                    'data_vencimento' => $item->data_vencimento,
                ];
            }
            $faturasPorCartao[$cartaoId]['total'] += (float) $item->valor;
            $faturasPorCartao[$cartaoId]['itens']++;
        }

        $totalFaturas = 0;
        $countFaturas = count($faturasPorCartao);
        $proximosFaturas = [];

        // Buscar nomes dos cartões e montar lista de próximas faturas
        $cartoes = CartaoCredito::where('user_id', $userId)->get()->keyBy('id');
        
        foreach ($faturasPorCartao as $cartaoId => $dados) {
            $totalFaturas += $dados['total'];
            $cartao = $cartoes->get($cartaoId);
            
            if ($cartao) {
                $dataVenc = $dados['data_vencimento'];
                if ($dataVenc instanceof \DateTimeInterface) {
                    $dataVencStr = $dataVenc->format('Y-m-d H:i:s');
                } else {
                    $dataVencStr = (string) $dataVenc;
                }
                
                $proximosFaturas[] = [
                    'id' => 'fatura_' . $cartaoId . '_' . $mesNum . '_' . $anoNum,
                    'titulo' => 'Fatura ' . $cartao->nome_cartao,
                    'tipo' => 'fatura',
                    'valor' => round($dados['total'], 2),
                    'data_pagamento' => $dataVencStr,
                    'categoria' => null,
                    'eh_parcelado' => false,
                    'parcela_atual' => null,
                    'numero_parcelas' => null,
                    'recorrente' => false,
                    'is_fatura' => true,
                    'cartao_id' => $cartaoId,
                    'cartao_nome' => $cartao->nome_cartao,
                    'cartao_ultimos_digitos' => $cartao->ultimos_digitos,
                    'itens_count' => $dados['itens'],
                ];
            }
        }

        // Buscar faturas vencidas (de meses anteriores)
        $faturasVencidas = FaturaCartaoItem::where('user_id', $userId)
            ->where('pago', false)
            ->where('data_vencimento', '<', $now)
            ->get();

        $faturasVencidasPorCartao = [];
        foreach ($faturasVencidas as $item) {
            $cartaoId = $item->cartao_credito_id;
            if (!isset($faturasVencidasPorCartao[$cartaoId])) {
                $faturasVencidasPorCartao[$cartaoId] = [
                    'total' => 0,
                    'data_vencimento' => $item->data_vencimento,
                ];
            }
            $faturasVencidasPorCartao[$cartaoId]['total'] += (float) $item->valor;
        }

        $totalFaturasVencidas = 0;
        $countFaturasVencidas = count($faturasVencidasPorCartao);
        $vencidosFaturas = [];

        foreach ($faturasVencidasPorCartao as $cartaoId => $dados) {
            $totalFaturasVencidas += $dados['total'];
            $cartao = $cartoes->get($cartaoId);
            
            if ($cartao) {
                $dataVenc = $dados['data_vencimento'];
                if ($dataVenc instanceof \DateTimeInterface) {
                    $dataVencStr = $dataVenc->format('Y-m-d H:i:s');
                } else {
                    $dataVencStr = (string) $dataVenc;
                }
                
                $vencidosFaturas[] = [
                    'id' => 'fatura_vencida_' . $cartaoId,
                    'titulo' => 'Fatura ' . $cartao->nome_cartao,
                    'tipo' => 'fatura',
                    'valor' => round($dados['total'], 2),
                    'data_pagamento' => $dataVencStr,
                    'is_fatura' => true,
                    'cartao_nome' => $cartao->nome_cartao,
                ];
            }
        }

        // Mesclar próximos vencimentos com faturas e ordenar por data
        $todosProximos = array_merge($proximos->values()->all(), $proximosFaturas);
        usort($todosProximos, function ($a, $b) {
            $dataA = $a['data_pagamento'] ?? '';
            $dataB = $b['data_pagamento'] ?? '';
            return strcmp($dataA, $dataB);
        });
        $todosProximos = array_slice($todosProximos, 0, 5);

        // Atualizar totais incluindo faturas
        $totalPagarComFaturas = $totalPagar + $totalFaturas;
        $saldoProjetadoComFaturas = $saldoAtual + $totalReceber - $totalPagarComFaturas;

        // Mesclar vencidos
        $todosVencidosItems = array_merge($vencidos->values()->take(5)->all(), $vencidosFaturas);
        $totalVencidosGeral = round($vencidos->sum('valor'), 2) + $totalFaturasVencidas;
        $countVencidosGeral = $vencidos->count() + $countFaturasVencidas;

        // Calcular totais separados de despesas e receitas vencidas
        $totalDespesasVencidas = $vencidosDespesas->sum(fn($ag) => ($ag->valor_centavos ?? 0) / 100);
        $totalReceitasVencidas = $vencidosReceitas->sum(fn($ag) => ($ag->valor_centavos ?? 0) / 100);

        Response::json([
            'month' => $month,
            'provisao' => [
                'a_pagar' => round($totalPagarComFaturas, 2),
                'a_receber' => round($totalReceber, 2),
                'saldo_projetado' => round($saldoProjetadoComFaturas, 2),
                'saldo_atual' => round($saldoAtual, 2),
                'count_pagar' => $countPagar,
                'count_receber' => $countReceber,
                'count_faturas' => $countFaturas,
                'total_faturas' => round($totalFaturas, 2),
            ],
            'proximos' => $todosProximos,
            'vencidos' => [
                'count' => $countVencidosGeral,
                'total' => round($totalVencidosGeral, 2),
                'items' => array_slice($todosVencidosItems, 0, 5),
                'count_faturas' => $countFaturasVencidas,
                'total_faturas' => round($totalFaturasVencidas, 2),
                // Separação por tipo
                'despesas' => [
                    'count' => $vencidosDespesas->count(),
                    'total' => round($totalDespesasVencidas, 2),
                ],
                'receitas' => [
                    'count' => $vencidosReceitas->count(),
                    'total' => round($totalReceitasVencidas, 2),
                ],
            ],
            'parcelas' => [
                'ativas' => $parcelasAtivas->count(),
                'total_mensal' => round($totalMensalParcelas, 2),
            ],
        ]);
    }
}
