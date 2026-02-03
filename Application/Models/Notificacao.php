<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Notificacao
 *
 * @property int $id
 * @property int $user_id
 * @property string $tipo
 * @property string $titulo
 * @property string $mensagem
 * @property bool $lida
 * @property string|null $link
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Notificacao where(string $column, $value = null)
 * @mixin \Eloquent
 */
class Notificacao extends Model
{
    protected $table = 'notificacoes';
    protected $fillable = ['user_id', 'tipo', 'titulo', 'mensagem', 'lida', 'link'];
    
    /**
     * Cast para garantir que 'lida' seja sempre integer (0 ou 1)
     */
    protected $casts = [
        'lida' => 'integer',
    ];
}
