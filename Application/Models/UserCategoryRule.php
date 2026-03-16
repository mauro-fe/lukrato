<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Regra de categorização personalizada do usuário.
 * Armazena padrões aprendidos a partir de correções e confirmações,
 * permitindo categorização adaptativa por usuário.
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $pattern             Palavra-chave ou padrão original
 * @property string      $normalized_pattern   Padrão normalizado (lowercase, sem acentos)
 * @property int         $categoria_id
 * @property int|null    $subcategoria_id
 * @property int         $usage_count          Quantas vezes foi usado/confirmado
 * @property string      $source               'correction' | 'confirmed' | 'manual'
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class UserCategoryRule extends Model
{
    public const MIN_CONFIRMED_USAGE_FOR_MATCH = 2;

    private const GENERIC_PATTERNS = [
        'compra',
        'compras',
        'conta',
        'contas',
        'despesa',
        'despesas',
        'entrada',
        'entradas',
        'gasto',
        'gastos',
        'item',
        'itens',
        'lancamento',
        'lancamentos',
        'pagamento',
        'pagamentos',
        'produto',
        'produtos',
        'receita',
        'receitas',
        'saida',
        'saidas',
        'servico',
        'servicos',
        'site',
        'valor',
        'valores',
    ];

    private const SHORT_PATTERN_WHITELIST = [
        '99',
        'bb',
        'c6',
        'das',
        'fgts',
        'inss',
        'iptu',
        'ipva',
        'ir',
        'mei',
        'oi',
        'pix',
        'tim',
    ];

    protected $table = 'user_category_rules';

    protected $fillable = [
        'user_id',
        'pattern',
        'normalized_pattern',
        'categoria_id',
        'subcategoria_id',
        'usage_count',
        'source',
    ];

    protected $casts = [
        'user_id'          => 'int',
        'categoria_id'     => 'int',
        'subcategoria_id'  => 'int',
        'usage_count'      => 'int',
    ];

    // ─── Relacionamentos ────────────────────────────────

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Categoria::class, 'subcategoria_id');
    }

    // ─── Helpers ────────────────────────────────────────

    /**
     * Normaliza um padrão para busca (lowercase, sem acentos, trim).
     */
    public static function normalize(string $pattern): string
    {
        $normalized = mb_strtolower(trim($pattern));

        // Remove acentos
        $normalized = preg_replace('/[áàâã]/u', 'a', $normalized);
        $normalized = preg_replace('/[éèê]/u', 'e', $normalized);
        $normalized = preg_replace('/[íìî]/u', 'i', $normalized);
        $normalized = preg_replace('/[óòôõ]/u', 'o', $normalized);
        $normalized = preg_replace('/[úùû]/u', 'u', $normalized);
        $normalized = preg_replace('/[ç]/u', 'c', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return $normalized;
    }

    public static function isWeakPattern(string $pattern): bool
    {
        $normalized = self::normalize($pattern);
        if ($normalized === '') {
            return true;
        }

        if (in_array($normalized, self::GENERIC_PATTERNS, true)) {
            return true;
        }

        if (mb_strlen($normalized) <= 2 && !in_array($normalized, self::SHORT_PATTERN_WHITELIST, true)) {
            return true;
        }

        $tokens = preg_split('/[\s,;:\-\/\(\)]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($tokens)) {
            return true;
        }

        foreach ($tokens as $token) {
            if (!in_array($token, self::GENERIC_PATTERNS, true)) {
                return false;
            }
        }

        return true;
    }

    public static function shouldUseForMatching(self $rule): bool
    {
        $pattern = (string) ($rule->normalized_pattern ?: $rule->pattern ?: '');
        if (self::isWeakPattern($pattern)) {
            return false;
        }

        return !self::requiresMoreConfirmations($rule);
    }

    public static function requiresMoreConfirmations(self $rule): bool
    {
        return (string) $rule->source === 'confirmed'
            && (int) $rule->usage_count < self::MIN_CONFIRMED_USAGE_FOR_MATCH;
    }

    public static function getAuditFlags(self $rule): array
    {
        $flags = [];

        if (self::isWeakPattern((string) ($rule->normalized_pattern ?: $rule->pattern ?: ''))) {
            $flags[] = 'weak_pattern';
        }

        if (self::requiresMoreConfirmations($rule)) {
            $flags[] = 'pending_confirmation_threshold';
        }

        return $flags;
    }

    /**
     * Busca regras do usuário que casam com a descrição.
     *
     * @return static|null A regra com maior usage_count que casar
     */
    public static function findMatch(string $description, int $userId, ?string $context = null): ?self
    {
        $normalizedDescription = self::normalize($description);
        $normalizedContext = self::normalize($context ?? '');

        if ($normalizedDescription === '' && $normalizedContext === '') {
            return null;
        }

        // Buscar todas as regras do usuário, ordenadas por usage_count desc
        $rules = static::where('user_id', $userId)
            ->orderByDesc('usage_count')
            ->get();

        $bestRule = null;
        $bestScore = -1;

        foreach ($rules as $rule) {
            if (!self::shouldUseForMatching($rule)) {
                continue;
            }

            $score = self::scoreRuleMatch($rule->normalized_pattern, $normalizedDescription, $normalizedContext, (int) $rule->usage_count);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRule = $rule;
            }
        }

        if ($bestRule === null || $bestScore <= 0) {
            return null;
        }

        $bestRule->setAttribute('_match_score', $bestScore);
        return $bestRule;
    }

    /**
     * Registra ou atualiza uma regra de categorização do usuário.
     * Se o padrão já existe, incrementa usage_count.
     */
    public static function learn(
        int $userId,
        string $pattern,
        int $categoriaId,
        ?int $subcategoriaId = null,
        string $source = 'correction'
    ): self {
        $normalized = self::normalize($pattern);

        $rule = static::where('user_id', $userId)
            ->where('normalized_pattern', $normalized)
            ->first();

        if ($rule === null) {
            return static::create([
                'user_id'            => $userId,
                'pattern'            => $pattern,
                'normalized_pattern' => $normalized,
                'categoria_id'       => $categoriaId,
                'subcategoria_id'    => $subcategoriaId,
                'usage_count'        => 1,
                'source'             => $source,
            ]);
        }

        $currentRank = self::sourceRank((string) $rule->source);
        $incomingRank = self::sourceRank($source);

        $rule->pattern = $pattern;

        if ($incomingRank >= $currentRank) {
            $rule->categoria_id = $categoriaId;
            $rule->subcategoria_id = $subcategoriaId;
            $rule->source = $source;
        }

        $rule->save();
        $rule->increment('usage_count');

        $rule = $rule->fresh() ?? $rule;
        return $rule;
    }

    /**
     * Incrementa usage_count ao confirmar uma sugestão.
     */
    public function confirm(): void
    {
        $this->increment('usage_count');
    }

    private static function sourceRank(string $source): int
    {
        return match ($source) {
            'manual' => 3,
            'correction' => 2,
            'confirmed' => 1,
            default => 0,
        };
    }

    private static function scoreRuleMatch(string $pattern, string $description, string $context, int $usageCount): int
    {
        $score = 0;

        if ($description !== '') {
            $score = max($score, self::scoreAgainstText($pattern, $description, true, $usageCount));
        }

        if ($context !== '') {
            $score = max($score, self::scoreAgainstText($pattern, $context, false, $usageCount));
        }

        return $score;
    }

    private static function scoreAgainstText(string $pattern, string $text, bool $isPrimaryDescription, int $usageCount): int
    {
        if ($pattern === '') {
            return 0;
        }

        $matchedText = null;
        if (str_contains($text, $pattern)) {
            $matchedText = $pattern;
        } elseif (str_contains($pattern, '|') || str_contains($pattern, '\\')) {
            try {
                if (preg_match('/' . $pattern . '/iu', $text, $matches)) {
                    $matchedText = $matches[0] ?? $pattern;
                }
            } catch (\Throwable) {
                return 0;
            }
        }

        if ($matchedText === null) {
            return 0;
        }

        $base = $isPrimaryDescription ? 300 : 90;
        $length = mb_strlen($matchedText);

        return $base + ($usageCount * 10) + ($length * 4);
    }
}
