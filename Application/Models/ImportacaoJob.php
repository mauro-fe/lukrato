<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacaoJob extends Model
{
    protected $table = 'importacao_jobs';

    protected $fillable = [
        'user_id',
        'conta_id',
        'cartao_id',
        'source_type',
        'import_target',
        'filename',
        'temp_file_path',
        'status',
        'attempts',
        'started_at',
        'finished_at',
        'total_rows',
        'processed_rows',
        'imported_rows',
        'duplicate_rows',
        'error_rows',
        'result_batch_id',
        'error_summary',
        'meta_json',
    ];

    protected $casts = [
        'user_id' => 'int',
        'conta_id' => 'int',
        'cartao_id' => 'int',
        'attempts' => 'int',
        'total_rows' => 'int',
        'processed_rows' => 'int',
        'imported_rows' => 'int',
        'duplicate_rows' => 'int',
        'error_rows' => 'int',
        'result_batch_id' => 'int',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<ImportacaoLote, $this>
     */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(ImportacaoLote::class, 'result_batch_id');
    }
}
