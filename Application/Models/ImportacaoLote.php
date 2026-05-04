<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ImportacaoItem> $itens
 */
class ImportacaoLote extends Model
{
    protected $table = 'importacao_lotes';

    protected $fillable = [
        'user_id',
        'conta_id',
        'source_type',
        'filename',
        'file_hash',
        'status',
        'total_rows',
        'imported_rows',
        'duplicate_rows',
        'error_rows',
        'error_summary',
        'meta_json',
    ];

    protected $casts = [
        'user_id' => 'int',
        'conta_id' => 'int',
        'total_rows' => 'int',
        'imported_rows' => 'int',
        'duplicate_rows' => 'int',
        'error_rows' => 'int',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    /**
     * @return HasMany<ImportacaoItem, $this>
     */
    public function itens(): HasMany
    {
        return $this->hasMany(ImportacaoItem::class, 'lote_id');
    }
}
