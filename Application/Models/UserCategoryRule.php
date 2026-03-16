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

        return $normalized;
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
            $score = self::scoreRuleMatch($rule->normalized_pattern, $normalizedDescription, $normalizedContext, (int) $rule->usage_count);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRule = $rule;
            }
        }

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

        $rule = static::updateOrCreate(
            [
                'user_id'            => $userId,
                'normalized_pattern' => $normalized,
            ],
            [
                'pattern'           => $pattern,
                'categoria_id'      => $categoriaId,
                'subcategoria_id'   => $subcategoriaId,
                'source'            => $source,
            ]
        );

        // Se já existia, incrementar usage_count
        if (!$rule->wasRecentlyCreated) {
            $rule->increment('usage_count');
        }

        return $rule;
    }

    /**
     * Incrementa usage_count ao confirmar uma sugestão.
     */
    public function confirm(): void
    {
        $this->increment('usage_count');
        if ($this->source === 'correction') {
            $this->update(['source' => 'confirmed']);
        }
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
