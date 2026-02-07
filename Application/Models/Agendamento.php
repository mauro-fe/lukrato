<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Agendamento
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $conta_id
 * @property int|null $categoria_id
 * @property string|null $titulo
 * @property string|null $descricao
 * @property string $tipo
 * @property string|null $forma_pagamento
 * @property int|null $valor_centavos
 * @property string|null $moeda
 * @property \Carbon\Carbon|null $data_pagamento
 * @property \Carbon\Carbon|null $proxima_execucao
 * @property int|null $lembrar_antes_segundos
 * @property bool $canal_email
 * @property bool $canal_inapp
 * @property bool $recorrente
 * @property string|null $recorrencia_freq
 * @property int|null $recorrencia_intervalo
 * @property \Carbon\Carbon|null $recorrencia_fim
 * @property bool $eh_parcelado
 * @property int|null $numero_parcelas
 * @property int $parcela_atual
 * @property \Carbon\Carbon|null $notificado_em
 * @property \Carbon\Carbon|null $lembrete_antecedencia_em
 * @property \Carbon\Carbon|null $concluido_em
 * @property string|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Agendamento where(string $column, $value = null)
 * @mixin \Eloquent
 */
class Agendamento extends Model
{
    protected $table = 'agendamentos';

    protected $fillable = [
        'user_id',
        'conta_id',
        'categoria_id',
        'titulo',
        'descricao',
        'tipo',
        'forma_pagamento',
        'valor_centavos',
        'moeda',
        'data_pagamento',
        'proxima_execucao',
        'lembrar_antes_segundos',
        'canal_email',
        'canal_inapp',
        'recorrente',
        'recorrencia_freq',
        'recorrencia_intervalo',
        'recorrencia_fim',
        'eh_parcelado',
        'numero_parcelas',
        'parcela_atual',
        'notificado_em',
        'lembrete_antecedencia_em',
        'concluido_em',
        'status',
    ];

    protected $casts = [
        'data_pagamento'   => 'datetime',
        'proxima_execucao' => 'datetime',
        'recorrencia_fim'  => 'date',
        'notificado_em'    => 'datetime',
        'lembrete_antecedencia_em' => 'datetime',
        'concluido_em'     => 'datetime',
        'canal_email'      => 'bool',
        'canal_inapp'      => 'bool',
        'recorrente'       => 'bool',
        'eh_parcelado'     => 'bool',
        'numero_parcelas'  => 'int',
        'parcela_atual'    => 'int',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}
