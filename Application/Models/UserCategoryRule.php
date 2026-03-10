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
    public static function findMatch(string $description, int $userId): ?self
    {
        $normalized = self::normalize($description);

        if ($normalized === '') {
            return null;
        }

        // Buscar todas as regras do usuário, ordenadas por usage_count desc
        $rules = static::where('user_id', $userId)
            ->orderByDesc('usage_count')
            ->get();

        foreach ($rules as $rule) {
            // Verificar se o padrão normalizado aparece na descrição normalizada
            if (str_contains($normalized, $rule->normalized_pattern)) {
                return $rule;
            }

            // Tentar como regex se o padrão parece ser um
            if (str_contains($rule->normalized_pattern, '|') || str_contains($rule->normalized_pattern, '\\')) {
                try {
                    if (preg_match('/' . $rule->normalized_pattern . '/iu', $normalized)) {
                        return $rule;
                    }
                } catch (\Throwable) {
                    // Padrão regex inválido — ignorar
                }
            }
        }

        return null;
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
}
