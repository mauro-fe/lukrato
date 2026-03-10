<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Models\CartaoCredito;
use Application\Models\Categoria;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\ContextCompressor;
use Application\Services\AI\PromptBuilder;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Handler para consultas rápidas respondíveis com dados pré-computados.
 * Tenta resolver com SQL direto antes de recorrer ao LLM.
 *
 * Exemplos: "Quanto gastei este mês?", "Qual meu saldo?", "Quantos lançamentos tenho?"
 */
class QuickQueryHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;

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
        'quanto\s+(gastei|gasto)|total\s+(de\s+)?(gasto|despesa)'   => 'getTotalDespesas',
        'quanto\s+(recebi|ganho)|total\s+(de\s+)?receita'           => 'getTotalReceitas',
        'saldo\s+(atual|total|geral)|quanto\s+(tenho|sobrou|falta)' => 'getSaldo',
        'quantos?\s+lançamento|quantos?\s+transaç|quantos?\s+registro' => 'getCountLancamentos',
        'quantos?\s+conta|quantas?\s+conta'                         => 'getCountContas',
        'quantos?\s+cart[ãa]o|quantos?\s+cartao'                    => 'getCountCartoes',
        'gastos?\s+do\s+m[eê]s|despesas?\s+do\s+m[eê]s'            => 'getTotalDespesas',
        'maior\s+gasto|gasto\s+mais\s+caro|maior\s+despesa'        => 'getMaiorGasto',
        'menor\s+gasto|gasto\s+mais\s+barato|menor\s+despesa'      => 'getMenorGasto',
        'm[eé]dia\s+(de\s+)?(gasto|despesa)|gasto\s+m[eé]dio'      => 'getMediaDespesas',
        'quantas?\s+categori|total.*categori'                       => 'getCountCategorias',
        'limite.*cart[ãa]o|cart[ãa]o.*limite|limite.*cr[eé]dito'    => 'getLimiteCartoes',
        'contas?\s+a\s+pagar|pendente|vencid'                       => 'getContasAPagar',
        'receitas?\s+do\s+m[eê]s|ganhos?\s+do\s+m[eê]s'            => 'getTotalReceitas',
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
                                $result['data'] ?? [],
                                IntentType::QUICK_QUERY,
                            );
                        }
                    } catch (\Throwable) {
                        // Falha na resolução direta, cair para LLM
                    }
                }
            }
        }

        // Fallback: delegar para o ChatHandler com contexto
        $chatHandler = new ChatHandler();
        $chatHandler->setProvider($this->provider);
        return $chatHandler->handle($request);
    }

    // ─── Extração de período ─────────────────────────────────

    /**
     * Extrai mês/ano da mensagem do usuário.
     * Retorna [month, year] ou null para usar o mês atual.
     */
    private function extractPeriod(string $message): ?array
    {
        $months = [
            'janeiro' => 1,
            'fevereiro' => 2,
            'março' => 3,
            'marco' => 3,
            'abril' => 4,
            'maio' => 5,
            'junho' => 6,
            'julho' => 7,
            'agosto' => 8,
            'setembro' => 9,
            'outubro' => 10,
            'novembro' => 11,
            'dezembro' => 12,
        ];

        // "mês passado" / "mes anterior"
        if (preg_match('/m[eê]s\s+(passado|anterior)/iu', $message)) {
            $prev = now()->subMonth();
            return [(int) $prev->month, (int) $prev->year];
        }

        // "último trimestre" / "trimestre passado"
        if (preg_match('/(último|ultimo)\s+trimestre|trimestre\s+passado/iu', $message)) {
            return null; // Trimestre requer lógica mais complexa; cai para LLM
        }

        // Nome de mês explícito (ex: "janeiro", "em março", "março 2025")
        foreach ($months as $name => $num) {
            if (preg_match('/\b' . $name . '\b(?:\s+(?:de\s+)?(\d{4}))?/iu', $message, $m)) {
                $year = !empty($m[1]) ? (int) $m[1] : (int) now()->year;
                return [$num, $year];
            }
        }

        return null; // Mês atual (padrão)
    }

    /**
     * Retorna mês e ano para queries, considerando período extraído.
     */
    private function getPeriodValues(?array $period): array
    {
        if ($period !== null) {
            return [$period[0], $period[1]];
        }
        return [(int) now()->month, (int) now()->year];
    }

    private function getPeriodLabel(?array $period): string
    {
        if ($period !== null) {
            return Carbon::createFromDate($period[1], $period[0], 1)->translatedFormat('F/Y');
        }
        return now()->translatedFormat('F/Y');
    }

    // ─── Resolvedores de consulta direta ─────────────────────

    private function getTotalDespesas(?int $userId, ?array $period = null): ?array
    {
        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('tipo', 'despesa')
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

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

    private function getTotalReceitas(?int $userId, ?array $period = null): ?array
    {
        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('tipo', 'receita')
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

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

    private function getSaldo(?int $userId, ?array $period = null): ?array
    {
        $query = Conta::query()->where('ativo', true);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $contas = $query->get(['id', 'nome', 'saldo_inicial']);

        $totalSaldo = 0;
        foreach ($contas as $conta) {
            $saldo = (float) $conta->saldo_inicial;

            $receitasConta = Lancamento::query()
                ->where('conta_id', $conta->id)
                ->where('tipo', 'receita')
                ->where('pago', true)
                ->whereNull('cancelado_em')
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->sum('valor');

            $despesasConta = Lancamento::query()
                ->where('conta_id', $conta->id)
                ->where('tipo', 'despesa')
                ->where('pago', true)
                ->whereNull('cancelado_em')
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->sum('valor');

            $saldo += (float) $receitasConta - (float) $despesasConta;
            $totalSaldo += $saldo;
        }

        $formatted = 'R$ ' . number_format($totalSaldo, 2, ',', '.');

        return [
            'message' => "🏦 Seu saldo total em **{$contas->count()} conta(s)** é **{$formatted}**.",
            'data'    => [
                'saldo_total'  => $totalSaldo,
                'total_contas' => $contas->count(),
                'formatted'    => $formatted,
            ],
        ];
    }

    private function getCountLancamentos(?int $userId, ?array $period = null): ?array
    {
        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $count = (int) $query->count();

        return [
            'message' => "📋 Você tem **{$count} lançamentos** registrados em {$label}.",
            'data'    => ['count' => $count, 'period' => $label],
        ];
    }

    private function getCountContas(?int $userId, ?array $period = null): ?array
    {
        $query = Conta::query()->where('ativo', true);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $count = (int) $query->count();

        return [
            'message' => "🏦 Você tem **{$count} conta(s) ativa(s)** cadastradas.",
            'data'    => ['count' => $count],
        ];
    }

    private function getCountCartoes(?int $userId, ?array $period = null): ?array
    {
        $query = CartaoCredito::query()->where('ativo', true);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $count = (int) $query->count();

        return [
            'message' => "💳 Você tem **{$count} cartão(ões) de crédito ativo(s)**.",
            'data'    => ['count' => $count],
        ];
    }

    private function getMaiorGasto(?int $userId, ?array $period = null): ?array
    {
        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('tipo', 'despesa')
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

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

    private function getMenorGasto(?int $userId, ?array $period = null): ?array
    {
        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('tipo', 'despesa')
            ->whereNull('cancelado_em')
            ->where('valor', '>', 0)
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

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

    private function getMediaDespesas(?int $userId, ?array $period = null): ?array
    {
        [$month, $year] = $this->getPeriodValues($period);
        $label = $this->getPeriodLabel($period);

        $query = Lancamento::query()
            ->where('tipo', 'despesa')
            ->whereNull('cancelado_em')
            ->whereMonth('data', $month)
            ->whereYear('data', $year);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

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

    // ─── Admin queries ──────────────────────────────────────

    private function getCountUsuarios(?int $userId, ?array $period = null): ?array
    {
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

    private function getMRR(?int $userId, ?array $period = null): ?array
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

    private function getCriticalErrors(?int $userId, ?array $period = null): ?array
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

    private function getRegistrosSemana(?int $userId, ?array $period = null): ?array
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

    private function getCountCategorias(?int $userId, ?array $period = null): ?array
    {
        $query = Categoria::query();
        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            });
        }

        $total  = (int) $query->count();
        $custom = $userId ? (int) Categoria::where('user_id', $userId)->count() : 0;

        return [
            'message' => "🏷️ Você tem **{$total} categorias** disponíveis" . ($custom > 0 ? " ({$custom} personalizadas)" : "") . ".",
            'data'    => ['total' => $total, 'custom' => $custom],
        ];
    }

    private function getLimiteCartoes(?int $userId, ?array $period = null): ?array
    {
        $query = CartaoCredito::query()->where('ativo', true);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

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

    private function getContasAPagar(?int $userId, ?array $period = null): ?array
    {
        $query = Lancamento::query()
            ->where('tipo', 'despesa')
            ->where('pago', false)
            ->whereNull('cancelado_em')
            ->where('data', '<=', now()->endOfMonth());

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

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
