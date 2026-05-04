<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\Container\ApplicationContainer;
use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Models\CartaoCredito;
use Application\Models\FaturaCartaoItem;
use Application\Models\Categoria;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\ContextCompressor;
use Application\Services\AI\PromptBuilder;
use Application\Services\Infrastructure\LogService;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Handler para consultas rápidas respondíveis com dados pré-computados.
 * Tenta resolver com SQL direto antes de recorrer ao LLM.
 *
 * Exemplos: "Quanto gastei este mês?", "Qual meu saldo?", "Quantos lançamentos tenho?"
 *
 * @phpstan-type Period array{0: int, 1: int}
 * @phpstan-type QueryData array<string, mixed>
 * @phpstan-type QueryResult array{message: string, data: QueryData}
 */
class QuickQueryHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;
    private ?ChatHandler $chatHandler = null;
    private ?string $lastMessage = null;

    public function __construct(?ChatHandler $chatHandler = null)
    {
        $this->chatHandler = $chatHandler;
    }

    public function setProvider(AIProvider $provider): void
    {
        $this->provider = $provider;
    }
    /**
     * Mapeamento de padrões para métodos de resolução direta.
     *
     * @var array<string, string>
     */
    private const QUERY_PATTERNS = [
        'quanto\s+(gastei|gasto|paguei)|total\s+(de\s+)?(gasto|despesa)'   => 'getTotalDespesas',
        'quanto\s+(recebi|ganho|ganhei)|total\s+(de\s+)?receita'           => 'getTotalReceitas',
        'saldo\s+(atual|total|geral)|quanto\s+(tenho|sobrou|falta|sobra)|to\s+com\s+quanto' => 'getSaldo',
        'me\s+(?:diz|fala|mostra|conta)\s+(?:o\s+)?(?:meu\s+)?saldo'      => 'getSaldo',
        'quantos?\s+lan[çc]amento|quantos?\s+transa[çc]|quantos?\s+registro' => 'getCountLancamentos',
        'quantos?\s+conta|quantas?\s+conta'                                 => 'getCountContas',
        'quantos?\s+cart[ãa]o|quantos?\s+cartao'                            => 'getCountCartoes',
        'gastos?\s+do\s+m[eê]s|despesas?\s+do\s+m[eê]s'                    => 'getTotalDespesas',
        'maior\s+gasto|gasto\s+mais\s+caro|maior\s+despesa'                => 'getMaiorGasto',
        'menor\s+gasto|gasto\s+mais\s+barato|menor\s+despesa'              => 'getMenorGasto',
        'm[eé]dia\s+(de\s+)?(gasto|despesa)|gasto\s+m[eé]dio'              => 'getMediaDespesas',
        'quantas?\s+categori|total.*categori'                               => 'getCountCategorias',
        'limite.*cart[ãa]o|cart[ãa]o.*limite|limite.*cr[eé]dito'            => 'getLimiteCartoes',
        // Faturas de cartão (antes de contas a pagar para prioridade)
        'fatura.*cart[ãa]o|cart[ãa]o.*fatura|valor.*fatura|fatura.*valor|pr[óo]xima\s+fatura' => 'getFaturaAtual',
        'quanto\s+devo\s+(?:no|do)\s+(?:cart[ãa]o|nubank|inter|ita[úu]|bradesco|santander|c6|next|bb)' => 'getFaturaCartaoEspecifico',
        'fatura\s+(?:do|da|de)\s+(?:nubank|inter|ita[úu]|itau|bradesco|santander|c6|next|bb)' => 'getFaturaCartaoEspecifico',
        'itens?\s+(?:da|de)\s+fatura|(?:o\s+que|oq)\s+(?:tem|t[áa])\s+na\s+fatura' => 'getItensFatura',

        'contas?\s+a\s+pagar|pendente|vencid'                               => 'getContasAPagar',
        'receitas?\s+do\s+m[eê]s|ganhos?\s+do\s+m[eê]s'                    => 'getTotalReceitas',
        'quanto\s+(?:eu\s+)?devo|d[ií]vida'                                => 'getContasAPagar',
        'sobr(?:ou|ando|a)\s+quanto|quanto\s+(?:ta\s+)?sobr'               => 'getSaldo',
    ];

    /**
     * Padrões para consultas admin.
     */
    private const ADMIN_PATTERNS = [
        'quantos\s+usu[áa]rio|quantos\s+usuario|total.*usu[áa]rio' => 'getCountUsuarios',
        'mrr|receita\s+recorrente'                                  => 'getMRR',
        'erro.*cr[íi]tico|erro.*critical'                           => 'getCriticalErrors',
        'cadastro.*semana|registr.*semana|usu[áa]rio.*semana'       => 'getRegistrosSemana',
    ];

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::QUICK_QUERY;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        $message = mb_strtolower(trim($request->message));
        $userId  = $request->userId;
        $this->lastMessage = $message;

        if ($userId === null) {
            return AIResponseDTO::fail('Usuário não identificado.', IntentType::QUICK_QUERY);
        }

        // Extrair período da mensagem
        $period = $this->extractPeriod($message);

        // Tentar resolução direta (0 tokens)
        $patterns = $request->isAdmin()
            ? array_merge(self::QUERY_PATTERNS, self::ADMIN_PATTERNS)
            : self::QUERY_PATTERNS;

        foreach ($patterns as $pattern => $method) {
            if (preg_match('/' . $pattern . '/iu', $message)) {
                if (method_exists($this, $method)) {
                    try {
                        $result = $this->$method($request->userId, $period);
                        if ($result !== null) {
                            return AIResponseDTO::fromComputed(
                                $result['message'],
                                $result['data'],
                                IntentType::QUICK_QUERY,
                            );
                        }
                    } catch (\Throwable $e) {
                        LogService::warning('QuickQueryHandler.handle', ['error' => $e->getMessage()]);
                        // Falha na resolução direta, cair para LLM
                    }
                }
            }
        }

        // Fallback: delegar para o ChatHandler com contexto
        $chatHandler = $this->chatHandler();
        if ($this->provider !== null) {
            $chatHandler->setProvider($this->provider);
        }

        return $chatHandler->handle($request);
    }

    private function chatHandler(): ChatHandler
    {
        return $this->chatHandler ??= ApplicationContainer::resolveOrNew($this->chatHandler, ChatHandler::class);
    }

    // ─── Extração de período ─────────────────────────────────

    /**
     * @return Period|null
     */
    private function extractPeriod(string $message): ?array
    {
        return \Application\Services\AI\NLP\PeriodExtractor::extract($message);
    }

    /**
     * Retorna mês e ano para queries, considerando período extraído.
     */
    /**
     * @param Period|null $period
     * @return Period
     */
    private function getPeriodValues(?array $period): array
    {
        if ($period !== null) {
            return [$period[0], $period[1]];
        }
        return [(int) now()->month, (int) now()->year];
    }

    /**
     * @param Period|null $period
     */
    private function getPeriodLabel(?array $period): string
    {
        if ($period !== null) {
            return Carbon::createFromDate($period[1], $period[0], 1)->translatedFormat('F/Y');
        }
        return now()->translatedFormat('F/Y');
    }

    // ─── Resolvedores de consulta direta ─────────────────────

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getTotalDespesas(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->where('pago', true)
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        $total = (float) $query->sum('valor');
        $count = (int) $query->count();

        $formatted = 'R$ ' . number_format($total, 2, ',', '.');

        return [
            'message' => "📊 Em {$label} você tem **{$count} despesas** totalizando **{$formatted}**.",
            'data'    => [
                'total'     => $total,
                'count'     => $count,
                'formatted' => $formatted,
                'period'    => $label,
            ],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getTotalReceitas(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('user_id', $userId)
            ->where('tipo', 'receita')
            ->where('pago', true)
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        $total = (float) $query->sum('valor');
        $count = (int) $query->count();

        $formatted = 'R$ ' . number_format($total, 2, ',', '.');

        return [
            'message' => "💰 Em {$label} você tem **{$count} receitas** totalizando **{$formatted}**.",
            'data'    => [
                'total'     => $total,
                'count'     => $count,
                'formatted' => $formatted,
                'period'    => $label,
            ],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getSaldo(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        $contas = Conta::query()->where('ativo', true)
            ->where('user_id', $userId)
            ->get(['id', 'nome', 'saldo_inicial']);

        $totalSaldo = 0;
        foreach ($contas as $conta) {
            $saldo = (float) $conta->saldo_inicial;

            $receitasQuery = Lancamento::query()
                ->where('conta_id', $conta->id)
                ->where('tipo', 'receita')
                ->where('pago', true)
                ->whereNull('cancelado_em')
                ->when($userId, fn($q) => $q->where('user_id', $userId));

            $despesasQuery = Lancamento::query()
                ->where('conta_id', $conta->id)
                ->where('tipo', 'despesa')
                ->where('pago', true)
                ->whereNull('cancelado_em')
                ->when($userId, fn($q) => $q->where('user_id', $userId));

            if ($period !== null) {
                [$month, $year] = $this->getPeriodValues($period);
                $receitasQuery->whereMonth('data', $month)->whereYear('data', $year);
                $despesasQuery->whereMonth('data', $month)->whereYear('data', $year);
            }

            $saldo += (float) $receitasQuery->sum('valor') - (float) $despesasQuery->sum('valor');
            $totalSaldo += $saldo;
        }

        $formatted = 'R$ ' . number_format($totalSaldo, 2, ',', '.');
        $label = $period !== null ? ' em ' . $this->getPeriodLabel($period) : '';

        return [
            'message' => "🏦 Seu saldo total em **{$contas->count()} conta(s)**{$label} é **{$formatted}**.",
            'data'    => [
                'saldo_total'  => $totalSaldo,
                'total_contas' => $contas->count(),
                'formatted'    => $formatted,
            ],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getCountLancamentos(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('user_id', $userId)
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        $count = (int) $query->count();

        return [
            'message' => "📋 Você tem **{$count} lançamentos** registrados em {$label}.",
            'data'    => ['count' => $count, 'period' => $label],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getCountContas(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        $query = Conta::query()->where('ativo', true)
            ->where('user_id', $userId);

        $count = (int) $query->count();

        return [
            'message' => "🏦 Você tem **{$count} conta(s) ativa(s)** cadastradas.",
            'data'    => ['count' => $count],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getCountCartoes(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        $query = CartaoCredito::query()->where('ativo', true)
            ->where('user_id', $userId);

        $count = (int) $query->count();

        return [
            'message' => "💳 Você tem **{$count} cartão(ões) de crédito ativo(s)**.",
            'data'    => ['count' => $count],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getMaiorGasto(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        $lancamento = $query->orderByDesc('valor')->first(['descricao', 'valor', 'data']);

        if (!$lancamento) {
            return [
                'message' => "📊 Nenhuma despesa registrada em {$label}.",
                'data'    => [],
            ];
        }

        $formatted = 'R$ ' . number_format((float) $lancamento->valor, 2, ',', '.');

        return [
            'message' => "🔝 A maior despesa de {$label} é **{$lancamento->descricao}** — **{$formatted}** em " . Carbon::parse($lancamento->data)->translatedFormat('d/m') . ".",
            'data'    => [
                'descricao' => $lancamento->descricao,
                'valor'     => (float) $lancamento->valor,
                'data'      => $lancamento->data,
                'formatted' => $formatted,
            ],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getMenorGasto(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->whereNull('cancelado_em')
            ->where('valor', '>', 0)
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        $lancamento = $query->orderBy('valor')->first(['descricao', 'valor', 'data']);

        if (!$lancamento) {
            return [
                'message' => "📊 Nenhuma despesa registrada em {$label}.",
                'data'    => [],
            ];
        }

        $formatted = 'R$ ' . number_format((float) $lancamento->valor, 2, ',', '.');

        return [
            'message' => "🔻 A menor despesa de {$label} é **{$lancamento->descricao}** — **{$formatted}** em " . Carbon::parse($lancamento->data)->translatedFormat('d/m') . ".",
            'data'    => [
                'descricao' => $lancamento->descricao,
                'valor'     => (float) $lancamento->valor,
                'data'      => $lancamento->data,
                'formatted' => $formatted,
            ],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getMediaDespesas(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        $avg   = (float) $query->avg('valor');
        $count = (int) $query->count();

        $formatted = 'R$ ' . number_format($avg, 2, ',', '.');

        return [
            'message' => "📐 A média das suas **{$count} despesas** em {$label} é **{$formatted}**.",
            'data'    => [
                'media'     => $avg,
                'count'     => $count,
                'formatted' => $formatted,
                'period'    => $label,
            ],
        ];
    }

    // ─── Resolvedores de fatura de cartão ─────────────────────

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getFaturaAtual(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $itens = FaturaCartaoItem::forUser($userId)
            ->doMesAno($month, $year)
            ->whereNull('cancelado_em')
            ->get(['cartao_credito_id', 'valor', 'pago']);

        if ($itens->isEmpty()) {
            return [
                'message' => "💳 Nenhum item na fatura de {$label}.",
                'data'    => ['total' => 0, 'period' => $label],
            ];
        }

        $total = (float) $itens->sum('valor');
        $pendente = (float) $itens->where('pago', false)->sum('valor');
        $pago = (float) $itens->where('pago', true)->sum('valor');

        $fmtTotal = 'R$ ' . number_format($total, 2, ',', '.');
        $fmtPendente = 'R$ ' . number_format($pendente, 2, ',', '.');
        $fmtPago = 'R$ ' . number_format($pago, 2, ',', '.');

        // Agrupar por cartão
        $cartaoIds = $itens->pluck('cartao_credito_id')->unique()->values()->toArray();
        $cartoes = CartaoCredito::whereIn('id', $cartaoIds)->pluck('nome_cartao', 'id');

        $porCartao = [];
        foreach ($itens->groupBy('cartao_credito_id') as $cartaoId => $group) {
            $nome = $cartoes[$cartaoId] ?? 'Cartão #' . $cartaoId;
            $porCartao[$nome] = 'R$ ' . number_format((float) $group->sum('valor'), 2, ',', '.');
        }

        $detalhes = implode(' | ', array_map(fn($n, $v) => "{$n}: {$v}", array_keys($porCartao), $porCartao));
        $qtdCartoes = count($porCartao);

        $msg = "💳 Suas faturas de {$label}: **{$fmtTotal}** total | **{$fmtPendente}** pendente | **{$fmtPago}** pago em {$qtdCartoes} cartão(ões).";
        if ($qtdCartoes > 1) {
            $msg .= "\nDetalhes: {$detalhes}";
        }

        return [
            'message' => $msg,
            'data'    => [
                'total'     => $total,
                'pendente'  => $pendente,
                'pago'      => $pago,
                'por_cartao' => $porCartao,
                'period'    => $label,
            ],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getFaturaCartaoEspecifico(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        $nomeCartao = $this->extractCartaoName($this->lastMessage ?? '');
        if ($nomeCartao === null) {
            return $this->getFaturaAtual($userId, $period);
        }

        $cartao = CartaoCredito::where('user_id', $userId)
            ->where('ativo', true)
            ->where('nome_cartao', 'LIKE', "%{$nomeCartao}%")
            ->first();

        if (!$cartao) {
            return $this->getFaturaAtual($userId, $period);
        }

        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $itens = FaturaCartaoItem::forUser($userId)
            ->where('cartao_credito_id', $cartao->id)
            ->doMesAno($month, $year)
            ->whereNull('cancelado_em')
            ->get(['valor', 'pago', 'data_vencimento']);

        if ($itens->isEmpty()) {
            return [
                'message' => "💳 Nenhum item na fatura do **{$cartao->nome_cartao}** em {$label}.",
                'data'    => ['cartao' => $cartao->nome_cartao, 'total' => 0, 'period' => $label],
            ];
        }

        $total = (float) $itens->sum('valor');
        $pendente = (float) $itens->where('pago', false)->sum('valor');
        $pago = (float) $itens->where('pago', true)->sum('valor');
        $count = $itens->count();

        $fmtTotal = 'R$ ' . number_format($total, 2, ',', '.');
        $fmtPendente = 'R$ ' . number_format($pendente, 2, ',', '.');
        $fmtPago = 'R$ ' . number_format($pago, 2, ',', '.');

        $vencimento = $cartao->dia_vencimento ? "Vence dia {$cartao->dia_vencimento}" : '';

        $msg = "💳 Fatura do **{$cartao->nome_cartao}** em {$label}: **{$fmtTotal}** | Pendente: **{$fmtPendente}** | Pago: **{$fmtPago}** ({$count} itens)";
        if ($vencimento) {
            $msg .= " — {$vencimento}";
        }

        return [
            'message' => $msg,
            'data'    => [
                'cartao'   => $cartao->nome_cartao,
                'total'    => $total,
                'pendente' => $pendente,
                'pago'     => $pago,
                'count'    => $count,
                'period'   => $label,
            ],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getItensFatura(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $itens = FaturaCartaoItem::forUser($userId)
            ->doMesAno($month, $year)
            ->whereNull('cancelado_em')
            ->orderByDesc('valor')
            ->limit(5)
            ->with('cartaoCredito:id,nome_cartao')
            ->get(['id', 'descricao', 'valor', 'cartao_credito_id', 'pago']);

        if ($itens->isEmpty()) {
            return [
                'message' => "📋 Nenhum item na fatura de {$label}.",
                'data'    => ['itens' => [], 'period' => $label],
            ];
        }

        $lines = [];
        foreach ($itens as $i => $item) {
            $num = $i + 1;
            $fmtValor = 'R$ ' . number_format((float) $item->valor, 2, ',', '.');
            $nomeCartao = $item->cartaoCredito->nome_cartao ?? 'Cartão';
            $status = $item->pago ? '✅' : '⏳';
            $lines[] = "{$num}. {$item->descricao} — {$fmtValor} ({$nomeCartao}) {$status}";
        }

        $msg = "📋 Maiores itens da fatura de {$label}:\n" . implode("\n", $lines);

        return [
            'message' => $msg,
            'data'    => [
                'itens'  => $itens->toArray(),
                'period' => $label,
            ],
        ];
    }

    /**
     * Extrai nome do cartão/banco da mensagem do usuário.
     */
    private function extractCartaoName(string $message): ?string
    {
        $banks = [
            'nubank'          => 'nubank',
            'inter'           => 'inter',
            'ita[úu]'         => 'itaú',
            'itau'            => 'itaú',
            'bradesco'        => 'bradesco',
            'santander'       => 'santander',
            'c6'              => 'c6',
            'next'            => 'next',
            'bb'              => 'banco do brasil',
            'banco\s+do\s+brasil' => 'banco do brasil',
            'caixa'           => 'caixa',
            'original'        => 'original',
            'neon'            => 'neon',
            'picpay'          => 'picpay',
            'mercado\s+pago'  => 'mercado pago',
            'will'            => 'will',
            'xp'              => 'xp',
        ];

        $normalized = mb_strtolower(trim($message));
        foreach ($banks as $pattern => $name) {
            if (preg_match('/\b' . $pattern . '\b/iu', $normalized)) {
                return $name;
            }
        }

        return null;
    }

    // ─── Admin queries ──────────────────────────────────────

    /**
     * @param Period|null $period
     * @return QueryResult
     */
    private function getCountUsuarios(?int $userId, ?array $period = null): array
    {
        // Admin queries requerem isAdmin() no request (verificado em handle())
        $total = (int) DB::table('usuarios')->whereNull('deleted_at')->count();
        $newThisMonth = (int) DB::table('usuarios')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'message' => "👥 Total de **{$total} usuários** cadastrados. **{$newThisMonth} novos** este mês.",
            'data'    => ['total' => $total, 'new_this_month' => $newThisMonth],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult
     */
    private function getMRR(?int $userId, ?array $period = null): array
    {
        $mrr = (float) DB::table('assinaturas_usuarios')
            ->join('planos', 'assinaturas_usuarios.plano_id', '=', 'planos.id')
            ->where('assinaturas_usuarios.status', 'active')
            ->sum('planos.preco_centavos');

        $mrrReais = $mrr / 100;
        $formatted = 'R$ ' . number_format($mrrReais, 2, ',', '.');
        $activeCount = (int) DB::table('assinaturas_usuarios')
            ->where('status', 'active')
            ->count();

        return [
            'message' => "💰 MRR atual: **{$formatted}** com **{$activeCount} assinantes ativos**.",
            'data'    => [
                'mrr_centavos'   => (int) $mrr,
                'mrr_reais'      => $mrrReais,
                'active_count'   => $activeCount,
                'formatted'      => $formatted,
            ],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult
     */
    private function getCriticalErrors(?int $userId, ?array $period = null): array
    {
        $count = (int) DB::table('error_logs')
            ->where('level', 'critical')
            ->whereNull('resolved_at')
            ->count();

        $recent = DB::table('error_logs')
            ->where('level', 'critical')
            ->whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['message', 'category', 'created_at'])
            ->toArray();

        $status = $count === 0
            ? "✅ Nenhum erro crítico não resolvido no momento."
            : "⚠️ Existem **{$count} erros críticos** não resolvidos.";

        return [
            'message' => $status,
            'data'    => ['count' => $count, 'recent' => $recent],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult
     */
    private function getRegistrosSemana(?int $userId, ?array $period = null): array
    {
        $count = (int) DB::table('usuarios')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        return [
            'message' => "📈 **{$count} novos usuários** se cadastraram esta semana.",
            'data'    => ['count' => $count, 'period' => 'semana_atual'],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getCountCategorias(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        $query = Categoria::query()
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            });

        $total  = (int) $query->count();
        $custom = $userId ? (int) Categoria::where('user_id', $userId)->count() : 0;

        return [
            'message' => "🏷️ Você tem **{$total} categorias** disponíveis" . ($custom > 0 ? " ({$custom} personalizadas)" : "") . ".",
            'data'    => ['total' => $total, 'custom' => $custom],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getLimiteCartoes(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        $query = CartaoCredito::query()->where('ativo', true)
            ->where('user_id', $userId);

        $cartoes = $query->get(['nome', 'limite_total', 'limite_disponivel']);

        if ($cartoes->isEmpty()) {
            return [
                'message' => "💳 Nenhum cartão de crédito ativo cadastrado.",
                'data'    => [],
            ];
        }

        $total = $cartoes->sum('limite_total');
        $disponivel = $cartoes->sum('limite_disponivel');
        $usado = $total - $disponivel;
        $pctUsado = $total > 0 ? round(($usado / $total) * 100) : 0;

        $fmtTotal = 'R$ ' . number_format((float) $total, 2, ',', '.');
        $fmtDisp  = 'R$ ' . number_format((float) $disponivel, 2, ',', '.');

        return [
            'message' => "💳 Limite total: **{$fmtTotal}** | Disponível: **{$fmtDisp}** | Uso: **{$pctUsado}%** em {$cartoes->count()} cartão(ões).",
            'data'    => [
                'limite_total'     => (float) $total,
                'limite_disponivel' => (float) $disponivel,
                'pct_usado'        => $pctUsado,
                'count'            => $cartoes->count(),
            ],
        ];
    }

    /**
     * @param Period|null $period
     * @return QueryResult|null
     */
    private function getContasAPagar(?int $userId, ?array $period = null): ?array
    {
        if ($userId === null) return null;

        $query = Lancamento::query()
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->where('pago', false)
            ->whereNull('cancelado_em')
            ->where('data', '<=', now()->endOfMonth());

        $count = (int) $query->count();
        $total = (float) $query->sum('valor');
        $vencidas = (int) (clone $query)->where('data', '<', now()->startOfDay())->count();

        $formatted = 'R$ ' . number_format($total, 2, ',', '.');

        $msg = "📋 Você tem **{$count} contas a pagar** este mês, totalizando **{$formatted}**.";
        if ($vencidas > 0) {
            $msg .= " ⚠️ **{$vencidas} já vencida(s)!**";
        }

        return [
            'message' => $msg,
            'data'    => ['count' => $count, 'total' => $total, 'vencidas' => $vencidas, 'formatted' => $formatted],
        ];
    }
}
